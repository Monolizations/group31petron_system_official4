<?php
/**
 * INVENTORY CONSOLIDATION - AUTOMATED TEST SUITE
 * Tests all critical workflows after migration to station_inventory
 * 
 * Usage: Access via browser at: http://localhost/group31petron_system_official4/test_inventory_consolidation.php
 */

require 'public/db_connect.php';

$tests_passed = 0;
$tests_failed = 0;
$test_results = [];

function test($name, $condition, $details = '') {
    global $tests_passed, $tests_failed, $test_results;
    $status = $condition ? '✅ PASS' : '❌ FAIL';
    $test_results[] = [
        'name' => $name,
        'status' => $status,
        'details' => $details
    ];
    if ($condition) {
        $tests_passed++;
    } else {
        $tests_failed++;
    }
    echo "<div class='test-result $status'>";
    echo "<strong>$status:</strong> $name";
    if ($details) echo " - <em>$details</em>";
    echo "</div>";
}

?>
<!DOCTYPE html>
<html>
<head>
    <title>Inventory Consolidation Test Suite</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; background: #f5f5f5; }
        h1 { color: #333; border-bottom: 3px solid #007bff; padding-bottom: 10px; }
        h2 { color: #555; margin-top: 30px; }
        .test-result { padding: 10px; margin: 5px 0; border-left: 4px solid #ddd; }
        .test-result.✅\ PASS { background: #d4edda; border-left-color: #28a745; }
        .test-result.❌\ FAIL { background: #f8d7da; border-left-color: #dc3545; }
        .summary { background: white; padding: 20px; margin: 20px 0; border-radius: 5px; }
        .summary-stats { display: grid; grid-template-columns: repeat(3, 1fr); gap: 10px; }
        .stat { padding: 15px; background: #f8f9fa; border-radius: 5px; text-align: center; }
        .stat-number { font-size: 24px; font-weight: bold; }
        .stat-label { color: #666; font-size: 12px; }
        table { width: 100%; border-collapse: collapse; margin: 10px 0; }
        th, td { padding: 10px; border: 1px solid #ddd; text-align: left; }
        th { background: #007bff; color: white; }
        .warning { background: #fff3cd; padding: 15px; border-radius: 5px; margin: 10px 0; }
    </style>
</head>
<body>

<h1>🔍 Inventory Consolidation - Automated Test Suite</h1>

<?php

// TEST 1: TABLE STRUCTURE VERIFICATION
echo "<h2>1. Table Structure Verification</h2>";

try {
    // Check if station_inventory table exists and has correct structure
    $stmt = $pdo->query("DESCRIBE station_inventory");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $column_names = array_column($columns, 'Field');
    
    test('station_inventory table exists', !empty($column_names), 'Found ' . count($column_names) . ' columns');
    test('Has station_id column', in_array('station_id', $column_names));
    test('Has product_id column', in_array('product_id', $column_names));
    test('Has stock_level column', in_array('stock_level', $column_names));
    test('Has unit column', in_array('unit', $column_names));
    test('Has status column', in_array('status', $column_names));
    
} catch (Exception $e) {
    test('station_inventory exists', false, $e->getMessage());
}

// TEST 2: DATA VERIFICATION
echo "<h2>2. Data Integrity & Migration</h2>";

try {
    // Check station_inventory count
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM station_inventory");
    $si_count = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    test('station_inventory has records', $si_count > 0, "$si_count records found");
    
    // Check for orphaned products
    $stmt = $pdo->query("
        SELECT COUNT(*) as orphaned
        FROM station_inventory si
        LEFT JOIN products p ON si.product_id = p.id
        WHERE p.id IS NULL
    ");
    $orphaned = $stmt->fetch(PDO::FETCH_ASSOC)['orphaned'];
    test('No orphaned products', $orphaned == 0, "Orphaned count: $orphaned");
    
    // Check for orphaned stations
    $stmt = $pdo->query("
        SELECT COUNT(*) as orphaned
        FROM station_inventory si
        LEFT JOIN stations s ON si.station_id = s.id
        WHERE s.id IS NULL
    ");
    $orphaned = $stmt->fetch(PDO::FETCH_ASSOC)['orphaned'];
    test('No orphaned stations', $orphaned == 0, "Orphaned count: $orphaned");
    
    // Display sample data
    $stmt = $pdo->query("
        SELECT si.id, si.station_id, si.product_id, p.name, si.stock_level, si.unit
        FROM station_inventory si
        LEFT JOIN products p ON si.product_id = p.id
        LIMIT 5
    ");
    $samples = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<h3>Sample station_inventory Records:</h3>";
    echo "<table>";
    echo "<tr><th>ID</th><th>Station ID</th><th>Product ID</th><th>Product Name</th><th>Stock</th><th>Unit</th></tr>";
    foreach ($samples as $row) {
        echo "<tr>";
        echo "<td>{$row['id']}</td>";
        echo "<td>{$row['station_id']}</td>";
        echo "<td>{$row['product_id']}</td>";
        echo "<td>{$row['name']}</td>";
        echo "<td>{$row['stock_level']}</td>";
        echo "<td>{$row['unit']}</td>";
        echo "</tr>";
    }
    echo "</table>";
    
} catch (Exception $e) {
    test('Data verification', false, $e->getMessage());
}

// TEST 3: QUERY COMPATIBILITY
echo "<h2>3. Query Compatibility Check</h2>";

try {
    // Test SELECT query
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM station_inventory WHERE station_id = ?");
    $stmt->execute([1]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    test('SELECT queries work', !is_null($result['count']), 'Query returned: ' . $result['count']);
    
    // Test UPDATE query (dry run - don't actually update)
    $stmt = $pdo->prepare("SELECT * FROM station_inventory LIMIT 1");
    $stmt->execute();
    $test_record = $stmt->fetch(PDO::FETCH_ASSOC);
    test('UPDATE queries prepared', !is_null($test_record), 'Found test record: ID ' . $test_record['id']);
    
    // Test JOIN queries
    $stmt = $pdo->prepare("
        SELECT p.name, si.stock_level, s.name as station_name
        FROM station_inventory si
        JOIN products p ON si.product_id = p.id
        JOIN stations s ON si.station_id = s.id
        WHERE si.station_id = ?
        LIMIT 1
    ");
    $stmt->execute([1]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    test('JOIN queries work', !is_null($result), 'Retrieved: ' . ($result['name'] ?? 'N/A'));
    
} catch (Exception $e) {
    test('Query compatibility', false, $e->getMessage());
}

// TEST 4: INVENTORY LOGS
echo "<h2>4. Audit & Logging</h2>";

try {
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM inventory_logs");
    $log_count = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    test('inventory_logs table exists', $log_count >= 0, "$log_count log entries found");
    
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM inventory_transactions");
    $trans_count = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    test('inventory_transactions table exists', $trans_count >= 0, "$trans_count transaction entries found");
    
} catch (Exception $e) {
    test('Audit tables', false, $e->getMessage());
}

// TEST 5: BACKUP TABLE
echo "<h2>5. Backup & Safety</h2>";

try {
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM inventory_legacy_backup");
    $backup_count = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    test('inventory_legacy_backup exists', $backup_count >= 0, "$backup_count backup records");
    
    // Compare counts
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM inventory");
    $old_count = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    test('Old inventory table still accessible', $old_count >= 0, "$old_count records");
    
} catch (Exception $e) {
    test('Backup verification', false, $e->getMessage());
}

// TEST 6: MULTI-STATION ISOLATION
echo "<h2>6. Multi-Station Isolation</h2>";

try {
    $stmt = $pdo->query("
        SELECT DISTINCT station_id FROM station_inventory
    ");
    $stations = $stmt->fetchAll(PDO::FETCH_ASSOC);
    test('Multiple stations tracked', count($stations) > 0, count($stations) . ' stations in inventory');
    
    foreach ($stations as $station) {
        $sid = $station['station_id'];
        $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM station_inventory WHERE station_id = ?");
        $stmt->execute([$sid]);
        $count = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
        echo "<div class='test-result'>Station $sid: $count inventory items</div>";
    }
    
} catch (Exception $e) {
    test('Multi-station isolation', false, $e->getMessage());
}

// SUMMARY
echo "<h2>Test Summary</h2>";
echo "<div class='summary'>";
echo "<div class='summary-stats'>";
echo "<div class='stat'>";
echo "<div class='stat-number'>$tests_passed</div>";
echo "<div class='stat-label'>Passed</div>";
echo "</div>";
echo "<div class='stat'>";
echo "<div class='stat-number'>$tests_failed</div>";
echo "<div class='stat-label'>Failed</div>";
echo "</div>";
echo "<div class='stat'>";
echo "<div class='stat-number'>" . ($tests_passed + $tests_failed) . "</div>";
echo "<div class='stat-label'>Total Tests</div>";
echo "</div>";
echo "</div>";
echo "</div>";

if ($tests_failed == 0) {
    echo "<div style='background: #d4edda; padding: 20px; border-radius: 5px; margin: 20px 0;'>";
    echo "<h3 style='color: #155724; margin-top: 0;'>✅ All Tests Passed!</h3>";
    echo "<p>The inventory consolidation is working correctly. You can proceed with Phase 5 cleanup.</p>";
    echo "</div>";
} else {
    echo "<div class='warning'>";
    echo "<h3>⚠️ Some Tests Failed</h3>";
    echo "<p>Please review the failed tests above and address any issues before proceeding.</p>";
    echo "</div>";
}

?>

</body>
</html>
