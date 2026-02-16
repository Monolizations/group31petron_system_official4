<?php
$page_id = 'pos';
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

// Handle password verification
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['verify'])) {
    if ($role === 'admin') {
        $stmt = $pdo->prepare("SELECT password FROM users WHERE station_id = ? AND status = 'active' AND role IN ('manager','Manager')");
        $stmt->execute([$station_id]);
        $hashes = $stmt->fetchAll(PDO::FETCH_COLUMN);

        $ok = false;
        foreach ($hashes as $hash) {
            if (password_verify($_POST['verify_password'] ?? '', $hash)) { $ok = true; break; }
        }

        if ($ok) {
            $_SESSION['pos_verified'] = true;
            $_SESSION['pos_verified_time'] = time();
        } else {
            $error = 'Incorrect password.';
        }
    } elseif ($role === 'superadmin') {
        $_SESSION['pos_verified'] = true;
        $_SESSION['pos_verified_time'] = time();
    }
}

// Check session verification (valid for 10 mins)
if (isset($_SESSION['pos_verified']) && $_SESSION['pos_verified'] && (time() - $_SESSION['pos_verified_time'] < 600)) {
    $_SESSION['pos_verified_time'] = time(); // extend
}

// Ensure tables exist (Auto-fix for missing tables)
try {
    $pdo->exec("CREATE TABLE IF NOT EXISTS sales (
        id VARCHAR(64) NOT NULL PRIMARY KEY,
        station_id INT,
        user_id INT,
        customer VARCHAR(255),
        payment_method VARCHAR(32) NOT NULL,
        total DECIMAL(12,2) NOT NULL,
        sale_date DATE NOT NULL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        due_date DATE NULL,
        status VARCHAR(50) DEFAULT 'Completed',
        is_locked TINYINT(1) DEFAULT 0,
        gcash_ref_number VARCHAR(50) NULL
    )");
    $pdo->exec("CREATE TABLE IF NOT EXISTS sale_items (
        id INT AUTO_INCREMENT PRIMARY KEY,
        sale_id VARCHAR(64),
        name VARCHAR(255),
        qty INT,
        price DECIMAL(12,2),
        amount DECIMAL(12,2),
        FOREIGN KEY (sale_id) REFERENCES sales(id) ON DELETE CASCADE
    )");
    
    // Add missing columns if they don't exist
    try {
        $pdo->exec("ALTER TABLE sales ADD COLUMN customer VARCHAR(255) NULL");
    } catch (PDOException $e) {
        // Column already exists, ignore error
    }
    
    // Add gcash_ref_number column if it doesn't exist
    try {
        $pdo->exec("ALTER TABLE sales ADD COLUMN gcash_ref_number VARCHAR(50) NULL");
    } catch (PDOException $e) {
        // Column already exists, ignore error
    }
} catch (PDOException $e) {}

// Handle New Transaction
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // ADMIN ACTIONS: Approve / Reject / Unlock
    if (isset($_POST['action']) && $isAdmin) {
        $sale_id = $_POST['sale_id'] ?? '';
        $action = $_POST['action'];

        // Unlock Transaction
        if ($action === 'unlock' && isset($_POST['unlock_reason']) && !empty($_POST['unlock_reason'])) {
            try {
                // Password verification required
                if (!isset($_SESSION['pos_verified'])) {
                    $error = 'Password verification required to unlock transactions.';
                } else {
                    $role = role_key($me['role'] ?? '');
                    if ($role === 'admin') {
                        $stmt = $pdo->prepare("SELECT password FROM users WHERE station_id = ? AND status = 'active' AND role IN ('manager','Manager')");
                        $stmt->execute([$station_id]);
                        $hashes = $stmt->fetchAll(PDO::FETCH_COLUMN);

                        $ok = false;
                        foreach ($hashes as $hash) {
                            if (password_verify($_POST['unlock_password'] ?? '', $hash)) { $ok = true; break; }
                        }

                        if (!$ok) {
                            $error = 'Incorrect password.';
                        }
                    } elseif ($role === 'superadmin') {
                        $ok = true;
                    } else {
                        $error = 'Only Admin can unlock transactions.';
                    }
                }

                if (!isset($error) && $ok) {
                    $unlock_reason = $_POST['unlock_reason'] ?? '';

                    // Unlock the transaction
                    $stmt = $pdo->prepare("UPDATE sales SET is_locked = 0, override_reason = ?, override_by = ?, override_at = NOW() WHERE id = ?");
                    $stmt->execute([$unlock_reason, $me['id'], $sale_id]);

                    log_activity($pdo, $me['id'], 'Admin Unlock Transaction', 'UNLOCKED Transaction #' . $sale_id . ' - Reason: ' . substr($unlock_reason, 0, 100));

                    $msg = "✅ Transaction #" . $sale_id . " unlocked successfully.";
                    $completed_transactions = []; // Refresh list
                }
            } catch (Exception $e) {
                $msg = "❌ Error: " . $e->getMessage();
            }
        }

        // Approve / Reject
        if ($action && in_array($action, ['approve', 'reject'])) {
            try {
                // Password verification required
                if (!isset($_SESSION['pos_verified'])) {
                    $error = 'Password verification required to approve/reject transactions.';
                } else {
                    $role = role_key($me['role'] ?? '');
                    if ($role === 'admin') {
                        // Admin must verify using manager password
                        $stmt = $pdo->prepare("SELECT password FROM users WHERE station_id = ? AND status = 'active' AND role IN ('manager','Manager')");
                        $stmt->execute([$station_id]);
                        $hashes = $stmt->fetchAll(PDO::FETCH_COLUMN);

                        $ok = false;
                        foreach ($hashes as $hash) {
                            if (password_verify($_POST['verify_password'] ?? '', $hash)) { $ok = true; break; }
                        }

                        if (!$ok) {
                            $error = 'Incorrect password.';
                        }
                    } elseif ($role === 'superadmin') {
                        $ok = true;
                    } else {
                        $error = 'Only Admin can approve/reject transactions.';
                    }
                }

                if (!isset($error) && $ok) {
                    $new_status = ($action === 'approve') ? 'Completed' : 'Rejected';
                    $stmt = $pdo->prepare("UPDATE sales SET status = ?, is_locked = ? WHERE id = ?");
                    $stmt->execute([$new_status, 1, $sale_id]);

                    $action_verb = ($action === 'approve') ? 'Approved' : 'Rejected';
                    log_activity($pdo, $me['id'], "Transaction $action_verb", "$action_verb transaction #$sale_id");

                    $msg = "✅ Transaction #$sale_id has been " . strtolower($action_verb) . ".";
                }
            } catch (Exception $e) {
                $msg = "❌ Error: " . $e->getMessage();
            }
        }
    }
    // STAFF ACTION: Create Multi-Item Transaction
    else {
         $customer_name = $_POST['customer_name'] ?? 'Walk-in';
         
         // Parse items from JSON
         $items_raw = $_POST['items'] ?? '[]';
         if (is_string($items_raw)) {
             $items = json_decode($items_raw, true) ?? [];
         } else {
             $items = $items_raw;
         }
         
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
                  $validation_error = '';
                  
                   // Validate and process each item
                   foreach ($items as $item) {
                       if ($validation_error) break; // Exit loop if error found
                       
                       $product_id = (int)($item['product_id'] ?? 0);
                       $quantity = (int)($item['quantity'] ?? 0);
                       
                       if ($product_id <= 0 || $quantity <= 0) {
                           $validation_error = "❌ Error: Invalid product or quantity.";
                           break;
                       }
                       
                       // Get product details
                       $stmt = $pdo->prepare("SELECT p.id as product_id, p.name, pt.name as type_name, si.unit, p.price, p.type_id FROM products p INNER JOIN product_types pt ON p.type_id = pt.id INNER JOIN station_inventory si ON p.id = si.product_id AND si.station_id = ? WHERE p.id = ?");
                       $stmt->execute([$station_id, $product_id]);
                       $product = $stmt->fetch(PDO::FETCH_ASSOC);
                       
                       if (!$product) {
                           $validation_error = "❌ Error: Product not found (ID: $product_id)";
                           break;
                       }
                       
                       // Check stock availability
                       $stmt = $pdo->prepare("SELECT stock_level FROM station_inventory WHERE product_id = ? AND station_id = ?");
                       $stmt->execute([$product_id, $station_id]);
                       $stock = $stmt->fetchColumn();
                       
                       if ($stock === null || $stock === false || $stock < $quantity) {
                           $validation_error = "❌ Error: Insufficient stock for {$product['name']}. Available: {$stock} {$product['unit']}. Requested: {$quantity} {$product['unit']}.";
                           break;
                       }
                       
                       $item_price = $product['price'];
                       $item_total = $quantity * $item_price;
                       $total += $item_total;
                       
                       $item_details[] = [
                           'product_id' => $product['product_id'],
                           'name' => $product['name'],
                           'type_name' => $product['type_name'],
                           'quantity' => $quantity,
                           'price' => $item_price,
                           'unit_price' => $item_price,
                           'total' => $item_total,
                           'unit' => $product['unit']
                       ];
                    }
                   
                   // Apply discount to total
                   $final_total = $total - $discount;
                   
                   if ($validation_error) {
                       $msg = $validation_error;
                       $pdo->rollBack();
                   } elseif ($payment_type === 'GCash' && empty($gcash_ref_number)) {
                       $msg = "❌ Error: GCash reference number is required for GCash payments.";
                       $pdo->rollBack();
                   } else {
                        // Insert Sale
                        $sale_id = uniqid('SALE-');
                        $stmt = $pdo->prepare("INSERT INTO sales (id, station_id, user_id, customer, sale_date, sale_time, payment_method, total, gcash_ref_number, status, created_at) VALUES (?, ?, ?, ?, CURDATE(), CURTIME(), ?, ?, ?, 'Completed', NOW())");
                        $stmt->execute([$sale_id, $station_id, $me['id'], $customer_name, $payment_type, $final_total, $gcash_ref_number]);
                       $last_sale_id = $sale_id;
                       
                        // Insert each item and deduct stock
                        foreach ($item_details as $item) {
                            // Insert Item
                            $stmtItem = $pdo->prepare("INSERT INTO sale_items (sale_id, product_id, name, quantity, unit_price, total_amount) VALUES (?, ?, ?, ?, ?, ?)");
                            $stmtItem->execute([$sale_id, $item['product_id'], $item['name'], $item['quantity'], $item['unit_price'], $item['total']]);
                           
                           // Deduct inventory stock immediately
                           $stmtStock = $pdo->prepare("UPDATE station_inventory SET stock_level = stock_level - ? WHERE product_id = ? AND station_id = ?");
                           $stmtStock->execute([$item['quantity'], $item['product_id'], $station_id]);
                       }
                       
                       $pdo->commit();
                       $msg = "✅ Multi-item transaction completed successfully. Stock deducted immediately.";
                   }
               } catch (Exception $e) {
                   if ($pdo->inTransaction()) {
                       $pdo->rollBack();
                   }
                   $msg = "❌ Error: " . $e->getMessage();
               }
          }
      }
}

// Fetch customers for autocomplete
$customers = [];
try {
    $customers = $pdo->query("SELECT name FROM customers ORDER BY name")->fetchAll(PDO::FETCH_COLUMN);
} catch(Exception $e){}

// Load merchandise inventory for POS dropdown
$inventory = [];
try {
    // Load merchandise products from inventory
    $stmt = $pdo->prepare("
        SELECT p.id, p.name, p.type_id, p.price, p.cost, p.sku, si.stock_level, si.unit, si.status as inventory_status
        FROM products p
        INNER JOIN product_types pt ON p.type_id = pt.id
        INNER JOIN station_inventory si ON p.id = si.product_id AND si.station_id = ? AND si.status = 'active'
        WHERE pt.name = 'merch'
        ORDER BY p.name
    ");
    $stmt->execute([$station_id]);
    $merchProducts = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $inventory = [
        'merch' => $merchProducts
    ];
     
} catch (Exception $e) {
     $inventory = ['merch' => []];
}

// Fetch Recent Completed Transactions for Admin Review
$recent_transactions = [];
if ($isAdmin) {
    try {
        // Get recent completed sales with staff name and item summary
        $sql = "SELECT s.*, u.name as staff_name,
                (SELECT GROUP_CONCAT(CONCAT(name, ' (', qty, ')') SEPARATOR ', ') FROM sale_items WHERE sale_id = s.id) as items_summary,
                (SELECT SUM(qty) FROM sale_items WHERE sale_id = s.id) as total_qty
                FROM sales s
                LEFT JOIN users u ON s.user_id = u.id
                WHERE s.status = 'Completed' AND s.station_id = ? AND DATE(s.created_at) = CURDATE()
                ORDER BY s.created_at DESC
                LIMIT 20";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$station_id]);
        $recent_transactions = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {}
}

// Fetch Completed Transactions for Admin Unlock
$completed_transactions = [];
if ($isAdmin) {
    try {
        // Get completed sales with staff name and item summary
        $sql = "SELECT s.*, u.name as staff_name,
                (SELECT GROUP_CONCAT(CONCAT(name, ' (', qty, ')') SEPARATOR ', ') FROM sale_items WHERE sale_id = s.id) as items_summary,
                (SELECT SUM(qty) FROM sale_items WHERE sale_id = s.id) as total_qty
                FROM sales s
                LEFT JOIN users u ON s.user_id = u.id
                WHERE s.status = 'Completed' AND s.is_locked = 1 AND s.station_id = ?
                ORDER BY s.finalized_at DESC";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$station_id]);
        $completed_transactions = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {}
}

include __DIR__ . '/../partials/header.php';
?>

<div class="page-head">
    <div>
        <h1 class="h1"><?php echo $isAdmin ? 'Transaction Overview' : 'New Transaction'; ?></h1>
        <div class="sub"><?php echo $isAdmin ? 'Monitor recent completed transactions' : 'Create a new point of sale transaction'; ?></div>
    </div>
</div>

<?php if($msg): ?>
<div id="toast" class="toast show" style="background: <?php echo strpos($msg, 'Error')!==false ? '#dc3545' : '#28a745'; ?>; position: fixed; bottom: 100px; left: 50%; transform: translateX(-50%); padding: 16px 20px; border-radius: 8px; color: white; font-weight: 500; z-index: 1000; max-width: 500px;">
    <?php echo $msg; ?>
</div>
<script>setTimeout(() => { const el = document.getElementById('toast'); if(el) el.remove(); }, <?php echo strpos($msg, 'Error')!==false ? '8000' : '3000'; ?>);</script>
<?php endif; ?>

<?php if ($isAdmin && !isset($_SESSION['pos_verified'])): ?>
<div class="card" style="max-width: 400px; margin: 40px auto; padding: 30px;">
    <h3 class="h3" style="text-align: center; margin-bottom: 20px;"><i class="fas fa-lock"></i> Security Check</h3>
    <p style="text-align: center; color: #666; margin-bottom: 20px;">
        Admin privileges required. Please enter your password to approve/reject transactions.
    </p>

    <?php if (isset($error)): ?>
        <div style="background: #f8d7da; color: #721c24; padding: 10px; border-radius: 4px; margin-bottom: 15px; text-align: center;">
            <?php echo htmlspecialchars($error); ?>
        </div>
    <?php endif; ?>

    <form method="post">
        <div style="margin-bottom: 20px;">
            <input type="password" name="verify_password" class="inp" style="width: 100%; padding: 12px;" placeholder="Enter Password" required autofocus>
        </div>
        <button type="submit" name="verify" class="btn primary" style="width: 100%;">Verify & Continue</button>
    </form>
</div>
<?php endif; ?>

<?php if ($isAdmin): ?>
<!-- ADMIN VIEW: Recent Transactions Table -->
<div class="card" style="padding: 0;">
    <div style="padding: 20px; border-bottom: 1px solid #e5e7eb;">
        <h3 style="margin: 0; color: #003d7a;">Today's Completed Transactions</h3>
    </div>
    <div class="table-wrap">
        <table class="table">
            <thead>
                <tr>
                    <th>Transaction ID</th>
                    <th>Customer</th>
                    <th>Product Summary</th>
                    <th>Qty/Liters</th>
                    <th>Total Amount</th>
                    <th>Payment</th>
                    <th>Staff Encoder</th>
                    <th>Status</th>
                    <th>Time</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($recent_transactions)): ?>
                    <tr><td colspan="9" style="text-align:center; padding:30px; color:#666;">No transactions completed today.</td></tr>
                <?php else: ?>
                    <?php foreach($recent_transactions as $t): ?>
                    <tr>
                        <td>#<?php echo htmlspecialchars($t['id']); ?></td>
                        <td><b><?php echo htmlspecialchars($t['customer']); ?></b></td>
                        <td><?php echo htmlspecialchars(mb_strimwidth($t['items_summary'], 0, 40, "...")); ?></td>
                        <td><?php echo number_format($t['total_qty'], 2); ?></td>
                        <td style="font-weight:bold; color:var(--petron-blue);">₱<?php echo number_format($t['total'], 2); ?></td>
                        <td><span class="badge"><?php echo htmlspecialchars($t['payment_method']); ?></span></td>
                        <td><?php echo htmlspecialchars($t['staff_name']); ?></td>
                        <td><span class="badge" style="background:#d1fae5; color:#065f46;">Completed</span></td>
                        <td>
                            <div style="display:flex; gap:5px;">
                                <button class="btn small ghost" onclick="viewTransaction(<?php echo htmlspecialchars(json_encode($t)); ?>)" title="View Details">👁️</button>
                                <span style="font-size:11px; color:#666;"><?php echo date('g:i A', strtotime($t['created_at'])); ?></span>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- ADMIN VIEW: Completed Transactions for Unlock -->
<?php if (!empty($completed_transactions)): ?>
<div class="card" style="padding: 0; margin-top: 30px;">
    <div style="padding: 20px; border-bottom: 1px solid #e5e7eb;">
        <h3 style="margin: 0; color: #003d7a;">Completed Transactions (Locked)</h3>
    </div>
    <div class="table-wrap">
        <table class="table">
            <thead>
                <tr>
                    <th>Transaction ID</th>
                    <th>Customer</th>
                    <th>Product Summary</th>
                    <th>Total Amount</th>
                    <th>Payment</th>
                    <th>Staff Encoder</th>
                    <th>Finalized</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach($completed_transactions as $t): ?>
                <tr>
                    <td>#<?php echo htmlspecialchars($t['id']); ?></td>
                    <td><b><?php echo htmlspecialchars($t['customer']); ?></b></td>
                    <td><?php echo htmlspecialchars(mb_strimwidth($t['items_summary'], 0, 40, "...")); ?></td>
                    <td style="font-weight:bold; color:var(--petron-blue);">₱<?php echo number_format($t['total'], 2); ?></td>
                    <td><span class="badge"><?php echo htmlspecialchars($t['payment_method']); ?></span></td>
                    <td><?php echo htmlspecialchars($t['staff_name']); ?></td>
                    <td><?php echo date('M d, H:i', strtotime($t['finalized_at'] ?? $t['created_at'])); ?></td>
                    <td>
                        <button type="button" class="btn small primary" onclick="openUnlockModal('<?php echo htmlspecialchars($t['id']); ?>', '<?php echo htmlspecialchars($t['customer']); ?>')">
                            <i class="fas fa-unlock"></i> Unlock
                        </button>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php endif; ?>

<!-- Modal (View Transaction) -->
<div class="modal" id="viewTransModal">
    <div class="modal-content" style="max-width: 600px;">
        <div class="modal-header">
            <h3 class="modal-title">Transaction Details</h3>
            <button class="modal-close" onclick="closeModal('viewTransModal')">&times;</button>
        </div>
        <div class="modal-body" id="viewTransContent">
            <!-- Populated by JS -->
        </div>
        <div class="modal-footer">
            <button class="btn ghost" onclick="closeModal('viewTransModal')">Close</button>
        </div>
    </div>
</div>

<script>
function viewTransaction(t) {
    const content = `
        <div style="display:grid; grid-template-columns: 1fr 1fr; gap:15px; margin-bottom:20px; background:#f8f9fa; padding:15px; border-radius:8px;">
            <div>
                <small class="text-muted">Transaction ID</small>
                <div style="font-weight:bold;">#${t.id}</div>
            </div>
            <div>
                <small class="text-muted">Customer</small>
                <div style="font-weight:bold;">${t.customer}</div>
            </div>
            <div>
                <small class="text-muted">Staff Encoder</small>
                <div>${t.staff_name}</div>
                <small class="text-muted">${t.created_at}</small>
            </div>
             <div>
                 <small class="text-muted">Payment Type</small>
                 <div>${t.payment_method}</div>
                 ${t.payment_method === 'GCash' && t.gcash_ref_number ? `<small class="text-muted">Ref: ${t.gcash_ref_number}</small>` : ''}
             </div>
        </div>
        
        <h4 style="margin-bottom:10px; border-bottom:1px solid #eee; padding-bottom:5px;">Product Breakdown</h4>
        <table class="table" style="font-size:0.9em;">
            <thead>
                <tr>
                    <th>Product / Category</th>
                    <th class="right">Qty/Liters</th>
                    <th class="right">Unit Price</th>
                    <th class="right">Subtotal</th>
                </tr>
            </thead>
            <tbody>
                <!-- In a real app, we'd fetch items via AJAX. For now, parsing summary or showing placeholder if simple -->
                <tr>
                    <td>${t.items_summary}</td>
                    <td class="right">${t.total_qty}</td>
                    <td class="right">-</td>
                    <td class="right"><b>₱${parseFloat(t.total).toLocaleString(undefined, {minimumFractionDigits:2})}</b></td>
                </tr>
            </tbody>
        </table>
        
        <div style="margin-top:20px; text-align:right;">
            <span style="font-size:1.2em; font-weight:bold;">Total: ₱${parseFloat(t.total).toLocaleString(undefined, {minimumFractionDigits:2})}</span>
        </div>
        
        <div style="margin-top:20px; display:flex; gap:10px; justify-content:flex-end; border-top:1px solid #eee; padding-top:15px;">
            <form method="post" style="flex:1;">
                <input type="hidden" name="sale_id" value="${t.id}">
                <input type="hidden" name="action" value="reject">
                <button type="submit" class="btn danger full">Reject ❌</button>
            </form>
            <form method="post" style="flex:1;">
                <input type="hidden" name="sale_id" value="${t.id}">
                <input type="hidden" name="action" value="approve">
                <button type="submit" class="btn primary full">Approve ✅</button>
            </form>
        </div>
    `;
    document.getElementById('viewTransContent').innerHTML = content;
    document.getElementById('viewTransModal').classList.add('show');
}

function closeModal(id) {
    document.getElementById(id).classList.remove('show');
}
</script>


<?php else: ?>
<!-- STAFF VIEW: Multi-Item POS Form -->
<div class="card" style="padding: 20px; max-width: 1200px; margin: 0 auto;">
    <form method="post" id="posForm">
        <!-- Customer Information -->
        <div class="form-group mb-3">
            <label class="lbl">Customer Name</label>
            <input type="text" name="customer_name" list="customerList" class="inp full" placeholder="Walk-in" required>
            <datalist id="customerList">
                <?php foreach($customers as $c): ?>
                    <option value="<?php echo htmlspecialchars($c); ?>">
                <?php endforeach; ?>
            </datalist>
        </div>
        
        <!-- Transaction Items Section -->
        <div class="card" style="padding: 20px; margin: 20px 0; background: #f8f9fa;">
            <h3 style="margin: 0 0 20px 0; color: #003d7a;">Transaction Items</h3>
            
            <!-- Add New Item Section -->
            <div style="display: grid; grid-template-columns: 1fr 2fr 1fr auto; gap: 15px; margin-bottom: 20px; padding-bottom: 20px; border-bottom: 1px dashed #ccc;">
                <div>
                    <div class="form-group mb-3">
                        <label class="lbl">Type</label>
                        <select id="add_product_type" class="inp full" onchange="loadProductsMulti()">
                            <option value="">Select Type</option>
                            <option value="merch">Merchandise</option>
                        </select>
                    </div>
                </div>
                <div>
                    <div class="form-group mb-3">
                        <label class="lbl">Product</label>
                        <select name="new_product_id" id="add_product_id" class="inp full" onchange="updateProductInfoMulti()">
                            <option value="">Select Product</option>
                        </select>
                        <small class="muted" id="add_stock_info"></small>
                    </div>
                </div>
                <div>
                    <div class="form-group mb-3">
                        <label class="lbl">Quantity</label>
                        <input type="number" id="add_quantity" class="inp full" min="1" value="1">
                    </div>
                </div>
                <div style="display: flex; align-items: flex-end;">
                    <button type="button" onclick="addItemMulti()" class="btn primary">
                        <i class="fas fa-plus"></i> Add
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
                    <div style="font-size: 0.9em; color: #666;" id="discount_display">- ₱0.00</div>
                    <div style="font-size: 0.9em; color: #666; margin-top: 10px;">Grand Total</div>
                    <div style="font-size: 2em; font-weight: bold; color: var(--petron-blue);" id="displayTotal">₱0.00</div>
                </div>
            </div>
        </div>
        
        <!-- Hidden field for items JSON -->
        <input type="hidden" name="items" id="items_json">
        
        <div class="actions" style="margin-top: 30px; display: flex; gap: 10px; justify-content: flex-end;">
            <button type="button" class="btn ghost" onclick="clearForm()">Clear All</button>
            <button type="submit" class="btn primary" onclick="return validateMultiPayment();">Save Transaction</button>
        </div>
    </form>
</div>
<?php endif; ?>

<!-- Receipt Modal -->
<?php if($last_sale_id): ?>
<div class="modal show" id="receiptModal">
    <div class="modal-content" style="max-width: 400px; text-align: center;">
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
    /* Multi-Item POS Styles */
    .item-row {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 15px;
        margin-bottom: 10px;
        background: white;
        border: 1px solid #e0e0e0;
        border-radius: 8px;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
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
    .grid-2 { display: grid; grid-template-columns: 1fr 1fr; }
    .inp.full { width: 100%; }
    .mb-3 { margin-bottom: 1rem; }
    @media (max-width: 768px) { .grid-2 { grid-template-columns: 1fr; } }
</style>

<script>
// Multi-Item POS JavaScript
const inventoryData = <?php echo json_encode($inventory); ?>;
let items = [];
let itemIdCounter = 0;

// Load products based on type selection for multi-item
function loadProductsMulti() {
    const typeSelect = document.getElementById('add_product_type');
    const productSelect = document.getElementById('add_product_id');
    const stockInfo = document.getElementById('add_stock_info');
    const type = typeSelect.value;
    
    productSelect.innerHTML = '<option value="">Select Product</option>';
    
    if (type && inventoryData[type]) {
        inventoryData[type].forEach(product => {
            const option = document.createElement('option');
            option.value = product.id;
            
            const stockLevel = parseFloat(product.stock_level) || 0;
            const stockClass = stockLevel <= 0 ? 'color: #dc3545; font-weight: bold;' : '';
            const stockText = stockLevel <= 0 ? ' (OUT OF STOCK)' : ` (Stock: ${stockLevel} ${product.unit || 'pc'})`;
            
            option.textContent = `${product.name}${stockText}`;
            option.dataset.price = product.price || 0;
            option.dataset.stock = stockLevel;
            option.dataset.unit = product.unit || '';
            option.style = stockClass;
            
            productSelect.appendChild(option);
        });
        
        stockInfo.textContent = `${inventoryData[type].length} products available`;
    } else {
        stockInfo.textContent = 'Select a product type first';
    }
}

// Update product info (no fuel logic needed)
function updateProductInfoMulti() {
    // For merchandise products, no additional actions needed
    // This function is kept for future extensibility
}

// Add item to cart
function addItemMulti() {
    const typeSelect = document.getElementById('add_product_type');
    const productSelect = document.getElementById('add_product_id');
    const quantityInput = document.getElementById('add_quantity');
    const selectedOption = productSelect.options[productSelect.selectedIndex];
    const type = typeSelect.value;
    
    // Validation
    if (!type) {
        alert('Please select a product type first.');
        return;
    }
    
    if (!selectedOption || !selectedOption.value) {
        alert('Please select a product first.');
        return;
    }
    
    const quantity = parseInt(quantityInput.value) || 0;
    if (quantity <= 0) {
        alert('Quantity must be greater than 0.');
        return;
    }
    
    const stockLevel = parseFloat(selectedOption.dataset.stock) || 0;
    if (stockLevel <= 0) {
        alert('This product is out of stock. Please select a different product.');
        return;
    }
    
    if (quantity > stockLevel) {
        alert(`Insufficient stock! Available: ${stockLevel} ${selectedOption.dataset.unit || 'pc'}`);
        return;
    }
    
    // Check if item already exists in cart
    const existingItemIndex = items.findIndex(item => item.product_id === parseInt(selectedOption.value));
    if (existingItemIndex !== -1) {
        // Update existing item quantity
        const newQuantity = items[existingItemIndex].quantity + quantity;
        if (newQuantity > stockLevel) {
            alert(`Cannot add ${quantity} more. Total would exceed available stock (${stockLevel} ${selectedOption.dataset.unit || 'pc'})`);
            return;
        }
        items[existingItemIndex].quantity = newQuantity;
    } else {
        // Add new item
        const newItem = {
            id: ++itemIdCounter,
            product_id: parseInt(selectedOption.value),
            name: selectedOption.textContent.split(' (')[0].trim(),
            price: parseFloat(selectedOption.dataset.price) || 0,
            unit: selectedOption.dataset.unit || '',
            stock_level: stockLevel,
            quantity: quantity,
            type: 'merch'
        };
        items.push(newItem);
    }
    
    // Reset form
    document.getElementById('add_product_type').value = '';
    document.getElementById('add_product_id').innerHTML = '<option value="">Select Product</option>';
    document.getElementById('add_quantity').value = '1';
    document.getElementById('add_stock_info').textContent = '';
    
    renderItems();
    calculateGrandTotal();
}

// Render items in the cart
function renderItems() {
    const container = document.getElementById('items-container');
    
    if (items.length === 0) {
        container.innerHTML = '<p style="text-align: center; color: #666; padding: 20px;">No items added yet. Select products and click "Add" to build your transaction.</p>';
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
                               min="1" max="${item.stock_level}" class="item-qty">
                    </div>
                    <input type="number" value="${item.price.toFixed(2)}" 
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

// Update item quantity
function updateItem(index, field, value) {
    const item = items[index];
    
    if (field === 'quantity') {
        const newQty = parseInt(value) || 1;
        
        if (newQty > item.stock_level) {
            alert(`Insufficient stock! Available: ${item.stock_level} ${item.unit}. Requested: ${newQty} ${item.unit}.`);
            renderItems(); // Reset to previous value
            return;
        }
        
        if (newQty <= 0) {
            removeItem(index);
            return;
        }
        
        item.quantity = newQty;
    }
    
    renderItems();
}

// Remove item from cart
function removeItem(index) {
    items.splice(index, 1);
    renderItems();
}

// Clear all items
function clearForm() {
    if (items.length > 0 && !confirm('Are you sure you want to clear all items?')) {
        return;
    }
    items = [];
    renderItems();
    document.getElementById('add_product_type').value = '';
    document.getElementById('add_product_id').innerHTML = '<option value="">Select Product</option>';
    document.getElementById('add_stock_info').textContent = '';
    calculateGrandTotal();
}

// Calculate grand total
function calculateGrandTotal() {
    const discount = parseFloat(document.getElementById('discount').value) || 0;
    let itemsTotal = 0;
    
    items.forEach(item => {
        itemsTotal += item.quantity * item.price;
    });
    
    const grandTotal = itemsTotal - discount;
    
    document.getElementById('discount_display').textContent = `- ₱${discount.toFixed(2)}`;
    document.getElementById('displayTotal').textContent = `₱${grandTotal.toFixed(2)}`;
}

// Toggle GCash reference field
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

// Validate multi-item payment
function validateMultiPayment() {
    // Get form elements
    const customerName = document.querySelector('input[name="customer_name"]').value.trim();
    const paymentType = document.getElementById('payment_method_pos').value;
    const gcashRefInput = document.getElementById('gcash_ref_number');
    
    // Validate customer name
    if (!customerName) {
        alert('Customer name is required. Please enter a customer name or "Walk-in".');
        return false;
    }
    
    // Validate items
    if (items.length === 0) {
        alert('Please add at least one item to the transaction.');
        return false;
    }
    
    // Validate payment type
    if (!paymentType) {
        alert('Payment method is required. Please select Cash or GCash.');
        return false;
    }
    
    // Validate GCash reference if GCash is selected
    if (paymentType === 'GCash') {
        if (!gcashRefInput.value.trim()) {
            alert('GCash reference number is required for GCash payments.');
            return false;
        }
        
        // Validate GCash reference format (at least 5 characters, alphanumeric)
        const gcashRef = gcashRefInput.value.trim();
        if (gcashRef.length < 5) {
            alert('GCash reference number must be at least 5 characters long.');
            return false;
        }
        
        if (!/^[a-zA-Z0-9]+$/.test(gcashRef)) {
            alert('GCash reference number must contain only letters and numbers.');
            return false;
        }
    }
    
    // Set items JSON for form submission
    document.getElementById('items_json').value = JSON.stringify(items);
    
    return true;
}

function openUnlockModal(saleId, customerName) {
    const reason = prompt('Reason for unlocking transaction #' + saleId + ' (' + customerName + '):\n\nRequired for audit trail (minimum 10 characters)');
    if (reason && reason.length >= 10) {
        const password = prompt('Enter your password to confirm unlock:');
        if (password) {
            const form = document.createElement('form');
            form.method = 'post';
            form.innerHTML = `
                <input type="hidden" name="action" value="unlock">
                <input type="hidden" name="sale_id" value="${saleId}">
                <input type="hidden" name="unlock_reason" value="${reason}">
                <input type="hidden" name="unlock_password" value="${password}">
            `;
            document.body.appendChild(form);
            form.submit();
        }
    } else if (reason && reason.length > 0) {
        alert('Reason must be at least 10 characters for audit trail compliance.');
    }
}

</script>

<?php include __DIR__ . '/../partials/footer.php'; ?>
