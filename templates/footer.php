<?php
/**
 * FOOTER TEMPLATE — Toko Sakinah
 * Include di akhir setiap halaman:
 *   require 'templates/footer.php';
 * 
 * Optional variables:
 *   $extra_js — JavaScript tambahan khusus halaman
 */
?>

<!-- ═══ FOOTER ═══ -->
<footer class="main-footer" id="main-footer">
  <div class="container">
    <div class="row g-4">

      <!-- Kolom 1: Brand & Info Toko -->
      <div class="col-lg-4 col-md-6">
        <div class="footer-brand">
          <img src="<?= BASE_URL ?>data/logo_toko.png" alt="Logo Toko Sakinah">
          <h5>Toko Sakinah</h5>
          <p>Pusat baju sekolah, perlengkapan haji & oleh-oleh haji terlengkap dan terpercaya di Palembang.</p>

          <div class="footer-contact-item">
            <i class="bi bi-geo-alt-fill"></i>
            <span>Lr. Basah Permai,<br>Palembang, Sumatera Selatan</span>
          </div>
          <div class="footer-contact-item">
            <i class="bi bi-whatsapp"></i>
            <span>0813-7374-1040</span>
          </div>
          <div class="footer-contact-item">
            <i class="bi bi-clock-fill"></i>
            <span>Senin – Sabtu, 08:00 – 17:00 WIB</span>
          </div>
        </div>
      </div>

      <!-- Kolom 2: Navigasi -->
      <div class="col-lg-2 col-md-6 col-6">
        <h6 class="footer-heading">Navigasi</h6>
        <ul class="footer-links">
          <li><a href="<?= BASE_URL ?>">Beranda</a></li>
          <li><a href="<?= BASE_URL ?>katalog.php">Katalog</a></li>
          <li><a href="<?= BASE_URL ?>pelanggan/dashboard.php">Profil Saya</a></li>
          <li><a href="<?= BASE_URL ?>pelanggan/keranjang.php">Keranjang</a></li>
          <li><a href="<?= BASE_URL ?>pelanggan/dashboard.php?tab=pesanan">Pesanan</a></li>
          <li><a href="<?= BASE_URL ?>pelanggan/try_on.php">Virtual Try-On</a></li>
        </ul>
      </div>

      <!-- Kolom 3: Kategori -->
      <div class="col-lg-2 col-md-6 col-6">
        <h6 class="footer-heading">Kategori</h6>
        <ul class="footer-links">
          <li><a href="<?= BASE_URL ?>katalog.php?kategori=baju-sekolah">Baju Sekolah</a></li>
          <li><a href="<?= BASE_URL ?>katalog.php?kategori=oleh-oleh-haji">Oleh-Oleh Haji</a></li>
          <li><a href="<?= BASE_URL ?>pelanggan/custom_order.php">Custom Order</a></li>
        </ul>
      </div>

      <!-- Kolom 4: Ikuti Kami -->
      <div class="col-lg-4 col-md-6">
        <h6 class="footer-heading">Ikuti Kami</h6>
        <p style="font-size:13px; color:rgba(255,255,255,0.6); margin-bottom:16px;">
          Follow kami untuk info promo, produk baru, dan inspirasi terkini!
        </p>
        <div class="social-icons">
          <a href="https://instagram.com/toko.sakinahh" target="_blank" class="social-icon" title="Instagram">
            <i class="bi bi-instagram"></i>
          </a>
          <a href="https://wa.me/6281373741040" target="_blank" class="social-icon" title="WhatsApp">
            <i class="bi bi-whatsapp"></i>
          </a>
        </div>
      </div>

    </div>
  </div>

  <!-- Copyright Bar -->
  <div class="footer-bottom">
    <div class="container">
      &copy; <?= date('Y') ?> <span>Toko Sakinah</span> Palembang. All rights reserved.
    </div>
  </div>
</footer>

<!-- ═══ SCRIPTS ═══ -->
<!-- Bootstrap 5.3 JS Bundle -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<!-- Custom JS -->
<script src="<?= BASE_URL ?>assets/js/main.js"></script>

<?php if (!empty($extra_js)): ?>
  <script src="<?= BASE_URL ?>assets/js/<?= $extra_js ?>"></script>
<?php endif; ?>

</body>

</html>