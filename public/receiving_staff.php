<?php
if (session_status() === PHP_SESSION_NONE) session_start();
$page_id = 'receiving_staff';
require_once __DIR__ . '/../backend/lib.php';
require_once __DIR__ . '/../public/db_connect.php';
require_login();
$me = current_user();
$station_id = user_station_id();
$role = function_exists('role_key') ? role_key($me['role'] ?? '') : strtolower(trim($me['role'] ?? ''));
if (!in_array($role, ['staff'])) { header("Location: dashboard.php"); exit; }

include __DIR__ . '/../partials/header.php';
?>
<div class="page-head">
  <div>
    <h1 class="h1">Receiving (Staff)</h1>
    <div class="sub">This page is part of the Receiving (Staff) module.</div>
  </div>
</div>
<style>
  .receive-layout { display: grid; grid-template-columns: 1.4fr 0.6fr; gap: 16px; }
  .receive-card { background: #fff; border: 1px solid #eef1f5; border-radius: 14px; box-shadow: 0 10px 24px rgba(16,24,40,0.06); }
  .receive-head { display: flex; align-items: center; justify-content: space-between; padding: 16px 20px; border-bottom: 1px solid #eef1f5; }
  .receive-title { display: flex; align-items: center; gap: 10px; font-weight: 700; color: #0f172a; }
  .receive-sub { color: #64748b; font-size: 13px; }
  .receive-tabs { display: flex; gap: 8px; flex-wrap: wrap; }
  .receive-tabs .btn { border-radius: 999px; }
  .receive-body { padding: 18px 20px; }
  .form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 14px; }
  .form-field label { font-size: 12px; font-weight: 600; color: #475569; display: block; margin-bottom: 6px; }
  .form-field input, .form-field textarea, .form-field select { width: 100%; padding: 10px 12px; border: 1px solid #e2e8f0; border-radius: 10px; outline: none; }
  .form-field input:focus, .form-field textarea:focus, .form-field select:focus { border-color: #2563eb; box-shadow: 0 0 0 3px rgba(37,99,235,0.12); }
  .form-actions { display: flex; align-items: center; gap: 10px; margin-top: 14px; flex-wrap: wrap; }
  .receive-body button.btn { cursor: pointer; display: inline-flex; align-items: center; gap: 6px; }
  .receive-body .btn-primary { background: #2563eb; color: #fff; border: 0; }
  .receive-body .btn-primary:hover { background: #1d4ed8; }
  .tips { padding: 14px 16px; background: #f8fafc; border: 1px dashed #e2e8f0; border-radius: 12px; }
  .tips h4 { margin: 0 0 8px; font-size: 14px; color: #0f172a; }
  .tips ul { margin: 0; padding-left: 18px; color: #64748b; font-size: 13px; }
  .receive-badge { background: #e0f2fe; color: #075985; font-size: 11px; padding: 4px 8px; border-radius: 999px; }
  .table-wrap { border: 1px solid #eef1f5; border-radius: 12px; overflow: hidden; }
  @media (max-width: 1000px) { .receive-layout { grid-template-columns: 1fr; } }
  @media (max-width: 720px) { .form-grid { grid-template-columns: 1fr; } }
</style>
<?php
$view = $_GET['view'] ?? 'encode';

// Handle form submission for encoding received items
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $view === 'encode') {
    $item_name = $_POST['item_name'] ?? '';
    $quantity = (float)($_POST['quantity'] ?? 0);
    $supplier = $_POST['supplier'] ?? '';
    $delivery_date = $_POST['delivery_date'] ?? date('Y-m-d');
    $notes = $_POST['notes'] ?? '';
    
    if ($item_name && $quantity > 0) {
        try {
            $pdo->beginTransaction();
            
            // Insert into received_items table
            $stmt = $pdo->prepare("INSERT INTO received_items (station_id, product_id, item_name, quantity, supplier, delivery_date, received_by, notes, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())");
            
            // First, get or create the product
            $stmt_product = $pdo->prepare("SELECT id FROM products WHERE name = ? LIMIT 1");
            $stmt_product->execute([$item_name]);
            $product_id = $stmt_product->fetchColumn();
            
            if (!$product_id) {
                // Create new product if it doesn't exist
                $stmt_create = $pdo->prepare("INSERT INTO products (name, type_id, category_id) VALUES (?, (SELECT id FROM product_types WHERE name = 'merch'), (SELECT id FROM product_categories WHERE name = 'Convenience'))");
                $stmt_create->execute([$item_name]);
                $product_id = $pdo->lastInsertId();
            }
            
            $stmt->execute([$station_id, $product_id, $item_name, $quantity, $supplier, $delivery_date, $me['id'], $notes]);
            
            // Update inventory
            $stmt = $pdo->prepare("UPDATE station_inventory SET stock_level = stock_level + ? WHERE station_id = ? AND product_id = ?");
            $result = $stmt->execute([$quantity, $station_id, $product_id]);
            
            if ($stmt->rowCount() === 0) {
                // If inventory record doesn't exist, create it
                $stmt_insert = $pdo->prepare("INSERT INTO station_inventory (station_id, product_id, stock_level, unit) VALUES (?, ?, ?, 'pieces')");
                $stmt_insert->execute([$station_id, $product_id, $quantity]);
            }
            
            // Log the inventory change
            $stmt_log = $pdo->prepare("INSERT INTO inventory_logs (station_id, product_id, user_id, action, quantity_before, quantity_after, quantity_change, reference_type, notes, created_at) VALUES (?, ?, ?, 'stock_in', COALESCE((SELECT stock_level FROM station_inventory WHERE station_id = ? AND product_id = ?), 0), COALESCE((SELECT stock_level FROM station_inventory WHERE station_id = ? AND product_id = ?), 0) + ?, ?, 'receiving', ?, NOW())");
            $stmt_log->execute([$station_id, $product_id, $me['id'], $station_id, $product_id, $station_id, $product_id, $quantity, $quantity, $notes]);
            
            $pdo->commit();
            $msg = "✅ Items received and inventory updated successfully!";
        } catch (Exception $e) {
            $pdo->rollBack();
            $msg = "❌ Error: " . $e->getMessage();
        }
    } else {
        $msg = "❌ Please fill in all required fields.";
    }
}

// Fetch delivery history for current staff
$delivery_history = [];
if ($view === 'my_history') {
    try {
        $stmt = $pdo->prepare("SELECT ri.*, u.name as staff_name FROM received_items ri LEFT JOIN users u ON ri.received_by = u.id WHERE ri.station_id = ? AND ri.received_by = ? ORDER BY ri.delivery_date DESC, ri.created_at DESC LIMIT 50");
        $stmt->execute([$station_id, $me['id']]);
        $delivery_history = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {}
}
?>
<section class="receive-layout">
  <div class="receive-card">
    <div class="receive-head">
      <div>
        <div class="receive-title"><i class="fas fa-box"></i> Merchandise Receiving <span class="receive-badge">Staff</span></div>
        <div class="receive-sub">Encode received items to update Available Parts and stock levels.</div>
      </div>
      <div class="receive-tabs">
        <a class="btn <?php echo $view === 'encode' ? 'btn-primary' : 'ghost'; ?>" href="receiving_staff.php">Encode Received Items</a>
        <a class="btn <?php echo $view === 'my_history' ? 'btn-primary' : 'ghost'; ?>" href="receiving_staff.php?view=my_history">My Delivery History</a>
        <a class="btn ghost" href="inventory.php"><i class="fas fa-arrow-right"></i> Open Inventory</a>
        <?php if($view === 'encode'): ?>
          <button class="btn btn-primary" form="receive-form" type="submit"><i class="fas fa-save"></i> Submit</button>
        <?php endif; ?>
      </div>
    </div>

    <div class="receive-body">
      <?php if(isset($msg)): ?>
        <div class="alert <?php echo strpos($msg, '✅') !== false ? 'alert-success' : 'alert-error'; ?>" style="margin-bottom:16px;">
          <?php echo htmlspecialchars($msg); ?>
        </div>
      <?php endif; ?>

      <?php if($view === 'encode'): ?>
        <form method="post" id="receive-form">
          <div class="form-grid" style="margin-bottom:14px;">
            <div class="form-field">
              <label>Item Name *</label>
              <input type="text" name="item_name" placeholder="e.g., Engine Oil 5W-30" required>
            </div>
            <div class="form-field">
              <label>Quantity *</label>
              <input type="number" name="quantity" step="0.01" placeholder="e.g., 24" required>
            </div>
          </div>
          <div class="form-grid" style="margin-bottom:14px;">
            <div class="form-field">
              <label>Supplier</label>
              <input type="text" name="supplier" placeholder="Supplier name">
            </div>
            <div class="form-field">
              <label>Delivery Date</label>
              <input type="date" name="delivery_date" value="<?php echo date('Y-m-d'); ?>">
            </div>
          </div>
          <div class="form-field" style="margin-bottom:10px;">
            <label>Notes</label>
            <textarea name="notes" rows="3" placeholder="Any extra details"></textarea>
          </div>
          <div class="form-actions">
            <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Record Received Items</button>
            <span class="muted">Items will reflect in Available Parts immediately.</span>
          </div>
        </form>
      <?php elseif($view === 'my_history'): ?>
        <div class="table-wrap">
          <table class="table">
            <thead>
              <tr>
                <th>Date</th>
                <th>Item</th>
                <th>Quantity</th>
                <th>Supplier</th>
                <th>Notes</th>
              </tr>
            </thead>
            <tbody>
              <?php if(empty($delivery_history)): ?>
                <tr>
                  <td colspan="5" style="text-align:center; padding:20px; color:#888;">No delivery history found.</td>
                </tr>
              <?php else: ?>
                <?php foreach($delivery_history as $item): ?>
                  <tr>
                    <td><?php echo htmlspecialchars(date('M d, Y', strtotime($item['delivery_date']))); ?></td>
                    <td><?php echo htmlspecialchars($item['item_name']); ?></td>
                    <td><?php echo number_format($item['quantity'], 2); ?></td>
                    <td><?php echo htmlspecialchars($item['supplier'] ?? '-'); ?></td>
                    <td><?php echo htmlspecialchars($item['notes'] ?? '-'); ?></td>
                  </tr>
                <?php endforeach; ?>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
      <?php endif; ?>
    </div>
  </div>

  <div class="receive-card" style="height:max-content;">
    <div class="receive-head">
      <div class="receive-title"><i class="fas fa-info-circle"></i> Quick Tips</div>
    </div>
    <div class="receive-body">
      <div class="tips">
        <h4>Before you submit</h4>
        <ul>
          <li>Use the exact item name on the delivery receipt.</li>
          <li>Double-check the quantity and delivery date.</li>
          <li>Notes are optional but helpful for audits.</li>
        </ul>
      </div>
      <div style="margin-top:12px;" class="tips">
        <h4>After submission</h4>
        <ul>
          <li>Stock updates automatically in Available Parts.</li>
          <li>Your entry appears under My Delivery History.</li>
        </ul>
      </div>
    </div>
  </div>
</section>

<?php include __DIR__ . '/../partials/footer.php'; ?>
