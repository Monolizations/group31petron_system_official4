<?php
// Fix POS Database Schema Issues
require_once 'public/db_connect.php';

echo "<h2>Fixing POS Database Schema Issues</h2>\n";

try {
    // 1. Add fuel_type_id column to products table if missing
    echo "<h3>1. Adding fuel_type_id column to products table</h3>\n";
    try {
        $pdo->exec("ALTER TABLE products ADD COLUMN fuel_type_id INT(11) NULL COMMENT 'Link to fuel_types for fuel products' AFTER type_id");
        echo "✅ Added fuel_type_id column to products table\n";
    } catch(PDOException $e) {
        if (strpos($e->getMessage(), 'Duplicate column name') !== false) {
            echo "✅ fuel_type_id column already exists\n";
        } else {
            echo "❌ Error adding fuel_type_id column: " . $e->getMessage() . "\n";
        }
    }

    // 2. Create fuel_pricing table if missing
    echo "<h3>2. Creating fuel_pricing table</h3>\n";
    try {
        $pdo->exec("CREATE TABLE IF NOT EXISTS fuel_pricing (
            id INT(11) NOT NULL AUTO_INCREMENT,
            station_id INT(11) NOT NULL,
            fuel_type_id INT(11) NOT NULL,
            price_per_liter DECIMAL(10,2) NOT NULL,
            effective_date DATETIME DEFAULT CURRENT_TIMESTAMP,
            is_active BOOLEAN DEFAULT TRUE,
            created_by INT(11) NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY idx_station_fuel_active (station_id, fuel_type_id, is_active),
            FOREIGN KEY (station_id) REFERENCES stations(id),
            FOREIGN KEY (fuel_type_id) REFERENCES fuel_types(id)
        )");
        echo "✅ Created fuel_pricing table\n";
    } catch(PDOException $e) {
        echo "❌ Error creating fuel_pricing table: " . $e->getMessage() . "\n";
    }

    // 3. Update fuel products with proper fuel_type_id based on product names
    echo "<h3>3. Setting up fuel product relationships</h3>\n";
    try {
        // Map fuel product names to fuel_type_id based on common patterns
        $fuel_mappings = [
            'gasoline' => 1,
            'premium' => 4, 
            'unleaded' => 5,
            'diesel' => 2,
            'lpg' => 3
        ];
        
        foreach($fuel_mappings as $fuel_name => $fuel_type_id) {
            $stmt = $pdo->prepare("
                UPDATE products p
                JOIN product_types pt ON p.type_id = pt.id 
                SET p.fuel_type_id = ? 
                WHERE pt.name = 'fuel' 
                AND p.fuel_type_id IS NULL
                AND LOWER(p.name) LIKE ?
            ");
            $stmt->execute([$fuel_type_id, '%' . $fuel_name . '%']);
            $count = $stmt->rowCount();
            if ($count > 0) {
                echo "✅ Updated $count fuel products matching '$fuel_name' to fuel_type_id $fuel_type_id\n";
            }
        }
        
        // Set remaining fuel products to gasoline as default
        $stmt = $pdo->prepare("
            UPDATE products p
            JOIN product_types pt ON p.type_id = pt.id 
            SET p.fuel_type_id = 1 
            WHERE pt.name = 'fuel' AND p.fuel_type_id IS NULL
        ");
        $stmt->execute();
        $count = $stmt->rowCount();
        if ($count > 0) {
            echo "✅ Set $count remaining fuel products to default fuel_type_id 1 (Gasoline)\n";
        }
        
    } catch(PDOException $e) {
        echo "❌ Error updating fuel product relationships: " . $e->getMessage() . "\n";
    }

    // 4. Add sample fuel pricing data for all stations
    echo "<h3>4. Setting up fuel pricing data</h3>\n";
    try {
        // Get all active stations
        $stations = $pdo->query("SELECT id FROM stations WHERE status = 'active'")->fetchAll(PDO::FETCH_COLUMN);
        
        // Standard fuel prices (can be customized later)
        $default_prices = [
            1 => 58.50, // Gasoline
            2 => 56.20, // Diesel  
            3 => 32.10, // LPG
            4 => 62.30, // Premium
            5 => 58.50  // Unleaded
        ];
        
        foreach($stations as $station_id) {
            foreach($default_prices as $fuel_type_id => $price) {
                // Check if pricing already exists
                $stmt = $pdo->prepare("SELECT COUNT(*) FROM fuel_pricing WHERE station_id = ? AND fuel_type_id = ? AND is_active = 1");
                $stmt->execute([$station_id, $fuel_type_id]);
                
                if ($stmt->fetchColumn() == 0) {
                    // Insert default pricing
                    $stmt = $pdo->prepare("
                        INSERT INTO fuel_pricing (station_id, fuel_type_id, price_per_liter, created_by) 
                        VALUES (?, ?, ?, 1)
                    ");
                    $stmt->execute([$station_id, $fuel_type_id, $price]);
                }
            }
        }
        echo "✅ Set up fuel pricing for " . count($stations) . " stations\n";
        
    } catch(PDOException $e) {
        echo "❌ Error setting up fuel pricing: " . $e->getMessage() . "\n";
    }

    // 5. Verify the setup
    echo "<h3>5. Verification</h3>\n";
    
    // Check fuel products with fuel_type_id
    $stmt = $pdo->query("
        SELECT COUNT(*) as count 
        FROM products p 
        JOIN product_types pt ON p.type_id = pt.id 
        WHERE pt.name = 'fuel' AND p.fuel_type_id IS NOT NULL
    ");
    $fuel_products_count = $stmt->fetchColumn();
    echo "✅ Fuel products with fuel_type_id: $fuel_products_count\n";
    
    // Check fuel pricing entries
    $pricing_count = $pdo->query("SELECT COUNT(*) FROM fuel_pricing WHERE is_active = 1")->fetchColumn();
    echo "✅ Active fuel pricing entries: $pricing_count\n";
    
    // Check station inventory
    $inventory_count = $pdo->query("
        SELECT COUNT(*) 
        FROM station_inventory si
        JOIN products p ON si.product_id = p.id
        JOIN product_types pt ON p.type_id = pt.id
        WHERE pt.name = 'fuel'
    ")->fetchColumn();
    echo "✅ Fuel inventory entries: $inventory_count\n";
    
    echo "<h3>✅ Database Schema Fix Complete!</h3>\n";
    echo "<p>The POS system should now be able to load fuel products properly.</p>\n";
    
} catch(Exception $e) {
    echo "<h3>❌ Critical Error</h3>\n";
    echo "<p>Error: " . $e->getMessage() . "</p>\n";
}
?>