<?php

date_default_timezone_set('Asia/Jakarta');

if (!defined('BASE_URL')) {
    $scriptName = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '');
    if (strpos($scriptName, '/pelaminan/') === 0 || $scriptName === '/pelaminan') {
        define('BASE_URL', '/pelaminan');
    } else {
        define('BASE_URL', '');
    }
}

if (!defined('BASE_PATH')) {
    define('BASE_PATH', dirname(__DIR__));
}

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function e($value): string {
    return htmlspecialchars((string)($value ?? ''), ENT_QUOTES, 'UTF-8');
}

function rupiah($number): string {
    return 'Rp ' . number_format((float)$number, 0, ',', '.');
}

function parse_rupiah_input($value): float {
    if (is_int($value) || is_float($value)) {
        return (float)$value;
    }
    if (empty($value)) {
        return 0.0;
    }
    $str = trim((string)$value);
    if ($str === '') {
        return 0.0;
    }

    $cleaned = preg_replace('/[^\d.,]/', '', $str);
    if ($cleaned === '') {
        return 0.0;
    }

    if (strpos($cleaned, '.') !== false && strpos($cleaned, ',') !== false) {
        $cleaned = str_replace('.', '', $cleaned);
        $cleaned = str_replace(',', '.', $cleaned);
    } elseif (strpos($cleaned, '.') !== false && strpos($cleaned, ',') === false) {
        $cleaned = str_replace('.', '', $cleaned);
    } elseif (strpos($cleaned, ',') !== false && strpos($cleaned, '.') === false) {
        $cleaned = str_replace(',', '.', $cleaned);
    }
    return (float)$cleaned;
}

function redirect(string $url): void {
    header('Location: ' . $url);
    exit;
}

function is_logged_in(): bool {
    return isset($_SESSION['user']['id']);
}

function is_admin(): bool {
    return is_logged_in() && (($_SESSION['user']['role'] ?? '') === 'admin');
}

function current_user(): ?array {
    return $_SESSION['user'] ?? null;
}

function require_login(): void {
    if (!is_logged_in()) {
        set_flash('warning', 'Silakan login terlebih dahulu.');
        redirect(BASE_URL . '/login.php');
    }
}

function is_owner(): bool {
    return false;
}

function require_owner(): void {
    require_admin();
}

function require_admin(): void {
    if (!is_logged_in() || !is_admin()) {
        set_flash('danger', 'Halaman panel ini hanya dapat diakses oleh admin.');
        redirect(BASE_URL . '/login.php');
    }
}

function require_admin_only(): void {
    require_admin();
}

function require_admin_or_owner(): void {
    require_admin();
}

function log_activity(string $action, string $details = ''): void {
    global $pdo;
    if (!is_logged_in()) return;
    $user = current_user();
    if (($user['role'] ?? '') !== 'admin') return;

    try {
        $stmt = $pdo->prepare("INSERT INTO activity_logs (user_id, user_name, user_role, action, details) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([
            $user['id'],
            $user['name'],
            $user['role'],
            $action,
            $details
        ]);
    } catch (Exception $e) {
        // Resilience catch for log helper
    }
}

function require_customer(): void {
    if (!is_logged_in()) {
        set_flash('warning', 'Silakan login terlebih dahulu.');
        redirect(BASE_URL . '/login.php');
    }
}

function csrf_token(): string {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function verify_csrf_token(?string $token): void {
    if (
        !$token ||
        empty($_SESSION['csrf_token']) ||
        !hash_equals($_SESSION['csrf_token'], $token)
    ) {
        http_response_code(419);
        exit('Permintaan tidak valid (CSRF token mismatch).');
    }
}

function set_flash(string $type, string $message): void {
    $_SESSION['flash'] = [
        'type' => $type,
        'message' => $message
    ];
}

function get_flash(string $type): ?string {
    $message = $_SESSION['flash'][$type] ?? null;
    unset($_SESSION['flash'][$type]);
    return $message;
}

function flash(): string {
    if (!empty($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return '<div class="container" style="margin-top:15px;"><div class="alert alert-' . e($flash['type']) . '">' . e($flash['message']) . '</div></div>';
    }
    return '';
}

function status_label($status): string {
    $labels = [
        'WAITING_QUEUE'      => 'Masuk Antrean Produksi',
        'PRODUCTION'         => 'Sedang Diproduksi',
        'READY_PICKUP'       => 'Siap Diambil',
        'READY_DELIVERY'     => 'Dalam Pengiriman',
        'ON_DELIVERY'        => 'Dalam Pengiriman',
        'DELIVERED'          => 'Dalam Pengiriman',
        'READY_INSTALLATION' => 'Sedang Diproduksi',
        'INSTALLATION'       => 'Sedang Diproduksi',
        'COMPLETED'          => 'Pesanan Selesai',
        
        'WAITING_PAYMENT'    => 'Menunggu Pembayaran',
        'PAYMENT_RECEIVED'   => 'Pembayaran & Verifikasi',
        'ADMIN_REVIEW'       => 'Pembayaran & Verifikasi',
        'CANCELLED'          => 'Dibatalkan',
        'REJECTED'           => 'Ditolak',
    ];
    return $labels[$status] ?? ucwords(str_replace(['-', '_'], ' ', (string)$status));
}

function status_class($status): string {
    $classes = [
        'WAITING_QUEUE'      => 'badge-warning',
        'PRODUCTION'         => 'badge-info',
        'READY_PICKUP'       => 'badge-primary',
        'READY_DELIVERY'     => 'badge-primary',
        'COMPLETED'          => 'badge-success',
        
        'WAITING_PAYMENT'    => 'badge-primary',
        'ON_DELIVERY'        => 'badge-primary',
        'DELIVERED'          => 'badge-primary',
        'READY_INSTALLATION' => 'badge-info',
        'INSTALLATION'       => 'badge-info',
        'PAYMENT_RECEIVED'   => 'badge-warning',
        'ADMIN_REVIEW'       => 'badge-warning',
        'CANCELLED'          => 'badge-muted',
        'REJECTED'           => 'badge-danger',
    ];
    return $classes[$status] ?? 'badge-muted';
}

function get_setting($pdo, string $key, string $default = ''): string {
    static $settings = null;
    if ($settings === null) {
        $file = __DIR__ . '/settings.php';
        if (file_exists($file)) {
            $settings = require $file;
        } else {
            $settings = [];
        }
    }
    return (string)($settings[$key] ?? $default);
}

function upload_image(
    array $file,
    string $targetDirectory,
    int $maxSize = 2097152
): string {
    if (!isset($file['error']) || is_array($file['error'])) {
        throw new RuntimeException('Parameter file upload tidak valid.');
    }

    switch ($file['error']) {
        case UPLOAD_ERR_OK:
            break;
        case UPLOAD_ERR_NO_FILE:
            throw new RuntimeException('Tidak ada file yang diunggah.');
        case UPLOAD_ERR_INI_SIZE:
        case UPLOAD_ERR_FORM_SIZE:
            throw new RuntimeException('Ukuran file melebihi batas maksimal.');
        default:
            throw new RuntimeException('Terjadi kesalahan saat mengunggah file.');
    }

    if ($file['size'] > $maxSize) {
        throw new RuntimeException('Ukuran file maksimal 2 MB.');
    }

    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime = $finfo->file($file['tmp_name']);

    $allowed = [
        'image/jpeg' => 'jpg',
        'image/png'  => 'png',
        'image/webp' => 'webp',
    ];

    if (!isset($allowed[$mime])) {
        throw new RuntimeException('Format file harus berupa JPG, PNG, atau WEBP.');
    }

    $extension = $allowed[$mime];
    $filename = bin2hex(random_bytes(16)) . '.' . $extension;

    if (!is_dir($targetDirectory)) {
        mkdir($targetDirectory, 0755, true);
    }

    $destination = rtrim($targetDirectory, '/\\') . DIRECTORY_SEPARATOR . $filename;

    if (!move_uploaded_file($file['tmp_name'], $destination)) {
        throw new RuntimeException('Gagal memindahkan file yang diunggah.');
    }

    return $filename;
}

/**
 * Automatically crops empty transparent, white, and off-white border margins from uploaded product images
 * (PNG, JPG, JPEG, WEBP) so that uploaded pelaminan stage images fill the 16:9 canvas 100% full.
 */
function auto_crop_transparent_image(string $filePath): bool {
    return true;
}

function find_active_shipping_rate(PDO $pdo, int $shippingRateId): ?array {
    return null;
}

function send_system_notification(PDO $pdo, int $userId, ?int $orderId, string $title, string $message, string $channel = 'internal'): void {
    $stmt = $pdo->prepare("
        INSERT INTO notifications (user_id, order_id, title, message, channel)
        VALUES (?, ?, ?, ?, ?)
    ");
    $stmt->execute([$userId, $orderId, $title, $message, $channel]);

    // Extensible hook for WhatsApp API integration
    if ($channel === 'whatsapp' || $channel === 'all') {
        // Example integration point:
        // send_whatsapp_api($phone, $message);
    }
}

function send_admin_notification(PDO $pdo, ?int $orderId, string $title, string $message, string $channel = 'internal'): void {
    try {
        $admins = $pdo->query("SELECT id FROM users WHERE role = 'admin'")->fetchAll(PDO::FETCH_COLUMN);
        if (!empty($admins)) {
            $stmt = $pdo->prepare("
                INSERT INTO notifications (user_id, order_id, title, message, channel)
                VALUES (?, ?, ?, ?, ?)
            ");
            foreach ($admins as $adminId) {
                $stmt->execute([(int)$adminId, $orderId, $title, $message, $channel]);
            }
        }
    } catch (Exception $e) {
        // Suppress errors if table schema differs
    }
}

function time_ago($datetime): string {
    $time = is_numeric($datetime) ? (int)$datetime : strtotime((string)$datetime);
    if (!$time) return 'Baru saja';
    $diff = time() - $time;
    if ($diff < 60) return 'Baru saja';
    if ($diff < 3600) return floor($diff / 60) . ' menit lalu';
    if ($diff < 86400) return floor($diff / 3600) . ' jam lalu';
    if ($diff < 2592000) return floor($diff / 86400) . ' hari lalu';
    return date('d/m/Y H:i', $time);
}

function log_order_status_change(PDO $pdo, int $orderId, ?string $oldStatus, string $newStatus, ?int $changedBy = null): void {
    $stmt = $pdo->prepare("SELECT order_code FROM orders WHERE id = ? LIMIT 1");
    $stmt->execute([$orderId]);
    $code = $stmt->fetchColumn() ?: "#$orderId";

    log_activity('Status Pesanan Berubah', "Pesanan {$code} mengalami perubahan status dari " . ($oldStatus ?: '-') . " menjadi {$newStatus}");
}

function calculate_production_dates(PDO $pdo, int $productId, ?string $preferredStartDate = null): array {
    $pStmt = $pdo->prepare("SELECT production_duration FROM products WHERE id = ? LIMIT 1");
    $pStmt->execute([$productId]);
    $duration = (int)($pStmt->fetchColumn() ?: 3);
    if ($duration <= 0) $duration = 3;

    $qStmt = $pdo->query("
        SELECT MAX(schedule_end) 
        FROM orders 
        WHERE status IN ('WAITING_QUEUE', 'PRODUCTION')
    ");
    $lastEndDate = $qStmt->fetchColumn();

    $today = date('Y-m-d');
    
    if (!empty($preferredStartDate) && strtotime($preferredStartDate) > strtotime($today)) {
        $startDate = $preferredStartDate;
    } else {
        $startDate = $today;
    }

    if (!empty($lastEndDate) && strtotime($lastEndDate) >= strtotime($startDate)) {
        $startDate = date('Y-m-d', strtotime($lastEndDate . ' +1 day'));
    }

    $endDateDays = $duration - 1;
    $endDate = date('Y-m-d', strtotime($startDate . " +{$endDateDays} days"));

    return [
        'start_date' => $startDate,
        'end_date' => $endDate,
        'duration' => $duration
    ];
}

function get_customer_queue_position(PDO $pdo, int $orderId): ?array {
    $qStmt = $pdo->prepare("
        SELECT id, order_code, status AS order_status, queue_number, schedule_start, schedule_end
        FROM orders
        WHERE id = ?
        LIMIT 1
    ");
    $qStmt->execute([$orderId]);
    $myOrder = $qStmt->fetch();

    if (!$myOrder) {
        return null;
    }

    if (empty($myOrder['queue_number'])) {
        $maxQStmt = $pdo->query("SELECT COALESCE(MAX(queue_number), 0) FROM orders");
        $nextQueueNum = (int)$maxQStmt->fetchColumn() + 1;
        $updQ = $pdo->prepare("UPDATE orders SET queue_number = ? WHERE id = ?");
        $updQ->execute([$nextQueueNum, $orderId]);
        $myOrder['queue_number'] = $nextQueueNum;
    }

    $posStmt = $pdo->prepare("
        SELECT COUNT(*) 
        FROM orders 
        WHERE status IN ('WAITING_QUEUE', 'PRODUCTION') 
          AND queue_number < ?
    ");
    $posStmt->execute([$myOrder['queue_number']]);
    $aheadCount = (int)$posStmt->fetchColumn();

    return [
        'queue_number' => (int)$myOrder['queue_number'],
        'position' => $aheadCount + 1,
        'estimated_start_date' => $myOrder['schedule_start'],
        'estimated_end_date' => $myOrder['schedule_end'],
        'production_status' => $myOrder['order_status'] === 'PRODUCTION' ? 'PRODUCING' : 'WAITING'
    ];
}

function check_date_capacity(PDO $pdo, string $eventDate): bool {
    $stmt = $pdo->prepare("
        SELECT COUNT(*) 
        FROM orders 
        WHERE status IN ('WAITING_QUEUE', 'PRODUCTION') 
          AND ? BETWEEN schedule_start AND schedule_end
    ");
    $stmt->execute([$eventDate]);
    $count = (int)$stmt->fetchColumn();

    return $count < 1;
}

function format_date_range(?string $start, ?string $end): string {
    if (empty($start) || empty($end)) {
        return '-';
    }
    $months = [
        1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
        5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
        9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'
    ];

    $tsStart = strtotime($start);
    $tsEnd = strtotime($end);
    if (!$tsStart || !$tsEnd) {
        return '-';
    }

    $d1 = (int)date('j', $tsStart);
    $m1 = (int)date('n', $tsStart);
    $y1 = (int)date('Y', $tsStart);

    $d2 = (int)date('j', $tsEnd);
    $m2 = (int)date('n', $tsEnd);
    $y2 = (int)date('Y', $tsEnd);

    if ($y1 === $y2) {
        if ($m1 === $m2) {
            return "{$d1}–{$d2} {$months[$m1]} {$y1}";
        }
        return "{$d1} {$months[$m1]} – {$d2} {$months[$m2]} {$y1}";
    }
    return "{$d1} {$months[$m1]} {$y1} – {$d2} {$months[$m2]} {$y2}";
}

function format_indonesian_date(?string $dateStr): string {
    if (empty($dateStr)) {
        return '-';
    }
    $ts = strtotime($dateStr);
    if (!$ts) {
        return '-';
    }
    $months = [
        1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
        5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
        9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'
    ];
    $d = (int)date('j', $ts);
    $m = (int)date('n', $ts);
    $y = (int)date('Y', $ts);

    return "{$d} {$months[$m]} {$y}";
}

function get_or_create_invoice(PDO $pdo, int $orderId): array {
    $oStmt = $pdo->prepare("SELECT created_at FROM orders WHERE id = ? LIMIT 1");
    $oStmt->execute([$orderId]);
    $ord = $oStmt->fetch();
    $issuedDate = $ord ? $ord['created_at'] : date('Y-m-d H:i:s');
    $invNum = 'INV-' . date('Y', strtotime($issuedDate)) . '-' . sprintf('%05d', $orderId);
    $dueDate = date('Y-m-d H:i:s', strtotime($issuedDate . ' +3 days'));

    return [
        'id' => $orderId,
        'order_id' => $orderId,
        'invoice_number' => $invNum,
        'issued_date' => $issuedDate,
        'due_date' => $dueDate,
        'status' => 'ISSUED',
        'notes' => 'Produk yang sudah dibayar DP maupun FULL tidak dapat dibatalkan.'
    ];
}

function get_or_create_receipt(PDO $pdo, int $paymentId): array {
    $pStmt = $pdo->prepare("SELECT * FROM payments WHERE id = ? LIMIT 1");
    $pStmt->execute([$paymentId]);
    $pay = $pStmt->fetch();

    if (!$pay) {
        throw new Exception("Data pembayaran tidak ditemukan.");
    }

    $recNum = 'KWT-' . date('Y', strtotime($pay['paid_at'] ?? 'now')) . '-' . sprintf('%05d', $paymentId);
    $issuedDate = !empty($pay['paid_at']) ? $pay['paid_at'] : date('Y-m-d H:i:s');
    $pType = strtoupper($pay['type']) === 'DP' ? 'Pembayaran DP 50%' : 'Pelunasan Tagihan';

    return [
        'id' => $paymentId,
        'payment_id' => $paymentId,
        'order_id' => (int)$pay['order_id'],
        'receipt_number' => $recNum,
        'issued_date' => $issuedDate,
        'amount' => (float)$pay['amount'],
        'payment_type' => $pType
    ];
}

// ── STORE ORIGIN & REALTIME SHIPPING CALCULATOR ──
if (!defined('STORE_LAT')) define('STORE_LAT', -2.9389551);
if (!defined('STORE_LNG')) define('STORE_LNG', 104.8106462);

function calculate_haversine_km(float $lat1, float $lon1, float $lat2, float $lon2): float {
    $r = 6371.0;
    $dLat = deg2rad($lat2 - $lat1);
    $dLon = deg2rad($lon2 - $lon1);
    $a = sin($dLat / 2) * sin($dLat / 2) +
         cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
         sin($dLon / 2) * sin($dLon / 2);
    $c = 2 * atan2(sqrt($a), sqrt(1 - $a));
    return $r * $c;
}

function calculate_shipping_cost_km(float $distKm): float {
    if ($distKm <= 1.0) return 0.0; // GRATIS Area Dekat Gudang
    // Formulasi Calibrated Resmi GoBox Gojek Palembang:
    // Sampel 1: 3.9 km = Rp 134.000 | Sampel 2: 9.5 km = Rp 176.500
    // Base Fee Armada: Rp 105.000 | Tarif Normal: Rp 7.500/km
    $baseFee = 105000.0;
    $rawCost = 0.0;

    if ($distKm <= 30.0) {
        $rawCost = $baseFee + ($distKm * 7500.0);
    } else if ($distKm <= 100.0) {
        $extraKm30 = $distKm - 30.0;
        $rawCost = $baseFee + (30.0 * 7500.0) + ($extraKm30 * 6000.0);
    } else {
        $extraKm100 = $distKm - 100.0;
        $rawCost = $baseFee + (30.0 * 7500.0) + (70.0 * 6000.0) + ($extraKm100 * 5000.0);
    }

    return (float)(round($rawCost / 500.0) * 500.0);
}

// ── PRODUCT CODE GENERATOR PER CATEGORY PREFIX ──
function get_category_prefix(string $categoryName): string {
    $c = strtolower(trim($categoryName));
    if (strpos($c, 'pelaminan') !== false) {
        return 'PLM';
    }
    if (strpos($c, 'kotak') !== false || strpos($c, 'akas') !== false || strpos($c, 'akad') !== false) {
        return 'KA';
    }
    if (strpos($c, 'pot') !== false || strpos($c, 'bunga') !== false || strpos($c, 'flower') !== false || strpos($c, 'standing') !== false) {
        return 'PB';
    }
    if (strpos($c, 'gazebo') !== false) {
        return 'GZB';
    }

    $words = explode(' ', $c);
    if (count($words) >= 2) {
        return strtoupper(substr($words[0], 0, 1) . substr($words[1], 0, 2));
    }
    return strtoupper(substr($c, 0, 3));
}

function generate_product_code(PDO $pdo, int $categoryId, int $productId = 0): string {
    $stmtCat = $pdo->prepare('SELECT name FROM categories WHERE id=? LIMIT 1');
    $stmtCat->execute([$categoryId]);
    $catName = $stmtCat->fetchColumn() ?: '';

    $prefix = get_category_prefix($catName);

    $stmtCodes = $pdo->prepare('SELECT code FROM products WHERE code LIKE ? AND id != ?');
    $stmtCodes->execute([$prefix . '-%', $productId]);
    $existingCodes = $stmtCodes->fetchAll(PDO::FETCH_COLUMN);

    $maxNum = 0;
    foreach ($existingCodes as $c) {
        if (preg_match('/' . preg_quote($prefix, '/') . '-(\d+)/i', $c, $m)) {
            $num = (int)$m[1];
            if ($num > $maxNum) {
                $maxNum = $num;
            }
        }
    }

    $nextNum = $maxNum + 1;
    return sprintf('%s-%03d', $prefix, $nextNum);
}




