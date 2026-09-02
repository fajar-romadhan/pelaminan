<?php
require_once 'config/database.php';
require_once 'config/helpers.php';

if (is_logged_in()) {
    redirect(current_user()['role'] === 'admin' ? BASE_URL . '/admin/index.php' : BASE_URL . '/index.php');
}

$token = trim($_GET['token'] ?? $_POST['token'] ?? $_SESSION['reset_otp_token'] ?? '');

if (empty($token)) {
    set_flash('danger', 'Sesi verifikasi tidak ditemukan. Silakan ajukan ulang permintaan reset password.');
    redirect(BASE_URL . '/forgot-password.php');
}

// Fetch active reset request
$stmt = $pdo->prepare('SELECT * FROM password_resets WHERE token = ? AND expires_at > NOW() LIMIT 1');
$stmt->execute([$token]);
$reset_data = $stmt->fetch();

if (!$reset_data) {
    set_flash('danger', 'Permintaan reset password telah kadaluarsa atau tidak valid. Silakan ajukan ulang.');
    redirect(BASE_URL . '/forgot-password.php');
}

// Fetch user data
$userStmt = $pdo->prepare('SELECT id, name, email, role FROM users WHERE email = ? AND role = ? LIMIT 1');
$userStmt->execute([$reset_data['email'], $reset_data['role']]);
$user = $userStmt->fetch();

if (!$user) {
    set_flash('danger', 'Data pengguna tidak ditemukan.');
    redirect(BASE_URL . '/forgot-password.php');
}

// Calculate resend cooldown remaining seconds
$cooldown_remaining = 0;
if (!empty($reset_data['resend_cooldown_until'])) {
    $cooldown_time = strtotime($reset_data['resend_cooldown_until']);
    if ($cooldown_time > time()) {
        $cooldown_remaining = $cooldown_time - time();
    }
}

// Process POST actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf_token($_POST['csrf_token'] ?? null);
    $action = $_POST['action'] ?? 'verify';

    // 1. Resend OTP
    if ($action === 'resend') {
        if ($cooldown_remaining > 0) {
            set_flash('warning', 'Harap tunggu ' . $cooldown_remaining . ' detik lagi sebelum meminta kode OTP baru.');
            redirect(BASE_URL . '/verify-otp.php?token=' . urlencode($token));
        }

        $new_otp = sprintf('%06d', random_int(100000, 999999));
        $new_otp_expires = date('Y-m-d H:i:s', strtotime('+10 minutes'));
        $new_cooldown = date('Y-m-d H:i:s', strtotime('+60 seconds'));

        $updateStmt = $pdo->prepare('UPDATE password_resets SET otp_code = ?, otp_expires_at = ?, resend_cooldown_until = ?, attempts = 0 WHERE id = ?');
        $updateStmt->execute([$new_otp, $new_otp_expires, $new_cooldown, $reset_data['id']]);

        $mailResult = send_otp_email($user['email'], $user['name'], $new_otp, $user['role'], 10);

        if ($mailResult['success']) {
            set_flash('success', 'Kode OTP baru telah dikirimkan ke email <strong>' . e($user['email']) . '</strong>.');
        } else {
            set_flash('warning', 'Kode OTP baru telah di-generate. Silakan periksa email Anda.');
        }

        redirect(BASE_URL . '/verify-otp.php?token=' . urlencode($token));
    }

    // 2. Verify OTP
    if ($action === 'verify') {
        // Collect OTP digits
        $otp_input = '';
        if (isset($_POST['otp_code']) && strlen(trim($_POST['otp_code'])) > 0) {
            $otp_input = trim($_POST['otp_code']);
        } else {
            for ($i = 1; $i <= 6; $i++) {
                $otp_input .= trim($_POST['otp_' . $i] ?? '');
            }
        }

        // Clean non-digits
        $otp_input = preg_replace('/[^0-9]/', '', $otp_input);

        if (strlen($otp_input) !== 6) {
            set_flash('danger', 'Harap masukkan 6 digit kode OTP secara lengkap.');
            redirect(BASE_URL . '/verify-otp.php?token=' . urlencode($token));
        }

        // Check attempts limit (max 5)
        if ((int)$reset_data['attempts'] >= 5) {
            $delStmt = $pdo->prepare('DELETE FROM password_resets WHERE id = ?');
            $delStmt->execute([$reset_data['id']]);
            set_flash('danger', 'Anda telah salah memasukkan kode OTP sebanyak 5 kali. Demi keamanan, permintaan reset dibatalkan. Silakan ajukan ulang.');
            redirect(BASE_URL . '/forgot-password.php');
        }

        // Check OTP expiry
        if (strtotime($reset_data['otp_expires_at']) < time()) {
            set_flash('danger', 'Kode OTP telah kadaluarsa (lebih dari 10 menit). Silakan klik "Kirim Ulang Kode OTP".');
            redirect(BASE_URL . '/verify-otp.php?token=' . urlencode($token));
        }

        // Check OTP match
        if ($otp_input !== (string)$reset_data['otp_code']) {
            $new_attempts = (int)$reset_data['attempts'] + 1;
            $remaining = 5 - $new_attempts;
            
            $attStmt = $pdo->prepare('UPDATE password_resets SET attempts = ? WHERE id = ?');
            $attStmt->execute([$new_attempts, $reset_data['id']]);

            if ($remaining > 0) {
                set_flash('danger', 'Kode OTP salah. Sisa kesempatan mencoba: <strong>' . $remaining . ' kali</strong>.');
            } else {
                set_flash('danger', 'Batas percobaan habis. Silakan ajukan ulang permintaan reset.');
            }

            redirect(BASE_URL . '/verify-otp.php?token=' . urlencode($token));
        }

        // OTP Verified Successfully
        $verifyStmt = $pdo->prepare('UPDATE password_resets SET is_verified = 1, attempts = 0 WHERE id = ?');
        $verifyStmt->execute([$reset_data['id']]);

        set_flash('success', 'Verifikasi berhasil! Silakan buat password baru Anda.');
        redirect(BASE_URL . '/reset-password.php?token=' . urlencode($token));
    }
}
?>
<!doctype html>
<html lang="id">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Verifikasi Kode OTP - Distributor Pelaminan Family</title>
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
      max-width: 460px;
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
    .email-target-badge {
      background: #fdfaf6;
      border: 1.5px solid rgba(216, 133, 78, 0.25);
      border-radius: 14px;
      padding: 12px 16px;
      text-align: center;
      margin-bottom: 24px;
    }
    .otp-grid-inputs {
      display: flex;
      justify-content: center;
      gap: 10px;
      margin: 24px 0 28px;
    }
    .otp-digit-box {
      width: 52px;
      height: 60px;
      font-size: 26px;
      font-weight: 800;
      text-align: center;
      color: var(--espresso);
      background: #fffdfa;
      border: 2px solid #e5d5c5;
      border-radius: 14px;
      transition: all 0.2s ease;
      font-family: 'Courier New', Courier, monospace;
      outline: none;
      box-sizing: border-box;
    }
    .otp-digit-box:focus {
      border-color: var(--terracotta);
      background: #ffffff;
      box-shadow: 0 0 0 4px rgba(216, 133, 78, 0.18);
      transform: translateY(-2px);
    }
    .otp-digit-box.filled {
      border-color: var(--terracotta-dark);
      background: #fff8f2;
    }
    @media (max-width: 480px) {
      .otp-digit-box {
        width: 44px;
        height: 52px;
        font-size: 22px;
        gap: 6px;
      }
      .otp-grid-inputs {
        gap: 6px;
      }
      .login-card-box {
        padding: 28px 20px;
      }
    }
    .resend-section {
      text-align: center;
      margin-top: 22px;
      font-size: 13px;
      color: #666;
    }
    .btn-resend-link {
      background: none;
      border: none;
      color: var(--terracotta-dark);
      font-weight: 800;
      cursor: pointer;
      padding: 0;
      font-size: 13px;
      text-decoration: underline;
    }
    .btn-resend-link:disabled {
      color: #999;
      cursor: not-allowed;
      text-decoration: none;
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
    <div style="text-align:center;margin-bottom:16px;">
      <div style="display:inline-flex;align-items:center;justify-content:center;width:52px;height:52px;background:#fff5eb;color:#b86028;border-radius:50%;font-size:24px;margin-bottom:12px;border:1px solid rgba(216,133,78,0.25);">
        📩
      </div>
      <h2 style="color:var(--espresso);margin:0 0 4px;font-size:21px;">Verifikasi Kode OTP</h2>
      <p style="margin:0;font-size:13px;color:#777;">Masukkan 6 digit kode yang kami kirimkan ke email Anda</p>
    </div>

    <?= flash() ?>

    <div class="email-target-badge">
      <div style="font-size:12px;color:#777;margin-bottom:2px;">Kode OTP dikirim ke:</div>
      <div style="font-weight:700;color:var(--espresso);font-size:14px;"><?= e($user['email']) ?></div>
      <div style="font-size:11.5px;color:var(--terracotta-dark);font-weight:700;text-transform:uppercase;margin-top:3px;letter-spacing:0.5px;">
        Akun <?= ucfirst(e($user['role'])) ?>
      </div>
    </div>

    <form method="post" id="otpVerifyForm">
      <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
      <input type="hidden" name="token" value="<?= e($token) ?>">
      <input type="hidden" name="action" value="verify">
      <input type="hidden" name="otp_code" id="otp_hidden_input" value="">

      <div class="otp-grid-inputs">
        <input type="text" name="otp_1" class="otp-digit-box" maxlength="1" inputmode="numeric" pattern="[0-9]" autocomplete="off" autofocus required>
        <input type="text" name="otp_2" class="otp-digit-box" maxlength="1" inputmode="numeric" pattern="[0-9]" autocomplete="off" required>
        <input type="text" name="otp_3" class="otp-digit-box" maxlength="1" inputmode="numeric" pattern="[0-9]" autocomplete="off" required>
        <input type="text" name="otp_4" class="otp-digit-box" maxlength="1" inputmode="numeric" pattern="[0-9]" autocomplete="off" required>
        <input type="text" name="otp_5" class="otp-digit-box" maxlength="1" inputmode="numeric" pattern="[0-9]" autocomplete="off" required>
        <input type="text" name="otp_6" class="otp-digit-box" maxlength="1" inputmode="numeric" pattern="[0-9]" autocomplete="off" required>
      </div>

      <button class="btn btn-primary btn-block" type="submit" id="btnSubmitOtp" style="padding:13px;font-size:15px;font-weight:700;border-radius:12px;box-shadow:0 6px 18px rgba(216,133,78,0.28);display:flex;align-items:center;justify-content:center;gap:8px;">
        <span>Verifikasi & Lanjutkan</span> <span>➔</span>
      </button>
    </form>

    <div class="resend-section">
      <form method="post" id="resendForm" style="display:inline;">
        <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
        <input type="hidden" name="token" value="<?= e($token) ?>">
        <input type="hidden" name="action" value="resend">
        
        <span>Tidak menerima email? </span>
        <button type="submit" class="btn-resend-link" id="btnResendOtp" <?= ($cooldown_remaining > 0) ? 'disabled' : '' ?>>
          <?= ($cooldown_remaining > 0) ? 'Kirim Ulang (' . $cooldown_remaining . 's)' : 'Kirim Ulang Kode OTP' ?>
        </button>
      </form>
    </div>

    <p style="text-align:center;font-size:13px;margin-top:22px;margin-bottom:0;color:#666;">
      Salah memasukkan email? <a style="color:var(--terracotta-dark);font-weight:800;" href="<?= BASE_URL ?>/forgot-password.php">Ganti Email</a>
    </p>

  </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
  const inputs = Array.from(document.querySelectorAll('.otp-digit-box'));
  const form = document.getElementById('otpVerifyForm');
  const hiddenInput = document.getElementById('otp_hidden_input');
  const btnResend = document.getElementById('btnResendOtp');
  let countdown = <?= (int)$cooldown_remaining ?>;

  function updateHiddenOtp() {
    const code = inputs.map(input => input.value.trim()).join('');
    hiddenInput.value = code;
    return code;
  }

  inputs.forEach((input, index) => {
    // Input event
    input.addEventListener('input', function(e) {
      const val = this.value.replace(/[^0-9]/g, '');
      this.value = val ? val.slice(-1) : '';

      if (this.value) {
        this.classList.add('filled');
        if (index < inputs.length - 1) {
          inputs[index + 1].focus();
        }
      } else {
        this.classList.remove('filled');
      }

      const currentCode = updateHiddenOtp();
      if (currentCode.length === 6) {
        // Auto submit when all 6 digits filled
        form.submit();
      }
    });

    // Keydown event (Backspace & Navigation)
    input.addEventListener('keydown', function(e) {
      if (e.key === 'Backspace') {
        if (!this.value && index > 0) {
          inputs[index - 1].focus();
          inputs[index - 1].value = '';
          inputs[index - 1].classList.remove('filled');
          updateHiddenOtp();
        } else {
          this.value = '';
          this.classList.remove('filled');
          updateHiddenOtp();
        }
      } else if (e.key === 'ArrowLeft' && index > 0) {
        inputs[index - 1].focus();
      } else if (e.key === 'ArrowRight' && index < inputs.length - 1) {
        inputs[index + 1].focus();
      }
    });

    // Paste event (support pasting whole 6 digits)
    input.addEventListener('paste', function(e) {
      e.preventDefault();
      const pasteData = (e.clipboardData || window.clipboardData).getData('text').replace(/[^0-9]/g, '');
      if (!pasteData) return;

      const digits = pasteData.slice(0, 6).split('');
      digits.forEach((digit, i) => {
        if (inputs[i]) {
          inputs[i].value = digit;
          inputs[i].classList.add('filled');
        }
      });

      updateHiddenOtp();

      const lastFilledIndex = Math.min(digits.length, inputs.length) - 1;
      if (lastFilledIndex >= 0 && lastFilledIndex < inputs.length) {
        inputs[lastFilledIndex].focus();
      }

      if (digits.length >= 6) {
        form.submit();
      }
    });
  });

  // Focus on first empty box
  const firstEmpty = inputs.find(input => !input.value);
  if (firstEmpty) {
    firstEmpty.focus();
  }

  // Resend Countdown Timer
  if (countdown > 0) {
    const timerInterval = setInterval(function() {
      countdown--;
      if (countdown <= 0) {
        clearInterval(timerInterval);
        btnResend.disabled = false;
        btnResend.textContent = 'Kirim Ulang Kode OTP';
      } else {
        btnResend.textContent = 'Kirim Ulang (' + countdown + 's)';
      }
    }, 1000);
  }

  form.addEventListener('submit', function() {
    updateHiddenOtp();
  });
});
</script>

</body>
</html>
