<?php
/**
 * TIER 1 Data Generation
 * Generates foundation data: users and products
 */

require_once __DIR__ . '/../public/db_connect.php';

echo "=== TIER 1 DATA GENERATION ===\n\n";

// 1. Generate More Users
echo "1. Generating Users...\n";

$existing_users = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
echo "   Current users: $existing_users\n";

if ($existing_users < 25) {
    $new_users = [];
    
    // Get 5 random stations
    $stations = $pdo->query("SELECT id FROM stations ORDER BY RAND() LIMIT 5")->fetchAll(PDO::FETCH_COLUMN);
    
    // Create users for each station
    $first_names = ['Juan', 'Maria', 'Carlos', 'Ana', 'Miguel', 'Rosa', 'Luis', 'Carmen', 'Diego', 'Sofia', 
                     'Pedro', 'Lucia', 'Fernando', 'Isabel', 'Manuel', 'Elena', 'Antonio', 'Gabriela', 'Ricardo', 'Patricia'];
    $last_names = ['Santos', 'Garcia', 'Rodriguez', 'Martinez', 'Lopez', 'Gonzalez', 'Hernandez', 'Perez', 'Flores', 'Rivera'];
    
    $roles_per_station = [
        'superadmin' => 1,  // 1 total
        'admin' => 2,        // 2 total
        'manager' => 3,      // per station
        'staff' => 5         // per station
    ];
    
    $user_count = $existing_users;
    
    // Add superadmin if not exists
    $superadmin_exists = $pdo->query("SELECT COUNT(*) FROM users WHERE role='superadmin'")->fetchColumn();
    if ($superadmin_exists == 0) {
        $username = 'admin_super';
        $password = password_hash('password123', PASSWORD_DEFAULT);
        $name = 'Super Admin';
        $email = 'superadmin@petron.com';
        
        $stmt = $pdo->prepare("INSERT INTO users (username, password, name, email, role, status) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([$username, $password, $name, $email, 'superadmin', 'active']);
        $user_count++;
        echo "   + Added superadmin\n";
    }
    
    // Add admins
    $admin_count = $pdo->query("SELECT COUNT(*) FROM users WHERE role='admin'")->fetchColumn();
    while ($admin_count < 2) {
        $fn = $first_names[array_rand($first_names)];
        $ln = $last_names[array_rand($last_names)];
        $username = strtolower($fn[0] . '_' . $ln);
        $password = password_hash('password123', PASSWORD_DEFAULT);
        $name = "$fn $ln";
        $email = strtolower("$fn.$ln@petron.com");
        
        try {
            $stmt = $pdo->prepare("INSERT INTO users (username, password, name, email, role, status) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->execute([$username, $password, $name, $email, 'admin', 'active']);
            $admin_count++;
            $user_count++;
            echo "   + Added admin: $name\n";
        } catch (Exception $e) {
            // Username might already exist, skip
        }
    }
    
    // Add managers and staff per station
    foreach ($stations as $station_id) {
        $manager_count = $pdo->query("SELECT COUNT(*) FROM users WHERE station_id=$station_id AND role='manager'")->fetchColumn();
        
        while ($manager_count < 3) {
            $fn = $first_names[array_rand($first_names)];
            $ln = $last_names[array_rand($last_names)];
            $username = strtolower($fn[0] . '_' . $ln . '_m' . ($manager_count + 1));
            $password = password_hash('password123', PASSWORD_DEFAULT);
            $name = "$fn $ln";
            $email = strtolower("$fn.$ln@petron.com");
            
            try {
                $stmt = $pdo->prepare("INSERT INTO users (username, password, name, email, role, station_id, status) VALUES (?, ?, ?, ?, ?, ?, ?)");
                $stmt->execute([$username, $password, $name, $email, 'manager', $station_id, 'active']);
                $manager_count++;
                $user_count++;
                echo "   + Added manager (Station $station_id): $name\n";
            } catch (Exception $e) {
                break;
            }
        }
        
        $staff_count = $pdo->query("SELECT COUNT(*) FROM users WHERE station_id=$station_id AND role='staff'")->fetchColumn();
        
        while ($staff_count < 5) {
            $fn = $first_names[array_rand($first_names)];
            $ln = $last_names[array_rand($last_names)];
            $username = strtolower($fn[0] . '_' . $ln . '_s' . ($staff_count + 1));
            $password = password_hash('password123', PASSWORD_DEFAULT);
            $name = "$fn $ln";
            $email = strtolower("$fn.$ln@petron.com");
            
            try {
                $stmt = $pdo->prepare("INSERT INTO users (username, password, name, email, role, station_id, status) VALUES (?, ?, ?, ?, ?, ?, ?)");
                $stmt->execute([$username, $password, $name, $email, 'staff', $station_id, 'active']);
                $staff_count++;
                $user_count++;
                echo "   + Added staff (Station $station_id): $name\n";
            } catch (Exception $e) {
                break;
            }
        }
    }
    
    echo "   ✓ User generation complete. Total: $user_count\n\n";
}

// 2. Generate More Products
echo "2. Generating Products...\n";

$existing_products = $pdo->query("SELECT COUNT(*) FROM products")->fetchColumn();
echo "   Current products: $existing_products\n";

if ($existing_products < 40) {
    $fuel_products = [
        ['name' => 'Petron Blaze 100', 'sku' => 'BLAZE100', 'cost' => 45.00, 'price' => 52.50],
        ['name' => 'Petron XCS Diesel', 'sku' => 'XCSDIESEL', 'cost' => 42.00, 'price' => 49.00],
        ['name' => 'Petron Turbo Diesel', 'sku' => 'TURBODSL', 'cost' => 41.00, 'price' => 48.00],
        ['name' => 'Petron BlueMax', 'sku' => 'BLUEMAX', 'cost' => 46.00, 'price' => 53.50],
        ['name' => 'Petron Radiant Plus', 'sku' => 'RADIANT', 'cost' => 47.00, 'price' => 54.00],
    ];
    
    $merch_products = [
        ['name' => 'Coffee (12oz)', 'sku' => 'COFFEE12', 'cost' => 15.00, 'price' => 35.00],
        ['name' => 'Bottled Water (500ml)', 'sku' => 'WATER500', 'cost' => 8.00, 'price' => 20.00],
        ['name' => 'Energy Drink (250ml)', 'sku' => 'ENERGY250', 'cost' => 25.00, 'price' => 55.00],
        ['name' => 'Snack Chips (100g)', 'sku' => 'CHIPS100', 'cost' => 20.00, 'price' => 45.00],
        ['name' => 'Chocolate Bar', 'sku' => 'CHOCO', 'cost' => 12.00, 'price' => 28.00],
        ['name' => 'Sandwich (Premium)', 'sku' => 'SANDWICH', 'cost' => 35.00, 'price' => 85.00],
        ['name' => 'Windshield Cleaner', 'sku' => 'WINDCLEAN', 'cost' => 50.00, 'price' => 120.00],
        ['name' => 'Car Air Freshener', 'sku' => 'AIRFRESH', 'cost' => 30.00, 'price' => 75.00],
        ['name' => 'Engine Oil (1L)', 'sku' => 'OIL1L', 'cost' => 200.00, 'price' => 450.00],
        ['name' => 'Tire Pressure Gauge', 'sku' => 'TIREGAUGE', 'cost' => 100.00, 'price' => 250.00],
    ];
    
    // Get fuel type ID
    $fuel_type_id = $pdo->query("SELECT id FROM product_types WHERE name='fuel'")->fetchColumn();
    $merch_type_id = $pdo->query("SELECT id FROM product_types WHERE name='merch'")->fetchColumn();
    
    // Insert fuel products
    foreach ($fuel_products as $product) {
        try {
            $stmt = $pdo->prepare("INSERT INTO products (sku, name, type_id, cost, price) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$product['sku'], $product['name'], $fuel_type_id, $product['cost'], $product['price']]);
            echo "   + Added product: " . $product['name'] . "\n";
        } catch (Exception $e) {
            // SKU might exist
        }
    }
    
    // Insert merch products
    foreach ($merch_products as $product) {
        try {
            $stmt = $pdo->prepare("INSERT INTO products (sku, name, type_id, cost, price) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$product['sku'], $product['name'], $merch_type_id, $product['cost'], $product['price']]);
            echo "   + Added product: " . $product['name'] . "\n";
        } catch (Exception $e) {
            // SKU might exist
        }
    }
    
    $new_product_count = $pdo->query("SELECT COUNT(*) FROM products")->fetchColumn();
    echo "   ✓ Product generation complete. Total: $new_product_count\n\n";
}

echo "=== TIER 1 COMPLETE ===\n";
?>
