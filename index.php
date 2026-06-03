<?php
/**
 * INDEX.PHP — Landing Page Toko Sakinah
 * Hero + Keunggulan + Produk Populer + Kategori Highlight + Testimoni
 */

$page_title  = 'Toko Sakinah Online — Baju Sekolah & Oleh-Oleh Haji Palembang';
$active_page = 'beranda';
$active_tab  = 'semua';

require __DIR__ . '/templates/header.php';
require __DIR__ . '/templates/navbar.php';
?>

<style>
/* ── Hero ──────────────────────────────────────── */
.hero-section {
  background: linear-gradient(135deg, #FCE4EC 0%, #F8BBD0 40%, #F48FB1 100%);
  padding: 60px 0 50px;
  position: relative;
  overflow: hidden;
}
.hero-section::after {
  content: '';
  position: absolute;
  bottom: -2px;
  left: 0;
  right: 0;
  height: 60px;
  background: var(--white);
  clip-path: ellipse(55% 100% at 50% 100%);
}
.hero-content { position: relative; z-index: 2; }
.hero-content h1 {
  font-family: var(--font-heading);
  font-weight: 800;
  font-size: 2.4rem;
  color: var(--gray-900);
  line-height: 1.25;
  margin-bottom: 16px;
}
.hero-content p {
  font-size: 15px;
  color: var(--gray-700);
  max-width: 480px;
  margin-bottom: 28px;
  line-height: 1.7;
}
.hero-img-wrapper { text-align: center; }
.hero-img-wrapper img {
  max-height: 320px;
  filter: drop-shadow(0 12px 30px rgba(0,0,0,0.15));
  animation: floatY 3s ease-in-out infinite;
}
@keyframes floatY {
  0%,100% { transform: translateY(0); }
  50% { transform: translateY(-12px); }
}

/* ── Keunggulan ────────────────────────────────── */
.advantage-card {
  text-align: center;
  padding: 28px 18px;
  border-radius: var(--radius-lg);
  background: var(--white);
  border: 1px solid var(--gray-300);
  transition: var(--transition);
  height: 100%;
}
.advantage-card:hover {
  transform: translateY(-6px);
  box-shadow: var(--shadow-lg);
  border-color: var(--pink-primary);
}
.advantage-card .adv-icon {
  width: 56px; height: 56px;
  border-radius: 50%;
  background: var(--pink-light);
  display: flex; align-items: center; justify-content: center;
  margin: 0 auto 16px;
  font-size: 24px;
  color: var(--pink-primary);
  transition: var(--transition);
}
.advantage-card:hover .adv-icon {
  background: var(--pink-primary);
  color: var(--white);
}
.advantage-card h5 {
  font-family: var(--font-heading);
  font-weight: 600;
  font-size: 14.5px;
  margin-bottom: 8px;
}
.advantage-card p { font-size: 12.5px; color: var(--gray-500); margin: 0; }

/* ── Kategori Highlight ────────────────────────── */
.kategori-card {
  position: relative;
  border-radius: var(--radius-lg);
  overflow: hidden;
  height: 220px;
  display: flex;
  align-items: flex-end;
  transition: var(--transition);
}
.kategori-card:hover { transform: scale(1.02); }
.kategori-card img {
  position: absolute;
  inset: 0;
  width: 100%; height: 100%;
  object-fit: cover;
  transition: transform 0.6s ease;
}
.kategori-card:hover img { transform: scale(1.08); }
.kategori-card .overlay {
  position: relative;
  z-index: 2;
  width: 100%;
  padding: 24px;
  background: linear-gradient(transparent, rgba(0,0,0,0.75));
}
.kategori-card .overlay h4 {
  color: var(--white);
  font-family: var(--font-heading);
  font-weight: 700;
  font-size: 20px;
  margin-bottom: 4px;
}
.kategori-card .overlay p {
  color: rgba(255,255,255,0.75);
  font-size: 13px;
  margin-bottom: 12px;
}
.kategori-card .overlay .btn-sm {
  background: var(--white);
  color: var(--pink-primary);
  font-weight: 600;
  font-size: 12.5px;
  border-radius: var(--radius-full);
  padding: 7px 20px;
  border: none;
  transition: var(--transition);
}
.kategori-card .overlay .btn-sm:hover {
  background: var(--pink-primary);
  color: var(--white);
}

/* ── Testimoni ─────────────────────────────────── */
.testimoni-card {
  background: var(--white);
  border: 1px solid var(--gray-300);
  border-radius: var(--radius-lg);
  padding: 24px;
  height: 100%;
  transition: var(--transition);
}
.testimoni-card:hover { box-shadow: var(--shadow-md); }
.testimoni-stars { color: var(--gold-rating); font-size: 14px; margin-bottom: 12px; }
.testimoni-text {
  font-size: 13.5px;
  color: var(--gray-700);
  line-height: 1.7;
  margin-bottom: 16px;
  font-style: italic;
}
.testimoni-author {
  display: flex;
  align-items: center;
  gap: 12px;
}
.testimoni-avatar {
  width: 40px; height: 40px;
  border-radius: 50%;
  background: var(--pink-light);
  display: flex; align-items: center; justify-content: center;
  font-weight: 700;
  color: var(--pink-primary);
  font-size: 15px;
}
.testimoni-name {
  font-family: var(--font-heading);
  font-weight: 600;
  font-size: 13.5px;
}
.testimoni-label { font-size: 11.5px; color: var(--gray-500); }

@media (max-width: 767.98px) {
  .hero-content h1 { font-size: 1.6rem; }
  .hero-img-wrapper img { max-height: 200px; }
  .hero-section { padding: 40px 0 36px; }
  .kategori-card { height: 170px; }
}
</style>

<!-- ═══ HERO SECTION ═══ -->
<section class="hero-section">
  <div class="container">
    <div class="row align-items-center">
      <div class="col-lg-6 hero-content">
        <h1>Belanja Kebutuhan<br>Sekolah & Haji<br>di <span class="text-pink">Toko Sakinah</span></h1>
        <p>Temukan koleksi baju sekolah, perlengkapan haji, dan oleh-oleh haji berkualitas dengan harga terjangkau. Trusted since 2015.</p>
        <div class="d-flex gap-3 flex-wrap">
          <a href="<?= BASE_URL ?>katalog.php" class="btn-pink" style="display:inline-block;text-decoration:none;">
            <i class="bi bi-bag-heart me-2"></i>Belanja Sekarang
          </a>
          <a href="<?= BASE_URL ?>pelanggan/custom_order.php" class="btn-outline-pink" style="display:inline-block;text-decoration:none;">
            <i class="bi bi-pencil-square me-2"></i>Custom Order
          </a>
        </div>
      </div>
      <div class="col-lg-6 hero-img-wrapper d-none d-lg-block">
        <img src="<?= BASE_URL ?>data/logo_toko.png" alt="Toko Sakinah" style="max-height:280px;">
      </div>
    </div>
  </div>
</section>

<!-- ═══ KEUNGGULAN ═══ -->
<section class="py-5">
  <div class="container">
    <div class="row g-3">
      <div class="col-6 col-md-3">
        <div class="advantage-card animate-on-scroll">
          <div class="adv-icon"><i class="bi bi-shield-check"></i></div>
          <h5>Produk Original</h5>
          <p>Semua produk dijamin 100% original dan berkualitas tinggi</p>
        </div>
      </div>
      <div class="col-6 col-md-3">
        <div class="advantage-card animate-on-scroll delay-1">
          <div class="adv-icon"><i class="bi bi-truck"></i></div>
          <h5>Gratis Ongkir</h5>
          <p>Gratis ongkir untuk pembelian di atas Rp 1.000.000</p>
        </div>
      </div>
      <div class="col-6 col-md-3">
        <div class="advantage-card animate-on-scroll delay-2">
          <div class="adv-icon"><i class="bi bi-credit-card"></i></div>
          <h5>Pembayaran Aman</h5>
          <p>Transaksi aman via Midtrans dengan berbagai metode bayar</p>
        </div>
      </div>
      <div class="col-6 col-md-3">
        <div class="advantage-card animate-on-scroll delay-3">
          <div class="adv-icon"><i class="bi bi-palette"></i></div>
          <h5>Custom Order</h5>
          <p>Layanan pemesanan seragam custom untuk institusi Anda</p>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- ═══ PRODUK POPULER ═══ -->
<section class="pb-5">
  <div class="container">
    <div class="d-flex justify-content-between align-items-center mb-4">
      <h2 class="section-title mb-0"><i class="bi bi-fire text-pink me-2"></i>Produk Populer</h2>
      <a href="<?= BASE_URL ?>katalog.php?sort=sold_desc" class="text-pink" style="font-size:13.5px;font-weight:600;">
        Lihat Semua <i class="bi bi-arrow-right"></i>
      </a>
    </div>

    <div class="row g-3">
      <?php
      $sql = "SELECT p.*, k.nama_kategori, k.slug as kategori_slug 
              FROM produk p 
              JOIN kategori k ON p.id_kategori = k.id_kategori 
              WHERE p.status = 'aktif' 
              ORDER BY p.sold DESC 
              LIMIT 8";
      $result = $conn->query($sql);

      if ($result && $result->num_rows > 0):
        while ($produk = $result->fetch_assoc()):
          $price_by_size = json_decode($produk['price_by_size'], true) ?: [];
          $prices = array_values($price_by_size);
          $min_price = !empty($prices) ? min($prices) : $produk['harga'];
          $max_price = !empty($prices) ? max($prices) : $produk['harga'];
      ?>
        <div class="col-6 col-md-4 col-lg-3">
          <div class="product-card animate-on-scroll">
            <?php if (!empty($produk['badge'])): ?>
              <span class="<?= $produk['badge'] === 'Terlaris' ? 'badge-terlaris' : 'badge-baru' ?>">
                <?= htmlspecialchars($produk['badge']) ?>
              </span>
            <?php endif; ?>
            <div class="card-img-wrapper">
              <a href="<?= BASE_URL ?>produk_detail.php?id=<?= $produk['id_produk'] ?>">
                <img src="<?= BASE_URL . htmlspecialchars($produk['foto']) ?>"
                     alt="<?= htmlspecialchars($produk['nama_produk']) ?>" loading="lazy">
              </a>
            </div>
            <div class="card-body">
              <div class="card-subcategory"><?= htmlspecialchars($produk['subcategory'] ?: $produk['nama_kategori']) ?></div>
              <h3 class="card-title">
                <a href="<?= BASE_URL ?>produk_detail.php?id=<?= $produk['id_produk'] ?>" style="color:inherit;text-decoration:none;">
                  <?= htmlspecialchars($produk['nama_produk']) ?>
                </a>
              </h3>
              <div class="card-price-main">Mulai Rp <?= number_format($min_price, 0, ',', '.') ?></div>
              <?php if ($min_price !== $max_price): ?>
                <div class="card-price-range">Rp <?= number_format($min_price, 0, ',', '.') ?> — Rp <?= number_format($max_price, 0, ',', '.') ?></div>
              <?php endif; ?>
              <div class="card-meta">
                <?php if ($produk['rating'] > 0): ?>
                  <span class="rating"><i class="bi bi-star-fill"></i> <?= number_format($produk['rating'], 1) ?></span>
                  <span>·</span>
                <?php endif; ?>
                <span><?= number_format($produk['sold']) ?> terjual</span>
              </div>
            </div>
          </div>
        </div>
      <?php endwhile; endif; ?>
    </div>
  </div>
</section>

<!-- ═══ KATEGORI HIGHLIGHT ═══ -->
<section class="pb-5">
  <div class="container">
    <h2 class="section-title"><i class="bi bi-grid text-pink me-2"></i>Jelajahi Kategori</h2>
    <div class="row g-3">
      <div class="col-md-6">
        <a href="<?= BASE_URL ?>katalog.php?kategori=baju-sekolah" style="text-decoration:none;">
          <div class="kategori-card">
            <img src="<?= BASE_URL ?>data/baju-sekolah/set-pramuka-cowo.jpeg" alt="Baju Sekolah">
            <div class="overlay">
              <h4>Baju Sekolah</h4>
              <p>Seragam lengkap SD, SMP, SMA, Pramuka & Muslim</p>
              <span class="btn-sm">Lihat Koleksi →</span>
            </div>
          </div>
        </a>
      </div>
      <div class="col-md-6">
        <a href="<?= BASE_URL ?>katalog.php?kategori=oleh-oleh-haji" style="text-decoration:none;">
          <div class="kategori-card">
            <img src="<?= BASE_URL ?>data/oleh-oleh-haji/kurma-sukari.jpeg" alt="Oleh-Oleh Haji">
            <div class="overlay">
              <h4>Oleh-Oleh Haji</h4>
              <p>Kurma, kacang, coklat, peci, sajadah premium</p>
              <span class="btn-sm">Lihat Koleksi →</span>
            </div>
          </div>
        </a>
      </div>
    </div>
  </div>
</section>

<!-- ═══ TESTIMONI ═══ -->
<section class="pb-5">
  <div class="container">
    <h2 class="section-title"><i class="bi bi-chat-heart text-pink me-2"></i>Kata Pelanggan</h2>
    <div class="row g-3">
      <?php
      $testimoni = [
        ['nama' => 'Aisyah R.', 'text' => 'Baju sekolah anak saya dari Toko Sakinah kualitasnya bagus banget. Bahannya adem, jahitan rapi, dan harganya terjangkau. Pasti balik lagi!', 'rating' => 5, 'label' => 'Beli Set Pramuka'],
        ['nama' => 'Hasan M.', 'text' => 'Kurma Ajwa-nya asli Madinah, rasanya beda sama yang lain. Packing rapi dan pengiriman cepat. Recommended buat oleh-oleh haji!', 'rating' => 5, 'label' => 'Beli Kurma Ajwa'],
        ['nama' => 'Siti N.', 'text' => 'Pesan seragam custom untuk sekolah kami. Prosesnya mudah, hasilnya memuaskan. Kualitas bahan premium dan tepat waktu. Terima kasih!', 'rating' => 5, 'label' => 'Custom Order Seragam'],
      ];
      foreach ($testimoni as $t):
      ?>
        <div class="col-md-4">
          <div class="testimoni-card animate-on-scroll">
            <div class="testimoni-stars">
              <?php for ($i = 0; $i < $t['rating']; $i++): ?>
                <i class="bi bi-star-fill"></i>
              <?php endfor; ?>
            </div>
            <p class="testimoni-text">"<?= $t['text'] ?>"</p>
            <div class="testimoni-author">
              <div class="testimoni-avatar"><?= strtoupper($t['nama'][0]) ?></div>
              <div>
                <div class="testimoni-name"><?= $t['nama'] ?></div>
                <div class="testimoni-label"><?= $t['label'] ?></div>
              </div>
            </div>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<?php require __DIR__ . '/templates/footer.php'; ?>
