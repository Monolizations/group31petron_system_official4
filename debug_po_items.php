<?php
/**
 * Debug: Check purchase order items data
 */

require_once __DIR__ . '/public/db_connect.php';

echo "🔍 DEBUGGING PURCHASE ORDER ITEMS\n";
echo str_repeat("=", 70) . "\n\n";

try {
    // Get the most recent PO
    $stmt = $pdo->query("SELECT * FROM purchase_orders ORDER BY id DESC LIMIT 1");
    $po = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($po) {
        echo "Most Recent PO:\n";
        echo "  ID: {$po['id']}\n";
        echo "  PO Number: {$po['po_number']}\n";
        echo "  Status: {$po['status']}\n";
        echo "  Created By: {$po['created_by']}\n";
        echo "\n";
        
        // Check for items
        $stmt = $pdo->prepare("SELECT * FROM purchase_order_items WHERE po_id = ?");
        $stmt->execute([$po['id']]);
        $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "Items for this PO:\n";
        if (empty($items)) {
            echo "  ❌ NO ITEMS FOUND!\n\n";
            
            // Check if there are ANY items in the table
            $stmt = $pdo->query("SELECT COUNT(*) FROM purchase_order_items");
            $count = $stmt->fetchColumn();
            echo "Total items in purchase_order_items table: $count\n\n";
            
            // Check all POs
            $stmt = $pdo->query("SELECT id, po_number FROM purchase_orders ORDER BY id DESC LIMIT 5");
            $pos = $stmt->fetchAll();
            echo "Recent POs:\n";
            foreach ($pos as $p) {
                $stmt2 = $pdo->prepare("SELECT COUNT(*) FROM purchase_order_items WHERE po_id = ?");
                $stmt2->execute([$p['id']]);
                $item_count = $stmt2->fetchColumn();
                echo "  - {$p['po_number']} (ID: {$p['id']}): $item_count items\n";
            }
        } else {
            foreach ($items as $i => $item) {
                echo "  Item " . ($i + 1) . ":\n";
                echo "    - item_name: {$item['item_name']}\n";
                echo "    - quantity: {$item['quantity']}\n";
                echo "    - unit_price: {$item['unit_price']}\n";
                echo "    - total_price: {$item['total_price']}\n\n";
            }
        }
    } else {
        echo "No purchase orders found in database.\n";
    }
    
    echo "\n" . str_repeat("=", 70) . "\n";
    echo "✅ Debug complete\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}

echo "\n";
?>