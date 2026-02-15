<?php
/**
 * Create Fuel Pricing Table via PHP
 * 
 * This script runs the same setup as setup_fuel_pricing.php
 * but can be accessed via web browser which uses your XAMPP PHP credentials.
 */

require_once __DIR__ . '/public/db_connect.php';

try {
    echo "<!DOCTYPE html>";
    echo "<html lang='en'>";
    echo "<head>";
    echo "<meta charset='UTF-8'>";
    echo "<meta name='viewport' content='width=device-width, initial-scale=1.0'>";
    echo "<title>Fuel Pricing Setup</title>";
    echo "<style>";
    echo "body { font-family: Arial, sans-serif; padding: 20px; background: #f5f5f5; }";
    echo "h1 { color: #002F6C; }";
    echo "h2 { color: #003d7a; margin-top: 30px; border-bottom: 2px solid #ddd; padding-bottom: 10px; }";
    echo ".success { color: #28a745; font-weight: bold; }";
    echo ".error { color: #dc3545; font-weight: bold; }";
    echo ".info { color: #17a2b8; font-weight: bold; }";
    echo ".code { background: #f4f4f4; padding: 15px; border-radius: 4px; font-family: monospace; font-size: 12px; display: block; margin-bottom: 10px; }";
    echo "button { background: #002F6C; color: white; padding: 10px 20px; border: none; border-radius: 4px; cursor: pointer; font-size: 14px; }";
    echo "button:hover { background: #003d7a; }";
    echo "</style>";
    echo "</head>";
    echo "<body>";
    
    echo "<h1>🔧 Fuel Pricing Table Setup (via PHP)</h1>";
    echo "<p>This script creates the fuel_pricing table using your web server's MySQL connection (no password needed for web PHP).</p>";
    
    // Step 1: Create table
    echo "<div class='info'>Step 1: Creating fuel_pricing table...</div>";
    $sql1 = "CREATE TABLE IF NOT EXISTS `fuel_pricing` (
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
    
    try {
        $pdo->exec($sql1);
        echo "<div class='success'>✅ Table created successfully!</div>";
    } catch (PDOException $e) {
        echo "<div class='error'>❌ Error creating table: " . htmlspecialchars($e->getMessage()) . "</div>";
        die();
    }
    
    // Step 2: Insert default data
    echo "<div class='info'>Step 2: Inserting default fuel pricing data...</div>";
    $sql2 = "INSERT IGNORE INTO `fuel_pricing` (`station_id`, `fuel_type_id`, `price_per_liter`, `created_by`, `is_active`)
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
    
    try {
        $affected_rows = $pdo->exec($sql2);
        echo "<div class='success'>✅ Inserted $affected_rows default fuel price records</div>";
    } catch (PDOException $e) {
        echo "<div class='error'>❌ Error inserting data: " . htmlspecialchars($e->getMessage()) . "</div>";
        die();
    }
    
    // Step 3: Verify data
    echo "<div class='info'>Step 3: Verifying fuel pricing data...</div>";
    $stmt = $pdo->query("
        SELECT fp.*, ft.name as fuel_name
        FROM fuel_pricing fp
        INNER JOIN fuel_types ft ON fp.fuel_type_id = ft.id
        WHERE fp.is_active = 1
        ORDER BY ft.name
    ");
    
    $fuelPricing = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<h2>📊 Current Active Fuel Prices</h2>";
    echo "<table style='width: 100%; border-collapse: collapse; margin-top: 20px;'>";
    echo "<tr style='background: #002F6C; color: white;'><th>ID</th><th>Station</th><th>Fuel Type</th><th>Price/Liter</th><th>Active</th><th>Effective Date</th></tr>";
    
    foreach ($fuelPricing as $fp) {
        echo "<tr>";
        echo "<td>{$fp['id']}</td>";
        echo "<td>{$fp['station_id']}</td>";
        echo "<td>{$fp['fuel_name']}</td>";
        echo "<td>₱" . number_format($fp['price_per_liter'], 2) . "</td>";
        echo "<td>" . ($fp['is_active'] ? '✅ Yes' : '❌ No') . "</td>";
        echo "<td>{$fp['effective_date']}</td>";
        echo "</tr>";
    }
    
    echo "</table>";
    
    echo "<hr style='margin: 40px 0; border: 2px solid #ddd;'>";
    
    echo "<h2>✅ Setup Complete!</h2>";
    echo "<p>The fuel_pricing table has been created and populated with default data.</p>";
    echo "<p><strong>Next Steps:</strong></p>";
    echo "<ol>";
    echo "<li>Go to <a href='pos_multi.php' style='color: #002F6C; text-decoration: underline; font-weight: bold;'>Multi-Product POS</a></li>";
    echo "<li>Test adding multiple items to transactions</li>";
    echo "<li>Verify fuel prices auto-populate correctly</li>";
    echo "<li>Verify stock levels are checked</li>";
    echo "</ol>";
    echo "</body>";
    echo "</html>";
    
} catch (Exception $e) {
    echo "<div class='error'>❌ Database Error: " . htmlspecialchars($e->getMessage()) . "</div>";
}
?>
