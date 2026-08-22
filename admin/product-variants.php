<?php
require_once '../config/database.php';
require_once '../config/helpers.php';
require_admin();



$pageTitle = 'Kelola Variasi Warna Produk';
$active = 'variants';

$filterCategoryId = filter_input(INPUT_GET, 'category_id', FILTER_VALIDATE_INT) ?: 0;
$filterProductId = filter_input(INPUT_GET, 'product_id', FILTER_VALIDATE_INT) ?: 0;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf_token($_POST['csrf_token'] ?? null);



    $id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT) ?: 0;
    $productId = filter_input(INPUT_POST, 'product_id', FILTER_VALIDATE_INT);
    $variantName = trim($_POST['variant_name'] ?? '');

    if (!$productId || empty($variantName)) {
        set_flash('danger', 'Kategori, produk, dan nama warna variasi wajib diisi.');
        redirect(BASE_URL . '/admin/product-variants.php' . ($productId ? '?product_id=' . $productId : ''));
    }

    // Verify product exists
    $prodStmt = $pdo->prepare('SELECT id FROM products WHERE id=? LIMIT 1');
    $prodStmt->execute([$productId]);
    if (!$prodStmt->fetch()) {
        set_flash('danger', 'Produk yang dipilih tidak ditemukan.');
        redirect(BASE_URL . '/admin/product-variants.php');
    }

    // Fetch existing image if editing
    $existingImage = null;
    if ($id > 0) {
        $chkStmt = $pdo->prepare('SELECT image FROM product_variants WHERE id=? LIMIT 1');
        $chkStmt->execute([$id]);
        $existingImage = $chkStmt->fetchColumn() ?: null;
    }

    $imageName = $existingImage;

    if (isset($_FILES['image']) && $_FILES['image']['error'] !== UPLOAD_ERR_NO_FILE) {
        try {
            $targetFolder = BASE_PATH . '/uploads/products/variants';
            $imageName = upload_image($_FILES['image'], $targetFolder, 5242880); // 5MB max
        } catch (Exception $e) {
            set_flash('danger', 'Gagal upload foto variasi: ' . $e->getMessage());
            redirect(BASE_URL . '/admin/product-variants.php?product_id=' . $productId . ($id > 0 ? '&edit=' . $id : ''));
        }
    }

    if ($id === 0 && empty($imageName)) {
        set_flash('danger', 'Foto variasi warna wajib diunggah.');
        redirect(BASE_URL . '/admin/product-variants.php?product_id=' . $productId);
    }

    if ($id > 0) {
        $stmt = $pdo->prepare('UPDATE product_variants SET product_id=?, variant_name=?, image=? WHERE id=?');
        $stmt->execute([$productId, $variantName, $imageName, $id]);
        set_flash('success', 'Variasi warna berhasil diperbarui.');
    } else {
        $stmt = $pdo->prepare('INSERT INTO product_variants (product_id, variant_name, image) VALUES (?, ?, ?)');
        $stmt->execute([$productId, $variantName, $imageName]);
        set_flash('success', 'Variasi warna baru berhasil ditambahkan.');
    }

    redirect(BASE_URL . '/admin/product-variants.php?product_id=' . $productId);
}

if (isset($_GET['action']) && $_GET['action'] === 'delete') {
    verify_csrf_token($_GET['csrf_token'] ?? null);
    $deleteId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
    if ($deleteId) {
        $stmt = $pdo->prepare('DELETE FROM product_variants WHERE id=?');
        $stmt->execute([$deleteId]);
        set_flash('success', 'Variasi warna berhasil dihapus.');
    }
    redirect(BASE_URL . '/admin/product-variants.php' . ($filterProductId ? '?product_id=' . $filterProductId : ($filterCategoryId ? '?category_id=' . $filterCategoryId : '')));
}

$edit = null;
if (isset($_GET['edit'])) {
    $editId = filter_input(INPUT_GET, 'edit', FILTER_VALIDATE_INT);
    if ($editId) {
        $stmt = $pdo->prepare('SELECT * FROM product_variants WHERE id=? LIMIT 1');
        $stmt->execute([$editId]);
        $edit = $stmt->fetch();
        if ($edit) {
            $filterProductId = $edit['product_id'];
        }
    }
}

// Fetch categories and products for 2-step select
$categories = $pdo->query('SELECT id, name FROM categories ORDER BY id ASC')->fetchAll();
$products = $pdo->query('SELECT id, category_id, name, code FROM products ORDER BY name ASC')->fetchAll();

// Determine pre-selected category and product
$selectedProductId = $edit['product_id'] ?? $filterProductId ?? 0;
$selectedCategoryId = $filterCategoryId;

if ($selectedProductId > 0) {
    foreach ($products as $p) {
        if ($p['id'] == $selectedProductId) {
            $selectedCategoryId = $p['category_id'];
            break;
        }
    }
}

// Fetch variants list with product & category info
$sql = '
    SELECT v.*, p.name AS product_name, p.code AS product_code, c.name AS category_name
    FROM product_variants v 
    JOIN products p ON p.id = v.product_id 
    JOIN categories c ON c.id = p.category_id
';
$params = [];
$where = [];

if ($filterCategoryId > 0) {
    $where[] = 'p.category_id = ?';
    $params[] = $filterCategoryId;
}

if ($filterProductId > 0) {
    $where[] = 'v.product_id = ?';
    $params[] = $filterProductId;
}

if ($where) {
    $sql .= ' WHERE ' . implode(' AND ', $where);
}

$sql .= ' ORDER BY c.id ASC, p.name ASC, v.id DESC';

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$variants = $stmt->fetchAll();

include '../includes/admin_header.php';
?>

<div class="card" style="margin-bottom:20px">
  <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:16px;">
    <h3 style="color:var(--terracotta-dark);margin:0"><?= $edit ? 'Edit Variasi Warna' : 'Tambah Variasi Warna Baru' ?></h3>
    <a href="<?= BASE_URL ?>/admin/products.php" class="btn btn-outline btn-sm">← Kembali ke Kelola Produk</a>
  </div>

  <form method="post" enctype="multipart/form-data">
    <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
    <input type="hidden" name="id" value="<?= e($edit['id'] ?? 0) ?>">
    
    <div style="display:grid;grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));gap:16px;">
      <div class="form-group">
        <label style="font-weight:600;color:var(--espresso);">1. Pilih Kategori Produk</label>
        <select class="select" id="form_category_id" required style="border-color:var(--terracotta);background:#fffaf5;">
          <option value="">-- Pilih Kategori Produk --</option>
          <?php foreach ($categories as $c): ?>
            <option value="<?= (int)$c['id'] ?>" <?= ($selectedCategoryId == $c['id']) ? 'selected' : '' ?>>
              📂 <?= e($c['name']) ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>

      <div class="form-group">
        <label style="font-weight:600;color:var(--espresso);">2. Pilih Produk</label>
        <select class="select" id="form_product_id" name="product_id" required style="border-color:var(--terracotta);background:#fffaf5;">
          <option value="">-- Pilih Kategori Terlebih Dahulu --</option>
          <?php foreach ($products as $p): ?>
            <option value="<?= (int)$p['id'] ?>" data-category="<?= (int)$p['category_id'] ?>" <?= ($selectedProductId == $p['id']) ? 'selected' : '' ?>>
              <?= e($p['name']) ?> (<?= e($p['code']) ?>)
            </option>
          <?php endforeach; ?>
        </select>
      </div>

      <div class="form-group">
        <label style="font-weight:600;color:var(--espresso);">3. Nama Warna Variasi</label>
        <input class="input" name="variant_name" value="<?= e($edit['variant_name'] ?? '') ?>" required placeholder="Contoh: Gold Krem, Rose Gold">
      </div>

      <div class="form-group">
        <label style="font-weight:600;color:var(--espresso);">4. Foto Variasi (Max 5MB)</label>
        <input class="input" type="file" name="image" accept="image/jpeg,image/png,image/webp" <?= $edit ? '' : 'required' ?>>
        <?php if (!empty($edit['image'])): ?>
          <small class="muted" style="display:block;margin-top:4px;">Foto saat ini: <?= e($edit['image']) ?></small>
        <?php endif; ?>
      </div>
    </div>

    <div style="margin-top:16px;">
      <button class="btn btn-primary" type="submit">💾 Simpan Variasi Warna</button>
      <?php if ($edit): ?>
        <a class="btn btn-outline" href="<?= BASE_URL ?>/admin/product-variants.php<?= $filterProductId ? '?product_id='.$filterProductId : '' ?>">Batal Edit</a>
      <?php endif; ?>
    </div>
  </form>
</div>

<!-- Filter Bar 2-Step -->
<div class="card" style="margin-bottom:20px;padding:14px 20px;">
  <form method="get" id="filter_form" style="display:flex;align-items:center;gap:12px;flex-wrap:wrap;">
    <label style="font-weight:700;color:var(--espresso);white-space:nowrap;">🔎 Filter Variasi:</label>
    
    <select class="select" id="filter_category_id" name="category_id" onchange="onFilterCategoryChange()" style="max-width:220px;">
      <option value="0">-- Semua Kategori --</option>
      <?php foreach ($categories as $c): ?>
        <option value="<?= (int)$c['id'] ?>" <?= ($filterCategoryId == $c['id']) ? 'selected' : '' ?>>
          📂 <?= e($c['name']) ?>
        </option>
      <?php endforeach; ?>
    </select>

    <select class="select" id="filter_product_id" name="product_id" onchange="this.form.submit()" style="max-width:320px;">
      <option value="0">-- Semua Produk --</option>
      <?php foreach ($products as $p): ?>
        <option value="<?= (int)$p['id'] ?>" data-category="<?= (int)$p['category_id'] ?>" <?= ($filterProductId == $p['id']) ? 'selected' : '' ?>>
          <?= e($p['name']) ?> (<?= e($p['code']) ?>)
        </option>
      <?php endforeach; ?>
    </select>

    <?php if ($filterCategoryId > 0 || $filterProductId > 0): ?>
      <a href="<?= BASE_URL ?>/admin/product-variants.php" class="btn btn-outline btn-sm">Reset Filter</a>
    <?php endif; ?>
  </form>
</div>

<div class="table-wrap">
  <table class="table">
    <thead>
      <tr>
        <th style="width:80px">Foto</th>
        <th>Kategori</th>
        <th>Nama Produk</th>
        <th>Warna Variasi</th>
        <th>Dibuat Pada</th>
        <th style="width:160px">Aksi</th>
      </tr>
    </thead>
    <tbody>
    <?php foreach ($variants as $v): 
      $variantImg = !empty($v['image']) ? BASE_URL . '/uploads/products/variants/' . e($v['image']) : BASE_URL . '/assets/img/no-image.png';
    ?>
      <tr>
        <td>
          <img src="<?= $variantImg ?>" alt="<?= e($v['variant_name']) ?>" style="width:60px;height:50px;object-fit:cover;border-radius:6px;" onerror="this.src='<?= BASE_URL ?>/assets/img/no-image.png'">
        </td>
        <td>
          <span class="badge badge-secondary" style="font-size:12px;background:#f3e8de;color:var(--terracotta-dark);font-weight:600;">📂 <?= e($v['category_name']) ?></span>
        </td>
        <td>
          <strong><?= e($v['product_name']) ?></strong><br>
          <small class="muted">Kode: <?= e($v['product_code']) ?></small>
        </td>
        <td>
          <span class="badge badge-info" style="font-size:13px;"><?= e($v['variant_name']) ?></span>
        </td>
        <td><small class="muted"><?= date('d M Y H:i', strtotime($v['created_at'])) ?></small></td>
        <td class="actions">
          <a class="btn btn-outline btn-sm" href="<?= BASE_URL ?>/admin/product-variants.php?edit=<?= (int)$v['id'] ?>">Edit</a>
          <a onclick="return confirm('Hapus variasi warna ini?')" class="btn btn-sm" style="background:#dc3545;color:#fff;" href="<?= BASE_URL ?>/admin/product-variants.php?action=delete&id=<?= (int)$v['id'] ?>&product_id=<?= (int)$v['product_id'] ?>&category_id=<?= (int)$filterCategoryId ?>&csrf_token=<?= e(csrf_token()) ?>">Hapus</a>
        </td>
      </tr>
    <?php endforeach; ?>
    <?php if (empty($variants)): ?>
      <tr>
        <td colspan="6" style="text-align:center;padding:36px">
          Belum ada variasi warna produk yang ditemukan. Silakan tambahkan variasi di atas.
        </td>
      </tr>
    <?php endif; ?>
    </tbody>
  </table>
</div>

<script>
function filterFormProducts(resetProduct = false) {
    const catSelect = document.getElementById('form_category_id');
    const prodSelect = document.getElementById('form_product_id');
    if (!catSelect || !prodSelect) return;

    const selectedCat = catSelect.value;
    
    if (resetProduct) {
        prodSelect.value = '';
    }

    let matchCount = 0;

    for (let i = 0; i < prodSelect.options.length; i++) {
        const opt = prodSelect.options[i];
        if (!opt.value) continue;

        const cat = opt.getAttribute('data-category');
        if (!selectedCat || cat === selectedCat) {
            opt.disabled = false;
            opt.hidden = false;
            opt.style.display = '';
            matchCount++;
        } else {
            opt.disabled = true;
            opt.hidden = true;
            opt.style.display = 'none';
        }
    }

    if (!selectedCat) {
        prodSelect.options[0].text = '-- Pilih Kategori Terlebih Dahulu --';
        prodSelect.disabled = true;
    } else {
        prodSelect.options[0].text = matchCount > 0 ? '-- Pilih Produk (' + matchCount + ' Produk) --' : '-- Tidak ada produk di kategori ini --';
        prodSelect.disabled = false;
    }
}

function updateFilterBarProducts() {
    const catSelect = document.getElementById('filter_category_id');
    const prodSelect = document.getElementById('filter_product_id');
    if (!catSelect || !prodSelect) return;

    const selectedCat = catSelect.value;

    for (let i = 0; i < prodSelect.options.length; i++) {
        const opt = prodSelect.options[i];
        if (!opt.value || opt.value === '0') continue;

        const cat = opt.getAttribute('data-category');
        if (selectedCat === '0' || !selectedCat || cat === selectedCat) {
            opt.disabled = false;
            opt.hidden = false;
            opt.style.display = '';
        } else {
            opt.disabled = true;
            opt.hidden = true;
            opt.style.display = 'none';
        }
    }
}

function onFilterCategoryChange() {
    const prodSelect = document.getElementById('filter_product_id');
    if (prodSelect) prodSelect.value = '0';
    document.getElementById('filter_form').submit();
}

document.addEventListener('DOMContentLoaded', function() {
    const catSelect = document.getElementById('form_category_id');
    if (catSelect) {
        catSelect.addEventListener('change', function() {
            filterFormProducts(true);
        });
        filterFormProducts(false);
    }
    
    updateFilterBarProducts();
});
</script>

<?php include '../includes/admin_footer.php'; ?>
