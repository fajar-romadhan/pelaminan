<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/helpers.php';
require_customer();

$pageTitle = "Keranjang Saya";
$active = "cart";

$userId = current_user()['id'];

$stmt = $pdo->prepare("
    SELECT 
        c.id AS cart_id,
        c.quantity,
        c.design_id,
        c.variant_id,
        c.variant_name,
        p.id AS product_id,
        p.name,
        p.code,
        p.price,
        p.image_url,
        p.status,
        pv.image AS variant_image,
        d.title AS design_title,
        d.extra_items_json,
        d.extra_price,
        d.variant_name AS design_variant_name,
        d.flower,
        d.kotak
    FROM carts c
    JOIN products p ON c.product_id = p.id
    LEFT JOIN product_variants pv ON c.variant_id = pv.id
    LEFT JOIN editor_designs d ON c.design_id = d.id
    WHERE c.user_id = ?
    ORDER BY c.id DESC
");
$stmt->execute([$userId]);
$carts = $stmt->fetchAll();

$grandTotal = 0;
foreach ($carts as $item) {
    $unitPrice = (float)$item['price'] + (float)($item['extra_price'] ?? 0);
    $grandTotal += $unitPrice * (int)$item['quantity'];
}

include __DIR__ . "/../includes/header.php";
?>

<div class="page-head">
  <div class="container">
    <h1>Keranjang Belanja</h1>
    <p>Kelola jumlah produk dan pilih item yang ingin Anda checkout</p>
  </div>
</div>

<main class="container" style="padding-top:30px">
  <?php if (empty($carts)): ?>
    <div class="card" style="text-align:center;padding:60px;">
      <div class="icon-big">🛒</div>
      <h3>Keranjang Anda Kosong</h3>
      <p class="muted">Anda belum menambahkan produk apa pun ke keranjang.</p>
      <a href="<?= BASE_URL ?>/gallery.php" class="btn btn-primary" style="margin-top:14px;">Lihat Galeri Produk</a>
    </div>
  <?php else: ?>
    <form method="post" action="<?= BASE_URL ?>/checkout.php" id="cart-checkout-form">
      <div class="grid grid-3" style="align-items:start">
        <div class="card" style="grid-column:span 2">
          <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:14px;">
            <h3 style="color:var(--espresso);margin:0">Daftar Item</h3>
            <label style="font-size:13px;font-weight:700;cursor:pointer;display:flex;align-items:center;gap:6px;color:var(--terracotta-dark);">
              <input type="checkbox" id="check-all" checked style="width:16px;height:16px;cursor:pointer;">
              <span>Pilih Semua (<?= count($carts) ?>)</span>
            </label>
          </div>
          <div class="table-wrap">
            <table class="table">
              <thead>
                <tr>
                  <th style="width:40px;text-align:center;">Pilih</th>
                  <th>Produk</th>
                  <th>Harga Satuan</th>
                  <th style="text-align:center;">Jumlah</th>
                  <th>Subtotal</th>
                  <th>Aksi</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($carts as $item): 
                  $basePrice = (float)$item['price'];
                  $extraPrice = (float)($item['extra_price'] ?? 0);
                  $unitPrice = $basePrice + $extraPrice;
                  $qty = (int)$item['quantity'];
                  $subtotal = $unitPrice * $qty;
                  $imagePath = !empty($item['variant_image']) ? BASE_URL . '/uploads/products/variants/' . e($item['variant_image']) : (!empty($item['image_url']) ? BASE_URL . '/uploads/products/' . e($item['image_url']) : BASE_URL . '/assets/img/no-image.png');
                  $variantDisplayName = !empty($item['variant_name']) ? $item['variant_name'] : (!empty($item['design_variant_name']) ? $item['design_variant_name'] : '');

                  $extraDetails = !empty($item['extra_items_json']) ? json_decode($item['extra_items_json'], true) : [];
                ?>
                  <tr>
                    <td style="text-align:center;">
                      <input type="checkbox" name="cart_ids[]" value="<?= (int)$item['cart_id'] ?>" class="cart-item-checkbox" data-subtotal="<?= $subtotal ?>" checked style="width:18px;height:18px;cursor:pointer;">
                    </td>
                    <td>
                      <div style="display:flex;align-items:flex-start;gap:12px">
                        <img src="<?= $imagePath ?>" alt="<?= e($item['name']) ?>" style="width:65px;height:65px;object-fit:contain;background:#f8f9fa;border:1px solid #e9ecef;border-radius:8px;" onerror="this.src='<?= BASE_URL ?>/assets/img/no-image.png'">
                        <div>
                          <strong><?= e($item['name']) ?></strong>
                          <?php if (!empty($variantDisplayName)): ?>
                            <span class="badge badge-info" style="font-size:11px;margin-left:4px;">Warna: <?= e($variantDisplayName) ?></span>
                          <?php endif; ?>
                          <br><small class="muted">Kode: <?= e($item['code']) ?></small>
                          
                          <?php if (!empty($item['design_id'])): ?>
                            <div style="background:#f8f9fa;border:1px solid #e9ecef;border-radius:6px;padding:6px 10px;margin-top:6px;font-size:12px;">
                              <span style="color:var(--terracotta-dark);font-weight:bold;">🎨 <?= e($item['design_title'] ?: 'Custom Design') ?></span><br>
                              <span style="color:green;">✓ Include: Backdrop & Sofa Pengantin</span><br>
                              <?php if (!empty($extraDetails)): ?>
                                <strong>Item Tambahan:</strong>
                                 <ul style="margin:2px 0 0 16px;padding:0;">
                                   <?php foreach ($extraDetails as $ex): 
                                     $exQty = (int)($ex['quantity'] ?? 1);
                                     $exPrice = (float)($ex['subtotal'] ?? ($ex['price'] * $exQty));
                                   ?>
                                     <li><?= e($ex['name']) ?><?= $exQty > 1 ? ' <strong>(' . $exQty . ' pcs)</strong>' : '' ?> (+<?= rupiah($exPrice) ?>)</li>
                                   <?php endforeach; ?>
                                 </ul>
                              <?php else: ?>
                                <span class="muted">Item Tambahan: Tanpa Tambahan</span>
                              <?php endif; ?>
                            </div>
                          <?php endif; ?>
                        </div>
                      </div>
                    </td>
                    <td>
                      <strong><?= rupiah($unitPrice) ?></strong>
                      <?php if ($extraPrice > 0): ?>
                        <br><small class="muted">(Dasar: <?= rupiah($basePrice) ?> + Extra: <?= rupiah($extraPrice) ?>)</small>
                      <?php endif; ?>
                    </td>
                    <td style="text-align:center;">
                      <div class="qty-control" style="display:inline-flex;align-items:center;border:1.5px solid var(--border);border-radius:var(--radius-pill);background:#fff;padding:2px 8px;">
                        <button type="button" class="btn-qty btn-minus" data-id="<?= (int)$item['cart_id'] ?>" title="Kurangi jumlah" style="border:0;background:none;width:24px;height:24px;font-size:16px;font-weight:800;cursor:pointer;color:var(--espresso);line-height:1;">-</button>
                        <input type="number" name="quantities[<?= (int)$item['cart_id'] ?>]" value="<?= $qty ?>" min="1" max="999" class="input-qty" data-id="<?= (int)$item['cart_id'] ?>" data-price="<?= $unitPrice ?>" style="width:40px;text-align:center;border:0;outline:none;font-weight:700;font-size:14px;background:transparent;">
                        <button type="button" class="btn-qty btn-plus" data-id="<?= (int)$item['cart_id'] ?>" title="Tambah jumlah" style="border:0;background:none;width:24px;height:24px;font-size:16px;font-weight:800;cursor:pointer;color:var(--espresso);line-height:1;">+</button>
                      </div>
                    </td>
                    <td><strong id="subtotal-<?= (int)$item['cart_id'] ?>"><?= rupiah($subtotal) ?></strong></td>
                    <td>
                      <button type="button" class="btn btn-sm btn-delete-cart" style="background:#dc3545;color:#fff;" data-id="<?= (int)$item['cart_id'] ?>">
                        Hapus
                      </button>
                    </td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        </div>

        <aside class="card">
          <h3 style="color:var(--espresso);">Ringkasan Keranjang</h3>
          <div class="summary-line">
            <span>Total Items</span>
            <strong id="selected-count"><?= count($carts) ?> produk terpilih</strong>
          </div>
          <div class="summary-line summary-total" style="margin-top:14px;">
            <span>Subtotal</span>
            <span id="selected-subtotal"><?= rupiah($grandTotal) ?></span>
          </div>
          <button type="submit" id="btn-checkout" class="btn btn-primary btn-block" style="margin-top:18px;">
            Lanjut Checkout →
          </button>
        </aside>
      </div>
    </form>
  <?php endif; ?>
</main>

<form id="delete-single-cart-form" method="post" action="<?= BASE_URL ?>/customers/remove_cart.php">
  <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
  <input type="hidden" name="id" id="delete-cart-id" value="">
</form>

<script>
document.addEventListener('DOMContentLoaded', function () {
  const checkAll = document.getElementById('check-all');
  const itemCheckboxes = document.querySelectorAll('.cart-item-checkbox');
  const summaryCount = document.getElementById('selected-count');
  const summarySubtotal = document.getElementById('selected-subtotal');
  const btnCheckout = document.getElementById('btn-checkout');
  const cartForm = document.getElementById('cart-checkout-form');

  function calculateSummary() {
    let total = 0;
    let count = 0;
    itemCheckboxes.forEach(cb => {
      if (cb.checked) {
        count++;
        total += parseFloat(cb.getAttribute('data-subtotal')) || 0;
      }
    });

    if (summaryCount) {
      summaryCount.textContent = count + ' produk terpilih';
    }
    if (summarySubtotal) {
      summarySubtotal.textContent = 'Rp ' + total.toLocaleString('id-ID');
    }
    if (btnCheckout) {
      if (count === 0) {
        btnCheckout.disabled = true;
        btnCheckout.style.opacity = '0.5';
        btnCheckout.style.cursor = 'not-allowed';
      } else {
        btnCheckout.disabled = false;
        btnCheckout.style.opacity = '1';
        btnCheckout.style.cursor = 'pointer';
      }
    }
  }

  function updateRowSubtotal(cartId, qty, price) {
    const subtotal = price * qty;
    const subtotalElem = document.getElementById('subtotal-' + cartId);
    const checkboxElem = document.querySelector(`.cart-item-checkbox[value="${cartId}"]`);

    if (subtotalElem) {
      subtotalElem.textContent = 'Rp ' + subtotal.toLocaleString('id-ID');
    }
    if (checkboxElem) {
      checkboxElem.setAttribute('data-subtotal', subtotal);
    }
    calculateSummary();

    const formData = new FormData();
    formData.append('cart_id', cartId);
    formData.append('quantity', qty);

    fetch('<?= BASE_URL ?>/customers/update_cart.php', {
      method: 'POST',
      body: formData
    })
    .then(res => res.json())
    .then(data => {
      if (!data.success) {
        console.warn('Update quantity warning:', data.message);
      }
    })
    .catch(err => console.error('AJAX error:', err));
  }

  if (checkAll) {
    checkAll.addEventListener('change', function () {
      itemCheckboxes.forEach(cb => {
        cb.checked = checkAll.checked;
      });
      calculateSummary();
    });
  }

  itemCheckboxes.forEach(cb => {
    cb.addEventListener('change', function () {
      const allChecked = Array.from(itemCheckboxes).every(c => c.checked);
      if (checkAll) checkAll.checked = allChecked;
      calculateSummary();
    });
  });

  document.querySelectorAll('.btn-plus').forEach(btn => {
    btn.addEventListener('click', function () {
      const id = this.getAttribute('data-id');
      const input = document.querySelector(`.input-qty[data-id="${id}"]`);
      if (input) {
        let val = parseInt(input.value) || 1;
        val++;
        input.value = val;
        const price = parseFloat(input.getAttribute('data-price')) || 0;
        updateRowSubtotal(id, val, price);
      }
    });
  });

  document.querySelectorAll('.btn-minus').forEach(btn => {
    btn.addEventListener('click', function () {
      const id = this.getAttribute('data-id');
      const input = document.querySelector(`.input-qty[data-id="${id}"]`);
      if (input) {
        let val = parseInt(input.value) || 1;
        if (val > 1) {
          val--;
          input.value = val;
          const price = parseFloat(input.getAttribute('data-price')) || 0;
          updateRowSubtotal(id, val, price);
        }
      }
    });
  });

  document.querySelectorAll('.input-qty').forEach(input => {
    input.addEventListener('change', function () {
      const id = this.getAttribute('data-id');
      let val = parseInt(this.value);
      if (isNaN(val) || val < 1) {
        val = 1;
        this.value = 1;
      }
      const price = parseFloat(this.getAttribute('data-price')) || 0;
      updateRowSubtotal(id, val, price);
    });
  });

  if (cartForm) {
    cartForm.addEventListener('submit', function (e) {
      const selectedCount = Array.from(itemCheckboxes).filter(cb => cb.checked).length;
      if (selectedCount === 0) {
        e.preventDefault();
        alert('Silakan pilih setidaknya satu produk yang ingin di-checkout.');
        return false;
      }
    });
  }

  document.querySelectorAll('.btn-delete-cart').forEach(btn => {
    btn.addEventListener('click', function () {
      if (confirm('Hapus produk dari keranjang?')) {
        const cartId = this.getAttribute('data-id');
        document.getElementById('delete-cart-id').value = cartId;
        document.getElementById('delete-single-cart-form').submit();
      }
    });
  });

  calculateSummary();
});
</script>

<?php include __DIR__ . "/../includes/footer.php"; ?>