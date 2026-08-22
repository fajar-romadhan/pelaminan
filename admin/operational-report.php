<?php
require_once '../config/database.php';
require_once '../config/helpers.php';
require_admin();

$pageTitle = 'Laporan Operasional Produksi & Pendapatan'; 
$active = 'reports';

// Filter Parameters
$startDate = $_GET['start_date'] ?? '';
$endDate = $_GET['end_date'] ?? '';
$statusFilter = $_GET['status'] ?? '';
$customerQuery = trim($_GET['customer'] ?? '');

$whereClauses = [];
$params = [];

if (!empty($startDate)) {
    $whereClauses[] = "o.created_at >= ?";
    $params[] = $startDate . ' 00:00:00';
}
if (!empty($endDate)) {
    $whereClauses[] = "o.created_at <= ?";
    $params[] = $endDate . ' 23:59:59';
}
if (!empty($statusFilter)) {
    $whereClauses[] = "o.status = ?";
    $params[] = $statusFilter;
}
if (!empty($customerQuery)) {
    $whereClauses[] = "(o.customer_name LIKE ? OR o.order_code LIKE ? OR o.phone LIKE ?)";
    $params[] = "%{$customerQuery}%";
    $params[] = "%{$customerQuery}%";
    $params[] = "%{$customerQuery}%";
}

$whereSql = !empty($whereClauses) ? 'WHERE ' . implode(' AND ', $whereClauses) : '';

// 1. Operational Metrics
$totalOrders = (int)$pdo->query("SELECT COUNT(*) FROM orders")->fetchColumn();
$completedOrders = (int)$pdo->query("SELECT COUNT(*) FROM orders WHERE status = 'COMPLETED'")->fetchColumn();
$producingOrders = (int)$pdo->query("SELECT COUNT(*) FROM orders WHERE status = 'PRODUCTION'")->fetchColumn();
$waitingQueueOrders = (int)$pdo->query("SELECT COUNT(*) FROM orders WHERE status = 'WAITING_QUEUE'")->fetchColumn();

// 2. Revenue Metrics
$totalTransaction = (float)$pdo->query("SELECT COALESCE(SUM(total_amount),0) FROM orders WHERE status NOT IN ('CANCELLED', 'REJECTED')")->fetchColumn();
$dpReceived = (float)$pdo->query("SELECT COALESCE(SUM(dp_amount),0) FROM orders WHERE paid_amount > 0 AND status NOT IN ('CANCELLED', 'REJECTED')")->fetchColumn();
$finalPaid = (float)$pdo->query("SELECT COALESCE(SUM(paid_amount - dp_amount),0) FROM orders WHERE paid_amount > dp_amount AND status NOT IN ('CANCELLED', 'REJECTED')")->fetchColumn();
$totalRevenue = (float)$pdo->query("SELECT COALESCE(SUM(paid_amount),0) FROM orders WHERE status NOT IN ('CANCELLED', 'REJECTED')")->fetchColumn();

// 3. Filtered Production & Financial List
$reportStmt = $pdo->prepare("
    SELECT o.*, p.name AS product_name, p.production_duration,
           COALESCE(o.queue_number, o.id) AS queue_number,
           o.schedule_start AS estimated_start_date,
           o.schedule_end AS estimated_end_date
    FROM orders o
    JOIN products p ON p.id = o.product_id
    {$whereSql}
    ORDER BY o.id DESC
");
$reportStmt->execute($params);
$reportList = $reportStmt->fetchAll();

include '../includes/admin_header.php';
?>

<div class="card" style="margin-bottom:20px;">
  <h2 style="color:var(--terracotta-dark);margin:0 0 8px;">📊 Laporan Operasional Produksi & Pendapatan</h2>
  <p class="muted" style="margin:0;">Ringkasan statistik performa pemesanan, alokasi antrean produksi, serta rincian arus kas pendapatan.</p>
</div>

<!-- Operational Summary Cards -->
<div class="grid grid-4" style="margin-bottom:20px;">
  <div class="card stat-card">
    <div class="stat-icon">🛒</div>
    <div>
      <div class="stat-number"><?= $totalOrders ?></div>
      <p>Total Pesanan</p>
    </div>
  </div>
  <div class="card stat-card" style="border-left:4px solid #28a745;">
    <div class="stat-icon">✅</div>
    <div>
      <div class="stat-number"><?= $completedOrders ?></div>
      <p>Pesanan Selesai</p>
    </div>
  </div>
  <div class="card stat-card" style="border-left:4px solid #007bff;">
    <div class="stat-icon">🔨</div>
    <div>
      <div class="stat-number"><?= $producingOrders ?></div>
      <p>Sedang Produksi</p>
    </div>
  </div>
  <div class="card stat-card" style="border-left:4px solid #ffc107;">
    <div class="stat-icon">⏳</div>
    <div>
      <div class="stat-number"><?= $waitingQueueOrders ?></div>
      <p>Dalam Antrean</p>
    </div>
  </div>
</div>

<!-- Revenue Summary Cards -->
<div class="grid grid-4" style="margin-bottom:24px;">
  <div class="card stat-card">
    <div class="stat-icon">💵</div>
    <div>
      <div class="stat-number" style="font-size:17px;"><?= rupiah($totalTransaction) ?></div>
      <p>Total Nilai Transaksi</p>
    </div>
  </div>
  <div class="card stat-card" style="border-left:4px solid #17a2b8;">
    <div class="stat-icon">💳</div>
    <div>
      <div class="stat-number" style="font-size:17px;"><?= rupiah($dpReceived) ?></div>
      <p>DP Masuk (50%)</p>
    </div>
  </div>
  <div class="card stat-card" style="border-left:4px solid #6f42c1;">
    <div class="stat-icon">💰</div>
    <div>
      <div class="stat-number" style="font-size:17px;"><?= rupiah($finalPaid) ?></div>
      <p>Pelunasan Masuk</p>
    </div>
  </div>
  <div class="card stat-card" style="border-left:4px solid #28a745;">
    <div class="stat-icon">🏦</div>
    <div>
      <div class="stat-number" style="font-size:17px;color:#28a745;"><?= rupiah($totalRevenue) ?></div>
      <p>Total Revenue Terbayar</p>
    </div>
  </div>
</div>

<!-- Filter Box -->
<div class="card" style="margin-bottom:24px;">
  <h3 style="color:var(--terracotta-dark);margin:0 0 12px;">🔍 Filter Laporan</h3>
  <form method="get" action="<?= BASE_URL ?>/admin/operational-report.php" class="grid grid-4" style="align-items:end;gap:12px;">
    <div class="form-group" style="margin:0;">
      <label>Dari Tanggal</label>
      <input class="input" type="date" name="start_date" value="<?= e($startDate) ?>">
    </div>
    <div class="form-group" style="margin:0;">
      <label>Sampai Tanggal</label>
      <input class="input" type="date" name="end_date" value="<?= e($endDate) ?>">
    </div>
    <div class="form-group" style="margin:0;">
      <label>Status Pesanan</label>
      <select class="select" name="status">
        <option value="">-- Semua Status --</option>
        <option value="WAITING_QUEUE" <?= $statusFilter === 'WAITING_QUEUE' ? 'selected' : '' ?>>Masuk Antrean Produksi</option>
        <option value="PRODUCTION" <?= $statusFilter === 'PRODUCTION' ? 'selected' : '' ?>>Sedang Diproduksi</option>
        <option value="READY_DELIVERY" <?= $statusFilter === 'READY_DELIVERY' ? 'selected' : '' ?>>Dalam Pengiriman</option>
        <option value="READY_PICKUP" <?= $statusFilter === 'READY_PICKUP' ? 'selected' : '' ?>>Siap Diambil</option>
        <option value="COMPLETED" <?= $statusFilter === 'COMPLETED' ? 'selected' : '' ?>>Pesanan Selesai</option>
        <option value="CANCELLED" <?= $statusFilter === 'CANCELLED' ? 'selected' : '' ?>>Dibatalkan</option>
      </select>
    </div>
    <div class="form-group" style="margin:0;">
      <label>Customer / No. Order</label>
      <input class="input" name="customer" value="<?= e($customerQuery) ?>" placeholder="Nama customer / kode...">
    </div>
    <div style="grid-column:span 4;display:flex;gap:10px;justify-content:flex-end;margin-top:8px;flex-wrap:wrap;align-items:center;">
      <button type="submit" class="btn btn-primary">Terapkan Filter</button>
      <a class="btn btn-outline" href="<?= BASE_URL ?>/admin/operational-report.php">Reset</a>
    </div>
  </form>
</div>

<!-- Report Table -->
<div class="card">
  <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:14px;flex-wrap:wrap;gap:10px;">
    <h3 style="color:var(--terracotta-dark);margin:0;">📋 Detail Laporan Produksi & Transaksi</h3>
    <a href="<?= BASE_URL ?>/admin/export-report-pdf.php?<?= http_build_query($_GET) ?>" target="_blank" class="btn btn-primary btn-sm" style="background:#dc3545;border-color:#dc3545;font-weight:bold;display:inline-flex;align-items:center;gap:6px;">
      📄 Export PDF / Cetak Laporan
    </a>
  </div>
  <div class="table-wrap">
    <table class="table">
      <thead>
        <tr>
          <th>Tanggal Order</th>
          <th>No. Order</th>
          <th>Customer</th>
          <th>Produk</th>
          <th>Durasi</th>
          <th>Total Harga</th>
          <th>Terbayar</th>
          <th>Status</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($reportList as $r): ?>
          <tr>
            <td><?= date('d/m/Y H:i', strtotime($r['created_at'])) ?></td>
            <td><strong style="color:var(--terracotta-dark)"><?= e($r['order_code']) ?></strong></td>
            <td><?= e($r['customer_name']) ?><br><small class="muted"><?= e($r['phone']) ?></small></td>
            <td><?= e($r['product_name']) ?></td>
            <td><?= (int)$r['production_duration'] ?> Hari</td>
            <td><strong><?= rupiah($r['total_amount']) ?></strong></td>
            <td style="color:green;font-weight:bold;"><?= rupiah($r['paid_amount']) ?></td>
            <td><span class="badge <?= status_class($r['status']) ?>"><?= status_label($r['status']) ?></span></td>
          </tr>
        <?php endforeach; ?>
        <?php if(empty($reportList)): ?>
          <tr>
            <td colspan="8" style="text-align:center;padding:40px">
              Tidak ada data laporan yang sesuai dengan filter.
            </td>
          </tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<?php include '../includes/admin_footer.php'; ?>
