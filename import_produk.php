<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require 'koneksi.php';

$json = file_get_contents(__DIR__ . '/data/products.json');
$products = json_decode($json, true);

// DEBUG: Cek isi tabel kategori
echo "<h3>Isi tabel kategori:</h3>";
$r = $conn->query("SELECT id_kategori, nama_kategori, slug FROM kategori");
if ($r) {
    while ($row = $r->fetch_assoc()) {
        echo "id={$row['id_kategori']} | nama={$row['nama_kategori']} | slug={$row['slug']}<br>";
    }
} else {
    echo "ERROR: " . $conn->error . "<br>";
}

echo "<h3>Log per produk:</h3>";
$inserted = 0;
foreach ($products as $p) {
    // Ambil id kategori
    $slug = $conn->real_escape_string($p['category']);
    $res = $conn->query("SELECT id_kategori FROM kategori WHERE slug = '$slug'");
    $id_kat = $res->fetch_assoc()['id_kategori'];

    // Hitung total stok
    $stok_total = array_sum($p['stockBySize'] ?? []);

    // Foto pertama
    $foto = $conn->real_escape_string($p['images'][0] ?? '');

    // JSON fields
    $price_by_size = $conn->real_escape_string(json_encode($p['priceBySize'] ?? []));
    $stock_by_size = $conn->real_escape_string(json_encode($p['stockBySize'] ?? []));
    $foto_all = $conn->real_escape_string(json_encode($p['images'] ?? []));
    $ukuran = $conn->real_escape_string(implode(',', $p['sizes'] ?? []));
    $genders = $conn->real_escape_string(implode(',', $p['genders'] ?? []));

    // Field lain
    $nama = $conn->real_escape_string($p['name']);
    $subcat = $conn->real_escape_string($p['subcategory'] ?? '');
    $desc = $conn->real_escape_string($p['description'] ?? '');
    $harga = (float) $p['price'];
    $badge = $conn->real_escape_string($p['badge'] ?? '');
    $rating = (float) ($p['rating'] ?? 0);
    $sold = (int) ($p['sold'] ?? 0);

    // Baju sekolah = try-on enabled
    $tryon = $p['category'] === 'baju-sekolah' ? 1 : 0;

    $sql = "INSERT INTO produk 
      (id_kategori, nama_produk, subcategory, deskripsi, harga, stok_total,
       ukuran, price_by_size, stock_by_size, genders, foto, foto_all,
       tryon_enabled, badge, rating, sold)
      VALUES
      ($id_kat, '$nama', '$subcat', '$desc', $harga, $stok_total,
       '$ukuran', '$price_by_size', '$stock_by_size', '$genders', '$foto', '$foto_all',
       $tryon, '$badge', $rating, $sold)";
}

echo "Berhasil import $inserted produk dari " . count($products) . " data";