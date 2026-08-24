<?php
require_once 'config/database.php';
require_once 'config/helpers.php';
require_login();

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT) ?: 1;

$stmt = $pdo->prepare('SELECT p.*, c.name AS category_name FROM products p JOIN categories c ON c.id=p.category_id WHERE p.id=? LIMIT 1');
$stmt->execute([$id]);
$product = $stmt->fetch();

if (!$product) {
    redirect(BASE_URL . '/gallery.php');
}

if (strtolower(trim($product['category_name'])) !== 'pelaminan') {
    redirect(BASE_URL . '/product.php?id=' . $id);
}

// Fetch photo color variants for this product
$variantStmt = $pdo->prepare("SELECT * FROM product_variants WHERE product_id = ? ORDER BY id ASC");
$variantStmt->execute([$id]);
$variants = $variantStmt->fetchAll();

$presetVariantId = filter_input(INPUT_GET, 'variant_id', FILTER_VALIDATE_INT);
$presetVariantName = trim($_GET['variant_name'] ?? '');

// Fetch active extra decor products (Pot Bunga & Kotak Akas) directly from Products table
$extraItemsStmt = $pdo->query("
    SELECT p.id, p.name, p.price, p.image_url, c.name AS category 
    FROM products p 
    JOIN categories c ON c.id = p.category_id 
    WHERE c.name IN ('Pot Bunga', 'Kotak Akas') 
    ORDER BY c.name ASC, p.name ASC
");
$extraItems = $extraItemsStmt->fetchAll();

$groupedExtraItems = [];
$extraItemIds = [];
foreach ($extraItems as $ei) {
    $groupedExtraItems[$ei['category']][] = $ei;
    $extraItemIds[] = (int)$ei['id'];
}

// Fetch color variants for extra decor items
$variantsByProduct = [];
if (!empty($extraItemIds)) {
    $placeholders = implode(',', array_fill(0, count($extraItemIds), '?'));
    $pvStmt = $pdo->prepare("SELECT * FROM product_variants WHERE product_id IN ($placeholders) ORDER BY id ASC");
    $pvStmt->execute($extraItemIds);
    foreach ($pvStmt->fetchAll() as $pv) {
        $variantsByProduct[$pv['product_id']][] = $pv;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf_token($_POST['csrf_token'] ?? null);

    $variantId = filter_input(INPUT_POST, 'variant_id', FILTER_VALIDATE_INT) ?: null;
    $variantName = trim($_POST['variant_name'] ?? '');
    $colorVal = !empty($variantName) ? $variantName : ($_POST['color'] ?? '#800020');

    // Parse positions sent from Drag & Drop Canvas
    $rawPositionsJson = $_POST['extra_positions_json'] ?? '{}';
    $positionsMap = json_decode($rawPositionsJson, true) ?: [];

    $rawExtraQty = $_POST['extra_qty'] ?? [];
    $selectedExtraDetails = [];
    $totalExtraPrice = 0.0;
    $selectedFlowers = [];
    $selectedKotak = [];

    if (is_array($rawExtraQty)) {
        $itemIdsWithQty = [];
        foreach ($rawExtraQty as $itemId => $qty) {
            $itemId = (int)$itemId;
            $qty = max(0, (int)$qty);
            if ($itemId > 0 && $qty > 0) {
                $itemIdsWithQty[$itemId] = $qty;
            }
        }

        if (!empty($itemIdsWithQty)) {
            $placeholders = implode(',', array_fill(0, count($itemIdsWithQty), '?'));
            $itemFetch = $pdo->prepare("
                SELECT p.id, p.name, p.price, p.image_url, c.name AS category 
                FROM products p 
                JOIN categories c ON c.id = p.category_id 
                WHERE p.id IN ($placeholders)
            ");
            $itemFetch->execute(array_keys($itemIdsWithQty));
            $fetchedItems = $itemFetch->fetchAll();

            $rawExtraVar = $_POST['extra_variant'] ?? [];
            $unitCounter = 0;
            foreach ($fetchedItems as $fItem) {
                $fId = (int)$fItem['id'];
                $qty = $itemIdsWithQty[$fId] ?? 1;
                $unitPrice = (float)$fItem['price'];
                $itemSubtotal = $unitPrice * $qty;
                $totalExtraPrice += $itemSubtotal;

                $varId = (int)($rawExtraVar[$fId] ?? 0);
                $extraVarName = null;
                $extraImgUrl = $fItem['image_url'];

                if ($varId > 0) {
                    $vCheck = $pdo->prepare("SELECT * FROM product_variants WHERE id = ? AND product_id = ? LIMIT 1");
                    $vCheck->execute([$varId, $fId]);
                    if ($vRow = $vCheck->fetch()) {
                        $extraVarName = $vRow['variant_name'];
                        if (!empty($vRow['image'])) {
                            $extraImgUrl = 'variants/' . $vRow['image'];
                        }
                    }
                }

                $units = [];
                for ($u = 1; $u <= $qty; $u++) {
                    $uKey = "{$fId}_{$u}";
                    $defaultX = 40 + (($unitCounter % 5) * 110);
                    $defaultY = 70 + (floor($unitCounter / 5) * 40);
                    $unitPos = $positionsMap[$uKey] ?? ['x' => $defaultX, 'y' => $defaultY];

                    $units[] = [
                        'unit' => $u,
                        'x' => (int)($unitPos['x'] ?? $defaultX),
                        'y' => (int)($unitPos['y'] ?? $defaultY)
                    ];
                    $unitCounter++;
                }

                $itemDisplayName = $extraVarName ? $fItem['name'] . " (Warna: " . $extraVarName . ")" : $fItem['name'];

                $selectedExtraDetails[] = [
                    'id' => $fId,
                    'name' => $fItem['name'],
                    'variant_id' => $varId ?: null,
                    'variant_name' => $extraVarName,
                    'category' => $fItem['category'],
                    'price' => $unitPrice,
                    'quantity' => $qty,
                    'subtotal' => $itemSubtotal,
                    'image_url' => $extraImgUrl,
                    'positions' => $units
                ];

                if ($fItem['category'] === 'Pot Bunga') {
                    $selectedFlowers[] = $itemDisplayName . " ({$qty} pcs)";
                } elseif (in_array($fItem['category'], ['Kotak Akad', 'Kotak Akas'], true)) {
                    $selectedKotak[] = $itemDisplayName . " ({$qty} pcs)";
                }
            }
        }
    }

    $shippingRateId = filter_input(INPUT_POST, 'shipping_rate_id', FILTER_VALIDATE_INT) ?: null;
    $shippingCost = (float)($_POST['shipping_cost'] ?? 0);
    $shippingCity = trim($_POST['shipping_city'] ?? '');
    $shippingDistrict = trim($_POST['shipping_district'] ?? '');

    if ($shippingCost > 0) {

        $selectedExtraDetails[] = [
            'is_shipping_meta' => true,
            'rate_id' => $shippingRateId,
            'city' => $shippingCity,
            'district' => $shippingDistrict,
            'cost' => $shippingCost
        ];
    }

    $extraItemsJson = !empty($selectedExtraDetails) ? json_encode($selectedExtraDetails, JSON_UNESCAPED_UNICODE) : null;

    // Automatic title from product name & size from product size (no notes)
    $designTitle = $product['name'];
    $designSize = $product['size'];

    $stmt = $pdo->prepare('
        INSERT INTO editor_designs(user_id, product_id, variant_id, variant_name, title, size, color, sofa, flower, kotak, notes, extra_items_json, extra_price) 
        VALUES(?,?,?,?,?,?,?,?,?,?,?,?,?)
    ');
    $stmt->execute([
        current_user()['id'],
        $id,
        $variantId,
        $variantName ?: null,
        $designTitle,
        $designSize,
        $colorVal,
        'Sudah Termasuk Dalam Paket',
        !empty($selectedFlowers) ? implode(', ', $selectedFlowers) : 'Tanpa Pot Bunga',
        !empty($selectedKotak) ? implode(', ', $selectedKotak) : 'Tanpa Kotak Akas',
        null,
        $extraItemsJson,
        $totalExtraPrice
    ]);
    $designId = $pdo->lastInsertId();

    // Insert customized product into cart
    $cartStmt = $pdo->prepare("INSERT INTO carts (user_id, product_id, design_id, variant_id, variant_name, quantity) VALUES (?, ?, ?, ?, ?, 1)");
    $cartStmt->execute([current_user()['id'], $id, $designId, $variantId, $variantName ?: null]);
    $newCartId = $pdo->lastInsertId();

    $submitAction = $_POST['submit_action'] ?? 'cart';

    if ($submitAction === 'checkout') {
        set_flash('success', 'Rancangan kustomisasi berhasil disimpan. Silakan lengkapi data pemesanan Anda.');
        redirect(BASE_URL . '/checkout.php?cart_ids=' . $newCartId);
    } else {
        set_flash('success', 'Rancangan desain pelaminan berhasil disimpan dan ditambahkan ke keranjang!');
        redirect(BASE_URL . '/customers/cart.php');
    }
}

$pageTitle = 'Design Editor Pelaminan - ' . $product['name'];
$active = 'gallery';
include 'includes/header.php';

$baseImgUrl = !empty($product['image_url']) ? BASE_URL . '/uploads/products/' . e($product['image_url']) : BASE_URL . '/assets/img/no-image.png';
?>

<div class="page-head">
    <div class="container">
        <div style="margin-bottom:14px;">
            <a href="<?= BASE_URL ?>/product.php?id=<?= (int)$id ?>" class="btn-back-nav">
                <span class="icon-arrow">←</span> Kembali ke Detail Produk
            </a>
        </div>
        <h1>Design Editor Pelaminan</h1>
        <p>Sesuaikan Warna & Drag Tata Letak Item Tambahan <?= e($product['name']) ?></p>
    </div>
<style>
.configurator-wrapper {
  display: grid;
  grid-template-columns: 1.15fr 0.85fr;
  gap: 24px;
  align-items: start;
}

.configurator-left-column {
  position: sticky;
  top: 84px;
  display: flex;
  flex-direction: column;
  gap: 16px;
  z-index: 50;
}

.configurator-preview-container {
  width: 100%;
  aspect-ratio: 16 / 9;
  min-height: 280px;
  background: #ffffff;
  border: 1px solid var(--border-subtle);
  border-radius: 16px;
  overflow: hidden;
  box-shadow: 0 8px 30px rgba(0, 0, 0, 0.06);
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 6px;
  box-sizing: border-box;
}

.configurator-right-column {
  display: flex;
  flex-direction: column;
  gap: 20px;
}

.configurator-section {
  background: #ffffff;
  border: 1px solid var(--border-subtle);
  border-radius: 16px;
  padding: 20px;
  box-shadow: 0 4px 18px rgba(0,0,0,0.03);
}

.variant-grid-3 {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(105px, 1fr));
  gap: 10px;
}

@media (max-width: 991px) {
  .configurator-wrapper {
    display: flex !important;
    flex-direction: column !important;
    width: 100% !important;
    max-width: 100% !important;
    gap: 16px !important;
  }
  .configurator-left-column {
    position: relative !important;
    top: 0 !important;
    width: 100% !important;
    max-width: 100% !important;
    z-index: 10 !important;
    background: transparent !important;
    padding: 0 !important;
    box-shadow: none !important;
  }
  .configurator-preview-container {
    width: 100% !important;
    aspect-ratio: 16 / 9 !important;
    min-height: 200px !important;
    max-height: 320px !important;
  }
  .configurator-right-column {
    width: 100% !important;
    max-width: 100% !important;
    gap: 14px !important;
  }
  .configurator-section {
    padding: 16px 14px !important;
    border-radius: 14px !important;
  }
  .variant-grid-3 {
    display: flex !important;
    flex-wrap: nowrap !important;
    overflow-x: auto !important;
    gap: 8px !important;
    padding-bottom: 6px !important;
    margin: 0 -2px !important;
    padding-left: 2px !important;
    padding-right: 2px !important;
    -webkit-overflow-scrolling: touch !important;
    scroll-snap-type: x mandatory !important;
    scrollbar-width: thin !important;
    scrollbar-color: var(--terracotta-light) transparent !important;
  }
  .variant-grid-3::-webkit-scrollbar {
    height: 4px;
  }
  .variant-grid-3::-webkit-scrollbar-thumb {
    background: var(--terracotta-light);
    border-radius: 4px;
  }
  .variant-card-item {
    flex: 0 0 96px !important;
    min-width: 96px !important;
    scroll-snap-align: start !important;
    padding: 6px 4px !important;
    border-radius: 8px !important;
  }
  .variant-card-item img {
    height: 58px !important;
  }
  .variant-card-item .variant-title {
    font-size: 10.5px !important;
  }
}
</style>

<main class="container" style="padding-top:24px;padding-bottom:60px;">
    <form method="post" id="configuratorForm" class="configurator-wrapper">
        <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
        <input type="hidden" name="variant_id" id="inputVariantId" value="<?= e($presetVariantId ?: '') ?>">
        <input type="hidden" name="variant_name" id="inputVariantName" value="<?= e($presetVariantName ?: '') ?>">
        <input type="hidden" name="extra_positions_json" id="extraPositionsJson" value="{}">
        <input type="hidden" name="shipping_rate_id" id="shippingRateIdInput" value="">
        <input type="hidden" name="shipping_cost" id="shippingCostInput" value="0">
        <input type="hidden" name="shipping_city" id="shippingCityInput" value="">
        <input type="hidden" name="shipping_district" id="shippingDistrictInput" value="">

        <!-- ==========================================
             SISI KIRI: STICKY LIVE PREVIEW CANVAS & DETAIL
             ========================================== -->
        <div class="configurator-left-column">
            <!-- 1. PREVIEW CANVAS INTERAKTIF (DRAG & DROP LAYER) -->
            <div id="configuratorCanvas" class="configurator-preview-container" style="position:relative;overflow:hidden;">
                <?php if (!empty($variants)): ?>
                    <?php 
                      $initialPhoto = $baseImgUrl;
                      if ($presetVariantId) {
                          foreach ($variants as $varItem) {
                              if ($varItem['id'] == $presetVariantId && !empty($varItem['image'])) {
                                  $initialPhoto = BASE_URL . '/uploads/products/variants/' . e($varItem['image']);
                                  break;
                              }
                          }
                      }
                    ?>
                    <!-- Layer 1: Base/Variant Background Image (100% Full Canvas View) -->
                    <img id="photoVariantPreview" src="<?= $initialPhoto ?>" alt="<?= e($product['name']) ?>" style="width:100%;height:100%;object-fit:contain;object-position:center;pointer-events:none;" onerror="this.src='<?= BASE_URL ?>/assets/img/no-image.png'">
                    
                    <!-- Layer 2+: Draggable Items Layer Container -->
                    <div id="canvasLayersContainer" style="position:absolute;top:0;left:0;width:100%;height:100%;pointer-events:none;z-index:10;"></div>
                <?php else: ?>
                    <!-- SVG Fallback Preview area -->
                    <div class="custom-preview-wrap" style="width:100%;height:100%;position:relative;">
                        <div id="previewArea" class="custom-preview-area" style="height:100%;">
                            <svg id="pelaminanSvg" viewBox="0 0 900 560" width="100%" height="100%" xmlns="http://www.w3.org/2000/svg">
                                <rect x="0" y="0" width="900" height="560" fill="#fffdf8"/>
                                <rect x="0" y="390" width="900" height="170" fill="#f2eadf"/>
                                <rect id="carpet" x="280" y="380" width="340" height="130" rx="16" fill="#d4af37" opacity="0.20"/>
                                <rect id="backdropMain" x="180" y="110" width="540" height="240" rx="24" fill="#800020"/>
                                <rect id="backdropInner" x="230" y="145" width="440" height="160" rx="18" fill="#a3263d"/>
                                <path id="archTop" d="M260 145 Q450 25 640 145" fill="none" stroke="#d4af37" stroke-width="16" stroke-linecap="round"/>
                                <g id="sofaGroup">
                                    <rect id="sofaBody" x="325" y="315" width="250" height="95" rx="26" fill="#6d1220"/>
                                    <rect id="sofaSeat" x="345" y="330" width="210" height="45" rx="16" fill="#8b2234"/>
                                    <text id="sofaLabel" x="450" y="365" text-anchor="middle" font-size="18" font-weight="700" fill="#ffffff">Sofa Included</text>
                                </g>
                                <text id="previewTitle" x="450" y="85" text-anchor="middle" font-size="26" font-weight="700" fill="#5f2130">Preview Pelaminan</text>
                            </svg>
                        </div>
                        <div id="canvasLayersContainer" style="position:absolute;top:0;left:0;width:100%;height:100%;pointer-events:none;z-index:10;"></div>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Petunjuk Canvas -->
            <div style="background:#fffcf5;border:1px solid #f5e6ca;border-radius:12px;padding:10px 14px;display:flex;align-items:center;gap:10px;font-size:12.5px;color:#8a6d3b;">
                <span style="font-size:16px;">💡</span>
                <span><strong>Petunjuk Canvas:</strong> Setiap pcs item Pot Bunga & Kotak Akas dapat <strong>digeser (drag & drop)</strong> pada gambar di atas untuk menyesuaikan tata letak.</span>
            </div>

            <!-- DETAIL PRODUK CARD & TOMBOL SIMPAN FOTO -->
            <div class="configurator-section" style="margin-bottom:0;padding:16px;">
                <h3 class="configurator-section-title" style="font-size:15px;margin-bottom:10px;">📦 Informasi Produk</h3>
                <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:8px;">
                    <strong style="color:var(--espresso);font-size:15px;"><?= e($product['name']) ?></strong>
                    <span class="badge badge-muted">Kode: <?= e($product['code']) ?></span>
                </div>
                <div style="display:flex;gap:8px;align-items:center;flex-wrap:wrap;font-size:13px;margin-bottom:14px;">
                    <div style="background:#f8f9fa;border:1px solid var(--border-subtle);border-radius:8px;padding:6px 10px;font-weight:700;color:var(--terracotta-dark);">
                        📐 Ukuran: <?= e($product['size']) ?>
                    </div>
                    <div style="background:#f8f9fa;border:1px solid var(--border-subtle);border-radius:8px;padding:6px 10px;font-weight:700;color:#155724;">
                        ✓ Sofa Pengantin Included
                    </div>
                </div>

                <button type="button" id="btnDownloadDesign" onclick="downloadCustomizedDesign()" class="btn btn-outline btn-block" style="background:#fff;border-color:var(--terracotta-dark);color:var(--terracotta-dark);font-weight:800;font-size:13.5px;padding:10px;display:flex;align-items:center;justify-content:center;gap:8px;box-shadow:0 3px 10px rgba(216,133,78,0.12);margin-top:4px;">
                    📸 Simpan / Unduh Foto Hasil Kustom
                </button>
            </div>
        </div>

        <!-- ==========================================
             SISI KANAN: PANEL OPSI & PEMESANAN
             ========================================== -->
        <div class="configurator-right-column">
            
            <!-- STEP 1: PILIHAN WARNA PELAMINAN -->
            <div class="configurator-section" style="margin-bottom:0;">
                <h3 class="configurator-section-title">🎨 1. Pilih Warna Tema Pelaminan</h3>
                
                <?php if (!empty($variants)): ?>
                    <div class="variant-grid-3">
                        <!-- Base Photo Card (Always First) -->
                        <div class="variant-card-item photo-variant-card <?= (!$presetVariantId) ? 'active' : '' ?>" 
                             onclick="selectPhotoVariant(this)"
                             data-id="" 
                             data-name="Warna Utama (Original)" 
                             data-img="<?= $baseImgUrl ?>">
                            <img src="<?= $baseImgUrl ?>" alt="Warna Utama" onerror="this.src='<?= BASE_URL ?>/assets/img/no-image.png'">
                            <span class="variant-title">Warna Utama (Base)</span>
                        </div>

                        <!-- Photo Variants from database -->
                        <?php foreach ($variants as $v): 
                            $isSel = ($presetVariantId == $v['id']);
                            $vImg = !empty($v['image']) ? BASE_URL . '/uploads/products/variants/' . e($v['image']) : $baseImgUrl;
                        ?>
                            <div class="variant-card-item photo-variant-card <?= $isSel ? 'active' : '' ?>" 
                                 onclick="selectPhotoVariant(this)"
                                 data-id="<?= (int)$v['id'] ?>" 
                                 data-name="<?= e($v['variant_name']) ?>" 
                                 data-img="<?= $vImg ?>">
                                <img src="<?= $vImg ?>" alt="<?= e($v['variant_name']) ?>" onerror="this.src='<?= BASE_URL ?>/assets/img/no-image.png'">
                                <span class="variant-title"><?= e($v['variant_name']) ?></span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <!-- Backward Compatible Color Swatch Fallback -->
                    <input type="hidden" name="color" id="colorInput" value="#800020">
                    <div class="color-choice-wrap" style="display:flex;gap:10px;">
                        <button type="button" class="color-swatch active" style="background:#800020;width:38px;height:38px;border-radius:50%;border:2px solid #ccc;cursor:pointer;" data-color="#800020" title="Maroon"></button>
                        <button type="button" class="color-swatch" style="background:#d4af37;width:38px;height:38px;border-radius:50%;border:2px solid #ccc;cursor:pointer;" data-color="#d4af37" title="Gold"></button>
                        <button type="button" class="color-swatch" style="background:#f5f0e6;width:38px;height:38px;border-radius:50%;border:2px solid #ccc;cursor:pointer;" data-color="#f5f0e6" title="Ivory White"></button>
                        <button type="button" class="color-swatch" style="background:#6f8f72;width:38px;height:38px;border-radius:50%;border:2px solid #ccc;cursor:pointer;" data-color="#6f8f72" title="Sage Green"></button>
                        <button type="button" class="color-swatch" style="background:#b76e79;width:38px;height:38px;border-radius:50%;border:2px solid #ccc;cursor:pointer;" data-color="#b76e79" title="Rose Gold"></button>
                    </div>
                <?php endif; ?>
            </div>

            <!-- STEP 2: ITEM TAMBAHAN DEKORASI (ACCORDION PER KATEGORI & VARIASI WARNA) -->
            <div class="configurator-section" style="margin-bottom:0;">
                <h3 class="configurator-section-title">🧩 2. Tambah Item Dekorasi</h3>
                <p class="muted" style="font-size:12px;margin:-8px 0 14px 0;">Pilih kuantitas & warna item tambahan. Setiap unit item dapat digeser pada Canvas.</p>

                <div class="decor-accordion-group" style="display:flex;flex-direction:column;gap:10px;">
                    <?php 
                    $accIndex = 0;
                    foreach ($groupedExtraItems as $catName => $catItems): 
                        $accIndex++;
                        $icon = ($catName === 'Kotak Akas') ? '🏺' : '🌸';
                        $isOpen = ($accIndex === 1);
                    ?>
                        <div class="decor-accordion-item" style="border:1px solid var(--border-subtle);border-radius:12px;overflow:hidden;background:#fff;">
                            <button type="button" 
                                    class="decor-accordion-header" 
                                    onclick="toggleDecorAccordion(this)" 
                                    style="width:100%;padding:12px 14px;background:#fdfaf6;border:none;display:flex;justify-content:space-between;align-items:center;cursor:pointer;font-weight:700;color:var(--espresso);font-size:13.5px;text-align:left;">
                                <span><?= $icon ?> <?= e($catName) ?> <span class="badge badge-muted" style="margin-left:6px;font-weight:600;font-size:10px;"><?= count($catItems) ?> Model</span></span>
                                <span class="accordion-arrow" style="transition:transform 0.2s ease;transform:<?= $isOpen ? 'rotate(180deg)' : 'rotate(0deg)' ?>;">▼</span>
                            </button>
                            
                            <div class="decor-accordion-body" style="display:<?= $isOpen ? 'flex' : 'none' ?>;flex-direction:column;gap:10px;padding:12px;background:#fff;border-top:1px solid var(--border-subtle);">
                                <?php foreach ($catItems as $item): 
                                    $baseImg = !empty($item['image_url']) ? BASE_URL . '/uploads/products/' . e($item['image_url']) : BASE_URL . '/assets/img/no-image.png';
                                    $itemVars = $variantsByProduct[$item['id']] ?? [];
                                ?>
                                    <div class="decor-item-card" id="card-item-<?= (int)$item['id'] ?>" style="margin-bottom:0;padding:10px 12px;border:1px solid #eee;border-radius:10px;display:flex;align-items:center;gap:10px;">
                                        <img id="img-item-<?= (int)$item['id'] ?>" src="<?= $baseImg ?>" alt="<?= e($item['name']) ?>" class="decor-item-img" style="width:48px;height:48px;object-fit:contain;border-radius:8px;background:#f8f9fa;" onerror="this.src='<?= BASE_URL ?>/assets/img/no-image.png'">
                                        
                                        <div class="decor-item-info" style="flex:1;min-width:0;">
                                            <h4 style="font-size:13px;margin:0;color:var(--espresso);font-weight:700;"><?= e($item['name']) ?></h4>
                                            <div class="price-tag" style="font-size:12.5px;color:var(--terracotta-dark);font-weight:800;"><?= rupiah($item['price']) ?></div>
                                            
                                            <!-- Dropdown Variasi Warna (Jika Produk Memiliki Varian) -->
                                            <?php if (!empty($itemVars)): ?>
                                                <div style="margin-top:4px;">
                                                    <select name="extra_variant[<?= (int)$item['id'] ?>]" 
                                                            id="variant-select-<?= (int)$item['id'] ?>" 
                                                            class="decor-variant-field" 
                                                            data-id="<?= (int)$item['id'] ?>" 
                                                            onchange="onExtraVariantChange(<?= (int)$item['id'] ?>)" 
                                                            style="font-size:11px;padding:3px 6px;border-radius:6px;border:1px solid var(--border-subtle);background:#fffcf7;color:var(--espresso);font-weight:600;max-width:100%;">
                                                        <option value="" data-name="Warna Utama" data-img="<?= $baseImg ?>">🎨 Warna Utama (Base)</option>
                                                        <?php foreach ($itemVars as $iv): 
                                                            $ivImg = !empty($iv['image']) ? BASE_URL . '/uploads/products/variants/' . e($iv['image']) : $baseImg;
                                                        ?>
                                                            <option value="<?= (int)$iv['id'] ?>" data-name="<?= e($iv['variant_name']) ?>" data-img="<?= $ivImg ?>">
                                                                🎨 <?= e($iv['variant_name']) ?>
                                                            </option>
                                                        <?php endforeach; ?>
                                                    </select>
                                                </div>
                                            <?php endif; ?>
                                        </div>

                                        <div class="decor-item-qty" style="flex-shrink:0;">
                                            <div class="qty-control">
                                                <button type="button" class="qty-btn btn-minus" data-id="<?= (int)$item['id'] ?>">-</button>
                                                <input type="number" 
                                                       name="extra_qty[<?= (int)$item['id'] ?>]" 
                                                       id="qty-input-<?= (int)$item['id'] ?>" 
                                                       value="0" 
                                                       min="0" 
                                                       max="99" 
                                                       class="qty-input extra-qty-field" 
                                                       data-id="<?= (int)$item['id'] ?>" 
                                                       data-name="<?= e($item['name']) ?>" 
                                                       data-category="<?= e($item['category']) ?>" 
                                                       data-price="<?= (float)$item['price'] ?>"
                                                       data-img="<?= $baseImg ?>">
                                                <button type="button" class="qty-btn btn-plus" data-id="<?= (int)$item['id'] ?>">+</button>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- STEP 3: RINGKASAN TOTAL & SUBMIT -->
            <div class="configurator-section" style="margin-bottom:0;display:flex;flex-direction:column;justify-content:space-between;">
                <div>
                    <h3 class="configurator-section-title">📋 3. Ringkasan & Pemesanan</h3>

                    <div style="background:linear-gradient(90deg,rgba(247,231,206,.7),#fff);border:1.5px solid rgba(212,175,55,.4);border-radius:14px;padding:16px;margin-bottom:16px;">
                        <!-- Produk Utama -->
                        <div style="display:flex;justify-content:space-between;align-items:start;font-size:13px;color:var(--espresso);margin-bottom:10px;padding-bottom:10px;border-bottom:1px dashed rgba(0,0,0,0.12);">
                            <div>
                                <small class="muted" style="display:block;font-size:10.5px;font-weight:700;letter-spacing:0.5px;">PRODUK UTAMA YANG DIBELI:</small>
                                <strong style="color:var(--terracotta-dark);font-size:14px;"><?= e($product['name']) ?></strong>
                                <br><small class="muted"><?= e($product['category_name']) ?> · <?= e($product['code']) ?></small>
                            </div>
                            <strong style="font-size:14px;color:var(--espresso);"><?= rupiah($product['price']) ?></strong>
                        </div>

                        <!-- Item Tambahan (Daftar & total) -->
                        <div style="margin-bottom:10px;padding-bottom:10px;border-bottom:1px dashed rgba(0,0,0,0.12);">
                            <div style="display:flex;justify-content:space-between;align-items:center;font-size:13px;margin-bottom:6px;">
                                <small class="muted" style="font-weight:700;font-size:10.5px;letter-spacing:0.5px;">ITEM TAMBAHAN DECOR:</small>
                                <strong id="displayExtraPrice" style="color:var(--terracotta-dark);">Rp 0</strong>
                            </div>
                            <div id="selectedDecorSummary" style="display:flex;flex-direction:column;gap:5px;">
                                <small class="muted" style="font-size:11.5px;font-style:italic;">Tanpa item tambahan (Paket Standar)</small>
                            </div>
                        </div>

                        <!-- Subtotal -->
                        <div style="display:flex;justify-content:space-between;font-size:13px;color:var(--espresso);font-weight:700;margin-bottom:10px;">
                            <span>Subtotal Produk & Decor:</span>
                            <strong id="displaySubtotalDecor"><?= rupiah($product['price']) ?></strong>
                        </div>

                        <!-- Catatan untuk Admin (Opsional) -->
                        <div style="margin-bottom:12px;border-top:1px dashed rgba(0,0,0,0.12);padding-top:10px;">
                            <label style="font-size:12px;font-weight:700;color:var(--espresso);display:block;margin-bottom:5px;">📝 Catatan untuk Admin (Opsional):</label>
                            <textarea name="delivery_note" class="input" rows="2" placeholder="Contoh: Request patokan lokasi, jam pengantaran, instruksi khusus, dll." style="font-size:12px;padding:8px 10px;border-radius:10px;resize:vertical;min-height:55px;"></textarea>
                        </div>

                        <!-- Total Akhir -->
                        <div style="border-top:1.5px solid rgba(0,0,0,0.15);padding-top:10px;display:flex;justify-content:space-between;align-items:center;">
                            <span style="font-weight:800;color:var(--espresso);font-size:14px;">Total Akhir:</span>
                            <strong id="displayTotalPrice" style="font-size:22px;color:var(--terracotta-dark);"><?= rupiah($product['price']) ?></strong>
                        </div>
                    </div>
                </div>

                <div style="display:flex;flex-direction:column;gap:10px;">
                    <button class="btn btn-primary btn-block" type="submit" name="submit_action" value="checkout" style="font-size:15px;padding:12px;font-weight:bold;background:var(--terracotta-dark);border-color:var(--terracotta-dark);box-shadow:0 3px 10px rgba(128,0,32,0.25);">
                        ⚡ Pesan Sekarang (Langsung Checkout)
                    </button>
                    <button class="btn btn-outline btn-block" type="submit" name="submit_action" value="cart" style="font-size:14px;padding:10px;font-weight:bold;margin-top:0;">
                        🛒 Simpan & Tambahkan ke Keranjang
                    </button>
                </div>
            </div>

        </div>
    </form>
</main>

<script>
// Accordion Toggle for Decor Item Categories
function toggleDecorAccordion(headerEl) {
    if (!headerEl) return;
    const item = headerEl.closest('.decor-accordion-item');
    if (!item) return;
    const body = item.querySelector('.decor-accordion-body');
    const arrow = headerEl.querySelector('.accordion-arrow');

    if (body.style.display === 'none' || !body.style.display) {
        body.style.display = 'flex';
        if (arrow) arrow.style.transform = 'rotate(180deg)';
    } else {
        body.style.display = 'none';
        if (arrow) arrow.style.transform = 'rotate(0deg)';
    }
}

// Handler when a Color Variant selection changes for a Pot Bunga or Kotak Akas model
function onExtraVariantChange(itemId) {
    const select = document.getElementById('variant-select-' + itemId);
    const qtyInput = document.getElementById('qty-input-' + itemId);
    const itemThumbnail = document.getElementById('img-item-' + itemId);

    if (select && qtyInput) {
        const selectedOpt = select.options[select.selectedIndex];
        const newImg = selectedOpt.getAttribute('data-img');
        const vName = selectedOpt.getAttribute('data-name');

        if (newImg) {
            qtyInput.setAttribute('data-img', newImg);
            if (itemThumbnail) itemThumbnail.src = newImg;
        }

        if (vName && vName !== 'Warna Utama') {
            qtyInput.setAttribute('data-variant-name', vName);
        } else {
            qtyInput.removeAttribute('data-variant-name');
        }
    }

    if (typeof window.updateTotalsAndCanvasGlobal === 'function') {
        window.updateTotalsAndCanvasGlobal();
    }
}

// Ensure Base Image Fills 100% Full Canvas Edge-to-Edge
function adjustBaseImageScale() {
    const previewImg = document.getElementById('photoVariantPreview');
    if (previewImg) {
        previewImg.style.objectFit = 'cover';
        previewImg.style.objectPosition = 'center';
    }
}

// Global Variant Switcher Handler
function selectPhotoVariant(cardEl) {
    if (!cardEl) return;
    const allCards = document.querySelectorAll('.photo-variant-card');
    allCards.forEach(c => c.classList.remove('active'));
    cardEl.classList.add('active');

    const imgUrl = cardEl.getAttribute('data-img');
    const vName = cardEl.getAttribute('data-name');
    const vId = cardEl.getAttribute('data-id');

    const previewImg = document.getElementById('photoVariantPreview');
    if (previewImg && imgUrl) {
        previewImg.src = imgUrl;
        adjustBaseImageScale();
    }

    const inputVariantId = document.getElementById('inputVariantId');
    const inputVariantName = document.getElementById('inputVariantName');
    if (inputVariantId) inputVariantId.value = vId || '';
    if (inputVariantName) inputVariantName.value = vName || '';
}

// Global Download Customized Design Image Handler
function downloadCustomizedDesign() {
    const canvasContainer = document.getElementById('configuratorCanvas');
    const baseImg = document.getElementById('photoVariantPreview');
    const downloadBtn = document.getElementById('btnDownloadDesign');

    if (!canvasContainer || !baseImg) {
        alert('Gagal mengambil preview foto pelaminan.');
        return;
    }

    if (downloadBtn) {
        downloadBtn.disabled = true;
        downloadBtn.innerHTML = '⏳ Memproses Foto...';
    }

    const cWidth = canvasContainer.offsetWidth || 800;
    const cHeight = canvasContainer.offsetHeight || 500;

    const exportCanvas = document.createElement('canvas');
    exportCanvas.width = cWidth * 2;
    exportCanvas.height = cHeight * 2;
    const ctx = exportCanvas.getContext('2d');
    ctx.scale(2, 2);

    ctx.fillStyle = '#ffffff';
    ctx.fillRect(0, 0, cWidth, cHeight);

    const bgImg = new Image();
    bgImg.onload = function() {
        const imgRatio = (bgImg.width && bgImg.height) ? (bgImg.width / bgImg.height) : (cWidth / cHeight);
        const canvasRatio = cWidth / cHeight;
        let drawWidth = cWidth, drawHeight = cHeight, drawX = 0, drawY = 0;

        if (imgRatio > canvasRatio) {
            drawWidth = cWidth;
            drawHeight = cWidth / imgRatio;
            drawY = (cHeight - drawHeight) / 2;
        } else {
            drawHeight = cHeight;
            drawWidth = cHeight * imgRatio;
            drawX = (cWidth - drawWidth) / 2;
        }

        ctx.drawImage(bgImg, drawX, drawY, drawWidth, drawHeight);

        const layers = canvasContainer.querySelectorAll('.canvas-item-layer');
        if (layers.length === 0) {
            triggerPngSave(exportCanvas);
            return;
        }

        let pending = layers.length;
        layers.forEach(layer => {
            const layerImg = layer.querySelector('img');
            const containerRect = canvasContainer.getBoundingClientRect();
            const layerRect = layer.getBoundingClientRect();

            const x = layerRect.left - containerRect.left;
            const y = layerRect.top - containerRect.top;
            const w = layerRect.width;
            const h = layerRect.height;

            if (layerImg && layerImg.src) {
                const itemImg = new Image();
                itemImg.onload = function() {
                    ctx.drawImage(itemImg, x, y, w, h);
                    pending--;
                    if (pending === 0) triggerPngSave(exportCanvas);
                };
                itemImg.onerror = function() {
                    pending--;
                    if (pending === 0) triggerPngSave(exportCanvas);
                };
                itemImg.src = layerImg.src;
            } else {
                pending--;
                if (pending === 0) triggerPngSave(exportCanvas);
            }
        });
    };

    bgImg.onerror = function() {
        alert('Gambar utama pelaminan gagal dimuat.');
        if (downloadBtn) {
            downloadBtn.disabled = false;
            downloadBtn.innerHTML = '📸 Simpan / Unduh Foto Hasil Kustom';
        }
    };

    bgImg.src = baseImg.src;
}

function triggerPngSave(canvas) {
    const downloadBtn = document.getElementById('btnDownloadDesign');
    try {
        const dataUrl = canvas.toDataURL('image/png');
        const link = document.createElement('a');
        const prodName = "<?= e(preg_replace('/[^a-zA-Z0-9_-]/', '_', $product['name'])) ?>";
        link.download = 'Kustom_Pelaminan_' + prodName + '_' + Math.floor(Date.now() / 1000) + '.png';
        link.href = dataUrl;
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
    } catch (e) {
        console.error(e);
        alert('Hasil kustomisasi siap disimpan. Silakan klik kanan pada gambar pelaminan dan pilih "Simpan Gambar Sebagai".');
    }

    if (downloadBtn) {
        downloadBtn.disabled = false;
        downloadBtn.innerHTML = '📸 Simpan / Unduh Foto Hasil Kustom';
    }
}

document.addEventListener('DOMContentLoaded', function() {
    const basePrice = <?= (float)$product['price'] ?>;
    const photoVariantCards = document.querySelectorAll('.photo-variant-card');
    const photoVariantPreview = document.getElementById('photoVariantPreview');
    const inputVariantId = document.getElementById('inputVariantId');
    const inputVariantName = document.getElementById('inputVariantName');
    const canvasContainer = document.getElementById('configuratorCanvas');
    const layersContainer = document.getElementById('canvasLayersContainer');
    const extraPositionsInput = document.getElementById('extraPositionsJson');
    const shippingRates = <?= json_encode($shippingRates ?? []) ?>;
    const configCitySelect = document.getElementById('configCitySelect');
    const configDistrictSelect = document.getElementById('configDistrictSelect');
    const displaySubtotalDecor = document.getElementById('displaySubtotalDecor');
    const displayShippingCost = document.getElementById('displayShippingCost');

    let currentShippingCost = 0;

    if (configCitySelect && configDistrictSelect) {
        configCitySelect.addEventListener('change', function() {
            const city = this.value;
            configDistrictSelect.innerHTML = '<option value="">-- Pilih Kecamatan --</option>';
            currentShippingCost = 0;
            
            if (city) {
                const matching = shippingRates.filter(r => String(r.city).trim().toLowerCase() === String(city).trim().toLowerCase());
                matching.forEach(r => {
                    const opt = document.createElement('option');
                    opt.value = r.id;
                    opt.textContent = r.district + ' — Rp ' + Number(r.cost).toLocaleString('id-ID');
                    opt.dataset.cost = r.cost;
                    configDistrictSelect.appendChild(opt);
                });
            }
            updateTotalsAndCanvas();
        });

        configDistrictSelect.addEventListener('change', function() {
            const selectedOpt = this.options[this.selectedIndex];
            if (selectedOpt && selectedOpt.value) {
                currentShippingCost = parseFloat(selectedOpt.dataset.cost) || 0;
            } else {
                currentShippingCost = 0;
            }
            updateTotalsAndCanvas();
        });
    }

    // Object storing current coordinates per unit: { "itemId_unitIndex": { x, y } }
    let itemPositions = {};

    // Quantity Control logic [-] qty [+]
    const qtyFields = document.querySelectorAll('.extra-qty-field');
    const displayExtraPrice = document.getElementById('displayExtraPrice');
    const displayTotalPrice = document.getElementById('displayTotalPrice');
    const selectedDecorSummary = document.getElementById('selectedDecorSummary');

    document.querySelectorAll('.btn-minus').forEach(btn => {
        btn.addEventListener('click', function() {
            const itemId = this.getAttribute('data-id');
            const input = document.getElementById('qty-input-' + itemId);
            if (input) {
                let currentVal = parseInt(input.value) || 0;
                if (currentVal > 0) {
                    input.value = currentVal - 1;
                    updateTotalsAndCanvas();
                }
            }
        });
    });

    document.querySelectorAll('.btn-plus').forEach(btn => {
        btn.addEventListener('click', function() {
            const itemId = this.getAttribute('data-id');
            const input = document.getElementById('qty-input-' + itemId);
            if (input) {
                let currentVal = parseInt(input.value) || 0;
                input.value = currentVal + 1;
                updateTotalsAndCanvas();
            }
        });
    });

    qtyFields.forEach(input => {
        input.addEventListener('change', updateTotalsAndCanvas);
        input.addEventListener('keyup', updateTotalsAndCanvas);
    });

    function updateTotalsAndCanvas() {
        let totalExtra = 0;
        let summaryBadgesHtml = '';
        let globalUnitCounter = 0;

        // Clear existing canvas draggable layers
        if (layersContainer) layersContainer.innerHTML = '';

        qtyFields.forEach(input => {
            const itemId = input.getAttribute('data-id');
            const itemName = input.getAttribute('data-name');
            const category = input.getAttribute('data-category');
            const unitPrice = parseFloat(input.getAttribute('data-price')) || 0;
            const itemImg = input.getAttribute('data-img');
            const varName = input.getAttribute('data-variant-name');
            const qty = parseInt(input.value) || 0;
            const card = document.getElementById('card-item-' + itemId);

            const displayName = varName ? `${itemName} (${varName})` : itemName;

            if (qty > 0) {
                const subtotal = unitPrice * qty;
                totalExtra += subtotal;

                if (card) card.classList.add('has-qty');

                summaryBadgesHtml += `
                    <div style="display:flex;justify-content:space-between;align-items:center;font-size:12px;background:#f8f9fa;padding:6px 10px;border-radius:6px;border:1px solid #e9ecef;">
                        <span>➕ <strong>${displayName}</strong> <span class="badge badge-info" style="font-size:10px;margin-left:4px;">${qty} pcs</span></span>
                        <strong style="color:var(--terracotta-dark);">+ Rp ${subtotal.toLocaleString('id-ID')}</strong>
                    </div>
                `;

                // Add an independent Draggable Layer onto Canvas for EACH unit instance (1..qty)
                if (layersContainer) {
                    for (let u = 1; u <= qty; u++) {
                        const unitKey = itemId + '_' + u;
                        
                        // Default initial position on bottom-left stage floor (never covering sofa)
                        if (!itemPositions[unitKey]) {
                            const cW = canvasContainer ? (canvasContainer.offsetWidth || 800) : 800;
                            const cH = canvasContainer ? (canvasContainer.offsetHeight || 450) : 450;
                            const defaultPctX = 5 + ((globalUnitCounter % 3) * 11);
                            const defaultPctY = 64; // Sitting on the bottom-left stage floor!
                            const defaultX = (defaultPctX / 100) * cW;
                            const defaultY = (defaultPctY / 100) * cH;
                            itemPositions[unitKey] = { 
                                x: Math.round(defaultX), 
                                y: Math.round(defaultY),
                                pctX: parseFloat(defaultPctX.toFixed(2)),
                                pctY: parseFloat(defaultPctY.toFixed(2))
                            };
                        }

                        const pos = itemPositions[unitKey];
                        const layerDiv = document.createElement('div');
                        layerDiv.className = 'canvas-item-layer';
                        layerDiv.setAttribute('data-unit-key', unitKey);
                        if (pos.pctX !== undefined && pos.pctY !== undefined) {
                            layerDiv.style.left = pos.pctX + '%';
                            layerDiv.style.top = pos.pctY + '%';
                        } else {
                            layerDiv.style.left = pos.x + 'px';
                            layerDiv.style.top = pos.y + 'px';
                        }

                        const unitBadgeText = qty > 1 ? `#${u}` : '1 pcs';

                        layerDiv.innerHTML = `
                            <div class="canvas-item-wrapper">
                                <span class="layer-qty-badge">${unitBadgeText}</span>
                                <img src="${itemImg}" alt="${displayName}" onerror="this.src='<?= BASE_URL ?>/assets/img/no-image.png'">
                                <span class="layer-title">${displayName} ${qty > 1 ? '(' + u + ')' : ''}</span>
                            </div>
                        `;

                        layersContainer.appendChild(layerDiv);
                        initDragElement(layerDiv, unitKey);
                        globalUnitCounter++;
                    }
                }
            } else {
                if (card) card.classList.remove('has-qty');
                // Clean positions for unused item keys
                Object.keys(itemPositions).forEach(k => {
                    if (k.startsWith(itemId + '_')) delete itemPositions[k];
                });
            }
        });

        if (selectedDecorSummary) {
            selectedDecorSummary.innerHTML = summaryBadgesHtml || '<small class="muted" style="font-size:11.5px;font-style:italic;">Tanpa item tambahan (Paket Standar)</small>';
        }
        
        const subtotalDecor = basePrice + totalExtra;
        const grandTotal = subtotalDecor + currentShippingCost;

        if (displayExtraPrice) displayExtraPrice.textContent = 'Rp ' + totalExtra.toLocaleString('id-ID');
        if (displaySubtotalDecor) displaySubtotalDecor.textContent = 'Rp ' + subtotalDecor.toLocaleString('id-ID');
        if (displayShippingCost) displayShippingCost.textContent = 'Rp ' + currentShippingCost.toLocaleString('id-ID');
        if (displayTotalPrice) displayTotalPrice.textContent = 'Rp ' + grandTotal.toLocaleString('id-ID');

        // Save current positions map to hidden JSON input
        if (extraPositionsInput) extraPositionsInput.value = JSON.stringify(itemPositions);
    }

    window.updateTotalsAndCanvasGlobal = updateTotalsAndCanvas;

    // Interactive Drag Engine for Canvas Layer Elements
    function initDragElement(elmnt, unitKey) {
        let pos1 = 0, pos2 = 0, pos3 = 0, pos4 = 0;

        elmnt.onmousedown = dragMouseDown;
        elmnt.ontouchstart = dragTouchStart;

        function dragMouseDown(e) {
            e = e || window.event;
            e.preventDefault();
            pos3 = e.clientX;
            pos4 = e.clientY;
            elmnt.classList.add('dragging');
            document.onmouseup = closeDragElement;
            document.onmousemove = elementDrag;
        }

        function elementDrag(e) {
            e = e || window.event;
            e.preventDefault();
            pos1 = pos3 - e.clientX;
            pos2 = pos4 - e.clientY;
            pos3 = e.clientX;
            pos4 = e.clientY;

            let newTop = elmnt.offsetTop - pos2;
            let newLeft = elmnt.offsetLeft - pos1;

            // Bound positions within canvas container
            const containerRect = canvasContainer.getBoundingClientRect();
            const elmntRect = elmnt.getBoundingClientRect();

            const maxLeft = containerRect.width - elmntRect.width;
            const maxTop = containerRect.height - elmntRect.height;

            newLeft = Math.max(0, Math.min(newLeft, maxLeft));
            newTop = Math.max(0, Math.min(newTop, maxTop));

            const cW = containerRect.width || 1;
            const cH = containerRect.height || 1;
            const pctX = Math.min(100, Math.max(0, (newLeft / cW) * 100));
            const pctY = Math.min(100, Math.max(0, (newTop / cH) * 100));

            elmnt.style.left = pctX.toFixed(2) + "%";
            elmnt.style.top = pctY.toFixed(2) + "%";

            itemPositions[unitKey] = { 
                x: Math.round(newLeft), 
                y: Math.round(newTop),
                pctX: parseFloat(pctX.toFixed(2)),
                pctY: parseFloat(pctY.toFixed(2))
            };
            if (extraPositionsInput) extraPositionsInput.value = JSON.stringify(itemPositions);
        }

        function closeDragElement() {
            elmnt.classList.remove('dragging');
            document.onmouseup = null;
            document.onmousemove = null;
        }

        // Touch Drag Handlers (Mobile Support)
        function dragTouchStart(e) {
            if (e.touches.length === 1) {
                const touch = e.touches[0];
                pos3 = touch.clientX;
                pos4 = touch.clientY;
                elmnt.classList.add('dragging');
                document.ontouchend = closeTouchDragElement;
                document.ontouchmove = elementTouchDrag;
            }
        }

        function elementTouchDrag(e) {
            if (e.touches.length === 1) {
                const touch = e.touches[0];
                pos1 = pos3 - touch.clientX;
                pos2 = pos4 - touch.clientY;
                pos3 = touch.clientX;
                pos4 = touch.clientY;

                let newTop = elmnt.offsetTop - pos2;
                let newLeft = elmnt.offsetLeft - pos1;

                const containerRect = canvasContainer.getBoundingClientRect();
                const elmntRect = elmnt.getBoundingClientRect();

                const maxLeft = containerRect.width - elmntRect.width;
                const maxTop = containerRect.height - elmntRect.height;

                newLeft = Math.max(0, Math.min(newLeft, maxLeft));
                newTop = Math.max(0, Math.min(newTop, maxTop));

                const cW2 = containerRect.width || 1;
                const cH2 = containerRect.height || 1;
                const pctX2 = Math.min(100, Math.max(0, (newLeft / cW2) * 100));
                const pctY2 = Math.min(100, Math.max(0, (newTop / cH2) * 100));

                elmnt.style.left = pctX2.toFixed(2) + "%";
                elmnt.style.top = pctY2.toFixed(2) + "%";

                itemPositions[unitKey] = { 
                    x: Math.round(newLeft), 
                    y: Math.round(newTop),
                    pctX: parseFloat(pctX2.toFixed(2)),
                    pctY: parseFloat(pctY2.toFixed(2))
                };
                if (extraPositionsInput) extraPositionsInput.value = JSON.stringify(itemPositions);
            }
        }

        function closeTouchDragElement() {
            elmnt.classList.remove('dragging');
            document.ontouchend = null;
            document.ontouchmove = null;
        }
    }

    // Initial render call & base image scale adjustment
    adjustBaseImageScale();
    window.addEventListener('resize', adjustBaseImageScale);
    updateTotalsAndCanvas();
});
</script>

<?php include 'includes/footer.php'; ?>