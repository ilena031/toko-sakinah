<?php
/**
 * ADMIN HEADER TEMPLATE
 * Include after auth_check.php & config.php
 * Variables: $page_title, $active_menu (dashboard|produk|kategori|pesanan|custom|laporan)
 */

if (!defined('BASE_URL')) {
    require_once __DIR__ . '/../../config/config.php';
}

$page_title  = $page_title  ?? 'Admin — Toko Sakinah';
$active_menu = $active_menu ?? '';
$admin_nama  = $_SESSION['user_nama'] ?? 'Admin';

$menus = [
    'dashboard' => ['icon' => 'bi-speedometer2',     'label' => 'Dashboard',     'href' => 'dashboard.php'],
    'produk'    => ['icon' => 'bi-box-seam',         'label' => 'Produk',        'href' => 'produk/index.php'],
    'kategori'  => ['icon' => 'bi-tags',             'label' => 'Kategori',      'href' => 'kategori/index.php'],
    'pesanan'   => ['icon' => 'bi-receipt',          'label' => 'Pesanan',       'href' => 'pesanan/index.php'],
    'custom'    => ['icon' => 'bi-palette2',         'label' => 'Custom Order',  'href' => 'custom_order/index.php'],
    'laporan'   => ['icon' => 'bi-bar-chart-fill',   'label' => 'Laporan',       'href' => 'laporan/penjualan.php'],
];
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= htmlspecialchars($page_title) ?></title>
  <link rel="icon" type="image/png" href="<?= BASE_URL ?>data/logo_toko.png">

  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
  <link href="https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap5.min.css" rel="stylesheet">

  <link rel="stylesheet" href="<?= BASE_URL ?>assets/css/style.css">
  <link rel="stylesheet" href="<?= BASE_URL ?>assets/css/admin.css">

  <?php if (!empty($extra_css)): ?>
    <link rel="stylesheet" href="<?= BASE_URL ?>assets/css/<?= $extra_css ?>">
  <?php endif; ?>

  <script>
    // Apply collapsed state ASAP (before render) untuk hindari flash
    (function () {
      if (localStorage.getItem('admin_sidebar_collapsed') === '1') {
        document.documentElement.classList.add('sidebar-collapsed');
      }
    })();
  </script>
</head>
<body>

<!-- ═══ SIDEBAR ═══ -->
<aside class="admin-sidebar" id="admin-sidebar">
  <div class="sidebar-brand">
    <img src="<?= BASE_URL ?>data/logo_toko.png" alt="Logo">
    <div class="brand-text">
      <div class="brand-title">Toko Sakinah</div>
      <div class="brand-sub">Admin Panel</div>
    </div>
    <button type="button" class="sidebar-collapse-btn" id="sidebar-collapse-btn" title="Sembunyikan sidebar">
      <i class="bi bi-chevron-double-left"></i>
    </button>
  </div>

  <nav class="sidebar-menu">
    <?php foreach ($menus as $key => $m): ?>
      <a href="<?= BASE_URL ?>admin/<?= $m['href'] ?>"
         class="sidebar-link <?= $active_menu === $key ? 'active' : '' ?>"
         data-tooltip="<?= htmlspecialchars($m['label']) ?>">
        <i class="bi <?= $m['icon'] ?>"></i>
        <span><?= $m['label'] ?></span>
      </a>
    <?php endforeach; ?>
  </nav>

  <div class="sidebar-footer">
    <a href="<?= BASE_URL ?>" target="_blank" class="sidebar-link" data-tooltip="Lihat Toko">
      <i class="bi bi-box-arrow-up-right"></i>
      <span>Lihat Toko</span>
    </a>
    <a href="<?= BASE_URL ?>pelanggan/logout.php" class="sidebar-link logout" data-tooltip="Logout">
      <i class="bi bi-box-arrow-right"></i>
      <span>Logout</span>
    </a>
  </div>
</aside>

<!-- ═══ MAIN AREA ═══ -->
<div class="admin-main">

  <!-- Topbar -->
  <header class="admin-topbar">
    <button type="button" class="sidebar-toggle" id="sidebar-toggle" title="Tampilkan menu">
      <i class="bi bi-list"></i>
    </button>
    <h1 class="topbar-title"><?= htmlspecialchars($page_title) ?></h1>
    <div class="topbar-actions">
      <a href="<?= BASE_URL ?>" target="_blank" class="topbar-link" title="Lihat toko">
        <i class="bi bi-shop"></i>
      </a>
      <div class="topbar-user">
        <i class="bi bi-person-circle"></i>
        <span><?= htmlspecialchars($admin_nama) ?></span>
      </div>
    </div>
  </header>

  <!-- Content -->
  <main class="admin-content">
    <?php
    if (!empty($_SESSION['flash_success'])):
    ?>
      <div class="alert alert-success alert-dismissible fade show">
        <i class="bi bi-check-circle"></i> <?= htmlspecialchars($_SESSION['flash_success']) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
      </div>
      <?php unset($_SESSION['flash_success']); endif; ?>

    <?php if (!empty($_SESSION['flash_error'])): ?>
      <div class="alert alert-danger alert-dismissible fade show">
        <i class="bi bi-exclamation-circle"></i> <?= htmlspecialchars($_SESSION['flash_error']) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
      </div>
      <?php unset($_SESSION['flash_error']); endif; ?>
