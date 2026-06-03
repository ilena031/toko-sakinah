<?php
/**
 * UPDATE_CART.PHP — AJAX endpoint untuk update qty item di cart
 * POST: cart_key, jumlah
 * Response: JSON { success, message, item:{subtotal,jumlah,harga}, totals:{items,subtotal}, cart_count }
 */

require_once __DIR__ . '/../config/config.php';
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Metode tidak valid.']);
    exit;
}

$cart_key = $_POST['cart_key'] ?? '';
$jumlah   = (int) ($_POST['jumlah'] ?? 0);

if (!$cart_key || !isset($_SESSION['cart'][$cart_key])) {
    echo json_encode(['success' => false, 'message' => 'Item tidak ditemukan di keranjang.']);
    exit;
}

if ($jumlah < 1) {
    echo json_encode(['success' => false, 'message' => 'Jumlah minimal 1.']);
    exit;
}

$item = $_SESSION['cart'][$cart_key];

// Cek stok terkini dari DB
$stmt = $conn->prepare("SELECT stock_by_size, price_by_size, status FROM produk WHERE id_produk = ?");
$stmt->bind_param('i', $item['id_produk']);
$stmt->execute();
$row = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$row || $row['status'] !== 'aktif') {
    unset($_SESSION['cart'][$cart_key]);
    echo json_encode(['success' => false, 'message' => 'Produk tidak tersedia.']);
    exit;
}

$stock_by_size = json_decode($row['stock_by_size'], true) ?: [];
$price_by_size = json_decode($row['price_by_size'], true) ?: [];
$stok_max      = (int) ($stock_by_size[$item['ukuran']] ?? 0);
$harga         = (float) ($price_by_size[$item['ukuran']] ?? $item['harga']);

if ($jumlah > $stok_max) {
    echo json_encode([
        'success' => false,
        'message' => 'Jumlah melebihi stok (' . $stok_max . ' pcs).'
    ]);
    exit;
}

// Update session
$_SESSION['cart'][$cart_key]['jumlah']   = $jumlah;
$_SESSION['cart'][$cart_key]['harga']    = $harga;
$_SESSION['cart'][$cart_key]['stok_max'] = $stok_max;

$subtotal = $harga * $jumlah;

// Hitung total keseluruhan
$total_subtotal = 0;
$total_items    = 0;
$cart_count     = 0;
foreach ($_SESSION['cart'] as $it) {
    $itPrice = (float) $it['harga'];
    $itQty   = (int)   $it['jumlah'];
    $total_subtotal += $itPrice * $itQty;
    $total_items    += $itQty;
    $cart_count     += $itQty;
}

echo json_encode([
    'success' => true,
    'message' => 'Jumlah berhasil diperbarui.',
    'item' => [
        'subtotal'           => $subtotal,
        'subtotal_formatted' => 'Rp ' . number_format($subtotal, 0, ',', '.'),
        'jumlah'             => $jumlah,
        'harga'              => $harga,
    ],
    'totals' => [
        'items'               => $total_items,
        'subtotal'            => $total_subtotal,
        'subtotal_formatted'  => 'Rp ' . number_format($total_subtotal, 0, ',', '.'),
    ],
    'cart_count' => $cart_count,
]);
