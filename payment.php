<?php
require_once 'config/database.php';
require_once 'config/helpers.php';
require_login();

$orderId = filter_input(INPUT_GET, 'order_id', FILTER_VALIDATE_INT);
$type = $_GET['type'] ?? 'dp';

if (!$orderId) {
    set_flash('danger', 'ID pesanan tidak valid.');
    redirect(BASE_URL . '/my-orders.php');
}

$stmt = $pdo->prepare('
    SELECT o.*, p.name AS product_name, p.code, c.name AS category_name 
    FROM orders o 
    JOIN products p ON p.id=o.product_id 
    JOIN categories c ON c.id=p.category_id 
    WHERE o.id=? AND o.user_id=? 
    LIMIT 1
');
$stmt->execute([$orderId, current_user()['id']]);
$order = $stmt->fetch();

if (!$order) {
    set_flash('danger', 'Pesanan tidak ditemukan.');
    redirect(BASE_URL . '/my-orders.php');
}

if (in_array($order['status'], ['CANCELLED', 'REJECTED'], true)) {
    set_flash('danger', 'Pesanan ini sudah dibatalkan atau ditolak.');
    redirect(BASE_URL . '/my-orders.php');
}

$remaining = max(0, (float)$order['total_amount'] - (float)$order['paid_amount']);

// Calculate official server-side amount based on type
if ($type === 'final') {
    $amount = $remaining;
} else {
    $type = 'dp';
    $amount = (float)$order['dp_amount'];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf_token($_POST['csrf_token'] ?? null);
    
    $postType = $_POST['type'] ?? 'dp';

    // File Upload Validation for Payment Proof
    if (!isset($_FILES['payment_proof']) || $_FILES['payment_proof']['error'] !== UPLOAD_ERR_OK) {
        set_flash('danger', 'Silakan unggah foto / file bukti transfer pembayaran Anda.');
        redirect(BASE_URL . '/payment.php?order_id=' . $orderId . '&type=' . $postType);
    }

    $file = $_FILES['payment_proof'];
    $allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/webp', 'image/pjpeg', 'image/x-png'];
    $fileMime = function_exists('mime_content_type') ? @mime_content_type($file['tmp_name']) : '';
    $fileExt = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

    if (!in_array($fileExt, ['jpg', 'jpeg', 'png', 'webp'], true)) {
        set_flash('danger', 'Format bukti transfer harus berupa gambar JPG, JPEG, PNG, atau WEBP.');
        redirect(BASE_URL . '/payment.php?order_id=' . $orderId . '&type=' . $postType);
    }

    if ($file['size'] > 10 * 1024 * 1024) {
        set_flash('danger', 'Ukuran file bukti transfer maksimal 10MB.');
        redirect(BASE_URL . '/payment.php?order_id=' . $orderId . '&type=' . $postType);
    }

    // Target upload directory
    $baseDir = defined('BASE_PATH') ? BASE_PATH : __DIR__;
    $uploadDir = $baseDir . '/uploads/payments/';
    if (!is_dir($uploadDir)) {
        @mkdir($uploadDir, 0777, true);
        @chmod($uploadDir, 0777);
    }

    $cleanCode = preg_replace('/[^A-Za-z0-9_\-]/', '_', $order['order_code']);
    $fileName = 'proof_' . $cleanCode . '_' . time() . '_' . rand(100, 999) . '.' . $fileExt;
    $targetPath = $uploadDir . $fileName;
    $dbPath = 'uploads/payments/' . $fileName;

    $uploaded = @move_uploaded_file($file['tmp_name'], $targetPath);
    if (!$uploaded) {
        $uploaded = @copy($file['tmp_name'], $targetPath);
    }
    if (!$uploaded && is_readable($file['tmp_name'])) {
        $fileData = @file_get_contents($file['tmp_name']);
        if ($fileData !== false && $fileData !== '') {
            $uploaded = (@file_put_contents($targetPath, $fileData) !== false);
        }
    }

    if (!$uploaded) {
        set_flash('danger', 'Gagal menyimpan file bukti transfer ke server. Pastikan folder uploads/payments/ memiliki izin tulis (chmod 777).');
        redirect(BASE_URL . '/payment.php?order_id=' . $orderId . '&type=' . $postType);
    }

    // SERVER-SIDE VALIDATION & RE-CALCULATION OF AMOUNT
    if ($postType === 'dp') {
        if ((float)$order['paid_amount'] > 0 || in_array($order['status'], ['PAYMENT_RECEIVED', 'ADMIN_REVIEW', 'WAITING_QUEUE', 'PRODUCTION', 'READY_INSTALLATION', 'INSTALLATION', 'COMPLETED'], true)) {
            set_flash('danger', 'Pembayaran DP untuk pesanan ini sudah dilakukan.');
            redirect(BASE_URL . '/my-orders.php');
        }
        $payAmount = (float)$order['dp_amount'];
    } elseif ($postType === 'final') {
        if ($order['status'] === 'WAITING_PAYMENT') {
            set_flash('danger', 'Pembayaran DP harus dilakukan terlebih dahulu sebelum pelunasan.');
            redirect(BASE_URL . '/payment.php?order_id=' . $orderId . '&type=dp');
        }
        if ($remaining <= 0 || $order['status'] === 'COMPLETED') {
            set_flash('danger', 'Pesanan ini sudah lunas.');
            redirect(BASE_URL . '/my-orders.php');
        }
        $payAmount = $remaining;
    } else {
        set_flash('danger', 'Jenis pembayaran tidak valid.');
        redirect(BASE_URL . '/my-orders.php');
    }

    if ($payAmount <= 0) {
        set_flash('danger', 'Nominal pembayaran tidak valid.');
        redirect(BASE_URL . '/my-orders.php');
    }

    $pdo->beginTransaction();
    try {
        $payStmt = $pdo->prepare('INSERT INTO payments(order_id, type, method, amount, status, proof_image, paid_at) VALUES(?,?,?,?,?,?,NOW())');
        $payStmt->execute([
            $orderId,
            $postType,
            'Transfer Bank BRI',
            $payAmount,
            'pending',
            $dbPath
        ]);

        $oldStatus = $order['status'];
        $newStatus = 'ADMIN_REVIEW';

        $updStmt = $pdo->prepare('UPDATE orders SET status=? WHERE id=?');
        $updStmt->execute([$newStatus, $orderId]);

        log_order_status_change($pdo, (int)$orderId, $oldStatus, $newStatus, current_user()['id']);

        // Send notification to customer
        send_system_notification(
            $pdo,
            (int)$order['user_id'],
            (int)$orderId,
            'Bukti Pembayaran Diunggah',
            'Bukti transfer pembayaran berhasil diunggah. Pesanan Anda kini sedang diverifikasi oleh Admin.'
        );

        // Send notification to admin
        $payLabel = strtoupper($postType) === 'DP' ? 'DP (50%)' : 'Pelunasan';
        send_admin_notification(
            $pdo,
            (int)$orderId,
            '💳 Bukti Transfer ' . $payLabel . ' Diunggah',
            'Customer ' . $order['customer_name'] . ' mengunggah bukti transfer ' . $payLabel . ' sebesar ' . rupiah($payAmount) . ' untuk order ' . $order['order_code'] . '. Silakan verifikasi.'
        );

        $pdo->commit();

        set_flash('success', 'Bukti transfer sebesar ' . rupiah($payAmount) . ' berhasil diunggah! Pembayaran Anda kini dalam proses verifikasi dari Admin operasional.');
        redirect(BASE_URL . '/my-orders.php');
    } catch (Throwable $e) {
        $pdo->rollBack();
        set_flash('danger', 'Gagal memproses pembayaran: ' . $e->getMessage());
        redirect(BASE_URL . '/payment.php?order_id=' . $orderId . '&type=' . $postType);
    }
}

$pageTitle = $type === 'final' ? 'Pembayaran Pelunasan' : 'Pembayaran DP';
$active = 'orders';
include 'includes/header.php';
$bankAccNumber = '5741-01-007952-53-6';
$bankAccName = "MIS'ATI";
?>
<div class="page-head"><div class="container"><h1><?= e($pageTitle) ?></h1><p><?= e($order['order_code']) ?> · <?= e($order['product_name']) ?></p></div></div>
<main class="container" style="padding-top:30px;max-width:860px">
  <div class="grid grid-2">
    <div class="card">
      <h3 style="color:var(--terracotta-dark);margin-top:0;">Ringkasan Pesanan</h3>
      <div style="display:flex;gap:14px">
        <div class="image-placeholder" style="width:80px;min-height:80px;flex-shrink:0">🏛️</div>
        <div>
          <strong style="font-size:15px;color:var(--espresso);"><?= e($order['product_name']) ?></strong>
          <p class="muted" style="font-size:12px;margin:2px 0 0;"><?= e($order['category_name']) ?> · <?= e($order['code']) ?></p>
        </div>
      </div>
      <div class="summary-line" style="margin-top:14px;"><span>Total Harga</span><strong><?= rupiah($order['total_amount']) ?></strong></div>
      <div class="summary-line"><span>Sudah Dibayar</span><strong><?= rupiah($order['paid_amount']) ?></strong></div>
      <div class="summary-line summary-total">
        <span><?= $type==='final' ? 'Sisa Tagihan' : 'Nominal DP 50%' ?></span>
        <span id="nominalText"><?= rupiah($amount) ?></span>
      </div>

      <div style="margin-top:16px;padding:12px;background:#fbf8f3;border-radius:12px;border:1px solid rgba(216,133,78,0.2);font-size:12px;color:#666;">
        💡 <strong>Petunjuk Pembayaran:</strong>
        <ol style="margin:6px 0 0;padding-left:18px;line-height:1.5;">
          <li>Lakukan transfer sesuai nominal di atas ke rekening BRI.</li>
          <li>Simpan / screenshot foto bukti transfer.</li>
          <li>Unggah file bukti transfer pada form di sebelah kanan.</li>
        </ol>
      </div>
    </div>
    
    <div class="card">
      <h3 style="color:var(--terracotta-dark);margin-top:0;">Metode Pembayaran</h3>
      <div class="card" style="background:#fff9f0;border:1.5px solid var(--terracotta);box-shadow:none;padding:16px;">
        <div style="display:flex;align-items:center;gap:12px;margin-bottom:8px;">
          <img src="<?= BASE_URL ?>/assets/img/bri_logo.png" alt="Logo Bank BRI" style="height:32px;width:auto;border-radius:6px;box-shadow:0 2px 6px rgba(0,0,0,0.12);">
          <div>
            <strong style="font-size:15px;color:var(--espresso);">Transfer Bank BRI</strong>
            <p style="font-size:11.5px;color:var(--muted);margin:1px 0 0;">ATM, m-Banking (BRImo), atau i-Banking</p>
          </div>
        </div>
        <div style="background:#fff;border:1.5px dashed var(--terracotta);border-radius:12px;padding:14px;margin-top:10px;">
          <div style="font-size:12px;color:var(--espresso);font-weight:600;">Nomor Rekening Bank BRI:</div>
          <div style="font-size:18px;font-weight:800;color:var(--terracotta-dark);letter-spacing:1px;margin:4px 0 2px;">
            <?= e($bankAccNumber) ?>
          </div>
          <div style="font-size:12px;color:#666;font-weight:600;">a.n. <?= e($bankAccName) ?></div>
          
          <button type="button" onclick="copyToClipboard('<?= e($bankAccNumber) ?>', 'Nomor Rekening BRI berhasil disalin!')" class="btn btn-outline btn-sm" style="margin-top:10px;width:100%;font-size:12px;padding:6px 10px;border-radius:8px;">
            📋 Salin No. Rekening
          </button>
        </div>
      </div>
      
      <form method="post" enctype="multipart/form-data" style="margin-top:16px">
        <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
        <input type="hidden" name="type" value="<?= e($type) ?>">

        <div class="form-group">
          <label style="font-weight:700;font-size:13px;color:var(--espresso);">Unggah Bukti Transfer Struk <span style="color:red">*</span></label>
          <input type="file" name="payment_proof" id="payment_proof" accept="image/jpeg,image/png,image/jpg" required class="input" onchange="previewProofImage(this)" style="padding:8px;border-radius:10px;">
          <small class="muted" style="font-size:11.5px;display:block;margin-top:4px;">Format gambar: JPG, JPEG, PNG (Maksimal 5MB)</small>
        </div>

        <div id="proofPreviewBox" style="display:none;margin-bottom:14px;text-align:center;padding:10px;background:#f8f9fa;border-radius:10px;border:1px solid #ddd;">
          <small style="display:block;font-weight:700;margin-bottom:6px;color:#555;">Preview Bukti Transfer:</small>
          <img id="proofPreviewImg" src="" alt="Preview Bukti" style="max-height:160px;max-width:100%;border-radius:8px;box-shadow:0 2px 8px rgba(0,0,0,0.15);">
        </div>

        <button class="btn <?= $type==='final' ? 'btn-gold' : 'btn-primary' ?> btn-block" type="submit" style="font-size:15px;padding:12px 20px;border-radius:12px;box-shadow:0 6px 18px rgba(216,133,78,0.25);">
          📤 Upload Bukti Pembayaran
        </button>
      </form>
    </div>
  </div>
</main>

<script>
function copyToClipboard(text, msg) {
  navigator.clipboard.writeText(text).then(function() {
    alert(msg);
  }, function() {
    const tempInput = document.createElement("input");
    tempInput.value = text;
    document.body.appendChild(tempInput);
    tempInput.select();
    document.execCommand("copy");
    document.body.removeChild(tempInput);
    alert(msg);
  });
}

function previewProofImage(input) {
  const box = document.getElementById('proofPreviewBox');
  const img = document.getElementById('proofPreviewImg');
  if (input.files && input.files[0]) {
    const reader = new FileReader();
    reader.onload = function(e) {
      img.src = e.target.result;
      box.style.display = 'block';
    }
    reader.readAsDataURL(input.files[0]);
  } else {
    box.style.display = 'none';
  }
}
</script>

<?php include 'includes/footer.php'; ?>
