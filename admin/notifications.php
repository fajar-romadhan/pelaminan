<?php
require_once '../config/database.php';
require_once '../config/helpers.php';
require_admin();



$pageTitle = 'Pusat Notifikasi Admin';
$active = 'notifications';

$adminUser = current_user();

// Handle Actions
if (isset($_GET['action'])) {
    $action = $_GET['action'];
    if ($action === 'mark_all_read') {
        $stmt = $pdo->prepare("UPDATE notifications SET is_read = 1 WHERE user_id = ?");
        $stmt->execute([$adminUser['id']]);
        set_flash('success', 'Semua notifikasi admin telah ditandai dibaca.');
        redirect(BASE_URL . '/admin/notifications.php');
    } elseif ($action === 'mark_read' && isset($_GET['id'])) {
        $notifId = (int)$_GET['id'];
        $stmt = $pdo->prepare("UPDATE notifications SET is_read = 1 WHERE id = ? AND user_id = ?");
        $stmt->execute([$notifId, $adminUser['id']]);
        set_flash('success', 'Notifikasi telah ditandai dibaca.');
        redirect(BASE_URL . '/admin/notifications.php');
    } elseif ($action === 'view_order' && isset($_GET['id']) && isset($_GET['order_id'])) {
        $notifId = (int)$_GET['id'];
        $orderId = (int)$_GET['order_id'];
        $stmt = $pdo->prepare("UPDATE notifications SET is_read = 1 WHERE id = ? AND user_id = ?");
        $stmt->execute([$notifId, $adminUser['id']]);
        redirect(BASE_URL . '/admin/orders.php?detail=' . $orderId);
    }
}

// Fetch all notifications for admin
$stmt = $pdo->prepare("
    SELECT n.*, o.order_code, o.customer_name 
    FROM notifications n
    LEFT JOIN orders o ON o.id = n.order_id
    WHERE n.user_id = ?
    ORDER BY n.id DESC
");
$stmt->execute([$adminUser['id']]);
$allNotifications = $stmt->fetchAll();

// Counts
$totalAll = count($allNotifications);
$totalUnread = 0;
$totalRead = 0;

foreach ($allNotifications as $n) {
    if (!$n['is_read']) {
        $totalUnread++;
    } else {
        $totalRead++;
    }
}

// Filter mode
$filter = $_GET['filter'] ?? 'all';
$notifications = array_filter($allNotifications, function($n) use ($filter) {
    if ($filter === 'unread') return !$n['is_read'];
    if ($filter === 'read') return (bool)$n['is_read'];
    return true;
});

include '../includes/admin_header.php';
?>

<div class="card" style="margin-bottom:20px;display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:14px;padding:22px;">
  <div>
    <div style="display:flex;align-items:center;gap:10px;margin-bottom:4px;flex-wrap:wrap;">
      <h2 style="color:var(--terracotta-dark);margin:0;">🔔 Pusat Notifikasi Admin</h2>
      <?php if ($totalUnread > 0): ?>
        <span style="background:#dc3545;color:#fff;font-weight:700;font-size:12px;padding:3px 10px;border-radius:12px;">
          <?= $totalUnread ?> Belum Dibaca
        </span>
      <?php endif; ?>
    </div>
    <p class="muted" style="margin:0;font-size:13.5px;">Riwayat seluruh pesan pemberitahuan pesanan masuk, pembayaran, dan pengingat produksi.</p>
  </div>
  <div style="display:flex;gap:10px;align-items:center;flex-wrap:wrap;">
    <?php if ($totalUnread > 0): ?>
      <a href="<?= BASE_URL ?>/admin/notifications.php?action=mark_all_read" class="btn btn-outline btn-sm" style="font-weight:700;">
        ✓ Tandai Semua Dibaca
      </a>
    <?php endif; ?>
  </div>
</div>

<!-- Filter Tabs -->
<div style="display:flex;gap:8px;margin-bottom:18px;flex-wrap:wrap;">
  <a href="<?= BASE_URL ?>/admin/notifications.php?filter=all" class="btn btn-sm <?= $filter === 'all' ? 'btn-primary' : 'btn-outline' ?>" style="border-radius:20px;padding:6px 16px;font-weight:600;">
    Semua Notifikasi (<?= $totalAll ?>)
  </a>
  <a href="<?= BASE_URL ?>/admin/notifications.php?filter=unread" class="btn btn-sm <?= $filter === 'unread' ? 'btn-primary' : 'btn-outline' ?>" style="border-radius:20px;padding:6px 16px;font-weight:600;<?= $filter !== 'unread' && $totalUnread > 0 ? 'color:#dc3545;border-color:#dc3545;' : '' ?>">
    🔴 Belum Dibaca (<?= $totalUnread ?>)
  </a>
  <a href="<?= BASE_URL ?>/admin/notifications.php?filter=read" class="btn btn-sm <?= $filter === 'read' ? 'btn-primary' : 'btn-outline' ?>" style="border-radius:20px;padding:6px 16px;font-weight:600;">
    ✓ Sudah Dibaca (<?= $totalRead ?>)
  </a>
</div>

<div class="card">
  <?php if (empty($notifications)): ?>
    <div style="text-align:center;padding:50px 20px;color:#777;">
      <div style="font-size:48px;margin-bottom:12px;">🔕</div>
      <p style="margin:0;font-size:15px;font-weight:600;">Tidak Ada Notifikasi dalam Kategori Ini</p>
      <small class="muted">Semua pemberitahuan pesanan dan jadwal akan muncul di sini secara otomatis.</small>
    </div>
  <?php else: ?>
    <div style="display:flex;flex-direction:column;gap:12px;">
      <?php foreach ($notifications as $n): ?>
        <div style="padding:16px 20px;border-radius:12px;border:1.5px solid <?= $n['is_read'] ? '#e2e8f0' : 'var(--terracotta)' ?>;border-left:6px solid <?= $n['is_read'] ? '#cbd5e1' : 'var(--terracotta-dark)' ?>;background:<?= $n['is_read'] ? '#ffffff' : '#fffaf5' ?>;box-shadow:<?= $n['is_read'] ? 'none' : '0 4px 14px rgba(216,133,78,0.12)' ?>;display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:16px;transition:all 0.2s ease;">
          <div style="flex:1;min-width:260px;">
            <div style="display:flex;align-items:center;gap:10px;margin-bottom:6px;flex-wrap:wrap;">
              <strong style="font-size:15px;color:<?= $n['is_read'] ? 'var(--espresso)' : 'var(--terracotta-dark)' ?>;">
                <?= e($n['title']) ?>
              </strong>
              <?php if (!$n['is_read']): ?>
                <span style="background:#dc3545;color:#fff;font-size:10.5px;font-weight:700;padding:2.5px 9px;border-radius:10px;display:inline-flex;align-items:center;gap:4px;">
                  <span style="width:6px;height:6px;border-radius:50%;background:#fff;display:inline-block;"></span> BELUM DIBACA
                </span>
              <?php else: ?>
                <span style="background:#f1f5f9;color:#64748b;font-size:10.5px;font-weight:600;padding:2.5px 9px;border-radius:10px;">
                  ✓ Sudah Dibaca
                </span>
              <?php endif; ?>
            </div>
            
            <p style="margin:0 0 8px;font-size:13.5px;color:<?= $n['is_read'] ? '#475569' : '#1e293b' ?>;line-height:1.5;">
              <?= nl2br(e($n['message'])) ?>
            </p>
            
            <small style="color:#64748b;font-size:11.5px;display:flex;align-items:center;gap:6px;">
              <span>⏰ <?= time_ago($n['created_at']) ?></span>
              <span>•</span>
              <span><?= date('d/m/Y H:i', strtotime($n['created_at'])) ?> WIB</span>
            </small>
          </div>

          <div style="display:flex;gap:8px;align-items:center;flex-wrap:wrap;">
            <?php if (!$n['is_read']): ?>
              <a href="<?= BASE_URL ?>/admin/notifications.php?action=mark_read&id=<?= (int)$n['id'] ?>" class="btn btn-outline btn-sm" style="font-size:12px;padding:6px 12px;" title="Tandai Sudah Dibaca">
                ✓ Tandai Dibaca
              </a>
            <?php endif; ?>
            
            <?php if (!empty($n['order_id'])): ?>
              <a href="<?= BASE_URL ?>/admin/notifications.php?action=view_order&id=<?= (int)$n['id'] ?>&order_id=<?= (int)$n['order_id'] ?>" class="btn btn-primary btn-sm" style="font-size:12px;padding:6px 14px;font-weight:700;">
                Detail Order →
              </a>
            <?php endif; ?>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</div>

<?php include '../includes/admin_footer.php'; ?>
