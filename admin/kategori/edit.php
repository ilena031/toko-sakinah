<?php
require_once __DIR__ . '/../../config/config.php';
require __DIR__ . '/../auth_check.php';

$page_title  = 'Edit Kategori';
$active_menu = 'kategori';

$id = (int) ($_GET['id'] ?? 0);
if (!$id) {
    header('Location: ' . BASE_URL . 'admin/kategori/index.php');
    exit;
}

$stmt = $conn->prepare("SELECT * FROM kategori WHERE id_kategori = ?");
$stmt->bind_param('i', $id);
$stmt->execute();
$kategori = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$kategori) {
    $_SESSION['flash_error'] = 'Kategori tidak ditemukan.';
    header('Location: ' . BASE_URL . 'admin/kategori/index.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama = trim($_POST['nama_kategori'] ?? '');
    $slug = trim($_POST['slug'] ?? '');
    $desc = trim($_POST['deskripsi'] ?? '');

    if (!$nama || !$slug) {
        $error = 'Nama dan slug wajib diisi.';
    } else {
        $check = $conn->prepare("SELECT id_kategori FROM kategori WHERE slug = ? AND id_kategori != ?");
        $check->bind_param('si', $slug, $id);
        $check->execute();
        if ($check->get_result()->num_rows > 0) {
            $error = 'Slug sudah dipakai kategori lain.';
        }
        $check->close();
        if (!$error) {
            $stmt = $conn->prepare("UPDATE kategori SET nama_kategori=?, slug=?, deskripsi=? WHERE id_kategori=?");
            $stmt->bind_param('sssi', $nama, $slug, $desc, $id);
            if ($stmt->execute()) {
                $_SESSION['flash_success'] = 'Kategori berhasil diupdate!';
                header('Location: ' . BASE_URL . 'admin/kategori/index.php');
                exit;
            } else {
                $error = 'Gagal update: ' . $stmt->error;
            }
            $stmt->close();
        }
    }
    // Refresh kategori data dari POST
    $kategori['nama_kategori'] = $nama;
    $kategori['slug']          = $slug;
    $kategori['deskripsi']     = $desc;
}

require __DIR__ . '/../templates/header.php';
?>

<div class="adm-card">
  <div class="adm-card-header">
    <h6><i class="bi bi-pencil-square text-pink"></i> Edit Kategori</h6>
    <a href="<?= BASE_URL ?>admin/kategori/index.php" class="adm-link-sm">← Kembali</a>
  </div>
  <div class="adm-card-body">
    <?php if ($error): ?>
      <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <form action="" method="POST" style="max-width:560px;">
      <div class="form-group">
        <label>Nama Kategori <span class="text-danger">*</span></label>
        <input type="text" name="nama_kategori" class="form-control" required maxlength="100"
               value="<?= htmlspecialchars($kategori['nama_kategori']) ?>">
      </div>
      <div class="form-group">
        <label>Slug <span class="text-danger">*</span></label>
        <input type="text" name="slug" class="form-control" required maxlength="100"
               value="<?= htmlspecialchars($kategori['slug']) ?>">
      </div>
      <div class="form-group">
        <label>Deskripsi</label>
        <textarea name="deskripsi" class="form-control" rows="3"><?= htmlspecialchars($kategori['deskripsi'] ?? '') ?></textarea>
      </div>
      <hr>
      <div class="d-flex gap-2 justify-content-end">
        <a href="<?= BASE_URL ?>admin/kategori/index.php" class="btn btn-secondary">Batal</a>
        <button type="submit" class="btn btn-pink">
          <i class="bi bi-check-circle"></i> Update
        </button>
      </div>
    </form>
  </div>
</div>

<?php require __DIR__ . '/../templates/footer.php'; ?>
