<?php
/**
 * REMOVE_CART.PHP — AJAX endpoint hapus item dari cart
 * POST: cart_key (atau action=clear untuk kosongkan semua)
 */

require_once __DIR__ . '/../config/config.php';
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Metode tidak valid.']);
    exit;
}

$action   = $_POST['action']   ?? 'remove';
$cart_key = $_POST['cart_key'] ?? '';

if ($action === 'clear') {
    $_SESSION['cart'] = [];
    echo json_encode([
        'success' => true,
        'message' => 'Keranjang berhasil dikosongkan.',
        'cart_count' => 0,
        'totals' => ['items' => 0, 'subtotal_formatted' => 'Rp 0'],
    ]);
    exit;
}

if (!$cart_key || !isset($_SESSION['cart'][$cart_key])) {
    echo json_encode(['success' => false, 'message' => 'Item tidak ditemukan.']);
    exit;
}

unset($_SESSION['cart'][$cart_key]);

// Recompute
$total_subtotal = 0;
$total_items    = 0;
$cart_count     = 0;
foreach ($_SESSION['cart'] as $it) {
    $sub = (float) $it['harga'] * (int) $it['jumlah'];
    $total_subtotal += $sub;
    $total_items    += (int) $it['jumlah'];
    $cart_count     += (int) $it['jumlah'];
}

echo json_encode([
    'success' => true,
    'message' => 'Item berhasil dihapus.',
    'cart_count' => $cart_count,
    'cart_empty' => empty($_SESSION['cart']),
    'totals' => [
        'items'              => $total_items,
        'subtotal'           => $total_subtotal,
        'subtotal_formatted' => 'Rp ' . number_format($total_subtotal, 0, ',', '.'),
    ],
]);
