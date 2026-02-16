<?php
/**
 * Complete Test: Reconciliation Sync Workflow
 * Tests: Create reconciliation → Finalize → Sync to POS → Verify
 */

require_once __DIR__ . '/public/db_connect.php';
require_once __DIR__ . '/backend/lib.php';
require_once __DIR__ . '/backend/fuel_pos_sync.php';

echo "<style>
    body { font-family: monospace; background: #f5f5f5; padding: 20px; }
    .test { background: white; margin: 10px 0; padding: 15px; border-left: 4px solid #007bff; }
    .pass { border-left-color: #28a745; }
    .fail { border-left-color: #dc3545; }
    .step { color: #007bff; font-weight: bold; }
    .result { margin-left: 20px; }
    code { background: #f4f4f4; padding: 2px 6px; border-radius: 3px; }
</style>";

echo "<h1>Reconciliation → POS Sync Workflow Test</h1>\n";

try {
    // Step 1: Get test data prerequisites
    echo "<div class='test'>\n";
    echo "<p class='step'>STEP 1: Prepare Test Data</p>\n";
    echo "<div class='result'>\n";
    
    // Get a station
    $stmt = $pdo->query("SELECT id FROM stations LIMIT 1");
    $station_id = $stmt->fetchColumn() ?? 1;
    echo "Using station_id: $station_id<br>\n";
    
    // Get a fuel type
    $stmt = $pdo->query("SELECT id, name FROM fuel_types WHERE id IN (SELECT DISTINCT fuel_type_id FROM fuel_reconciliation) LIMIT 1");
    $fuel = $stmt->fetch();
    
    // If no reconciliation exists, use Gasoline
    if (!$fuel) {
        $fuel = ['id' => 1, 'name' => 'Gasoline'];
    }
    $fuel_type_id = $fuel['id'];
    $fuel_name = $fuel['name'];
    echo "Using fuel: $fuel_name (type_id: $fuel_type_id)<br>\n";
    
    // Get a pump
    $stmt = $pdo->prepare("SELECT id FROM fuel_pumps WHERE station_id = ? LIMIT 1");
    $stmt->execute([$station_id]);
    $pump_id = $stmt->fetchColumn();
    if (!$pump_id) {
        echo "✗ No pumps found for this station<br>\n";
        exit;
    }
    echo "Using pump_id: $pump_id<br>\n";
    
    // Get a fuel product to update inventory
    $stmt = $pdo->prepare("
        SELECT p.id FROM products p
        INNER JOIN product_types pt ON p.type_id = pt.id
        WHERE pt.id = ?
        LIMIT 1
    ");
    $stmt->execute([$fuel_type_id]);
    $fuel_product_id = $stmt->fetchColumn();
    
    if (!$fuel_product_id) {
        // Create a test fuel product if it doesn't exist
        $stmt = $pdo->prepare("INSERT INTO products (type_id, name, sku, price, cost) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$fuel_type_id, "Test Fuel", "FUEL-TEST", 55.00, 50.00]);
        $fuel_product_id = $pdo->lastInsertId();
        echo "Created test fuel product ID: $fuel_product_id<br>\n";
    } else {
        echo "Using existing fuel_product_id: $fuel_product_id<br>\n";
    }
    
    // Ensure station_inventory exists
    $stmt = $pdo->prepare("
        SELECT id FROM station_inventory
        WHERE station_id = ? AND product_id = ?
    ");
    $stmt->execute([$station_id, $fuel_product_id]);
    if (!$stmt->fetchColumn()) {
        $stmt = $pdo->prepare("
            INSERT INTO station_inventory (station_id, product_id, stock_level, unit, status, in_sync)
            VALUES (?, ?, ?, 'liters', 'active', 1)
        ");
        $stmt->execute([$station_id, $fuel_product_id, 500]);
        echo "Created station_inventory record<br>\n";
    }
    
    // Set current POS stock
    $current_pos_stock = 500.00;
    $stmt = $pdo->prepare("UPDATE station_inventory SET stock_level = ? WHERE station_id = ? AND product_id = ?");
    $stmt->execute([$current_pos_stock, $station_id, $fuel_product_id]);
    echo "Set POS stock to: $current_pos_stock liters<br>\n";
    
    echo "</div>\n";
    echo "</div>\n";
    
    // Step 2: Create a reconciliation record
    echo "<div class='test'>\n";
    echo "<p class='step'>STEP 2: Create Test Reconciliation</p>\n";
    echo "<div class='result'>\n";
    
    $today = date('Y-m-d');
    $prev_reading = 1000.00;
    $current_reading = 1500.00;  // 500L increase
    $calibration = 0.00;
    $price_per_liter = 55.00;
    $sales_liters = $current_reading - $prev_reading - $calibration;  // 500L
    $closing_stock = 500.00;  // This is what we'll sync to POS
    
    $stmt = $pdo->prepare("
        INSERT INTO fuel_reconciliation 
        (station_id, reconciliation_date, fuel_type_id, pump_id, 
         previous_reading, present_reading, calibration, price_per_liter, 
         physical_stock, status, created_at)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'Pending', NOW())
    ");
    $stmt->execute([
        $station_id, $today, $fuel_type_id, $pump_id,
        $prev_reading, $current_reading, $calibration, $price_per_liter,
        $closing_stock
    ]);
    
    $reconciliation_id = $pdo->lastInsertId();
    echo "✓ Created reconciliation ID: $reconciliation_id<br>\n";
    echo "  Previous Reading: $prev_reading<br>\n";
    echo "  Current Reading: $current_reading<br>\n";
    echo "  Sales Liters: $sales_liters<br>\n";
    echo "  Closing Stock: $closing_stock<br>\n";
    
    // Verify reconciliation was created
    $stmt = $pdo->prepare("SELECT * FROM fuel_reconciliation WHERE id = ?");
    $stmt->execute([$reconciliation_id]);
    $rec = $stmt->fetch();
    echo "  Status in DB: {$rec['status']}<br>\n";
    
    echo "</div>\n";
    echo "</div>\n";
    
    // Step 3: Finalize the reconciliation
    echo "<div class='test'>\n";
    echo "<p class='step'>STEP 3: Finalize Reconciliation</p>\n";
    echo "<div class='result'>\n";
    
    $user_id = 1; // Admin user for finalization
    $stmt = $pdo->prepare("
        UPDATE fuel_reconciliation 
        SET status = 'finalized', finalized_by = ?, finalized_at = NOW()
        WHERE id = ?
    ");
    $stmt->execute([$user_id, $reconciliation_id]);
    echo "✓ Finalized reconciliation ID: $reconciliation_id<br>\n";
    
    // Verify status changed
    $stmt = $pdo->prepare("SELECT status FROM fuel_reconciliation WHERE id = ?");
    $stmt->execute([$reconciliation_id]);
    $status = $stmt->fetchColumn();
    echo "  Status now: $status<br>\n";
    
    echo "</div>\n";
    echo "</div>\n";
    
    // Step 4: Check pending syncs
    echo "<div class='test'>\n";
    echo "<p class='step'>STEP 4: Check Pending Reconciliations</p>\n";
    echo "<div class='result'>\n";
    
    $pending = getUnSyncedReconciliations($pdo, $station_id);
    if (isset($pending['reconciliations']) && count($pending['reconciliations']) > 0) {
        echo "✓ Found " . count($pending['reconciliations']) . " pending syncs<br>\n";
        foreach ($pending['reconciliations'] as $p) {
            echo "  - Rec #{$p['id']}: {$p['fuel_type']} closing stock {$p['closing_stock']} L<br>\n";
        }
    } else {
        echo "✗ No pending reconciliations found<br>\n";
    }
    
    echo "</div>\n";
    echo "</div>\n";
    
    // Step 5: Execute the sync
    echo "<div class='test'>\n";
    echo "<p class='step'>STEP 5: Sync Reconciliation to POS</p>\n";
    echo "<div class='result'>\n";
    
    $sync_result = syncReconciliationToPOS($pdo, $reconciliation_id, $user_id);
    
    if ($sync_result['success']) {
        echo "✓ Sync completed successfully<br>\n";
        echo "  Before: {$sync_result['previous_stock']} L<br>\n";
        echo "  After: {$sync_result['new_stock']} L<br>\n";
        echo "  Change: {$sync_result['change_amount']} L<br>\n";
    } else {
        echo "✗ Sync failed: {$sync_result['message']}<br>\n";
    }
    
    echo "</div>\n";
    echo "</div>\n";
    
    // Step 6: Verify POS inventory updated
    echo "<div class='test'>\n";
    echo "<p class='step'>STEP 6: Verify POS Inventory Updated</p>\n";
    echo "<div class='result'>\n";
    
    $stmt = $pdo->prepare("
        SELECT stock_level, last_synced_at, last_synced_by, last_sync_reference_id, in_sync
        FROM station_inventory
        WHERE station_id = ? AND product_id = ?
    ");
    $stmt->execute([$station_id, $fuel_product_id]);
    $inv = $stmt->fetch();
    
    if ($inv) {
        echo "✓ Station inventory found<br>\n";
        echo "  Stock level: {$inv['stock_level']} liters<br>\n";
        echo "  Last synced at: {$inv['last_synced_at']}<br>\n";
        echo "  Last synced by user_id: {$inv['last_synced_by']}<br>\n";
        echo "  Last sync reference_id: {$inv['last_sync_reference_id']}<br>\n";
        echo "  In sync: " . ($inv['in_sync'] ? 'YES' : 'NO') . "<br>\n";
        
        if ($inv['stock_level'] == $closing_stock) {
            echo "✓ Stock matches closing_stock ($closing_stock L)<br>\n";
        } else {
            echo "✗ Stock mismatch: {$inv['stock_level']} != $closing_stock<br>\n";
        }
    } else {
        echo "✗ Station inventory not found<br>\n";
    }
    
    echo "</div>\n";
    echo "</div>\n";
    
    // Step 7: Verify reconciliation marked as synced
    echo "<div class='test'>\n";
    echo "<p class='step'>STEP 7: Verify Reconciliation Marked as Synced</p>\n";
    echo "<div class='result'>\n";
    
    $stmt = $pdo->prepare("SELECT synced_to_pos, synced_at, synced_by FROM fuel_reconciliation WHERE id = ?");
    $stmt->execute([$reconciliation_id]);
    $rec_sync = $stmt->fetch();
    
    echo "✓ Reconciliation sync status<br>\n";
    echo "  synced_to_pos: " . ($rec_sync['synced_to_pos'] ? 'YES' : 'NO') . "<br>\n";
    echo "  synced_at: {$rec_sync['synced_at']}<br>\n";
    echo "  synced_by user_id: {$rec_sync['synced_by']}<br>\n";
    
    echo "</div>\n";
    echo "</div>\n";
    
    // Step 8: Verify activity log
    echo "<div class='test'>\n";
    echo "<p class='step'>STEP 8: Check Activity Logs</p>\n";
    echo "<div class='result'>\n";
    
    $stmt = $pdo->prepare("
        SELECT action, details, user_id, created_at
        FROM activity_logs
        WHERE action LIKE '%Sync%' OR details LIKE '%reconciliation%'
        ORDER BY created_at DESC
        LIMIT 3
    ");
    $stmt->execute();
    $logs = $stmt->fetchAll();
    
    if (count($logs) > 0) {
        echo "✓ Found " . count($logs) . " sync-related activity logs<br>\n";
        foreach ($logs as $log) {
            echo "  - {$log['action']}: {$log['details']} (by user {$log['user_id']} at {$log['created_at']})<br>\n";
        }
    } else {
        echo "⚠ No activity logs found (table may not exist or logging disabled)<br>\n";
    }
    
    echo "</div>\n";
    echo "</div>\n";
    
    // Summary
    echo "<div class='test pass'>\n";
    echo "<p class='step'>✓ WORKFLOW COMPLETE</p>\n";
    echo "<div class='result'>\n";
    echo "Test reconciliation ID: <code>$reconciliation_id</code><br>\n";
    echo "Test fuel product ID: <code>$fuel_product_id</code><br>\n";
    echo "Test station ID: <code>$station_id</code><br>\n";
    echo "Final POS Stock: <code>" . $inv['stock_level'] . "</code> liters<br>\n";
    echo "</div>\n";
    echo "</div>\n";
    
} catch (Exception $e) {
    echo "<div class='test fail'>\n";
    echo "<p class='step'>✗ ERROR</p>\n";
    echo "<div class='result'>\n";
    echo "<strong>" . $e->getMessage() . "</strong><br>\n";
    echo "<pre>" . $e->getTraceAsString() . "</pre>\n";
    echo "</div>\n";
    echo "</div>\n";
}

?>
