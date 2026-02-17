<?php
/**
 * Manual test: Insert PO item directly
 */

require_once __DIR__ . '/public/db_connect.php';

echo "🧪 MANUAL INSERT TEST\n";
echo str_repeat("=", 70) . "\n\n";

try {
    // Get the most recent PO
    $stmt = $pdo->query("SELECT id FROM purchase_orders ORDER BY id DESC LIMIT 1");
    $po = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$po) {
        echo "❌ No PO found to test with\n";
        exit;
    }
    
    $po_id = $po['id'];
    echo "Testing with PO ID: $po_id\n\n";
    
    // Try to insert a test item
    echo "Attempting to insert test item...\n";
    $stmt = $pdo->prepare("INSERT INTO purchase_order_items 
        (po_id, item_name, quantity, unit_price, total_price) 
        VALUES (?, ?, ?, ?, ?)");
    
    $item_name = "Test Diesel";
    $quantity = 1000;
    $unit_price = 45.50;
    $total_price = $quantity * $unit_price;
    
    echo "Data:\n";
    echo "  PO ID: $po_id\n";
    echo "  Item: $item_name\n";
    echo "  Qty: $quantity\n";
    echo "  Price: $unit_price\n";
    echo "  Total: $total_price\n\n";
    
    $stmt->execute([$po_id, $item_name, $quantity, $unit_price, $total_price]);
    
    echo "✅ Insert successful!\n\n";
    
    // Verify it was inserted
    $stmt = $pdo->prepare("SELECT * FROM purchase_order_items WHERE po_id = ?");
    $stmt->execute([$po_id]);
    $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "Items now in PO $po_id: " . count($items) . "\n";
    foreach ($items as $item) {
        echo "  - {$item['item_name']}: {$item['quantity']} @ {$item['unit_price']} = {$item['total_price']}\n";
    }
    
} catch (Exception $e) {
    echo "❌ ERROR: " . $e->getMessage() . "\n";
    echo "Error code: " . $e->getCode() . "\n";
}

echo "\n" . str_repeat("=", 70) . "\n";
?>