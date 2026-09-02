<?php
require_once 'config/database.php';
require_once 'config/helpers.php';

if (is_logged_in()) {
    redirect(current_user()['role'] === 'admin' ? BASE_URL . '/admin/index.php' : BASE_URL . '/index.php');
}

$token = trim($_GET['token'] ?? '');
$invalid_token = false;
$not_verified = false;
$reset_data = null;
$user = null;

if (empty($token)) {
    $invalid_token = true;
} else {
    $stmt = $pdo->prepare('SELECT * FROM password_resets WHERE token = ? AND expires_at > NOW() LIMIT 1');
    $stmt->execute([$token]);
    $reset_data = $stmt->fetch();

    if (!$reset_data) {
        $invalid_token = true;
    } elseif ((int)$reset_data['is_verified'] !== 1) {
        // Must verify OTP first
        set_flash('warning', 'Harap lakukan verifikasi kode OTP terlebih dahulu sebelum membuat password baru.');
        redirect(BASE_URL . '/verify-otp.php?token=' . urlencode($token));
    } else {
        $userStmt = $pdo->prepare('SELECT id, name, email, role FROM users WHERE email = ? AND role = ? LIMIT 1');
        $userStmt->execute([$reset_data['email'], $reset_data['role']]);
        $user = $userStmt->fetch();

        if (!$user) {
            $invalid_token = true;
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$invalid_token && $user) {
    verify_csrf_token($_POST['csrf_token'] ?? null);

    $password = $_POST['password'] ?? '';
    $confirm = $_POST['confirm_password'] ?? '';

    if (empty($password) || empty($confirm)) {
        set_flash('danger', 'Password baru dan konfirmasi password wajib diisi.');
        redirect(BASE_URL . '/reset-password.php?token=' . urlencode($token));
    }

    if ($password !== $confirm) {
        set_flash('danger', 'Konfirmasi password tidak cocok.');
        redirect(BASE_URL . '/reset-password.php?token=' . urlencode($token));
    }

    if (strlen($password) < 6) {
        set_flash('danger', 'Password minimal 6 karakter.');
        redirect(BASE_URL . '/reset-password.php?token=' . urlencode($token));
    }

    try {
        $new_hash = password_hash($password, PASSWORD_DEFAULT);
        $updateStmt = $pdo->prepare('UPDATE users SET password = ? WHERE id = ?');
        $updateStmt->execute([$new_hash, $user['id']]);

        // Delete used reset tokens for this email & role
        $delStmt = $pdo->prepare('DELETE FROM password_resets WHERE email = ? AND role = ?');
        $delStmt->execute([$user['email'], $user['role']]);

        unset($_SESSION['reset_otp_token']);

        // Send confirmation email
        @send_password_changed_email($user['email'], $user['name'], $user['role']);

        set_flash('success', 'Selamat! Password Anda berhasil diperbarui. Silakan masuk menggunakan password baru Anda.');
        redirect(BASE_URL . '/login.php');
    } catch (PDOException $e) {
        set_flash('danger', 'Gagal memperbarui password. Silakan coba lagi.');
        redirect(BASE_URL . '/reset-password.php?token=' . urlencode($token));
    }
}
?>
<!doctype html>
<html lang="id">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Reset Password - Distributor Pelaminan Family</title>
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
    .account-badge-box {
      background: #fdfaf6;
      border: 1px solid rgba(216, 133, 78, 0.25);
      border-radius: 12px;
      padding: 12px 14px;
      margin-bottom: 20px;
      font-size: 13px;
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
      <h2 style="color:var(--espresso);margin:0 0 4px;font-size:21px;">Buat Password Baru</h2>
      <p style="margin:0;font-size:13px;color:#777;">Masukkan password baru yang aman untuk akun Anda</p>
    </div>

    <?= flash() ?>

    <?php if ($invalid_token): ?>
      <div style="text-align:center;padding:10px 0;">
        <div style="font-size:44px;margin-bottom:10px;">⚠️</div>
        <h4 style="color:#b91c1c;margin:0 0 8px;font-size:16px;">Sesi Reset Tidak Valid atau Kadaluarsa</h4>
        <p style="font-size:13px;color:#666;margin:0 0 20px;line-height:1.5;">
          Sesi reset password yang Anda gunakan telah kadaluarsa atau tidak ditemukan dalam sistem. Silakan ajukan ulang permintaan reset.
        </p>
        <a href="<?= BASE_URL ?>/forgot-password.php" class="btn btn-primary btn-block" style="padding:12px;font-size:14px;font-weight:700;border-radius:12px;display:block;text-decoration:none;">
          ← Ajukan Ulang Reset Password
        </a>
      </div>
    <?php else: ?>
      <div class="account-badge-box">
        <div style="font-weight:700;color:var(--espresso);"><?= e($user['name']) ?></div>
        <div style="color:#666;font-size:12px;"><?= e($user['email']) ?> &bull; <span style="text-transform:capitalize;font-weight:700;color:var(--terracotta-dark);"><?= e($user['role']) ?></span></div>
      </div>

      <form method="post">
        <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">

        <div class="form-group" style="margin-bottom:16px;">
          <label style="font-weight:700;font-size:12.5px;color:var(--espresso);margin-bottom:6px;display:block;">Password Baru</label>
          <div style="position:relative;">
            <input class="input" type="password" id="new_password" name="password" required minlength="6" placeholder="Minimal 6 karakter" style="padding-right:42px;border-radius:12px;">
            <button type="button" class="btn-toggle-pwd" data-target="new_password" style="position:absolute;right:10px;top:50%;transform:translateY(-50%);background:none;border:none;cursor:pointer;font-size:16px;color:#777;padding:4px;" title="Tampilkan/Sembunyikan Password" aria-label="Tampilkan Password">
              👁️
            </button>
          </div>
        </div>

        <div class="form-group" style="margin-bottom:22px;">
          <label style="font-weight:700;font-size:12.5px;color:var(--espresso);margin-bottom:6px;display:block;">Konfirmasi Password Baru</label>
          <div style="position:relative;">
            <input class="input" type="password" id="confirm_new_password" name="confirm_password" required minlength="6" placeholder="Ulangi password baru" style="padding-right:42px;border-radius:12px;">
            <button type="button" class="btn-toggle-pwd" data-target="confirm_new_password" style="position:absolute;right:10px;top:50%;transform:translateY(-50%);background:none;border:none;cursor:pointer;font-size:16px;color:#777;padding:4px;" title="Tampilkan/Sembunyikan Password" aria-label="Tampilkan Password">
              👁️
            </button>
          </div>
        </div>

        <button class="btn btn-primary btn-block" type="submit" style="padding:12px;font-size:15px;font-weight:700;border-radius:12px;box-shadow:0 6px 18px rgba(216,133,78,0.28);">
          💾 Simpan Password Baru
        </button>
      </form>
    <?php endif; ?>

    <p style="text-align:center;font-size:13px;margin-top:20px;margin-bottom:0;color:#666;">
      Batal? <a style="color:var(--terracotta-dark);font-weight:800;" href="<?= BASE_URL ?>/login.php">Kembali ke Halaman Login</a>
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
