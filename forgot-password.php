<?php
require_once 'config/database.php';
require_once 'config/helpers.php';

if (is_logged_in()) {
    redirect(current_user()['role'] === 'admin' ? BASE_URL . '/admin/index.php' : BASE_URL . '/index.php');
}

// Auto-migration: create table and columns if not exists
try {
    $pdo->exec("CREATE TABLE IF NOT EXISTS password_resets (
        id INT AUTO_INCREMENT PRIMARY KEY,
        email VARCHAR(120) NOT NULL,
        role ENUM('admin','customer') NOT NULL DEFAULT 'customer',
        token VARCHAR(100) NOT NULL UNIQUE,
        otp_code VARCHAR(10) NOT NULL,
        otp_expires_at DATETIME NOT NULL,
        is_verified TINYINT(1) NOT NULL DEFAULT 0,
        attempts INT NOT NULL DEFAULT 0,
        resend_cooldown_until DATETIME NULL,
        expires_at DATETIME NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_reset_email (email),
        INDEX idx_reset_token (token),
        INDEX idx_reset_otp (otp_code)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    // Check & add columns if old table existed without OTP columns
    $cols = $pdo->query("SHOW COLUMNS FROM password_resets")->fetchAll(PDO::FETCH_COLUMN);
    if (!in_array('otp_code', $cols)) {
        $pdo->exec("ALTER TABLE password_resets ADD COLUMN otp_code VARCHAR(10) NOT NULL AFTER token");
    }
    if (!in_array('otp_expires_at', $cols)) {
        $pdo->exec("ALTER TABLE password_resets ADD COLUMN otp_expires_at DATETIME NOT NULL AFTER otp_code");
    }
    if (!in_array('is_verified', $cols)) {
        $pdo->exec("ALTER TABLE password_resets ADD COLUMN is_verified TINYINT(1) NOT NULL DEFAULT 0 AFTER otp_expires_at");
    }
    if (!in_array('attempts', $cols)) {
        $pdo->exec("ALTER TABLE password_resets ADD COLUMN attempts INT NOT NULL DEFAULT 0 AFTER is_verified");
    }
    if (!in_array('resend_cooldown_until', $cols)) {
        $pdo->exec("ALTER TABLE password_resets ADD COLUMN resend_cooldown_until DATETIME NULL AFTER attempts");
    }
} catch (Throwable $e) {
    // Ignore if table/columns already exist
}


if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf_token($_POST['csrf_token'] ?? null);

    $email = trim($_POST['email'] ?? '');
    $role = $_POST['role'] ?? 'customer';

    if (empty($email)) {
        set_flash('danger', 'Alamat email wajib diisi.');
        redirect(BASE_URL . '/forgot-password.php');
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        set_flash('danger', 'Format alamat email tidak valid.');
        redirect(BASE_URL . '/forgot-password.php');
    }

    // Verify user exists for the selected role
    $stmt = $pdo->prepare('SELECT id, name, email, role FROM users WHERE email = ? AND role = ? LIMIT 1');
    $stmt->execute([$email, $role]);
    $user = $stmt->fetch();

    if (!$user) {
        $role_label = ($role === 'admin') ? 'Admin' : 'Customer';
        set_flash('danger', 'Email tidak terdaftar untuk akun ' . $role_label . '. Periksa kembali email dan role pilihan Anda.');
        redirect(BASE_URL . '/forgot-password.php');
    }

    // Generate secure 6-digit numeric OTP & session token
    $otp_code = sprintf('%06d', random_int(100000, 999999));
    $token = bin2hex(random_bytes(32));
    $otp_expires_at = date('Y-m-d H:i:s', strtotime('+10 minutes'));
    $expires_at = date('Y-m-d H:i:s', strtotime('+1 hour'));
    $cooldown_until = date('Y-m-d H:i:s', strtotime('+60 seconds'));

    // Remove old reset requests for this email & role
    $delStmt = $pdo->prepare('DELETE FROM password_resets WHERE email = ? AND role = ?');
    $delStmt->execute([$email, $role]);

    // Insert new OTP record
    $insStmt = $pdo->prepare('INSERT INTO password_resets (email, role, token, otp_code, otp_expires_at, is_verified, attempts, resend_cooldown_until, expires_at) VALUES (?, ?, ?, ?, ?, 0, 0, ?, ?)');
    $insStmt->execute([$email, $role, $token, $otp_code, $otp_expires_at, $cooldown_until, $expires_at]);

    // Send OTP email
    $mailResult = send_otp_email($user['email'], $user['name'], $otp_code, $user['role'], 10);

    // Save active token in session for convenience
    $_SESSION['reset_otp_token'] = $token;

    if ($mailResult['success']) {
        set_flash('success', 'Kode OTP telah berhasil dikirimkan ke email <strong>' . e($user['email']) . '</strong>. Silakan periksa kotak masuk atau folder spam Anda.');
    } else {
        // If mail server is unreachable, provide graceful notice
        set_flash('warning', 'Permintaan reset dibuat. Jika email tidak masuk, periksa koneksi internet Anda atau hubungi admin.');
    }

    redirect(BASE_URL . '/verify-otp.php?token=' . urlencode($token));
}
?>
<!doctype html>
<html lang="id">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Lupa Password - Distributor Pelaminan Family</title>
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
      <h2 style="color:var(--espresso);margin:0 0 4px;font-size:21px;">Lupa Password?</h2>
      <p style="margin:0;font-size:13px;color:#777;">Masukkan email terdaftar Anda untuk menerima kode OTP</p>
    </div>

    <?= flash() ?>

    <form method="post">
      <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
      
      <div class="role-pills-wrap">
        <label><input type="radio" name="role" value="customer" id="role_customer" <?= (($_POST['role'] ?? 'customer') === 'customer') ? 'checked' : '' ?>><span>Customer</span></label>
        <label><input type="radio" name="role" value="admin" id="role_admin" <?= (($_POST['role'] ?? '') === 'admin') ? 'checked' : '' ?>><span>Admin</span></label>
      </div>

      <div class="form-group" style="margin-bottom:20px;">
        <label style="font-weight:700;font-size:12.5px;color:var(--espresso);margin-bottom:6px;display:block;">Email Akun Anda</label>
        <input class="input" type="email" id="reset_email" name="email" required placeholder="email@example.com" value="<?= e($_POST['email'] ?? '') ?>" style="border-radius:12px;">
      </div>

      <button class="btn btn-primary btn-block" type="submit" style="padding:13px;font-size:15px;font-weight:700;border-radius:12px;box-shadow:0 6px 18px rgba(216,133,78,0.28);display:flex;align-items:center;justify-content:center;gap:8px;">
        <span>Kirim Kode OTP ke Email</span> <span>➔</span>
      </button>
    </form>

    <p style="text-align:center;font-size:13px;margin-top:22px;margin-bottom:0;color:#666;">
      Sudah ingat password? <a style="color:var(--terracotta-dark);font-weight:800;" href="<?= BASE_URL ?>/login.php">Kembali ke Halaman Login</a>
    </p>

  </div>
</div>

</body>
</html>
