<?php
/**
 * Debug: Check why fuel products not loading
 */

require_once __DIR__ . '/public/db_connect.php';
require_once __DIR__ . '/backend/lib.php';

echo "<h1>Debug: Fuel Products Loading</h1>\n";
echo "<style>
    pre { background: #f4f4f4; padding: 15px; border-radius: 4px; overflow: auto; }
    .error { color: red; font-weight: bold; }
    .section { margin: 20px 0; padding: 15px; border: 1px solid #ddd; }
</style>\n";

// Assume logged in as station 226
$station_id = 226;

try {
    // Step 1: Check if products exist
    echo "<div class='section'><h2>Step 1: Check if fuel products exist</h2>\n";
    $stmt = $pdo->query("
        SELECT p.id, p.name, p.type_id, pt.name as product_type_name
        FROM products p
        INNER JOIN product_types pt ON p.type_id = pt.id
        WHERE pt.name = 'fuel'
    ");
    $prods = $stmt->fetchAll();
    echo "Found " . count($prods) . " fuel products\n";
    echo "<pre>" . json_encode($prods, JSON_PRETTY_PRINT) . "</pre>\n";
    echo "</div>\n";
    
    // Step 2: Check station_inventory
    echo "<div class='section'><h2>Step 2: Check station_inventory for fuel products</h2>\n";
    $stmt = $pdo->query("
        SELECT si.id, si.product_id, si.station_id, si.stock_level, p.name
        FROM station_inventory si
        INNER JOIN products p ON si.product_id = p.id
        INNER JOIN product_types pt ON p.type_id = pt.id
        WHERE pt.name = 'fuel'
    ");
    $inv = $stmt->fetchAll();
    echo "Found " . count($inv) . " fuel products in inventory\n";
    echo "<pre>" . json_encode($inv, JSON_PRETTY_PRINT) . "</pre>\n";
    echo "</div>\n";
    
    // Step 3: Check for station 226 specifically
    echo "<div class='section'><h2>Step 3: Check station 226 inventory</h2>\n";
    $stmt = $pdo->prepare("
        SELECT si.id, si.product_id, si.station_id, si.stock_level, p.name, pt.name as type_name
        FROM station_inventory si
        INNER JOIN products p ON si.product_id = p.id
        INNER JOIN product_types pt ON p.type_id = pt.id
        WHERE si.station_id = ? AND pt.name = 'fuel'
    ");
    $stmt->execute([$station_id]);
    $inv226 = $stmt->fetchAll();
    echo "Found " . count($inv226) . " fuel products for station $station_id\n";
    echo "<pre>" . json_encode($inv226, JSON_PRETTY_PRINT) . "</pre>\n";
    echo "</div>\n";
    
    // Step 4: Test the actual query from pos.php
    echo "<div class='section'><h2>Step 4: Test actual POS query</h2>\n";
    $stmt = $pdo->prepare("
         SELECT p.id, p.name, p.type_id, ft.name as fuel_type_name, si.stock_level, si.unit,
                COALESCE(fp.price_per_liter, p.price, 0) as price_per_liter
         FROM products p
         INNER JOIN product_types pt ON p.type_id = pt.id
         INNER JOIN station_inventory si ON p.id = si.product_id AND si.station_id = ? AND si.status = 'active'
         INNER JOIN fuel_types ft ON p.type_id = ft.id
         LEFT JOIN fuel_pricing fp ON fp.fuel_type_id = ft.id AND fp.station_id = ? AND fp.is_active = 1
         WHERE pt.name = 'fuel'
         GROUP BY ft.id
         ORDER BY ft.name
     ");
    $stmt->execute([$station_id, $station_id]);
    $fuelProds = $stmt->fetchAll();
    echo "Query returned: " . count($fuelProds) . " fuel products\n";
    echo "<pre>" . json_encode($fuelProds, JSON_PRETTY_PRINT) . "</pre>\n";
    
    if (count($fuelProds) === 0) {
        echo "<p class='error'>⚠ No fuel products found! Debugging...</p>\n";
        
        // Check status of inventory
        $stmt = $pdo->prepare("
            SELECT si.id, si.product_id, si.status, p.name
            FROM station_inventory si
            INNER JOIN products p ON si.product_id = p.id
            INNER JOIN product_types pt ON p.type_id = pt.id
            WHERE si.station_id = ? AND pt.name = 'fuel'
        ");
        $stmt->execute([$station_id]);
        $statusCheck = $stmt->fetchAll();
        echo "<p>Status check (all fuel inventory for station):</p>\n";
        echo "<pre>" . json_encode($statusCheck, JSON_PRETTY_PRINT) . "</pre>\n";
    }
    
    echo "</div>\n";
    
} catch (Exception $e) {
    echo "<div class='section'><p class='error'>Error: " . htmlspecialchars($e->getMessage()) . "</p>\n";
    echo "<pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>\n";
    echo "</div>\n";
}

?>
