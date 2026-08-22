<footer class="footer">
  <div class="container">
    <div class="footer-grid">
      <div>
        <div class="brand"><img src="<?= BASE_URL ?>/assets/img/logo.png" alt="Logo Distributor Pelaminan Family" class="logo"><span>Distributor Pelaminan<br><small>Family · Palembang</small></span></div>
        <p>Mewujudkan hari istimewa Anda dengan dekorasi pelaminan, gazebo, pot bunga, dan aksesori premium.</p>
      </div>
      <div>
        <h4>Kontak</h4>
        <p>Jl. Betawi Raya RS. Benteng, Perumahan Kencana Indah Blok C.1 No. 17, Palembang</p>
        <p>Instagram: <a href="https://www.instagram.com/pengerajin_pelaminan_modern/" target="_blank" rel="noopener noreferrer" style="color: inherit; text-decoration: underline;">@pengerajin_pelaminan_modern</a></p>
      </div>
      <div>
        <h4>Navigasi</h4>
        <p><a href="<?= BASE_URL ?>/index.php">Beranda</a></p>
        <p><a href="<?= BASE_URL ?>/gallery.php">Galeri Produk</a></p>
        <p><a href="<?= BASE_URL ?>/login.php">Login Customer/Admin</a></p>
      </div>
    </div>
    <div class="copyright">© <?= date('Y') ?> Distributor Pelaminan Family. Hak cipta dilindungi.</div>
  </div>
</footer>

<script>
document.addEventListener('DOMContentLoaded', function () {
  const menuToggle = document.getElementById('mobile-menu-toggle');
  const navDrawer = document.getElementById('main-navigation');

  if (menuToggle && navDrawer) {
    menuToggle.addEventListener('click', function () {
      const isOpen = navDrawer.classList.toggle('is-open');
      menuToggle.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
      menuToggle.innerHTML = isOpen ? '✕' : '☰';
    });

    navDrawer.querySelectorAll('a').forEach(link => {
      link.addEventListener('click', function () {
        navDrawer.classList.remove('is-open');
        menuToggle.setAttribute('aria-expanded', 'false');
        menuToggle.innerHTML = '☰';
      });
    });
  }
});
</script>
</body>
</html>
