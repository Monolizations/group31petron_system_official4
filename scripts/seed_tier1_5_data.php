<?php
/**
 * TIER 1.5 Data Generation
 * Generates: customers and station_inventory
 */

require_once __DIR__ . '/../public/db_connect.php';

echo "=== TIER 1.5 DATA GENERATION ===\n\n";

// 1. Generate More Customers
echo "1. Generating Customers...\n";

$existing_customers = $pdo->query("SELECT COUNT(*) FROM customers")->fetchColumn();
echo "   Current customers: $existing_customers\n";

if ($existing_customers < 40) {
    $company_names = [
        'ABC Transport', 'QuickRide Taxi', 'Metro Delivery', 'Premier Logistics', 'FastTrack Shipping',
        'Urban Express', 'Regional Carriers', 'City Fleet Services', 'National Distribution', 'Elite Movers',
        'Standard Fleet', 'Local Delivery Co', 'Premium Services', 'Express Transport', 'Quick Cargo',
        'John Santos', 'Maria Garcia', 'Carlos Rodriguez', 'Ana Martinez', 'Miguel Lopez',
        'Rosa Gonzalez', 'Luis Hernandez', 'Carmen Perez', 'Diego Flores', 'Sofia Rivera'
    ];
    
    $customers_to_add = 40 - $existing_customers;
    
    for ($i = 0; $i < $customers_to_add; $i++) {
        $name = $company_names[$i % count($company_names)] . ($i > count($company_names) ? " ($i)" : "");
        $type = (rand(1, 100) <= 60) ? 'cash' : 'credit';
        $credit_limit = $type === 'credit' ? rand(10000, 100000) : 0;
        $current_balance = $type === 'credit' ? rand(0, $credit_limit) : 0;
        $phone = '09' . str_pad(rand(1, 999999999), 9, '0', STR_PAD_LEFT);
        $email = strtolower(str_replace(' ', '_', $name)) . '@example.com';
        
        try {
            $stmt = $pdo->prepare("INSERT INTO customers (name, phone, email, type, credit_limit, current_balance, status) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$name, $phone, $email, $type, $credit_limit, $current_balance, 'active']);
            echo "   + Added customer: $name ($type)\n";
        } catch (Exception $e) {
            // Skip duplicates
        }
    }
    
    $new_customer_count = $pdo->query("SELECT COUNT(*) FROM customers")->fetchColumn();
    echo "   ✓ Customer generation complete. Total: $new_customer_count\n\n";
}

// 2. Generate Station Inventory
echo "2. Generating Station Inventory...\n";

$existing_inventory = $pdo->query("SELECT COUNT(*) FROM station_inventory")->fetchColumn();
echo "   Current inventory records: $existing_inventory\n";

if ($existing_inventory == 0) {
    // Get 5 sample stations
    $stations = $pdo->query("SELECT id FROM stations ORDER BY RAND() LIMIT 5")->fetchAll(PDO::FETCH_COLUMN);
    
    // Get all products
    $products = $pdo->query("SELECT id, type_id FROM products")->fetchAll(PDO::FETCH_ASSOC);
    
    $fuel_type_id = $pdo->query("SELECT id FROM product_types WHERE name='fuel'")->fetchColumn();
    
    $inventory_count = 0;
    
    foreach ($stations as $station_id) {
        foreach ($products as $product) {
            // Fuel: higher stock levels
            if ($product['type_id'] == $fuel_type_id) {
                $stock = rand(2000, 8000); // liters
            } else {
                $stock = rand(10, 150); // units
            }
            
            try {
                $stmt = $pdo->prepare("INSERT INTO station_inventory (station_id, product_id, stock_level, reorder_level, capacity) VALUES (?, ?, ?, ?, ?)");
                $stmt->execute([$station_id, $product['id'], $stock, max(100, $stock / 2), $stock * 2]);
                $inventory_count++;
            } catch (Exception $e) {
                // Skip if already exists
            }
        }
    }
    
    echo "   ✓ Inventory generation complete. Added: $inventory_count records\n\n";
}

echo "=== TIER 1.5 COMPLETE ===\n";
?>
