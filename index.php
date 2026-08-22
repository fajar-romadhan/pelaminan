<?php
require_once 'config/database.php';
require_once 'config/helpers.php';

$pageTitle = 'Beranda - Distributor Pelaminan Family';
$active = 'home';

$stmt = $pdo->query("SELECT p.*, c.name AS category_name FROM products p JOIN categories c ON c.id=p.category_id WHERE p.status='Aktif' ORDER BY p.id ASC LIMIT 3");
$featured = $stmt->fetchAll();

include 'includes/header.php';
?>
<section class="hero">
  <div class="container hero-grid">
    <div>
      <div class="eyebrow">★ Terpercaya sejak 2014 · Palembang</div>
      <h1>Wujudkan Pernikahan <span>Impian Anda</span></h1>
      <p>Kami menyediakan dekorasi pelaminan, gazebo, pot bunga, dan aksesori pernikahan premium dengan alur pemesanan online yang mudah.</p>
      <div class="actions">
        <a class="btn btn-primary" href="<?= BASE_URL ?>/gallery.php">Lihat Katalog →</a>
        <a class="btn btn-outline" href="https://wa.me/6281273400312" target="_blank" rel="noopener noreferrer">Hubungi Kami</a>
      </div>
    </div>
    <div class="hero-card">
      <img src="<?= BASE_URL ?>/assets/img/hero_pelaminan_transparent.png" alt="Dekorasi Pelaminan Premium" style="width:100%;max-width:340px;height:auto;margin-bottom:16px;filter:drop-shadow(0 12px 24px rgba(54,34,23,0.14));">
      <div>Dekorasi Pelaminan Premium</div>
      <small style="font-family:var(--font-body);font-size:13px;color:var(--muted);font-weight:500;margin-top:6px">Desain Elegan & Pengalaman 10+ Tahun</small>
    </div>
  </div>
</section>

<section class="section">
  <div class="container">
    <div class="section-title">
      <h2>Mengapa Memilih Kami?</h2>
      <div class="line"></div>
      <p>Layanan profesional dengan katalog online lengkap, kustomisasi warna & dekorasi, serta sistem pemesanan yang mudah.</p>
    </div>
    <div class="grid grid-4">
      <div class="card"><div class="feature-icon">🏆</div><h3>Pengalaman 10+ Tahun</h3><p>Telah melayani banyak pasangan di Sumatera Selatan.</p></div>
      <div class="card"><div class="feature-icon">🎨</div><h3>Desain Custom</h3><p>Dekorasi dapat disesuaikan tema dan warna acara.</p></div>
      <div class="card"><div class="feature-icon">🛡️</div><h3>Terpercaya</h3><p>Admin dapat mengelola produk, jadwal, tarif, dan pesanan.</p></div>
      <div class="card"><div class="feature-icon">⚡</div><h3>Pemesanan Mudah</h3><p>Customer dapat pesan, bayar, dan tracking langsung.</p></div>
    </div>
  </div>
</section>



<?php include 'includes/footer.php'; ?>
