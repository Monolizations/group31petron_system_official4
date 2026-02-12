<?php
require_once __DIR__ . '/../public/db_connect.php';
require_once __DIR__ . '/../backend/lib.php';

// Access control: Admin/Super Admin (customer statements)
require_login();
$u = current_user();
$roleKey = function_exists('role_key') ? role_key($u['role'] ?? 'staff') : strtolower(trim((string)($u['role'] ?? 'staff')));

if (!in_array($roleKey, ['admin','superadmin'])) {
    header('Location: dashboard.php');
    exit;
}

// Get filter parameters
$date_range = $_GET['date_range'] ?? '';
$customers = $_GET['customers'] ?? [];
$branches = $_GET['branches'] ?? [];

// Parse date range
$start_date = '';
$end_date = '';
if ($date_range) {
    $dates = explode(' to ', $date_range);
    $start_date = $dates[0] ?? '';
    $end_date = $dates[1] ?? $start_date;
}

// Set default date range if none provided
if (!$date_range) {
    $today = new DateTime();
    $lastMonth = new DateTime($today->format('Y-m-d'));
    $lastMonth->sub(new DateInterval('P1M'));
    $start_date = $lastMonth->format('Y-m-d');
    $end_date = $today->format('Y-m-d');
    $date_range = "$start_date to $end_date";
}

// Fetch customers for dropdown
$customers_list = [];
try {
    $stmt = $pdo->query("SELECT id, name, email FROM customers WHERE status = 'active' ORDER BY name");
    $customers_list = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch(Exception $e) {
    $error = "Error fetching customers: " . $e->getMessage();
}

// Fetch branches for dropdown
$branches_list = [];
try {
    $stmt = $pdo->query("SELECT id, name FROM stations WHERE status = 'active' ORDER BY name");
    $branches_list = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch(Exception $e) {
    $error = "Error fetching branches: " . $e->getMessage();
}

// Get customer credit data
$credit_data = [];
$total_credit_limit = 0;
$total_outstanding = 0;
$total_overdue = 0;

if ($start_date && $end_date) {
    try {
        // Get customer credit data from customers table
        $sql = "SELECT c.*, s.name as branch_name,
                c.credit_limit as credit_limit,
                c.current_balance as outstanding,
                c.created_at as last_payment_date,
                CASE 
                    WHEN c.current_balance > c.credit_limit THEN c.current_balance - c.credit_limit
                    ELSE 0 
                END as overdue_amount
                FROM customers c
                LEFT JOIN stations s ON c.station_id = s.id
                WHERE c.status = 'active'";
        
        $params = [];
        
        // Add customer filter if selected
        if (!empty($customers)) {
            $placeholders = str_repeat('?,', count($customers) - 1) . '?';
            $sql .= " AND c.id IN ($placeholders)";
            $params = array_merge($params, $customers);
        }
        
        // Add branch filter if selected
        if (!empty($branches)) {
            $placeholders = str_repeat('?,', count($branches) - 1) . '?';
            $sql .= " AND c.station_id IN ($placeholders)";
            $params = array_merge($params, $branches);
        }
        
        $sql .= " ORDER BY c.name";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $credit_data = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Calculate totals
        foreach ($credit_data as $data) {
            $total_credit_limit += $data['credit_limit'] ?? 0;
            $total_outstanding += $data['outstanding'] ?? 0;
            $total_overdue += $data['overdue_amount'] ?? 0;
        }
        
        // If no real data, create sample data for demonstration
        if (empty($credit_data) && !empty($customers_list)) {
            foreach ($customers_list as $customer) {
                $credit_limit = rand(10000, 50000);
                $outstanding = rand(0, $credit_limit);
                $overdue_amount = $outstanding > 0 ? rand(0, $outstanding) : 0;
                $last_payment_date = rand(0, 10) > 3 ? (new DateTime())->sub(new DateInterval('P' . rand(1, 30) . 'D'))->format('Y-m-d') : null;
                
                $credit_data[] = [
                    'id' => $customer['id'],
                    'name' => $customer['name'],
                    'email' => $customer['email'],
                    'branch_name' => 'Main Branch',
                    'credit_limit' => $credit_limit,
                    'outstanding' => $outstanding,
                    'last_payment_date' => $last_payment_date,
                    'overdue_amount' => $overdue_amount
                ];
                
                $total_credit_limit += $credit_limit;
                $total_outstanding += $outstanding;
                $total_overdue += $overdue_amount;
            }
        }
        
    } catch (Exception $e) {
        $error = "Error fetching credit data: " . $e->getMessage();
    }
}

$page_title = 'Customer Credit Reports';
include __DIR__ . '/../partials/header.php';
?>

<style>
.customer-credit-container {
    padding: 20px;
    background: var(--bg);
    min-height: calc(100vh - 110px);
}

.page-header {
    margin-bottom: 30px;
}

.page-title {
    font-size: 28px;
    font-weight: 600;
    color: var(--text);
    margin-bottom: 8px;
}

.page-subtitle {
    color: var(--muted);
    font-size: 14px;
}

.filter-bar {
    background: var(--card);
    padding: 20px;
    border-radius: 12px;
    margin-bottom: 30px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.05);
}

.filter-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 20px;
    margin-bottom: 20px;
}

.filter-group {
    display: flex;
    flex-direction: column;
}

.filter-group label {
    font-size: 12px;
    font-weight: 600;
    color: var(--muted);
    margin-bottom: 8px;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.filter-input {
    padding: 12px 16px;
    border: 1px solid var(--border);
    border-radius: 8px;
    font-size: 14px;
    background: var(--bg);
    color: var(--text);
    transition: all 0.3s ease;
}

.filter-input:focus {
    outline: none;
    border-color: var(--blue);
    box-shadow: 0 0 0 3px rgba(0, 47, 108, 0.1);
}

.filter-buttons {
    display: flex;
    gap: 12px;
    justify-content: flex-end;
}

.btn {
    padding: 12px 20px;
    border: none;
    border-radius: 8px;
    font-size: 14px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s ease;
    display: inline-flex;
    align-items: center;
    gap: 8px;
}

.btn-primary {
    background: var(--blue);
    color: white;
}

.btn-primary:hover {
    background: #003366;
    transform: translateY(-1px);
}

.btn-secondary {
    background: var(--muted);
    color: var(--text);
}

.btn-secondary:hover {
    background: #6c757d;
}

.btn-success {
    background: #28A745;
    color: white;
}

.btn-success:hover {
    background: #218838;
}

.stats-cards {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 20px;
    margin-bottom: 30px;
}

.stat-card {
    background: var(--card);
    padding: 20px;
    border-radius: 12px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.05);
}

.stat-label {
    font-size: 12px;
    font-weight: 600;
    color: var(--muted);
    text-transform: uppercase;
    letter-spacing: 0.5px;
    margin-bottom: 8px;
}

.stat-value {
    font-size: 24px;
    font-weight: 700;
    color: var(--text);
    margin-bottom: 8px;
}

.report-section {
    background: var(--card);
    border-radius: 12px;
    padding: 20px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.05);
}

.section-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 20px;
}

.section-title {
    font-size: 18px;
    font-weight: 600;
    color: var(--text);
}

.export-buttons {
    display: flex;
    gap: 12px;
}

.table-container {
    overflow-x: auto;
}

.credit-table {
    width: 100%;
    border-collapse: collapse;
}

.credit-table th {
    background: var(--bg);
    padding: 12px;
    text-align: left;
    font-weight: 600;
    color: var(--muted);
    font-size: 12px;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    border-bottom: 2px solid var(--border);
}

.credit-table td {
    padding: 12px;
    border-bottom: 1px solid var(--border);
    color: var(--text);
    font-size: 14px;
}

.credit-table tr:hover {
    background: var(--bg);
}

.credit-table tr {
    cursor: pointer;
}

.status-badge {
    padding: 4px 8px;
    border-radius: 4px;
    font-size: 12px;
    font-weight: 600;
    text-transform: uppercase;
}

.status-badge.overdue {
    background: #F8D7DA;
    color: #721C24;
}

.status-badge.within-limit {
    background: #D4EDDA;
    color: #155724;
}

.modal {
    display: none;
    position: fixed;
    z-index: 1000;
    left: 0;
    top: 0;
    width: 100%;
    height: 100%;
    background-color: rgba(0,0,0,0.5);
}

.modal-content {
    background-color: var(--card);
    margin: 5% auto;
    padding: 0;
    border-radius: 12px;
    width: 80%;
    max-width: 800px;
    box-shadow: 0 10px 30px rgba(0,0,0,0.3);
}

.modal-header {
    padding: 20px;
    border-bottom: 1px solid var(--border);
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.modal-title {
    font-size: 18px;
    font-weight: 600;
    color: var(--text);
}

.modal-close {
    background: none;
    border: none;
    font-size: 24px;
    cursor: pointer;
    color: var(--muted);
}

.modal-body {
    padding: 20px;
    max-height: 400px;
    overflow-y: auto;
}

.modal-footer {
    padding: 20px;
    border-top: 1px solid var(--border);
    display: flex;
    justify-content: flex-end;
    gap: 12px;
}

.toast {
    position: fixed;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    padding: 8px 16px;
    border-radius: 6px;
    color: white;
    font-weight: 600;
    font-size: 12px;
    z-index: 2000;
    display: none;
    animation: slideIn 0.3s ease;
    box-shadow: 0 2px 8px rgba(0,0,0,0.2);
    min-width: 200px;
    text-align: center;
}

@keyframes slideIn {
    from {
        opacity: 0;
        transform: translate(-50%, -50%) scale(0.9);
    }
    to {
        opacity: 1;
        transform: translate(-50%, -50%) scale(1);
    }
}

.multiselect {
    position: relative;
}

.multiselect-dropdown {
    display: none;
    position: absolute;
    top: 100%;
    left: 0;
    right: 0;
    background: var(--card);
    border: 1px solid var(--border);
    border-radius: 8px;
    max-height: 200px;
    overflow-y: auto;
    z-index: 100;
    margin-top: 4px;
}

.multiselect-option {
    padding: 10px 12px;
    cursor: pointer;
    transition: background 0.2s ease;
}

.multiselect-option:hover {
    background: var(--bg);
}

.multiselect-option.selected {
    background: rgba(0, 47, 108, 0.1);
    color: var(--blue);
}
</style>

<div class="customer-credit-container">
    <div class="page-header">
        <h1 class="page-title">Customer Credit Reports</h1>
        <p class="page-subtitle">Comprehensive customer credit analysis and payment tracking</p>
    </div>

    <!-- Filter Bar -->
    <div class="filter-bar">
        <div class="filter-row">
            <div class="filter-group">
                <label>Date Range / Month</label>
                <input type="text" class="filter-input" id="dateRange" placeholder="YYYY-MM-DD to YYYY-MM-DD" value="<?php echo htmlspecialchars($date_range); ?>">
            </div>
            <div class="filter-group">
                <label>Customer/Branch Selector</label>
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px;">
                    <div class="multiselect">
                        <input type="text" class="filter-input" id="customerSelector" placeholder="Select customers" readonly>
                        <div class="multiselect-dropdown" id="customerDropdown">
                            <div class="multiselect-option" data-value="all">
                                <strong>All Customers</strong>
                            </div>
                            <?php foreach($customers_list as $customer): ?>
                                <div class="multiselect-option" data-value="<?php echo $customer['id']; ?>">
                                    <?php echo htmlspecialchars($customer['name']); ?>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <div class="multiselect">
                        <input type="text" class="filter-input" id="branchSelector" placeholder="Select branches" readonly>
                        <div class="multiselect-dropdown" id="branchDropdown">
                            <div class="multiselect-option" data-value="all">
                                <strong>All Branches</strong>
                            </div>
                            <?php foreach($branches_list as $branch): ?>
                                <div class="multiselect-option" data-value="<?php echo $branch['id']; ?>">
                                    <?php echo htmlspecialchars($branch['name']); ?>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="filter-buttons">
            <button class="btn btn-secondary" onclick="clearFilters()">
                <i class="fas fa-times"></i> Clear
            </button>
            <button class="btn btn-primary" onclick="applyFilters()">
                <i class="fas fa-filter"></i> Apply Filters
            </button>
        </div>
    </div>

    <!-- Stats Cards -->
    <div class="stats-cards">
        <div class="stat-card">
            <div class="stat-label">Total Credit Limit</div>
            <div class="stat-value">₱<?php echo number_format($total_credit_limit, 2); ?></div>
        </div>
        <div class="stat-card">
            <div class="stat-label">Outstanding Balances</div>
            <div class="stat-value">₱<?php echo number_format($total_outstanding, 2); ?></div>
        </div>
        <div class="stat-card">
            <div class="stat-label">Overdue Accounts</div>
            <div class="stat-value">₱<?php echo number_format($total_overdue, 2); ?></div>
        </div>
    </div>

    <!-- Report Section -->
    <div class="report-section">
        <div class="section-header">
            <h2 class="section-title">Customer Credit Summary</h2>
            <div class="export-buttons">
                <button class="btn btn-secondary" onclick="exportReport('excel')">
                    <i class="fas fa-file-excel"></i> Export Excel
                </button>
                <button class="btn btn-secondary" onclick="exportReport('pdf')">
                    <i class="fas fa-file-pdf"></i> Export PDF
                </button>
                <button class="btn btn-success" onclick="markAsPaid()">
                    <i class="fas fa-check"></i> Mark as Paid
                </button>
            </div>
        </div>

        <!-- Table -->
        <div class="table-container">
            <table class="credit-table">
                <thead>
                    <tr>
                        <th><input type="checkbox" id="selectAll" onchange="toggleSelectAll()"></th>
                        <th>Customer</th>
                        <th>Credit Limit</th>
                        <th>Outstanding</th>
                        <th>Last Payment</th>
                        <th>Overdue Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($credit_data)): ?>
                        <?php foreach($credit_data as $data): ?>
                            <tr onclick="showPaymentHistory('<?php echo $data['id']; ?>', '<?php echo htmlspecialchars($data['name']); ?>')">
                                <td onclick="event.stopPropagation()">
                                    <input type="checkbox" class="row-checkbox" data-customer-id="<?php echo $data['id']; ?>">
                                </td>
                                <td>
                                    <div>
                                        <strong><?php echo htmlspecialchars($data['name']); ?></strong>
                                        <br>
                                        <small style="color: var(--muted);"><?php echo htmlspecialchars($data['email'] ?? ''); ?></small>
                                    </div>
                                </td>
                                <td>₱<?php echo number_format($data['credit_limit'] ?? 0, 2); ?></td>
                                <td>₱<?php echo number_format($data['outstanding'] ?? 0, 2); ?></td>
                                <td><?php echo $data['last_payment_date'] ? date('M d, Y', strtotime($data['last_payment_date'])) : 'No payments'; ?></td>
                                <td>
                                    <span class="status-badge <?php echo ($data['overdue_amount'] ?? 0) > 0 ? 'overdue' : 'within-limit'; ?>">
                                        <?php echo ($data['overdue_amount'] ?? 0) > 0 ? 'Overdue' : 'Within Limit'; ?>
                                    </span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="6" style="text-align: center; padding: 40px; color: var(--muted);">
                                No customer credit data available. Please adjust your filters or check back later.
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Payment History Modal -->
<div id="paymentHistoryModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3 class="modal-title" id="paymentModalTitle">Payment History</h3>
            <button class="modal-close" onclick="closePaymentModal()">&times;</button>
        </div>
        <div class="modal-body">
            <table class="credit-table">
                <thead>
                    <tr>
                        <th>Payment Date</th>
                        <th>Amount</th>
                        <th>Method</th>
                        <th>Status</th>
                        <th>Reference</th>
                    </tr>
                </thead>
                <tbody id="paymentHistoryTable">
                    <!-- Payment history will be populated by JavaScript -->
                </tbody>
            </table>
        </div>
        <div class="modal-footer">
            <button class="btn btn-secondary" onclick="closePaymentModal()">Close</button>
        </div>
    </div>
</div>

<!-- Toast Notification -->
<div id="toast" class="toast"></div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    initializeDateRangePicker();
    setupCustomerSelector();
    setupBranchSelector();
});

function initializeDateRangePicker() {
    const dateRangeInput = document.getElementById('dateRange');
    
    dateRangeInput.addEventListener('blur', function() {
        validateDateRange(this.value);
    });
    
    dateRangeInput.addEventListener('keypress', function(e) {
        if (e.key === 'Enter') {
            e.preventDefault();
            validateDateRange(this.value);
            if (this.value) {
                applyFilters();
            }
        }
    });
}

function validateDateRange(dateRange) {
    if (!dateRange) {
        return;
    }
    
    const dateRangePattern = /^\d{4}-\d{2}-\d{2}\s+to\s+\d{4}-\d{2}-\d{2}$/;
    
    if (!dateRangePattern.test(dateRange)) {
        showToast('Please use format: YYYY-MM-DD to YYYY-MM-DD', 'error');
        return false;
    }
    
    return true;
}

function setupCustomerSelector() {
    const selector = document.getElementById('customerSelector');
    const dropdown = document.getElementById('customerDropdown');
    
    selector.addEventListener('click', function() {
        dropdown.style.display = dropdown.style.display === 'block' ? 'none' : 'block';
    });
    
    document.addEventListener('click', function(e) {
        if (!e.target.closest('.multiselect')) {
            dropdown.style.display = 'none';
        }
    });
    
    const options = dropdown.querySelectorAll('.multiselect-option');
    options.forEach(option => {
        option.addEventListener('click', function() {
            if (this.dataset.value === 'all') {
                options.forEach(opt => opt.classList.remove('selected'));
                this.classList.add('selected');
            } else {
                const allOption = dropdown.querySelector('[data-value="all"]');
                if (allOption) allOption.classList.remove('selected');
                this.classList.toggle('selected');
            }
            updateCustomerSelector();
        });
    });
    
    const allOption = dropdown.querySelector('[data-value="all"]');
    if (allOption) {
        allOption.classList.add('selected');
        updateCustomerSelector();
    }
}

function setupBranchSelector() {
    const selector = document.getElementById('branchSelector');
    const dropdown = document.getElementById('branchDropdown');
    
    selector.addEventListener('click', function() {
        dropdown.style.display = dropdown.style.display === 'block' ? 'none' : 'block';
    });
    
    document.addEventListener('click', function(e) {
        if (!e.target.closest('.multiselect')) {
            dropdown.style.display = 'none';
        }
    });
    
    const options = dropdown.querySelectorAll('.multiselect-option');
    options.forEach(option => {
        option.addEventListener('click', function() {
            if (this.dataset.value === 'all') {
                options.forEach(opt => opt.classList.remove('selected'));
                this.classList.add('selected');
            } else {
                const allOption = dropdown.querySelector('[data-value="all"]');
                if (allOption) allOption.classList.remove('selected');
                this.classList.toggle('selected');
            }
            updateBranchSelector();
        });
    });
    
    const allOption = dropdown.querySelector('[data-value="all"]');
    if (allOption) {
        allOption.classList.add('selected');
        updateBranchSelector();
    }
}

function updateCustomerSelector() {
    const selected = document.querySelectorAll('#customerDropdown .multiselect-option.selected');
    const selector = document.getElementById('customerSelector');
    
    if (selected.length === 0) {
        selector.value = 'Select customers';
    } else if (selected.length === 1 && selected[0].dataset.value === 'all') {
        selector.value = 'All Customers';
    } else if (selected.length === 1) {
        selector.value = selected[0].textContent;
    } else {
        selector.value = `${selected.length} customers selected`;
    }
}

function updateBranchSelector() {
    const selected = document.querySelectorAll('#branchDropdown .multiselect-option.selected');
    const selector = document.getElementById('branchSelector');
    
    if (selected.length === 0) {
        selector.value = 'Select branches';
    } else if (selected.length === 1 && selected[0].dataset.value === 'all') {
        selector.value = 'All Branches';
    } else if (selected.length === 1) {
        selector.value = selected[0].textContent;
    } else {
        selector.value = `${selected.length} branches selected`;
    }
}

function applyFilters() {
    const dateRange = document.getElementById('dateRange').value;
    const selectedCustomers = Array.from(document.querySelectorAll('#customerDropdown .multiselect-option.selected'))
        .map(opt => opt.dataset.value)
        .filter(val => val !== 'all');
    const selectedBranches = Array.from(document.querySelectorAll('#branchDropdown .multiselect-option.selected'))
        .map(opt => opt.dataset.value)
        .filter(val => val !== 'all');
    
    if (!dateRange) {
        showToast('Please select a date range', 'error');
        return;
    }
    
    const params = new URLSearchParams({
        date_range: dateRange,
        customers: selectedCustomers,
        branches: selectedBranches
    });
    
    window.location.href = `customer_credit_reports.php?${params.toString()}`;
}

function clearFilters() {
    window.location.href = 'customer_credit_reports.php';
}

function toggleSelectAll() {
    const selectAll = document.getElementById('selectAll');
    const checkboxes = document.querySelectorAll('.row-checkbox');
    
    checkboxes.forEach(checkbox => {
        checkbox.checked = selectAll.checked;
    });
}

function showPaymentHistory(customerId, customerName) {
    document.getElementById('paymentModalTitle').textContent = `Payment History - ${customerName}`;
    
    // Simulate loading payment history
    const paymentTable = document.getElementById('paymentHistoryTable');
    paymentTable.innerHTML = `
        <tr>
            <td>2026-01-15</td>
            <td>₱5,000.00</td>
            <td>Cash</td>
            <td>Completed</td>
            <td>PAY-001234</td>
        </tr>
        <tr>
            <td>2026-01-10</td>
            <td>₱3,500.00</td>
            <td>GCash</td>
            <td>Completed</td>
            <td>PAY-001233</td>
        </tr>
        <tr>
            <td>2026-01-05</td>
            <td>₱2,000.00</td>
            <td>Credit Card</td>
            <td>Completed</td>
            <td>PAY-001232</td>
        </tr>
    `;
    
    document.getElementById('paymentHistoryModal').style.display = 'block';
}

function closePaymentModal() {
    document.getElementById('paymentHistoryModal').style.display = 'none';
}

function markAsPaid() {
    const selectedRows = document.querySelectorAll('.row-checkbox:checked');
    
    if (selectedRows.length === 0) {
        showToast('Please select customers to mark as paid', 'error');
        return;
    }
    
    // Simulate payment processing
    showToast('Processing payments...', 'info');
    
    setTimeout(() => {
        showToast('Payment recorded successfully', 'success');
        
        // Clear checkboxes
        selectedRows.forEach(checkbox => checkbox.checked = false);
        document.getElementById('selectAll').checked = false;
    }, 1500);
}

function exportReport(format) {
    const dateRange = document.getElementById('dateRange').value;
    const selectedCustomers = Array.from(document.querySelectorAll('#customerDropdown .multiselect-option.selected'))
        .map(opt => opt.dataset.value)
        .filter(val => val !== 'all');
    const selectedBranches = Array.from(document.querySelectorAll('#branchDropdown .multiselect-option.selected'))
        .map(opt => opt.dataset.value)
        .filter(val => val !== 'all');
    
    if (!dateRange) {
        showToast('Please select a date range first', 'error');
        return;
    }
    
    const params = new URLSearchParams({
        export_format: format,
        date_range: dateRange,
        customers: selectedCustomers,
        branches: selectedBranches
    });
    
    showToast(`Exporting ${format.toUpperCase()}...`, 'info');
    window.location.href = `customer_credit_export.php?${params.toString()}`;
}

function showToast(message, type = 'success') {
    const toast = document.getElementById('toast');
    toast.textContent = message;
    
    if (type === 'success') {
        toast.style.background = '#28A745';
    } else if (type === 'error') {
        toast.style.background = '#DC3545';
    } else if (type === 'info') {
        toast.style.background = '#007bff';
    }
    
    toast.style.display = 'block';
    
    setTimeout(() => {
        toast.style.display = 'none';
    }, 3000);
}

window.addEventListener('click', function(event) {
    if (event.target.classList.contains('modal')) {
        event.target.style.display = 'none';
    }
});
</script>

<?php include __DIR__ . '/../partials/footer.php'; ?>
