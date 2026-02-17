<?php
/**
 * Fix: Populate fuel_station_id column from pump_id
 * This ensures backward compatibility with queries using fuel_station_id
 */

require_once __DIR__ . '/public/db_connect.php';

echo "🔧 Fixing fuel_daily_readings table...\n\n";

try {
    // Check if fuel_station_id column exists
    $stmt = $pdo->query("SHOW COLUMNS FROM fuel_daily_readings LIKE 'fuel_station_id'");
    if ($stmt->rowCount() === 0) {
        echo "➕ Adding fuel_station_id column...\n";
        $pdo->exec("ALTER TABLE `fuel_daily_readings` ADD COLUMN `fuel_station_id` int(11) DEFAULT NULL AFTER `pump_id`");
        echo "✅ Column added\n";
    } else {
        echo "✅ fuel_station_id column already exists\n";
    }
    
    // Update NULL fuel_station_id values from pump_id
    $stmt = $pdo->query("SELECT COUNT(*) FROM fuel_daily_readings WHERE fuel_station_id IS NULL AND pump_id IS NOT NULL");
    $null_count = $stmt->fetchColumn();
    
    if ($null_count > 0) {
        echo "➕ Updating $null_count records with NULL fuel_station_id...\n";
        $pdo->exec("UPDATE `fuel_daily_readings` SET `fuel_station_id` = `pump_id` WHERE `fuel_station_id` IS NULL AND `pump_id` IS NOT NULL");
        echo "✅ Updated $null_count records\n";
    } else {
        echo "✅ All records already have fuel_station_id populated\n";
    }
    
    // Show statistics
    $stmt = $pdo->query("SELECT COUNT(*) FROM fuel_daily_readings");
    $total = $stmt->fetchColumn();
    
    $stmt = $pdo->query("SELECT COUNT(*) FROM fuel_daily_readings WHERE fuel_station_id IS NOT NULL");
    $populated = $stmt->fetchColumn();
    
    echo "\n📊 Statistics:\n";
    echo "   Total readings: $total\n";
    echo "   With fuel_station_id: $populated\n";
    
    echo "\n✅ Fix complete! Pump numbers should now display correctly.\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
?>