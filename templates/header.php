<?php
/**
 * HEADER TEMPLATE — Toko Sakinah
 * Include di awal setiap halaman:
 *   $page_title = 'Judul Halaman';
 *   require 'templates/header.php';
 * 
 * Optional variables:
 *   $page_title  — judul tab browser (default: 'Toko Sakinah Online')
 *   $extra_css    — CSS tambahan khusus halaman
 */

if (!defined('BASE_URL')) {
  require_once __DIR__ . '/../config/config.php';
}

$page_title = $page_title ?? APP_NAME;
$cart_count = 0;
if (isset($_SESSION['cart']) && is_array($_SESSION['cart'])) {
  foreach ($_SESSION['cart'] as $item) {
    $cart_count += (int) ($item['jumlah'] ?? 0);
  }
}
$is_logged_in = isset($_SESSION['user_id']);
$user_name = $_SESSION['user_nama'] ?? '';
?>
<!DOCTYPE html>
<html lang="id">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="description"
    content="Toko Sakinah Palembang — Pusat baju sekolah, perlengkapan haji & oleh-oleh haji terlengkap dan terpercaya.">
  <title><?= htmlspecialchars($page_title) ?></title>

  <!-- Favicon -->
  <link rel="icon" type="image/png" href="<?= BASE_URL ?>data/logo_toko.png">

  <!-- Bootstrap 5.3 CSS -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

  <!-- Bootstrap Icons -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">

  <!-- Google Fonts (Poppins + Inter) -->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>

  <!-- Custom CSS -->
  <link rel="stylesheet" href="<?= BASE_URL ?>assets/css/style.css">

  <?php if (!empty($extra_css)): ?>
    <link rel="stylesheet" href="<?= BASE_URL ?>assets/css/<?= $extra_css ?>">
  <?php endif; ?>
</head>

<body>

  <!-- ═══ MAIN HEADER ═══ -->
  <header class="main-header" id="main-header">
    <div class="container">
      <div class="header-inner">

        <!-- Logo -->
        <a href="<?= BASE_URL ?>" class="logo-area" id="logo-link">
          <img src="<?= BASE_URL ?>data/logo_toko.png" alt="Logo Toko Sakinah">
          <div class="logo-text">
            <span class="brand-name">Toko Sakinah</span>
            <span class="brand-sub">Palembang</span>
          </div>
        </a>

        <!-- Search Bar -->
        <div class="search-wrapper">
          <form action="<?= BASE_URL ?>katalog.php" method="GET" id="search-form">
            <i class="bi bi-search search-icon"></i>
            <input type="text" name="q" class="form-control" placeholder="Cari produk di Toko Sakinah..."
              id="search-input" autocomplete="off" value="<?= htmlspecialchars($_GET['q'] ?? '') ?>">
          </form>
        </div>

        <!-- Nav Icons -->
        <nav class="header-nav" id="header-nav">
          <a href="<?= BASE_URL ?>" class="nav-icon-link <?= ($active_page ?? '') === 'beranda' ? 'active' : '' ?>"
            id="nav-beranda">
            <i class="bi bi-house-door"></i>
            <span>BERANDA</span>
          </a>

          <?php if ($is_logged_in): ?>
            <a href="<?= BASE_URL ?>pelanggan/dashboard.php"
              class="nav-icon-link <?= ($active_page ?? '') === 'profil' ? 'active' : '' ?>" id="nav-profil">
              <i class="bi bi-person"></i>
              <span>PROFIL</span>
            </a>
          <?php else: ?>
            <a href="<?= BASE_URL ?>pelanggan/login.php"
              class="nav-icon-link <?= ($active_page ?? '') === 'profil' ? 'active' : '' ?>" id="nav-profil">
              <i class="bi bi-person"></i>
              <span>PROFIL</span>
            </a>
          <?php endif; ?>

          <a href="<?= BASE_URL ?>pelanggan/keranjang.php"
            class="nav-icon-link <?= ($active_page ?? '') === 'keranjang' ? 'active' : '' ?>" id="nav-keranjang">
            <i class="bi bi-bag"></i>
            <span>KERANJANG</span>
            <?php if ($cart_count > 0): ?>
              <span class="cart-badge" id="cart-badge"><?= $cart_count ?></span>
            <?php endif; ?>
          </a>
        </nav>

      </div>
    </div>
  </header>