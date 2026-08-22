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
<main class="container" style="padding-top:34px">
  <p><a style="color:var(--terracotta-dark);font-weight:900" href="<?= BASE_URL ?>/gallery.php">← Kembali ke Galeri</a></p>
  
  <div class="grid grid-2" style="align-items:start;margin-top:20px">
    <div>
      <div style="width:100%;aspect-ratio:16/9;min-height:360px;max-height:560px;overflow:hidden;border-radius:16px;margin-bottom:14px;background:#ffffff;border:1px solid var(--border-subtle);display:flex;align-items:center;justify-content:center;padding:8px;">
        <?php if (!empty($product['image_url'])): ?>
          <img id="mainProductImage" src="<?= $baseImgUrl ?>" alt="<?= e($product['name']) ?>" style="width:100%;height:100%;object-fit:contain;object-position:center;transition:all 0.3s ease;" onerror="this.src='<?= BASE_URL ?>/assets/img/no-image.png'">
        <?php else: ?>
          <div class="image-placeholder" style="height:100%"><div class="icon-big">🏛️</div>Foto Utama Produk</div>
        <?php endif; ?>
      </div>

      <div style="background:#fff;border:1px solid var(--border-subtle);border-radius:14px;padding:16px;">
        <h4 style="font-size:15px;color:var(--espresso);margin:0 0 10px;">Pilihan Warna (Foto Asli Pelaminan):</h4>
        <div class="variant-cards-grid" style="display:grid;grid-template-columns:repeat(auto-fill, minmax(110px, 1fr));gap:10px;">
          <!-- 1. Base Product Photo Card (Always available to return to base photo) -->
          <div class="variant-card active" 
               data-id="" 
               data-name="Warna Utama (Original)" 
               data-img="<?= $baseImgUrl ?>"
               style="border:2px solid var(--terracotta-dark);border-radius:10px;padding:6px;cursor:pointer;background:#fff;text-align:center;transition:all 0.2s ease;">
            <img src="<?= $baseImgUrl ?>" alt="Warna Utama" style="width:100%;height:70px;object-fit:contain;object-position:center;border-radius:6px;margin-bottom:4px;" onerror="this.src='<?= BASE_URL ?>/assets/img/no-image.png'">
            <span style="font-size:11px;font-weight:700;color:var(--espresso);display:block;">Warna Utama</span>
          </div>

          <!-- 2. Photo Color Variants from product_variants -->
          <?php foreach ($variants as $v): 
            $vImg = !empty($v['image']) ? BASE_URL . '/uploads/products/variants/' . e($v['image']) : $baseImgUrl;
          ?>
            <div class="variant-card" 
                 data-id="<?= (int)$v['id'] ?>" 
                 data-name="<?= e($v['variant_name']) ?>" 
                 data-img="<?= $vImg ?>"
                 style="border:2px solid var(--border-subtle);border-radius:10px;padding:6px;cursor:pointer;background:#fff;text-align:center;transition:all 0.2s ease;">
              <img src="<?= $vImg ?>" alt="<?= e($v['variant_name']) ?>" style="width:100%;height:70px;object-fit:contain;object-position:center;border-radius:6px;margin-bottom:4px;" onerror="this.src='<?= BASE_URL ?>/assets/img/no-image.png'">
              <span style="font-size:11px;font-weight:700;color:var(--espresso);display:block;"><?= e($v['variant_name']) ?></span>
            </div>
          <?php endforeach; ?>
        </div>
      </div>
    </div>
    
    <div class="card">
      <span class="tag"><?= e($product['category_name']) ?></span>
      <h1 style="font-size:30px;color:var(--espresso);margin-top:12px">
        <?= e($product['name']) ?>
        <span id="selectedVariantName" style="color:var(--terracotta-dark);font-size:20px;display:block;margin-top:4px;"></span>
      </h1>
      <p><span class="badge badge-muted">Kode: <?= e($product['code']) ?></span> <span class="badge badge-muted"><?= e($product['size']) ?></span></p>
      <p style="font-size:15px;color:var(--muted);line-height:1.5;"><?= e($product['description']) ?></p>
      
      <div class="price-box" style="background:linear-gradient(90deg,rgba(247,231,206,.7),#fff);border:1px solid rgba(212,175,55,.25);border-radius:16px;padding:18px;margin:24px 0">
        <small class="muted">Harga Mulai Dari</small>
        <div class="product-price" style="font-size:32px;font-weight:900;color:var(--terracotta-dark)"><?= rupiah($product['price']) ?></div>
      </div>
      
      <div class="actions" style="display:flex;flex-direction:column;gap:10px">
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
            variantCards.forEach(c => {
                c.style.borderColor = 'var(--border-subtle)';
                c.style.boxShadow = 'none';
                c.classList.remove('active');
            });
            this.style.borderColor = 'var(--terracotta-dark)';
            this.style.boxShadow = '0 2px 8px rgba(183,110,121,0.25)';
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
