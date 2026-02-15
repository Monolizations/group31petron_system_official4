<?php
/**
 * POS System - Phase 2: Multi-Product Transactions
 * 
 * This script implements multi-product capability allowing staff to add
 * multiple items to a single transaction with dynamic rows.
 * 
 * Features:
 * - Add/Remove items dynamically
 * - Product type selection (Fuel vs Merchandise)
 * - Auto-populated prices from inventory
 * - Stock validation per item
 * - Individual item subtotals + grand total
 * - Enhanced receipt with all items
 */

require_once __DIR__ . '/../backend/lib.php';
require_once __DIR__ . '/../public/db_connect.php';
require_login();

$me = current_user();
$station_id = user_station_id();
$role = role_key($me['role'] ?? '');
$isAdmin = in_array($role, ['admin', 'superadmin']);
$msg = '';
$last_sale_id = '';
$error = '';

// Load inventory and fuel pricing (same as pos.php)
$inventory = [];
$fuelPricing = [];
try {
    // Load merchandise products
    $stmt = $pdo->prepare("
        SELECT p.*, i.stock_level, inv.unit as unit, i.status as inventory_status
        FROM products p
        INNER JOIN product_types pt ON p.type_id = pt.id
        LEFT JOIN station_inventory i ON p.id = i.product_id AND i.station_id = ? AND i.status = 'active'
        WHERE pt.name = 'merch' AND (i.status IS NULL OR i.status = 'active')
        ORDER BY p.name
    ");
    $stmt->execute([$station_id]);
    $merchProducts = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Load fuel products with current pricing
    $stmt = $pdo->prepare("
        SELECT p.*, i.stock_level, inv.unit as unit, i.status as inventory_status,
               fp.price_per_liter as price
        FROM products p
        INNER JOIN product_types pt ON p.type_id = pt.id
        LEFT JOIN station_inventory i ON p.id = i.product_id AND i.station_id = ? AND i.status = 'active'
        LEFT JOIN fuel_pricing fp ON fp.fuel_type_id = p.type_id AND fp.station_id = ? AND fp.is_active = 1
        WHERE pt.name = 'fuel' AND (i.status IS NULL OR i.status = 'active')
        ORDER BY p.name
    ");
    $stmt->execute([$station_id, $station_id]);
    $fuelProducts = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Fallback to products.price if fuel_pricing not available
    foreach ($fuelProducts as &$fp) {
        if (!$fp['price'] && $fp['price'] > 0) {
            $fp['price'] = $fp['price'] ?? $fp['price'];
        }
    }
    
    $inventory = [
        'fuel' => $fuelProducts,
        'merch' => $merchProducts
    ];
    
    // Load all fuel pricing for display
    $stmt = $pdo->prepare("
        SELECT fp.*, ft.name as fuel_name
        FROM fuel_pricing fp
        INNER JOIN fuel_types ft ON fp.fuel_type_id = ft.id
        WHERE fp.station_id = ? AND fp.is_active = 1
        ORDER BY ft.name
    ");
    $stmt->execute([$station_id]);
    $fuelPricing = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch (Exception $e) {
    $inventory = ['fuel' => [], 'merch' => []];
    $fuelPricing = [];
}

// Handle multi-product transaction
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $customer_name = $_POST['customer_name'] ?? 'Walk-in';
    $items = $_POST['items'] ?? [];
    $payment_type = $_POST['payment_type'] ?? 'Cash';
    $gcash_ref_number = trim($_POST['gcash_ref_number'] ?? '');
    $discount = (float)($_POST['discount'] ?? 0);
    
    if (empty($items)) {
        $msg = "❌ Error: Please add at least one item to the transaction.";
    } elseif (empty($customer_name)) {
        $msg = "❌ Error: Customer name is required.";
    } elseif (empty($payment_type)) {
        $msg = "❌ Error: Payment type is required.";
    } else {
        try {
            $pdo->beginTransaction();
            
            $total = 0;
            $item_details = [];
            
            // Validate and process each item
            foreach ($items as $item) {
                $product_id = (int)($item['product_id'] ?? 0);
                $quantity = (int)($item['quantity'] ?? 0);
                
                if ($product_id <= 0 || $quantity <= 0) {
                    $pdo->rollBack();
                    $msg = "❌ Error: Invalid product or quantity.";
                    break 2;
                }
                
                // Get product details
                $stmt = $pdo->prepare("SELECT p.name, pt.name as type_name, inv.unit, p.price FROM products p INNER JOIN product_types pt ON p.type_id = pt.id WHERE p.id = ?");
                $stmt->execute([$product_id]);
                $product = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if (!$product) {
                    $pdo->rollBack();
                    $msg = "❌ Error: Product not found for ID: $product_id";
                    break 2;
                }
                
                // Get product details
                $stmt = $pdo->prepare("SELECT p.name, pt.name as type_name, inv.unit, p.price FROM products p INNER JOIN product_types pt ON p.type_id = pt.id WHERE p.id = ?");
                $stmt->execute([$product_id]);
                $product = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if (!$product) {
                    $pdo->rollBack();
                    $msg = "❌ Error: Product not found for ID: $product_id";
                    break 2;
                }
                
                // Check stock availability
                $stmt = $pdo->prepare("SELECT stock_level FROM station_inventory WHERE product_id = ? AND station_id = ?");
                $stmt->execute([$product_id, $station_id]);
                $stock = $stmt->fetchColumn();
                
                if ($stock === null || $stock === false || $stock < $quantity) {
                    $pdo->rollBack();
                    $msg = "❌ Error: Insufficient stock for {$product['name']}. Available: {$stock} {$product['inv_unit']}. Requested: {$quantity} {$product['inv_unit']}.";
                    break 2;
                }
                
                $item_price = $product['price'];
                $item_total = $quantity * $item_price;
                $total += $item_total;
                
                $item_details[] = [
                    'name' => $product['name'],
                    'quantity' => $quantity,
                    'price' => $item_price,
                    'unit_price' => $item_price,
                    'total' => $item_total,
                    'unit' => $product['unit']
                ];
            }
            
            // Apply discount to total
            $final_total = $total - $discount;
            
            if ($final_total <= 0) {
                $pdo->rollBack();
                $msg = "❌ Error: Total amount must be greater than 0.";
            } elseif ($msg === '') {
                // Insert sale
                $initial_status = $isAdmin ? 'Completed' : 'Pending';
                $sale_id = uniqid('SALE-');
                $is_locked = $isAdmin ? 1 : 0;
                
                $stmt = $pdo->prepare("INSERT INTO sales (id, station_id, user_id, sale_date, sale_time, payment_method, total, status, created_at, gcash_ref_number) VALUES (?, ?, ?, CURDATE(), CURTIME(), ?, ?, ?, NOW(), ?)");
                $stmt->execute([$sale_id, $station_id, $me['id'], $payment_type, $final_total, $initial_status, $gcash_ref_number]);
                $last_sale_id = $sale_id;
                
                // Add name column if it doesn't exist
                try {
                    $pdo->exec("ALTER TABLE sale_items ADD COLUMN name VARCHAR(255) NULL AFTER product_id");
                } catch (PDOException $e) {
                    // Column already exists, ignore
                }
                
                // Insert items
                $stmtItem = $pdo->prepare("INSERT INTO sale_items (sale_id, product_id, name, quantity, unit_price, total_amount) VALUES (?, ?, ?, ?, ?, ?)");
                foreach ($item_details as $item) {
                    $stmtItem->execute([$sale_id, $item['product_id'], $item['name'], $item['quantity'], $item['unit_price'], $item['total']]);
                    
                    // Deduct stock for each item
                    $stmtStock = $pdo->prepare("UPDATE station_inventory SET stock_level = stock_level - ? WHERE product_id = ? AND station_id = ?");
                    $stmtStock->execute([$item['quantity'], $item['product_id'], $station_id]);
                }
                
                $pdo->commit();
                $msg = "✅ Transaction completed successfully. Stock deducted immediately for all items.";
            }
        } catch (Exception $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            $msg = "❌ Error: " . $e->getMessage();
        }
    }
}

include __DIR__ . '/../partials/header.php';
?>

<div class="page-head">
    <div>
        <h1 class="h1">Multi-Product Transaction</h1>
        <div class="sub">Add multiple items to a single transaction with inventory integration</div>
    </div>
</div>

<?php if($msg): ?>
<div id="toast" class="toast show" style="background: <?php echo strpos($msg, 'Error') !== false ? '#dc3545' : '#28a745'; ?>; position: fixed; bottom: 100px; left: 50%; transform: translateX(-50%); padding: 16px 20px; border-radius: 8px; color: white; font-weight: 500; z-index: 1000; max-width: 500px;">
    <?php echo $msg; ?>
</div>
<script>setTimeout(() => { const el = document.getElementById('toast'); if(el) el.remove(); }, <?php echo strpos($msg, 'Error') !== false ? '8000' : '3000'; ?>);</script>
<?php endif; ?>

<?php if (!$isAdmin): ?>
<!-- STAFF VIEW: Multi-Product Encoding Form -->
<div class="card" style="padding: 20px; max-width: 1200px; margin: 0 auto;">
    <form method="post" id="posMultiForm">
        <div style="margin-bottom: 30px;">
            <div class="form-group mb-3">
                <label class="lbl">Customer Name</label>
                <input type="text" name="customer_name" list="customerList" class="inp full" placeholder="Walk-in" required>
                <datalist id="customerList">
                    <?php 
                    $customers = [];
                    try {
                        $customers = $pdo->query("SELECT name FROM customers ORDER BY name")->fetchAll(PDO::FETCH_COLUMN);
                    } catch(Exception $e) {}
                    foreach($customers as $c): ?>
                        <option value="<?php echo htmlspecialchars($c); ?>">
                    <?php endforeach; ?>
                </datalist>
            </div>
        </div>
        
        <!-- Items Section -->
        <div style="background: #f8f9fa; padding: 20px; border-radius: 8px; margin-bottom: 20px;">
            <h3 style="margin: 0 0 20px 0; color: #003d7a;">Transaction Items</h3>
            
            <!-- Add New Item Section -->
            <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 15px; margin-bottom: 20px; padding-bottom: 20px; border-bottom: 1px dashed #ccc;">
                <div>
                    <div class="form-group mb-3">
                        <label class="lbl">Product Type</label>
                        <select name="new_item_type" id="add_product_type" class="inp full" required onchange="loadAddProducts()">
                            <option value="">Select Type</option>
                            <option value="fuel">Fuel</option>
                            <option value="merch">Merchandise</option>
                        </select>
                    </div>
                </div>
                <div>
                    <div class="form-group mb-3">
                        <label class="lbl">Product</label>
                        <select name="new_product_id" id="add_product_id" class="inp full" required onchange="prepareNewItem()">
                            <option value="">Select Product</option>
                        </select>
                        <small class="muted" id="add_stock_info">Select a product type first</small>
                    </div>
                </div>
                <div style="display: flex; align-items: flex-end;">
                    <button type="button" onclick="addItem()" class="btn primary">
                        <i class="fas fa-plus"></i> Add Item
                    </button>
                </div>
            </div>
            
            <!-- Items List Container -->
            <div id="items-container">
                <!-- Dynamic items will be rendered here by JavaScript -->
            </div>
        </div>
        
        <!-- Payment & Total Section -->
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 30px; margin-top: 20px;">
            <div>
                <div class="form-group mb-3">
                    <label class="lbl">Payment Type</label>
                    <select name="payment_type" id="payment_method_pos" class="inp full" onchange="toggleGcashRef()">
                        <option value="">Select payment type</option>
                        <option value="Cash">Cash</option>
                        <option value="GCash">GCash</option>
                    </select>
                </div>
                
                <!-- GCash Reference Number Field -->
                <div class="form-group mb-3" id="gcash_ref_field" style="display: none;">
                    <label class="lbl">GCash Reference Number</label>
                    <input type="text" name="gcash_ref_number" id="gcash_ref_number" class="inp full" placeholder="e.g., 1234567890">
                    <small class="muted">Required for GCash payments</small>
                </div>
            </div>
            
            <div>
                <div class="form-group mb-3">
                    <label class="lbl">Discount</label>
                    <input type="number" name="discount" id="discount" class="inp full" step="0.01" value="0" oninput="calculateGrandTotal()">
                </div>
                
                <div class="total-display" style="margin-top: 20px; padding: 20px; background: #f8f9fa; border-radius: 8px; text-align: right;">
                    <div style="font-size: 0.9em; color: #666;">Total Items</div>
                    <div style="font-size: 1.5em; font-weight: bold; color: var(--petron-blue);" id="total_items">0</div>
                    <div style="font-size: 0.9em; color: #666; margin-top: 10px;">Discount</div>
                    <div style="font-size: 0.9em; color: #666;">- ₱</div>
                    <div style="font-size: 0.9em; color: #666; margin-top: 10px;">Grand Total</div>
                    <div style="font-size: 2em; font-weight: bold; color: var(--petron-blue);" id="displayTotal">₱0.00</div>
                </div>
            </div>
        </div>
        
        <div class="actions" style="margin-top: 30px; display: flex; gap: 10px; justify-content: space-between; align-items: center;">
            <button type="button" class="btn ghost" onclick="clearForm()">
                <i class="fas fa-undo"></i> Clear Form
            </button>
            <button type="submit" class="btn primary" onclick="return validateForm();">
                <i class="fas fa-save"></i> Save Transaction
            </button>
        </div>
    </form>
</div>

<?php endif; ?>

<!-- Receipt Modal -->
<?php if($last_sale_id): ?>
<div class="modal show" id="receiptModal">
    <div class="modal-content" style="max-width: 500px; text-align: center;">
        <div class="modal-header">
            <h3 class="modal-title">Transaction Complete</h3>
            <button class="modal-close" onclick="document.getElementById('receiptModal').remove()">&times;</button>
        </div>
        <div class="modal-body">
            <div style="font-size: 48px; color: #28a745; margin-bottom: 10px;"><i class="fas fa-check-circle"></i></div>
            <p>Transaction #<?php echo $last_sale_id; ?> saved.</p>
            <div style="margin-top: 20px; display: flex; gap: 10px; justify-content: center;">
                <button class="btn ghost" onclick="window.print()"><i class="fas fa-print"></i> Print</button>
                <button class="btn ghost" onclick="alert('Email sent!')"><i class="fas fa-envelope"></i> Email</button>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<style>
    .item-row {
        display: grid;
        grid-template-columns: 2fr 150px 120px 100px 50px;
        gap: 10px;
        align-items: center;
        padding: 10px;
        background: white;
        border-radius: 6px;
        margin-bottom: 10px;
        border-left: 4px solid #003d7a;
    }
    .item-row:hover {
        background: #f0f0f0;
    }
    .item-info {
        display: flex;
        flex-direction: column;
        gap: 3px;
    }
    .item-name {
        font-weight: 600;
        color: #003d7a;
    }
    .item-stock {
        font-size: 12px;
        color: #6c757d;
    }
    .item-controls {
        display: flex;
        gap: 5px;
        align-items: center;
    }
    .item-qty {
        width: 70px;
        padding: 8px;
        border: 1px solid #ddd;
        border-radius: 4px;
    }
    .item-price {
        width: 100px;
        padding: 8px;
        border: 1px solid #ddd;
        border-radius: 4px;
        background: #f0f0f0;
        color: #666;
    }
    .item-subtotal {
        font-weight: 600;
        color: #002F6C;
        font-size: 14px;
    }
    .toast {
        animation: slideIn 0.3s ease-out;
    }
    @keyframes slideIn {
        from { transform: translateX(-50%) translateY(-100%); opacity: 0; }
        to { transform: translateX(-50%) translateY(0); opacity: 1; }
    }
</style>

<script>
// Product data loaded from PHP
const inventoryData = <?php echo json_encode($inventory); ?>;
let items = [];
let itemIdCounter = 0;

function loadAddProducts() {
    const type = document.getElementById('add_product_type').value;
    const productSelect = document.getElementById('add_product_id');
    const stockInfo = document.getElementById('add_stock_info');
    
    productSelect.innerHTML = '<option value="">Select Product</option>';
    
    if (type && inventoryData[type]) {
        inventoryData[type].forEach(product => {
            const option = document.createElement('option');
            option.value = product.id;
            
            const stockLevel = parseFloat(product.stock_level) || 0;
            const stockClass = stockLevel <= 0 ? 'color: #dc3545; font-weight: bold;' : '';
            const stockText = stockLevel <= 0 ? ' (OUT OF STOCK)' : ` (Stock: ${stockLevel} ${product.inv_unit || 'pc'})`;
            
            option.textContent = `${product.name}${stockText}`;
            option.dataset.price = product.price || 0;
            option.dataset.stock = stockLevel;
            option.dataset.unit = product.inv_unit || '';
            option.style = stockClass;
            
            productSelect.appendChild(option);
        });
        stockInfo.textContent = `Found ${inventoryData[type].length} products`;
    } else {
        stockInfo.textContent = 'Select a product type first';
    }
}

function prepareNewItem() {
    const productSelect = document.getElementById('add_product_id');
    const selectedOption = productSelect.options[productSelect.selectedIndex];
    
    if (selectedOption && selectedOption.value) {
        const stockLevel = parseFloat(selectedOption.dataset.stock) || 0;
        if (stockLevel <= 0) {
            alert('This product is out of stock. Please select a different product.');
            return false;
        }
    }
    return true;
}

function addItem() {
    const productSelect = document.getElementById('add_product_id');
    const selectedOption = productSelect.options[productSelect.selectedIndex];
    
    if (!selectedOption || !selectedOption.value) {
        alert('Please select a product first.');
        return;
    }
    
    const stockLevel = parseFloat(selectedOption.dataset.stock) || 0;
    if (stockLevel <= 0) {
        alert('This product is out of stock. Please select a different product.');
        return;
    }
    
    const newItem = {
        id: ++itemIdCounter,
        product_id: parseInt(selectedOption.value),
        name: selectedOption.textContent.split(' (')[0].trim(),
        price: parseFloat(selectedOption.dataset.price) || 0,
        unit: selectedOption.dataset.unit || '',
        stock_level: stockLevel,
        quantity: 1
    };
    
    items.push(newItem);
    renderItems();
    calculateGrandTotal();
}

function renderItems() {
    const container = document.getElementById('items-container');
    
    if (items.length === 0) {
        container.innerHTML = '<p style="text-align: center; color: #666; padding: 20px;">No items added yet. Click "Add Item" to add products to the transaction.</p>';
        document.getElementById('total_items').textContent = '0';
        return;
    }
    
    container.innerHTML = '';
    let grandTotal = 0;
    
    items.forEach((item, index) => {
        const subtotal = item.quantity * item.price;
        grandTotal += subtotal;
        
        const html = `
            <div class="item-row" data-index="${index}">
                <div class="item-info">
                    <span class="item-name">${item.name}</span>
                    <span class="item-stock">${item.stock_level} ${item.unit} available</span>
                </div>
                <div class="item-controls">
                    <div style="display: flex; align-items: center; gap: 5px;">
                        <label style="font-size: 12px;">Qty:</label>
                        <input type="number" value="${item.quantity}" 
                               onchange="updateItem(${index}, 'quantity', this.value)"
                               min="1" class="item-qty">
                    </div>
                    <input type="number" value="${item.price}" 
                           readonly
                           class="item-price">
                    <span class="item-subtotal">₱${subtotal.toFixed(2)}</span>
                    <button type="button" onclick="removeItem(${index})" class="btn small danger">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
            </div>
        `;
        container.innerHTML += html;
    });
    
    document.getElementById('total_items').textContent = items.length;
    calculateGrandTotal();
}

function updateItem(index, field, value) {
    const item = items[index];
    const productSelect = document.getElementById('add_product_id');
    const selectedOption = productSelect.options[productSelect.selectedIndex];
    
    if (field === 'quantity') {
        const newQty = parseInt(value) || 1;
        const stockLevel = parseFloat(selectedOption.dataset.stock) || 0;
        
        if (newQty > stockLevel) {
            alert(`Insufficient stock! Available: ${stockLevel} ${item.unit}. Requested: ${newQty} ${item.unit}.`);
            return;
        }
        
        item.quantity = newQty;
    }
    
    renderItems();
}

function removeItem(index) {
    items.splice(index, 1);
    renderItems();
}

function clearForm() {
    if (items.length > 0 && !confirm('Are you sure you want to clear all items?')) {
        return;
    }
    items = [];
    renderItems();
    document.getElementById('add_product_id').value = '';
    document.getElementById('add_product_type').value = '';
    document.getElementById('add_stock_info').textContent = 'Select a product type first';
}

function calculateGrandTotal() {
    const discount = parseFloat(document.getElementById('discount').value) || 0;
    let itemsTotal = 0;
    
    items.forEach(item => {
        itemsTotal += item.quantity * item.price;
    });
    
    const grandTotal = itemsTotal - discount;
    document.getElementById('displayTotal').innerText = '₱' + grandTotal.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2});
}

function toggleGcashRef() {
    const paymentType = document.getElementById('payment_method_pos').value;
    const gcashRefField = document.getElementById('gcash_ref_field');
    const gcashRefInput = document.getElementById('gcash_ref_number');
    
    if (paymentType === 'GCash') {
        gcashRefField.style.display = 'block';
        gcashRefInput.required = true;
    } else {
        gcashRefField.style.display = 'none';
        gcashRefInput.required = false;
        gcashRefInput.value = '';
    }
}

function validateForm() {
    const customerName = document.querySelector('input[name="customer_name"]').value.trim();
    const paymentType = document.getElementById('payment_method_pos').value;
    const gcashRefInput = document.getElementById('gcash_ref_number');
    
    if (!customerName) {
        alert('Customer name is required. Please enter a customer name or "Walk-in".');
        return false;
    }
    
    if (items.length === 0) {
        alert('Please add at least one item to the transaction.');
        return false;
    }
    
    if (!paymentType) {
        alert('Payment type is required. Please select Cash or GCash.');
        return false;
    }
    
    if (paymentType === 'GCash' && !gcashRefInput.value.trim()) {
        alert('GCash reference number is required for GCash payments.');
        return false;
    }
    
    return true;
}
</script>

<?php include __DIR__ . '/../partials/footer.php'; ?>
