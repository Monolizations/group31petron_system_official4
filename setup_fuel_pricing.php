<?php
/**
 * Create Fuel Pricing Configuration Table
 * Run this script to create the fuel_pricing table and insert default data
 */

require_once __DIR__ . '/public/db_connect.php';

try {
    // Create fuel_pricing table
    $sql = "CREATE TABLE IF NOT EXISTS `fuel_pricing` (
      `id` int(11) NOT NULL AUTO_INCREMENT,
      `station_id` int(11) NOT NULL,
      `fuel_type_id` int(11) NOT NULL COMMENT 'Reference to fuel_types table',
      `price_per_liter` decimal(10,2) NOT NULL DEFAULT 0.00 COMMENT 'Price per liter',
      `effective_date` datetime DEFAULT CURRENT_TIMESTAMP COMMENT 'When this price became effective',
      `created_by` int(11) DEFAULT NULL COMMENT 'User who set this price',
      `is_active` tinyint(1) DEFAULT 1 COMMENT 'Whether this price is currently active',
      `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
      PRIMARY KEY (`id`),
      KEY `idx_station_fuel` (`station_id`, `fuel_type_id`, `is_active`),
      KEY `idx_effective_date` (`effective_date`),
      FOREIGN KEY (`fuel_type_id`) REFERENCES `fuel_types`(`id`) ON DELETE CASCADE,
      FOREIGN KEY (`station_id`) REFERENCES `stations`(`id`) ON DELETE CASCADE,
      FOREIGN KEY (`created_by`) REFERENCES `users`(`id`) ON DELETE SET NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='Fuel pricing configuration per station'";
    
    $pdo->exec($sql);
    echo "✅ fuel_pricing table created successfully!\n";
    
    // Insert default fuel pricing for existing fuel types
    $sql = "INSERT IGNORE INTO `fuel_pricing` (`station_id`, `fuel_type_id`, `price_per_liter`, `created_by`, `is_active`)
    SELECT 
        1 as station_id,
        ft.id as fuel_type_id,
        CASE ft.id
            WHEN 1 THEN 55.00
            WHEN 2 THEN 60.00
            WHEN 3 THEN 45.00
            WHEN 4 THEN 60.00
            WHEN 5 THEN 58.00
        END as price_per_liter,
        NULL as created_by,
        1 as is_active
    FROM `fuel_types` ft
    WHERE NOT EXISTS (
        SELECT 1 FROM `fuel_pricing` fp 
        WHERE fp.fuel_type_id = ft.id AND fp.station_id = 1
    )";
    
    $pdo->exec($sql);
    echo "✅ Default fuel pricing data inserted!\n";
    
    // Show current fuel pricing
    $stmt = $pdo->query("
        SELECT fp.*, ft.name as fuel_name
        FROM fuel_pricing fp
        INNER JOIN fuel_types ft ON fp.fuel_type_id = ft.id
        WHERE fp.is_active = 1
        ORDER BY ft.name
    ");
    
    $fuelPricing = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "\n📊 Current Active Fuel Prices:\n";
    echo str_repeat("-", 60) . "\n";
    echo sprintf("%-20s | %15s | %10s\n", "Fuel Type", "Price/Liter", "Station");
    echo str_repeat("-", 60) . "\n";
    
    foreach ($fuelPricing as $fp) {
        echo sprintf("%-20s | ₱%13.2f | %10d\n", $fp['fuel_name'], $fp['price_per_liter'], $fp['station_id']);
    }
    
    echo str_repeat("-", 60) . "\n";
    echo "\n✅ Setup complete!\n";
    
} catch (PDOException $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    exit(1);
}
?>
