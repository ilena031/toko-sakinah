<?php
/**
 * ULASAN_SIMPAN.PHP — Handler POST simpan ulasan/review produk
 */

require_once __DIR__ . '/../config/config.php';
require __DIR__ . '/auth_check.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . BASE_URL);
    exit;
}

$user_id   = (int) $_SESSION['user_id'];
$id_produk = (int) ($_POST['id_produk'] ?? 0);
$rating    = (int) ($_POST['rating'] ?? 0);
$komentar  = trim($_POST['komentar'] ?? '');

$back = BASE_URL . 'produk_detail.php?id=' . $id_produk . '#ulasan';

if (!$id_produk || $rating < 1 || $rating > 5 || !$komentar) {
    $_SESSION['flash_ulasan_error'] = 'Lengkapi rating & komentar dengan benar.';
    header('Location: ' . $back);
    exit;
}

if (mb_strlen($komentar) > 500) {
    $_SESSION['flash_ulasan_error'] = 'Komentar maksimal 500 karakter.';
    header('Location: ' . $back);
    exit;
}

// Cek sudah pernah review?
$st = $conn->prepare("SELECT id_ulasan FROM ulasan WHERE user_id = ? AND id_produk = ?");
$st->bind_param('ii', $user_id, $id_produk);
$st->execute();
if ($st->get_result()->num_rows > 0) {
    $st->close();
    $_SESSION['flash_ulasan_error'] = 'Kamu sudah memberi ulasan untuk produk ini.';
    header('Location: ' . $back);
    exit;
}
$st->close();

// Cek apakah eligible (pernah beli & selesai)
$st = $conn->prepare(
    "SELECT COUNT(*) AS n FROM pesanan p
     JOIN detail_pesanan d ON p.id_pesanan = d.id_pesanan
     WHERE p.user_id = ? AND d.id_produk = ? AND p.status = 'Selesai'"
);
$st->bind_param('ii', $user_id, $id_produk);
$st->execute();
$eligible = (int) $st->get_result()->fetch_assoc()['n'] > 0;
$st->close();

if (!$eligible) {
    $_SESSION['flash_ulasan_error'] = 'Kamu harus menyelesaikan pesanan produk ini dulu.';
    header('Location: ' . $back);
    exit;
}

// Simpan ulasan
$st = $conn->prepare("INSERT INTO ulasan (user_id, id_produk, rating, komentar) VALUES (?, ?, ?, ?)");
$st->bind_param('iiis', $user_id, $id_produk, $rating, $komentar);
if ($st->execute()) {
    $st->close();

    // Update rating produk dengan avg
    $r = $conn->prepare("UPDATE produk SET rating = (SELECT AVG(rating) FROM ulasan WHERE id_produk = ?) WHERE id_produk = ?");
    $r->bind_param('ii', $id_produk, $id_produk);
    $r->execute();
    $r->close();

    $_SESSION['flash_ulasan_success'] = 'Ulasan berhasil dikirim! Terima kasih.';
} else {
    $_SESSION['flash_ulasan_error'] = 'Gagal menyimpan ulasan: ' . $st->error;
}

header('Location: ' . $back);
exit;
