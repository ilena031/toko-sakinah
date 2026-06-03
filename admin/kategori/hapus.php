<?php
require_once __DIR__ . '/../../config/config.php';
require __DIR__ . '/../auth_check.php';

$id = (int) ($_GET['id'] ?? 0);
if (!$id) {
    header('Location: ' . BASE_URL . 'admin/kategori/index.php');
    exit;
}

// Cek apakah ada produk yang masih pakai
$stmt = $conn->prepare("SELECT COUNT(*) AS n FROM produk WHERE id_kategori = ?");
$stmt->bind_param('i', $id);
$stmt->execute();
$used = (int) $stmt->get_result()->fetch_assoc()['n'];
$stmt->close();

if ($used > 0) {
    $_SESSION['flash_error'] = 'Tidak bisa hapus kategori yang masih dipakai produk (' . $used . ' produk).';
} else {
    $stmt = $conn->prepare("DELETE FROM kategori WHERE id_kategori = ?");
    $stmt->bind_param('i', $id);
    if ($stmt->execute()) {
        $_SESSION['flash_success'] = 'Kategori dihapus.';
    } else {
        $_SESSION['flash_error'] = 'Gagal hapus: ' . $stmt->error;
    }
    $stmt->close();
}

header('Location: ' . BASE_URL . 'admin/kategori/index.php');
exit;
