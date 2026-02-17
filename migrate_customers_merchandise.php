<?php
/**
 * Migration Script: Populate Customers for Station 1250 & Add Merchandise Categories
 * 
 * This script:
 * 1. Adds "Others" merchandise category
 * 2. Populates sample customers for station 1250
 * 3. Adds merchandise type preference to customers table
 */

require_once __DIR__ . '/backend/lib.php';
require_once __DIR__ . '/public/db_connect.php';

echo "<h1>Migration: Customers & Merchandise Types</h1>";
echo "<pre>";

// =====================================================
// STEP 1: Add "Others" Merchandise Category
// =====================================================
echo "\n=== STEP 1: Adding 'Others' Merchandise Category ===\n";

try {
    // Check if "Others" category exists
    $stmt = $pdo->query("SELECT id FROM product_categories WHERE name = 'Others'");
    $existing = $stmt->fetch();
    
    if (!$existing) {
        $pdo->query("INSERT INTO product_categories (name, description, created_at) VALUES ('Others', 'Other merchandise items', NOW())");
        echo "✅ Added 'Others' category to product_categories\n";
    } else {
        echo "ℹ️ 'Others' category already exists (ID: {$existing['id']})\n";
    }
} catch (Exception $e) {
    echo "❌ Error adding category: " . $e->getMessage() . "\n";
}

// =====================================================
// STEP 2: Add merchandise_type column to customers table
// =====================================================
echo "\n=== STEP 2: Adding merchandise_type Column to Customers ===\n";

try {
    // Check if column exists
    $stmt = $pdo->query("SHOW COLUMNS FROM customers LIKE 'merchandise_type'");
    $columnExists = $stmt->fetch();
    
    if (!$columnExists) {
        $pdo->query("ALTER TABLE customers ADD COLUMN merchandise_type ENUM('oil_lube_grease', 'car_accessories', 'oil_fuel_filter', 'others', 'multiple') DEFAULT NULL AFTER type");
        echo "✅ Added merchandise_type column to customers table\n";
    } else {
        echo "ℹ️ merchandise_type column already exists\n";
    }
} catch (Exception $e) {
    echo "❌ Error adding column: " . $e->getMessage() . "\n";
}

// =====================================================
// STEP 3: Populate Sample Customers for Station 1250
// =====================================================
echo "\n=== STEP 3: Populating Sample Customers for Station 1250 ===\n";

$sampleCustomers = [
    // Cash Customers
    [
        'name' => 'Juan Dela Cruz',
        'contact_person' => 'Juan Dela Cruz',
        'phone' => '09171234567',
        'email' => 'juan.delacruz@email.com',
        'address' => '123 Rizal St, Quezon City',
        'type' => 'cash',
        'credit_limit' => 0,
        'status' => 'active',
        'station_id' => 1250,
        'merchandise_type' => 'oil_lube_grease'
    ],
    [
        'name' => 'Maria Santos',
        'contact_person' => 'Maria Santos',
        'phone' => '09182345678',
        'email' => 'maria.santos@email.com',
        'address' => '456 Mabini Ave, Makati City',
        'type' => 'cash',
        'credit_limit' => 0,
        'status' => 'active',
        'station_id' => 1250,
        'merchandise_type' => 'car_accessories'
    ],
    [
        'name' => 'Pedro Reyes',
        'contact_person' => 'Pedro Reyes',
        'phone' => '09193456789',
        'email' => 'pedro.reyes@email.com',
        'address' => '789 Bonifacio St, Pasig City',
        'type' => 'cash',
        'credit_limit' => 0,
        'status' => 'active',
        'station_id' => 1250,
        'merchandise_type' => 'oil_fuel_filter'
    ],
    [
        'name' => 'Ana Garcia',
        'contact_person' => 'Ana Garcia',
        'phone' => '09204567890',
        'email' => 'ana.garcia@email.com',
        'address' => '321 Aurora Blvd, Marikina City',
        'type' => 'cash',
        'credit_limit' => 0,
        'status' => 'active',
        'station_id' => 1250,
        'merchandise_type' => 'others'
    ],
    // Credit Customers
    [
        'name' => 'ABC Transport Services Inc.',
        'contact_person' => 'Roberto Tan',
        'phone' => '09215678901',
        'email' => 'rtan@abctransport.com',
        'address' => '567 EDSA, Mandaluyong City',
        'type' => 'credit',
        'credit_limit' => 50000.00,
        'status' => 'active',
        'station_id' => 1250,
        'merchandise_type' => 'oil_lube_grease'
    ],
    [
        'name' => 'XYZ Logistics Corp.',
        'contact_person' => 'Carmen Lim',
        'phone' => '09226789012',
        'email' => 'clim@xyzlogistics.com',
        'address' => '890 C5 Road, Taguig City',
        'type' => 'credit',
        'credit_limit' => 100000.00,
        'status' => 'active',
        'station_id' => 1250,
        'merchandise_type' => 'multiple'
    ],
    [
        'name' => 'Fast Fleet Solutions',
        'contact_person' => 'Michael Torres',
        'phone' => '09237890123',
        'email' => 'mtorres@fastfleet.com',
        'address' => '234 Ortigas Ave, Pasig City',
        'type' => 'credit',
        'credit_limit' => 75000.00,
        'status' => 'active',
        'station_id' => 1250,
        'merchandise_type' => 'oil_lube_grease'
    ],
    [
        'name' => 'City Cab Company',
        'contact_person' => 'Linda Villanueva',
        'phone' => '09248901234',
        'email' => 'lvillanueva@citycab.com',
        'address' => '678 Quezon Ave, Quezon City',
        'type' => 'credit',
        'credit_limit' => 80000.00,
        'status' => 'active',
        'station_id' => 1250,
        'merchandise_type' => 'car_accessories'
    ],
    [
        'name' => 'Metro Delivery Services',
        'contact_person' => 'George Hernandez',
        'phone' => '09259012345',
        'email' => 'ghernandez@metrodelivery.com',
        'address' => '901 Shaw Blvd, Mandaluyong City',
        'type' => 'credit',
        'credit_limit' => 60000.00,
        'status' => 'active',
        'station_id' => 1250,
        'merchandise_type' => 'oil_fuel_filter'
    ],
    [
        'name' => 'Premium Auto Parts Trading',
        'contact_person' => 'Sandra Cruz',
        'phone' => '09260123456',
        'email' => 'scruz@premiumauto.com',
        'address' => '1234 Marcos Highway, Marikina City',
        'type' => 'credit',
        'credit_limit' => 120000.00,
        'status' => 'active',
        'station_id' => 1250,
        'merchandise_type' => 'multiple'
    ],
    // More Cash Customers
    [
        'name' => 'Jose Rizal Motors',
        'contact_person' => 'Jose Rizal',
        'phone' => '09271234567',
        'email' => 'jose.rizal@email.com',
        'address' => '567 España Blvd, Manila',
        'type' => 'cash',
        'credit_limit' => 0,
        'status' => 'active',
        'station_id' => 1250,
        'merchandise_type' => 'oil_lube_grease'
    ],
    [
        'name' => 'Grace Automotive Shop',
        'contact_person' => 'Grace Mendoza',
        'phone' => '09282345678',
        'email' => 'grace.auto@email.com',
        'address' => '890 Commonwealth Ave, Quezon City',
        'type' => 'cash',
        'credit_limit' => 0,
        'status' => 'active',
        'station_id' => 1250,
        'merchandise_type' => 'car_accessories'
    ],
    [
        'name' => 'TNT Express Delivery',
        'contact_person' => 'Tony Ng',
        'phone' => '09293456789',
        'email' => 'tony.ng@tntexpress.com',
        'address' => '234 Roxas Blvd, Pasay City',
        'type' => 'credit',
        'credit_limit' => 45000.00,
        'status' => 'active',
        'station_id' => 1250,
        'merchandise_type' => 'oil_fuel_filter'
    ],
    [
        'name' => 'Sunshine Gas Station Supplies',
        'contact_person' => 'Sunny Aquino',
        'phone' => '09304567890',
        'email' => 'sunny.aquino@email.com',
        'address' => '456 Timog Ave, Quezon City',
        'type' => 'cash',
        'credit_limit' => 0,
        'status' => 'active',
        'station_id' => 1250,
        'merchandise_type' => 'others'
    ],
    [
        'name' => 'Quick Stop Convenience',
        'contact_person' => 'Rachel Go',
        'phone' => '09315678901',
        'email' => 'rachel.go@email.com',
        'address' => '789 Visayas Ave, Quezon City',
        'type' => 'cash',
        'credit_limit' => 0,
        'status' => 'active',
        'station_id' => 1250,
        'merchandise_type' => 'multiple'
    ]
];

$insertStmt = $pdo->prepare("
    INSERT INTO customers (name, contact_person, phone, email, address, type, credit_limit, status, station_id, merchandise_type, created_at) 
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
    ON DUPLICATE KEY UPDATE phone = VALUES(phone)
");

$insertedCount = 0;
$skippedCount = 0;

foreach ($sampleCustomers as $customer) {
    try {
        // Check if customer already exists
        $checkStmt = $pdo->prepare("SELECT id FROM customers WHERE name = ? AND station_id = ?");
        $checkStmt->execute([$customer['name'], $customer['station_id']]);
        
        if ($checkStmt->fetch()) {
            echo "ℹ️ Customer '{$customer['name']}' already exists - skipping\n";
            $skippedCount++;
            continue;
        }
        
        $insertStmt->execute([
            $customer['name'],
            $customer['contact_person'],
            $customer['phone'],
            $customer['email'],
            $customer['address'],
            $customer['type'],
            $customer['credit_limit'],
            $customer['status'],
            $customer['station_id'],
            $customer['merchandise_type']
        ]);
        
        echo "✅ Added customer: {$customer['name']} ({$customer['type']}, {$customer['merchandise_type']})\n";
        $insertedCount++;
    } catch (Exception $e) {
        echo "❌ Error adding customer '{$customer['name']}': " . $e->getMessage() . "\n";
    }
}

echo "\n📊 Summary: {$insertedCount} customers added, {$skippedCount} skipped\n";

// =====================================================
// STEP 4: Verify Data
// =====================================================
echo "\n=== STEP 4: Verification ===\n";

// Count customers for station 1250
$stmt = $pdo->prepare("SELECT COUNT(*) as count FROM customers WHERE station_id = 1250");
$stmt->execute();
$count = $stmt->fetch()['count'];
echo "Total customers for station 1250: {$count}\n";

// Count by type
$stmt = $pdo->prepare("SELECT type, COUNT(*) as count FROM customers WHERE station_id = 1250 GROUP BY type");
$stmt->execute();
$types = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
echo "By Type: " . json_encode($types) . "\n";

// Count by merchandise type
$stmt = $pdo->prepare("SELECT merchandise_type, COUNT(*) as count FROM customers WHERE station_id = 1250 GROUP BY merchandise_type");
$stmt->execute();
$merchTypes = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
echo "By Merchandise Type: " . json_encode($merchTypes) . "\n";

// Show product categories
echo "\nProduct Categories:\n";
$stmt = $pdo->query("SELECT id, name, description FROM product_categories ORDER BY id");
foreach ($stmt->fetchAll() as $cat) {
    echo "  [{$cat['id']}] {$cat['name']} - {$cat['description']}\n";
}

echo "\n✅ Migration completed successfully!\n";
echo "</pre>";
?>
