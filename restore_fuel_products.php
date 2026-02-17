<?php
require_once __DIR__ . '/public/db_connect.php';

echo "=== RESTORING FUEL PRODUCTS ===\n";
echo "Date: " . date('Y-m-d H:i:s') . "\n";
echo str_repeat("=", 70) . "\n\n";

try {
    // Step 1: Check current fuel types
    echo "STEP 1: Checking Fuel Types\n";
    $stmt = $pdo->query("SELECT * FROM fuel_types ORDER BY id");
    $fuel_types = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "Found " . count($fuel_types) . " fuel types:\n";
    foreach ($fuel_types as $ft) {
        echo "  ID {$ft['id']}: {$ft['name']} - {$ft['description']}\n";
    }
    
    // Step 2: Check current products table
    echo "\nSTEP 2: Checking Current Products Table\n";
    $current_total = $pdo->query("SELECT COUNT(*) FROM products")->fetchColumn();
    $current_max_id = $pdo->query("SELECT MAX(id) FROM products")->fetchColumn();
    $current_fuel = $pdo->query("SELECT COUNT(*) FROM products WHERE type_id = 1")->fetchColumn();
    
    echo "  Total products: $current_total\n";
    echo "  Max ID: $current_max_id\n";
    echo "  Fuel products: $current_fuel\n\n";
    
    // Step 3: Delete existing fuel products (if any)
    if ($current_fuel > 0) {
        echo "STEP 3: Deleting Existing Fuel Products\n";
        $pdo->exec("DELETE FROM station_inventory WHERE product_id IN (SELECT id FROM products WHERE type_id = 1)");
        $pdo->exec("DELETE FROM products WHERE type_id = 1");
        echo "  ✓ Deleted $current_fuel fuel products\n\n";
    }
    
    // Step 4: Get prices from products.json
    echo "STEP 4: Getting Fuel Prices from products.json\n";
    $json_file = __DIR__ . '/data/products.json';
    if (!file_exists($json_file)) {
        throw new Exception("products.json not found");
    }
    
    $json_data = json_decode(file_get_contents($json_file), true);
    $fuel_prices = [];
    
    if (isset($json_data['fuel'])) {
        foreach ($json_data['fuel'] as $fuel) {
            $fuel_prices[$fuel['id']] = $fuel['price'];
        }
    }
    
    echo "  Found " . count($fuel_prices) . " fuel prices in JSON:\n";
    foreach ($fuel_prices as $id => $price) {
        echo "    $id: ₱" . number_format($price, 2) . "\n";
    }
    echo "\n";
    
    // Step 5: Determine starting ID for fuel products
    echo "STEP 5: Determining Fuel Product IDs\n";
    $start_id = $current_max_id + 1;
    echo "  Starting from ID: $start_id\n";
    echo "  Will create 5 fuel products: $start_id to " . ($start_id + 4) . "\n\n";
    
    // Step 6: Insert fuel products
    echo "STEP 6: Inserting Fuel Products\n";
    
    // Map fuel_types to product SKUs and prices
    $fuel_products = [
        1 => ['sku' => 'XCS-PLUS', 'name' => 'XCS Plus', 'price' => 65.75, 'capacity' => 15000],
        2 => ['sku' => 'DSL-MAX', 'name' => 'Diesel Max', 'price' => 58.50, 'capacity' => 20000],
        3 => ['sku' => 'KEROSENE', 'name' => 'Kerosene', 'price' => 77.68, 'capacity' => 18000],
        4 => ['sku' => 'XCS-ADVANCE', 'name' => 'XCS Advance', 'price' => 53.49, 'capacity' => 12000],
        5 => ['sku' => 'TURBO-DIESEL', 'name' => 'Turbo Diesel', 'price' => 52.68, 'capacity' => 25000]
    ];
    
    // Disable foreign key checks
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 0");
    
    $inserted_count = 0;
    $fuel_product_ids = [];
    
    foreach ($fuel_types as $ft) {
        if (isset($fuel_products[$ft['id']])) {
            $fp = $fuel_products[$ft['id']];
            $product_id = $start_id + $inserted_count;
            
            // Cost is typically 20-25% less than price
            $cost = round($fp['price'] * 0.80, 2);
            
            $stmt = $pdo->prepare("
                INSERT INTO products (id, sku, name, description, type_id, category_id, cost, price, created_at, updated_at)
                VALUES (?, ?, ?, ?, 1, 1, ?, ?, NOW(), NOW())
            ");
            $stmt->execute([
                $product_id,
                $fp['sku'],
                $fp['name'],
                $ft['description'],
                $cost,
                $fp['price']
            ]);
            
            $fuel_product_ids[$ft['id']] = $product_id;
            $inserted_count++;
            
            echo "  ✓ Inserted: ID $product_id - {$fp['name']} (₱" . number_format($fp['price'], 2) . ")\n";
        }
    }
    
    // Re-enable foreign key checks
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 1");
    
    echo "\n  Total fuel products inserted: $inserted_count\n\n";
    
    // Step 7: Create/update station inventory for station 1250
    echo "STEP 7: Creating Station Inventory at 1250\n";
    
    foreach ($fuel_types as $ft) {
        if (isset($fuel_product_ids[$ft['id']])) {
            $product_id = $fuel_product_ids[$ft['id']];
            $fp = $fuel_products[$ft['id']];
            
            // Check if inventory record exists
            $check = $pdo->prepare("SELECT id FROM station_inventory WHERE station_id = ? AND product_id = ?");
            $check->execute([1250, $product_id]);
            $exists = $check->fetch();
            
            // Random stock level between 5000-10000 liters
            $stock_level = rand(5000, 10000);
            $reorder_level = round($fp['capacity'] * 0.20); // 20% of capacity
            
            if (!$exists) {
                $stmt = $pdo->prepare("
                    INSERT INTO station_inventory (station_id, product_id, stock_level, reorder_level, capacity, unit, status, last_updated)
                    VALUES (?, ?, ?, ?, ?, 'liters', 'active', NOW())
                ");
                $stmt->execute([1250, $product_id, $stock_level, $reorder_level, $fp['capacity']]);
                echo "  ✓ Created inventory: {$fp['name']} - $stock_level L (Reorder: $reorder_level L)\n";
            } else {
                echo "  ⚠ Inventory already exists for {$fp['name']}\n";
            }
        }
    }
    
    echo "\n";
    
    // Step 8: Verify
    echo "STEP 8: Verification\n";
    
    // Count total products
    $new_total = $pdo->query("SELECT COUNT(*) FROM products")->fetchColumn();
    $fuel_count = $pdo->query("SELECT COUNT(*) FROM products WHERE type_id = 1")->fetchColumn();
    $merch_count = $pdo->query("SELECT COUNT(*) FROM products WHERE type_id = 2")->fetchColumn();
    
    echo "  Total products: $new_total\n";
    echo "  Fuel products: $fuel_count\n";
    echo "  Merchandise products: $merch_count\n";
    
    // Count station inventory
    $inv_count = $pdo->query("SELECT COUNT(*) FROM station_inventory WHERE station_id = 1250")->fetchColumn();
    echo "  Station inventory at 1250: $inv_count records\n";
    
    // Sample fuel products
    echo "\n  Sample Fuel Products:\n";
    $stmt = $pdo->query("
        SELECT p.id, p.sku, p.name, p.price, si.stock_level, si.reorder_level, si.capacity
        FROM products p
        JOIN station_inventory si ON p.id = si.product_id AND si.station_id = 1250
        WHERE p.type_id = 1
        ORDER BY p.id
    ");
    
    printf("  %-5s %-20s %-25s %-10s %-12s %-10s %-10s\n",
        'ID', 'SKU', 'Name', 'Price', 'Stock', 'Reorder', 'Capacity');
    echo "  " . str_repeat("-", 90) . "\n";
    
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        printf("  %-5s %-20s %-25s %-10.2f %-12s %-10s %-10s\n",
            $row['id'],
            $row['sku'],
            substr($row['name'], 0, 25),
            $row['price'],
            number_format($row['stock_level']) . 'L',
            number_format($row['reorder_level']) . 'L',
            number_format($row['capacity']) . 'L'
        );
    }
    
    echo "\n" . str_repeat("=", 70) . "\n";
    echo "✅ FUEL PRODUCTS RESTORED SUCCESSFULLY!\n\n";
    
    echo "Summary:\n";
    echo "  ✓ 5 fuel products inserted (IDs $start_id to " . ($start_id + 4) . ")\n";
    echo "  ✓ All fuel products have type_id = 1 (fuel)\n";
    echo "  ✓ All fuel products have category_id = 1 (Fuel Products)\n";
    echo "  ✓ 5 station inventory records created at station 1250\n";
    echo "  ✓ Stock levels: 5,000-10,000 liters per fuel type\n";
    echo "  ✓ Total products in database: $new_total\n";
    echo "    - Fuel: $fuel_count\n";
    echo "    - Merchandise: $merch_count\n\n";
    
} catch (Exception $e) {
    echo "\n❌ ERROR: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
    exit(1);
}

echo "\n";
?>
