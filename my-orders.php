<?php
require_once 'config/database.php';
require_once 'config/helpers.php';
require_customer();

$stmt = $pdo->prepare('
    SELECT o.*, p.name AS product_name 
    FROM orders o 
    JOIN products p ON p.id=o.product_id 
    WHERE o.user_id=? 
    ORDER BY o.id DESC
');
$stmt->execute([current_user()['id']]);
$orders = $stmt->fetchAll();

$pageTitle = 'Pesanan Saya'; 
$active = 'orders'; 
include 'includes/header.php';
?>
<div class="page-head"><div class="container"><h1>Pesanan Saya</h1><p>Pantau status dan riwayat pesanan Anda</p></div></div>
<main class="container" style="padding-top:30px">
  <div class="table-wrap">
    <table class="table">
      <thead>
        <tr>
          <th>No. Pesanan</th>
          <th>Produk</th>
          <th>Tanggal Order</th>
          <th>Jadwal Pengerjaan</th>
          <th>Total</th>
          <th>Status</th>
          <th>Aksi</th>
        </tr>
      </thead>
      <tbody>
      <?php foreach($orders as $o): ?>
        <tr>
          <td><strong style="color:var(--terracotta-dark)"><?= e($o['order_code']) ?></strong></td>
          <td><?= e($o['product_name']) ?></td>
          <td><?= date('d M Y', strtotime($o['created_at'])) ?></td>
          <td>
            <?php if (!empty($o['schedule_start']) && !empty($o['schedule_end'])): ?>
              <span class="badge badge-info" style="font-size:12px;">
                🔨 Diproses <?= format_date_range($o['schedule_start'], $o['schedule_end']) ?>
              </span>
            <?php else: ?>
              <small class="muted">Belum dijadwalkan</small>
            <?php endif; ?>
          </td>
          <td><strong><?= rupiah($o['total_amount']) ?></strong></td>
          <td><span class="badge <?= status_class($o['status']) ?>"><?= status_label($o['status']) ?></span></td>
          <td class="actions" style="white-space:nowrap;">
            <a class="btn btn-outline btn-sm" href="<?= BASE_URL ?>/tracking.php?order_id=<?= (int)$o['id'] ?>">Tracking</a>
            <a class="btn btn-outline btn-sm" style="border-color:var(--terracotta-dark);color:var(--terracotta-dark);" href="<?= BASE_URL ?>/invoice.php?order_id=<?= (int)$o['id'] ?>" target="_blank">📄 Invoice</a>
            <?php if((float)$o['paid_amount'] > 0): ?>
              <a class="btn btn-outline btn-sm" style="border-color:#28a745;color:#28a745;" href="<?= BASE_URL ?>/receipt.php?order_id=<?= (int)$o['id'] ?>" target="_blank">🧾 Kwitansi</a>
            <?php endif; ?>
            <?php if($o['status'] === 'WAITING_PAYMENT'): ?>
              <a class="btn btn-primary btn-sm" href="<?= BASE_URL ?>/payment.php?order_id=<?= (int)$o['id'] ?>&type=dp">💳 Upload Bukti DP</a>
            <?php elseif($o['status'] === 'ADMIN_REVIEW'): ?>
              <span class="badge badge-warning" style="font-size:11px;padding:6px 10px;">⏳ Verifikasi Pembayaran</span>
            <?php endif; ?>
            <?php if(in_array($o['status'], ['READY_PICKUP', 'READY_DELIVERY', 'ON_DELIVERY', 'DELIVERED', 'READY_INSTALLATION', 'INSTALLATION', 'COMPLETED'], true) && (float)$o['paid_amount'] < (float)$o['total_amount']): ?>
              <a class="btn btn-gold btn-sm" href="<?= BASE_URL ?>/payment.php?order_id=<?= (int)$o['id'] ?>&type=final">💳 Upload Bukti Pelunasan</a>
            <?php endif; ?>
          </td>
        </tr>
      <?php endforeach; ?>
      <?php if(!$orders): ?>
        <tr>
          <td colspan="7" style="text-align:center;padding:40px">
            Belum ada pesanan. <a style="color:var(--terracotta-dark);font-weight:900" href="<?= BASE_URL ?>/gallery.php">Lihat katalog produk</a>
          </td>
        </tr>
      <?php endif; ?>
      </tbody>
    </table>
  </div>
</main>
<?php include 'includes/footer.php'; ?>
