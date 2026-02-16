<?php
require_once __DIR__ . '/db_connect.php';

echo "<h2>Sales Table Schema</h2>";
echo "<pre>";

// Get table structure
$stmt = $pdo->query("DESCRIBE sales");
$columns = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "SALES TABLE COLUMNS:\n";
echo str_repeat("=", 80) . "\n";
foreach ($columns as $col) {
    echo sprintf("%-20s %-15s %-10s %-20s %s\n", 
        $col['Field'], 
        $col['Type'], 
        $col['Null'], 
        $col['Key'], 
        $col['Default']
    );
}

echo "\n\nSALE_ITEMS TABLE COLUMNS:\n";
echo str_repeat("=", 80) . "\n";
$stmt = $pdo->query("DESCRIBE sale_items");
$columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
foreach ($columns as $col) {
    echo sprintf("%-20s %-15s %-10s %-20s %s\n", 
        $col['Field'], 
        $col['Type'], 
        $col['Null'], 
        $col['Key'], 
        $col['Default']
    );
}

echo "\n\nCOUNT OF SALES: ";
echo $pdo->query("SELECT COUNT(*) FROM sales")->fetchColumn();

echo "\nCOUNT OF SALE_ITEMS: ";
echo $pdo->query("SELECT COUNT(*) FROM sale_items")->fetchColumn();

echo "\n\nLAST 5 SALES:\n";
echo str_repeat("=", 80) . "\n";
$stmt = $pdo->query("SELECT id, station_id, user_id, payment_method, total, status, created_at FROM sales ORDER BY created_at DESC LIMIT 5");
$sales = $stmt->fetchAll(PDO::FETCH_ASSOC);
foreach ($sales as $s) {
    echo sprintf("ID: %-20s | Station: %-3s | Status: %-12s | Total: %10s | Created: %s\n", 
        $s['id'], 
        $s['station_id'],
        $s['status'],
        $s['total'],
        $s['created_at']
    );
}

echo "</pre>";
?>
