<?php
/**
 * PRODUK_DETAIL.PHP — Halaman Detail Produk
 * Gallery + Info + Size/Gender + Qty + Add to Cart + Deskripsi + Related
 */

$extra_css = 'katalog.css';
$extra_js  = 'katalog.js';
$active_page = 'beranda';

require __DIR__ . '/config/config.php';

// ── Get Product ──────────────────────────────────────────────
$id = (int)($_GET['id'] ?? 0);
if (!$id) {
    header('Location: ' . BASE_URL . 'katalog.php');
    exit;
}

$stmt = $conn->prepare("SELECT p.*, k.nama_kategori, k.slug as kategori_slug 
                         FROM produk p 
                         JOIN kategori k ON p.id_kategori = k.id_kategori 
                         WHERE p.id_produk = ? AND p.status = 'aktif'");
$stmt->bind_param('i', $id);
$stmt->execute();
$produk = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$produk) {
    header('Location: ' . BASE_URL . 'katalog.php');
    exit;
}

$page_title = $produk['nama_produk'] . ' — Toko Sakinah';
$active_tab = $produk['kategori_slug'];

// Parse JSON fields
$images       = json_decode($produk['foto_all'], true) ?: [$produk['foto']];
$price_by_size = json_decode($produk['price_by_size'], true) ?: [];
$stock_by_size = json_decode($produk['stock_by_size'], true) ?: [];
$sizes        = !empty($produk['ukuran']) ? explode(',', $produk['ukuran']) : [];
$genders      = !empty($produk['genders']) ? explode(',', $produk['genders']) : [];

$prices = array_values($price_by_size);
$min_price = !empty($prices) ? min($prices) : $produk['harga'];
$max_price = !empty($prices) ? max($prices) : $produk['harga'];
$first_size = $sizes[0] ?? '';
$first_price = $price_by_size[$first_size] ?? $produk['harga'];
$first_stock = $stock_by_size[$first_size] ?? $produk['stok_total'];

// ── ULASAN: data + cek apakah user bisa review ───────────────
$ulasan_list = $conn->prepare(
    "SELECT u.*, us.nama AS user_nama
     FROM ulasan u JOIN users us ON u.user_id = us.id
     WHERE u.id_produk = ? ORDER BY u.created_at DESC"
);
$ulasan_list->bind_param('i', $id);
$ulasan_list->execute();
$ulasan_data = $ulasan_list->get_result()->fetch_all(MYSQLI_ASSOC);
$ulasan_list->close();

// Hitung rating average
$rating_sum = 0;
$rating_dist = [5=>0, 4=>0, 3=>0, 2=>0, 1=>0];
foreach ($ulasan_data as $u) {
    $rating_sum += (int) $u['rating'];
    $rating_dist[(int) $u['rating']]++;
}
$rating_avg = count($ulasan_data) > 0 ? $rating_sum / count($ulasan_data) : 0;

// User bisa review kalau: login + pernah beli produk ini dengan status Selesai + belum pernah review
$can_review     = false;
$already_review = false;
if (isset($_SESSION['user_id'])) {
    $uid = (int) $_SESSION['user_id'];

    // Cek sudah pernah review?
    $st = $conn->prepare("SELECT id_ulasan FROM ulasan WHERE user_id = ? AND id_produk = ?");
    $st->bind_param('ii', $uid, $id);
    $st->execute();
    $already_review = $st->get_result()->num_rows > 0;
    $st->close();

    if (!$already_review) {
        // Cek apakah pernah beli & selesai
        $st = $conn->prepare(
            "SELECT COUNT(*) AS n FROM pesanan p
             JOIN detail_pesanan d ON p.id_pesanan = d.id_pesanan
             WHERE p.user_id = ? AND d.id_produk = ? AND p.status = 'Selesai'"
        );
        $st->bind_param('ii', $uid, $id);
        $st->execute();
        $can_review = (int) $st->get_result()->fetch_assoc()['n'] > 0;
        $st->close();
    }
}

require __DIR__ . '/templates/header.php';
require __DIR__ . '/templates/navbar.php';
?>

<!-- ═══ BREADCRUMB ═══ -->
<div class="detail-breadcrumb">
  <div class="container">
    <a href="<?= BASE_URL ?>">Beranda</a>
    <span class="separator">›</span>
    <a href="<?= BASE_URL ?>katalog.php?kategori=<?= urlencode($produk['kategori_slug']) ?>">
      <?= htmlspecialchars($produk['nama_kategori']) ?>
    </a>
    <?php if ($produk['subcategory']): ?>
      <span class="separator">›</span>
      <a href="<?= BASE_URL ?>katalog.php?kategori=<?= urlencode($produk['kategori_slug']) ?>&subcategory=<?= urlencode($produk['subcategory']) ?>">
        <?= htmlspecialchars($produk['subcategory']) ?>
      </a>
    <?php endif; ?>
    <span class="separator">›</span>
    <span class="current"><?= htmlspecialchars($produk['nama_produk']) ?></span>
  </div>
</div>

<!-- ═══ DETAIL CONTENT ═══ -->
<main class="page-content" style="padding-top:8px;">
  <div class="container">
    <div class="row g-4">

      <!-- ── GALLERY ── -->
      <div class="col-lg-5">
        <div class="product-gallery">
          <div class="gallery-main">
            <img src="<?= BASE_URL . htmlspecialchars($images[0]) ?>"
                 alt="<?= htmlspecialchars($produk['nama_produk']) ?>"
                 id="gallery-main-img">
          </div>
          <?php if (count($images) > 1): ?>
            <div class="gallery-thumbs">
              <?php foreach ($images as $idx => $img): ?>
                <div class="gallery-thumb <?= $idx === 0 ? 'active' : '' ?>"
                     data-src="<?= BASE_URL . htmlspecialchars($img) ?>">
                  <img src="<?= BASE_URL . htmlspecialchars($img) ?>"
                       alt="Thumbnail <?= $idx + 1 ?>">
                </div>
              <?php endforeach; ?>
            </div>
          <?php endif; ?>
        </div>
      </div>

      <!-- ── INFO ── -->
      <div class="col-lg-7">
        <div class="detail-info">
          <div class="detail-subcategory">
            <?= htmlspecialchars($produk['subcategory'] ?: $produk['nama_kategori']) ?>
          </div>

          <h1 class="detail-name"><?= htmlspecialchars($produk['nama_produk']) ?></h1>

          <!-- Meta: Rating, Sold, Badge -->
          <div class="detail-meta">
            <?php if ($produk['rating'] > 0): ?>
              <div class="meta-item">
                <span class="rating"><i class="bi bi-star-fill"></i></span>
                <strong><?= number_format($produk['rating'], 1) ?></strong>
              </div>
            <?php endif; ?>
            <div class="meta-item">
              <i class="bi bi-bag-check"></i>
              <?= number_format($produk['sold']) ?> terjual
            </div>
            <?php if (!empty($produk['badge'])): ?>
              <span class="detail-badge-inline" style="background:<?= $produk['badge'] === 'Terlaris' ? 'var(--red-badge)' : 'var(--green-success)' ?>;color:#fff;">
                <?= htmlspecialchars($produk['badge']) ?>
              </span>
            <?php endif; ?>
          </div>

          <!-- Price -->
          <div class="detail-price" id="detail-price">
            Rp <?= number_format($first_price, 0, ',', '.') ?>
          </div>
          <?php if ($min_price !== $max_price): ?>
            <div class="detail-price-range">
              Rentang harga: Rp <?= number_format($min_price, 0, ',', '.') ?> — Rp <?= number_format($max_price, 0, ',', '.') ?>
            </div>
          <?php endif; ?>

          <!-- Add to Cart Form -->
          <form id="add-cart-form" action="<?= BASE_URL ?>pelanggan/add_cart.php" method="POST">
            <input type="hidden" name="id_produk" value="<?= $produk['id_produk'] ?>">
            <input type="hidden" name="ukuran" id="selected-size" value="<?= htmlspecialchars($first_size) ?>">
            <input type="hidden" name="gender" id="selected-gender" value="">

            <!-- Size Options -->
            <?php if (!empty($sizes)): ?>
              <div class="option-group">
                <span class="option-label">Pilih Ukuran</span>
                <div class="size-options">
                  <?php foreach ($sizes as $size):
                    $stok = $stock_by_size[$size] ?? 0;
                  ?>
                    <button type="button"
                            class="size-btn <?= $stok <= 0 ? 'out-of-stock' : '' ?>"
                            data-size="<?= htmlspecialchars($size) ?>"
                            <?= $stok <= 0 ? 'disabled' : '' ?>>
                      <?= htmlspecialchars($size) ?>
                    </button>
                  <?php endforeach; ?>
                </div>
                <div class="stock-info" id="stock-display">
                  Stok: <span class="<?= $first_stock > 10 ? 'stock-number' : 'stock-low' ?>">
                    <?= $first_stock ?><?= $first_stock <= 10 ? ' tersisa' : '' ?>
                  </span>
                </div>
              </div>
            <?php endif; ?>

            <!-- Gender Options -->
            <?php if (!empty($genders)): ?>
              <div class="option-group">
                <span class="option-label">Pilih Gender</span>
                <div class="gender-options">
                  <?php foreach ($genders as $g): ?>
                    <button type="button" class="gender-btn" data-gender="<?= htmlspecialchars(trim($g)) ?>">
                      <i class="bi bi-<?= trim($g) === 'Cewe' ? 'gender-female' : 'gender-male' ?> me-1"></i>
                      <?= htmlspecialchars(trim($g)) ?>
                    </button>
                  <?php endforeach; ?>
                </div>
              </div>
            <?php endif; ?>

            <!-- Quantity -->
            <div class="option-group">
              <span class="option-label">Jumlah</span>
              <div class="qty-wrapper">
                <button type="button" class="qty-btn" id="qty-minus">−</button>
                <input type="number" name="jumlah" value="1" min="1" class="qty-input" id="qty-input">
                <button type="button" class="qty-btn" id="qty-plus">+</button>
              </div>
            </div>

            <!-- Action Buttons -->
            <div class="detail-actions">
              <button type="submit" class="btn-add-cart">
                <i class="bi bi-cart-plus"></i> Tambah ke Keranjang
              </button>
              <button type="button" class="btn-buy-now" id="btn-buy-now">
                <i class="bi bi-lightning"></i> Beli Sekarang
              </button>
            </div>

            <?php if (!empty($produk['tryon_enabled'])): ?>
              <a href="<?= BASE_URL ?>pelanggan/try_on.php?id=<?= $produk['id_produk'] ?>"
                 class="btn-tryon-cta"
                 style="display:flex;align-items:center;justify-content:center;gap:8px;margin-top:10px;
                        padding:12px;background:linear-gradient(135deg,#FCE4EC,#F48FB1);
                        color:var(--pink-dark);font-family:var(--font-heading);font-weight:600;
                        font-size:13.5px;border-radius:var(--radius-md);text-decoration:none;
                        transition:var(--transition);">
                <i class="bi bi-camera2"></i> Coba Virtual Try-On
                <span style="background:var(--pink-primary);color:#fff;font-size:9.5px;padding:2px 6px;border-radius:8px;">BETA</span>
              </a>
            <?php endif; ?>
          </form>

          <!-- Description -->
          <div class="detail-description">
            <h4><i class="bi bi-info-circle me-2"></i>Deskripsi Produk</h4>
            <div class="desc-text">
              <?= nl2br(htmlspecialchars($produk['deskripsi'])) ?>
            </div>
          </div>
        </div>
      </div>

    </div>

    <!-- ═══ ULASAN ═══ -->
    <div class="ulasan-section">
      <div class="ulasan-head">
        <h2 class="section-title mb-0"><i class="bi bi-chat-square-heart text-pink me-2"></i>Ulasan Pembeli</h2>
        <?php if (count($ulasan_data) > 0): ?>
          <div class="ulasan-summary">
            <div class="ulasan-avg-num"><?= number_format($rating_avg, 1) ?></div>
            <div>
              <div class="ulasan-stars">
                <?php for ($s = 1; $s <= 5; $s++): ?>
                  <i class="bi bi-star<?= $s <= round($rating_avg) ? '-fill' : '' ?>"></i>
                <?php endfor; ?>
              </div>
              <div class="ulasan-count"><?= count($ulasan_data) ?> ulasan</div>
            </div>
          </div>
        <?php endif; ?>
      </div>

      <?php
      $flash_u_success = $_SESSION['flash_ulasan_success'] ?? '';
      $flash_u_error   = $_SESSION['flash_ulasan_error']   ?? '';
      unset($_SESSION['flash_ulasan_success'], $_SESSION['flash_ulasan_error']);
      ?>
      <?php if ($flash_u_success): ?>
        <div class="cart-alert alert-success"><i class="bi bi-check-circle"></i> <?= htmlspecialchars($flash_u_success) ?></div>
      <?php endif; ?>
      <?php if ($flash_u_error): ?>
        <div class="cart-alert alert-danger"><i class="bi bi-exclamation-circle"></i> <?= htmlspecialchars($flash_u_error) ?></div>
      <?php endif; ?>

      <!-- Form ulasan -->
      <?php if ($can_review): ?>
        <div class="ulasan-form-wrap">
          <h6><i class="bi bi-pencil-square"></i> Tulis Ulasan Kamu</h6>
          <form action="<?= BASE_URL ?>pelanggan/ulasan_simpan.php" method="POST" id="ulasan-form">
            <input type="hidden" name="id_produk" value="<?= $produk['id_produk'] ?>">
            <div class="rating-pick" id="rating-pick">
              <span class="rating-label">Rating:</span>
              <?php for ($s = 1; $s <= 5; $s++): ?>
                <button type="button" class="rating-star" data-val="<?= $s ?>"><i class="bi bi-star"></i></button>
              <?php endfor; ?>
              <input type="hidden" name="rating" id="rating-input" value="5">
            </div>
            <textarea name="komentar" rows="3" placeholder="Ceritakan pengalamanmu dengan produk ini..." required maxlength="500"></textarea>
            <button type="submit" class="btn-pink">
              <i class="bi bi-send-fill me-1"></i> Kirim Ulasan
            </button>
          </form>
        </div>
      <?php elseif ($already_review): ?>
        <div class="ulasan-info">
          <i class="bi bi-check-circle"></i> Kamu sudah memberikan ulasan untuk produk ini. Terima kasih!
        </div>
      <?php elseif (isset($_SESSION['user_id'])): ?>
        <div class="ulasan-info">
          <i class="bi bi-info-circle"></i> Selesaikan pembelian produk ini dulu untuk bisa kasih ulasan.
        </div>
      <?php else: ?>
        <div class="ulasan-info">
          <i class="bi bi-person"></i> <a href="<?= BASE_URL ?>pelanggan/login.php">Login</a> untuk kasih ulasan produk.
        </div>
      <?php endif; ?>

      <!-- List ulasan -->
      <?php if (empty($ulasan_data)): ?>
        <div class="ulasan-empty">
          <i class="bi bi-chat-square-dots"></i>
          <p>Belum ada ulasan untuk produk ini. Jadilah yang pertama!</p>
        </div>
      <?php else: ?>
        <div class="ulasan-list">
          <?php foreach ($ulasan_data as $u): ?>
            <div class="ulasan-item">
              <div class="ulasan-avatar"><?= strtoupper(mb_substr($u['user_nama'], 0, 1)) ?></div>
              <div class="ulasan-body">
                <div class="ulasan-meta">
                  <strong><?= htmlspecialchars($u['user_nama']) ?></strong>
                  <span class="ulasan-stars">
                    <?php for ($s = 1; $s <= 5; $s++): ?>
                      <i class="bi bi-star<?= $s <= (int) $u['rating'] ? '-fill' : '' ?>"></i>
                    <?php endfor; ?>
                  </span>
                  <span class="ulasan-date"><?= date('d M Y', strtotime($u['created_at'])) ?></span>
                </div>
                <div class="ulasan-text"><?= nl2br(htmlspecialchars($u['komentar'])) ?></div>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </div>

    <!-- ═══ RELATED PRODUCTS ═══ -->
    <div class="related-section">
      <h2 class="section-title"><i class="bi bi-grid text-pink me-2"></i>Produk Terkait</h2>
      <div class="row g-3">
        <?php
        $related_stmt = $conn->prepare(
          "SELECT p.*, k.nama_kategori, k.slug as kategori_slug 
           FROM produk p 
           JOIN kategori k ON p.id_kategori = k.id_kategori 
           WHERE p.id_kategori = ? AND p.id_produk != ? AND p.status = 'aktif' 
           ORDER BY p.sold DESC 
           LIMIT 4"
        );
        $related_stmt->bind_param('ii', $produk['id_kategori'], $produk['id_produk']);
        $related_stmt->execute();
        $related = $related_stmt->get_result();

        while ($rel = $related->fetch_assoc()):
          $rPrices = json_decode($rel['price_by_size'], true) ?: [];
          $rVals = array_values($rPrices);
          $rMin = !empty($rVals) ? min($rVals) : $rel['harga'];
          $rMax = !empty($rVals) ? max($rVals) : $rel['harga'];
        ?>
          <div class="col-6 col-md-4 col-lg-3">
            <div class="product-card">
              <?php if (!empty($rel['badge'])): ?>
                <span class="<?= $rel['badge'] === 'Terlaris' ? 'badge-terlaris' : 'badge-baru' ?>">
                  <?= htmlspecialchars($rel['badge']) ?>
                </span>
              <?php endif; ?>
              <div class="card-img-wrapper">
                <a href="<?= BASE_URL ?>produk_detail.php?id=<?= $rel['id_produk'] ?>">
                  <img src="<?= BASE_URL . htmlspecialchars($rel['foto']) ?>"
                       alt="<?= htmlspecialchars($rel['nama_produk']) ?>" loading="lazy">
                </a>
              </div>
              <div class="card-body">
                <div class="card-subcategory"><?= htmlspecialchars($rel['subcategory'] ?: $rel['nama_kategori']) ?></div>
                <h3 class="card-title">
                  <a href="<?= BASE_URL ?>produk_detail.php?id=<?= $rel['id_produk'] ?>" style="color:inherit;text-decoration:none;">
                    <?= htmlspecialchars($rel['nama_produk']) ?>
                  </a>
                </h3>
                <div class="card-price-main">Mulai Rp <?= number_format($rMin, 0, ',', '.') ?></div>
                <div class="card-meta">
                  <?php if ($rel['rating'] > 0): ?>
                    <span class="rating"><i class="bi bi-star-fill"></i> <?= number_format($rel['rating'], 1) ?></span>
                    <span>·</span>
                  <?php endif; ?>
                  <span><?= number_format($rel['sold']) ?> terjual</span>
                </div>
              </div>
            </div>
          </div>
        <?php endwhile;
        $related_stmt->close();
        ?>
      </div>
    </div>

  </div>
</main>

<!-- Pass product data to JS -->
<script>
  window.productPriceBySize = <?= json_encode($price_by_size) ?>;
  window.productStockBySize = <?= json_encode($stock_by_size) ?>;
</script>

<?php require __DIR__ . '/templates/footer.php'; ?>
