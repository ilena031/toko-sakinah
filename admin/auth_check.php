<?php
/**
 * AUTH_CHECK.PHP — Admin role guard
 * Include di awal setiap halaman admin.
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id']) || ($_SESSION['user_role'] ?? '') !== 'admin') {
    $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'] ?? '';
    $base = defined('BASE_URL') ? BASE_URL : '/toko-sakinah/';
    header('Location: ' . $base . 'pelanggan/login.php');
    exit;
}
