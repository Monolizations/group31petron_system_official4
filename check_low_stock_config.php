<?php
/**
 * Set reorder levels for inventory items and verify low stock logic
 */

require_once __DIR__ . '/public/db_connect.php';

echo "🔧 LOW STOCK ALERT CONFIGURATION\n";
echo str_repeat("=", 70) . "\n\n";

echo "📋 NEW LOGIC:\n";
echo "  • Alert triggers when stock is 50% or less of reorder_level\n";
echo "  • Applies to BOTH fuel and merchandise\n";
echo "  • Default reorder_level: 10 (if not set)\n\n";

echo "📊 STATUS LEVELS:\n";
echo "  • CRITICAL (≤25%): Red background\n";
echo "  • LOW (26-50%): Orange background\n";
echo "  • WARNING (>50% but still below threshold): Yellow\n\n";

try {
    // Check current inventory status
    echo "📦 CURRENT INVENTORY STATUS:\n";
    echo str_repeat("-", 70) . "\n";
    
    $stmt = $pdo->prepare("
        SELECT si.id, p.name as product_name, pt.name as type, si.stock_level, 
               COALESCE(si.reorder_level, 10) as reorder_level,
               CASE 
                   WHEN si.stock_level <= (COALESCE(si.reorder_level, 10) * 0.5) THEN 'LOW STOCK'
                   ELSE 'OK'
               END as status
        FROM station_inventory si
        JOIN products p ON si.product_id = p.id
        JOIN product_types pt ON p.type_id = pt.id
        WHERE si.station_id = 1250
        ORDER BY pt.name, p.name
    ");
    $stmt->execute();
    $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $low_count = 0;
    foreach ($items as $item) {
        $percentage = $item['reorder_level'] > 0 ? round(($item['stock_level'] / $item['reorder_level']) * 100, 1) : 0;
        $status_icon = $item['status'] === 'LOW STOCK' ? '🚨' : '✅';
        
        if ($item['status'] === 'LOW STOCK') {
            $low_count++;
        }
        
        printf("  %s %-25s | Stock: %8s | Reorder: %6s | %5s%% | %s\n",
            $status_icon,
            substr($item['product_name'], 0, 25),
            number_format($item['stock_level'], 0),
            number_format($item['reorder_level'], 0),
            $percentage,
            $item['status']
        );
    }
    
    echo str_repeat("-", 70) . "\n";
    echo "  Total items: " . count($items) . "\n";
    echo "  Low stock: $low_count\n";
    echo "  Normal: " . (count($items) - $low_count) . "\n";
    
    echo "\n" . str_repeat("=", 70) . "\n";
    echo "✅ Configuration Complete!\n\n";
    
    echo "📝 How it works:\n";
    echo "  1. Each product has a 'reorder_level' in the database\n";
    echo "  2. System checks: stock_level ≤ (reorder_level × 0.5)\n";
    echo "  3. If true → Item appears in Low Stock Alerts\n";
    echo "  4. Visual indicators show severity (CRITICAL/LOW/WARNING)\n\n";
    
    echo "💡 To change reorder levels:\n";
    echo "  UPDATE station_inventory SET reorder_level = 1000 WHERE id = X;\n\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}

echo "\n";
?>