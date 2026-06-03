<?php
/**
 * MIDTRANS_WEBHOOK.PHP — Endpoint webhook (notification handler) Midtrans
 * Dipanggil oleh server Midtrans tiap kali status pembayaran berubah.
 *
 * Verifikasi: SHA-512(order_id + status_code + gross_amount + server_key)
 * Update status pesanan & log untuk debug.
 *
 * URL untuk Midtrans Dashboard: https://YOUR_DOMAIN/midtrans_webhook.php
 * Lokal? pakai ngrok: `ngrok http 8888` lalu paste URL ngrok-nya ke Midtrans dashboard.
 */

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/vendor/autoload.php';

use Midtrans\Config as MidtransConfig;
use Midtrans\Notification;

// Log helper
function wh_log(string $msg): void {
    $dir = __DIR__ . '/uploads/logs';
    if (!is_dir($dir)) @mkdir($dir, 0775, true);
    @file_put_contents(
        $dir . '/midtrans_webhook.log',
        '[' . date('Y-m-d H:i:s') . '] ' . $msg . PHP_EOL,
        FILE_APPEND
    );
}

header('Content-Type: application/json');

MidtransConfig::$serverKey    = MIDTRANS_SERVER_KEY;
MidtransConfig::$isProduction = MIDTRANS_IS_PRODUCTION;

try {
    $notif = new Notification();
} catch (Exception $e) {
    wh_log('PARSE ERROR: ' . $e->getMessage());
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Invalid notification']);
    exit;
}

$order_id        = $notif->order_id          ?? '';
$status_code     = $notif->status_code       ?? '';
$gross_amount    = $notif->gross_amount      ?? '';
$signature_key   = $notif->signature_key     ?? '';
$trx_status      = $notif->transaction_status?? '';
$payment_type    = $notif->payment_type      ?? '';
$fraud_status    = $notif->fraud_status      ?? '';

wh_log("RECV order={$order_id} trx={$trx_status} pay={$payment_type} fraud={$fraud_status}");

// ── Verifikasi signature ───────────────────────────────────
$expected = hash('sha512', $order_id . $status_code . $gross_amount . MIDTRANS_SERVER_KEY);
if (!hash_equals($expected, $signature_key)) {
    wh_log("SIGNATURE MISMATCH for order={$order_id}");
    http_response_code(403);
    echo json_encode(['status' => 'error', 'message' => 'Invalid signature']);
    exit;
}

// ── Cari pesanan ────────────────────────────────────────────
$stmt = $conn->prepare(
    "SELECT id_pesanan, status FROM pesanan
     WHERE midtrans_order_id = ? OR no_pesanan = ?
     LIMIT 1"
);
$stmt->bind_param('ss', $order_id, $order_id);
$stmt->execute();
$pesanan = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$pesanan) {
    wh_log("PESANAN NOT FOUND: {$order_id}");
    http_response_code(404);
    echo json_encode(['status' => 'error', 'message' => 'Pesanan tidak ditemukan']);
    exit;
}
$id_pesanan = (int) $pesanan['id_pesanan'];

// ── Map Midtrans status → status internal ──────────────────
$new_status = $pesanan['status'];
switch ($trx_status) {
    case 'capture':
        // Untuk kartu kredit: cek fraud
        if ($fraud_status === 'accept') {
            $new_status = 'Dikonfirmasi';
        } elseif ($fraud_status === 'challenge') {
            $new_status = 'Menunggu';
        } else {
            $new_status = 'Dibatalkan';
        }
        break;
    case 'settlement':
        $new_status = 'Dikonfirmasi';
        break;
    case 'pending':
        $new_status = 'Menunggu';
        break;
    case 'deny':
    case 'expire':
    case 'cancel':
    case 'failure':
        $new_status = 'Dibatalkan';
        break;
    case 'refund':
    case 'partial_refund':
    case 'chargeback':
        $new_status = 'Dibatalkan';
        break;
}

// ── Rollback stok kalau dibatalkan & belum pernah dibatalkan ───
$rollback_stok = false;
if ($new_status === 'Dibatalkan' && $pesanan['status'] !== 'Dibatalkan') {
    $rollback_stok = true;
}

// ── Update DB ───────────────────────────────────────────────
$conn->begin_transaction();
try {
    $stmt = $conn->prepare(
        "UPDATE pesanan
         SET status = ?, midtrans_status = ?, metode_bayar = ?
         WHERE id_pesanan = ?"
    );
    $stmt->bind_param('sssi', $new_status, $trx_status, $payment_type, $id_pesanan);
    $stmt->execute();
    $stmt->close();

    if ($rollback_stok) {
        // Kembalikan stok per item ke produk.stock_by_size
        $stmt = $conn->prepare(
            "SELECT dp.id_produk, dp.ukuran, dp.jumlah, p.stock_by_size
             FROM detail_pesanan dp
             JOIN produk p ON dp.id_produk = p.id_produk
             WHERE dp.id_pesanan = ?"
        );
        $stmt->bind_param('i', $id_pesanan);
        $stmt->execute();
        $items = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();

        $upd = $conn->prepare("UPDATE produk SET stock_by_size = ? WHERE id_produk = ?");
        foreach ($items as $it) {
            $stock = json_decode($it['stock_by_size'], true) ?: [];
            $stock[$it['ukuran']] = ($stock[$it['ukuran']] ?? 0) + (int) $it['jumlah'];
            $stock_json = json_encode($stock);
            $upd->bind_param('si', $stock_json, $it['id_produk']);
            $upd->execute();
        }
        $upd->close();
        wh_log("STOK ROLLBACK for pesanan_id={$id_pesanan}");
    }

    $conn->commit();
    wh_log("OK order={$order_id} status={$new_status}");
    echo json_encode(['status' => 'ok', 'order_id' => $order_id, 'new_status' => $new_status]);
} catch (Exception $e) {
    $conn->rollback();
    wh_log('UPDATE ERROR: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'DB error']);
}
