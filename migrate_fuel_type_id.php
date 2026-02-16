<?php
/**
 * Migration Script: Add fuel_type_id column to products table
 * This allows fuel products to be linked directly to fuel_types
 * Making POS fuel dropdown work correctly
 */

// Database connection
$host = 'localhost';
$db = 'petron_pos_db_main';
$user = 'root';
$pass = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8mb4", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "Connected to database\n";
    
    // Check if column already exists
    $stmt = $pdo->prepare("SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME = 'products' AND COLUMN_NAME = 'fuel_type_id'");
    $stmt->execute();
    
    if ($stmt->rowCount() > 0) {
        echo "Column 'fuel_type_id' already exists in products table. No migration needed.\n";
        exit(0);
    }
    
    echo "Column does not exist. Starting migration...\n";
    
    // Step 1: Add the column
    echo "Step 1: Adding fuel_type_id column...\n";
    $pdo->exec("ALTER TABLE products 
    ADD COLUMN fuel_type_id INT(11) NULL COMMENT 'Link to fuel_types for fuel products' AFTER type_id,
    ADD CONSTRAINT `fk_product_fuel_type` FOREIGN KEY (`fuel_type_id`) 
      REFERENCES `fuel_types`(`id`) ON DELETE SET NULL");
    echo "✓ Column added successfully\n";
    
    // Step 2: Update existing fuel products
    echo "Step 2: Updating fuel_type_id values based on product names...\n";
    
    $updates = [
        1 => 'gasoline',
        2 => 'diesel',
        3 => 'lpg',
        4 => 'premium',
        5 => 'unleaded'
    ];
    
    $totalUpdated = 0;
    foreach ($updates as $fuel_type_id => $keyword) {
        $stmt = $pdo->prepare("UPDATE products SET fuel_type_id = ? WHERE type_id = 1 AND LOWER(name) LIKE ? AND fuel_type_id IS NULL");
        $stmt->execute([$fuel_type_id, "%$keyword%"]);
        $count = $stmt->rowCount();
        echo "  - Updated $count products with keyword '$keyword' to fuel_type_id = $fuel_type_id\n";
        $totalUpdated += $count;
    }
    
    echo "✓ Updated $totalUpdated fuel products\n";
    
    // Step 3: Show results
    echo "\nStep 3: Verification - Fuel products:\n";
    $stmt = $pdo->prepare("SELECT p.id, p.name, p.fuel_type_id, ft.name as fuel_type_name 
                           FROM products p 
                           LEFT JOIN fuel_types ft ON p.fuel_type_id = ft.id 
                           WHERE p.type_id = 1 
                           ORDER BY p.name");
    $stmt->execute();
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "Total fuel products: " . count($results) . "\n";
    foreach ($results as $product) {
        $ft = $product['fuel_type_name'] ? $product['fuel_type_name'] : 'NOT SET';
        echo "  - ID {$product['id']}: {$product['name']} → fuel_type_id={$product['fuel_type_id']} ({$ft})\n";
    }
    
    echo "\n✓ Migration completed successfully!\n";
    echo "POS fuel dropdown should now work correctly.\n";
    
} catch (Exception $e) {
    echo "✗ Migration failed: " . $e->getMessage() . "\n";
    exit(1);
}
?>
