<?php
/**
 * PROSES_CHECKOUT.PHP — Handler buat pesanan baru
 * Validasi stok, generate no_pesanan unik, insert pesanan + detail_pesanan,
 * kurangi stok per ukuran (transaction), kosongkan cart, redirect ke pembayaran.
 */

require_once __DIR__ . '/../config/config.php';
require __DIR__ . '/auth_check.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . BASE_URL . 'pelanggan/keranjang.php');
    exit;
}

$user_id = (int) $_SESSION['user_id'];
$cart    = $_SESSION['cart'] ?? [];

if (empty($cart)) {
    $_SESSION['flash_error'] = 'Keranjang kosong.';
    header('Location: ' . BASE_URL . 'pelanggan/keranjang.php');
    exit;
}

// ── Ambil & validasi input form ────────────────────────────
$penerima_nama   = trim($_POST['penerima_nama']    ?? '');
$penerima_hp     = trim($_POST['penerima_hp']      ?? '');
$penerima_alamat = trim($_POST['penerima_alamat']  ?? '');
$catatan         = trim($_POST['catatan']          ?? '');
$metode_kirim    = trim($_POST['metode_pengiriman']?? '');

if (!$penerima_nama || !$penerima_hp || !$penerima_alamat || !$metode_kirim) {
    $_SESSION['flash_error'] = 'Lengkapi semua data alamat pengiriman.';
    header('Location: ' . BASE_URL . 'pelanggan/checkout.php');
    exit;
}

// ── Tabel ongkir (HARUS server-side, JANGAN trust input client) ─
$shipping_table = [
    'JNE'     => 15000,
    'J&T'     => 12000,
    'SiCepat' => 10000,
    'Ambil'   => 0,
];
if (!isset($shipping_table[$metode_kirim])) {
    $_SESSION['flash_error'] = 'Metode pengiriman tidak valid.';
    header('Location: ' . BASE_URL . 'pelanggan/checkout.php');
    exit;
}
$ongkir = $shipping_table[$metode_kirim];

// ── Refresh cart dari DB & validasi stok ───────────────────
$subtotal     = 0;
$items_valid  = [];
foreach ($cart as $key => $item) {
    $stmt = $conn->prepare("SELECT id_produk, nama_produk, stock_by_size, price_by_size, status
                             FROM produk WHERE id_produk = ?");
    $stmt->bind_param('i', $item['id_produk']);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$row || $row['status'] !== 'aktif') {
        $_SESSION['flash_error'] = 'Produk "' . htmlspecialchars($item['nama']) . '" tidak tersedia lagi.';
        unset($_SESSION['cart'][$key]);
        header('Location: ' . BASE_URL . 'pelanggan/keranjang.php');
        exit;
    }
    $stock_by_size = json_decode($row['stock_by_size'], true) ?: [];
    $price_by_size = json_decode($row['price_by_size'], true) ?: [];
    $stok_real     = (int) ($stock_by_size[$item['ukuran']] ?? 0);
    $harga_real    = (float) ($price_by_size[$item['ukuran']] ?? 0);

    if ($item['jumlah'] > $stok_real) {
        $_SESSION['flash_error'] = 'Stok "' . htmlspecialchars($row['nama_produk']) .
                                   '" ukuran ' . htmlspecialchars($item['ukuran']) .
                                   ' tersisa ' . $stok_real . ' pcs.';
        $_SESSION['cart'][$key]['jumlah'] = $stok_real;
        header('Location: ' . BASE_URL . 'pelanggan/keranjang.php');
        exit;
    }

    $items_valid[$key] = [
        'id_produk'   => (int) $row['id_produk'],
        'nama_produk' => $row['nama_produk'],
        'ukuran'      => $item['ukuran'],
        'harga'       => $harga_real,
        'jumlah'      => (int) $item['jumlah'],
        'subtotal'    => $harga_real * (int) $item['jumlah'],
        // Untuk update stok
        'stock_by_size' => $stock_by_size,
    ];
    $subtotal += $items_valid[$key]['subtotal'];
}

$total_harga = $subtotal + $ongkir;

// ── Gabung jadi alamat_kirim lengkap (1 kolom TEXT) ────────
$alamat_kirim = $penerima_nama . "\n" . $penerima_hp . "\n" . $penerima_alamat;
if ($catatan) {
    $alamat_kirim .= "\n\nCatatan: " . $catatan;
}

// ── Generate no_pesanan unik (TS-YYYYMMDD-XXXX) ────────────
function generate_no_pesanan(mysqli $conn): string {
    for ($i = 0; $i < 10; $i++) {
        $no = 'TS-' . date('Ymd') . '-' . str_pad((string) random_int(0, 9999), 4, '0', STR_PAD_LEFT);
        $stmt = $conn->prepare("SELECT id_pesanan FROM pesanan WHERE no_pesanan = ?");
        $stmt->bind_param('s', $no);
        $stmt->execute();
        $exists = $stmt->get_result()->num_rows > 0;
        $stmt->close();
        if (!$exists) return $no;
    }
    return 'TS-' . date('YmdHis') . '-' . random_int(1000, 9999);
}
$no_pesanan = generate_no_pesanan($conn);

// ── Mulai transaction ─────────────────────────────────────
$conn->begin_transaction();
try {
    // Insert pesanan
    $stmt = $conn->prepare(
        "INSERT INTO pesanan (user_id, no_pesanan, total_harga, ongkir, alamat_kirim,
                              metode_pengiriman, status)
         VALUES (?, ?, ?, ?, ?, ?, 'Menunggu')"
    );
    $stmt->bind_param('isddss', $user_id, $no_pesanan, $total_harga, $ongkir,
                                $alamat_kirim, $metode_kirim);
    if (!$stmt->execute()) throw new Exception('Gagal simpan pesanan: ' . $stmt->error);
    $id_pesanan = $stmt->insert_id;
    $stmt->close();

    // Insert detail + update stok per item
    $stmt_detail = $conn->prepare(
        "INSERT INTO detail_pesanan (id_pesanan, id_produk, nama_produk, harga_satuan, ukuran, jumlah, subtotal)
         VALUES (?, ?, ?, ?, ?, ?, ?)"
    );
    $stmt_stok = $conn->prepare("UPDATE produk SET stock_by_size = ? WHERE id_produk = ?");

    foreach ($items_valid as $it) {
        // Insert detail
        $stmt_detail->bind_param('iisdsid',
            $id_pesanan, $it['id_produk'], $it['nama_produk'], $it['harga'],
            $it['ukuran'], $it['jumlah'], $it['subtotal']);
        if (!$stmt_detail->execute()) throw new Exception('Gagal simpan detail: ' . $stmt_detail->error);

        // Update stok
        $new_stock = $it['stock_by_size'];
        $new_stock[$it['ukuran']] = ($new_stock[$it['ukuran']] ?? 0) - $it['jumlah'];
        $new_stock_json = json_encode($new_stock);

        $stmt_stok->bind_param('si', $new_stock_json, $it['id_produk']);
        if (!$stmt_stok->execute()) throw new Exception('Gagal update stok: ' . $stmt_stok->error);
    }
    $stmt_detail->close();
    $stmt_stok->close();

    $conn->commit();
} catch (Exception $e) {
    $conn->rollback();
    error_log('[CHECKOUT] ' . $e->getMessage());
    $_SESSION['flash_error'] = 'Terjadi kesalahan saat menyimpan pesanan. Coba lagi.';
    header('Location: ' . BASE_URL . 'pelanggan/checkout.php');
    exit;
}

// ── Sukses: kosongkan cart, set flash, redirect ─────────────
$_SESSION['cart'] = [];
$_SESSION['flash_success'] = 'Pesanan ' . $no_pesanan . ' berhasil dibuat! Silakan lanjutkan ke pembayaran.';
$_SESSION['last_order_id'] = $id_pesanan;

// Hari 10 akan punya pembayaran.php — sementara fallback ke dashboard kalau belum ada
$pembayaran_file = __DIR__ . '/pembayaran.php';
if (file_exists($pembayaran_file)) {
    header('Location: ' . BASE_URL . 'pelanggan/pembayaran.php?id=' . $id_pesanan);
} else {
    header('Location: ' . BASE_URL . 'pelanggan/dashboard.php?tab=pesanan');
}
exit;
