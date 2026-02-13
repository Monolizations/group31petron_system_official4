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
    // STAFF ACTION: Create Transaction
    else {
    $customer_name = $_POST['customer_name'] ?? 'Walk-in';
    $item_name = $_POST['item_name'] ?? '';
    $quantity = (int)($_POST['quantity'] ?? 1);
    $price = (float)($_POST['price'] ?? 0);
    $payment_type = $_POST['payment_type'] ?? 'Cash';
    $gcash_ref_number = trim($_POST['gcash_ref_number'] ?? '');
    $discount = (float)($_POST['discount'] ?? 0);
    
    $subtotal = $quantity * $price;
    $total = $subtotal - $discount;

    // Validation: GCash requires reference number
    if ($payment_type === 'GCash' && empty($gcash_ref_number)) {
        $msg = "❌ Error: GCash reference number is required for GCash payments.";
    } elseif ($item_name && $quantity > 0 && $price >= 0) {
        try {
            $pdo->beginTransaction();

            // Insert Sale - Status is 'Pending' for Staff, 'Completed' for Admin (if they encode directly)
            // Per requirement: Admin validates staff entries. Staff entries are Pending.
            $initial_status = $isAdmin ? 'Completed' : 'Pending';
            $sale_id = uniqid('SALE-');
            $is_locked = $isAdmin ? 1 : 0;

            $stmt = $pdo->prepare("INSERT INTO sales (id, station_id, user_id, customer, payment_method, total, sale_date, created_at, status, is_locked, gcash_ref_number) VALUES (?, ?, ?, ?, ?, ?, CURDATE(), NOW(), ?, ?, ?)");
            $stmt->execute([$sale_id, $station_id, $me['id'], $customer_name, $payment_type, $total, $initial_status, $is_locked, ($payment_type === 'GCash' ? $gcash_ref_number : null)]);
            $last_sale_id = $sale_id;

            // Insert Item
            $stmtItem = $pdo->prepare("INSERT INTO sale_items (sale_id, name, qty, price, amount) VALUES (?, ?, ?, ?, ?)");
            $stmtItem->execute([$sale_id, $item_name, $quantity, $price, $subtotal]);

            $pdo->commit();
            $msg = $isAdmin ? "✅ Transaction completed successfully." : "✅ Transaction submitted for approval.";
        } catch (Exception $e) {
            $pdo->rollBack();
            $msg = "❌ Error: " . $e->getMessage();
        }
    } else {
        $msg = "❌ Error: Invalid item details.";
    }
    }
}

// Fetch customers for autocomplete
$customers = [];
try {
    $customers = $pdo->query("SELECT name FROM customers ORDER BY name")->fetchAll(PDO::FETCH_COLUMN);
} catch(Exception $e){}

// Fetch Pending Transactions for Admin
$pending_transactions = [];
if ($isAdmin) {
    try {
        // Get pending sales with staff name and item summary
        $sql = "SELECT s.*, u.name as staff_name,
                (SELECT GROUP_CONCAT(CONCAT(name, ' (', qty, ')') SEPARATOR ', ') FROM sale_items WHERE sale_id = s.id) as items_summary,
                (SELECT SUM(qty) FROM sale_items WHERE sale_id = s.id) as total_qty
                FROM sales s
                LEFT JOIN users u ON s.user_id = u.id
                WHERE s.status = 'Pending' AND s.station_id = ? AND s.is_locked = 0
                ORDER BY s.created_at DESC";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$station_id]);
        $pending_transactions = $stmt->fetchAll(PDO::FETCH_ASSOC);
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
        <h1 class="h1"><?php echo $isAdmin ? 'Transaction Review' : 'New Transaction'; ?></h1>
        <div class="sub"><?php echo $isAdmin ? 'Validate and approve staff entries' : 'Create a new point of sale transaction'; ?></div>
    </div>
</div>

<?php if($msg): ?>
<div id="toast" class="toast show" style="background: <?php echo strpos($msg, 'Error')!==false ? '#dc3545' : '#28a745'; ?>;">
    <?php echo $msg; ?>
</div>
<script>setTimeout(() => document.getElementById('toast').remove(), 3000);</script>
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
<!-- ADMIN VIEW: Pending Transactions Table -->
<div class="card" style="padding: 0;">
    <div style="padding: 20px; border-bottom: 1px solid #e5e7eb;">
        <h3 style="margin: 0; color: #003d7a;">Pending Transactions</h3>
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
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($pending_transactions)): ?>
                    <tr><td colspan="9" style="text-align:center; padding:30px; color:#666;">No pending transactions to review.</td></tr>
                <?php else: ?>
                    <?php foreach($pending_transactions as $t): ?>
                    <tr>
                        <td>#<?php echo htmlspecialchars($t['id']); ?></td>
                        <td><b><?php echo htmlspecialchars($t['customer']); ?></b></td>
                        <td><?php echo htmlspecialchars(mb_strimwidth($t['items_summary'], 0, 40, "...")); ?></td>
                        <td><?php echo number_format($t['total_qty'], 2); ?></td>
                        <td style="font-weight:bold; color:var(--petron-blue);">₱<?php echo number_format($t['total'], 2); ?></td>
                        <td><span class="badge"><?php echo htmlspecialchars($t['payment_method']); ?></span></td>
                        <td><?php echo htmlspecialchars($t['staff_name']); ?></td>
                        <td><span class="badge" style="background:#fff3cd; color:#856404;">Pending</span></td>
                        <td>
                            <div style="display:flex; gap:5px;">
                                <button class="btn small ghost" onclick="viewTransaction(<?php echo htmlspecialchars(json_encode($t)); ?>)" title="View Details">👁️</button>
                                <form method="post" style="display:inline;">
                                    <input type="hidden" name="sale_id" value="<?php echo $t['id']; ?>">
                                    <input type="hidden" name="action" value="approve">
                                    <button type="submit" class="btn small primary" title="Approve">✅</button>
                                </form>
                                <form method="post" style="display:inline;" onsubmit="return confirm('Reject this transaction?');">
                                    <input type="hidden" name="sale_id" value="<?php echo $t['id']; ?>">
                                    <input type="hidden" name="action" value="reject">
                                    <button type="submit" class="btn small danger" title="Reject">❌</button>
                                </form>
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
<!-- STAFF VIEW: Encoding Form -->
<div class="card" style="padding: 20px; max-width: 900px; margin: 0 auto;">
    <form method="post" id="posForm">
        <div class="grid-2" style="gap: 30px;">
            <!-- Left Column -->
            <div>
                <div class="form-group mb-3">
                    <label class="lbl">Customer Name</label>
                    <input type="text" name="customer_name" list="customerList" class="inp full" placeholder="Walk-in" required>
                    <datalist id="customerList">
                        <?php foreach($customers as $c): ?>
                            <option value="<?php echo htmlspecialchars($c); ?>">
                        <?php endforeach; ?>
                    </datalist>
                </div>
                
                <div class="form-group mb-3">
                    <label class="lbl">Item / Service</label>
                    <input type="text" name="item_name" class="inp full" placeholder="e.g. Diesel, Oil Change" required>
                </div>
                
                <div class="form-group mb-3">
                    <label class="lbl">Quantity</label>
                    <input type="number" name="quantity" class="inp full" value="1" min="1" required oninput="calcTotal()">
                </div>
            </div>
            
            <!-- Right Column -->
            <div>
                <div class="form-group mb-3">
                    <label class="lbl">Price</label>
                    <input type="number" name="price" class="inp full" step="0.01" placeholder="0.00" required oninput="calcTotal()">
                </div>
                
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
                
                <div class="form-group mb-3">
                    <label class="lbl">Discount</label>
                    <input type="number" name="discount" class="inp full" step="0.01" value="0" oninput="calcTotal()">
                </div>
                
                <div class="total-display" style="margin-top: 20px; padding: 15px; background: #f8f9fa; border-radius: 8px; text-align: right;">
                    <div style="font-size: 0.9em; color: #666;">Total Amount</div>
                    <div style="font-size: 2em; font-weight: bold; color: var(--petron-blue);" id="displayTotal">₱0.00</div>
                </div>
            </div>
        </div>
        
        <div class="actions" style="margin-top: 30px; display: flex; gap: 10px; justify-content: flex-end;">
            <button id="btnClear" type="button" class="btn ghost" onclick="window.location.reload()">Cancel</button>
            <button id="btnPay" type="submit" class="btn primary" onclick="return validatePayment();">Save Transaction</button>
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

<script>
function calcTotal() {
    const qty = parseFloat(document.querySelector('[name=quantity]').value) || 0;
    const price = parseFloat(document.querySelector('[name=price]').value) || 0;
    const discount = parseFloat(document.querySelector('[name=discount]').value) || 0;
    const total = (qty * price) - discount;
    document.getElementById('displayTotal').innerText = '₱' + total.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2});
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

function validatePayment() {
    // Get form elements
    const customerName = document.querySelector('input[name="customer_name"]').value.trim();
    const itemName = document.querySelector('input[name="item_name"]').value.trim();
    const quantity = document.querySelector('input[name="quantity"]').value;
    const price = document.querySelector('input[name="price"]').value;
    const paymentType = document.getElementById('payment_method_pos').value;
    const gcashRefInput = document.getElementById('gcash_ref_number');
    
    // Validate customer name
    if (!customerName) {
        alert('⚠️ Customer name is required. Please enter a customer name or "Walk-in".');
        return false;
    }
    
    // Validate item name
    if (!itemName) {
        alert('⚠️ Item name is required. Please enter the product/service name.');
        return false;
    }
    
    // Validate quantity
    if (!quantity || parseFloat(quantity) <= 0) {
        alert('⚠️ Quantity must be greater than 0.');
        return false;
    }
    
    // Validate price
    if (!price || parseFloat(price) < 0) {
        alert('⚠️ Price must be a valid amount (0 or greater).');
        return false;
    }
    
    // Validate payment type
    if (!paymentType) {
        alert('⚠️ Payment method is required. Please select Cash or GCash.');
        return false;
    }
    
    // Validate GCash reference if GCash is selected
    if (paymentType === 'GCash') {
        if (!gcashRefInput.value.trim()) {
            alert('⚠️ GCash reference number is required for GCash payments.');
            return false;
        }
        
        // Validate GCash reference format (at least 5 characters, alphanumeric)
        const gcashRef = gcashRefInput.value.trim();
        if (gcashRef.length < 5) {
            alert('⚠️ GCash reference number must be at least 5 characters long.');
            return false;
        }
        
        if (!/^[a-zA-Z0-9]+$/.test(gcashRef)) {
            alert('⚠️ GCash reference number must contain only letters and numbers.');
            return false;
        }
    }
    
    return true;
}
</script>

<style>
    .grid-2 { display: grid; grid-template-columns: 1fr 1fr; }
    .inp.full { width: 100%; }
    .mb-3 { margin-bottom: 1rem; }
    @media (max-width: 768px) { .grid-2 { grid-template-columns: 1fr; } }
</style>

<?php include __DIR__ . '/../partials/footer.php'; ?>
