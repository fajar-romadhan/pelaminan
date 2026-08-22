# 📖 DOKUMEN HALAMAN & STRUKTUR SISTEM E-COMMERCE PELAMINAN FAMILY

> **Nama Sistem**: Sistem Informasi E-Commerce & Pemesanan Dekorasi (Distributor Pelaminan Family Zainal - Palembang)  
> **Pemilik / Pimpinan Usaha**: **Zainal Abidin Fikri**  
> **Lokasi Workshop / Gudang**: Sematang Borang / Sako, Palembang, Sumatera Selatan  
> **Teknologi**: PHP (Native), MySQL, JavaScript (ES6, Leaflet Maps, HTML5 Canvas), CSS3 (Vanilla Custom Luxury Theme)

---

## 📌 Ringkasan Arsitektur Sistem

Sistem ini terbagi menjadi 2 area utama:
1. **Area Pelanggan (Public & Customer Area)**: Digunakan oleh calon pembeli untuk menjelajahi katalog pelaminan, menggunakan editor kustomisasi visual 3D live, menghitung ongkos kirim otomatis via peta GPS GoBox Palembang, melakukan pemesanan, membayar via transfer BRI, serta melacak tahapan pengerjaan pesanan.
2. **Area Administrasi (Admin Panel Area)**: Digunakan oleh pengelola/admin untuk mengelola katalog produk & variasi warna, meninjau rancangan kustomisasi visual pelanggan, memverifikasi bukti pembayaran transfer BRI, mengatur status & kalender jadwal produksi, serta mencetak dokumen resmi (Invoice, Kwitansi, Laporan Operasional PDF).

---

## 🌐 I. AREA PELANGGAN & PUBLIK (CUSTOMER PAGES)

### 1. 🏠 Beranda / Home Page (`index.php`)
- **Fungsi Utama**: Halaman landing page utama yang menyambut pengunjung web.
- **Fitur & Komponen**:
  - **Hero Banner**: Display visual mewah dengan tombol call-to-action ke Katalog & Kustomisasi.
  - **Kategori Produk**: Pilihan cepat kategori (*Pelaminan, Kotak Akas, Pot Bunga, Gazebo*).
  - **Produk Unggulan**: Card grid produk terpopuler dengan harga, ukuran, dan badge kategori.
  - **Keunggulan Layanan**: Informasi transaksi aman, pengiriman se-Sumsel, kustomisasi visual, & garansi kualitas.
  - **Galeri Singkat & Footer**: Tautan navigasi, info kontak WA, lokasi toko, & media sosial.
- **Akses**: Publik.

---

### 2. 🖼️ Galeri Portofolio & Katalog (`gallery.php`)
- **Fungsi Utama**: Menampilkan seluruh foto dokumentasi pemasangan pelaminan dan katalog produk secara luas.
- **Fitur & Komponen**:
  - Grid galeri foto resolusi tinggi dengan filter kategori.
  - Lightbox / Modal preview foto untuk melihat detail keindahan ukiran pelaminan.
  - Tombol pintasan langsung ke detail produk atau pemesanan.
- **Akses**: Publik.

---

### 3. 📦 Detail Produk (`product.php`)
- **Fungsi Utama**: Menampilkan rincian lengkap dari satu produk dekorasi.
- **Fitur & Komponen**:
  - **Galeri Foto & Varian Warna**: Foto utama panggung pelaminan (`object-fit: cover`) beserta pilihan thumbnail variasi warna (*White Gold, Cream Gold, Natural, Rose*, dll.) yang dapat berganti secara instan saat diklik.
  - **Spesifikasi Produk**: Ukuran presisi Panjang (P) x Lebar (L) atau Tinggi (T) x Lebar (L) dalam satuan `m` (Meter) atau `cm` (Centimeter).
  - **Tombol Kustomisasi (Khusus Pelaminan)**: Tombol **`🎨 Kustomisasi Produk Ini`** yang khusus muncul pada produk berkategori Pelaminan untuk mengarahkan ke editor 3D.
  - **Tombol Beli Direct & Keranjang**: Tombol `🛒 Pesan Sekarang` (Direct Order) dan `🛒 Tambah ke Keranjang`.
- **Akses**: Publik.

---

### 4. 🎨 Editor Visual Live Kustomisasi (`customization.php`)
- **Fungsi Utama**: Halaman editor kustomisasi dekorasi pelaminan interaktif berbasis 2-kolom split screen (Desktop & Mobile Responsive).
- **Fitur & Komponen**:
  - **Sisi Kiri (Live Canvas Container 16:9)**:
    - Render foto panggung pelaminan secara penuh (`object-fit: cover`).
    - **Layer Drag & Drop 3D**: Penempatan item dekorasi (*Pot Bunga & Kotak Akas*) di atas panggung dengan koordinat persentase relatif (`pctX%`, `pctY%`) yang presisi di semua resolusi perangkat.
    - Efek bayangan alami (*3D drop-shadow*) tanpa kotak putih yang menutupi sofa.
    - **Posisi Awal Aman**: Item baru otomatis muncul di lantai sudut kiri bawah panggung (`left: 5%`, `top: 64%`) agar tidak menutupi sofa utama.
    - **Tombol Unduh PNG (`📸 Simpan / Unduh Foto Hasil Kustom`)**: Komposisi HTML5 Canvas 2x DPI untuk mendownload gambar hasil kustomisasi resolusi tinggi.
  - **Sisi Kanan (Panel Opsi & Pemesanan 3 Langkah)**:
    - *Step 1: Pilih Tema Warna Pelaminan*.
    - *Step 2: Tambah Item Dekorasi (Pot Bunga & Kotak Akas)* dengan lipatan Accordion per kategori & dropdown pilihan varian warna per item.
    - *Step 3: Ringkasan Total & Pemesanan Real-Time*.
- **Akses**: Publik / Pelanggan.

---

### 5. 🛒 Keranjang Belanja (`customers/cart.php`)
- **Fungsi Utama**: Menampilkan daftar item produk yang telah dimasukkan ke dalam keranjang belanja pelanggan.
- **Fitur & Komponen**:
  - Tabel rincian produk, variasi warna yang dipilih, harga satuan, penyesuaian kuantitas (`qty`), dan subtotal.
  - Hapus item dari keranjang (`remove_cart.php`), update kuantitas realtime (`update_cart.php`), serta pemrosesan tambah item (`add_cart.php`).
  - Ringkasan subtotal dan tombol **`Lanjut ke Checkout ➔`**.
- **Akses**: Customer Login / Session Cart.

---

### 6. 🗺️ Form Checkout Keranjang (`checkout.php`) & Direct Order (`order.php`)
- **Fungsi Utama**: Formulir pendaftaran Alamat Pengiriman, Perhitungan Ongkir GPS, dan Finalisasi Pesanan.
- **Fitur & Komponen**:
  - **Peta GPS Interaktif (Leaflet + Haversine Formula)**:
    - Autocomplete pencarian alamat se-Sumatera Selatan (17 Kabupaten/Kota).
    - Titik awal (*Origin*) Gudang Toko Pelaminan Family Zainal Sako Palembang (`-2.9389551, 104.8106462`).
    - **Kalkulasi Ongkir GoBox Palembang Real-Time**: Base fee pick-up Rp 105.000 + Rp 7.500/KM. Gratis jika <= 1.0 KM atau diambil sendiri.
    - Validasi area pengiriman khusus wilayah Sumatera Selatan dengan Modal Warning Pop-up.
  - **Pilihan Metode Penerimaan**: `dikirim` (Pengiriman Armada Toko) atau `diambil` (Diambil Sendiri di Workshop Sako).
  - **Kolom Catatan Admin**: Textarea bebas untuk instruksi khusus/patokan alamat.
  - **Ringkasan Pembayaran & Minimum DP 50%**: Menampilkan Subtotal, Ongkir, Grand Total, dan Syarat Pembayaran Minimum DP 50%.
- **Akses**: Customer Login.

---

### 7. 💳 Pembayaran & Unggah Struk (`payment.php`)
- **Fungsi Utama**: Halaman instruksi transfer bank dan formulir unggah bukti pembayaran.
- **Fitur & Komponen**:
  - **Informasi Rekening Resmi**: **Bank BRI `5741-01-007952-53-6` (a.n. MIS'ATI)** dilengkapi tombol **📋 Salin No. Rekening**.
  - Rincian nominal yang harus dibayar (Minimum DP 50% atau Pelunasan).
  - **Form Unggah Bukti Struk Transfer**: Upload file foto JPG/PNG (Max 5MB) dengan preview realtime.
  - Memperbarui status pesanan menjadi `ADMIN_REVIEW` untuk diperiksa oleh pengelola toko.
- **Akses**: Customer Login.

---

### 8. 📜 Pesanan Saya / Order List (`my-orders.php`)
- **Fungsi Utama**: Halaman riwayat transaksi seluruh pesanan yang pernah dibuat oleh pelanggan.
- **Fitur & Komponen**:
  - Tabel/Card pesanan berisi Kode Order (`#ORD-xxx`), Tanggal, Total Biaya, Status Pembayaran, dan Status Pengerjaan.
  - Tombol Aksi: `💳 Bayar / Unggah Struk`, `📄 Invoice Tagihan`, `🧾 Kwitansi`, dan `🚚 Lacak Pesanan`.
- **Akses**: Customer Login.

---

### 9. 🧾 Invoice Tagihan (`invoice.php`) & Kwitansi Resmi (`receipt.php`)
- **Fungsi Utama**: Dokumen resmi transaksi untuk pelanggan dan arsip tagihan.
- **Fitur & Komponen**:
  - **Invoice (`invoice.php`)**: Menampilkan Kop Resmi Toko Pelaminan Family Zainal, data pemesan, rincian biaya barang, ongkir, nominal DP 50%, sisa pelunasan, serta metode penerimaan.
  - **Kwitansi (`receipt.php`)**: Bukti penerimaan uang pembayaran resmi yang sah (DP/Pelunasan) dari toko ke pelanggan.
  - Dilengkapi tombol **`📄 Cetak Document / Save PDF`** bersatukan CSS `@media print`.
- **Akses**: Customer Login / Admin.

---

### 10. 🚚 Timeline Lacak Pesanan (`tracking.php`)
- **Fungsi Utama**: Halaman pelacakan real-time tahapan pengerjaan pesanan pelanggan.
- **Fitur & Komponen**:
  - Visual Progress Bar Timeline 5 Tahapan Utama:
    1. 🟠 **Masuk Antrean Produksi** (`WAITING_QUEUE` / `ADMIN_REVIEW`)
    2. 🔵 **Sedang Diproduksi** (`PRODUCTION`)
    3. 🚚 / 🏪 **Dalam Pengiriman** (`READY_DELIVERY`) / **Siap Diambil** (`READY_PICKUP`)
    4. 🟢 **Pesanan Selesai** (`COMPLETED`)
  - Catatan tanggal estimasi mulai & selesai pengerjaan oleh tim toko.
- **Akses**: Customer Login.

---

### 11. 🔔 Notifikasi Customer (`notifications.php`)
- **Fungsi Utama**: Pusat pemberitahuan realtime untuk pelanggan.
- **Fitur & Komponen**:
  - Ikon lonceng SVG dengan red badge counter di header.
  - Daftar pemberitahuan ketika pembayaran diverifikasi/diterima admin, atau saat status produksi pesanan bergeser.
- **Akses**: Customer Login.

---

### 12. 🔐 Autentikasi Customer & Admin (`login.php`, `register.php`, `logout.php`)
- **Fungsi Utama**: Modul pendaftaran, masuk, dan keluar dari sistem.
- **Fitur & Komponen**:
  - **`login.php`**: Single Centered Card Layout modern dengan **Role Selector Tab 2-Kolom (`Customer` & `Admin`)**, tombol toggle show/hide password (`👁️` / `🙈`), serta proteksi session.
  - **`register.php`**: Form pendaftaran akun customer baru.
  - **`logout.php`**: Handler penghapusan session.
- **Akses**: Publik.

---

## 🛠️ II. AREA PANEL ADMIN (ADMINISTRATOR PAGES)

### 1. 📊 Dashboard Admin (`admin/index.php`)
- **Fungsi Utama**: Pusat ringkasan eksekutif operasional toko.
- **Fitur & Komponen**:
  - **Stat-Cards Metric**: Total Pesanan, Total Pendapatan (Rp), Pesanan Diproses, dan Katalog Produk Aktif.
  - Ringkasan pesanan masuk terbaru yang memerlukan verifikasi pembayaran atau tindakan admin.
  - Navigasi cepat ke kelola pesanan & laporan operasional.
- **Akses**: Admin Login.

---

### 2. 📋 Kelola Pesanan Masuk / All-in-One Detail View (`admin/orders.php`)
- **Fungsi Utama**: Halaman pusat pengelolaan seluruh alur transaksi & produksi pesanan.
- **Fitur & Komponen**:
  - **Mesin Pencari Pintar & Filter Tanggal**: Cari berdasarkan Kode Order (`#ORD-xxx`), Nama Customer, No. WA, Nama Produk, Alamat, atau Range Tanggal & Status Pesanan.
  - **All-in-One Detail Order View**:
    - *Informasi Customer*: Data pemesan & tombol instan `💬 Chat WhatsApp Customer`.
    - *Review Visual Live Layout Canvas 16:9*: Merender foto panggung pelaminan beserta **posisi presisi `(x,y)` item Pot Bunga & Kotak Akas** yang dirancang oleh pelanggan.
    - *Rincian Keuangan Transparan*: Subtotal produk, variasi warna, item tambahan, ongkir, Grand Total, DP 50%, Pembayaran Diterima, dan Sisa Pelunasan.
    - *Verifikasi Bukti Transfer BRI*: Modal preview foto struk transfer dengan tombol **`✅ Verifikasi & Terima Pembayaran`** atau **`❌ Tolak Pembayaran`**.
    - *Peta Lokasi Delivery*: Visual titik lokasi pengiriman di Peta Leaflet & link Google Maps.
    - *Status & Alur Pengerjaan*: Dropdown pengubahan status pesanan + Kalender Input Tanggal Mulai (`schedule_start`) & Selesai Pengerjaan (`schedule_end`).
    - *Tombol Cetak*: Pintasan instan `📄 Cetak Invoice Tagihan` & `🧾 Cetak Kwitansi Pembayaran`.
- **Akses**: Admin Login.

---

### 3. 📦 Kelola Katalog Produk (`admin/products.php`)
- **Fungsi Utama**: Manajemen data produk yang dijual di e-commerce.
- **Fitur & Komponen**:
  - Tabel daftar produk dengan foto thumbnail, nama, kategori, harga, dan ukuran.
  - **Form Tambah / Edit Produk**:
    - Auto Kode Produk Dinamis (`PLM-xxx`, `KA-xxx`, `PB-xxx`, `GZB-xxx`).
    - Dropdown Kategori (*Pelaminan, Kotak Akas, Pot Bunga, Gazebo*).
    - Form Input Ukuran Terpisah: Panjang (P) / Tinggi (T) & Lebar (L) dengan Satuan `Meter (m)` atau `Centimeter (cm)`.
    - **Engine Auto-Crop Backend (`auto_crop_transparent_image()`)**: Pemangkasan otomatis ruang transparan/putih pada foto produk yang di-upload.
  - Hapus produk (dengan proteksi jika produk terikat transaksi).
- **Akses**: Admin Login.

---

### 4. 🎨 Kelola Variasi Warna Produk (`admin/product-variants.php`)
- **Fungsi Utama**: Manajemen foto variasi warna pendukung untuk tiap produk pelaminan/aksesoris.
- **Fitur & Komponen**:
  - **Cascading Select 2-Step**: Step 1 Dropdown Kategori -> Step 2 Dropdown Produk sasaran yang tersaring otomatis secara realtime.
  - Input nama warna (*White Gold, Cream Gold, Natural Wood*, dll.), foto variasi warna, dan penyesuaian harga opsional.
  - Auto-crop photo engine untuk memastikan foto variansi seragam di canvas.
- **Akses**: Admin Login.

---

### 5. 🏺 Kelola Item Dekorasi Terpusat (`admin/items.php`)
- **Fungsi Utama**: Pengarahan terpusat manajemen item dekorasi (Pot Bunga & Kotak Akas).
- **Fitur & Komponen**:
  - Mengarahkan admin secara otomatis ke halaman Kelola Produk ([admin/products.php](file:///c:/xampp/htdocs/pelaminan/admin/products.php)) untuk menjaga *Single Source of Truth* tanpa perlu menginput item dekorasi 2 kali.
- **Akses**: Admin Login.

---

### 6. 📅 Kalender & Schedules Produksi (`admin/production-calendar.php`)
- **Fungsi Utama**: Manajerial jadwal pengerjaan dan kapasitas antrean panggung pelaminan.
- **Fitur & Komponen**:
  - **Grid Kalender Bulanan Interaktif**: Menampilkan blok bar event pengerjaan pesanan berdasarkan tanggal mulai & tanggal selesai.
  - **Tabel Pengaturan Jadwal Pengerjaan Antrean**: Form penyesuaian tanggal `Mulai Pengerjaan` dan `Selesai Pengerjaan` secara manual oleh admin.
  - Two-way sync otomatis dengan tabel `orders`.
- **Akses**: Admin Login.

---

### 7. 📈 Laporan Operasional & Pendapatan (`admin/operational-report.php`)
- **Fungsi Utama**: Ringkasan laporan kinerja keuangan dan operasional pengerjaan pesanan.
- **Fitur & Komponen**:
  - Filter periode tanggal, status pesanan, dan pencarian transaksi.
  - **8 Stat-Cards Metric**: Total Pesanan, Omset Bruto, Total DP Masuk, Pelunasan, Pesanan Selesai, Diproses, Dibatalkan, dan Rata-rata Nilai Pesanan.
  - Tabel rincian transaksi lengkap.
  - **Tombol Utama `📄 Export PDF / Cetak Laporan`**: Mengarahkan ke halaman cetak PDF resmi A4 Landscape.
- **Akses**: Admin Login.

---

### 8. 📄 Export PDF Laporan Operasional (`admin/export-report-pdf.php`)
- **Fungsi Utama**: Halaman pencetakan & unduh file PDF Laporan Operasional Toko berformat A4 Landscape.
- **Fitur & Komponen**:
  - Layout A4 Landscape resmi dengan Kop Distributor Pelaminan Family Zainal Palembang.
  - Tabel data transaksi yang ter-filter tanpa terpotong.
  - Total pendapatan dan akumulasi keuangan.
  - **Lembar Pengesahan / Tanda Tangan Resmi**: Nama penandatangan **Zainal Abidin Fikri (Pemilik / Pimpinan)** toko.
- **Akses**: Admin Login.

---

### 9. 🔔 Pusat Notifikasi Admin (`admin/notifications.php` & `admin/ajax-notifications.php`)
- **Fungsi Utama**: Notifikasi realtime operasional untuk staf admin.
- **Fitur & Komponen**:
  - **Header Bell Button**: Tombol lonceng SVG dengan badge counter merah menyala animasi pulse.
  - **AJAX Background Polling (`admin/ajax-notifications.php`)**: Memeriksa pesanan baru, pembayaran masuk DP/Pelunasan, pengingat jadwal produksi per 10 detik, memicu Web Audio Synth Chime & Toast Popup.
  - **Pusat Notifikasi (`admin/notifications.php`)**: Tab filter (Semua, Belum Dibaca, Sudah Dibaca), aksen garis kiri terakota & badge merah untuk item baru.
- **Akses**: Admin Login.

---

## 🛠️ III. STRUKTUR FILE SYSTEM & INTEGRASI KUNCI

| Path File | Kategori | Peran Utama |
| :--- | :--- | :--- |
| [index.php](file:///c:/xampp/htdocs/pelaminan/index.php) | Customer | Beranda Web |
| [gallery.php](file:///c:/xampp/htdocs/pelaminan/gallery.php) | Customer | Galeri Portofolio & Produk |
| [product.php](file:///c:/xampp/htdocs/pelaminan/product.php) | Customer | Detail Produk & Opsi Varian Warna |
| [customization.php](file:///c:/xampp/htdocs/pelaminan/customization.php) | Customer | Live Visual Editor 16:9 3D Drag & Drop |
| [checkout.php](file:///c:/xampp/htdocs/pelaminan/checkout.php) | Customer | Checkout Keranjang + Peta Ongkir GoBox |
| [order.php](file:///c:/xampp/htdocs/pelaminan/order.php) | Customer | Direct Checkout + Peta Ongkir GoBox |
| [payment.php](file:///c:/xampp/htdocs/pelaminan/payment.php) | Customer | Instruksi Transfer BRI & Upload Struk |
| [my-orders.php](file:///c:/xampp/htdocs/pelaminan/my-orders.php) | Customer | Riwayat Pesanan Saya |
| [invoice.php](file:///c:/xampp/htdocs/pelaminan/invoice.php) | Shared | Invoice Tagihan Transaksi Resmi |
| [receipt.php](file:///c:/xampp/htdocs/pelaminan/receipt.php) | Shared | Kwitansi Pembayaran Resmi |
| [tracking.php](file:///c:/xampp/htdocs/pelaminan/tracking.php) | Customer | Timeline 5 Stage Tracking Pesanan |
| [login.php](file:///c:/xampp/htdocs/pelaminan/login.php) | Auth | Form Login Customer & Admin |
| [register.php](file:///c:/xampp/htdocs/pelaminan/register.php) | Auth | Form Registrasi Customer Baru |
| [admin/index.php](file:///c:/xampp/htdocs/pelaminan/admin/index.php) | Admin | Dashboard Ringkasan Admin |
| [admin/orders.php](file:///c:/xampp/htdocs/pelaminan/admin/orders.php) | Admin | All-in-One Detail & Kelola Pesanan |
| [admin/products.php](file:///c:/xampp/htdocs/pelaminan/admin/products.php) | Admin | CRUD Katalog Produk & Auto-Crop |
| [admin/product-variants.php](file:///c:/xampp/htdocs/pelaminan/admin/product-variants.php) | Admin | CRUD Variasi Warna (Cascading Select) |
| [admin/production-calendar.php](file:///c:/xampp/htdocs/pelaminan/admin/production-calendar.php) | Admin | Grid Kalender & Jadwal Produksi |
| [admin/operational-report.php](file:///c:/xampp/htdocs/pelaminan/admin/operational-report.php) | Admin | Laporan Operasional & Keuangan |
| [admin/export-report-pdf.php](file:///c:/xampp/htdocs/pelaminan/admin/export-report-pdf.php) | Admin | Cetak PDF A4 Landscape Laporan |
| [admin/notifications.php](file:///c:/xampp/htdocs/pelaminan/admin/notifications.php) | Admin | Pusat Notifikasi Real-Time Admin |
| [config/settings.php](file:///c:/xampp/htdocs/pelaminan/config/settings.php) | Config | Profil Usaha & Rekening BRI |
| [config/helpers.php](file:///c:/xampp/htdocs/pelaminan/config/helpers.php) | Helper | Utility, Auto-Crop, Status Label |
| [assets/css/style.css](file:///c:/xampp/htdocs/pelaminan/assets/css/style.css) | Asset | Custom Luxury Theme & Canvas Style |
| [assets/js/delivery-map.js](file:///c:/xampp/htdocs/pelaminan/assets/js/delivery-map.js) | Asset | Engine Leaflet GPS & GoBox Tarif |

---

*Dokumen ini dibuat otomatis sebagai panduan komprehensif struktur dan fungsi seluruh halaman Sistem Informasi E-Commerce Distributor Pelaminan Family Zainal (Palembang).*
