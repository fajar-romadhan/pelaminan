<?php
require_once 'config/database.php';
require_once 'config/helpers.php';

if (is_logged_in()) {
    redirect(current_user()['role'] === 'admin' ? BASE_URL . '/admin/index.php' : BASE_URL . '/index.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf_token($_POST['csrf_token'] ?? null);

    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $role = $_POST['role'] ?? 'customer';

    if (empty($email) || empty($password)) {
        set_flash('danger', 'Email dan password wajib diisi.');
        redirect(BASE_URL . '/login.php');
    }

    $stmt = $pdo->prepare('SELECT * FROM users WHERE email = ? LIMIT 1');
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password'])) {
        if ($user['role'] !== $role) {
            set_flash('danger', 'Role pengguna tidak sesuai.');
            redirect(BASE_URL . '/login.php');
        }

        session_regenerate_id(true);
        $_SESSION['user'] = [
            'id' => (int)$user['id'],
            'name' => $user['name'],
            'email' => $user['email'],
            'role' => $user['role']
        ];

        set_flash('success', 'Selamat datang kembali, ' . $user['name'] . '!');
        redirect($role === 'admin' ? BASE_URL . '/admin/index.php' : BASE_URL . '/index.php');
    }

    set_flash('danger', 'Email, password, atau role tidak sesuai.');
    redirect(BASE_URL . '/login.php');
}
?>
<!doctype html>
<html lang="id">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Login - Distributor Pelaminan Family</title>
  <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css">
  <style>
    body {
      background: radial-gradient(circle at 50% 30%, #f9f3ec 0%, #ede2d5 100%);
      min-height: 100vh;
      display: flex;
      align-items: center;
      justify-content: center;
      padding: 24px 16px;
      margin: 0;
    }
    .auth-center-wrap {
      width: 100%;
      max-width: 440px;
      margin: 0 auto;
    }
    .auth-brand-header {
      text-align: center;
      margin-bottom: 22px;
    }
    .auth-brand-logo {
      width: 84px;
      height: 84px;
      border-radius: 50%;
      object-fit: contain;
      background: #000000;
      box-shadow: 0 8px 24px rgba(0, 0, 0, 0.3), 0 0 0 3px rgba(212, 175, 55, 0.4);
      margin: 0 auto 14px;
      display: block;
      border: 2px solid rgba(255, 255, 255, 0.6);
      transition: transform 0.3s ease;
    }
    .auth-brand-logo:hover {
      transform: scale(1.05);
    }
    .auth-brand-title {
      font-family: var(--font-heading);
      font-size: 24px;
      color: var(--espresso);
      margin: 0 0 4px;
    }
    .auth-brand-sub {
      font-size: 13.5px;
      color: #666;
      margin: 0;
    }
    .login-card-box {
      background: #ffffff;
      border-radius: 24px;
      padding: 34px 28px;
      box-shadow: 0 20px 50px rgba(54, 34, 23, 0.08);
      border: 1px solid rgba(216, 133, 78, 0.18);
    }
    .role-pills-wrap {
      display: grid !important;
      grid-template-columns: repeat(2, 1fr) !important;
      gap: 4px;
      border: 1.5px solid rgba(216, 133, 78, 0.22);
      border-radius: 99px;
      background: #fdfaf6;
      padding: 4px;
      margin-bottom: 22px;
    }
    .role-pills-wrap label {
      margin: 0;
      cursor: pointer;
      text-align: center;
    }
    .role-pills-wrap input {
      display: none;
    }
    .role-pills-wrap span {
      display: block;
      text-align: center;
      padding: 9px 2px;
      font-size: 13px;
      font-weight: 700;
      border-radius: 99px;
      color: var(--muted);
      transition: all 0.2s ease;
      white-space: nowrap;
    }
    .role-pills-wrap input:checked + span {
      background: linear-gradient(135deg, var(--terracotta-dark) 0%, var(--terracotta) 100%);
      color: #ffffff;
      box-shadow: 0 4px 12px rgba(184, 96, 40, 0.28);
    }
    .login-card-box input:-webkit-autofill,
    .login-card-box input:-webkit-autofill:hover, 
    .login-card-box input:-webkit-autofill:focus {
      -webkit-box-shadow: 0 0 0px 1000px #ffffff inset !important;
      -webkit-text-fill-color: var(--espresso) !important;
      transition: background-color 5000s ease-in-out 0s;
    }
  </style>
</head>
<body>

<div class="auth-center-wrap">
  <div class="auth-brand-header">
    <img src="<?= BASE_URL ?>/assets/img/logo.png" alt="Logo Distributor Pelaminan Family" class="auth-brand-logo">
    <h1 class="auth-brand-title">Distributor Pelaminan Family</h1>
    <p class="auth-brand-sub">Sistem Manajemen Dekorasi Pernikahan</p>
  </div>

  <div class="login-card-box">
    <div style="text-align:center;margin-bottom:20px;">
      <h2 style="color:var(--espresso);margin:0 0 4px;font-size:21px;">Selamat Datang</h2>
      <p style="margin:0;font-size:13px;color:#777;">Pilih akses dan masuk ke akun Anda</p>
    </div>

    <?= flash() ?>

    <form method="post">
      <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
      
      <div class="role-pills-wrap">
        <label><input type="radio" name="role" value="customer" id="role_customer" checked><span>Customer</span></label>
        <label><input type="radio" name="role" value="admin" id="role_admin"><span>Admin</span></label>
      </div>

      <div class="form-group" style="margin-bottom:16px;">
        <label style="font-weight:700;font-size:12.5px;color:var(--espresso);margin-bottom:6px;display:block;">Email</label>
        <input class="input" type="email" id="login_email" name="email" required placeholder="email@example.com" style="border-radius:12px;">
      </div>

      <div class="form-group" style="margin-bottom:20px;">
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:6px;">
          <label style="font-weight:700;font-size:12.5px;color:var(--espresso);margin:0;">Password</label>
          <a href="<?= BASE_URL ?>/forgot-password.php" style="font-size:12.5px;color:var(--terracotta-dark);font-weight:700;text-decoration:none;transition:color 0.2s;" onmouseover="this.style.textDecoration='underline'" onmouseout="this.style.textDecoration='none'">Lupa Password?</a>
        </div>
        <div style="position:relative;">
          <input class="input" type="password" id="login_password" name="password" required placeholder="••••••••" style="padding-right:42px;border-radius:12px;">
          <button type="button" class="btn-toggle-pwd" data-target="login_password" style="position:absolute;right:10px;top:50%;transform:translateY(-50%);background:none;border:none;cursor:pointer;font-size:16px;color:#777;padding:4px;" title="Tampilkan/Sembunyikan Password" aria-label="Tampilkan Password">
            👁️
          </button>
        </div>
      </div>

      <button class="btn btn-primary btn-block" type="submit" style="padding:12px;font-size:15px;font-weight:700;border-radius:12px;box-shadow:0 6px 18px rgba(216,133,78,0.28);">
        Masuk Sekarang
      </button>
    </form>

    <p style="text-align:center;font-size:13px;margin-top:20px;margin-bottom:0;color:#666;">
      Belum punya akun? <a style="color:var(--terracotta-dark);font-weight:800;" href="<?= BASE_URL ?>/register.php">Daftar Akun Baru</a>
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
