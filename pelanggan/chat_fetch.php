<?php
/**
 * CHAT_FETCH.PHP — AJAX: ambil daftar chat (polling)
 * GET: id_custom, since (id_chat terakhir, opsional)
 */

require_once __DIR__ . '/../config/config.php';
require __DIR__ . '/auth_check.php';
header('Content-Type: application/json');

$user_id   = (int) $_SESSION['user_id'];
$id_custom = (int) ($_GET['id_custom'] ?? 0);
$since     = (int) ($_GET['since'] ?? 0);

if (!$id_custom) {
    echo json_encode(['success' => false, 'message' => 'ID tidak valid.']);
    exit;
}

// Validasi kepemilikan
$stmt = $conn->prepare("SELECT status FROM custom_order WHERE id_custom = ? AND user_id = ?");
$stmt->bind_param('ii', $id_custom, $user_id);
$stmt->execute();
$co = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$co) {
    echo json_encode(['success' => false, 'message' => 'Custom order tidak ditemukan.']);
    exit;
}

// Ambil chat
$stmt = $conn->prepare(
    "SELECT id_chat, pengirim_role, pesan, created_at
     FROM konsultasi_chat
     WHERE id_custom = ? AND id_chat > ?
     ORDER BY id_chat ASC"
);
$stmt->bind_param('ii', $id_custom, $since);
$stmt->execute();
$chats = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

echo json_encode([
    'success'      => true,
    'chats'        => $chats,
    'status'       => $co['status'],
    'chat_closed'  => in_array($co['status'], ['Selesai','Dibatalkan'], true),
]);
