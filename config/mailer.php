<?php
// ==============================================================================
// Pure-PHP SMTP & Email Engine for Distributor Pelaminan Family
// ==============================================================================

/**
 * Mendapatkan konfigurasi email dari config/mail.php atau fallback
 */
function get_mail_config() {
    static $config = null;
    if ($config !== null) {
        return $config;
    }

    $configFile = __DIR__ . '/mail.php';
    if (file_exists($configFile)) {
        $config = require $configFile;
    } else {
        $exampleFile = __DIR__ . '/mail.example.php';
        if (file_exists($exampleFile)) {
            $config = require $exampleFile;
        } else {
            $config = [
                'smtp_host'    => 'mail.pelaminanfamily.my.id',
                'smtp_port'    => 465,
                'smtp_secure'  => 'ssl',
                'smtp_auth'    => true,
                'smtp_user'    => 'noreply@pelaminanfamily.my.id',
                'smtp_pass'    => '',
                'smtp_timeout' => 15,
                'from_email'   => 'noreply@pelaminanfamily.my.id',
                'from_name'    => 'Distributor Pelaminan Family',
                'reply_to'     => 'noreply@pelaminanfamily.my.id',
                'debug'        => false
            ];
        }
    }

    return $config;
}

/**
 * Mengirim email menggunakan Pure-PHP SMTP Socket Client dengan fallback ke mail()
 * 
 * @param string $toEmail Email penerima
 * @param string $toName Nama penerima
 * @param string $subject Judul email
 * @param string $htmlBody Konten email format HTML
 * @param string $plainBody Konten email teks biasa (opsional)
 * @return array ['success' => bool, 'message' => string]
 */
function send_app_mail($toEmail, $toName, $subject, $htmlBody, $plainBody = '') {
    $cfg = get_mail_config();
    $lastError = '';

    // Validasi email penerima
    if (!filter_var($toEmail, FILTER_VALIDATE_EMAIL)) {
        return ['success' => false, 'message' => 'Alamat email tujuan tidak valid.'];
    }

    if (empty($plainBody)) {
        $plainBody = strip_tags(str_replace(['<br>', '<br/>', '<br />', '</p>'], "\n", $htmlBody));
    }

    $fromEmail = $cfg['from_email'] ?? 'noreply@pelaminanfamily.my.id';
    $fromName = $cfg['from_name'] ?? 'Distributor Pelaminan Family';

    // Coba kirim via SMTP jika user & host telah terkonfigurasi
    if (!empty($cfg['smtp_host']) && !empty($cfg['smtp_user']) && !empty($cfg['smtp_pass'])) {
        $smtpResult = send_via_smtp_socket($toEmail, $toName, $subject, $htmlBody, $plainBody, $cfg);
        if ($smtpResult['success']) {
            return $smtpResult;
        }
        $lastError = $smtpResult['message'];
    }

    // Fallback: Coba kirim via native PHP mail()
    $nativeResult = send_via_native_mail($toEmail, $toName, $subject, $htmlBody, $plainBody, $fromEmail, $fromName);
    if ($nativeResult['success']) {
        return $nativeResult;
    }

    return [
        'success' => false,
        'message' => 'Gagal mengirim email. SMTP error: ' . $lastError . ' | Native mail error: ' . $nativeResult['message']
    ];
}

/**
 * Socket SMTP implementation supporting SSL/TLS
 */
function send_via_smtp_socket($toEmail, $toName, $subject, $htmlBody, $plainBody, $cfg) {
    $host = $cfg['smtp_host'];
    $port = (int)($cfg['smtp_port'] ?? 465);
    $secure = strtolower($cfg['smtp_secure'] ?? 'ssl');
    $timeout = (int)($cfg['smtp_timeout'] ?? 15);
    $username = $cfg['smtp_user'];
    $password = $cfg['smtp_pass'];
    $fromEmail = $cfg['from_email'];
    $fromName = $cfg['from_name'];

    $targetHost = ($secure === 'ssl' || $port === 465) ? "ssl://{$host}" : $host;

    $context = stream_context_create([
        'ssl' => [
            'verify_peer'       => false,
            'verify_peer_name'  => false,
            'allow_self_signed' => true
        ]
    ]);

    $socket = @stream_socket_client("{$targetHost}:{$port}", $errno, $errstr, $timeout, STREAM_CLIENT_CONNECT, $context);
    if (!$socket) {
        return ['success' => false, 'message' => "Koneksi SMTP socket gagal ({$errno}): {$errstr}"];
    }

    stream_set_timeout($socket, $timeout);

    $readResponse = function() use ($socket) {
        $data = '';
        while ($line = fgets($socket, 515)) {
            $data .= $line;
            if (isset($line[3]) && $line[3] === ' ') {
                break;
            }
        }
        return $data;
    };

    $sendCommand = function($cmd, $expectedCode = 250) use ($socket, $readResponse) {
        fputs($socket, $cmd . "\r\n");
        $response = $readResponse();
        $code = (int)substr($response, 0, 3);
        if ($code !== $expectedCode && !in_array($code, (array)$expectedCode)) {
            throw new Exception("SMTP Error for '{$cmd}': {$response}");
        }
        return $response;
    };

    try {
        // Initial Greeting
        $greeting = $readResponse();
        if ((int)substr($greeting, 0, 3) !== 220) {
            throw new Exception("Server banner error: {$greeting}");
        }

        // EHLO
        $clientDomain = $_SERVER['HTTP_HOST'] ?? 'pelaminanfamily.my.id';
        $sendCommand("EHLO {$clientDomain}", [250]);

        // STARTTLS if configured for port 587
        if ($secure === 'tls' && $port !== 465) {
            $sendCommand("STARTTLS", 220);
            if (!stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT)) {
                throw new Exception("Gagal memulai negosiasi TLS enkripsi.");
            }
            $sendCommand("EHLO {$clientDomain}", [250]);
        }

        // AUTH LOGIN
        if (!empty($cfg['smtp_auth'])) {
            $sendCommand("AUTH LOGIN", 334);
            $sendCommand(base64_encode($username), 334);
            $sendCommand(base64_encode($password), 235);
        }

        // MAIL FROM & RCPT TO
        $sendCommand("MAIL FROM: <{$fromEmail}>", 250);
        $sendCommand("RCPT TO: <{$toEmail}>", [250, 251]);

        // DATA
        $sendCommand("DATA", 354);

        // Boundary for multipart
        $boundary = "----=_NextPart_" . md5(uniqid(microtime(true), true));

        // MIME Headers
        $headers = [];
        $headers[] = "MIME-Version: 1.0";
        $headers[] = "From: =?UTF-8?B?" . base64_encode($fromName) . "?= <{$fromEmail}>";
        $headers[] = "To: =?UTF-8?B?" . base64_encode($toName) . "?= <{$toEmail}>";
        $headers[] = "Subject: =?UTF-8?B?" . base64_encode($subject) . "?=";
        $headers[] = "Date: " . date('r');
        $headers[] = "Message-ID: <" . md5(uniqid(microtime(true), true)) . "@" . ($cfg['smtp_host'] ?? 'pelaminanfamily.my.id') . ">";
        $headers[] = "Reply-To: <" . ($cfg['reply_to'] ?? $fromEmail) . ">";
        $headers[] = "X-Mailer: PelaminanFamily/1.0";
        $headers[] = "Content-Type: multipart/alternative; boundary=\"{$boundary}\"";

        // Body Content
        $messageBody = implode("\r\n", $headers) . "\r\n\r\n";
        
        // Plain text part
        $messageBody .= "--{$boundary}\r\n";
        $messageBody .= "Content-Type: text/plain; charset=UTF-8\r\n";
        $messageBody .= "Content-Transfer-Encoding: base64\r\n\r\n";
        $messageBody .= chunk_split(base64_encode($plainBody)) . "\r\n";

        // HTML part
        $messageBody .= "--{$boundary}\r\n";
        $messageBody .= "Content-Type: text/html; charset=UTF-8\r\n";
        $messageBody .= "Content-Transfer-Encoding: base64\r\n\r\n";
        $messageBody .= chunk_split(base64_encode($htmlBody)) . "\r\n";

        $messageBody .= "--{$boundary}--\r\n";
        $messageBody .= ".";

        fputs($socket, $messageBody . "\r\n");
        $dataResponse = $readResponse();
        if ((int)substr($dataResponse, 0, 3) !== 250) {
            throw new Exception("Gagal mengirim data email: {$dataResponse}");
        }

        // QUIT
        fputs($socket, "QUIT\r\n");
        fclose($socket);

        return ['success' => true, 'message' => 'Email berhasil dikirim melalui SMTP cPanel.'];
    } catch (Exception $e) {
        if (is_resource($socket)) {
            @fclose($socket);
        }
        return ['success' => false, 'message' => $e->getMessage()];
    }
}

/**
 * Native PHP mail() fallback
 */
function send_via_native_mail($toEmail, $toName, $subject, $htmlBody, $plainBody, $fromEmail, $fromName) {
    $boundary = "----=_Part_" . md5(uniqid(time(), true));

    $headers = [];
    $headers[] = "MIME-Version: 1.0";
    $headers[] = "From: =?UTF-8?B?" . base64_encode($fromName) . "?= <{$fromEmail}>";
    $headers[] = "Reply-To: <{$fromEmail}>";
    $headers[] = "X-Mailer: PHP/" . phpversion();
    $headers[] = "Content-Type: multipart/alternative; boundary=\"{$boundary}\"";

    $message = "--{$boundary}\r\n";
    $message .= "Content-Type: text/plain; charset=UTF-8\r\n";
    $message .= "Content-Transfer-Encoding: base64\r\n\r\n";
    $message .= chunk_split(base64_encode($plainBody)) . "\r\n";

    $message .= "--{$boundary}\r\n";
    $message .= "Content-Type: text/html; charset=UTF-8\r\n";
    $message .= "Content-Transfer-Encoding: base64\r\n\r\n";
    $message .= chunk_split(base64_encode($htmlBody)) . "\r\n";
    $message .= "--{$boundary}--";


    $encodedSubject = "=?UTF-8?B?" . base64_encode($subject) . "?=";
    $toFormatted = "=?UTF-8?B?" . base64_encode($toName) . "?= <{$toEmail}>";

    $sent = @mail($toFormatted, $encodedSubject, $message, implode("\r\n", $headers));
    if ($sent) {
        return ['success' => true, 'message' => 'Email berhasil dikirim melalui native mail server.'];
    }

    return ['success' => false, 'message' => 'Fungsi mail() server hosting tidak merespon.'];
}

/**
 * Mengirimkan Email Kode OTP Reset Password dengan Template Mewah Pelaminan Family
 */
function send_otp_email($toEmail, $toName, $otpCode, $role = 'customer', $expiresMinutes = 10) {
    $roleLabel = ($role === 'admin') ? 'Administrator' : 'Pelanggan (Customer)';
    $subject = "Kode OTP Reset Password: {$otpCode} - Distributor Pelaminan Family";

    $htmlBody = '
    <!DOCTYPE html>
    <html lang="id">
    <head>
      <meta charset="UTF-8">
      <meta name="viewport" content="width=device-width, initial-scale=1.0">
      <title>Kode OTP Reset Password</title>
      <style>
        body { margin: 0; padding: 0; font-family: "Helvetica Neue", Helvetica, Arial, sans-serif; background-color: #f7f1ea; color: #362217; }
        .wrapper { width: 100%; background-color: #f7f1ea; padding: 35px 15px; box-sizing: border-box; }
        .card { max-width: 540px; margin: 0 auto; background-color: #ffffff; border-radius: 20px; overflow: hidden; box-shadow: 0 10px 30px rgba(54,34,23,0.08); border: 1px solid #ebdccc; }
        .header { background: linear-gradient(135deg, #2b1810 0%, #4a2d1d 100%); padding: 32px 20px; text-align: center; color: #ffffff; border-bottom: 3px solid #d4af37; }
        .brand-logo-text { font-size: 22px; font-weight: 800; letter-spacing: 1px; color: #fdfbf7; margin: 0 0 6px; text-transform: uppercase; }
        .brand-sub { font-size: 13px; color: #e2cbb2; margin: 0; font-weight: 300; }
        .content { padding: 36px 30px; line-height: 1.6; }
        .greeting { font-size: 18px; font-weight: 700; color: #362217; margin-bottom: 14px; }
        .text { font-size: 14.5px; color: #555555; margin-bottom: 22px; }
        .otp-container { background: #fdfaf6; border: 2px dashed #d8854e; border-radius: 16px; padding: 24px 15px; text-align: center; margin: 26px 0; }
        .otp-label { font-size: 12px; font-weight: 700; text-transform: uppercase; color: #b86028; letter-spacing: 1.5px; margin-bottom: 8px; }
        .otp-code { font-size: 38px; font-weight: 800; letter-spacing: 10px; color: #362217; font-family: "Courier New", Courier, monospace; margin: 0; }
        .otp-expiry { font-size: 12.5px; color: #888888; margin-top: 10px; }
        .security-note { background: #fff8f0; border-left: 4px solid #d8854e; padding: 14px 16px; border-radius: 6px; font-size: 13px; color: #734522; margin-bottom: 26px; }
        .footer { background-color: #faf5ef; padding: 22px 30px; text-align: center; font-size: 12px; color: #8c7b70; border-top: 1px solid #ebdccc; }
        .footer a { color: #b86028; text-decoration: none; font-weight: 600; }
      </style>
    </head>
    <body>
      <div class="wrapper">
        <div class="card">
          <div class="header">
            <div class="brand-logo-text">👑 Distributor Pelaminan Family</div>
            <div class="brand-sub">Sistem Manajemen Dekorasi Pernikahan</div>
          </div>
          <div class="content">
            <div class="greeting">Halo, ' . htmlspecialchars($toName) . '!</div>
            <div class="text">
              Kami menerima permintaan untuk mereset kata sandi akun <strong>' . htmlspecialchars($roleLabel) . '</strong> Anda. Gunakan kode OTP di bawah ini untuk memverifikasi identitas Anda:
            </div>
            
            <div class="otp-container">
              <div class="otp-label">Kode Verifikasi OTP</div>
              <div class="otp-code">' . htmlspecialchars($otpCode) . '</div>
              <div class="otp-expiry">⏳ Berlaku selama <strong>' . (int)$expiresMinutes . ' Menit</strong></div>
            </div>

            <div class="security-note">
              <strong>⚠️ Peringatan Keamanan:</strong> Jangan berikan kode OTP ini kepada siapa pun, termasuk pihak yang mengaku sebagai staf Pelaminan Family. Jika Anda tidak merasa meminta reset password, abaikan email ini dan akun Anda akan tetap aman.
            </div>

            <div class="text" style="margin-bottom:0;">
              Terima kasih,<br>
              <strong>Tim Distributor Pelaminan Family</strong>
            </div>
          </div>
          <div class="footer">
            <div>Distributor Pelaminan Family &bull; Palembang, Sumatera Selatan</div>
            <div style="margin-top:4px;">Layanan Bantuan WhatsApp: <a href="https://wa.me/6281273400312">0812-7340-0312</a></div>
          </div>
        </div>
      </div>
    </body>
    </html>
    ';

    $plainBody = "DISTRIBUTOR PELAMINAN FAMILY - RESET PASSWORD\n\n"
        . "Halo, {$toName}!\n"
        . "Kami menerima permintaan reset password untuk akun {$roleLabel} Anda.\n\n"
        . "KODE OTP ANDA: {$otpCode}\n\n"
        . "Kode ini berlaku selama {$expiresMinutes} Menit.\n"
        . "Jangan berikan kode ini kepada siapa pun demi keamanan akun Anda.\n\n"
        . "Jika Anda tidak meminta reset password, abaikan pesan ini.\n\n"
        . "Distributor Pelaminan Family - Palembang\n"
        . "WhatsApp: 0812-7340-0312";

    return send_app_mail($toEmail, $toName, $subject, $htmlBody, $plainBody);
}

/**
 * Mengirimkan Email Konfirmasi Sukses Perubahan Password
 */
function send_password_changed_email($toEmail, $toName, $role = 'customer') {
    $roleLabel = ($role === 'admin') ? 'Administrator' : 'Pelanggan (Customer)';
    $subject = "Password Akun Berhasil Diperbarui - Distributor Pelaminan Family";

    $htmlBody = '
    <!DOCTYPE html>
    <html lang="id">
    <head>
      <meta charset="UTF-8">
      <meta name="viewport" content="width=device-width, initial-scale=1.0">
      <title>Password Berhasil Diperbarui</title>
      <style>
        body { margin: 0; padding: 0; font-family: "Helvetica Neue", Helvetica, Arial, sans-serif; background-color: #f7f1ea; color: #362217; }
        .wrapper { width: 100%; background-color: #f7f1ea; padding: 35px 15px; box-sizing: border-box; }
        .card { max-width: 540px; margin: 0 auto; background-color: #ffffff; border-radius: 20px; overflow: hidden; box-shadow: 0 10px 30px rgba(54,34,23,0.08); border: 1px solid #ebdccc; }
        .header { background: linear-gradient(135deg, #2b1810 0%, #4a2d1d 100%); padding: 32px 20px; text-align: center; color: #ffffff; border-bottom: 3px solid #d4af37; }
        .brand-logo-text { font-size: 22px; font-weight: 800; letter-spacing: 1px; color: #fdfbf7; margin: 0 0 6px; text-transform: uppercase; }
        .brand-sub { font-size: 13px; color: #e2cbb2; margin: 0; font-weight: 300; }
        .content { padding: 36px 30px; line-height: 1.6; }
        .greeting { font-size: 18px; font-weight: 700; color: #362217; margin-bottom: 14px; }
        .success-box { background: #f0fdf4; border: 1.5px solid #bbf7d0; border-radius: 14px; padding: 18px 20px; margin: 20px 0; text-align: center; color: #166534; }
        .footer { background-color: #faf5ef; padding: 22px 30px; text-align: center; font-size: 12px; color: #8c7b70; border-top: 1px solid #ebdccc; }
      </style>
    </head>
    <body>
      <div class="wrapper">
        <div class="card">
          <div class="header">
            <div class="brand-logo-text">👑 Distributor Pelaminan Family</div>
            <div class="brand-sub">Sistem Manajemen Dekorasi Pernikahan</div>
          </div>
          <div class="content">
            <div class="greeting">Halo, ' . htmlspecialchars($toName) . '!</div>
            <div class="success-box">
              <div style="font-size:32px;margin-bottom:6px;">✅</div>
              <strong style="font-size:16px;">Password Berhasil Diperbarui</strong>
              <div style="font-size:13px;margin-top:6px;color:#15803d;">
                Kata sandi untuk akun ' . htmlspecialchars($roleLabel) . ' Anda telah berhasil diganti pada ' . date('d F Y, H:i') . ' WIB.
              </div>
            </div>
            <p style="font-size:14px;color:#666;line-height:1.5;">
              Jika Anda merasa melakukan perubahan ini, Anda dapat mengabaikan email ini dan masuk menggunakan kata sandi baru Anda.
            </p>
            <p style="font-size:13px;color:#b91c1c;background:#fef2f2;padding:12px;border-radius:8px;">
              <strong>Bukan Anda yang melakukan perubahan?</strong> Segera hubungi customer care kami melalui WhatsApp untuk mengamankan akun Anda.
            </p>
          </div>
          <div class="footer">
            <div>Distributor Pelaminan Family &bull; Palembang, Sumatera Selatan</div>
            <div style="margin-top:4px;">WhatsApp: 0812-7340-0312</div>
          </div>
        </div>
      </div>
    </body>
    </html>
    ';

    $plainBody = "DISTRIBUTOR PELAMINAN FAMILY - PASSWORD DIPERBARUI\n\n"
        . "Halo, {$toName}!\n"
        . "Kata sandi untuk akun {$roleLabel} Anda telah berhasil diperbarui pada " . date('d F Y, H:i') . " WIB.\n\n"
        . "Jika Anda yang melakukan perubahan ini, Anda dapat login seperti biasa.\n"
        . "Jika bukan Anda, segera hubungi kami melalui WhatsApp di 0812-7340-0312.\n\n"
        . "Distributor Pelaminan Family";

    return send_app_mail($toEmail, $toName, $subject, $htmlBody, $plainBody);
}
