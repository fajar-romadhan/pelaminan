SET FOREIGN_KEY_CHECKS = 0;

DROP TABLE IF EXISTS notifications;
DROP TABLE IF EXISTS order_status_history;
DROP TABLE IF EXISTS production_schedule;
DROP TABLE IF EXISTS production_queue;
DROP TABLE IF EXISTS receipts;
DROP TABLE IF EXISTS invoices;
DROP TABLE IF EXISTS payments;
DROP TABLE IF EXISTS orders;
DROP TABLE IF EXISTS carts;
DROP TABLE IF EXISTS editor_designs;
DROP TABLE IF EXISTS extra_items;
DROP TABLE IF EXISTS shipping_rates;
DROP TABLE IF EXISTS product_variants;
DROP TABLE IF EXISTS products;
DROP TABLE IF EXISTS categories;
DROP TABLE IF EXISTS settings;
DROP TABLE IF EXISTS password_resets;
DROP TABLE IF EXISTS activity_logs;
DROP TABLE IF EXISTS users;

CREATE TABLE users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(120) NOT NULL,
  phone VARCHAR(30),
  email VARCHAR(120) NOT NULL UNIQUE,
  address TEXT,
  password VARCHAR(255) NOT NULL,
  role ENUM('admin','owner','customer') NOT NULL DEFAULT 'customer',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE password_resets (
  id INT AUTO_INCREMENT PRIMARY KEY,
  email VARCHAR(120) NOT NULL,
  role ENUM('admin','customer') NOT NULL DEFAULT 'customer',
  token VARCHAR(100) NOT NULL UNIQUE,
  expires_at DATETIME NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_reset_email (email),
  INDEX idx_reset_token (token)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE settings (
  id INT AUTO_INCREMENT PRIMARY KEY,
  setting_key VARCHAR(80) NOT NULL UNIQUE,
  setting_value TEXT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE categories (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(80) NOT NULL UNIQUE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE products (
  id INT AUTO_INCREMENT PRIMARY KEY,
  category_id INT NOT NULL,
  name VARCHAR(160) NOT NULL,
  code VARCHAR(40) NOT NULL UNIQUE,
  description TEXT NOT NULL,
  size VARCHAR(120) DEFAULT '-',
  price DECIMAL(14,2) NOT NULL DEFAULT 0,
  production_duration INT NOT NULL DEFAULT 3,
  status ENUM('Aktif','Tidak Aktif') DEFAULT 'Aktif',
  image_url VARCHAR(255),
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE product_variants (
  id INT AUTO_INCREMENT PRIMARY KEY,
  product_id INT NOT NULL,
  variant_name VARCHAR(120) NOT NULL,
  image VARCHAR(255) NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE shipping_rates (
  id INT AUTO_INCREMENT PRIMARY KEY,
  city VARCHAR(120) NOT NULL,
  district VARCHAR(120) NOT NULL,
  cost DECIMAL(14,2) NOT NULL DEFAULT 0,
  status ENUM('Aktif','Tidak Aktif') DEFAULT 'Aktif'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE extra_items (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(160) NOT NULL,
  category VARCHAR(80) NOT NULL,
  price DECIMAL(14,2) NOT NULL DEFAULT 0,
  image_url VARCHAR(255) NULL,
  description TEXT NULL,
  status ENUM('Aktif','Tidak Aktif') DEFAULT 'Aktif',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE editor_designs (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  product_id INT NOT NULL,
  variant_id INT NULL,
  variant_name VARCHAR(120) NULL,
  title VARCHAR(160) NOT NULL,
  size VARCHAR(60) DEFAULT 'Medium',
  color VARCHAR(20) DEFAULT '#800020',
  sofa VARCHAR(120),
  flower VARCHAR(120),
  kotak VARCHAR(120),
  notes TEXT,
  extra_items_json TEXT NULL,
  extra_price DECIMAL(14,2) DEFAULT 0,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE carts (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  product_id INT NOT NULL,
  design_id INT NULL,
  variant_id INT NULL,
  variant_name VARCHAR(120) NULL,
  quantity INT DEFAULT 1,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
  FOREIGN KEY (design_id) REFERENCES editor_designs(id) ON DELETE SET NULL,
  FOREIGN KEY (variant_id) REFERENCES product_variants(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE orders (
  id INT AUTO_INCREMENT PRIMARY KEY,
  order_code VARCHAR(40) NOT NULL UNIQUE,
  user_id INT NOT NULL,
  receiver_name VARCHAR(120) NULL,
  receiver_phone VARCHAR(30) NULL,
  delivery_address TEXT NULL,
  delivery_note VARCHAR(255) NULL,
  delivery_latitude DECIMAL(10,7) NULL,
  delivery_longitude DECIMAL(10,7) NULL,
  delivery_map_address TEXT NULL,
  product_id INT NOT NULL,
  variant_id INT NULL,
  variant_name VARCHAR(120) NULL,
  design_id INT NULL,
  extra_items_detail LONGTEXT NULL,
  customer_name VARCHAR(120) NOT NULL,
  phone VARCHAR(30) NOT NULL,
  address TEXT NOT NULL,
  city VARCHAR(120) NOT NULL,
  district VARCHAR(120) NOT NULL,
  pickup_method ENUM('diantar','diambil') DEFAULT 'diantar',
  shipping_rate_id INT NULL,
  shipping_cost DECIMAL(14,2) DEFAULT 0,
  total_amount DECIMAL(14,2) NOT NULL,
  dp_amount DECIMAL(14,2) NOT NULL,
  paid_amount DECIMAL(14,2) DEFAULT 0,
  queue_number INT NULL DEFAULT NULL,
  status ENUM(
    'WAITING_PAYMENT',
    'PAYMENT_RECEIVED',
    'ADMIN_REVIEW',
    'WAITING_QUEUE',
    'PRODUCTION',
    'READY_PICKUP',
    'READY_DELIVERY',
    'ON_DELIVERY',
    'DELIVERED',
    'READY_INSTALLATION',
    'INSTALLATION',
    'COMPLETED',
    'CANCELLED',
    'REJECTED'
  ) DEFAULT 'WAITING_PAYMENT',
  event_date DATE NULL,
  schedule_start DATE NULL,
  schedule_end DATE NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE RESTRICT,
  FOREIGN KEY (design_id) REFERENCES editor_designs(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE payments (
  id INT AUTO_INCREMENT PRIMARY KEY,
  order_id INT NOT NULL,
  type ENUM('dp','final','full') NOT NULL,
  method VARCHAR(80) DEFAULT 'Virtual Account BRI',
  amount DECIMAL(14,2) NOT NULL,
  proof_image VARCHAR(255) NULL DEFAULT NULL,
  status ENUM('pending','berhasil','gagal') DEFAULT 'pending',
  paid_at DATETIME NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Tabel legacy (invoices, receipts, production_queue, production_schedule, order_status_history)
-- telah dihapus. Sistem menggunakan data langsung dari tabel orders & payments.

CREATE TABLE notifications (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  order_id INT NULL,
  title VARCHAR(255) NOT NULL,
  message TEXT NOT NULL,
  is_read TINYINT(1) DEFAULT 0,
  channel VARCHAR(50) DEFAULT 'internal',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE activity_logs (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  user_name VARCHAR(120) NOT NULL,
  user_role VARCHAR(40) NOT NULL,
  action VARCHAR(255) NOT NULL,
  details TEXT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- INITIAL SEED DATA

INSERT INTO users(name, phone, email, address, password, role) VALUES
('Muhammad Dani', '081273400312', 'danimuh816@gmail.com', 'Palembang', '$2y$12$b4L2fsRiztedSKj4fx2QFOVe6k2RIhbQuR4L7/xMFYZ08A6y7YHSK', 'admin'),
('Admin Pelaminan', '081273400312', 'admin@pelaminan.local', 'Palembang', '$2y$12$b4L2fsRiztedSKj4fx2QFOVe6k2RIhbQuR4L7/xMFYZ08A6y7YHSK', 'admin'),
('Rina Wulandari', '081234567890', 'customer@pelaminan.local', 'Palembang', '$2y$12$TFG3dI3ps5xXN/DeiWIzcuKjkempr/5VL6ZXXXZ6Fo.BulJj8V.W6', 'customer');

INSERT INTO settings(setting_key, setting_value) VALUES
('business_name','Distributor Pelaminan Family'),
('business_address','Jl. Betawi Raya RS. Benteng, Perumahan Kencana Indah Blok C.1 No. 17, Palembang'),
('instagram','@pengerajin_pelaminan_modern'),
('whatsapp','6281273400312');

INSERT INTO categories(name) VALUES
('Pelaminan'),('Gazebo'),('Pot Bunga'),('Kotak Akas');

INSERT INTO products(id, category_id, name, code, image_url, description, size, price, production_duration, status) VALUES
(10, 1, 'Pelaminan Istana', 'PLM-004', '02da1c66ee7d045496fe51f1f129f36c.png', 'Pelaminan Istana megah bernuansa emas mewah dengan ukiran istana kerajaan, relief mahkota, serta set sofa tahta pengantin.', 'T: 4m x L: 10m', 15000000, 4, 'Aktif'),
(15, 1, 'Pelaminan Eropa Motif Tengah Lengkung', 'PLM-006', 'pelaminan_eropa_lengkung_base.png', 'Pelaminan Eropa mewah dengan ornamen ukiran khas dan motif lengkungan tengah yang anggun, dilengkapi set kursi pengantin & pendamping.', 'T: 4m x L: 10m', 30000000, 4, 'Aktif'),
(16, 1, 'Pelaminan Modern Minimalis', 'PLM-007', 'pelaminan_modern_minimalis_base.png', 'Pelaminan modern minimalis dengan aksen ukiran kelopak bunga lotus mewah dan kombinasi warna elegan.', 'T: 3,5m x L: 9m', 25000000, 3, 'Aktif'),
(23, 1, 'Pelaminan Tirai Lengkung', 'PLM-008', 'pelaminan_tirai_lengkung_base.jpg', 'Pelaminan tirai lengkung mewah berkonsep Eropa klasik dengan detail ukiran halus, balkon mini, serta tirai latar elegan.', 'T: 4m x L: 9m', 28500000, 3, 'Aktif'),
(24, 1, 'Pelaminan Menara Kencana', 'PLM-009', 'pelaminan_menara_kencana_base.png', 'Pelaminan Menara Kencana berkonsep menara istana emas megah dengan ukiran relief presisi dan tahta pengantin mewah.', 'T: 4m x L: 10m', 32000000, 3, 'Aktif'),
(25, 1, 'Pelaminan Eropa Pilar Ukir', 'PLM-010', 'pelaminan_eropa_pilar_ukir_base.png', 'Pelaminan Eropa Pilar Ukir berkonsep istana megah bernuansa Eropa dengan detail pilar relief ukiran emas presisi, ornamen mahkota, cermin oval tengah yang anggun, serta set kursi sofa pengantin dan pendamping berukir emas mewah.', 'T: 4m x L: 9m', 26000000, 4, 'Aktif'),
(26, 1, 'Pelaminan Villa Eropa', 'PLM-011', 'pelaminan_villa_eropa_base.png', 'Pelaminan Villa Eropa berkonsep arsitektur hunian klasik Eropa dengan fasad jendela kayu elegan, balkon ukir, pilar corinthian presisi, serta set sofa pengantin dan pendamping bergaya modern luxury.', 'T: 4m x L: 10m', 28000000, 4, 'Aktif'),
(27, 1, 'Pelaminan Kastil Eropa', 'PLM-012', 'pelaminan_kastil_eropa_base.png', 'Pelaminan Kastil Eropa berkonsep istana kerajaan klasik dengan multiple menara kubah megah, relief ukiran gothic emas presisi, jendela kayu kerajaan, serta set kursi tahta pengantin dan pendamping berukir emas mewah.', 'T: 4,5m x L: 11m', 35000000, 5, 'Aktif'),
(28, 1, 'Pelaminan Istana Corinthian', 'PLM-013', 'pelaminan_roman_luxury_base.png', 'Pelaminan Istana Corinthian berkonsep kuil megah Romawi Klasik dengan pilar relief corinthian emas ganda, ukiran pediment mahkota bunga yang anggun, ornamen ukiran lengkung filigree emas, serta set sofa tahta pengantin berukir emas mewah.', 'T: 4m x L: 10m', 32000000, 4, 'Aktif'),
(29, 2, 'Gazebo Lingkar Corinthian', 'GZB-001', 'gazebo_lingkar_corinthian_base.png', 'Gazebo Lingkar Corinthian berkonsep kuil lingkar Eropa klasik dengan 4 pilar corinthian relief kokoh, atap balustrade melingkar presisi, serta panggung bundar berbahan MDF premium.', 'T: 4m x L: 6m', 5000000, 3, 'Aktif'),
(30, 2, 'Gazebo Balkon Ukir Eropa', 'GZB-002', 'gazebo_balkon_ukir_base.png', 'Gazebo Balkon Ukir Eropa berkonsep paviliun balkon klasik dengan mahkota ukiran filigree halus, pilar bermotif kisi silang, serta pagar pembatas balustrade elegan berbahan kayu MDF solid.', 'T: 4m x L: 6m', 4500000, 3, 'Aktif'),
(31, 2, 'Gazebo Hexagonal Kubah Ukir', 'GZB-003', 'gazebo_segi6_kubah_ukir_base.png', 'Gazebo Hexagonal Kubah Ukir berkonsep paviliun segi enam klasik dengan mahkota kubah bertingkat, relief ukiran floral, serta tiang pilar kokoh berbahan kayu solid premium.', 'T: 4m x L: 6m', 5500000, 3, 'Aktif'),
(32, 2, 'Gazebo Gerbang Kisi Ukir', 'GZB-004', 'gazebo_gerbang_kisi_ukir_base.png', 'Gazebo Gerbang Kisi Ukir berkonsep paviliun gerbang klasik dengan kubah archway lengkung kisi silang, pilar relief persegi corinthian kokoh, serta mahkota cornice rata bermotif ukiran halus.', 'T: 4m x L: 6m', 4800000, 3, 'Aktif'),
(11, 4, 'Kotak Akas Motif Kaca', 'KA-003', 'Kotak Akas Motif Kaca.png', 'Kotak amplop pernikahan bermotif cermin kaca dan ornamen ukiran emas mewah berbahan MDF premium.', 'T: 90cm x L: 45cm x P: 45cm', 450000, 2, 'Aktif'),
(13, 4, 'Kotak Akas Pusaka Emas', 'KA-004', '333204b144a702e0f85defae46895cfd.png', 'Kotak amplop pernikahan berbalut ornamen pusaka ukir emas mewah dengan sentuhan finishing glossy premium.', 'T: 90cm x L: 45cm x P: 45cm', 500000, 2, 'Aktif'),
(33, 4, 'Kotak Akas Bintang Ukir', 'KA-005', 'kotak_akas_bintang_ukir_base.png', 'Kotak Akas Bintang Ukir berkonsep kotak uang/amplop kubah masjid islami bernuansa mewah dengan relief ukiran bintang segi-8, lis bingkai lengkung kerawang, serta bahan kayu MDF premium.', 'T: 90cm x L: 45cm x P: 45cm', 500000, 2, 'Aktif'),
(34, 4, 'Kotak Akas Kubah Ukir Arabesq', 'KA-006', 'kotak_akas_kubah_arabesq_base.png', 'Kotak Akas Kubah Ukir Arabesq berkonsep kotak amplop tahta masjid dengan puncak mahkota kubah bawang (onion dome), lis pilar bulat corinthian, serta panel ukiran floral arabesque khas Timur Tengah yang sangat halus.', 'T: 95cm x L: 45cm x P: 45cm', 550000, 2, 'Aktif'),
(35, 4, 'Kotak Akas Kipas Art Deco', 'KA-007', 'kotak_akas_kipas_artdeco_base.png', 'Kotak Akas Kipas Art Deco berkonsep kotak amplop ukiran kipas palmet eropa klasik bernuansa modern vintage dengan tinggi presisi 70 cm, lis pilar relief kerang, serta bahan kayu MDF premium.', 'T: 70cm x L: 45cm x P: 45cm', 480000, 2, 'Aktif'),
(36, 4, 'Kotak Akas Kisi Silang Lotus', 'KA-008', 'kotak_akas_kisi_silang_base.png', 'Kotak Akas Kisi Silang Lotus berkonsep kotak amplop ukiran anyaman geometris 3D dengan ornamen pusat kelopak bunga lotus murni, lis pilar garis ganda, serta bahan kayu MDF premium.', 'T: 90cm x L: 45cm x P: 45cm', 500000, 2, 'Aktif'),
(14, 3, 'Pot Bunga Ukir Eropa Klasik', 'PB-003', 'Standing Flower Klasik.png', 'Pot bunga dekorasi pelaminan dengan motif ukiran pilar klasik Eropa yang elegan berbahan kayu solid premium.', 'T: 90cm x L: 40cm', 750000, 2, 'Aktif'),
(37, 3, 'Pot Bunga Kubah Ukir', 'PB-004', 'pot_bunga_kubah_ukir_base.png', 'Pot Bunga Kubah Ukir berkonsep standing pot bunga tahta kubah dengan relief ukiran khas istana, lis pilar bundar, serta dudukan bunga kokoh.', 'T: 95cm x L: 40cm', 650000, 2, 'Aktif'),
(38, 3, 'Pot Bunga Ramping Lotus', 'PB-005', 'pot_bunga_ramping_lotus_base.png', 'Pot Bunga Ramping Lotus berkonsep standing vase ramping bergaya modern oriental dengan motif kelopak lotus mekar dan aksen emas elegan.', 'T: 90cm x L: 35cm', 600000, 2, 'Aktif'),
(39, 3, 'Pot Bunga Ukir Anggrek', 'PB-006', 'pot_bunga_ukir_anggrek_base.png', 'Pot Bunga Ukir Anggrek berkonsep standing vase mewah dengan ukiran sulur bunga anggrek mekar di sekeliling badan pot dan kaki berkontur indah.', 'T: 90cm x L: 40cm', 650000, 2, 'Aktif');

INSERT INTO product_variants(product_id, variant_name, image_url) VALUES
(10, 'Cream Gold', 'pelaminan_istana_cream_gold.png'),
(10, 'Cokelat Emas', 'pelaminan_istana_cokelat_emas.png'),
(15, 'Putih Emas', 'pelaminan_eropa_lengkung_putih_emas.png'),
(15, 'Biru Muda Emas', 'pelaminan_eropa_lengkung_biru_emas.png'),
(15, 'Hijau Sage Emas', 'pelaminan_eropa_lengkung_hijau_emas.png'),
(16, 'Cream Gold', 'pelaminan_modern_minimalis_cream_gold_v2.png'),
(16, 'Biru Muda Emas', 'pelaminan_modern_minimalis_biru_emas_v2.png'),
(16, 'Cokelat Emas', 'pelaminan_modern_minimalis_cokelat_emas_v2.png'),
(23, 'Putih Emas', 'pelaminan_tirai_lengkung_putih_emas.jpg'),
(23, 'Cream Gold', 'pelaminan_tirai_lengkung_cream_gold.jpg'),
(23, 'Biru Muda Emas', 'pelaminan_tirai_lengkung_biru_emas.jpg'),
(24, 'Putih Emas', 'pelaminan_menara_kencana_putih_emas.png'),
(24, 'Cream Gold', 'pelaminan_menara_kencana_cream_gold.png'),
(24, 'Biru Muda Emas', 'pelaminan_menara_kencana_biru_emas.png'),
(25, 'Cream Gold', 'pelaminan_eropa_pilar_ukir_cream_gold.png'),
(25, 'Biru Muda Emas', 'pelaminan_eropa_pilar_ukir_biru_emas.png'),
(25, 'Cokelat Emas', 'pelaminan_eropa_pilar_ukir_cokelat_emas.png'),
(26, 'Cream Gold', 'pelaminan_villa_eropa_cream_gold.png'),
(26, 'Biru Muda', 'pelaminan_villa_eropa_biru_muda.png'),
(26, 'Cokelat Emas', 'pelaminan_villa_eropa_cokelat_emas.png'),
(27, 'Cream Gold', 'pelaminan_kastil_eropa_cream_gold.png'),
(27, 'Biru Muda Emas', 'pelaminan_kastil_eropa_biru_muda.png'),
(27, 'Cokelat Emas', 'pelaminan_kastil_eropa_cokelat_emas.png'),
(28, 'Cream Gold', 'pelaminan_roman_luxury_cream_gold.png'),
(28, 'Biru Muda Emas', 'pelaminan_roman_luxury_biru_muda.png'),
(28, 'Cokelat Emas', 'pelaminan_roman_luxury_cokelat_emas.png'),
(29, 'Cream Gold', 'gazebo_lingkar_corinthian_cream_gold.png'),
(29, 'Biru Muda', 'gazebo_lingkar_corinthian_biru_muda.png'),
(29, 'Cokelat Klasik', 'gazebo_lingkar_corinthian_cokelat_klasik.png'),
(30, 'Cream Gold', 'gazebo_balkon_ukir_cream_gold.png'),
(30, 'Biru Muda', 'gazebo_balkon_ukir_biru_muda.png'),
(30, 'Cokelat Klasik', 'gazebo_balkon_ukir_cokelat_klasik.png'),
(31, 'Cream Gold', 'gazebo_segi6_kubah_ukir_cream_gold.png'),
(31, 'Biru Muda', 'gazebo_segi6_kubah_ukir_biru_muda.png'),
(31, 'Cokelat Klasik', 'gazebo_segi6_kubah_ukir_cokelat_klasik.png'),
(32, 'Cream Gold', 'gazebo_gerbang_kisi_ukir_cream_gold.png'),
(32, 'Biru Muda', 'gazebo_gerbang_kisi_ukir_biru_muda.png'),
(32, 'Cokelat Klasik', 'gazebo_gerbang_kisi_ukir_cokelat_klasik.png'),
(11, 'Biru', 'kotak_akas_motif_kaca_biru.png'),
(13, 'Biru', 'kotak_akas_pusaka_emas_biru.png'),
(13, 'Cokelat', 'kotak_akas_pusaka_emas_cokelat.png'),
(33, 'Cream Gold', 'kotak_akas_bintang_ukir_cream_gold.png'),
(33, 'Biru Muda', 'kotak_akas_bintang_ukir_biru_muda.png'),
(33, 'Cokelat Klasik', 'kotak_akas_bintang_ukir_cokelat_klasik.png'),
(34, 'Cream Gold', 'kotak_akas_kubah_arabesq_cream_gold.png'),
(34, 'Biru Muda', 'kotak_akas_kubah_arabesq_biru_muda.png'),
(34, 'Cokelat Klasik', 'kotak_akas_kubah_arabesq_cokelat_klasik.png'),
(35, 'Cream Gold', 'kotak_akas_kipas_artdeco_cream_gold.png'),
(35, 'Biru Muda', 'kotak_akas_kipas_artdeco_biru_muda.png'),
(35, 'Cokelat Klasik', 'kotak_akas_kipas_artdeco_cokelat_klasik.png'),
(36, 'Biru Muda', 'kotak_akas_kisi_silang_biru_muda.png'),
(36, 'Cokelat Klasik', 'kotak_akas_kisi_silang_cokelat_klasik.png'),
(14, 'Biru', 'pot_bunga_eropa_klasik_biru.png'),
(14, 'Cokelat', 'pot_bunga_eropa_klasik_cokelat.png'),
(37, 'Cream Gold', 'pot_bunga_kubah_ukir_cream_gold.png'),
(37, 'Biru Muda', 'pot_bunga_kubah_ukir_biru_muda.png'),
(37, 'Cokelat Klasik', 'pot_bunga_kubah_ukir_cokelat_klasik.png'),
(38, 'Cream Gold', 'pot_bunga_ramping_lotus_cream_gold.png'),
(38, 'Biru Muda', 'pot_bunga_ramping_lotus_biru_muda.png'),
(38, 'Cokelat Klasik', 'pot_bunga_ramping_lotus_cokelat_klasik.png'),
(39, 'Cream Gold', 'pot_bunga_ukir_anggrek_cream_gold.png'),
(39, 'Biru Muda', 'pot_bunga_ukir_anggrek_biru_muda.png'),
(39, 'Cokelat Klasik', 'pot_bunga_ukir_anggrek_cokelat_klasik.png');

INSERT INTO shipping_rates(city, district, cost, status) VALUES
('Palembang','Ilir Barat I',0,'Aktif'),
('Palembang','Ilir Timur I',0,'Aktif'),
('Palembang','Seberang Ulu I',0,'Aktif'),
('Prabumulih','Prabumulih Barat',500000,'Aktif'),
('Prabumulih','Prabumulih Timur',500000,'Aktif'),
('Pagaralam','Dempo Selatan',800000,'Aktif'),
('Lubuklinggau','Lubuklinggau Barat',1000000,'Aktif'),
('Banyuasin','Betung',350000,'Aktif'),
('Musi Banyuasin','Sekayu',600000,'Aktif'),
('Musi Rawas','Muara Beliti',900000,'Aktif'),
('Lahat','Lahat',700000,'Aktif'),
('Ogan Komering Ulu','Baturaja Timur',600000,'Aktif'),
('Ogan Komering Ilir','Kayuagung',500000,'Aktif');

INSERT INTO extra_items(name, category, price, status) VALUES
('Sofa Maroon Royal', 'Sofa', 1200000, 'Aktif'),
('Sofa Gold Classic', 'Sofa', 1400000, 'Aktif'),
('Pot Bunga Rose Premium', 'Pot Bunga', 850000, 'Aktif'),
('Pot Bunga Lily White', 'Pot Bunga', 750000, 'Aktif'),
('Kotak Akas Emas Premium', 'Kotak Akas', 450000, 'Aktif'),
('Kotak Akas Maroon Royal', 'Kotak Akas', 380000, 'Aktif');

INSERT INTO orders(order_code,user_id,receiver_name,receiver_phone,delivery_address,delivery_note,delivery_latitude,delivery_longitude,delivery_map_address,product_id,customer_name,phone,address,city,district,pickup_method,shipping_cost,total_amount,dp_amount,paid_amount,status,schedule_start,schedule_end,created_at) VALUES
('#ORD-001',2,'Rina Wulandari','081234567890','Jl. Merdeka No. 10, RT 02/RW 03','Rumah cat hijau pagar hitam',-2.9909340,104.7565540,'Jl. Merdeka, Ilir Barat I, Palembang',1,'Rina Wulandari','081234567890','Jl. Merdeka No. 10','Palembang','Ilir Barat I','diantar',0,8500000,4250000,4250000,'PAYMENT_RECEIVED','2027-07-12','2027-07-16', NOW()),
('#ORD-002',2,'Rina Wulandari','081234567890','Jl. Demang Lebar Daun No. 45','Depan Minimarket Indomaret',-3.4298100,104.2312000,'Prabumulih Barat, Prabumulih',4,'Rina Wulandari','081234567890','Jl. Demang Lebar Daun','Prabumulih','Prabumulih Barat','diantar',500000,3700000,1850000,1850000,'WAITING_QUEUE','2027-07-20','2027-07-22', NOW());

INSERT INTO payments(order_id,type,method,amount,status,paid_at) VALUES
(1,'dp','Virtual Account BRI',4250000,'berhasil',NOW()),
(2,'dp','Virtual Account BRI',1850000,'berhasil',NOW());

SET FOREIGN_KEY_CHECKS = 1;
