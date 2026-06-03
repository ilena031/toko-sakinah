<?php
/**
 * CETAK_BUKTI.PHP — Generate PDF bukti pesanan
 * Menggunakan mPDF + QR code built-in.
 * URL: /toko-sakinah/cetak_bukti.php?id=ID_PESANAN
 */

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/vendor/autoload.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: ' . BASE_URL . 'pelanggan/login.php');
    exit;
}

$user_id    = (int) $_SESSION['user_id'];
$id_pesanan = (int) ($_GET['id'] ?? 0);
$is_admin   = ($_SESSION['user_role'] ?? '') === 'admin';

if (!$id_pesanan) {
    die('ID pesanan tidak valid.');
}

// Ambil pesanan + cek kepemilikan (admin bisa cetak semua)
$where  = $is_admin ? 'p.id_pesanan = ?' : 'p.id_pesanan = ? AND p.user_id = ?';
$bind   = $is_admin ? 'i' : 'ii';
$params = $is_admin ? [$id_pesanan] : [$id_pesanan, $user_id];

$sql  = "SELECT p.*, u.nama AS user_nama, u.email AS user_email, u.no_hp AS user_hp
         FROM pesanan p JOIN users u ON p.user_id = u.id WHERE $where";
$stmt = $conn->prepare($sql);
$stmt->bind_param($bind, ...$params);
$stmt->execute();
$pesanan = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$pesanan) {
    die('Pesanan tidak ditemukan.');
}

// Ambil items
$stmt = $conn->prepare("SELECT * FROM detail_pesanan WHERE id_pesanan = ?");
$stmt->bind_param('i', $id_pesanan);
$stmt->execute();
$items = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Format alamat
$alamat_lines = explode("\n", $pesanan['alamat_kirim']);

// QR berisi URL status pesanan (untuk track)
$qr_data = BASE_URL . 'pelanggan/pembayaran_status.php?id=' . $id_pesanan;

// Build HTML PDF
$status_color = [
    'Menunggu'      => '#E65100',
    'Dikonfirmasi'  => '#2E7D32',
    'Diproses'      => '#1976D2',
    'Dikirim'       => '#1976D2',
    'Selesai'       => '#2E7D32',
    'Dibatalkan'    => '#C62828',
][$pesanan['status']] ?? '#616161';

$html = '
<style>
  body { font-family: sans-serif; color: #333; font-size: 11pt; }
  .header { border-bottom: 3px solid #E91E63; padding-bottom: 12px; margin-bottom: 18px; }
  .header table { width: 100%; }
  .brand-name { font-size: 22pt; font-weight: bold; color: #E91E63; }
  .brand-sub  { font-size: 10pt; color: #777; }
  .doc-title  { text-align: right; font-size: 18pt; font-weight: bold; color: #333; }
  .doc-no     { text-align: right; color: #666; font-size: 10pt; }

  .info-grid { width: 100%; margin-bottom: 14px; }
  .info-grid td { padding: 4px 6px; font-size: 10pt; vertical-align: top; }
  .info-grid .label { color: #777; width: 35%; }
  .info-grid .val   { color: #333; font-weight: bold; }

  .table-items { width: 100%; border-collapse: collapse; margin-bottom: 14px; }
  .table-items th { background: #FCE4EC; color: #C2185B; padding: 8px 10px; font-size: 10pt; text-align: left; }
  .table-items td { padding: 8px 10px; border-bottom: 1px solid #E0E0E0; font-size: 10pt; }
  .table-items .right { text-align: right; }
  .table-items .center { text-align: center; }

  .totals { width: 50%; margin-left: 50%; }
  .totals td { padding: 5px 10px; font-size: 11pt; }
  .totals .label { color: #777; }
  .totals .val   { text-align: right; }
  .totals .grand { background: #FCE4EC; font-weight: bold; color: #C2185B; font-size: 13pt; }

  .footer-note { margin-top: 24px; padding-top: 14px; border-top: 1px solid #E0E0E0; color: #777; font-size: 9pt; text-align: center; }
  .status-pill {
    display: inline-block; padding: 4px 14px; border-radius: 12px;
    background: ' . $status_color . '; color: #fff;
    font-weight: bold; font-size: 10pt;
  }
  .alamat-box {
    background: #FAFAFA; padding: 10px 14px; border-radius: 6px;
    font-size: 10pt; line-height: 1.6;
  }
</style>

<div class="header">
  <table>
    <tr>
      <td>
        <div class="brand-name">Toko Sakinah</div>
        <div class="brand-sub">Pusat baju sekolah, perlengkapan haji & oleh-oleh haji</div>
        <div class="brand-sub">Lr. Basah Permai, Palembang · 0813-7374-1040</div>
      </td>
      <td>
        <div class="doc-title">BUKTI PESANAN</div>
        <div class="doc-no">No: ' . htmlspecialchars($pesanan['no_pesanan']) . '</div>
        <div class="doc-no">Tanggal: ' . date('d M Y H:i', strtotime($pesanan['created_at'])) . ' WIB</div>
      </td>
    </tr>
  </table>
</div>

<table class="info-grid" cellpadding="0" cellspacing="0">
  <tr>
    <td colspan="2"><strong style="color:#C2185B;">DETAIL PELANGGAN</strong></td>
    <td colspan="2"><strong style="color:#C2185B;">DETAIL PESANAN</strong></td>
  </tr>
  <tr>
    <td class="label">Nama</td>
    <td class="val">' . htmlspecialchars($pesanan['user_nama']) . '</td>
    <td class="label">Status</td>
    <td class="val"><span class="status-pill">' . htmlspecialchars($pesanan['status']) . '</span></td>
  </tr>
  <tr>
    <td class="label">Email</td>
    <td class="val">' . htmlspecialchars($pesanan['user_email']) . '</td>
    <td class="label">Metode Bayar</td>
    <td class="val">' . htmlspecialchars(strtoupper($pesanan['metode_bayar'] ?: '-')) . '</td>
  </tr>
  <tr>
    <td class="label">HP</td>
    <td class="val">' . htmlspecialchars($pesanan['user_hp']) . '</td>
    <td class="label">Ekspedisi</td>
    <td class="val">' . htmlspecialchars($pesanan['metode_pengiriman']) . '</td>
  </tr>
  <tr>
    <td class="label">No. Resi</td>
    <td class="val">' . htmlspecialchars($pesanan['no_resi'] ?: '-') . '</td>
    <td class="label">Midtrans ID</td>
    <td class="val">' . htmlspecialchars($pesanan['midtrans_order_id'] ?: '-') . '</td>
  </tr>
</table>

<div style="margin-bottom:8px;"><strong style="color:#C2185B;">ALAMAT PENGIRIMAN</strong></div>
<div class="alamat-box">' . nl2br(htmlspecialchars($pesanan['alamat_kirim'])) . '</div>

<h4 style="color:#C2185B;margin-top:18px;margin-bottom:8px;">Detail Item</h4>
<table class="table-items">
  <thead>
    <tr>
      <th width="8%" class="center">#</th>
      <th width="45%">Produk</th>
      <th width="10%" class="center">Ukuran</th>
      <th width="8%" class="center">Qty</th>
      <th width="14%" class="right">Harga</th>
      <th width="15%" class="right">Subtotal</th>
    </tr>
  </thead>
  <tbody>';

$no = 1;
foreach ($items as $it) {
    $html .= '
    <tr>
      <td class="center">' . $no++ . '</td>
      <td>' . htmlspecialchars($it['nama_produk']) . '</td>
      <td class="center">' . htmlspecialchars($it['ukuran']) . '</td>
      <td class="center">' . (int) $it['jumlah'] . '</td>
      <td class="right">Rp ' . number_format($it['harga_satuan'], 0, ',', '.') . '</td>
      <td class="right">Rp ' . number_format($it['subtotal'], 0, ',', '.') . '</td>
    </tr>';
}

$subtotal_produk = $pesanan['total_harga'] - $pesanan['ongkir'];
$html .= '
  </tbody>
</table>

<table class="totals">
  <tr>
    <td class="label">Subtotal</td>
    <td class="val">Rp ' . number_format($subtotal_produk, 0, ',', '.') . '</td>
  </tr>
  <tr>
    <td class="label">Ongkos Kirim</td>
    <td class="val">Rp ' . number_format($pesanan['ongkir'], 0, ',', '.') . '</td>
  </tr>
  <tr class="grand">
    <td>TOTAL BAYAR</td>
    <td class="val">Rp ' . number_format($pesanan['total_harga'], 0, ',', '.') . '</td>
  </tr>
</table>

<table style="width:100%;margin-top:30px;">
  <tr>
    <td style="width:40%;text-align:center;vertical-align:top;">
      <barcode code="' . htmlspecialchars($qr_data) . '" type="QR" class="barcode" size="0.7" error="M" />
      <div style="font-size:9pt;color:#777;margin-top:6px;">Scan untuk cek status</div>
    </td>
    <td style="vertical-align:top;font-size:10pt;color:#777;line-height:1.6;">
      <strong style="color:#333;">Terima kasih telah berbelanja di Toko Sakinah!</strong><br>
      Hubungi kami via WhatsApp 0813-7374-1040 jika ada pertanyaan tentang pesanan ini.
      <br><br>
      <em>Dokumen ini dibuat otomatis oleh sistem.<br>Dicetak pada ' . date('d M Y H:i') . ' WIB.</em>
    </td>
  </tr>
</table>

<div class="footer-note">
  Toko Sakinah Online · ' . BASE_URL . ' · ' . date('Y') . '
</div>';

// ── Render ke PDF ─────────────────────────────────────────
try {
    $tmpDir = __DIR__ . '/uploads/tmp_mpdf';
    if (!is_dir($tmpDir)) @mkdir($tmpDir, 0775, true);

    $mpdf = new \Mpdf\Mpdf([
        'mode'        => 'utf-8',
        'format'      => 'A4',
        'orientation' => 'P',
        'margin_top'    => 15,
        'margin_bottom' => 15,
        'margin_left'   => 14,
        'margin_right'  => 14,
        'tempDir'     => $tmpDir,
    ]);

    $mpdf->SetTitle('Bukti Pesanan ' . $pesanan['no_pesanan']);
    $mpdf->SetAuthor('Toko Sakinah');
    $mpdf->WriteHTML($html);

    $filename = 'Bukti-' . $pesanan['no_pesanan'] . '.pdf';
    $mode = ($_GET['mode'] ?? 'I') === 'D' ? 'D' : 'I';   // I = inline, D = download
    $mpdf->Output($filename, $mode);
} catch (Exception $e) {
    die('Gagal generate PDF: ' . $e->getMessage());
}
