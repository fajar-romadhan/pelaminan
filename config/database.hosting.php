<?php

// ============================================================
// KONFIGURASI DATABASE - HOSTING ARENHOST
// ============================================================
// Salin file ini menjadi database.php dan isi sesuai data cPanel:
//   cp config/database.hosting.php config/database.php
// ============================================================

$host     = 'localhost';          // Di shared hosting selalu 'localhost'
$port     = '3306';
$dbname   = 'CPANEL_USER_pelaminan';  // Ganti: cpaneluser_nama_database
$username = 'CPANEL_USER_dbuser';    // Ganti: cpaneluser_nama_dbuser
$password = 'PASSWORD_DB_ANDA';       // Ganti: password database

try {
    $pdo = new PDO(
        "mysql:host={$host};port={$port};dbname={$dbname};charset=utf8mb4",
        $username,
        $password,
        [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false
        ]
    );
} catch (PDOException $e) {
    // Di production: jangan tampilkan detail error ke pengunjung
    exit('Koneksi database gagal. Hubungi administrator.');
}
