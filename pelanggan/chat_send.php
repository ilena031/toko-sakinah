<?php
/**
 * CHAT_SEND.PHP — AJAX: kirim pesan chat konsultasi custom order
 */

require_once __DIR__ . '/../config/config.php';
require __DIR__ . '/auth_check.php';
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Metode tidak valid.']);
    exit;
}

$user_id   = (int) $_SESSION['user_id'];
$id_custom = (int) ($_POST['id_custom'] ?? 0);
$pesan     = trim($_POST['pesan'] ?? '');

if (!$id_custom || !$pesan) {
    echo json_encode(['success' => false, 'message' => 'Pesan tidak boleh kosong.']);
    exit;
}

// Validasi kepemilikan
$stmt = $conn->prepare("SELECT id_custom, status FROM custom_order WHERE id_custom = ? AND user_id = ?");
$stmt->bind_param('ii', $id_custom, $user_id);
$stmt->execute();
$co = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$co) {
    echo json_encode(['success' => false, 'message' => 'Custom order tidak ditemukan.']);
    exit;
}

if (in_array($co['status'], ['Selesai','Dibatalkan'], true)) {
    echo json_encode(['success' => false, 'message' => 'Chat ditutup untuk pesanan ini.']);
    exit;
}

// Insert chat
$role = 'pelanggan';
$stmt = $conn->prepare("INSERT INTO konsultasi_chat (id_custom, pengirim_role, pesan) VALUES (?, ?, ?)");
$stmt->bind_param('iss', $id_custom, $role, $pesan);

if ($stmt->execute()) {
    $id_chat = $stmt->insert_id;
    $stmt->close();

    // Update custom order status: kalau masih "Menunggu Review", ubah ke "Dalam Diskusi"
    if ($co['status'] === 'Menunggu Review') {
        $u = $conn->prepare("UPDATE custom_order SET status = 'Dalam Diskusi' WHERE id_custom = ?");
        $u->bind_param('i', $id_custom);
        $u->execute();
        $u->close();
    }

    echo json_encode([
        'success' => true,
        'chat' => [
            'id_chat'       => $id_chat,
            'pengirim_role' => 'pelanggan',
            'pesan'         => $pesan,
            'created_at'    => date('Y-m-d H:i:s'),
        ]
    ]);
} else {
    echo json_encode(['success' => false, 'message' => 'Gagal mengirim pesan.']);
    $stmt->close();
}
