<?php
/**
 * Fix: Make all optional columns nullable in purchase_order_items
 */

require_once __DIR__ . '/public/db_connect.php';

echo "🔧 FIXING ALL COLUMNS IN purchase_order_items\n";
echo str_repeat("=", 70) . "\n\n";

try {
    // Get current columns
    $stmt = $pdo->query("SHOW COLUMNS FROM purchase_order_items");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "Current columns:\n";
    foreach ($columns as $col) {
        $nullable = $col['Null'] === 'YES' ? 'NULL' : 'NOT NULL';
        echo "  - {$col['Field']}: {$col['Type']} $nullable\n";
    }
    
    echo "\n";
    
    // Make optional columns nullable
    $pdo->exec("ALTER TABLE purchase_order_items 
        MODIFY COLUMN product_id INT NULL,
        MODIFY COLUMN quantity_ordered INT NULL,
        MODIFY COLUMN quantity_received INT NULL DEFAULT 0,
        MODIFY COLUMN received_at DATETIME NULL,
        MODIFY COLUMN received_by INT NULL");
    
    echo "✅ Columns updated to be nullable\n\n";
    
    // Test insert
    echo "Testing insert without product_id...\n";
    $stmt = $pdo->query("SELECT id FROM purchase_orders ORDER BY id DESC LIMIT 1");
    $po = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($po) {
        $po_id = $po['id'];
        $stmt = $pdo->prepare("INSERT INTO purchase_order_items 
            (po_id, item_name, quantity, unit_price, total_price) 
            VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$po_id, "Test Diesel", 1000, 45.50, 45500.00]);
        
        echo "✅ Insert successful!\n\n";
        
        // Verify
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM purchase_order_items WHERE po_id = ?");
        $stmt->execute([$po_id]);
        $count = $stmt->fetchColumn();
        
        echo "Total items in PO $po_id: $count\n";
    }
    
    echo "\n" . str_repeat("=", 70) . "\n";
    echo "✅ ALL FIXES COMPLETE!\n";
    echo "\nPO items will now save correctly.\n";
    
} catch (Exception $e) {
    echo "\n❌ Error: " . $e->getMessage() . "\n";
}

echo "\n";
?>