<?php
/**
 * ADMIN PESANAN DETAIL — Detail + update status + input resi + verifikasi
 */

require_once __DIR__ . '/../../config/config.php';
require __DIR__ . '/../auth_check.php';

$id = (int) ($_GET['id'] ?? 0);
if (!$id) {
    header('Location: ' . BASE_URL . 'admin/pesanan/index.php');
    exit;
}

// Handle POST update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'update_status') {
        $new_status = $_POST['status'] ?? '';
        $valid = ['Menunggu','Dikonfirmasi','Diproses','Dikirim','Selesai','Dibatalkan'];
        if (in_array($new_status, $valid, true)) {
            $stmt = $conn->prepare("UPDATE pesanan SET status = ? WHERE id_pesanan = ?");
            $stmt->bind_param('si', $new_status, $id);
            $stmt->execute();
            $stmt->close();
            $_SESSION['flash_success'] = 'Status pesanan berhasil diubah ke ' . $new_status;
        }
        header('Location: ' . BASE_URL . 'admin/pesanan/detail.php?id=' . $id);
        exit;
    }

    if ($action === 'update_resi') {
        $resi = trim($_POST['no_resi'] ?? '');
        $stmt = $conn->prepare("UPDATE pesanan SET no_resi = ?, status = 'Dikirim' WHERE id_pesanan = ?");
        $stmt->bind_param('si', $resi, $id);
        $stmt->execute();
        $stmt->close();
        $_SESSION['flash_success'] = 'Nomor resi tersimpan & status diubah ke Dikirim.';
        header('Location: ' . BASE_URL . 'admin/pesanan/detail.php?id=' . $id);
        exit;
    }

    if ($action === 'verifikasi') {
        $approve = ($_POST['decision'] ?? '') === 'approve';
        $new_status = $approve ? 'Dikonfirmasi' : 'Dibatalkan';
        $stmt = $conn->prepare("UPDATE pesanan SET status = ?, midtrans_status = 'manual_verified' WHERE id_pesanan = ?");
        $stmt->bind_param('si', $new_status, $id);
        $stmt->execute();
        $stmt->close();
        $_SESSION['flash_success'] = $approve
            ? 'Pembayaran manual dikonfirmasi.'
            : 'Pembayaran ditolak — pesanan dibatalkan.';
        header('Location: ' . BASE_URL . 'admin/pesanan/detail.php?id=' . $id);
        exit;
    }
}

// Fetch pesanan + items
$stmt = $conn->prepare(
    "SELECT p.*, u.nama AS user_nama, u.email AS user_email, u.no_hp AS user_hp
     FROM pesanan p JOIN users u ON p.user_id = u.id
     WHERE p.id_pesanan = ?"
);
$stmt->bind_param('i', $id);
$stmt->execute();
$pesanan = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$pesanan) {
    header('Location: ' . BASE_URL . 'admin/pesanan/index.php');
    exit;
}

$page_title  = 'Detail #' . $pesanan['no_pesanan'];
$active_menu = 'pesanan';

$stmt = $conn->prepare(
    "SELECT dp.*, p.foto FROM detail_pesanan dp
     LEFT JOIN produk p ON dp.id_produk = p.id_produk
     WHERE dp.id_pesanan = ?"
);
$stmt->bind_param('i', $id);
$stmt->execute();
$items = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

$subtotal = $pesanan['total_harga'] - $pesanan['ongkir'];

require __DIR__ . '/../templates/header.php';
?>

<a href="<?= BASE_URL ?>admin/pesanan/index.php" class="adm-link-sm mb-3 d-inline-block">
  <i class="bi bi-arrow-left"></i> Kembali ke daftar pesanan
</a>

<div class="row g-3">
  <!-- ═══ LEFT: ITEMS + INFO ═══ -->
  <div class="col-lg-8">

    <!-- Header info -->
    <div class="adm-card mb-3">
      <div class="adm-card-body">
        <div class="d-flex justify-content-between align-items-start flex-wrap gap-2">
          <div>
            <h4 style="font-family:var(--font-heading);font-weight:700;margin-bottom:4px;">
              <?= htmlspecialchars($pesanan['no_pesanan']) ?>
            </h4>
            <div class="text-muted small">
              Dibuat <?= date('d M Y, H:i', strtotime($pesanan['created_at'])) ?> WIB
            </div>
          </div>
          <div class="text-end">
            <?php
              $status_class = 'badge-' . strtolower(str_replace(' ', '', $pesanan['status']));
            ?>
            <span class="adm-badge <?= $status_class ?>" style="font-size:13px;padding:6px 14px;">
              <?= htmlspecialchars($pesanan['status']) ?>
            </span>
            <div class="text-muted small mt-1">
              <?php if ($pesanan['midtrans_status']): ?>
                Midtrans: <?= htmlspecialchars($pesanan['midtrans_status']) ?>
              <?php endif; ?>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Items -->
    <div class="adm-card mb-3">
      <div class="adm-card-header">
        <h6><i class="bi bi-box-seam text-pink"></i> Item Pesanan (<?= count($items) ?>)</h6>
      </div>
      <div class="adm-card-body p-0">
        <table class="table mb-0">
          <thead>
            <tr>
              <th></th>
              <th>Produk</th>
              <th>Ukuran</th>
              <th>Harga</th>
              <th>Qty</th>
              <th class="text-end">Subtotal</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($items as $it): ?>
              <tr>
                <td style="width:60px;">
                  <img src="<?= BASE_URL . htmlspecialchars($it['foto'] ?? '') ?>" alt=""
                       style="width:50px;height:50px;object-fit:cover;border-radius:6px;">
                </td>
                <td><strong><?= htmlspecialchars($it['nama_produk']) ?></strong></td>
                <td><?= htmlspecialchars($it['ukuran']) ?></td>
                <td>Rp <?= number_format($it['harga_satuan'], 0, ',', '.') ?></td>
                <td><?= $it['jumlah'] ?></td>
                <td class="text-end fw-semibold">Rp <?= number_format($it['subtotal'], 0, ',', '.') ?></td>
              </tr>
            <?php endforeach; ?>
            <tr>
              <td colspan="5" class="text-end text-muted">Subtotal Produk</td>
              <td class="text-end">Rp <?= number_format($subtotal, 0, ',', '.') ?></td>
            </tr>
            <tr>
              <td colspan="5" class="text-end text-muted">Ongkir (<?= htmlspecialchars($pesanan['metode_pengiriman']) ?>)</td>
              <td class="text-end">Rp <?= number_format($pesanan['ongkir'], 0, ',', '.') ?></td>
            </tr>
            <tr style="background:var(--adm-pink-soft);">
              <td colspan="5" class="text-end fw-bold" style="color:var(--adm-pink-dark);">TOTAL BAYAR</td>
              <td class="text-end fw-bold" style="color:var(--adm-pink-dark);font-size:15px;">
                Rp <?= number_format($pesanan['total_harga'], 0, ',', '.') ?>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Alamat -->
    <div class="adm-card">
      <div class="adm-card-header">
        <h6><i class="bi bi-geo-alt text-pink"></i> Alamat Pengiriman</h6>
      </div>
      <div class="adm-card-body">
        <pre style="white-space:pre-wrap;font-family:inherit;margin:0;font-size:13px;color:var(--adm-text);"><?= htmlspecialchars($pesanan['alamat_kirim']) ?></pre>
      </div>
    </div>
  </div>

  <!-- ═══ RIGHT: ACTIONS ═══ -->
  <div class="col-lg-4">

    <!-- Pelanggan -->
    <div class="adm-card mb-3">
      <div class="adm-card-header">
        <h6><i class="bi bi-person text-pink"></i> Pelanggan</h6>
      </div>
      <div class="adm-card-body">
        <div class="info-row"><span>Nama</span><strong><?= htmlspecialchars($pesanan['user_nama']) ?></strong></div>
        <div class="info-row"><span>Email</span><strong><?= htmlspecialchars($pesanan['user_email']) ?></strong></div>
        <div class="info-row"><span>HP</span><strong><?= htmlspecialchars($pesanan['user_hp']) ?></strong></div>
      </div>
    </div>

    <!-- Ubah status -->
    <div class="adm-card mb-3">
      <div class="adm-card-header">
        <h6><i class="bi bi-arrow-repeat text-pink"></i> Ubah Status</h6>
      </div>
      <div class="adm-card-body">
        <form method="POST">
          <input type="hidden" name="action" value="update_status">
          <select name="status" class="form-select form-select-sm mb-2">
            <?php foreach (['Menunggu','Dikonfirmasi','Diproses','Dikirim','Selesai','Dibatalkan'] as $s): ?>
              <option value="<?= $s ?>" <?= $pesanan['status'] === $s ? 'selected' : '' ?>><?= $s ?></option>
            <?php endforeach; ?>
          </select>
          <button type="submit" class="btn btn-pink-sm w-100">
            <i class="bi bi-check-circle"></i> Update Status
          </button>
        </form>
      </div>
    </div>

    <!-- Verifikasi manual transfer -->
    <?php if ($pesanan['status'] === 'Menunggu' && !$pesanan['midtrans_order_id']): ?>
      <div class="adm-card mb-3">
        <div class="adm-card-header">
          <h6><i class="bi bi-cash-coin text-pink"></i> Verifikasi Manual Transfer</h6>
        </div>
        <div class="adm-card-body">
          <?php if ($pesanan['bukti_bayar']): ?>
            <p class="small mb-2">Bukti transfer:</p>
            <img src="<?= BASE_URL . htmlspecialchars($pesanan['bukti_bayar']) ?>" alt="Bukti"
                 style="max-width:100%;border-radius:8px;border:1px solid var(--adm-border);">
          <?php else: ?>
            <p class="small text-muted">Belum ada bukti transfer di-upload.</p>
          <?php endif; ?>
          <form method="POST" class="mt-2 d-flex gap-2">
            <input type="hidden" name="action" value="verifikasi">
            <button type="submit" name="decision" value="approve" class="btn btn-sm flex-fill"
                    style="background:#2E7D32;color:#fff;border:none;border-radius:8px;font-weight:600;font-size:12px;padding:7px;"
                    onclick="return confirm('Konfirmasi pembayaran diterima?')">
              <i class="bi bi-check-circle"></i> Setujui
            </button>
            <button type="submit" name="decision" value="reject" class="btn btn-sm flex-fill"
                    style="background:#C62828;color:#fff;border:none;border-radius:8px;font-weight:600;font-size:12px;padding:7px;"
                    onclick="return confirm('Tolak pembayaran & batalkan pesanan?')">
              <i class="bi bi-x-circle"></i> Tolak
            </button>
          </form>
        </div>
      </div>
    <?php endif; ?>

    <!-- Resi pengiriman -->
    <?php if (in_array($pesanan['status'], ['Dikonfirmasi','Diproses','Dikirim'], true)): ?>
      <div class="adm-card mb-3">
        <div class="adm-card-header">
          <h6><i class="bi bi-truck text-pink"></i> Resi Pengiriman</h6>
        </div>
        <div class="adm-card-body">
          <form method="POST">
            <input type="hidden" name="action" value="update_resi">
            <input type="text" name="no_resi" value="<?= htmlspecialchars($pesanan['no_resi'] ?? '') ?>"
                   class="form-control form-control-sm mb-2" placeholder="Masukkan nomor resi">
            <button type="submit" class="btn btn-blue w-100" style="font-size:12px;padding:7px;">
              <i class="bi bi-send"></i> Simpan & Tandai Dikirim
            </button>
          </form>
        </div>
      </div>
    <?php elseif ($pesanan['no_resi']): ?>
      <div class="adm-card mb-3">
        <div class="adm-card-header">
          <h6><i class="bi bi-truck text-pink"></i> Resi</h6>
        </div>
        <div class="adm-card-body">
          <code style="background:var(--adm-blue-soft);padding:6px 10px;border-radius:6px;display:inline-block;">
            <?= htmlspecialchars($pesanan['no_resi']) ?>
          </code>
        </div>
      </div>
    <?php endif; ?>

    <!-- Cetak PDF -->
    <a href="<?= BASE_URL ?>cetak_bukti.php?id=<?= $id ?>" target="_blank" class="btn btn-pink w-100 mb-2">
      <i class="bi bi-file-earmark-pdf"></i> Cetak Bukti PDF
    </a>

  </div>
</div>

<style>
.info-row {
  display: flex;
  justify-content: space-between;
  padding: 6px 0;
  font-size: 13px;
  border-bottom: 1px dashed var(--adm-border);
}
.info-row:last-child { border-bottom: none; }
.info-row span { color: var(--adm-muted); }
.info-row strong { color: var(--adm-text); }
</style>

<?php require __DIR__ . '/../templates/footer.php'; ?>
