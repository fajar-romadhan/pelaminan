<?php
// ============================================================
// KONFIGURASI SMTP / EMAIL - HOSTING ARENHOST
// ============================================================
// File ini dapat disalin menjadi config/mail.php di server cPanel:
//   cp config/mail.hosting.php config/mail.php
// ============================================================

return [
    'smtp_host'    => 'mail.pelaminanfamily.my.id',
    'smtp_port'    => 465,
    'smtp_secure'  => 'ssl',
    'smtp_auth'    => true,
    'smtp_user'    => 'noreply@pelaminanfamily.my.id',
    'smtp_pass'    => 'FrhqP#jnc80e?R,1',
    'smtp_timeout' => 15,
    'from_email'   => 'noreply@pelaminanfamily.my.id',
    'from_name'    => 'Distributor Pelaminan Family',
    'reply_to'     => 'noreply@pelaminanfamily.my.id',
    'debug'        => false
];
