<?php
if (($_GET['key'] ?? '') !== 'pelaminan2026') { http_response_code(403); exit('Akses ditolak.'); }
error_reporting(E_ALL); ini_set('display_errors', 1);
echo '<pre style="font-family:monospace;font-size:13px;padding:20px;">';
echo "=== DIAGNOSA PELAMINAN FAMILY WEBSITE ===\n";
echo "Waktu server: " . date('Y-m-d H:i:s') . "\n\n";
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/helpers.php';
echo "✅ Koneksi database OK\n";
echo "  BASE_URL: '" . BASE_URL . "'\n\n";

echo "=== CEK TABEL DATABASE ===\n";
$tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
foreach ($tables as $t) { echo "  ✅ Tabel: $t\n"; }
echo "\n";

echo "=== KOLOM TABEL ORDERS ===\n";
$cols = $pdo->query("SHOW COLUMNS FROM orders")->fetchAll();
foreach ($cols as $c) { echo "  - {$c['Field']} ({$c['Type']})\n"; }
echo "\n";

echo "=== TEST QUERY PRODUCTION-CALENDAR ===\n";
try {
    $q = $pdo->query("SELECT o.id AS order_id, o.order_code, o.customer_name, o.phone, o.status AS order_status, o.event_date, o.schedule_start, o.schedule_end, o.id AS queue_id, COALESCE(o.queue_number, o.id) AS queue_number, IF(o.status = 'PRODUCTION', 'PRODUCING', 'WAITING') AS production_status, o.schedule_start AS estimated_start_date, o.schedule_end AS estimated_end_date, p.name AS product_name, p.code AS product_code, p.production_duration FROM orders o JOIN products p ON p.id = o.product_id WHERE o.status NOT IN ('CANCELLED', 'REJECTED') ORDER BY o.id DESC");
    echo "  ✅ Query OK - " . count($q->fetchAll()) . " baris\n";
} catch (Exception $e) { echo "  ❌ ERROR: " . $e->getMessage() . "\n"; }
echo "\n";

echo "=== TEST QUERY OPERATIONAL-REPORT ===\n";
try {
    $r = $pdo->prepare("SELECT o.*, p.name AS product_name, p.production_duration, COALESCE(o.queue_number, o.id) AS queue_number, o.schedule_start AS estimated_start_date, o.schedule_end AS estimated_end_date FROM orders o JOIN products p ON p.id = o.product_id ORDER BY o.id DESC");
    $r->execute([]);
    echo "  ✅ Query OK - " . count($r->fetchAll()) . " baris\n";
} catch (Exception $e) { echo "  ❌ ERROR: " . $e->getMessage() . "\n"; }
echo "\n";

echo "=== TEST NOTIFICATIONS TABLE ===\n";
try {
    $cnt = $pdo->query("SELECT COUNT(*) FROM notifications")->fetchColumn();
    echo "  ✅ notifications: $cnt baris\n";
} catch (Exception $e) { echo "  ❌ ERROR: " . $e->getMessage() . "\n"; }

echo "\n=== TEST ACTIVITY_LOGS TABLE ===\n";
try {
    $cnt2 = $pdo->query("SELECT COUNT(*) FROM activity_logs")->fetchColumn();
    echo "  ✅ activity_logs: $cnt2 baris\n";
} catch (Exception $e) { echo "  ❌ activity_logs ERROR: " . $e->getMessage() . "\n"; }

echo "\n=== PHP INFO ===\n";
echo "  PHP Version: " . phpversion() . "\n";
echo "  Memory limit: " . ini_get('memory_limit') . "\n";
echo "\n=== DIAGNOSA SELESAI ===\n";
echo '</pre>';
