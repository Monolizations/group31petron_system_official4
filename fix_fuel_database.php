<?php
/**
 * Database Fix Script for Fuel Management System
 * Executes the database fixes required for the fuel management system
 */

require_once __DIR__ . '/public/db_connect.php';

echo "🔧 FUEL MANAGEMENT SYSTEM - DATABASE FIX SCRIPT\n";
echo "================================================\n\n";

$success = [];
$errors = [];

try {
    echo "1. Creating fuel_variance_reports table...\n";
    $sql1 = "CREATE TABLE IF NOT EXISTS `fuel_variance_reports` (
      `id` int(11) NOT NULL AUTO_INCREMENT,
      `station_id` int(11) NOT NULL,
      `report_date` date NOT NULL,
      `fuel_type` varchar(50) NOT NULL,
      `expected_stock` decimal(10,2) NOT NULL,
      `actual_stock` decimal(10,2) NOT NULL,
      `variance_liters` decimal(10,2) NOT NULL,
      `variance_percent` decimal(5,2) NOT NULL,
      `reason` text DEFAULT NULL,
      `status` enum('Open','Under Investigation','Resolved') DEFAULT 'Open',
      `investigated_by` int(11) DEFAULT NULL,
      `resolution_notes` text DEFAULT NULL,
      `created_at` datetime DEFAULT current_timestamp(),
      `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
      PRIMARY KEY (`id`),
      KEY `idx_station_date` (`station_id`, `report_date`),
      KEY `idx_fuel_type` (`fuel_type`),
      KEY `idx_status` (`status`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci";
    
    $pdo->exec($sql1);
    $success[] = "✅ fuel_variance_reports table created successfully";
    
    echo "2. Creating fuel_stations view for compatibility...\n";
    $pdo->exec("DROP VIEW IF EXISTS `fuel_stations`");
    
    $sql2 = "CREATE VIEW `fuel_stations` AS 
    SELECT 
        fp.id,
        fp.station_id,
        fp.pump_number,
        ft.name as fuel_type,
        fp.capacity,
        fp.status,
        fp.created_at
    FROM fuel_pumps fp
    LEFT JOIN fuel_types ft ON fp.fuel_type_id = ft.id";
    
    $pdo->exec($sql2);
    $success[] = "✅ fuel_stations view created successfully";
    
    echo "3. Adding missing columns to existing tables...\n";
    
    // Add columns to fuel_deliveries
    $columns_deliveries = [
        "ALTER TABLE `fuel_deliveries` ADD COLUMN IF NOT EXISTS `verified_by` int(11) DEFAULT NULL",
        "ALTER TABLE `fuel_deliveries` ADD COLUMN IF NOT EXISTS `verified_at` datetime DEFAULT NULL"
    ];
    
    foreach ($columns_deliveries as $sql) {
        try {
            $pdo->exec($sql);
        } catch (PDOException $e) {
            if (strpos($e->getMessage(), 'Duplicate column') === false) {
                $errors[] = "Error adding column to fuel_deliveries: " . $e->getMessage();
            }
        }
    }
    $success[] = "✅ Updated fuel_deliveries table structure";
    
    // Add columns to fuel_adjustments
    $columns_adjustments = [
        "ALTER TABLE `fuel_adjustments` ADD COLUMN IF NOT EXISTS `approved_by` int(11) DEFAULT NULL",
        "ALTER TABLE `fuel_adjustments` ADD COLUMN IF NOT EXISTS `approved_at` datetime DEFAULT NULL"
    ];
    
    foreach ($columns_adjustments as $sql) {
        try {
            $pdo->exec($sql);
        } catch (PDOException $e) {
            if (strpos($e->getMessage(), 'Duplicate column') === false) {
                $errors[] = "Error adding column to fuel_adjustments: " . $e->getMessage();
            }
        }
    }
    $success[] = "✅ Updated fuel_adjustments table structure";
    
    // Add fuel_station_id to fuel_daily_readings
    try {
        $pdo->exec("ALTER TABLE `fuel_daily_readings` ADD COLUMN IF NOT EXISTS `fuel_station_id` int(11) DEFAULT NULL AFTER `pump_id`");
        
        // Update existing records
        $pdo->exec("UPDATE `fuel_daily_readings` 
                   SET `fuel_station_id` = `pump_id` 
                   WHERE `fuel_station_id` IS NULL");
        $success[] = "✅ Added and populated fuel_station_id column in fuel_daily_readings";
        
    } catch (PDOException $e) {
        if (strpos($e->getMessage(), 'Duplicate column') === false) {
            $errors[] = "Error adding fuel_station_id: " . $e->getMessage();
        } else {
            $success[] = "ℹ️ fuel_station_id column already exists";
        }
    }
    
    echo "4. Adding reconciliation columns...\n";
    $reconciliation_columns = [
        "ALTER TABLE `fuel_reconciliation` ADD COLUMN IF NOT EXISTS `opening_stock` decimal(10,2) DEFAULT 0.00",
        "ALTER TABLE `fuel_reconciliation` ADD COLUMN IF NOT EXISTS `deliveries` decimal(10,2) DEFAULT 0.00",
        "ALTER TABLE `fuel_reconciliation` ADD COLUMN IF NOT EXISTS `sales` decimal(10,2) DEFAULT 0.00",
        "ALTER TABLE `fuel_reconciliation` ADD COLUMN IF NOT EXISTS `adjustments` decimal(10,2) DEFAULT 0.00",
        "ALTER TABLE `fuel_reconciliation` ADD COLUMN IF NOT EXISTS `closing_stock` decimal(10,2) DEFAULT 0.00",
        "ALTER TABLE `fuel_reconciliation` ADD COLUMN IF NOT EXISTS `fuel_type` varchar(50) DEFAULT NULL"
    ];
    
    foreach ($reconciliation_columns as $sql) {
        try {
            $pdo->exec($sql);
        } catch (PDOException $e) {
            if (strpos($e->getMessage(), 'Duplicate column') === false) {
                $errors[] = "Error adding reconciliation column: " . $e->getMessage();
            }
        }
    }
    $success[] = "✅ Updated fuel_reconciliation table structure";
    
    echo "5. Updating fuel_type values...\n";
    $pdo->exec("UPDATE fuel_reconciliation fr
               LEFT JOIN fuel_types ft ON fr.fuel_type_id = ft.id
               SET fr.fuel_type = ft.name
               WHERE fr.fuel_type IS NULL OR fr.fuel_type = ''");
    $success[] = "✅ Updated fuel_type values in fuel_reconciliation";
    
    echo "6. Inserting sample variance reports...\n";
    $pdo->exec("INSERT IGNORE INTO `fuel_variance_reports` 
               (`station_id`, `report_date`, `fuel_type`, `expected_stock`, `actual_stock`, `variance_liters`, `variance_percent`, `reason`, `status`) 
               VALUES 
               (226, '2026-02-10', 'Gasoline', 1000.00, 980.00, -20.00, -2.00, 'Minor evaporation loss', 'Open'),
               (226, '2026-02-09', 'Diesel', 800.00, 805.00, 5.00, 0.63, 'Measurement variance', 'Under Investigation')");
    $success[] = "✅ Sample variance reports inserted";
    
    echo "\n🎉 DATABASE FIXES COMPLETED!\n";
    echo "============================\n\n";
    
    echo "✅ SUCCESSFUL CHANGES:\n";
    foreach ($success as $item) {
        echo "   {$item}\n";
    }
    
    if (!empty($errors)) {
        echo "\n⚠️ NON-CRITICAL ERRORS:\n";
        foreach ($errors as $error) {
            echo "   {$error}\n";
        }
    }
    
    echo "\n🔍 VERIFICATION:\n";
    
    // Test that fuel_stations view works
    $test_query = $pdo->query("SELECT COUNT(*) as count FROM fuel_stations");
    $result = $test_query->fetch();
    echo "   📊 fuel_stations view: {$result['count']} pumps found\n";
    
    // Test variance reports table
    $test_query = $pdo->query("SELECT COUNT(*) as count FROM fuel_variance_reports");
    $result = $test_query->fetch();
    echo "   📊 fuel_variance_reports table: {$result['count']} reports found\n";
    
    echo "\n🎯 DATABASE PHASE COMPLETED!\n";
    echo "Next: Creating backend modal files for manager interface...\n\n";
    
} catch (Exception $e) {
    echo "\n❌ CRITICAL ERROR: " . $e->getMessage() . "\n";
    
    if (!empty($success)) {
        echo "\n✅ Completed before error:\n";
        foreach ($success as $item) {
            echo "   {$item}\n";
        }
    }
    
    exit(1);
}
?>