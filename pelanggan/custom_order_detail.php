<?php
/**
 * CUSTOM_ORDER_DETAIL.PHP — Detail + chat konsultasi
 * Polling AJAX tiap 5 detik untuk pesan baru dari admin.
 */

require_once __DIR__ . '/../config/config.php';
require __DIR__ . '/auth_check.php';

$page_title  = 'Detail Custom Order — Toko Sakinah';
$active_page = 'profil';
$extra_css   = 'custom_order.css';
$extra_js    = 'custom_order.js';

$user_id   = (int) $_SESSION['user_id'];
$id_custom = (int) ($_GET['id'] ?? 0);

if (!$id_custom) {
    header('Location: ' . BASE_URL . 'pelanggan/custom_order.php');
    exit;
}

// Ambil custom order
$stmt = $conn->prepare("SELECT * FROM custom_order WHERE id_custom = ? AND user_id = ?");
$stmt->bind_param('ii', $id_custom, $user_id);
$stmt->execute();
$co = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$co) {
    header('Location: ' . BASE_URL . 'pelanggan/custom_order.php');
    exit;
}

// Status meta
$status_map = [
    'Menunggu Review'   => ['#FFF3E0', '#E65100', 'bi-hourglass-split'],
    'Dalam Diskusi'     => ['#E3F2FD', '#1976D2', 'bi-chat-dots'],
    'Penawaran Dikirim' => ['#FFF8E1', '#F57F17', 'bi-receipt'],
    'Disetujui'         => ['#E8F5E9', '#2E7D32', 'bi-check-circle'],
    'Dalam Produksi'    => ['#E3F2FD', '#1976D2', 'bi-gear-fill'],
    'Selesai'           => ['#E8F5E9', '#2E7D32', 'bi-bag-check-fill'],
    'Dibatalkan'        => ['#FFEBEE', '#C62828', 'bi-x-circle'],
];
$sm = $status_map[$co['status']] ?? ['#F5F5F5','#616161','bi-circle'];

// Flash
$flash_success = $_SESSION['flash_success'] ?? '';
unset($_SESSION['flash_success']);

require __DIR__ . '/../templates/header.php';
require __DIR__ . '/../templates/navbar.php';
?>

<main class="page-content co-wrapper">
  <div class="container">

    <a href="<?= BASE_URL ?>pelanggan/custom_order.php" class="btn-back-cart" style="display:inline-flex;align-items:center;gap:6px;margin-bottom:14px;">
      <i class="bi bi-arrow-left"></i> Kembali ke Custom Order
    </a>

    <?php if ($flash_success): ?>
      <div class="cart-alert alert-success">
        <i class="bi bi-check-circle"></i> <?= htmlspecialchars($flash_success) ?>
      </div>
    <?php endif; ?>

    <div class="row g-4">

      <!-- ═══ DETAIL INFO ═══ -->
      <div class="col-lg-5">
        <div class="checkout-card">
          <div class="checkout-card-header" style="display:flex;justify-content:space-between;align-items:center;">
            <h5 style="margin:0;"><i class="bi bi-info-circle text-pink me-2"></i>Detail Pengajuan</h5>
            <span class="co-list-badge" style="background:<?= $sm[0] ?>;color:<?= $sm[1] ?>;">
              <i class="bi <?= $sm[2] ?>"></i> <?= htmlspecialchars($co['status']) ?>
            </span>
          </div>
          <div class="checkout-card-body">
            <div class="co-detail-row">
              <span>Institusi</span>
              <strong><?= htmlspecialchars($co['nama_institusi']) ?></strong>
            </div>
            <div class="co-detail-row">
              <span>Jenis Seragam</span>
              <strong><?= htmlspecialchars($co['jenis_seragam']) ?></strong>
            </div>
            <?php if ($co['estimasi_jumlah']): ?>
              <div class="co-detail-row">
                <span>Estimasi Jumlah</span>
                <strong><?= number_format($co['estimasi_jumlah']) ?> pcs</strong>
              </div>
            <?php endif; ?>
            <?php if ($co['bahan']): ?>
              <div class="co-detail-row">
                <span>Bahan</span>
                <strong><?= htmlspecialchars($co['bahan']) ?></strong>
              </div>
            <?php endif; ?>
            <?php if ($co['warna']): ?>
              <div class="co-detail-row">
                <span>Warna</span>
                <strong><?= htmlspecialchars($co['warna']) ?></strong>
              </div>
            <?php endif; ?>
            <?php if ($co['catatan']): ?>
              <div class="co-detail-row" style="flex-direction:column;align-items:flex-start;">
                <span style="margin-bottom:4px;">Catatan</span>
                <div style="background:var(--gray-100);padding:10px 12px;border-radius:8px;font-size:13px;color:var(--gray-700);width:100%;">
                  <?= nl2br(htmlspecialchars($co['catatan'])) ?>
                </div>
              </div>
            <?php endif; ?>
            <?php if ($co['file_referensi']): ?>
              <div class="co-detail-row">
                <span>File Referensi</span>
                <a href="<?= BASE_URL . htmlspecialchars($co['file_referensi']) ?>" target="_blank" style="color:var(--pink-primary);text-decoration:none;font-weight:600;">
                  <i class="bi bi-download"></i> Lihat File
                </a>
              </div>
            <?php endif; ?>

            <?php if ($co['total_deal']): ?>
              <hr>
              <h6 style="font-family:var(--font-heading);font-weight:700;font-size:13.5px;margin:14px 0 10px;color:var(--gray-900);">
                <i class="bi bi-receipt-cutoff text-pink me-2"></i>Penawaran Resmi
              </h6>
              <div class="co-detail-row">
                <span>Harga Satuan</span>
                <strong>Rp <?= number_format($co['harga_satuan_deal'], 0, ',', '.') ?></strong>
              </div>
              <div class="co-detail-row">
                <span>Jumlah Final</span>
                <strong><?= number_format($co['jumlah_final']) ?> pcs</strong>
              </div>
              <div class="co-detail-row">
                <span>Total Deal</span>
                <strong style="color:var(--pink-primary);font-size:15px;">Rp <?= number_format($co['total_deal'], 0, ',', '.') ?></strong>
              </div>
              <?php if ($co['dp_diminta']): ?>
                <div class="co-detail-row">
                  <span>DP Diminta</span>
                  <strong>Rp <?= number_format($co['dp_diminta'], 0, ',', '.') ?></strong>
                </div>
              <?php endif; ?>
              <?php if ($co['deadline']): ?>
                <div class="co-detail-row">
                  <span>Target Selesai</span>
                  <strong><?= date('d M Y', strtotime($co['deadline'])) ?></strong>
                </div>
              <?php endif; ?>
            <?php endif; ?>

            <div style="margin-top:14px;font-size:11.5px;color:var(--gray-500);text-align:center;">
              Diajukan <?= date('d M Y H:i', strtotime($co['created_at'])) ?> WIB
            </div>
          </div>
        </div>
      </div>

      <!-- ═══ CHAT KONSULTASI ═══ -->
      <div class="col-lg-7">
        <div class="checkout-card co-chat-card">
          <div class="checkout-card-header" style="display:flex;justify-content:space-between;align-items:center;">
            <h5 style="margin:0;"><i class="bi bi-chat-dots-fill text-pink me-2"></i>Chat Konsultasi</h5>
            <span style="font-size:11.5px;color:var(--gray-500);">
              <i class="bi bi-arrow-clockwise"></i> Update otomatis tiap 5 detik
            </span>
          </div>

          <div class="co-chat-box" id="chat-box" data-id-custom="<?= $id_custom ?>">
            <div class="co-chat-loading">
              <i class="bi bi-arrow-clockwise spinning"></i> Memuat percakapan...
            </div>
          </div>

          <?php if (!in_array($co['status'], ['Selesai','Dibatalkan'], true)): ?>
            <form id="chat-form" class="co-chat-form">
              <input type="hidden" name="id_custom" value="<?= $id_custom ?>">
              <textarea name="pesan" id="chat-input" placeholder="Tulis pesan untuk admin..." rows="2" required></textarea>
              <button type="submit" id="chat-send-btn">
                <i class="bi bi-send-fill"></i>
              </button>
            </form>
          <?php else: ?>
            <div style="padding:14px 20px;background:var(--gray-100);text-align:center;font-size:12.5px;color:var(--gray-500);">
              <i class="bi bi-lock"></i> Chat ditutup karena pesanan sudah <?= htmlspecialchars($co['status']) ?>
            </div>
          <?php endif; ?>
        </div>
      </div>
    </div>

  </div>
</main>

<?php require __DIR__ . '/../templates/footer.php'; ?>
