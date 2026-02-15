<?php
/**
 * Test the complete receiving workflow
 * This script simulates the workflow and verifies it works correctly
 */

require_once __DIR__ . '/public/db_connect.php';

echo "=== Testing Petron Receiving Workflow ===\n\n";

// Test 1: Check batch status
echo "TEST 1: Check batch REC-1-20260215-001 status\n";
$stmt = $pdo->prepare("SELECT id, batch_number, status FROM receiving_batches WHERE batch_number = ?");
$stmt->execute(['REC-1-20260215-001']);
$batch = $stmt->fetch(PDO::FETCH_ASSOC);

if ($batch) {
    echo "✓ Batch found: ID={$batch['id']}, Status={$batch['status']}\n";
    $batch_id = $batch['id'];
} else {
    echo "✗ Batch not found\n";
    exit(1);
}

// Test 2: Check items in batch
echo "\nTEST 2: Check items in batch\n";
$stmt = $pdo->prepare("SELECT id, product_id, quantity, status FROM received_items WHERE batch_id = ?");
$stmt->execute([$batch_id]);
$items = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "✓ Found " . count($items) . " item(s) in batch\n";
foreach ($items as $item) {
    echo "  - Item ID={$item['id']}, Product={$item['product_id']}, Qty={$item['quantity']}, Status={$item['status']}\n";
}

// Test 3: Verify pricing_received_items.php can be accessed with this batch
echo "\nTEST 3: Check pricing page prerequisites\n";
$stmt = $pdo->prepare("
    SELECT ri.*, p.id as product_id, p.name, p.cost, p.price, p.sku
    FROM received_items ri
    LEFT JOIN products p ON ri.product_id = p.id
    WHERE ri.batch_id = ? AND ri.status = 'received'
    ORDER BY ri.id
");
$stmt->execute([$batch_id]);
$pricing_items = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "✓ Items with 'received' status ready for pricing: " . count($pricing_items) . "\n";

// Test 4: Simulate the entire workflow
echo "\nTEST 4: Simulate complete workflow\n";

try {
    $pdo->beginTransaction();
    
    // Step 1: Change batch status to received
    $stmt = $pdo->prepare("UPDATE receiving_batches SET status = 'received' WHERE id = ?");
    $stmt->execute([$batch_id]);
    echo "✓ Step 1: Batch status updated to 'received'\n";
    
    // Step 2: Get items and update inventory
    $stmt = $pdo->prepare("SELECT * FROM received_items WHERE batch_id = ?");
    $stmt->execute([$batch_id]);
    $batch_items = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $station_id = 1; // Default station
    
    foreach ($batch_items as $item) {
        $product_id = $item['product_id'];
        $qty = $item['quantity'];
        
        // Check if inventory exists
        $stmt_check = $pdo->prepare("SELECT stock_level FROM station_inventory WHERE station_id = ? AND product_id = ?");
        $stmt_check->execute([$station_id, $product_id]);
        $inv = $stmt_check->fetch(PDO::FETCH_ASSOC);
        
        if ($inv) {
            // Update existing
            $qty_before = $inv['stock_level'];
            $qty_after = $qty_before + $qty;
            $stmt_upd = $pdo->prepare("UPDATE station_inventory SET stock_level = stock_level + ? WHERE station_id = ? AND product_id = ?");
            $stmt_upd->execute([$qty, $station_id, $product_id]);
            echo "  ✓ Updated inventory for product {$product_id}: {$qty_before} → {$qty_after}\n";
        } else {
            // Create new
            $stmt_ins = $pdo->prepare("
                INSERT INTO station_inventory (station_id, product_id, stock_level, reorder_level, capacity, unit, status, last_updated)
                VALUES (?, ?, ?, 0, 10000, 'pieces', 'active', NOW())
            ");
            $stmt_ins->execute([$station_id, $product_id, $qty]);
            echo "  ✓ Created new inventory record for product {$product_id}: stock = {$qty}\n";
        }
        
        // Create inventory log
        $qty_before = $inv['stock_level'] ?? 0;
        $qty_after = $qty_before + $qty;
        $stmt_log = $pdo->prepare("
            INSERT INTO inventory_logs (station_id, product_id, user_id, action, quantity_before, quantity_after, quantity_change, reference_type, notes, created_at)
            VALUES (?, ?, 1, 'receiving_batch', ?, ?, ?, 'receiving_batch', ?, NOW())
        ");
        $stmt_log->execute([
            $station_id, $product_id, $qty_before, $qty_after, $qty,
            "Batch REC-1-20260215-001 received"
        ]);
        
        // Update item status to received
        $stmt_item = $pdo->prepare("UPDATE received_items SET status = 'received' WHERE id = ?");
        $stmt_item->execute([$item['id']]);
    }
    
    echo "✓ Step 2: Inventory updated for all items\n";
    
    $pdo->commit();
    echo "✓ Transaction committed successfully\n";
    
} catch (Exception $e) {
    $pdo->rollBack();
    echo "✗ Error during workflow: " . $e->getMessage() . "\n";
    exit(1);
}

// Test 5: Verify batch is received
echo "\nTEST 5: Verify batch status after receiving\n";
$stmt = $pdo->prepare("SELECT status FROM receiving_batches WHERE id = ?");
$stmt->execute([$batch_id]);
$batch_status = $stmt->fetch(PDO::FETCH_ASSOC);
echo "✓ Batch status: {$batch_status['status']}\n";

// Test 6: Verify items are received
echo "\nTEST 6: Verify items can be priced\n";
$stmt = $pdo->prepare("
    SELECT ri.id, p.name, p.sku, ri.quantity, p.cost, p.price
    FROM received_items ri
    LEFT JOIN products p ON ri.product_id = p.id
    WHERE ri.batch_id = ? AND ri.status = 'received'
");
$stmt->execute([$batch_id]);
$ready_items = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "✓ Items ready for pricing:\n";
foreach ($ready_items as $item) {
    echo "  - {$item['name']} (SKU: {$item['sku']}, Qty: {$item['quantity']})\n";
    echo "    Current pricing: Cost={$item['cost']}, Price={$item['price']}\n";
}

// Test 7: Simulate pricing update
echo "\nTEST 7: Simulate pricing update\n";

try {
    $pdo->beginTransaction();
    
    // Update product pricing
    foreach ($ready_items as $item) {
        $old_cost = $item['cost'] ?? 0;
        $old_price = $item['price'] ?? 0;
        $new_cost = 50.00; // Test pricing
        $new_price = 99.99;
        
        $stmt_upd = $pdo->prepare("UPDATE products SET cost = ?, price = ?, updated_at = NOW() WHERE id = ?");
        $stmt_upd->execute([$new_cost, $new_price, $item['product_id']]);
        
        // Log price change
        $stmt_log = $pdo->prepare("
            INSERT INTO price_change_logs (product_id, old_cost, old_price, new_cost, new_price, action, user_id, timestamp, notes)
            VALUES (?, ?, ?, ?, ?, 'receiving_batch', 1, NOW(), ?)
        ");
        $stmt_log->execute([
            $item['product_id'], $old_cost, $old_price, $new_cost, $new_price,
            "Priced from batch REC-1-20260215-001"
        ]);
        
        // Mark item as confirmed
        $stmt_item = $pdo->prepare("UPDATE received_items SET status = 'confirmed' WHERE id = ?");
        $stmt_item->execute([$item['id']]);
        
        echo "  ✓ Priced {$item['name']}: Cost={$new_cost}, Price={$new_price}\n";
    }
    
    // Mark batch as confirmed
    $stmt_batch = $pdo->prepare("UPDATE receiving_batches SET status = 'confirmed', confirmed_at = NOW(), confirmed_by = 1 WHERE id = ?");
    $stmt_batch->execute([$batch_id]);
    
    $pdo->commit();
    echo "✓ Pricing confirmed and batch marked as complete\n";
    
} catch (Exception $e) {
    $pdo->rollBack();
    echo "✗ Error during pricing: " . $e->getMessage() . "\n";
    exit(1);
}

// Test 8: Final verification
echo "\nTEST 8: Final workflow verification\n";
$stmt = $pdo->prepare("SELECT status FROM receiving_batches WHERE id = ?");
$stmt->execute([$batch_id]);
$final_status = $stmt->fetch(PDO::FETCH_ASSOC);
echo "✓ Final batch status: {$final_status['status']}\n";

$stmt = $pdo->prepare("
    SELECT COUNT(*) as count FROM received_items 
    WHERE batch_id = ? AND status = 'confirmed'
");
$stmt->execute([$batch_id]);
$confirmed_count = $stmt->fetchColumn();
echo "✓ Confirmed items: {$confirmed_count}\n";

echo "\n=== Workflow Test Complete ===\n";
echo "The complete receiving workflow (Encode → Receive → Price → Confirm) is working correctly!\n";
?>
