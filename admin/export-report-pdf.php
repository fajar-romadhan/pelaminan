<?php
require_once '../config/database.php';
require_once '../config/helpers.php';
require_admin();

$pageTitle = 'Cetak Laporan Operasional & Pendapatan';

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

$bizName = get_setting($pdo, 'business_name', 'Distributor Pelaminan Family');
$bizAddress = get_setting($pdo, 'business_address', 'Jl. Betawi Raya RS. Benteng, Perumahan Kencana Indah Blok C.1 No. 17, Palembang');
$bizPhone = get_setting($pdo, 'business_phone', '0812-7340-0312');
?>
<!doctype html>
<html lang="id">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Laporan_Operasional_<?= date('Ymd_His') ?></title>
  <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css">
  <style>
    body {
      background: #f4f6f9;
      color: #2c2523;
      font-family: 'Plus Jakarta Sans', -apple-system, sans-serif;
      padding: 20px;
      margin: 0;
    }
    .print-container {
      max-width: 1100px;
      margin: 0 auto;
      background: #ffffff;
      padding: 30px;
      border-radius: 12px;
      box-shadow: 0 4px 20px rgba(0,0,0,0.08);
    }
    .print-action-bar {
      max-width: 1100px;
      margin: 0 auto 20px;
      display: flex;
      justify-content: space-between;
      align-items: center;
    }
    .header-kop {
      display: flex;
      justify-content: space-between;
      align-items: center;
      border-bottom: 3px double #b86733;
      padding-bottom: 16px;
      margin-bottom: 20px;
    }
    .header-kop h1 {
      margin: 0 0 4px;
      font-size: 22px;
      color: #b86733;
    }
    .header-kop p {
      margin: 2px 0;
      font-size: 13px;
      color: #555;
    }
    .report-meta {
      background: #fdfaf6;
      border: 1px solid #f0e6df;
      padding: 12px 16px;
      border-radius: 8px;
      margin-bottom: 20px;
      font-size: 13px;
      display: flex;
      justify-content: space-between;
      flex-wrap: wrap;
      gap: 10px;
    }
    .summary-grid {
      display: grid;
      grid-template-columns: repeat(4, 1fr);
      gap: 12px;
      margin-bottom: 20px;
    }
    .summary-box {
      border: 1px solid #e0e0e0;
      border-radius: 8px;
      padding: 12px;
      background: #fafafa;
    }
    .summary-box small {
      color: #666;
      font-size: 11px;
      display: block;
      margin-bottom: 4px;
      text-transform: uppercase;
      font-weight: 600;
    }
    .summary-box strong {
      font-size: 16px;
      color: #2c2523;
    }
    .report-table {
      width: 100%;
      border-collapse: collapse;
      font-size: 12px;
      margin-bottom: 30px;
    }
    .report-table th {
      background: #362217;
      color: #ffffff;
      padding: 10px 12px;
      text-align: left;
      font-weight: 600;
      border: 1px solid #362217;
    }
    .report-table td {
      padding: 9px 12px;
      border: 1px solid #e0e0e0;
    }
    .report-table tr:nth-child(even) {
      background: #fdfdfd;
    }
    .signature-section {
      display: flex;
      justify-content: space-between;
      margin-top: 40px;
      page-break-inside: avoid;
    }
    .signature-box {
      text-align: center;
      width: 220px;
      font-size: 13px;
    }
    .signature-space {
      height: 70px;
    }

    @page {
      size: A4 landscape;
      margin: 10mm;
    }

    @media print {
      body {
        background: #ffffff !important;
        padding: 0 !important;
      }
      .print-action-bar {
        display: none !important;
      }
      .print-container {
        box-shadow: none !important;
        padding: 0 !important;
        max-width: 100% !important;
      }
      .report-table th {
        background: #362217 !important;
        color: #ffffff !important;
        -webkit-print-color-adjust: exact;
        print-color-adjust: exact;
      }
      .summary-box {
        background: #fcfcfc !important;
        -webkit-print-color-adjust: exact;
        print-color-adjust: exact;
      }
      tr {
        page-break-inside: avoid;
      }
    }
  </style>
</head>
<body>

<div class="print-action-bar">
  <a href="<?= BASE_URL ?>/admin/operational-report.php" class="btn btn-outline">← Kembali ke Laporan Operasional</a>
  <div style="display:flex;gap:10px;">
    <button onclick="window.print()" class="btn btn-primary" style="background:#dc3545;border-color:#dc3545;font-weight:bold;display:inline-flex;align-items:center;gap:6px;">
      📄 Cetak / Unduh PDF Laporan
    </button>
  </div>
</div>

<div class="print-container">
  <!-- Kop Laporan -->
  <div class="header-kop" style="display:flex;align-items:center;justify-content:space-between;">
    <div style="display:flex;align-items:center;gap:14px;">
      <img src="<?= BASE_URL ?>/assets/img/logo.png" alt="Logo Distributor Pelaminan Family" style="width:64px;height:64px;border-radius:50%;object-fit:contain;background:#000;box-shadow:0 3px 8px rgba(0,0,0,0.2);flex-shrink:0;">
      <div>
        <h1 style="margin:0;font-size:20px;color:#362217;"><?= e($bizName) ?></h1>
        <p style="margin:2px 0 0;"><?= e($bizAddress) ?></p>
        <p style="margin:2px 0 0;">Telepon/WA: <?= e($bizPhone) ?></p>
      </div>
    </div>
    <div style="text-align:right;">
      <h2 style="margin:0;font-size:18px;color:#362217;">LAPORAN OPERASIONAL & PENDAPATAN</h2>
      <p style="margin:4px 0 0;font-size:12px;color:#666;">Dicetak pada: <?= date('d/m/Y H:i') ?> WIB</p>
      <?php
        $currUser = current_user();
        $creatorName = trim(str_replace('(Pemilik)', '', $currUser['name'] ?? 'Admin'));
      ?>
      <p style="margin:2px 0 0;font-size:12px;color:#666;">
        Oleh: Administrator (<?= e($creatorName) ?>)
      </p>
    </div>
  </div>

  <!-- Meta Filter Info -->
  <div class="report-meta">
    <div>
      <strong>Periode Filter:</strong> 
      <?= !empty($startDate) ? format_indonesian_date($startDate) : 'Awal' ?> s/d <?= !empty($endDate) ? format_indonesian_date($endDate) : 'Hari Ini' ?>
    </div>
    <div>
      <strong>Status:</strong> <?= !empty($statusFilter) ? e(status_label($statusFilter)) : 'Semua Status' ?>
    </div>
    <div>
      <strong>Filter Customer:</strong> <?= !empty($customerQuery) ? e($customerQuery) : 'Semua' ?>
    </div>
    <div>
      <strong>Total Baris Data:</strong> <?= count($reportList) ?> Pesanan
    </div>
  </div>

  <!-- Summary Cards Grid -->
  <div class="summary-grid">
    <div class="summary-box">
      <small>Total Pesanan System</small>
      <strong><?= $totalOrders ?> Orders</strong>
    </div>
    <div class="summary-box">
      <small>Pesanan Selesai</small>
      <strong style="color:#28a745;"><?= $completedOrders ?> Completed</strong>
    </div>
    <div class="summary-box">
      <small>Sedang Produksi</small>
      <strong style="color:#007bff;"><?= $producingOrders ?> Production</strong>
    </div>
    <div class="summary-box">
      <small>Dalam Antrean</small>
      <strong style="color:#ffc107;"><?= $waitingQueueOrders ?> Queue</strong>
    </div>

    <div class="summary-box">
      <small>Nilai Transaksi</small>
      <strong><?= rupiah($totalTransaction) ?></strong>
    </div>
    <div class="summary-box">
      <small>DP Masuk (50%)</small>
      <strong style="color:#17a2b8;"><?= rupiah($dpReceived) ?></strong>
    </div>
    <div class="summary-box">
      <small>Pelunasan Masuk</small>
      <strong style="color:#6f42c1;"><?= rupiah($finalPaid) ?></strong>
    </div>
    <div class="summary-box">
      <small>Total Terbayar</small>
      <strong style="color:#28a745;"><?= rupiah($totalRevenue) ?></strong>
    </div>
  </div>

  <!-- Table Details -->
  <table class="report-table">
    <thead>
      <tr>
        <th style="width:40px;text-align:center;">No</th>
        <th>Tgl Order</th>
        <th>No. Order</th>
        <th>Nama Customer</th>
        <th>Produk</th>
        <th style="text-align:center;">Durasi</th>
        <th style="text-align:right;">Total Harga</th>
        <th style="text-align:right;">Terbayar</th>
        <th style="text-align:center;">Status</th>
      </tr>
    </thead>
    <tbody>
      <?php 
      $no = 1;
      $grandTotal = 0;
      $grandPaid = 0;
      foreach ($reportList as $r): 
        $grandTotal += (float)$r['total_amount'];
        $grandPaid += (float)$r['paid_amount'];
      ?>
        <tr>
          <td style="text-align:center;"><?= $no++ ?></td>
          <td><?= date('d/m/Y H:i', strtotime($r['created_at'])) ?></td>
          <td><strong style="color:#b86733;"><?= e($r['order_code']) ?></strong></td>
          <td>
            <strong><?= e($r['customer_name']) ?></strong>
            <br><small style="color:#666;"><?= e($r['phone']) ?></small>
          </td>
          <td><?= e($r['product_name']) ?></td>
          <td style="text-align:center;"><?= (int)$r['production_duration'] ?> Hari</td>
          <td style="text-align:right;"><?= rupiah($r['total_amount']) ?></td>
          <td style="text-align:right;color:#28a745;font-weight:bold;"><?= rupiah($r['paid_amount']) ?></td>
          <td style="text-align:center;">
            <span class="badge <?= status_class($r['status']) ?>" style="font-size:11px;"><?= status_label($r['status']) ?></span>
          </td>
        </tr>
      <?php endforeach; ?>
      <?php if (empty($reportList)): ?>
        <tr>
          <td colspan="9" style="text-align:center;padding:30px;color:#777;">
            Tidak ada data transaksi / laporan yang sesuai dengan kriteria filter.
          </td>
        </tr>
      <?php else: ?>
        <tr style="background:#f8f9fa;font-weight:bold;">
          <td colspan="6" style="text-align:right;">TOTAL DALAM TABEL LAPORAN:</td>
          <td style="text-align:right;"><?= rupiah($grandTotal) ?></td>
          <td style="text-align:right;color:#28a745;"><?= rupiah($grandPaid) ?></td>
          <td></td>
        </tr>
      <?php endif; ?>
    </tbody>
  </table>

  <!-- Signature Footer -->
  <div class="signature-section">
    <div class="signature-box">
      <p>Dibuat Oleh,</p>
      <div class="signature-space"></div>
      <p><strong>( <?= e($creatorName) ?> )</strong><br><small style="color:#666;">Admin Operasional</small></p>
    </div>
    <div class="signature-box">
      <p>Palembang, <?= format_indonesian_date(date('Y-m-d')) ?></p>
      <p>Mengetahui / Menyetujui,</p>
      <div class="signature-space"></div>
      <p><strong>( Zainal Abidin Fikri )</strong><br><small style="color:#666;">Pemilik - <?= e($bizName) ?></small></p>
    </div>
  </div>
</div>

</body>
</html>
