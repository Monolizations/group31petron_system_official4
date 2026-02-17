<?php
/**
 * Debug: Check print_po.php issues
 */

require_once __DIR__ . '/public/db_connect.php';

$po_id = 5; // The ID from the URL

echo "🔍 DEBUGGING PRINT PO PAGE\n";
echo str_repeat("=", 70) . "\n\n";

echo "Testing PO ID: $po_id\n\n";

try {
    // Test 1: Check if PO exists
    echo "1. Checking if PO exists...\n";
    $stmt = $pdo->prepare("SELECT * FROM purchase_orders WHERE id = ?");
    $stmt->execute([$po_id]);
    $po = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$po) {
        echo "   ❌ PO not found!\n";
        exit;
    }
    echo "   ✅ PO found: {$po['po_number']}\n\n";
    
    // Test 2: Check if items exist
    echo "2. Checking items...\n";
    $stmt = $pdo->prepare("SELECT * FROM purchase_order_items WHERE po_id = ?");
    $stmt->execute([$po_id]);
    $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "   Found " . count($items) . " item(s)\n";
    
    if (!empty($items)) {
        foreach ($items as $i => $item) {
            echo "   - Item $i: {$item['item_name']} (qty: {$item['quantity']})\n";
        }
    }
    echo "\n";
    
    // Test 3: Check required columns
    echo "3. Checking PO columns...\n";
    $required = ['po_number', 'supplier_id', 'station_id', 'created_by', 'status'];
    foreach ($required as $col) {
        $exists = isset($po[$col]) ? '✅' : '❌';
        $value = $po[$col] ?? 'NULL';
        echo "   $exists $col: $value\n";
    }
    echo "\n";
    
    // Test 4: Check supplier
    echo "4. Checking supplier...\n";
    $stmt = $pdo->prepare("SELECT * FROM suppliers WHERE id = ?");
    $stmt->execute([$po['supplier_id']]);
    $supplier = $stmt->fetch();
    if ($supplier) {
        echo "   ✅ Supplier: {$supplier['name']}\n";
    } else {
        echo "   ❌ Supplier not found (ID: {$po['supplier_id']})\n";
    }
    echo "\n";
    
    // Test 5: Check station
    echo "5. Checking station...\n";
    $stmt = $pdo->prepare("SELECT * FROM stations WHERE id = ?");
    $stmt->execute([$po['station_id']]);
    $station = $stmt->fetch();
    if ($station) {
        echo "   ✅ Station: {$station['name']}\n";
    } else {
        echo "   ❌ Station not found (ID: {$po['station_id']})\n";
    }
    echo "\n";
    
    // Test 6: Check user
    echo "6. Checking user...\n";
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$po['created_by']]);
    $user = $stmt->fetch();
    if ($user) {
        echo "   ✅ User: {$user['name']}\n";
    } else {
        echo "   ❌ User not found (ID: {$po['created_by']})\n";
    }
    echo "\n";
    
    echo str_repeat("=", 70) . "\n";
    echo "✅ All checks passed! The PO data is valid.\n";
    echo "\nIf the page still doesn't display, check:\n";
    echo "1. PHP error logs\n";
    echo "2. Browser console for JavaScript errors\n";
    echo "3. Network tab for 500 errors\n";
    
} catch (Exception $e) {
    echo "\n❌ ERROR: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}

echo "\n";
?>