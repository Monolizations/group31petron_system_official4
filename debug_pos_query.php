<?php
/**
 * Debug: Test the exact fuel products query from pos.php
 */

require_once __DIR__ . '/public/db_connect.php';

$station_id = 226;

echo "<h1>Testing POS Fuel Query Step-by-Step</h1>\n";
echo "<style>pre { background: #f4f4f4; padding: 15px; overflow: auto; }</style>\n";

try {
    echo "<h2>1. Testing merchandise query</h2>\n";
    $stmt = $pdo->prepare("
        SELECT p.id, p.name, p.type_id, p.price, p.cost, p.sku, si.stock_level, si.unit, si.status as inventory_status
        FROM products p
        INNER JOIN product_types pt ON p.type_id = pt.id
        INNER JOIN station_inventory si ON p.id = si.product_id AND si.station_id = ? AND si.status = 'active'
        WHERE pt.name = 'merch'
        ORDER BY p.name
    ");
    $stmt->execute([$station_id]);
    $merchProducts = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "✓ Merchandise query succeeded. Found: " . count($merchProducts) . " products\n";
    
    echo "<h2>2. Testing fuel query</h2>\n";
    echo "<p>About to execute fuel query with station_id=$station_id</p>\n";
    
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
    
    echo "<p>Query prepared successfully</p>\n";
    
    $stmt->execute([$station_id, $station_id]);
    echo "<p>Query executed successfully</p>\n";
    
    $fuelProducts = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "✓ Fuel query succeeded. Found: " . count($fuelProducts) . " fuel products\n";
    
    if (count($fuelProducts) > 0) {
        echo "<h3>Fuel Products Data:</h3>\n";
        echo "<pre>" . json_encode($fuelProducts, JSON_PRETTY_PRINT) . "</pre>\n";
    } else {
        echo "<p style='color: red;'><strong>⚠ No fuel products found!</strong></p>\n";
    }
    
    echo "<h2>3. Testing inventory array</h2>\n";
    $inventory = [
        'fuel' => $fuelProducts,
        'merch' => $merchProducts
    ];
    
    echo "<p>Inventory array created successfully</p>\n";
    echo "Fuel count: " . count($inventory['fuel']) . "\n";
    echo "Merch count: " . count($inventory['merch']) . "\n";
    
    echo "<h2>4. Testing JSON encode for JavaScript</h2>\n";
    $json = json_encode($inventory);
    if ($json === false) {
        echo "<p style='color: red;'><strong>✗ JSON encode failed!</strong></p>\n";
        echo "Error: " . json_last_error_msg() . "\n";
    } else {
        echo "<p>✓ JSON encode succeeded</p>\n";
        echo "<p>JSON size: " . strlen($json) . " bytes</p>\n";
        echo "<pre>" . htmlspecialchars($json) . "</pre>\n";
    }
    
    echo "<h2>✓ All tests passed!</h2>\n";
    
} catch (PDOException $e) {
    echo "<h2 style='color: red;'>✗ Database Error</h2>\n";
    echo "<p>" . htmlspecialchars($e->getMessage()) . "</p>\n";
    echo "<pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>\n";
} catch (Exception $e) {
    echo "<h2 style='color: red;'>✗ Error</h2>\n";
    echo "<p>" . htmlspecialchars($e->getMessage()) . "</p>\n";
    echo "<pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>\n";
}

?>
