<?php
/**
 * ADMIN DASHBOARD — Statistik + Grafik + Pesanan terbaru
 */

require_once __DIR__ . '/../config/config.php';
require __DIR__ . '/auth_check.php';

$page_title  = 'Dashboard';
$active_menu = 'dashboard';

// ── Statistik ────────────────────────────────────────────
$stats = [];

// Total produk aktif
$r = $conn->query("SELECT COUNT(*) AS n FROM produk WHERE status = 'aktif'");
$stats['produk'] = (int) $r->fetch_assoc()['n'];

// Total kategori
$r = $conn->query("SELECT COUNT(*) AS n FROM kategori");
$stats['kategori'] = (int) $r->fetch_assoc()['n'];

// Total user pelanggan
$r = $conn->query("SELECT COUNT(*) AS n FROM users WHERE role = 'pelanggan'");
$stats['pelanggan'] = (int) $r->fetch_assoc()['n'];

// Pesanan hari ini
$r = $conn->query("SELECT COUNT(*) AS n FROM pesanan WHERE DATE(created_at) = CURDATE()");
$stats['pesanan_hari'] = (int) $r->fetch_assoc()['n'];

// Pendapatan bulan ini (status Dikonfirmasi/Diproses/Dikirim/Selesai)
$r = $conn->query(
    "SELECT COALESCE(SUM(total_harga),0) AS total
     FROM pesanan
     WHERE YEAR(created_at) = YEAR(CURDATE())
       AND MONTH(created_at) = MONTH(CURDATE())
       AND status IN ('Dikonfirmasi','Diproses','Dikirim','Selesai')"
);
$stats['pendapatan'] = (float) $r->fetch_assoc()['total'];

// Custom order aktif
$r = $conn->query(
    "SELECT COUNT(*) AS n FROM custom_order
     WHERE status NOT IN ('Selesai','Dibatalkan')"
);
$stats['custom_aktif'] = (int) $r->fetch_assoc()['n'];

// Pesanan terbaru
$pesanan_terbaru = $conn->query(
    "SELECT p.id_pesanan, p.no_pesanan, p.total_harga, p.status, p.created_at, u.nama AS user_nama
     FROM pesanan p JOIN users u ON p.user_id = u.id
     ORDER BY p.created_at DESC LIMIT 8"
)->fetch_all(MYSQLI_ASSOC);

// Grafik penjualan 14 hari terakhir
$grafik = [];
$r = $conn->query(
    "SELECT DATE(created_at) AS tgl, COUNT(*) AS jml, COALESCE(SUM(total_harga),0) AS total
     FROM pesanan
     WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 13 DAY)
       AND status IN ('Dikonfirmasi','Diproses','Dikirim','Selesai')
     GROUP BY DATE(created_at)
     ORDER BY tgl"
);
$by_date = [];
while ($row = $r->fetch_assoc()) {
    $by_date[$row['tgl']] = [
        'jml'   => (int) $row['jml'],
        'total' => (float) $row['total'],
    ];
}
// Fill missing dates
for ($i = 13; $i >= 0; $i--) {
    $d = date('Y-m-d', strtotime("-$i day"));
    $grafik[] = [
        'date'  => date('d M', strtotime($d)),
        'jml'   => $by_date[$d]['jml']   ?? 0,
        'total' => $by_date[$d]['total'] ?? 0,
    ];
}

// Top 5 produk terlaris
$top_produk = $conn->query(
    "SELECT id_produk, nama_produk, foto, sold
     FROM produk WHERE status = 'aktif'
     ORDER BY sold DESC LIMIT 5"
)->fetch_all(MYSQLI_ASSOC);

require __DIR__ . '/templates/header.php';
?>

<!-- ═══ STAT CARDS ═══ -->
<div class="row g-3 mb-4">
  <div class="col-6 col-md-3">
    <div class="stat-card stat-pink">
      <div class="stat-icon"><i class="bi bi-box-seam"></i></div>
      <div class="stat-info">
        <div class="stat-num"><?= number_format($stats['produk']) ?></div>
        <div class="stat-label">Produk Aktif</div>
      </div>
    </div>
  </div>
  <div class="col-6 col-md-3">
    <div class="stat-card stat-blue">
      <div class="stat-icon"><i class="bi bi-people"></i></div>
      <div class="stat-info">
        <div class="stat-num"><?= number_format($stats['pelanggan']) ?></div>
        <div class="stat-label">Pelanggan</div>
      </div>
    </div>
  </div>
  <div class="col-6 col-md-3">
    <div class="stat-card stat-orange">
      <div class="stat-icon"><i class="bi bi-receipt"></i></div>
      <div class="stat-info">
        <div class="stat-num"><?= number_format($stats['pesanan_hari']) ?></div>
        <div class="stat-label">Pesanan Hari Ini</div>
      </div>
    </div>
  </div>
  <div class="col-6 col-md-3">
    <div class="stat-card stat-green">
      <div class="stat-icon"><i class="bi bi-cash-stack"></i></div>
      <div class="stat-info">
        <div class="stat-num">Rp <?= number_format($stats['pendapatan'] / 1000, 0, ',', '.') ?>K</div>
        <div class="stat-label">Pendapatan Bulan Ini</div>
      </div>
    </div>
  </div>
</div>

<!-- ═══ GRAFIK + CUSTOM ORDER ═══ -->
<div class="row g-3 mb-4">
  <div class="col-lg-8">
    <div class="adm-card">
      <div class="adm-card-header">
        <h6><i class="bi bi-graph-up text-pink"></i> Penjualan 14 Hari Terakhir</h6>
      </div>
      <div class="adm-card-body">
        <canvas id="chartPenjualan" height="100"></canvas>
      </div>
    </div>
  </div>
  <div class="col-lg-4">
    <div class="adm-card stat-highlight-card">
      <div class="adm-card-header">
        <h6><i class="bi bi-palette2 text-pink"></i> Custom Order Aktif</h6>
      </div>
      <div class="adm-card-body text-center">
        <div class="big-stat"><?= number_format($stats['custom_aktif']) ?></div>
        <div class="text-muted small">Custom order yang sedang diproses</div>
        <a href="<?= BASE_URL ?>admin/custom_order/index.php" class="btn btn-pink-sm mt-3">
          <i class="bi bi-eye"></i> Lihat semua
        </a>
      </div>
    </div>

    <div class="adm-card mt-3">
      <div class="adm-card-header">
        <h6><i class="bi bi-tags text-pink"></i> Kategori</h6>
      </div>
      <div class="adm-card-body text-center">
        <div class="big-stat"><?= number_format($stats['kategori']) ?></div>
        <a href="<?= BASE_URL ?>admin/kategori/index.php" class="btn btn-pink-sm mt-3">
          <i class="bi bi-gear"></i> Kelola
        </a>
      </div>
    </div>
  </div>
</div>

<!-- ═══ PESANAN TERBARU + TOP PRODUK ═══ -->
<div class="row g-3">
  <div class="col-lg-8">
    <div class="adm-card">
      <div class="adm-card-header">
        <h6><i class="bi bi-clock-history text-pink"></i> Pesanan Terbaru</h6>
        <a href="<?= BASE_URL ?>admin/pesanan/index.php" class="adm-link-sm">Lihat semua →</a>
      </div>
      <div class="adm-card-body p-0">
        <div class="table-responsive">
          <table class="table mb-0">
            <thead>
              <tr>
                <th>No. Pesanan</th>
                <th>Pelanggan</th>
                <th>Total</th>
                <th>Status</th>
                <th>Tanggal</th>
              </tr>
            </thead>
            <tbody>
              <?php if (empty($pesanan_terbaru)): ?>
                <tr><td colspan="5" class="text-center text-muted py-4">Belum ada pesanan</td></tr>
              <?php else: foreach ($pesanan_terbaru as $p):
                $status_class = 'badge-' . strtolower(str_replace(' ', '', $p['status']));
              ?>
                <tr>
                  <td><a href="<?= BASE_URL ?>admin/pesanan/detail.php?id=<?= $p['id_pesanan'] ?>" class="link-pink"><?= htmlspecialchars($p['no_pesanan']) ?></a></td>
                  <td><?= htmlspecialchars($p['user_nama']) ?></td>
                  <td>Rp <?= number_format($p['total_harga'], 0, ',', '.') ?></td>
                  <td><span class="adm-badge <?= $status_class ?>"><?= htmlspecialchars($p['status']) ?></span></td>
                  <td><?= date('d M H:i', strtotime($p['created_at'])) ?></td>
                </tr>
              <?php endforeach; endif; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>

  <div class="col-lg-4">
    <div class="adm-card">
      <div class="adm-card-header">
        <h6><i class="bi bi-trophy text-pink"></i> Top 5 Terlaris</h6>
      </div>
      <div class="adm-card-body p-0">
        <?php if (empty($top_produk)): ?>
          <div class="text-center text-muted py-4">Belum ada data</div>
        <?php else: foreach ($top_produk as $i => $tp): ?>
          <div class="top-prod-item">
            <span class="top-rank"><?= $i + 1 ?></span>
            <img src="<?= BASE_URL . htmlspecialchars($tp['foto']) ?>" alt="">
            <div class="top-info">
              <div class="top-name"><?= htmlspecialchars($tp['nama_produk']) ?></div>
              <div class="top-sold"><?= number_format($tp['sold']) ?> terjual</div>
            </div>
          </div>
        <?php endforeach; endif; ?>
      </div>
    </div>
  </div>
</div>

<!-- Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<script>
  const ctx = document.getElementById('chartPenjualan');
  if (ctx) {
    new Chart(ctx, {
      type: 'line',
      data: {
        labels: <?= json_encode(array_column($grafik, 'date')) ?>,
        datasets: [
          {
            label: 'Total Penjualan (Rp)',
            data: <?= json_encode(array_column($grafik, 'total')) ?>,
            borderColor: '#E91E63',
            backgroundColor: 'rgba(233, 30, 99, 0.1)',
            fill: true,
            tension: 0.35,
            borderWidth: 2,
            pointRadius: 4,
            pointBackgroundColor: '#E91E63',
          }
        ]
      },
      options: {
        responsive: true,
        plugins: {
          legend: { display: false },
          tooltip: {
            callbacks: {
              label: (ctx) => 'Rp ' + new Intl.NumberFormat('id-ID').format(ctx.parsed.y),
            }
          }
        },
        scales: {
          y: {
            beginAtZero: true,
            ticks: {
              callback: (v) => 'Rp ' + (v >= 1000 ? (v / 1000) + 'K' : v),
            }
          }
        }
      }
    });
  }
</script>

<?php require __DIR__ . '/templates/footer.php'; ?>
