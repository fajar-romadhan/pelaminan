<?php
require_once 'config/database.php';
require_once 'config/helpers.php';
require_customer();

$userId = current_user()['id'];

// Get selected cart IDs if specified
$selectedCartIds = $_REQUEST['cart_ids'] ?? [];
if (is_string($selectedCartIds)) {
    $selectedCartIds = explode(',', $selectedCartIds);
}
$selectedCartIds = array_filter(array_map('intval', (array)$selectedCartIds));

if (!empty($selectedCartIds)) {
    $placeholders = implode(',', array_fill(0, count($selectedCartIds), '?'));
    $cartStmt = $pdo->prepare("
        SELECT c.id AS cart_id, c.quantity, c.design_id, c.variant_id, c.variant_name, p.id AS product_id, p.name, p.price,
               d.extra_items_json, d.extra_price, d.title AS design_title, d.variant_id AS design_variant_id, d.variant_name AS design_variant_name
        FROM carts c
        JOIN products p ON c.product_id = p.id
        LEFT JOIN editor_designs d ON c.design_id = d.id
        WHERE c.user_id = ? AND p.status = 'Aktif' AND c.id IN ($placeholders)
    ");
    $cartStmt->execute(array_merge([$userId], $selectedCartIds));
} else {
    // If no cart_ids specified, fetch all active cart items
    $cartStmt = $pdo->prepare("
        SELECT c.id AS cart_id, c.quantity, c.design_id, c.variant_id, c.variant_name, p.id AS product_id, p.name, p.price,
               d.extra_items_json, d.extra_price, d.title AS design_title, d.variant_id AS design_variant_id, d.variant_name AS design_variant_name
        FROM carts c
        JOIN products p ON c.product_id = p.id
        LEFT JOIN editor_designs d ON c.design_id = d.id
        WHERE c.user_id = ? AND p.status = 'Aktif'
    ");
    $cartStmt->execute([$userId]);
}

$carts = $cartStmt->fetchAll();

if (empty($carts)) {
    set_flash('warning', 'Tidak ada produk yang dipilih untuk di-checkout. Silakan pilih produk di keranjang Anda terlebih dahulu.');
    redirect(BASE_URL . '/customers/cart.php');
}

// Update quantity from POST if sent from cart form
if (isset($_POST['quantities']) && is_array($_POST['quantities'])) {
    foreach ($carts as &$item) {
        $cId = (int)$item['cart_id'];
        if (isset($_POST['quantities'][$cId])) {
            $newQty = max(1, (int)$_POST['quantities'][$cId]);
            $item['quantity'] = $newQty;
            $upd = $pdo->prepare("UPDATE carts SET quantity = ? WHERE id = ? AND user_id = ?");
            $upd->execute([$newQty, $cId, $userId]);
        }
    }
    unset($item);
}

// Subtotal calculation for selected items only & check for preselected shipping meta
$subtotal = 0;
$preselectedCity = $_POST['shipping_city'] ?? '';
$preselectedRateId = $_POST['shipping_rate_id'] ?? '';

foreach ($carts as $item) {
    $unitPrice = (float)$item['price'] + (float)($item['extra_price'] ?? 0);
    $subtotal += $unitPrice * (int)$item['quantity'];

    if (empty($preselectedCity) && empty($preselectedRateId) && !empty($item['extra_items_json'])) {
        $exList = json_decode($item['extra_items_json'], true);
        if (is_array($exList)) {
            foreach ($exList as $exObj) {
                if (!empty($exObj['is_shipping_meta'])) {
                    $preselectedCity = $exObj['city'] ?? '';
                    $preselectedRateId = $exObj['rate_id'] ?? '';
                    break;
                }
            }
        }
    }
}

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['customer_name'])) {
    verify_csrf_token($_POST['csrf_token'] ?? null);

    $customerName = trim($_POST['customer_name'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $deliveryLatitude = filter_input(INPUT_POST, 'delivery_latitude', FILTER_VALIDATE_FLOAT);
    $deliveryLongitude = filter_input(INPUT_POST, 'delivery_longitude', FILTER_VALIDATE_FLOAT);
    $deliveryMapAddress = trim($_POST['delivery_map_address'] ?? '');
    $deliveryNote = trim($_POST['delivery_note'] ?? '');
    $pickupMethod = trim($_POST['pickup_method'] ?? 'diantar');

    $address = !empty($deliveryMapAddress) ? $deliveryMapAddress : ($pickupMethod === 'diambil' ? 'Diambil Sendiri (Kantor Distributor)' : '');

    if (empty($customerName) || empty($phone)) {
        $errors[] = 'Silakan lengkapi nama dan nomor telepon.';
    }

    $allowedPickupMethods = ['diantar', 'diambil'];
    if (!in_array($pickupMethod, $allowedPickupMethods, true)) {
        $errors[] = 'Metode penerimaan tidak valid.';
    }

    $shippingCost = 0;
    $city = '';
    $district = '';
    $validShippingRateId = null;

    if ($pickupMethod === 'diantar') {
        $city = 'Palembang';
        $district = 'Sumatera Selatan';
        $validShippingRateId = null;

        if ($deliveryLatitude === false || $deliveryLongitude === false || empty($deliveryMapAddress)) {
            $errors[] = 'Silakan tentukan titik lokasi acara pada peta dan pastikan alamat peta terisi.';
        } else {
            if ($deliveryLatitude < -4.95 || $deliveryLatitude > -1.60 || $deliveryLongitude < 102.00 || $deliveryLongitude > 106.10) {
                $errors[] = 'Maaf, layanan pengiriman armada Distributor Pelaminan Family saat ini hanya melayani wilayah Sumatera Selatan. Silakan tentukan lokasi di area Sumatera Selatan.';
            } else {
                $distKm = calculate_haversine_km(STORE_LAT, STORE_LNG, (float)$deliveryLatitude, (float)$deliveryLongitude);
                $shippingCost = calculate_shipping_cost_km($distKm);
            }
        }
    } else { // 'diambil'
        $shippingCost = 0;
        $validShippingRateId = null;
        $city = 'Palembang';
        $district = 'Diambil Sendiri';
    }

    if (!empty($errors)) {
        set_flash('danger', implode('<br>', $errors));
    } else {
        // Process orders inside a PDO transaction
        $pdo->beginTransaction();
        try {
            $createdOrderIds = [];
            
            foreach ($carts as $item) {
                $unitPrice = (float)$item['price'] + (float)($item['extra_price'] ?? 0);
                $itemTotal = ($unitPrice * (int)$item['quantity']) + $shippingCost;
                $dpAmount = $itemTotal * 0.5;
                $code = '#ORD-' . date('ymd') . '-' . strtoupper(substr(bin2hex(random_bytes(3)), 0, 5));

                $vId = !empty($item['variant_id']) ? $item['variant_id'] : (!empty($item['design_variant_id']) ? $item['design_variant_id'] : null);
                $vName = !empty($item['variant_name']) ? $item['variant_name'] : (!empty($item['design_variant_name']) ? $item['design_variant_name'] : null);

                $insStmt = $pdo->prepare('
                    INSERT INTO orders(
                        order_code, user_id, receiver_name, receiver_phone, delivery_address, delivery_note,
                        delivery_latitude, delivery_longitude, delivery_map_address, product_id, variant_id, variant_name, 
                        design_id, extra_items_detail, customer_name, 
                        phone, address, city, district, pickup_method, shipping_rate_id, shipping_cost, total_amount, 
                        dp_amount, paid_amount, status, event_date
                    ) VALUES(?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)
                ');

                $insStmt->execute([
                    $code,
                    $userId,
                    $customerName,
                    $phone,
                    $address,
                    $deliveryNote,
                    $deliveryLatitude ?: null,
                    $deliveryLongitude ?: null,
                    $deliveryMapAddress ?: null,
                    $item['product_id'],
                    $vId,
                    $vName,
                    $item['design_id'] ?: null,
                    $item['extra_items_json'] ?: null,
                    $customerName,
                    $phone,
                    $address,
                    $city,
                    $district,
                    $pickupMethod,
                    $validShippingRateId,
                    $shippingCost,
                    $itemTotal,
                    $dpAmount,
                    0,
                    'WAITING_PAYMENT',
                    $eventDate
                ]);

                $orderId = $pdo->lastInsertId();
                $createdOrderIds[] = $orderId;
                log_order_status_change($pdo, (int)$orderId, null, 'WAITING_PAYMENT', $userId);
                send_admin_notification($pdo, (int)$orderId, '🛒 Pesanan Baru Masuk', "Pesanan baru {$code} dari {$customerName} (Total: " . rupiah($totalAmount) . ")");
            }

            // Clear ONLY processed cart items for current user
            $cartIdsToDelete = array_column($carts, 'cart_id');
            if (!empty($cartIdsToDelete)) {
                $delPlaceholders = implode(',', array_fill(0, count($cartIdsToDelete), '?'));
                $clearCart = $pdo->prepare("DELETE FROM carts WHERE user_id = ? AND id IN ($delPlaceholders)");
                $clearCart->execute(array_merge([$userId], $cartIdsToDelete));
            }

            $pdo->commit();

            set_flash('success', 'Pesanan Anda berhasil dibuat! Silakan lakukan pembayaran DP.');
            
            $firstOrderId = $createdOrderIds[0] ?? 0;
            redirect(BASE_URL . '/payment.php?order_id=' . $firstOrderId . '&type=dp');
        } catch (Throwable $e) {
            $pdo->rollBack();
            set_flash('danger', 'Gagal memproses pesanan: ' . $e->getMessage());
        }
    }
}

$pageTitle = 'Checkout Pesanan';
$active = 'cart';
include 'includes/header.php';
?>

<script>
window.SHIPPING_RATES = <?= json_encode(
    $shippingRates,
    JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT
) ?>;
</script>

<div class="page-head">
  <div class="container">
    <div style="margin-bottom:14px;">
      <a href="<?= BASE_URL ?>/customers/cart.php" class="btn-back-nav">
        <span class="icon-arrow">←</span> Kembali ke Keranjang
      </a>
    </div>
    <h1>Checkout Pesanan</h1>
    <p>Lengkapi informasi pengiriman untuk menyelesaikan pesanan Anda (<?= count($carts) ?> produk terpilih)</p>
  </div>
</div>

<main class="container" style="padding-top:30px">
  <form method="post" class="grid grid-3" style="align-items:start">
    <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
    <?php foreach ($carts as $item): ?>
      <input type="hidden" name="cart_ids[]" value="<?= (int)$item['cart_id'] ?>">
      <input type="hidden" name="quantities[<?= (int)$item['cart_id'] ?>]" value="<?= (int)$item['quantity'] ?>">
    <?php endforeach; ?>

    <div class="card" style="grid-column:span 2">
      <h3 style="color:var(--terracotta-dark)">Data Pelanggan</h3>
      
      <div class="form-row">
        <div class="form-group">
          <label>Nama Lengkap</label>
          <input class="input" name="customer_name" value="<?= e($_POST['customer_name'] ?? current_user()['name']) ?>" required>
        </div>
        <div class="form-group">
          <label>Nomor Telepon / WhatsApp</label>
          <input class="input" name="phone" value="<?= e($_POST['phone'] ?? current_user()['phone'] ?? '') ?>" required placeholder="08xxxxxxxxxx">
        </div>
      </div>

      <h3 style="color:var(--terracotta-dark);margin-top:22px">Metode Penerimaan</h3>
      <div class="form-row">
        <label class="card" style="cursor:pointer;flex:1;">
          <input type="radio" name="pickup_method" value="diantar" <?= (($_POST['pickup_method'] ?? 'diantar') === 'diantar') ? 'checked' : '' ?>> 
          <strong>Diantarkan</strong>
          <p class="muted" style="font-size:13px;margin-top:4px;">Ongkos kirim dihitung berdasarkan kota & kecamatan.</p>
        </label>
        <label class="card" style="cursor:pointer;flex:1;">
          <input type="radio" name="pickup_method" value="diambil" <?= (($_POST['pickup_method'] ?? '') === 'diambil') ? 'checked' : '' ?>> 
          <strong>Diambil sendiri</strong>
          <p class="muted" style="font-size:13px;margin-top:4px;">Bebas ongkos kirim (Rp0).</p>
        </label>
      </div>

      <div id="map-picker-container" style="margin-top:22px">
        <div class="map-header">
          <h3 style="color:var(--terracotta-dark);margin:0">Titik Lokasi Pengiriman Peta</h3>
          <button type="button" id="btn-use-my-location" class="btn btn-outline btn-sm">📍 Gunakan Lokasi Saya</button>
        </div>
        <p class="muted" style="font-size:13px;margin-bottom:10px;line-height:1.5;">💡 <strong>Pesan dari mana saja:</strong> Ketik nama perumahan/jalan rumah Anda pada kolom pencari di bawah, atau geser marker ke titik rumah Anda di peta. *(Jika Anda sedang berada di rumah, Anda bisa langsung mengklik 📍 <strong>Gunakan Lokasi Saya</strong>)*.</p>
        
        <div id="map-search-wrapper" class="form-group" style="margin-bottom:14px;position:relative;">
          <label style="font-weight:700;color:var(--espresso);font-size:13.5px;">🔍 Cari Alamat Pengiriman</label>
          <div class="map-search-input-group" style="display:flex;gap:8px;margin-top:4px;width:100%;">
            <input type="text" id="map-search-input" class="input" placeholder="Ketik nama jalan / perumahan / toko (misal: Perumahan Kencana Indah Palembang)..." style="flex:1;min-width:0;background:#ffffff;" autocomplete="off">
            <button type="button" id="btn-search-map" class="btn btn-primary btn-sm" style="white-space:nowrap;border-radius:12px;padding:0 20px;font-weight:700;flex-shrink:0;">🔍 Cari Alamat</button>
          </div>
          <div id="map-search-autocomplete" class="map-autocomplete-dropdown" style="position:absolute;top:calc(100% + 4px);left:0;right:0;width:100%;z-index:999999;background:#ffffff;border-radius:16px;border:1px solid rgba(216,133,78,0.35);box-shadow:0 16px 36px rgba(54,34,23,0.16);max-height:280px;overflow-y:auto;padding:6px;display:none;box-sizing:border-box;"></div>
        </div>

        <div id="delivery-map"></div>
        
        <input type="hidden" name="delivery_latitude" id="delivery_latitude" value="<?= e($_POST['delivery_latitude'] ?? '') ?>">
        <input type="hidden" name="delivery_longitude" id="delivery_longitude" value="<?= e($_POST['delivery_longitude'] ?? '') ?>">
        
        <div class="form-group">
          <label>Alamat Hasil Peta</label>
          <input class="input" name="delivery_map_address" id="delivery_map_address" value="<?= e($_POST['delivery_map_address'] ?? '') ?>" placeholder="Klik titik pada peta untuk mendapatkan alamat otomatis" readonly required>
        </div>

        <input type="hidden" name="delivery_distance_km" id="delivery_distance_km" value="<?= e($_POST['delivery_distance_km'] ?? '0') ?>">
        <input type="hidden" name="shipping_cost_input" id="shipping_cost_input" value="<?= e($_POST['shipping_cost_input'] ?? '0') ?>">

        <div class="form-group">
          <label style="font-weight:700;color:var(--espresso);">📝 Patokan Lokasi (Landmark / Catatan Pengiriman)</label>
          <input class="input" name="delivery_note" id="delivery_note" value="<?= e($_POST['delivery_note'] ?? '') ?>" placeholder="Contoh: Depan Masjid Al-Ikhlas, Rumah Pagar Hijau, Seberang Lapangan">
        </div>
      </div>
    </div>

    <aside class="card">
      <h3 style="color:var(--terracotta-dark)">Ringkasan Pesanan</h3>
      
      <?php foreach ($carts as $item): 
        $unitPrice = (float)$item['price'] + (float)($item['extra_price'] ?? 0);
        $itemSub = $unitPrice * (int)$item['quantity'];
        $extraDetails = !empty($item['extra_items_json']) ? json_decode($item['extra_items_json'], true) : [];
      ?>
        <div style="border-bottom:1px solid var(--border-subtle);padding-bottom:10px;margin-bottom:10px;">
          <strong><?= e($item['name']) ?></strong><br>
          <small class="muted"><?= (int)$item['quantity'] ?>x @ <?= rupiah($unitPrice) ?></small>
          <?php if (!empty($extraDetails)): ?>
            <div style="font-size:11px;color:var(--espresso);margin-top:4px;">
              <strong>Item Tambahan:</strong>
              <?php foreach($extraDetails as $ex): 
                if (!empty($ex['is_shipping_meta'])) continue;
              ?>
                <div>• <?= e($ex['name'] ?? '') ?> (+<?= rupiah($ex['price'] ?? 0) ?>)</div>
              <?php endforeach; ?>
            </div>
          <?php endif; ?>
        </div>
      <?php endforeach; ?>

      <div class="summary-line">
        <span>Subtotal Produk & Decor</span>
        <strong id="displaySubtotal" data-value="<?= (float)$subtotal ?>"><?= rupiah($subtotal) ?></strong>
      </div>
      
      <div class="summary-line" style="margin-top:6px;">
        <span>Biaya Pengantaran</span>
        <strong id="displayShippingCost" style="color:var(--terracotta-dark);">Rp 0</strong>
      </div>

      <!-- Distance & Realtime Shipping Badge Inside Summary -->
      <div id="shipping-distance-badge" style="background:#fff9f0;border:1.5px solid var(--terracotta);border-radius:12px;padding:10px 12px;margin:12px 0;">
        <div style="display:flex;justify-content:space-between;align-items:center;font-size:12.5px;">
          <span style="color:var(--espresso);">📏 Jarak Pengiriman:</span>
          <strong id="distance-km-display" style="color:var(--terracotta-dark);font-weight:700;">0.0 km</strong>
        </div>
        <div style="display:flex;justify-content:space-between;align-items:center;font-size:12.5px;margin-top:4px;">
          <span style="color:var(--espresso);">🚚 Tarif Ongkir:</span>
          <strong id="shipping-cost-display" style="color:#27ae60;font-weight:700;">Rp 0 (GRATIS)</strong>
        </div>
      </div>

      <div class="summary-line summary-total" style="margin-top:14px;border-top:1.5px solid var(--border-subtle);padding-top:10px;">
        <span>Total Akhir</span>
        <strong id="displayGrandTotal" style="color:var(--terracotta-dark);font-size:20px;"><?= rupiah($subtotal) ?></strong>
      </div>

      <div class="summary-line" style="margin-top:6px;font-size:13px;color:var(--muted);">
        <span>Minimal DP (50%)</span>
        <strong id="displayDpAmount"><?= rupiah($subtotal * 0.5) ?></strong>
      </div>

      <button class="btn btn-primary btn-block" type="submit" style="margin-top:18px;">
        Buat Pesanan & Bayar DP →
      </button>
    </aside>
  </form>
</main>

<script src="<?= BASE_URL ?>/assets/js/checkout-shipping.js"></script>
<script src="<?= BASE_URL ?>/assets/js/delivery-map.js?v=<?= time() ?>"></script>


<?php include 'includes/footer.php'; ?>
