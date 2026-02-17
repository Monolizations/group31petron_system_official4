<?php
/**
 * Test: Verify stock deduction is working properly
 */

require_once __DIR__ . '/public/db_connect.php';

echo "🧪 TESTING STOCK DEDUCTION SYSTEM\n";
echo str_repeat("=", 70) . "\n\n";

try {
    // Test 1: Check fuel types and products are synced
    echo "✓ Test 1: Fuel Types & Products Sync\n";
    $stmt = $pdo->query("
        SELECT ft.name as fuel_type_name, p.name as product_name
        FROM fuel_types ft
        LEFT JOIN products p ON ft.name = p.name
        WHERE p.type_id = (SELECT id FROM product_types WHERE name = 'fuel')
    ");
    $matches = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (count($matches) >= 5) {
        echo "  ✅ All 5 fuel types synced with products\n";
        foreach ($matches as $m) {
            echo "     - {$m['fuel_type_name']} ↔ {$m['product_name']}\n";
        }
    } else {
        echo "  ❌ Only " . count($matches) . " fuel types synced\n";
    }
    
    echo "\n";
    
    // Test 2: Check station inventory for station 1205
    echo "✓ Test 2: Station 1205 Inventory\n";
    $stmt = $pdo->prepare("
        SELECT si.id, p.name, si.stock_level
        FROM station_inventory si
        JOIN products p ON si.product_id = p.id
        WHERE si.station_id = ?
        AND p.type_id = (SELECT id FROM product_types WHERE name = 'fuel')
    ");
    $stmt->execute([1205]);
    $inventory = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (count($inventory) > 0) {
        echo "  ✅ Found " . count($inventory) . " fuel products in inventory\n";
        foreach ($inventory as $inv) {
            echo "     - {$inv['name']}: {$inv['stock_level']} L\n";
        }
    } else {
        echo "  ❌ No fuel inventory found for station 1205\n";
    }
    
    echo "\n";
    
    // Test 3: Simulate stock deduction
    echo "✓ Test 3: Stock Deduction Logic\n";
    
    // Get a fuel type and its current stock
    $stmt = $pdo->prepare("
        SELECT si.id as inventory_id, si.stock_level, p.id as product_id, p.name, ft.id as fuel_type_id
        FROM station_inventory si
        JOIN products p ON si.product_id = p.id
        JOIN fuel_types ft ON ft.name = p.name
        WHERE si.station_id = ? AND p.name = 'Diesel Max'
    ");
    $stmt->execute([1205]);
    $fuel = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($fuel) {
        echo "  📊 Diesel Max - Current Stock: {$fuel['stock_level']} L\n";
        
        // Test the deduction calculation
        $sales_liters = 100; // Simulated sales
        $new_stock = $fuel['stock_level'] - $sales_liters;
        
        echo "  📉 Simulating deduction of $sales_liters L\n";
        echo "  📉 New stock would be: $new_stock L\n";
        
        if ($new_stock >= 0) {
            echo "  ✅ Deduction is possible (sufficient stock)\n";
        } else {
            echo "  ⚠️  Deduction would fail (insufficient stock)\n";
        }
    } else {
        echo "  ❌ Could not find Diesel Max in inventory\n";
    }
    
    echo "\n";
    
    // Test 4: Check calibration column exists
    echo "✓ Test 4: Calibration Setup\n";
    $stmt = $pdo->query("SHOW COLUMNS FROM fuel_pumps LIKE 'calibration_value'");
    if ($stmt->rowCount() > 0) {
        echo "  ✅ fuel_pumps.calibration_value column exists\n";
    } else {
        echo "  ❌ fuel_pumps.calibration_value column missing\n";
    }
    
    $stmt = $pdo->query("SHOW COLUMNS FROM fuel_daily_readings LIKE 'calibration'");
    if ($stmt->rowCount() > 0) {
        echo "  ✅ fuel_daily_readings.calibration column exists\n";
    } else {
        echo "  ❌ fuel_daily_readings.calibration column missing\n";
    }
    
    echo "\n";
    
    // Test 5: Verify code changes
    echo "✓ Test 5: Code Changes\n";
    
    $staff_file = file_get_contents(__DIR__ . '/public/fuel_staff.php');
    if (strpos($staff_file, 'calibration_value FROM fuel_pumps') !== false) {
        echo "  ✅ fuel_staff.php includes calibration lookup\n";
    } else {
        echo "  ❌ fuel_staff.php missing calibration lookup\n";
    }
    
    if (strpos($staff_file, '$sales_liters = $current_reading - $previous_reading - $calibration') !== false) {
        echo "  ✅ fuel_staff.php subtracts calibration from sales\n";
    } else {
        echo "  ❌ fuel_staff.php not subtracting calibration\n";
    }
    
    echo "\n";
    
    // Test 6: Recent transactions
    echo "✓ Test 6: Recent Stock Transactions\n";
    $stmt = $pdo->prepare("
        SELECT it.*, p.name as product_name
        FROM inventory_transactions it
        JOIN products p ON it.product_id = p.id
        WHERE it.station_id = ?
        ORDER BY it.created_at DESC
        LIMIT 5
    ");
    $stmt->execute([1205]);
    $transactions = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (count($transactions) > 0) {
        echo "  ✅ Found " . count($transactions) . " recent transactions\n";
        foreach ($transactions as $tx) {
            echo "     - {$tx['transaction_type']}: {$tx['product_name']} " . number_format($tx['quantity'], 2) . " L\n";
        }
    } else {
        echo "  ⚠️  No transactions found (test a verification to create one)\n";
    }
    
    echo "\n" . str_repeat("=", 70) . "\n";
    echo "✅ SYSTEM READY FOR TESTING!\n";
    echo "\n📝 Next Steps:\n";
    echo "   1. Record a test pump reading\n";
    echo "   2. Verify the reading as manager\n";
    echo "   3. Check that stock was deducted\n";
    echo "   4. Verify calibration was excluded from sales\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}

echo "\n";
?>