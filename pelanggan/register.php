<?php
/**
 * REGISTER.PHP — Halaman Registrasi Pelanggan
 */

require_once __DIR__ . '/../config/config.php';

// Sudah login? redirect
if (isset($_SESSION['user_id'])) {
    header('Location: ' . BASE_URL . 'pelanggan/dashboard.php');
    exit;
}

$error  = '';
$nama   = '';
$email  = '';
$no_hp  = '';

// ── Handle POST ──────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama     = trim($_POST['nama'] ?? '');
    $email    = trim($_POST['email'] ?? '');
    $no_hp    = trim($_POST['no_hp'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm  = $_POST['confirm_password'] ?? '';

    // Validasi
    if (!$nama || !$email || !$no_hp || !$password || !$confirm) {
        $error = 'Semua field harus diisi.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Format email tidak valid.';
    } elseif (strlen($password) < 6) {
        $error = 'Password minimal 6 karakter.';
    } elseif ($password !== $confirm) {
        $error = 'Konfirmasi password tidak cocok.';
    } else {
        // Cek email unik
        $check = $conn->prepare("SELECT id FROM users WHERE email = ?");
        $check->bind_param('s', $email);
        $check->execute();
        if ($check->get_result()->num_rows > 0) {
            $error = 'Email sudah terdaftar. Silakan login.';
        } else {
            // Insert user
            $hashed = password_hash($password, PASSWORD_DEFAULT);
            $role   = 'pelanggan';
            $stmt   = $conn->prepare("INSERT INTO users (nama, email, password, no_hp, role) VALUES (?, ?, ?, ?, ?)");
            $stmt->bind_param('sssss', $nama, $email, $hashed, $no_hp, $role);

            if ($stmt->execute()) {
                // Auto-login
                $user_id = $stmt->insert_id;
                $_SESSION['user_id']    = $user_id;
                $_SESSION['user_nama']  = $nama;
                $_SESSION['user_email'] = $email;
                $_SESSION['user_role']  = 'pelanggan';

                header('Location: ' . BASE_URL . 'pelanggan/dashboard.php');
                exit;
            } else {
                $error = 'Terjadi kesalahan. Coba lagi.';
            }
            $stmt->close();
        }
        $check->close();
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Daftar Akun — Toko Sakinah</title>
  <link rel="icon" type="image/png" href="<?= BASE_URL ?>data/logo_toko.png">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
  <link rel="stylesheet" href="<?= BASE_URL ?>assets/css/style.css">
  <link rel="stylesheet" href="<?= BASE_URL ?>assets/css/auth.css">
</head>
<body>

<div class="auth-page">
  <div class="auth-back">
    <a href="<?= BASE_URL ?>"><i class="bi bi-arrow-left"></i> Kembali ke Beranda</a>
  </div>

  <div class="auth-card" style="max-width:460px;">
    <div class="auth-logo">
      <img src="<?= BASE_URL ?>data/logo_toko.png" alt="Logo Toko Sakinah">
      <h2>Buat Akun Baru</h2>
      <p>Daftar untuk mulai belanja di Toko Sakinah</p>
    </div>

    <?php if ($error): ?>
      <div class="auth-alert alert-danger">
        <i class="bi bi-exclamation-circle"></i> <?= htmlspecialchars($error) ?>
      </div>
    <?php endif; ?>

    <form action="" method="POST" id="register-form">
      <div class="auth-field">
        <label for="nama">Nama Lengkap</label>
        <div class="input-wrapper">
          <i class="bi bi-person"></i>
          <input type="text" id="nama" name="nama" placeholder="Masukkan nama lengkap"
                 value="<?= htmlspecialchars($nama) ?>" required autofocus>
        </div>
      </div>

      <div class="auth-field">
        <label for="email">Email</label>
        <div class="input-wrapper">
          <i class="bi bi-envelope"></i>
          <input type="email" id="email" name="email" placeholder="contoh@email.com"
                 value="<?= htmlspecialchars($email) ?>" required>
        </div>
      </div>

      <div class="auth-field">
        <label for="no_hp">Nomor HP</label>
        <div class="input-wrapper">
          <i class="bi bi-phone"></i>
          <input type="text" id="no_hp" name="no_hp" placeholder="08xxxxxxxxxx"
                 value="<?= htmlspecialchars($no_hp) ?>" required>
        </div>
      </div>

      <div class="auth-field">
        <label for="password">Password</label>
        <div class="input-wrapper">
          <i class="bi bi-lock"></i>
          <input type="password" id="password" name="password" placeholder="Minimal 6 karakter" required minlength="6">
          <i class="bi bi-eye toggle-password" onclick="togglePass(this, 'password')"></i>
        </div>
      </div>

      <div class="auth-field">
        <label for="confirm_password">Konfirmasi Password</label>
        <div class="input-wrapper">
          <i class="bi bi-lock-fill"></i>
          <input type="password" id="confirm_password" name="confirm_password" placeholder="Ulangi password" required>
          <i class="bi bi-eye toggle-password" onclick="togglePass(this, 'confirm_password')"></i>
        </div>
      </div>

      <button type="submit" class="auth-submit">
        <i class="bi bi-person-plus me-2"></i>Daftar Sekarang
      </button>
    </form>

    <div class="auth-link">
      Sudah punya akun? <a href="<?= BASE_URL ?>pelanggan/login.php">Masuk di sini</a>
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
