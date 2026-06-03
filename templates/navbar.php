<?php
/**
 * NAVBAR TEMPLATE — Toko Sakinah
 * Tab navigasi kategori produk.
 * 
 * Set $active_tab sebelum include:
 *   $active_tab = 'semua';       // Semua Produk
 *   $active_tab = 'baju-sekolah';
 *   $active_tab = 'oleh-oleh-haji';
 */

$active_tab = $active_tab ?? 'semua';
?>

<!-- ═══ CATEGORY NAV TABS ═══ -->
<nav class="category-nav" id="category-nav">
  <div class="container">
    <ul class="category-tabs" id="category-tabs">
      <li>
        <a href="<?= BASE_URL ?>katalog.php"
           class="<?= $active_tab === 'semua' ? 'active' : '' ?>"
           id="tab-semua">
          SEMUA PRODUK
        </a>
      </li>
      <li>
        <a href="<?= BASE_URL ?>katalog.php?kategori=baju-sekolah"
           class="<?= $active_tab === 'baju-sekolah' ? 'active' : '' ?>"
           id="tab-baju-sekolah">
          BAJU SEKOLAH
        </a>
      </li>
      <li>
        <a href="<?= BASE_URL ?>katalog.php?kategori=oleh-oleh-haji"
           class="<?= $active_tab === 'oleh-oleh-haji' ? 'active' : '' ?>"
           id="tab-oleh-oleh-haji">
          OLEH-OLEH HAJI
        </a>
      </li>
    </ul>
  </div>
</nav>
