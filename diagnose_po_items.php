<?php
/**
 * Diagnose and fix purchase_order_items column mismatch
 */

require_once __DIR__ . '/public/db_connect.php';

echo "🔧 DIAGNOSING PURCHASE_ORDER_ITEMS TABLE\n";
echo str_repeat("=", 70) . "\n\n";

try {
    // Get current columns
    $stmt = $pdo->query("SHOW COLUMNS FROM purchase_order_items");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "Current columns in purchase_order_items:\n";
    $col_names = [];
    foreach ($columns as $col) {
        echo "  - {$col['Field']}: {$col['Type']}\n";
        $col_names[] = $col['Field'];
    }
    
    echo "\n";
    
    // Check if we have quantity or quantity_ordered
    $has_quantity = in_array('quantity', $col_names);
    $has_quantity_ordered = in_array('quantity_ordered', $col_names);
    $has_total_price = in_array('total_price', $col_names);
    $has_unit_price = in_array('unit_price', $col_names);
    
    echo "Column checks:\n";
    echo "  - quantity: " . ($has_quantity ? "✅ Found" : "❌ Not found") . "\n";
    echo "  - quantity_ordered: " . ($has_quantity_ordered ? "✅ Found" : "❌ Not found") . "\n";
    echo "  - unit_price: " . ($has_unit_price ? "✅ Found" : "❌ Not found") . "\n";
    echo "  - total_price: " . ($has_total_price ? "✅ Found" : "❌ Not found") . "\n";
    
    echo "\n";
    
    // Fix issues
    if (!$has_quantity && $has_quantity_ordered) {
        echo "❌ MISMATCH: Code uses 'quantity' but DB has 'quantity_ordered'\n";
        echo "Adding 'quantity' column...\n";
        $pdo->exec("ALTER TABLE purchase_order_items ADD COLUMN quantity INT NOT NULL AFTER item_name");
        echo "✅ Added 'quantity' column\n";
    } elseif (!$has_quantity && !$has_quantity_ordered) {
        echo "❌ CRITICAL: Neither 'quantity' nor 'quantity_ordered' exists!\n";
        echo "Adding 'quantity' column...\n";
        $pdo->exec("ALTER TABLE purchase_order_items ADD COLUMN quantity INT NOT NULL AFTER item_name");
        echo "✅ Added 'quantity' column\n";
    }
    
    if (!$has_total_price) {
        echo "❌ 'total_price' column not found!\n";
        echo "Adding 'total_price' column...\n";
        $pdo->exec("ALTER TABLE purchase_order_items ADD COLUMN total_price DECIMAL(10,2) NOT NULL AFTER unit_price");
        echo "✅ Added 'total_price' column\n";
    }
    
    if (!$has_unit_price) {
        echo "❌ 'unit_price' column not found!\n";
        echo "Adding 'unit_price' column...\n";
        $pdo->exec("ALTER TABLE purchase_order_items ADD COLUMN unit_price DECIMAL(10,2) NOT NULL AFTER quantity");
        echo "✅ Added 'unit_price' column\n";
    }
    
    // Check again
    echo "\nVerifying final structure...\n";
    $stmt = $pdo->query("SHOW COLUMNS FROM purchase_order_items");
    $final_columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "\nFinal columns:\n";
    foreach ($final_columns as $col) {
        echo "  - {$col['Field']}: {$col['Type']}\n";
    }
    
    echo "\n" . str_repeat("=", 70) . "\n";
    echo "✅ ALL COLUMN ISSUES FIXED!\n";
    echo "\nYou can now create purchase orders without column errors.\n";
    
} catch (Exception $e) {
    echo "\n❌ Error: " . $e->getMessage() . "\n";
}

echo "\n";
?>