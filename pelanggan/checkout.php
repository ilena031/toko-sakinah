<?php
/**
 * CHECKOUT.PHP — Halaman Checkout
 * Form alamat pengiriman + metode kirim + ringkasan order.
 * Submit → proses_checkout.php → buat pesanan di DB → redirect ke pembayaran.
 */

require_once __DIR__ . '/../config/config.php';
require __DIR__ . '/auth_check.php';

$page_title  = 'Checkout — Toko Sakinah';
$active_page = 'keranjang';
$extra_css   = 'checkout.css';
$extra_js    = 'checkout.js';

// ── Validasi cart tidak kosong ─────────────────────────────
$cart = $_SESSION['cart'] ?? [];
if (empty($cart)) {
    $_SESSION['flash_error'] = 'Keranjang kosong. Silakan tambah produk dulu.';
    header('Location: ' . BASE_URL . 'pelanggan/keranjang.php');
    exit;
}

// ── Ambil data user untuk auto-fill ────────────────────────
$user_id = $_SESSION['user_id'];
$stmt = $conn->prepare("SELECT nama, email, no_hp, alamat FROM users WHERE id = ?");
$stmt->bind_param('i', $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
$stmt->close();

// ── Refresh cart dari DB & validasi stok ───────────────────
$subtotal    = 0;
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

    if ($cart[$key]['jumlah'] > $cart[$key]['stok_max']) {
        $cart[$key]['jumlah'] = max(0, $cart[$key]['stok_max']);
        $_SESSION['cart'][$key]['jumlah'] = $cart[$key]['jumlah'];
    }
    $cart[$key]['subtotal'] = $cart[$key]['harga'] * $cart[$key]['jumlah'];
    $subtotal    += $cart[$key]['subtotal'];
    $total_items += $cart[$key]['jumlah'];
}

// Kalau setelah refresh cart jadi kosong
if (empty($cart)) {
    $_SESSION['flash_error'] = 'Semua produk di keranjang sudah tidak tersedia.';
    header('Location: ' . BASE_URL . 'pelanggan/keranjang.php');
    exit;
}

// ── Daftar metode pengiriman ───────────────────────────────
$shipping_options = [
    'JNE'         => ['label' => 'JNE Reguler',  'ongkir' => 15000, 'eta' => '2-4 hari', 'icon' => 'bi-truck'],
    'J&T'         => ['label' => 'J&T Express',  'ongkir' => 12000, 'eta' => '2-3 hari', 'icon' => 'bi-truck'],
    'SiCepat'     => ['label' => 'SiCepat',      'ongkir' => 10000, 'eta' => '3-5 hari', 'icon' => 'bi-truck'],
    'Ambil'       => ['label' => 'Ambil di Toko','ongkir' => 0,     'eta' => 'Hari ini', 'icon' => 'bi-shop'],
];

// Flash
$flash_error = $_SESSION['flash_error'] ?? '';
unset($_SESSION['flash_error']);

require __DIR__ . '/../templates/header.php';
require __DIR__ . '/../templates/navbar.php';
?>

<main class="page-content checkout-wrapper">
  <div class="container">

    <!-- Steps -->
    <div class="checkout-steps">
      <div class="step done"><span>1</span>Keranjang</div>
      <div class="step-line"></div>
      <div class="step active"><span>2</span>Checkout</div>
      <div class="step-line"></div>
      <div class="step"><span>3</span>Pembayaran</div>
      <div class="step-line"></div>
      <div class="step"><span>4</span>Selesai</div>
    </div>

    <?php if ($flash_error): ?>
      <div class="cart-alert alert-danger">
        <i class="bi bi-exclamation-circle"></i> <?= htmlspecialchars($flash_error) ?>
      </div>
    <?php endif; ?>

    <form action="<?= BASE_URL ?>pelanggan/proses_checkout.php" method="POST" id="checkout-form">
      <div class="row g-4">

        <!-- ═══ LEFT: ALAMAT + KIRIM ═══ -->
        <div class="col-lg-8">

          <!-- Alamat Pengiriman -->
          <div class="checkout-card">
            <div class="checkout-card-header">
              <h5><i class="bi bi-geo-alt-fill text-pink me-2"></i>Alamat Pengiriman</h5>
            </div>
            <div class="checkout-card-body">
              <div class="row g-3">
                <div class="col-md-6">
                  <label class="form-label-pink">Nama Penerima <span class="req">*</span></label>
                  <input type="text" name="penerima_nama" class="form-control-pink"
                         value="<?= htmlspecialchars($user['nama']) ?>" required maxlength="100">
                </div>
                <div class="col-md-6">
                  <label class="form-label-pink">Nomor HP <span class="req">*</span></label>
                  <input type="tel" name="penerima_hp" class="form-control-pink"
                         value="<?= htmlspecialchars($user['no_hp']) ?>" required maxlength="20"
                         pattern="[0-9+\-\s]+">
                </div>
                <div class="col-12">
                  <label class="form-label-pink">Alamat Lengkap <span class="req">*</span></label>
                  <textarea name="penerima_alamat" class="form-control-pink" rows="3" required
                            placeholder="Jalan, RT/RW, Kelurahan, Kecamatan, Kota, Provinsi, Kode Pos"><?= htmlspecialchars($user['alamat'] ?? '') ?></textarea>
                  <small class="form-hint">Tulis selengkap mungkin agar paket sampai dengan tepat.</small>
                </div>
                <div class="col-12">
                  <label class="form-label-pink">Catatan untuk Penjual <span class="text-muted">(opsional)</span></label>
                  <textarea name="catatan" class="form-control-pink" rows="2"
                            placeholder="Contoh: Tolong dibungkus rapi, alamat patokan..."></textarea>
                </div>
              </div>
            </div>
          </div>

          <!-- Metode Pengiriman -->
          <div class="checkout-card">
            <div class="checkout-card-header">
              <h5><i class="bi bi-truck text-pink me-2"></i>Metode Pengiriman</h5>
            </div>
            <div class="checkout-card-body">
              <div class="shipping-options">
                <?php $first = true; foreach ($shipping_options as $key => $opt): ?>
                  <label class="shipping-option <?= $first ? 'selected' : '' ?>">
                    <input type="radio" name="metode_pengiriman" value="<?= $key ?>"
                           data-ongkir="<?= $opt['ongkir'] ?>"
                           <?= $first ? 'checked' : '' ?> required>
                    <div class="shipping-icon"><i class="bi <?= $opt['icon'] ?>"></i></div>
                    <div class="shipping-info">
                      <div class="shipping-name"><?= htmlspecialchars($opt['label']) ?></div>
                      <div class="shipping-eta"><i class="bi bi-clock"></i> Estimasi <?= htmlspecialchars($opt['eta']) ?></div>
                    </div>
                    <div class="shipping-price">
                      <?= $opt['ongkir'] > 0 ? 'Rp ' . number_format($opt['ongkir'], 0, ',', '.') : 'GRATIS' ?>
                    </div>
                  </label>
                <?php $first = false; endforeach; ?>
              </div>
            </div>
          </div>

          <!-- Item Pesanan -->
          <div class="checkout-card">
            <div class="checkout-card-header">
              <h5><i class="bi bi-box-seam text-pink me-2"></i>Item Pesanan (<?= count($cart) ?>)</h5>
            </div>
            <div class="checkout-card-body" style="padding:0;">
              <?php foreach ($cart as $item): ?>
                <div class="checkout-item">
                  <img src="<?= BASE_URL . htmlspecialchars($item['foto']) ?>" alt="<?= htmlspecialchars($item['nama']) ?>">
                  <div class="checkout-item-info">
                    <div class="checkout-item-name"><?= htmlspecialchars($item['nama']) ?></div>
                    <div class="checkout-item-meta">
                      <?php if ($item['ukuran']): ?>
                        Ukuran <strong><?= htmlspecialchars($item['ukuran']) ?></strong>
                      <?php endif; ?>
                      <?php if ($item['gender']): ?>
                        · <?= htmlspecialchars($item['gender']) ?>
                      <?php endif; ?>
                      · <?= $item['jumlah'] ?> pcs × Rp <?= number_format($item['harga'], 0, ',', '.') ?>
                    </div>
                  </div>
                  <div class="checkout-item-subtotal">
                    Rp <?= number_format($item['subtotal'], 0, ',', '.') ?>
                  </div>
                </div>
              <?php endforeach; ?>
            </div>
          </div>

        </div>

        <!-- ═══ RIGHT: RINGKASAN ═══ -->
        <div class="col-lg-4">
          <div class="checkout-summary">
            <h5><i class="bi bi-receipt me-2"></i>Ringkasan Pembayaran</h5>

            <div class="summary-row">
              <span>Total Item</span>
              <span><?= $total_items ?> pcs</span>
            </div>
            <div class="summary-row">
              <span>Subtotal Produk</span>
              <span id="sum-subtotal" data-value="<?= $subtotal ?>">Rp <?= number_format($subtotal, 0, ',', '.') ?></span>
            </div>
            <div class="summary-row">
              <span>Ongkos Kirim</span>
              <span id="sum-ongkir" data-value="15000">Rp 15.000</span>
            </div>
            <hr>
            <div class="summary-row summary-total">
              <span>Total Bayar</span>
              <span id="sum-total"><?= 'Rp ' . number_format($subtotal + 15000, 0, ',', '.') ?></span>
            </div>

            <input type="hidden" name="subtotal" value="<?= $subtotal ?>">
            <input type="hidden" name="ongkir"   id="hidden-ongkir" value="15000">
            <input type="hidden" name="total"    id="hidden-total"   value="<?= $subtotal + 15000 ?>">

            <button type="submit" class="btn-checkout-submit" id="btn-checkout-submit">
              <i class="bi bi-arrow-right-circle-fill me-2"></i>Lanjut ke Pembayaran
            </button>

            <a href="<?= BASE_URL ?>pelanggan/keranjang.php" class="btn-back-cart">
              <i class="bi bi-arrow-left"></i> Kembali ke Keranjang
            </a>

            <div class="summary-secure">
              <i class="bi bi-shield-check"></i>
              Pembayaran aman via Midtrans
            </div>
          </div>
        </div>

      </div>
    </form>

  </div>
</main>

<?php require __DIR__ . '/../templates/footer.php'; ?>
