<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/helpers.php';
require_customer();

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

$userId = current_user()['id'];
$cartId = filter_input(INPUT_POST, 'cart_id', FILTER_VALIDATE_INT);
$quantity = filter_input(INPUT_POST, 'quantity', FILTER_VALIDATE_INT);

if (!$cartId || !$quantity || $quantity < 1) {
    echo json_encode(['success' => false, 'message' => 'Data jumlah tidak valid']);
    exit;
}

// Verify item belongs to user
$stmt = $pdo->prepare("
    SELECT c.id, c.product_id, p.price, p.status, COALESCE(d.extra_price, 0) AS extra_price 
    FROM carts c 
    JOIN products p ON c.product_id = p.id 
    LEFT JOIN editor_designs d ON c.design_id = d.id
    WHERE c.id = ? AND c.user_id = ? AND p.status = 'Aktif' 
    LIMIT 1
");
$stmt->execute([$cartId, $userId]);
$item = $stmt->fetch();

if (!$item) {
    echo json_encode(['success' => false, 'message' => 'Item keranjang tidak ditemukan']);
    exit;
}

// Update quantity in database
$updateStmt = $pdo->prepare("UPDATE carts SET quantity = ? WHERE id = ? AND user_id = ?");
$updateStmt->execute([$quantity, $cartId, $userId]);

$unitPrice = (float)$item['price'] + (float)$item['extra_price'];
$subtotal = $unitPrice * $quantity;

echo json_encode([
    'success' => true,
    'cart_id' => $cartId,
    'quantity' => $quantity,
    'unit_price' => $unitPrice,
    'subtotal' => $subtotal,
    'subtotal_formatted' => rupiah($subtotal)
]);
exit;
