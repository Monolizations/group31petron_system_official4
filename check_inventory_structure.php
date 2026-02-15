<?php
/**
 * Check inventory table structure
 */

require_once __DIR__ . '/public/db_connect.php';

echo "<!DOCTYPE html>";
echo "<html lang='en'>";
echo "<head>";
echo "<meta charset='UTF-8'>";
echo "<title>Check Inventory Table Structure</title>";
echo "</head>";
echo "<body style='padding: 20px; font-family: Arial, sans-serif;'>";

echo "<h1>📊 Database Structure Check</h1>";

// Check if inventory table exists
$exists = $pdo->query("SHOW TABLES LIKE 'inventory'");
if ($exists->rowCount() > 0) {
    echo "<div style='background: #d4edda; color: #155724; padding: 15px; border-radius: 8px; margin-bottom: 20px;'>";
    echo "<strong>✅ inventory table exists</strong>";
    echo "</div>";
    
    // Show table structure
    $columns = $pdo->query("SHOW COLUMNS FROM inventory");
    $column_list = $columns->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<h2>Inventory Table Columns:</h2>";
    echo "<table style='width: 100%; border-collapse: collapse;'>";
    echo "<tr style='background: #002F6C; color: white;'><th>Column</th><th>Type</th><th>Null</th><th>Key</th></tr>";
    foreach ($column_list as $col) {
        echo "<tr>";
        echo "<td style='border: 1px solid #ddd; padding: 8px;'><strong>{$col['Field']}</strong></td>";
        echo "<td style='border: 1px solid #ddd; padding: 8px;'>{$col['Type']}</td>";
        echo "<td style='border: 1px solid #ddd; padding: 8px;'>{$col['Null']}</td>";
        echo "<td style='border: 1px solid #ddd; padding: 8px;'>{$col['Key']}</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    // Check if unit column exists
    $has_unit = false;
    foreach ($column_list as $col) {
        if ($col['Field'] === 'unit') {
            $has_unit = true;
            break;
        }
    }
    
    echo "<h2>Column Check Results:</h2>";
    if ($has_unit) {
        echo "<p style='background: #d4edda; color: #155724; padding: 15px; border-radius: 8px;'>";
        echo "<strong>✅ 'unit' column EXISTS in inventory table</strong>";
        echo "</p>";
        echo "<p style='color: #155724;'>The unit column is present and should work correctly.</p>";
    } else {
        echo "<p style='background: #f8d7da; color: #721c24; padding: 15px; border-radius: 8px;'>";
        echo "<strong>❌ 'unit' column MISSING from inventory table</strong>";
        echo "</p>";
        echo "<p style='color: #721c24;'>This means the SQL query in pos.php will fail because it tries to select i.unit</p>";
        echo "<p><strong>Fix Required:</strong> Run this SQL to add the unit column:</p>";
        echo "<code style='background: #f4f4f4; padding: 15px; display: block;'>";
        echo "ALTER TABLE inventory ADD COLUMN unit VARCHAR(50) DEFAULT NULL AFTER stock_level;";
        echo "</code>";
    }
} else {
    echo "<div style='background: #f8d7da; color: #721c24; padding: 15px; border-radius: 8px;'>";
    echo "<strong>❌ inventory table does NOT exist</strong>";
    echo "</div>";
    echo "<p style='color: #721c24;'>Please run the database setup script first to create the inventory table.</p>";
}

// Also check products table structure
echo "<hr>";
echo "<h2>Products Table Structure Check:</h2>";
$products_columns = $pdo->query("SHOW COLUMNS FROM products");
$products_column_list = $products_columns->fetchAll(PDO::FETCH_ASSOC);

echo "<table style='width: 100%; border-collapse: collapse;'>";
echo "<tr style='background: #002F6C; color: white;'><th>Column</th><th>Type</th><th>Null</th></tr>";
foreach ($products_column_list as $col) {
    echo "<tr>";
    echo "<td style='border: 1px solid #ddd; padding: 8px;'><strong>{$col['Field']}</strong></td>";
    echo "<td style='border: 1px solid #ddd; padding: 8px;'>{$col['Type']}</td>";
    echo "<td style='border: 1px solid #ddd; padding: 8px;'>{$col['Null']}</td>";
    echo "</tr>";
}
echo "</table>";

echo "</body>";
echo "</html>";
?>
