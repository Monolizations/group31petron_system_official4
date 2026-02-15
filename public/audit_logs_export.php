<?php
require_once __DIR__ . '/../public/db_connect.php';
require_once __DIR__ . '/../backend/lib.php';

// Check if user is logged in
require_login();
$u = current_user();
$role = role_key($u['role'] ?? 'staff');

// Manager, Admin, and Super Admin can export audit logs
if (!in_array($role, ['manager','admin','superadmin'])) {
    header('Location: dashboard.php');
    exit;
}

// Get parameters
$log_type = $_GET['type'] ?? 'user';
$date_range = $_GET['date_range'] ?? '';
$users = $_GET['users'] ?? [];
$branches = $_GET['branches'] ?? [];
$transaction_types = $_GET['transaction_types'] ?? [];
$items = $_GET['items'] ?? [];

// Parse date range
$start_date = '';
$end_date = '';
if ($date_range) {
    $dates = explode(' to ', $date_range);
    $start_date = $dates[0] ?? '';
    $end_date = $dates[1] ?? $start_date;
}

// Set default date range if none provided
if (!$date_range) {
    $today = new DateTime();
    $lastWeek = new DateTime($today->format('Y-m-d'));
    $lastWeek->sub(new DateInterval('P7D'));
    $start_date = $lastWeek->format('Y-m-d');
    $end_date = $today->format('Y-m-d');
    $date_range = "$start_date to $end_date";
}

// Fetch data based on log type
$data = [];
$filename = '';
$headers = [];

switch ($log_type) {
    case 'user':
        $filename = "user_audit_logs_" . date('Y-m-d_H-i-s') . ".csv";
        $headers = ['Date', 'User', 'Action', 'Details', 'IP Address', 'Status'];
        
        $sql = "SELECT 
                    al.created_at,
                    u.username,
                    al.action,
                    al.details,
                    al.ip_address,
                    'Success' as status
                FROM activity_logs al
                LEFT JOIN users u ON al.user_id = u.id
                WHERE DATE(al.created_at) BETWEEN ? AND ?";
        $params = [$start_date, $end_date];
        
        if (!empty($users)) {
            $placeholders = str_repeat('?,', count($users) - 1) . '?';
            $sql .= " AND al.user_id IN ($placeholders)";
            $params = array_merge($params, $users);
        }
        
        $sql .= " ORDER BY al.created_at DESC";
        
        try {
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch(Exception $e) {
            $data = [];
        }
        break;
        
    case 'transaction':
        $filename = "transaction_audit_logs_" . date('Y-m-d_H-i-s') . ".csv";
        $headers = ['Date', 'Transaction ID', 'Type', 'Amount', 'Status', 'User', 'Station'];
        
        // Generate sample transaction data
        $sql = "SELECT 
                    s.created_at,
                    s.id as transaction_id,
                    'Sale' as type,
                    s.total as amount,
                    'Completed' as status,
                    u.username,
                    'Main Station' as station
                FROM sales s
                LEFT JOIN users u ON s.user_id = u.id
                WHERE DATE(s.created_at) BETWEEN ? AND ?";
        $params = [$start_date, $end_date];
        
        if (!empty($branches)) {
            $placeholders = str_repeat('?,', count($branches) - 1) . '?';
            $sql .= " AND s.station_id IN ($placeholders)";
            $params = array_merge($params, $branches);
        }
        
        $sql .= " ORDER BY s.created_at DESC";
        
        try {
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch(Exception $e) {
            $data = [];
        }
        break;
        
    case 'inventory':
        $filename = "inventory_audit_logs_" . date('Y-m-d_H-i-s') . ".csv";
        $headers = ['Date', 'Product', 'Action', 'Stock Level', 'User', 'Station'];
        
        $sql = "SELECT 
                    i.created_at,
                    i.product_name,
                    'Stock Update' as action,
                    i.stock_level,
                    u.username,
                    'Main Station' as station
                FROM station_inventory i
                LEFT JOIN users u ON i.user_id = u.id
                WHERE DATE(i.created_at) BETWEEN ? AND ?";
        $params = [$start_date, $end_date];
        
        if (!empty($branches)) {
            $placeholders = str_repeat('?,', count($branches) - 1) . '?';
            $sql .= " AND i.station_id IN ($placeholders)";
            $params = array_merge($params, $branches);
        }
        
        if (!empty($items)) {
            $placeholders = str_repeat('?,', count($items) - 1) . '?';
            $sql .= " AND i.product_name IN ($placeholders)";
            $params = array_merge($params, $items);
        }
        
        $sql .= " ORDER BY i.created_at DESC";
        
        try {
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch(Exception $e) {
            $data = [];
        }
        break;
        
    default:
        $filename = "audit_logs_" . date('Y-m-d_H-i-s') . ".csv";
        $headers = ['Date', 'Category', 'Details'];
        $data = [];
        break;
}

// Set headers for CSV download
header('Content-Type: text/csv');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Cache-Control: no-cache, must-revalidate');
header('Expires: 0');

// Open output stream
$output = fopen('php://output', 'w');

// Add UTF-8 BOM for proper Excel display
fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

// Write headers
fputcsv($output, $headers);

// Write data
foreach ($data as $row) {
    fputcsv($output, array_values($row));
}

// Close output stream
fclose($output);
exit;
?>
