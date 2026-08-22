<?php
require_once 'config/database.php';
require_once 'config/helpers.php';
require_login();

$orderId = filter_input(INPUT_GET, 'order_id', FILTER_VALIDATE_INT);
if (!$orderId) {
    redirect(BASE_URL . '/my-orders.php');
}

$stmt = $pdo->prepare('
    SELECT o.*, p.name AS product_name, p.production_duration 
    FROM orders o 
    JOIN products p ON p.id=o.product_id 
    WHERE o.id=? AND (o.user_id=? OR ?="admin") 
    LIMIT 1
');
$stmt->execute([$orderId, current_user()['id'], current_user()['role']]);
$order = $stmt->fetch();

if (!$order) {
    set_flash('danger', 'Pesanan tidak ditemukan atau Anda tidak memiliki akses.');
    redirect(BASE_URL . '/my-orders.php');
}

// Fetch workshop/business address from settings
$businessAddress = get_setting($pdo, 'business_address', 'Jl. Betawi Raya RS. Benteng, Perumahan Kencana Indah Blok C.1 No. 17, Palembang');

// Queue details
$queueData = get_customer_queue_position($pdo, $orderId);
$pickupMethod = $order['pickup_method'] ?? 'diantar';

if ($pickupMethod === 'diambil') {
    // 5 Steps for Self-pickup
    $timelineSteps = [
        'step1' => ['key' => 'PAYMENT_RECEIVED', 'label' => 'Pembayaran & Verifikasi'],
        'step2' => ['key' => 'WAITING_QUEUE', 'label' => 'Masuk Antrean Produksi'],
        'step3' => ['key' => 'PRODUCTION', 'label' => 'Sedang Diproduksi'],
        'step4' => ['key' => 'READY_PICKUP', 'label' => 'Siap Diambil'],
        'step5' => ['key' => 'COMPLETED', 'label' => 'Pesanan Selesai']
    ];

    $ranks = [
        'WAITING_PAYMENT' => 0,
        'PAYMENT_RECEIVED' => 1,
        'ADMIN_REVIEW' => 1,
        'WAITING_QUEUE' => 2,
        'PRODUCTION' => 3,
        'READY_PICKUP' => 4,
        'COMPLETED' => 5,
        'REJECTED' => -1,
        'CANCELLED' => -1
    ];
} else {
    // 5 Steps for Delivery
    $timelineSteps = [
        'step1' => ['key' => 'PAYMENT_RECEIVED', 'label' => 'Pembayaran & Verifikasi'],
        'step2' => ['key' => 'WAITING_QUEUE', 'label' => 'Masuk Antrean Produksi'],
        'step3' => ['key' => 'PRODUCTION', 'label' => 'Sedang Diproduksi'],
        'step4' => ['key' => 'ON_DELIVERY', 'label' => 'Dalam Pengiriman'],
        'step5' => ['key' => 'COMPLETED', 'label' => 'Pesanan Selesai']
    ];

    $ranks = [
        'WAITING_PAYMENT' => 0,
        'PAYMENT_RECEIVED' => 1,
        'ADMIN_REVIEW' => 1,
        'WAITING_QUEUE' => 2,
        'PRODUCTION' => 3,
        'READY_DELIVERY' => 4,
        'READY_INSTALLATION' => 4,
        'ON_DELIVERY' => 4,
        'INSTALLATION' => 4,
        'DELIVERED' => 4,
        'COMPLETED' => 5,
        'REJECTED' => -1,
        'CANCELLED' => -1
    ];
}

$currentRank = $ranks[$order['status']] ?? 0;

$pageTitle = 'Tracking Pesanan & Antrean Produksi'; 
$active = 'orders'; 
include 'includes/header.php';
?>
<div class="page-head"><div class="container"><h1>Tracking Pesanan & Antrean Produksi</h1><p><?= e($order['order_code']) ?> · <?= e($order['product_name']) ?></p></div></div>
<main class="container" style="padding-top:30px;max-width:850px">
  <div class="card" style="margin-bottom:18px">
    <div class="grid grid-4">
      <div><small class="muted">Nomor Order</small><br><strong style="color:var(--terracotta-dark);font-size:18px"><?= e($order['order_code']) ?></strong></div>
      <div>
        <small class="muted">Nomor Antrean</small><br>
        <strong style="color:var(--terracotta-dark);font-size:18px">
          <?= $queueData ? '#' . sprintf('%03d', $queueData['queue_number']) : '-' ?>
        </strong>
      </div>
      <div><small class="muted">Status Pesanan</small><br><span class="badge <?= status_class($order['status']) ?>" style="font-size:14px;padding:6px 12px;"><?= status_label($order['status']) ?></span></div>
      <div><small class="muted">Total Harga</small><br><strong><?= rupiah($order['total_amount']) ?></strong></div>
    </div>
    <div style="border-top:1px dashed #e0e0e0;margin-top:14px;padding-top:12px;display:flex;gap:10px;flex-wrap:wrap;justify-content:flex-end;">
      <a class="btn btn-outline btn-sm" style="border-color:var(--terracotta-dark);color:var(--terracotta-dark);font-weight:bold;" href="<?= BASE_URL ?>/invoice.php?order_id=<?= (int)$order['id'] ?>" target="_blank">
        📄 Lihat Invoice Tagihan
      </a>
      <?php if ((float)$order['paid_amount'] > 0): ?>
        <a class="btn btn-outline btn-sm" style="border-color:#28a745;color:#28a745;font-weight:bold;" href="<?= BASE_URL ?>/receipt.php?order_id=<?= (int)$order['id'] ?>" target="_blank">
          🧾 Kwitansi Pembayaran
        </a>
      <?php endif; ?>
    </div>
  </div>

  <?php if ($pickupMethod === 'diambil' && in_array($order['status'], ['READY_PICKUP', 'COMPLETED'], true)): ?>
    <div class="card" style="margin-bottom:18px;background:#eef7ee;border:2px solid #28a745;padding:20px;border-radius:10px;">
      <h3 style="color:#155724;margin:0 0 12px;font-size:20px;">📦 Pesanan Anda sudah selesai dan dapat diambil.</h3>
      <div style="font-size:15px;line-height:1.6;color:#1e4620;">
        <p style="margin:4px 0;"><strong>📍 Lokasi pengambilan:</strong> <?= e($businessAddress) ?></p>
        <p style="margin:4px 0;"><strong>📅 Tanggal pengambilan:</strong> <?= format_indonesian_date($order['schedule_end'] ?: date('Y-m-d')) ?></p>
        <p style="margin:4px 0;"><strong>⏰ Jam operasional:</strong> 09.00–17.00 WIB</p>
      </div>
    </div>
  <?php endif; ?>

  <?php 
  $schedStart = !empty($order['schedule_start']) ? $order['schedule_start'] : (!empty($queueData['estimated_start_date']) ? $queueData['estimated_start_date'] : null);
  $schedEnd = !empty($order['schedule_end']) ? $order['schedule_end'] : (!empty($queueData['estimated_end_date']) ? $queueData['estimated_end_date'] : null);
  if (!empty($schedStart) && !empty($schedEnd)): 
  ?>
    <div class="card" style="margin-bottom:18px;background:#fff9f0;border:1.5px solid var(--terracotta);padding:16px 20px;border-radius:12px;">
      <div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:12px;">
        <div>
          <span style="font-weight:700;color:var(--espresso);font-size:13.5px;display:block;">🔨 Jadwal Pengerjaan Produksi Workshop:</span>
          <strong style="font-size:17px;color:var(--terracotta-dark);margin-top:2px;display:block;">
            <?= format_date_range($schedStart, $schedEnd) ?>
          </strong>
        </div>
        <div style="font-size:12px;color:#27ae60;font-weight:600;background:#eef7ee;border:1px solid #b7e4c7;padding:6px 12px;border-radius:8px;">
          ✓ Telah Dijadwalkan Admin
        </div>
      </div>
    </div>
  <?php endif; ?>

  <div class="card" style="margin-top:18px;padding:24px;border-radius:16px;">
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:20px;padding-bottom:14px;border-bottom:1px solid var(--border-subtle);flex-wrap:wrap;gap:10px;">
      <div>
        <h3 style="color:var(--terracotta-dark);margin:0;font-size:20px;font-weight:700;">📍 Timeline Status Pesanan</h3>
        <p style="font-size:12.5px;color:var(--muted);margin:4px 0 0;">Lacak perkembangan pesanan pelaminan Anda secara real-time</p>
      </div>
      <span style="background:rgba(216,133,78,0.12);color:var(--terracotta-dark);font-weight:700;font-size:13px;padding:6px 14px;border-radius:20px;border:1px solid rgba(216,133,78,0.3);">
        <?= $pickupMethod === 'diambil' ? '🏪 Diambil Sendiri' : '🚚 Diantarkan' ?>
      </span>
    </div>

    <!-- Stepper Grid Container -->
    <div style="display:grid;grid-template-columns:repeat(auto-fit, minmax(130px, 1fr));gap:12px;margin-top:10px;">
      <?php 
      $stepIndex = 1;
      foreach ($timelineSteps as $stepKey => $stepData):
          $isDone = ($currentRank > $stepIndex);
          $isActive = ($currentRank === $stepIndex);
          
          if ($isDone) {
              $bgColor = '#f4fbf7';
              $borderColor = '#27ae60';
              $badgeBg = '#27ae60';
              $badgeColor = '#ffffff';
              $iconSymbol = '✓';
              $statusLabel = 'Selesai';
              $statusTagColor = '#27ae60';
              $statusTagBg = '#e6f7ec';
          } elseif ($isActive) {
              $bgColor = '#fffdfa';
              $borderColor = 'var(--terracotta-dark)';
              $badgeBg = 'var(--terracotta-dark)';
              $badgeColor = '#ffffff';
              $iconSymbol = '⚡';
              $statusLabel = 'Sedang Berjalan';
              $statusTagColor = 'var(--terracotta-dark)';
              $statusTagBg = '#fff0e6';
          } else {
              $bgColor = '#fcfcfc';
              $borderColor = '#e8e8e8';
              $badgeBg = '#f0f0f0';
              $badgeColor = '#888888';
              $iconSymbol = $stepIndex;
              $statusLabel = 'Menunggu';
              $statusTagColor = '#888888';
              $statusTagBg = '#f0f0f0';
          }
      ?>
        <div style="background:<?= $bgColor ?>;border:1.5px solid <?= $borderColor ?>;border-radius:12px;padding:14px 10px;text-align:center;position:relative;box-shadow:<?= $isActive ? '0 4px 14px rgba(216,133,78,0.2)' : 'none' ?>;transition:all 0.3s ease;">
          <div style="width:32px;height:32px;border-radius:50%;background:<?= $badgeBg ?>;color:<?= $badgeColor ?>;display:flex;align-items:center;justify-content:center;font-weight:bold;font-size:13px;margin:0 auto 8px;<?= $isActive ? 'box-shadow:0 0 0 4px rgba(216,133,78,0.25);' : '' ?>">
            <?= $iconSymbol ?>
          </div>
          <div style="font-weight:700;font-size:12.5px;color:<?= $isDone ? '#1e7e34' : ($isActive ? 'var(--terracotta-dark)' : '#555') ?>;line-height:1.3;margin-bottom:6px;min-height:32px;display:flex;align-items:center;justify-content:center;">
            <?= e($stepData['label']) ?>
          </div>
          <span style="font-size:10.5px;font-weight:600;color:<?= $statusTagColor ?>;background:<?= $statusTagBg ?>;padding:3px 8px;border-radius:10px;display:inline-block;">
            <?= $statusLabel ?>
          </span>
        </div>
      <?php $stepIndex++; endforeach; ?>
    </div>
  </div>

  <div class="card" style="margin-top:18px">
    <h3 style="color:var(--terracotta-dark)">Data Penerimaan</h3>
    <div class="grid grid-2" style="margin-bottom:12px">
      <div><small class="muted">Metode Penerimaan</small><br><strong><?= $pickupMethod === 'diambil' ? 'Diambil Sendiri di Workshop' : 'Diantarkan ke Alamat Acara' ?></strong></div>
      <div><small class="muted">Patokan Lokasi</small><br><strong><?= e($order['delivery_note'] ?: '-') ?></strong></div>
      <div style="grid-column: span 2;"><small class="muted">Alamat Acara / Customer</small><br><strong><?= e($order['address']) ?></strong></div>
    </div>

    <?php if ($pickupMethod === 'diantar' && !empty($order['delivery_latitude']) && !empty($order['delivery_longitude'])): ?>
      <div id="tracking-map" class="admin-map-view" style="height:260px;margin-top:10px;"></div>
      <div style="margin-top:12px">
        <a href="https://www.google.com/maps?q=<?= e($order['delivery_latitude']) ?>,<?= e($order['delivery_longitude']) ?>" target="_blank" rel="noopener noreferrer" class="btn btn-primary btn-sm">
          🗺️ Buka di Google Maps
        </a>
      </div>
      <script>
        document.addEventListener('DOMContentLoaded', function() {
          const lat = <?= (float)$order['delivery_latitude'] ?>;
          const lng = <?= (float)$order['delivery_longitude'] ?>;
          const map = L.map('tracking-map').setView([lat, lng], 15);
          L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            maxZoom: 19,
            attribution: '&copy; OpenStreetMap'
          }).addTo(map);
          L.marker([lat, lng]).addTo(map)
            .bindPopup('<b>Lokasi Acara</b><br><?= e(addslashes($order['delivery_map_address'] ?? '')) ?>').openPopup();
        });
      </script>
    <?php endif; ?>
  </div>
</main>
<?php include 'includes/footer.php'; ?>
