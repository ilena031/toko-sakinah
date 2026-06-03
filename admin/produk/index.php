<?php
/**
 * ADMIN PRODUK INDEX — List semua produk + filter + DataTables
 */

require_once __DIR__ . '/../../config/config.php';
require __DIR__ . '/../auth_check.php';

$page_title  = 'Manajemen Produk';
$active_menu = 'produk';

// Filter
$filter_kategori = (int) ($_GET['kategori'] ?? 0);
$filter_status   = $_GET['status'] ?? '';

$where = ['1=1'];
$params = [];
$types  = '';
if ($filter_kategori) {
    $where[] = 'p.id_kategori = ?';
    $params[] = $filter_kategori;
    $types .= 'i';
}
if (in_array($filter_status, ['aktif', 'nonaktif'], true)) {
    $where[] = 'p.status = ?';
    $params[] = $filter_status;
    $types .= 's';
}

$sql = "SELECT p.*, k.nama_kategori
        FROM produk p JOIN kategori k ON p.id_kategori = k.id_kategori
        WHERE " . implode(' AND ', $where) . "
        ORDER BY p.id_produk DESC";

$stmt = $conn->prepare($sql);
if ($types) $stmt->bind_param($types, ...$params);
$stmt->execute();
$produk_list = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Kategori untuk filter
$kategori_all = $conn->query("SELECT id_kategori, nama_kategori FROM kategori ORDER BY nama_kategori")
                     ->fetch_all(MYSQLI_ASSOC);

require __DIR__ . '/../templates/header.php';
?>

<div class="adm-card">
  <div class="adm-card-header">
    <h6><i class="bi bi-box-seam text-pink"></i> Daftar Produk (<?= count($produk_list) ?>)</h6>
    <a href="<?= BASE_URL ?>admin/produk/tambah.php" class="btn btn-pink-sm">
      <i class="bi bi-plus-circle"></i> Tambah Produk
    </a>
  </div>
  <div class="adm-card-body">

    <!-- Filter -->
    <form method="GET" class="row g-2 mb-3">
      <div class="col-md-4">
        <select name="kategori" class="form-select form-select-sm" onchange="this.form.submit()">
          <option value="0">Semua Kategori</option>
          <?php foreach ($kategori_all as $k): ?>
            <option value="<?= $k['id_kategori'] ?>" <?= $filter_kategori == $k['id_kategori'] ? 'selected' : '' ?>>
              <?= htmlspecialchars($k['nama_kategori']) ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-md-3">
        <select name="status" class="form-select form-select-sm" onchange="this.form.submit()">
          <option value="">Semua Status</option>
          <option value="aktif"    <?= $filter_status === 'aktif' ? 'selected' : '' ?>>Aktif</option>
          <option value="nonaktif" <?= $filter_status === 'nonaktif' ? 'selected' : '' ?>>Nonaktif</option>
        </select>
      </div>
      <?php if ($filter_kategori || $filter_status): ?>
        <div class="col-auto">
          <a href="<?= BASE_URL ?>admin/produk/index.php" class="btn btn-sm btn-outline-secondary">
            <i class="bi bi-x"></i> Reset
          </a>
        </div>
      <?php endif; ?>
    </form>

    <div class="table-responsive">
      <table class="table table-hover datatable">
        <thead>
          <tr>
            <th>Foto</th>
            <th>Nama Produk</th>
            <th>Kategori</th>
            <th>Subkategori</th>
            <th>Harga</th>
            <th>Stok</th>
            <th>Sold</th>
            <th>Status</th>
            <th>Aksi</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($produk_list as $p):
            $stock_by_size = json_decode($p['stock_by_size'], true) ?: [];
            $total_stok = array_sum($stock_by_size) ?: (int) $p['stok_total'];
            $price_by_size = json_decode($p['price_by_size'], true) ?: [];
            $min_price = !empty($price_by_size) ? min($price_by_size) : (float) $p['harga'];
            $max_price = !empty($price_by_size) ? max($price_by_size) : (float) $p['harga'];
          ?>
            <tr>
              <td>
                <img src="<?= BASE_URL . htmlspecialchars($p['foto']) ?>" alt=""
                     style="width:50px;height:50px;object-fit:cover;border-radius:6px;">
              </td>
              <td>
                <div class="fw-semibold"><?= htmlspecialchars($p['nama_produk']) ?></div>
                <?php if ($p['tryon_enabled']): ?>
                  <span class="adm-badge badge-info" style="font-size:9.5px;">
                    <i class="bi bi-camera2"></i> Try-On
                  </span>
                <?php endif; ?>
              </td>
              <td><?= htmlspecialchars($p['nama_kategori']) ?></td>
              <td><?= htmlspecialchars($p['subcategory'] ?: '-') ?></td>
              <td>
                <?php if ($min_price === $max_price): ?>
                  Rp <?= number_format($min_price, 0, ',', '.') ?>
                <?php else: ?>
                  Rp <?= number_format($min_price, 0, ',', '.') ?><br>
                  <small class="text-muted">— Rp <?= number_format($max_price, 0, ',', '.') ?></small>
                <?php endif; ?>
              </td>
              <td>
                <?php if ($total_stok <= 0): ?>
                  <span class="text-danger fw-bold">Habis</span>
                <?php elseif ($total_stok <= 10): ?>
                  <span class="text-warning fw-bold"><?= $total_stok ?></span>
                <?php else: ?>
                  <?= $total_stok ?>
                <?php endif; ?>
              </td>
              <td><?= number_format($p['sold']) ?></td>
              <td>
                <span class="adm-badge <?= $p['status'] === 'aktif' ? 'badge-success' : 'badge-secondary' ?>">
                  <?= htmlspecialchars($p['status']) ?>
                </span>
              </td>
              <td style="white-space:nowrap;">
                <a href="<?= BASE_URL ?>produk_detail.php?id=<?= $p['id_produk'] ?>" target="_blank" class="btn-icon" title="Preview">
                  <i class="bi bi-eye"></i>
                </a>
                <a href="<?= BASE_URL ?>admin/produk/edit.php?id=<?= $p['id_produk'] ?>" class="btn-icon" title="Edit">
                  <i class="bi bi-pencil-square"></i>
                </a>
                <a href="<?= BASE_URL ?>admin/produk/hapus.php?id=<?= $p['id_produk'] ?>"
                   onclick="return confirm('Yakin hapus produk ini? Tidak bisa di-undo.')"
                   class="btn-icon text-danger" title="Hapus">
                  <i class="bi bi-trash"></i>
                </a>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<?php require __DIR__ . '/../templates/footer.php'; ?>
