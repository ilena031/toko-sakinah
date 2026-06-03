<?php
require_once __DIR__ . '/../../config/config.php';
require __DIR__ . '/../auth_check.php';

$page_title  = 'Tambah Kategori';
$active_menu = 'kategori';

$error = '';
$nama_kategori = '';
$slug = '';
$deskripsi = '';

function buat_slug(string $s): string {
    $s = strtolower(trim($s));
    $s = preg_replace('/[^a-z0-9]+/', '-', $s);
    return trim($s, '-');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama_kategori = trim($_POST['nama_kategori'] ?? '');
    $slug          = trim($_POST['slug'] ?? '') ?: buat_slug($nama_kategori);
    $deskripsi     = trim($_POST['deskripsi'] ?? '');

    if (!$nama_kategori) {
        $error = 'Nama kategori wajib diisi.';
    } else {
        $check = $conn->prepare("SELECT id_kategori FROM kategori WHERE slug = ?");
        $check->bind_param('s', $slug);
        $check->execute();
        if ($check->get_result()->num_rows > 0) {
            $error = 'Slug sudah dipakai. Ganti yang lain.';
        }
        $check->close();
        if (!$error) {
            $stmt = $conn->prepare("INSERT INTO kategori (nama_kategori, slug, deskripsi) VALUES (?, ?, ?)");
            $stmt->bind_param('sss', $nama_kategori, $slug, $deskripsi);
            if ($stmt->execute()) {
                $_SESSION['flash_success'] = 'Kategori berhasil ditambahkan!';
                header('Location: ' . BASE_URL . 'admin/kategori/index.php');
                exit;
            } else {
                $error = 'Gagal simpan: ' . $stmt->error;
            }
            $stmt->close();
        }
    }
}

require __DIR__ . '/../templates/header.php';
?>

<div class="adm-card">
  <div class="adm-card-header">
    <h6><i class="bi bi-plus-circle text-pink"></i> Tambah Kategori</h6>
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
               value="<?= htmlspecialchars($nama_kategori) ?>" id="input-nama">
      </div>
      <div class="form-group">
        <label>Slug <small class="text-muted" style="font-weight:400;">auto-generate kalau dikosongkan</small></label>
        <input type="text" name="slug" class="form-control" maxlength="100"
               value="<?= htmlspecialchars($slug) ?>" id="input-slug" placeholder="baju-sekolah">
      </div>
      <div class="form-group">
        <label>Deskripsi</label>
        <textarea name="deskripsi" class="form-control" rows="3"><?= htmlspecialchars($deskripsi) ?></textarea>
      </div>
      <hr>
      <div class="d-flex gap-2 justify-content-end">
        <a href="<?= BASE_URL ?>admin/kategori/index.php" class="btn btn-secondary">Batal</a>
        <button type="submit" class="btn btn-pink">
          <i class="bi bi-check-circle"></i> Simpan
        </button>
      </div>
    </form>
  </div>
</div>

<script>
  // Auto-fill slug saat nama berubah
  const nama = document.getElementById('input-nama');
  const slug = document.getElementById('input-slug');
  let manualSlug = false;
  slug.addEventListener('input', () => { manualSlug = slug.value.length > 0; });
  nama.addEventListener('input', () => {
    if (!manualSlug) {
      slug.value = nama.value.toLowerCase().trim().replace(/[^a-z0-9]+/g, '-').replace(/^-|-$/g, '');
    }
  });
</script>

<?php require __DIR__ . '/../templates/footer.php'; ?>
