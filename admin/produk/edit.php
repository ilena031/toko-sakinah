<?php
/**
 * ADMIN PRODUK — Edit Produk
 */

require_once __DIR__ . '/../../config/config.php';
require __DIR__ . '/../auth_check.php';

$page_title  = 'Edit Produk';
$active_menu = 'produk';

$id = (int) ($_GET['id'] ?? 0);
if (!$id) {
    header('Location: ' . BASE_URL . 'admin/produk/index.php');
    exit;
}

$has_jenjang_col = false;
$col = $conn->query("SHOW COLUMNS FROM produk LIKE 'jenjang'");
if ($col && $col->num_rows > 0) $has_jenjang_col = true;

$kategori_all = $conn->query("SELECT id_kategori, nama_kategori FROM kategori ORDER BY nama_kategori")
                     ->fetch_all(MYSQLI_ASSOC);

// Ambil produk
$stmt = $conn->prepare("SELECT * FROM produk WHERE id_produk = ?");
$stmt->bind_param('i', $id);
$stmt->execute();
$produk = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$produk) {
    $_SESSION['flash_error'] = 'Produk tidak ditemukan.';
    header('Location: ' . BASE_URL . 'admin/produk/index.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama_produk   = trim($_POST['nama_produk'] ?? '');
    $id_kategori   = (int)  ($_POST['id_kategori'] ?? 0);
    $subcategory   = trim($_POST['subcategory'] ?? '');
    $deskripsi     = trim($_POST['deskripsi'] ?? '');
    $harga         = (float)($_POST['harga'] ?? 0);
    $badge         = trim($_POST['badge'] ?? '');
    $genders_arr   = $_POST['genders'] ?? [];
    $sizes_arr     = $_POST['sizes'] ?? [];
    $tryon_enabled = isset($_POST['tryon_enabled']) ? 1 : 0;
    $status        = $_POST['status'] ?? 'aktif';
    $jenjang       = $_POST['jenjang'] ?? '';
    $price_by_size = $_POST['price_by_size'] ?? [];
    $stock_by_size = $_POST['stock_by_size'] ?? [];

    if (!$nama_produk || !$id_kategori) {
        $error = 'Nama produk dan kategori wajib diisi.';
    } elseif (empty($sizes_arr)) {
        $error = 'Pilih minimal 1 ukuran.';
    } else {
        // Foto handling — preserve existing kalau tidak upload baru
        $foto_main = $produk['foto'];
        $foto_all  = json_decode($produk['foto_all'], true) ?: [];

        if (!empty($_FILES['foto_utama']['name']) && $_FILES['foto_utama']['error'] === UPLOAD_ERR_OK) {
            $f = $_FILES['foto_utama'];
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mime = finfo_file($finfo, $f['tmp_name']);
            finfo_close($finfo);
            if (in_array($mime, ALLOWED_IMG_TYPES, true) && $f['size'] <= MAX_UPLOAD_SIZE) {
                $ext = pathinfo($f['name'], PATHINFO_EXTENSION);
                $name = 'prod_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . strtolower($ext);
                if (move_uploaded_file($f['tmp_name'], UPLOAD_PATH . 'produk/' . $name)) {
                    $foto_main = 'uploads/produk/' . $name;
                    $foto_all[0] = $foto_main;
                }
            }
        }

        if (!empty($_FILES['foto_tambahan']['name'][0])) {
            $files = $_FILES['foto_tambahan'];
            $count = count($files['name']);
            for ($i = 0; $i < $count; $i++) {
                if ($files['error'][$i] !== UPLOAD_ERR_OK) continue;
                $finfo = finfo_open(FILEINFO_MIME_TYPE);
                $mime = finfo_file($finfo, $files['tmp_name'][$i]);
                finfo_close($finfo);
                if (!in_array($mime, ALLOWED_IMG_TYPES, true)) continue;
                if ($files['size'][$i] > MAX_UPLOAD_SIZE) continue;
                $ext = pathinfo($files['name'][$i], PATHINFO_EXTENSION);
                $name = 'prod_' . time() . '_' . bin2hex(random_bytes(4)) . '_' . $i . '.' . strtolower($ext);
                if (move_uploaded_file($files['tmp_name'][$i], UPLOAD_PATH . 'produk/' . $name)) {
                    $foto_all[] = 'uploads/produk/' . $name;
                }
            }
        }

        $ukuran_csv  = implode(',', array_map('trim', $sizes_arr));
        $genders_csv = implode(',', array_map('trim', $genders_arr));

        $price_json = [];
        $stock_json = [];
        $stok_total = 0;
        foreach ($sizes_arr as $sz) {
            $price_json[$sz] = (float) ($price_by_size[$sz] ?? $harga);
            $stock_json[$sz] = (int)   ($stock_by_size[$sz] ?? 0);
            $stok_total     += $stock_json[$sz];
        }
        $price_json_str = json_encode($price_json);
        $stock_json_str = json_encode($stock_json);
        $foto_all_str   = json_encode(array_values(array_unique($foto_all)));

        if ($has_jenjang_col) {
            $stmt = $conn->prepare(
                "UPDATE produk SET
                    id_kategori = ?, nama_produk = ?, subcategory = ?, deskripsi = ?,
                    harga = ?, stok_total = ?, ukuran = ?, price_by_size = ?, stock_by_size = ?,
                    genders = ?, foto = ?, foto_all = ?,
                    tryon_enabled = ?, badge = ?, status = ?, jenjang = ?
                 WHERE id_produk = ?"
            );
            $stmt->bind_param('isssdississssisssi',
                $id_kategori, $nama_produk, $subcategory, $deskripsi,
                $harga, $stok_total, $ukuran_csv, $price_json_str, $stock_json_str,
                $genders_csv, $foto_main, $foto_all_str,
                $tryon_enabled, $badge, $status, $jenjang,
                $id
            );
        } else {
            $stmt = $conn->prepare(
                "UPDATE produk SET
                    id_kategori = ?, nama_produk = ?, subcategory = ?, deskripsi = ?,
                    harga = ?, stok_total = ?, ukuran = ?, price_by_size = ?, stock_by_size = ?,
                    genders = ?, foto = ?, foto_all = ?,
                    tryon_enabled = ?, badge = ?, status = ?
                 WHERE id_produk = ?"
            );
            $stmt->bind_param('isssdississssissi',
                $id_kategori, $nama_produk, $subcategory, $deskripsi,
                $harga, $stok_total, $ukuran_csv, $price_json_str, $stock_json_str,
                $genders_csv, $foto_main, $foto_all_str,
                $tryon_enabled, $badge, $status,
                $id
            );
        }

        if ($stmt->execute()) {
            $_SESSION['flash_success'] = 'Produk berhasil diupdate!';
            header('Location: ' . BASE_URL . 'admin/produk/index.php');
            exit;
        } else {
            $error = 'Gagal update: ' . $stmt->error;
        }
        $stmt->close();
    }

    // Re-fetch untuk display
    if ($error) {
        $stmt = $conn->prepare("SELECT * FROM produk WHERE id_produk = ?");
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $produk = $stmt->get_result()->fetch_assoc();
        $stmt->close();
    }
}

$sizes_curr   = $produk['ukuran'] ? array_map('trim', explode(',', $produk['ukuran'])) : [];
$genders_curr = $produk['genders'] ? array_map('trim', explode(',', $produk['genders'])) : [];
$price_curr   = json_decode($produk['price_by_size'], true) ?: [];
$stock_curr   = json_decode($produk['stock_by_size'], true) ?: [];
$foto_all     = json_decode($produk['foto_all'], true) ?: [$produk['foto']];

require __DIR__ . '/../templates/header.php';
?>

<div class="adm-card">
  <div class="adm-card-header">
    <h6><i class="bi bi-pencil-square text-pink"></i> Edit: <?= htmlspecialchars($produk['nama_produk']) ?></h6>
    <a href="<?= BASE_URL ?>admin/produk/index.php" class="adm-link-sm">← Kembali</a>
  </div>
  <div class="adm-card-body">
    <?php if ($error): ?>
      <div class="alert alert-danger"><i class="bi bi-exclamation-circle"></i> <?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <form action="" method="POST" enctype="multipart/form-data">
      <div class="row g-3">
        <div class="col-lg-7">
          <div class="form-group">
            <label>Nama Produk <span class="text-danger">*</span></label>
            <input type="text" name="nama_produk" class="form-control" required maxlength="150"
                   value="<?= htmlspecialchars($produk['nama_produk']) ?>">
          </div>

          <div class="row g-2">
            <div class="col-md-6">
              <div class="form-group">
                <label>Kategori <span class="text-danger">*</span></label>
                <select name="id_kategori" class="form-select" required>
                  <?php foreach ($kategori_all as $k): ?>
                    <option value="<?= $k['id_kategori'] ?>" <?= $produk['id_kategori'] == $k['id_kategori'] ? 'selected' : '' ?>>
                      <?= htmlspecialchars($k['nama_kategori']) ?>
                    </option>
                  <?php endforeach; ?>
                </select>
              </div>
            </div>
            <div class="col-md-6">
              <div class="form-group">
                <label>Subkategori</label>
                <input type="text" name="subcategory" class="form-control" maxlength="100"
                       value="<?= htmlspecialchars($produk['subcategory'] ?? '') ?>">
              </div>
            </div>
          </div>

          <?php if ($has_jenjang_col): ?>
            <div class="form-group">
              <label>Jenjang</label>
              <select name="jenjang" class="form-select">
                <option value="">Tidak spesifik</option>
                <?php
                $j_opts = ['tk'=>'TK','sd_kecil'=>'SD Kelas 1–3','sd_besar'=>'SD Kelas 4–6',
                           'smp'=>'SMP','sma'=>'SMA','dewasa'=>'Dewasa'];
                foreach ($j_opts as $jk => $jv): ?>
                  <option value="<?= $jk ?>" <?= ($produk['jenjang'] ?? '') === $jk ? 'selected' : '' ?>><?= $jv ?></option>
                <?php endforeach; ?>
              </select>
            </div>
          <?php endif; ?>

          <div class="form-group">
            <label>Deskripsi</label>
            <textarea name="deskripsi" class="form-control" rows="4"><?= htmlspecialchars($produk['deskripsi'] ?? '') ?></textarea>
          </div>

          <div class="row g-2">
            <div class="col-md-6">
              <div class="form-group">
                <label>Harga Default <span class="text-danger">*</span></label>
                <input type="number" name="harga" class="form-control" required min="0" value="<?= $produk['harga'] ?>">
              </div>
            </div>
            <div class="col-md-6">
              <div class="form-group">
                <label>Badge</label>
                <select name="badge" class="form-select">
                  <option value="">Tanpa badge</option>
                  <option value="Terlaris" <?= ($produk['badge'] ?? '') === 'Terlaris' ? 'selected' : '' ?>>Terlaris</option>
                  <option value="Baru"     <?= ($produk['badge'] ?? '') === 'Baru' ? 'selected' : '' ?>>Baru</option>
                </select>
              </div>
            </div>
          </div>

          <div class="form-group">
            <label>Ukuran <span class="text-danger">*</span></label>
            <div class="size-picker">
              <?php foreach (['XS','S','M','L','XL','XXL','4','6','7','8','9','10','12','14','16'] as $sz): ?>
                <label class="size-check">
                  <input type="checkbox" name="sizes[]" value="<?= $sz ?>" class="size-toggle" <?= in_array($sz, $sizes_curr, true) ? 'checked' : '' ?>>
                  <span><?= $sz ?></span>
                </label>
              <?php endforeach; ?>
            </div>
          </div>

          <div class="form-group" id="sizes-detail" style="display:none;">
            <label>Harga & Stok per Ukuran</label>
            <table class="table table-sm">
              <thead><tr><th>Ukuran</th><th>Harga (Rp)</th><th>Stok</th></tr></thead>
              <tbody id="sizes-tbody"></tbody>
            </table>
          </div>

          <div class="form-group">
            <label>Gender</label>
            <div class="size-picker">
              <?php foreach (['Cowo','Cewe','Unisex'] as $g): ?>
                <label class="size-check">
                  <input type="checkbox" name="genders[]" value="<?= $g ?>" <?= in_array($g, $genders_curr, true) ? 'checked' : '' ?>>
                  <span><?= $g ?></span>
                </label>
              <?php endforeach; ?>
            </div>
          </div>

          <div class="form-group">
            <label class="d-flex align-items-center gap-2">
              <input type="checkbox" name="tryon_enabled" value="1" <?= $produk['tryon_enabled'] ? 'checked' : '' ?>>
              <span>Aktifkan Virtual Try-On</span>
            </label>
          </div>

          <div class="form-group">
            <label>Status</label>
            <select name="status" class="form-select">
              <option value="aktif"    <?= $produk['status'] === 'aktif' ? 'selected' : '' ?>>Aktif</option>
              <option value="nonaktif" <?= $produk['status'] === 'nonaktif' ? 'selected' : '' ?>>Nonaktif</option>
            </select>
          </div>
        </div>

        <div class="col-lg-5">
          <div class="form-group">
            <label>Foto Saat Ini</label>
            <div class="foto-preview-grid">
              <?php foreach ($foto_all as $fp): ?>
                <div><img src="<?= BASE_URL . htmlspecialchars($fp) ?>" alt=""></div>
              <?php endforeach; ?>
            </div>
          </div>

          <div class="form-group">
            <label>Ganti Foto Utama (opsional)</label>
            <input type="file" name="foto_utama" class="form-control" accept="image/*">
            <small class="text-muted">Kosongkan kalau tidak ingin ganti</small>
          </div>

          <div class="form-group">
            <label>Tambah Foto Lain (opsional)</label>
            <input type="file" name="foto_tambahan[]" class="form-control" accept="image/*" multiple>
          </div>
        </div>
      </div>

      <hr class="my-4">
      <div class="d-flex gap-2 justify-content-end">
        <a href="<?= BASE_URL ?>admin/produk/index.php" class="btn btn-secondary">Batal</a>
        <button type="submit" class="btn btn-pink">
          <i class="bi bi-check-circle"></i> Update Produk
        </button>
      </div>
    </form>
  </div>
</div>

<script>
  const sizeToggles = document.querySelectorAll('.size-toggle');
  const sizesDetail = document.getElementById('sizes-detail');
  const sizesTbody  = document.getElementById('sizes-tbody');
  const defaultPrice = document.querySelector('input[name="harga"]');
  const initialPrice = <?= json_encode($price_curr) ?>;
  const initialStock = <?= json_encode($stock_curr) ?>;

  function rebuildSizeDetail() {
    const checked = Array.from(sizeToggles).filter(c => c.checked);
    sizesTbody.innerHTML = '';
    if (checked.length === 0) { sizesDetail.style.display = 'none'; return; }
    sizesDetail.style.display = 'block';
    checked.forEach(c => {
      const sz = c.value;
      const pp = initialPrice[sz] || defaultPrice.value || 0;
      const ss = initialStock[sz] || 0;
      const tr = document.createElement('tr');
      tr.innerHTML = `
        <td><strong>${sz}</strong></td>
        <td><input type="number" name="price_by_size[${sz}]" class="form-control form-control-sm" min="0" value="${pp}"></td>
        <td><input type="number" name="stock_by_size[${sz}]" class="form-control form-control-sm" min="0" value="${ss}"></td>
      `;
      sizesTbody.appendChild(tr);
    });
  }
  sizeToggles.forEach(c => c.addEventListener('change', rebuildSizeDetail));
  rebuildSizeDetail();
</script>

<?php require __DIR__ . '/../templates/footer.php'; ?>
