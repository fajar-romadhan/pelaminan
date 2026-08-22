<?php
require_once '../config/database.php';
require_once '../config/helpers.php';
require_admin();

$pageTitle = 'Dashboard Admin'; 
$active = 'dashboard';

$totalProducts = (int)$pdo->query('SELECT COUNT(*) FROM products')->fetchColumn();
$totalOrders = (int)$pdo->query('SELECT COUNT(*) FROM orders')->fetchColumn();
$pendingReviewCount = (int)$pdo->query("SELECT COUNT(*) FROM orders WHERE status IN ('PAYMENT_RECEIVED', 'ADMIN_REVIEW')")->fetchColumn();
$producingCount = (int)$pdo->query("SELECT COUNT(*) FROM orders WHERE status = 'PRODUCTION'")->fetchColumn();
$totalRevenue = (float)$pdo->query("SELECT COALESCE(SUM(paid_amount),0) FROM orders WHERE status NOT IN ('CANCELLED', 'REJECTED')")->fetchColumn();

$latest = $pdo->query('SELECT o.*, p.name AS product_name FROM orders o JOIN products p ON p.id=o.product_id ORDER BY o.id DESC LIMIT 5')->fetchAll();

include '../includes/admin_header.php';
?>
<div class="grid grid-4">
  <div class="card stat-card"><div class="stat-icon">🛒</div><div><div class="stat-number"><?= $totalOrders ?></div><p>Total Pesanan</p></div></div>
  <div class="card stat-card" style="border-left:4px solid #ffc107;"><div class="stat-icon">📋</div><div><div class="stat-number"><?= $pendingReviewCount ?></div><p>Menunggu Review</p></div></div>
  <div class="card stat-card" style="border-left:4px solid #007bff;"><div class="stat-icon">🔨</div><div><div class="stat-number"><?= $producingCount ?></div><p>Sedang Produksi</p></div></div>
  <div class="card stat-card" style="border-left:4px solid #28a745;"><div class="stat-icon">💰</div><div><div class="stat-number" style="font-size:18px"><?= rupiah($totalRevenue) ?></div><p>Pendapatan Masuk</p></div></div>
</div>

<div class="card" style="margin-top:24px">
  <h3 style="color:var(--terracotta-dark)">Pesanan Terbaru</h3>
  <div class="table-wrap">
    <table class="table">
      <thead>
        <tr>
          <th>No. Pesanan</th>
          <th>Customer</th>
          <th>Produk</th>
          <th>Total</th>
          <th>Status</th>
          <th>Aksi</th>
        </tr>
      </thead>
      <tbody>
      <?php foreach($latest as $o): ?>
        <tr>
          <td><strong><?= e($o['order_code']) ?></strong></td>
          <td><?= e($o['customer_name']) ?></td>
          <td><?= e($o['product_name']) ?></td>
          <td><?= rupiah($o['total_amount']) ?></td>
          <td><span class="badge <?= status_class($o['status']) ?>"><?= status_label($o['status']) ?></span></td>
          <td><a class="btn btn-outline btn-sm" href="<?= BASE_URL ?>/admin/orders.php?detail=<?= (int)$o['id'] ?>">Detail</a></td>
        </tr>
      <?php endforeach; ?>
      <?php if(!$latest): ?>
        <tr><td colspan="6" style="text-align:center;padding:30px">Belum ada pesanan terbaru.</td></tr>
      <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>
<?php include '../includes/admin_footer.php'; ?>
