<?php
require_once 'config/database.php';
require_once 'config/helpers.php';

$pageTitle = 'Galeri Produk';
$active = 'gallery';

$cat = $_GET['cat'] ?? 'Semua';
$q = trim($_GET['q'] ?? '');

$categories = $pdo->query('SELECT * FROM categories ORDER BY name')->fetchAll();

$sql = "SELECT p.*, c.name AS category_name FROM products p JOIN categories c ON c.id=p.category_id WHERE p.status='Aktif'";
$params = [];

if ($cat !== 'Semua' && $cat !== '') {
    $sql .= ' AND c.name = ?';
    $params[] = $cat;
}

if ($q !== '') {
    $sql .= ' AND p.name LIKE ?';
    $params[] = "%$q%";
}

$sql .= ' ORDER BY p.id DESC';
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$products = $stmt->fetchAll();

include 'includes/header.php';
?>
<div class="page-head"><div class="container"><h1>Galeri Produk</h1><p>Temukan dekorasi pernikahan impian Anda</p></div></div>
<main class="container" style="padding-top:30px">
  <form class="filters" method="get" action="<?= BASE_URL ?>/gallery.php">
    <div class="chips">
      <a class="chip <?= $cat === 'Semua' ? 'active' : '' ?>" href="<?= BASE_URL ?>/gallery.php">Semua</a>
      <?php foreach ($categories as $c): ?>
        <a class="chip <?= $cat === $c['name'] ? 'active' : '' ?>" href="<?= BASE_URL ?>/gallery.php?cat=<?= urlencode($c['name']) ?>"><?= e($c['name']) ?></a>
      <?php endforeach; ?>
    </div>
    <div style="display:flex;gap:10px">
      <?php if ($cat !== 'Semua' && $cat !== ''): ?>
        <input type="hidden" name="cat" value="<?= e($cat) ?>">
      <?php endif; ?>
      <input class="input" name="q" value="<?= e($q) ?>" placeholder="Cari produk..." style="width:240px">
      <button class="btn btn-primary" type="submit">Cari</button>
    </div>
  </form>

  <?php if (!$products): ?>
    <div class="card" style="text-align:center;padding:70px"><div class="icon-big">📦</div><p>Tidak ada produk ditemukan.</p></div>
  <?php else: ?>
    <div class="grid grid-3">
      <?php foreach ($products as $p): ?>
      <div class="card product-card">
        <?php if (!empty($p['image_url'])): ?>
          <div class="product-img" style="height:220px;overflow:hidden;border-radius:12px;margin-bottom:12px;background:#f8f9fa;display:flex;align-items:center;justify-content:center;border:1px solid var(--border-subtle);">
            <img src="<?= BASE_URL ?>/uploads/products/<?= e($p['image_url']) ?>" alt="<?= e($p['name']) ?>" style="width:100%;height:100%;object-fit:contain;" onerror="this.src='<?= BASE_URL ?>/assets/img/no-image.png'">
          </div>
        <?php else: ?>
          <div class="image-placeholder product-img"><div class="icon-big">🌸</div><?= e($p['name']) ?></div>
        <?php endif; ?>
        <div class="product-body">
          <span class="tag"><?= e($p['category_name']) ?></span>
          <h3><?= e($p['name']) ?></h3>
          <div class="price">Mulai dari <?= rupiah($p['price']) ?></div>
          <a class="btn btn-primary btn-block" href="<?= BASE_URL ?>/product.php?id=<?= (int)$p['id'] ?>">Lihat Detail</a>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</main>
<?php include 'includes/footer.php'; ?>
