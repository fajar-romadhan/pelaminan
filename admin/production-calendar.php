<?php
require_once '../config/database.php';
require_once '../config/helpers.php';
require_admin();



$pageTitle = 'Kalender Produksi Pelaminan'; 
$active = 'calendar';

// Month selection (Format YYYY-MM)
$month = $_GET['month'] ?? date('Y-m');
if (!preg_match('/^\d{4}-\d{2}$/', $month)) {
    $month = date('Y-m');
}

$firstDayOfMonth = $month . '-01';
$lastDayOfMonth = date('Y-m-t', strtotime($firstDayOfMonth));
$daysInMonth = (int)date('t', strtotime($firstDayOfMonth));
$startDayOfWeek = (int)date('w', strtotime($firstDayOfMonth)); // 0 (Sun) - 6 (Sat)

// Handle manual date schedule update by admin
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_schedule'])) {
    verify_csrf_token($_POST['csrf_token'] ?? null);

    $orderId = filter_input(INPUT_POST, 'order_id', FILTER_VALIDATE_INT);
    $startDate = $_POST['start_date'] ?? '';
    $endDate = $_POST['end_date'] ?? '';

    if ($orderId && !empty($startDate)) {
        if (empty($endDate)) {
            $oStmt = $pdo->prepare("
                SELECT o.*, p.production_duration 
                FROM orders o
                JOIN products p ON p.id = o.product_id
                WHERE o.id = ?
                LIMIT 1
            ");
            $oStmt->execute([$orderId]);
            $oItem = $oStmt->fetch();
            $duration = (int)($oItem['production_duration'] ?: 3);
            $endDateDays = max(0, $duration - 1);
            $endDate = date('Y-m-d', strtotime($startDate . " +{$endDateDays} days"));
        }

        $updO = $pdo->prepare("UPDATE orders SET schedule_start = ?, schedule_end = ? WHERE id = ?");
        $updO->execute([$startDate, $endDate, $orderId]);

        set_flash('success', "Jadwal pengerjaan antrean berhasil diperbarui: {$startDate} s/d {$endDate}.");
    }
    redirect(BASE_URL . "/admin/production-calendar.php?month={$month}");
}

// Fetch all active orders for calendar display & rescheduling
$qStmt = $pdo->query("
    SELECT o.id AS order_id, o.order_code, o.customer_name, o.phone, o.status AS order_status, o.event_date,
           o.schedule_start, o.schedule_end,
           o.id AS queue_id, COALESCE(o.queue_number, o.id) AS queue_number,
           IF(o.status = 'PRODUCTION', 'PRODUCING', 'WAITING') AS production_status,
           o.schedule_start AS estimated_start_date,
           o.schedule_end AS estimated_end_date,
           p.name AS product_name, p.code AS product_code, p.production_duration
    FROM orders o
    JOIN products p ON p.id = o.product_id
    WHERE o.status NOT IN ('CANCELLED', 'REJECTED')
    ORDER BY o.id DESC
");
$allQueue = $qStmt->fetchAll();

// Map events to specific calendar dates
$calendarEvents = [];
$dateOccupancy = []; // to track overlaps per date

foreach ($allQueue as $q) {
    if (empty($q['estimated_start_date']) || empty($q['estimated_end_date'])) {
        continue;
    }

    $curr = strtotime($q['estimated_start_date']);
    $end = strtotime($q['estimated_end_date']);

    while ($curr <= $end) {
        $dStr = date('Y-m-d', $curr);
        
        if (!isset($calendarEvents[$dStr])) {
            $calendarEvents[$dStr] = [];
        }
        if (!isset($dateOccupancy[$dStr])) {
            $dateOccupancy[$dStr] = [];
        }

        $calendarEvents[$dStr][] = $q;
        $dateOccupancy[$dStr][] = $q['order_code'] . ' (' . $q['customer_name'] . ')';

        $curr = strtotime('+1 day', $curr);
    }
}

// Check for overlap warnings
$overlapWarnings = [];
foreach ($dateOccupancy as $dStr => $ordersOnDate) {
    if (count($ordersOnDate) > 1) {
        $overlapWarnings[] = "<strong>" . date('d F Y', strtotime($dStr)) . "</strong>: " . implode(', ', $ordersOnDate);
    }
}

include '../includes/admin_header.php';
?>

<div class="card" style="margin-bottom:20px;">
  <div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:14px;">
    <div>
      <h2 style="color:var(--terracotta-dark);margin:0 0 4px;">📅 Kalender Produksi Pelaminan</h2>
      <p class="muted" style="margin:0;">Jadwal alokasi pengerjaan produksi berdasarkan antrean aktif (Single Queue).</p>
    </div>
    
    <div style="display:flex;gap:10px;align-items:center;">
      <a class="btn btn-outline btn-sm" href="<?= BASE_URL ?>/admin/production-calendar.php?month=<?= date('Y-m', strtotime($firstDayOfMonth . ' -1 month')) ?>">← Bulan Lalu</a>
      
      <form method="get" action="<?= BASE_URL ?>/admin/production-calendar.php" style="margin:0;">
        <input class="input" type="month" name="month" value="<?= e($month) ?>" onchange="this.form.submit()" style="padding:6px 12px;font-weight:bold;">
      </form>

      <a class="btn btn-outline btn-sm" href="<?= BASE_URL ?>/admin/production-calendar.php?month=<?= date('Y-m', strtotime($firstDayOfMonth . ' +1 month')) ?>">Bulan Depan →</a>
    </div>
  </div>
</div>

<?php if (!empty($overlapWarnings)): ?>
  <div class="alert alert-danger" style="margin-bottom:20px;">
    ⚠️ <strong>PERINGATAN KONFLIK JADWAL PRODUKSI!</strong><br>
    Terdapat tanggal produksi yang digunakan secara bersamaan oleh lebih dari 1 order:<br>
    <ul style="margin:6px 0 0 20px;padding:0;">
      <?php foreach($overlapWarnings as $warn): ?>
        <li><?= $warn ?></li>
      <?php endforeach; ?>
    </ul>
  </div>
<?php endif; ?>

<!-- Status Legend -->
<div class="card" style="margin-bottom:20px;padding:12px 18px;">
  <div style="display:flex;gap:20px;align-items:center;flex-wrap:wrap;font-size:13.5px;">
    <strong>Indikator Warna Status:</strong>
    <span><span class="badge badge-warning">⏳ WAITING_QUEUE</span> : Menunggu Antrean</span>
    <span><span class="badge badge-primary">🔨 PRODUCTION</span> : Sedang Diproduksi</span>
    <span><span class="badge badge-success">✓ COMPLETED</span> : Produksi Selesai</span>
  </div>
</div>

<!-- Calendar Grid -->
<div class="card" style="padding:15px;overflow-x:auto;">
  <h3 style="text-align:center;color:var(--terracotta-dark);margin:0 0 16px;">
    Bulan <?= date('F Y', strtotime($firstDayOfMonth)) ?>
  </h3>

  <div style="display:grid;grid-template-columns:repeat(7, 1fr);gap:8px;min-width:750px;">
    <!-- Day Headers -->
    <?php 
    $dayHeaders = ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];
    foreach ($dayHeaders as $dh): 
    ?>
      <div style="background:var(--terracotta-dark);color:#fff;text-align:center;padding:8px;font-weight:bold;border-radius:6px;font-size:13px;">
        <?= $dh ?>
      </div>
    <?php endforeach; ?>

    <!-- Empty cells before day 1 -->
    <?php for ($i = 0; $i < $startDayOfWeek; $i++): ?>
      <div style="background:#f8f9fa;border:1px solid var(--border-subtle);min-height:95px;border-radius:6px;opacity:0.4;"></div>
    <?php endfor; ?>

    <!-- Days of Month -->
    <?php for ($day = 1; $day <= $daysInMonth; $day++): ?>
      <?php 
      $currentDateStr = sprintf('%s-%02d', $month, $day);
      $isToday = ($currentDateStr === date('Y-m-d'));
      $eventsOnThisDay = $calendarEvents[$currentDateStr] ?? [];
      $hasOverlap = count($eventsOnThisDay) > 1;
      ?>
      <div style="background:<?= $isToday ? '#fffdf0' : '#ffffff' ?>;border:<?= $hasOverlap ? '2px solid #dc3545' : ($isToday ? '2px solid var(--terracotta-dark)' : '1px solid var(--border-subtle)') ?>;min-height:105px;padding:6px;border-radius:6px;position:relative;">
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:6px;">
          <strong style="font-size:14px;color:<?= $isToday ? 'var(--terracotta-dark)' : 'var(--espresso)' ?>;">
            <?= $day ?>
          </strong>
          <?php if($hasOverlap): ?>
            <span title="Konflik Jadwal!" style="color:#dc3545;font-weight:bold;font-size:12px;">⚠️ Bentrok</span>
          <?php endif; ?>
        </div>

        <?php foreach ($eventsOnThisDay as $ev): ?>
          <?php 
          $prodStatus = $ev['production_status'] ?? 'WAITING';
          $orderSt = $ev['order_status'] ?? '';
          $statusBadge = 'badge-warning';

          if ($prodStatus === 'PRODUCING' || $orderSt === 'PRODUCTION') {
              $statusBadge = 'badge-primary';
          } elseif ($prodStatus === 'COMPLETED' || in_array($orderSt, ['COMPLETED', 'selesai'], true)) {
              $statusBadge = 'badge-success';
          }
          ?>
          <div style="margin-bottom:6px;padding:4px 6px;border-radius:4px;font-size:11px;background:rgba(0,0,0,0.03);border-left:3px solid var(--terracotta-dark);">
            <div style="font-weight:bold;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;" title="<?= e($ev['customer_name']) ?>">
              👤 <?= e($ev['customer_name']) ?>
            </div>
            <div style="white-space:nowrap;overflow:hidden;text-overflow:ellipsis;" class="muted" title="<?= e($ev['product_name']) ?>">
              📦 <?= e($ev['product_name']) ?>
            </div>
            <div style="margin-top:2px;">
              <span class="badge <?= $statusBadge ?>" style="font-size:9.5px;padding:2px 5px;">
                <?= e(status_label($orderSt)) ?>
              </span>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    <?php endfor; ?>
  </div>
</div>

<!-- Queue List Reschedule Form -->
<div class="card" style="margin-top:24px;">
  <h3 style="color:var(--terracotta-dark);margin:0 0 14px;">🛠️ Pengaturan Jadwal Pengerjaan Antrean</h3>
  <div class="table-wrap">
    <table class="table">
      <thead>
        <tr>
          <th>Antrean</th>
          <th>No. Order</th>
          <th>Customer</th>
          <th>Produk</th>
          <th>Durasi</th>
          <th>Tanggal Mulai</th>
          <th>Tanggal Selesai</th>
          <th>Aksi Ubah Jadwal</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach($allQueue as $q): ?>
          <tr>
            <td><span class="badge badge-primary">#<?= sprintf('%03d', $q['queue_number']) ?></span></td>
            <td><strong><?= e($q['order_code']) ?></strong></td>
            <td><?= e($q['customer_name']) ?></td>
            <td><?= e($q['product_name']) ?></td>
            <td><?= (int)$q['production_duration'] ?> Hari</td>
            <form method="post">
              <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
              <input type="hidden" name="order_id" value="<?= (int)$q['order_id'] ?>">
              <td>
                <input class="input" type="date" name="start_date" value="<?= e($q['estimated_start_date'] ?? '') ?>" required style="padding:4px 8px;font-size:13px;">
              </td>
              <td>
                <input class="input" type="date" name="end_date" value="<?= e($q['estimated_end_date'] ?? '') ?>" required style="padding:4px 8px;font-size:13px;">
              </td>
              <td>
                <button type="submit" name="update_schedule" value="1" class="btn btn-outline btn-sm">Simpan Jadwal</button>
              </td>
            </form>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<?php include '../includes/admin_footer.php'; ?>
