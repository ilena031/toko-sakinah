<?php
/**
 * TRYON_HISTORY.PHP — Riwayat try-on pelanggan
 */

require_once __DIR__ . '/../config/config.php';
require __DIR__ . '/auth_check.php';

$page_title  = 'Riwayat Try-On — Toko Sakinah';
$active_page = 'beranda';
$extra_css   = 'tryon.css';

$user_id = (int) $_SESSION['user_id'];

$stmt = $conn->prepare(
    "SELECT t.*, p.nama_produk, p.foto AS produk_foto
     FROM tryon_log t
     LEFT JOIN produk p ON t.id_produk = p.id_produk
     WHERE t.user_id = ?
     ORDER BY t.created_at DESC"
);
$stmt->bind_param('i', $user_id);
$stmt->execute();
$logs = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

require __DIR__ . '/../templates/header.php';
require __DIR__ . '/../templates/navbar.php';
?>

<main class="page-content tryon-wrapper">
  <div class="container">
    <div class="tryon-header">
      <h2><i class="bi bi-clock-history text-pink me-2"></i>Riwayat Try-On</h2>
      <p>Semua sesi body scan yang pernah kamu lakukan.</p>
    </div>

    <a href="<?= BASE_URL ?>pelanggan/try_on.php" class="btn-pink mb-3" style="display:inline-flex;align-items:center;gap:6px;text-decoration:none;">
      <i class="bi bi-camera2"></i> Buat Try-On Baru
    </a>

    <?php if (empty($logs)): ?>
      <div class="tryon-placeholder" style="background:var(--white);border-radius:var(--radius-lg);padding:60px 30px;text-align:center;">
        <i class="bi bi-camera-video-off" style="font-size:64px;color:var(--gray-300);"></i>
        <h6 style="margin-top:14px;">Belum ada riwayat try-on</h6>
        <p style="color:var(--gray-500);font-size:13.5px;">Coba fitur Virtual Try-On Scanner untuk lihat baju sebelum beli!</p>
      </div>
    <?php else: ?>
      <div class="row g-3">
        <?php foreach ($logs as $log):
          // foto_input baru: JSON summary; lama: path file
          $summary = null;
          $is_old_format = false;
          if ($log['foto_input']) {
              $decoded = json_decode($log['foto_input'], true);
              if (is_array($decoded) && isset($decoded['size'])) {
                  $summary = $decoded;
              } else {
                  $is_old_format = true;
              }
          }
        ?>
          <div class="col-md-6 col-lg-4">
            <div class="tryon-history-card">
              <div class="tryon-history-imgs">
                <img src="<?= BASE_URL . htmlspecialchars($log['foto_hasil']) ?>" alt="Hasil try-on">
              </div>
              <div class="tryon-history-info">
                <div class="tryon-history-name">
                  <?= htmlspecialchars($log['nama_produk'] ?? 'Body Scan Umum') ?>
                  <?php if ($summary): ?>
                    <span class="tryon-history-size">Size <?= htmlspecialchars($summary['size']) ?></span>
                  <?php endif; ?>
                </div>
                <?php if ($summary && !empty($summary['jenjang_label'])): ?>
                  <div style="font-size:11px;color:var(--pink-primary);font-weight:600;margin-bottom:4px;">
                    <i class="bi bi-mortarboard"></i> <?= htmlspecialchars($summary['jenjang_label']) ?>
                  </div>
                <?php endif; ?>
                <div class="tryon-history-date">
                  <i class="bi bi-calendar3"></i> <?= date('d M Y H:i', strtotime($log['created_at'])) ?>
                </div>

                <?php if ($summary): ?>
                  <div style="background:var(--gray-100);border-radius:8px;padding:8px 12px;margin:8px 0;font-size:11.5px;color:var(--gray-700);">
                    <div>Lebar bahu: <strong><?= number_format($summary['shoulder_cm'], 1) ?> cm</strong></div>
                    <div>Tinggi torso: <strong><?= number_format($summary['torso_cm'], 1) ?> cm</strong></div>
                    <div>Est. lingkar dada: <strong><?= number_format($summary['chest_cm'], 1) ?> cm</strong></div>
                  </div>
                <?php endif; ?>

                <?php if ($log['id_produk']): ?>
                  <a href="<?= BASE_URL ?>produk_detail.php?id=<?= $log['id_produk'] ?>" class="tryon-history-link">
                    Lihat Produk →
                  </a>
                <?php endif; ?>
              </div>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>

  </div>
</main>

<?php require __DIR__ . '/../templates/footer.php'; ?>
