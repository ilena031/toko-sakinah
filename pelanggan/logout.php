<?php
/**
 * LOGOUT.PHP — Logout handler
 */

session_start();
session_unset();
session_destroy();

// Redirect ke beranda
$base = 'http://localhost:8888/toko-sakinah/';
header('Location: ' . $base);
exit;
