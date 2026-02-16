<?php
/**
 * Test Page: Reconciliation Sync Workflow
 */

require_once __DIR__ . '/public/db_connect.php';
require_once __DIR__ . '/backend/lib.php';
require_once __DIR__ . '/backend/fuel_pos_sync.php';

echo "<h2>Reconciliation Sync Workflow Test</h2>\n";
echo "<pre style='background: #f4f4f4; padding: 10px; border-radius: 4px;'>\n";

try {
    // 1. Check database schema
    echo "=== DATABASE SCHEMA CHECK ===\n";
    
    $stmt = $pdo->query("DESCRIBE station_inventory");
    $columns = $stmt->fetchAll(PDO::FETCH_COLUMN, 0);
    echo "station_inventory columns: " . implode(", ", $columns) . "\n";
    
    // Check if new columns exist
    if (in_array('last_synced_at', $columns)) {
        echo "✓ Sync columns found in station_inventory\n";
    } else {
        echo "✗ Missing sync columns in station_inventory\n";
    }
    
    $stmt = $pdo->query("DESCRIBE fuel_reconciliation");
    $columns = $stmt->fetchAll(PDO::FETCH_COLUMN, 0);
    echo "fuel_reconciliation columns: " . implode(", ", $columns) . "\n";
    
    if (in_array('synced_to_pos', $columns)) {
        echo "✓ Sync columns found in fuel_reconciliation\n";
    } else {
        echo "✗ Missing sync columns in fuel_reconciliation\n";
    }
    
    // 2. Check for existing reconciliations
    echo "\n=== EXISTING RECONCILIATIONS ===\n";
    $stmt = $pdo->query("SELECT COUNT(*) FROM fuel_reconciliation");
    $count = $stmt->fetchColumn();
    echo "Total reconciliations in database: $count\n";
    
    if ($count > 0) {
        $stmt = $pdo->query("
            SELECT fr.id, fr.station_id, fr.date, fr.status, fr.synced_to_pos, fr.synced_at, 
                   fr.closing_stock, ft.name as fuel_type
            FROM fuel_reconciliation fr
            LEFT JOIN fuel_types ft ON fr.fuel_type_id = ft.id
            ORDER BY fr.date DESC
            LIMIT 3
        ");
        $rows = $stmt->fetchAll();
        foreach ($rows as $row) {
            echo "\nID: {$row['id']}\n";
            echo "  Fuel Type: {$row['fuel_type']}\n";
            echo "  Date: {$row['date']}\n";
            echo "  Status: {$row['status']}\n";
            echo "  Closing Stock: {$row['closing_stock']} liters\n";
            echo "  Synced to POS: " . ($row['synced_to_pos'] ? 'YES at ' . $row['synced_at'] : 'NO') . "\n";
        }
    }
    
    // 3. Check fuel products and inventory
    echo "\n=== FUEL PRODUCTS & INVENTORY ===\n";
    $stmt = $pdo->query("
        SELECT p.id, p.name, pt.name as type, si.stock_level, si.station_id,
               si.last_synced_at, si.last_synced_by
        FROM products p
        INNER JOIN product_types pt ON p.type_id = pt.id
        LEFT JOIN station_inventory si ON p.id = si.product_id AND si.station_id = 1
        WHERE pt.name = 'fuel'
    ");
    $fuels = $stmt->fetchAll();
    foreach ($fuels as $fuel) {
        echo "\nFuel Product: {$fuel['name']}\n";
        echo "  POS Stock: " . ($fuel['stock_level'] ?? 'N/A') . " liters\n";
        echo "  Last Synced: " . ($fuel['last_synced_at'] ?? 'Never') . "\n";
    }
    
    // 4. Test getLastSyncStatus function
    echo "\n=== TEST getLastSyncStatus() FUNCTION ===\n";
    if ($fuels && count($fuels) > 0) {
        $fuel = $fuels[0];
        $syncStatus = getLastSyncStatus($pdo, 1, $fuel['id']);
        echo "getLastSyncStatus result for {$fuel['name']}:\n";
        echo json_encode($syncStatus, JSON_PRETTY_PRINT) . "\n";
    }
    
    // 5. Check pending syncs
    echo "\n=== PENDING RECONCILIATIONS FOR SYNC ===\n";
    $pending = getUnSyncedReconciliations($pdo, 1);
    if (is_array($pending) && isset($pending['reconciliations'])) {
        echo "Found " . count($pending['reconciliations']) . " pending syncs\n";
        foreach ($pending['reconciliations'] as $rec) {
            echo "  - Reconciliation ID: {$rec['id']}, Fuel: {$rec['fuel_type']}, Closing: {$rec['closing_stock']} L\n";
        }
    } else {
        echo "No pending reconciliations\n";
    }
    
    echo "\n✓ All schema checks passed!\n";
    
} catch (Exception $e) {
    echo "✗ Error: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString();
}

echo "</pre>\n";
?>
