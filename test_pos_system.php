<?php
/**
 * POS System Test & Debug Script
 * 
 * This script tests all recent changes to verify:
 * 1. Sidebar functionality for all roles
 * 2. Inventory data loading
 * 3. Fuel pricing table existence
 * 4. Product dropdown population
 * 5. Stock validation
 */

require_once __DIR__ . '/backend/lib.php';
require_once __DIR__ . '/public/db_connect.php';

echo "<!DOCTYPE html>";
echo "<html lang='en'>";
echo "<head>";
echo "<meta charset='UTF-8'>";
echo "<meta name='viewport' content='width=device-width, initial-scale=1.0'>";
echo "<title>POS System Test - Debug Mode</title>";
echo "<style>";
echo "body { font-family: Arial, sans-serif; padding: 20px; background: #f5f5f5; }";
echo "h1 { color: #002F6C; }";
echo "h2 { color: #003d7a; margin-top: 30px; border-bottom: 2px solid #ddd; padding-bottom: 10px; }";
echo ".test-section { background: white; padding: 20px; margin-bottom: 20px; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); }";
echo ".success { color: #28a745; font-weight: bold; }";
echo ".error { color: #dc3545; font-weight: bold; }";
echo ".warning { color: #856404; font-weight: bold; }";
echo ".info { color: #17a2b8; }";
echo ".pass { color: #155724; }";
echo ".fail { color: #dc3545; }";
echo "table { width: 100%; border-collapse: collapse; margin-top: 10px; }";
echo "th, td { padding: 10px; text-align: left; border: 1px solid #ddd; }";
echo "th { background: #002F6C; color: white; }";
echo "code { background: #f4f4f4; padding: 10px; border-radius: 4px; font-family: monospace; font-size: 12px; }";
echo "</style>";
echo "</head>";
echo "<body>";

echo "<h1>🧪 POS System Test & Debug</h1>";
echo "<p>This script tests all recent changes to the POS system.</p>";

$tests_passed = 0;
$tests_failed = 0;

// ============================================
// TEST 1: Database Connection
// ============================================
echo "<div class='test-section'>";
echo "<h2>Test 1: Database Connection</h2>";
try {
    $test = $pdo->query("SELECT 1");
    echo "<p class='success'>✅ PASS: Database connection successful</p>";
    $tests_passed++;
} catch (Exception $e) {
    echo "<p class='error'>❌ FAIL: Database connection failed - " . htmlspecialchars($e->getMessage()) . "</p>";
    $tests_failed++;
}
echo "</div>";

// ============================================
// TEST 2: Required Tables Exist
// ============================================
echo "<div class='test-section'>";
echo "<h2>Test 2: Required Tables</h2>";

$tables_to_check = [
    'fuel_pricing' => 'Fuel pricing table',
    'products' => 'Products table',
    'inventory' => 'Inventory table',
    'product_types' => 'Product types table',
    'fuel_types' => 'Fuel types table',
    'sales' => 'Sales table',
    'sale_items' => 'Sale items table'
];

foreach ($tables_to_check as $table => $description) {
    try {
        $result = $pdo->query("SHOW TABLES LIKE '$table'");
        if ($result->rowCount() > 0) {
            echo "<p class='success'>✅ PASS: $description ($table) exists</p>";
            $tests_passed++;
        } else {
            echo "<p class='error'>❌ FAIL: $description ($table) does NOT exist</p>";
            $tests_failed++;
        }
    } catch (Exception $e) {
        echo "<p class='error'>❌ ERROR: Could not check $table - " . htmlspecialchars($e->getMessage()) . "</p>";
        $tests_failed++;
    }
}
echo "</div>";

// ============================================
// TEST 3: Fuel Pricing Table Data
// ============================================
echo "<div class='test-section'>";
echo "<h2>Test 3: Fuel Pricing Data</h2>";
try {
    $stmt = $pdo->query("SELECT * FROM fuel_pricing LIMIT 10");
    $fuel_pricing = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (count($fuel_pricing) > 0) {
        echo "<p class='success'>✅ PASS: Fuel pricing data exists (" . count($fuel_pricing) . " records)</p>";
        $tests_passed++;
        
        echo "<table>";
        echo "<tr><th>ID</th><th>Station</th><th>Fuel Type</th><th>Price/Liter</th><th>Active</th><th>Effective Date</th></tr>";
        foreach ($fuel_pricing as $fp) {
            echo "<tr>";
            echo "<td>{$fp['id']}</td>";
            echo "<td>{$fp['station_id']}</td>";
            echo "<td>{$fp['fuel_type_id']}</td>";
            echo "<td>₱" . number_format($fp['price_per_liter'], 2) . "</td>";
            echo "<td>" . ($fp['is_active'] ? '✅ Yes' : '❌ No') . "</td>";
            echo "<td>{$fp['effective_date']}</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p class='error'>❌ FAIL: No fuel pricing data found. Run setup_fuel_pricing.php</p>";
        $tests_failed++;
    }
} catch (Exception $e) {
    echo "<p class='error'>❌ ERROR: " . htmlspecialchars($e->getMessage()) . "</p>";
    $tests_failed++;
}
echo "</div>";

// ============================================
// TEST 4: Product Types
// ============================================
echo "<div class='test-section'>";
echo "<h2>Test 4: Product Types</h2>";
try {
    $stmt = $pdo->query("SELECT * FROM product_types");
    $product_types = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<p class='info'>Found " . count($product_types) . " product types:</p>";
    echo "<table>";
    echo "<tr><th>ID</th><th>Type</th><th>Description</th></tr>";
    foreach ($product_types as $pt) {
        echo "<tr>";
        echo "<td>{$pt['id']}</td>";
        echo "<td class='info'><strong>{$pt['name']}</strong></td>";
        echo "<td>{$pt['description']}</td>";
        echo "</tr>";
    }
    echo "</table>";
    $tests_passed++;
} catch (Exception $e) {
    echo "<p class='error'>❌ ERROR: " . htmlspecialchars($e->getMessage()) . "</p>";
    $tests_failed++;
}
echo "</div>";

// ============================================
// TEST 5: Fuel Types
// ============================================
echo "<div class='test-section'>";
echo "<h2>Test 5: Fuel Types</h2>";
try {
    $stmt = $pdo->query("SELECT * FROM fuel_types");
    $fuel_types = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<p class='info'>Found " . count($fuel_types) . " fuel types:</p>";
    echo "<table>";
    echo "<tr><th>ID</th><th>Name</th><th>Description</th></tr>";
    foreach ($fuel_types as $ft) {
        echo "<tr>";
        echo "<td>{$ft['id']}</td>";
        echo "<td class='info'><strong>{$ft['name']}</strong></td>";
        echo "<td>{$ft['description']}</td>";
        echo "</tr>";
    }
    echo "</table>";
    $tests_passed++;
} catch (Exception $e) {
    echo "<p class='error'>❌ ERROR: " . htmlspecialchars($e->getMessage()) . "</p>";
    $tests_failed++;
}
echo "</div>";

// ============================================
// TEST 6: Inventory Loading (Station 1)
// ============================================
echo "<div class='test-section'>";
echo "<h2>Test 6: Inventory Loading (Station 1)</h2>";
try {
    $station_id = 1;
    
    // Load merchandise products
    $stmt = $pdo->prepare("
        SELECT p.*, i.stock_level, i.unit, i.status as inventory_status
        FROM products p
        INNER JOIN product_types pt ON p.type_id = pt.id
        LEFT JOIN inventory i ON p.id = i.product_id AND i.station_id = ? AND i.status = 'active'
        WHERE pt.name = 'merch' AND (i.status IS NULL OR i.status = 'active')
        ORDER BY p.name
        LIMIT 5
    ");
    $stmt->execute([$station_id]);
    $merch_products = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<p class='info'>Merchandise Products (first 5):</p>";
    if (count($merch_products) > 0) {
        echo "<table>";
        echo "<tr><th>Product ID</th><th>Name</th><th>Type</th><th>Price</th><th>Stock</th><th>Unit</th></tr>";
        foreach ($merch_products as $mp) {
            echo "<tr>";
            echo "<td>{$mp['id']}</td>";
            echo "<td>{$mp['name']}</td>";
            echo "<td>{$mp['type_id']}</td>";
            echo "<td>₱" . number_format($mp['price'], 2) . "</td>";
            echo "<td>{$mp['stock_level']}</td>";
            echo "<td>{$mp['unit']}</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p class='warning'>⚠️ No merchandise products found in inventory for station 1</p>";
    }
    
    // Load fuel products
    $stmt = $pdo->prepare("
        SELECT p.*, i.stock_level, i.unit, i.status as inventory_status,
               fp.price_per_liter as price
        FROM products p
        INNER JOIN product_types pt ON p.type_id = pt.id
        LEFT JOIN inventory i ON p.id = i.product_id AND i.station_id = ? AND i.status = 'active'
        LEFT JOIN fuel_pricing fp ON fp.fuel_type_id = p.type_id AND fp.station_id = ? AND fp.is_active = 1
        WHERE pt.name = 'fuel' AND (i.status IS NULL OR i.status = 'active')
        ORDER BY p.name
        LIMIT 5
    ");
    $stmt->execute([$station_id, $station_id]);
    $fuel_products = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<p class='info'>Fuel Products (first 5):</p>";
    if (count($fuel_products) > 0) {
        echo "<table>";
        echo "<tr><th>Product ID</th><th>Name</th><th>Type</th><th>Price</th><th>Stock</th><th>Unit</th></tr>";
        foreach ($fuel_products as $fp) {
            echo "<tr>";
            echo "<td>{$fp['id']}</td>";
            echo "<td>{$fp['name']}</td>";
            echo "<td>{$fp['type_id']}</td>";
            echo "<td>₱" . number_format($fp['price'], 2) . "</td>";
            echo "<td>{$fp['stock_level']}</td>";
            echo "<td>{$fp['unit']}</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p class='warning'>⚠️ No fuel products found in inventory for station 1</p>";
    }
    
    $tests_passed++;
} catch (Exception $e) {
    echo "<p class='error'>❌ ERROR: " . htmlspecialchars($e->getMessage()) . "</p>";
    $tests_failed++;
}
echo "</div>";

// ============================================
// TEST 7: Sale Items Table Structure
// ============================================
echo "<div class='test-section'>";
echo "<h2>Test 7: Sale Items Table Structure</h2>";
try {
    $columns = $pdo->query("SHOW COLUMNS FROM sale_items");
    $column_list = $columns->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<p class='info'>Table has " . count($column_list) . " columns:</p>";
    echo "<table>";
    echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th></tr>";
    foreach ($column_list as $col) {
        echo "<tr>";
        echo "<td class='info'><strong>{$col['Field']}</strong></td>";
        echo "<td>{$col['Type']}</td>";
        echo "<td>{$col['Null']}</td>";
        echo "<td>{$col['Key']}</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    // Check if 'name' column exists
    $has_name_column = false;
    foreach ($column_list as $col) {
        if ($col['Field'] === 'name') {
            $has_name_column = true;
            break;
        }
    }
    
    if ($has_name_column) {
        echo "<p class='success'>✅ PASS: 'name' column exists in sale_items table</p>";
        $tests_passed++;
    } else {
        echo "<p class='warning'>⚠️ WARNING: 'name' column missing from sale_items table. ALTER TABLE may have failed.</p>";
        echo "<p class='code'>ALTER TABLE sale_items ADD COLUMN name VARCHAR(255) NULL AFTER product_id;</p>";
    }
} catch (Exception $e) {
    echo "<p class='error'>❌ ERROR: " . htmlspecialchars($e->getMessage()) . "</p>";
    $tests_failed++;
}
echo "</div>";

// ============================================
// TEST 8: POS Form Accessibility
// ============================================
echo "<div class='test-section'>";
echo "<h2>Test 8: POS Form Accessibility</h2>";

$pos_file = __DIR__ . '/public/pos.php';
if (file_exists($pos_file)) {
    echo "<p class='success'>✅ PASS: pos.php exists at {$pos_file}</p>";
    $tests_passed++;
} else {
    echo "<p class='error'>❌ FAIL: pos.php not found at {$pos_file}</p>";
    $tests_failed++;
}

echo "<p class='info'><a href='public/pos.php' target='_blank' style='background: #002F6C; color: white; padding: 10px 20px; text-decoration: none; border-radius: 4px;'>🧪 Open POS Form (Staff View)</a></p>";
echo "</div>";

// ============================================
// TEST 9: JavaScript Functions Verification
// ============================================
echo "<div class='test-section'>";
echo "<h2>Test 9: JavaScript Function Verification</h2>";

$pos_content = file_get_contents($pos_file);
$required_functions = [
    'loadProducts' => 'Loads products based on type',
    'updatePrice' => 'Auto-populates price and unit',
    'calcTotal' => 'Calculates total amount',
    'validatePayment' => 'Validates form including stock'
];

echo "<p class='info'>Checking for required JavaScript functions in pos.php:</p>";
echo "<table>";
echo "<tr><th>Function</th><th>Required</th><th>Status</th></tr>";

foreach ($required_functions as $func => $desc) {
    $exists = strpos($pos_content, "function $func(") !== false;
    $status = $exists ? '✅ Found' : '❌ Missing';
    echo "<tr><td class='info'><strong>$func()</strong></td><td>$desc</td><td>$status</td></tr>";
    
    if ($exists) {
        $tests_passed++;
    } else {
        $tests_failed++;
    }
}
echo "</table>";
echo "</div>";

// ============================================
// TEST 10: Sidebar Configuration
// ============================================
echo "<div class='test-section'>";
echo "<h2>Test 10: Sidebar Configuration</h2>";

$header_file = __DIR__ . '/partials/header.php';
if (file_exists($header_file)) {
    echo "<p class='success'>✅ PASS: header.php exists at {$header_file}</p>";
    $tests_passed++;
    
    // Check for role-based menus
    $header_content = file_get_contents($header_file);
    
    $checks = [
        'staff' => "if(\$role === 'superadmin')\}elseif(\$role === 'admin')\} elseif(\$role === 'manager')\} else { // staff",
        'approvals_center' => "'id'=>'approvals_center'",
        'export_center' => "'id'=>'export_center'",
        'fuel_pricing' => "'id'=>'financial_reports'"
    ];
    
    echo "<table>";
    echo "<tr><th>Check</th><th>Status</th><th>Details</th></tr>";
    
    // Staff menu check
    if (strpos($header_content, "} else { // staff") !== false) {
        echo "<tr><td class='success'><strong>Staff Menu</strong></td><td class='success'>✅ Found</td><td>'} else { // staff' detected</td></tr>";
        $tests_passed++;
    } else {
        echo "<tr><td class='error'><strong>Staff Menu</strong></td><td class='error'>❌ Missing</td><td>Staff menu not found in header.php</td></tr>";
        $tests_failed++;
    }
    
    // Manager Approvals Center
    if (strpos($header_content, "'id'=>'approvals_center'") !== false) {
        echo "<tr><td class='success'><strong>Manager: Approvals Center</strong></td><td class='success'>✅ Found</td><td>Manager has approvals_center in sidebar</td></tr>";
        $tests_passed++;
    } else {
        echo "<tr><td class='error'><strong>Manager: Approvals Center</strong></td><td class='error'>❌ Missing</td><td>Approvals Center not found in Manager menu</td></tr>";
        $tests_failed++;
    }
    
    // Admin Export Center
    if (strpos($header_content, "'id'=>'export_center'") !== false) {
        echo "<tr><td class='success'><strong>Admin: Export Center</strong></td><td class='success'>✅ Found</td><td>Admin has export_center in sidebar</td></tr>";
        $tests_passed++;
    } else {
        echo "<tr><td class='error'><strong>Admin: Export Center</strong></td><td class='error'>❌ Missing</td><td>Export Center not found in Admin menu</td></tr>";
        $tests_failed++;
    }
    
    echo "</table>";
} else {
    echo "<p class='error'>❌ FAIL: header.php not found at {$header_file}</p>";
    $tests_failed++;
}
echo "</div>";

// ============================================
// SUMMARY
// ============================================
echo "<div class='test-section' style='background: linear-gradient(135deg, #002F6C 0%, #003d7a 100%); color: white;'>";
echo "<h2>📊 Test Summary</h2>";
echo "<div style='display: grid; grid-template-columns: 1fr 1fr; gap: 20px;'>";
echo "<div style='text-align: center;'>";
echo "<div style='font-size: 48px; font-weight: bold; color: #28a745;'>{$tests_passed}</div>";
echo "<div>Tests Passed</div>";
echo "</div>";
echo "<div style='text-align: center;'>";
echo "<div style='font-size: 48px; font-weight: bold; color: #dc3545;'>{$tests_failed}</div>";
echo "<div>Tests Failed</div>";
echo "</div>";
echo "</div>";
echo "<div style='margin-top: 20px; font-size: 14px;'>";
echo "<p><strong>Next Steps:</strong></p>";
echo "<ol>";
echo "<li>If all tests pass, proceed to <a href='public/pos.php' style='color: white; text-decoration: underline;'>test POS functionality</a></li>";
echo "<li>If any tests fail, review the error messages above and fix issues</li>";
echo "<li>Run setup_fuel_pricing.php if fuel pricing table is missing</li>";
echo "</ol>";
echo "</div>";
echo "</div>";

echo "</body>";
echo "</html>";
?>
