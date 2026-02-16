<?php
/**
 * Migration Script: Fix Merchandise Product Type IDs
 *
 * This script updates all merchandise products to have type_id = 2
 * by excluding fuel products (category_id = 1).
 *
 * Categories included:
 * - Oils/Lubes/Grease (category_id = 4)
 * - Car Accessories (category_id = 5)
 * - Filters (category_id = 6)
 * - VIC Filters (category_id = 9)
 * - Merchandise (category_id = 2)
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/public/db_connect.php';

echo "<h2>🔧 Fix Merchandise Product Type IDs</h2>";
echo "<p>This script will update all merchandise products to have type_id = 2.</p>";
echo "<hr>";

echo "<h3>Step 1: Current Product Types</h3>";

try {
    // Count products by type_id
    $stmt = $pdo->query("
        SELECT type_id, COUNT(*) as count
        FROM products
        GROUP BY type_id
        ORDER BY type_id
    ");
    $counts = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo "<p>Current type distribution:</p>";
    echo "<table border='1' cellpadding='5' style='border-collapse:collapse;'>";
    echo "<tr style='background:#f0f0f0;'><th>Type ID</th><th>Type Name</th><th>Count</th></tr>";

    $type_names = [1 => 'Fuel', 2 => 'Merchandise', 3 => 'Service'];
    foreach ($counts as $c) {
        echo "<tr>";
        echo "<td>{$c['type_id']}</td>";
        echo "<td>" . ($type_names[$c['type_id']] ?? 'Unknown') . "</td>";
        echo "<td><strong>{$c['count']}</strong></td>";
        echo "</tr>";
    }
    echo "</table>";

} catch (Exception $e) {
    echo "<p style='color:red;'>❌ Error: " . $e->getMessage() . "</p>";
    exit(1);
}

echo "<hr>";

echo "<h3>Step 2: Identify Merchandise Products</h3>";

try {
    // Get products that should be merchandise (based on category)
    $stmt = $pdo->query("
        SELECT p.id, p.name, p.type_id as current_type, pc.name as category, pc.id as category_id
        FROM products p
        LEFT JOIN product_categories pc ON p.category_id = pc.id
        WHERE p.category_id IN (2, 4, 5, 6, 9)
        ORDER BY p.category_id, p.name
        LIMIT 20
    ");
    $merch_products = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo "<p>Products to update (max 20 shown):</p>";
    echo "<table border='1' cellpadding='5' style='border-collapse:collapse;'>";
    echo "<tr style='background:#f0f0f0;'><th>ID</th><th>Name</th><th>Current Type</th><th>Category</th></tr>";

    $count_to_update = 0;
    foreach ($merch_products as $p) {
        echo "<tr>";
        echo "<td>{$p['id']}</td>";
        echo "<td>{$p['name']}</td>";
        echo "<td>" . ($p['current_type'] == 2 ? '✅ Already correct' : "❌ Current: {$p['current_type']}") . "</td>";
        echo "<td>{$p['category']} (ID: {$p['category_id']})</td>";
        echo "</tr>";

        if ($p['current_type'] != 2) {
            $count_to_update++;
        }
    }
    echo "</table>";

    echo "<p><strong>Total products to update: $count_to_update</strong></p>";

} catch (Exception $e) {
    echo "<p style='color:red;'>❌ Error: " . $e->getMessage() . "</p>";
    exit(1);
}

echo "<hr>";

echo "<h3>Step 3: Perform Migration</h3>";

try {
    $pdo->beginTransaction();

    // Update all merchandise products to type_id = 2
    // Categories included: Merchandise(2), Oils/Lubes(4), Car Accessories(5), Filters(6), VIC Filters(9)
    // Excluded: Fuel Products(1), Services(3), Drinks/Food(7), Snacks(8)
    $stmt = $pdo->prepare("
        UPDATE products
        SET type_id = 2
        WHERE category_id IN (2, 4, 5, 6, 9)
          AND type_id != 2
    ");
    $stmt->execute();

    $updated_count = $stmt->rowCount();
    $pdo->commit();

    echo "<p style='color:green; font-size:18px;'>✅ Success!</p>";
    echo "<p><strong>$updated_count</strong> products updated to type_id = 2 (Merchandise)</p>";

} catch (Exception $e) {
    $pdo->rollBack();
    echo "<p style='color:red; font-size:18px;'>❌ Migration Failed!</p>";
    echo "<p>Error: " . $e->getMessage() . "</p>";
    exit(1);
}

echo "<hr>";

echo "<h3>Step 4: Verification</h3>";

try {
    // Verify the update
    $stmt = $pdo->query("
        SELECT type_id, COUNT(*) as count
        FROM products
        GROUP BY type_id
        ORDER BY type_id
    ");
    $counts = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo "<p>New type distribution:</p>";
    echo "<table border='1' cellpadding='5' style='border-collapse:collapse;'>";
    echo "<tr style='background:#e8f5e9;'><th>Type ID</th><th>Type Name</th><th>Count</th></tr>";

    foreach ($counts as $c) {
        echo "<tr>";
        echo "<td><strong>{$c['type_id']}</strong></td>";
        echo "<td>" . ($type_names[$c['type_id']] ?? 'Unknown') . "</td>";
        echo "<td><strong>{$c['count']}</strong></td>";
        echo "</tr>";
    }
    echo "</table>";

    // Count merchandise products
    $stmt2 = $pdo->query("SELECT COUNT(*) as count FROM products WHERE type_id = 2");
    $merch_count = $stmt2->fetchColumn();

    echo "<p><strong>✅ Total Merchandise Products (type_id = 2): $merch_count</strong></p>";

} catch (Exception $e) {
    echo "<p style='color:red;'>❌ Error: " . $e->getMessage() . "</p>";
}

echo "<hr>";
echo "<h3>Step 5: Test Job Order Query</h3>";

try {
    // Test the actual query from joborder.php
    $station_id = 226; // Your station ID

    $stmt = $pdo->prepare("
        SELECT p.id, p.name, p.barcode, i.stock_level, p.price
        FROM products p
        INNER JOIN inventory i ON i.product_id = p.id
        WHERE i.station_id = ?
          AND i.status = 'active'
          AND i.stock_level > 0
          AND p.type_id = 2
        ORDER BY p.name
        LIMIT 10
    ");
    $stmt->execute([$station_id]);
    $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $count = count($result);

    echo "<p>Testing joborder.php query for station $station_id...</p>";

    if ($count > 0) {
        echo "<p style='color:green;'>✅ Query SUCCESS - Found $count merchandise products!</p>";
        echo "<table border='1' cellpadding='5' style='border-collapse:collapse;'>";
        echo "<tr style='background:#e0ffe0;'><th>ID</th><th>Name</th><th>Stock</th><th>Price</th></tr>";
        foreach ($result as $r) {
            echo "<tr>";
            echo "<td>{$r['id']}</td>";
            echo "<td>{$r['name']}</td>";
            echo "<td>{$r['stock_level']}</td>";
            echo "<td>₱" . number_format($r['price'], 2) . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p style='color:orange;'>⚠️ Query returned 0 results</p>";
        echo "<p><strong>Note:</strong> This is expected if station_inventory table is empty or has no active merchandise products.</p>";
    }

} catch (Exception $e) {
    echo "<p style='color:red;'>❌ Query FAILED: " . $e->getMessage() . "</p>";
}

echo "<hr>";
echo "<h3>🎉 Migration Complete!</h3>";
echo "<p><strong>Next Steps:</strong></p>";
echo "<ol>";
echo "<li>Add merchandise products to station_inventory table for your station</li>";
echo "<li>Set correct stock levels for each product</li>";
echo "<li>Go to <a href='joborder.php?tab=ongoing'>Job Order page</a> and test completing a job</li>";
echo "<li>Verify products load correctly in the dropdown</li>";
echo "</ol>";
