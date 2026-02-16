<?php
require_once __DIR__ . '/../backend/lib.php';
require_once __DIR__ . '/db_connect.php';
require_login();

$me = current_user();
$station_id = user_station_id();

if (!$me) {
    echo "Not logged in. Please login first.";
    exit;
}

?>
<!DOCTYPE html>
<html>
<head>
    <title>POS Transaction Debug</title>
    <style>
        body { font-family: monospace; padding: 20px; background: #f5f5f5; }
        .container { max-width: 1000px; margin: 0 auto; background: white; padding: 20px; border-radius: 8px; }
        table { width: 100%; border-collapse: collapse; margin: 20px 0; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background: #f0f0f0; }
        .success { color: green; }
        .error { color: red; }
    </style>
</head>
<body>
    <div class="container">
        <h1>POS Transaction Debug</h1>
        
        <p><strong>Your Station ID:</strong> <?php echo $station_id; ?></p>
        <p><strong>Your User ID:</strong> <?php echo $me['id'] ?? 'N/A'; ?></p>
        <p><strong>Your Name:</strong> <?php echo htmlspecialchars($me['name'] ?? 'N/A'); ?></p>
        
        <h2>1. Recent Sales (All Stations)</h2>
        <?php
        try {
            $stmt = $pdo->prepare("SELECT s.id, s.station_id, s.user_id, u.name as user_name, s.sale_date, s.sale_time, s.payment_method, s.total, s.status, s.created_at FROM sales s LEFT JOIN users u ON s.user_id = u.id ORDER BY s.created_at DESC LIMIT 10");
            $stmt->execute();
            $sales = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            if (count($sales) > 0) {
                echo "<table>";
                echo "<tr><th>Sale ID</th><th>Station</th><th>User</th><th>Date</th><th>Total</th><th>Status</th><th>Created At</th></tr>";
                foreach ($sales as $s) {
                    $stationMatch = $s['station_id'] == $station_id ? '<span class="success">✓</span>' : '<span class="error">✗</span>';
                    $userMatch = $s['user_id'] == ($me['id'] ?? 0) ? '<span class="success">✓</span>' : '<span class="error">✗</span>';
                    echo "<tr>";
                    echo "<td>{$s['id']}</td>";
                    echo "<td>{$s['station_id']} {$stationMatch}</td>";
                    echo "<td>{$s['user_name']} {$userMatch}</td>";
                    echo "<td>{$s['sale_date']} {$s['sale_time']}</td>";
                    echo "<td>₱{$s['total']}</td>";
                    echo "<td>{$s['status']}</td>";
                    echo "<td>{$s['created_at']}</td>";
                    echo "</tr>";
                }
                echo "</table>";
            } else {
                echo "<p class='error'>No sales found in database!</p>";
            }
        } catch (Exception $e) {
            echo "<p class='error'>Error: " . htmlspecialchars($e->getMessage()) . "</p>";
        }
        ?>
        
        <h2>2. Sales for Your Station (<?php echo $station_id; ?>)</h2>
        <?php
        try {
            $stmt = $pdo->prepare("SELECT s.id, s.user_id, u.name as user_name, s.sale_date, s.sale_time, s.payment_method, s.total, s.status, s.created_at FROM sales s LEFT JOIN users u ON s.user_id = u.id WHERE s.station_id = ? ORDER BY s.created_at DESC LIMIT 10");
            $stmt->execute([$station_id]);
            $sales = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            if (count($sales) > 0) {
                echo "<table>";
                echo "<tr><th>Sale ID</th><th>User</th><th>Date</th><th>Total</th><th>Status</th><th>Created At</th></tr>";
                foreach ($sales as $s) {
                    echo "<tr>";
                    echo "<td>{$s['id']}</td>";
                    echo "<td>{$s['user_name']}</td>";
                    echo "<td>{$s['sale_date']} {$s['sale_time']}</td>";
                    echo "<td>₱{$s['total']}</td>";
                    echo "<td>{$s['status']}</td>";
                    echo "<td>{$s['created_at']}</td>";
                    echo "</tr>";
                }
                echo "</table>";
            } else {
                echo "<p class='error'>No sales found for your station!</p>";
            }
        } catch (Exception $e) {
            echo "<p class='error'>Error: " . htmlspecialchars($e->getMessage()) . "</p>";
        }
        ?>
        
        <h2>3. Sale Items (Items in Those Sales)</h2>
        <?php
        try {
            $stmt = $pdo->prepare("SELECT si.sale_id, si.product_id, si.name, si.quantity, si.unit_price, si.total_amount, p.name as product_name FROM sale_items si LEFT JOIN products p ON si.product_id = p.id WHERE si.sale_id IN (SELECT id FROM sales WHERE station_id = ?) ORDER BY si.sale_id DESC LIMIT 20");
            $stmt->execute([$station_id]);
            $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            if (count($items) > 0) {
                echo "<table>";
                echo "<tr><th>Sale ID</th><th>Product</th><th>Name Stored</th><th>Qty</th><th>Unit Price</th><th>Total</th></tr>";
                foreach ($items as $item) {
                    echo "<tr>";
                    echo "<td>{$item['sale_id']}</td>";
                    echo "<td>ID: {$item['product_id']}</td>";
                    echo "<td>{$item['name']}</td>";
                    echo "<td>{$item['quantity']}</td>";
                    echo "<td>₱{$item['unit_price']}</td>";
                    echo "<td>₱{$item['total_amount']}</td>";
                    echo "</tr>";
                }
                echo "</table>";
            } else {
                echo "<p class='error'>No sale items found for your station's sales!</p>";
            }
        } catch (Exception $e) {
            echo "<p class='error'>Error: " . htmlspecialchars($e->getMessage()) . "</p>";
        }
        ?>
    </div>
</body>
</html>
