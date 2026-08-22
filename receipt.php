<?php
require_once 'config/database.php';
require_once 'config/helpers.php';
require_login();

$paymentId = filter_input(INPUT_GET, 'payment_id', FILTER_VALIDATE_INT);
$orderId = filter_input(INPUT_GET, 'order_id', FILTER_VALIDATE_INT);

if (!$paymentId && $orderId) {
    // Get latest successful payment for order
    $pStmt = $pdo->prepare("SELECT id FROM payments WHERE order_id = ? AND status = 'berhasil' ORDER BY id DESC LIMIT 1");
    $pStmt->execute([$orderId]);
    $paymentId = (int)$pStmt->fetchColumn();
}

if (!$paymentId) {
    set_flash('danger', 'ID pembayaran tidak valid atau belum diverifikasi.');
    redirect(BASE_URL . '/my-orders.php');
}

// Fetch payment details
$stmt = $pdo->prepare('
    SELECT p.*, o.order_code, o.customer_name, o.phone, o.user_id, o.total_amount, o.paid_amount, o.status AS order_status,
           pr.name AS product_name
    FROM payments p
    JOIN orders o ON o.id = p.order_id
    JOIN products pr ON pr.id = o.product_id
    WHERE p.id = ? AND (o.user_id = ? OR ? = "admin")
    LIMIT 1
');
$stmt->execute([$paymentId, current_user()['id'], current_user()['role']]);
$payment = $stmt->fetch();

if (!$payment) {
    set_flash('danger', 'Kwitansi pembayaran tidak ditemukan.');
    redirect(BASE_URL . '/my-orders.php');
}

// Get or auto-generate receipt
$receipt = get_or_create_receipt($pdo, $paymentId);
$invoice = get_or_create_invoice($pdo, (int)$payment['order_id']);

// Business & Bank Info
$bizName = get_setting($pdo, 'business_name', 'Distributor Pelaminan Family');
$bizAddress = get_setting($pdo, 'business_address', 'Jl. Betawi Raya RS. Benteng, Perumahan Kencana Indah Blok C.1 No. 17, Palembang');
$bizWa = get_setting($pdo, 'whatsapp', '6281273400312');
$bankAccNumber = get_setting($pdo, 'bank_account_number', '5741-01-007952-53-6');
$bankAccName = get_setting($pdo, 'bank_account_name', "MIS'ATI");

$pageTitle = 'Kwitansi Pembayaran ' . $receipt['receipt_number'];
?>
<!doctype html>
<html lang="id">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= e($pageTitle) ?></title>
  <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css">
  <style>
    body { background: #f4f6f9; color: #333; font-family: system-ui, -apple-system, sans-serif; }
    .receipt-card {
      max-width: 750px; margin: 30px auto; background: #fff; border-radius: 12px;
      padding: 40px; box-shadow: 0 4px 20px rgba(0,0,0,0.08); border: 2px solid #28a745;
      position: relative; overflow: hidden;
    }
    .receipt-watermark {
      position: absolute; right: -20px; bottom: 20px; font-size: 90px; font-weight: bold;
      color: rgba(40,167,69,0.06); text-transform: uppercase; transform: rotate(-15deg);
      pointer-events: none; user-select: none;
    }
    .receipt-header { display: flex; justify-content: space-between; align-items: flex-start; border-bottom: 2px solid #28a745; padding-bottom: 16px; margin-bottom: 24px; }
    .receipt-row { display: flex; border-bottom: 1px dashed #e0e0e0; padding: 12px 0; font-size: 14.5px; }
    .receipt-label { width: 180px; color: var(--espresso); font-weight: bold; flex-shrink: 0; }
    .receipt-value { flex-grow: 1; color: #222; }
    .receipt-amount-box { background: #eef7ee; border: 2px solid #28a745; padding: 16px; border-radius: 8px; text-align: center; margin: 24px 0; }
    .action-bar { max-width: 750px; margin: 20px auto 0; display: flex; justify-content: space-between; align-items: center; }
    
    @media print {
      body { background: #fff; margin: 0; }
      .action-bar, header, footer, .page-head { display: none !important; }
      .receipt-card { box-shadow: none; border: 1px solid #28a745; padding: 20px; margin: 0; max-width: 100%; }
    }
  </style>
</head>
<body>

<div class="action-bar">
  <div style="display:flex;gap:8px;">
    <?php if (is_admin()): ?>
      <a href="<?= BASE_URL ?>/admin/orders.php" class="btn btn-outline">← Kelola Pesanan Admin</a>
    <?php else: ?>
      <a href="<?= BASE_URL ?>/my-orders.php" class="btn btn-outline">← Pesanan Saya</a>
    <?php endif; ?>
    <a href="<?= BASE_URL ?>/invoice.php?order_id=<?= (int)$payment['order_id'] ?>" class="btn btn-outline">📄 Lihat Invoice</a>
  </div>
  <div>
    <button onclick="window.print()" class="btn btn-primary" style="font-weight:bold;background:#28a745;border-color:#28a745;">
      🖨️ Cetak Kwitansi
    </button>
  </div>
</div>

<main class="receipt-card">
  <div class="receipt-watermark">TERVERIFIKASI</div>

  <div class="receipt-header">
    <div style="display:flex;align-items:center;gap:14px;">
      <img src="<?= BASE_URL ?>/assets/img/logo.png" alt="Logo Distributor Pelaminan Family" style="width:54px;height:54px;border-radius:50%;object-fit:contain;background:#000;box-shadow:0 3px 8px rgba(0,0,0,0.2);flex-shrink:0;">
      <div>
        <h1 style="font-size:20px;color:var(--terracotta-dark);margin:0;"><?= e($bizName) ?></h1>
        <p style="margin:2px 0 0;font-size:12px;color:#666;max-width:380px;"><?= e($bizAddress) ?></p>
        <p style="margin:2px 0 0;font-size:12px;color:#666;">WhatsApp: +<?= e($bizWa) ?></p>
      </div>
    </div>
    <div style="text-align:right;">
      <h2 style="margin:0;color:#28a745;font-size:20px;">KWITANSI PEMBAYARAN</h2>
      <strong style="font-size:15px;color:var(--espresso);"><?= e($receipt['receipt_number']) ?></strong><br>
      <small class="muted">Ref Invoice: <?= e($invoice['invoice_number']) ?></small><br>
      <span class="badge badge-success" style="margin-top:6px;font-size:12px;">✓ TERVERIFIKASI / SAH</span>
    </div>
  </div>

  <div class="receipt-row">
    <div class="receipt-label">Telah Diterima Dari:</div>
    <div class="receipt-value"><strong><?= e($payment['customer_name']) ?></strong> (<?= e($payment['phone']) ?>)</div>
  </div>

  <div class="receipt-row">
    <div class="receipt-label">Untuk Pembayaran:</div>
    <div class="receipt-value">
      <strong><?= e($receipt['payment_type']) ?></strong> pesanan pelaminan <strong><?= e($payment['product_name']) ?></strong> (No. Order: <?= e($payment['order_code']) ?>)
    </div>
  </div>

  <div class="receipt-row">
    <div class="receipt-label">Tanggal Transaksi:</div>
    <div class="receipt-value"><?= date('d F Y H:i', strtotime($receipt['issued_date'])) ?> WIB</div>
  </div>

  <div class="receipt-row">
    <div class="receipt-label">Metode Pembayaran:</div>
    <div class="receipt-value"><?= e($payment['method'] ?? 'Transfer Bank BRI') ?> (No. Rek: <?= e($bankAccNumber) ?> a.n. <?= e($bankAccName) ?>)</div>
  </div>

  <div class="receipt-amount-box">
    <small class="muted" style="text-transform:uppercase;font-weight:bold;letter-spacing:0.5px;">JUMLAH TERBAYAR SAH</small>
    <div style="font-size:32px;font-weight:bold;color:#28a745;margin-top:4px;"><?= rupiah($receipt['amount']) ?></div>
  </div>

  <div style="display:flex;justify-content:space-between;font-size:13px;color:#555;margin-top:16px;background:#f9f9f9;padding:12px;border-radius:6px;">
    <span>Total Tagihan Order: <strong><?= rupiah($payment['total_amount']) ?></strong></span>
    <span>Total Kumulatif Terbayar: <strong style="color:green;"><?= rupiah($payment['paid_amount']) ?></strong></span>
    <span>Sisa Tagihan: <strong style="color:var(--terracotta-dark);"><?= rupiah(max(0, (float)$payment['total_amount'] - (float)$payment['paid_amount'])) ?></strong></span>
  </div>

  <div style="margin-top:40px;display:flex;justify-content:space-between;text-align:center;font-size:13px;">
    <div>
      <p style="margin-bottom:60px;">Penyetor,</p>
      <strong>( <?= e($payment['customer_name']) ?> )</strong>
    </div>
    <div>
      <p style="margin-bottom:60px;"><?= e($bizName) ?>,</p>
      <strong>( Zainal Abidin Fikri )</strong>
    </div>
  </div>
</main>

</body>
</html>
