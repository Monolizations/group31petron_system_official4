<?php
/**
 * Test: Fuel Dropdown Display
 */

require_once __DIR__ . '/public/db_connect.php';
require_once __DIR__ . '/backend/lib.php';
require_once __DIR__ . '/backend/fuel_pos_sync.php';

$station_id = 226; // Test station

echo "<h1>Fuel Dropdown Test - JSON Data</h1>\n";
echo "<style>
    pre { background: #f4f4f4; padding: 15px; border-radius: 4px; overflow-x: auto; }
    .section { margin: 20px 0; padding: 15px; border: 1px solid #ddd; border-radius: 4px; }
</style>\n";

try {
    // Load fuel products
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
    
    echo "<div class='section'>\n";
    echo "<h2>Raw Fuel Products Data</h2>\n";
    echo "<pre>" . json_encode($fuelProducts, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "</pre>\n";
    echo "</div>\n";
    
    // Load sync status
    $fuelSyncStatus = [];
    foreach ($fuelProducts as $fuel) {
        $syncStatus = getLastSyncStatus($pdo, $station_id, $fuel['type_id']);
        $fuelSyncStatus[$fuel['id']] = $syncStatus;
        $fuelSyncStatus[$fuel['type_id']] = $syncStatus;
    }
    
    echo "<div class='section'>\n";
    echo "<h2>Fuel Sync Status</h2>\n";
    echo "<pre>" . json_encode($fuelSyncStatus, JSON_PRETTY_PRINT) . "</pre>\n";
    echo "</div>\n";
    
    // Prepare inventory data as it would be sent to JavaScript
    $inventory = [
        'fuel' => $fuelProducts,
        'merch' => []
    ];
    
    echo "<div class='section'>\n";
    echo "<h2>inventoryData (as sent to JavaScript)</h2>\n";
    echo "<pre>" . json_encode($inventory, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "</pre>\n";
    echo "</div>\n";
    
    // Show formatted dropdown options
    echo "<div class='section'>\n";
    echo "<h2>Formatted Dropdown Options</h2>\n";
    echo "<select style='width: 100%; padding: 10px; font-size: 14px;'>\n";
    echo "<option>Select Product</option>\n";
    
    foreach ($fuelProducts as $product) {
        $stockLevel = (float) $product['stock_level'];
        $fuelName = $product['fuel_type_name'] ?? $product['name'];
        $price = (float) ($product['price_per_liter'] ?? 0);
        $priceText = $price > 0 ? '₱' . number_format($price, 2) . '/L' : 'Price TBD';
        $stockText = "Stock: {$stockLevel} " . ($product['unit'] ?? 'L');
        $optionText = "{$fuelName} - {$priceText} ({$stockText})";
        
        echo "<option value='{$product['id']}'>{$optionText}</option>\n";
    }
    
    echo "</select>\n";
    echo "</div>\n";
    
    echo "<div class='section'>\n";
    echo "<h2>✓ Test Complete</h2>\n";
    echo "Fuel products loaded: " . count($fuelProducts) . "<br>\n";
    echo "Sample dropdown format: [Fuel Type] - [Price/L] (Stock: [X] [Unit])<br>\n";
    echo "</div>\n";
    
} catch (Exception $e) {
    echo "<div class='section' style='background: #ffe6e6; border-color: #cc0000;'>\n";
    echo "<h2>✗ Error</h2>\n";
    echo "<pre>" . htmlspecialchars($e->getMessage()) . "\n" . htmlspecialchars($e->getTraceAsString()) . "</pre>\n";
    echo "</div>\n";
}

?>
