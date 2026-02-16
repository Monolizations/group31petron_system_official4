<?php
$page_id = 'transactions';
require_once __DIR__ . '/../backend/lib.php';
require_once __DIR__ . '/../public/db_connect.php';
require_login();

$me = current_user();
$station_id = user_station_id();

// Filters
$view = $_GET['view'] ?? 'all';
$start = $_GET['start'] ?? date('Y-m-01');
// Use database date to avoid timezone issues
$default_end = $pdo->query("SELECT DATE(NOW()) as today")->fetch(PDO::FETCH_ASSOC)['today'] ?? date('Y-m-d');
$end = $_GET['end'] ?? $default_end;
$customer = $_GET['customer'] ?? '';
$payment = $_GET['payment'] ?? '';
$category = $_GET['category'] ?? '';

// Ensure status column exists in sales table
try {
    $pdo->exec("ALTER TABLE sales ADD COLUMN status VARCHAR(50) DEFAULT 'Completed'");
} catch (Exception $e) {}

// Build Query
$sql = "SELECT
            s.id as transaction_id,
            c.name as customer,
            s.payment_method,
            s.created_at,
            s.status,
            u.name as staff_name,
            p.name as product_name,
            si.quantity,
            si.unit_price,
            si.total_amount as subtotal,
            COALESCE(pt.name, 'Merchandise') as category
        FROM sales s
        JOIN sale_items si ON s.id = si.sale_id
        LEFT JOIN customers c ON s.customer_id = c.id
        LEFT JOIN users u ON s.user_id = u.id
        LEFT JOIN products p ON si.product_id = p.id
        LEFT JOIN product_types pt ON p.type_id = pt.id
        WHERE s.station_id = ?
        AND DATE(s.created_at) BETWEEN ? AND ?";

$params = [$station_id, $start, $end];

// Filter by current user if view is "my_history"
if ($view === 'my_history') {
    $sql .= " AND s.user_id = ?";
    $params[] = $me['id'];
}

if ($customer) {
    $sql .= " AND c.name LIKE ?";
    $params[] = "%$customer%";
}
if ($payment) {
    $sql .= " AND s.payment_method = ?";
    $params[] = $payment;
}
if ($category) {
    $sql .= " AND (pt.name LIKE ? OR p.name LIKE ?)";
    $params[] = "%$category%";
    $params[] = "%$category%";
}

$sql .= " ORDER BY s.created_at DESC, s.id DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$transactions = $stmt->fetchAll();

// Export Logic
if (isset($_GET['export'])) {
    if ($_GET['export'] == 'excel') {
        $filename = "transaction_history_" . date('Ymd') . ".xls";
        header("Content-Type: application/vnd.ms-excel");
        header("Content-Disposition: attachment; filename=\"$filename\"");
        echo "Transaction ID\tCustomer\tCategory\tProduct Name\tQty/Liters\tUnit Price\tSubtotal\tPayment Type\tStaff\tDate/Time\tStatus\n";
        foreach ($transactions as $t) {
            echo implode("\t", [
                $t['transaction_id'],
                $t['customer'],
                $t['category'],
                $t['product_name'],
                $t['quantity'],
                $t['unit_price'],
                $t['subtotal'],
                $t['payment_method'],
                $t['staff_name'],
                $t['created_at'],
                $t['status']
            ]) . "\n";
        }
        exit;
    }
}

include __DIR__ . '/../partials/header.php';
?>

<div class="page-head">
    <div>
        <h1 class="h1"><?php echo ($view === 'my_history') ? 'My Transaction History' : 'Transaction History (Admin View)'; ?></h1>
        <div class="sub"><?php echo ($view === 'my_history') ? 'Your recorded transactions' : 'Record of all finalized transactions (Cash, Card, Credit)'; ?></div>
    </div>
    <div class="actions">
        <a href="?<?php echo http_build_query(array_merge($_GET, ['export'=>'excel'])); ?>" class="btn ghost"><i class="fas fa-file-excel"></i> Export Excel</a>
        <button onclick="window.print()" class="btn ghost"><i class="fas fa-print"></i> Print PDF</button>
    </div>
</div>

<div class="card" style="padding: 15px; margin-bottom: 20px;">
    <form method="get" style="display: flex; gap: 10px; align-items: end; flex-wrap: wrap;">
        <div>
            <label class="lbl">Date Range</label>
            <div style="display: flex; gap: 5px;">
                <input type="date" name="start" value="<?php echo $start; ?>" class="inp">
                <input type="date" name="end" value="<?php echo $end; ?>" class="inp">
            </div>
        </div>
        <div>
            <label class="lbl">Customer</label>
            <input type="text" name="customer" value="<?php echo htmlspecialchars($customer); ?>" class="inp" placeholder="Name...">
        </div>
        <div>
            <label class="lbl">Payment</label>
            <select name="payment" id="payment_method_transactions" class="inp">
                <option value="">All</option>
            </select>
        </div>
         <div>
             <label class="lbl">Category</label>
             <select name="category" class="inp">
                 <option value="">All</option>
                 <option value="Merchandise" <?php echo $category=='Merchandise'?'selected':''; ?>>Merchandise</option>
                 <option value="Services" <?php echo $category=='Services'?'selected':''; ?>>Services</option>
             </select>
         </div>
        <button type="submit" class="btn primary" style="padding:6px 15px;">Filter</button>
    </form>
</div>

<div class="card" style="padding: 0;">
    <div class="table-wrap">
        <table class="table">
            <thead>
                <tr>
                    <th>Transaction ID</th>
                    <th>Customer</th>
                    <th>Category</th>
                    <th>Product Name</th>
                    <th>Qty/Liters</th>
                    <th>Unit Price</th>
                    <th>Subtotal</th>
                    <th>Payment Type</th>
                    <th>Staff Encoder</th>
                    <th>Date/Time</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach($transactions as $t): ?>
                <tr>
                    <td>#<?php echo $t['transaction_id']; ?></td>
                    <td><?php echo htmlspecialchars($t['customer']); ?></td>
                    <td><span class="badge"><?php echo htmlspecialchars($t['category']); ?></span></td>
                    <td><?php echo htmlspecialchars($t['product_name']); ?></td>
                    <td><?php echo number_format($t['quantity'], 2); ?></td>
                    <td>₱<?php echo number_format($t['unit_price'], 2); ?></td>
                    <td style="font-weight:bold;">₱<?php echo number_format($t['subtotal'], 2); ?></td>
                    <td><?php echo htmlspecialchars($t['payment_method']); ?></td>
                    <td><?php echo htmlspecialchars($t['staff_name']); ?></td>
                    <td><?php echo date('M d, H:i', strtotime($t['created_at'])); ?></td>
                    <td>
                        <?php
                        $statusClass = 'success';
                        if($t['status'] == 'Pending') $statusClass = 'warning';
                        if($t['status'] == 'Rejected') $statusClass = 'danger';
                        ?>
                        <span class="badge" style="background:var(--petron-<?php echo $statusClass == 'success' ? 'green' : ($statusClass == 'warning' ? 'yellow' : 'red'); ?>); color:white;">
                            <?php echo htmlspecialchars($t['status']); ?>
                        </span>
                    </td>
                    <td>
                        <button class="btn small ghost" onclick="viewTransaction(<?php echo $t['transaction_id']; ?>)" title="View">👁️</button>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php if(empty($transactions)): ?>
                    <tr><td colspan="12" style="text-align:center; padding:20px;">No transactions found.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- View Transaction Modal -->
<div class="modal" id="viewModal">
    <div class="modal-content" style="max-width: 600px;">
        <div class="modal-header">
            <h3 class="modal-title">Transaction Details</h3>
            <button class="modal-close" onclick="closeModal('viewModal')">&times;</button>
        </div>
        <div class="modal-body" id="transactionDetails">
            <!-- Details loaded here -->
        </div>
        <div class="modal-footer">
            <button class="btn primary" onclick="closeModal('viewModal')">Close</button>
        </div>
    </div>
</div>

<script>
function viewTransaction(id) {
    // Fetch details via AJAX or load statically for now
    fetch('backend/get_transaction_details.php?id=' + id)
        .then(response => response.json())
        .then(data => {
            document.getElementById('transactionDetails').innerHTML = `
                <p><strong>Customer:</strong> ${data.customer}</p>
                <p><strong>Staff:</strong> ${data.staff_name}</p>
                <p><strong>Payment:</strong> ${data.payment_method}</p>
                <p><strong>Status:</strong> ${data.status}</p>
                <p><strong>Date:</strong> ${data.created_at}</p>
                <p><strong>Products:</strong></p>
                <ul>${data.items.map(item => `<li>${item.name} - ${item.quantity} x ₱${item.unit_price} = ₱${item.total_amount}</li>`).join('')}</ul>
                <p><strong>Audit Log:</strong></p>
                <ul>${data.logs ? data.logs.map(log => `<li>${log.created_at}: ${log.action} - ${log.details}</li>`).join('') : 'No logs available'}</ul>
            `;
            document.getElementById('viewModal').classList.add('show');
        });
}

function closeModal(id) {
    document.getElementById(id).classList.remove('show');
}
</script>

<script src="../assets/js/data_helper.js"></script>

<script>
document.addEventListener('DOMContentLoaded', function() {
    DataHelper.populatePaymentMethods('payment_method_transactions', 'All');
});
</script>

<style>
    @media print {
        .page-head .actions, .filter-bar, .card form { display: none; }
        .card { border: none; box-shadow: none; }
        .table th, .table td { border: 1px solid #ddd; }
    }
</style>

<?php include __DIR__ . '/../partials/footer.php'; ?>
