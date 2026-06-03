<?php
/**
 * PEMBAYARAN.PHP — Halaman Pembayaran (Midtrans Snap)
 * Generate Snap Token & tampilkan popup pembayaran Midtrans.
 */

require_once __DIR__ . '/../config/config.php';
require __DIR__ . '/auth_check.php';
require_once __DIR__ . '/../vendor/autoload.php';

use Midtrans\Config as MidtransConfig;
use Midtrans\Snap;

$page_title  = 'Pembayaran — Toko Sakinah';
$active_page = 'keranjang';
$extra_css   = 'checkout.css';

$id_pesanan = (int) ($_GET['id'] ?? $_SESSION['last_order_id'] ?? 0);
$user_id    = (int) $_SESSION['user_id'];

if (!$id_pesanan) {
    $_SESSION['flash_error'] = 'Pesanan tidak ditemukan.';
    header('Location: ' . BASE_URL . 'pelanggan/dashboard.php?tab=pesanan');
    exit;
}

// ── Ambil pesanan & validasi kepemilikan ───────────────────
$stmt = $conn->prepare(
    "SELECT p.*, u.nama, u.email, u.no_hp
     FROM pesanan p
     JOIN users u ON p.user_id = u.id
     WHERE p.id_pesanan = ? AND p.user_id = ?"
);
$stmt->bind_param('ii', $id_pesanan, $user_id);
$stmt->execute();
$pesanan = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$pesanan) {
    $_SESSION['flash_error'] = 'Pesanan tidak ditemukan atau bukan milik Anda.';
    header('Location: ' . BASE_URL . 'pelanggan/dashboard.php?tab=pesanan');
    exit;
}

// Kalau sudah dibayar/diproses, redirect ke status
if (!in_array($pesanan['status'], ['Menunggu'], true)) {
    header('Location: ' . BASE_URL . 'pelanggan/pembayaran_status.php?id=' . $id_pesanan);
    exit;
}

// ── Ambil detail items ─────────────────────────────────────
$stmt = $conn->prepare("SELECT * FROM detail_pesanan WHERE id_pesanan = ?");
$stmt->bind_param('i', $id_pesanan);
$stmt->execute();
$items = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// ── Konfigurasi Midtrans ───────────────────────────────────
MidtransConfig::$serverKey    = MIDTRANS_SERVER_KEY;
MidtransConfig::$isProduction = MIDTRANS_IS_PRODUCTION;
MidtransConfig::$isSanitized  = true;
MidtransConfig::$is3ds        = true;

// Gunakan no_pesanan sebagai order_id Midtrans (unik). Tambah suffix kalau retry.
$mid_order_id = $pesanan['midtrans_order_id'];
if (empty($mid_order_id)) {
    $mid_order_id = $pesanan['no_pesanan'];
    $stmt = $conn->prepare("UPDATE pesanan SET midtrans_order_id = ? WHERE id_pesanan = ?");
    $stmt->bind_param('si', $mid_order_id, $id_pesanan);
    $stmt->execute();
    $stmt->close();
}

// ── Build Snap params ──────────────────────────────────────
$item_details = [];
foreach ($items as $it) {
    $item_details[] = [
        'id'       => (string) $it['id_produk'],
        'price'    => (int) $it['harga_satuan'],
        'quantity' => (int) $it['jumlah'],
        'name'     => mb_substr($it['nama_produk'] . ' (' . $it['ukuran'] . ')', 0, 50),
    ];
}
// Tambahin ongkir sebagai item terpisah
if ($pesanan['ongkir'] > 0) {
    $item_details[] = [
        'id'       => 'ONGKIR',
        'price'    => (int) $pesanan['ongkir'],
        'quantity' => 1,
        'name'     => 'Ongkos Kirim (' . $pesanan['metode_pengiriman'] . ')',
    ];
}

// Parse alamat (format: nama\nhp\nalamat\n\nCatatan: ...)
$alamat_lines = explode("\n", $pesanan['alamat_kirim']);
$penerima_nama   = trim($alamat_lines[0] ?? $pesanan['nama']);
$penerima_hp     = trim($alamat_lines[1] ?? $pesanan['no_hp']);
$penerima_alamat = trim($alamat_lines[2] ?? '');

$params = [
    'transaction_details' => [
        'order_id'     => $mid_order_id,
        'gross_amount' => (int) $pesanan['total_harga'],
    ],
    'item_details' => $item_details,
    'customer_details' => [
        'first_name' => mb_substr($penerima_nama, 0, 50),
        'email'      => $pesanan['email'],
        'phone'      => $penerima_hp,
        'shipping_address' => [
            'first_name' => mb_substr($penerima_nama, 0, 50),
            'phone'      => $penerima_hp,
            'address'    => mb_substr($penerima_alamat, 0, 200),
            'country_code' => 'IDN',
        ],
    ],
    'callbacks' => [
        'finish' => BASE_URL . 'pelanggan/pembayaran_status.php?id=' . $id_pesanan,
    ],
];

// ── Generate Snap Token ────────────────────────────────────
$snap_error = '';
$snap_token = '';
try {
    $snap_token = Snap::getSnapToken($params);
} catch (Exception $e) {
    $snap_error = $e->getMessage();
    error_log('[MIDTRANS] Snap token error: ' . $snap_error);
}

require __DIR__ . '/../templates/header.php';
require __DIR__ . '/../templates/navbar.php';
?>

<main class="page-content checkout-wrapper">
  <div class="container">

    <!-- Steps -->
    <div class="checkout-steps">
      <div class="step done"><span>1</span>Keranjang</div>
      <div class="step-line"></div>
      <div class="step done"><span>2</span>Checkout</div>
      <div class="step-line"></div>
      <div class="step active"><span>3</span>Pembayaran</div>
      <div class="step-line"></div>
      <div class="step"><span>4</span>Selesai</div>
    </div>

    <?php if ($snap_error): ?>
      <div class="cart-alert alert-danger">
        <i class="bi bi-exclamation-circle"></i>
        Gagal menyiapkan pembayaran: <?= htmlspecialchars($snap_error) ?>
        <br><small>Cek SERVER_KEY Midtrans di <code>config/config.php</code>.</small>
      </div>
    <?php endif; ?>

    <div class="row g-4 justify-content-center">
      <div class="col-lg-7">
        <div class="checkout-card">
          <div class="checkout-card-header">
            <h5><i class="bi bi-credit-card-2-front-fill text-pink me-2"></i>Pembayaran Pesanan</h5>
          </div>
          <div class="checkout-card-body">
            <div class="pay-order-info">
              <div class="pay-info-row">
                <span>Nomor Pesanan</span>
                <strong><?= htmlspecialchars($pesanan['no_pesanan']) ?></strong>
              </div>
              <div class="pay-info-row">
                <span>Tanggal</span>
                <span><?= date('d M Y H:i', strtotime($pesanan['created_at'])) ?> WIB</span>
              </div>
              <div class="pay-info-row">
                <span>Status</span>
                <span style="background:#FFF3E0;color:#E65100;padding:4px 10px;border-radius:12px;font-size:11.5px;font-weight:600;">
                  <i class="bi bi-hourglass-split"></i> Menunggu Pembayaran
                </span>
              </div>
            </div>

            <hr>

            <h6 style="font-family:var(--font-heading);font-weight:600;font-size:13.5px;margin:14px 0 10px;">
              Detail Item
            </h6>
            <?php foreach ($items as $it): ?>
              <div class="checkout-item" style="border:none;padding:8px 0;">
                <div></div>
                <div class="checkout-item-info">
                  <div class="checkout-item-name"><?= htmlspecialchars($it['nama_produk']) ?></div>
                  <div class="checkout-item-meta">
                    Ukuran <strong><?= htmlspecialchars($it['ukuran']) ?></strong>
                    · <?= $it['jumlah'] ?> × Rp <?= number_format($it['harga_satuan'], 0, ',', '.') ?>
                  </div>
                </div>
                <div class="checkout-item-subtotal">
                  Rp <?= number_format($it['subtotal'], 0, ',', '.') ?>
                </div>
              </div>
            <?php endforeach; ?>

            <hr>
            <div class="summary-row">
              <span>Subtotal</span>
              <span>Rp <?= number_format($pesanan['total_harga'] - $pesanan['ongkir'], 0, ',', '.') ?></span>
            </div>
            <div class="summary-row">
              <span>Ongkos Kirim (<?= htmlspecialchars($pesanan['metode_pengiriman']) ?>)</span>
              <span><?= $pesanan['ongkir'] > 0 ? 'Rp ' . number_format($pesanan['ongkir'], 0, ',', '.') : 'GRATIS' ?></span>
            </div>
            <hr>
            <div class="summary-row summary-total">
              <span>Total Bayar</span>
              <span>Rp <?= number_format($pesanan['total_harga'], 0, ',', '.') ?></span>
            </div>

            <?php if ($snap_token): ?>
              <button type="button" class="btn-checkout-submit" id="btn-pay-now">
                <i class="bi bi-credit-card-fill me-2"></i>Bayar Sekarang
              </button>
              <div class="summary-secure">
                <i class="bi bi-shield-check"></i>
                Dialihkan ke Midtrans Snap (GoPay, QRIS, VA, Kartu Kredit)
              </div>
            <?php endif; ?>

            <a href="<?= BASE_URL ?>pelanggan/dashboard.php?tab=pesanan" class="btn-back-cart">
              Bayar nanti — kembali ke dashboard
            </a>
          </div>
        </div>
      </div>
    </div>

  </div>
</main>

<?php if ($snap_token): ?>
<!-- Midtrans Snap.js -->
<script src="https://app.<?= MIDTRANS_IS_PRODUCTION ? '' : 'sandbox.' ?>midtrans.com/snap/snap.js"
        data-client-key="<?= MIDTRANS_CLIENT_KEY ?>"></script>
<script>
  document.getElementById('btn-pay-now').addEventListener('click', function () {
    const btn = this;
    btn.disabled = true;
    const original = btn.innerHTML;
    btn.innerHTML = '<i class="bi bi-arrow-clockwise spinning me-2"></i>Membuka Midtrans...';

    snap.pay('<?= $snap_token ?>', {
      onSuccess: function (result) {
        window.location.href = '<?= BASE_URL ?>pelanggan/pembayaran_status.php?id=<?= $id_pesanan ?>&status=success';
      },
      onPending: function (result) {
        window.location.href = '<?= BASE_URL ?>pelanggan/pembayaran_status.php?id=<?= $id_pesanan ?>&status=pending';
      },
      onError: function (result) {
        alert('Pembayaran gagal. Coba lagi atau pilih metode lain.');
        btn.disabled = false;
        btn.innerHTML = original;
      },
      onClose: function () {
        btn.disabled = false;
        btn.innerHTML = original;
      }
    });
  });
</script>
<?php endif; ?>

<?php require __DIR__ . '/../templates/footer.php'; ?>
