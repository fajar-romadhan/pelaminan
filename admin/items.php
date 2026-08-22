<?php
require_once '../config/database.php';
require_once '../config/helpers.php';
require_admin();

set_flash('info', 'Semua item Pot Bunga & Kotak Akas sekarang dikelola secara terpusat pada menu Kelola Produk.');
redirect(BASE_URL . '/admin/products.php');



$pageTitle = 'Item Tambahan'; 
$active = 'items';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf_token($_POST['csrf_token'] ?? null);



    $id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT) ?: 0;
    $name = trim($_POST['name'] ?? '');
    $category = trim($_POST['category'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $price = parse_rupiah_input($_POST['price'] ?? 0);
    $status = ($_POST['status'] ?? '') === 'Tidak Aktif' ? 'Tidak Aktif' : 'Aktif';

    $allowedCategories = ['Pot Bunga', 'Kotak Akas', 'Kotak Akad'];

    if (empty($name) || !in_array($category, $allowedCategories, true) || $price < 0) {
        set_flash('danger', 'Nama item, kategori, dan harga harus diisi dengan benar.');
        redirect(BASE_URL . '/admin/items.php' . ($id > 0 ? '?edit=' . $id : ''));
    }

    // Existing image check if editing
    $existingImage = null;
    if ($id > 0) {
        $checkStmt = $pdo->prepare('SELECT image_url FROM extra_items WHERE id=? LIMIT 1');
        $checkStmt->execute([$id]);
        $existingImage = $checkStmt->fetchColumn() ?: null;
    }

    $imageUrl = $existingImage;
    if (isset($_FILES['image']) && $_FILES['image']['error'] !== UPLOAD_ERR_NO_FILE) {
        try {
            $imageUrl = upload_image($_FILES['image'], BASE_PATH . '/uploads/items');
        } catch (Exception $e) {
            set_flash('danger', 'Gagal upload foto item: ' . $e->getMessage());
            redirect(BASE_URL . '/admin/items.php' . ($id > 0 ? '?edit=' . $id : ''));
        }
    }

    if ($id > 0) {
        $stmt = $pdo->prepare('UPDATE extra_items SET name=?, category=?, description=?, price=?, image_url=?, status=? WHERE id=?');
        $stmt->execute([$name, $category, $description, $price, $imageUrl, $status, $id]);
        set_flash('success', 'Item tambahan berhasil diperbarui.');
    } else {
        $stmt = $pdo->prepare('INSERT INTO extra_items(name, category, description, price, image_url, status) VALUES(?,?,?,?,?,?)');
        $stmt->execute([$name, $category, $description, $price, $imageUrl, $status]);
        set_flash('success', 'Item tambahan berhasil ditambahkan.');
    }

    redirect(BASE_URL . '/admin/items.php');
}

if (isset($_GET['action']) && $_GET['action'] === 'delete') {
    verify_csrf_token($_GET['csrf_token'] ?? null);
    $deleteId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
    if ($deleteId) {
        $stmt = $pdo->prepare('DELETE FROM extra_items WHERE id=?');
        $stmt->execute([$deleteId]);
        set_flash('success', 'Item tambahan berhasil dihapus.');
    }
    redirect(BASE_URL . '/admin/items.php');
}

$edit = null;
if (isset($_GET['edit'])) {
    $editId = filter_input(INPUT_GET, 'edit', FILTER_VALIDATE_INT);
    if ($editId) {
        $stmt = $pdo->prepare('SELECT * FROM extra_items WHERE id=? LIMIT 1');
        $stmt->execute([$editId]);
        $edit = $stmt->fetch();
    }
}

$items = $pdo->query("SELECT * FROM extra_items WHERE category IN ('Pot Bunga', 'Kotak Akas', 'Kotak Akad') ORDER BY category, name")->fetchAll();

include '../includes/admin_header.php';
?>
<div class="card" style="margin-bottom:20px">
  <h3 style="color:var(--terracotta-dark)"><?= $edit ? 'Edit Item Tambahan' : 'Tambah Item Tambahan Baru' ?></h3>
  <form method="post" enctype="multipart/form-data">
    <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
    <input type="hidden" name="id" value="<?= e($edit['id'] ?? 0) ?>">
    
    <div class="form-row-3">
      <div class="form-group">
        <label>Nama Item</label>
        <input class="input" name="name" value="<?= e($edit['name'] ?? '') ?>" required placeholder="Contoh: Pot Bunga Rose Premium">
      </div>
      
      <div class="form-group">
        <label>Kategori</label>
        <select class="select" name="category" required>
          <option value="Pot Bunga" <?= ($edit['category'] ?? '')==='Pot Bunga'?'selected':'' ?>>Pot Bunga</option>
          <option value="Kotak Akas" <?= in_array(($edit['category'] ?? ''), ['Kotak Akas', 'Kotak Akad'], true)?'selected':'' ?>>Kotak Akas</option>
        </select>
      </div>
      
      <div class="form-group">
        <label>Harga Tambahan (Rp)</label>
        <input class="input price-input" type="text" name="price" value="<?= isset($edit['price']) ? e(number_format((float)$edit['price'], 0, ',', '.')) : '' ?>" required placeholder="Contoh: 300.000">
      </div>
    </div>

    <div class="form-group">
      <label>Deskripsi Singkat</label>
      <textarea class="textarea" name="description" placeholder="Deskripsi singkat item tambahan..."><?= e($edit['description'] ?? '') ?></textarea>
    </div>

    <div class="form-row" style="align-items:center;">
      <div class="form-group" style="flex:2;">
        <label>Foto Item (JPG/PNG/WEBP, Max 2MB)</label>
        <input class="input" type="file" name="image" accept="image/jpeg,image/png,image/webp">
        <?php if (!empty($edit['image_url'])): ?>
          <small class="muted">Foto saat ini: <?= e($edit['image_url']) ?></small>
        <?php endif; ?>
      </div>

      <div class="form-group" style="flex:1;">
        <label>Status</label>
        <select class="select" name="status">
          <option value="Aktif" <?= ($edit['status'] ?? '')==='Aktif'?'selected':'' ?>>Aktif</option>
          <option value="Tidak Aktif" <?= ($edit['status'] ?? '')==='Tidak Aktif'?'selected':'' ?>>Tidak Aktif</option>
        </select>
      </div>
    </div>
    
    <div class="form-group" style="margin-top:10px;">
      <button class="btn btn-primary" type="submit">Simpan Item</button> 
      <?php if($edit): ?>
        <a class="btn btn-outline" href="<?= BASE_URL ?>/admin/items.php">Batal Edit</a>
      <?php endif; ?>
    </div>
  </form>
</div>

<div class="table-wrap">
  <table class="table">
    <thead>
      <tr>
        <th>Foto</th>
        <th>Nama Item</th>
        <th>Kategori</th>
        <th>Deskripsi</th>
        <th>Harga</th>
        <th>Status</th>
        <th>Aksi</th>
      </tr>
    </thead>
    <tbody>
    <?php foreach($items as $i): ?>
      <tr>
        <td>
          <?php if (!empty($i['image_url'])): ?>
            <img src="<?= BASE_URL ?>/uploads/items/<?= e($i['image_url']) ?>" alt="<?= e($i['name']) ?>" style="width:50px;height:50px;object-fit:cover;border-radius:6px;" onerror="this.src='<?= BASE_URL ?>/assets/img/no-image.png'">
          <?php else: ?>
            <span class="badge badge-muted">No Image</span>
          <?php endif; ?>
        </td>
        <td><strong><?= e($i['name']) ?></strong></td>
        <td><span class="badge badge-info"><?= e($i['category']) ?></span></td>
        <td><small class="muted"><?= e($i['description'] ?: '-') ?></small></td>
        <td><strong><?= rupiah($i['price']) ?></strong></td>
        <td><span class="badge <?= $i['status']==='Aktif'?'badge-success':'badge-muted' ?>"><?= e($i['status']) ?></span></td>
        <td class="actions">
          <a class="btn btn-outline btn-sm" href="<?= BASE_URL ?>/admin/items.php?edit=<?= (int)$i['id'] ?>">Edit</a>
          <a onclick="return confirm('Hapus item ini?')" class="btn btn-sm" style="background:#dc3545;color:#fff;" href="<?= BASE_URL ?>/admin/items.php?action=delete&id=<?= (int)$i['id'] ?>&csrf_token=<?= e(csrf_token()) ?>">Hapus</a>
        </td>
      </tr>
    <?php endforeach; ?>
    <?php if (empty($items)): ?>
      <tr><td colspan="7" style="text-align:center;padding:30px;">Belum ada item tambahan.</td></tr>
    <?php endif; ?>
    </tbody>
  </table>
</div>
<?php include '../includes/admin_footer.php'; ?>
