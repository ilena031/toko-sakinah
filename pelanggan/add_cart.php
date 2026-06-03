<?php
/**
 * ADD_CART.PHP — Handler POST tambah produk ke keranjang
 * Sumber: form di produk_detail.php
 * Mendukung response JSON (untuk AJAX) atau redirect (untuk form submit biasa).
 */

require_once __DIR__ . '/../config/config.php';

// Deteksi AJAX via beberapa signal (robust meski ada cache JS lama):
// 1. X-Requested-With: XMLHttpRequest (custom header)
// 2. Accept: application/json
// 3. Sec-Fetch-Mode: cors  (fetch() pakai ini; form biasa pakai 'navigate')
// 4. Sec-Fetch-Dest: empty (fetch() pakai ini; form biasa pakai 'document')
$accept       = $_SERVER['HTTP_ACCEPT']           ?? '';
$xrw          = $_SERVER['HTTP_X_REQUESTED_WITH'] ?? '';
$fetch_mode   = $_SERVER['HTTP_SEC_FETCH_MODE']   ?? '';
$fetch_dest   = $_SERVER['HTTP_SEC_FETCH_DEST']   ?? '';

$is_ajax = strtolower($xrw) === 'xmlhttprequest'
        || strpos($accept, 'application/json') !== false
        || ($fetch_mode === 'cors' && $fetch_dest === 'empty');

function respond($success, $message, $redirect = null, $cart_count = null) {
    global $is_ajax;
    if ($is_ajax) {
        header('Content-Type: application/json');
        echo json_encode(['success' => $success, 'message' => $message, 'cart_count' => $cart_count]);
        exit;
    }
    if ($success) {
        $_SESSION['flash_success'] = $message;
    } else {
        $_SESSION['flash_error'] = $message;
    }
    header('Location: ' . ($redirect ?? BASE_URL . 'pelanggan/keranjang.php'));
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    respond(false, 'Metode tidak valid.', BASE_URL);
}

// ── Ambil & validasi input ───────────────────────────────────
$id_produk = (int) ($_POST['id_produk'] ?? 0);
$ukuran    = trim($_POST['ukuran'] ?? '');
$gender    = trim($_POST['gender'] ?? '');
$jumlah    = max(1, (int) ($_POST['jumlah'] ?? 1));

if (!$id_produk) {
    respond(false, 'Produk tidak ditemukan.', BASE_URL . 'katalog.php');
}

// ── Ambil data produk dari DB ────────────────────────────────
$stmt = $conn->prepare("SELECT id_produk, nama_produk, harga, foto, ukuran, price_by_size, stock_by_size, status
                         FROM produk WHERE id_produk = ?");
$stmt->bind_param('i', $id_produk);
$stmt->execute();
$produk = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$produk || $produk['status'] !== 'aktif') {
    respond(false, 'Produk tidak tersedia.', BASE_URL . 'katalog.php');
}

// ── Validasi ukuran & stok ───────────────────────────────────
$sizes         = !empty($produk['ukuran']) ? array_map('trim', explode(',', $produk['ukuran'])) : [];
$price_by_size = json_decode($produk['price_by_size'], true) ?: [];
$stock_by_size = json_decode($produk['stock_by_size'], true) ?: [];

if (!empty($sizes) && !in_array($ukuran, $sizes, true)) {
    respond(false, 'Silakan pilih ukuran terlebih dahulu.',
            BASE_URL . 'produk_detail.php?id=' . $id_produk);
}

$harga = $price_by_size[$ukuran] ?? (float) $produk['harga'];
$stok  = $stock_by_size[$ukuran] ?? 0;

if ($stok <= 0) {
    respond(false, 'Stok ukuran ' . htmlspecialchars($ukuran) . ' habis.',
            BASE_URL . 'produk_detail.php?id=' . $id_produk);
}

if ($jumlah > $stok) {
    respond(false, 'Jumlah melebihi stok tersedia (' . $stok . ' pcs).',
            BASE_URL . 'produk_detail.php?id=' . $id_produk);
}

// ── Init cart session ────────────────────────────────────────
if (!isset($_SESSION['cart']) || !is_array($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

// Key unik = id_produk + ukuran + gender (produk sama ukuran/gender beda = item terpisah)
$cart_key = $id_produk . '|' . $ukuran . '|' . $gender;

if (isset($_SESSION['cart'][$cart_key])) {
    // Sudah ada → tambah qty
    $new_qty = $_SESSION['cart'][$cart_key]['jumlah'] + $jumlah;
    if ($new_qty > $stok) {
        respond(false, 'Total di keranjang akan melebihi stok (' . $stok . ' pcs).',
                BASE_URL . 'produk_detail.php?id=' . $id_produk);
    }
    $_SESSION['cart'][$cart_key]['jumlah'] = $new_qty;
} else {
    $_SESSION['cart'][$cart_key] = [
        'id_produk' => $id_produk,
        'nama'      => $produk['nama_produk'],
        'foto'      => $produk['foto'],
        'ukuran'    => $ukuran,
        'gender'    => $gender,
        'harga'     => (float) $harga,
        'jumlah'    => $jumlah,
        'stok_max'  => (int) $stok,
    ];
}

// Hitung total cart count
$cart_count = 0;
foreach ($_SESSION['cart'] as $item) {
    $cart_count += (int) $item['jumlah'];
}

respond(true,
        'Produk berhasil ditambahkan ke keranjang!',
        BASE_URL . 'pelanggan/keranjang.php',
        $cart_count);
