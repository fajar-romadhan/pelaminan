<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/helpers.php';
require_admin();

$pageTitle = $pageTitle ?? 'Admin Panel';
$active = $active ?? '';
$adminUser = current_user();

$unreadAdminCount = 0;
if ($adminUser) {
    $cnStmt = $pdo->prepare("SELECT COUNT(*) FROM notifications WHERE user_id = ? AND is_read = 0");
    $cnStmt->execute([$adminUser['id']]);
    $unreadAdminCount = (int)$cnStmt->fetchColumn();
}
?>
<!doctype html>
<html lang="id">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= e($pageTitle) ?></title>
  <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css">
  <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin="" />
  <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>
  <style>
    .admin-toast-container {
      position: fixed;
      top: 20px;
      right: 20px;
      z-index: 99999;
      display: flex;
      flex-direction: column;
      gap: 10px;
      pointer-events: none;
    }
    .admin-toast {
      pointer-events: auto;
      min-width: 300px;
      max-width: 380px;
      background: #ffffff;
      border-left: 4px solid var(--terracotta-dark);
      border-radius: 12px;
      padding: 14px 16px;
      box-shadow: 0 10px 30px rgba(0,0,0,0.18);
      display: flex;
      align-items: flex-start;
      gap: 12px;
      transform: translateX(120%);
      transition: transform 0.35s cubic-bezier(0.16, 1, 0.3, 1);
    }
    .admin-toast.show {
      transform: translateX(0);
    }

    /* Modern Bell Button & Badge Styling */
    .admin-bell-button {
      background: #ffffff;
      border: 1.5px solid rgba(216, 133, 78, 0.28);
      color: var(--terracotta-dark);
      width: 42px;
      height: 42px;
      border-radius: 12px;
      display: flex;
      align-items: center;
      justify-content: center;
      cursor: pointer;
      position: relative;
      transition: all 0.25s cubic-bezier(0.16, 1, 0.3, 1);
      box-shadow: 0 4px 12px rgba(54, 34, 23, 0.05);
    }
    .admin-bell-button:hover {
      background: #fdfaf6;
      border-color: var(--terracotta-dark);
      transform: translateY(-2px);
      box-shadow: 0 6px 16px rgba(216, 133, 78, 0.22);
    }
    .admin-bell-button:hover svg {
      animation: ringBell 0.6s ease-in-out;
    }
    .admin-bell-badge {
      position: absolute;
      top: -5px;
      right: -5px;
      background: linear-gradient(135deg, #e63946 0%, #d62828 100%);
      color: #ffffff;
      font-size: 11px;
      font-weight: 800;
      padding: 2px 6.5px;
      border-radius: 99px;
      border: 2px solid #ffffff;
      box-shadow: 0 3px 8px rgba(214, 40, 40, 0.4);
      line-height: 1;
    }
    .admin-bell-badge.pulse {
      animation: badgePulse 2s infinite;
    }
    @keyframes ringBell {
      0% { transform: rotate(0); }
      20% { transform: rotate(18deg); }
      40% { transform: rotate(-18deg); }
      60% { transform: rotate(12deg); }
      80% { transform: rotate(-12deg); }
      100% { transform: rotate(0); }
    }
    @keyframes badgePulse {
      0% { box-shadow: 0 0 0 0 rgba(230, 57, 70, 0.6); }
      70% { box-shadow: 0 0 0 8px rgba(230, 57, 70, 0); }
      100% { box-shadow: 0 0 0 0 rgba(230, 57, 70, 0); }
    }
    .admin-notif-dropdown {
      display: none;
      position: absolute;
      right: 0;
      top: 50px;
      width: 360px;
      background: #ffffff;
      border-radius: 16px;
      box-shadow: 0 12px 35px rgba(0, 0, 0, 0.18), 0 4px 12px rgba(54, 34, 23, 0.08);
      border: 1px solid rgba(216, 133, 78, 0.22);
      z-index: 9999;
      overflow: hidden;
      animation: dropdownFade 0.25s cubic-bezier(0.16, 1, 0.3, 1);
    }
    @keyframes dropdownFade {
      from { opacity: 0; transform: translateY(-8px); }
      to { opacity: 1; transform: translateY(0); }
    }
    .admin-notif-header {
      padding: 14px 18px;
      background: var(--espresso-gradient);
      color: #ffffff;
      display: flex;
      justify-content: space-between;
      align-items: center;
      border-bottom: 1px solid rgba(255, 255, 255, 0.1);
    }
    .admin-notif-header strong {
      font-size: 13.5px;
      font-family: var(--font-heading);
      color: var(--terracotta-light);
    }
    .admin-notif-header button {
      background: none;
      border: none;
      color: rgba(255, 255, 255, 0.85);
      font-size: 11.5px;
      cursor: pointer;
      text-decoration: underline;
      transition: color 0.2s;
    }
    .admin-notif-header button:hover {
      color: var(--terracotta-light);
    }
    .admin-notif-footer {
      padding: 12px;
      text-align: center;
      background: #faf7f2;
      border-top: 1px solid rgba(216, 133, 78, 0.12);
    }
    .admin-notif-footer a {
      font-size: 12.5px;
      font-weight: 700;
      color: var(--terracotta-dark);
      text-decoration: none;
    }
    .admin-notif-footer a:hover {
      text-decoration: underline;
    }
  </style>
</head>
<body>
<div id="adminToastContainer" class="admin-toast-container"></div>
<div class="sidebar-overlay" id="sidebar-overlay"></div>
<div class="admin-layout">
  <aside class="sidebar">
    <div class="sidebar-brand">
      <img src="<?= BASE_URL ?>/assets/img/logo.png" alt="Logo Distributor Pelaminan Family" class="logo">
      <div class="sidebar-brand-text">
        <span class="sidebar-brand-title">Pelaminan Family</span>
        <span class="sidebar-brand-sub">
          <span class="online-dot"></span> Admin Panel
        </span>
      </div>
    </div>
    <nav class="side-nav">
      <a class="<?= $active === 'dashboard' ? 'active' : '' ?>" href="<?= BASE_URL ?>/admin/index.php">🏠 Dashboard</a>
      <a class="<?= $active === 'orders' ? 'active' : '' ?>" href="<?= BASE_URL ?>/admin/orders.php">🛒 Pesanan Masuk</a>
      <a class="<?= $active === 'calendar' ? 'active' : '' ?>" href="<?= BASE_URL ?>/admin/production-calendar.php">📅 Kalender Produksi</a>
      <a class="<?= $active === 'reports' ? 'active' : '' ?>" href="<?= BASE_URL ?>/admin/operational-report.php">📊 Laporan Operasional</a>
      <a class="<?= $active === 'notifications' ? 'active' : '' ?>" href="<?= BASE_URL ?>/admin/notifications.php">
        🔔 Notifikasi <?= $unreadAdminCount > 0 ? "<span class='badge badge-danger' style='margin-left:auto;font-size:10px;'>$unreadAdminCount</span>" : '' ?>
      </a>

      <a class="<?= $active === 'products' ? 'active' : '' ?>" href="<?= BASE_URL ?>/admin/products.php">📦 Kelola Produk</a>
      <a class="<?= $active === 'variants' ? 'active' : '' ?>" href="<?= BASE_URL ?>/admin/product-variants.php">🎨 Variasi Warna</a>
    </nav>
    <div style="padding:12px;border-top:1px solid rgba(255,255,255,.1)">
      <a class="btn btn-outline-light btn-block btn-sm" href="<?= BASE_URL ?>/logout.php">Logout</a>
    </div>
  </aside>
  <main class="admin-main">
    <header class="admin-top">
      <div style="display:flex;align-items:center;gap:12px">
        <button type="button" class="admin-menu-toggle" id="admin-menu-toggle" aria-label="Buka menu admin" aria-expanded="false">☰</button>
        <strong style="color:var(--espresso);font-size:18px"><?= e($pageTitle) ?></strong>
      </div>
      
      <div style="display:flex;align-items:center;gap:14px;position:relative;">
        <!-- Notification Bell Container -->
        <div style="position:relative;">
          <button type="button" id="adminNotifBellBtn" class="admin-bell-button" aria-label="Notifikasi Admin">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
              <path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"></path>
              <path d="M13.73 21a2 2 0 0 1-3.46 0"></path>
            </svg>
            <span id="adminNotifBadge" class="admin-bell-badge <?= $unreadAdminCount > 0 ? 'pulse' : '' ?>" style="<?= $unreadAdminCount > 0 ? '' : 'display:none;' ?>">
              <?= $unreadAdminCount ?>
            </span>
          </button>

          <!-- Dropdown Menu -->
          <div id="adminNotifDropdown" class="admin-notif-dropdown">
            <div class="admin-notif-header">
              <strong>🔔 Notifikasi Admin</strong>
              <button type="button" id="adminMarkAllReadBtn">Tandai semua dibaca</button>
            </div>
            <div id="adminNotifList" style="max-height:320px;overflow-y:auto;padding:6px 0;">
              <div style="text-align:center;padding:24px;color:#888;font-size:12.5px;">Memuat notifikasi...</div>
            </div>
            <div class="admin-notif-footer">
              <a href="<?= BASE_URL ?>/admin/notifications.php">Lihat Semua Notifikasi →</a>
            </div>
          </div>
        </div>

        <span class="badge badge-primary"><?= e($adminUser['name'] ?? 'Admin Panel') ?></span>
      </div>
    </header>
    <section class="admin-content">
      <?= flash() ?>
