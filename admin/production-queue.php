<?php
require_once '../config/database.php';
require_once '../config/helpers.php';
require_admin();

$pageTitle = 'Manajemen Antrean Produksi (Single Production Queue)'; 
$active = 'queue';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf_token($_POST['csrf_token'] ?? null);

    $queueId = filter_input(INPUT_POST, 'queue_id', FILTER_VALIDATE_INT);
    $newOrderStatus = $_POST['new_order_status'] ?? '';

    if ($queueId && !empty($newOrderStatus)) {
        $qStmt = $pdo->prepare("
            SELECT o.id AS queue_id, o.id AS order_id, o.order_code, o.user_id, o.status AS current_order_status,
                   o.queue_number, IF(o.status = 'PRODUCTION', 'PRODUCING', 'WAITING') AS production_status
            FROM orders o
            WHERE o.id = ?
            LIMIT 1
        ");
        $qStmt->execute([$queueId]);
        $queueItem = $qStmt->fetch();

        if ($queueItem) {
            $orderId = (int)$queueItem['order_id'];
            $userId = (int)$queueItem['user_id'];
            $oldOrderStatus = $queueItem['current_order_status'];

            // STRICT RULE CHECK: Only 1 order with status PRODUCTION
            if ($newOrderStatus === 'PRODUCTION') {
                $checkActiveProd = $pdo->prepare("
                    SELECT COUNT(*) 
                    FROM orders 
                    WHERE status = 'PRODUCTION' AND id != ?
                ");
                $checkActiveProd->execute([$orderId]);
                $activeCount = (int)$checkActiveProd->fetchColumn();

                if ($activeCount > 0) {
                    set_flash('danger', '⚠️ ATURAN ANTREAN: Hanya boleh ada 1 order dengan status PRODUCTION. Selesaikan pengerjaan order sebelumnya terlebih dahulu!');
                    redirect(BASE_URL . '/admin/production-queue.php');
                }
            }

            $pdo->beginTransaction();
            try {
                // Update orders table
                $updOrder = $pdo->prepare("UPDATE orders SET status = ? WHERE id = ?");
                $updOrder->execute([$newOrderStatus, $orderId]);

                // Log status history
                log_order_status_change($pdo, $orderId, $oldOrderStatus, $newOrderStatus, current_user()['id']);

                // Custom notification messages
                $notifMessages = [
                    'PRODUCTION' => "Pesanan Anda #{$queueItem['order_code']} sedang diproduksi oleh tim pengerajin.",
                    'READY_INSTALLATION' => "Pesanan Anda #{$queueItem['order_code']} telah selesai diproduksi dan SIAP DIPASANG.",
                    'INSTALLATION' => "Tim kami sedang melakukan PEMASANGAN pelaminan di lokasi acara Anda.",
                    'COMPLETED' => "Pesanan Pelaminan #{$queueItem['order_code']} telah SELESAI. Terima kasih telah mempercayai Distributor Pelaminan Family!"
                ];

                $msg = $notifMessages[$newOrderStatus] ?? "Status pesanan Anda #{$queueItem['order_code']} diperbarui menjadi " . status_label($newOrderStatus);

                send_system_notification(
                    $pdo,
                    $userId,
                    $orderId,
                    'Perubahan Status Pesanan',
                    $msg
                );

                $pdo->commit();
                set_flash('success', "Status antrean pesanan {$queueItem['order_code']} berhasil diperbarui ke: " . status_label($newOrderStatus));
            } catch (Throwable $e) {
                $pdo->rollBack();
                set_flash('danger', 'Gagal mengupdate status antrean: ' . $e->getMessage());
            }
        }
    }
    redirect(BASE_URL . '/admin/production-queue.php');
}

// Fetch queue list ordered by queue_number
$queueList = $pdo->query("
    SELECT o.id AS queue_id, o.id AS order_id, o.order_code, o.customer_name, o.phone, o.status AS order_status, o.event_date, o.pickup_method,
           o.schedule_start AS estimated_start_date, o.schedule_end AS estimated_end_date,
           COALESCE(o.queue_number, o.id) AS queue_number,
           IF(o.status = 'PRODUCTION', 'PRODUCING', IF(o.status = 'COMPLETED', 'COMPLETED', 'WAITING')) AS production_status,
           p.name AS product_name, p.production_duration
    FROM orders o
    JOIN products p ON p.id = o.product_id
    WHERE o.status NOT IN ('CANCELLED', 'REJECTED')
    ORDER BY COALESCE(o.queue_number, o.id) ASC
")->fetchAll();

// Check if currently producing order exists
$currentProducing = array_filter($queueList, function($q) {
    return $q['production_status'] === 'PRODUCING' || $q['order_status'] === 'PRODUCTION';
});
$producingOrder = !empty($currentProducing) ? reset($currentProducing) : null;

include '../includes/admin_header.php';
?>

<div class="card" style="margin-bottom:20px;">
  <div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:12px;">
    <div>
      <h2 style="color:var(--terracotta-dark);margin:0 0 4px;">⚙️ Modul Antrean Produksi (Single Production Queue)</h2>
      <p class="muted" style="margin:0;">Aturan utama: Hanya 1 pesanan yang dapat berstatus <strong>PRODUCTION</strong> dalam 1 waktu. Pesanan lain wajib mengantre.</p>
    </div>
    <div>
      <a class="btn btn-outline" href="<?= BASE_URL ?>/admin/production-calendar.php">📅 Lihat Kalender Produksi</a>
    </div>
  </div>
</div>

<?php if ($producingOrder): ?>
  <div class="card" style="margin-bottom:20px;background:#eef7ff;border:2px solid #007bff;">
    <div style="display:flex;align-items:center;gap:12px;">
      <span style="font-size:32px;">🔨</span>
      <div>
        <h4 style="margin:0;color:#0056b3;">Pesanan Sedang Diproduksi Saat Ini (Active Production):</h4>
        <strong style="font-size:18px;color:var(--terracotta-dark);">
          Antrean #<?= sprintf('%03d', $producingOrder['queue_number']) ?> - <?= e($producingOrder['order_code']) ?> (<?= e($producingOrder['customer_name']) ?>)
        </strong>
        <p style="margin:4px 0 0;font-size:14px;">
          Produk: <strong><?= e($producingOrder['product_name']) ?></strong> | Metode: <span class="badge badge-info"><?= $producingOrder['pickup_method'] === 'diambil' ? '🏪 Diambil Sendiri' : '🚚 Diantarkan' ?></span> | Estimasi: <strong><?= format_date_range($producingOrder['estimated_start_date'], $producingOrder['estimated_end_date']) ?></strong>
        </p>
      </div>
    </div>
  </div>
<?php else: ?>
  <div class="alert alert-info" style="margin-bottom:20px;">
    ℹ️ Belum ada pesanan yang sedang diproduksi (Status `PRODUCTION` kosong). Admin dapat memulai pengerjaan antrean pertama.
  </div>
<?php endif; ?>

<div class="table-wrap">
  <table class="table">
    <thead>
      <tr>
        <th>No. Antrean</th>
        <th>No. Order</th>
        <th>Customer</th>
        <th>Produk & Penerimaan</th>
        <th>Tanggal Acara</th>
        <th>Estimasi Pengerjaan</th>
        <th>Status Order</th>
        <th>Status Antrean</th>
        <th>Aksi Status</th>
      </tr>
    </thead>
    <tbody>
    <?php foreach($queueList as $q): ?>
      <tr style="<?= $q['production_status'] === 'PRODUCING' ? 'background:rgba(0,123,255,0.05);font-weight:bold;' : '' ?>">
        <td>
          <span class="badge badge-primary" style="font-size:15px;padding:6px 10px;">
            #<?= sprintf('%03d', $q['queue_number']) ?>
          </span>
        </td>
        <td><strong style="color:var(--terracotta-dark)"><?= e($q['order_code']) ?></strong></td>
        <td><?= e($q['customer_name']) ?><br><small class="muted"><?= e($q['phone']) ?></small></td>
        <td>
          <?= e($q['product_name']) ?><br>
          <span class="badge badge-info" style="font-size:11px;margin-top:2px;">
            <?= $q['pickup_method'] === 'diambil' ? '🏪 Diambil' : '🚚 Diantar' ?>
          </span>
        </td>
        <td><?= !empty($q['event_date']) ? date('d M Y', strtotime($q['event_date'])) : '-' ?></td>
        <td>
          <small style="font-weight:bold;color:var(--espresso);">
            Diproses <?= format_date_range($q['estimated_start_date'], $q['estimated_end_date']) ?>
          </small>
        </td>
        <td><span class="badge <?= status_class($q['order_status']) ?>"><?= status_label($q['order_status']) ?></span></td>
        <td>
          <?php if($q['production_status'] === 'PRODUCING'): ?>
            <span class="badge badge-primary">🔨 PRODUCING</span>
          <?php elseif($q['production_status'] === 'COMPLETED'): ?>
            <span class="badge badge-success">✓ COMPLETED</span>
          <?php else: ?>
            <span class="badge badge-warning">⏳ WAITING</span>
          <?php endif; ?>
        </td>
        <td class="actions">
          <form method="post" style="display:inline-block;">
            <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
            <input type="hidden" name="queue_id" value="<?= (int)$q['id'] ?>">

            <?php if ($q['order_status'] === 'WAITING_QUEUE'): ?>
              <button type="submit" name="new_order_status" value="PRODUCTION" class="btn btn-primary btn-sm" onclick="return confirm('Mulai pengerjaan produksi untuk antrean ini?')">
                ▶️ Mulai Produksi
              </button>

            <?php elseif ($q['order_status'] === 'PRODUCTION'): ?>
              <?php if ($q['pickup_method'] === 'diambil'): ?>
                <button type="submit" name="new_order_status" value="READY_PICKUP" class="btn btn-purple btn-sm" style="background:#6f42c1;color:#fff;" onclick="return confirm('Tandai produksi SELESAI dan Siap Diambil di tempat?')">
                  📦 Siap Diambil
                </button>
              <?php else: ?>
                <button type="submit" name="new_order_status" value="READY_DELIVERY" class="btn btn-purple btn-sm" style="background:#6f42c1;color:#fff;" onclick="return confirm('Tandai produksi SELESAI dan Siap Dikirim?')">
                  🚚 Siap Dikirim
                </button>
              <?php endif; ?>

            <?php elseif ($q['order_status'] === 'READY_PICKUP'): ?>
              <button type="submit" name="new_order_status" value="COMPLETED" class="btn btn-success btn-sm" style="background:#28a745;color:#fff;" onclick="return confirm('Tandai pesanan telah diambil customer & SELESAI?')">
                ✅ Selesai (Sudah Diambil)
              </button>

            <?php elseif ($q['order_status'] === 'READY_DELIVERY' || $q['order_status'] === 'READY_INSTALLATION'): ?>
              <button type="submit" name="new_order_status" value="ON_DELIVERY" class="btn btn-sm" style="background:#17a2b8;color:#fff;" onclick="return confirm('Tandai pesanan sedang Dalam Pengiriman?')">
                🚛 Dalam Pengiriman
              </button>

            <?php elseif ($q['order_status'] === 'ON_DELIVERY' || $q['order_status'] === 'INSTALLATION'): ?>
              <button type="submit" name="new_order_status" value="DELIVERED" class="btn btn-success btn-sm" style="background:#28a745;color:#fff;" onclick="return confirm('Tandai pesanan Sudah Diterima oleh customer?')">
                🏠 Sudah Diterima
              </button>

            <?php elseif ($q['order_status'] === 'DELIVERED'): ?>
              <button type="submit" name="new_order_status" value="COMPLETED" class="btn btn-success btn-sm" style="background:#28a745;color:#fff;" onclick="return confirm('Selesaikan pesanan secara penuh?')">
                ✅ Selesai
              </button>

            <?php else: ?>
              <a class="btn btn-outline btn-sm" href="<?= BASE_URL ?>/tracking.php?order_id=<?= (int)$q['order_id'] ?>" target="_blank">Lihat Tracking</a>
            <?php endif; ?>
          </form>
        </td>
      </tr>
    <?php endforeach; ?>
    <?php if(empty($queueList)): ?>
      <tr>
        <td colspan="9" style="text-align:center;padding:40px">
          Belum ada pesanan dalam antrean produksi. Approve pesanan pada halaman <a href="<?= BASE_URL ?>/admin/order-review.php">Review Pesanan</a>.
        </td>
      </tr>
    <?php endif; ?>
    </tbody>
  </table>
</div>

<?php include '../includes/admin_footer.php'; ?>
