<?php
/**
 * TRY_ON.PHP — Virtual Try-On dengan Live Webcam + MediaPipe Pose
 * - Kamera real-time, deteksi 33 landmark tubuh
 * - Hitung lebar bahu + panjang torso → estimasi lingkar dada → rekomendasi size
 * - Animasi scanner (lingkaran pulsing) di titik-titik kunci tubuh
 * - Capture → simpan ke tryon_log
 */

require_once __DIR__ . '/../config/config.php';
require __DIR__ . '/auth_check.php';

$page_title  = 'Virtual Try-On — Toko Sakinah';
$active_page = 'beranda';
$extra_css   = 'tryon.css';
$extra_js    = 'tryon.js';

$id_produk = (int) ($_GET['id'] ?? 0);
$produk    = null;

// Cek apakah kolom jenjang sudah ada (untuk graceful fallback kalau migrasi belum jalan)
$has_jenjang_col = false;
$col_check = $conn->query("SHOW COLUMNS FROM produk LIKE 'jenjang'");
if ($col_check && $col_check->num_rows > 0) $has_jenjang_col = true;

$jenjang_select = $has_jenjang_col ? ', jenjang' : '';

if ($id_produk) {
    $stmt = $conn->prepare(
        "SELECT id_produk, nama_produk, foto, harga, tryon_enabled, subcategory, ukuran, stock_by_size $jenjang_select
         FROM produk WHERE id_produk = ? AND status = 'aktif'"
    );
    $stmt->bind_param('i', $id_produk);
    $stmt->execute();
    $produk = $stmt->get_result()->fetch_assoc();
    $stmt->close();
}

// Daftar produk tryon enabled
$stmt = $conn->prepare(
    "SELECT id_produk, nama_produk, foto, subcategory, ukuran, stock_by_size $jenjang_select
     FROM produk WHERE tryon_enabled = 1 AND status = 'aktif'
     ORDER BY sold DESC LIMIT 16"
);
$stmt->execute();
$tryon_products = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

require __DIR__ . '/../templates/header.php';
require __DIR__ . '/../templates/navbar.php';
?>

<main class="page-content tryon-wrapper">
  <div class="container">

    <div class="tryon-header">
      <h2><i class="bi bi-camera2 text-pink me-2"></i>Virtual Try-On Scanner</h2>
      <p>Nyalakan kamera, berdiri tegak, dan biarkan AI scan ukuran tubuh kamu untuk rekomendasi size!</p>
      <div class="tryon-badge-beta">
        <i class="bi bi-cpu"></i> Live Body Scanner · Powered by MediaPipe Pose
      </div>
    </div>

    <!-- ═══ JENJANG SELECTOR ═══ -->
    <div class="jenjang-selector-card">
      <div class="jenjang-label">
        <i class="bi bi-mortarboard-fill"></i>
        <span>Pilih jenjang untuk size chart yang tepat:</span>
      </div>
      <div class="jenjang-options" id="jenjang-options">
        <button type="button" class="jenjang-opt" data-jenjang="tk">
          <i class="bi bi-emoji-smile"></i>
          <strong>TK</strong>
          <small>4–6 thn</small>
        </button>
        <button type="button" class="jenjang-opt" data-jenjang="sd_kecil">
          <i class="bi bi-backpack"></i>
          <strong>SD 1–3</strong>
          <small>6–9 thn</small>
        </button>
        <button type="button" class="jenjang-opt" data-jenjang="sd_besar">
          <i class="bi bi-backpack-fill"></i>
          <strong>SD 4–6</strong>
          <small>9–12 thn</small>
        </button>
        <button type="button" class="jenjang-opt" data-jenjang="smp">
          <i class="bi bi-book"></i>
          <strong>SMP</strong>
          <small>12–15 thn</small>
        </button>
        <button type="button" class="jenjang-opt active" data-jenjang="sma">
          <i class="bi bi-book-half"></i>
          <strong>SMA</strong>
          <small>15–18 thn</small>
        </button>
        <button type="button" class="jenjang-opt" data-jenjang="dewasa">
          <i class="bi bi-person-fill"></i>
          <strong>Dewasa</strong>
          <small>18+ thn</small>
        </button>
      </div>
    </div>

    <div class="row g-4">

      <!-- ═══ LEFT: SCANNER ═══ -->
      <div class="col-lg-8">
        <div class="checkout-card scanner-card">
          <div class="checkout-card-header" style="display:flex;justify-content:space-between;align-items:center;">
            <h5 style="margin:0;"><i class="bi bi-broadcast text-pink me-2"></i>Live Body Scanner</h5>
            <span id="scanner-status" class="scanner-status idle">
              <i class="bi bi-circle-fill"></i> Menunggu kamera
            </span>
          </div>

          <div class="scanner-stage" id="scanner-stage">
            <!-- Video & canvas overlay -->
            <video id="webcam" playsinline muted></video>
            <canvas id="overlay"></canvas>

            <!-- Status overlay sebelum kamera nyala -->
            <div class="scanner-prestart" id="scanner-prestart">
              <i class="bi bi-camera-video"></i>
              <h4>Siap melakukan body scan?</h4>
              <p>Pastikan tubuh dari kepala sampai pinggul terlihat di kamera.<br>Berdiri ~1.5 meter dari kamera dengan pencahayaan cukup.</p>
              <button type="button" id="btn-start-camera" class="btn-checkout-submit" style="width:auto;padding:14px 32px;">
                <i class="bi bi-camera-fill me-2"></i>Nyalakan Kamera
              </button>
            </div>

            <!-- Scanning loader -->
            <div class="scanner-loading" id="scanner-loading">
              <div class="scan-line"></div>
            </div>

            <!-- HUD info overlay -->
            <div class="scanner-hud" id="scanner-hud" style="display:none;">
              <div class="hud-corner tl"></div>
              <div class="hud-corner tr"></div>
              <div class="hud-corner bl"></div>
              <div class="hud-corner br"></div>
              <div class="hud-info" id="hud-info">
                <span><i class="bi bi-rulers"></i> Lebar bahu: <strong id="hud-shoulder">--</strong></span>
                <span><i class="bi bi-arrows-vertical"></i> Tinggi torso: <strong id="hud-torso">--</strong></span>
                <span><i class="bi bi-circle"></i> Est. dada: <strong id="hud-chest">--</strong></span>
              </div>
            </div>
          </div>

          <div class="scanner-actions">
            <button type="button" id="btn-capture" class="btn-checkout-submit" disabled style="flex:1;">
              <i class="bi bi-camera-fill me-2"></i>Capture & Simpan
            </button>
            <button type="button" id="btn-stop" class="btn-back-cart" style="background:var(--white);border:1.5px solid var(--gray-300);padding:12px 20px;border-radius:var(--radius-md);">
              <i class="bi bi-stop-fill"></i> Stop
            </button>
          </div>
        </div>
      </div>

      <!-- ═══ RIGHT: SIZE RESULT + PRODUK ═══ -->
      <div class="col-lg-4">

        <!-- Hasil ukuran -->
        <div class="checkout-card">
          <div class="checkout-card-header">
            <h5><i class="bi bi-bullseye text-pink me-2"></i>Rekomendasi Ukuran</h5>
          </div>
          <div class="checkout-card-body" id="size-result-area">
            <div class="size-empty">
              <i class="bi bi-question-circle"></i>
              <p>Nyalakan kamera & berdiri di depannya untuk dapat rekomendasi ukuran.</p>
            </div>
          </div>
        </div>

        <!-- Pilih produk -->
        <div class="checkout-card mt-3">
          <div class="checkout-card-header">
            <h5><i class="bi bi-tshirt text-pink me-2"></i>Pilih Produk</h5>
          </div>
          <div class="checkout-card-body" style="max-height:340px;overflow-y:auto;padding:8px;">
            <div class="tryon-product-grid">
              <?php foreach ($tryon_products as $p):
                $p_sizes  = !empty($p['ukuran']) ? array_map('trim', explode(',', $p['ukuran'])) : [];
                $p_stock  = json_decode($p['stock_by_size'] ?? '{}', true) ?: [];
                $p_avail  = array_values(array_filter($p_sizes, fn($s) => ($p_stock[$s] ?? 0) > 0));
                $p_jenj   = $p['jenjang'] ?? '';
              ?>
                <div class="tryon-product-card <?= $produk && $produk['id_produk'] == $p['id_produk'] ? 'selected' : '' ?>"
                     data-id="<?= $p['id_produk'] ?>"
                     data-name="<?= htmlspecialchars($p['nama_produk']) ?>"
                     data-foto="<?= BASE_URL . htmlspecialchars($p['foto']) ?>"
                     data-jenjang="<?= htmlspecialchars($p_jenj) ?>"
                     data-sizes="<?= htmlspecialchars(implode(',', $p_avail)) ?>"
                     data-all-sizes="<?= htmlspecialchars(implode(',', $p_sizes)) ?>"
                     data-subcategory="<?= htmlspecialchars($p['subcategory'] ?: '') ?>">
                  <img src="<?= BASE_URL . htmlspecialchars($p['foto']) ?>" alt="<?= htmlspecialchars($p['nama_produk']) ?>">
                  <div class="tryon-product-name"><?= htmlspecialchars($p['nama_produk']) ?></div>
                </div>
              <?php endforeach; ?>
            </div>
          </div>
        </div>

        <div class="text-center mt-3">
          <a href="<?= BASE_URL ?>pelanggan/tryon_history.php" class="btn-back-cart" style="display:inline-flex;align-items:center;gap:6px;">
            <i class="bi bi-clock-history"></i> Riwayat Try-On Saya
          </a>
        </div>
      </div>
    </div>

    <!-- ═══ TIPS ═══ -->
    <div class="row mt-4">
      <div class="col-12">
        <div class="checkout-card">
          <div class="checkout-card-header">
            <h5><i class="bi bi-info-circle text-pink me-2"></i>Tips agar Scan Akurat</h5>
          </div>
          <div class="checkout-card-body">
            <div class="row g-3">
              <div class="col-md-3 col-6">
                <div class="tip-item">
                  <i class="bi bi-person-arms-up"></i>
                  <div><strong>Berdiri tegak</strong><br><small>Pose seperti foto KTP</small></div>
                </div>
              </div>
              <div class="col-md-3 col-6">
                <div class="tip-item">
                  <i class="bi bi-rulers"></i>
                  <div><strong>~1.5 meter</strong><br><small>Dari kamera</small></div>
                </div>
              </div>
              <div class="col-md-3 col-6">
                <div class="tip-item">
                  <i class="bi bi-brightness-high"></i>
                  <div><strong>Cahaya cukup</strong><br><small>Hindari backlight</small></div>
                </div>
              </div>
              <div class="col-md-3 col-6">
                <div class="tip-item">
                  <i class="bi bi-shirt"></i>
                  <div><strong>Baju ngepas</strong><br><small>Agar bentuk tubuh kelihatan</small></div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

  </div>
</main>

<!-- MediaPipe Pose CDN -->
<script src="https://cdn.jsdelivr.net/npm/@mediapipe/camera_utils/camera_utils.js" crossorigin="anonymous"></script>
<script src="https://cdn.jsdelivr.net/npm/@mediapipe/drawing_utils/drawing_utils.js" crossorigin="anonymous"></script>
<script src="https://cdn.jsdelivr.net/npm/@mediapipe/pose/pose.js" crossorigin="anonymous"></script>

<?php require __DIR__ . '/../templates/footer.php'; ?>
