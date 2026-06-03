<?php
/**
 * KONEKSI.PHP — VERSI HOSTINGER PRODUCTION
 * Rename file ini jadi koneksi.php pas upload ke hosting
 * (atau overwrite file koneksi.php yang ada)
 */

define('DB_HOST', 'localhost');
define('DB_USER', 'u451357797_admin');
define('DB_PASS', 'ISI_PASSWORD_DB_KAMU_DISINI');  // ← GANTI INI dengan password yg kamu bikin di hPanel
define('DB_NAME', 'u451357797_tokosakinah');

define('BASE_URL', 'https://tokosakinah.com/');
define('UPLOAD_PATH', __DIR__ . '/uploads/');

$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
$conn->set_charset('utf8mb4');

if ($conn->connect_error) {
    die("Koneksi gagal: " . $conn->connect_error);
}
