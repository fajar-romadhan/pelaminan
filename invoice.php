<?php
require_once 'config/database.php';
require_once 'config/helpers.php';
require_login();

$orderId = filter_input(INPUT_GET, 'order_id', FILTER_VALIDATE_INT);
if (!$orderId) {
    set_flash('danger', 'ID pesanan tidak valid.');
    redirect(BASE_URL . '/my-orders.php');
}

// Fetch order details
$stmt = $pdo->prepare('
    SELECT o.*, p.name AS product_name, p.code AS product_code, p.size AS product_size, p.price AS product_price,
           u.name AS user_name, u.email AS user_email
    FROM orders o
    JOIN products p ON p.id = o.product_id
    JOIN users u ON u.id = o.user_id
    WHERE o.id = ? AND (o.user_id = ? OR ? = "admin")
    LIMIT 1
');
$stmt->execute([$orderId, current_user()['id'], current_user()['role']]);
$order = $stmt->fetch();

if (!$order) {
    set_flash('danger', 'Invoice tidak ditemukan atau Anda tidak memiliki akses.');
    redirect(BASE_URL . '/my-orders.php');
}

// Get or auto-generate invoice
$invoice = get_or_create_invoice($pdo, $orderId);

// Business & Bank Info
$bizName = get_setting($pdo, 'business_name', 'Distributor Pelaminan Family');
$bizAddress = get_setting($pdo, 'business_address', 'Jl. Betawi Raya RS. Benteng, Perumahan Kencana Indah Blok C.1 No. 17, Palembang');
$bizWa = get_setting($pdo, 'whatsapp', '6281273400312');
$bizIg = get_setting($pdo, 'instagram', '@pengerajin_pelaminan_modern');
$bankAccNumber = get_setting($pdo, 'bank_account_number', '5741-01-007952-53-6');
$bankAccName = get_setting($pdo, 'bank_account_name', "MIS'ATI");

// Fetch Custom Design if exists
$dStmt = $pdo->prepare("SELECT * FROM editor_designs WHERE user_id = ? AND product_id = ? ORDER BY id DESC LIMIT 1");
$dStmt->execute([$order['user_id'], $order['product_id']]);
$customDesign = $dStmt->fetch();

// Parse Extra Items
$extraItems = [];
if (!empty($order['extra_items_detail'])) {
    $extraItems = json_decode($order['extra_items_detail'], true) ?: [];
} elseif (!empty($customDesign['extra_items_json'])) {
    $extraItems = json_decode($customDesign['extra_items_json'], true) ?: [];
}

$remainingBalance = max(0, (float)$order['total_amount'] - (float)$order['paid_amount']);

$pageTitle = 'Invoice ' . $invoice['invoice_number'];
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
    .invoice-card {
      max-width: 850px; margin: 30px auto; background: #fff; border-radius: 12px;
      padding: 40px; box-shadow: 0 4px 20px rgba(0,0,0,0.08); border: 1px solid #e0e0e0;
    }
    .invoice-header { display: flex; justify-content: space-between; align-items: flex-start; border-bottom: 2px solid var(--terracotta-dark); padding-bottom: 20px; margin-bottom: 24px; }
    .invoice-brand { font-size: 24px; font-weight: bold; color: var(--terracotta-dark); margin: 0; }
    .invoice-details-grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: 20px; margin-bottom: 24px; }
    .invoice-table { width: 100%; border-collapse: collapse; margin-bottom: 24px; }
    .invoice-table th { background: #f8f9fa; border-bottom: 2px solid #dee2e6; text-align: left; padding: 12px; font-size: 13px; text-transform: uppercase; color: #555; }
    .invoice-table td { padding: 12px; border-bottom: 1px solid #eee; font-size: 14px; }
    .invoice-summary { max-width: 380px; margin-left: auto; background: #fdfaf6; padding: 16px; border-radius: 8px; border: 1px solid #f0e6df; }
    .invoice-summary-line { display: flex; justify-content: space-between; margin-bottom: 8px; font-size: 14px; }
    .invoice-summary-total { font-weight: bold; font-size: 18px; color: var(--terracotta-dark); border-top: 2px solid var(--terracotta-dark); padding-top: 8px; margin-top: 8px; }
    .invoice-policy-box { background: #fff3cd; border: 1.5px solid #ffeeba; color: #856404; padding: 14px 18px; border-radius: 8px; margin-top: 24px; font-size: 13.5px; }
    .action-bar { max-width: 850px; margin: 20px auto 0; display: flex; justify-content: space-between; align-items: center; }
    
    @media print {
      body { background: #fff; margin: 0; }
      .action-bar, header, footer, .page-head { display: none !important; }
      .invoice-card { box-shadow: none; border: none; padding: 0; margin: 0; max-width: 100%; }
    }
  </style>
</head>
<body>

<div class="action-bar">
  <?php 
    $backUrl = is_admin() ? BASE_URL . '/admin/orders.php' : BASE_URL . '/my-orders.php';
    $backLabel = is_admin() ? '← Kembali ke Kelola Pesanan Admin' : '← Kembali ke Pesanan Saya';
  ?>
  <a href="<?= $backUrl ?>" class="btn btn-outline"><?= $backLabel ?></a>
  <div>
    <button onclick="window.print()" class="btn btn-primary" style="font-weight:bold;background:var(--terracotta-dark);border-color:var(--terracotta-dark);">
      🖨️ Cetak / Unduh PDF Invoice
    </button>
  </div>
</div>

<main class="invoice-card">
  <div class="invoice-header">
    <div style="display:flex;align-items:center;gap:14px;">
      <img src="<?= BASE_URL ?>/assets/img/logo.png" alt="Logo Distributor Pelaminan Family" style="width:60px;height:60px;border-radius:50%;object-fit:contain;background:#000;box-shadow:0 3px 10px rgba(0,0,0,0.2);flex-shrink:0;">
      <div>
        <h1 class="invoice-brand" style="margin:0;font-size:20px;"><?= e($bizName) ?></h1>
        <p style="margin:2px 0 0;font-size:12.5px;color:#666;max-width:420px;"><?= e($bizAddress) ?></p>
        <p style="margin:2px 0 0;font-size:12.5px;color:#666;">WhatsApp: +<?= e($bizWa) ?> | Instagram: <?= e($bizIg) ?></p>
      </div>
    </div>
    <div style="text-align:right;">
      <h2 style="margin:0;color:var(--espresso);font-size:22px;">INVOICE TAGIHAN</h2>
      <strong style="font-size:16px;color:var(--terracotta-dark);"><?= e($invoice['invoice_number']) ?></strong><br>
      <small class="muted">No. Order: <strong><?= e($order['order_code']) ?></strong></small><br>
      <span class="badge <?= status_class($order['status']) ?>" style="margin-top:6px;"><?= status_label($order['status']) ?></span>
    </div>
  </div>

  <div class="invoice-details-grid">
    <div>
      <h4 style="margin:0 0 8px;color:var(--espresso);">👤 Diterbitkan Kepada:</h4>
      <strong style="font-size:15px;"><?= e($order['customer_name']) ?></strong><br>
      <small class="muted">Telepon/WA: <?= e($order['phone']) ?></small><br>
      <small class="muted">Email: <?= e($order['user_email']) ?></small><br>
      <p style="margin:6px 0 0;font-size:13px;">
        <strong>Alamat:</strong><br>
        <?php if (($order['pickup_method'] ?? '') === 'diambil' || ($order['district'] ?? '') === 'Diambil Sendiri'): ?>
          <?= e($order['address']) ?>
        <?php else: ?>
          <?= e($order['address']) ?><?= !empty($order['district']) ? ', ' . e($order['district']) : '' ?><?= !empty($order['city']) ? ', ' . e($order['city']) : '' ?>
        <?php endif; ?>
      </p>
      <?php if (!empty($order['delivery_note'])): ?>
        <small class="muted">Patokan: <?= e($order['delivery_note']) ?></small>
      <?php endif; ?>
    </div>

    <div style="text-align:right;">
      <h4 style="margin:0 0 8px;color:var(--espresso);">📅 Tanggal Dokumen:</h4>
      <p style="margin:2px 0;font-size:13px;"><strong>Tanggal Invoice:</strong> <?= format_indonesian_date($invoice['issued_date']) ?></p>
      <p style="margin:2px 0;font-size:13px;"><strong>Tanggal Pesanan:</strong> <?= date('d F Y H:i', strtotime($order['created_at'])) ?></p>
      <p style="margin:2px 0;font-size:13px;"><strong>Batas Pembayaran:</strong> <?= format_indonesian_date($invoice['due_date']) ?></p>
      <p style="margin:2px 0;font-size:13px;"><strong>Metode Penerimaan:</strong> <?= $order['pickup_method'] === 'diambil' ? '🏪 Diambil Sendiri di Workshop' : '🚚 Diantarkan ke Alamat' ?></p>
      <?php if (!empty($order['event_date'])): ?>
        <p style="margin:2px 0;font-size:13px;"><strong>Tanggal Acara:</strong> <?= format_indonesian_date($order['event_date']) ?></p>
      <?php endif; ?>
    </div>
  </div>

  <h4 style="margin:0 0 10px;color:var(--espresso);">📦 Rincian Produk & Kustomisasi</h4>
  <table class="invoice-table">
    <thead>
      <tr>
        <th>No.</th>
        <th>Deskripsi Produk & Decor</th>
        <th style="text-align:center;">Qty</th>
        <th style="text-align:right;">Harga Satuan</th>
        <th style="text-align:right;">Total</th>
      </tr>
    </thead>
    <tbody>
      <tr>
        <td>1</td>
        <td>
          <strong><?= e($order['product_name']) ?></strong> (<?= e($order['product_code']) ?>)
          <?php if (!empty($order['variant_name'])): ?>
            <br><small class="muted">Warna Variasi: <?= e($order['variant_name']) ?></small>
          <?php endif; ?>
          <br><small class="muted">Ukuran Paket: <?= e($order['product_size']) ?></small>
        </td>
        <td style="text-align:center;">1</td>
        <td style="text-align:right;"><?= rupiah($order['product_price']) ?></td>
        <td style="text-align:right;"><?= rupiah($order['product_price']) ?></td>
      </tr>

      <?php 
      $no = 2;
      foreach ($extraItems as $ex):
          if (!empty($ex['is_shipping_meta'])) continue;
          $exQty = (int)($ex['quantity'] ?? 1);
          $exPrice = (float)($ex['price'] ?? 0);
          $exSubtotal = (float)($ex['subtotal'] ?? ($exPrice * $exQty));
      ?>
        <tr>
          <td><?= $no++ ?></td>
          <td>
            <strong>➕ <?= e($ex['name'] ?? 'Item Tambahan') ?></strong>
            <br><small class="muted">Kategori: <?= e($ex['category'] ?? 'Kustomisasi') ?></small>
          </td>
          <td style="text-align:center;"><?= $exQty ?></td>
          <td style="text-align:right;"><?= rupiah($exPrice) ?></td>
          <td style="text-align:right;"><?= rupiah($exSubtotal) ?></td>
        </tr>
      <?php endforeach; ?>

      <?php if ((float)$order['shipping_cost'] > 0): ?>
        <tr>
          <td><?= $no++ ?></td>
          <td>
            <strong>🚚 Biaya Pengantaran (Ongkir)</strong>
            <br><small class="muted">Tujuan: <?= e($order['district']) ?>, <?= e($order['city']) ?></small>
          </td>
          <td style="text-align:center;">1</td>
          <td style="text-align:right;"><?= rupiah($order['shipping_cost']) ?></td>
          <td style="text-align:right;"><?= rupiah($order['shipping_cost']) ?></td>
        </tr>
      <?php endif; ?>
    </tbody>
  </table>

  <div style="display:flex;justify-content:space-between;align-items:flex-start;gap:20px;">
    <div style="flex:1;">
      <h4 style="margin:0 0 6px;color:var(--espresso);">💳 Informasi Pembayaran Transfer:</h4>
      <div style="background:#fff9f0;padding:12px;border-radius:8px;border:1.5px solid var(--terracotta);font-size:13px;display:flex;align-items:center;gap:12px;">
        <img src="<?= BASE_URL ?>/assets/img/bri_logo.png" alt="Logo Bank BRI" style="height:28px;width:auto;border-radius:4px;">
        <div>
          <p style="margin:0 0 2px;"><strong>Bank Transfer BRI:</strong></p>
          <p style="margin:0;font-size:16px;color:var(--terracotta-dark);font-weight:bold;letter-spacing:0.5px;"><?= e($bankAccNumber) ?></p>
          <p style="margin:2px 0 0;font-size:12px;color:#666;font-weight:600;">a.n. <?= e($bankAccName) ?></p>
        </div>
      </div>
    </div>

    <div class="invoice-summary">
      <div class="invoice-summary-line">
        <span>Subtotal Produk & Decor:</span>
        <strong><?= rupiah((float)$order['total_amount'] - (float)$order['shipping_cost']) ?></strong>
      </div>
      <div class="invoice-summary-line">
        <span>Ongkos Kirim:</span>
        <strong><?= rupiah($order['shipping_cost']) ?></strong>
      </div>
      <div class="invoice-summary-line invoice-summary-total">
        <span>Total Tagihan:</span>
        <span><?= rupiah($order['total_amount']) ?></span>
      </div>
      <div style="margin-top:10px;border-top:1px dashed #ccc;padding-top:8px;">
        <div class="invoice-summary-line" style="font-size:13px;">
          <span>Minimal DP (50%):</span>
          <strong><?= rupiah($order['dp_amount']) ?></strong>
        </div>
        <div class="invoice-summary-line" style="font-size:13px;color:green;">
          <span>Sudah Dibayar:</span>
          <strong><?= rupiah($order['paid_amount']) ?></strong>
        </div>
        <div class="invoice-summary-line" style="font-size:13px;color:var(--terracotta-dark);font-weight:bold;">
          <span>Sisa Tagihan:</span>
          <strong><?= rupiah($remainingBalance) ?></strong>
        </div>
      </div>
    </div>
  </div>

  <div class="invoice-policy-box">
    <strong>⚠️ Catatan Pesanan Resmi:</strong><br>
    Produk yang sudah dibayar DP maupun FULL <u>tidak dapat dibatalkan</u>. Pastikan data pemesanan Anda telah sesuai.
  </div>

  <div style="margin-top:40px;display:flex;justify-content:space-between;text-align:center;font-size:13px;">
    <div>
      <p style="margin-bottom:60px;">Pelanggan,</p>
      <strong>( <?= e($order['customer_name']) ?> )</strong>
    </div>
    <div>
      <p style="margin-bottom:60px;"><?= e($bizName) ?>,</p>
      <strong>( Zainal Abidin Fikri )</strong>
    </div>
  </div>
</main>

</body>
</html>
