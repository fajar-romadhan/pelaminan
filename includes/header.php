<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/helpers.php';

$pageTitle = $pageTitle ?? 'Distributor Pelaminan Family';
$active = $active ?? '';
$user = current_user();
$cartCount = 0;

$unreadNotifCount = 0;

if ($user && ($user['role'] ?? '') === 'customer') {
    $stmt = $pdo->prepare("
        SELECT COUNT(*) 
        FROM carts
        WHERE user_id = ?
    ");
    $stmt->execute([$user['id']]);
    $cartCount = (int)$stmt->fetchColumn();

    $nStmt = $pdo->prepare("
        SELECT COUNT(*) 
        FROM notifications
        WHERE user_id = ? AND is_read = 0
    ");
    $nStmt->execute([$user['id']]);
    $unreadNotifCount = (int)$nStmt->fetchColumn();
}
?>
<!doctype html>
<html lang="id">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= e($pageTitle) ?></title>
  <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css?v=<?= time() ?>">
  <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/leaflet.css">
  <script src="<?= BASE_URL ?>/assets/js/leaflet.js"></script>

</head>
<body>
<nav class="navbar">
  <div class="container navbar-inner">
    <a class="brand" href="<?= BASE_URL ?>/index.php" style="white-space:nowrap;flex-shrink:0;">
      <img src="<?= BASE_URL ?>/assets/img/logo.png" alt="Logo Distributor Pelaminan Family" class="logo">
      <span style="white-space:nowrap;">Distributor Pelaminan<br><small style="white-space:nowrap;">Family · Palembang</small></span>
    </a>

    <button
      type="button"
      class="mobile-menu-toggle"
      id="mobile-menu-toggle"
      aria-label="Buka menu"
      aria-expanded="false"
      aria-controls="main-navigation"
    >
      ☰
    </button>

    <div class="nav-drawer" id="main-navigation">
      <div class="nav-links">
        <a class="<?= $active === 'home' ? 'active' : '' ?>" href="<?= BASE_URL ?>/index.php" style="white-space:nowrap;">Beranda</a>
        <a class="<?= $active === 'gallery' ? 'active' : '' ?>" href="<?= BASE_URL ?>/gallery.php" style="white-space:nowrap;">Galeri</a>

        <?php if ($user && ($user['role'] ?? '') === 'customer'): ?>
          <a class="<?= $active === 'cart' ? 'active' : '' ?>" href="<?= BASE_URL ?>/customers/cart.php" style="white-space:nowrap;">🛒 Keranjang (<?= $cartCount ?>)</a>
          <a class="<?= $active === 'orders' ? 'active' : '' ?>" href="<?= BASE_URL ?>/my-orders.php" style="white-space:nowrap;">Pesanan Saya</a>
        <?php endif; ?>

        <?php if ($user && ($user['role'] ?? '') === 'admin'): ?>
          <a href="<?= BASE_URL ?>/admin/index.php" style="white-space:nowrap;">Admin Panel</a>
        <?php endif; ?>
      </div>
      <div class="actions" style="display:flex;align-items:center;gap:12px;white-space:nowrap;">
        <?php if ($user && ($user['role'] ?? '') === 'customer'): ?>
          <a class="nav-bell-link <?= $active === 'notifications' ? 'active' : '' ?>" href="<?= BASE_URL ?>/notifications.php" title="Notifikasi" style="position:relative;display:inline-flex;align-items:center;justify-content:center;width:40px;height:40px;border-radius:50%;background:rgba(216,133,78,0.12);color:var(--terracotta-dark);transition:all 0.2s ease;flex-shrink:0;">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
              <path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"></path>
              <path d="M13.73 21a2 2 0 0 1-3.46 0"></path>
            </svg>
            <?php if ($unreadNotifCount > 0): ?>
              <span style="position:absolute;top:-2px;right:-2px;background:#e74c3c;color:#fff;font-size:10px;font-weight:800;padding:2px 6px;border-radius:99px;border:2px solid #faf7f2;min-width:18px;height:18px;display:inline-flex;align-items:center;justify-content:center;line-height:1;">
                <?= $unreadNotifCount ?>
              </span>
            <?php endif; ?>
          </a>
        <?php endif; ?>

        <?php if ($user): ?>
          <span class="badge badge-primary" style="white-space:nowrap;"><?= e($user['name']) ?> (<?= e(ucfirst($user['role'])) ?>)</span>
          <a class="btn btn-primary btn-sm" href="<?= BASE_URL ?>/logout.php" style="white-space:nowrap;">Logout</a>
        <?php else: ?>
          <a class="btn btn-primary btn-sm" href="<?= BASE_URL ?>/login.php" style="white-space:nowrap;">Masuk</a>
          <a class="btn btn-outline btn-sm" href="<?= BASE_URL ?>/register.php" style="white-space:nowrap;">Daftar</a>
        <?php endif; ?>
      </div>
    </div>
  </div>
</nav>
<?= flash() ?>
