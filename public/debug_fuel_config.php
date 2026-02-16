<?php
/**
 * Debug script to check fuel product configuration
 * Shows what's missing and helps fix it
 */

require_once __DIR__ . '/db_connect.php';

try {
    echo "<h1>Fuel Product Configuration Debug</h1>\n";
    echo "<style>body { font-family: monospace; padding: 20px; } table { border-collapse: collapse; width: 100%; margin: 20px 0; } th, td { border: 1px solid #ddd; padding: 8px; text-align: left; } th { background: #f0f0f0; }</style>\n";
    
    // Get user's station
    session_start();
    $station_id = $_SESSION['station_id'] ?? 1;
    echo "<p><strong>Your Station ID:</strong> $station_id</p>\n";
    
    // Check fuel_types
    echo "<h2>1. Fuel Types in System</h2>\n";
    $stmt = $pdo->prepare("SELECT * FROM fuel_types ORDER BY id");
    $stmt->execute();
    $fuel_types = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<table><tr><th>ID</th><th>Name</th></tr>";
    foreach ($fuel_types as $ft) {
        echo "<tr><td>{$ft['id']}</td><td>{$ft['name']}</td></tr>";
    }
    echo "</table>";
    
    // Check fuel products
    echo "<h2>2. Fuel Products (type_id = 1)</h2>\n";
    $stmt = $pdo->prepare("SELECT p.id, p.name, p.type_id, p.fuel_type_id, pt.name as type_name FROM products p LEFT JOIN product_types pt ON p.type_id = pt.id WHERE p.type_id = 1 ORDER BY p.id");
    $stmt->execute();
    $fuel_products = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<table><tr><th>ID</th><th>Name</th><th>type_id</th><th>fuel_type_id</th><th>Type Name</th></tr>";
    foreach ($fuel_products as $fp) {
        $ft_id = $fp['fuel_type_id'] ? $fp['fuel_type_id'] : '<span style="color:red;">NULL</span>';
        echo "<tr><td>{$fp['id']}</td><td>{$fp['name']}</td><td>{$fp['type_id']}</td><td>$ft_id</td><td>{$fp['type_name']}</td></tr>";
    }
    echo "</table>";
    
    // Check fuel_inventory for user's station
    echo "<h2>3. Fuel Inventory (Station ID: $station_id)</h2>\n";
    $stmt = $pdo->prepare("SELECT fi.id, fi.product_id, fi.stock_level, fi.unit, fi.status, p.name FROM fuel_inventory fi JOIN products p ON fi.product_id = p.id WHERE fi.station_id = ? ORDER BY p.name");
    $stmt->execute([$station_id]);
    $fuel_inv = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (count($fuel_inv) > 0) {
        echo "<table><tr><th>ID</th><th>Product ID</th><th>Product Name</th><th>Stock Level</th><th>Unit</th><th>Status</th></tr>";
        foreach ($fuel_inv as $fi) {
            echo "<tr><td>{$fi['id']}</td><td>{$fi['product_id']}</td><td>{$fi['name']}</td><td>{$fi['stock_level']}</td><td>{$fi['unit']}</td><td>{$fi['status']}</td></tr>";
        }
        echo "</table>";
    } else {
        echo "<p style='color:red;'><strong>NO FUEL INVENTORY FOUND for station $station_id!</strong></p>";
    }
    
    // Check fuel_pricing
    echo "<h2>4. Fuel Pricing (Station ID: $station_id)</h2>\n";
    $stmt = $pdo->prepare("SELECT fp.id, fp.fuel_type_id, ft.name, fp.price_per_liter, fp.is_active FROM fuel_pricing fp JOIN fuel_types ft ON fp.fuel_type_id = ft.id WHERE fp.station_id = ? ORDER BY ft.name");
    $stmt->execute([$station_id]);
    $fuel_pricing = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (count($fuel_pricing) > 0) {
        echo "<table><tr><th>ID</th><th>Fuel Type ID</th><th>Fuel Type</th><th>Price/Liter</th><th>Active</th></tr>";
        foreach ($fuel_pricing as $fp) {
            $active = $fp['is_active'] ? 'Yes' : 'No';
            echo "<tr><td>{$fp['id']}</td><td>{$fp['fuel_type_id']}</td><td>{$fp['name']}</td><td>₱{$fp['price_per_liter']}</td><td>$active</td></tr>";
        }
        echo "</table>";
    } else {
        echo "<p style='color:red;'><strong>NO FUEL PRICING FOUND for station $station_id!</strong></p>";
    }
    
    // Test the POS query
    echo "<h2>5. POS Query Result (What POS will show)</h2>\n";
    $stmt = $pdo->prepare("
        SELECT p.id, p.name, p.fuel_type_id, ft.name as fuel_type_name, 
               SUM(fi.stock_level) as stock_level, fi.unit,
               COALESCE(fp.price_per_liter, p.price, 0) as price_per_liter
        FROM fuel_inventory fi
        INNER JOIN products p ON fi.product_id = p.id
        INNER JOIN fuel_types ft ON p.fuel_type_id = ft.id
        LEFT JOIN fuel_pricing fp ON fp.fuel_type_id = ft.id AND fp.station_id = ? AND fp.is_active = 1
        WHERE fi.station_id = ? AND fi.status = 'active'
        GROUP BY ft.id, fp.id
        ORDER BY ft.name
    ");
    $stmt->execute([$station_id, $station_id]);
    $pos_results = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (count($pos_results) > 0) {
        echo "<p style='color:green;'><strong>✓ POS will show " . count($pos_results) . " fuel types</strong></p>";
        echo "<table><tr><th>ID</th><th>Product Name</th><th>Fuel Type ID</th><th>Fuel Type</th><th>Stock</th><th>Unit</th><th>Price/L</th></tr>";
        foreach ($pos_results as $pr) {
            echo "<tr><td>{$pr['id']}</td><td>{$pr['name']}</td><td>{$pr['fuel_type_id']}</td><td>{$pr['fuel_type_name']}</td><td>{$pr['total_stock_level']}</td><td>{$pr['unit']}</td><td>₱{$pr['price_per_liter']}</td></tr>";
        }
        echo "</table>";
    } else {
        echo "<p style='color:red;'><strong>✗ POS QUERY RETURNS 0 PRODUCTS - This is the problem!</strong></p>";
        
        // Diagnose why
        echo "<h3>Diagnosis:</h3>";
        
        // Check fuel_type_id
        $stmt = $pdo->prepare("SELECT COUNT(*) as cnt FROM products WHERE type_id = 1 AND fuel_type_id IS NULL");
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($result['cnt'] > 0) {
            echo "<p style='color:orange;'>⚠ {$result['cnt']} fuel products have NO fuel_type_id assigned!</p>";
        }
        
        // Check fuel_inventory
        $stmt = $pdo->prepare("SELECT COUNT(*) as cnt FROM fuel_inventory WHERE station_id = ?");
        $stmt->execute([$station_id]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($result['cnt'] == 0) {
            echo "<p style='color:orange;'>⚠ No fuel_inventory records for station $station_id!</p>";
        } else {
            echo "<p style='color:green;'>✓ Fuel inventory exists ({$result['cnt']} records)</p>";
        }
        
        // Check fuel_pricing
        $stmt = $pdo->prepare("SELECT COUNT(*) as cnt FROM fuel_pricing WHERE station_id = ? AND is_active = 1");
        $stmt->execute([$station_id]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($result['cnt'] == 0) {
            echo "<p style='color:orange;'>⚠ No active fuel_pricing records for station $station_id!</p>";
        } else {
            echo "<p style='color:green;'>✓ Fuel pricing exists ({$result['cnt']} records)</p>";
        }
    }
    
    echo "<hr>";
    echo "<p><strong>Instructions:</strong></p>";
    echo "<ol>";
    echo "<li>If fuel_type_id is NULL, run the migration script first</li>";
    echo "<li>If fuel_inventory is empty, you need to add fuel inventory records</li>";
    echo "<li>If fuel_pricing is empty, you need to add fuel pricing records</li>";
    echo "</ol>";
    
} catch (Exception $e) {
    echo "<h2 style='color:red;'>Error: " . htmlspecialchars($e->getMessage()) . "</h2>";
}
?>
