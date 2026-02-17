<?php
/**
 * Fix: Add item_name column to purchase_order_items table
 */

require_once __DIR__ . '/public/db_connect.php';

echo "🔧 FIXING PURCHASE_ORDER_ITEMS TABLE\n";
echo str_repeat("=", 70) . "\n\n";

try {
    // Check current columns
    echo "Checking current columns in purchase_order_items...\n";
    $stmt = $pdo->query("SHOW COLUMNS FROM purchase_order_items");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "\nCurrent columns:\n";
    $has_item_name = false;
    foreach ($columns as $col) {
        echo "  - {$col['Field']}: {$col['Type']}\n";
        if ($col['Field'] === 'item_name') {
            $has_item_name = true;
        }
    }
    
    if (!$has_item_name) {
        echo "\n❌ 'item_name' column not found!\n";
        echo "Adding column...\n";
        
        $pdo->exec("ALTER TABLE purchase_order_items 
            ADD COLUMN item_name VARCHAR(255) NOT NULL AFTER po_id");
        
        echo "✅ 'item_name' column added successfully!\n";
    } else {
        echo "\n✅ 'item_name' column already exists!\n";
    }
    
    echo "\n" . str_repeat("=", 70) . "\n";
    echo "✅ FIX COMPLETE!\n";
    echo "\nYou can now create purchase orders with items.\n";
    
} catch (Exception $e) {
    echo "\n❌ Error: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}

echo "\n";
?>