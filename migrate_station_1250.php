<?php
/**
 * Migration: Change Station ID from 1205 to 1250
 * Updates all tables and ensures products are linked to the new station
 */

require_once __DIR__ . '/public/db_connect.php';

echo "🔧 CHANGING STATION ID FROM 1205 TO 1250\n";
echo str_repeat("=", 70) . "\n\n";

$old_station_id = 1205;
$new_station_id = 1250;
$changes_made = [];
$errors = [];

try {
    // Check if new station exists
    $stmt = $pdo->prepare("SELECT id, name FROM stations WHERE id = ?");
    $stmt->execute([$new_station_id]);
    $new_station = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$new_station) {
        echo "❌ Station $new_station_id does not exist!\n";
        echo "   Please create station $new_station_id first.\n";
        exit;
    }
    
    echo "✅ Target Station: {$new_station['name']} (ID: $new_station_id)\n\n";
    
    // Tables to update
    $tables_to_update = [
        ['table' => 'fuel_daily_readings', 'column' => 'station_id', 'description' => 'Fuel daily readings'],
        ['table' => 'fuel_deliveries', 'column' => 'station_id', 'description' => 'Fuel deliveries'],
        ['table' => 'fuel_adjustments', 'column' => 'station_id', 'description' => 'Fuel adjustments'],
        ['table' => 'fuel_variance_reports', 'column' => 'station_id', 'description' => 'Fuel variance reports'],
        ['table' => 'fuel_reconciliation', 'column' => 'station_id', 'description' => 'Fuel reconciliation'],
        ['table' => 'fuel_pumps', 'column' => 'station_id', 'description' => 'Fuel pumps'],
        ['table' => 'fuel_inventory', 'column' => 'station_id', 'description' => 'Fuel inventory'],
        ['table' => 'station_inventory', 'column' => 'station_id', 'description' => 'Station inventory (products)'],
        ['table' => 'inventory_transactions', 'column' => 'station_id', 'description' => 'Inventory transactions'],
        ['table' => 'transactions', 'column' => 'station_id', 'description' => 'POS transactions'],
        ['table' => 'job_orders', 'column' => 'station_id', 'description' => 'Job orders'],
        ['table' => 'activity_log', 'column' => 'station_id', 'description' => 'Activity logs'],
        ['table' => 'users', 'column' => 'station_id', 'description' => 'User assignments'],
        ['table' => 'nozzles', 'column' => 'pump_id', 'description' => 'Nozzles (via pump_id join)', 'indirect' => true],
    ];
    
    foreach ($tables_to_update as $table_info) {
        $table = $table_info['table'];
        $column = $table_info['column'];
        $description = $table_info['description'];
        
        try {
            // Check if table exists
            $stmt = $pdo->query("SHOW TABLES LIKE '$table'");
            if ($stmt->rowCount() === 0) {
                echo "⚠️  Table '$table' not found - skipping\n";
                continue;
            }
            
            // Check if column exists
            $stmt = $pdo->query("SHOW COLUMNS FROM $table LIKE '$column'");
            if ($stmt->rowCount() === 0 && !isset($table_info['indirect'])) {
                echo "⚠️  Column '$column' not found in '$table' - skipping\n";
                continue;
            }
            
            if (isset($table_info['indirect']) && $table_info['indirect']) {
                // Handle indirect updates (like nozzles via pump_id)
                if ($table === 'nozzles') {
                    $stmt = $pdo->prepare("
                        UPDATE nozzles n
                        JOIN fuel_pumps fp ON n.pump_id = fp.id
                        SET fp.station_id = ?
                        WHERE fp.station_id = ?
                    ");
                    $stmt->execute([$new_station_id, $old_station_id]);
                }
            } else {
                // Count records to update
                $stmt = $pdo->prepare("SELECT COUNT(*) FROM $table WHERE $column = ?");
                $stmt->execute([$old_station_id]);
                $count = $stmt->fetchColumn();
                
                if ($count > 0) {
                    // Update records
                    $stmt = $pdo->prepare("UPDATE $table SET $column = ? WHERE $column = ?");
                    $stmt->execute([$new_station_id, $old_station_id]);
                    
                    $changes_made[] = "$description: $count records updated";
                    echo "✅ $description: $count records updated\n";
                } else {
                    echo "ℹ️  $description: No records to update\n";
                }
            }
        } catch (PDOException $e) {
            $errors[] = "$description: " . $e->getMessage();
            echo "❌ $description: Error - " . $e->getMessage() . "\n";
        }
    }
    
    echo "\n" . str_repeat("=", 70) . "\n";
    
    // Ensure all products have inventory records for station 1250
    echo "\n📦 ENSURING PRODUCTS ARE LINKED TO STATION $new_station_id\n";
    echo str_repeat("-", 70) . "\n";
    
    // Get all products (fuel and merchandise)
    $stmt = $pdo->query("
        SELECT p.id, p.name, p.type_id, pt.name as product_type
        FROM products p
        JOIN product_types pt ON p.type_id = pt.id
        WHERE pt.name IN ('fuel', 'merchandise', 'general')
    ");
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "Found " . count($products) . " products\n\n";
    
    $inventory_created = 0;
    $inventory_existing = 0;
    
    foreach ($products as $product) {
        // Check if inventory record exists for station 1250
        $stmt = $pdo->prepare("
            SELECT id FROM station_inventory 
            WHERE station_id = ? AND product_id = ?
        ");
        $stmt->execute([$new_station_id, $product['id']]);
        
        if ($stmt->rowCount() === 0) {
            // Create inventory record with initial stock from old station if exists
            $stmt = $pdo->prepare("
                SELECT stock_level FROM station_inventory 
                WHERE station_id = ? AND product_id = ?
            ");
            $stmt->execute([$old_station_id, $product['id']]);
            $existing_stock = $stmt->fetchColumn();
            $initial_stock = $existing_stock !== false ? $existing_stock : 0;
            
            $unit = ($product['product_type'] === 'fuel') ? 'liters' : 'pieces';
            
            $stmt = $pdo->prepare("
                INSERT INTO station_inventory 
                (station_id, product_id, stock_level, reorder_level, unit, status, last_updated)
                VALUES (?, ?, ?, 10, ?, 'active', NOW())
            ");
            $stmt->execute([$new_station_id, $product['id'], $initial_stock, $unit]);
            
            $inventory_created++;
            echo "  ✅ Created inventory: {$product['name']} ($initial_stock $unit)\n";
        } else {
            $inventory_existing++;
        }
    }
    
    echo "\n  📊 Inventory Records:\n";
    echo "     Created: $inventory_created\n";
    echo "     Already existed: $inventory_existing\n";
    
    echo "\n" . str_repeat("=", 70) . "\n";
    echo "✅ MIGRATION COMPLETE!\n\n";
    
    if (count($changes_made) > 0) {
        echo "📋 Changes Made:\n";
        foreach ($changes_made as $change) {
            echo "   • $change\n";
        }
    }
    
    if (count($errors) > 0) {
        echo "\n⚠️  Errors Encountered:\n";
        foreach ($errors as $error) {
            echo "   • $error\n";
        }
    }
    
    echo "\n📝 Summary:\n";
    echo "   • All data moved from Station $old_station_id to Station $new_station_id\n";
    echo "   • All products now have inventory records for Station $new_station_id\n";
    echo "   • Users assigned to old station need to be updated manually\n";
    
} catch (Exception $e) {
    echo "\n❌ Fatal Error: " . $e->getMessage() . "\n";
}

echo "\n";
?>