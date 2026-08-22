<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/helpers.php';
require_customer();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf_token($_POST['csrf_token'] ?? null);
    $cartId = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
} else {
    $cartId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
}

if ($cartId) {
    $stmt = $pdo->prepare('DELETE FROM carts WHERE id = ? AND user_id = ?');
    $stmt->execute([
        $cartId,
        current_user()['id']
    ]);
    set_flash('success', 'Item berhasil dihapus dari keranjang.');
} else {
    set_flash('danger', 'ID item keranjang tidak valid.');
}

redirect(BASE_URL . '/customers/cart.php');
