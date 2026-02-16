<?php
// Simple diagnostic - no login required, for debugging only
require_once __DIR__ . '/../public/db_connect.php';

echo "<h2>Fuel Transaction Diagnostic</h2>";
echo "<pre>";

// Check product_types
echo "=== PRODUCT_TYPES TABLE ===\n";
$stmt = $pdo->query("SELECT * FROM product_types LIMIT 5");
$types = $stmt->fetchAll(PDO::FETCH_ASSOC);
print_r($types);

// Check fuel products
echo "\n=== FUEL PRODUCTS ===\n";
$stmt = $pdo->query("SELECT id, name, type_id, fuel_type_id FROM products WHERE type_id = 1 LIMIT 5");
$products = $stmt->fetchAll(PDO::FETCH_ASSOC);
print_r($products);

// Check recent sales
echo "\n=== RECENT SALES (last 10) ===\n";
$stmt = $pdo->query("SELECT id, station_id, payment_method, total, status, created_at FROM sales ORDER BY created_at DESC LIMIT 10");
$sales = $stmt->fetchAll(PDO::FETCH_ASSOC);
print_r($sales);

// Check recent sale_items
echo "\n=== RECENT SALE_ITEMS (last 10) ===\n";
$stmt = $pdo->query("SELECT id, sale_id, product_id, name, quantity, unit_price, total_amount FROM sale_items ORDER BY id DESC LIMIT 10");
$items = $stmt->fetchAll(PDO::FETCH_ASSOC);
print_r($items);

// Check if sales and sale_items match
echo "\n=== SALES & ITEMS MATCH ===\n";
$stmt = $pdo->query("SELECT s.id, s.created_at, COUNT(si.id) as item_count FROM sales s LEFT JOIN sale_items si ON s.id = si.sale_id GROUP BY s.id ORDER BY s.created_at DESC LIMIT 5");
$matches = $stmt->fetchAll(PDO::FETCH_ASSOC);
print_r($matches);

// Try the transaction history query manually
echo "\n=== TRANSACTION HISTORY QUERY (Station 1) ===\n";
$sql = "SELECT
    s.id as transaction_id,
    c.name as customer,
    s.payment_method,
    s.created_at,
    s.status,
    u.name as staff_name,
    p.name as product_name,
    si.quantity,
    si.unit_price,
    si.total_amount as subtotal,
    COALESCE(pt.name, 'Merchandise') as category
FROM sales s
JOIN sale_items si ON s.id = si.sale_id
LEFT JOIN customers c ON s.customer_id = c.id
LEFT JOIN users u ON s.user_id = u.id
LEFT JOIN products p ON si.product_id = p.id
LEFT JOIN product_types pt ON p.type_id = pt.id
WHERE s.station_id = 1
ORDER BY s.created_at DESC LIMIT 5";

try {
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "Query executed successfully. Results:\n";
    print_r($results);
} catch (Exception $e) {
    echo "Query error: " . $e->getMessage() . "\n";
}

echo "</pre>";
?>
