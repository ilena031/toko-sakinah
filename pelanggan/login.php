<?php
/**
 * LOGIN.PHP — Halaman Login Pelanggan
 */

require_once __DIR__ . '/../config/config.php';

// Sudah login? redirect ke dashboard
if (isset($_SESSION['user_id'])) {
    header('Location: ' . BASE_URL . 'pelanggan/dashboard.php');
    exit;
}

$error = '';
$email = '';

// ── Handle POST ──────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (!$email || !$password) {
        $error = 'Email dan password harus diisi.';
    } else {
        $stmt = $conn->prepare("SELECT id, nama, email, password, role FROM users WHERE email = ?");
        $stmt->bind_param('s', $email);
        $stmt->execute();
        $user = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if ($user && password_verify($password, $user['password'])) {
            // Set session
            $_SESSION['user_id']   = $user['id'];
            $_SESSION['user_nama'] = $user['nama'];
            $_SESSION['user_email'] = $user['email'];
            $_SESSION['user_role']  = $user['role'];

            // Redirect
            $redirect = $_SESSION['redirect_after_login'] ?? BASE_URL . 'pelanggan/dashboard.php';
            unset($_SESSION['redirect_after_login']);

            // Admin goes to admin panel
            if ($user['role'] === 'admin') {
                $redirect = BASE_URL . 'admin/';
            }

            header('Location: ' . $redirect);
            exit;
        } else {
            $error = 'Email atau password salah.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Login — Toko Sakinah</title>
  <link rel="icon" type="image/png" href="<?= BASE_URL ?>data/logo_toko.png">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
  <link rel="stylesheet" href="<?= BASE_URL ?>assets/css/style.css">
  <link rel="stylesheet" href="<?= BASE_URL ?>assets/css/auth.css">
</head>
<body>

<div class="auth-page">
  <!-- Back to Home -->
  <div class="auth-back">
    <a href="<?= BASE_URL ?>"><i class="bi bi-arrow-left"></i> Kembali ke Beranda</a>
  </div>

  <div class="auth-card">
    <!-- Logo -->
    <div class="auth-logo">
      <img src="<?= BASE_URL ?>data/logo_toko.png" alt="Logo Toko Sakinah">
      <h2>Masuk ke Akun</h2>
      <p>Selamat datang kembali di Toko Sakinah!</p>
    </div>

    <!-- Error Alert -->
    <?php if ($error): ?>
      <div class="auth-alert alert-danger">
        <i class="bi bi-exclamation-circle"></i> <?= htmlspecialchars($error) ?>
      </div>
    <?php endif; ?>

    <!-- Success Message (from register) -->
    <?php if (!empty($_GET['registered'])): ?>
      <div class="auth-alert alert-success">
        <i class="bi bi-check-circle"></i> Registrasi berhasil! Silakan login.
      </div>
    <?php endif; ?>

    <!-- Login Form -->
    <form action="" method="POST" id="login-form">
      <div class="auth-field">
        <label for="email">Email</label>
        <div class="input-wrapper">
          <i class="bi bi-envelope"></i>
          <input type="email" id="email" name="email" placeholder="Masukkan email Anda"
                 value="<?= htmlspecialchars($email) ?>" required autofocus>
        </div>
      </div>

      <div class="auth-field">
        <label for="password">Password</label>
        <div class="input-wrapper">
          <i class="bi bi-lock"></i>
          <input type="password" id="password" name="password" placeholder="Masukkan password" required>
          <i class="bi bi-eye toggle-password" onclick="togglePass(this, 'password')"></i>
        </div>
      </div>

      <button type="submit" class="auth-submit">
        <i class="bi bi-box-arrow-in-right me-2"></i>Masuk
      </button>
    </form>

    <!-- Register Link -->
    <div class="auth-link">
      Belum punya akun? <a href="<?= BASE_URL ?>pelanggan/register.php">Daftar sekarang</a>
    </div>
  </div>
</div>

<script>
function togglePass(icon, inputId) {
  const input = document.getElementById(inputId);
  if (input.type === 'password') {
    input.type = 'text';
    icon.classList.replace('bi-eye', 'bi-eye-slash');
  } else {
    input.type = 'password';
    icon.classList.replace('bi-eye-slash', 'bi-eye');
  }
}
</script>
</body>
</html>
