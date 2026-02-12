
<?php
require_once __DIR__ . '/../backend/lib.php';
require_once __DIR__ . '/../public/db_connect.php';
require_login();

$user = current_user();
$station_id = user_station_id();
$item_id = (int)($_GET['item_id'] ?? 0);

// Get product info if item_id is provided
$product_info = null;
if ($item_id > 0) {
    $stmt = $pdo->prepare("
        SELECT p.name, p.sku, pc.name as category_name
        FROM products p
        LEFT JOIN product_categories pc ON p.category_id = pc.id
        WHERE p.id = ?
    ");
    $stmt->execute([$item_id]);
    $product_info = $stmt->fetch(PDO::FETCH_ASSOC);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $qty = (int)$_POST['quantity'];
    $type = $_POST['type'] ?? 'merch';
    $product_name = $product_info['name'] ?? '';
    
    $stmt = $pdo->prepare("
        INSERT INTO stock_requests
        (station_id, requested_by, type, product_name, qty, notes, status, created_at)
        VALUES (?, ?, ?, ?, ?, ?, 'pending', NOW())
    ");
    $stmt->execute([$station_id, $user['id'], $type, $product_name, $qty, $_POST['notes'] ?? null]);
    header("Location: inventory.php?view=stock&requested=1");
    exit;
}
?>
<h3>Request Stock</h3>
<form method="post">
    <?php if ($product_info): ?>
        <div class="card" style="padding:15px; margin-bottom:15px;">
            <h4><?php echo htmlspecialchars($product_info['name']); ?></h4>
            <p><strong>SKU:</strong> <?php echo htmlspecialchars($product_info['sku'] ?? ''); ?></p>
            <p><strong>Category:</strong> <?php echo htmlspecialchars($product_info['category_name'] ?? ''); ?></p>
        </div>
    <?php endif; ?>
    
    <div class="form-group">
        <label>Request Type</label>
        <select name="type" class="form-control" required>
            <option value="fuel">Fuel</option>
            <option value="merch" selected>Merchandise</option>
        </select>
    </div>
    
    <div class="form-group">
        <label>Quantity</label>
        <input type="number" name="quantity" class="form-control" required>
    </div>
    
    <div class="form-group">
        <label>Notes</label>
        <textarea name="notes" class="form-control" rows="3" placeholder="Optional notes..."></textarea>
    </div>
    
    <button class="btn btn-primary mt-2">Submit Request</button>
</form>
