<?php
$page_id = 'customer_credit';
require_once __DIR__ . '/../backend/lib.php';
require_once __DIR__ . '/../public/db_connect.php';
require_login();

$me = current_user();
$isAdmin = in_array($me['role'], ['admin', 'superadmin', 'manager']);
$station_id = $me['station_id'] ?? 1;

// Get current view
$view = $_GET['view'] ?? 'ledger';

// Ensure ledger table exists
try {
    $pdo->exec("CREATE TABLE IF NOT EXISTS customer_ledger (
        id INT AUTO_INCREMENT PRIMARY KEY,
        customer_id INT NOT NULL,
        date DATE NOT NULL,
        reference_no VARCHAR(50),
        type ENUM('Debit', 'Credit', 'Adjustment') NOT NULL,
        amount DECIMAL(10,2) NOT NULL,
        balance DECIMAL(10,2) NOT NULL,
        remarks TEXT,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    )");
    
    // Add sample data if table is empty (for testing)
    $count = $pdo->query("SELECT COUNT(*) FROM customer_ledger")->fetchColumn();
    if ($count == 0) {
        // Get first customer for sample data
        $first_customer = $pdo->query("SELECT id, current_balance FROM customers LIMIT 1")->fetch();
        if ($first_customer) {
            $sample_data = [
                [$first_customer['id'], date('Y-m-d', strtotime('-30 days')), 'SALE-001', 'Debit', 1500.00, 1500.00, 'Fuel purchase'],
                [$first_customer['id'], date('Y-m-d', strtotime('-20 days')), 'SALE-002', 'Debit', 800.50, 2300.50, 'Service charge'],
                [$first_customer['id'], date('Y-m-d', strtotime('-15 days')), 'PAY-001', 'Credit', 1000.00, 1300.50, 'Partial payment'],
                [$first_customer['id'], date('Y-m-d', strtotime('-10 days')), 'SALE-003', 'Debit', 500.25, 1800.75, 'Oil change'],
                [$first_customer['id'], date('Y-m-d', strtotime('-5 days')), 'PAY-002', 'Credit', 800.75, 1000.00, 'Full payment'],
            ];
            
            foreach ($sample_data as $data) {
                $stmt = $pdo->prepare("INSERT INTO customer_ledger (customer_id, date, reference_no, type, amount, balance, remarks) VALUES (?, ?, ?, ?, ?, ?, ?)");
                $stmt->execute($data);
            }
        }
    }
} catch (Exception $e) {}

// Handle Manual Adjustment
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'adjustment' && $isAdmin) {
    $cust_id = $_POST['customer_id'];
    $type = $_POST['type']; // Debit (Charge) or Credit (Payment)
    $amount = (float)$_POST['amount'];
    $reason = $_POST['reason'];
    
    // Get current balance
    $stmt = $pdo->prepare("SELECT current_balance FROM customers WHERE id = ?");
    $stmt->execute([$cust_id]);
    $current_bal = $stmt->fetchColumn() ?: 0;
    
    $new_bal = ($type === 'Debit') ? $current_bal + $amount : $current_bal - $amount;
    
    try {
        $pdo->beginTransaction();
        // Update Customer
        $pdo->prepare("UPDATE customers SET current_balance = ? WHERE id = ?")->execute([$new_bal, $cust_id]);
        // Add Ledger Entry
        $stmt = $pdo->prepare("INSERT INTO customer_ledger (customer_id, date, reference_no, type, amount, balance, remarks) VALUES (?, CURDATE(), ?, ?, ?, ?, ?)");
        $stmt->execute([$cust_id, 'ADJ-' . time(), $type, $amount, $new_bal, $reason]);
        $pdo->commit();
    } catch (Exception $e) { $pdo->rollBack(); }
}

// Fetch Customers based on view
$customers = [];
if ($view === 'my_history' && !$isAdmin) {
    // Staff can only see customers they've interacted with
    $stmt = $pdo->prepare("
        SELECT DISTINCT c.* FROM customers c
        LEFT JOIN sales s ON c.id = s.customer_id AND s.user_id = ?
        LEFT JOIN job_orders jo ON c.id = jo.customer_id AND jo.assigned_by = ?
        WHERE (s.id IS NOT NULL OR jo.id IS NOT NULL) AND (c.station_id = ? OR c.station_id IS NULL)
        ORDER BY c.name ASC
    ");
    $stmt->execute([$me['id'], $me['id'], $station_id]);
} else {
    // Admin/Manager see all customers
    $customers = $pdo->query("SELECT id, name FROM customers ORDER BY name")->fetchAll();
}

// Filter Logic
$selected_cust = $_GET['id'] ?? ($customers[0]['id'] ?? 0);
$ledger = [];
$summary = ['charges'=>0, 'payments'=>0, 'balance'=>0];

if ($selected_cust) {
    // Fetch Ledger
    $stmt = $pdo->prepare("SELECT * FROM customer_ledger WHERE customer_id = ? ORDER BY date DESC, id DESC");
    $stmt->execute([$selected_cust]);
    $ledger = $stmt->fetchAll();
    
    // Calculate Summary
    foreach($ledger as $l) {
        if ($l['type'] === 'Debit') $summary['charges'] += $l['amount'];
        else $summary['payments'] += $l['amount'];
    }
    // Get live balance
    $stmt = $pdo->prepare("SELECT current_balance FROM customers WHERE id = ?");
    $stmt->execute([$selected_cust]);
    $summary['balance'] = $stmt->fetchColumn() ?: 0;
}

include __DIR__ . '/../partials/header.php';
?>
<style>
    .page-layout { height: 100%; display: flex; flex-direction: column; gap: 15px; }
    .layout-header { flex: 0 0 auto; }
    .layout-filter { flex: 0 0 auto; background: white; padding: 12px; border-radius: 8px; border: 1px solid #e0e0e0; display: flex; gap: 10px; align-items: center; }
    .layout-summary { flex: 0 0 auto; display: grid; grid-template-columns: repeat(3, 1fr); gap: 15px; }
    .layout-content { flex: 1 1 auto; background: white; border-radius: 8px; border: 1px solid #e0e0e0; overflow: hidden; display: flex; flex-direction: column; }
    .table-scroll { overflow-y: auto; flex: 1; }
    
    .summary-card { background: white; padding: 15px; border-radius: 8px; border: 1px solid #e0e0e0; text-align: center; }
    .summary-val { font-size: 24px; font-weight: bold; color: var(--petron-blue); }
    .summary-lbl { font-size: 12px; color: #666; text-transform: uppercase; }
</style>

<div class="page-layout">
    <!-- 1. HEADER -->
    <div class="layout-header">
        <?php if ($view === 'my_history'): ?>
            <h1 class="h1">My Credit History</h1>
            <div class="muted">Credit transactions for your customers</div>
        <?php else: ?>
            <h1 class="h1">Credit Ledger</h1>
            <div class="muted">Customer credit management and history</div>
        <?php endif; ?>
    </div>

    <!-- 2. FILTER BAR -->
    <div class="layout-filter">
        <form method="get" style="display:flex; gap:10px; width:100%;">
            <input type="hidden" name="view" value="<?php echo htmlspecialchars($view); ?>">
            <select name="id" class="inp" onchange="this.form.submit()" style="min-width: 250px;">
                <option value="">-- Select Customer --</option>
                <?php foreach($customers as $c): ?>
                    <option value="<?php echo $c['id']; ?>" <?php echo $selected_cust == $c['id'] ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($c['name']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <input type="date" class="inp" name="start">
            <input type="date" class="inp" name="end">
            <button type="submit" class="btn ghost">Filter</button>
        </form>
        <div style="flex:1;"></div>
        <?php if (!$isAdmin): ?>
            <a href="customer_credit.php?view=my_history" class="btn <?php echo $view === 'my_history' ? 'primary' : 'ghost'; ?>">
                <i class="fas fa-history"></i> My History
            </a>
        <?php endif; ?>
        <button class="btn ghost" onclick="window.print()"><i class="fas fa-file-export"></i> Export</button>
    </div>

    <!-- 3. SUMMARY CARDS -->
    <div class="layout-summary">
        <div class="summary-card">
            <div class="summary-val" style="color:#dc3545;">₱<?php echo number_format($summary['charges'], 2); ?></div>
            <div class="summary-lbl">Total Charges</div>
        </div>
        <div class="summary-card">
            <div class="summary-val" style="color:#28a745;">₱<?php echo number_format($summary['payments'], 2); ?></div>
            <div class="summary-lbl">Total Payments</div>
        </div>
        <div class="summary-card">
            <div class="summary-val">₱<?php echo number_format($summary['balance'], 2); ?></div>
            <div class="summary-lbl">Outstanding Balance</div>
        </div>
    </div>

    <!-- 4. MAIN TABLE -->
    <div class="layout-content">
        <div style="padding: 10px; border-bottom: 1px solid #eee; display:flex; justify-content:space-between; align-items:center;">
            <h3 class="h3" style="margin:0;">Transactions</h3>
            <?php if($isAdmin && $selected_cust): ?>
                <button class="btn primary small" onclick="openAdjModal()">+ Add Adjustment</button>
            <?php endif; ?>
        </div>
        <div class="table-scroll">
            <table class="table">
                <thead style="position: sticky; top: 0; background: #f8f9fa;">
                    <tr>
                        <th>Date</th>
                        <th>Reference No.</th>
                        <th>Type</th>
                        <th>Debit (Charge)</th>
                        <th>Credit (Payment)</th>
                        <th>Balance</th>
                        <th>Remarks</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if(empty($ledger)): ?>
                        <tr><td colspan="7" style="text-align:center; padding:20px;">No transactions found.</td></tr>
                    <?php else: ?>
                        <?php foreach($ledger as $row): ?>
                        <tr>
                            <td><?php echo $row['date']; ?></td>
                            <td><?php echo htmlspecialchars($row['reference_no']); ?></td>
                            <td><?php echo htmlspecialchars($row['type']); ?></td>
                            <td style="color:#dc3545;"><?php echo $row['type']=='Debit' ? number_format($row['amount'], 2) : '-'; ?></td>
                            <td style="color:#28a745;"><?php echo $row['type']!='Debit' ? number_format($row['amount'], 2) : '-'; ?></td>
                            <td><b><?php echo number_format($row['balance'], 2); ?></b></td>
                            <td><?php echo htmlspecialchars($row['remarks']); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Adjustment Modal -->
<div class="modal" id="adjModal">
    <div class="modal-content" style="max-width: 400px;">
        <div class="modal-header">
            <h3 class="modal-title">Manual Adjustment</h3>
        </div>
        <form method="post">
            <input type="hidden" name="action" value="adjustment">
            <input type="hidden" name="customer_id" value="<?php echo $selected_cust; ?>">
            <div class="modal-body">
                <div class="form-group" style="margin-bottom:15px;">
                    <label>Type</label>
                    <select name="type" class="inp" style="width:100%;">
                        <option value="Debit">Debit (Charge/Fee)</option>
                        <option value="Credit">Credit (Payment/Refund)</option>
                    </select>
                </div>
                <div class="form-group" style="margin-bottom:15px;">
                    <label>Amount</label>
                    <input type="number" name="amount" step="0.01" class="inp" style="width:100%;" required>
                </div>
                <div class="form-group">
                    <label>Reason</label>
                    <textarea name="reason" class="inp" style="width:100%;" rows="2" required></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn ghost" onclick="closeAdjModal()">Cancel</button>
                <button type="submit" class="btn primary">Save</button>
            </div>
        </form>
    </div>
</div>

<script>
function openAdjModal() {
    document.getElementById('adjModal').style.display = 'block';
}
function closeAdjModal() {
    document.getElementById('adjModal').style.display = 'none';
}
</script>
<?php require_once __DIR__ . '/../partials/footer.php'; ?>
