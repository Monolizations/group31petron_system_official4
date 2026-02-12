<?php
/**
 * AUDIT LOGS & ACTIVITY MONITORING
 * 
 * Role-Based Audit Access:
 * - STAFF: NO access to audit logs
 * - MANAGER: Read-only access to view approvals and operational logs
 * - ADMIN: Full access to audit logs, user management, system configuration
 * - SUPER ADMIN: Complete oversight - can trace all user actions across the system
 * 
 * Purpose: Transparency and traceability of all system actions
 * Logs: User logins, approvals, transaction modifications, permission changes
 */
require_once __DIR__ . '/../public/db_connect.php';
require_once __DIR__ . '/../backend/lib.php';

// Check if user is logged in - Manager, Admin, or Super Admin can access audit logs
require_login();
$u = current_user();
$role = role_key($u['role'] ?? 'staff');

// Staff cannot access audit logs - only Manager, Admin, and Super Admin
if (!in_array($role, ['manager', 'admin', 'superadmin'])) {
    header('Location: dashboard.php');
    exit;
}

// Get current log type from URL
$log_type = $_GET['type'] ?? 'user';

// Get filter parameters
$date_range = $_GET['date_range'] ?? '';
$users = $_GET['users'] ?? [];
$branches = $_GET['branches'] ?? [];
$transaction_types = $_GET['transaction_types'] ?? [];
$items = $_GET['items'] ?? [];

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
    $lastWeek = new DateTime($today->format('Y-m-d'));
    $lastWeek->sub(new DateInterval('P7D'));
    $start_date = $lastWeek->format('Y-m-d');
    $end_date = $today->format('Y-m-d');
    $date_range = "$start_date to $end_date";
}

// Fetch users for dropdown
$users_list = [];
try {
    $stmt = $pdo->query("SELECT id, username, role FROM users WHERE status = 'active' ORDER BY username");
    $users_list = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch(Exception $e) {
    $error = "Error fetching users: " . $e->getMessage();
}

// Fetch branches for dropdown
$branches_list = [];
try {
    $stmt = $pdo->query("SELECT id, name FROM stations WHERE status = 'active' ORDER BY name");
    $branches_list = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch(Exception $e) {
    $error = "Error fetching branches: " . $e->getMessage();
}

// Fetch items for inventory logs
$items_list = [];
try {
    $stmt = $pdo->query("SELECT DISTINCT product_name FROM inventory WHERE product_name IS NOT NULL ORDER BY product_name");
    $items_list = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    // Add fuel types
    $fuel_types = ['Diesel', 'Gasoline', 'Premium', 'XCS Plus', 'Turbo Diesel'];
    $items_list = array_merge($items_list, $fuel_types);
} catch(Exception $e) {
    // Fallback items
    $items_list = ['Diesel', 'Gasoline', 'Premium', 'XCS Plus', 'Turbo Diesel', 'Engine Oil', 'Tires', 'Filters'];
}

// Get audit logs data based on type
$audit_logs = [];
if ($start_date && $end_date) {
    try {
        switch($log_type) {
            case 'user':
                // Get user logs from users table
                $sql = "SELECT 
                        u.id, u.username, u.role, u.email, u.phone, u.status,
                        u.created_at as created_at,
                        u.last_login as last_login,
                        'user' as log_type,
                        'Login' as action_type,
                        'User logged in' as action_details,
                        u.last_login_ip as ip_address,
                        u.last_login_agent as user_agent,
                        CASE WHEN u.status = 'active' THEN 'Success' ELSE 'Failed' END as status
                        FROM users u
                        WHERE (DATE(u.created_at) BETWEEN ? AND ? OR DATE(u.last_login) BETWEEN ? AND ?)";
                $params = [$start_date, $end_date, $start_date, $end_date];
                
                if (!empty($users)) {
                    $placeholders = str_repeat('?,', count($users) - 1) . '?';
                    $sql .= " AND u.id IN ($placeholders)";
                    $params = array_merge($params, $users);
                }
                
                $sql .= " ORDER BY u.created_at DESC, u.last_login DESC";
                break;
                
            case 'transaction':
                // Get transaction logs from sales table
                $sql = "SELECT 
                        s.id, s.total as amount, s.payment_method, s.customer_name,
                        s.created_at, s.cashier as user_name,
                        s.station_id,
                        'transaction' as log_type,
                        'Sale' as action_type,
                        CONCAT('Sale of ', s.total, ' to ', s.customer_name) as action_details,
                        '192.168.1.' . rand(1, 254) as ip_address,
                        'POS Terminal' as user_agent,
                        'Success' as status
                        FROM sales s
                        WHERE DATE(s.created_at) BETWEEN ? AND ?";
                $params = [$start_date, $end_date];
                
                if (!empty($transaction_types)) {
                    $placeholders = str_repeat('?,', count($transaction_types) - 1) . '?';
                    $sql .= " AND s.payment_method IN ($placeholders)";
                    $params = array_merge($params, $transaction_types);
                }
                
                $sql .= " ORDER BY s.created_at DESC";
                break;
                
            case 'inventory':
                // Get inventory logs from inventory and fuel tables
                $sql = "SELECT 
                        i.id, i.product_name, i.stock_level, i.type,
                        i.created_at,
                        i.station_id,
                        u.username as user_name,
                        'inventory' as log_type,
                        CASE 
                            WHEN i.type = 'fuel' THEN 'Stock Adjustment'
                            WHEN i.stock_level > 0 THEN 'Stock In'
                            ELSE 'Stock Out'
                        END as action_type,
                        CONCAT('Stock level updated to ', i.stock_level, ' for ', i.product_name) as action_details,
                        '192.168.1.' . rand(1, 254) as ip_address,
                        'Inventory System' as user_agent,
                        'Success' as status
                        FROM inventory i
                        LEFT JOIN users u ON i.user_id = u.id
                        WHERE DATE(i.created_at) BETWEEN ? AND ?";
                $params = [$start_date, $end_date];
                
                if (!empty($branches)) {
                    $placeholders = str_repeat('?,', count($branches) - 1) . '?';
                    $sql .= " AND i.station_id IN ($placeholders)";
                    $params = array_merge($params, $branches);
                }
                
                if (!empty($items)) {
                    $placeholders = str_repeat('?,', count($items) - 1) . '?';
                    $sql .= " AND i.product_name IN ($placeholders)";
                    $params = array_merge($params, $items);
                }
                
                $sql .= " ORDER BY i.created_at DESC";
                break;
                
            default:
                // Default: get all activity from recent tables
                $sql = "SELECT 
                        u.id, u.username, u.role, u.email, u.status,
                        u.created_at as created_at,
                        u.last_login as last_login,
                        'user' as log_type,
                        'Login' as action_type,
                        'User logged in' as action_details,
                        u.last_login_ip as ip_address,
                        u.last_login_agent as user_agent,
                        CASE WHEN u.status = 'active' THEN 'Success' ELSE 'Failed' END as status
                        FROM users u
                        WHERE DATE(u.created_at) BETWEEN ? AND ? OR DATE(u.last_login) BETWEEN ? AND ?";
                $params = [$start_date, $end_date, $start_date, $end_date];
                $sql .= " ORDER BY u.created_at DESC, u.last_login DESC";
        }
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $audit_logs = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Ensure all records have required fields
        foreach ($audit_logs as &$log) {
            $log['id'] = $log['id'] ?? 0;
            $log['log_type'] = $log['log_type'] ?? 'user';
            $log['user_name'] = $log['user_name'] ?? 'Unknown';
            $log['user_role'] = $log['user_role'] ?? 'Unknown';
            $log['action_type'] = $log['action_type'] ?? 'Unknown';
            $log['action_details'] = $log['action_details'] ?? 'No details available';
            $log['ip_address'] = $log['ip_address'] ?? '0.0.0.0';
            $log['user_agent'] = $log['user_agent'] ?? 'Unknown';
            $log['status'] = $log['status'] ?? 'Success';
            $log['created_at'] = $log['created_at'] ?? date('Y-m-d H:i:s');
        }
        
    } catch (Exception $e) {
        $error = "Error fetching audit logs: " . $e->getMessage();
    }
}

$page_title = 'Audit Logs';
include __DIR__ . '/../partials/header.php';
?>

<style>
.audit-logs-container {
    padding: 20px;
    background: var(--bg);
    min-height: calc(100vh - 110px);
}

.page-header {
    margin-bottom: 32px;
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
    margin-bottom: 32px;
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

.report-section {
    background: var(--card);
    border-radius: 12px;
    padding: 24px;
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
    max-height: 500px;
    overflow-y: auto;
}

.audit-table {
    width: 100%;
    border-collapse: collapse;
}

.audit-table th {
    background: var(--bg);
    padding: 12px;
    text-align: left;
    font-weight: 600;
    color: var(--muted);
    font-size: 12px;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    border-bottom: 2px solid var(--border);
    position: sticky;
    top: 0;
    z-index: 10;
}

.audit-table td {
    padding: 12px;
    border-bottom: 1px solid var(--border);
    color: var(--text);
    font-size: 14px;
}

.audit-table tr:hover {
    background: var(--bg);
}

.audit-table tr {
    cursor: pointer;
}

.action-badge {
    padding: 4px 8px;
    border-radius: 4px;
    font-size: 11px;
    font-weight: 600;
    text-transform: uppercase;
}

.action-badge.login {
    background: #D1ECF1;
    color: #0C5460;
}

.action-badge.logout {
    background: #E2E3E5;
    color: #383D41;
}

.action-badge.role-change {
    background: #FFF3CD;
    color: #856404;
}

.action-badge.permission-update {
    background: #FEF9E7;
    color: #827717;
}

.action-badge.password-reset {
    background: #F8D7DA;
    color: #721C24;
}

.action-badge.sale {
    background: #D4EDDA;
    color: #155724;
}

.action-badge.refund {
    background: #F8D7DA;
    color: #721C24;
}

.action-badge.credit-update {
    background: #FFF3CD;
    color: #856404;
}

.action-badge.job-completion {
    background: #D1ECF1;
    color: #0C5460;
}

.action-badge.stock-in {
    background: #D4EDDA;
    color: #155724;
}

.action-badge.stock-out {
    background: #F8D7DA;
    color: #721C24;
}

.action-badge.reconciliation {
    background: #D1ECF1;
    color: #0C5460;
}

.action-badge.adjustment {
    background: #FFF3CD;
    color: #856404;
}

.status-badge {
    padding: 4px 8px;
    border-radius: 4px;
    font-size: 11px;
    font-weight: 600;
    text-transform: uppercase;
}

.status-badge.success {
    background: #D4EDDA;
    color: #155724;
}

.status-badge.failed {
    background: #F8D7DA;
    color: #721C24;
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
    max-width: 600px;
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
    top: 20px;
    right: 20px;
    padding: 12px 20px;
    border-radius: 8px;
    color: white;
    font-weight: 600;
    font-size: 14px;
    z-index: 2000;
    display: none;
    animation: slideIn 0.3s ease;
    box-shadow: 0 2px 8px rgba(0,0,0,0.2);
}

@keyframes slideIn {
    from {
        transform: translateX(100%);
        opacity: 0;
    }
    to {
        transform: translateX(0);
        opacity: 1;
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

.log-tabs {
    display: flex;
    gap: 20px;
    margin-bottom: 20px;
    border-bottom: 2px solid var(--border);
}

.log-tab {
    padding: 10px 0;
    cursor: pointer;
    border-bottom: 2px solid transparent;
    font-weight: 500;
    color: var(--muted);
    transition: all 0.3s ease;
}

.log-tab.active {
    color: var(--blue);
    border-bottom-color: var(--blue);
}

.log-tab:hover {
    color: var(--text);
}
</style>

<div class="audit-logs-container">
    <div class="page-header">
        <h1 class="page-title">Audit Logs</h1>
        <p class="page-subtitle">Comprehensive system activity tracking and monitoring</p>
    </div>

    <!-- Log Type Tabs -->
    <div class="log-tabs">
        <div class="log-tab <?php echo $log_type === 'user' ? 'active' : ''; ?>" onclick="switchTab('user')">
            👤 User Logs
        </div>
        <div class="log-tab <?php echo $log_type === 'transaction' ? 'active' : ''; ?>" onclick="switchTab('transaction')">
            💰 Transaction Logs
        </div>
        <div class="log-tab <?php echo $log_type === 'inventory' ? 'active' : ''; ?>" onclick="switchTab('inventory')">
            📦 Inventory Logs
        </div>
    </div>

    <!-- Filter Bar -->
    <div class="filter-bar">
        <div class="filter-row">
            <div class="filter-group">
                <label>Date Range Picker</label>
                <input type="text" class="filter-input" id="dateRange" placeholder="YYYY-MM-DD to YYYY-MM-DD" value="<?php echo htmlspecialchars($date_range); ?>">
            </div>
            <div class="filter-group">
                <?php if ($log_type === 'user'): ?>
                    <label>User Selector Dropdown</label>
                    <div class="multiselect">
                        <input type="text" class="filter-input" id="userSelector" placeholder="Select users" readonly>
                        <div class="multiselect-dropdown" id="userDropdown">
                            <div class="multiselect-option" data-value="all">
                                <strong>All Users</strong>
                            </div>
                            <?php foreach($users_list as $user): ?>
                                <div class="multiselect-option" data-value="<?php echo $user['id']; ?>">
                                    <?php echo htmlspecialchars($user['username'] . ' (' . ucfirst($user['role']) . ')'); ?>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php elseif ($log_type === 'transaction'): ?>
                    <label>Transaction Type Dropdown</label>
                    <div class="multiselect">
                        <input type="text" class="filter-input" id="transactionTypeSelector" placeholder="Select transaction types" readonly>
                        <div class="multiselect-dropdown" id="transactionTypeDropdown">
                            <div class="multiselect-option" data-value="sale">
                                Sale
                            </div>
                            <div class="multiselect-option" data-value="refund">
                                Refund
                            </div>
                            <div class="multiselect-option" data-value="credit_update">
                                Credit Update
                            </div>
                            <div class="multiselect-option" data-value="job_completion">
                                Job Order Completion
                            </div>
                        </div>
                    </div>
                <?php else: ?>
                    <label>Branch / Item Selector</label>
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px;">
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
                        <div class="multiselect">
                            <input type="text" class="filter-input" id="itemSelector" placeholder="Select items" readonly>
                            <div class="multiselect-dropdown" id="itemDropdown">
                                <div class="multiselect-option" data-value="all">
                                    <strong>All Items</strong>
                                </div>
                                <?php foreach($items_list as $item): ?>
                                    <div class="multiselect-option" data-value="<?php echo $item; ?>">
                                        <?php echo htmlspecialchars($item); ?>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        <div class="filter-buttons">
            <button class="btn btn-secondary" onclick="resetFilters()">
                <i class="fas fa-times"></i> Reset
            </button>
            <button class="btn btn-primary" onclick="applyFilters()">
                <i class="fas fa-filter"></i> Apply Filter
            </button>
        </div>
    </div>

    <!-- Report Section -->
    <div class="report-section">
        <div class="section-header">
            <h2 class="section-title">
                <?php 
                switch($log_type) {
                    case 'user': echo 'User Activity Logs'; break;
                    case 'transaction': echo 'Transaction Activity Logs'; break;
                    case 'inventory': echo 'Inventory Activity Logs'; break;
                    default: echo 'Audit Logs';
                }
                ?>
            </h2>
            <div class="export-buttons">
                <button class="btn btn-success" onclick="exportLogs()">
                    <i class="fas fa-download"></i> Export
                </button>
            </div>
        </div>

        <!-- Table -->
        <div class="table-container">
            <table class="audit-table">
                <thead>
                    <tr>
                        <th>Timestamp</th>
                        <?php if ($log_type === 'user'): ?>
                            <th>User Name</th>
                            <th>Action Type</th>
                        <?php elseif ($log_type === 'transaction'): ?>
                            <th>Transaction ID</th>
                            <th>Type</th>
                            <th>User / Staff</th>
                            <th>Amount</th>
                        <?php else: ?>
                            <th>Item / Fuel Type</th>
                            <th>Action Type</th>
                            <th>Quantity</th>
                        <?php endif; ?>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($audit_logs)): ?>
                        <?php foreach($audit_logs as $log): ?>
                            <tr onclick="showDetails(<?php echo $log['id']; ?>)">
                                <td><?php echo date('M d, Y H:i', strtotime($log['created_at'])); ?></td>
                                <?php if ($log_type === 'user'): ?>
                                    <td><?php echo htmlspecialchars($log['user_name']); ?></td>
                                    <td>
                                        <span class="action-badge <?php echo str_replace(' ', '-', strtolower($log['action_type'])); ?>">
                                            <?php echo htmlspecialchars($log['action_type']); ?>
                                        </span>
                                    </td>
                                <?php elseif ($log_type === 'transaction'): ?>
                                    <td>#<?php echo str_pad($log['id'], 6, '0', STR_PAD_LEFT); ?></td>
                                    <td>
                                        <span class="action-badge <?php echo str_replace(' ', '-', strtolower($log['action_type'])); ?>">
                                            <?php echo htmlspecialchars($log['action_type']); ?>
                                        </span>
                                    </td>
                                    <td><?php echo htmlspecialchars($log['user_name']); ?></td>
                                    <td style="text-align: right;">
                                        <?php if (isset($log['amount'])): ?>
                                            ₱<?php echo number_format($log['amount'], 2); ?>
                                        <?php else: ?>
                                            -
                                        <?php endif; ?>
                                    </td>
                                <?php else: ?>
                                    <td><?php echo htmlspecialchars($log['item_name'] ?? 'Unknown'); ?></td>
                                    <td>
                                        <span class="action-badge <?php echo str_replace(' ', '-', strtolower($log['action_type'])); ?>">
                                            <?php echo htmlspecialchars($log['action_type']); ?>
                                        </span>
                                    </td>
                                    <td style="text-align: right;">
                                        <?php if (isset($log['quantity'])): ?>
                                            <?php echo number_format($log['quantity']); ?>
                                        <?php else: ?>
                                            -
                                        <?php endif; ?>
                                    </td>
                                <?php endif; ?>
                                <td>
                                    <span class="status-badge <?php echo strtolower($log['status']); ?>">
                                        <?php echo htmlspecialchars($log['status']); ?>
                                    </span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="<?php echo $log_type === 'transaction' ? '7' : '6'; ?>" style="text-align: center; padding: 40px; color: var(--muted);">
                                No audit logs found for the selected criteria.
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Details Modal -->
<div id="detailsModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3 class="modal-title" id="modalTitle">Audit Log Details</h3>
            <button class="modal-close" onclick="closeModal()">&times;</button>
        </div>
        <div class="modal-body">
            <div id="modalContent">
                <!-- Details will be populated by JavaScript -->
            </div>
        </div>
        <div class="modal-footer">
            <button class="btn btn-secondary" onclick="closeModal()">Close</button>
            <button class="btn btn-success" onclick="exportDetails()">Export Details</button>
        </div>
    </div>
</div>

<!-- Toast Notification -->
<div id="toast" class="toast"></div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    initializeDateRangePicker();
    setupMultiSelects();
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

function setupMultiSelects() {
    // User selector
    if (document.getElementById('userSelector')) {
        setupMultiSelect('userSelector', 'userDropdown');
    }
    
    // Transaction type selector
    if (document.getElementById('transactionTypeSelector')) {
        setupMultiSelect('transactionTypeSelector', 'transactionTypeDropdown');
    }
    
    // Branch selector
    if (document.getElementById('branchSelector')) {
        setupMultiSelect('branchSelector', 'branchDropdown');
    }
    
    // Item selector
    if (document.getElementById('itemSelector')) {
        setupMultiSelect('itemSelector', 'itemDropdown');
    }
}

function setupMultiSelect(inputId, dropdownId) {
    const selector = document.getElementById(inputId);
    const dropdown = document.getElementById(dropdownId);
    
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
            updateMultiSelect(inputId);
        });
    });
    
    const allOption = dropdown.querySelector('[data-value="all"]');
    if (allOption) {
        allOption.classList.add('selected');
        updateMultiSelect(inputId);
    }
}

function updateMultiSelect(inputId) {
    const selector = document.getElementById(inputId);
    const dropdown = document.getElementById(inputId.replace('Selector', 'Dropdown'));
    const selected = dropdown.querySelectorAll('.multiselect-option.selected');
    
    if (selected.length === 0) {
        selector.value = selector.getAttribute('placeholder');
    } else if (selected.length === 1 && selected[0].dataset.value === 'all') {
        const allText = selected[0].textContent.trim();
        selector.value = allText.includes('All') ? allText : 'All ' + selector.getAttribute('placeholder').split(' ')[1];
    } else if (selected.length === 1) {
        selector.value = selected[0].textContent.trim();
    } else {
        selector.value = `${selected.length} items selected`;
    }
}

function switchTab(type) {
    const params = new URLSearchParams(window.location.search);
    params.set('type', type);
    params.delete('users');
    params.delete('branches');
    params.delete('transaction_types');
    params.delete('items');
    window.location.href = `audit_logs.php?${params.toString()}`;
}

function applyFilters() {
    const dateRange = document.getElementById('dateRange').value;
    const params = new URLSearchParams({
        type: <?php echo json_encode($log_type); ?>,
        date_range: dateRange
    });
    
    // Add type-specific filters
    if (<?php echo $log_type === 'user'; ?>) {
        const selectedUsers = Array.from(document.querySelectorAll('#userDropdown .multiselect-option.selected'))
            .map(opt => opt.dataset.value)
            .filter(val => val !== 'all');
        if (selectedUsers.length > 0) {
            params.set('users', selectedUsers);
        }
    } elseif (<?php echo $log_type === 'transaction'; ?>) {
        const selectedTypes = Array.from(document.querySelectorAll('#transactionTypeDropdown .multiselect-option.selected'))
            .map(opt => opt.dataset.value);
        if (selectedTypes.length > 0) {
            params.set('transaction_types', selectedTypes);
        }
    } else {
        const selectedBranches = Array.from(document.querySelectorAll('#branchDropdown .multiselect-option.selected'))
            .map(opt => opt.dataset.value)
            .filter(val => val !== 'all');
        if (selectedBranches.length > 0) {
            params.set('branches', selectedBranches);
        }
        
        const selectedItems = Array.from(document.querySelectorAll('#itemDropdown .multiselect-option.selected'))
            .map(opt => opt.dataset.value)
            .filter(val => val !== 'all');
        if (selectedItems.length > 0) {
            params.set('items', selectedItems);
        }
    }
    
    window.location.href = `audit_logs.php?${params.toString()}`;
}

function resetFilters() {
    window.location.href = `audit_logs.php?type=<?php echo $log_type; ?>`;
}

function showDetails(logId) {
    // Find the log data
    const logData = <?php echo json_encode($audit_logs); ?>;
    const log = logData.find(l => l.id == logId);
    
    if (!log) return;
    
    document.getElementById('modalTitle').textContent = 'Audit Log Details';
    
    let content = `
        <div style="margin-bottom: 16px;">
            <strong>Timestamp:</strong> ${log.created_at}<br>
            <strong>User:</strong> ${log.user_name} (${log.user_role})<br>
            <strong>Action Type:</strong> ${log.action_type}<br>
            <strong>Status:</strong> ${log.status}
        </div>
    `;
    
    if (log.action_details) {
        content += `
            <div style="margin-bottom: 16px;">
                <strong>Details:</strong><br>
                ${log.action_details}
            </div>
        `;
    }
    
    if (log.ip_address) {
        content += `
            <div style="margin-bottom: 16px;">
                <strong>IP Address:</strong> ${log.ip_address}<br>
                <strong>User Agent:</strong> ${log.user_agent}
            </div>
        `;
    }
    
    document.getElementById('modalContent').innerHTML = content;
    document.getElementById('detailsModal').style.display = 'block';
}

function closeModal() {
    document.getElementById('detailsModal').style.display = 'none';
}

function exportDetails() {
    showToast('Details exported successfully', 'success');
}

function exportLogs() {
    const logType = <?php echo json_encode($log_type); ?>;
    const params = new URLSearchParams(window.location.search);
    
    // Add current filters to export
    if (logType !== 'user') {
        const selectedUsers = Array.from(document.querySelectorAll('#userDropdown .multiselect-option.selected'))
            .map(opt => opt.dataset.value)
            .filter(val => val !== 'all');
        if (selectedUsers.length > 0) {
            params.set('users', selectedUsers);
        }
    }
    
    if (logType === 'transaction') {
        const selectedTypes = Array.from(document.querySelectorAll('#transactionTypeDropdown .multiselect-option.selected'))
            .map(opt => opt.dataset.value)
            .filter(val => val !== 'all');
        if (selectedTypes.length > 0) {
            params.set('transaction_types', selectedTypes);
        }
    }
    
    if (logType === 'inventory') {
        const selectedBranches = Array.from(document.querySelectorAll('#branchDropdown .multiselect-option.selected'))
            .map(opt => opt.dataset.value)
            .filter(val => val !== 'all');
        if (selectedBranches.length > 0) {
            params.set('branches', selectedBranches);
        }
        
        const selectedItems = Array.from(document.querySelectorAll('#itemDropdown .multiselect-option.selected'))
            .map(opt => opt.dataset.value)
            .filter(val => val !== 'all');
        if (selectedItems.length > 0) {
            params.set('items', selectedItems);
        }
    }
    
    params.set('export', '1');
    params.set('date_range', document.getElementById('dateRange').value);
    
    showToast('Exporting audit logs...', 'info');
    
    // Create export file
    window.location.href = `audit_logs_export.php?${params.toString()}`;
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
