<?php
/**
 * KATALOG.PHP — Halaman Katalog Produk
 * Filter sidebar + sorting + pagination
 * URL params: ?kategori=&subcategory=&q=&sort=&min_price=&max_price=&page=
 */

$page_title = 'Katalog Produk — Toko Sakinah';
$active_page = 'beranda';
$extra_css   = 'katalog.css';

// ── Ambil parameter filter ───────────────────────────────────
$filter_kategori   = $_GET['kategori'] ?? '';
$filter_subcategory = $_GET['subcategory'] ?? [];
if (is_string($filter_subcategory)) $filter_subcategory = $filter_subcategory ? [$filter_subcategory] : [];
$filter_q          = trim($_GET['q'] ?? '');
$filter_sort       = $_GET['sort'] ?? 'default';
$filter_min_price  = (int)($_GET['min_price'] ?? 0);
$filter_max_price  = (int)($_GET['max_price'] ?? 0);
$current_page      = max(1, (int)($_GET['page'] ?? 1));
$per_page          = 12;

// Active tab
$active_tab = $filter_kategori ?: 'semua';

require __DIR__ . '/templates/header.php';
require __DIR__ . '/templates/navbar.php';

// ── Build WHERE clause ───────────────────────────────────────
$where  = ["p.status = 'aktif'"];
$params = [];

if ($filter_kategori) {
    $where[]  = "k.slug = ?";
    $params[] = $filter_kategori;
}

if (!empty($filter_subcategory)) {
    $placeholders = implode(',', array_fill(0, count($filter_subcategory), '?'));
    $where[] = "p.subcategory IN ($placeholders)";
    $params = array_merge($params, $filter_subcategory);
}

if ($filter_q) {
    $where[]  = "(p.nama_produk LIKE ? OR p.deskripsi LIKE ? OR p.subcategory LIKE ?)";
    $like     = "%$filter_q%";
    $params[] = $like;
    $params[] = $like;
    $params[] = $like;
}

if ($filter_min_price > 0) {
    $where[]  = "p.harga >= ?";
    $params[] = $filter_min_price;
}

if ($filter_max_price > 0) {
    $where[]  = "p.harga <= ?";
    $params[] = $filter_max_price;
}

$where_sql = implode(' AND ', $where);

// ── Sort ─────────────────────────────────────────────────────
$order_map = [
    'default'    => 'p.id_produk DESC',
    'price_asc'  => 'p.harga ASC',
    'price_desc' => 'p.harga DESC',
    'sold_desc'  => 'p.sold DESC',
    'rating_desc'=> 'p.rating DESC',
    'newest'     => 'p.created_at DESC',
];
$order_sql = $order_map[$filter_sort] ?? $order_map['default'];

// ── Count total ──────────────────────────────────────────────
$count_sql = "SELECT COUNT(*) as total FROM produk p JOIN kategori k ON p.id_kategori = k.id_kategori WHERE $where_sql";
$stmt = $conn->prepare($count_sql);
if (!empty($params)) {
    $types = str_repeat('s', count($params));
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$total_produk = $stmt->get_result()->fetch_assoc()['total'];
$total_pages  = ceil($total_produk / $per_page);
$offset       = ($current_page - 1) * $per_page;
$stmt->close();

// ── Fetch products ───────────────────────────────────────────
$sql = "SELECT p.*, k.nama_kategori, k.slug as kategori_slug 
        FROM produk p 
        JOIN kategori k ON p.id_kategori = k.id_kategori 
        WHERE $where_sql 
        ORDER BY $order_sql 
        LIMIT $per_page OFFSET $offset";
$stmt = $conn->prepare($sql);
if (!empty($params)) {
    $types = str_repeat('s', count($params));
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$products = $stmt->get_result();
$stmt->close();

// ── Fetch subcategories with count (for sidebar) ─────────────
$subcat_where = "p.status = 'aktif'";
if ($filter_kategori) {
    $subcat_where .= " AND k.slug = '" . $conn->real_escape_string($filter_kategori) . "'";
}
$subcat_sql = "SELECT p.subcategory, COUNT(*) as cnt 
               FROM produk p 
               JOIN kategori k ON p.id_kategori = k.id_kategori 
               WHERE $subcat_where 
               GROUP BY p.subcategory 
               ORDER BY p.subcategory";
$subcategories = $conn->query($subcat_sql);

// ── Build current query string for pagination/sort ───────────
function buildUrl($overrides = []) {
    $params = $_GET;
    foreach ($overrides as $key => $val) {
        if ($val === null || $val === '') {
            unset($params[$key]);
        } else {
            $params[$key] = $val;
        }
    }
    return '?' . http_build_query($params);
}
?>

<!-- ═══ KATALOG CONTENT ═══ -->
<main class="page-content">
  <div class="container">
    <div class="row g-4">

      <!-- ── Mobile Filter Toggle ── -->
      <div class="col-12 filter-toggle-mobile">
        <button class="btn-toggle-filter" onclick="document.getElementById('filterSidebar').classList.toggle('show')">
          <i class="bi bi-funnel"></i> Filter Produk
        </button>
      </div>

      <!-- ── SIDEBAR FILTER ── -->
      <div class="col-lg-3 filter-sidebar-col" id="filterSidebar">
        <form action="<?= BASE_URL ?>katalog.php" method="GET" id="filter-form">
          <!-- Preserve kategori & sort -->
          <?php if ($filter_kategori): ?>
            <input type="hidden" name="kategori" value="<?= htmlspecialchars($filter_kategori) ?>">
          <?php endif; ?>
          <?php if ($filter_q): ?>
            <input type="hidden" name="q" value="<?= htmlspecialchars($filter_q) ?>">
          <?php endif; ?>
          <input type="hidden" name="sort" value="<?= htmlspecialchars($filter_sort) ?>">

          <div class="filter-sidebar">
            <!-- Kategori / Subcategory -->
            <div class="filter-section">
              <div class="filter-title">
                KATEGORI <i class="bi bi-chevron-down"></i>
              </div>
              <?php if ($subcategories && $subcategories->num_rows > 0): ?>
                <?php while ($sc = $subcategories->fetch_assoc()): ?>
                  <div class="filter-option">
                    <label>
                      <input type="checkbox" name="subcategory[]"
                             value="<?= htmlspecialchars($sc['subcategory']) ?>"
                             <?= in_array($sc['subcategory'], $filter_subcategory) ? 'checked' : '' ?>>
                      <?= htmlspecialchars($sc['subcategory']) ?>
                    </label>
                    <span class="count"><?= $sc['cnt'] ?></span>
                  </div>
                <?php endwhile; ?>
              <?php endif; ?>
            </div>

            <!-- Harga -->
            <div class="filter-section">
              <div class="filter-title">
                HARGA <i class="bi bi-chevron-down"></i>
              </div>
              <div class="filter-price-inputs">
                <input type="number" name="min_price" placeholder="Min"
                       value="<?= $filter_min_price ?: '' ?>">
                <span>—</span>
                <input type="number" name="max_price" placeholder="Max"
                       value="<?= $filter_max_price ?: '' ?>">
              </div>
            </div>

            <!-- Actions -->
            <div class="filter-actions">
              <button type="submit" class="btn-filter btn-apply">
                <i class="bi bi-funnel me-1"></i> Terapkan
              </button>
              <a href="<?= BASE_URL ?>katalog.php<?= $filter_kategori ? '?kategori=' . urlencode($filter_kategori) : '' ?>"
                 class="btn-filter btn-reset text-center" style="text-decoration:none;">
                Reset
              </a>
            </div>
          </div>
        </form>
      </div>

      <!-- ── PRODUCT GRID ── -->
      <div class="col-lg-9">
        <!-- Header: Count + Sort -->
        <div class="katalog-header">
          <div class="result-count">
            <strong><?= $total_produk ?></strong> produk ditemukan
            <?php if ($filter_q): ?>
              untuk "<em><?= htmlspecialchars($filter_q) ?></em>"
            <?php endif; ?>
          </div>
          <div class="sort-wrapper">
            <label>Urutkan:</label>
            <select onchange="window.location.href=this.value" id="sort-select">
              <?php
              $sort_options = [
                'default'     => 'Paling Sesuai',
                'price_asc'   => 'Harga Terendah',
                'price_desc'  => 'Harga Tertinggi',
                'sold_desc'   => 'Terlaris',
                'rating_desc' => 'Rating Tertinggi',
                'newest'      => 'Terbaru',
              ];
              foreach ($sort_options as $val => $label):
              ?>
                <option value="<?= buildUrl(['sort' => $val, 'page' => 1]) ?>"
                        <?= $filter_sort === $val ? 'selected' : '' ?>>
                  <?= $label ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>
        </div>

        <!-- Product Cards -->
        <?php if ($products && $products->num_rows > 0): ?>
          <div class="row g-3">
            <?php while ($produk = $products->fetch_assoc()):
              $price_by_size = json_decode($produk['price_by_size'], true) ?: [];
              $prices = array_values($price_by_size);
              $min_price = !empty($prices) ? min($prices) : $produk['harga'];
              $max_price = !empty($prices) ? max($prices) : $produk['harga'];
            ?>
              <div class="col-6 col-md-4 col-xl-3">
                <div class="product-card">
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
            <?php endwhile; ?>
          </div>

          <!-- Pagination -->
          <?php if ($total_pages > 1): ?>
            <nav class="pagination-wrapper">
              <ul class="pagination">
                <li class="page-item <?= $current_page <= 1 ? 'disabled' : '' ?>">
                  <a class="page-link" href="<?= buildUrl(['page' => $current_page - 1]) ?>">
                    <i class="bi bi-chevron-left"></i>
                  </a>
                </li>
                <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                  <li class="page-item <?= $i === $current_page ? 'active' : '' ?>">
                    <a class="page-link" href="<?= buildUrl(['page' => $i]) ?>"><?= $i ?></a>
                  </li>
                <?php endfor; ?>
                <li class="page-item <?= $current_page >= $total_pages ? 'disabled' : '' ?>">
                  <a class="page-link" href="<?= buildUrl(['page' => $current_page + 1]) ?>">
                    <i class="bi bi-chevron-right"></i>
                  </a>
                </li>
              </ul>
            </nav>
          <?php endif; ?>

        <?php else: ?>
          <div class="no-results">
            <i class="bi bi-search"></i>
            <h5>Produk tidak ditemukan</h5>
            <p>Coba ubah kata kunci pencarian atau filter yang digunakan.</p>
            <a href="<?= BASE_URL ?>katalog.php" class="btn-pink" style="display:inline-block;text-decoration:none;margin-top:12px;">
              Lihat Semua Produk
            </a>
          </div>
        <?php endif; ?>
      </div>

    </div>
  </div>
</main>

<?php require __DIR__ . '/templates/footer.php'; ?>
