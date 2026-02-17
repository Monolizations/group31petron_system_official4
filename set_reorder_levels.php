<?php
/**
 * Set reorder levels for fuel and merchandise at Station 1250
 */

require_once __DIR__ . '/public/db_connect.php';

echo "🔧 SETTING REORDER LEVELS FOR STATION 1250\n";
echo str_repeat("=", 70) . "\n\n";

try {
    // Set reorder levels for fuel products (based on typical tank capacity of 10000L)
    $fuel_reorder_levels = [
        'Diesel Max' => 2000,      // Alert at 1000L (50% of 2000)
        'XCS Plus' => 3000,        // Alert at 1500L
        'XCS Advance' => 2000,     // Alert at 1000L
        'Turbo Diesel' => 2000,    // Alert at 1000L
        'Kerosene' => 1500,        // Alert at 750L
    ];
    
    echo "⛽ Setting Fuel Reorder Levels:\n";
    echo str_repeat("-", 70) . "\n";
    
    foreach ($fuel_reorder_levels as $fuel_name => $reorder_level) {
        $stmt = $pdo->prepare("
            UPDATE station_inventory si
            JOIN products p ON si.product_id = p.id
            SET si.reorder_level = ?
            WHERE p.name = ? AND si.station_id = 1250
        ");
        $stmt->execute([$reorder_level, $fuel_name]);
        
        if ($stmt->rowCount() > 0) {
            echo "  ✅ $fuel_name: Reorder level set to $reorder_level L\n";
            echo "     → Low stock alert at " . ($reorder_level * 0.5) . " L (50%)\n";
        } else {
            echo "  ℹ️  $fuel_name: Not found or already set\n";
        }
    }
    
    echo "\n";
    
    // Set reorder levels for merchandise (based on typical stock)
    $merch_reorder_levels = [
        'Engine Oil 5W-30' => 20,    // Alert at 10 pieces
        'HD 10' => 15,               // Alert at 7 pieces
        'HD 30' => 15,               // Alert at 7 pieces
        'kanesheto' => 50,           // Alert at 25 pieces
        'PAIL/18 Liters' => 20,      // Alert at 10 pieces
        'tatakae' => 50,             // Alert at 25 pieces
        'wdw' => 20,                 // Alert at 10 pieces
    ];
    
    echo "📦 Setting Merchandise Reorder Levels:\n";
    echo str_repeat("-", 70) . "\n";
    
    foreach ($merch_reorder_levels as $product_name => $reorder_level) {
        $stmt = $pdo->prepare("
            UPDATE station_inventory si
            JOIN products p ON si.product_id = p.id
            SET si.reorder_level = ?
            WHERE p.name = ? AND si.station_id = 1250
        ");
        $stmt->execute([$reorder_level, $product_name]);
        
        if ($stmt->rowCount() > 0) {
            echo "  ✅ $product_name: Reorder level set to $reorder_level pieces\n";
            echo "     → Low stock alert at " . ($reorder_level * 0.5) . " pieces (50%)\n";
        } else {
            echo "  ℹ️  $product_name: Not found or already set\n";
        }
    }
    
    echo "\n" . str_repeat("=", 70) . "\n";
    
    // Show updated status
    echo "📊 UPDATED LOW STOCK STATUS:\n";
    echo str_repeat("-", 70) . "\n";
    
    $stmt = $pdo->prepare("
        SELECT p.name as product_name, pt.name as type, si.stock_level, si.reorder_level,
               ROUND((si.stock_level / NULLIF(si.reorder_level, 0)) * 100, 1) as percentage
        FROM station_inventory si
        JOIN products p ON si.product_id = p.id
        JOIN product_types pt ON p.type_id = pt.id
        WHERE si.station_id = 1250 AND si.reorder_level > 0
        ORDER BY pt.name, p.name
    ");
    $stmt->execute();
    $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $low_count = 0;
    foreach ($items as $item) {
        $percentage = $item['percentage'] ?? 0;
        $is_low = $percentage <= 50;
        $status_icon = $is_low ? '🚨' : '✅';
        
        if ($is_low) {
            $low_count++;
        }
        
        $unit = $item['type'] === 'fuel' ? 'L' : 'pcs';
        
        printf("  %s %-25s | Stock: %8s %s | Reorder: %6s | %5s%% | %s\n",
            $status_icon,
            substr($item['product_name'], 0, 25),
            number_format($item['stock_level'], 0),
            $unit,
            number_format($item['reorder_level'], 0),
            $percentage,
            $is_low ? 'LOW STOCK' : 'OK'
        );
    }
    
    echo str_repeat("-", 70) . "\n";
    echo "  Items configured: " . count($items) . "\n";
    echo "  Low stock alerts: $low_count\n";
    
    if ($low_count > 0) {
        echo "\n⚠️  ATTENTION: $low_count items are currently below 50% of reorder level!\n";
        echo "   Check the Low Stock Alerts tab in Inventory Management.\n";
    } else {
        echo "\n✅ All items are above 50% of their reorder levels.\n";
    }
    
    echo "\n" . str_repeat("=", 70) . "\n";
    echo "✅ REORDER LEVELS CONFIGURED!\n\n";
    
    echo "📝 Summary:\n";
    echo "  • Fuel products: 50% threshold based on tank capacity\n";
    echo "  • Merchandise: 50% threshold based on typical usage\n";
    echo "  • Applies to ALL items at Station 1250\n";
    echo "  • Visual alerts show severity (CRITICAL ≤25%, LOW ≤50%)\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}

echo "\n";
?>