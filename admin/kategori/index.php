<?php
require_once __DIR__ . '/../../config/config.php';
require __DIR__ . '/../auth_check.php';

$page_title  = 'Manajemen Kategori';
$active_menu = 'kategori';

$kategori_list = $conn->query(
    "SELECT k.*, COUNT(p.id_produk) AS jml_produk
     FROM kategori k LEFT JOIN produk p ON k.id_kategori = p.id_kategori
     GROUP BY k.id_kategori
     ORDER BY k.nama_kategori"
)->fetch_all(MYSQLI_ASSOC);

require __DIR__ . '/../templates/header.php';
?>

<div class="adm-card">
  <div class="adm-card-header">
    <h6><i class="bi bi-tags text-pink"></i> Daftar Kategori (<?= count($kategori_list) ?>)</h6>
    <a href="<?= BASE_URL ?>admin/kategori/tambah.php" class="btn btn-pink-sm">
      <i class="bi bi-plus-circle"></i> Tambah Kategori
    </a>
  </div>
  <div class="adm-card-body">
    <div class="table-responsive">
      <table class="table table-hover datatable">
        <thead>
          <tr>
            <th>ID</th>
            <th>Nama Kategori</th>
            <th>Slug</th>
            <th>Deskripsi</th>
            <th>Jumlah Produk</th>
            <th>Aksi</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($kategori_list as $k): ?>
            <tr>
              <td><?= $k['id_kategori'] ?></td>
              <td><strong><?= htmlspecialchars($k['nama_kategori']) ?></strong></td>
              <td><code><?= htmlspecialchars($k['slug']) ?></code></td>
              <td><?= htmlspecialchars($k['deskripsi'] ?: '-') ?></td>
              <td><span class="adm-badge badge-info"><?= $k['jml_produk'] ?> produk</span></td>
              <td style="white-space:nowrap;">
                <a href="<?= BASE_URL ?>admin/kategori/edit.php?id=<?= $k['id_kategori'] ?>" class="btn-icon" title="Edit">
                  <i class="bi bi-pencil-square"></i>
                </a>
                <?php if ($k['jml_produk'] == 0): ?>
                  <a href="<?= BASE_URL ?>admin/kategori/hapus.php?id=<?= $k['id_kategori'] ?>"
                     onclick="return confirm('Yakin hapus kategori ini?')"
                     class="btn-icon text-danger" title="Hapus">
                    <i class="bi bi-trash"></i>
                  </a>
                <?php else: ?>
                  <span class="btn-icon text-muted" title="Tidak bisa hapus, ada produk">
                    <i class="bi bi-lock"></i>
                  </span>
                <?php endif; ?>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<?php require __DIR__ . '/../templates/footer.php'; ?>
