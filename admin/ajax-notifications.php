<?php
require_once '../config/database.php';
require_once '../config/helpers.php';
require_admin();

header('Content-Type: application/json');

$user = current_user();
if (!$user) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$action = $_GET['action'] ?? $_POST['action'] ?? 'fetch';

if ($action === 'mark_read') {
    $notifId = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
    if ($notifId) {
        $stmt = $pdo->prepare("UPDATE notifications SET is_read = 1 WHERE id = ? AND user_id = ?");
        $stmt->execute([$notifId, $user['id']]);
    } else {
        $stmt = $pdo->prepare("UPDATE notifications SET is_read = 1 WHERE user_id = ?");
        $stmt->execute([$user['id']]);
    }
    echo json_encode(['success' => true]);
    exit;
}

// Auto-check Production Schedule Reminders for today
try {
    $today = date('Y-m-d');
    
    // 1. Remind production start today
    $startStmt = $pdo->prepare("
        SELECT id, order_code, customer_name 
        FROM orders 
        WHERE schedule_start = ? AND status IN ('WAITING_QUEUE', 'PRODUCTION')
    ");
    $startStmt->execute([$today]);
    $startOrders = $startStmt->fetchAll();

    foreach ($startOrders as $so) {
        $chkTitle = '🔨 Jadwal Produksi Hari Ini';
        $chkMsg = "Pengingat: Pesanan {$so['order_code']} ({$so['customer_name']}) dijadwalkan mulai diproduksi hari ini.";
        
        $chkNotif = $pdo->prepare("
            SELECT COUNT(*) FROM notifications 
            WHERE user_id = ? AND order_id = ? AND title = ? AND DATE(created_at) = ?
        ");
        $chkNotif->execute([$user['id'], $so['id'], $chkTitle, $today]);
        if ((int)$chkNotif->fetchColumn() === 0) {
            send_system_notification($pdo, $user['id'], $so['id'], $chkTitle, $chkMsg);
        }
    }

    // 2. Remind production deadline today
    $endStmt = $pdo->prepare("
        SELECT id, order_code, customer_name 
        FROM orders 
        WHERE schedule_end = ? AND status IN ('PRODUCTION', 'WAITING_QUEUE')
    ");
    $endStmt->execute([$today]);
    $endOrders = $endStmt->fetchAll();

    foreach ($endOrders as $eo) {
        $chkTitle = '⏳ Tenggat Selesai Produksi Hari Ini';
        $chkMsg = "Perhatian: Target penyelesaian produksi untuk pesanan {$eo['order_code']} ({$eo['customer_name']}) jatuh pada hari ini.";
        
        $chkNotif = $pdo->prepare("
            SELECT COUNT(*) FROM notifications 
            WHERE user_id = ? AND order_id = ? AND title = ? AND DATE(created_at) = ?
        ");
        $chkNotif->execute([$user['id'], $eo['id'], $chkTitle, $today]);
        if ((int)$chkNotif->fetchColumn() === 0) {
            send_system_notification($pdo, $user['id'], $eo['id'], $chkTitle, $chkMsg);
        }
    }
} catch (Exception $e) {
    // Suppress error
}

// Fetch unread count
$countStmt = $pdo->prepare("SELECT COUNT(*) FROM notifications WHERE user_id = ? AND is_read = 0");
$countStmt->execute([$user['id']]);
$unreadCount = (int)$countStmt->fetchColumn();

// Fetch latest notifications
$listStmt = $pdo->prepare("
    SELECT id, order_id, title, message, is_read, created_at 
    FROM notifications 
    WHERE user_id = ? 
    ORDER BY id DESC 
    LIMIT 10
");
$listStmt->execute([$user['id']]);
$notifications = $listStmt->fetchAll();

foreach ($notifications as &$n) {
    $n['time_ago'] = time_ago($n['created_at']);
}

echo json_encode([
    'success' => true,
    'unread_count' => $unreadCount,
    'notifications' => $notifications
]);
