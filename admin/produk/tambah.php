<?php
/**
 * ADMIN PRODUK — Tambah Produk
 * Form dengan size/gender dinamis + multi-upload foto.
 */

require_once __DIR__ . '/../../config/config.php';
require __DIR__ . '/../auth_check.php';

$page_title  = 'Tambah Produk';
$active_menu = 'produk';

$kategori_all = $conn->query("SELECT id_kategori, nama_kategori FROM kategori ORDER BY nama_kategori")
                     ->fetch_all(MYSQLI_ASSOC);

// Cek apakah kolom jenjang sudah ada
$has_jenjang_col = false;
$col = $conn->query("SHOW COLUMNS FROM produk LIKE 'jenjang'");
if ($col && $col->num_rows > 0) $has_jenjang_col = true;

$error = '';
$form_data = [
    'nama_produk' => '', 'id_kategori' => 0, 'subcategory' => '',
    'deskripsi' => '', 'harga' => 0, 'badge' => '',
    'genders' => [], 'sizes' => [], 'tryon_enabled' => 0,
    'status' => 'aktif', 'jenjang' => '',
];
$price_by_size = [];
$stock_by_size = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $form_data['nama_produk']    = trim($_POST['nama_produk'] ?? '');
    $form_data['id_kategori']    = (int)  ($_POST['id_kategori'] ?? 0);
    $form_data['subcategory']    = trim($_POST['subcategory'] ?? '');
    $form_data['deskripsi']      = trim($_POST['deskripsi'] ?? '');
    $form_data['harga']          = (float)($_POST['harga'] ?? 0);
    $form_data['badge']          = trim($_POST['badge'] ?? '');
    $form_data['genders']        = $_POST['genders'] ?? [];
    $form_data['sizes']          = $_POST['sizes'] ?? [];
    $form_data['tryon_enabled']  = isset($_POST['tryon_enabled']) ? 1 : 0;
    $form_data['status']         = $_POST['status'] ?? 'aktif';
    $form_data['jenjang']        = $_POST['jenjang'] ?? '';

    $price_by_size = $_POST['price_by_size'] ?? [];
    $stock_by_size = $_POST['stock_by_size'] ?? [];

    // Validasi
    if (!$form_data['nama_produk'] || !$form_data['id_kategori']) {
        $error = 'Nama produk dan kategori wajib diisi.';
    } elseif (empty($form_data['sizes'])) {
        $error = 'Pilih minimal 1 ukuran.';
    } else {
        // ── Upload foto utama ─────────────────────────
        $foto_main = '';
        $foto_all  = [];

        if (!empty($_FILES['foto_utama']['name']) && $_FILES['foto_utama']['error'] === UPLOAD_ERR_OK) {
            $f = $_FILES['foto_utama'];
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mime  = finfo_file($finfo, $f['tmp_name']);
            finfo_close($finfo);
            if (!in_array($mime, ALLOWED_IMG_TYPES, true)) {
                $error = 'Format foto utama harus JPG/PNG/WebP.';
            } elseif ($f['size'] > MAX_UPLOAD_SIZE) {
                $error = 'Foto utama maksimal 2MB.';
            } else {
                $ext = pathinfo($f['name'], PATHINFO_EXTENSION);
                $name = 'prod_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . strtolower($ext);
                $dest_dir = UPLOAD_PATH . 'produk';
                if (!is_dir($dest_dir)) @mkdir($dest_dir, 0775, true);
                if (move_uploaded_file($f['tmp_name'], $dest_dir . '/' . $name)) {
                    $foto_main = 'uploads/produk/' . $name;
                    $foto_all[] = $foto_main;
                } else {
                    $error = 'Gagal upload foto utama.';
                }
            }
        } else {
            $error = 'Foto utama wajib di-upload.';
        }

        // Foto tambahan (multi)
        if (!$error && !empty($_FILES['foto_tambahan']['name'][0])) {
            $files = $_FILES['foto_tambahan'];
            $count = count($files['name']);
            for ($i = 0; $i < $count; $i++) {
                if ($files['error'][$i] !== UPLOAD_ERR_OK) continue;
                $finfo = finfo_open(FILEINFO_MIME_TYPE);
                $mime  = finfo_file($finfo, $files['tmp_name'][$i]);
                finfo_close($finfo);
                if (!in_array($mime, ALLOWED_IMG_TYPES, true)) continue;
                if ($files['size'][$i] > MAX_UPLOAD_SIZE) continue;

                $ext  = pathinfo($files['name'][$i], PATHINFO_EXTENSION);
                $name = 'prod_' . time() . '_' . bin2hex(random_bytes(4)) . '_' . $i . '.' . strtolower($ext);
                if (move_uploaded_file($files['tmp_name'][$i], UPLOAD_PATH . 'produk/' . $name)) {
                    $foto_all[] = 'uploads/produk/' . $name;
                }
            }
        }

        if (!$error) {
            // Build CSV & JSON
            $ukuran_csv  = implode(',', array_map('trim', $form_data['sizes']));
            $genders_csv = implode(',', array_map('trim', $form_data['genders']));

            // Filter price/stock only for picked sizes
            $price_json = [];
            $stock_json = [];
            $stok_total = 0;
            foreach ($form_data['sizes'] as $sz) {
                $price_json[$sz] = (float) ($price_by_size[$sz] ?? $form_data['harga']);
                $stock_json[$sz] = (int)   ($stock_by_size[$sz] ?? 0);
                $stok_total     += $stock_json[$sz];
            }
            $price_json_str = json_encode($price_json);
            $stock_json_str = json_encode($stock_json);
            $foto_all_str   = json_encode($foto_all);

            // Insert
            if ($has_jenjang_col) {
                $stmt = $conn->prepare(
                    "INSERT INTO produk (id_kategori, nama_produk, subcategory, deskripsi, harga, stok_total,
                        ukuran, price_by_size, stock_by_size, genders, foto, foto_all,
                        tryon_enabled, badge, status, jenjang)
                     VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
                );
                $stmt->bind_param('isssdissssssisss',
                    $form_data['id_kategori'], $form_data['nama_produk'], $form_data['subcategory'],
                    $form_data['deskripsi'], $form_data['harga'], $stok_total,
                    $ukuran_csv, $price_json_str, $stock_json_str, $genders_csv,
                    $foto_main, $foto_all_str,
                    $form_data['tryon_enabled'], $form_data['badge'], $form_data['status'],
                    $form_data['jenjang']
                );
            } else {
                $stmt = $conn->prepare(
                    "INSERT INTO produk (id_kategori, nama_produk, subcategory, deskripsi, harga, stok_total,
                        ukuran, price_by_size, stock_by_size, genders, foto, foto_all,
                        tryon_enabled, badge, status)
                     VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
                );
                $stmt->bind_param('isssdississssis',
                    $form_data['id_kategori'], $form_data['nama_produk'], $form_data['subcategory'],
                    $form_data['deskripsi'], $form_data['harga'], $stok_total,
                    $ukuran_csv, $price_json_str, $stock_json_str, $genders_csv,
                    $foto_main, $foto_all_str,
                    $form_data['tryon_enabled'], $form_data['badge'], $form_data['status']
                );
            }
            if ($stmt->execute()) {
                $_SESSION['flash_success'] = 'Produk berhasil ditambahkan!';
                header('Location: ' . BASE_URL . 'admin/produk/index.php');
                exit;
            } else {
                $error = 'Gagal simpan: ' . $stmt->error;
                $stmt->close();
            }
        }
    }
}

require __DIR__ . '/../templates/header.php';
?>

<div class="adm-card">
  <div class="adm-card-header">
    <h6><i class="bi bi-plus-circle text-pink"></i> Tambah Produk Baru</h6>
    <a href="<?= BASE_URL ?>admin/produk/index.php" class="adm-link-sm">← Kembali</a>
  </div>
  <div class="adm-card-body">

    <?php if ($error): ?>
      <div class="alert alert-danger"><i class="bi bi-exclamation-circle"></i> <?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <form action="" method="POST" enctype="multipart/form-data" id="form-produk">
      <div class="row g-3">

        <!-- Kolom kiri: info -->
        <div class="col-lg-7">
          <div class="form-group">
            <label>Nama Produk <span class="text-danger">*</span></label>
            <input type="text" name="nama_produk" class="form-control" required maxlength="150"
                   value="<?= htmlspecialchars($form_data['nama_produk']) ?>">
          </div>

          <div class="row g-2">
            <div class="col-md-6">
              <div class="form-group">
                <label>Kategori <span class="text-danger">*</span></label>
                <select name="id_kategori" class="form-select" required>
                  <option value="">Pilih kategori...</option>
                  <?php foreach ($kategori_all as $k): ?>
                    <option value="<?= $k['id_kategori'] ?>" <?= $form_data['id_kategori'] == $k['id_kategori'] ? 'selected' : '' ?>>
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
                       value="<?= htmlspecialchars($form_data['subcategory']) ?>"
                       placeholder="Contoh: SMA, Batik, Pramuka">
              </div>
            </div>
          </div>

          <?php if ($has_jenjang_col): ?>
            <div class="form-group">
              <label>Jenjang (untuk Try-On size matching)</label>
              <select name="jenjang" class="form-select">
                <option value="">Tidak spesifik</option>
                <option value="tk"       <?= $form_data['jenjang'] === 'tk' ? 'selected' : '' ?>>TK (4–6 tahun)</option>
                <option value="sd_kecil" <?= $form_data['jenjang'] === 'sd_kecil' ? 'selected' : '' ?>>SD Kelas 1–3 (6–9 tahun)</option>
                <option value="sd_besar" <?= $form_data['jenjang'] === 'sd_besar' ? 'selected' : '' ?>>SD Kelas 4–6 (9–12 tahun)</option>
                <option value="smp"      <?= $form_data['jenjang'] === 'smp' ? 'selected' : '' ?>>SMP (12–15 tahun)</option>
                <option value="sma"      <?= $form_data['jenjang'] === 'sma' ? 'selected' : '' ?>>SMA (15–18 tahun)</option>
                <option value="dewasa"   <?= $form_data['jenjang'] === 'dewasa' ? 'selected' : '' ?>>Dewasa</option>
              </select>
            </div>
          <?php endif; ?>

          <div class="form-group">
            <label>Deskripsi</label>
            <textarea name="deskripsi" class="form-control" rows="4"><?= htmlspecialchars($form_data['deskripsi']) ?></textarea>
          </div>

          <div class="row g-2">
            <div class="col-md-6">
              <div class="form-group">
                <label>Harga Default <span class="text-danger">*</span></label>
                <small class="text-muted" style="font-weight:400;display:block;margin-top:-4px;margin-bottom:4px;font-size:11px;">Dipakai jika tidak ada harga per ukuran</small>
                <input type="number" name="harga" class="form-control" required min="0"
                       value="<?= $form_data['harga'] ?>">
              </div>
            </div>
            <div class="col-md-6">
              <div class="form-group">
                <label>Badge</label>
                <select name="badge" class="form-select">
                  <option value="">Tanpa badge</option>
                  <option value="Terlaris" <?= $form_data['badge'] === 'Terlaris' ? 'selected' : '' ?>>Terlaris</option>
                  <option value="Baru"     <?= $form_data['badge'] === 'Baru' ? 'selected' : '' ?>>Baru</option>
                </select>
              </div>
            </div>
          </div>

          <!-- Sizes & Stock -->
          <div class="form-group">
            <label>Pilih Ukuran <span class="text-danger">*</span></label>
            <div class="size-picker">
              <?php foreach (['XS','S','M','L','XL','XXL','4','6','7','8','9','10','12','14','16'] as $sz):
                $picked = in_array($sz, $form_data['sizes'], true);
              ?>
                <label class="size-check">
                  <input type="checkbox" name="sizes[]" value="<?= $sz ?>" class="size-toggle" <?= $picked ? 'checked' : '' ?>>
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

          <!-- Gender -->
          <div class="form-group">
            <label>Gender (opsional)</label>
            <div class="size-picker">
              <?php foreach (['Cowo','Cewe','Unisex'] as $g): ?>
                <label class="size-check">
                  <input type="checkbox" name="genders[]" value="<?= $g ?>" <?= in_array($g, $form_data['genders'], true) ? 'checked' : '' ?>>
                  <span><?= $g ?></span>
                </label>
              <?php endforeach; ?>
            </div>
          </div>

          <div class="form-group">
            <label class="d-flex align-items-center gap-2">
              <input type="checkbox" name="tryon_enabled" value="1" <?= $form_data['tryon_enabled'] ? 'checked' : '' ?>>
              <span>Aktifkan Virtual Try-On untuk produk ini</span>
            </label>
          </div>

          <div class="form-group">
            <label>Status</label>
            <select name="status" class="form-select">
              <option value="aktif"    <?= $form_data['status'] === 'aktif' ? 'selected' : '' ?>>Aktif</option>
              <option value="nonaktif" <?= $form_data['status'] === 'nonaktif' ? 'selected' : '' ?>>Nonaktif</option>
            </select>
          </div>
        </div>

        <!-- Kolom kanan: foto -->
        <div class="col-lg-5">
          <div class="form-group">
            <label>Foto Utama <span class="text-danger">*</span></label>
            <input type="file" name="foto_utama" class="form-control" accept="image/*" id="input-foto-utama" required>
            <small class="text-muted">JPG/PNG/WebP, max 2MB</small>
            <div id="preview-foto-utama" class="foto-preview mt-2"></div>
          </div>

          <div class="form-group">
            <label>Foto Tambahan (max 5)</label>
            <input type="file" name="foto_tambahan[]" class="form-control" accept="image/*" multiple id="input-foto-tambahan">
            <small class="text-muted">Bisa pilih beberapa sekaligus</small>
            <div id="preview-foto-tambahan" class="foto-preview-grid mt-2"></div>
          </div>
        </div>
      </div>

      <hr class="my-4">
      <div class="d-flex gap-2 justify-content-end">
        <a href="<?= BASE_URL ?>admin/produk/index.php" class="btn btn-secondary">Batal</a>
        <button type="submit" class="btn btn-pink">
          <i class="bi bi-check-circle"></i> Simpan Produk
        </button>
      </div>
    </form>

  </div>
</div>

<script>
  // Toggle size detail table
  const sizeToggles = document.querySelectorAll('.size-toggle');
  const sizesDetail = document.getElementById('sizes-detail');
  const sizesTbody  = document.getElementById('sizes-tbody');
  const defaultPrice = document.querySelector('input[name="harga"]');
  const initialPrice = <?= json_encode($price_by_size) ?>;
  const initialStock = <?= json_encode($stock_by_size) ?>;

  function rebuildSizeDetail() {
    const checked = Array.from(sizeToggles).filter(c => c.checked);
    sizesTbody.innerHTML = '';
    if (checked.length === 0) {
      sizesDetail.style.display = 'none';
      return;
    }
    sizesDetail.style.display = 'block';
    checked.forEach(c => {
      const sz = c.value;
      const tr = document.createElement('tr');
      const pp = initialPrice[sz] || defaultPrice.value || 0;
      const ss = initialStock[sz] || 0;
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

  // Preview foto utama
  document.getElementById('input-foto-utama').addEventListener('change', function () {
    const p = document.getElementById('preview-foto-utama');
    p.innerHTML = '';
    if (this.files[0]) {
      const url = URL.createObjectURL(this.files[0]);
      p.innerHTML = `<img src="${url}" alt="">`;
    }
  });
  document.getElementById('input-foto-tambahan').addEventListener('change', function () {
    const p = document.getElementById('preview-foto-tambahan');
    p.innerHTML = '';
    Array.from(this.files).slice(0,5).forEach(f => {
      const url = URL.createObjectURL(f);
      const div = document.createElement('div');
      div.innerHTML = `<img src="${url}" alt="">`;
      p.appendChild(div);
    });
  });
</script>

<?php require __DIR__ . '/../templates/footer.php'; ?>
