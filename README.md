# Website Distributor Pelaminan Family - PHP Native + MySQL

Project ini dibuat berdasarkan alur dan tampilan UI pada file ZIP React/Vite: landing page, galeri produk, detail produk, form pemesanan, kustomisasi, pembayaran DP/pelunasan, tracking pesanan, dan dashboard admin.

## Teknologi

- PHP Native
- MySQL / MariaDB
- PDO
- HTML, CSS, JavaScript sederhana
- Tanpa framework, sehingga mudah dijalankan di XAMPP/Laragon

## Cara Menjalankan di XAMPP

1. Copy folder `pelaminan_php_mysql` ke folder `htdocs`.
2. Buka `phpMyAdmin`.
3. Import file `database.sql`.
4. Cek file `config/database.php`.
   - Default database: `pelaminan_family`
   - Username: `root`
   - Password: kosong
5. Jalankan di browser:
   - `http://localhost/pelaminan_php_mysql/`

## Akun Demo

Admin:
- Email: `admin@pelaminan.local`
- Password: `admin123`

Customer:
- Email: `customer@pelaminan.local`
- Password: `customer123`

## User Sistem

1. Admin
2. Customer

Pengunjung dapat melihat beranda dan galeri, tetapi untuk melakukan pemesanan harus login sebagai customer.

## Fitur Customer

- Register dan login
- Melihat landing page
- Melihat galeri produk
- Melihat detail produk
- Kustomisasi desain sederhana
- Membuat pesanan
- Simulasi pembayaran DP dan pelunasan
- Melihat pesanan saya
- Tracking status pesanan

## Fitur Admin

- Dashboard
- Kelola produk
- Kelola pesanan dan status
- Atur jadwal pengerjaan
- Kelola tarif pengiriman
- Kelola item tambahan
- Melihat desain kustom customer
- Laporan bulanan dan kategori

## Catatan

Fitur pembayaran masih berupa simulasi tombol bayar. Untuk sistem produksi, bisa dihubungkan ke payment gateway seperti Midtrans, Duitku, Xendit, atau payment gateway bank.
