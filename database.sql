CREATE DATABASE IF NOT EXISTS pelaminan_family CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE pelaminan_family;

SET FOREIGN_KEY_CHECKS = 0;

DROP TABLE IF EXISTS notifications;
DROP TABLE IF EXISTS order_status_history;
DROP TABLE IF EXISTS production_schedule;
DROP TABLE IF EXISTS production_queue;
DROP TABLE IF EXISTS receipts;
DROP TABLE IF EXISTS invoices;
DROP TABLE IF EXISTS payments;
DROP TABLE IF EXISTS editor_designs;
DROP TABLE IF EXISTS orders;
DROP TABLE IF EXISTS carts;
DROP TABLE IF EXISTS extra_items;
DROP TABLE IF EXISTS shipping_rates;
DROP TABLE IF EXISTS product_variants;
DROP TABLE IF EXISTS products;
DROP TABLE IF EXISTS categories;
DROP TABLE IF EXISTS settings;
DROP TABLE IF EXISTS password_resets;
DROP TABLE IF EXISTS users;

SET FOREIGN_KEY_CHECKS = 1;

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
  FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE payments (
  id INT AUTO_INCREMENT PRIMARY KEY,
  order_id INT NOT NULL,
  type ENUM('dp','final','full') NOT NULL,
  method VARCHAR(80) DEFAULT 'Virtual Account BRI',
  amount DECIMAL(14,2) NOT NULL,
  status ENUM('pending','berhasil','gagal') DEFAULT 'pending',
  paid_at DATETIME NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE invoices (
  id INT AUTO_INCREMENT PRIMARY KEY,
  order_id INT NOT NULL UNIQUE,
  invoice_number VARCHAR(60) NOT NULL UNIQUE,
  issued_date DATETIME DEFAULT CURRENT_TIMESTAMP,
  due_date DATETIME NULL,
  status ENUM('DRAFT', 'ISSUED', 'PAID', 'CANCELLED') DEFAULT 'ISSUED',
  notes TEXT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE receipts (
  id INT AUTO_INCREMENT PRIMARY KEY,
  payment_id INT NOT NULL UNIQUE,
  order_id INT NOT NULL,
  receipt_number VARCHAR(60) NOT NULL UNIQUE,
  issued_date DATETIME DEFAULT CURRENT_TIMESTAMP,
  amount DECIMAL(14,2) NOT NULL,
  payment_type VARCHAR(50) NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (payment_id) REFERENCES payments(id) ON DELETE CASCADE,
  FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE production_queue (
  id INT AUTO_INCREMENT PRIMARY KEY,
  order_id INT NOT NULL UNIQUE,
  queue_number INT NOT NULL,
  priority INT DEFAULT 0,
  estimated_start_date DATE NULL,
  estimated_end_date DATE NULL,
  production_status ENUM('WAITING', 'PRODUCING', 'COMPLETED', 'CANCELLED') DEFAULT 'WAITING',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE production_schedule (
  id INT AUTO_INCREMENT PRIMARY KEY,
  order_id INT NOT NULL,
  start_date DATE NOT NULL,
  end_date DATE NOT NULL,
  status VARCHAR(50) DEFAULT 'SCHEDULED',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE order_status_history (
  id INT AUTO_INCREMENT PRIMARY KEY,
  order_id INT NOT NULL,
  old_status VARCHAR(50) NULL,
  new_status VARCHAR(50) NOT NULL,
  changed_by INT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

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

INSERT INTO products(category_id, name, code, description, size, price, production_duration, status) VALUES
(1, 'Pelaminan Royal Gold', 'PLM-001', 'Pelaminan mewah berbalut kain satin dengan ornamen emas, cocok untuk pernikahan indoor maupun outdoor.', 'Medium (P: 10 m × T: 5 m)', 8500000, 5, 'Aktif'),
(1, 'Pelaminan Maroon Elegan', 'PLM-002', 'Pelaminan bertema maroon elegan dengan sentuhan champagne, memberikan kesan mewah dan romantis.', 'Large (P: 14 m × T: 7 m)', 7200000, 5, 'Aktif'),
(1, 'Pelaminan Ivory Classic', 'PLM-003', 'Pelaminan klasik bernuansa ivory putih dengan detail ukiran kayu halus.', 'Small (P: 6 m × T: 3 m)', 6500000, 3, 'Aktif'),
(1, 'Pelaminan Eropa Motif Tengah Lengkung', 'PLM-006', 'Pelaminan Eropa mewah dengan ornamen ukiran khas dan motif lengkungan tengah yang anggun, dilengkapi set kursi pengantin & pendamping.', 'T: 4m x L: 10m', 30000000, 4, 'Aktif'),
(1, 'Pelaminan Modern Minimalis', 'PLM-007', 'Pelaminan modern minimalis dengan aksen ukiran kelopak bunga lotus mewah dan kombinasi warna elegan.', 'T: 3,5m x L: 9m', 25000000, 3, 'Aktif'),
(1, 'Pelaminan Tirai Lengkung', 'PLM-008', 'Pelaminan tirai lengkung mewah berkonsep Eropa klasik dengan detail ukiran halus, balkon mini, serta tirai latar elegan.', 'T: 4 m x L: 9m', 22000000, 3, 'Aktif'),
(1, 'Pelaminan Eropa Pilar Ukir', 'PLM-010', 'Pelaminan Eropa Pilar Ukir berkonsep istana megah bernuansa Eropa dengan detail pilar relief ukiran emas presisi, ornamen mahkota, cermin oval tengah yang anggun, serta set kursi sofa pengantin dan pendamping berukir emas mewah.', 'T: 4m x L: 9m', 26000000, 4, 'Aktif'),
(1, 'Pelaminan Villa Eropa', 'PLM-011', 'Pelaminan Villa Eropa berkonsep arsitektur hunian klasik Eropa dengan fasad jendela kayu elegan, balkon ukir, pilar corinthian presisi, serta set sofa pengantin dan pendamping bergaya modern luxury.', 'T: 4m x L: 10m', 28000000, 4, 'Aktif'),
(1, 'Pelaminan Kastil Eropa', 'PLM-012', 'Pelaminan Kastil Eropa berkonsep istana kerajaan klasik dengan multiple menara kubah megah, relief ukiran gothic emas presisi, jendela kayu kerajaan, serta set kursi tahta pengantin dan pendamping berukir emas mewah.', 'T: 4,5m x L: 11m', 35000000, 5, 'Aktif'),
(1, 'Pelaminan Istana Corinthian', 'PLM-013', 'Pelaminan Istana Corinthian berkonsep kuil megah Romawi Klasik dengan pilar relief corinthian emas ganda, ukiran pediment mahkota bunga yang anggun, ornamen ukiran lengkung filigree emas, serta set sofa tahta pengantin berukir emas mewah.', 'T: 4m x L: 10m', 32000000, 4, 'Aktif'),
(2, 'Gazebo Lingkar Corinthian', 'GZB-001', 'Gazebo Lingkar Corinthian berkonsep kuil lingkar Eropa klasik dengan 4 pilar corinthian relief kokoh, atap balustrade melingkar presisi, serta panggung bundar berbahan MDF premium.', 'T: 4m x L: 6m', 5000000, 3, 'Aktif'),
(2, 'Gazebo Balkon Ukir Eropa', 'GZB-002', 'Gazebo Balkon Ukir Eropa berkonsep paviliun balkon klasik dengan mahkota ukiran filigree halus, pilar bermotif kisi silang, serta pagar pembatas balustrade elegan berbahan kayu MDF solid.', 'T: 4m x L: 6m', 4500000, 3, 'Aktif'),
(2, 'Gazebo Gerbang Kisi Ukir', 'GZB-004', 'Gazebo Gerbang Kisi Ukir berkonsep paviliun gerbang klasik dengan kubah archway lengkung kisi silang, pilar relief persegi corinthian kokoh, serta mahkota cornice rata bermotif ukiran halus.', 'T: 4m x L: 6m', 4800000, 3, 'Aktif'),
(3, 'Pot Bunga Rose Premium', 'PB-001', 'Rangkaian bunga mawar premium dalam pot tinggi elegan.', '-', 850000, 2, 'Aktif'),
(3, 'Pot Bunga Lily White', 'PB-002', 'Rangkaian bunga lily putih dalam vase kristal tinggi.', '-', 750000, 2, 'Aktif'),
(4, 'Kotak Akas Emas Premium', 'KA-001', 'Kotak amplop bertahta ornamen emas dengan bahan MDF premium.', '-', 450000, 2, 'Aktif'),
(4, 'Kotak Akas Maroon Royal', 'KA-002', 'Kotak amplop berwarna maroon dengan detail ukiran elegan.', '-', 380000, 2, 'Tidak Aktif'),
(4, 'Kotak Akas Bintang Ukir', 'KA-005', 'Kotak Akas Bintang Ukir berkonsep kotak uang/amplop kubah masjid islami bernuansa mewah dengan relief ukiran bintang segi-8, lis bingkai lengkung kerawang, serta bahan kayu MDF premium.', 'T: 90cm x L: 45cm x P: 45cm', 500000, 2, 'Aktif'),
(4, 'Kotak Akas Kubah Ukir Arabesq', 'KA-006', 'Kotak Akas Kubah Ukir Arabesq berkonsep kotak amplop tahta masjid dengan puncak mahkota kubah bawang (onion dome), lis pilar bulat corinthian, serta panel ukiran floral arabesque khas Timur Tengah yang sangat halus.', 'T: 95cm x L: 45cm x P: 45cm', 550000, 2, 'Aktif'),
(4, 'Kotak Akas Kipas Art Deco', 'KA-007', 'Kotak Akas Kipas Art Deco berkonsep kotak amplop ukiran kipas palmet eropa klasik bernuansa modern vintage dengan tinggi presisi 70 cm, lis pilar relief kerang, serta bahan kayu MDF premium.', 'T: 70cm x L: 45cm x P: 45cm', 480000, 2, 'Aktif'),
(4, 'Kotak Akas Kisi Silang Lotus', 'KA-008', 'Kotak Akas Kisi Silang Lotus berkonsep kotak amplop ukiran anyaman geometris 3D dengan ornamen pusat kelopak bunga lotus murni, lis pilar garis ganda, serta bahan kayu MDF premium.', 'T: 90cm x L: 45cm x P: 45cm', 500000, 2, 'Aktif');

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
