<?php
/**
 * CUSTOM_ORDER.PHP — Form pengajuan custom order baju seragam
 * + list custom order pelanggan
 */

require_once __DIR__ . '/../config/config.php';
require __DIR__ . '/auth_check.php';

$page_title  = 'Custom Order — Toko Sakinah';
$active_page = 'profil';
$extra_css   = 'custom_order.css';

$user_id = (int) $_SESSION['user_id'];
$error   = '';
$success = '';

// ── Handle POST submit ────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama_institusi  = trim($_POST['nama_institusi']  ?? '');
    $jenis_seragam   = trim($_POST['jenis_seragam']   ?? '');
    $estimasi_jumlah = (int)  ($_POST['estimasi_jumlah'] ?? 0);
    $bahan           = trim($_POST['bahan']           ?? '');
    $warna           = trim($_POST['warna']           ?? '');
    $catatan         = trim($_POST['catatan']         ?? '');

    if (!$nama_institusi || !$jenis_seragam) {
        $error = 'Nama institusi dan jenis seragam wajib diisi.';
    } else {
        // ── Handle upload file referensi (optional) ────
        $file_referensi = null;
        if (!empty($_FILES['file_referensi']['name']) && $_FILES['file_referensi']['error'] === UPLOAD_ERR_OK) {
            $f = $_FILES['file_referensi'];
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mime  = finfo_file($finfo, $f['tmp_name']);
            finfo_close($finfo);

            if (!in_array($mime, ALLOWED_DOC_TYPES, true)) {
                $error = 'Format file referensi harus JPG/PNG/PDF.';
            } elseif ($f['size'] > 5 * 1024 * 1024) {
                $error = 'Ukuran file referensi maksimal 5MB.';
            } else {
                $ext = pathinfo($f['name'], PATHINFO_EXTENSION);
                $safe_name = 'ref_' . $user_id . '_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . strtolower($ext);
                $dest = UPLOAD_PATH . 'custom_referensi/' . $safe_name;
                if (!is_dir(dirname($dest))) @mkdir(dirname($dest), 0775, true);
                if (move_uploaded_file($f['tmp_name'], $dest)) {
                    $file_referensi = 'uploads/custom_referensi/' . $safe_name;
                } else {
                    $error = 'Gagal mengunggah file referensi.';
                }
            }
        }

        if (!$error) {
            $stmt = $conn->prepare(
                "INSERT INTO custom_order (user_id, nama_institusi, jenis_seragam, estimasi_jumlah,
                                            bahan, warna, catatan, file_referensi, status)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'Menunggu Review')"
            );
            $stmt->bind_param('issiisss',
                $user_id, $nama_institusi, $jenis_seragam, $estimasi_jumlah,
                $bahan, $warna, $catatan, $file_referensi);
            if ($stmt->execute()) {
                $id_custom = $stmt->insert_id;
                $stmt->close();

                // Auto chat pertama dari pelanggan
                $msg_init = "Halo admin! Saya ingin mengajukan custom order untuk: {$jenis_seragam}.\n";
                $msg_init .= "Institusi: {$nama_institusi}\n";
                if ($estimasi_jumlah) $msg_init .= "Estimasi jumlah: {$estimasi_jumlah} pcs\n";
                if ($bahan)           $msg_init .= "Bahan: {$bahan}\n";
                if ($warna)           $msg_init .= "Warna: {$warna}\n";
                if ($catatan)         $msg_init .= "Catatan: {$catatan}";

                $role_pel = 'pelanggan';
                $stmt2 = $conn->prepare("INSERT INTO konsultasi_chat (id_custom, pengirim_role, pesan) VALUES (?, ?, ?)");
                $stmt2->bind_param('iss', $id_custom, $role_pel, $msg_init);
                $stmt2->execute();
                $stmt2->close();

                $_SESSION['flash_success'] = 'Custom order berhasil diajukan! Admin akan segera menghubungi Anda.';
                header('Location: ' . BASE_URL . 'pelanggan/custom_order_detail.php?id=' . $id_custom);
                exit;
            } else {
                $error = 'Gagal menyimpan pengajuan: ' . $stmt->error;
                $stmt->close();
            }
        }
    }
}

// ── Ambil list custom order pelanggan ─────────────────────
$stmt = $conn->prepare("SELECT * FROM custom_order WHERE user_id = ? ORDER BY created_at DESC");
$stmt->bind_param('i', $user_id);
$stmt->execute();
$list = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

require __DIR__ . '/../templates/header.php';
require __DIR__ . '/../templates/navbar.php';
?>

<main class="page-content co-wrapper">
  <div class="container">

    <div class="co-header">
      <h2><i class="bi bi-palette2 text-pink me-2"></i>Custom Order Seragam</h2>
      <p>Pesan seragam khusus untuk sekolah atau institusi Anda. Tim kami akan mengkonsultasikan spesifikasi & harga.</p>
    </div>

    <?php if ($error): ?>
      <div class="cart-alert alert-danger"><i class="bi bi-exclamation-circle"></i> <?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <div class="row g-4">

      <!-- ═══ FORM ═══ -->
      <div class="col-lg-7">
        <div class="checkout-card">
          <div class="checkout-card-header">
            <h5><i class="bi bi-pencil-square text-pink me-2"></i>Form Pengajuan Custom Order</h5>
          </div>
          <div class="checkout-card-body">
            <form action="" method="POST" enctype="multipart/form-data">
              <div class="row g-3">
                <div class="col-md-6">
                  <label class="form-label-pink">Nama Institusi/Sekolah <span class="req">*</span></label>
                  <input type="text" name="nama_institusi" class="form-control-pink" required
                         placeholder="Contoh: SDN 12 Palembang" maxlength="150">
                </div>
                <div class="col-md-6">
                  <label class="form-label-pink">Jenis Seragam <span class="req">*</span></label>
                  <input type="text" name="jenis_seragam" class="form-control-pink" required
                         placeholder="Contoh: Seragam Pramuka, Batik Sekolah" maxlength="100">
                </div>
                <div class="col-md-6">
                  <label class="form-label-pink">Estimasi Jumlah (pcs)</label>
                  <input type="number" name="estimasi_jumlah" class="form-control-pink" min="0"
                         placeholder="Contoh: 200">
                </div>
                <div class="col-md-6">
                  <label class="form-label-pink">Bahan yang Diinginkan</label>
                  <input type="text" name="bahan" class="form-control-pink"
                         placeholder="Contoh: Katun, Drill, Linen">
                </div>
                <div class="col-12">
                  <label class="form-label-pink">Warna</label>
                  <input type="text" name="warna" class="form-control-pink"
                         placeholder="Contoh: Coklat tua, Hijau pramuka">
                </div>
                <div class="col-12">
                  <label class="form-label-pink">Catatan & Detail</label>
                  <textarea name="catatan" class="form-control-pink" rows="4"
                            placeholder="Detail desain, deadline, ukuran khusus, dll."></textarea>
                </div>
                <div class="col-12">
                  <label class="form-label-pink">Upload Referensi Desain <span class="text-muted">(JPG/PNG/PDF, max 5MB)</span></label>
                  <input type="file" name="file_referensi" class="form-control-pink"
                         accept="image/jpeg,image/png,application/pdf">
                  <small class="form-hint">Gambar/dokumen referensi membantu kami memahami desain yang Anda inginkan.</small>
                </div>
                <div class="col-12">
                  <button type="submit" class="btn-checkout-submit">
                    <i class="bi bi-send-fill me-2"></i>Ajukan Custom Order
                  </button>
                </div>
              </div>
            </form>
          </div>
        </div>
      </div>

      <!-- ═══ LIST CUSTOM ORDER ═══ -->
      <div class="col-lg-5">
        <div class="checkout-card">
          <div class="checkout-card-header">
            <h5><i class="bi bi-clock-history text-pink me-2"></i>Riwayat Custom Order</h5>
          </div>
          <div class="checkout-card-body" style="padding:0;">
            <?php if (empty($list)): ?>
              <div style="padding:30px;text-align:center;color:var(--gray-500);">
                <i class="bi bi-inbox" style="font-size:36px;display:block;margin-bottom:8px;"></i>
                Belum ada pengajuan custom order
              </div>
            <?php else: ?>
              <?php foreach ($list as $co):
                $status_map = [
                  'Menunggu Review'   => ['#FFF3E0', '#E65100'],
                  'Dalam Diskusi'     => ['#E3F2FD', '#1976D2'],
                  'Penawaran Dikirim' => ['#FFF8E1', '#F57F17'],
                  'Disetujui'         => ['#E8F5E9', '#2E7D32'],
                  'Dalam Produksi'    => ['#E3F2FD', '#1976D2'],
                  'Selesai'           => ['#E8F5E9', '#2E7D32'],
                  'Dibatalkan'        => ['#FFEBEE', '#C62828'],
                ];
                $colors = $status_map[$co['status']] ?? ['#F5F5F5', '#616161'];
              ?>
                <a href="<?= BASE_URL ?>pelanggan/custom_order_detail.php?id=<?= $co['id_custom'] ?>" class="co-list-item">
                  <div class="co-list-info">
                    <div class="co-list-title"><?= htmlspecialchars($co['nama_institusi']) ?></div>
                    <div class="co-list-sub"><?= htmlspecialchars($co['jenis_seragam']) ?></div>
                    <div class="co-list-date"><i class="bi bi-calendar3"></i> <?= date('d M Y', strtotime($co['created_at'])) ?></div>
                  </div>
                  <span class="co-list-badge" style="background:<?= $colors[0] ?>;color:<?= $colors[1] ?>;">
                    <?= htmlspecialchars($co['status']) ?>
                  </span>
                </a>
              <?php endforeach; ?>
            <?php endif; ?>
          </div>
        </div>
      </div>
    </div>

  </div>
</main>

<?php require __DIR__ . '/../templates/footer.php'; ?>
