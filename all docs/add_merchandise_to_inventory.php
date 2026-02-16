<?php
/**
 * Add All Merchandise Products to Station Inventory
 *
 * This script adds all merchandise products (type_id = 2) to station_inventory
 * with a default stock level of 5 for each product.
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/public/db_connect.php';

$station_id = 226; // Your station ID
$default_stock = 5; // Default stock level

echo "<h2>📦 Add All Merchandise Products to Station Inventory</h2>";
echo "<p>Station ID: <strong>$station_id</strong></p>";
echo "<p>Default Stock Level: <strong>$default_stock</strong></p>";
echo "<hr>";

echo "<h3>Step 1: Find All Merchandise Products</h3>";

try {
    $stmt = $pdo->prepare("
        SELECT p.id, p.name, p.sku, p.price
        FROM products p
        WHERE p.type_id = 2
        ORDER BY p.name
    ");
    $stmt->execute();
    $merch_products = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $total_products = count($merch_products);
    echo "<p><strong>$total_products</strong> merchandise products found.</p>";

    echo "<table border='1' cellpadding='5' style='border-collapse:collapse;'>";
    echo "<tr style='background:#f0f0f0;'><th>ID</th><th>Name</th><th>SKU</th><th>Price</th></tr>";

    foreach ($merch_products as $p) {
        echo "<tr>";
        echo "<td>{$p['id']}</td>";
        echo "<td>{$p['name']}</td>";
        echo "<td>" . htmlspecialchars($p['sku']) . "</td>";
        echo "<td>₱" . number_format($p['price'], 2) . "</td>";
        echo "</tr>";
    }
    echo "</table>";

} catch (Exception $e) {
    echo "<p style='color:red;'>❌ Error: " . $e->getMessage() . "</p>";
    exit(1);
}

echo "<hr>";

echo "<h3>Step 2: Add Products to Station Inventory</h3>";

try {
    $pdo->beginTransaction();

    $added_count = 0;
    $updated_count = 0;
    $skipped_count = 0;

    echo "<p>Adding products to station_inventory...</p>";

    foreach ($merch_products as $p) {
        // Try to insert
        $stmt = $pdo->prepare("
            INSERT INTO station_inventory (station_id, product_id, stock_level, reorder_level, capacity, unit, status, last_updated)
            VALUES (?, ?, ?, 0, 10000.00, 'pieces', 'active', NOW())
        ");
        $stmt->execute([$station_id, $p['id'], $default_stock]);

        if ($stmt->rowCount() > 0) {
            $added_count++;
            echo "<p style='color:green;'>✅ Added: <strong>{$p['name']}</strong> (Stock: $default_stock)</p>";
        } else {
            $updated_count++;
            echo "<p style='color:orange;'>⚠️ Already exists: <strong>{$p['name']}</strong></p>";
        }
    }

    $pdo->commit();

    echo "<hr>";

    echo "<h3>Step 3: Results Summary</h3>";
    echo "<table border='1' cellpadding='10' style='border-collapse:collapse;'>";
    echo "<tr style='background:#e8f5e9;'><th>Action</th><th>Count</th></tr>";
    echo "<tr><td>✅ Products Added</td><td><strong>$added_count</strong></td></tr>";
    echo "<tr><td>⚠️ Already Exists</td><td><strong>$updated_count</strong></td></tr>";
    echo "<tr><td><strong>Total Products</strong></td><td><strong>$total_products</strong></td></tr>";
    echo "</table>";

} catch (Exception $e) {
    $pdo->rollBack();
    echo "<p style='color:red; font-size:18px;'>❌ FAILED!</p>";
    echo "<p>Error: " . $e->getMessage() . "</p>";
    exit(1);
}

echo "<hr>";

echo "<h3>Step 4: Verification</h3>";

try {
    // Count all inventory records for this station
    $stmt = $pdo->prepare("
        SELECT COUNT(*) as total
        FROM station_inventory
        WHERE station_id = ?
    ");
    $stmt->execute([$station_id]);
    $total_inventory = $stmt->fetchColumn();

    echo "<p><strong>Total station_inventory records: $total_inventory</strong></p>";

    // Show all inventory records
    $stmt2 = $pdo->prepare("
        SELECT si.id, p.name as product_name, si.stock_level, si.status
        FROM station_inventory si
        INNER JOIN products p ON p.id = si.product_id
        WHERE si.station_id = ?
        ORDER BY p.name
    ");
    $stmt2->execute([$station_id]);
    $inventory_records = $stmt2->fetchAll(PDO::FETCH_ASSOC);

    echo "<p>All inventory records for station $station_id:</p>";
    echo "<table border='1' cellpadding='5' style='border-collapse:collapse;'>";
    echo "<tr style='background:#d1ecf1;'><th>ID</th><th>Product Name</th><th>Stock Level</th><th>Status</th></tr>";

    foreach ($inventory_records as $r) {
        echo "<tr>";
        echo "<td>{$r['id']}</td>";
        echo "<td>{$r['product_name']}</td>";
        echo "<td><strong>{$r['stock_level']}</strong></td>";
        echo "<td>{$r['status']}</td>";
        echo "</tr>";
    }
    echo "</table>";

} catch (Exception $e) {
    echo "<p style='color:red;'>❌ Error: " . $e->getMessage() . "</p>";
}

echo "<hr>";

echo "<h3>Step 5: Test Job Order Query</h3>";

try {
    // Test the query from joborder.php
    $stmt = $pdo->prepare("
        SELECT p.id, p.name, p.barcode, si.stock_level, p.price
        FROM products p
        INNER JOIN station_inventory si ON p.id = si.product_id AND si.station_id = ?
        WHERE si.status = 'active'
          AND si.stock_level > 0
          AND p.type_id = 2
        ORDER BY p.name
        LIMIT 10
    ");
    $stmt->execute([$station_id]);
    $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $count = count($result);

    echo "<p>Testing joborder.php query for station $station_id...</p>";

    if ($count > 0) {
        echo "<p style='color:green; font-size:18px;'>✅ SUCCESS! Found $count merchandise products!</p>";
        echo "<table border='1' cellpadding='5' style='border-collapse:collapse;'>";
        echo "<tr style='background:#e0ffe0;'><th>ID</th><th>Name</th><th>Stock</th><th>Price</th></tr>";
        foreach ($result as $r) {
            echo "<tr>";
            echo "<td>{$r['id']}</td>";
            echo "<td>{$r['name']}</td>";
            echo "<td><strong>{$r['stock_level']}</strong></td>";
            echo "<td>₱" . number_format($r['price'], 2) . "</td>";
            echo "</tr>";
        }
        echo "</table>";
        echo "<p style='color:green; font-size:20px;'><strong>🎉 Job Order products will load correctly now!</strong></p>";
    } else {
        echo "<p style='color:orange;'>⚠️ Query returned 0 results</p>";
    }

} catch (Exception $e) {
    echo "<p style='color:red;'>❌ Query FAILED: " . $e->getMessage() . "</p>";
}

echo "<hr>";
echo "<h3>🎉 Migration Complete!</h3>";
echo "<p><strong>Next Steps:</strong></p>";
echo "<ol>";
echo "<li>Go to <a href='public/joborder.php?tab=create'>Job Order Creation page</a></li>";
echo "<li>Go to <a href='public/joborder.php?tab=ongoing'>Ongoing Jobs page</a> and click 'Complete' on any job</li>";
echo "<li>Click 'Add Part' button</li>";
echo "<li>Verify all 12 merchandise products appear in dropdown</li>";
echo "<li>Verify stock levels show as 'Stock: 5' for each product</li>";
echo "<li>You can update stock levels via Admin panel as needed</li>";
echo "</ol>";
