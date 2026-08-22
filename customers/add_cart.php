<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/helpers.php';
require_customer();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf_token($_POST['csrf_token'] ?? null);
    $productId = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
    $variantId = filter_input(INPUT_POST, 'variant_id', FILTER_VALIDATE_INT) ?: null;
    $variantName = trim($_POST['variant_name'] ?? '');
} else {
    $productId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
    $variantId = filter_input(INPUT_GET, 'variant_id', FILTER_VALIDATE_INT) ?: null;
    $variantName = trim($_GET['variant_name'] ?? '');
}

if (!$productId) {
    set_flash('danger', 'Produk tidak valid.');
    redirect(BASE_URL . '/gallery.php');
}

// Verify product exists and is active
$prodStmt = $pdo->prepare("SELECT id FROM products WHERE id = ? AND status = 'Aktif' LIMIT 1");
$prodStmt->execute([$productId]);
$product = $prodStmt->fetch();

if (!$product) {
    set_flash('danger', 'Produk tidak ditemukan atau sedang tidak aktif.');
    redirect(BASE_URL . '/gallery.php');
}

$userId = current_user()['id'];

// Check if matching item and variant already in cart
if ($variantId) {
    $cartStmt = $pdo->prepare("SELECT id, quantity FROM carts WHERE user_id = ? AND product_id = ? AND variant_id = ? LIMIT 1");
    $cartStmt->execute([$userId, $productId, $variantId]);
} else {
    $cartStmt = $pdo->prepare("SELECT id, quantity FROM carts WHERE user_id = ? AND product_id = ? AND variant_id IS NULL LIMIT 1");
    $cartStmt->execute([$userId, $productId]);
}
$cartItem = $cartStmt->fetch();

if ($cartItem) {
    $updateStmt = $pdo->prepare("UPDATE carts SET quantity = quantity + 1 WHERE id = ?");
    $updateStmt->execute([$cartItem['id']]);
    set_flash('success', 'Jumlah produk di keranjang berhasil diperbarui.');
} else {
    $insertStmt = $pdo->prepare("INSERT INTO carts (user_id, product_id, variant_id, variant_name, quantity) VALUES (?, ?, ?, ?, 1)");
    $insertStmt->execute([$userId, $productId, $variantId, $variantName ?: null]);
    set_flash('success', 'Produk berhasil ditambahkan ke keranjang.');
}

redirect(BASE_URL . '/customers/cart.php');
