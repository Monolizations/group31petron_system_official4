<?php
/**
 * Test: Check what data is actually sent to POS page
 */

require_once __DIR__ . '/public/db_connect.php';
require_once __DIR__ . '/backend/lib.php';
require_once __DIR__ . '/backend/fuel_pos_sync.php';

// Simulate being logged in
$station_id = 226;

// Load inventory exactly as pos.php does
$inventory = [];
$fuelSyncStatus = [];

try {
    // Load merchandise products from inventory
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
    
    // Load fuel products grouped by fuel type with pricing and stock
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
    
    $inventory = [
        'fuel' => $fuelProducts,
        'merch' => $merchProducts
    ];
    
    // Load sync status for each fuel type
    $fuelSyncStatus = [];
    foreach ($fuelProducts as $fuel) {
        $syncStatus = getLastSyncStatus($pdo, $station_id, $fuel['type_id']);
        // Key by both product_id and type_id for compatibility
        $fuelSyncStatus[$fuel['id']] = $syncStatus;
        $fuelSyncStatus[$fuel['type_id']] = $syncStatus;
    }
    
} catch (Exception $e) {
    $inventory = ['fuel' => [], 'merch' => []];
    $fuelSyncStatus = [];
}

echo "<h1>Data sent to POS JavaScript</h1>\n";
echo "<style>
    pre { background: #f4f4f4; padding: 15px; border-radius: 4px; }
    .section { margin: 20px 0; }
</style>\n";

echo "<div class='section'>\n";
echo "<h2>inventoryData</h2>\n";
echo "<pre>\n";
echo htmlspecialchars(json_encode($inventory, JSON_PRETTY_PRINT));
echo "\n</pre>\n";
echo "Total fuel products: " . count($inventory['fuel']) . "<br>\n";
echo "Total merch products: " . count($inventory['merch']) . "<br>\n";
echo "</div>\n";

echo "<div class='section'>\n";
echo "<h2>fuelSyncStatus</h2>\n";
echo "<pre>\n";
echo htmlspecialchars(json_encode($fuelSyncStatus, JSON_PRETTY_PRINT));
echo "\n</pre>\n";
echo "</div>\n";

?>
