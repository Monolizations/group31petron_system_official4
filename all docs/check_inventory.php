<?php
// Diagnostic script to check inventory tables
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/public/db_connect.php';

$station_id = 226; // Your station ID

echo "<h2>🔍 Database Diagnostic for Station $station_id</h2>";
echo "<hr>";

// Check 1: Does inventory table exist?
echo "<h3>1. Check if 'inventory' table exists</h3>";
try {
    $stmt = $pdo->query("SHOW TABLES LIKE 'inventory'");
    $result = $stmt->fetchAll();
    if (empty($result)) {
        echo "<p style='color:red;'>❌ Table 'inventory' does NOT exist</p>";
    } else {
        echo "<p style='color:green;'>✅ Table 'inventory' EXISTS</p>";
    }
} catch (Exception $e) {
    echo "<p style='color:red;'>❌ Error checking table: " . $e->getMessage() . "</p>";
}

echo "<hr>";

// Check 2: Does station_inventory table exist?
echo "<h3>2. Check if 'station_inventory' table exists</h3>";
try {
    $stmt = $pdo->query("SHOW TABLES LIKE 'station_inventory'");
    $result = $stmt->fetchAll();
    if (empty($result)) {
        echo "<p style='color:red;'>❌ Table 'station_inventory' does NOT exist</p>";
    } else {
        echo "<p style='color:green;'>✅ Table 'station_inventory' EXISTS</p>";
    }
} catch (Exception $e) {
    echo "<p style='color:red;'>❌ Error checking table: " . $e->getMessage() . "</p>";
}

echo "<hr>";

// Check 3: Inventory records for station 226 in inventory table
echo "<h3>3. Check records in 'inventory' table for station $station_id</h3>";
try {
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM inventory WHERE station_id = ?");
    $stmt->execute([$station_id]);
    $count = $stmt->fetchColumn();
    echo "<p>Records found: <strong>$count</strong></p>";

    if ($count > 0) {
        echo "<p>Sample records:</p>";
        $stmt2 = $pdo->prepare("
            SELECT i.id, i.product_id, i.stock_level, i.status, p.name, p.type_id
            FROM inventory i
            LEFT JOIN products p ON p.id = i.product_id
            WHERE i.station_id = ?
            LIMIT 5
        ");
        $stmt2->execute([$station_id]);
        $records = $stmt2->fetchAll(PDO::FETCH_ASSOC);
        echo "<table border='1' cellpadding='5' style='border-collapse:collapse;'>";
        echo "<tr style='background:#f0f0f0;'><th>ID</th><th>Product ID</th><th>Product Name</th><th>Type ID</th><th>Stock</th><th>Status</th></tr>";
        foreach ($records as $r) {
            echo "<tr>";
            echo "<td>{$r['id']}</td>";
            echo "<td>{$r['product_id']}</td>";
            echo "<td>{$r['name']}</td>";
            echo "<td>{$r['type_id']}</td>";
            echo "<td>{$r['stock_level']}</td>";
            echo "<td>{$r['status']}</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p style='color:orange;'>⚠️ No inventory records found for station $station_id</p>";
    }
} catch (Exception $e) {
    echo "<p style='color:red;'>❌ Error querying inventory: " . $e->getMessage() . "</p>";
}

echo "<hr>";

// Check 4: Inventory records for station 226 in station_inventory table
echo "<h3>4. Check records in 'station_inventory' table for station $station_id</h3>";
try {
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM station_inventory WHERE station_id = ?");
    $stmt->execute([$station_id]);
    $count = $stmt->fetchColumn();
    echo "<p>Records found: <strong>$count</strong></p>";

    if ($count > 0) {
        echo "<p>Sample records:</p>";
        $stmt2 = $pdo->prepare("
            SELECT i.id, i.product_id, i.stock_level, i.status, p.name, p.type_id
            FROM station_inventory i
            LEFT JOIN products p ON p.id = i.product_id
            WHERE i.station_id = ?
            LIMIT 5
        ");
        $stmt2->execute([$station_id]);
        $records = $stmt2->fetchAll(PDO::FETCH_ASSOC);
        echo "<table border='1' cellpadding='5' style='border-collapse:collapse;'>";
        echo "<tr style='background:#f0f0f0;'><th>ID</th><th>Product ID</th><th>Product Name</th><th>Type ID</th><th>Stock</th><th>Status</th></tr>";
        foreach ($records as $r) {
            echo "<tr>";
            echo "<td>{$r['id']}</td>";
            echo "<td>{$r['product_id']}</td>";
            echo "<td>{$r['name']}</td>";
            echo "<td>{$r['type_id']}</td>";
            echo "<td>{$r['stock_level']}</td>";
            echo "<td>{$r['status']}</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p style='color:orange;'>⚠️ No station_inventory records found for station $station_id</p>";
    }
} catch (Exception $e) {
    echo "<p style='color:red;'>❌ Error querying station_inventory: " . $e->getMessage() . "</p>";
}

echo "<hr>";

// Check 5: Merchandise products (type_id = 2) in products table
echo "<h3>5. Check merchandise products (type_id = 2) in products table</h3>";
try {
    $stmt = $pdo->query("SELECT id, name, type_id, price FROM products WHERE type_id = 2 LIMIT 10");
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "<p>Merchandise products found: <strong>" . count($products) . "</strong></p>";

    if (!empty($products)) {
        echo "<p>Sample products:</p>";
        echo "<table border='1' cellpadding='5' style='border-collapse:collapse;'>";
        echo "<tr style='background:#f0f0f0;'><th>ID</th><th>Name</th><th>Type ID</th><th>Price</th></tr>";
        foreach ($products as $p) {
            echo "<tr>";
            echo "<td>{$p['id']}</td>";
            echo "<td>{$p['name']}</td>";
            echo "<td>{$p['type_id']}</td>";
            echo "<td>₱" . number_format($p['price'], 2) . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
} catch (Exception $e) {
    echo "<p style='color:red;'>❌ Error querying products: " . $e->getMessage() . "</p>";
}

echo "<hr>";

// Check 6: Test the actual query we're using in joborder.php
echo "<h3>6. Test the actual query from joborder.php</h3>";
try {
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
    echo "<p>Query result: <strong>$count</strong> products found</p>";

    if ($count > 0) {
        echo "<p style='color:green;'>✅ Query SUCCESS - Products loaded!</p>";
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
        echo "<p style='color:red;'>❌ Query returned 0 results</p>";
    }
} catch (Exception $e) {
    echo "<p style='color:red;'>❌ Query FAILED with error:</p>";
    echo "<pre style='background:#ffe0e0; padding:10px; border:1px solid red;'>" . $e->getMessage() . "</pre>";
}

echo "<hr>";
echo "<p><strong>Diagnostic Complete!</strong> Please review the results above.</p>";
