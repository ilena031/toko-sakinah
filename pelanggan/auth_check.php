<?php
/**
 * AUTH_CHECK.PHP — Helper: Redirect ke login jika belum login
 * Include di awal halaman yang butuh autentikasi:
 *   require __DIR__ . '/auth_check.php';
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id'])) {
    // Simpan halaman tujuan untuk redirect setelah login
    $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'];
    header('Location: ' . (defined('BASE_URL') ? BASE_URL : '/toko-sakinah/') . 'pelanggan/login.php');
    exit;
}
