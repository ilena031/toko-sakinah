<?php
/**
 * ADMIN PRODUK — Hapus
 */

require_once __DIR__ . '/../../config/config.php';
require __DIR__ . '/../auth_check.php';

$id = (int) ($_GET['id'] ?? 0);
if (!$id) {
    header('Location: ' . BASE_URL . 'admin/produk/index.php');
    exit;
}

// Cek apakah produk pernah dipakai di detail_pesanan
$stmt = $conn->prepare("SELECT COUNT(*) AS n FROM detail_pesanan WHERE id_produk = ?");
$stmt->bind_param('i', $id);
$stmt->execute();
$used = (int) $stmt->get_result()->fetch_assoc()['n'];
$stmt->close();

if ($used > 0) {
    // Jangan hard delete — soft delete jadi nonaktif
    $stmt = $conn->prepare("UPDATE produk SET status = 'nonaktif' WHERE id_produk = ?");
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $stmt->close();
    $_SESSION['flash_success'] = 'Produk dinonaktifkan (sudah ada riwayat pesanan, tidak bisa dihapus permanen).';
} else {
    // Aman hapus — ambil dulu foto untuk dihapus dari disk
    $stmt = $conn->prepare("SELECT foto, foto_all FROM produk WHERE id_produk = ?");
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if ($row) {
        $all = json_decode($row['foto_all'], true) ?: [$row['foto']];
        foreach ($all as $fp) {
            $abs = __DIR__ . '/../../' . $fp;
            if (file_exists($abs)) @unlink($abs);
        }
    }

    $stmt = $conn->prepare("DELETE FROM produk WHERE id_produk = ?");
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $stmt->close();
    $_SESSION['flash_success'] = 'Produk dihapus.';
}

header('Location: ' . BASE_URL . 'admin/produk/index.php');
exit;
