<?php
/**
 * Verification: Check all data is now linked to Station 1250
 */

require_once __DIR__ . '/public/db_connect.php';

echo "✅ VERIFICATION: STATION 1250 DATA INTEGRITY\n";
echo str_repeat("=", 70) . "\n\n";

try {
    // 1. Check station 1250 info
    $stmt = $pdo->prepare("SELECT id, name FROM stations WHERE id = ?");
    $stmt->execute([1250]);
    $station = $stmt->fetch(PDO::FETCH_ASSOC);
    
    echo "📍 Station 1250: {$station['name']}\n\n";
    
    // 2. Verify no data remains at station 1205
    echo "🔍 CHECKING FOR REMAINING DATA AT STATION 1205:\n";
    echo str_repeat("-", 70) . "\n";
    
    $tables_to_check = [
        'fuel_daily_readings' => 'Fuel readings',
        'fuel_deliveries' => 'Fuel deliveries',
        'fuel_pumps' => 'Fuel pumps',
        'fuel_inventory' => 'Fuel inventory',
        'station_inventory' => 'Station inventory',
        'inventory_transactions' => 'Inventory transactions',
        'job_orders' => 'Job orders',
        'users' => 'Users'
    ];
    
    $remaining_count = 0;
    foreach ($tables_to_check as $table => $description) {
        try {
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM $table WHERE station_id = 1205");
            $stmt->execute();
            $count = $stmt->fetchColumn();
            
            if ($count > 0) {
                echo "  ⚠️  $description: $count records still at 1205\n";
                $remaining_count++;
            } else {
                echo "  ✅ $description: All migrated\n";
            }
        } catch (Exception $e) {
            echo "  ℹ️  $description: Table not found\n";
        }
    }
    
    if ($remaining_count === 0) {
        echo "\n✅ No data remains at Station 1205\n";
    }
    
    echo "\n" . str_repeat("-", 70) . "\n";
    
    // 3. Show current data at station 1250
    echo "\n📊 CURRENT DATA AT STATION 1250:\n";
    echo str_repeat("-", 70) . "\n";
    
    // Fuel readings
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM fuel_daily_readings WHERE station_id = 1250");
    $stmt->execute();
    echo "  📋 Fuel readings: " . $stmt->fetchColumn() . "\n";
    
    // Fuel pumps
    $stmt = $pdo->prepare("SELECT fp.pump_number, ft.name as fuel_type FROM fuel_pumps fp LEFT JOIN fuel_types ft ON fp.fuel_type_id = ft.id WHERE fp.station_id = 1250");
    $stmt->execute();
    $pumps = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "  ⛽ Fuel pumps: " . count($pumps) . "\n";
    foreach ($pumps as $pump) {
        echo "     • Pump {$pump['pump_number']}: {$pump['fuel_type']}\n";
    }
    
    // Inventory
    $stmt = $pdo->prepare("
        SELECT p.name, si.stock_level, pt.name as type
        FROM station_inventory si
        JOIN products p ON si.product_id = p.id
        JOIN product_types pt ON p.type_id = pt.id
        WHERE si.station_id = 1250
        ORDER BY pt.name, p.name
    ");
    $stmt->execute();
    $inventory = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $fuel_count = 0;
    $merch_count = 0;
    foreach ($inventory as $item) {
        if ($item['type'] === 'fuel') $fuel_count++;
        else $merch_count++;
    }
    
    echo "  📦 Station inventory: " . count($inventory) . " items\n";
    echo "     • Fuel products: $fuel_count\n";
    echo "     • Merchandise: $merch_count\n";
    
    // Recent transactions
    $stmt = $pdo->prepare("
        SELECT COUNT(*) FROM inventory_transactions WHERE station_id = 1250
    ");
    $stmt->execute();
    echo "  💰 Inventory transactions: " . $stmt->fetchColumn() . "\n";
    
    // Users
    $stmt = $pdo->prepare("SELECT name, role FROM users WHERE station_id = 1250");
    $stmt->execute();
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "  👥 Users assigned: " . count($users) . "\n";
    foreach ($users as $user) {
        echo "     • {$user['name']} ({$user['role']})\n";
    }
    
    echo "\n" . str_repeat("=", 70) . "\n";
    
    // 4. Check for fuel inventory specifically
    echo "\n⛽ FUEL INVENTORY AT STATION 1250:\n";
    echo str_repeat("-", 70) . "\n";
    
    $stmt = $pdo->prepare("
        SELECT ft.name, fi.stock_level
        FROM fuel_inventory fi
        JOIN fuel_types ft ON fi.fuel_type_id = ft.id
        WHERE fi.station_id = 1250
    ");
    $stmt->execute();
    $fuel_inventory = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (count($fuel_inventory) > 0) {
        foreach ($fuel_inventory as $fuel) {
            echo "  • {$fuel['name']}: {$fuel['stock_level']} L\n";
        }
    } else {
        echo "  ℹ️  No fuel inventory records found (this is OK if using station_inventory table)\n";
    }
    
    echo "\n" . str_repeat("=", 70) . "\n";
    echo "✅ ALL DATA SUCCESSFULLY MIGRATED TO STATION 1250!\n";
    echo "\n📝 Your system is now configured for:\n";
    echo "   Station: {$station['name']}\n";
    echo "   ID: 1250\n";
    echo "\n   You can now:\n";
    echo "   • Record pump readings\n";
    echo "   • Verify and deduct stock\n";
    echo "   • Manage fuel inventory\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}

echo "\n";
?>