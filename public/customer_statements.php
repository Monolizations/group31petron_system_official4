<?php
$page_id = 'customer_statements';
require_once __DIR__ . '/../backend/lib.php';
require_once __DIR__ . '/../public/db_connect.php';
require_login();

// Fetch Customers
$customers = $pdo->query("SELECT id, name FROM customers ORDER BY name")->fetchAll();

$selected_cust_id = $_GET['id'] ?? '';
$billing_period = $_GET['period'] ?? date('Y-m');

$cust_data = null;
$transactions = [];
$summary = ['charges' => 0, 'payments' => 0, 'balance' => 0];

if ($selected_cust_id) {
    // Fetch the specific customer for the report
    $stmt = $pdo->prepare("SELECT * FROM customers WHERE id = ?");
    $stmt->execute([$selected_cust_id]);
    $cust_data = $stmt->fetch();

    if ($cust_data) {
        // Filter by billing period
        $start_date = $billing_period . '-01';
        $end_date = date('Y-m-t', strtotime($start_date));

        $stmt = $pdo->prepare("SELECT * FROM customer_ledger WHERE customer_id = ? AND date BETWEEN ? AND ? ORDER BY date ASC");
        $stmt->execute([$selected_cust_id, $start_date, $end_date]);
        $transactions = $stmt->fetchAll();

        // Calculate totals for the period
        foreach ($transactions as $t) {
            if ($t['type'] === 'Debit') $summary['charges'] += $t['amount'];
            else $summary['payments'] += $t['amount'];
        }
        // Get current outstanding balance from the main customer record
        $summary['balance'] = $cust_data['current_balance'];
    }
}

include __DIR__ . '/../partials/header.php';
?>
<style>
    .page-layout { height: 100%; display: flex; flex-direction: column; gap: 15px; }
    .layout-header { flex: 0 0 auto; }
    .layout-filter { flex: 0 0 auto; background: white; padding: 12px; border-radius: 8px; border: 1px solid #e0e0e0; display: flex; gap: 10px; align-items: center; }
    .layout-content { flex: 1 1 auto; background: #525659; padding: 20px; border-radius: 8px; overflow-y: auto; display: flex; justify-content: center; }
    .layout-actions { flex: 0 0 auto; display: flex; gap: 10px; justify-content: flex-end; }
    
    /* SOA Paper Style */
    .soa-paper {
        background: white;
        width: 210mm; /* A4 width */
        min-height: 297mm;
        padding: 20mm;
        box-shadow: 0 0 10px rgba(0,0,0,0.5);
        display: flex;
        flex-direction: column;
    }
    .soa-header { display: flex; justify-content: space-between; border-bottom: 2px solid #002F6C; padding-bottom: 20px; margin-bottom: 20px; }
    .soa-logo img { height: 60px; }
    .soa-title { text-align: right; }
    .soa-title h2 { color: #002F6C; margin: 0; }
    .soa-info { display: flex; justify-content: space-between; margin-bottom: 30px; }
    .soa-table { width: 100%; border-collapse: collapse; margin-bottom: 30px; }
    .soa-table th { background: #f0f0f0; padding: 10px; text-align: left; border-bottom: 1px solid #ddd; }
    .soa-table td { padding: 10px; border-bottom: 1px solid #eee; }
    .soa-summary { align-self: flex-end; width: 300px; border: 1px solid #ddd; padding: 15px; }
    .sum-row { display: flex; justify-content: space-between; margin-bottom: 5px; }
    .sum-total { font-weight: bold; border-top: 1px solid #ddd; padding-top: 5px; margin-top: 5px; font-size: 1.1em; }
</style>

<div class="page-layout">
    <!-- 1. HEADER -->
    <div class="layout-header">
        <h1 class="h1">Statements of Account</h1>
    </div>

    <!-- 2. FILTER BAR -->
    <div class="layout-filter">
        <form method="get" style="display:flex; gap:10px; width:100%;">
            <select name="id" class="inp" style="min-width: 250px;">
                <option value="">-- Select Customer --</option>
                <?php foreach($customers as $c): ?>
                    <option value="<?php echo $c['id']; ?>" <?php echo $selected_cust_id == $c['id'] ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($c['name']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <input type="month" name="period" class="inp" value="<?php echo $billing_period; ?>">
            <button type="submit" class="btn primary">Generate</button>
        </form>
    </div>

    <!-- 3. SOA PREVIEW PANEL -->
    <div class="layout-content">
        <?php if($cust_data): ?>
        <div class="soa-paper" id="printableArea">
            <!-- A. HEADER BLOCK -->
            <div class="soa-header">
                <div class="soa-logo">
                    <img src="../assets/img/Petron Logo.png" alt="Petron">
                    <div><strong>PETRON CORPORATION</strong></div>
                    <div style="font-size:0.9em;">Station Address Here</div>
                </div>
                <div class="soa-title">
                    <h2>STATEMENT OF ACCOUNT</h2>
                    <div>Date: <?php echo date('M d, Y'); ?></div>
                    <div>Period: <?php echo date('F Y', strtotime($billing_period)); ?></div>
                </div>
            </div>

            <div class="soa-info">
                <div>
                    <strong>Bill To:</strong><br>
                    <?php echo htmlspecialchars($cust_data['name']); ?><br>
                    <?php echo htmlspecialchars($cust_data['address']); ?><br>
                    <?php echo htmlspecialchars($cust_data['email']); ?>
                </div>
            </div>

            <!-- B. TRANSACTION TABLE -->
            <table class="soa-table">
                <thead>
                    <tr><th>Date</th><th>Reference</th><th>Description</th><th style="text-align:right;">Amount</th></tr>
                </thead>
                <tbody>
                    <?php if (empty($transactions)): ?>
                        <tr>
                            <td colspan="4" style="text-align: center; padding: 20px; color: #666;">
                                No transactions found for this billing period.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach($transactions as $t): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($t['date']); ?></td>
                            <td><?php echo htmlspecialchars($t['reference_no']); ?></td>
                            <td><?php echo htmlspecialchars($t['type'] . ' - ' . ($t['remarks'] ?? '')); ?></td>
                            <td style="text-align:right;"><?php echo number_format($t['amount'], 2); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>

            <!-- C. TOTAL SUMMARY BOX -->
            <div class="soa-summary">
                <div class="sum-row"><span>Total Charges:</span> <span>₱<?php echo number_format($summary['charges'], 2); ?></span></div>
                <div class="sum-row"><span>Total Payments:</span> <span>(₱<?php echo number_format($summary['payments'], 2); ?>)</span></div>
                <div class="sum-row sum-total"><span>Outstanding Balance:</span> <span>₱<?php echo number_format($summary['balance'], 2); ?></span></div>
                <div style="margin-top:10px; font-size:0.9em; color:red;">Due Date: <?php echo date('M d, Y', strtotime('+15 days')); ?></div>
            </div>
        </div>
        <?php else: ?>
            <div style="color:white; align-self:center;">Select a customer to generate statement.</div>
        <?php endif; ?>
    </div>

    <!-- 4. ACTION BUTTONS -->
    <div class="layout-actions">
        <button class="btn ghost" onclick="window.print()">Print</button>
        <button class="btn primary">Download PDF</button>
    </div>
</div>
<?php require_once __DIR__ . '/../partials/footer.php'; ?>
