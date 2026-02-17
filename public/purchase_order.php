<?php
$page_id = 'create_purchase_order';
require_once __DIR__ . '/../backend/lib.php';
require_once __DIR__ . '/../public/db_connect.php';
require_login();

$me = current_user();
$station_id = user_station_id();
$role = strtolower(trim($me['role'] ?? 'staff'));
$isStaff = ($role === 'staff');
$isManager = in_array($role, ['manager', 'admin', 'superadmin']);

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
        status ENUM('Draft', 'Pending Approval', 'Approved', 'Rejected', 'Pending', 'Confirmed', 'Received', 'Cancelled') DEFAULT 'Draft',
        expected_delivery_date DATE,
        remarks TEXT,
        rejection_reason TEXT NULL,
        approved_by INT NULL,
        approved_at DATETIME NULL,
        submitted_at DATETIME NULL,
        withdrawn_at DATETIME NULL,
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
    
    $pdo->exec("CREATE TABLE IF NOT EXISTS po_activity_log (
        id INT AUTO_INCREMENT PRIMARY KEY,
        po_id INT NOT NULL,
        action VARCHAR(50) NOT NULL,
        performed_by INT NOT NULL,
        details TEXT,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    )");
    
    $pdo->exec("CREATE TABLE IF NOT EXISTS user_notifications (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        type VARCHAR(50) NOT NULL,
        title VARCHAR(255) NOT NULL,
        message TEXT,
        link VARCHAR(255),
        is_read TINYINT DEFAULT 0,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    )");
} catch (PDOException $e) {}

// Helper function to log PO activity
function log_po_activity($pdo, $po_id, $action, $user_id, $details = '') {
    $stmt = $pdo->prepare("INSERT INTO po_activity_log (po_id, action, performed_by, details) VALUES (?, ?, ?, ?)");
    $stmt->execute([$po_id, $action, $user_id, $details]);
}

// Helper function to create notification
function create_notification($pdo, $user_id, $type, $title, $message, $link = '') {
    $stmt = $pdo->prepare("INSERT INTO user_notifications (user_id, type, title, message, link) VALUES (?, ?, ?, ?, ?)");
    $stmt->execute([$user_id, $type, $title, $message, $link]);
}

$msg = '';
$msg_type = 'success';

// Get pre-fill data from URL (for Low Stock Alert)
$prefill_item = $_GET['item'] ?? '';
$prefill_qty = $_GET['qty'] ?? '';

// Get PO ID if editing/viewing existing
$po_id = $_GET['id'] ?? null;
$po = null;
$po_items = [];
$mode = 'create'; // create, edit, view

if ($po_id) {
    // Fetch existing PO
    $stmt = $pdo->prepare("
        SELECT po.*, s.name as supplier_name, u.name as created_by_name,
               approver.name as approved_by_name
        FROM purchase_orders po
        JOIN suppliers s ON po.supplier_id = s.id
        JOIN users u ON po.created_by = u.id
        LEFT JOIN users approver ON po.approved_by = approver.id
        WHERE po.id = ? AND po.station_id = ?
    ");
    $stmt->execute([$po_id, $station_id]);
    $po = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$po) {
        header("Location: purchase_order.php");
        exit;
    }
    
    // Fetch PO items
    $stmt = $pdo->prepare("SELECT * FROM purchase_order_items WHERE po_id = ?");
    $stmt->execute([$po_id]);
    $po_items = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Determine mode based on status and user role
    if ($po['created_by'] == $me['id'] || $isManager) {
        if (in_array($po['status'], ['Draft', 'Rejected'])) {
            $mode = 'edit';
        } else {
            $mode = 'view';
        }
    } else {
        $mode = 'view';
    }
}

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];
    
    // SAVE AS DRAFT
    if ($action === 'save_draft') {
        $supplier_id = $_POST['supplier_id'] ?? '';
        $remarks = $_POST['remarks'] ?? '';
        $items = $_POST['items'] ?? [];
        
        if (empty($supplier_id) || empty($items)) {
            $msg = "❌ Please select a supplier and add at least one item.";
            $msg_type = 'error';
        } else {
            try {
                if ($po_id && $mode === 'edit') {
                    // Update existing draft
                    $stmt = $pdo->prepare("UPDATE purchase_orders SET supplier_id = ?, remarks = ? WHERE id = ?");
                    $stmt->execute([$supplier_id, $remarks, $po_id]);
                    
                    // Delete old items and insert new
                    $pdo->prepare("DELETE FROM purchase_order_items WHERE po_id = ?")->execute([$po_id]);
                    $stmtItem = $pdo->prepare("INSERT INTO purchase_order_items (po_id, item_name, quantity, unit_price, total_price) VALUES (?, ?, ?, ?, ?)");
                    $insert_errors = [];
                    foreach ($items as $index => $item) {
                        if (!empty($item['name']) && $item['qty'] > 0) {
                            $total = $item['qty'] * $item['price'];
                            try {
                                $stmtItem->execute([$po_id, $item['name'], $item['qty'], $item['price'], $total]);
                            } catch (Exception $itemEx) {
                                $insert_errors[] = "Item $index: " . $itemEx->getMessage();
                            }
                        }
                    }
                    if (!empty($insert_errors)) {
                        $msg .= "<br>⚠️ Some items failed: " . implode(", ", $insert_errors);
                    }
                    
                    log_po_activity($pdo, $po_id, 'Updated Draft', $me['id'], 'Draft updated');
                    $msg = "✅ Draft updated successfully.";
                } else {
                    // Create new draft
                    $po_number = 'PO-' . date('Ymd') . '-' . rand(1000, 9999);
                    $stmt = $pdo->prepare("INSERT INTO purchase_orders (po_number, supplier_id, station_id, created_by, remarks, status) VALUES (?, ?, ?, ?, ?, 'Draft')");
                    $stmt->execute([$po_number, $supplier_id, $station_id, $me['id'], $remarks]);
                    $new_po_id = $pdo->lastInsertId();
                    
                    $stmtItem = $pdo->prepare("INSERT INTO purchase_order_items (po_id, item_name, quantity, unit_price, total_price) VALUES (?, ?, ?, ?, ?)");
                    $items_inserted = 0;
                    foreach ($items as $index => $item) {
                        if (!empty($item['name']) && $item['qty'] > 0) {
                            $total = $item['qty'] * $item['price'];
                            try {
                                $stmtItem->execute([$new_po_id, $item['name'], $item['qty'], $item['price'], $total]);
                                $items_inserted++;
                            } catch (Exception $itemEx) {
                                error_log("Failed to insert item $index: " . $itemEx->getMessage());
                            }
                        } else {
                            error_log("Item $index skipped - name: '{$item['name']}', qty: '{$item['qty']}'");
                        }
                    }
                    error_log("Total items inserted for PO $new_po_id: $items_inserted");
                    
                    log_po_activity($pdo, $new_po_id, 'Created Draft', $me['id'], 'New draft created');
                    $msg = "✅ Draft saved successfully. PO Number: $po_number";
                    
                    // Redirect to edit mode
                    header("Location: purchase_order.php?id=$new_po_id&saved=1");
                    exit;
                }
            } catch (Exception $e) {
                $msg = "❌ Error: " . $e->getMessage() . "<br><small style='color:#666;'>" . str_replace("\n", "<br>", $e->getTraceAsString()) . "</small>";
                $msg_type = 'error';
            }
        }
    }
    
    // SUBMIT FOR APPROVAL
    if ($action === 'submit_for_approval') {
        if (!$po_id) {
            $msg = "❌ Please save as draft first.";
            $msg_type = 'error';
        } else {
            try {
                $stmt = $pdo->prepare("UPDATE purchase_orders SET status = 'Pending Approval', submitted_at = NOW() WHERE id = ?");
                $stmt->execute([$po_id]);
                
                log_po_activity($pdo, $po_id, 'Submitted for Approval', $me['id'], 'PO submitted to manager');
                
                // Notify managers
                $managers = $pdo->prepare("SELECT id FROM users WHERE station_id = ? AND role IN ('manager', 'admin', 'superadmin')");
                $managers->execute([$station_id]);
                foreach ($managers->fetchAll(PDO::FETCH_COLUMN) as $manager_id) {
                    create_notification($pdo, $manager_id, 'po_pending', 'PO Pending Approval', 
                        "PO {$po['po_number']} is pending your approval", 
                        "manager_po_review.php?id=$po_id");
                }
                
                $msg = "✅ Purchase Order submitted for manager approval.";
                $mode = 'view';
            } catch (Exception $e) {
                $msg = "❌ Error: " . $e->getMessage();
                $msg_type = 'error';
            }
        }
    }
    
    // WITHDRAW PO
    if ($action === 'withdraw') {
        if ($po_id && $po['status'] === 'Pending Approval') {
            try {
                $stmt = $pdo->prepare("UPDATE purchase_orders SET status = 'Draft', withdrawn_at = NOW() WHERE id = ?");
                $stmt->execute([$po_id]);
                
                log_po_activity($pdo, $po_id, 'Withdrawn', $me['id'], 'PO withdrawn from approval');
                
                $msg = "✅ Purchase Order withdrawn. You can now edit and resubmit.";
                $mode = 'edit';
                $po['status'] = 'Draft';
            } catch (Exception $e) {
                $msg = "❌ Error: " . $e->getMessage();
                $msg_type = 'error';
            }
        }
    }
    
    // DELETE DRAFT
    if ($action === 'delete') {
        if ($po_id && in_array($po['status'], ['Draft', 'Rejected'])) {
            try {
                $pdo->prepare("DELETE FROM purchase_order_items WHERE po_id = ?")->execute([$po_id]);
                $pdo->prepare("DELETE FROM purchase_orders WHERE id = ?")->execute([$po_id]);
                
                log_po_activity($pdo, $po_id, 'Deleted', $me['id'], 'Draft/rejected PO deleted');
                
                $msg = "✅ Purchase Order deleted.";
                header("Location: view_po.php?mode=my&deleted=1");
                exit;
            } catch (Exception $e) {
                $msg = "❌ Error: " . $e->getMessage();
                $msg_type = 'error';
            }
        }
    }
}

// Fetch Suppliers
$suppliers = $pdo->query("SELECT * FROM suppliers ORDER BY name")->fetchAll();
if (empty($suppliers)) {
    $pdo->exec("INSERT INTO suppliers (name) VALUES ('Petron Corporation'), ('Local Merchandise Supplier')");
    $suppliers = $pdo->query("SELECT * FROM suppliers ORDER BY name")->fetchAll();
}

// Fetch Products for dropdown (fuel types and merchandise)
$products = [];
try {
    // Get fuel types
    $fuel_types = $pdo->query("SELECT name as product_name, 'Fuel' as category FROM fuel_types ORDER BY name")->fetchAll();
    
    // Get merchandise products from station_inventory
    $merch_products = $pdo->prepare("
        SELECT p.name as product_name, pc.name as category
        FROM station_inventory si
        JOIN products p ON si.product_id = p.id
        LEFT JOIN product_categories pc ON p.category_id = pc.id
        WHERE si.station_id = ?
        ORDER BY p.name
    ");
    $merch_products->execute([$station_id]);
    $merch_products = $merch_products->fetchAll();
    
    $products = array_merge($fuel_types, $merch_products);
} catch (Exception $e) {
    // If tables don't exist, use defaults
    $products = [
        ['product_name' => 'Diesel Max', 'category' => 'Fuel'],
        ['product_name' => 'XCS Plus', 'category' => 'Fuel'],
        ['product_name' => 'XCS Advance', 'category' => 'Fuel'],
        ['product_name' => 'Turbo Diesel', 'category' => 'Fuel'],
        ['product_name' => 'Kerosene', 'category' => 'Fuel'],
    ];
}

// Fetch my POs for sidebar
$my_pos = [];
if ($isStaff) {
    $stmt = $pdo->prepare("
        SELECT id, po_number, status, created_at 
        FROM purchase_orders 
        WHERE created_by = ? AND station_id = ?
        ORDER BY created_at DESC
        LIMIT 5
    ");
    $stmt->execute([$me['id'], $station_id]);
    $my_pos = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

$status_badges = [
    'Draft' => ['color' => '#6c757d', 'bg' => '#e9ecef', 'text' => 'Draft'],
    'Pending Approval' => ['color' => '#fd7e14', 'bg' => '#fff3cd', 'text' => 'Pending Approval'],
    'Approved' => ['color' => '#198754', 'bg' => '#d1fae5', 'text' => 'Approved'],
    'Rejected' => ['color' => '#dc3545', 'bg' => '#f8d7da', 'text' => 'Rejected'],
    'Pending' => ['color' => '#0dcaf0', 'bg' => '#cff4fc', 'text' => 'Pending with Supplier'],
    'Confirmed' => ['color' => '#6610f2', 'bg' => '#e0cffc', 'text' => 'Confirmed'],
    'Received' => ['color' => '#198754', 'bg' => '#d1fae5', 'text' => 'Received'],
    'Cancelled' => ['color' => '#dc3545', 'bg' => '#f8d7da', 'text' => 'Cancelled']
];

include __DIR__ . '/../partials/header.php';
?>

<style>
.po-status-badge {
    display: inline-block;
    padding: 6px 12px;
    border-radius: 20px;
    font-weight: 600;
    font-size: 12px;
}
.po-info-banner {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 15px 20px;
    border-radius: 8px;
    margin-bottom: 20px;
}
.po-sidebar {
    background: #f8f9fa;
    border-radius: 8px;
    padding: 15px;
    margin-bottom: 20px;
}
.po-sidebar-item {
    padding: 10px;
    border-bottom: 1px solid #dee2e6;
    cursor: pointer;
    transition: background 0.2s;
}
.po-sidebar-item:hover {
    background: #e9ecef;
}
.po-sidebar-item.active {
    background: #667eea;
    color: white;
    border-radius: 4px;
}
.action-buttons {
    display: flex;
    gap: 10px;
    flex-wrap: wrap;
    margin-top: 20px;
}
.action-buttons .btn {
    padding: 10px 20px;
    font-weight: 500;
}
.btn-draft {
    background: #6c757d;
    color: white;
}
.btn-submit {
    background: #fd7e14;
    color: white;
}
.btn-withdraw {
    background: #ffc107;
    color: #212529;
}
.btn-delete {
    background: #dc3545;
    color: white;
}
.btn-view {
    background: #0dcaf0;
    color: #212529;
}
.grid-3 {
    display: grid;
    grid-template-columns: 2fr 1fr 1fr;
    gap: 10px;
}
</style>

<div class="page-head">
    <div>
        <h1 class="h1">
            <?php if ($mode === 'create'): ?>
                Create Purchase Order
            <?php elseif ($mode === 'edit'): ?>
                Edit Purchase Order
            <?php else: ?>
                View Purchase Order
            <?php endif; ?>
        </h1>
        <div class="sub">
            <?php if ($po): ?>
                PO Number: <strong><?php echo htmlspecialchars($po['po_number']); ?></strong>
                <span class="po-status-badge" style="background: <?php echo $status_badges[$po['status']]['bg']; ?>; color: <?php echo $status_badges[$po['status']]['color']; ?>; margin-left: 10px;">
                    <?php echo $status_badges[$po['status']]['text']; ?>
                </span>
            <?php else: ?>
                Create new orders for suppliers
            <?php endif; ?>
        </div>
    </div>
    <?php if ($po && $po['status'] === 'Approved'): ?>
        <a href="print_po.php?id=<?php echo $po['id']; ?>" target="_blank" class="btn primary">
            <i class="fas fa-print"></i> Print PO
        </a>
    <?php endif; ?>
</div>

<?php if($msg): ?>
<div class="card" style="padding:15px; margin-bottom:20px; background:<?php echo $msg_type === 'error' ? '#f8d7da' : '#e6f4ea'; ?>; color:<?php echo $msg_type === 'error' ? '#721c24' : '#155724'; ?>;">
    <?php echo $msg; ?>
</div>
<?php endif; ?>

<?php if ($prefill_item && $mode === 'create'): ?>
<div class="po-info-banner">
    <i class="fas fa-info-circle"></i> 
    <strong>Low Stock Alert:</strong> Creating PO for <strong><?php echo htmlspecialchars($prefill_item); ?></strong> 
    (Suggested qty: <?php echo htmlspecialchars($prefill_qty); ?>)
</div>
<?php endif; ?>

<?php if ($po && $po['status'] === 'Rejected'): ?>
<div class="card" style="padding:15px; margin-bottom:20px; background: #f8d7da; border-left: 4px solid #dc3545;">
    <strong><i class="fas fa-times-circle"></i> Rejected</strong><br>
    <strong>Reason:</strong> <?php echo nl2br(htmlspecialchars($po['rejection_reason'])); ?><br>
    <small>Rejected by <?php echo htmlspecialchars($po['approved_by_name']); ?> on <?php echo date('M d, Y g:i A', strtotime($po['approved_at'])); ?></small>
</div>
<?php endif; ?>

<div style="display: grid; grid-template-columns: 250px 1fr; gap: 20px;">
    <!-- Sidebar -->
    <div>
        <?php if ($isStaff && !empty($my_pos)): ?>
        <div class="po-sidebar">
            <div style="font-weight: bold; margin-bottom: 10px; padding-bottom: 10px; border-bottom: 2px solid #dee2e6;">
                <i class="fas fa-history"></i> My Recent POs
            </div>
            <?php foreach ($my_pos as $my_po): ?>
            <div class="po-sidebar-item <?php echo ($po_id == $my_po['id']) ? 'active' : ''; ?>" 
                 onclick="window.location.href='purchase_order.php?id=<?php echo $my_po['id']; ?>'">
                <div style="font-size: 12px; font-weight: 600;"><?php echo htmlspecialchars($my_po['po_number']); ?></div>
                <div style="font-size: 11px; margin-top: 3px;">
                    <span class="po-status-badge" style="background: <?php echo $status_badges[$my_po['status']]['bg']; ?>; color: <?php echo $status_badges[$my_po['status']]['color']; ?>; font-size: 10px; padding: 2px 6px;">
                        <?php echo $status_badges[$my_po['status']]['text']; ?>
                    </span>
                </div>
                <div style="font-size: 10px; color: #6c757d; margin-top: 3px;">
                    <?php echo date('M d, Y', strtotime($my_po['created_at'])); ?>
                </div>
            </div>
            <?php endforeach; ?>
            <div style="margin-top: 15px; padding-top: 10px; border-top: 1px solid #dee2e6;">
                <a href="view_po.php?mode=my" class="btn ghost small" style="width: 100%;">View All My POs</a>
            </div>
        </div>
        <?php endif; ?>
        
        <div class="po-sidebar">
            <div style="font-weight: bold; margin-bottom: 10px;">
                <i class="fas fa-lightbulb"></i> Tips
            </div>
            <ul style="font-size: 12px; margin: 0; padding-left: 15px; color: #6c757d;">
                <li style="margin-bottom: 8px;">Save as Draft to edit later</li>
                <li style="margin-bottom: 8px;">Submit when ready for approval</li>
                <li style="margin-bottom: 8px;">Manager will review and approve</li>
                <li>Print PO after approval</li>
            </ul>
        </div>
    </div>
    
    <!-- Main Content -->
    <div>
        <section class="card" style="padding: 25px;">
            <form method="post" id="poForm">
                <?php if ($mode !== 'view'): ?>
                <input type="hidden" name="action" id="formAction" value="save_draft">
                <?php endif; ?>
                
                <div class="grid-2" style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 20px;">
                    <div>
                        <label class="lbl" style="display: block; font-weight: bold; margin-bottom: 8px;">Supplier *</label>
                        <select name="supplier_id" class="select" required <?php echo $mode === 'view' ? 'disabled' : ''; ?>>
                            <option value="">-- Select Supplier --</option>
                            <?php foreach($suppliers as $s): ?>
                                <option value="<?php echo $s['id']; ?>" <?php echo ($po && $po['supplier_id'] == $s['id']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($s['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label class="lbl" style="display: block; font-weight: bold; margin-bottom: 8px;">Remarks</label>
                        <input type="text" name="remarks" class="input" placeholder="Optional notes" 
                               value="<?php echo $po ? htmlspecialchars($po['remarks']) : ($prefill_item ? 'Auto-generated from Low Stock Alert for: ' . $prefill_item : ''); ?>" 
                               <?php echo $mode === 'view' ? 'disabled' : ''; ?>>
                    </div>
                </div>

                <h3 class="h3" style="margin-top: 30px; margin-bottom: 15px;">Items</h3>
                
                <!-- Product datalist for autocomplete -->
                <datalist id="productList">
                    <?php foreach ($products as $product): ?>
                        <option value="<?php echo htmlspecialchars($product['product_name']); ?>" data-category="<?php echo htmlspecialchars($product['category'] ?? 'General'); ?>">
                            <?php echo htmlspecialchars($product['product_name']); ?> (<?php echo htmlspecialchars($product['category'] ?? 'General'); ?>)
                        </option>
                    <?php endforeach; ?>
                </datalist>
                
                <div id="items-container">
                    <?php if (!empty($po_items)): ?>
                        <?php foreach ($po_items as $index => $item): ?>
                        <div class="grid-3 item-row" style="margin-bottom: 10px; align-items: center;">
                            <div style="position: relative;">
                                <input type="text" name="items[<?php echo $index; ?>][name]" class="input" placeholder="Type or select item..." 
                                       value="<?php echo htmlspecialchars($item['item_name']); ?>" list="productList" 
                                       style="width: 100%;" required <?php echo $mode === 'view' ? 'disabled' : ''; ?>>
                                <small style="color: #6c757d; font-size: 10px;"><i class="fas fa-info-circle"></i> Type to search or enter new</small>
                            </div>
                            <input type="number" name="items[<?php echo $index; ?>][qty]" class="input" placeholder="Quantity" min="1" 
                                   value="<?php echo $item['quantity']; ?>" required <?php echo $mode === 'view' ? 'disabled' : ''; ?>>
                            <div style="display: flex; gap: 10px; align-items: center;">
                                <input type="number" name="items[<?php echo $index; ?>][price]" class="input" placeholder="Unit Price" step="0.01" 
                                       value="<?php echo $item['unit_price']; ?>" required <?php echo $mode === 'view' ? 'disabled' : ''; ?>>
                                <?php if ($mode !== 'view'): ?>
                                <button type="button" class="btn ghost small" onclick="removeItemRow(this)" style="color: #dc3545;">
                                    <i class="fas fa-trash"></i>
                                </button>
                                <?php endif; ?>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="grid-3 item-row" style="margin-bottom: 10px; align-items: center;">
                            <div style="position: relative;">
                                <input type="text" name="items[0][name]" class="input" placeholder="Type or select item..." 
                                       value="<?php echo htmlspecialchars($prefill_item); ?>" list="productList" 
                                       style="width: 100%;" required <?php echo $mode === 'view' ? 'disabled' : ''; ?>>
                                <small style="color: #6c757d; font-size: 10px;"><i class="fas fa-info-circle"></i> Type to search or enter new</small>
                            </div>
                            <input type="number" name="items[0][qty]" class="input" placeholder="Quantity" min="1" 
                                   value="<?php echo htmlspecialchars($prefill_qty); ?>" required <?php echo $mode === 'view' ? 'disabled' : ''; ?>>
                            <input type="number" name="items[0][price]" class="input" placeholder="Unit Price" step="0.01" required <?php echo $mode === 'view' ? 'disabled' : ''; ?>>
                        </div>
                    <?php endif; ?>
                </div>
                
                <?php if ($mode !== 'view'): ?>
                <button type="button" class="btn ghost small" onclick="addItemRow()" style="margin-top: 10px;">
                    <i class="fas fa-plus"></i> Add Item
                </button>
                <?php endif; ?>

                <?php if ($mode !== 'view'): ?>
                <div class="action-buttons">
                    <?php if ($mode === 'create' || ($po && in_array($po['status'], ['Draft', 'Rejected']))): ?>
                        <button type="button" class="btn btn-draft" onclick="submitForm('save_draft')">
                            <i class="fas fa-save"></i> Save as Draft
                        </button>
                    <?php endif; ?>
                    
                    <?php if ($po && in_array($po['status'], ['Draft', 'Rejected'])): ?>
                        <button type="button" class="btn btn-submit" onclick="submitForm('submit_for_approval')">
                            <i class="fas fa-paper-plane"></i> Submit for Approval
                        </button>
                    <?php endif; ?>
                    
                    <?php if ($po && $po['status'] === 'Pending Approval'): ?>
                        <button type="button" class="btn btn-withdraw" onclick="submitForm('withdraw')">
                            <i class="fas fa-undo"></i> Withdraw
                        </button>
                    <?php endif; ?>
                    
                    <?php if ($po && in_array($po['status'], ['Draft', 'Rejected'])): ?>
                        <button type="button" class="btn btn-delete" onclick="if(confirm('Are you sure you want to delete this PO?')) submitForm('delete')">
                            <i class="fas fa-trash"></i> Delete
                        </button>
                    <?php endif; ?>
                </div>
                <?php else: ?>
                <div class="action-buttons">
                    <?php if ($po['status'] === 'Approved'): ?>
                        <a href="print_po.php?id=<?php echo $po['id']; ?>" target="_blank" class="btn btn-view">
                            <i class="fas fa-print"></i> Print PO
                        </a>
                    <?php endif; ?>
                    <a href="view_po.php?mode=my" class="btn ghost">Back to List</a>
                </div>
                <?php endif; ?>
            </form>
        </section>
        
        <?php if ($po): ?>
        <!-- Activity Log -->
        <section class="card" style="padding: 20px; margin-top: 20px;">
            <h3 class="h3" style="margin-bottom: 15px;"><i class="fas fa-history"></i> Activity Log</h3>
            <?php
            $activities = $pdo->prepare("SELECT * FROM po_activity_log WHERE po_id = ? ORDER BY created_at DESC");
            $activities->execute([$po_id]);
            $logs = $activities->fetchAll();
            ?>
            <?php if (!empty($logs)): ?>
            <div style="max-height: 200px; overflow-y: auto;">
                <?php foreach ($logs as $log): ?>
                <div style="padding: 10px; border-bottom: 1px solid #dee2e6;">
                    <strong><?php echo htmlspecialchars($log['action']); ?></strong>
                    <span style="color: #6c757d; float: right;"><?php echo date('M d, Y g:i A', strtotime($log['created_at'])); ?></span>
                    <?php if ($log['details']): ?>
                    <div style="font-size: 12px; color: #6c757d; margin-top: 5px;"><?php echo htmlspecialchars($log['details']); ?></div>
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>
            </div>
            <?php else: ?>
            <p style="color: #6c757d;">No activity recorded yet.</p>
            <?php endif; ?>
        </section>
        <?php endif; ?>
    </div>
</div>

<script>
let itemCount = <?php echo count($po_items) > 0 ? count($po_items) : 1; ?>;

function addItemRow() {
    const container = document.getElementById('items-container');
    const div = document.createElement('div');
    div.className = 'grid-3 item-row';
    div.style.marginBottom = '10px';
    div.style.alignItems = 'center';
    div.innerHTML = `
        <div style="position: relative;">
            <input type="text" name="items[${itemCount}][name]" class="input" placeholder="Type or select item..." list="productList" style="width: 100%;" required>
            <small style="color: #6c757d; font-size: 10px;"><i class="fas fa-info-circle"></i> Type to search or enter new</small>
        </div>
        <input type="number" name="items[${itemCount}][qty]" class="input" placeholder="Quantity" min="1" required>
        <div style="display: flex; gap: 10px; align-items: center;">
            <input type="number" name="items[${itemCount}][price]" class="input" placeholder="Unit Price" step="0.01" required>
            <button type="button" class="btn ghost small" onclick="removeItemRow(this)" style="color: #dc3545;">
                <i class="fas fa-trash"></i>
            </button>
        </div>
    `;
    container.appendChild(div);
    itemCount++;
}

function removeItemRow(btn) {
    const row = btn.closest('.item-row');
    const container = document.getElementById('items-container');
    if (container.children.length > 1) {
        row.remove();
    } else {
        alert('At least one item is required.');
    }
}

function submitForm(action) {
    document.getElementById('formAction').value = action;
    document.getElementById('poForm').submit();
}
</script>

<?php include __DIR__ . '/../partials/footer.php'; ?>