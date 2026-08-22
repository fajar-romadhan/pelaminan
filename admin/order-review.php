<?php
require_once '../config/database.php';
require_once '../config/helpers.php';
require_admin();

$pageTitle = 'Review Pesanan Pelaminan'; 
$active = 'review';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf_token($_POST['csrf_token'] ?? null);

    $action = $_POST['action'] ?? '';
    $orderId = filter_input(INPUT_POST, 'order_id', FILTER_VALIDATE_INT);
    $rejectReason = trim($_POST['reject_reason'] ?? 'Pesanan tidak dapat diproses.');

    if ($orderId) {
        $stmt = $pdo->prepare('
            SELECT o.*, p.id AS product_id, p.name AS product_name, p.production_duration 
            FROM orders o 
            JOIN products p ON p.id = o.product_id 
            WHERE o.id = ? 
            LIMIT 1
        ');
        $stmt->execute([$orderId]);
        $order = $stmt->fetch();

        if ($order) {
            $oldStatus = $order['status'];

            if ($action === 'approve') {
                $pdo->beginTransaction();
                try {
                    if (in_array($order['status'], ['WAITING_QUEUE', 'PRODUCTION', 'COMPLETED'], true)) {
                        throw new Exception("Pesanan ini sudah diproses atau masuk antrean produksi.");
                    }

                    // Get next queue number
                    $maxQStmt = $pdo->query("SELECT COALESCE(MAX(queue_number), 0) FROM orders");
                    $nextQueueNum = (int)$maxQStmt->fetchColumn() + 1;

                    // Calculate production start & end date based on duration and existing queue
                    $dates = calculate_production_dates($pdo, (int)$order['product_id'], $order['event_date']);
                    $startDate = $dates['start_date'];
                    $endDate = $dates['end_date'];

                    // Update order status & schedule directly in orders table
                    $updO = $pdo->prepare("
                        UPDATE orders 
                        SET status = 'WAITING_QUEUE', queue_number = ?, schedule_start = ?, schedule_end = ? 
                        WHERE id = ?
                    ");
                    $updO->execute([$nextQueueNum, $startDate, $endDate, $orderId]);

                    // Log history
                    log_order_status_change($pdo, $orderId, $oldStatus, 'WAITING_QUEUE', current_user()['id']);

                    // Send notification to customer
                    $formattedRange = format_date_range($startDate, $endDate);
                    $qFormatted = '#' . sprintf('%03d', $nextQueueNum);

                    $msg = "Pesanan Anda telah diterima.\n\nNomor antrean: {$qFormatted}\n\nJadwal pengerjaan: Diproses {$formattedRange}";

                    send_system_notification(
                        $pdo,
                        (int)$order['user_id'],
                        $orderId,
                        'Pesanan Diterima & Masuk Antrean',
                        $msg
                    );

                    $pdo->commit();
                    set_flash('success', "Pesanan {$order['order_code']} BERHASIL disetujui! Masuk antrean {$qFormatted} (Diproses {$formattedRange}).");
                } catch (Throwable $e) {
                    $pdo->rollBack();
                    set_flash('danger', 'Gagal menyetujui pesanan: ' . $e->getMessage());
                }
            } elseif ($action === 'reject') {
                $pdo->beginTransaction();
                try {
                    $updO = $pdo->prepare("UPDATE orders SET status = 'REJECTED' WHERE id = ?");
                    $updO->execute([$orderId]);

                    log_order_status_change($pdo, $orderId, $oldStatus, 'REJECTED', current_user()['id']);

                    send_system_notification(
                        $pdo,
                        (int)$order['user_id'],
                        $orderId,
                        'Pesanan Ditolak',
                        "Pesanan Anda #{$order['order_code']} tidak disetujui oleh admin.\nAlasan: {$rejectReason}"
                    );

                    $pdo->commit();
                    set_flash('warning', "Pesanan {$order['order_code']} telah DITOLAK.");
                } catch (Throwable $e) {
                    $pdo->rollBack();
                    set_flash('danger', 'Gagal menolak pesanan: ' . $e->getMessage());
                }
            }
        }
    }
    redirect(BASE_URL . '/admin/order-review.php');
}

// Fetch pending review orders
$stmt = $pdo->query("
    SELECT o.*, p.name AS product_name, p.code AS product_code, p.size AS product_size, p.production_duration,
           u.name AS user_name, u.email AS user_email
    FROM orders o
    JOIN products p ON p.id = o.product_id
    JOIN users u ON u.id = o.user_id
    WHERE o.status IN ('PAYMENT_RECEIVED', 'ADMIN_REVIEW')
    ORDER BY o.id ASC
");
$pendingOrders = $stmt->fetchAll();

include '../includes/admin_header.php';
?>

<div class="card" style="margin-bottom:20px;">
  <h2 style="color:var(--terracotta-dark);margin:0 0 8px;">📋 Review & Approval Pesanan Pelaminan</h2>
  <p class="muted">Halaman ini digunakan oleh admin untuk memeriksa detail pesanan, kelengkapan custom, tanggal acara, lokasi, dan bukti pembayaran sebelum menentukan jadwal antrean produksi.</p>
</div>

<?php if (empty($pendingOrders)): ?>
  <div class="card" style="text-align:center;padding:50px;">
    <h3 style="color:var(--muted)">Belum Ada Pesanan yang Menunggu Review</h3>
    <p class="muted">Semua pesanan baru yang sudah dibayar akan tampil di sini untuk diperiksa.</p>
  </div>
<?php else: ?>
  <?php foreach ($pendingOrders as $ord): ?>
    <?php
    // Fetch custom design if available
    $dStmt = $pdo->prepare("SELECT * FROM editor_designs WHERE user_id = ? AND product_id = ? ORDER BY id DESC LIMIT 1");
    $dStmt->execute([$ord['user_id'], $ord['product_id']]);
    $customDesign = $dStmt->fetch();

    // Fetch payments
    $pStmt = $pdo->prepare("SELECT * FROM payments WHERE order_id = ? ORDER BY id DESC");
    $pStmt->execute([$ord['id']]);
    $payments = $pStmt->fetchAll();
    ?>

    <div class="card" style="margin-bottom:24px;border:2px solid var(--border-subtle);">
      <div style="display:flex;justify-content:space-between;align-items:center;border-bottom:1px solid var(--border-subtle);padding-bottom:12px;margin-bottom:16px;">
        <div>
          <strong style="font-size:20px;color:var(--terracotta-dark);"><?= e($ord['order_code']) ?></strong>
          <span class="badge <?= status_class($ord['status']) ?>" style="margin-left:10px;"><?= status_label($ord['status']) ?></span>
        </div>
        <small class="muted">Dibuat: <?= date('d M Y H:i', strtotime($ord['created_at'])) ?></small>
      </div>

      <div class="grid grid-3" style="align-items:start;">
        <div>
          <h4 style="color:var(--espresso);margin-top:0;">👤 Data Customer</h4>
          <p style="margin:4px 0;"><strong>Nama:</strong> <?= e($ord['customer_name']) ?></p>
          <p style="margin:4px 0;"><strong>Telepon / WA:</strong> <a href="https://wa.me/<?= preg_replace('/[^0-9]/', '', $ord['phone']) ?>" target="_blank" style="color:green;font-weight:bold;">📱 <?= e($ord['phone']) ?></a></p>
          <p style="margin:4px 0;"><strong>Akun User:</strong> <?= e($ord['user_name']) ?> (<?= e($ord['user_email']) ?>)</p>
          <p style="margin:4px 0;"><strong>Patokan Lokasi / Catatan:</strong> <?= !empty($ord['delivery_note']) ? e($ord['delivery_note']) : '-' ?></p>
        </div>

        <div>
          <h4 style="color:var(--espresso);margin-top:0;">📦 Produk & Custom</h4>
          <p style="margin:4px 0;"><strong>Produk:</strong> <?= e($ord['product_name']) ?> (<?= e($ord['product_code']) ?>)</p>
          <?php 
            $vDisplayName = !empty($ord['variant_name']) ? $ord['variant_name'] : (!empty($customDesign['variant_name']) ? $customDesign['variant_name'] : '');
          ?>
          <?php if (!empty($vDisplayName)): ?>
            <p style="margin:4px 0;"><strong>Warna Variasi:</strong> <span class="badge badge-info"><?= e($vDisplayName) ?></span></p>
          <?php endif; ?>
          <p style="margin:4px 0;"><strong>Ukuran Standar:</strong> <?= e($ord['product_size']) ?></p>
          <p style="margin:4px 0;"><strong>Durasi Pengerjaan:</strong> <?= (int)$ord['production_duration'] ?> Hari</p>
          
          <div style="background:#f8f9fa;padding:10px 12px;border-radius:8px;margin-top:8px;border:1px solid #e9ecef;">
            <small class="muted" style="font-weight:bold;">Detail Kustomisasi & Item Tambahan:</small><br>
            <?php 
              $extraDetails = !empty($ord['extra_items_detail']) ? json_decode($ord['extra_items_detail'], true) : [];
              if (empty($extraDetails) && !empty($customDesign['extra_items_json'])) {
                  $extraDetails = json_decode($customDesign['extra_items_json'], true);
              }
            ?>
            <?php if ($customDesign || !empty($extraDetails)): ?>
              <small>🎨 <strong>Judul:</strong> <?= e($customDesign['title'] ?? 'Custom Pelaminan') ?></small><br>
              <small>📐 <strong>Ukuran:</strong> <?= e($customDesign['size'] ?? 'Medium') ?></small><br>
              <small style="color:green;">🛋️ <strong>Sofa Pengantin:</strong> Sudah Termasuk Dalam Paket</small><br>
              
              <div style="margin-top:6px;border-top:1px dashed #ccc;padding-top:6px;">
                <small><strong>Item Tambahan Custom:</strong></small>
                <?php if (!empty($extraDetails)): ?>
                  <div style="display:flex;flex-direction:column;gap:6px;margin-top:4px;">
                    <?php foreach ($extraDetails as $ex): 
                      $exImg = !empty($ex['image_url']) ? BASE_URL . '/uploads/items/' . e($ex['image_url']) : BASE_URL . '/assets/img/no-image.png';
                      $exQty = (int)($ex['quantity'] ?? 1);
                      $exPrice = (float)($ex['subtotal'] ?? ($ex['price'] * $exQty));
                    ?>
                      <div style="display:flex;align-items:center;gap:8px;background:#fff;padding:4px 8px;border-radius:6px;border:1px solid #ddd;">
                        <img src="<?= $exImg ?>" alt="<?= e($ex['name']) ?>" style="width:34px;height:34px;object-fit:contain;border-radius:4px;background:#f8f9fa;" onerror="this.src='<?= BASE_URL ?>/assets/img/no-image.png'">
                        <div style="font-size:12px;">
                          <strong><?= e($ex['name']) ?></strong> <?= $exQty > 1 ? '<span class="badge badge-info" style="font-size:10px;">' . $exQty . ' pcs</span>' : '' ?> <span class="muted">(<?= e($ex['category']) ?>)</span>
                          <?php if (!empty($ex['positions'])): ?>
                            <div style="font-size:11px;color:var(--muted);margin-top:2px;">
                              📍 <strong>Posisi Canvas:</strong>
                              <?php foreach ($ex['positions'] as $uPos): ?>
                                <span class="badge badge-muted" style="font-size:10px;margin-right:2px;">Unit <?= (int)$uPos['unit'] ?> (X:<?= (int)$uPos['x'] ?>, Y:<?= (int)$uPos['y'] ?>)</span>
                              <?php endforeach; ?>
                            </div>
                          <?php elseif (!empty($ex['position'])): ?>
                            <span class="badge badge-muted" style="font-size:10px;margin-left:4px;">📍 Posisi: X:<?= (int)$ex['position']['x'] ?>, Y:<?= (int)$ex['position']['y'] ?></span>
                          <?php endif; ?>
                          <br>
                          <span style="color:var(--terracotta-dark);font-weight:bold;">+ <?= rupiah($exPrice) ?></span>
                        </div>
                      </div>
                    <?php endforeach; ?>
                  </div>
                <?php else: ?>
                  <br><small class="muted">Tanpa Item Tambahan</small>
                <?php endif; ?>
              </div>

              <?php if (!empty($customDesign['notes'])): ?>
                <small style="display:block;margin-top:6px;">📝 <strong>Catatan:</strong> <?= e($customDesign['notes']) ?></small>
              <?php endif; ?>
            <?php else: ?>
              <small class="muted">Tidak ada kustomisasi khusus (menggunakan paket standar).</small>
            <?php endif; ?>
          </div>
        </div>

        <div>
          <h4 style="color:var(--espresso);margin-top:0;">💰 Transaksi & Lokasi</h4>
          <p style="margin:4px 0;"><strong>Total Biaya:</strong> <strong style="color:var(--terracotta-dark);"><?= rupiah($ord['total_amount']) ?></strong></p>
          <p style="margin:4px 0;"><strong>Sudah Dibayar:</strong> <strong style="color:green;"><?= rupiah($ord['paid_amount']) ?></strong></p>
          <p style="margin:4px 0;"><strong>Lokasi Acara:</strong> <?= e($ord['address']) ?>, <?= e($ord['district']) ?>, <?= e($ord['city']) ?></p>
          
          <div style="margin-top:8px;">
            <small class="muted">Bukti / Riwayat Pembayaran:</small>
            <?php foreach($payments as $p): ?>
              <div style="font-size:12px;background:#eef7ee;padding:4px 8px;border-radius:4px;margin-top:4px;">
                💳 <strong><?= strtoupper($p['type']) ?>:</strong> <?= rupiah($p['amount']) ?> (<?= e($p['method']) ?>) - <span style="color:green;font-weight:bold;"><?= e($p['status']) ?></span>
              </div>
            <?php endforeach; ?>
          </div>
        </div>
      </div>

      <div style="border-top:1px solid var(--border-subtle);margin-top:16px;padding-top:16px;display:flex;gap:12px;justify-content:flex-end;">
        <form method="post" onsubmit="return confirm('Apakah Anda yakin ingin MENYETUJUI pesanan ini dan memasukkannya ke Antrean Produksi?')">
          <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
          <input type="hidden" name="order_id" value="<?= (int)$ord['id'] ?>">
          <input type="hidden" name="action" value="approve">
          <button type="submit" class="btn btn-primary" style="background:#28a745;border-color:#28a745;font-weight:bold;">
            ✅ Terima Pesanan & Masuk Antrean
          </button>
        </form>

        <button type="button" class="btn btn-outline" style="color:#dc3545;border-color:#dc3545;" onclick="toggleRejectForm(<?= (int)$ord['id'] ?>)">
          ❌ Reject Pesanan
        </button>
      </div>

      <div id="reject-form-<?= (int)$ord['id'] ?>" style="display:none;margin-top:14px;padding:14px;background:#fff5f5;border:1px solid #f5c6cb;border-radius:8px;">
        <form method="post">
          <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
          <input type="hidden" name="order_id" value="<?= (int)$ord['id'] ?>">
          <input type="hidden" name="action" value="reject">
          <div class="form-group" style="margin-bottom:10px;">
            <label style="color:#721c24;">Alasan Penolakan Pesanan:</label>
            <input class="input" name="reject_reason" required placeholder="Contoh: Tanggal acara tidak dapat diakomodasi / Informasi kurang jelas">
          </div>
          <button type="submit" class="btn btn-sm" style="background:#dc3545;color:#fff;">Konfirmasi Reject Pesanan</button>
          <button type="button" class="btn btn-outline btn-sm" onclick="toggleRejectForm(<?= (int)$ord['id'] ?>)">Batal</button>
        </form>
      </div>
    </div>
  <?php endforeach; ?>
<?php endif; ?>

<script>
function toggleRejectForm(id) {
  const f = document.getElementById('reject-form-' + id);
  if (f) {
    f.style.display = f.style.display === 'none' ? 'block' : 'none';
  }
}
</script>

<?php include '../includes/admin_footer.php'; ?>
