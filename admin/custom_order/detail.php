<?php
/**
 * ADMIN CUSTOM ORDER DETAIL — Chat balasan + buat penawaran + update status
 */

require_once __DIR__ . '/../../config/config.php';
require __DIR__ . '/../auth_check.php';

$id = (int) ($_GET['id'] ?? 0);
if (!$id) {
    header('Location: ' . BASE_URL . 'admin/custom_order/index.php');
    exit;
}

// POST actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'update_status') {
        $new_status = $_POST['status'] ?? '';
        $valid = ['Menunggu Review','Dalam Diskusi','Penawaran Dikirim','Disetujui','Dalam Produksi','Selesai','Dibatalkan'];
        if (in_array($new_status, $valid, true)) {
            $stmt = $conn->prepare("UPDATE custom_order SET status = ? WHERE id_custom = ?");
            $stmt->bind_param('si', $new_status, $id);
            $stmt->execute();
            $stmt->close();
            $_SESSION['flash_success'] = 'Status diubah ke ' . $new_status;
        }
        header('Location: ' . BASE_URL . 'admin/custom_order/detail.php?id=' . $id);
        exit;
    }

    if ($action === 'penawaran') {
        $harga_satuan = (float) ($_POST['harga_satuan_deal'] ?? 0);
        $jumlah       = (int)   ($_POST['jumlah_final'] ?? 0);
        $dp           = (float) ($_POST['dp_diminta'] ?? 0);
        $deadline     = trim($_POST['deadline'] ?? '');
        $total        = $harga_satuan * $jumlah;

        if ($harga_satuan <= 0 || $jumlah <= 0) {
            $_SESSION['flash_error'] = 'Harga satuan dan jumlah harus lebih dari 0.';
        } else {
            $deadline_db = $deadline ?: null;
            $stmt = $conn->prepare(
                "UPDATE custom_order SET
                    harga_satuan_deal = ?, jumlah_final = ?, total_deal = ?,
                    dp_diminta = ?, deadline = ?, status = 'Penawaran Dikirim'
                 WHERE id_custom = ?"
            );
            $stmt->bind_param('didsi', $harga_satuan, $jumlah, $total, $dp, $deadline_db, $id);
            $stmt->execute();
            $stmt->close();

            // Auto-send chat dengan penawaran
            $msg = "📋 PENAWARAN RESMI:\n";
            $msg .= "• Harga satuan: Rp " . number_format($harga_satuan, 0, ',', '.') . "\n";
            $msg .= "• Jumlah: " . number_format($jumlah) . " pcs\n";
            $msg .= "• Total: Rp " . number_format($total, 0, ',', '.') . "\n";
            if ($dp > 0) $msg .= "• DP yang diminta: Rp " . number_format($dp, 0, ',', '.') . "\n";
            if ($deadline) $msg .= "• Deadline: " . date('d M Y', strtotime($deadline)) . "\n";
            $msg .= "\nMohon konfirmasi untuk lanjut ke pembayaran DP. Terima kasih!";

            $role = 'admin';
            $stmt = $conn->prepare("INSERT INTO konsultasi_chat (id_custom, pengirim_role, pesan) VALUES (?, ?, ?)");
            $stmt->bind_param('iss', $id, $role, $msg);
            $stmt->execute();
            $stmt->close();

            $_SESSION['flash_success'] = 'Penawaran berhasil dikirim ke pelanggan.';
        }
        header('Location: ' . BASE_URL . 'admin/custom_order/detail.php?id=' . $id);
        exit;
    }

    if ($action === 'send_chat') {
        $pesan = trim($_POST['pesan'] ?? '');
        if ($pesan) {
            $role = 'admin';
            $stmt = $conn->prepare("INSERT INTO konsultasi_chat (id_custom, pengirim_role, pesan) VALUES (?, ?, ?)");
            $stmt->bind_param('iss', $id, $role, $pesan);
            $stmt->execute();
            $stmt->close();

            // Update status jadi Dalam Diskusi kalau masih Menunggu Review
            $conn->query("UPDATE custom_order SET status = 'Dalam Diskusi'
                          WHERE id_custom = $id AND status = 'Menunggu Review'");
        }
        header('Location: ' . BASE_URL . 'admin/custom_order/detail.php?id=' . $id);
        exit;
    }
}

// Fetch custom order
$stmt = $conn->prepare(
    "SELECT co.*, u.nama AS user_nama, u.email AS user_email, u.no_hp AS user_hp
     FROM custom_order co JOIN users u ON co.user_id = u.id
     WHERE co.id_custom = ?"
);
$stmt->bind_param('i', $id);
$stmt->execute();
$co = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$co) {
    header('Location: ' . BASE_URL . 'admin/custom_order/index.php');
    exit;
}

// Fetch chats
$stmt = $conn->prepare("SELECT * FROM konsultasi_chat WHERE id_custom = ? ORDER BY id_chat ASC");
$stmt->bind_param('i', $id);
$stmt->execute();
$chats = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

$page_title  = 'Custom #' . $co['id_custom'] . ' — ' . $co['nama_institusi'];
$active_menu = 'custom';

require __DIR__ . '/../templates/header.php';
?>

<a href="<?= BASE_URL ?>admin/custom_order/index.php" class="adm-link-sm mb-3 d-inline-block">
  <i class="bi bi-arrow-left"></i> Kembali ke daftar custom order
</a>

<div class="row g-3">

  <!-- ═══ LEFT: DETAIL + CHAT ═══ -->
  <div class="col-lg-8">

    <!-- Info pengajuan -->
    <div class="adm-card mb-3">
      <div class="adm-card-header">
        <h6><i class="bi bi-info-circle text-pink"></i> Detail Pengajuan</h6>
        <?php
          $co_status_map = [
            'Menunggu Review' => 'menunggu', 'Dalam Diskusi' => 'dikonfirmasi',
            'Penawaran Dikirim' => 'diproses', 'Disetujui' => 'dikirim',
            'Dalam Produksi' => 'dikonfirmasi', 'Selesai' => 'selesai', 'Dibatalkan' => 'dibatalkan',
          ];
          $cls = 'badge-' . ($co_status_map[$co['status']] ?? 'menunggu');
        ?>
        <span class="adm-badge <?= $cls ?>"><?= htmlspecialchars($co['status']) ?></span>
      </div>
      <div class="adm-card-body">
        <div class="row g-3">
          <div class="col-md-6">
            <div class="info-row"><span>Institusi</span><strong><?= htmlspecialchars($co['nama_institusi']) ?></strong></div>
            <div class="info-row"><span>Jenis Seragam</span><strong><?= htmlspecialchars($co['jenis_seragam']) ?></strong></div>
            <?php if ($co['estimasi_jumlah']): ?>
              <div class="info-row"><span>Estimasi Jumlah</span><strong><?= number_format($co['estimasi_jumlah']) ?> pcs</strong></div>
            <?php endif; ?>
          </div>
          <div class="col-md-6">
            <?php if ($co['bahan']): ?>
              <div class="info-row"><span>Bahan</span><strong><?= htmlspecialchars($co['bahan']) ?></strong></div>
            <?php endif; ?>
            <?php if ($co['warna']): ?>
              <div class="info-row"><span>Warna</span><strong><?= htmlspecialchars($co['warna']) ?></strong></div>
            <?php endif; ?>
            <div class="info-row"><span>Diajukan</span><strong><?= date('d M Y H:i', strtotime($co['created_at'])) ?></strong></div>
          </div>
          <?php if ($co['catatan']): ?>
            <div class="col-12">
              <small class="text-muted d-block mb-1">Catatan dari Pelanggan</small>
              <div style="background:var(--adm-bg);padding:10px 14px;border-radius:8px;font-size:13px;">
                <?= nl2br(htmlspecialchars($co['catatan'])) ?>
              </div>
            </div>
          <?php endif; ?>
          <?php if ($co['file_referensi']): ?>
            <div class="col-12">
              <a href="<?= BASE_URL . htmlspecialchars($co['file_referensi']) ?>" target="_blank" class="btn btn-blue btn-sm">
                <i class="bi bi-file-earmark"></i> Lihat File Referensi
              </a>
            </div>
          <?php endif; ?>
        </div>
      </div>
    </div>

    <!-- Chat -->
    <div class="adm-card">
      <div class="adm-card-header">
        <h6><i class="bi bi-chat-dots-fill text-pink"></i> Chat Konsultasi (<?= count($chats) ?> pesan)</h6>
      </div>

      <div class="admin-chat-box" id="chat-box">
        <?php if (empty($chats)): ?>
          <div class="text-center text-muted py-4">
            <i class="bi bi-chat" style="font-size:36px;display:block;margin-bottom:8px;opacity:0.4;"></i>
            Belum ada pesan
          </div>
        <?php else:
          $last_day = '';
          foreach ($chats as $c):
            $day = date('Y-m-d', strtotime($c['created_at']));
            if ($day !== $last_day):
              $last_day = $day;
        ?>
          <div class="chat-day-divider"><span><?= date('d M Y', strtotime($c['created_at'])) ?></span></div>
        <?php endif;
            $is_admin = $c['pengirim_role'] === 'admin';
        ?>
          <div class="chat-bubble-wrap from-<?= $is_admin ? 'admin' : 'pelanggan' ?>">
            <div>
              <div class="chat-sender-label">
                <?= $is_admin ? '👨‍💼 Admin' : '🙋 ' . htmlspecialchars($co['user_nama']) ?>
              </div>
              <div class="chat-bubble"><?= nl2br(htmlspecialchars($c['pesan'])) ?></div>
              <div class="chat-time"><?= date('H:i', strtotime($c['created_at'])) ?></div>
            </div>
          </div>
        <?php endforeach; endif; ?>
      </div>

      <?php if (!in_array($co['status'], ['Selesai','Dibatalkan'], true)): ?>
        <form method="POST" class="co-chat-form" style="padding:12px 16px;border-top:1px solid var(--adm-border);display:flex;gap:8px;">
          <input type="hidden" name="action" value="send_chat">
          <textarea name="pesan" placeholder="Balas pelanggan..." rows="2" required
                    style="flex:1;border:1.5px solid var(--adm-border);border-radius:8px;padding:8px 12px;font-size:13.5px;resize:none;"></textarea>
          <button type="submit" class="btn btn-pink" style="width:48px;">
            <i class="bi bi-send-fill"></i>
          </button>
        </form>
      <?php endif; ?>
    </div>

  </div>

  <!-- ═══ RIGHT: ACTIONS ═══ -->
  <div class="col-lg-4">

    <!-- Pelanggan -->
    <div class="adm-card mb-3">
      <div class="adm-card-header"><h6><i class="bi bi-person text-pink"></i> Pelanggan</h6></div>
      <div class="adm-card-body">
        <div class="info-row"><span>Nama</span><strong><?= htmlspecialchars($co['user_nama']) ?></strong></div>
        <div class="info-row"><span>Email</span><strong><?= htmlspecialchars($co['user_email']) ?></strong></div>
        <div class="info-row"><span>HP</span><strong><?= htmlspecialchars($co['user_hp']) ?></strong></div>
      </div>
    </div>

    <!-- Ubah status -->
    <div class="adm-card mb-3">
      <div class="adm-card-header"><h6><i class="bi bi-arrow-repeat text-pink"></i> Update Status</h6></div>
      <div class="adm-card-body">
        <form method="POST">
          <input type="hidden" name="action" value="update_status">
          <select name="status" class="form-select form-select-sm mb-2">
            <?php foreach (['Menunggu Review','Dalam Diskusi','Penawaran Dikirim','Disetujui','Dalam Produksi','Selesai','Dibatalkan'] as $s): ?>
              <option value="<?= $s ?>" <?= $co['status'] === $s ? 'selected' : '' ?>><?= $s ?></option>
            <?php endforeach; ?>
          </select>
          <button type="submit" class="btn btn-pink-sm w-100">
            <i class="bi bi-check-circle"></i> Update
          </button>
        </form>
      </div>
    </div>

    <!-- Buat / Update Penawaran -->
    <div class="adm-card mb-3">
      <div class="adm-card-header">
        <h6><i class="bi bi-receipt-cutoff text-pink"></i>
          <?= $co['harga_satuan_deal'] ? 'Update Penawaran' : 'Buat Penawaran' ?>
        </h6>
      </div>
      <div class="adm-card-body">
        <form method="POST">
          <input type="hidden" name="action" value="penawaran">
          <div class="form-group mb-2">
            <label class="small">Harga Satuan (Rp)</label>
            <input type="number" name="harga_satuan_deal" class="form-control form-control-sm" required min="1"
                   value="<?= $co['harga_satuan_deal'] ?: '' ?>" id="harga_satuan_deal">
          </div>
          <div class="form-group mb-2">
            <label class="small">Jumlah Final (pcs)</label>
            <input type="number" name="jumlah_final" class="form-control form-control-sm" required min="1"
                   value="<?= $co['jumlah_final'] ?: ($co['estimasi_jumlah'] ?: '') ?>" id="jumlah_final">
          </div>
          <div class="form-group mb-2">
            <label class="small">Total <small class="text-muted">(otomatis)</small></label>
            <input type="text" class="form-control form-control-sm" readonly id="total_deal" style="font-weight:700;color:var(--adm-pink);">
          </div>
          <div class="form-group mb-2">
            <label class="small">DP Diminta (Rp)</label>
            <input type="number" name="dp_diminta" class="form-control form-control-sm" min="0"
                   value="<?= $co['dp_diminta'] ?: '' ?>" placeholder="Opsional">
          </div>
          <div class="form-group mb-2">
            <label class="small">Deadline</label>
            <input type="date" name="deadline" class="form-control form-control-sm"
                   value="<?= $co['deadline'] ?: '' ?>">
          </div>
          <button type="submit" class="btn btn-pink w-100" style="font-size:12.5px;">
            <i class="bi bi-send"></i> Kirim Penawaran
          </button>
        </form>
      </div>
    </div>

  </div>
</div>

<style>
.admin-chat-box {
  padding: 18px;
  max-height: 480px;
  overflow-y: auto;
  background: linear-gradient(to bottom, #FAFBFD 0%, #FFFFFF 100%);
}
.chat-day-divider { text-align: center; margin: 14px 0; }
.chat-day-divider span {
  background: #fff; padding: 4px 12px; border-radius: 12px;
  border: 1px solid var(--adm-border); font-size: 11px; color: var(--adm-muted);
}
.chat-bubble-wrap { display: flex; margin-bottom: 12px; }
.chat-bubble-wrap.from-admin { justify-content: flex-end; }
.chat-bubble-wrap.from-pelanggan { justify-content: flex-start; }
.chat-bubble {
  max-width: 75%; padding: 10px 14px; border-radius: 14px;
  font-size: 13.5px; line-height: 1.5; white-space: pre-wrap; word-wrap: break-word;
}
.chat-bubble-wrap.from-admin .chat-bubble {
  background: var(--adm-pink); color: #fff; border-bottom-right-radius: 4px;
}
.chat-bubble-wrap.from-pelanggan .chat-bubble {
  background: var(--adm-blue-soft); color: var(--adm-text); border-bottom-left-radius: 4px;
}
.chat-time { font-size: 10.5px; color: var(--adm-muted); margin-top: 4px; text-align: right; }
.chat-bubble-wrap.from-pelanggan .chat-time { text-align: left; }
.chat-sender-label { font-size: 11px; font-weight: 600; color: var(--adm-muted); margin-bottom: 2px; padding: 0 10px; }
.chat-bubble-wrap.from-admin .chat-sender-label { text-align: right; }
</style>

<script>
  // Total deal auto-calc
  const h = document.getElementById('harga_satuan_deal');
  const j = document.getElementById('jumlah_final');
  const t = document.getElementById('total_deal');
  function recalc() {
    const total = (parseFloat(h.value) || 0) * (parseInt(j.value) || 0);
    t.value = 'Rp ' + new Intl.NumberFormat('id-ID').format(total);
  }
  h.addEventListener('input', recalc);
  j.addEventListener('input', recalc);
  recalc();

  // Scroll chat to bottom
  const cb = document.getElementById('chat-box');
  if (cb) cb.scrollTop = cb.scrollHeight;
</script>

<style>
.info-row { display: flex; justify-content: space-between; padding: 6px 0; font-size: 13px; border-bottom: 1px dashed var(--adm-border); }
.info-row:last-child { border-bottom: none; }
.info-row span { color: var(--adm-muted); }
.info-row strong { color: var(--adm-text); }
</style>

<?php require __DIR__ . '/../templates/footer.php'; ?>
