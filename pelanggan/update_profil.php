<?php
/**
 * UPDATE_PROFIL.PHP — Handle update profil & ganti password
 * POST action: 'update_profil' atau 'change_password'
 */

require __DIR__ . '/auth_check.php';
require_once __DIR__ . '/../config/config.php';

$user_id = $_SESSION['user_id'];
$action  = $_POST['action'] ?? '';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . BASE_URL . 'pelanggan/dashboard.php');
    exit;
}

// ── Update Profil ────────────────────────────────────────────
if ($action === 'update_profil') {
    $nama   = trim($_POST['nama'] ?? '');
    $no_hp  = trim($_POST['no_hp'] ?? '');
    $alamat = trim($_POST['alamat'] ?? '');

    if (!$nama || !$no_hp) {
        $_SESSION['flash_error'] = 'Nama dan nomor HP harus diisi.';
    } else {
        $stmt = $conn->prepare("UPDATE users SET nama = ?, no_hp = ?, alamat = ? WHERE id = ?");
        $stmt->bind_param('sssi', $nama, $no_hp, $alamat, $user_id);

        if ($stmt->execute()) {
            $_SESSION['user_nama'] = $nama;
            $_SESSION['flash_success'] = 'Profil berhasil diperbarui!';
        } else {
            $_SESSION['flash_error'] = 'Gagal memperbarui profil. Coba lagi.';
        }
        $stmt->close();
    }

    header('Location: ' . BASE_URL . 'pelanggan/dashboard.php?tab=profil');
    exit;
}

// ── Ganti Password ───────────────────────────────────────────
if ($action === 'change_password') {
    $old_password = $_POST['old_password'] ?? '';
    $new_password = $_POST['new_password'] ?? '';
    $confirm      = $_POST['confirm_password'] ?? '';

    if (!$old_password || !$new_password || !$confirm) {
        $_SESSION['flash_error'] = 'Semua field password harus diisi.';
    } elseif (strlen($new_password) < 6) {
        $_SESSION['flash_error'] = 'Password baru minimal 6 karakter.';
    } elseif ($new_password !== $confirm) {
        $_SESSION['flash_error'] = 'Konfirmasi password tidak cocok.';
    } else {
        // Verify old password
        $stmt = $conn->prepare("SELECT password FROM users WHERE id = ?");
        $stmt->bind_param('i', $user_id);
        $stmt->execute();
        $user = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (!$user || !password_verify($old_password, $user['password'])) {
            $_SESSION['flash_error'] = 'Password lama salah.';
        } else {
            $hashed = password_hash($new_password, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
            $stmt->bind_param('si', $hashed, $user_id);

            if ($stmt->execute()) {
                $_SESSION['flash_success'] = 'Password berhasil diubah!';
            } else {
                $_SESSION['flash_error'] = 'Gagal mengubah password. Coba lagi.';
            }
            $stmt->close();
        }
    }

    header('Location: ' . BASE_URL . 'pelanggan/dashboard.php?tab=profil');
    exit;
}

// Unknown action
header('Location: ' . BASE_URL . 'pelanggan/dashboard.php');
exit;
