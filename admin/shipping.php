<?php
require_once '../config/database.php';
require_once '../config/helpers.php';
require_admin();

set_flash('info', 'Kalkulasi ongkir saat ini sudah otomatis 100% berbasis jarak real-time (Peta & GoBox), menu Kelola Pengiriman manual telah dinonaktifkan.');
redirect(BASE_URL . '/admin/index.php');
