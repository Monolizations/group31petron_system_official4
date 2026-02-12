<?php
require_once 'backend/lib.php';
require_once 'db_connect.php';
require_login();

$user = $_SESSION['user'];
$role = role_key($user['role'] ?? 'staff');
if (!in_array($role, ['superadmin', 'admin', 'manager', 'staff'])) {
    header("Location: home.php");
    exit;
}
$myStationId = user_station_id();

$query = trim($_GET['q'] ?? '');
$results = [];

if (!empty($query)) {
    // Search Stations
    try {
        $stmt = $pdo->prepare("SELECT id, name, location FROM stations WHERE name LIKE ? OR location LIKE ?");
        $stmt->execute(["%$query%", "%$query%"]);
        $stations = $stmt->fetchAll(PDO::FETCH_ASSOC);
        foreach ($stations as $s) {
            $results[] = ['type' => 'Station', 'title' => $s['name'], 'subtitle' => $s['location'], 'link' => 'stations.php?edit=' . $s['id']];
        }
    } catch (Exception $e) {}

    // Search Users
    try {
        $stmt = $pdo->prepare("SELECT id, username, name, role FROM users WHERE username LIKE ? OR name LIKE ? OR email LIKE ?");
        $stmt->execute(["%$query%", "%$query%", "%$query%"]);
        $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
        foreach ($users as $u) {
            $results[] = ['type' => 'User', 'title' => $u['name'] ?: $u['username'], 'subtitle' => $u['role'], 'link' => 'users.php?edit=' . $u['id']];
        }
    } catch (Exception $e) {}

    // Search Transactions (from sales.json)
    $sales_data = read_json('data/sales.json', []);
    foreach ($sales_data as $s) {
        if ($role === 'admin' && ($s['station_id'] ?? '') != $myStationId) continue;
        if (stripos($s['customer'] ?? '', $query) !== false || stripos($s['cashier'] ?? '', $query) !== false) {
            $results[] = ['type' => 'Transaction', 'title' => 'Sale #' . ($s['id'] ?? 'N/A'), 'subtitle' => 'Customer: ' . ($s['customer'] ?? ''), 'link' => 'transactions.php?view=' . ($s['id'] ?? '')];
        }
    }

    // Search Customers
    try {
        $stmt = $pdo->prepare("SELECT id, name, phone FROM customers WHERE (name LIKE ? OR phone LIKE ?) AND station_id = ?");
        $stmt->execute(["%$query%", "%$query%", $myStationId]);
        $customers = $stmt->fetchAll(PDO::FETCH_ASSOC);
        foreach ($customers as $c) {
            $results[] = ['type' => 'Customer', 'title' => $c['name'], 'subtitle' => 'Phone: ' . $c['phone'], 'link' => 'customers.php?edit=' . $c['id']];
        }
    } catch (Exception $e) {}

    // Search Job Orders
    try {
        $stmt = $pdo->prepare("SELECT id, description, status FROM job_orders WHERE description LIKE ? AND station_id = ?");
        $stmt->execute(["%$query%", $myStationId]);
        $job_orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
        foreach ($job_orders as $jo) {
            $results[] = ['type' => 'Job Order', 'title' => 'Job #' . $jo['id'], 'subtitle' => 'Status: ' . $jo['status'], 'link' => 'joborder.php?view=' . $jo['id']];
        }
    } catch (Exception $e) {}

    // Search Inventory
    try {
        $stmt = $pdo->prepare("SELECT id, product_name, stock_level FROM inventory WHERE product_name LIKE ? AND station_id = ?");
        $stmt->execute(["%$query%", $myStationId]);
        $inventory = $stmt->fetchAll(PDO::FETCH_ASSOC);
        foreach ($inventory as $inv) {
            $results[] = ['type' => 'Inventory', 'title' => $inv['product_name'], 'subtitle' => 'Stock: ' . $inv['stock_level'], 'link' => 'inventory.php?view=' . $inv['id']];
        }
    } catch (Exception $e) {}

    // Search Reports (placeholder, assuming reports are in activity_logs or files)
    try {
        $stmt = $pdo->prepare("SELECT id, action, details FROM activity_logs WHERE action LIKE ? OR details LIKE ? ORDER BY created_at DESC LIMIT 10");
        $stmt->execute(["%$query%", "%$query%"]);
        $logs = $stmt->fetchAll(PDO::FETCH_ASSOC);
        foreach ($logs as $l) {
            $results[] = ['type' => 'Report', 'title' => $l['action'], 'subtitle' => substr($l['details'], 0, 50) . '...', 'link' => 'reports.php'];
        }
    } catch (Exception $e) {}
}

$page_id = 'search';

// Handle AJAX requests
if (isset($_GET['ajax'])) {
    header('Content-Type: application/json');
    echo json_encode($results);
    exit;
}
?>
<?php include 'partials/header.php'; ?>

<main class="main">
    <div class="container">
        <h1>Search Results</h1>
        <p>Query: <strong><?php echo htmlspecialchars($query); ?></strong></p>

        <?php if (empty($results)): ?>
            <p>No results found.</p>
        <?php else: ?>
            <div class="search-results">
                <?php
                $grouped = [];
                foreach ($results as $r) {
                    $grouped[$r['type']][] = $r;
                }
                foreach ($grouped as $type => $items): ?>
                    <div class="result-group">
                        <h3><?php echo htmlspecialchars($type); ?>s</h3>
                        <ul>
                            <?php foreach ($items as $r): ?>
                                <li>
                                    <a href="<?php echo htmlspecialchars($r['link']); ?>"><?php echo htmlspecialchars($r['title']); ?></a>
                                    <br><small><?php echo htmlspecialchars($r['subtitle']); ?></small>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</main>

<?php include 'partials/footer.php'; ?>
