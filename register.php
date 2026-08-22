<?php
require_once 'config/database.php';
require_once 'config/helpers.php';

if (is_logged_in()) {
    redirect(current_user()['role'] === 'admin' ? BASE_URL . '/admin/index.php' : BASE_URL . '/index.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf_token($_POST['csrf_token'] ?? null);

    $name = trim($_POST['name'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm = $_POST['confirm_password'] ?? '';

    if (empty($name) || empty($email) || empty($password)) {
        set_flash('danger', 'Nama, email, dan password wajib diisi.');
        redirect(BASE_URL . '/register.php');
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        set_flash('danger', 'Format email tidak valid.');
        redirect(BASE_URL . '/register.php');
    }

    if ($password !== $confirm) {
        set_flash('danger', 'Konfirmasi password tidak cocok.');
        redirect(BASE_URL . '/register.php');
    }

    if (strlen($password) < 6) {
        set_flash('danger', 'Password minimal 6 karakter.');
        redirect(BASE_URL . '/register.php');
    }

    // Check duplicate email
    $checkStmt = $pdo->prepare('SELECT id FROM users WHERE email = ? LIMIT 1');
    $checkStmt->execute([$email]);
    if ($checkStmt->fetch()) {
        set_flash('danger', 'Email sudah terdaftar. Silakan gunakan email lain atau login.');
        redirect(BASE_URL . '/register.php');
    }

    try {
        $stmt = $pdo->prepare('INSERT INTO users(name, phone, email, address, password, role) VALUES(?,?,?,?,?,?)');
        $stmt->execute([
            $name,
            $phone,
            $email,
            $address,
            password_hash($password, PASSWORD_DEFAULT),
            'customer'
        ]);

        set_flash('success', 'Akun berhasil dibuat. Silakan login untuk melanjutkan.');
        redirect(BASE_URL . '/login.php');
    } catch (PDOException $e) {
        set_flash('danger', 'Terjadi kesalahan saat pendaftaran.');
        redirect(BASE_URL . '/register.php');
    }
}
?>
<!doctype html>
<html lang="id">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Registrasi Customer - Distributor Pelaminan Family</title>
  <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css">
</head>
<body>
<div class="auth-box" style="min-height:100vh">
  <div class="auth-card" style="max-width:560px">
    <div style="text-align:center">
      <img src="<?= BASE_URL ?>/assets/img/logo.png" alt="Logo Distributor Pelaminan Family" class="logo" style="width:72px;height:72px;margin:0 auto 14px;display:block;">
      <h2 style="color:var(--espresso);margin:0">Buat Akun Baru</h2>
      <p class="muted">Daftar sebagai customer untuk mulai pesan dekorasi</p>
    </div>
    <?= flash() ?>
    <form method="post">
      <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
      <div class="form-group">
        <label>Nama Lengkap</label>
        <input class="input" name="name" required placeholder="Nama Anda">
      </div>
      <div class="form-row">
        <div class="form-group">
          <label>Nomor Telepon</label>
          <input class="input" name="phone" required placeholder="08xxxxxxxxxx">
        </div>
        <div class="form-group">
          <label>Email</label>
          <input class="input" type="email" name="email" required placeholder="email@example.com">
        </div>
      </div>
      <div class="form-group">
        <label>Alamat</label>
        <input class="input" name="address" required placeholder="Alamat lengkap Anda">
      </div>
      <div class="form-row">
        <div class="form-group">
          <label>Password</label>
          <div style="position:relative;">
            <input class="input" type="password" id="reg_password" name="password" required minlength="6" placeholder="••••••••" style="padding-right:42px;">
            <button type="button" class="btn-toggle-pwd" data-target="reg_password" style="position:absolute;right:10px;top:50%;transform:translateY(-50%);background:none;border:none;cursor:pointer;font-size:16px;color:#777;padding:4px;" title="Tampilkan/Sembunyikan Password" aria-label="Tampilkan Password">
              👁️
            </button>
          </div>
        </div>
        <div class="form-group">
          <label>Konfirmasi Password</label>
          <div style="position:relative;">
            <input class="input" type="password" id="reg_confirm_password" name="confirm_password" required minlength="6" placeholder="••••••••" style="padding-right:42px;">
            <button type="button" class="btn-toggle-pwd" data-target="reg_confirm_password" style="position:absolute;right:10px;top:50%;transform:translateY(-50%);background:none;border:none;cursor:pointer;font-size:16px;color:#777;padding:4px;" title="Tampilkan/Sembunyikan Password" aria-label="Tampilkan Password">
              👁️
            </button>
          </div>
        </div>
      </div>
      <button class="btn btn-primary btn-block" type="submit">Daftar Sekarang</button>
    </form>
    <p style="text-align:center;font-size:14px;margin-top:16px;">
      Sudah punya akun? <a href="<?= BASE_URL ?>/login.php" style="color:var(--terracotta-dark);font-weight:900">Masuk di sini</a>
    </p>
  </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
  document.querySelectorAll('.btn-toggle-pwd').forEach(button => {
    button.addEventListener('click', function () {
      const targetId = this.getAttribute('data-target');
      const input = document.getElementById(targetId);
      if (input) {
        const isPassword = input.type === 'password';
        input.type = isPassword ? 'text' : 'password';
        this.innerHTML = isPassword ? '🙈' : '👁️';
        this.setAttribute('aria-label', isPassword ? 'Sembunyikan Password' : 'Tampilkan Password');
      }
    });
  });
});
</script>
</body>
</html>
