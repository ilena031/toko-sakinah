<?php
/**
 * ADMIN PESANAN INDEX — List + filter (status, tanggal, pelanggan)
 */

require_once __DIR__ . '/../../config/config.php';
require __DIR__ . '/../auth_check.php';

$page_title  = 'Manajemen Pesanan';
$active_menu = 'pesanan';

$filter_status = $_GET['status'] ?? '';
$filter_dari   = $_GET['dari']   ?? '';
$filter_sampai = $_GET['sampai'] ?? '';
$filter_search = trim($_GET['q'] ?? '');

$where  = ['1=1'];
$params = [];
$types  = '';

if ($filter_status) {
    $where[] = 'p.status = ?';
    $params[] = $filter_status;
    $types .= 's';
}
if ($filter_dari) {
    $where[] = 'DATE(p.created_at) >= ?';
    $params[] = $filter_dari;
    $types .= 's';
}
if ($filter_sampai) {
    $where[] = 'DATE(p.created_at) <= ?';
    $params[] = $filter_sampai;
    $types .= 's';
}
if ($filter_search) {
    $where[] = '(p.no_pesanan LIKE ? OR u.nama LIKE ? OR u.email LIKE ?)';
    $like = '%' . $filter_search . '%';
    $params[] = $like; $params[] = $like; $params[] = $like;
    $types .= 'sss';
}

$sql = "SELECT p.*, u.nama AS user_nama, u.email AS user_email,
               (SELECT COUNT(*) FROM detail_pesanan d WHERE d.id_pesanan = p.id_pesanan) AS jml_item
        FROM pesanan p JOIN users u ON p.user_id = u.id
        WHERE " . implode(' AND ', $where) . "
        ORDER BY p.created_at DESC";

$stmt = $conn->prepare($sql);
if ($types) $stmt->bind_param($types, ...$params);
$stmt->execute();
$pesanan_list = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Hitung per status
$r = $conn->query("SELECT status, COUNT(*) AS n FROM pesanan GROUP BY status");
$count_status = ['Menunggu'=>0,'Dikonfirmasi'=>0,'Diproses'=>0,'Dikirim'=>0,'Selesai'=>0,'Dibatalkan'=>0];
while ($row = $r->fetch_assoc()) {
    $count_status[$row['status']] = (int) $row['n'];
}

require __DIR__ . '/../templates/header.php';
?>

<!-- ═══ STATUS TABS ═══ -->
<div class="row g-2 mb-3">
  <?php
  $tabs = [
    ''             => ['label'=>'Semua', 'color'=>'pink',   'count'=>array_sum($count_status)],
    'Menunggu'     => ['label'=>'Menunggu',     'color'=>'orange','count'=>$count_status['Menunggu']],
    'Dikonfirmasi' => ['label'=>'Dikonfirmasi', 'color'=>'blue',  'count'=>$count_status['Dikonfirmasi']],
    'Diproses'     => ['label'=>'Diproses',     'color'=>'yellow','count'=>$count_status['Diproses']],
    'Dikirim'      => ['label'=>'Dikirim',      'color'=>'indigo','count'=>$count_status['Dikirim']],
    'Selesai'      => ['label'=>'Selesai',      'color'=>'green', 'count'=>$count_status['Selesai']],
    'Dibatalkan'   => ['label'=>'Dibatalkan',   'color'=>'red',   'count'=>$count_status['Dibatalkan']],
  ];
  foreach ($tabs as $key => $tab):
    $qs = $key !== '' ? '?status=' . urlencode($key) : '';
    $is_active = $filter_status === $key;
  ?>
    <div class="col">
      <a href="<?= BASE_URL ?>admin/pesanan/index.php<?= $qs ?>"
         class="status-tab <?= $is_active ? 'active' : '' ?> tab-<?= $tab['color'] ?>">
        <div class="tab-count"><?= $tab['count'] ?></div>
        <div class="tab-label"><?= $tab['label'] ?></div>
      </a>
    </div>
  <?php endforeach; ?>
</div>

<div class="adm-card">
  <div class="adm-card-header">
    <h6><i class="bi bi-receipt text-pink"></i> Daftar Pesanan (<?= count($pesanan_list) ?>)</h6>
  </div>
  <div class="adm-card-body">

    <!-- Filter -->
    <form method="GET" class="row g-2 mb-3 align-items-end">
      <input type="hidden" name="status" value="<?= htmlspecialchars($filter_status) ?>">
      <div class="col-md-4">
        <label class="form-label small mb-1">Cari no/nama/email</label>
        <input type="text" name="q" value="<?= htmlspecialchars($filter_search) ?>"
               class="form-control form-control-sm" placeholder="TS-20260530-1234, nama, email">
      </div>
      <div class="col-md-3">
        <label class="form-label small mb-1">Dari tanggal</label>
        <input type="date" name="dari" value="<?= htmlspecialchars($filter_dari) ?>" class="form-control form-control-sm">
      </div>
      <div class="col-md-3">
        <label class="form-label small mb-1">Sampai tanggal</label>
        <input type="date" name="sampai" value="<?= htmlspecialchars($filter_sampai) ?>" class="form-control form-control-sm">
      </div>
      <div class="col-md-2 d-flex gap-2">
        <button type="submit" class="btn btn-pink-sm flex-fill">
          <i class="bi bi-search"></i> Cari
        </button>
        <?php if ($filter_status || $filter_dari || $filter_sampai || $filter_search): ?>
          <a href="<?= BASE_URL ?>admin/pesanan/index.php" class="btn btn-sm btn-outline-secondary">
            <i class="bi bi-x"></i>
          </a>
        <?php endif; ?>
      </div>
    </form>

    <div class="table-responsive">
      <table class="table table-hover">
        <thead>
          <tr>
            <th>No. Pesanan</th>
            <th>Pelanggan</th>
            <th>Item</th>
            <th>Total</th>
            <th>Metode</th>
            <th>Status</th>
            <th>Tanggal</th>
            <th>Aksi</th>
          </tr>
        </thead>
        <tbody>
          <?php if (empty($pesanan_list)): ?>
            <tr><td colspan="8" class="text-center text-muted py-5">
              <i class="bi bi-inbox" style="font-size:36px;display:block;margin-bottom:8px;opacity:0.4;"></i>
              Belum ada pesanan dengan filter ini
            </td></tr>
          <?php else: foreach ($pesanan_list as $p):
            $status_class = 'badge-' . strtolower(str_replace(' ', '', $p['status']));
          ?>
            <tr>
              <td>
                <a href="<?= BASE_URL ?>admin/pesanan/detail.php?id=<?= $p['id_pesanan'] ?>" class="link-pink">
                  <?= htmlspecialchars($p['no_pesanan']) ?>
                </a>
                <?php if ($p['midtrans_status']): ?>
                  <br><small class="text-muted" style="font-size:10.5px;">
                    <i class="bi bi-credit-card"></i> <?= htmlspecialchars($p['midtrans_status']) ?>
                  </small>
                <?php endif; ?>
              </td>
              <td>
                <div class="fw-semibold"><?= htmlspecialchars($p['user_nama']) ?></div>
                <small class="text-muted"><?= htmlspecialchars($p['user_email']) ?></small>
              </td>
              <td><?= $p['jml_item'] ?> item</td>
              <td><strong>Rp <?= number_format($p['total_harga'], 0, ',', '.') ?></strong></td>
              <td><?= htmlspecialchars($p['metode_bayar'] ?: '-') ?></td>
              <td><span class="adm-badge <?= $status_class ?>"><?= htmlspecialchars($p['status']) ?></span></td>
              <td><?= date('d M Y', strtotime($p['created_at'])) ?><br>
                  <small class="text-muted"><?= date('H:i', strtotime($p['created_at'])) ?></small></td>
              <td style="white-space:nowrap;">
                <a href="<?= BASE_URL ?>admin/pesanan/detail.php?id=<?= $p['id_pesanan'] ?>" class="btn-icon" title="Detail">
                  <i class="bi bi-eye"></i>
                </a>
                <a href="<?= BASE_URL ?>cetak_bukti.php?id=<?= $p['id_pesanan'] ?>" target="_blank" class="btn-icon" title="Cetak PDF">
                  <i class="bi bi-file-earmark-pdf"></i>
                </a>
              </td>
            </tr>
          <?php endforeach; endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<style>
.status-tab {
  display: block;
  background: #fff;
  border: 1.5px solid var(--adm-border);
  border-radius: 12px;
  padding: 12px 8px;
  text-align: center;
  text-decoration: none;
  color: var(--adm-text);
  transition: var(--adm-trans);
}
.status-tab:hover { transform: translateY(-2px); box-shadow: 0 4px 12px rgba(15,23,42,0.06); }
.status-tab.active {
  border-color: var(--adm-pink);
  background: linear-gradient(135deg, var(--adm-pink-soft), #FFE4F0);
  color: var(--adm-pink-dark);
}
.tab-count {
  font-family: var(--font-heading, 'Poppins', sans-serif);
  font-weight: 700;
  font-size: 20px;
  line-height: 1;
  margin-bottom: 4px;
  color: inherit;
}
.tab-label {
  font-size: 11.5px;
  font-weight: 600;
  color: var(--adm-muted);
}
.status-tab.active .tab-label { color: var(--adm-pink-dark); }
.status-tab.tab-orange .tab-count { color: #E65100; }
.status-tab.tab-blue   .tab-count { color: var(--adm-blue); }
.status-tab.tab-yellow .tab-count { color: #F57F17; }
.status-tab.tab-indigo .tab-count { color: #3949AB; }
.status-tab.tab-green  .tab-count { color: #2E7D32; }
.status-tab.tab-red    .tab-count { color: #C62828; }
.status-tab.tab-pink   .tab-count { color: var(--adm-pink); }
</style>

<?php require __DIR__ . '/../templates/footer.php'; ?>
