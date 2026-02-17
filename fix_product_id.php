<?php
/**
 * Fix: Make product_id nullable in purchase_order_items
 */

require_once __DIR__ . '/public/db_connect.php';

echo "🔧 FIXING product_id COLUMN\n";
echo str_repeat("=", 70) . "\n\n";

try {
    echo "Making product_id column nullable...\n";
    
    $pdo->exec("ALTER TABLE purchase_order_items 
        MODIFY COLUMN product_id INT NULL");
    
    echo "✅ product_id is now nullable\n\n";
    
    // Test insert again
    echo "Testing insert...\n";
    $stmt = $pdo->query("SELECT id FROM purchase_orders ORDER BY id DESC LIMIT 1");
    $po = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($po) {
        $po_id = $po['id'];
        $stmt = $pdo->prepare("INSERT INTO purchase_order_items 
            (po_id, item_name, quantity, unit_price, total_price) 
            VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$po_id, "Test Item", 100, 50.00, 5000.00]);
        echo "✅ Test insert successful!\n";
    }
    
    echo "\n" . str_repeat("=", 70) . "\n";
    echo "✅ FIX COMPLETE!\n";
    echo "\nYou can now create POs with items.\n";
    
} catch (Exception $e) {
    echo "\n❌ Error: " . $e->getMessage() . "\n";
}

echo "\n";
?>