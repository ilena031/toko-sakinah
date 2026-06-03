<?php
/**
 * KERANJANG.PHP — Halaman Keranjang Belanja Pelanggan
 * Tampil isi cart dari session, edit qty, hapus, dan tombol checkout.
 */

require_once __DIR__ . '/../config/config.php';

$page_title  = 'Keranjang — Toko Sakinah';
$active_page = 'keranjang';
$extra_css   = 'keranjang.css';
$extra_js    = 'keranjang.js';

// Flash messages
$flash_success = $_SESSION['flash_success'] ?? '';
$flash_error   = $_SESSION['flash_error']   ?? '';
unset($_SESSION['flash_success'], $_SESSION['flash_error']);

$cart = $_SESSION['cart'] ?? [];

// Refresh stok dari DB & hitung subtotal (jaga konsistensi kalau stok berubah)
$total       = 0;
$total_items = 0;
foreach ($cart as $key => $item) {
    $stmt = $conn->prepare("SELECT stock_by_size, price_by_size, status FROM produk WHERE id_produk = ?");
    $stmt->bind_param('i', $item['id_produk']);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$row || $row['status'] !== 'aktif') {
        unset($_SESSION['cart'][$key]);
        unset($cart[$key]);
        continue;
    }

    $stock_by_size = json_decode($row['stock_by_size'], true) ?: [];
    $price_by_size = json_decode($row['price_by_size'], true) ?: [];
    $cart[$key]['stok_max'] = (int) ($stock_by_size[$item['ukuran']] ?? 0);
    $cart[$key]['harga']    = (float) ($price_by_size[$item['ukuran']] ?? $item['harga']);

    // Jika qty > stok max, clamp
    if ($cart[$key]['jumlah'] > $cart[$key]['stok_max']) {
        $cart[$key]['jumlah'] = max(0, $cart[$key]['stok_max']);
        $_SESSION['cart'][$key]['jumlah'] = $cart[$key]['jumlah'];
    }

    $cart[$key]['subtotal'] = $cart[$key]['harga'] * $cart[$key]['jumlah'];
    $total       += $cart[$key]['subtotal'];
    $total_items += $cart[$key]['jumlah'];
}

require __DIR__ . '/../templates/header.php';
require __DIR__ . '/../templates/navbar.php';
?>

<main class="page-content cart-wrapper">
  <div class="container">

    <!-- Header -->
    <div class="cart-header">
      <h2><i class="bi bi-bag-heart-fill text-pink me-2"></i>Keranjang Belanja</h2>
      <p>Periksa pesanan Anda sebelum melanjutkan ke pembayaran.</p>
    </div>

    <!-- Flash -->
    <?php if ($flash_success): ?>
      <div class="cart-alert alert-success">
        <i class="bi bi-check-circle"></i> <?= htmlspecialchars($flash_success) ?>
      </div>
    <?php endif; ?>
    <?php if ($flash_error): ?>
      <div class="cart-alert alert-danger">
        <i class="bi bi-exclamation-circle"></i> <?= htmlspecialchars($flash_error) ?>
      </div>
    <?php endif; ?>

    <?php if (empty($cart)): ?>
      <!-- Empty cart -->
      <div class="cart-empty">
        <i class="bi bi-bag-x"></i>
        <h4>Keranjang kamu masih kosong</h4>
        <p>Yuk mulai belanja produk kebutuhan sekolah & oleh-oleh haji di Toko Sakinah!</p>
        <a href="<?= BASE_URL ?>katalog.php" class="btn-pink">
          <i class="bi bi-shop me-2"></i>Mulai Belanja
        </a>
      </div>

    <?php else: ?>
      <div class="row g-4">
        <!-- ═══ ITEM LIST ═══ -->
        <div class="col-lg-8">
          <div class="cart-list-card">
            <div class="cart-list-header">
              <span><i class="bi bi-box-seam me-2"></i><?= count($cart) ?> Item di Keranjang</span>
              <button type="button" class="btn-clear-cart" id="btn-clear-cart">
                <i class="bi bi-trash"></i> Kosongkan
              </button>
            </div>

            <div class="cart-items" id="cart-items">
              <?php foreach ($cart as $key => $item): ?>
                <div class="cart-item" data-key="<?= htmlspecialchars($key) ?>">
                  <div class="cart-item-img">
                    <a href="<?= BASE_URL ?>produk_detail.php?id=<?= $item['id_produk'] ?>">
                      <img src="<?= BASE_URL . htmlspecialchars($item['foto']) ?>"
                           alt="<?= htmlspecialchars($item['nama']) ?>" loading="lazy">
                    </a>
                  </div>

                  <div class="cart-item-info">
                    <a href="<?= BASE_URL ?>produk_detail.php?id=<?= $item['id_produk'] ?>" class="cart-item-name">
                      <?= htmlspecialchars($item['nama']) ?>
                    </a>
                    <div class="cart-item-meta">
                      <?php if ($item['ukuran']): ?>
                        <span><i class="bi bi-rulers"></i> Ukuran: <strong><?= htmlspecialchars($item['ukuran']) ?></strong></span>
                      <?php endif; ?>
                      <?php if ($item['gender']): ?>
                        <span><i class="bi bi-person"></i> <?= htmlspecialchars($item['gender']) ?></span>
                      <?php endif; ?>
                    </div>
                    <div class="cart-item-price-mobile">
                      Rp <?= number_format($item['harga'], 0, ',', '.') ?>
                    </div>
                  </div>

                  <div class="cart-item-price">
                    Rp <?= number_format($item['harga'], 0, ',', '.') ?>
                  </div>

                  <div class="cart-item-qty">
                    <button type="button" class="qty-btn qty-minus" data-key="<?= htmlspecialchars($key) ?>">−</button>
                    <input type="number"
                           class="qty-input"
                           value="<?= $item['jumlah'] ?>"
                           min="1"
                           max="<?= $item['stok_max'] ?>"
                           data-key="<?= htmlspecialchars($key) ?>">
                    <button type="button" class="qty-btn qty-plus" data-key="<?= htmlspecialchars($key) ?>">+</button>
                  </div>

                  <div class="cart-item-subtotal" data-key="<?= htmlspecialchars($key) ?>">
                    Rp <?= number_format($item['subtotal'], 0, ',', '.') ?>
                  </div>

                  <button type="button" class="cart-item-remove" data-key="<?= htmlspecialchars($key) ?>" title="Hapus">
                    <i class="bi bi-trash"></i>
                  </button>

                  <?php if ($item['stok_max'] <= 5): ?>
                    <div class="cart-item-stockwarn">
                      <i class="bi bi-exclamation-triangle-fill"></i>
                      Stok tersisa <?= $item['stok_max'] ?> pcs
                    </div>
                  <?php endif; ?>
                </div>
              <?php endforeach; ?>
            </div>
          </div>

          <a href="<?= BASE_URL ?>katalog.php" class="btn-continue-shop">
            <i class="bi bi-arrow-left"></i> Lanjut Belanja
          </a>
        </div>

        <!-- ═══ RINGKASAN ═══ -->
        <div class="col-lg-4">
          <div class="cart-summary">
            <h5><i class="bi bi-receipt me-2"></i>Ringkasan Belanja</h5>
            <div class="summary-row">
              <span>Total Item</span>
              <span id="summary-items"><?= $total_items ?> pcs</span>
            </div>
            <div class="summary-row">
              <span>Subtotal</span>
              <span id="summary-subtotal">Rp <?= number_format($total, 0, ',', '.') ?></span>
            </div>
            <div class="summary-row text-muted-info">
              <span><i class="bi bi-info-circle"></i> Ongkir</span>
              <span>Dihitung saat checkout</span>
            </div>
            <hr>
            <div class="summary-row summary-total">
              <span>Total</span>
              <span id="summary-total">Rp <?= number_format($total, 0, ',', '.') ?></span>
            </div>

            <a href="<?= BASE_URL ?>pelanggan/checkout.php" class="btn-checkout">
              <i class="bi bi-credit-card-fill me-2"></i>Lanjut ke Checkout
            </a>

            <div class="summary-secure">
              <i class="bi bi-shield-check"></i>
              Pembayaran aman via Midtrans
            </div>
          </div>
        </div>
      </div>
    <?php endif; ?>

  </div>
</main>

<?php require __DIR__ . '/../templates/footer.php'; ?>
