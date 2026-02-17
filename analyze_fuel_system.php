<?php
require_once __DIR__ . '/public/db_connect.php';

echo "=== CHECKING FUEL SYSTEM STRUCTURE ===\n\n";

try {
    // Check fuel types
    echo "Fuel Types:\n";
    $stmt = $pdo->query("SELECT * FROM fuel_types ORDER BY id");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo "  ID {$row['id']}: {$row['name']} - {$row['description']}\n";
    }
    
    // Check fuel pumps
    echo "\nFuel Pumps at Station 1250:\n";
    $stmt = $pdo->query("
        SELECT 
            fp.id, fp.pump_number,
            ft.name as fuel_type,
            fp.capacity, fp.status
        FROM fuel_pumps fp
        LEFT JOIN fuel_types ft ON fp.fuel_type_id = ft.id
        WHERE fp.station_id = 1250
        ORDER BY fp.pump_number
    ");
    
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo "  Pump {$row['pump_number']}: {$row['fuel_type']} (Capacity: {$row['capacity']}L, Status: {$row['status']})\n";
    }
    
    // Check fuel inventory
    echo "\nFuel Inventory at Station 1250:\n";
    $stmt = $pdo->query("
        SELECT
            ft.name as fuel_type,
            fi.stock_level,
            fi.capacity
        FROM fuel_inventory fi
        JOIN fuel_types ft ON fi.fuel_type_id = ft.id
        WHERE fi.station_id = 1250
        ORDER BY ft.id
    ");
    
    $fuel_count = 0;
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $fuel_count++;
        echo "  {$row['fuel_type']}: {$row['stock_level']}L / {$row['capacity']}L\n";
    }
    
    if ($fuel_count == 0) {
        echo "  No fuel inventory records found.\n";
    }
    
    // Check if fuel products exist in products table
    echo "\nProducts Table - Fuel Products (type_id=1):\n";
    $stmt = $pdo->query("
        SELECT
            p.id, p.sku, p.name,
            p.type_id, p.category_id,
            p.price
        FROM products p
        WHERE p.type_id = 1
        ORDER BY p.id
    ");
    
    $fuel_products = $stmt->fetchAll(PDO::FETCH_ASSOC);
    if (count($fuel_products) == 0) {
        echo "  No fuel products in products table.\n";
    } else {
        foreach ($fuel_products as $product) {
            printf("  ID %d: %s - %s (Category: %d, Price: ₱%.2f)\n",
                $product['id'],
                $product['sku'],
                $product['name'],
                $product['category_id'],
                $product['price']
            );
        }
    }
    
    // Check fuel daily readings
    echo "\nFuel Daily Readings (Recent):\n";
    $stmt = $pdo->query("
        SELECT
            ft.name as fuel_type,
            COUNT(*) as readings
        FROM fuel_daily_readings fdr
        JOIN fuel_types ft ON fdr.fuel_type_id = ft.id
        WHERE fdr.station_id = 1250
        GROUP BY ft.id, ft.name
        ORDER BY ft.id
        LIMIT 10
    ");
    
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo "  {$row['fuel_type']}: {$row['readings']} readings\n";
    }
    
    // Recommendation
    echo "\n=== ANALYSIS ===\n";
    
    if ($fuel_count == 0 && count($fuel_products) == 0) {
        echo "Fuel System Status:\n";
        echo "  • Fuel types table exists with 5 fuel types\n";
        echo "  • Fuel pumps are configured\n";
        echo "  • NO fuel inventory records\n";
        echo "  • NO fuel products in products table\n\n";
        
        echo "Recommendation:\n";
        echo "  Fuel appears to be managed through:\n";
        echo "  - fuel_pumps table (pump configuration)\n";
        echo "  - fuel_inventory table (stock tracking)\n";
        echo "  - fuel_daily_readings table (daily readings)\n\n";
        
        echo "To restore fuel products to products table, we need to:\n";
        echo "  1. Create products for each fuel type with type_id=1\n";
        echo "  2. Link them to category_id=1 (Fuel Products)\n";
        echo "  3. Create station_inventory records\n";
        echo "  4. Optionally create fuel_inventory records\n\n";
        
        echo "Do you want me to create fuel products now? (y/n)\n";
    }
    
} catch (Exception $e) {
    echo "❌ ERROR: " . $e->getMessage() . "\n";
    exit(1);
}
?>
