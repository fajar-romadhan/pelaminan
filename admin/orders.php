<?php
require_once '../config/database.php';
require_once '../config/helpers.php';
require_admin();

$pageTitle = 'Kelola & Detail Pesanan'; 
$active = 'orders';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_order_workflow'])) {
    $orderId = filter_input(INPUT_POST, 'order_id', FILTER_VALIDATE_INT);
    $newStatus = trim($_POST['status'] ?? '');
    $schedStart = trim($_POST['schedule_start'] ?? '');
    $schedEnd = trim($_POST['schedule_end'] ?? '');

    if ($orderId && !empty($newStatus)) {


        $stmt = $pdo->prepare('SELECT order_code, status, schedule_start, schedule_end FROM orders WHERE id = ?');
        $stmt->execute([$orderId]);
        $currOrder = $stmt->fetch();

        if ($currOrder) {
            $oldStatus = $currOrder['status'];

            $startVal = !empty($schedStart) ? $schedStart : null;
            $endVal = !empty($schedEnd) ? $schedEnd : null;

            // Update orders table
            $upd = $pdo->prepare('UPDATE orders SET status = ?, schedule_start = ?, schedule_end = ? WHERE id = ?');
            $upd->execute([$newStatus, $startVal, $endVal, $orderId]);

            // Log status change if status changed
            if ($oldStatus !== $newStatus) {
                log_order_status_change($pdo, $orderId, $oldStatus, $newStatus, current_user()['id']);

                if (in_array($newStatus, ['READY_PICKUP', 'READY_DELIVERY'], true)) {
                    $lbl = $newStatus === 'READY_DELIVERY' ? 'Siap Dikirim' : 'Siap Diambil';
                    send_admin_notification($pdo, $orderId, '🚚 Pesanan ' . $lbl, "Status pesanan ID #{$orderId} diperbarui menjadi {$lbl}.");
                }
            }

            log_activity('Update Status & Jadwal Pesanan', "Mengubah pesanan {$currOrder['order_code']} ke status {$newStatus} (Jadwal: " . ($startVal ?: '-') . " s/d " . ($endVal ?: '-') . ")");

            set_flash('success', 'Status pesanan dan estimasi tanggal pengerjaan berhasil diperbarui.');
            redirect(BASE_URL . '/admin/orders.php?detail=' . $orderId);
        }
    }
}

// Admin Payment Verification Handlers
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action_type']) && in_array($_POST['action_type'], ['verify_payment', 'reject_payment'], true)) {
    verify_csrf_token($_POST['csrf_token'] ?? null);

    $paymentId = filter_input(INPUT_POST, 'payment_id', FILTER_VALIDATE_INT);
    $orderId = filter_input(INPUT_POST, 'order_id', FILTER_VALIDATE_INT);
    $actionType = $_POST['action_type'];

    if ($paymentId && $orderId) {
        $pCheck = $pdo->prepare('SELECT * FROM payments WHERE id = ? AND order_id = ? LIMIT 1');
        $pCheck->execute([$paymentId, $orderId]);
        $paymentRow = $pCheck->fetch();

        $oCheck = $pdo->prepare('SELECT * FROM orders WHERE id = ? LIMIT 1');
        $oCheck->execute([$orderId]);
        $orderRow = $oCheck->fetch();

        if ($paymentRow && $orderRow) {
            $pdo->beginTransaction();
            try {
                if ($actionType === 'verify_payment') {
                    $updP = $pdo->prepare("UPDATE payments SET status = 'berhasil', paid_at = NOW() WHERE id = ?");
                    $updP->execute([$paymentId]);

                    $payAmt = (float)$paymentRow['amount'];
                    $newPaid = (float)$orderRow['paid_amount'] + $payAmt;
                    $oldStatus = $orderRow['status'];

                    if ($paymentRow['type'] === 'dp') {
                        $newStatus = 'WAITING_QUEUE';
                    } else {
                        $newStatus = ($newPaid >= (float)$orderRow['total_amount']) ? 'COMPLETED' : $orderRow['status'];
                    }

                    $updO = $pdo->prepare("UPDATE orders SET paid_amount = ?, status = ? WHERE id = ?");
                    $updO->execute([$newPaid, $newStatus, $orderId]);

                    log_order_status_change($pdo, (int)$orderId, $oldStatus, $newStatus, current_user()['id']);

                    send_system_notification(
                        $pdo,
                        (int)$orderRow['user_id'],
                        (int)$orderId,
                        'Pembayaran Diverifikasi',
                        'Pembayaran ' . strtoupper($paymentRow['type']) . ' sebesar ' . rupiah($payAmt) . ' telah diverifikasi oleh Admin. Pesanan Anda kini masuk jadwal produksi.'
                    );

                    log_activity('Verifikasi Pembayaran', "Menyetujui pembayaran " . strtoupper($paymentRow['type']) . " sebesar " . rupiah($payAmt) . " untuk pesanan {$orderRow['order_code']}");

                    $pdo->commit();
                    set_flash('success', 'Pembayaran berhasil diverifikasi. Status pesanan diperbarui menjadi ' . status_label($newStatus) . '.');
                } else {
                    $updP = $pdo->prepare("UPDATE payments SET status = 'gagal' WHERE id = ?");
                    $updP->execute([$paymentId]);

                    $oldStatus = $orderRow['status'];
                    $newStatus = 'WAITING_PAYMENT';

                    $updO = $pdo->prepare("UPDATE orders SET status = ? WHERE id = ?");
                    $updO->execute([$newStatus, $orderId]);

                    log_order_status_change($pdo, (int)$orderId, $oldStatus, $newStatus, current_user()['id']);

                    send_system_notification(
                        $pdo,
                        (int)$orderRow['user_id'],
                        (int)$orderId,
                        'Bukti Pembayaran Ditolak',
                        'Bukti transfer pembayaran Anda belum dapat diverifikasi atau ditolak. Silakan unggah kembali bukti transfer yang valid.'
                    );

                    log_activity('Tolak Pembayaran', "Menolak bukti pembayaran untuk pesanan {$orderRow['order_code']}");

                    $pdo->commit();
                    set_flash('warning', 'Bukti pembayaran ditolak. Status pesanan dikembalikan menjadi Menunggu Pembayaran.');
                }
            } catch (Exception $e) {
                $pdo->rollBack();
                set_flash('danger', 'Gagal memproses verifikasi: ' . $e->getMessage());
            }
            redirect(BASE_URL . '/admin/orders.php?detail=' . $orderId);
        }
    }
}

$detail = null;
$payments = [];
$extraDetails = [];
if (isset($_GET['detail'])) {
    $detailId = filter_input(INPUT_GET, 'detail', FILTER_VALIDATE_INT);
    if ($detailId) {
        $stmt = $pdo->prepare('SELECT o.*, p.name AS product_name, p.code, p.image_url AS product_image, p.price AS base_product_price FROM orders o JOIN products p ON p.id=o.product_id WHERE o.id=? LIMIT 1');
        $stmt->execute([$detailId]);
        $detail = $stmt->fetch();

        if ($detail) {
            $pStmt = $pdo->prepare('SELECT * FROM payments WHERE order_id = ? ORDER BY id DESC');
            $pStmt->execute([$detailId]);
            $payments = $pStmt->fetchAll();

            $extraDetails = [];
            $designRow = null;

            if (!empty($detail['extra_items_detail'])) {
                $extraDetails = json_decode($detail['extra_items_detail'], true) ?: [];
            }

            if (!empty($detail['design_id'])) {
                $dStmt = $pdo->prepare("SELECT * FROM editor_designs WHERE id = ? LIMIT 1");
                $dStmt->execute([$detail['design_id']]);
                $designRow = $dStmt->fetch();

                if (empty($extraDetails) && !empty($designRow['extra_items_json'])) {
                    $extraDetails = json_decode($designRow['extra_items_json'], true) ?: [];
                }
            }
        }
    }
}

$search = trim($_GET['q'] ?? '');
$startDate = trim($_GET['start_date'] ?? '');
$endDate = trim($_GET['end_date'] ?? '');
$statusFilter = trim($_GET['status'] ?? '');

$where = [];
$params = [];

if ($search !== '') {
    $where[] = '(o.order_code LIKE ? OR o.customer_name LIKE ? OR o.phone LIKE ? OR p.name LIKE ? OR o.address LIKE ? OR o.city LIKE ?)';
    $searchTerm = "%{$search}%";
    $params[] = $searchTerm;
    $params[] = $searchTerm;
    $params[] = $searchTerm;
    $params[] = $searchTerm;
    $params[] = $searchTerm;
    $params[] = $searchTerm;
}

if ($startDate !== '') {
    $where[] = 'DATE(o.created_at) >= ?';
    $params[] = $startDate;
}

if ($endDate !== '') {
    $where[] = 'DATE(o.created_at) <= ?';
    $params[] = $endDate;
}

if ($statusFilter !== '') {
    $where[] = 'o.status = ?';
    $params[] = $statusFilter;
}

$whereSql = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';
$sql = "SELECT o.*, p.name AS product_name 
        FROM orders o 
        JOIN products p ON p.id=o.product_id 
        {$whereSql} 
        ORDER BY o.id DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$orders = $stmt->fetchAll();

include '../includes/admin_header.php';
?>
<?php if($detail): 
    $waPhone = preg_replace('/[^0-9]/', '', $detail['phone']);
    if (strpos($waPhone, '0') === 0) {
        $waPhone = '62' . substr($waPhone, 1);
    }
    $remBalance = max(0, (float)$detail['total_amount'] - (float)$detail['paid_amount']);
?>
  <p><a style="color:var(--terracotta-dark);font-weight:900" href="<?= BASE_URL ?>/admin/orders.php">← Kembali ke daftar pesanan</a></p>
  
  <div class="grid grid-3" style="align-items:start">
    <div class="card" style="grid-column:span 2">
      <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:16px;padding-bottom:12px;border-bottom:1.5px solid var(--border-subtle);">
        <div>
          <h3 style="color:var(--terracotta-dark);margin:0">Rincian Lengkap Pesanan <?= e($detail['order_code']) ?></h3>
          <small class="muted">Dibuat pada <?= date('d F Y, H:i', strtotime($detail['created_at'])) ?> WIB</small>
        </div>
        <span class="badge <?= status_class($detail['status']) ?>" style="font-size:14px;padding:6px 14px;"><?= status_label($detail['status']) ?></span>
      </div>

      <!-- Customer & Contact Info -->
      <div style="background:#fffdf8;border:1px solid #f5e6ca;border-radius:12px;padding:16px;margin-bottom:18px;">
        <h4 style="color:var(--espresso);margin:0 0 10px;font-size:15px;display:flex;align-items:center;gap:8px;">
          <span>👤 Informasi Pemesan & Kontak</span>
        </h4>
        <div class="grid grid-2" style="gap:12px;">
          <div><small class="muted">Nama Customer</small><br><strong style="font-size:15px;color:var(--espresso);"><?= e($detail['customer_name']) ?></strong></div>
          <div>
            <small class="muted">Nomor WhatsApp / HP</small><br>
            <strong style="color:green;font-size:14px;">📱 <?= e($detail['phone']) ?></strong>
            <a href="https://wa.me/<?= $waPhone ?>?text=<?= urlencode("Halo {$detail['customer_name']}, mengenai pesanan pelaminan #{$detail['order_code']}...") ?>" target="_blank" rel="noopener" class="btn btn-sm" style="background:#25D366;color:#fff;font-weight:700;margin-left:6px;padding:3px 10px;border-radius:6px;font-size:11.5px;text-decoration:none;display:inline-flex;align-items:center;gap:4px;">
              💬 Hubungi via WA
            </a>
          </div>
          <div><small class="muted">Metode Penerimaan</small><br><span class="badge badge-info" style="font-size:11.5px;"><?= $detail['pickup_method'] === 'diambil' ? '🏪 Diambil Sendiri di Workshop' : '🚚 Diantarkan Armada Toko' ?></span></div>
          <div><small class="muted">Patokan Lokasi / Catatan</small><br><strong><?= !empty($detail['delivery_note']) ? e($detail['delivery_note']) : '-' ?></strong></div>
        </div>
        
        <div style="margin-top:12px;padding-top:10px;border-top:1px dashed rgba(0,0,0,0.1);">
          <small class="muted">Alamat Lengkap Pengantaran / Acara:</small><br>
          <strong style="color:var(--espresso);"><?= e($detail['address']) ?></strong>
        </div>
      </div>

      <!-- Product & Customization Items -->
      <?php
      $basePhotoImg = !empty($detail['product_image']) ? BASE_URL . '/uploads/products/' . e($detail['product_image']) : BASE_URL . '/assets/img/no-image.png';
      if (!empty($detail['variant_id'])) {
          $pvCheck = $pdo->prepare("SELECT image FROM product_variants WHERE id = ? LIMIT 1");
          $pvCheck->execute([$detail['variant_id']]);
          if ($pvRow = $pvCheck->fetch()) {
              if (!empty($pvRow['image'])) {
                  $basePhotoImg = BASE_URL . '/uploads/products/variants/' . e($pvRow['image']);
              }
          }
      }
      ?>
      <div style="background:#fff;border:1px solid var(--border-subtle);border-radius:12px;padding:16px;margin-bottom:18px;">
        <h4 style="color:var(--espresso);margin:0 0 12px;font-size:15px;">📦 Produk Pelaminan & Item Kustomisasi</h4>
        <div style="display:flex;gap:14px;align-items:center;margin-bottom:14px;">
          <img src="<?= $basePhotoImg ?>" alt="<?= e($detail['product_name']) ?>" style="width:75px;height:60px;object-fit:cover;border-radius:8px;border:1px solid var(--border-subtle);" onerror="this.src='<?= BASE_URL ?>/assets/img/no-image.png'">
          <div>
            <strong style="font-size:15px;color:var(--espresso);"><?= e($detail['product_name']) ?></strong>
            <br><small class="muted">Kode Produk: <?= e($detail['code']) ?></small>
            <?= !empty($detail['variant_name']) ? '<br><span class="badge badge-info" style="font-size:11px;margin-top:4px;">🎨 Variasi Warna: '.e($detail['variant_name']).'</span>' : '' ?>
          </div>
        </div>

        <!-- VISUAL REVIEW CANVAS ADMIN (Live Visual Preview Layout & Item Dekorasi) -->
        <?php if (!empty($extraDetails) || !empty($designRow)): ?>
          <div style="margin-top:16px;padding:16px;background:#fdfaf6;border:1.5px solid rgba(212,175,55,.35);border-radius:14px;">
            <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:12px;">
              <h5 style="margin:0;font-size:14px;color:var(--espresso);font-weight:800;display:flex;align-items:center;gap:6px;">
                <span>🎨 Visual Live Layout Kustomisasi Pelaminan</span>
              </h5>
              <span class="badge badge-success" style="font-size:10px;">Review Admin</span>
            </div>

            <!-- Live Canvas Container with Overlay Item Layers at exact coordinates -->
            <div id="adminCanvasPreview" style="position:relative;width:100%;aspect-ratio:16/9;min-height:260px;max-height:420px;border-radius:12px;overflow:hidden;border:1px solid var(--border-subtle);background:#ffffff;">
              <img id="adminPhotoVariantPreview" src="<?= $basePhotoImg ?>" alt="<?= e($detail['product_name']) ?>" style="width:100%;height:100%;object-fit:cover;object-position:center;pointer-events:none;" onerror="this.src='<?= BASE_URL ?>/assets/img/no-image.png'">

              <div style="position:absolute;top:0;left:0;width:100%;height:100%;pointer-events:none;">
                <?php 
                $unitGlobalIndex = 0;
                foreach ($extraDetails as $ex): 
                    $exQty = (int)($ex['quantity'] ?? 1);
                    $rawImg = $ex['image_url'] ?? '';
                    $exImgPath = BASE_URL . '/uploads/products/' . e($rawImg);
                    if (strpos($rawImg, 'variants/') === 0 || strpos($rawImg, 'uploads/') === 0) {
                        $exImgPath = BASE_URL . '/uploads/' . preg_replace('#^uploads/#', '', e($rawImg));
                    }
                    $positions = $ex['positions'] ?? [];

                    for ($u = 1; $u <= $exQty; $u++):
                        $pos = $positions[$u - 1] ?? [];
                        if (isset($pos['pctX']) && isset($pos['pctY'])) {
                            $leftStyle = (float)$pos['pctX'] . '%';
                            $topStyle = (float)$pos['pctY'] . '%';
                        } else {
                            // Precise percentage conversion fallback for older orders (bottom-left stage floor)
                            $leftStyle = (5 + (($unitGlobalIndex % 3) * 11)) . '%';
                            $topStyle = '64%';
                        }
                        $unitGlobalIndex++;
                ?>
                    <div style="position:absolute;left:<?= $leftStyle ?>;top:<?= $topStyle ?>;pointer-events:none;z-index:15;">
                      <div style="position:relative;display:inline-flex;flex-direction:column;align-items:center;">
                        <span style="position:absolute;top:-8px;right:-8px;background:var(--terracotta-dark);color:#fff;font-size:9.5px;font-weight:800;padding:2px 6px;border-radius:99px;box-shadow:0 2px 6px rgba(0,0,0,0.3);z-index:5;">#<?= $u ?></span>
                        <img src="<?= $exImgPath ?>" alt="<?= e($ex['name'] ?? '') ?>" style="height:75px;width:auto;max-width:75px;max-height:80px;object-fit:contain;filter:drop-shadow(0 6px 12px rgba(0,0,0,0.45));" onerror="this.src='<?= BASE_URL ?>/assets/img/no-image.png'">
                      </div>
                    </div>
                <?php endfor; endforeach; ?>
              </div>
            </div>

            <!-- Detailed Table of Extra Items -->
            <div style="margin-top:14px;background:#ffffff;border:1px solid var(--border-subtle);border-radius:10px;padding:12px;">
              <strong style="color:var(--espresso);font-size:13px;display:block;margin-bottom:8px;">📋 Rincian Detail Item Dekorasi Tambahan:</strong>
              <div style="display:flex;flex-direction:column;gap:8px;">
                <?php foreach ($extraDetails as $ex): 
                    $exQty = (int)($ex['quantity'] ?? 1);
                    $exPrice = (float)($ex['price'] ?? 0);
                    $exSubtotal = (float)($ex['subtotal'] ?? ($exPrice * $exQty));
                    $exVar = !empty($ex['variant_name']) ? " (Warna: " . e($ex['variant_name']) . ")" : "";
                    $rawImg = $ex['image_url'] ?? '';
                    $exImgPath = BASE_URL . '/uploads/products/' . e($rawImg);
                    if (strpos($rawImg, 'variants/') === 0 || strpos($rawImg, 'uploads/') === 0) {
                        $exImgPath = BASE_URL . '/uploads/' . preg_replace('#^uploads/#', '', e($rawImg));
                    }
                ?>
                  <div style="display:flex;justify-content:space-between;align-items:center;padding:8px 10px;background:#f8f9fa;border-radius:8px;border:1px solid #e9ecef;font-size:12.5px;">
                    <div style="display:flex;align-items:center;gap:10px;">
                      <img src="<?= $exImgPath ?>" alt="<?= e($ex['name'] ?? '') ?>" style="width:38px;height:38px;object-fit:contain;border-radius:6px;background:#fff;border:1px solid #ddd;" onerror="this.src='<?= BASE_URL ?>/assets/img/no-image.png'">
                      <div>
                        <span class="badge badge-info" style="font-size:9px;margin-bottom:2px;"><?= e($ex['category'] ?? 'Item Extra') ?></span>
                        <strong style="color:var(--espresso);display:block;"><?= e($ex['name'] ?? '') ?><?= $exVar ?></strong>
                        <small class="muted"><?= $exQty ?> pcs × <?= rupiah($exPrice) ?></small>
                      </div>
                    </div>
                    <strong style="color:var(--terracotta-dark);font-size:13.5px;">+ <?= rupiah($exSubtotal) ?></strong>
                  </div>
                <?php endforeach; ?>
              </div>
            </div>
          </div>
        <?php endif; ?>
      </div>

      <!-- Payment & Transaction Breakdown -->
      <div style="background:#f9fbf9;border:1.5px solid #b7e4c7;border-radius:14px;padding:20px;margin-bottom:18px;">
        <h4 style="color:#155724;margin:0 0 14px;font-size:16px;font-weight:700;">💰 Rincian Subtotal Produk & Pembayaran Keuangan</h4>
        
        <!-- Detailed Subtotal Table Breakdown -->
        <div style="background:#ffffff;border:1px solid #d4edda;border-radius:10px;padding:14px;margin-bottom:16px;font-size:13.5px;">
          <?php 
          $addonsTotal = 0;
          if (!empty($extraDetails)) {
              foreach ($extraDetails as $ex) {
                  $addonsTotal += (float)($ex['price'] ?? 0);
              }
          }
          $shippingFee = (float)($detail['shipping_cost'] ?? 0);
          $grandTotal = (float)($detail['total_amount'] ?? 0);
          $baseProductPrice = (float)($detail['base_product_price'] ?? 0);
          if ($baseProductPrice <= 0 || ($baseProductPrice + $addonsTotal + $shippingFee != $grandTotal)) {
              $baseProductPrice = max(0, $grandTotal - $addonsTotal - $shippingFee);
          }
          ?>
          
          <div style="display:flex;justify-content:space-between;padding:6px 0;border-bottom:1px solid #f0f0f0;">
            <span style="color:#444;">📦 Subtotal Produk Utama (<?= e($detail['product_name']) ?>):</span>
            <strong style="color:var(--espresso);"><?= rupiah($baseProductPrice) ?></strong>
          </div>

          <?php if (!empty($detail['variant_name'])): ?>
            <div style="display:flex;justify-content:space-between;padding:6px 0;border-bottom:1px solid #f0f0f0;">
              <span style="color:#444;">🎨 Variasi Warna: <strong><?= e($detail['variant_name']) ?></strong></span>
              <span style="color:#777;">(Termasuk)</span>
            </div>
          <?php endif; ?>

          <?php if (!empty($extraDetails)): ?>
            <?php foreach ($extraDetails as $ex): 
                $exP = (float)($ex['price'] ?? 0);
            ?>
              <div style="display:flex;justify-content:space-between;padding:6px 0;border-bottom:1px solid #f0f0f0;">
                <span style="color:#444;">🧩 Item Tambahan (<?= e($ex['name'] ?? '') ?>):</span>
                <strong style="color:var(--terracotta-dark);"><?= $exP > 0 ? '+'.rupiah($exP) : 'Gratis' ?></strong>
              </div>
            <?php endforeach; ?>
          <?php endif; ?>

          <div style="display:flex;justify-content:space-between;padding:6px 0;border-bottom:1px solid #f0f0f0;">
            <span style="color:#444;">🚚 Biaya Pengiriman (<?= ($detail['pickup_method'] ?? '') === 'diambil' ? 'Diambil Sendiri di Workshop' : 'Diantarkan Armada Toko' ?>):</span>
            <strong style="color:<?= $shippingFee > 0 ? 'var(--terracotta-dark)' : '#27ae60' ?>;">
              <?= $shippingFee > 0 ? '+'.rupiah($shippingFee) : 'Rp 0 (Bebas Ongkir)' ?>
            </strong>
          </div>

          <div style="display:flex;justify-content:space-between;padding:10px 0 0;font-size:15.5px;font-weight:700;border-top:1.5px solid #d4edda;margin-top:4px;">
            <span style="color:var(--espresso);">Total Biaya Pesanan (Grand Total):</span>
            <strong style="color:var(--terracotta-dark);font-size:17px;"><?= rupiah($detail['total_amount']) ?></strong>
          </div>
        </div>

        <!-- 4 Summary Status Cards -->
        <div class="grid grid-4" style="gap:10px;margin-bottom:12px;background:#f0f9f0;padding:12px;border-radius:10px;border:1px solid #c3e6cb;">
          <div><small class="muted">Grand Total Biaya</small><br><strong style="font-size:15px;color:var(--espresso);"><?= rupiah($detail['total_amount']) ?></strong></div>
          <div><small class="muted">Minimal DP (50%)</small><br><strong style="color:var(--terracotta-dark);"><?= rupiah($detail['dp_amount']) ?></strong></div>
          <div><small class="muted">Sudah Dibayar</small><br><strong style="color:green;font-size:15px;"><?= rupiah($detail['paid_amount']) ?></strong></div>
          <div><small class="muted">Sisa Pelunasan</small><br><strong style="color:<?= $remBalance > 0 ? '#dc3545' : 'green' ?>;font-size:14px;"><?= rupiah($remBalance) ?></strong></div>
        </div>

        <?php if (!empty($payments)): ?>
          <div style="margin-top:14px;padding-top:14px;border-top:1px dashed #c3e6cb;">
            <small style="font-weight:800;color:#155724;display:block;margin-bottom:8px;font-size:13px;">💳 Riwayat & Bukti Pembayaran:</small>
            <div style="display:flex;flex-direction:column;gap:10px;">
              <?php foreach ($payments as $pay): ?>
                <div style="background:#fff;border:1.5px solid <?= $pay['status'] === 'pending' ? '#ffeba7' : ($pay['status'] === 'berhasil' ? '#d4edda' : '#f8d7da') ?>;padding:12px 14px;border-radius:10px;font-size:13px;">
                  <div style="display:flex;justify-content:space-between;align-items:flex-start;flex-wrap:wrap;gap:10px;">
                    <div>
                      <strong style="color:var(--espresso);font-size:14px;"><?= strtoupper($pay['type']) ?>: <?= rupiah($pay['amount']) ?></strong>
                      <span class="muted"> (<?= e($pay['method'] ?? 'Transfer Bank BRI') ?>)</span>
                      <br><small class="muted">Tgl Upload: <?= date('d M Y, H:i', strtotime($pay['paid_at'] ?? $pay['created_at'] ?? 'now')) ?> WIB</small>
                    </div>
                    <div>
                      <?php if ($pay['status'] === 'pending'): ?>
                        <span class="badge badge-warning" style="font-size:11px;padding:4px 8px;">⏳ MENUNGGU VERIFIKASI ADMIN</span>
                      <?php elseif ($pay['status'] === 'berhasil'): ?>
                        <span class="badge badge-success" style="font-size:11px;padding:4px 8px;">✅ VERIFIKASI BERHASIL</span>
                      <?php else: ?>
                        <span class="badge badge-danger" style="font-size:11px;padding:4px 8px;">❌ DITOLAK</span>
                      <?php endif; ?>
                    </div>
                  </div>

                  <?php if (!empty($pay['proof_image'])): ?>
                    <div style="margin-top:10px;padding-top:10px;border-top:1px dashed #eee;display:flex;align-items:center;gap:14px;">
                      <a href="<?= BASE_URL ?>/<?= e($pay['proof_image']) ?>" target="_blank" title="Klik untuk lihat foto bukti ukuran penuh">
                        <img src="<?= BASE_URL ?>/<?= e($pay['proof_image']) ?>" alt="Bukti Transfer" style="height:70px;width:100px;object-fit:cover;border-radius:8px;border:1px solid #ccc;box-shadow:0 2px 6px rgba(0,0,0,0.1);">
                      </a>
                      <div style="font-size:12px;">
                        <strong style="color:var(--terracotta-dark);">Foto Bukti Transfer Struk</strong>
                        <p style="margin:2px 0 0;color:#666;font-size:11.5px;">Klik foto di samping untuk membuka gambar asli.</p>
                      </div>
                    </div>
                  <?php endif; ?>

                  <?php if ($pay['status'] === 'pending'): ?>
                    <div style="margin-top:12px;padding-top:10px;border-top:1px solid #ffeba7;display:flex;gap:8px;">
                      <form method="post" action="<?= BASE_URL ?>/admin/orders.php?detail=<?= (int)$detail['id'] ?>" style="margin:0;display:inline;">
                        <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                        <input type="hidden" name="action_type" value="verify_payment">
                        <input type="hidden" name="payment_id" value="<?= (int)$pay['id'] ?>">
                        <input type="hidden" name="order_id" value="<?= (int)$detail['id'] ?>">
                        <button type="submit" class="btn btn-primary btn-sm" onclick="return confirm('Apakah Anda yakin ingin memverifikasi & menyetujui pembayaran ini?')" style="font-size:12px;padding:6px 12px;border-radius:8px;">
                          ✅ Verifikasi & Terima Pembayaran
                        </button>
                      </form>

                      <form method="post" action="<?= BASE_URL ?>/admin/orders.php?detail=<?= (int)$detail['id'] ?>" style="margin:0;display:inline;">
                        <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                        <input type="hidden" name="action_type" value="reject_payment">
                        <input type="hidden" name="payment_id" value="<?= (int)$pay['id'] ?>">
                        <input type="hidden" name="order_id" value="<?= (int)$detail['id'] ?>">
                        <button type="submit" class="btn btn-outline btn-sm" onclick="return confirm('Tolak bukti pembayaran ini?')" style="font-size:12px;padding:6px 12px;border-radius:8px;color:#dc3545;border-color:#dc3545;">
                          ❌ Tolak Pembayaran
                        </button>
                      </form>
                    </div>
                  <?php endif; ?>
                </div>
              <?php endforeach; ?>
            </div>
          </div>
        <?php endif; ?>
      </div>

      <!-- Map & Location Section -->
      <div style="border-top:1px solid var(--border-subtle);padding-top:18px;">
        <h4 style="color:var(--terracotta-dark);margin:0 0 12px">📍 Data Lokasi Peta & Catatan Admin</h4>
        <div class="grid grid-2" style="margin-bottom:12px">
          <div><small class="muted">Alamat Hasil Peta</small><br><strong><?= e($detail['delivery_map_address'] ?: 'Tidak ada lokasi peta') ?></strong></div>
          <div><small class="muted">Catatan untuk Admin</small><br><strong style="color:var(--terracotta-dark);"><?= e($detail['delivery_note'] ?: '-') ?></strong></div>
          <div><small class="muted">Latitude</small><br><code><?= e($detail['delivery_latitude'] ?? '-') ?></code></div>
          <div><small class="muted">Longitude</small><br><code><?= e($detail['delivery_longitude'] ?? '-') ?></code></div>
        </div>

        <?php if (!empty($detail['delivery_latitude']) && !empty($detail['delivery_longitude'])): ?>
          <div id="admin-detail-map" class="admin-map-view" style="height:240px;margin-top:10px;border-radius:12px;"></div>
          <div style="margin-top:12px">
            <a href="https://www.google.com/maps?q=<?= e($detail['delivery_latitude']) ?>,<?= e($detail['delivery_longitude']) ?>" target="_blank" rel="noopener noreferrer" class="btn btn-primary btn-sm" style="border-radius:8px;">
              🗺️ Buka di Google Maps
            </a>
          </div>
          <script>
            document.addEventListener('DOMContentLoaded', function() {
              const lat = <?= (float)$detail['delivery_latitude'] ?>;
              const lng = <?= (float)$detail['delivery_longitude'] ?>;
              const map = L.map('admin-detail-map').setView([lat, lng], 15);
              L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                maxZoom: 19,
                attribution: '&copy; OpenStreetMap'
              }).addTo(map);
              L.marker([lat, lng]).addTo(map)
                .bindPopup('<b>Lokasi Acara</b><br><?= e(addslashes($detail['delivery_map_address'] ?? '')) ?>').openPopup();
            });
          </script>
        <?php else: ?>
          <div style="margin-top:12px">
            <a href="https://www.google.com/maps?q=<?= urlencode(e($detail['address'] . ', Palembang, Sumatera Selatan')) ?>" target="_blank" rel="noopener noreferrer" class="btn btn-primary btn-sm" style="border-radius:8px;">
              🗺️ Buka di Google Maps
            </a>
          </div>
        <?php endif; ?>
      </div>
    </div>
    
    <!-- Sidebar Workflow & Actions -->
    <div class="card">
      <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:16px;">
        <h3 style="color:var(--terracotta-dark);margin:0;">Status & Alur Pengerjaan</h3>
        <span class="badge <?= status_class($detail['status']) ?>" style="font-size:12px;padding:5px 10px;"><?= status_label($detail['status']) ?></span>
      </div>
      
      <form method="post" action="<?= BASE_URL ?>/admin/orders.php?detail=<?= (int)$detail['id'] ?>" style="margin-bottom:20px;background:linear-gradient(180deg, #ffffff 0%, #fcf8f4 100%);padding:18px;border-radius:14px;border:1px solid rgba(216,133,78,0.22);box-shadow:0 4px 12px rgba(0,0,0,0.03);">
        <input type="hidden" name="order_id" value="<?= (int)$detail['id'] ?>">

        <div style="margin-bottom:14px;">
          <label style="font-size:12px;font-weight:700;letter-spacing:0.03em;text-transform:uppercase;display:block;margin-bottom:6px;color:var(--espresso);">
            Status Pesanan Saat Ini
          </label>
          <select name="status" class="select" style="width:100%;font-weight:600;font-size:13.5px;padding:10px 14px;border-radius:10px;border:1.5px solid var(--border);background:#fff;">
            <?php
            $statuses = [
              'WAITING_QUEUE'   => 'Masuk Antrean Produksi',
              'PRODUCTION'      => 'Sedang Diproduksi',
              'READY_DELIVERY'  => 'Dalam Pengiriman',
              'READY_PICKUP'    => 'Siap Diambil',
              'COMPLETED'       => 'Pesanan Selesai',
              'CANCELLED'       => 'Dibatalkan',
            ];
            foreach ($statuses as $stKey => $stLabel):
            ?>
              <option value="<?= $stKey ?>" <?= $detail['status'] === $stKey ? 'selected' : '' ?>>
                <?= e($stLabel) ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>

        <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px;margin-bottom:16px;">
          <div>
            <label style="font-size:11.5px;font-weight:700;letter-spacing:0.02em;text-transform:uppercase;display:block;margin-bottom:4px;color:var(--espresso);">
              📅 Mulai Pengerjaan
            </label>
            <input type="date" name="schedule_start" class="input" value="<?= e($detail['schedule_start'] ?? '') ?>" style="width:100%;padding:8px 10px;font-size:12.5px;border-radius:8px;">
          </div>
          <div>
            <label style="font-size:11.5px;font-weight:700;letter-spacing:0.02em;text-transform:uppercase;display:block;margin-bottom:4px;color:var(--espresso);">
              🏁 Selesai Pengerjaan
            </label>
            <input type="date" name="schedule_end" class="input" value="<?= e($detail['schedule_end'] ?? '') ?>" style="width:100%;padding:8px 10px;font-size:12.5px;border-radius:8px;">
          </div>
        </div>

        <button type="submit" name="update_order_workflow" class="btn btn-primary btn-block" style="font-weight:700;padding:10px;border-radius:10px;box-shadow:0 4px 12px rgba(216,133,78,0.25);">
          💾 Simpan Perubahan
        </button>
      </form>

      <h4 style="color:var(--espresso);margin:0 0 6px;font-size:14px;">Tindakan & Dokumen Order</h4>
      <p class="muted" style="font-size:12px;margin-bottom:12px;">Pintasan cetak dokumen resmi dan kompensasi transaksi.</p>

      <div style="display:flex;flex-direction:column;gap:8px;">
        <a class="btn btn-outline btn-block" style="border-color:var(--terracotta-dark);color:var(--terracotta-dark);font-weight:bold;border-radius:8px;font-size:13px;" href="<?= BASE_URL ?>/invoice.php?order_id=<?= (int)$detail['id'] ?>" target="_blank">
          📄 Cetak Invoice Tagihan
        </a>

        <?php if ((float)$detail['paid_amount'] > 0): ?>
          <a class="btn btn-outline btn-block" style="border-color:#28a745;color:#28a745;font-weight:bold;border-radius:8px;font-size:13px;" href="<?= BASE_URL ?>/receipt.php?order_id=<?= (int)$detail['id'] ?>" target="_blank">
            🧾 Cetak Kwitansi Pembayaran
          </a>
        <?php endif; ?>

        <a class="btn btn-outline btn-block" style="border-radius:8px;font-size:13px;" href="<?= BASE_URL ?>/tracking.php?order_id=<?= (int)$detail['id'] ?>" target="_blank">
          👁️ Lihat Halaman Tracking Customer
        </a>

        <a href="https://wa.me/<?= $waPhone ?>?text=<?= urlencode("Halo {$detail['customer_name']}, mengenai pesanan pelaminan #{$detail['order_code']}...") ?>" target="_blank" rel="noopener" class="btn btn-block" style="background:#25D366;color:#fff;font-weight:bold;border-radius:8px;font-size:13px;text-align:center;text-decoration:none;">
          💬 Chat WhatsApp Customer
        </a>
      </div>
    </div>
  </div>
<?php else: ?>
  <!-- Smart Search Engine & Filter Card -->
  <div class="card" style="margin-bottom:20px;background:linear-gradient(180deg, #ffffff 0%, #fcf9f5 100%);border:1px solid rgba(216,133,78,0.25);border-radius:16px;box-shadow:0 6px 20px rgba(54,34,23,0.04);padding:20px;">
    <form method="get" action="<?= BASE_URL ?>/admin/orders.php" style="margin:0;">
      <div style="display:grid;grid-template-columns:2fr 1fr 1fr 1fr;gap:12px;align-items:end;">
        
        <!-- Smart Search Input -->
        <div>
          <input type="text" name="q" value="<?= e($search) ?>" placeholder="Cari Kode Order, Customer, WA, Produk..." class="input" style="width:100%;padding:10px 14px;border-radius:10px;border:1.5px solid var(--border);background:#fff;font-size:13px;">
        </div>

        <!-- Start Date -->
        <div>
          <label style="font-size:11.5px;font-weight:800;letter-spacing:0.04em;text-transform:uppercase;color:var(--espresso);display:block;margin-bottom:6px;">
            📅 Dari Tanggal
          </label>
          <input type="date" name="start_date" value="<?= e($startDate) ?>" class="input" style="width:100%;padding:9px 12px;border-radius:10px;border:1.5px solid var(--border);background:#fff;font-size:12.5px;">
        </div>

        <!-- End Date -->
        <div>
          <label style="font-size:11.5px;font-weight:800;letter-spacing:0.04em;text-transform:uppercase;color:var(--espresso);display:block;margin-bottom:6px;">
            📅 Sampai Tanggal
          </label>
          <input type="date" name="end_date" value="<?= e($endDate) ?>" class="input" style="width:100%;padding:9px 12px;border-radius:10px;border:1.5px solid var(--border);background:#fff;font-size:12.5px;">
        </div>

        <!-- Status Filter -->
        <div>
          <label style="font-size:11.5px;font-weight:800;letter-spacing:0.04em;text-transform:uppercase;color:var(--espresso);display:block;margin-bottom:6px;">
            🏷️ Filter Status
          </label>
          <select name="status" class="select" style="width:100%;padding:9px 12px;border-radius:10px;border:1.5px solid var(--border);background:#fff;font-size:12.5px;">
            <option value="">-- Semua Status --</option>
            <option value="WAITING_QUEUE" <?= $statusFilter==='WAITING_QUEUE'?'selected':'' ?>>Masuk Antrean Produksi</option>
            <option value="PRODUCTION" <?= $statusFilter==='PRODUCTION'?'selected':'' ?>>Sedang Diproduksi</option>
            <option value="READY_DELIVERY" <?= $statusFilter==='READY_DELIVERY'?'selected':'' ?>>Dalam Pengiriman</option>
            <option value="READY_PICKUP" <?= $statusFilter==='READY_PICKUP'?'selected':'' ?>>Siap Diambil</option>
            <option value="COMPLETED" <?= $statusFilter==='COMPLETED'?'selected':'' ?>>Pesanan Selesai</option>
            <option value="CANCELLED" <?= $statusFilter==='CANCELLED'?'selected':'' ?>>Dibatalkan</option>
          </select>
        </div>

      </div>

      <!-- Quick Shortcuts & Action Buttons -->
      <div style="display:flex;justify-content:space-between;align-items:center;margin-top:14px;padding-top:12px;border-top:1px dashed rgba(216,133,78,0.2);flex-wrap:wrap;gap:10px;">
        <div style="display:flex;align-items:center;gap:6px;flex-wrap:wrap;">
          <span style="font-size:11.5px;font-weight:700;color:var(--espresso);margin-right:4px;">Pintasan Tanggal:</span>
          <button type="button" onclick="setQuickDate('today')" class="btn btn-outline btn-sm" style="font-size:11px;padding:3px 10px;border-radius:20px;">Hari Ini</button>
          <button type="button" onclick="setQuickDate('7days')" class="btn btn-outline btn-sm" style="font-size:11px;padding:3px 10px;border-radius:20px;">7 Hari Terakhir</button>
          <button type="button" onclick="setQuickDate('this_month')" class="btn btn-outline btn-sm" style="font-size:11px;padding:3px 10px;border-radius:20px;">Bulan Ini</button>
        </div>

        <div style="display:flex;gap:8px;align-items:center;">
          <?php if ($search !== '' || $startDate !== '' || $endDate !== '' || $statusFilter !== ''): ?>
            <span class="badge badge-info" style="font-size:11px;padding:5px 10px;">
              Ditemukan: <?= count($orders) ?> Data
            </span>
            <a href="<?= BASE_URL ?>/admin/orders.php" class="btn btn-outline btn-sm" style="border-radius:8px;font-size:12px;">
              🔄 Reset Filter
            </a>
          <?php endif; ?>
          <button type="submit" class="btn btn-primary btn-sm" style="padding:8px 18px;border-radius:8px;font-weight:700;font-size:12.5px;box-shadow:0 3px 10px rgba(216,133,78,0.25);">
            🔍 Cari & Filter Data
          </button>
        </div>
      </div>
    </form>
  </div>

  <script>
  function setQuickDate(type) {
    const startInput = document.querySelector('input[name="start_date"]');
    const endInput = document.querySelector('input[name="end_date"]');
    const today = new Date().toISOString().split('T')[0];

    if (type === 'today') {
      startInput.value = today;
      endInput.value = today;
    } else if (type === '7days') {
      const d = new Date();
      d.setDate(d.getDate() - 7);
      startInput.value = d.toISOString().split('T')[0];
      endInput.value = today;
    } else if (type === 'this_month') {
      const d = new Date();
      const firstDay = new Date(d.getFullYear(), d.getMonth(), 1).toISOString().split('T')[0];
      startInput.value = firstDay;
      endInput.value = today;
    }
  }
  </script>

  <?php if (empty($orders)): ?>
    <div class="card" style="text-align:center;padding:50px 20px;color:#777;margin-bottom:20px;">
      <div style="font-size:48px;margin-bottom:12px;">🔎</div>
      <h3 style="color:var(--espresso);margin:0 0 6px;">Pesanan Tidak Ditemukan</h3>
      <p style="margin:0 0 16px;font-size:13.5px;" class="muted">Tidak ada pesanan yang sesuai dengan kata kunci pencarian atau filter tanggal yang Anda pilih.</p>
      <a href="<?= BASE_URL ?>/admin/orders.php" class="btn btn-outline btn-sm">Tampilkan Semua Pesanan</a>
    </div>
  <?php else: ?>
  <div class="table-wrap">
    <table class="table">
      <thead>
        <tr>
          <th>No. Pesanan</th>
          <th>Customer</th>
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
          <td><?= e($o['customer_name']) ?></td>
          <td><?= e($o['product_name']) ?><?= !empty($o['variant_name']) ? '<br><small class="muted">Warna: '.e($o['variant_name']).'</small>' : '' ?></td>
          <td><?= date('d M Y', strtotime($o['created_at'])) ?></td>
          <td>
            <small style="font-weight:bold;color:var(--espresso);">
              <?php if (!empty($o['schedule_start']) && !empty($o['schedule_end'])): ?>
                Diproses <?= format_date_range($o['schedule_start'], $o['schedule_end']) ?>
              <?php else: ?>
                <span class="muted">-</span>
              <?php endif; ?>
            </small>
          </td>
          <td><?= rupiah($o['total_amount']) ?></td>
          <td><span class="badge <?= status_class($o['status']) ?>"><?= status_label($o['status']) ?></span></td>
          <td><a class="btn btn-outline btn-sm" href="<?= BASE_URL ?>/admin/orders.php?detail=<?= (int)$o['id'] ?>">Detail</a></td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <?php endif; ?>
<?php endif; ?>
<?php include '../includes/admin_footer.php'; ?>
