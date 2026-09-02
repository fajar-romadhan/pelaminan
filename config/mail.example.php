<?php
// SMTP & Email Configuration Template (Copy to config/mail.php)

return [
    // SMTP Server
    'smtp_host'    => 'mail.pelaminanfamily.my.id',
    'smtp_port'    => 465, // 465 (SSL) or 587 (TLS)
    'smtp_secure'  => 'ssl', // 'ssl' or 'tls' or 'none'
    'smtp_auth'    => true,
    'smtp_user'    => 'noreply@pelaminanfamily.my.id',
    'smtp_pass'    => 'YOUR_SMTP_PASSWORD_HERE',
    'smtp_timeout' => 15, // seconds

    // Sender Details
    'from_email'   => 'noreply@pelaminanfamily.my.id',
    'from_name'    => 'Distributor Pelaminan Family',
    'reply_to'     => 'noreply@pelaminanfamily.my.id',

    // Debug Mode
    'debug'        => false
];
