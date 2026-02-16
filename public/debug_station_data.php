<?php
/**
 * Debug: What does the LOGGED-IN user see?
 */

require_once __DIR__ . '/backend/lib.php';
require_once __DIR__ . '/public/db_connect.php';

require_login();

$me = current_user();
$station_id = user_station_id();

echo "<h1>Debug: Logged-In User Station Data</h1>\n";
echo "<style>
    pre { background: #f4f4f4; padding: 15px; overflow: auto; }
    .section { margin: 20px 0; padding: 15px; border: 1px solid #ddd; border-radius: 4px; }
    .error { background: #ffe6e6; border-color: #cc0000; }
</style>\n";

echo "<div class='section'>\n";
echo "<h2>Your Login Info</h2>\n";
echo "User ID: {$me['id']}<br>\n";
echo "User Name: {$me['name']}<br>\n";
echo "Role: {$me['role']}<br>\n";
echo "Station ID: $station_id<br>\n";
echo "</div>\n";

try {
    // Check fuel products for YOUR station
    echo "<div class='section'>\n";
    echo "<h2>Fuel Products for Your Station ($station_id)</h2>\n";
    
    $stmt = $pdo->prepare("
        SELECT p.id, p.name, p.type_id, ft.name as fuel_type_name, si.stock_level, si.unit, si.status,
               COALESCE(fp.price_per_liter, p.price, 0) as price_per_liter
        FROM products p
        INNER JOIN product_types pt ON p.type_id = pt.id
        LEFT JOIN station_inventory si ON p.id = si.product_id AND si.station_id = ? AND si.status = 'active'
        LEFT JOIN fuel_types ft ON p.type_id = ft.id
        LEFT JOIN fuel_pricing fp ON fp.fuel_type_id = ft.id AND fp.station_id = ? AND fp.is_active = 1
        WHERE pt.name = 'fuel'
        ORDER BY ft.name
    ");
    
    $stmt->execute([$station_id, $station_id]);
    $allFuels = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "Total fuel products in system: " . count($allFuels) . "<br>\n";
    echo "<pre>" . json_encode($allFuels, JSON_PRETTY_PRINT) . "</pre>\n";
    
    echo "<h3>Analysis:</h3>\n";
    foreach ($allFuels as $fuel) {
        if (is_null($fuel['stock_level'])) {
            echo "⚠ {$fuel['name']}: <strong>NO inventory record for station $station_id</strong><br>\n";
        } elseif ($fuel['status'] !== 'active') {
            echo "⚠ {$fuel['name']}: Status is '{$fuel['status']}' (not 'active')<br>\n";
        } else {
            echo "✓ {$fuel['name']}: Ready (Stock: {$fuel['stock_level']} {$fuel['unit']})<br>\n";
        }
    }
    
    echo "</div>\n";
    
    // Check station_inventory directly
    echo "<div class='section'>\n";
    echo "<h2>Station Inventory Records for Station $station_id</h2>\n";
    
    $stmt = $pdo->prepare("
        SELECT si.id, si.product_id, si.stock_level, si.unit, si.status, p.name
        FROM station_inventory si
        INNER JOIN products p ON si.product_id = p.id
        WHERE si.station_id = ?
    ");
    
    $stmt->execute([$station_id]);
    $invRecords = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "Total inventory records: " . count($invRecords) . "<br>\n";
    echo "<pre>" . json_encode($invRecords, JSON_PRETTY_PRINT) . "</pre>\n";
    echo "</div>\n";
    
    // Now test the actual POS query
    echo "<div class='section'>\n";
    echo "<h2>POS Query Result (as in pos.php)</h2>\n";
    
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
    $fuelProducts = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "Found: " . count($fuelProducts) . " fuel products<br>\n";
    
    if (count($fuelProducts) === 0) {
        echo "<div class='error' style='padding: 10px; border-radius: 4px;'>\n";
        echo "<strong>⚠ No fuel products found for your station!</strong><br>\n";
        echo "This is why you see '0 products' in the dropdown.<br>\n";
        echo "Possible reasons:<br>\n";
        echo "1. No fuel products in station_inventory for this station<br>\n";
        echo "2. Fuel inventory status is not 'active'<br>\n";
        echo "3. Fuel product not linked to fuel_types<br>\n";
        echo "</div>\n";
    } else {
        echo "<pre>" . json_encode($fuelProducts, JSON_PRETTY_PRINT) . "</pre>\n";
    }
    
    echo "</div>\n";
    
} catch (Exception $e) {
    echo "<div class='section error'>\n";
    echo "<h2>✗ Error</h2>\n";
    echo "<p>" . htmlspecialchars($e->getMessage()) . "</p>\n";
    echo "<pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>\n";
    echo "</div>\n";
}

?>
