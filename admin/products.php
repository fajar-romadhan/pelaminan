<?php
require_once '../config/database.php';
require_once '../config/helpers.php';
require_admin();



$pageTitle = 'Kelola Produk & Durasi Produksi'; 
$active = 'products';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf_token($_POST['csrf_token'] ?? null);



    $id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT) ?: 0;
    $categoryId = filter_input(INPUT_POST, 'category_id', FILTER_VALIDATE_INT);
    $name = trim($_POST['name'] ?? '');
    $description = trim($_POST['description'] ?? '');
    
    $height = trim($_POST['height'] ?? $_POST['length'] ?? '');
    $width = trim($_POST['width'] ?? '');
    $unit = trim($_POST['unit'] ?? 'm');
    if (!in_array($unit, ['m', 'cm'])) {
        $unit = 'm';
    }

    if ($height !== '' && $width !== '') {
        $heiUnit = (preg_match('/(cm|m)$/i', $height) || !is_numeric($height)) ? '' : ($unit === 'cm' ? ' cm' : 'm');
        $widUnit = (preg_match('/(cm|m)$/i', $width) || !is_numeric($width)) ? '' : ($unit === 'cm' ? ' cm' : 'm');
        $heiStr = $height . $heiUnit;
        $widStr = $width . $widUnit;
        $size = "T: {$heiStr} x L: {$widStr}";
    } elseif ($height !== '') {
        $heiUnit = (preg_match('/(cm|m)$/i', $height) || !is_numeric($height)) ? '' : ($unit === 'cm' ? ' cm' : 'm');
        $size = "T: " . $height . $heiUnit;
    } elseif ($width !== '') {
        $widUnit = (preg_match('/(cm|m)$/i', $width) || !is_numeric($width)) ? '' : ($unit === 'cm' ? ' cm' : 'm');
        $size = "L: " . $width . $widUnit;
    } else {
        $size = '-';
    }

    $price = parse_rupiah_input($_POST['price'] ?? 0);
    $productionDuration = filter_input(INPUT_POST, 'production_duration', FILTER_VALIDATE_INT) ?: 3;
    $status = $_POST['status'] === 'Tidak Aktif' ? 'Tidak Aktif' : 'Aktif';

    if (!$categoryId || empty($name) || $price < 0) {
        set_flash('danger', 'Kategori, nama produk, dan harga tidak valid.');
        redirect(BASE_URL . '/admin/products.php');
    }

    // Determine expected prefix for chosen category
    $stmtCatName = $pdo->prepare('SELECT name FROM categories WHERE id=? LIMIT 1');
    $stmtCatName->execute([$categoryId]);
    $targetCatName = $stmtCatName->fetchColumn() ?: '';
    $expectedPrefix = get_category_prefix($targetCatName);

    $existingImage = null;
    $code = '';
    if ($id > 0) {
        $checkStmt = $pdo->prepare('SELECT code, image_url FROM products WHERE id=? LIMIT 1');
        $checkStmt->execute([$id]);
        $row = $checkStmt->fetch();
        $existingImage = $row['image_url'] ?? null;
        $currentCode = $row['code'] ?? '';

        // If code is empty or prefix does not match category (e.g. Kotak Akas with PLM-011), regenerate with correct prefix
        if (empty($currentCode) || stristr($currentCode, $expectedPrefix . '-') === false) {
            $code = generate_product_code($pdo, $categoryId, $id);
        } else {
            $code = $currentCode;
        }
    } else {
        $code = generate_product_code($pdo, $categoryId, 0);
    }

    // Handle image upload if provided
    $imageUrl = $existingImage;
    if (isset($_FILES['image']) && $_FILES['image']['error'] !== UPLOAD_ERR_NO_FILE) {
        try {
            $imageUrl = upload_image($_FILES['image'], BASE_PATH . '/uploads/products');
        } catch (Exception $e) {
            set_flash('danger', 'Gagal upload gambar: ' . $e->getMessage());
            redirect(BASE_URL . '/admin/products.php' . ($id > 0 ? '?edit=' . $id : ''));
        }
    }

    if ($id > 0) {
        $stmt = $pdo->prepare('UPDATE products SET category_id=?, name=?, code=?, description=?, size=?, price=?, production_duration=?, status=?, image_url=? WHERE id=?');
        $stmt->execute([$categoryId, $name, $code, $description, $size, $price, $productionDuration, $status, $imageUrl, $id]);
        log_activity('Edit Produk', "Memperbarui produk: {$name} ({$code})");
        set_flash('success', 'Produk berhasil diperbarui.');
    } else {
        $stmt = $pdo->prepare('INSERT INTO products(category_id, name, code, description, size, price, production_duration, status, image_url) VALUES(?,?,?,?,?,?,?,?,?)');
        $stmt->execute([$categoryId, $name, $code, $description, $size, $price, $productionDuration, $status, $imageUrl]);
        log_activity('Tambah Produk Baru', "Menambahkan produk baru: {$name} ({$code})");
        set_flash('success', 'Produk berhasil ditambahkan.');
    }

    redirect(BASE_URL . '/admin/products.php');
}

if (isset($_GET['action']) && $_GET['action'] === 'delete') {
    verify_csrf_token($_GET['csrf_token'] ?? null);



    $deleteId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
    if ($deleteId) {
        try {
            $stmt = $pdo->prepare('DELETE FROM products WHERE id=?');
            $stmt->execute([$deleteId]);
            log_activity('Hapus Produk', "Menghapus produk ID #{$deleteId}");
            set_flash('success', 'Produk berhasil dihapus.');
        } catch (PDOException $e) {
            set_flash('danger', 'Gagal menghapus produk karena sedang digunakan pada data pesanan.');
        }
    }
    redirect(BASE_URL . '/admin/products.php');
}

$editHeight = '';
$editWidth = '';
$editUnit = 'm';
$edit = null;

if (isset($_GET['edit'])) {
    $editId = filter_input(INPUT_GET, 'edit', FILTER_VALIDATE_INT);
    if ($editId) {
        $stmt = $pdo->prepare('SELECT * FROM products WHERE id=? LIMIT 1');
        $stmt->execute([$editId]);
        $edit = $stmt->fetch();
        if ($edit && !empty($edit['size']) && $edit['size'] !== '-') {
            if (stripos($edit['size'], 'cm') !== false) {
                $editUnit = 'cm';
            }
            if (preg_match('/[TP]:\s*([\d.,]+)\s*(?:m|cm)?\s*x\s*L:\s*([\d.,]+)\s*(?:m|cm)?/i', $edit['size'], $m)) {
                $editHeight = $m[1];
                $editWidth = $m[2];
            } elseif (preg_match('/([\d.,]+)\s*(?:m|cm)?\s*x\s*([\d.,]+)\s*(?:m|cm)?/i', $edit['size'], $m)) {
                $editHeight = $m[1];
                $editWidth = $m[2];
            } else {
                $editHeight = $edit['size'];
            }
        }
    }
}

$categories = $pdo->query('SELECT * FROM categories ORDER BY name')->fetchAll();
$products = $pdo->query('SELECT p.*, c.name AS category_name FROM products p JOIN categories c ON c.id=p.category_id ORDER BY p.id DESC')->fetchAll();

include '../includes/admin_header.php';
?>
<div class="card" style="margin-bottom:20px">
  <h3 style="color:var(--terracotta-dark)"><?= $edit ? 'Edit Produk & Durasi Produksi' : 'Tambah Produk Baru' ?></h3>
  <form method="post" enctype="multipart/form-data">
    <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
    <input type="hidden" name="id" value="<?= e($edit['id'] ?? 0) ?>">
    
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px;">
      <div class="form-group">
        <label>Kategori</label>
        <select class="select" name="category_id" required>
          <option value="">-- Pilih Kategori --</option>
          <?php foreach($categories as $c): ?>
            <option value="<?= (int)$c['id'] ?>" <?= ($edit['category_id'] ?? '') == $c['id'] ? 'selected' : '' ?>><?= e($c['name']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="form-group">
        <label>Nama Produk</label>
        <input class="input" name="name" value="<?= e($edit['name'] ?? '') ?>" required placeholder="Contoh: Pelaminan Royal Gold">
      </div>
    </div>

    <div class="form-group">
      <label>Deskripsi Produk</label>
      <textarea class="textarea" name="description" required placeholder="Deskripsi lengkap produk..."><?= e($edit['description'] ?? '') ?></textarea>
    </div>

    <div style="display:grid;grid-template-columns:repeat(auto-fit, minmax(130px, 1fr));gap:12px;">
      <div class="form-group">
        <label>Tinggi</label>
        <input class="input" type="text" name="height" value="<?= e($editHeight) ?>" placeholder="Misal: 90 atau 3">
      </div>
      <div class="form-group">
        <label>Lebar</label>
        <input class="input" type="text" name="width" value="<?= e($editWidth) ?>" placeholder="Misal: 50 atau 5">
      </div>
      <div class="form-group">
        <label>Satuan</label>
        <select class="select" name="unit">
          <option value="cm" <?= $editUnit === 'cm' ? 'selected' : '' ?>>Centimeter (cm)</option>
          <option value="m" <?= $editUnit === 'm' ? 'selected' : '' ?>>Meter (m)</option>
        </select>
      </div>
      <div class="form-group">
        <label>Harga (Rp)</label>
        <input class="input price-input" type="text" name="price" value="<?= isset($edit['price']) ? e(number_format((float)$edit['price'], 0, ',', '.')) : '' ?>" required placeholder="Contoh: 1.000.000">
      </div>
      <div class="form-group">
        <label>Durasi (Hari)</label>
        <input class="input" type="number" name="production_duration" value="<?= e($edit['production_duration'] ?? 3) ?>" required min="1" placeholder="Contoh: 5">
      </div>
      <div class="form-group">
        <label>Status</label>
        <select class="select" name="status">
          <option value="Aktif" <?= ($edit['status'] ?? '')==='Aktif'?'selected':'' ?>>Aktif</option>
          <option value="Tidak Aktif" <?= ($edit['status'] ?? '')==='Tidak Aktif'?'selected':'' ?>>Tidak Aktif</option>
        </select>
      </div>
    </div>

    <div class="form-group">
      <label>Foto Produk (JPG/PNG/WEBP, Max 2MB)</label>
      <input class="input" type="file" name="image" accept="image/jpeg,image/png,image/webp">
      <?php if (!empty($edit['image_url'])): ?>
        <small class="muted">Foto saat ini: <?= e($edit['image_url']) ?></small>
      <?php endif; ?>
    </div>

    <button class="btn btn-primary" type="submit">Simpan Produk</button>
    <?php if (!empty($edit)): ?>
      <a class="btn btn-outline" href="<?= BASE_URL ?>/admin/products.php">Batal Edit</a>
    <?php endif; ?>
  </form>
</div>

<div class="table-wrap">
  <table class="table">
    <thead>
      <tr>
        <th>Foto</th>
        <th>Kode</th>
        <th>Nama Produk</th>
        <th>Kategori</th>
        <th>Harga</th>
        <th>Durasi Produksi</th>
        <th>Status</th>
        <th>Aksi</th>
      </tr>
    </thead>
    <tbody>
    <?php foreach($products as $p): ?>
      <tr>
        <td>
          <?php if (!empty($p['image_url'])): ?>
            <img src="<?= BASE_URL ?>/uploads/products/<?= e($p['image_url']) ?>" alt="<?= e($p['name']) ?>" style="width:45px;height:45px;object-fit:cover;border-radius:6px;">
          <?php else: ?>
            <span class="badge badge-muted">No Image</span>
          <?php endif; ?>
        </td>
        <td><strong><?= e($p['code']) ?></strong></td>
        <td><?= e($p['name']) ?></td>
        <td><?= e($p['category_name']) ?></td>
        <td><?= rupiah($p['price']) ?></td>
        <td><strong><?= (int)($p['production_duration'] ?: 3) ?> Hari</strong></td>
        <td><span class="badge <?= $p['status']==='Aktif'?'badge-success':'badge-muted' ?>"><?= e($p['status']) ?></span></td>
        <td class="actions">
          <a class="btn btn-outline btn-sm" href="<?= BASE_URL ?>/admin/products.php?edit=<?= (int)$p['id'] ?>">Edit</a>
          <a class="btn btn-outline btn-sm" style="color:var(--terracotta-dark);border-color:var(--terracotta-dark);" href="<?= BASE_URL ?>/admin/product-variants.php?product_id=<?= (int)$p['id'] ?>">🎨 Variasi Warna</a>
          <a class="btn btn-sm" style="background:#dc3545;color:#fff;" onclick="return confirm('Hapus produk ini?')" href="<?= BASE_URL ?>/admin/products.php?action=delete&id=<?= (int)$p['id'] ?>&csrf_token=<?= e(csrf_token()) ?>">Hapus</a>
        </td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
</div>
<?php include '../includes/admin_footer.php'; ?>
