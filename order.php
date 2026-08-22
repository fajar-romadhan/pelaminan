<?php
require_once 'config/database.php';
require_once 'config/helpers.php';
require_customer();

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT) ?: filter_input(INPUT_POST, 'product_id', FILTER_VALIDATE_INT);
if (!$id) {
    redirect(BASE_URL . '/gallery.php');
}

$stmt = $pdo->prepare('SELECT p.*, c.name AS category_name FROM products p JOIN categories c ON c.id=p.category_id WHERE p.id=? AND p.status=\'Aktif\' LIMIT 1');
$stmt->execute([$id]);
$product = $stmt->fetch();

if (!$product) {
    set_flash('danger', 'Produk tidak ditemukan atau tidak aktif.');
    redirect(BASE_URL . '/gallery.php');
}



$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
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
        $total = (float)$product['price'] + $shippingCost;
        $dp = $total * 0.5;
        $code = '#ORD-' . date('ymd') . '-' . strtoupper(substr(bin2hex(random_bytes(3)), 0, 5));

        $variantId = filter_input(INPUT_POST, 'variant_id', FILTER_VALIDATE_INT) ?: filter_input(INPUT_GET, 'variant_id', FILTER_VALIDATE_INT);
        $variantName = trim($_POST['variant_name'] ?? ($_GET['variant_name'] ?? ''));

        $pdo->beginTransaction();
        try {
            $insStmt = $pdo->prepare('
                INSERT INTO orders(
                    order_code, user_id, receiver_name, receiver_phone, delivery_address, delivery_note,
                    delivery_latitude, delivery_longitude, delivery_map_address, product_id, variant_id, variant_name, 
                    customer_name, phone, address, city, district, pickup_method, shipping_rate_id, shipping_cost, 
                    total_amount, dp_amount, paid_amount, status, event_date
                ) VALUES(?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)
            ');

            $insStmt->execute([
                $code,
                current_user()['id'],
                $customerName,
                $phone,
                $address,
                $deliveryNote,
                $deliveryLatitude ?: null,
                $deliveryLongitude ?: null,
                $deliveryMapAddress ?: null,
                $id,
                $variantId ?: null,
                $variantName ?: null,
                $customerName,
                $phone,
                $address,
                $city,
                $district,
                $pickupMethod,
                $validShippingRateId,
                $shippingCost,
                $total,
                $dp,
                0,
                'WAITING_PAYMENT',
                $eventDate
            ]);

            $orderId = $pdo->lastInsertId();
            log_order_status_change($pdo, (int)$orderId, null, 'WAITING_PAYMENT', current_user()['id']);
            send_admin_notification($pdo, (int)$orderId, '🛒 Pesanan Baru Masuk', "Pesanan baru {$code} dari {$customerName} (Total: " . rupiah($total) . ")");
            $pdo->commit();

            set_flash('success', 'Pesanan berhasil dibuat. Silakan lakukan pembayaran DP.');
            redirect(BASE_URL . '/payment.php?order_id=' . $orderId . '&type=dp');
        } catch (Throwable $e) {
            $pdo->rollBack();
            set_flash('danger', 'Gagal memproses pesanan: ' . $e->getMessage());
        }
    }
}

$pageTitle = 'Form Pemesanan';
$active = 'gallery';
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
      <a href="<?= BASE_URL ?>/product.php?id=<?= (int)$id ?>" class="btn-back-nav">
        <span class="icon-arrow">←</span> Kembali ke Detail Produk
      </a>
    </div>
    <h1>Form Pemesanan Direct</h1>
    <p><?= e($product['name']) ?></p>
  </div>
</div>
<main class="container" style="padding-top:30px">
  <form method="post" class="grid grid-3" style="align-items:start">
    <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
    <input type="hidden" name="product_id" value="<?= (int)$id ?>">
    <input type="hidden" name="variant_id" value="<?= e($_REQUEST['variant_id'] ?? '') ?>">
    <input type="hidden" name="variant_name" value="<?= e($_REQUEST['variant_name'] ?? '') ?>">

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
          <p class="muted" style="font-size:13px;margin-top:4px;">Ongkos kirim mengikuti kota & kecamatan yang dipilih.</p>
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

    <aside class="card order-summary">
      <h3 style="color:var(--terracotta-dark)">Ringkasan Pesanan</h3>
      <div class="image-placeholder" style="min-height:130px;margin-bottom:14px"><div>🏛️</div></div>
      <div class="summary-line" style="margin-top:14px;">
        <span>Harga Produk</span>
        <strong id="displaySubtotal" data-value="<?= (float)$product['price'] ?>"><?= rupiah($product['price']) ?></strong>
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
        <strong id="displayGrandTotal" style="color:var(--terracotta-dark);font-size:20px;"><?= rupiah($product['price']) ?></strong>
      </div>

      <div class="summary-line" style="margin-top:6px;font-size:13px;color:var(--muted);">
        <span>Minimal DP (50%)</span>
        <strong id="displayDpAmount"><?= rupiah($product['price'] * 0.5) ?></strong>
      </div>
      <button class="btn btn-primary btn-block" type="submit" style="margin-top:18px;">Lanjutkan Pemesanan →</button>
    </aside>
  </form>
</main>

<script src="<?= BASE_URL ?>/assets/js/checkout-shipping.js"></script>
<script src="<?= BASE_URL ?>/assets/js/delivery-map.js?v=<?= time() ?>"></script>


<?php include 'includes/footer.php'; ?>
