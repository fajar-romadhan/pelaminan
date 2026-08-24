<?php
require_once 'config/database.php';
require_once 'config/helpers.php';

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$id) {
    redirect(BASE_URL . '/gallery.php');
}

$stmt = $pdo->prepare('SELECT p.*, c.name AS category_name FROM products p JOIN categories c ON c.id=p.category_id WHERE p.id=? LIMIT 1');
$stmt->execute([$id]);
$product = $stmt->fetch();

if (!$product) { 
    redirect(BASE_URL . '/gallery.php'); 
}

// Fetch photo color variants for this product
$variantStmt = $pdo->prepare("SELECT * FROM product_variants WHERE product_id = ? ORDER BY id ASC");
$variantStmt->execute([$id]);
$variants = $variantStmt->fetchAll();

$pageTitle = 'Detail Produk - ' . $product['name'];
$active = 'gallery';
include 'includes/header.php';

$baseImgUrl = !empty($product['image_url']) ? BASE_URL . '/uploads/products/' . e($product['image_url']) : BASE_URL . '/assets/img/no-image.png';
?>
<main class="container product-detail-page">
  <p><a class="btn-back-nav" href="<?= BASE_URL ?>/gallery.php"><span class="icon-arrow">←</span> Kembali ke Galeri</a></p>
  
  <div class="grid grid-2 product-detail-grid">
    <div>
      <div class="product-main-preview">
        <?php if (!empty($product['image_url'])): ?>
          <img id="mainProductImage" src="<?= $baseImgUrl ?>" alt="<?= e($product['name']) ?>" onerror="this.src='<?= BASE_URL ?>/assets/img/no-image.png'">
        <?php else: ?>
          <div class="image-placeholder" style="height:100%"><div class="icon-big">🏛️</div>Foto Utama Produk</div>
        <?php endif; ?>
      </div>

      <div class="product-variants-section">
        <h4>Pilihan Warna (Foto Asli Pelaminan):</h4>
        <div class="variant-cards-grid">
          <!-- 1. Base Product Photo Card (Always available to return to base photo) -->
          <div class="variant-card active" 
               data-id="" 
               data-name="Warna Utama (Original)" 
               data-img="<?= $baseImgUrl ?>">
            <img src="<?= $baseImgUrl ?>" alt="Warna Utama" onerror="this.src='<?= BASE_URL ?>/assets/img/no-image.png'">
            <span>Warna Utama</span>
          </div>

          <!-- 2. Photo Color Variants from product_variants -->
          <?php foreach ($variants as $v): 
            $vImg = !empty($v['image']) ? BASE_URL . '/uploads/products/variants/' . e($v['image']) : $baseImgUrl;
          ?>
            <div class="variant-card" 
                 data-id="<?= (int)$v['id'] ?>" 
                 data-name="<?= e($v['variant_name']) ?>" 
                 data-img="<?= $vImg ?>">
              <img src="<?= $vImg ?>" alt="<?= e($v['variant_name']) ?>" onerror="this.src='<?= BASE_URL ?>/assets/img/no-image.png'">
              <span><?= e($v['variant_name']) ?></span>
            </div>
          <?php endforeach; ?>
        </div>
      </div>
    </div>
    
    <div class="card product-info-card">
      <span class="tag"><?= e($product['category_name']) ?></span>
      <h1 class="product-detail-title">
        <?= e($product['name']) ?>
        <span id="selectedVariantName" class="selected-variant-subtitle"></span>
      </h1>
      <p><span class="badge badge-muted">Kode: <?= e($product['code']) ?></span> <span class="badge badge-muted"><?= e($product['size']) ?></span></p>
      <p class="product-desc"><?= e($product['description']) ?></p>
      
      <div class="price-box">
        <small class="muted">Harga Mulai Dari</small>
        <div class="product-price"><?= rupiah($product['price']) ?></div>
      </div>
      
      <div class="actions">
        <a id="btnOrderNow" class="btn btn-primary btn-block" href="<?= BASE_URL ?>/order.php?id=<?= (int)$product['id'] ?>">🛒 Pesan Sekarang</a>
        <?php if (strtolower(trim($product['category_name'])) === 'pelaminan'): ?>
          <a id="btnCustomize" class="btn btn-outline btn-block" href="<?= BASE_URL ?>/customization.php?id=<?= (int)$product['id'] ?>">🎨 Kustomisasi Produk Ini</a>
        <?php endif; ?>
        <a id="btnAddCart" class="btn btn-outline btn-block" href="<?= BASE_URL ?>/customers/add_cart.php?id=<?= (int)$product['id'] ?>&csrf_token=<?= e(csrf_token()) ?>">🛒 Tambah Keranjang</a>
      </div>
    </div>
  </div>
</main>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const mainImg = document.getElementById('mainProductImage');
    const variantCards = document.querySelectorAll('.variant-card');
    const selectedVariantName = document.getElementById('selectedVariantName');
    const btnOrder = document.getElementById('btnOrderNow');
    const btnCustom = document.getElementById('btnCustomize');
    const btnCart = document.getElementById('btnAddCart');

    variantCards.forEach(card => {
        card.addEventListener('click', function() {
            variantCards.forEach(c => c.classList.remove('active'));
            this.classList.add('active');

            const imgUrl = this.getAttribute('data-img');
            const vName = this.getAttribute('data-name');
            const vId = this.getAttribute('data-id');

            if (mainImg) mainImg.src = imgUrl;

            if (vId && vName) {
                if (selectedVariantName) selectedVariantName.textContent = '(Warna: ' + vName + ')';
                if (btnOrder) btnOrder.href = '<?= BASE_URL ?>/order.php?id=<?= (int)$product['id'] ?>&variant_id=' + vId + '&variant_name=' + encodeURIComponent(vName);
                if (btnCustom) btnCustom.href = '<?= BASE_URL ?>/customization.php?id=<?= (int)$product['id'] ?>&variant_id=' + vId + '&variant_name=' + encodeURIComponent(vName);
                if (btnCart) btnCart.href = '<?= BASE_URL ?>/customers/add_cart.php?id=<?= (int)$product['id'] ?>&variant_id=' + vId + '&variant_name=' + encodeURIComponent(vName) + '&csrf_token=<?= e(csrf_token()) ?>';
            } else {
                // Return to Base Photo / Original State
                if (selectedVariantName) selectedVariantName.textContent = '';
                if (btnOrder) btnOrder.href = '<?= BASE_URL ?>/order.php?id=<?= (int)$product['id'] ?>';
                if (btnCustom) btnCustom.href = '<?= BASE_URL ?>/customization.php?id=<?= (int)$product['id'] ?>';
                if (btnCart) btnCart.href = '<?= BASE_URL ?>/customers/add_cart.php?id=<?= (int)$product['id'] ?>&csrf_token=<?= e(csrf_token()) ?>';
            }
        });
    });
});
</script>

<?php include 'includes/footer.php'; ?>
