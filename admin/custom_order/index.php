<?php
/**
 * ADMIN CUSTOM ORDER INDEX
 */

require_once __DIR__ . '/../../config/config.php';
require __DIR__ . '/../auth_check.php';

$page_title  = 'Manajemen Custom Order';
$active_menu = 'custom';

$filter_status = $_GET['status'] ?? '';
$filter_search = trim($_GET['q'] ?? '');

$where = ['1=1'];
$params = [];
$types  = '';

if ($filter_status) {
    $where[] = 'co.status = ?';
    $params[] = $filter_status;
    $types .= 's';
}
if ($filter_search) {
    $where[] = '(co.nama_institusi LIKE ? OR co.jenis_seragam LIKE ? OR u.nama LIKE ?)';
    $like = '%' . $filter_search . '%';
    $params[] = $like; $params[] = $like; $params[] = $like;
    $types .= 'sss';
}

$sql = "SELECT co.*, u.nama AS user_nama, u.email AS user_email,
               (SELECT COUNT(*) FROM konsultasi_chat c WHERE c.id_custom = co.id_custom) AS jml_chat,
               (SELECT COUNT(*) FROM konsultasi_chat c WHERE c.id_custom = co.id_custom AND c.pengirim_role = 'pelanggan') AS unread_pel
        FROM custom_order co JOIN users u ON co.user_id = u.id
        WHERE " . implode(' AND ', $where) . "
        ORDER BY co.created_at DESC";

$stmt = $conn->prepare($sql);
if ($types) $stmt->bind_param($types, ...$params);
$stmt->execute();
$list = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Count per status
$r = $conn->query("SELECT status, COUNT(*) AS n FROM custom_order GROUP BY status");
$count_status = [];
while ($row = $r->fetch_assoc()) $count_status[$row['status']] = (int) $row['n'];

require __DIR__ . '/../templates/header.php';
?>

<!-- Status tabs -->
<div class="row g-2 mb-3">
  <?php
  $tabs = [
    ''                   => ['label'=>'Semua',           'color'=>'pink',   'count'=>array_sum($count_status)],
    'Menunggu Review'    => ['label'=>'Menunggu Review', 'color'=>'orange', 'count'=>$count_status['Menunggu Review'] ?? 0],
    'Dalam Diskusi'      => ['label'=>'Diskusi',         'color'=>'blue',   'count'=>$count_status['Dalam Diskusi'] ?? 0],
    'Penawaran Dikirim'  => ['label'=>'Penawaran',       'color'=>'yellow', 'count'=>$count_status['Penawaran Dikirim'] ?? 0],
    'Disetujui'          => ['label'=>'Disetujui',       'color'=>'indigo', 'count'=>$count_status['Disetujui'] ?? 0],
    'Dalam Produksi'     => ['label'=>'Produksi',        'color'=>'blue',   'count'=>$count_status['Dalam Produksi'] ?? 0],
    'Selesai'            => ['label'=>'Selesai',         'color'=>'green',  'count'=>$count_status['Selesai'] ?? 0],
  ];
  foreach ($tabs as $key => $tab):
    $qs = $key !== '' ? '?status=' . urlencode($key) : '';
    $is_active = $filter_status === $key;
  ?>
    <div class="col">
      <a href="<?= BASE_URL ?>admin/custom_order/index.php<?= $qs ?>"
         class="status-tab <?= $is_active ? 'active' : '' ?> tab-<?= $tab['color'] ?>">
        <div class="tab-count"><?= $tab['count'] ?></div>
        <div class="tab-label"><?= $tab['label'] ?></div>
      </a>
    </div>
  <?php endforeach; ?>
</div>

<div class="adm-card">
  <div class="adm-card-header">
    <h6><i class="bi bi-palette2 text-pink"></i> Custom Order (<?= count($list) ?>)</h6>
  </div>
  <div class="adm-card-body">

    <form method="GET" class="row g-2 mb-3 align-items-end">
      <input type="hidden" name="status" value="<?= htmlspecialchars($filter_status) ?>">
      <div class="col-md-6">
        <label class="form-label small mb-1">Cari institusi/jenis/pelanggan</label>
        <input type="text" name="q" value="<?= htmlspecialchars($filter_search) ?>" class="form-control form-control-sm">
      </div>
      <div class="col-md-2">
        <button type="submit" class="btn btn-pink-sm w-100"><i class="bi bi-search"></i> Cari</button>
      </div>
    </form>

    <div class="table-responsive">
      <table class="table table-hover">
        <thead>
          <tr>
            <th>Institusi</th>
            <th>Pelanggan</th>
            <th>Jenis Seragam</th>
            <th>Est. Jumlah</th>
            <th>Chat</th>
            <th>Status</th>
            <th>Tanggal</th>
            <th>Aksi</th>
          </tr>
        </thead>
        <tbody>
          <?php if (empty($list)): ?>
            <tr><td colspan="8" class="text-center text-muted py-5">
              <i class="bi bi-inbox" style="font-size:36px;display:block;margin-bottom:8px;opacity:0.4;"></i>
              Belum ada custom order
            </td></tr>
          <?php else: foreach ($list as $co):
            $co_status_map = [
              'Menunggu Review'   => 'menunggu',
              'Dalam Diskusi'     => 'dikonfirmasi',
              'Penawaran Dikirim' => 'diproses',
              'Disetujui'         => 'dikirim',
              'Dalam Produksi'    => 'dikonfirmasi',
              'Selesai'           => 'selesai',
              'Dibatalkan'        => 'dibatalkan',
            ];
            $cls = 'badge-' . ($co_status_map[$co['status']] ?? 'menunggu');
          ?>
            <tr>
              <td><strong><?= htmlspecialchars($co['nama_institusi']) ?></strong></td>
              <td>
                <?= htmlspecialchars($co['user_nama']) ?>
                <br><small class="text-muted"><?= htmlspecialchars($co['user_email']) ?></small>
              </td>
              <td><?= htmlspecialchars($co['jenis_seragam']) ?></td>
              <td><?= $co['estimasi_jumlah'] ? number_format($co['estimasi_jumlah']) . ' pcs' : '-' ?></td>
              <td>
                <span class="adm-badge badge-info">
                  <i class="bi bi-chat-dots"></i> <?= $co['jml_chat'] ?>
                </span>
              </td>
              <td><span class="adm-badge <?= $cls ?>"><?= htmlspecialchars($co['status']) ?></span></td>
              <td><?= date('d M Y', strtotime($co['created_at'])) ?></td>
              <td>
                <a href="<?= BASE_URL ?>admin/custom_order/detail.php?id=<?= $co['id_custom'] ?>" class="btn-icon" title="Detail & Chat">
                  <i class="bi bi-chat-square-text"></i>
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
  padding: 10px 6px;
  text-align: center;
  text-decoration: none;
  color: var(--adm-text);
  transition: var(--adm-trans);
}
.status-tab:hover { transform: translateY(-2px); box-shadow: 0 4px 12px rgba(15,23,42,0.06); }
.status-tab.active { border-color: var(--adm-pink); background: linear-gradient(135deg, var(--adm-pink-soft), #FFE4F0); }
.tab-count { font-family: var(--font-heading); font-weight: 700; font-size: 18px; line-height: 1; margin-bottom: 4px; }
.tab-label { font-size: 11px; font-weight: 600; color: var(--adm-muted); }
.status-tab.tab-orange .tab-count { color: #E65100; }
.status-tab.tab-blue   .tab-count { color: var(--adm-blue); }
.status-tab.tab-yellow .tab-count { color: #F57F17; }
.status-tab.tab-indigo .tab-count { color: #3949AB; }
.status-tab.tab-green  .tab-count { color: #2E7D32; }
.status-tab.tab-pink   .tab-count { color: var(--adm-pink); }
</style>

<?php require __DIR__ . '/../templates/footer.php'; ?>
