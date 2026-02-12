<?php
$page_id = 'create_purchase_order';
require_once __DIR__ . '/../backend/lib.php';
require_once __DIR__ . '/../public/db_connect.php';
require_login();

$me = current_user();
$station_id = user_station_id();

// Ensure tables exist
try {
    $pdo->exec("CREATE TABLE IF NOT EXISTS suppliers (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(255) NOT NULL,
        contact_person VARCHAR(255),
        phone VARCHAR(50),
        email VARCHAR(100),
        address TEXT,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    )");
    
    $pdo->exec("CREATE TABLE IF NOT EXISTS purchase_orders (
        id INT AUTO_INCREMENT PRIMARY KEY,
        po_number VARCHAR(50) UNIQUE NOT NULL,
        supplier_id INT NOT NULL,
        station_id INT NOT NULL,
        created_by INT NOT NULL,
        status ENUM('Pending', 'Confirmed', 'Received', 'Cancelled') DEFAULT 'Pending',
        expected_delivery_date DATE,
        remarks TEXT,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )");
    
    $pdo->exec("CREATE TABLE IF NOT EXISTS purchase_order_items (
        id INT AUTO_INCREMENT PRIMARY KEY,
        po_id INT NOT NULL,
        item_name VARCHAR(255) NOT NULL,
        quantity INT NOT NULL,
        unit_price DECIMAL(10,2) NOT NULL,
        total_price DECIMAL(10,2) NOT NULL,
        received_quantity INT DEFAULT 0
    )");
} catch (PDOException $e) {}

$msg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'create_po') {
    $supplier_id = $_POST['supplier_id'];
    $remarks = $_POST['remarks'];
    $items = $_POST['items'] ?? []; // Array of [name, qty, price]
    
    if ($supplier_id && !empty($items)) {
        try {
            $po_number = 'PO-' . date('Ymd') . '-' . rand(1000, 9999);
            $stmt = $pdo->prepare("INSERT INTO purchase_orders (po_number, supplier_id, station_id, created_by, remarks) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$po_number, $supplier_id, $station_id, $me['id'], $remarks]);
            $po_id = $pdo->lastInsertId();
            
            $stmtItem = $pdo->prepare("INSERT INTO purchase_order_items (po_id, item_name, quantity, unit_price, total_price) VALUES (?, ?, ?, ?, ?)");
            foreach ($items as $item) {
                if (!empty($item['name']) && $item['qty'] > 0) {
                    $total = $item['qty'] * $item['price'];
                    $stmtItem->execute([$po_id, $item['name'], $item['qty'], $item['price'], $total]);
                }
            }
            
            log_activity($pdo, $me['id'], 'Create PO', "Created Purchase Order $po_number");
            $msg = "✅ Purchase Order $po_number created successfully.";
        } catch (Exception $e) {
            $msg = "❌ Error: " . $e->getMessage();
        }
    } else {
        $msg = "❌ Please select a supplier and add at least one item.";
    }
}

// Fetch Suppliers
$suppliers = $pdo->query("SELECT * FROM suppliers ORDER BY name")->fetchAll();
if (empty($suppliers)) {
    $pdo->exec("INSERT INTO suppliers (name) VALUES ('Petron Corp'), ('Local Merch Supplier')");
    $suppliers = $pdo->query("SELECT * FROM suppliers ORDER BY name")->fetchAll();
}

include __DIR__ . '/../partials/header.php';
?>
<div class="page-head">
    <div>
        <h1 class="h1">Create Purchase Order</h1>
        <div class="sub">Create new orders for suppliers</div>
    </div>
</div>

<?php if($msg): ?><div class="card" style="padding:10px; margin-bottom:20px; background:#e6f4ea; color:green;"><?php echo $msg; ?></div><?php endif; ?>

<section class="card" style="padding:20px;">
    <form method="post">
        <input type="hidden" name="action" value="create_po">
        <div class="grid-2">
            <div>
                <label class="lbl">Supplier</label>
                <select name="supplier_id" class="inp" required>
                    <option value="">-- Select Supplier --</option>
                    <?php foreach($suppliers as $s): ?>
                        <option value="<?php echo $s['id']; ?>"><?php echo htmlspecialchars($s['name']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label class="lbl">Remarks</label>
                <input type="text" name="remarks" class="inp" placeholder="Optional notes">
            </div>
        </div>

        <h3 class="h3" style="margin-top:20px;">Items</h3>
        <div id="items-container">
            <div class="grid-3 item-row" style="margin-bottom:10px;">
                <input type="text" name="items[0][name]" class="inp" placeholder="Item Name" required>
                <input type="number" name="items[0][qty]" class="inp" placeholder="Quantity" min="1" required>
                <input type="number" name="items[0][price]" class="inp" placeholder="Unit Price" step="0.01" required>
            </div>
        </div>
        <button type="button" class="btn ghost small" onclick="addItemRow()">+ Add Item</button>

        <div style="margin-top:20px; text-align:right;">
            <button type="submit" class="btn primary">Submit Purchase Order</button>
        </div>
    </form>
</section>

<script>
let itemCount = 1;
function addItemRow() {
    const div = document.createElement('div');
    div.className = 'grid-3 item-row';
    div.style.marginBottom = '10px';
    div.innerHTML = `
        <input type="text" name="items[${itemCount}][name]" class="inp" placeholder="Item Name" required>
        <input type="number" name="items[${itemCount}][qty]" class="inp" placeholder="Quantity" min="1" required>
        <input type="number" name="items[${itemCount}][price]" class="inp" placeholder="Unit Price" step="0.01" required>
    `;
    document.getElementById('items-container').appendChild(div);
    itemCount++;
}
</script>

<style>
    .grid-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 15px; }
    .grid-3 { display: grid; grid-template-columns: 2fr 1fr 1fr; gap: 10px; }
    .lbl { display: block; font-weight: bold; margin-bottom: 5px; font-size: 0.9em; }
    .inp { width: 100%; padding: 8px; border: 1px solid #ccc; border-radius: 4px; }
    .btn { padding: 8px 16px; border-radius: 4px; cursor: pointer; border: none; }
    .btn.primary { background: var(--petron-blue); color: white; }
    .btn.ghost { background: transparent; border: 1px solid #ccc; color: #333; }
</style>
<?php include __DIR__ . '/../partials/footer.php'; ?>
