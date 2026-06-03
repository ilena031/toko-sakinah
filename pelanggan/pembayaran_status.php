<?php
/**
 * PEMBAYARAN_STATUS.PHP — Halaman status setelah klik bayar
 * Bisa muncul karena: callback Midtrans (success/pending), atau diakses manual.
 * Sinkron ulang status dari Midtrans API untuk jaga-jaga webhook telat.
 */

require_once __DIR__ . '/../config/config.php';
require __DIR__ . '/auth_check.php';
require_once __DIR__ . '/../vendor/autoload.php';

use Midtrans\Config as MidtransConfig;
use Midtrans\Transaction;

$page_title  = 'Status Pembayaran — Toko Sakinah';
$active_page = 'profil';
$extra_css   = 'checkout.css';

$id_pesanan = (int) ($_GET['id'] ?? 0);
$user_id    = (int) $_SESSION['user_id'];

if (!$id_pesanan) {
    header('Location: ' . BASE_URL . 'pelanggan/dashboard.php?tab=pesanan');
    exit;
}

// Ambil pesanan
$stmt = $conn->prepare(
    "SELECT * FROM pesanan WHERE id_pesanan = ? AND user_id = ?"
);
$stmt->bind_param('ii', $id_pesanan, $user_id);
$stmt->execute();
$pesanan = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$pesanan) {
    header('Location: ' . BASE_URL . 'pelanggan/dashboard.php?tab=pesanan');
    exit;
}

// ── Sync status dari Midtrans API (fallback kalau webhook telat/belum aktif) ──
if ($pesanan['midtrans_order_id'] && $pesanan['status'] === 'Menunggu') {
    MidtransConfig::$serverKey    = MIDTRANS_SERVER_KEY;
    MidtransConfig::$isProduction = MIDTRANS_IS_PRODUCTION;
    try {
        $mstatus = Transaction::status($pesanan['midtrans_order_id']);
        $trx_status   = is_object($mstatus) ? ($mstatus->transaction_status ?? '') : '';
        $payment_type = is_object($mstatus) ? ($mstatus->payment_type ?? '') : '';
        $fraud_status = is_object($mstatus) ? ($mstatus->fraud_status ?? '') : '';

        $new_status = $pesanan['status'];
        if ($trx_status === 'capture' && $fraud_status === 'accept')       $new_status = 'Dikonfirmasi';
        elseif ($trx_status === 'settlement')                              $new_status = 'Dikonfirmasi';
        elseif (in_array($trx_status, ['deny','expire','cancel','failure'], true)) $new_status = 'Dibatalkan';

        if ($new_status !== $pesanan['status']) {
            $stmt = $conn->prepare("UPDATE pesanan SET status=?, midtrans_status=?, metode_bayar=? WHERE id_pesanan=?");
            $stmt->bind_param('sssi', $new_status, $trx_status, $payment_type, $id_pesanan);
            $stmt->execute();
            $stmt->close();
            $pesanan['status']          = $new_status;
            $pesanan['midtrans_status'] = $trx_status;
            $pesanan['metode_bayar']    = $payment_type;
        }
    } catch (Exception $e) {
        error_log('[STATUS SYNC] ' . $e->getMessage());
    }
}

// Map status ke tampilan
$status_meta = [
    'Menunggu'      => ['icon' => 'bi-hourglass-split', 'color' => 'orange', 'title' => 'Menunggu Pembayaran',
                        'desc' => 'Pembayaran kamu masih menunggu konfirmasi. Selesaikan pembayaran melalui metode yang dipilih.'],
    'Dikonfirmasi'  => ['icon' => 'bi-check-circle-fill', 'color' => 'green', 'title' => 'Pembayaran Berhasil!',
                        'desc' => 'Terima kasih! Pembayaran kamu sudah kami terima. Pesanan akan segera diproses.'],
    'Diproses'      => ['icon' => 'bi-box-seam', 'color' => 'blue', 'title' => 'Pesanan Diproses',
                        'desc' => 'Pesanan kamu sedang kami siapkan.'],
    'Dikirim'       => ['icon' => 'bi-truck', 'color' => 'blue', 'title' => 'Pesanan Dikirim',
                        'desc' => 'Pesanan kamu sedang dalam perjalanan.'],
    'Selesai'       => ['icon' => 'bi-bag-check-fill', 'color' => 'green', 'title' => 'Pesanan Selesai',
                        'desc' => 'Pesanan sudah diterima. Terima kasih telah berbelanja di Toko Sakinah!'],
    'Dibatalkan'    => ['icon' => 'bi-x-circle-fill', 'color' => 'red', 'title' => 'Pesanan Dibatalkan',
                        'desc' => 'Pembayaran dibatalkan atau kadaluarsa. Stok produk sudah dikembalikan.'],
];
$meta = $status_meta[$pesanan['status']] ?? $status_meta['Menunggu'];

require __DIR__ . '/../templates/header.php';
require __DIR__ . '/../templates/navbar.php';
?>

<style>
.pay-status-card {
  background: var(--white);
  border-radius: var(--radius-lg);
  box-shadow: var(--shadow-card);
  padding: 40px 30px;
  text-align: center;
  max-width: 560px;
  margin: 30px auto;
}
.pay-status-icon {
  width: 88px; height: 88px;
  border-radius: 50%;
  display: flex; align-items: center; justify-content: center;
  margin: 0 auto 18px;
  font-size: 42px;
}
.pay-status-icon.green  { background: #E8F5E9; color: var(--green-success); }
.pay-status-icon.orange { background: #FFF3E0; color: #E65100; }
.pay-status-icon.red    { background: #FFEBEE; color: var(--red-badge); }
.pay-status-icon.blue   { background: #E3F2FD; color: #1976D2; }
.pay-status-title {
  font-family: var(--font-heading);
  font-weight: 700;
  font-size: 22px;
  color: var(--gray-900);
  margin-bottom: 8px;
}
.pay-status-desc {
  font-size: 13.5px;
  color: var(--gray-700);
  margin-bottom: 20px;
  line-height: 1.6;
}
.pay-status-detail {
  background: var(--gray-100);
  border-radius: var(--radius-md);
  padding: 14px 18px;
  margin: 18px 0;
  text-align: left;
}
.pay-status-detail .row-detail {
  display: flex;
  justify-content: space-between;
  font-size: 12.5px;
  color: var(--gray-700);
  padding: 4px 0;
}
.pay-status-detail strong {
  color: var(--gray-900);
  font-weight: 600;
}
.pay-status-actions {
  display: flex;
  gap: 10px;
  justify-content: center;
  flex-wrap: wrap;
  margin-top: 20px;
}
.pay-status-actions a {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  padding: 11px 22px;
  border-radius: var(--radius-md);
  font-family: var(--font-heading);
  font-weight: 600;
  font-size: 13px;
  text-decoration: none;
  transition: var(--transition);
}
.pay-status-actions .btn-primary-pink {
  background: var(--pink-primary);
  color: var(--white);
}
.pay-status-actions .btn-primary-pink:hover {
  background: var(--pink-dark);
}
.pay-status-actions .btn-outline-pink {
  background: var(--white);
  color: var(--pink-primary);
  border: 1.5px solid var(--pink-primary);
}
.pay-status-actions .btn-outline-pink:hover {
  background: var(--pink-light);
}
</style>

<main class="page-content checkout-wrapper">
  <div class="container">

    <!-- Steps -->
    <div class="checkout-steps">
      <div class="step done"><span>1</span>Keranjang</div>
      <div class="step-line"></div>
      <div class="step done"><span>2</span>Checkout</div>
      <div class="step-line"></div>
      <div class="step done"><span>3</span>Pembayaran</div>
      <div class="step-line"></div>
      <div class="step <?= in_array($pesanan['status'], ['Dikonfirmasi','Selesai','Dikirim','Diproses'], true) ? 'active' : '' ?>">
        <span>4</span>Selesai
      </div>
    </div>

    <div class="pay-status-card">
      <div class="pay-status-icon <?= $meta['color'] ?>">
        <i class="bi <?= $meta['icon'] ?>"></i>
      </div>
      <h2 class="pay-status-title"><?= htmlspecialchars($meta['title']) ?></h2>
      <p class="pay-status-desc"><?= htmlspecialchars($meta['desc']) ?></p>

      <div class="pay-status-detail">
        <div class="row-detail">
          <span>No. Pesanan</span>
          <strong><?= htmlspecialchars($pesanan['no_pesanan']) ?></strong>
        </div>
        <div class="row-detail">
          <span>Total Bayar</span>
          <strong>Rp <?= number_format($pesanan['total_harga'], 0, ',', '.') ?></strong>
        </div>
        <?php if ($pesanan['metode_bayar']): ?>
          <div class="row-detail">
            <span>Metode</span>
            <strong><?= htmlspecialchars(strtoupper($pesanan['metode_bayar'])) ?></strong>
          </div>
        <?php endif; ?>
        <div class="row-detail">
          <span>Tanggal</span>
          <span><?= date('d M Y H:i', strtotime($pesanan['created_at'])) ?> WIB</span>
        </div>
      </div>

      <div class="pay-status-actions">
        <?php if ($pesanan['status'] === 'Menunggu'): ?>
          <a href="<?= BASE_URL ?>pelanggan/pembayaran.php?id=<?= $id_pesanan ?>" class="btn-primary-pink">
            <i class="bi bi-credit-card-fill"></i> Lanjutkan Pembayaran
          </a>
        <?php endif; ?>
        <a href="<?= BASE_URL ?>pelanggan/dashboard.php?tab=pesanan" class="btn-outline-pink">
          <i class="bi bi-list-ul"></i> Lihat Pesanan Saya
        </a>
        <a href="<?= BASE_URL ?>katalog.php" class="btn-outline-pink">
          <i class="bi bi-shop"></i> Belanja Lagi
        </a>
      </div>
    </div>

  </div>
</main>

<?php require __DIR__ . '/../templates/footer.php'; ?>
