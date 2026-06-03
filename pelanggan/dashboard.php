<?php
/**
 * DASHBOARD.PHP — Dashboard Pelanggan
 * Tabs: Profil Saya | Riwayat Pesanan | Custom Order
 */

require __DIR__ . '/auth_check.php';

$page_title  = 'Dashboard — Toko Sakinah';
$active_page = 'profil';
$extra_css   = 'dashboard.css';

require __DIR__ . '/../templates/header.php';

// ── Ambil data user ──────────────────────────────────────────
$user_id = $_SESSION['user_id'];
$stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
$stmt->bind_param('i', $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
$stmt->close();

// ── Tab aktif ────────────────────────────────────────────────
$tab = $_GET['tab'] ?? 'profil';
if (!in_array($tab, ['profil', 'pesanan', 'custom'])) $tab = 'profil';

// ── Statistik ────────────────────────────────────────────────
$stmt = $conn->prepare("SELECT COUNT(*) as total FROM pesanan WHERE user_id = ?");
$stmt->bind_param('i', $user_id);
$stmt->execute();
$total_pesanan = $stmt->get_result()->fetch_assoc()['total'];
$stmt->close();

$stmt = $conn->prepare("SELECT COUNT(*) as total FROM pesanan WHERE user_id = ? AND status NOT IN ('Selesai','Dibatalkan')");
$stmt->bind_param('i', $user_id);
$stmt->execute();
$pesanan_aktif = $stmt->get_result()->fetch_assoc()['total'];
$stmt->close();

// ── Flash message ────────────────────────────────────────────
$flash_success = $_SESSION['flash_success'] ?? '';
$flash_error   = $_SESSION['flash_error'] ?? '';
unset($_SESSION['flash_success'], $_SESSION['flash_error']);
?>

<main class="page-content dashboard-wrapper">
  <div class="container">

    <!-- ═══ WELCOME CARD ═══ -->
    <div class="welcome-card">
      <h3><i class="bi bi-person-heart me-2"></i>Halo, <?= htmlspecialchars($user['nama']) ?>!</h3>
      <p>Selamat datang di dashboard pelanggan Toko Sakinah</p>
      <div class="welcome-stats">
        <div class="welcome-stat">
          <span class="stat-number"><?= $total_pesanan ?></span>
          <span class="stat-label">Total Pesanan</span>
        </div>
        <div class="welcome-stat">
          <span class="stat-number"><?= $pesanan_aktif ?></span>
          <span class="stat-label">Pesanan Aktif</span>
        </div>
      </div>
    </div>

    <!-- Flash Messages -->
    <?php if ($flash_success): ?>
      <div class="auth-alert alert-success mb-3" style="border-radius:var(--radius-sm);">
        <i class="bi bi-check-circle"></i> <?= htmlspecialchars($flash_success) ?>
      </div>
    <?php endif; ?>
    <?php if ($flash_error): ?>
      <div class="auth-alert alert-danger mb-3" style="border-radius:var(--radius-sm);">
        <i class="bi bi-exclamation-circle"></i> <?= htmlspecialchars($flash_error) ?>
      </div>
    <?php endif; ?>

    <!-- ═══ TABS ═══ -->
    <div class="dashboard-tabs">
      <a href="?tab=profil" class="dash-tab <?= $tab === 'profil' ? 'active' : '' ?>">
        <i class="bi bi-person"></i> Profil Saya
      </a>
      <a href="?tab=pesanan" class="dash-tab <?= $tab === 'pesanan' ? 'active' : '' ?>">
        <i class="bi bi-bag"></i> Riwayat Pesanan
      </a>
      <a href="?tab=custom" class="dash-tab <?= $tab === 'custom' ? 'active' : '' ?>">
        <i class="bi bi-palette"></i> Custom Order
      </a>
      <a href="<?= BASE_URL ?>pelanggan/logout.php" class="dash-tab ms-auto" style="color:var(--red-badge);">
        <i class="bi bi-box-arrow-right"></i> Logout
      </a>
    </div>

    <!-- ═══ TAB CONTENT ═══ -->

    <?php if ($tab === 'profil'): ?>
    <!-- ── PROFIL ── -->
    <div class="row g-4">
      <div class="col-lg-7">
        <div class="dash-card">
          <div class="dash-card-header">
            <h5><i class="bi bi-person-gear"></i> Edit Profil</h5>
          </div>
          <form class="profile-form" action="<?= BASE_URL ?>pelanggan/update_profil.php" method="POST">
            <input type="hidden" name="action" value="update_profil">
            <div class="form-group">
              <label for="prof-nama">Nama Lengkap</label>
              <input type="text" id="prof-nama" name="nama" value="<?= htmlspecialchars($user['nama']) ?>" required>
            </div>
            <div class="form-group">
              <label for="prof-email">Email</label>
              <input type="email" id="prof-email" value="<?= htmlspecialchars($user['email']) ?>" disabled>
              <small style="color:var(--gray-500);font-size:11.5px;">Email tidak dapat diubah</small>
            </div>
            <div class="form-group">
              <label for="prof-hp">Nomor HP</label>
              <input type="text" id="prof-hp" name="no_hp" value="<?= htmlspecialchars($user['no_hp']) ?>" required>
            </div>
            <div class="form-group">
              <label for="prof-alamat">Alamat</label>
              <textarea id="prof-alamat" name="alamat" placeholder="Masukkan alamat lengkap"><?= htmlspecialchars($user['alamat'] ?? '') ?></textarea>
            </div>
            <button type="submit" class="btn-save">
              <i class="bi bi-check2 me-1"></i> Simpan Perubahan
            </button>
          </form>
        </div>
      </div>

      <div class="col-lg-5">
        <div class="dash-card">
          <div class="dash-card-header">
            <h5><i class="bi bi-key"></i> Ganti Password</h5>
          </div>
          <form class="profile-form" action="<?= BASE_URL ?>pelanggan/update_profil.php" method="POST">
            <input type="hidden" name="action" value="change_password">
            <div class="form-group">
              <label for="old-pass">Password Lama</label>
              <input type="password" id="old-pass" name="old_password" required>
            </div>
            <div class="form-group">
              <label for="new-pass">Password Baru</label>
              <input type="password" id="new-pass" name="new_password" required minlength="6">
            </div>
            <div class="form-group">
              <label for="conf-pass">Konfirmasi Password Baru</label>
              <input type="password" id="conf-pass" name="confirm_password" required>
            </div>
            <button type="submit" class="btn-save">
              <i class="bi bi-shield-check me-1"></i> Update Password
            </button>
          </form>
        </div>
      </div>
    </div>

    <?php elseif ($tab === 'pesanan'): ?>
    <!-- ── RIWAYAT PESANAN ── -->
    <div class="dash-card">
      <div class="dash-card-header">
        <h5><i class="bi bi-receipt"></i> Riwayat Pesanan</h5>
      </div>
      <?php
      $stmt = $conn->prepare("SELECT * FROM pesanan WHERE user_id = ? ORDER BY created_at DESC");
      $stmt->bind_param('i', $user_id);
      $stmt->execute();
      $pesanan_list = $stmt->get_result();
      $stmt->close();
      ?>

      <?php if ($pesanan_list->num_rows > 0): ?>
        <div class="table-responsive">
          <table class="order-table">
            <thead>
              <tr>
                <th>No. Pesanan</th>
                <th>Tanggal</th>
                <th>Total</th>
                <th>Metode Bayar</th>
                <th>Status</th>
                <th>Aksi</th>
              </tr>
            </thead>
            <tbody>
              <?php while ($pes = $pesanan_list->fetch_assoc()):
                $status_class = 'status-' . strtolower(str_replace(' ', '', $pes['status']));
              ?>
                <tr>
                  <td class="no-pesanan"><?= htmlspecialchars($pes['no_pesanan']) ?></td>
                  <td><?= date('d M Y', strtotime($pes['created_at'])) ?></td>
                  <td>Rp <?= number_format($pes['total_harga'], 0, ',', '.') ?></td>
                  <td><?= htmlspecialchars($pes['metode_bayar'] ?: '-') ?></td>
                  <td>
                    <span class="status-badge <?= $status_class ?>"><?= htmlspecialchars($pes['status']) ?></span>
                  </td>
                  <td style="white-space:nowrap;">
                    <?php if ($pes['status'] === 'Menunggu'): ?>
                      <a href="<?= BASE_URL ?>pelanggan/pembayaran.php?id=<?= $pes['id_pesanan'] ?>"
                         title="Bayar" style="color:var(--pink-primary);font-weight:600;font-size:12px;text-decoration:none;margin-right:8px;">
                        <i class="bi bi-credit-card"></i> Bayar
                      </a>
                    <?php endif; ?>
                    <a href="<?= BASE_URL ?>pelanggan/pembayaran_status.php?id=<?= $pes['id_pesanan'] ?>"
                       title="Detail" style="color:var(--gray-700);font-size:14px;margin-right:8px;">
                      <i class="bi bi-eye"></i>
                    </a>
                    <a href="<?= BASE_URL ?>cetak_bukti.php?id=<?= $pes['id_pesanan'] ?>&mode=D"
                       title="Cetak PDF" style="color:var(--red-badge);font-size:14px;" target="_blank">
                      <i class="bi bi-file-earmark-pdf"></i>
                    </a>
                  </td>
                </tr>
              <?php endwhile; ?>
            </tbody>
          </table>
        </div>
      <?php else: ?>
        <div class="empty-state">
          <i class="bi bi-bag-x"></i>
          <h6>Belum ada pesanan</h6>
          <p>Yuk mulai belanja dan pesanan pertamamu akan tampil di sini!</p>
          <a href="<?= BASE_URL ?>katalog.php" class="btn-pink" style="display:inline-block;text-decoration:none;margin-top:12px;font-size:13px;">
            Mulai Belanja
          </a>
        </div>
      <?php endif; ?>
    </div>

    <?php elseif ($tab === 'custom'): ?>
    <!-- ── CUSTOM ORDER ── -->
    <div class="dash-card">
      <div class="dash-card-header">
        <h5><i class="bi bi-palette2"></i> Custom Order Saya</h5>
      </div>
      <?php
      $stmt = $conn->prepare("SELECT * FROM custom_order WHERE user_id = ? ORDER BY created_at DESC");
      $stmt->bind_param('i', $user_id);
      $stmt->execute();
      $custom_list = $stmt->get_result();
      $stmt->close();
      ?>

      <?php if ($custom_list->num_rows > 0): ?>
        <div class="table-responsive">
          <table class="order-table">
            <thead>
              <tr>
                <th>Institusi</th>
                <th>Jenis Seragam</th>
                <th>Est. Jumlah</th>
                <th>Tanggal</th>
                <th>Status</th>
                <th>Aksi</th>
              </tr>
            </thead>
            <tbody>
              <?php while ($co = $custom_list->fetch_assoc()):
                $co_status_map = [
                  'Menunggu Review'     => 'status-menunggu',
                  'Dalam Diskusi'       => 'status-dikonfirmasi',
                  'Penawaran Dikirim'   => 'status-diproses',
                  'Disetujui'           => 'status-dikirim',
                  'Dalam Produksi'      => 'status-diproses',
                  'Selesai'             => 'status-selesai',
                  'Dibatalkan'          => 'status-dibatalkan',
                ];
                $co_class = $co_status_map[$co['status']] ?? 'status-menunggu';
              ?>
                <tr>
                  <td class="no-pesanan">
                    <a href="<?= BASE_URL ?>pelanggan/custom_order_detail.php?id=<?= $co['id_custom'] ?>"
                       style="color:var(--pink-primary);text-decoration:none;">
                      <?= htmlspecialchars($co['nama_institusi']) ?>
                    </a>
                  </td>
                  <td><?= htmlspecialchars($co['jenis_seragam']) ?></td>
                  <td><?= $co['estimasi_jumlah'] ? number_format($co['estimasi_jumlah']) . ' pcs' : '-' ?></td>
                  <td><?= date('d M Y', strtotime($co['created_at'])) ?></td>
                  <td>
                    <span class="status-badge <?= $co_class ?>"><?= htmlspecialchars($co['status']) ?></span>
                  </td>
                  <td>
                    <a href="<?= BASE_URL ?>pelanggan/custom_order_detail.php?id=<?= $co['id_custom'] ?>"
                       style="color:var(--pink-primary);text-decoration:none;font-weight:600;font-size:12px;">
                      <i class="bi bi-chat-dots"></i> Chat
                    </a>
                  </td>
                </tr>
              <?php endwhile; ?>
            </tbody>
          </table>
        </div>
      <?php else: ?>
        <div class="empty-state">
          <i class="bi bi-palette"></i>
          <h6>Belum ada custom order</h6>
          <p>Butuh seragam custom untuk institusi Anda? Ajukan permintaan custom order!</p>
          <a href="<?= BASE_URL ?>pelanggan/custom_order.php" class="btn-pink"
             style="display:inline-block;text-decoration:none;margin-top:12px;font-size:13px;">
            Buat Custom Order
          </a>
        </div>
      <?php endif; ?>
      <div style="text-align:right;margin-top:12px;">
        <a href="<?= BASE_URL ?>pelanggan/custom_order.php" class="btn-pink"
           style="display:inline-block;text-decoration:none;font-size:13px;padding:8px 16px;">
          <i class="bi bi-plus-circle me-1"></i> Buat Custom Order Baru
        </a>
      </div>
    </div>

    <?php endif; ?>

  </div>
</main>

<?php require __DIR__ . '/../templates/footer.php'; ?>
