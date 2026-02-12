<?php
$page_id = 'admin_set_prices';
require_once __DIR__ . '/../backend/lib.php';
require_once __DIR__ . '/db_connect.php';
require_login();

$me = current_user();
$role = role_key($me['role'] ?? '');
$station_id = user_station_id();

// Admin only
if (!in_array($role, ['admin', 'superadmin'])) {
    header("Location: dashboard.php");
    exit;
}

$msg = '';

// Handle price update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $product_id = (int)($_POST['product_id'] ?? 0);
    $cost = (float)($_POST['cost'] ?? 0);
    $price = (float)($_POST['price'] ?? 0);
    
    if ($product_id > 0 && $price >= 0) {
        if ($price < $cost) {
            $msg = "❌ Error: Selling price must be at least equal to cost.";
        } else {
            try {
                $stmt = $pdo->prepare("UPDATE products SET cost = ?, price = ? WHERE id = ?");
                $stmt->execute([$cost, $price, $product_id]);
                $msg = "✅ Price updated successfully!";
                
                log_activity($pdo, $me['id'], 'Update Product Price', "Updated price for product ID: $product_id to ₱$price");
            } catch (Exception $e) {
                $msg = "❌ Error: " . $e->getMessage();
            }
        }
    } else {
        $msg = "❌ Invalid data.";
    }
}

// Fetch products that need pricing (price = 0 or null)
$products_no_price = [];
try {
    if ($role === 'superadmin') {
        $stmt = $pdo->query("
            SELECT p.id, p.name, p.sku, p.cost, p.price, pc.name as category_name,
                   (SELECT SUM(stock_level) FROM station_inventory WHERE product_id = p.id) as total_stock
            FROM products p
            LEFT JOIN product_categories pc ON p.category_id = pc.id
            WHERE p.type_id IN (SELECT id FROM product_types WHERE name IN ('merch', 'service'))
            ORDER BY p.created_at DESC
        ");
        $products_no_price = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } else {
        $stmt = $pdo->prepare("
            SELECT p.id, p.name, p.sku, p.cost, p.price, pc.name as category_name, si.stock_level
            FROM station_inventory si
            JOIN products p ON p.id = si.product_id
            LEFT JOIN product_categories pc ON p.category_id = pc.id
            WHERE si.station_id = ? AND p.type_id IN (SELECT id FROM product_types WHERE name IN ('merch', 'service'))
            ORDER BY p.created_at DESC
        ");
        $stmt->execute([$station_id]);
        $products_no_price = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
} catch (Exception $e) {
    $products_no_price = [];
}

include __DIR__ . '/../partials/header.php';
?>

<style>
  .price-layout { display: grid; grid-template-columns: 2fr 1fr; gap: 16px; }
  .price-card { background: #fff; border: 1px solid #e2e8f0; border-radius: 12px; padding: 20px; }
  .price-head { font-size: 18px; font-weight: 700; margin-bottom: 16px; color: #0f172a; }
  .price-table { width: 100%; border-collapse: collapse; }
  .price-table th { background: #f8fafc; padding: 10px; text-align: left; font-size: 12px; font-weight: 600; color: #475569; border-bottom: 2px solid #e2e8f0; }
  .price-table td { padding: 12px 10px; border-bottom: 1px solid #f1f5f9; }
  .price-table tr:hover { background: #f8fafc; }
  .price-badge { display: inline-block; padding: 4px 10px; border-radius: 999px; font-size: 11px; font-weight: 600; }
  .price-badge.no-price { background: #fef3c7; color: #92400e; }
  .price-badge.has-price { background: #dcfce7; color: #166534; }
  .price-input { padding: 6px 10px; border: 1px solid #e2e8f0; border-radius: 6px; width: 90px; }
  .price-btn { padding: 6px 12px; background: #2563eb; color: #fff; border: 0; border-radius: 6px; cursor: pointer; font-size: 13px; }
  .price-btn:hover { background: #1d4ed8; }
  .guide-box { background: #f0f9ff; border: 1px solid #bae6fd; border-radius: 10px; padding: 14px; margin-bottom: 14px; }
  .guide-box h4 { margin: 0 0 8px; color: #075985; font-size: 14px; }
  .guide-box ul { margin: 0; padding-left: 18px; color: #0c4a6e; font-size: 13px; }
  @media (max-width: 1000px) { .price-layout { grid-template-columns: 1fr; } }
</style>

<div class="page-head">
  <div>
    <h1 class="h1">Set Product Prices (Admin)</h1>
    <div class="sub">Configure unit prices for parts and merchandise</div>
  </div>
</div>

<?php if($msg): ?>
  <div class="alert <?php echo strpos($msg, '✅') !== false ? 'alert-success' : 'alert-error'; ?>" style="margin-bottom:16px;">
    <?php echo htmlspecialchars($msg); ?>
  </div>
<?php endif; ?>

<div class="price-layout">
  <div class="price-card">
    <div class="price-head"><i class="fas fa-tags"></i> Product Pricing</div>
    
    <div class="table-wrap">
      <table class="price-table">
        <thead>
          <tr>
            <th>Product Name</th>
            <th>SKU</th>
            <th>Category</th>
            <th>Stock</th>
            <th>Cost (₱)</th>
            <th>Price (₱)</th>
            <th>Status</th>
            <th>Action</th>
          </tr>
        </thead>
        <tbody>
          <?php if(empty($products_no_price)): ?>
            <tr>
              <td colspan="8" style="text-align:center; padding:20px; color:#888;">No products found.</td>
            </tr>
          <?php else: ?>
            <?php foreach($products_no_price as $product): ?>
              <tr>
                <td><strong><?php echo htmlspecialchars($product['name']); ?></strong></td>
                <td><?php echo htmlspecialchars($product['sku'] ?? '-'); ?></td>
                <td><?php echo htmlspecialchars($product['category_name'] ?? '-'); ?></td>
                <td><?php echo number_format($product['stock_level'] ?? $product['total_stock'] ?? 0, 0); ?></td>
                <td>
                  <form method="post" style="display:inline;" id="form-<?php echo $product['id']; ?>">
                    <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">
                    <input type="number" name="cost" class="price-input" step="0.01" min="0" value="<?php echo number_format($product['cost'] ?? 0, 2, '.', ''); ?>" placeholder="Cost">
                </td>
                <td>
                    <input type="number" name="price" class="price-input" step="0.01" min="0" value="<?php echo number_format($product['price'] ?? 0, 2, '.', ''); ?>" placeholder="Price" required>
                </td>
                <td>
                  <?php if(($product['price'] ?? 0) > 0): ?>
                    <span class="price-badge has-price">✓ Priced</span>
                  <?php else: ?>
                    <span class="price-badge no-price">⚠ No Price</span>
                  <?php endif; ?>
                </td>
                <td>
                    <button type="submit" class="price-btn"><i class="fas fa-save"></i> Save</button>
                  </form>
                </td>
              </tr>
            <?php endforeach; ?>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>

  <div class="price-card" style="height:max-content;">
    <div class="price-head"><i class="fas fa-info-circle"></i> Pricing Guidelines</div>
    
    <div class="guide-box">
      <h4>Base price on</h4>
      <ul>
        <li>Supplier invoices</li>
        <li>Approved purchase orders</li>
        <li>Station pricing policies</li>
        <li>Market rates</li>
      </ul>
    </div>
    
    <div class="guide-box">
      <h4>Important rules</h4>
      <ul>
        <li>Selling price must be ≥ cost</li>
        <li>Update prices when costs change</li>
        <li>Only admins can set prices</li>
        <li>Changes are logged for audit</li>
      </ul>
    </div>
    
    <div class="guide-box">
      <h4>After setting prices</h4>
      <ul>
        <li>Items become available for job orders</li>
        <li>Staff can use parts in service entries</li>
        <li>Prices reflect in billing</li>
      </ul>
    </div>
  </div>
</div>

<?php include __DIR__ . '/../partials/footer.php'; ?>
