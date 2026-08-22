<?php
require_once 'config/database.php';
require_once 'config/helpers.php';
require_customer();

$userId = current_user()['id'];

// Mark notifications as read
if (isset($_GET['action']) && $_GET['action'] === 'read_all') {
    $stmt = $pdo->prepare("UPDATE notifications SET is_read = 1 WHERE user_id = ?");
    $stmt->execute([$userId]);
    set_flash('success', 'Semua notifikasi telah ditandai dibaca.');
    redirect(BASE_URL . '/notifications.php');
}

$stmt = $pdo->prepare("
    SELECT n.*, o.order_code 
    FROM notifications n
    LEFT JOIN orders o ON o.id = n.order_id
    WHERE n.user_id = ?
    ORDER BY n.id DESC
");
$stmt->execute([$userId]);
$notifications = $stmt->fetchAll();

$pageTitle = 'Notifikasi Saya';
$active = 'notifications';
include 'includes/header.php';
?>
<div class="page-head"><div class="container"><h1>Notifikasi Saya</h1><p>Riwayat pemberitahuan status pesanan dan antrean Anda</p></div></div>
<main class="container" style="padding-top:30px;max-width:800px">
  <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:18px;">
    <h3>Daftar Pemberitahuan</h3>
    <?php if(!empty($notifications)): ?>
      <a class="btn btn-outline btn-sm" href="<?= BASE_URL ?>/notifications.php?action=read_all">Tandai Sudah Dibaca</a>
    <?php endif; ?>
  </div>

  <div class="notifications-list">
    <?php foreach($notifications as $n): ?>
      <div class="card" style="margin-bottom:12px;<?= $n['is_read'] ? 'opacity:0.8;' : 'border-left:4px solid var(--terracotta-dark);background:#fffdf9;' ?>">
        <div style="display:flex;justify-content:space-between;align-items:flex-start;">
          <strong>🔔 <?= e($n['title']) ?></strong>
          <small class="muted"><?= date('d M Y H:i', strtotime($n['created_at'])) ?></small>
        </div>
        <p style="margin:8px 0;white-space:pre-line"><?= e($n['message']) ?></p>
        <?php if(!empty($n['order_id'])): ?>
          <div>
            <a class="btn btn-primary btn-sm" href="<?= BASE_URL ?>/tracking.php?order_id=<?= (int)$n['order_id'] ?>">Lihat Tracking Pesanan <?= e($n['order_code']) ?> →</a>
          </div>
        <?php endif; ?>
      </div>
    <?php endforeach; ?>
    <?php if(empty($notifications)): ?>
      <div class="card" style="text-align:center;padding:40px">
        Belum ada notifikasi.
      </div>
    <?php endif; ?>
  </div>
</main>
<?php include 'includes/footer.php'; ?>
