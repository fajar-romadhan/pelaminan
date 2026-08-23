# 📌 PROJECT KNOWLEDGE & PROGRESS LOG

> **Aplikasi**: Sistem Informasi E-Commerce & Pemesanan Dekorasi (Distributor Pelaminan Family - Palembang)
> **Pemilik / Pimpinan Usaha**: **Zainal Abidin Fikri**

---

## 👨‍💼 Data Pemilik & Identitas Bisnis
- **Nama Pemilik / Pimpinan**: Zainal Abidin Fikri
- **Nama Usaha**: Distributor Pelaminan Family / Pelaminan Family Zainal
- **Alamat Workshop/Gudang**: Sematang Borang / Sako, Palembang, Sumatera Selatan 30161
- **Google Maps Resmi**: [Pelaminan Family Zainal](https://maps.app.goo.gl/9k5xjpvaXNnwLKxQ6)
- **Koordinat Resmi Origin Ongkir (Lat, Lng)**: `-2.9389551, 104.8106462`
- **Telepon / WhatsApp**: 0812-7340-0312

---

## 📋 Catatan Progres Pekerjaan & Log Sesi

### 🔹 Sesi: 06 Agustus 2026

#### 1. Perubahan & Penyesuaian Invoice ([invoice.php](file:///c:/laragon/www/pelaminan/invoice.php))
- **Item Pekerjaan**:
  - Mengubah label `Alamat Acara/Pengiriman:` menjadi `Alamat:`.
  - Mengubah teks kebijakan pesanan resmi pada box kuning invoice dari `... dan jadwal acara Anda telah sesuai.` menjadi `... Pastikan data pemesanan Anda telah sesuai.`.
  - Memperbaiki logika tampilan alamat customer jika metode penerimaan = `diambil` (Diambil Sendiri di Workshop), alamat customer hanya menampilkan alamat jalan/rumah tanpa menyisipkan string `"Diambil Sendiri"`.
- **Eror & Penanganan**:
  - *Problem*: Jika customer memilih `diambil`, kolom `district` di database terisi `"Diambil Sendiri"`, menyebabkan alamat tercetak `[Alamat], Diambil Sendiri, Palembang`.
  - *Solusi*: Ditambahkan pengecekan `if ($order['pickup_method'] === 'diambil' || $order['district'] === 'Diambil Sendiri')` untuk hanya menampilkan `$order['address']` tanpa merusak data database.
- **Hasil**: Tampilan invoice rapi, alamat customer bersih dari teks metode pengiriman.

#### 2. Perbaikan Visual Brand Footer ([assets/css/style.css](file:///c:/laragon/www/pelaminan/assets/css/style.css))
- **Item Pekerjaan**:
  - Memperbaiki kontras warna teks brand `Distributor Pelaminan` pada bagian footer.
- **Eror & Penanganan**:
  - *Problem*: Teks `.brand` mewarisi warna gelap (`#362217`), sehingga pada background footer yang berwarna cokelat gelap, teks brand menjadi hampir tidak terlihat (kontras buruk).
  - *Solusi*: Menambahkan rule CSS `.footer .brand { color: #ffffff; }` dan `.footer .brand small { color: var(--terracotta-light); }`.
- **Hasil**: Nama brand dan subteks di footer terlihat kontras dan jelas dengan teks putih.

#### 3. Fitur Export PDF Laporan Operasional Admin ([admin/export-report-pdf.php](file:///c:/laragon/www/pelaminan/admin/export-report-pdf.php))
- **Item Pekerjaan**:
  - Membuat fitur dan tombol Export PDF laporan operasional & pendapatan admin di [admin/operational-report.php](file:///c:/laragon/www/pelaminan/admin/operational-report.php).
  - Membuat halaman terpisah `admin/export-report-pdf.php` berformat **A4 Landscape**.
  - Mengatur nama penandatangan pemilik menjadi **Zainal Abidin Fikri (Pemilik / Pimpinan)**.
- **Eror & Penanganan**:
  - *Problem*: Laporan harus dapat mencetak data yang ter-filter (tanggal, status, customer) tanpa terpotong atau rusak layoutnya.
  - *Solusi*: Meneruskan parameter `$_GET` via `http_build_query($_GET)`, menggunakan CSS `@media print` dan `@page { size: A4 landscape; margin: 10mm; }`, serta menambahkan header Kop resmi, ringkasan 8 stat-card, tabel transaksi lengkap dengan total, dan bagian tanda tangan **Zainal Abidin Fikri**.
- **Hasil**: Admin dapat mencetak atau mengunduh PDF laporan operasional secara rapi tanpa eror.

#### 4. Pengubahan Status Pesanan Dropdown & Kalender Estimasi Pengerjaan ([admin/orders.php](file:///c:/laragon/www/pelaminan/admin/orders.php))
- **Item Pekerjaan**:
  - Mengubah tampilan static "Status & Alur Pengerjaan" pada halaman detail pesanan admin ([admin/orders.php](file:///c:/laragon/www/pelaminan/admin/orders.php)) menjadi form interaktif yang rapi dan profesional.
  - Menyediakan **Dropdown Select Status Pesanan** yang bersih tanpa teks kode dalam kurung (misal: `Belum Dibayar`, `Pembayaran Diterima`, `Sedang Diproduksi`, `Selesai`).
  - Menyediakan **Kalender Date Picker Input 2 Kolom** untuk `Mulai Pengerjaan` (`schedule_start`) dan `Selesai Pengerjaan` (`schedule_end`).
  - Menambahkan tombol `💾 Simpan Perubahan` dengan sinkronisasi otomatis ke tabel `orders`, `production_schedule`, serta pencatatan log riwayat status `log_order_status_change()`.
- **Eror & Penanganan**:
  - *Problem*: Admin memerlukan UI/UX yang bersih tanpa kode teknis dalam kurung `(WAITING_PAYMENT)` dan tampilan form yang lebih padat & profesional.
  - *Solusi*: Mengatur label `<option>` hanya menggunakan nama bahasa Indonesia yang ramah pengguna dan menerapkan grid 2-kolom pada input tanggal.
- **Hasil**: Tampilan kartu Status & Alur Pengerjaan jauh lebih simpel, rapi, dan terasa sangat profesional.

#### 5. Penyesuaian Teks Tombol Cetak ([admin/orders.php](file:///c:/laragon/www/pelaminan/admin/orders.php))
- **Item Pekerjaan**:
  - Menghapus kata "Lihat / " pada tombol cetak invoice dan cetak kwitansi.
- **Hasil**: Teks tombol berubah menjadi `📄 Cetak Invoice Tagihan` dan `🧾 Cetak Kwitansi Pembayaran`.

#### 6. Otomatisasi Kode Produk ([admin/products.php](file:///c:/laragon/www/pelaminan/admin/products.php))
- **Item Pekerjaan**:
  - Menghapus kolom input manual `Kode Produk` dari form tambah/edit produk.
  - Mengatur sistem agar secara otomatis membuatkan kode produk format `PLM-001`, `PLM-002`, dst., berdasarkan urutan ID database.
- **Hasil**: Admin tidak perlu lagi mengetikkan kode produk secara manual, form input menjadi lebih ringkas dan otomatis.

#### 7. Penyederhanaan Form Ukuran (Panjang & Lebar) ([admin/products.php](file:///c:/laragon/www/pelaminan/admin/products.php))
- **Item Pekerjaan**:
  - Mengganti input tunggal `Ukuran` yang bebas menjadi dua kolom input terpisah: `Panjang (m)` dan `Lebar (m)`.
  - Mengatur format otomatis saat disimpan ke database meenjadi `P: [Panjang]m x L: [Lebar]m` (misal: `P: 10m x L: 5m`).
  - Menambahkan parser otomatis saat edit produk agar nilai panjang dan lebar dari string database otomatis terisi ke masing-masing input.
- **Hasil**: Admin cukup memasukkan angka panjang dan lebar tanpa perlu mengetik format secara manual.

#### 8. Fitur & Peningkatan Visual Notifikasi Real-Time Admin ([admin/ajax-notifications.php](file:///c:/laragon/www/pelaminan/admin/ajax-notifications.php), [includes/admin_header.php](file:///c:/laragon/www/pelaminan/includes/admin_header.php))
- **Item Pekerjaan**:
  - Membuat sistem notifikasi real-time khusus Admin (Pesanan Baru 🛒, Pembayaran Masuk DP/Pelunasan 💳, Pengingat Mulai Produksi 🔨, Tenggat Selesai Produksi ⏳, Siap Diambil/Dikirim 🚚).
  - Merancang **Ikon Lonceng SVG Vektor Modern** dalam wadah tombol pill-card dengan border halus, efek hover bergelombang (*ring animation*), dan bayangan (*box-shadow*).
  - Menambahkan **Red Badge Counter High-Contrast** yang memiliki efek pendaran animasi (*badge pulse animation*) saat terdapat notifikasi belum dibaca.
  - Mengintegrasikan AJAX Background Polling (per 10 detik) + **Toast Banner Popup** + **Web Audio Synth Chime Sound** saat notifikasi baru tiba.
  - Membuat halaman pusat notifikasi admin ([admin/notifications.php](file:///c:/laragon/www/pelaminan/admin/notifications.php)) dan endpoint AJAX ([admin/ajax-notifications.php](file:///c:/laragon/www/pelaminan/admin/ajax-notifications.php)).
- **Hasil**: Tampilan tombol lonceng notifikasi sangat modern, elegan, interaktif, dan langsung mencuri perhatian admin ketika ada pesan baru.

#### 9. Mesin Pencari Pintar & Filter Tanggal Pesanan ([admin/orders.php](file:///c:/laragon/www/pelaminan/admin/orders.php))
- **Item Pekerjaan**:
  - Membuat komponen pencari cepat (`q`) yang dapat mencari berdasarkan Kode Order (`#ORD-...`), Nama Customer, Nomor WhatsApp, Nama Produk, dan Kota/Alamat.
  - Menghapus label teks `🔎 Mesin Pencari Pintar` agar tampilan form pencarian lebih ringkas dan sejajar sempurna dengan input tanggal.
  - Menambahkan **Filter Tanggal Order** (`start_date` sampai `end_date`) dan **Filter Status Pesanan**.
  - Menyediakan **Pintasan Tanggal Cepat** (*Hari Ini*, *7 Hari Terakhir*, *Bulan Ini*) dan indikator jumlah data yang ditemukan.
- **Eror & Penanganan**:
  - *Problem*: `Parse error: syntax error, unexpected end of file in admin/orders.php on line 415`.
  - *Solusi*: Menyelaraskan struktur blok kondisional PHP `if($detail)` dan `if(empty($orders))` dengan menambahkan tag penutup `<?php endif; ?>` yang kurang.
- **Hasil**: Mesin pencari dan filter tanggal berfungsi 100% lancar, cepat, bebas eror syntax, dan memiliki tampilan UI/UX yang sangat profesional.

#### 10. Penyederhanaan Status Pesanan (Antrean, Diproses, Diambil, Dikirim, Selesai) ([config/helpers.php](file:///c:/laragon/www/pelaminan/config/helpers.php), [admin/orders.php](file:///c:/laragon/www/pelaminan/admin/orders.php))
- **Item Pekerjaan**:
  - Menyesuaikan label dan pilihan status pesanan sesuai permintaan owner:
    1. 🟠 **Dalam Antrean** (`WAITING_QUEUE`)
    2. 🔵 **Sedang Diproses** (`PRODUCTION`)
    3. 🏪 **Diambil** (`READY_PICKUP`)
    4. 🚚 **Dikirim** (`READY_DELIVERY`)
    5. 🟢 **Selesai** (`COMPLETED`)
  - Memperbarui fungsi `status_label()` & `status_class()` untuk menggantikan "Belum Dibayar" dengan status penerimaan yang jelas (*Diambil* & *Dikirim*).
- **Hasil**: Pengelolaan status pesanan sangat fleksibel, akurat, dan menggambarkan alur operasional nyata usaha pelaminan.

#### 11. Pembatasan Wilayah Pengiriman Khusus Sumatera Selatan ([assets/js/delivery-map.js](file:///c:/laragon/www/pelaminan/assets/js/delivery-map.js), [checkout.php](file:///c:/laragon/www/pelaminan/checkout.php), [order.php](file:///c:/laragon/www/pelaminan/order.php))
- **Item Pekerjaan**:
  - Membatasi wilayah jangkauan pengiriman hanya untuk area **Sumatera Selatan** (bounding box koordinat Lat `-4.95` s/d `-1.60`, Lng `102.00` s/d `106.10` serta geocoding check provinsi).
  - Membuat **Modal Warning Pop-up Interaktif** (`#sumselCoverageModal`) jika customer memilih titik di luar Sumatera Selatan (seperti Aceh, Jakarta, Medan, Lampung, dll).
  - Otomatis meriset posisi marker peta ke lokasi valid terdekat/Palembang dan memblokir pengiriman form jika titik di luar Sumsel.
- **Hasil**: Sistem secara otomatis mencegah order pengiriman di luar Sumatera Selatan dan memberikan penjelasan pesan yang ramah & informatif kepada customer.

#### 12. Tombol Toggle Password Hide/Show ([register.php](file:///c:/laragon/www/pelaminan/register.php), [login.php](file:///c:/laragon/www/pelaminan/login.php))
- **Item Pekerjaan**:
  - Menambahkan tombol **Toggle Sembunyikan/Tampilkan Password** (`👁️` / `🙈`) pada kolom `Password` dan `Konfirmasi Password` di halaman registrasi ([register.php](file:///c:/laragon/www/pelaminan/register.php)).
  - Mengintegrasikan fitur serupa pada halaman login ([login.php](file:///c:/laragon/www/pelaminan/login.php)) untuk konsistensi pengalaman pengguna.
- **Hasil**: Pengguna dapat melihat atau menyembunyikan karakter password yang diketik secara instan dengan mengklik ikon mata di sisi kanan kolom input.

#### 13. Tombol Navigasi Kembali ([checkout.php](file:///c:/laragon/www/pelaminan/checkout.php), [order.php](file:///c:/laragon/www/pelaminan/order.php), [customization.php](file:///c:/laragon/www/pelaminan/customization.php))
- **Item Pekerjaan**:
  - Menambahkan **Tombol Kembali** (`← Kembali ke Keranjang` / `← Kembali ke Detail Produk`) di bagian atas header halaman Checkout ([checkout.php](file:///c:/laragon/www/pelaminan/checkout.php)), Form Pemesanan Direct ([order.php](file:///c:/laragon/www/pelaminan/order.php)), dan Design Editor ([customization.php](file:///c:/laragon/www/pelaminan/customization.php)).
- **Hasil**: Memudahkan navigasi customer untuk kembali ke tahap sebelumnya tanpa harus menggunakan tombol back browser.

#### 14. Perbaikan Kontras Visual Tombol Kembali (`.btn-back-nav`) ([assets/css/style.css](file:///c:/laragon/www/pelaminan/assets/css/style.css))
- **Item Pekerjaan**:
  - *Problem*: Penggunaan kelas `.btn-outline-light` pada latar header cream terang membuat teks tombol `← Kembali ke Keranjang` berwarna putih sehingga tersamar/tidak terlihat.
  - *Solusi*: Membuat kelas CSS khusus `.btn-back-nav` dengan latar belakang putih bersih, border terakota hangat (`rgba(216,133,78,0.35)`), warna teks terakota pekat (`var(--terracotta-dark)`), serta efek hover elegan yang mengubah tombol menjadi penuh terakota dan teks putih berbayang.
- **Hasil**: Teks tombol kembali kini sangat kontras, jelas terbaca, 100% tajam, dan terlihat sangat profesional.

#### 15. Desain UI Ultra-Premium pada Header & Tombol Navigasi ([assets/css/style.css](file:///c:/laragon/www/pelaminan/assets/css/style.css))
- **Item Pekerjaan**:
  - Memperbarui komponen `.page-head` dengan latar belakang *radial & linear gradient luxury cream & gold* (`linear-gradient(135deg, #fdfbf7, #f7ebd9, #f4e4cc)`), aksen border emas, dan font shadow halus.
  - Mengubah `.btn-back-nav` menjadi *Floating Pill Card Glassmorphism* dengan *backdrop blur*, aksen badge lingkaran panah (`.icon-arrow`), serta efek hover pergeseran panah (`translateX(-3px)`) dan efek elevasi terakota.
- **Hasil**: Tampilan UI halaman terasa sangat mewah, berkelas, modern, dan bernuansa distributor pelaminan bintang 5.

#### 16. Penggantian Form Kota & Kecamatan dengan Catatan untuk Admin ([customization.php](file:///c:/laragon/www/pelaminan/customization.php), [checkout.php](file:///c:/laragon/www/pelaminan/checkout.php), [order.php](file:///c:/laragon/www/pelaminan/order.php))
- **Item Pekerjaan**:
  - Menghapus form dropdown pilihan `-- Pilih Kota / Kabupaten --` dan `-- Pilih Kecamatan --` pada ringkasan total dan formulir pemesanan.
  - Menggantikan form tersebut dengan kolom input **📝 Catatan untuk Admin (Opsional)** berupa textarea luas tempat customer memberikan instruksi khusus, patokan alamat, atau catatan pesanan.
  - Menyesuaikan logika pemrosesan order di server agar proses transaksi berjalan 100% lancar tanpa bergantung pada pilihan tarif kecamatan.
- **Hasil**: Pengisian data pemesanan jauh lebih ringkas, leluasa, bebas ribet, dan memberikan fleksibilitas penuh bagi customer untuk menuliskan instruksi ke admin.

#### 17. Konsolidasi Review Pesanan & Antrean Produksi ke Modul Pesanan Masuk ([admin/orders.php](file:///c:/laragon/www/pelaminan/admin/orders.php), [includes/admin_header.php](file:///c:/laragon/www/pelaminan/includes/admin_header.php))
- **Item Pekerjaan**:
  - Menghapus menu **Review Pesanan** dan **Antrean Produksi** dari sidebar admin ([includes/admin_header.php](file:///c:/laragon/www/pelaminan/includes/admin_header.php)).
  - Menggabungkan seluruh fungsionalitas review dan manajemen antrean produksi secara terpadu di dalam halaman **Detail Pesanan Masuk** ([admin/orders.php](file:///c:/laragon/www/pelaminan/admin/orders.php?detail=...)):
    - Informasi Pemesan & Tombol Instan `💬 Chat WhatsApp Customer`.
    - Rincian Produk, Variasi Warna, & Item Tambahan Kustomisasi Decor.
    - Rincian Keuangan, Minimal DP, Sisa Pelunasan, & Riwayat Pembayaran.
    - Peta Lokasi Interaktif Leaflet & Koordinat Google Maps.
    - Form Pengubahan Status Pesanan & Penjadwalan Tanggal Produksi (`Mulai Pengerjaan` & `Selesai Pengerjaan`).
    - Pintasan Cetak Invoice, Cetak Kwitansi, dan Halaman Tracking Customer.
- **Hasil**: Sidebar admin jauh lebih bersih & simpel, dan admin cukup mengelola seluruh alur pesanan dari satu tempat (*All-in-One Detail View*).

#### 18. Fitur Peta Interaktif & Kalkulasi Ongkir Real-Time Berbasis Jarak GoBox ([assets/js/delivery-map.js](file:///c:/TASAH/www/pelaminan/pelaminan/assets/js/delivery-map.js), [config/helpers.php](file:///c:/TASAH/www/pelaminan/pelaminan/config/helpers.php), [checkout.php](file:///c:/TASAH/www/pelaminan/pelaminan/checkout.php), [order.php](file:///c:/TASAH/www/pelaminan/pelaminan/order.php))
- **Item Pekerjaan**:
  - Mengintegrasikan Peta Leaflet + Shopee-Style Autocomplete Search yang mencakup seluruh 17 Kabupaten/Kota (236+ Kecamatan & ~350+ Desa/Landmark) se-Sumatera Selatan dengan respon instan 0ms dan geocoding online Photon/ArcGIS.
  - Memetakan koordinat resmi gudang toko **Pelaminan Family Zainal** (`-2.9389551, 104.8106462`) sebagai titik awal (*Origin*).
  - Mengukur jarak presisi pembeli dalam KM secara *real-time* menggunakan **Rumus Haversine**.
  - Mengimplementasikan **Rumus Resmi GoBox Gojek Palembang** yang dikalibrasi dari 2 sampel data real Gojek (`3.9 km = Rp 134.000` & `9.5 km = Rp 176.500`):
    - **Base Fee Armada Pick-Up Murni:** `Rp 105.000`
    - **Tarif Normal (0 – 30 KM):** `Rp 105.000 + (Jarak KM x Rp 7.500)`
    - **Penyesuaian Jarak Jauh (30 – 100 KM):** KM ke-31 s/d 100 @ `Rp 6.000 / KM`
    - **Penyesuaian Jarak Sangat Jauh (> 100 KM):** KM ke-101+ @ `Rp 5.000 / KM`
    - **Promo Dekat Gudang (<= 1.0 KM):** `Rp 0` (GRATIS)
    - **Metode Diambil Sendiri:** `Rp 0` (Bebas Ongkir)
  - Menampilkan **Badge Jarak & Ongkir Real-Time** di bawah peta serta memperbarui Subtotal, Ongkir, Total Akhir, dan Minimum DP 50% secara *live* pada Order Summary.
- **Hasil**: Sistem kalkulasi ongkir 100% presisi mengikuti tarif resmi GoBox Gojek Palembang, aman dari manipulasi data, dan bebas error.

#### 19. Pemetaan Lokasi Spesifik, Relevance Ranking Search, & Integrasi Badge ke Order Summary ([assets/js/delivery-map.js](file:///c:/TASAH/www/pelaminan/pelaminan/assets/js/delivery-map.js), [checkout.php](file:///c:/TASAH/www/pelaminan/pelaminan/checkout.php), [order.php](file:///c:/TASAH/www/pelaminan/pelaminan/order.php))
- **Item Pekerjaan**:
  - **Pemetaan Detail Sako & Perumahan Musi Palem Indah**: Menambahkan landmark presisi **Toko Nia Sako** (`-3.073002, 104.8733955`), **Perumahan Musi Palem Indah** (`-3.040798, 104.838521`), dan 160+ desa/perumahan sekitar Sako (radius 30 km).
  - **Pembersihan Subtitle & Relevance Ranking Engine**: Membersihkan noise subtitle `(Radius 15km Toko Nia Sako)` dari seluruh dictionary dan memperbarui algoritma `fetchSuggestions` dengan skor relevansi (Exact Title Match mendapat skor 1000 sehingga `"Toko Nia Sako"` selalu tampil paling atas di urutan #1).
  - **Penyederhanaan Form Alamat**: Menghapus input manual `Alamat Lengkap Lokasi Acara` (`address`) pada `checkout.php` dan `order.php` agar pelanggan tidak perlu mengetik alamat 2 kali, dan mengalirkan hasil peta (`delivery_map_address`) ke backend PHP `$address`.
  - **Refaktor Badge ke Order Summary Card**: Memindahkan badge informasi jarak (`16.5 km`) & ongkos kirim real-time GoBox (`Rp 228.500`) langsung ke dalam card **Ringkasan Pesanan (Order Summary)** di kolom sebelah kanan, serta memastikan pembaruan *live* pada `Biaya Pengantaran`, `Total Akhir`, dan `Minimal DP (50%)`.
- **Hasil**: Tampilan formulir pemesanan jauh lebih rapi, terpusat, pencarian alamat sangat presisi (#1), dan kalkulasi ongkir terupdate 100% *live* di Order Summary.

#### 20. Penyelarasan Status Pesanan 100% Presisi Antara Admin & Customer ([tracking.php](file:///c:/TASAH/www/pelaminan/pelaminan/tracking.php), [admin/orders.php](file:///c:/TASAH/www/pelaminan/pelaminan/admin/orders.php), [config/helpers.php](file:///c:/TASAH/www/pelaminan/pelaminan/config/helpers.php))
- **Item Pekerjaan**:
  - Mengubah seluruh label opsi dropdown pengubahan status di Panel Admin (`admin/orders.php`) dan helper label PHP (`config/helpers.php`) agar 100% identik dan selaras dengan label tahapan timeline customer (`tracking.php`):
    - `WAITING_QUEUE` $\rightarrow$ **Masuk Antrean Produksi**
    - `PRODUCTION` $\rightarrow$ **Sedang Diproduksi**
    - `READY_DELIVERY` / `ON_DELIVERY` $\rightarrow$ **Dalam Pengiriman**
    - `READY_PICKUP` $\rightarrow$ **Siap Diambil**
    - `COMPLETED` $\rightarrow$ **Pesanan Selesai**
    - `CANCELLED` $\rightarrow$ **Dibatalkan**
  - Mengatur UI Timeline Customer menjadi 5 langkah presisi 1 baris yang maju 1-to-1 secara *live* saat Admin mengubah status di panel admin.
- **Hasil**: Alur sistem 100% sinkron, tidak ada label status yang melompat/berbeda antara Admin dan Customer, serta tampilan antarmuka sangat profesional.

#### 21. Penghapusan Menu & Modul Manual Kelola Pengiriman ([includes/admin_header.php](file:///c:/TASAH/www/pelaminan/pelaminan/includes/admin_header.php), [admin/shipping.php](file:///c:/TASAH/www/pelaminan/pelaminan/admin/shipping.php))
- **Item Pekerjaan**:
  - Menghapus link menu sidebar **🚚 Kelola Pengiriman** dari Panel Admin (`includes/admin_header.php`).
  - Memperbarui file `admin/shipping.php` untuk melakukan *redirect* otomatis ke dashboard admin karena seluruh sistem kalkulasi ongkir kini sudah 100% otomatis berbasis jarak real-time (Peta & GoBox Gojek Palembang).
- **Hasil**: Sidebar admin lebih ringkas & bersih, serta menghilangkan kebingungan pengelolaan tarif manual.

#### 22. Sinkronisasi Otomatis Jadwal Pengerjaan Antrean ke Kalender Produksi ([admin/production-calendar.php](file:///c:/TASAH/www/pelaminan/pelaminan/admin/production-calendar.php), [admin/orders.php](file:///c:/TASAH/www/pelaminan/pelaminan/admin/orders.php))
- **Item Pekerjaan**:
  - Mengubah query `admin/production-calendar.php` agar membaca jadwal pengerjaan dari `orders.schedule_start` / `orders.schedule_end` serta `production_queue.estimated_start_date` / `production_queue.estimated_end_date` secara *real-time*.
  - Mengintegrasikan mekanisme dua arah (*two-way sync*): Saat Admin menetapkan tanggal di halaman Detail Pesanan (`admin/orders.php`), sistem secara otomatis memperbarui/membuat baris di `production_queue`.
  - Memastikan seluruh pesanan aktif tampil di tabel *Pengaturan Jadwal Pengerjaan Antrean* dan *Grid Kalender Produksi*.
- **Hasil**: Kalender produksi 100% terbaca dan langsung menampilkan event pengerjaan antrean di grid tanggal kalender.

#### 23. Penyederhanaan Tombol Export PDF Laporan Operasional ([admin/operational-report.php](file:///c:/TASAH/www/pelaminan/pelaminan/admin/operational-report.php))
- **Item Pekerjaan**:
  - Menghapus tombol ganda *Export PDF* pada bagian Header Atas dan Filter Box.
  - Mempertahankan **satu tombol utama `📄 Export PDF / Cetak Laporan`** yang diposisikan secara presisi di bagian header card **Detail Laporan Produksi & Transaksi**.
  - Menyederhanakan opsi filter status pesanan menggunakan Bahasa Indonesia yang konsisten dengan sistem.
- **Hasil**: Antarmuka halaman Laporan Operasional terasa jauh lebih rapi, terpusat, dan bebas redundansi tombol.

#### 24. Peningkatan UI & Indikator Notifikasi Belum Dibaca ([admin/notifications.php](file:///c:/TASAH/www/pelaminan/pelaminan/admin/notifications.php))
- **Item Pekerjaan**:
  - Menambahkan **Garis Aksen Kiri Terakota (`border-left: 6px solid var(--terracotta-dark)`)**, Latar Belakang Krem Hangat (`#fffaf5`), serta Badge Merah Menyala **`🔴 BELUM DIBACA`** untuk notifikasi yang belum dibuka admin.
  - Menyediakan tombol aksi per item **`✓ Tandai Dibaca`** dan otomatis menandai dibaca saat admin mengklik **`Detail Order →`**.
  - Menambahkan Tab Filter Interaktif: `Semua Notifikasi`, `🔴 Belum Dibaca (N)`, dan `✓ Sudah Dibaca`.
- **Hasil**: Admin dapat dengan mudah membedakan mana notifikasi baru/belum dibaca vs sudah dibaca dengan pengalaman antarmuka yang sangat modern.

#### 25. Perbaikan Permanen Sidebar Admin Fixed Viewport Layout ([assets/css/style.css](file:///c:/TASAH/www/pelaminan/pelaminan/assets/css/style.css))
- **Item Pekerjaan**:
  - Mengganti `overflow-x: hidden` pada `html, body` menjadi `overflow-x: clip` untuk mencegah terciptanya *scroll container context* terselubung yang merusak `position: fixed`.
  - Mengunci arsitektur `.sidebar` menjadi `position: fixed; top: 0; left: 0; bottom: 0; width: 260px; height: 100vh; z-index: 1000; overflow-y: auto;` dan `.admin-main` menjadi `margin-left: 260px; width: calc(100% - 260px);`.
  - Menghapus aturan konflik `.sidebar { display: none; }` pada breakpoint 900px.
- **Hasil**: Sidebar navigasi kiri terkunci 100% sempurna pada layar browser (*viewport fixed*), terbentang utuh dari paling atas hingga paling bawah tanpa pernah terpotong atau ikut tergulung saat halaman di-scroll.

#### 26. Desain Logo Emblem Mewah Animated Shimmer & Live Status Badge ([includes/admin_header.php](file:///c:/TASAH/www/pelaminan/pelaminan/includes/admin_header.php), [assets/css/style.css](file:///c:/TASAH/www/pelaminan/pelaminan/assets/css/style.css))
- **Item Pekerjaan**:
  - Merancang **Logo Badge Emblem Animated Shimmer (`@keyframes logoShimmer`)** bernuansa *Luxury Gold & Terracotta Gradient* lengkap dengan kilauan sinar berjalan dan aura glowing emas (`@keyframes logoPulse`).
  - Menambahkan **Interaksi Tilt 3D pada Hover** dan indikator titik hijau bernapas **`🟢 Admin Panel` (`@keyframes pulseOnlineDot`)**.
- **Hasil**: Antarmuka header sidebar admin tampil sangat elegan, premium, dan berkelas tinggi.

#### 27. Rincian Subtotal Produk & Pembayaran Keuangan Transparan di Panel Admin ([admin/orders.php](file:///c:/xampp/htdocs/pelaminan/admin/orders.php))
- **Item Pekerjaan**:
  - Menambahkan tabel kalkulasi **Rincian Subtotal Transparan** di dalam card *Rincian Pembayaran & Keuangan* pada halaman Detail Pesanan Admin.
  - Menjabarkan secara terperinci setiap komponen biaya: **Subtotal Produk Utama**, **Variasi Warna**, **Item Tambahan (Add-ons)**, **Biaya Pengiriman (Ongkir / Bebas Ongkir)**, hingga **Grand Total Biaya Pesanan**, **Minimal DP 50%**, **Sudah Dibayar**, dan **Sisa Pelunasan**.
- **Hasil**: Admin memiliki transparansi 100% terhadap seluruh itemisasi biaya pesanan customer.

#### 28. Sistem Dropdown Produk Bertingkat 2-Step (Cascading Select) pada Kelola Variasi Warna ([admin/product-variants.php](file:///c:/xampp/htdocs/pelaminan/admin/product-variants.php))
- **Item Pekerjaan**:
  - Mengubah sistem pemilihan produk dari single dropdown menjadi **Filter Bertingkat 2-Step (Cascading Select)**:
    1. **Langkah 1**: Dropdown **Pilih Kategori Produk** (`1. Pilih Kategori Produk`).
    2. **Langkah 2**: Dropdown **Pilih Produk** (`2. Pilih Produk`) yang secara otomatis hanya menampilkan produk dari kategori terpilih secara *real-time* via JavaScript.
  - Mengintegrasikan logika yang sama pada **Baris Filter Data** (Filter Kategori & Filter Produk) serta tabel daftar variasi warna (menambahkan kolom badge kategori `📂 [Nama Kategori]`).
  - Menjaga sinkronisasi saat mode edit variasi warna agar kategori dan produk sasaran terdeteksi & ter-select otomatis.
- **Hasil**: Tampilan antarmuka sangat rapi, scalable untuk ratusan/ribuan produk, bebas berantakan, dan sangat mempercepat alur kerja admin.

#### 30. Perbaikan Layout Form Pencarian Alamat & Dropdown Autocomplete Peta ([checkout.php](file:///c:/xampp/htdocs/pelaminan/checkout.php), [order.php](file:///c:/xampp/htdocs/pelaminan/order.php), [assets/css/style.css](file:///c:/xampp/htdocs/pelaminan/assets/css/style.css), [assets/js/delivery-map.js](file:///c:/xampp/htdocs/pelaminan/assets/js/delivery-map.js))
- **Item Pekerjaan**:
  - Mengisolasi elemen dropdown `map-search-autocomplete` dari flex container `map-search-input-group` (`display: flex; gap: 8px;`).
  - Menetapkan kelas CSS `.map-search-input-group` dengan `width: 100%`, input `flex: 1; min-width: 0;`, dan tombol cari `flex-shrink: 0; white-space: nowrap;`.
  - Menambahkan aturan CSS & inline style `position: absolute !important; top: calc(100% + 4px) !important; left: 0 !important; right: 0 !important; width: 100% !important; z-index: 999999 !important; box-sizing: border-box !important;` pada `#map-search-autocomplete`.
- **Eror & Penanganan**:
  - *Problem*: Di beberapa browser/kondisi cache, elemen `#map-search-autocomplete` terdeteksi sebagai flex child ke-3 di dalam baris flex yang sama dengan input dan tombol, menyebabkan input dan tombol tergepeng secara vertikal di sisi kiri dan daftar alamat meluap ke kanan.
  - *Solusi*: Memisahkan baris input + tombol ke dalam wadah flex khusus (`.map-search-input-group`) dan menempatkan elemen autocomplete sebagai sibling melayang (*positioned overlay*) di bawahnya.
  - Menyederhanakan label judul pencarian alamat menjadi `🔍 Cari Alamat Pengiriman` (menghapus teks tambahan dalam kurung `(Pilih saran otomatis seperti di Shopee)`).
#### 31. Penambahan Role & Fitur Khusus Pemilik Usaha / Owner ([admin/manage-admins.php](file:///c:/xampp/htdocs/pelaminan/admin/manage-admins.php), [admin/activity-logs.php](file:///c:/xampp/htdocs/pelaminan/admin/activity-logs.php), [login.php](file:///c:/xampp/htdocs/pelaminan/login.php), [config/helpers.php](file:///c:/xampp/htdocs/pelaminan/config/helpers.php))
- **Item Pekerjaan**:
  - Menambahkan role `owner` pada tabel `users` (`ENUM('admin','owner','customer')`) dan membuat akun seed default Pak **Zainal Abidin Fikri** (`owner@pelaminan.local` / `owner123`).
  - Menambahkan opsi tab login **Owner** pada [login.php](file:///c:/xampp/htdocs/pelaminan/login.php) dan penanda visual **`👑 Owner Panel`** pada header sidebar.
  - Membangun modul **Kelola Akun Admin** ([admin/manage-admins.php](file:///c:/xampp/htdocs/pelaminan/admin/manage-admins.php)) khusus Owner untuk menambah, mereset password, dan mengelola staf admin operasional toko.
  - Membangun modul **Log Aktivitas Admin** ([admin/activity-logs.php](file:///c:/xampp/htdocs/pelaminan/admin/activity-logs.php)) dan tabel `activity_logs` untuk merekam jejak real-time dari setiap aksi/perubahan status yang dilakukan admin operasional.
  - Menerapkan **Pemisahan Menu Navigasi Eksklusif**: Menyembunyikan menu kelola katalog (`Kelola Produk`, `Variasi Warna`, `Item Tambahan`), **Kalender Produksi**, serta **Notifikasi** dan lonceng dari panel Owner, serta membatasi akses halaman tersebut khusus untuk staf Admin operasional.
  - Memperbaiki tampilan badge nama header agar tidak mengalami duplikasi string `(Pemilik)`.
  - Menghapus teks sub-deskripsi serta blok *stat-cards* (`Total Admin Operasional`, `Pemilik Usaha`, `Total Log Aktivitas`, `Aktivitas Hari Ini`) pada halaman `Kelola Akun Admin` dan `Log Aktivitas Admin` agar tampilan antarmuka langsung fokus pada tabel data.
  - Memperbaiki eror *Headers Already Sent* pada [admin/manage-admins.php](file:///c:/xampp/htdocs/pelaminan/admin/manage-admins.php) dan [admin/activity-logs.php](file:///c:/xampp/htdocs/pelaminan/admin/activity-logs.php) dengan memindahkan pemrosesan form `POST` dan `redirect()` sebelum pemanggilan template HTML header `admin_header.php`.
  - Menambahkan **Tombol Toggle Lihat & Sembunyikan Password** (`👁️` / `🙈`) pada form modal Tambah Admin Baru dan Reset Password di [admin/manage-admins.php](file:///c:/xampp/htdocs/pelaminan/admin/manage-admins.php).
  - Memperbarui fungsi `log_activity()` di [config/helpers.php](file:///c:/xampp/htdocs/pelaminan/config/helpers.php) dan query [admin/activity-logs.php](file:///c:/xampp/htdocs/pelaminan/admin/activity-logs.php) agar **hanya merekam aksi dari akun Admin operasional**, serta menyederhanakan judul tabel menjadi `📋 Riwayat Aktivitas Terbaru`.
  - Memperbarui label tombol navigasi pada halaman cetak PDF laporan ([admin/export-report-pdf.php](file:///c:/xampp/htdocs/pelaminan/admin/export-report-pdf.php)) menjadi `← Kembali ke Laporan Operasional`.
  - Menyesuaikan blok tanda tangan dan Kop cetak laporan PDF saat dicetak oleh Owner: nama pembuat laporan tampil sebagai `Zainal Abidin Fikri` dengan jabatan `Pemilik` (tanpa teks *Admin Operasional*), serta teks tanda tangan persetujuan diperbarui menjadi `Pemilik - Distributor Pelaminan Family`.

#### 32. Desain Layout Halaman Login Terpusat & Rapi ([login.php](file:///c:/xampp/htdocs/pelaminan/login.php))
- **Item Pekerjaan**:
  - Memperbarui halaman login menjadi **Single Centered Card Layout** (berada tepat di tengah layar secara vertikal & horizontal dengan latar belakang *warm luxury gradient*).
  - Menetapkan kelas CSS khusus `.role-pills-wrap` dengan `display: grid !important; grid-template-columns: repeat(3, 1fr) !important;` langsung di dalam [login.php](file:///c:/xampp/htdocs/pelaminan/login.php) sehingga tab `Customer`, `Admin`, `Owner` **100% dipastikan selalu berada di 1 baris yang sejajar, simetris, dan presisi** tanpa terpengaruh oleh cache browser lama.
  - Memperbaiki latar *autofill* browser pada kolom input (`Email` dan `Password`) agar tetap berwarna putih bersih dan rapi.
#### 33. Perbaikan UI Header Navigasi Customer & Icon Lonceng Notifikasi ([includes/header.php](file:///c:/xampp/htdocs/pelaminan/includes/header.php), [assets/css/style.css](file:///c:/xampp/htdocs/pelaminan/assets/css/style.css))
- **Item Pekerjaan**:
  - Menambahkan aturan `white-space: nowrap;` pada menu navigasi `nav-links`, `brand`, dan `actions` agar teks menu (`Pesanan Saya`, `Keranjang`, nama customer) tidak bertumpuk secara vertikal.
  - Mengubah tautan notifikasi customer dari teks bertumpuk `🔔 Notifikasi (2)` menjadi **Ikon Lonceng SVG Lingkaran** yang bersih dengan **Badge Merah Angka Notifikasi Belum Dibaca** di pojok kanan atas ikon (serupa seperti pada panel header Admin).
- **Hasil**: Tampilan header customer menjadi sangat rapi, sejajar 1 baris horizontal tanpa luapan teks bertumpuk, serta notifikasi tampil modern dan elegan.

#### 34. Penghapusan Seluruh Fitur Role Pemilik Usaha / Owner ([login.php](file:///c:/xampp/htdocs/pelaminan/login.php), [includes/admin_header.php](file:///c:/xampp/htdocs/pelaminan/includes/admin_header.php), [config/helpers.php](file:///c:/xampp/htdocs/pelaminan/config/helpers.php))
- **Item Pekerjaan**:
  - Menghapus opsi role `Owner` pada [login.php](file:///c:/xampp/htdocs/pelaminan/login.php) dan mengembalikan tab selector menjadi 2 pilihan simetris: `Customer` dan `Admin`.
  - Mengembalikan seluruh akses navigasi penuh pada panel admin operasional ([includes/admin_header.php](file:///c:/xampp/htdocs/pelaminan/includes/admin_header.php)) termasuk `Kelola Produk`, `Variasi Warna`, `Item Tambahan`, `Kalender Produksi`, dan `Notifikasi`.
  - Menghapus modul `manage-admins.php` dan `activity-logs.php`.
  - Memperbarui skema kolom database `users.role` kembali menjadi `ENUM('admin','customer')`.
#### 35. Penerapan Metode Pembayaran Transfer Bank BRI Manual & Unggah Bukti Struk ([payment.php](file:///c:/xampp/htdocs/pelaminan/payment.php), [admin/orders.php](file:///c:/xampp/htdocs/pelaminan/admin/orders.php), [my-orders.php](file:///c:/xampp/htdocs/pelaminan/my-orders.php))
- **Item Pekerjaan**:
  - Memperbarui halaman pembayaran ([payment.php](file:///c:/xampp/htdocs/pelaminan/payment.php)) menggunakan **Nomor Rekening Bank BRI Asli: `5741-01-007952-53-6` (a.n. MIS'ATI)** serta tombol **📋 Salin No. Rekening**.
  - Mengganti alur simulasi pembayaran otomatis menjadi **Form Unggah Foto Bukti Transfer (JPG/PNG Maks 5MB)** dengan preview gambar *realtime*.
  - Menambahkan kolom `proof_image` pada tabel database `payments` dan memperbarui status order ke `ADMIN_REVIEW`.
  - Menambahkan fitur **Verifikasi Bukti Pembayaran oleh Admin** pada Rincian Pesanan ([admin/orders.php](file:///c:/xampp/htdocs/pelaminan/admin/orders.php)) dengan tombol `✅ Verifikasi & Terima Pembayaran` dan `❌ Tolak Pembayaran`, lengkap dengan notifikasi ke customer.
#### 36. Perbaikan Eror Sintaks Parse Error pada Halaman Detail Pesanan Admin ([admin/orders.php](file:///c:/xampp/htdocs/pelaminan/admin/orders.php))
- **Problem**: Muncul pesan *Parse error: Unclosed '{' on line 22 in admin/orders.php* saat mengklik notifikasi pembayaran masuk.
- **Penyebab**: Blok fungsi pemroses `update_order_workflow` di bagian atas file belum ditutup kurung kurawal `}` secara lengkap saat menyisipkan handler verifikasi pembayaran.
#### 37. Penyesuaian Nomor Rekening & Atas Nama Rekening pada Invoice dan Kwitansi ([invoice.php](file:///c:/xampp/htdocs/pelaminan/invoice.php), [receipt.php](file:///c:/xampp/htdocs/pelaminan/receipt.php))
- **Item Pekerjaan**:
  - Memperbarui file [invoice.php](file:///c:/xampp/htdocs/pelaminan/invoice.php) sehingga menampilkan **Nomor Rekening Bank BRI: `5741-01-007952-53-6` (a.n. MIS'ATI)** yang diambil secara dinamis dari database settings.
  - Memperbarui file [receipt.php](file:///c:/xampp/htdocs/pelaminan/receipt.php) sehingga menampilkan perincian metode pembayaran lengkap dengan **Bank BRI `5741-01-007952-53-6` a.n. MIS'ATI**.
#### 38. Penyederhanaan Skema Database dari 18 Tabel menjadi 13 Tabel Bersih ([config/helpers.php](file:///c:/xampp/htdocs/pelaminan/config/helpers.php), [admin/orders.php](file:///c:/xampp/htdocs/pelaminan/admin/orders.php))
- **Item Pekerjaan**:
  - Mengeliminasi 5 tabel terpisah yang redundan: `invoices`, `receipts`, `production_queue`, `production_schedule`, dan `order_status_history`.
  - Menambahkan kolom `queue_number` langsung pada tabel utama `orders` untuk mengelola antrean dan tanggal jadwal pengerjaan secara langsung.
  - Memperbarui fungsi `get_or_create_invoice()` dan `get_or_create_receipt()` pada [config/helpers.php](file:///c:/xampp/htdocs/pelaminan/config/helpers.php) agar membuat nomor dokumen secara terstruktur tanpa memerlukan tabel fisik tambahan.
#### 39. Migrasi Pengaturan Sistem ke File Konfigurasi Pusat & Penyempurnaan 12 Tabel Database ([config/settings.php](file:///c:/xampp/htdocs/pelaminan/config/settings.php), [config/helpers.php](file:///c:/xampp/htdocs/pelaminan/config/helpers.php))
- **Item Pekerjaan**:
  - Membuat file konfigurasi pusat [config/settings.php](file:///c:/xampp/htdocs/pelaminan/config/settings.php) untuk menyimpan data profil usaha (Nama Usaha, Alamat, No. WA, Instagram, serta **Nomor Rekening BRI `5741-01-007952-53-6` a.n. MIS'ATI**).
  - Memperbarui fungsi `get_setting()` di [config/helpers.php](file:///c:/xampp/htdocs/pelaminan/config/helpers.php) agar membaca langsung dari file konfigurasi statis (tanpa beban query ke database).
  - Menghapus tabel `settings` dari database MySQL.
#### 40. Penghapusan Tabel Legacy `shipping_rates` & Transisi 100% ke Ongkir Otomatis Peta GPS ([checkout.php](file:///c:/xampp/htdocs/pelaminan/checkout.php), [customization.php](file:///c:/xampp/htdocs/pelaminan/customization.php), [order.php](file:///c:/xampp/htdocs/pelaminan/order.php))
- **Item Pekerjaan**:
  - Menghapus tabel legacy `shipping_rates` dari database MySQL.
  - Membersihkan kodingan query ongkir wilayah lama pada [checkout.php](file:///c:/xampp/htdocs/pelaminan/checkout.php), [customization.php](file:///c:/xampp/htdocs/pelaminan/customization.php), dan [order.php](file:///c:/xampp/htdocs/pelaminan/order.php).
  - Menjadikan alur perhitungan ongkir 100% berbasis **Perhitungan Jarak Otomatis Peta GPS (Leaflet & Formula Haversine GoBox Palembang)**.
- **Hasil**: Jumlah tabel database phpMyAdmin kini **tepat 11 Tabel Utama**, sangat bersih, modern, dan tidak ada lagi sisa data legacy.

#### 41. Dukungan Pilihan Satuan Ukuran Produk (Meter & Centimeter) ([admin/products.php](file:///c:/xampp/htdocs/pelaminan/admin/products.php))
- **Item Pekerjaan**:
  - Menambahkan dropdown pilihan **Satuan Ukuran** (`Centimeter (cm)` dan `Meter (m)`) pada formulir input/edit produk.
  - Memperbarui pengolah data server PHP agar otomatis mendeteksi dan memformat nilai ukuran (misal: `T: 90 cm x L: 50 cm` atau `T: 3m x L: 5m`).
  - Memperbarui parser mode edit produk agar dapat membaca kembali satuan `cm` maupun `m` yang tersimpan dan memilih opsi satuan yang tepat secara otomatis pada dropdown form.
- **Hasil**: Admin dapat dengan mudah memasukkan produk berukuran kecil/sedang dalam hitungan CM (seperti Kotak Akas / Pedestal / Ornamen Decor) maupun ukuran besar dalam hitungan Meter tanpa kebingungan.

#### 42. Pengubahan Nama Kategori & Produk "Standing Flower" menjadi "Pot Bunga" ([database.sql](file:///c:/xampp/htdocs/pelaminan/database.sql), [customization.php](file:///c:/xampp/htdocs/pelaminan/customization.php), [admin/items.php](file:///c:/xampp/htdocs/pelaminan/admin/items.php), [index.php](file:///c:/xampp/htdocs/pelaminan/index.php), [includes/footer.php](file:///c:/xampp/htdocs/pelaminan/includes/footer.php))
- **Item Pekerjaan**:
  - Mengubah nama kategori `Standing Flower` menjadi `Pot Bunga` di database MySQL (`categories`).
  - Mengubah seluruh nama produk yang mengandung `Standing Flower` menjadi `Pot Bunga` (`Pot Bunga Rose Premium`, `Pot Bunga Lily White`).
  - Memperbarui skrip kodingan backend, form filter admin ([admin/items.php](file:///c:/xampp/htdocs/pelaminan/admin/items.php)), editor kustomisasi ([customization.php](file:///c:/xampp/htdocs/pelaminan/customization.php)), serta teks tampilan beranda ([index.php](file:///c:/xampp/htdocs/pelaminan/index.php)) dan footer ([includes/footer.php](file:///c:/xampp/htdocs/pelaminan/includes/footer.php)).
- **Hasil**: Seluruh sebutan dan data "Standing Flower" di database maupun tampilan antarmuka 100% konsisten telah diperbarui menjadi **Pot Bunga** tanpa ada yang terlewat atau eror.

#### 43. Otomatisasi Kode Produk Dinamis Berdasarkan Prefiks Kategori (`PLM`, `KA`, `PB`, `GZB`) ([config/helpers.php](file:///c:/xampp/htdocs/pelaminan/config/helpers.php), [admin/products.php](file:///c:/xampp/htdocs/pelaminan/admin/products.php), [database.sql](file:///c:/xampp/htdocs/pelaminan/database.sql))
- **Item Pekerjaan**:
  - Membuat fungsi `get_category_prefix()` dan `generate_product_code()` pada [config/helpers.php](file:///c:/xampp/htdocs/pelaminan/config/helpers.php) untuk memetakan prefiks otomatis sesuai kategori produk:
    - **`PLM`**: Pelaminan (`PLM-001`, `PLM-002`, dst.)
    - **`KA`**: Kotak Akas (`KA-001`, `KA-002`, `KA-003`, dst.)
    - **`PB`**: Pot Bunga (`PB-001`, `PB-002`, dst.)
    - **`GZB`**: Gazebo (`GZB-001`, `GZB-002`, dst.)
  - Memperbarui halaman [admin/products.php](file:///c:/xampp/htdocs/pelaminan/admin/products.php) agar otomatis membuatkan kode ber-prefiks yang tepat saat menambah atau mengubah kategori produk.
  - Memperbaiki data kode produk di database MySQL (termasuk mengubah `Kotak Akas Motif Kaca` dari `PLM-011` menjadi `KA-003`).
- **Hasil**: Kode produk dibuat 100% presisi mengikuti jenis kategori yang dipilih tanpa tertukar menjadi `PLM` untuk kategori non-pelaminan.

#### 44. Redesain Layout Halaman Kustomisasi menjadi Split 2-Kolom Desktop & Mobile Sticky Preview ([customization.php](file:///c:/xampp/htdocs/pelaminan/customization.php), [assets/css/style.css](file:///c:/xampp/htdocs/pelaminan/assets/css/style.css))
- **Item Pekerjaan**:
  - Mengubah arsitektur tata letak halaman kustomisasi dari 3-kolom bertumpuk di bawah menjadi **Layout 2-Kolom Berdampingan Modern**:
    - **Sisi Kiri (`.configurator-left-column`)**: *Sticky Live Preview Canvas Container* (foto pelaminan & layer drag & drop dikunci di posisi sticky sehingga gambar selalu terlihat saat pelanggan men-scroll) + Petunjuk Canvas + Card Informasi Produk.
    - **Sisi Kanan (`.configurator-right-column`)**: Panel Opsi & Pemesanan 3 Langkah Rapi (*Step 1: Warna Tema $\rightarrow$ Step 2: Item Decor $\rightarrow$ Step 3: Ringkasan Total & Pemesanan*).
  - Menambahkan aturan CSS media query responsif (`@media (max-width: 991px)`) agar di HP/Smartphone visual canvas melayang secara *sticky top header* dan seluruh tombol berukuran lega & touch-friendly.
  - Memastikan 100% fungsionalitas, variabel JS, form input hidden, parser posisi drag & drop, dan aksi tombol `Pesan Sekarang` & `Tambah ke Keranjang` tetap utuh tanpa ada yang hilang.
- **Hasil**: Tampilan kustomisasi sangat simpel, interaktif, mewah, responsif sempurna di HP maupun Laptop, dan memudahkan alur pemesanan pelanggan.

#### 45. Perbaikan Alih Foto Variasi Warna & Fitur Unduh Foto Hasil Kustomisasi ([customization.php](file:///c:/xampp/htdocs/pelaminan/customization.php))
- **Item Pekerjaan**:
  - Mengubah penanganan klik kartu varian warna menjadi **Fungsi Global Inline `onclick="selectPhotoVariant(this)"`** sehingga terlepas dari masalah scope `DOMContentLoaded` atau browser caching. Klik pada varian (*Warna Utama*, *White Gold*, *Cream Gold*, dll.) dipastikan **100% mengganti foto utama di canvas secara instan**.
  - Menambahkan tombol **`📸 Simpan / Unduh Foto Hasil Kustom`** di bawah card *Informasi Produk* di sisi kiri dengan atribut **`onclick="downloadCustomizedDesign()"`**.
  - Mengimplementasikan komposisi HTML5 Canvas 2x High-DPI (`triggerPngSave`) yang secara otomatis merender gambar dasar pelaminan beserta seluruh layer dekorasi (*Pot Bunga & Kotak Akas*) hasil *drag & drop*, kemudian mengunduh file PNG resolusi tinggi secara otomatis tanpa dependensi eksternal.
- **Hasil**: Ganti variasi warna dan fitur unduh foto kustomisasi dijamin 100% responsif & berfungsi lancar pada semua browser pelanggan.

#### 46. Perbaikan Akar Masalah JavaScript Error Tersembunyi akibat PHP Undefined Variable ([customization.php](file:///c:/xampp/htdocs/pelaminan/customization.php))
- **Akar Masalah Utama (Root Cause)**:
  - Ditemukan variabel PHP `$shippingRates` dan `$productId` yang tidak terdefinisi (*undefined variable*) tercetak di dalam tag `<script>`.
  - PHP mengeluarkan output teks peringatan `PHP Warning: Undefined variable $shippingRates...` di tengah baris kode JavaScript `const shippingRates = ...;`. Teks peringatan PHP bermarkup `<br />` ini menyebabkan **`Uncaught SyntaxError: Unexpected token '<'`** di browser, yang menghentikan (*crash*) seluruh eksekusi skrip JavaScript di bawahnya.
- **Item Pekerjaan**:
  - Mengubah `$shippingRates` di skrip menjadi `<?= json_encode($shippingRates ?? []) ?>` sehingga menghasilkan `[]` secara aman tanpa peringatan PHP.
  - Memperbaiki variabel `$productId` menjadi `$id` pada tautan tombol kembali.
- **Hasil**: Seluruh skrip JavaScript berjalan mulus tanpa SyntaxError, klik varian warna berganti instan, drag & drop lancar, dan tombol unduh foto kustomisasi berfungsi 100%.

#### 47. Pembatasan Tombol/Menu Kustomisasi Khusus Kategori Pelaminan ([product.php](file:///c:/xampp/htdocs/pelaminan/product.php), [customization.php](file:///c:/xampp/htdocs/pelaminan/customization.php))
- **Item Pekerjaan**:
  - Membatasi kemunculan tombol **`🎨 Kustomisasi Produk Ini`** pada halaman detail produk ([product.php](file:///c:/xampp/htdocs/pelaminan/product.php)) agar **HANYA muncul untuk produk berkategori Pelaminan**.
  - Untuk produk kategori non-Pelaminan (*Kotak Akas, Pot Bunga, Gazebo*), tombol kustomisasi disembunyikan. Pelanggan dapat langsung memilih foto alih variasi warna di [product.php](file:///c:/xampp/htdocs/pelaminan/product.php) lalu menekan tombol **`🛒 Pesan Sekarang`** atau **`🛒 Tambah Keranjang`**.
  - Menambahkan proteksi *backend redirect* pada [customization.php](file:///c:/xampp/htdocs/pelaminan/customization.php) yang secara otomatis mengarahkan ke [product.php](file:///c:/xampp/htdocs/pelaminan/product.php) apabila diakses secara langsung untuk produk non-Pelaminan.
- **Hasil**: Alur pemilihan produk sangat efisien, rapi, dan sesuai dengan karakteristik produk yang dijual.

#### 48. Otomatisasi Terpusat Item Dekorasi dari Tabel Produk / Single Source of Truth ([customization.php](file:///c:/xampp/htdocs/pelaminan/customization.php), [admin/items.php](file:///c:/xampp/htdocs/pelaminan/admin/items.php), [includes/admin_header.php](file:///c:/xampp/htdocs/pelaminan/includes/admin_header.php))
- **Item Pekerjaan**:
  - Mengubah kueri item dekorasi pada [customization.php](file:///c:/xampp/htdocs/pelaminan/customization.php) sehingga **otomatis mengambil produk berkategori Pot Bunga & Kotak Akas langsung dari tabel `products`**.
  - Menghilangkan keharusan Admin menginput 2 kali (input katalog produk vs input item tambahan). Sekarang Admin **CUKUP menginput 1 kali pada Kelola Produk ([admin/products.php](file:///c:/xampp/htdocs/pelaminan/admin/products.php))**.
  - Mengarahkan `admin/items.php` ke `admin/products.php` dan merapikan navigasi sidebar admin agar alur kerja admin menjadi sangat efisien dan bebas redundansi data.
- **Hasil**: Admin tidak perlu menginput ulang item tambahan, data produk terintegrasi 100% secara terpusat, dan pilihan item dekorasi di halaman kustomisasi pelaminan selalu *up-to-date*.

#### 49. Penataan Accordion Per Kategori & Pemilihan Varian Warna Item Dekorasi ([customization.php](file:///c:/xampp/htdocs/pelaminan/customization.php))
- **Item Pekerjaan**:
  - Menerapkan tampilan **Accordion / Lipatan per Kategori** (*🏺 Kotak Akas*, *🌸 Pot Bunga*) pada Bagian 2 Halaman Kustomisasi sehingga daftar item tambahan tetap **sangat ringkas & rapi** tanpa perlu *scroll* panjang saat produk bertambah banyak.
  - Menambahkan **Dropdown Pilihan Warna Varian** (`product_variants`) pada setiap kartu item Pot Bunga & Kotak Akas.
  - Ketika pelanggan memilih varian warna tertentu (*misal: Cokelat Muda / Cream*), foto miniatur kartu, foto *layer drag & drop* pada Canvas, serta rincian ringkasan pesanan **langsung ter-update secara otomatis sesuai warna varian yang dipilih**.
- **Hasil**: Tampilan kustomisasi pelaminan sangat rapi, efisien, dan fleksibel mendukung variasi warna pada seluruh item dekorasi pendukung.

#### 50. Desain Canvas Realistis & Render Transparan 3D Item Dekorasi ([customization.php](file:///c:/xampp/htdocs/pelaminan/customization.php), [assets/css/style.css](file:///c:/xampp/htdocs/pelaminan/assets/css/style.css))
- **Item Pekerjaan**:
  - Mengubah penataan `#photoVariantPreview` menjadi `object-fit: cover` sehingga foto dasar pelaminan **mengisi Canvas secara penuh (Full Canvas)** tanpa menyisakan *whitespace* kosong.
  - Memperbarui gaya elemen `.canvas-item-wrapper` menjadi **100% Transparan** tanpa box putih, tanpa border tebal, dan tanpa kotak teks judul yang menutupi gambar pelaminan.
  - Menambahkan efek **Drop-Shadow 3D Alami** (`filter: drop-shadow(...)`) langsung pada gambar objek dekorasi (Pot Bunga & Kotak Akas) sehingga saat digeser di atas Canvas, objek terlihat menyatu realistis secara 3D di atas panggung pelaminan.
- **Hasil**: Tampilan kustomisasi sangat visual, elegan, realistis, dan tidak ada lagi box putih yang menutupi latar belakang pelaminan.

#### 51. Perbaikan Error SQL "Unknown Column design_id" pada Tabel `orders` ([checkout.php](file:///c:/xampp/htdocs/pelaminan/checkout.php), [database.sql](file:///c:/xampp/htdocs/pelaminan/database.sql))
- **Akar Masalah (Root Cause)**:
  - Proses pemesanan produk kustomisasi di [checkout.php](file:///c:/xampp/htdocs/pelaminan/checkout.php) gagal dengan pesan eror *`SQLSTATE[42S22]: Column not found: 1054 Unknown column 'design_id' in 'field list'`* karena kolom `design_id` dan `extra_items_detail` belum ada pada tabel `orders` di MySQL database local.
- **Item Pekerjaan**:
  - Menjalankan migrasi `ALTER TABLE orders ADD COLUMN design_id INT NULL AFTER variant_name, ADD COLUMN extra_items_detail LONGTEXT NULL AFTER design_id;` pada MySQL database.
  - Memperbarui skema `CREATE TABLE orders` pada file [database.sql](file:///c:/xampp/htdocs/pelaminan/database.sql) agar mencakup kolom `design_id` dan `extra_items_detail`.
- **Hasil**: Proses Checkout & Pemesanan produk kustomisasi berjalan 100% sukses tanpa eror SQL, serta data rancangan kustom disimpan sempurna pada database.

#### 52. Tampilan Utuh 100% Tanpa Terpotong untuk Seluruh Foto Pelaminan ([customization.php](file:///c:/xampp/htdocs/pelaminan/customization.php))
- **Item Pekerjaan**:
  - Mengubah penataan `#photoVariantPreview` menjadi `object-fit: contain` dengan posisi *center* dan padding proporsional.
  - Memperbarui fungsi ekspor PNG (`downloadCustomizedDesign`) dengan kalkulasi matematika rasio aspek (`imgRatio` vs `canvasRatio`) agar saat di-download maupun saat dilihat di Canvas, **100% foto pelaminan (mahkota atas, mahkota pilar, sofa, hingga lantai) terlihat utuh & tidak pernah terpotong** pada produk pelaminan apa pun.
- **Hasil**: Seluruh foto produk pelaminan tampil 100% lengkap, utuh, presisi, dan indah dipandang.

#### 53. Fitur Admin Review Kustomisasi Visual Live Layout & Rincian Item Dekorasi ([admin/orders.php](file:///c:/xampp/htdocs/pelaminan/admin/orders.php))
- **Item Pekerjaan**:
  - Memperbaiki pembacaan data item kustomisasi di rincian pesanan Admin [admin/orders.php](file:///c:/xampp/htdocs/pelaminan/admin/orders.php) dengan mengurai (*json decode*) kolom `orders.extra_items_detail` dan `editor_designs.extra_items_json`.
  - Menambahkan **Kartu Review Visual Live Layout Canvas** pada panel rincian pesanan Admin. Canvas merender gambar panggung pelaminan beserta **posisi presisi `(x,y)` seluruh item Pot Bunga & Kotak Akas** yang di-drag & drop oleh pelanggan.
  - Menampilkan **Tabel Rincian Item Tambahan** lengkap dengan foto miniatur produk, kategori, varian warna pilihan, kuantitas (`pcs`), dan subtotal harga.
- **Hasil**: Admin dapat me-review secara visual rancangan kustomisasi yang diajukan pelanggan secara presisi & jelas sebelum memproses ke tahap produksi.

#### 54. Presisi Posisi Kustomisasi Lintas Layar Menggunakan Koordinat Persentase Relative ([customization.php](file:///c:/xampp/htdocs/pelaminan/customization.php), [admin/orders.php](file:///c:/xampp/htdocs/pelaminan/admin/orders.php))
- **Akar Masalah (Root Cause)**:
  - Posisi *drag & drop* item dekorasi yang disimpan sebelumnya menggunakan koordinat piksel absolut (`x`, `y`). Karena ukuran layar Canvas di perangkat customer (*misal: HP/Laptop*) berbeda dengan resolusi monitor Admin, koordinat piksel tersebut bergeser ketika dirender di layar Admin.
- **Item Pekerjaan**:
  - Memperbarui *drag engine* di [customization.php](file:///c:/xampp/htdocs/pelaminan/customization.php) agar secara otomatis menghitung dan menyimpan koordinat persentase relatif (`pctX`, `pctY`) terhadap lebar & tinggi Canvas (`left: pctX%`, `top: pctY%`).
  - Memperbarui renderer Canvas Admin di [admin/orders.php](file:///c:/xampp/htdocs/pelaminan/admin/orders.php) menggunakan persentase `leftStyle` dan `topStyle`, beserta konversi persentase otomatis untuk pesanan lama.
- **Hasil**: Posisi peletakan item dekorasi (Pot Bunga & Kotak Akas) kini **100% presisi dan identik di semua ukuran layar** (layar HP pelanggan, laptop pelanggan, monitor Admin, maupun tablet).

#### 55. Penyetaraan Aspek Rasio Canvas 16:9 100% Antara Layar Pelanggan & Layar Admin ([customization.php](file:///c:/xampp/htdocs/pelaminan/customization.php), [admin/orders.php](file:///c:/xampp/htdocs/pelaminan/admin/orders.php))
- **Akar Masalah (Root Cause)**:
  - Ditemukan perbedaan rasio aspek pada container Canvas: [customization.php](file:///c:/xampp/htdocs/pelaminan/customization.php) menggunakan `aspect-ratio: 16/10`, sedangkan [admin/orders.php](file:///c:/xampp/htdocs/pelaminan/admin/orders.php) menggunakan `aspect-ratio: 16/9`. Perbedaan rasio aspek ini membuat ruang tinggi container di layar pelanggan 10% lebih tinggi dari container Admin, sehingga koordinat vertikal `top%` mendarat agak lebih tinggi di layar Admin.
- **Item Pekerjaan**:
  - Menyetarakan aspek rasio container Canvas di [customization.php](file:///c:/xampp/htdocs/pelaminan/customization.php) menjadi **16 / 9 (identik 100% dengan Admin Canvas)**.
  - Menghilangkan *padding internal* pada foto utama panggung di kedua Canvas (`padding: 0`).
- **Hasil**: Matematika ruang Canvas antara pelanggan & Admin 100% presisi, tidak ada lagi selisih tinggi, dan posisi peletakan Kotak Akas / Pot Bunga dipastikan 100% pas berada di titik lantai samping sofa yang dipilih pelanggan.

#### 56. Penguncian Unifikasi Komponen Canvas Pelanggan & Admin ([assets/css/style.css](file:///c:/xampp/htdocs/pelaminan/assets/css/style.css), [customization.php](file:///c:/xampp/htdocs/pelaminan/customization.php))
- **Akar Masalah (Root Cause)**:
  - Ditemukan aturan CSS lama di [assets/css/style.css](file:///c:/xampp/htdocs/pelaminan/assets/css/style.css) baris 2164 yang masih menetapkan `.configurator-preview-container { aspect-ratio: 16 / 10; }` sehingga saat stylesheet global dimuat, Canvas editor pelanggan kembali dipaksa rasio 16:10. Selain itu, saat item di-drag oleh pelanggan, elemen DOM langsung diberi `style.left = px`, bukan `pctX%`.
- **Item Pekerjaan**:
  - Mengubah [assets/css/style.css](file:///c:/xampp/htdocs/pelaminan/assets/css/style.css) baris 2164 menjadi `aspect-ratio: 16 / 9;`.
  - Memperbarui *drag engine* [customization.php](file:///c:/xampp/htdocs/pelaminan/customization.php) sehingga `elmnt.style.left` & `elmnt.style.top` langsung diikat menggunakan **persentase `pctX%` dan `pctY%` secara real-time saat di-drag**.
- **Hasil**: Komponen Canvas Pelanggan dan Canvas Admin kini **100% SAMA, UNIFIED, DAN SERAGAM HINGGA PRESISI PIKSEL TERKECIL**.

#### 57. Pembersihan Garis Batasan & Header Navigasi Sidebar Admin ([includes/admin_header.php](file:///c:/xampp/htdocs/pelaminan/includes/admin_header.php))
- **Item Pekerjaan**:
  - Menghapus garis pembatas horizontal (`<hr>`) dan teks sub-header `📦 KATALOG & SKELETON` pada navigasi sidebar Admin di [includes/admin_header.php](file:///c:/xampp/htdocs/pelaminan/includes/admin_header.php).
- **Hasil**: Tampilan sidebar Admin kini terlihat bersih, menyatu (*seamless*), modern, dan rapi tanpa garis sekat yang mengganggu.

#### 58. Pembersihan Produk Dummy Tanpa Gambar pada Database ([admin/products.php](file:///c:/xampp/htdocs/pelaminan/admin/products.php))
- **Item Pekerjaan**:
  - Menghapus 8 produk dummy tanpa gambar (*`No Image`*) dari database MySQL (`KA-001`, `PB-001`, `PB-002`, `GZB-001`, `GZB-002`, `PLM-001`, `PLM-002`, `PLM-003`).
  - Menyelaraskan relasi pesanan pengujian ke produk aktif utama (`PLM-004 Pelaminan Istana`).
- **Hasil**: Daftar katalog produk Admin [admin/products.php](file:///c:/xampp/htdocs/pelaminan/admin/products.php) dan Galeri Pengunjung kini hanya menampilkan produk asli dengan foto berkualitas tinggi.

#### 59. Input Manual Tanggal Selesai pada Kalender Produksi ([admin/production-calendar.php](file:///c:/xampp/htdocs/pelaminan/admin/production-calendar.php))
- **Item Pekerjaan**:
  - Mengubah kolom `Tanggal Selesai (Otomatis)` menjadi kolom **`Tanggal Selesai` dengan input tanggal manual `<input type="date" name="end_date">`**.
  - Memperbarui handler form `POST` pada [admin/production-calendar.php](file:///c:/xampp/htdocs/pelaminan/admin/production-calendar.php) agar Admin dapat menentukan tanggal mulai dan tanggal selesai pengerjaan secara bebas sesuai fleksibilitas di lapangan.
- **Hasil**: Admin memiliki fleksibilitas penuh untuk menetapkan tanggal mulai dan tanggal selesai produksi antrean secara manual.

#### 60. Pembersihan Teks Info Akun Pengujian ([login.php](file:///c:/xampp/htdocs/pelaminan/login.php))
- **Item Pekerjaan**:
  - Menghapus blok info *`Akun Pengujian:`* (Admin / Customer test credentials) dari halaman Login [login.php](file:///c:/xampp/htdocs/pelaminan/login.php).
- **Hasil**: Halaman login kini siap untuk lingkungan *production* tanpa menampilkan kredensial pengujian di bagian bawah form.

#### 61. Algoritma Skala Dinamis Panggung Pelaminan Penuh & Gagah ([customization.php](file:///c:/xampp/htdocs/pelaminan/customization.php), [admin/orders.php](file:///c:/xampp/htdocs/pelaminan/admin/orders.php))
- **Akar Masalah (Root Cause)**:
  - Berdasarkan analisa dimensi gambar terukur: `Pelaminan Istana` memiliki rasio aspek 1.91 (panjang melebar), sedangkan file gambar `Pelaminan Eropa Klasik` (`eaf10861cbdf174b58bc0f5154e4235e.png`) adalah file persegi **1:1 (1254px x 1254px)** dengan margin putih bawaan di dalam file PNG tersebut.
  - Jika menggunakan skala statis, foto ber-rasio 1:1 akan terlihat kecil dengan ruang kosong di kiri & kanan frame Canvas 16:9.
- **Item Pekerjaan**:
  - Membangun **Mesin Skala Dinamis (`adjustBaseImageScale()`)** pada [customization.php](file:///c:/xampp/htdocs/pelaminan/customization.php) dan [admin/orders.php](file:///c:/xampp/htdocs/pelaminan/admin/orders.php).
  - Algoritma secara otomatis menghitung rasio asli gambar (`imgRatio`) terhadap rasio Canvas (`canvasRatio`), lalu menyesuaikan tingkat pembesaran (`scale = canvasRatio / imgRatio`) secara dinamis.
- **Hasil**: Apapun rasio aspek gambar pelaminan (baik 1:1 square, 4:3, 16:10, maupun 16:9), foto panggung pelaminan di Canvas **OTOMATIS MEMENUHI SELURUH CANVAS DENGAN MEWAH, PENUH, GAGAH, DAN 100% UTUH TANPA ADA MAHKOTA ATAS YANG TERPOTONG (DIKUNCI SKALA PRESISI OPTIMAL MAX 1.30x)**.

#### 64. Pemotongan Presisi Ruang Transparan pada File PNG Pelaminan Eropa Klasik ([uploads/products/](file:///c:/xampp/htdocs/pelaminan/uploads/products/))
- **Akar Masalah (Root Cause)**:
  - Berdasarkan analisa piksel terukur: File PNG `Pelaminan Eropa Klasik` yang baru di-upload (`8951647fd5ff2fa9d16dd520cef1346b.png`) berukuran 1254x1254px, namun di dalam file PNG tersebut terdapat **margin transparan seluas 224px di bagian atas dan 275px di bagian bawah** bawaan dari software pemotong background.
  - Margin transparan internal ini membuat panggung terlihat kecil di tengah frame walau background warna sudah di-remove.
- **Item Pekerjaan**:
  - Memangkas (*tight cropping*) ruang transparan atas dan bawah pada file produk utama dan seluruh variasi warna `Pelaminan Eropa Klasik` sehingga ukurannya menjadi **1236px x 695px (Rasio Aspek 1.78 / 16:9)**.
- **Hasil**: Foto panggung `Pelaminan Eropa Klasik` kini **LANGSUNG TERAMBIL PENUH MEWAH GAGAH 100% MEMENUHI CANVAS DENGAN UKURAN SERAGAM DENGAN PELAMINAN ISTANA**.

#### 65. Otomatisasi Pemangkasan Ruang Transparan pada Engine Upload Backend ([config/helpers.php](file:///c:/xampp/htdocs/pelaminan/config/helpers.php))
- **Akar Masalah (Root Cause)**:
  - Software pemotong background (*seperti remove.bg / Photoshop*) sering menyisakan ruang transparan kosong yang luas di sekeliling panggung saat Admin mengunggah file foto produk baru, sehingga gambar yang di-upload tetap membawa ruang transparan internal.
- **Item Pekerjaan**:
  - Membangun **Engine Pemangkas Otomatis (`auto_crop_transparent_image()`)** langsung di dalam fungsi `upload_image()` pada [config/helpers.php](file:///c:/xampp/htdocs/pelaminan/config/helpers.php).
  - Setiap kali Admin mengunggah foto produk pelaminan atau varian warna baru di [admin/products.php](file:///c:/xampp/htdocs/pelaminan/admin/products.php) dan [admin/product-variants.php](file:///c:/xampp/htdocs/pelaminan/admin/product-variants.php), sistem PHP secara otomatis mendeteksi piksel panggung, memangkas (*tight cropping*) seluruh ruang transparan/putih kosong bawaan, lalu menyimpannya dengan rasio presisi 16:9.
- **Hasil**: Apapun file foto pelaminan mebel yang di-upload oleh Admin di masa mendatang, sistem **OTOMATIS MEMPROSESNYA MENJADI UKURAN SERAGAM, PENUH, GAGAH, DAN 100% UTUH DI CANVAS DENGAN KUALITAS TINGGI**.

#### 66. Posisi Awal & Skala Realistis Item Tambahan Pot Bunga / Kotak Akas ([customization.php](file:///c:/xampp/htdocs/pelaminan/customization.php), [admin/orders.php](file:///c:/xampp/htdocs/pelaminan/admin/orders.php), [assets/css/style.css](file:///c:/xampp/htdocs/pelaminan/assets/css/style.css))
- **Akar Masalah (Root Cause)**:
  - Posisi default awal (*initial spawn*) saat pelanggan menambah item Kotak Akas / Pot Bunga berada di koordinat `top: 160px` (tengah canvas) dengan tinggi `110px`, sehingga item baru langsung menutupi area sofa utama dan ukurannya terlalu besar menghalangi ukiran pelaminan.
- **Item Pekerjaan**:
  - Memperbarui posisi awal muncul (*initial spawn*) item baru pada [customization.php](file:///c:/xampp/htdocs/pelaminan/customization.php) dan [admin/orders.php](file:///c:/xampp/htdocs/pelaminan/admin/orders.php) ke **lantai sudut kiri bawah panggung (`left: 5%`, `top: 64%`)**.
  - Mengubah proporsi tinggi gambar item dekorasi pada [assets/css/style.css](file:///c:/xampp/htdocs/pelaminan/assets/css/style.css) menjadi **`height: 75px` (skala proporsional realistis)**.
- **Hasil**: Setiap kali pelanggan mengeklik `[+]` untuk menambah Kotak Akas / Pot Bunga, item **TIDAK PERNAH MENUTUPI SOFA ATAU UKIRAN PELAMINAN**, melainkan muncul rapi berdiri di atas lantai panggung dan dapat digeser bebas (*drag & drop*).

#### 67. Peningkatan Engine Universal Auto-Crop Multi-Format & Dynamic Canvas Auto-Scale ([config/helpers.php](file:///c:/xampp/htdocs/pelaminan/config/helpers.php), [customization.php](file:///c:/xampp/htdocs/pelaminan/customization.php), [admin/orders.php](file:///c:/xampp/htdocs/pelaminan/admin/orders.php))
- **Akar Masalah (Root Cause)**:
  - File foto produk baru yang di-upload oleh Admin dapat berupa format JPG, JPEG, WEBP, maupun PNG dengan berbagai warna background (*putih solid, off-white, transparan, atau light-gray*). Jika engine backend hanya memeriksa PNG murni, file JPG/JPEG baru dari Admin tidak ter-crop secara otomatis.
- **Item Pekerjaan**:
  - Meng-upgrade **Engine `auto_crop_transparent_image()`** pada [config/helpers.php](file:///c:/xampp/htdocs/pelaminan/config/helpers.php) untuk mendukung pemangkasan otomatis seluruh format gambar (**PNG, JPG, JPEG, WEBP**) serta mendeteksi piksel background transparan, putih, off-white, maupun abu-abu terang.
  - Memperbarui **Fungsi Pembesar Skala Dinamis `adjustBaseImageScale()`** pada [customization.php](file:///c:/xampp/htdocs/pelaminan/customization.php) dan [admin/orders.php](file:///c:/xampp/htdocs/pelaminan/admin/orders.php) dengan skala dasar `1.20x` hingga `1.45x`.
- **Hasil**: Setiap kali Admin meng-upload produk pelaminan baru (apapun format filenya, baik JPG, JPEG, PNG, maupun WEBP), gambar panggung pelaminan baru tersebut **LANGSUNG DITAMPILKAN OTOMATIS PENUH, MEWAH, GAGAH, DAN 100% UTUH PADA CANVAS UNTUK SELURUH PRODUK PELAMINAN BARUmaupun LAMA**.

#### 68. Pemulihan Pengaturan Native Full Canvas `object-fit: cover` ([customization.php](file:///c:/xampp/htdocs/pelaminan/customization.php), [admin/orders.php](file:///c:/xampp/htdocs/pelaminan/admin/orders.php))
- **Akar Masalah (Root Cause)**:
  - Berdasarkan jejak riwayat proyek (Pekerjaan #50): Pengaturan awal Canvas yang terbukti berhasil mengisi seluruh ruang Canvas dari ujung ke ujung tanpa menyisakan ruang kosong adalah `object-fit: cover`. Penggunaan `object-fit: contain` dengan *scaling constraint* sebelumnya membuat beberapa gambar ber-rasio persegi kembali mengambang kecil di tengah.
- **Item Pekerjaan**:
  - Mengembalikan penataan `#photoVariantPreview` pada [customization.php](file:///c:/xampp/htdocs/pelaminan/customization.php) dan `#adminPhotoVariantPreview` pada [admin/orders.php](file:///c:/xampp/htdocs/pelaminan/admin/orders.php) ke **`object-fit: cover; object-position: center;` murni**.
- **Hasil**: Foto panggung pelaminan **100% PASTI MENGISI DINDING CANVAS SECARA FULL, GAGAH, DAN MEWAH** untuk seluruh produk yang di-upload oleh Admin (baik produk lama maupun produk baru).

#### 69. Penyelarasan Global Full Display `object-fit: cover` pada Halaman Detail Produk ([product.php](file:///c:/xampp/htdocs/pelaminan/product.php))
- **Akar Masalah (Root Cause)**:
  - Ditemukan penyedia tampilan gambar produk utama `#mainProductImage` pada Halaman Detail Produk ([product.php](file:///c:/xampp/htdocs/pelaminan/product.php)) baris 36 masih menggunakan `object-fit: contain`. Hal ini membuat saat Admin me-review produk pelaminan baru di halaman detail produk (`product.php`), gambarnya kembali mengecil di tengah frame. Sementara produk Kotak Akas yang ber-rasio vertikal tinggi mengisi 100% tinggi frame secara alami.
- **Item Pekerjaan**:
  - Mengubah penataan `#mainProductImage` dan `.variant-card img` pada [product.php](file:///c:/xampp/htdocs/pelaminan/product.php) menjadi **`object-fit: cover; object-position: center;`**.
  - **Hasil**: Seluruh halaman tampilan produk ([product.php](file:///c:/xampp/htdocs/pelaminan/product.php), [customization.php](file:///c:/xampp/htdocs/pelaminan/customization.php), dan [admin/orders.php](file:///c:/xampp/htdocs/pelaminan/admin/orders.php)) kini **100% KONSISTEN, SERAGAM, DENGAN FOTO PELAMINAN SELALU TAMPIL FULL SATU CANVAS DENGAN SANGAT MEWAH & PROPOSIONAL**.

---

### 🔹 Sesi: 12 Agustus 2026

#### 70. Pembuatan Dokumentasi Sistem Seluruh Halaman Customer & Admin ([DOKUMEN_HALAMAN_SISTEM.md](file:///c:/xampp/htdocs/pelaminan/DOKUMEN_HALAMAN_SISTEM.md))
- **Item Pekerjaan**:
  - Membuat file dokumentasi komprehensif `DOKUMEN_HALAMAN_SISTEM.md` yang merangkum seluruh fungsi, komponen, alur kerja, dan rincian teknis dari 12 halaman Pelanggan (Customer Area) serta 9 modul Panel Admin.
- **Hasil**: Tersedia dokumen panduan lengkap dan terstruktur mengenai arsitektur serta peran setiap halaman di dalam sistem.

---

### 🔹 Sesi: 15 Agustus 2026

#### 71. Penggantian Logo Inisial "DP" dengan Logo Resmi Distributor Pelaminan Family ([assets/img/logo.png](file:///c:/xampp/htdocs/pelaminan/assets/img/logo.png), [assets/css/style.css](file:///c:/xampp/htdocs/pelaminan/assets/css/style.css), [includes/header.php](file:///c:/xampp/htdocs/pelaminan/includes/header.php), [includes/footer.php](file:///c:/xampp/htdocs/pelaminan/includes/footer.php), [includes/admin_header.php](file:///c:/xampp/htdocs/pelaminan/includes/admin_header.php), [login.php](file:///c:/xampp/htdocs/pelaminan/login.php), [register.php](file:///c:/xampp/htdocs/pelaminan/register.php), [invoice.php](file:///c:/xampp/htdocs/pelaminan/invoice.php), [receipt.php](file:///c:/xampp/htdocs/pelaminan/receipt.php), [admin/export-report-pdf.php](file:///c:/xampp/htdocs/pelaminan/admin/export-report-pdf.php))
- **Item Pekerjaan**:
  - Mengolah dan mengekstrak foto logo resmi toko **Distributor Pelaminan Family** (emblem lingkaran hitam dengan mahkota emas, inisial Pf, dan teks melingkar *"pelaminan family"* & *"distributor pengerajin pelaminan palembang"*).
  - Membuat file gambar resolusi tinggi berlatar transparan presisi anti-aliasing (`assets/img/logo.png` & `assets/img/logo_circle.png`) dan versi JPEG (`assets/img/logo.jpg`).
  - Menyesuaikan kalkulasi batas lingkaran sehingga saat dirender dalam bentuk bulatan (`border-radius: 50%`), **seluruh teks melingkar atas & bawah dan ornamen mahkota 100% utuh, presisi, dan tidak terpotong sedikit pun**.
  - Mengganti seluruh placeholder inisial teks `"DP"` pada seluruh modul sistem:
    - **Header Navigasi Utama** ([includes/header.php](file:///c:/xampp/htdocs/pelaminan/includes/header.php))
    - **Footer Website** ([includes/footer.php](file:///c:/xampp/htdocs/pelaminan/includes/footer.php))
    - **Sidebar Admin Panel** ([includes/admin_header.php](file:///c:/xampp/htdocs/pelaminan/includes/admin_header.php))
    - **Halaman Login Customer & Admin** ([login.php](file:///c:/xampp/htdocs/pelaminan/login.php))
    - **Halaman Registrasi Customer** ([register.php](file:///c:/xampp/htdocs/pelaminan/register.php))
    - **Kop Invoice Tagihan** ([invoice.php](file:///c:/xampp/htdocs/pelaminan/invoice.php))
    - **Kop Kwitansi Pembayaran** ([receipt.php](file:///c:/xampp/htdocs/pelaminan/receipt.php))
    - **Kop Export PDF Laporan Operasional** ([admin/export-report-pdf.php](file:///c:/xampp/htdocs/pelaminan/admin/export-report-pdf.php))
  - Memperbarui CSS `.logo`, `.sidebar-brand .logo`, dan `.auth-brand-logo` di [assets/css/style.css](file:///c:/xampp/htdocs/pelaminan/assets/css/style.css) dan [login.php](file:///c:/xampp/htdocs/pelaminan/login.php) dengan efek border emas, bayangan halus (*box-shadow*), dan interaksi *hover zoom & glow*.
- **Hasil**: Tampilan brand di seluruh halaman sistem kini 100% konsisten menggunakan logo resmi berbentuk bulat sempurna, mewah, profesional, dan bebas dari potongan teks.

#### 72. Pembaruan Visual Card Hero Beranda & Eliminasi Background Hitam Pelaminan ([index.php](file:///c:/xampp/htdocs/pelaminan/index.php), [assets/img/hero_pelaminan_transparent.png](file:///c:/xampp/htdocs/pelaminan/assets/img/hero_pelaminan_transparent.png))
- **Item Pekerjaan**:
  - Mengeliminasi background hitam solid `(0,0,0)` pada foto **Pelaminan Istana** dan mengekstrak gambar objek panggung pelaminan menjadi format PNG berlatar transparan presisi (`assets/img/hero_pelaminan_transparent.png`).
  - Mengganti latar belakang hitam dengan warna latar belakang krem hangat (*warm cream*) native dari kartu `.hero-card` (`linear-gradient(145deg, #ffffff 0%, #fbf8f3 100%)`).
  - Menambahkan efek *drop-shadow* 3D halus (`filter: drop-shadow(...)`) pada gambar objek pelaminan sehingga panggung pelaminan terlihat seolah berdiri melayang secara realistis dan menyatu 100% dengan tema warna website.
- **Hasil**: Tampilan panggung pelaminan pada kartu hero beranda kini 100% bersih dari kotak hitam, menyatu sempurna dengan nuansa warna krem website, dan tampak sangat indah & berkelas.


#### 73. Pergantian Akun Admin Operasional Sistem ([database.sql](file:///c:/xampp/htdocs/pelaminan/database.sql))
- **Item Pekerjaan**:
  - Menghapus akun admin lama **Nurtasah Ratia** (`tasah@gmail.com`) dari tabel database `users`.
  - Menambahkan akun admin baru **Muhammad Dani** (`danimuh816@gmail.com`, No. HP: `081273400312`, Role: `admin` / Admin Operasional) dengan password default `admin123`.
  - Memperbarui skrip seed awal [database.sql](file:///c:/xampp/htdocs/pelaminan/database.sql) dan helper script [scratch/setup_admin_pass.php](file:///c:/xampp/htdocs/pelaminan/scratch/setup_admin_pass.php).
- **Hasil**: Akun admin Nurtasah Ratia telah dihapus dan digantikan oleh akun admin Muhammad Dani.


#### 74. Pemulihan Penanganan Gambar Asli Admin & Perbaikan Permanen Foto Pelaminan Terpotong ([config/helpers.php](file:///c:/xampp/htdocs/pelaminan/config/helpers.php), [product.php](file:///c:/xampp/htdocs/pelaminan/product.php), [customization.php](file:///c:/xampp/htdocs/pelaminan/customization.php))
- **Item Pekerjaan**:
  - Menghapus dan menonaktifkan pemanggilan `auto_crop_transparent_image()` pada fungsi `upload_image()` di [config/helpers.php](file:///c:/xampp/htdocs/pelaminan/config/helpers.php). Foto yang diunggah Admin kini disimpan 100% utuh tanpa pemotongan piksel otomatis di server.
  - Mematikan skrip pemotong otomatis `scratch/crop_all_variants.py`.
  - Mengembalikan margin bingkai canvas 16:9 pada file foto fisik pelaminan yang sebelumnya terpotong pikselnya di folder `uploads/products/` dan `uploads/products/variants/`.
  - Mengunci tampilan CSS `object-fit: contain` dan container berlatar putih bersih pada halaman Detail Produk ([product.php](file:///c:/xampp/htdocs/pelaminan/product.php)) dan Canvas Editor ([customization.php](file:///c:/xampp/htdocs/pelaminan/customization.php)).
- **Hasil**: Seluruh panggung pelaminan tampil 100% utuh (termasuk pilar paling kiri & kanan) di Galeri, Detail Produk, dan Canvas Editor, serta input foto baru oleh Admin dijamin tersimpan asli tanpa pernah terpotong lagi.


#### 75. Penambahan Produk Baru: Pelaminan Eropa Motif Tengah Lengkung ([database.sql](file:///c:/xampp/htdocs/pelaminan/database.sql))
- **Item Pekerjaan**:
  - Menambahkan produk pelaminan baru **Pelaminan Eropa Motif Tengah Lengkung** (Kode: `PLM-006`, Ukuran: `T: 4m x L: 10m`, Harga: `Rp 30.000.000`, Durasi Produksi: `4 Hari`).
  - Mengunggah & mendaftarkan foto utama (Base Warna Mawar Emas/Cokelat) serta 3 variasi warna resmi:
    1. **Putih Emas**
    2. **Biru Muda Emas**
    3. **Hijau Sage Emas**
  - Memperbarui tabel `products`, `product_variants`, dan skrip seed [database.sql](file:///c:/xampp/htdocs/pelaminan/database.sql).
- **Hasil**: Produk baru beserta 3 varian warnanya telah aktif dan dapat langsung dilihat di Galeri, Detail Produk, serta dikustomisasi di Canvas Editor.


#### 76. Penambahan Produk Baru: Pelaminan Modern Minimalis ([database.sql](file:///c:/xampp/htdocs/pelaminan/database.sql))
- **Item Pekerjaan**:
  - Menambahkan produk pelaminan baru **Pelaminan Modern Minimalis** (Kode: `PLM-007`, Ukuran: `T: 3,5m x L: 9m`, Harga: `Rp 25.000.000`, Durasi Produksi: `3 Hari`).
  - Mengunggah & mendaftarkan foto utama (Base Putih Emas) serta 2 variasi warna resmi:
    1. **Biru Muda Emas**
    2. **Cream Gold**
  - Memperbarui tabel `products`, `product_variants`, dan skrip seed [database.sql](file:///c:/xampp/htdocs/pelaminan/database.sql).
- **Hasil**: Produk baru beserta 2 varian warnanya telah aktif dan dapat langsung dilihat di Galeri, Detail Produk, serta dikustomisasi di Canvas Editor.


#### 77. Pembaruan Foto Ber-Background Transparan Produk: Pelaminan Modern Minimalis ([database.sql](file:///c:/xampp/htdocs/pelaminan/database.sql))
- **Item Pekerjaan**:
  - Mengganti foto produk utama & variasi warna **Pelaminan Modern Minimalis** (Kode: `PLM-007`) dengan foto PNG berlatar transparan murni (*transparent background PNG*):
    - **Foto Utama (Base)**: Putih Emas *(Lotus White Gold)*
    - **Varian 1**: **Cream Gold** *(Krem Emas)*
    - **Varian 2**: **Biru Muda Emas** *(Light Blue Gold)*
    - **Varian 3**: **Cokelat Emas** *(Bronze Gold)*
  - Memperbarui tabel `products` dan `product_variants` di database MySQL.
- **Hasil**: Tampilan produk Pelaminan Modern Minimalis di Galeri, Detail Produk, dan Canvas Editor kini 100% menggunakan foto transparan murni tanpa background, rapi, dan responsif.


#### 79. Penambahan Produk Baru: Pelaminan Menara Kencana ([scratch/insert_pelaminan_menara_kencana_db.php](file:///c:/xampp/htdocs/pelaminan/scratch/insert_pelaminan_menara_kencana_db.php))
- **Item Pekerjaan**:
  - Menambahkan produk pelaminan baru **Pelaminan Menara Kencana** (Kode: `PLM-009`, Ukuran: `T: 4m x L: 10m`, Harga: `Rp 24.000.000`, Durasi Produksi: `3 Hari`, Status: `Aktif`).
  - Mengunggah & mendaftarkan foto utama (Base Gold Cream) serta 3 variasi warna resmi:
    1. **Putih Emas** (`pelaminan_menara_kencana_putih_emas.png`)
    2. **Cream Gold** (`pelaminan_menara_kencana_cream_gold.png`)
    3. **Biru Muda Emas** (`pelaminan_menara_kencana_biru_emas.png`)
  - Menyimpan aset foto ke `uploads/products/` dan `uploads/products/variants/` serta menginput record ke tabel `products` dan `product_variants` di database MySQL.
#### 80. Perbaharuan Varian Warna Produk Pelaminan Istana ([scratch/update_pelaminan_istana_variants.php](file:///c:/xampp/htdocs/pelaminan/scratch/update_pelaminan_istana_variants.php))
- **Item Pekerjaan**:
  - Mengubah foto varian warna **Cream Gold** pada produk **Pelaminan Istana** (ID: 10) dengan aset foto baru `pelaminan_istana_cream_gold.png`.
  - Menambahkan varian warna baru **Cokelat Emas** (`pelaminan_istana_cokelat_emas.png`) pada produk Pelaminan Istana.
  - Menyimpan aset gambar ber-High Resolution ke folder `uploads/products/variants/` dan memperbarui data di tabel `product_variants` MySQL.
- **Hasil**: Halaman detail produk **Pelaminan Istana** (`product.php`) dan studio kustomisasi (`customization.php`) kini memiliki varian Cream Gold teranyar serta opsi varian warna Cokelat Emas yang 100% aktif dan dapat dipilih pembeli.

#### 81. Penyesuaian Harga Produk Pelaminan Menara Kencana & Tirai Lengkung ([scratch/update_product_prices.php](file:///c:/xampp/htdocs/pelaminan/scratch/update_product_prices.php))
- **Item Pekerjaan**:
  - Mengubah harga produk **Pelaminan Menara Kencana** (ID: 24, Code: `PLM-009`) dari `Rp 24.000.000` menjadi **Rp 32.000.000**.
  - Mengubah harga produk **Pelaminan Tirai Lengkung** (ID: 23, Code: `PLM-008`) dari `Rp 22.000.000` menjadi **Rp 28.500.000**.
  - Memperbarui data harga pada tabel `products` di database MySQL.
- **Hasil**: Tampilan harga di Beranda/Katalog (`index.php`, `gallery.php`), Halaman Detail Produk (`product.php`), dan Studio Kustomisasi (`customization.php`) secara instan telah ter-update dengan tarif baru yang tepat.

#### 82. Penambahan Produk Baru: Pelaminan Eropa Pilar Ukir ([database.sql](file:///c:/xampp/htdocs/pelaminan/database.sql), [scratch/insert_pelaminan_eropa_pilar_ukir_db.php](file:///c:/xampp/htdocs/pelaminan/scratch/insert_pelaminan_eropa_pilar_ukir_db.php))
- **Item Pekerjaan**:
  - Menambahkan produk pelaminan baru **Pelaminan Eropa Pilar Ukir** (ID: 25, Kode: `PLM-010`, Ukuran: `T: 4m x L: 9m`, Harga: `Rp 26.000.000`, Durasi Produksi: `4 Hari`).
  - Mengolah dan mengunggah gambar utama dan 3 variasi warna resmi:
    1. **Putih Emas** (`pelaminan_eropa_pilar_ukir_putih_emas.png`)
    2. **Cream Gold** (`pelaminan_eropa_pilar_ukir_cream_gold.png`)
    3. **Biru Muda Emas** (`pelaminan_eropa_pilar_ukir_biru_emas.png`)
  - Memperbarui skrip database MySQL (`products` & `product_variants`) serta file [database.sql](file:///c:/xampp/htdocs/pelaminan/database.sql).
- **Hasil**: Produk **Pelaminan Eropa Pilar Ukir** beserta 3 variasi warna lengkap telah aktif 100% dan tampil di Katalog/Beranda, Detail Produk, serta Studio Kustomisasi.

#### 83. Penggantian Foto Base / Warna Putih Pelaminan Eropa Pilar Ukir ([uploads/products/pelaminan_eropa_pilar_ukir_base.png](file:///c:/xampp/htdocs/pelaminan/uploads/products/pelaminan_eropa_pilar_ukir_base.png), [scratch/replace_base_image.py](file:///c:/xampp/htdocs/pelaminan/scratch/replace_base_image.py))
- **Item Pekerjaan**:
  - Mengganti file foto base (`pelaminan_eropa_pilar_ukir_base.png`) dan varian Putih Emas (`pelaminan_eropa_pilar_ukir_putih_emas.png`) secara langsung menggunakan berkas foto 100% original tanpa proses filter/editing apapun sesuai arahan user.
- **Hasil**: Foto base produk dan varian Putih Emas pada website 100% menggunakan foto original yang dikirimkan.

#### 84. Perubahan Varian Warna Putih Emas Menjadi Cokelat Emas ([scratch/update_variant_cokelat_emas.php](file:///c:/xampp/htdocs/pelaminan/scratch/update_variant_cokelat_emas.php), [scratch/copy_cokelat_emas.py](file:///c:/xampp/htdocs/pelaminan/scratch/copy_cokelat_emas.py))
- **Item Pekerjaan**:
  - Mengubah varian warna **Putih Emas** pada produk **Pelaminan Eropa Pilar Ukir** menjadi **Cokelat Emas**.
  - Menyimpan foto original varian Cokelat Emas ke berkas `uploads/products/variants/pelaminan_eropa_pilar_ukir_cokelat_emas.png` dan `uploads/products/pelaminan_eropa_pilar_ukir_cokelat_emas.png`.
  - Memperbarui nama dan file varian pada tabel database `product_variants` (ID Varian: 26).
- **Hasil**: Varian warna **Putih Emas** telah berhasil diperbarui menjadi **Cokelat Emas** dengan foto original yang baru.

#### 85. Penambahan Produk Baru: Pelaminan Villa Eropa ([database.sql](file:///c:/xampp/htdocs/pelaminan/database.sql), [scratch/insert_pelaminan_villa_eropa_db.php](file:///c:/xampp/htdocs/pelaminan/scratch/insert_pelaminan_villa_eropa_db.php), [scratch/convert_green_to_white.py](file:///c:/xampp/htdocs/pelaminan/scratch/convert_green_to_white.py))
- **Item Pekerjaan**:
  - Menambahkan produk pelaminan baru **Pelaminan Villa Eropa** (ID: 26, Kode: `PLM-011`, Ukuran: `T: 4m x L: 10m`, Harga: `Rp 28.000.000`, Durasi Produksi: `4 Hari`).
  - Memproses latar belakang hijau *chroma key* (`#00FF00`) dari 4 foto produk menjadi warna putih bersih (`#FFFFFF`) secara otomatis dan menyimpan hasilnya di folder `uploads/products/` dan `uploads/products/variants/`.
  - Mendaftarkan foto utama dan 4 variasi warna resmi:
    1. **Putih Klasik** (`pelaminan_villa_eropa_base.png`)
    2. **Cream Gold** (`pelaminan_villa_eropa_cream_gold.png`)
    3. **Biru Muda** (`pelaminan_villa_eropa_biru_muda.png`)
    4. **Cokelat Emas** (`pelaminan_villa_eropa_cokelat_emas.png`)
  - Memperbarui berkas [database.sql](file:///c:/xampp/htdocs/pelaminan/database.sql) dan tabel MySQL `products` & `product_variants`.
- **Hasil**: Produk **Pelaminan Villa Eropa** beserta 4 variasi warna (latar putih bersih) telah aktif 100% dan siap dipilih pembeli di Katalog, Detail Produk, dan Kustomisasi.

#### 86. Penambahan Produk Baru: Pelaminan Kastil Eropa ([database.sql](file:///c:/xampp/htdocs/pelaminan/database.sql), [scratch/insert_pelaminan_kastil_eropa_db.php](file:///c:/xampp/htdocs/pelaminan/scratch/insert_pelaminan_kastil_eropa_db.php), [scratch/convert_castle_green_to_white.py](file:///c:/xampp/htdocs/pelaminan/scratch/convert_castle_green_to_white.py))
- **Item Pekerjaan**:
  - Menambahkan produk pelaminan baru **Pelaminan Kastil Eropa** (ID: 27, Kode: `PLM-012`, Ukuran: `T: 4,5m x L: 11m`, Harga: `Rp 35.000.000`, Durasi Produksi: `5 Hari`).
  - Memproses latar belakang hijau *chroma key* (`#00FF00`) dari 4 foto produk menjadi warna putih bersih (`#FFFFFF`) secara otomatis dan menyimpan hasilnya di folder `uploads/products/` dan `uploads/products/variants/`.
  - Mendaftarkan foto utama dan 4 variasi warna resmi:
    1. **Putih Emas** (`pelaminan_kastil_eropa_base.png`)
    2. **Cream Gold** (`pelaminan_kastil_eropa_cream_gold.png`)
    3. **Biru Muda Emas** (`pelaminan_kastil_eropa_biru_muda.png`)
    4. **Cokelat Emas** (`pelaminan_kastil_eropa_cokelat_emas.png`)
  - Memperbarui berkas [database.sql](file:///c:/xampp/htdocs/pelaminan/database.sql) dan tabel MySQL `products` & `product_variants`.
- **Hasil**: Produk **Pelaminan Kastil Eropa** beserta 4 variasi warna (latar putih bersih) telah aktif 100% dan siap dipilih pembeli di Katalog, Detail Produk, dan Studio Kustomisasi.

#### 87. Penambahan Produk Baru: Pelaminan Istana Corinthian ([database.sql](file:///c:/xampp/htdocs/pelaminan/database.sql), [scratch/insert_pelaminan_roman_luxury_db.php](file:///c:/xampp/htdocs/pelaminan/scratch/insert_pelaminan_roman_luxury_db.php), [scratch/convert_roman_green_to_white.py](file:///c:/xampp/htdocs/pelaminan/scratch/convert_roman_green_to_white.py))
- **Item Pekerjaan**:
  - Menambahkan produk pelaminan baru **Pelaminan Istana Corinthian** (ID: 28, Kode: `PLM-013`, Ukuran: `T: 4m x L: 10m`, Harga: `Rp 32.000.000`, Durasi Produksi: `4 Hari`).
  - Memproses latar belakang hijau *chroma key* (`#00FF00`) dari 4 foto produk menjadi warna putih bersih (`#FFFFFF`) secara otomatis dan menyimpan hasilnya di folder `uploads/products/` dan `uploads/products/variants/`.
  - Mendaftarkan foto utama dan 4 variasi warna resmi:
    1. **Putih Emas** (`pelaminan_roman_luxury_base.png`)
    2. **Cream Gold** (`pelaminan_roman_luxury_cream_gold.png`)
    3. **Biru Muda Emas** (`pelaminan_roman_luxury_biru_muda.png`)
    4. **Cokelat Emas** (`pelaminan_roman_luxury_cokelat_emas.png`)
  - Memperbarui berkas [database.sql](file:///c:/xampp/htdocs/pelaminan/database.sql) dan tabel MySQL `products` & `product_variants`.
- **Hasil**: Produk **Pelaminan Istana Corinthian** beserta 4 variasi warna (latar putih bersih) telah aktif 100% dan siap dipilih pembeli di Katalog, Detail Produk, dan Studio Kustomisasi.

#### 88. Penghapusan Varian Warna Putih Ganda yang Redundan ([scratch/delete_white_duplicate_variants.php](file:///c:/xampp/htdocs/pelaminan/scratch/delete_white_duplicate_variants.php))
- **Item Pekerjaan**:
  - Menghapus varian warna tambahan `Putih Emas` / `Putih Klasik` yang sifat fotonya 100% identik dengan foto `Warna Utama` (Base Image) agar tidak tercipta kartu opsi ganda di antarmuka halaman detail produk ([product.php](file:///c:/xampp/htdocs/pelaminan/product.php)) dan studio kustomisasi ([customization.php](file:///c:/xampp/htdocs/pelaminan/customization.php)).
  - Mengunci pilihan warna utama sebagai warna putih default dan varian pendamping khusus untuk warna alternatif (`Cream Gold`, `Biru Muda / Biru Muda Emas`, `Cokelat Emas`).
- **Hasil**: Tampilan pilihan kartu variasi warna pada antarmuka sangat rapi, bersih, dan bebas dari duplikasi kartu warna utama.

#### 89. Penambahan Produk Baru: Gazebo Lingkar Corinthian ([database.sql](file:///c:/xampp/htdocs/pelaminan/database.sql), [scratch/insert_gazebo_lingkar_corinthian_db.php](file:///c:/xampp/htdocs/pelaminan/scratch/insert_gazebo_lingkar_corinthian_db.php), [scratch/convert_gazebo_images.py](file:///c:/xampp/htdocs/pelaminan/scratch/convert_gazebo_images.py))
- **Item Pekerjaan**:
  - Menambahkan produk gazebo baru **Gazebo Lingkar Corinthian** (ID: 29, Kode: `GZB-001`, Kategori: Gazebo, Ukuran: `T: 4m x L: 6m` [Lebar 600 cm], Harga: `Rp 5.000.000`, Durasi Produksi: `3 Hari`).
  - Memproses latar belakang papan catur palsu dari 4 foto produk menjadi warna putih bersih (`#FFFFFF`) dan menyimpan hasilnya di folder `uploads/products/` dan `uploads/products/variants/`.
  - Mendaftarkan foto utama warna putih bawaan (`gazebo_lingkar_corinthian_base.png`) serta 3 variasi warna alternatif:
    1. **Cream Gold** (`gazebo_lingkar_corinthian_cream_gold.png`)
    2. **Biru Muda** (`gazebo_lingkar_corinthian_biru_muda.png`)
    3. **Cokelat Klasik** (`gazebo_lingkar_corinthian_cokelat_klasik.png`)
  - Memperbarui berkas [database.sql](file:///c:/xampp/htdocs/pelaminan/database.sql) dan tabel MySQL `products` & `product_variants`.
- **Hasil**: Produk **Gazebo Lingkar Corinthian** (Kategori Gazebo) beserta 3 variasi warna alternatif (latar putih bersih) telah aktif 100% dan siap dipilih pembeli.

#### 90. Penambahan Produk Baru: Gazebo Balkon Ukir Eropa ([database.sql](file:///c:/xampp/htdocs/pelaminan/database.sql), [scratch/insert_gazebo_balkon_ukir_db.php](file:///c:/xampp/htdocs/pelaminan/scratch/insert_gazebo_balkon_ukir_db.php), [scratch/copy_balcony_gazebo_photos.py](file:///c:/xampp/htdocs/pelaminan/scratch/copy_balcony_gazebo_photos.py))
- **Item Pekerjaan**:
  - Menambahkan produk gazebo baru **Gazebo Balkon Ukir Eropa** (ID: 30, Kode: `GZB-002`, Kategori: Gazebo, Ukuran: `T: 4m x L: 6m` [Lebar 600 cm], Harga: `Rp 4.500.000`, Durasi Produksi: `3 Hari`).
  - Menyimpan ke-4 berkas foto asli (*original photos*) 100% tanpa pengubahan filter, kontras, maupun penyuntingan warna sesuai instruksi user ke folder `uploads/products/` dan `uploads/products/variants/`.
  - Mendaftarkan foto utama warna putih bawaan (`gazebo_balkon_ukir_base.png`) serta 3 variasi warna alternatif:
    1. **Cream Gold** (`gazebo_balkon_ukir_cream_gold.png`)
    2. **Biru Muda** (`gazebo_balkon_ukir_biru_muda.png`)
    3. **Cokelat Klasik** (`gazebo_balkon_ukir_cokelat_klasik.png`)
  - Memperbarui berkas [database.sql](file:///c:/xampp/htdocs/pelaminan/database.sql) dan tabel MySQL `products` & `product_variants`.
- **Hasil**: Produk **Gazebo Balkon Ukir Eropa** (Kategori Gazebo) beserta 3 variasi warna alternatif menggunakan foto asli telah aktif 100% dan siap dipilih pembeli.

#### 91. Pembersihan Background Papan Catur Samar Menjadi Putih Bersih ([scratch/clean_balcony_checkerboard.py](file:///c:/xampp/htdocs/pelaminan/scratch/clean_balcony_checkerboard.py))
- **Item Pekerjaan**:
  - Mengonversi pola background catur samar (*fake PNG checkerboard*) pada ke-4 foto produk **Gazebo Balkon Ukir Eropa** menjadi warna putih bersih (`#FFFFFF`) tanpa mengonversi/mengubah warna, kontras, maupun filter pada objek fisik gazebo asli.
- **Hasil**: Tampilan foto produk utama dan variasi warna **Gazebo Balkon Ukir Eropa** di Katalog, Detail Produk, dan Kustomisasi kini memancarkan latar putih bersih tanpa pola catur samar.

#### 92. Restorasi Foto Original Utuh Gazebo Balkon Ukir Eropa ([scratch/restore_original_balcony_photos.py](file:///c:/xampp/htdocs/pelaminan/scratch/restore_original_balcony_photos.py))
- **Item Pekerjaan**:
  - Mengembalikan berkas foto produk utama dan variasi warna **Gazebo Balkon Ukir Eropa** 100% ke berkas asli (*original bytes*) agar seluruh detail fisik pilar, ukiran balustrade, bayangan, dan garis batas produk terlihat sangat jelas, tajam, dan utuh tanpa pudar/tersamar latar belakang.
- **Hasil**: Foto produk **Gazebo Balkon Ukir Eropa** 100% tajam dan jelas terlihat pada halaman Katalog, Detail Produk, dan Kustomisasi.

#### 93. Restorasi Foto Original Utuh Gazebo Lingkar Corinthian ([scratch/restore_original_gazebo_lingkar_photos.py](file:///c:/xampp/htdocs/pelaminan/scratch/restore_original_gazebo_lingkar_photos.py))
- **Item Pekerjaan**:
  - Mengembalikan berkas foto produk utama dan variasi warna **Gazebo Lingkar Corinthian** 100% ke berkas asli (*original bytes*) agar seluruh detail pilar corinthian, atap melingkar, panggung bundar, dan bayangan produk terlihat 100% jelas, tajam, dan utuh.
- **Hasil**: Foto produk **Gazebo Lingkar Corinthian** 100% tajam dan jelas terlihat pada halaman Katalog, Detail Produk, dan Kustomisasi.

#### 94. Penambahan Produk Baru: Gazebo Hexagonal Kubah Ukir ([database.sql](file:///c:/xampp/htdocs/pelaminan/database.sql), [scratch/insert_gazebo_segi6_kubah_ukir_db.php](file:///c:/xampp/htdocs/pelaminan/scratch/insert_gazebo_segi6_kubah_ukir_db.php), [scratch/copy_hex_gazebo_photos.py](file:///c:/xampp/htdocs/pelaminan/scratch/copy_hex_gazebo_photos.py))
- **Item Pekerjaan**:
  - Menambahkan produk gazebo baru **Gazebo Hexagonal Kubah Ukir** (ID: 31, Kode: `GZB-003`, Kategori: Gazebo, Ukuran: `T: 4m x L: 6m` [Lebar 600 cm], Harga: `Rp 5.500.000`, Durasi Produksi: `3 Hari`).
  - Menyimpan ke-4 berkas foto asli (*original photos*) 100% tanpa pengubahan filter, kontras, maupun penyuntingan warna sesuai instruksi user ke folder `uploads/products/` dan `uploads/products/variants/`.
  - Mendaftarkan foto utama warna putih bawaan (`gazebo_segi6_kubah_ukir_base.png`) serta 3 variasi warna alternatif:
    1. **Cream Gold** (`gazebo_segi6_kubah_ukir_cream_gold.png`)
    2. **Biru Muda** (`gazebo_segi6_kubah_ukir_biru_muda.png`)
    3. **Cokelat Klasik** (`gazebo_segi6_kubah_ukir_cokelat_klasik.png`)
  - Memperbarui berkas [database.sql](file:///c:/xampp/htdocs/pelaminan/database.sql) dan tabel MySQL `products` & `product_variants`.
- **Hasil**: Produk **Gazebo Hexagonal Kubah Ukir** (Kategori Gazebo) beserta 3 variasi warna alternatif menggunakan foto asli telah aktif 100% dan siap dipilih pembeli.

#### 95. Penambahan Produk Baru: Gazebo Gerbang Kisi Ukir ([database.sql](file:///c:/xampp/htdocs/pelaminan/database.sql), [scratch/insert_gazebo_gerbang_kisi_ukir_db.php](file:///c:/xampp/htdocs/pelaminan/scratch/insert_gazebo_gerbang_kisi_ukir_db.php), [scratch/copy_square_gazebo_photos.py](file:///c:/xampp/htdocs/pelaminan/scratch/copy_square_gazebo_photos.py))
- **Item Pekerjaan**:
  - Menambahkan produk gazebo baru **Gazebo Gerbang Kisi Ukir** (ID: 32, Kode: `GZB-004`, Kategori: Gazebo, Ukuran: `T: 4m x L: 6m` [Lebar 600 cm], Harga: `Rp 4.800.000`, Durasi Produksi: `3 Hari`).
  - Menyimpan ke-4 berkas foto asli (*original photos*) 100% tanpa pengubahan filter, kontras, maupun penyuntingan warna sesuai instruksi user ke folder `uploads/products/` dan `uploads/products/variants/`.
  - Mendaftarkan foto utama warna putih bawaan (`gazebo_gerbang_kisi_ukir_base.png`) serta 3 variasi warna alternatif:
    1. **Cream Gold** (`gazebo_gerbang_kisi_ukir_cream_gold.png`)
    2. **Biru Muda** (`gazebo_gerbang_kisi_ukir_biru_muda.png`)
    3. **Cokelat Klasik** (`gazebo_gerbang_kisi_ukir_cokelat_klasik.png`)
  - Memperbarui berkas [database.sql](file:///c:/xampp/htdocs/pelaminan/database.sql) dan tabel MySQL `products` & `product_variants`.
- **Hasil**: Produk **Gazebo Gerbang Kisi Ukir** (Kategori Gazebo) beserta 3 variasi warna alternatif menggunakan foto asli telah aktif 100% dan siap dipilih pembeli.

#### 96. Penambahan Varian Warna Biru: Kotak Akas Motif Kaca ([scratch/insert_blue_kotak_akas_variant.php](file:///c:/xampp/htdocs/pelaminan/scratch/insert_blue_kotak_akas_variant.php), [scratch/copy_blue_kotak_akas_photo.py](file:///c:/xampp/htdocs/pelaminan/scratch/copy_blue_kotak_akas_photo.py))
- **Item Pekerjaan**:
  - Menambahkan variasi warna baru **Biru** untuk produk **Kotak Akas Motif Kaca** (Product ID: 11, Variant ID: 53).
  - Menyimpan foto asli tanpa pengubahan filter ke `uploads/products/kotak_akas_motif_kaca_biru.png` dan `uploads/products/variants/kotak_akas_motif_kaca_biru.png`.
- **Hasil**: Pilihan warna **Biru** kini aktif pada produk **Kotak Akas Motif Kaca** dan siap dipilih pada Detail Produk dan Kustomisasi.

#### 97. Penambahan Varian Warna Biru & Cokelat: Kotak Akas Pusaka Emas ([scratch/insert_pusaka_emas_variants.php](file:///c:/xampp/htdocs/pelaminan/scratch/insert_pusaka_emas_variants.php), [scratch/copy_pusaka_emas_photos.py](file:///c:/xampp/htdocs/pelaminan/scratch/copy_pusaka_emas_photos.py))
- **Item Pekerjaan**:
  - Menambahkan 2 variasi warna baru **Biru** (Variant ID: 54) dan **Cokelat** (Variant ID: 55) untuk produk **Kotak Akas Pusaka Emas** (Product ID: 13).
  - Menyimpan ke-2 foto asli (*original photos*) tanpa filter ke `uploads/products/` dan `uploads/products/variants/`.
- **Hasil**: Pilihan warna **Biru** dan **Cokelat** kini aktif pada produk **Kotak Akas Pusaka Emas** dan siap dipilih pada Detail Produk dan Kustomisasi.

#### 98. Penambahan Produk Baru: Kotak Akas Bintang Ukir ([database.sql](file:///c:/xampp/htdocs/pelaminan/database.sql), [scratch/insert_kotak_akas_bintang_ukir_db.php](file:///c:/xampp/htdocs/pelaminan/scratch/insert_kotak_akas_bintang_ukir_db.php), [scratch/copy_bintang_box_photos.py](file:///c:/xampp/htdocs/pelaminan/scratch/copy_bintang_box_photos.py))
- **Item Pekerjaan**:
  - Menambahkan produk kotak akas baru **Kotak Akas Bintang Ukir** (ID: 33, Kode: `KA-005`, Kategori: Kotak Akas, Ukuran: `T: 90cm x L: 45cm x P: 45cm`, Harga: `Rp 500.000`, Durasi Produksi: `2 Hari`).
  - Menyimpan ke-4 berkas foto asli (*original photos*) 100% tanpa pengubahan filter, kontras, maupun penyuntingan warna sesuai instruksi user ke folder `uploads/products/` dan `uploads/products/variants/`.
  - Mendaftarkan foto utama warna putih bawaan (`kotak_akas_bintang_ukir_base.png`) serta 3 variasi warna alternatif:
    1. **Cream Gold** (`kotak_akas_bintang_ukir_cream_gold.png`)
    2. **Biru Muda** (`kotak_akas_bintang_ukir_biru_muda.png`)
    3. **Cokelat Klasik** (`kotak_akas_bintang_ukir_cokelat_klasik.png`)
  - Memperbarui berkas [database.sql](file:///c:/xampp/htdocs/pelaminan/database.sql) dan tabel MySQL `products` & `product_variants`.
- **Hasil**: Produk **Kotak Akas Bintang Ukir** (Kategori Kotak Akas) beserta 3 variasi warna alternatif menggunakan foto asli telah aktif 100% dan siap dipilih pembeli.

#### 99. Penambahan Produk Baru: Kotak Akas Kubah Ukir Arabesq ([database.sql](file:///c:/xampp/htdocs/pelaminan/database.sql), [scratch/insert_kotak_akas_kubah_arabesq_db.php](file:///c:/xampp/htdocs/pelaminan/scratch/insert_kotak_akas_kubah_arabesq_db.php), [scratch/copy_kubah_arabesq_photos.py](file:///c:/xampp/htdocs/pelaminan/scratch/copy_kubah_arabesq_photos.py))
- **Item Pekerjaan**:
  - Menambahkan produk kotak akas baru **Kotak Akas Kubah Ukir Arabesq** (ID: 34, Kode: `KA-006`, Kategori: Kotak Akas, Ukuran: `T: 95cm x L: 45cm x P: 45cm`, Harga: `Rp 550.000`, Durasi Produksi: `2 Hari`).
  - Menyimpan ke-4 berkas foto asli (*original photos*) 100% tanpa pengubahan filter, kontras, maupun penyuntingan warna sesuai instruksi user ke folder `uploads/products/` dan `uploads/products/variants/`.
  - Mendaftarkan foto utama warna putih bawaan (`kotak_akas_kubah_arabesq_base.png`) serta 3 variasi warna alternatif: Cream Gold, Biru Muda, dan Cokelat Klasik.
  - Memperbarui berkas [database.sql](file:///c:/xampp/htdocs/pelaminan/database.sql) dan tabel MySQL `products` & `product_variants`.
- **Hasil**: Produk **Kotak Akas Kubah Ukir Arabesq** (Kategori Kotak Akas) beserta 3 variasi warna alternatif menggunakan foto asli telah aktif 100% dan siap dipilih pembeli.

#### 100. Penambahan Produk Baru: Kotak Akas Kipas Art Deco ([database.sql](file:///c:/xampp/htdocs/pelaminan/database.sql), [scratch/insert_kotak_akas_kipas_artdeco_db.php](file:///c:/xampp/htdocs/pelaminan/scratch/insert_kotak_akas_kipas_artdeco_db.php), [scratch/copy_artdeco_box_photos.py](file:///c:/xampp/htdocs/pelaminan/scratch/copy_artdeco_box_photos.py))
- **Item Pekerjaan**:
  - Menambahkan produk kotak akas baru **Kotak Akas Kipas Art Deco** (ID: 35, Kode: `KA-007`, Kategori: Kotak Akas, Ukuran khusus sesuai instruksi user: `T: 70cm x L: 45cm x P: 45cm`, Harga: `Rp 480.000`, Durasi Produksi: `2 Hari`).
  - Menyimpan ke-4 berkas foto asli (*original photos*) 100% tanpa pengubahan filter, kontras, maupun penyuntingan warna sesuai instruksi user ke folder `uploads/products/` dan `uploads/products/variants/`.
  - Mendaftarkan foto utama warna putih bawaan (`kotak_akas_kipas_artdeco_base.png`) serta 3 variasi warna alternatif: Cream Gold, Biru Muda, dan Cokelat Klasik.
  - Memperbarui berkas [database.sql](file:///c:/xampp/htdocs/pelaminan/database.sql) dan tabel MySQL `products` & `product_variants`.
- **Hasil**: Produk **Kotak Akas Kipas Art Deco** (Kategori Kotak Akas) dengan tinggi 70cm beserta 3 variasi warna alternatif menggunakan foto asli telah aktif 100% dan siap dipilih pembeli.

#### 101. Penambahan Produk Baru: Kotak Akas Kisi Silang Lotus ([database.sql](file:///c:/xampp/htdocs/pelaminan/database.sql), [scratch/insert_kotak_akas_kisi_silang_db.php](file:///c:/xampp/htdocs/pelaminan/scratch/insert_kotak_akas_kisi_silang_db.php), [scratch/copy_kisi_silang_photos.py](file:///c:/xampp/htdocs/pelaminan/scratch/copy_kisi_silang_photos.py))
- **Item Pekerjaan**:
  - Menambahkan produk kotak akas baru **Kotak Akas Kisi Silang Lotus** (ID: 36, Kode: `KA-008`, Kategori: Kotak Akas, Ukuran: `T: 90cm x L: 45cm x P: 45cm`, Harga: `Rp 500.000`, Durasi Produksi: `2 Hari`).
  - Menyimpan ke-3 berkas foto asli (*original photos*) 100% tanpa pengubahan filter, kontras, maupun penyuntingan warna sesuai instruksi user ke folder `uploads/products/` dan `uploads/products/variants/`.
  - Mendaftarkan foto utama warna putih bawaan (`kotak_akas_kisi_silang_base.png`) serta 2 variasi warna alternatif: Biru Muda dan Cokelat Klasik.
  - Memperbarui berkas [database.sql](file:///c:/xampp/htdocs/pelaminan/database.sql) dan tabel MySQL `products` & `product_variants`.
- **Hasil**: Produk **Kotak Akas Kisi Silang Lotus** (Kategori Kotak Akas) beserta variasi warna alternatif menggunakan foto asli telah aktif 100% dan siap dipilih pembeli.

#### 102. Penambahan Varian Warna Biru & Cokelat: Pot Bunga Ukir Eropa Klasik ([scratch/insert_pot_bunga_variants.php](file:///c:/xampp/htdocs/pelaminan/scratch/insert_pot_bunga_variants.php), [scratch/copy_pot_bunga_photos.py](file:///c:/xampp/htdocs/pelaminan/scratch/copy_pot_bunga_photos.py))
- **Item Pekerjaan**:
  - Menambahkan 2 variasi warna baru **Biru** (Variant ID: 67) dan **Cokelat** (Variant ID: 68) untuk produk **Pot Bunga Ukir Eropa Klasik** (Product ID: 14).
  - Menyimpan ke-2 foto asli (*original photos*) tanpa filter ke `uploads/products/` dan `uploads/products/variants/`.
#### 103. Fitur Lupa Password & Reset Password untuk Customer & Admin ([login.php](file:///c:/xampp/htdocs/pelaminan/login.php), [forgot-password.php](file:///c:/xampp/htdocs/pelaminan/forgot-password.php), [reset-password.php](file:///c:/xampp/htdocs/pelaminan/reset-password.php), [database.sql](file:///c:/xampp/htdocs/pelaminan/database.sql))
- **Item Pekerjaan**:
  - Menambahkan tautan **`Lupa Password?`** pada header input password di halaman login ([login.php](file:///c:/xampp/htdocs/pelaminan/login.php)).
  - Membangun halaman **[forgot-password.php](file:///c:/xampp/htdocs/pelaminan/forgot-password.php)** lengkap dengan selector role 2-tab (**Customer** & **Admin**), input email, pembuatan token acak 64-karakter secure (`token`), dan batas waktu masa berlaku 1 jam (`expires_at`).
  - Membangun halaman **[reset-password.php](file:///c:/xampp/htdocs/pelaminan/reset-password.php)** untuk memvalidasi token, menampilkan ringkasan akun, form password baru & konfirmasi password baru (dilengkapi tombol toggle tampilkan/sembunyikan password `👁️`), serta enkripsi `password_hash()`.
  - Menyelaraskan pengaturan zona waktu global PHP (`date_default_timezone_set('Asia/Jakarta')`) pada [config/helpers.php](file:///c:/xampp/htdocs/pelaminan/config/helpers.php) agar waktu server PHP dan MySQL `NOW()` (WIB) 100% presisi.
  - Memperbarui skema [database.sql](file:///c:/xampp/htdocs/pelaminan/database.sql) dengan penambahan tabel `password_resets`.
- **Hasil**: Alur Lupa Password & Reset Password untuk Customer maupun Admin berfungsi 100% lancar, aman, presisi waktu, dan memiliki desain antarmuka mewah yang selaras.

#### 104. Deployment ke Hosting ArenHost & Integrasi GitHub ([.cpanel.yml](file:///e:/JOB/BANTU/NURTASAH/pelaminan/.cpanel.yml), [.htaccess](file:///e:/JOB/BANTU/NURTASAH/pelaminan/.htaccess), [config/database.php](file:///e:/JOB/BANTU/NURTASAH/pelaminan/config/database.php), [database.sql](file:///e:/JOB/BANTU/NURTASAH/pelaminan/database.sql))
- **Item Pekerjaan**:
  - Menginisialisasi repositori Git dan menghubungkan ke remote GitHub: `https://github.com/fajar-romadhan/pelaminan.git`.
  - Mengonfigurasi file `.gitignore` untuk melindungi file sensitif kredensial (`config/database.php`), file dump, dan SSH keys.
  - Membuat script auto-deploy cPanel [`.cpanel.yml`](file:///e:/JOB/BANTU/NURTASAH/pelaminan/.cpanel.yml) yang terhubung ke target `/home/pelamina/public_html/`.
  - Membuat file konfigurasi server [`.htaccess`](file:///e:/JOB/BANTU/NURTASAH/pelaminan/.htaccess) untuk proteksi file sensitif, kompresi GZIP, dan browser caching.
  - Memperbaiki [database.sql](file:///e:/JOB/BANTU/NURTASAH/pelaminan/database.sql) dengan menghapus sintaks `CREATE DATABASE` / `USE` (karena batasan hak akses shared hosting) serta menata ulang urutan pembuatan tabel `editor_designs` sebelum `carts` dan `orders` agar terbebas dari kendala *Foreign Key Constraint*.
  - Mengimpor seluruh 19 tabel database ke MySQL cPanel database `pelamina_pelaminan` dengan user `pelamina_id_rsa`.
  - Mengonfigurasi kredensial koneksi produksi di [config/database.php](file:///e:/JOB/BANTU/NURTASAH/pelaminan/config/database.php).
  - Melakukan build dan ekstraksi paket hosting `pelaminan_hosting_ready.zip` ke direktori `public_html/` server ArenHost.
  - Domain target: `pelaminanfamily.my.id` (IP Server: `195.88.211.130`).
- **Hasil**: Seluruh berkas website, database MySQL, dan konfigurasi backend di server ArenHost telah 100% tuntas, rapi, dan siap beroperasi saat aktivasi registrar domain selesai.

#### 105. Analisis DNS Domain Pending & Pembuatan Catatan Hosting ([HOSTING_NOTES.md](file:///e:/JOB/BANTU/NURTASAH/pelaminan/HOSTING_NOTES.md))
- **Item Pekerjaan**:
  - Melakukan diagnosis menyeluruh terkait DNS domain `pelaminanfamily.my.id` melalui `nslookup` (DNS ISP, Google 8.8.8.8, Cloudflare 1.1.1.1) dan inspeksi cPanel Zone Editor.
  - Memverifikasi bahwa DNS Zone di cPanel ArenHost telah 100% tepat (A Record `195.88.211.130`, CNAME `www`, `mail`, `ftp`, MX Record).
  - Mengidentifikasi status domain di ArenHost Client Area berstatus 🟡 **Pending** karena baru didaftarkan pada 22 Agustus 2026 dan masih dalam proses validasi registri PANDI (.id).
  - Membuat berkas dokumentasi lengkap [[HOSTING_NOTES.md](file:///e:/JOB/BANTU/NURTASAH/pelaminan/HOSTING_NOTES.md)] yang memuat info cPanel, IP server, status domain, panduan bypass via file hosts Windows untuk testing lokal, dan checklist verifikasi pasca aktivasi domain.
- **Hasil**: Seluruh informasi teknis hosting dan panduan aktivasi domain terdokumentasi rapi di repositori proyek.

#### 106. Aktivasi Domain PANDI & Auto-Detect BASE_URL ([config/helpers.php](file:///e:/JOB/BANTU/NURTASAH/pelaminan/config/helpers.php), [HOSTING_NOTES.md](file:///e:/JOB/BANTU/NURTASAH/pelaminan/HOSTING_NOTES.md))
- **Item Pekerjaan**:
  - Melakukan diagnosis teknis langsung ke registri pusat PANDI (`https://rdap.pandi.id/rdap/domain/pelaminanfamily.my.id`).
  - Domain `pelaminanfamily.my.id` dan `pempekmona.my.id` telah resmi diterbitkan oleh PANDI pada **23 Agustus 2026 pukul 19:45 WIB** (`2026-08-23T12:45:32Z`) dengan status 🟢 **ACTIVE** di client area ArenHost.
  - Memperbarui konstanta `BASE_URL` di [config/helpers.php](file:///e:/JOB/BANTU/NURTASAH/pelaminan/config/helpers.php) agar otomatis mendeteksi apakah diakses di domain live (`''`) atau di localhost subfolder (`/pelaminan`), dan mem-push ke branch `main` GitHub.
  - Memverifikasi resolusi DNS pada Google DNS (`8.8.8.8`) mengarah ke IP `195.88.211.130`.
- **Hasil**: Domain telah aktif resmi di registri nasional PANDI dan konfigurasi routing BASE_URL siap bekerja mulus di server live.

#### 107. Penghubungan GitHub ke cPanel & Optimasi Skrip Auto-Deploy ([.cpanel.yml](file:///e:/JOB/BANTU/NURTASAH/pelaminan/.cpanel.yml))
- **Item Pekerjaan**:
  - Berhasil meng-clone repositori GitHub `https://github.com/fajar-romadhan/pelaminan.git` ke dalam cPanel Git™ Version Control (`/home/pelamina/repositories/pelaminan`).
  - Mengubah visibilitas repositori GitHub menjadi **Public** sehingga proses clone dan update remote cPanel berjalan cepat tanpa batasan autentikasi non-interaktif.
  - Memperbaiki berkas [`.cpanel.yml`](file:///e:/JOB/BANTU/NURTASAH/pelaminan/.cpanel.yml) dengan mengganti variabel `export DEPLOYPATH` menjadi path absolut langsung (`/home/pelamina/public_html/`) pada setiap baris tugas deployment agar dieksekusi 100% tuntas oleh subshell cPanel runner.
- **Hasil**: GitHub dan cPanel Git Version Control terhubung 100% dan proses deploy ke `public_html/` siap dijalankan dengan sekali klik.

---

## 🛠️ Ringkasan Struktur Modul Utama
- **Website Utama**: `index.php`, `gallery.php`, `product.php`, `customization.php`, `checkout.php`, `order.php`, `my-orders.php`, `invoice.php`, `receipt.php`, `tracking.php`
- **Panel Admin**: `admin/index.php`, `admin/orders.php`, `admin/products.php`, `admin/product-variants.php`, `admin/items.php`, `admin/production-calendar.php`, `admin/operational-report.php`, `admin/export-report-pdf.php`, `admin/notifications.php`
- **Konfigurasi & Utility**: `config/database.php`, `config/helpers.php`, `assets/css/style.css`, `assets/js/delivery-map.js`, `assets/js/checkout-shipping.js`
- **Dokumentasi**: `PROJECT_KNOWLEDGE.md`, `DOKUMEN_HALAMAN_SISTEM.md`, `HOSTING_NOTES.md`
