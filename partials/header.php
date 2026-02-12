<?php
require_once __DIR__ . '/../backend/lib.php';
require_once __DIR__ . '/../public/db_connect.php';
require_login();
$user = $_SESSION['user'];

// Get current page ID from filename
$page_id = basename($_SERVER['PHP_SELF'], '.php');
// Normalize Role to ensure sidebar works correctly regardless of DB casing / naming
// Supports: manager/supervisor, operations_staff, etc.
$role = function_exists('role_key') ? role_key($user['role'] ?? '') : strtolower(trim($user['role'] ?? 'staff'));

// --- FETCH ALERTS FOR DROPDOWN ---
$header_alerts = [];
if(in_array($role, ['superadmin','admin','manager'])){
    // 1. Failed Logins (Super Admin only)
    if($role === 'superadmin'){
        try {
            $failed_logins = $pdo->query("SELECT user_id, details, created_at FROM activity_logs WHERE action = 'Login Failed' AND created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR) ORDER BY created_at DESC LIMIT 5")->fetchAll();
            foreach($failed_logins as $fl) $header_alerts[] = ['msg'=>"Failed Login: " . htmlspecialchars($fl['details']), 'time'=>$fl['created_at'], 'link'=>'users.php'];
        } catch(Exception $e){}
    }
    // 2. Password Expirations
    try {
        $expiring_passwords = $pdo->query("SELECT username FROM users WHERE password_expires_at < NOW() AND status = 'active' LIMIT 5")->fetchAll();
        foreach($expiring_passwords as $ep) $header_alerts[] = ['msg'=>"Password Expired: {$ep['username']}", 'time'=>'Now', 'link'=>'users.php'];
    } catch(Exception $e){}
    // 3. Reconciliation Delays (Super Admin only)
    if($role === 'superadmin'){
        try {
            // Assuming reconciliation is daily, check if today's reconciliation is missing for any station
            $today = date('Y-m-d');
            $missing_recons = $pdo->query("SELECT s.name FROM stations s LEFT JOIN reconciliation_results r ON s.id = r.station_id AND r.recon_date = '$today' WHERE r.id IS NULL LIMIT 5")->fetchAll();
            foreach($missing_recons as $mr) $header_alerts[] = ['msg'=>"Reconciliation Delay: {$mr['name']}", 'time'=>'Today', 'link'=>'reconciliation.php'];
        } catch(Exception $e){}
    }
    // 4. Anomalies Detected
    $sales_data = read_json('sales.json', []);
    foreach($sales_data as $s){
        if(($s['total'] > 10000 || $s['total'] == 0)) $header_alerts[] = ['msg'=>"Anomaly Detected: ₱".number_format($s['total']), 'time'=>$s['date']??'', 'link'=>'transactions.php'];
    }
    // 5. Inventory (keep existing)
    try {
        $inv = $pdo->query("SELECT product_name FROM inventory WHERE stock_level <= 20 LIMIT 5")->fetchAll();
        foreach($inv as $i) $header_alerts[] = ['msg'=>"Low Stock: {$i['product_name']}", 'time'=>'Now', 'link'=>'oversight.php'];
    } catch(Exception $e){}
    // 6. Pending Jobs (keep existing)
    try {
        $pjobs = $pdo->query("SELECT id FROM job_orders WHERE status='Pending' LIMIT 5")->fetchAll();
        foreach($pjobs as $j) $header_alerts[] = ['msg'=>"Pending Job #{$j['id']}", 'time'=>'Now', 'link'=>'joborder_stats.php'];
    } catch(Exception $e){}
    // 7. Pending Purchase Orders
    try {
        $pending_po = $pdo->query("SELECT id FROM purchase_orders WHERE status = 'pending' LIMIT 5")->fetchAll();
        foreach($pending_po as $po) $header_alerts[] = ['msg'=>"Pending PO #{$po['id']}", 'time'=>'Now', 'link'=>'purchase_order.php'];
    } catch(Exception $e){}
    // 8. Pending Deliveries
    try {
        $pending_deliveries = $pdo->query("SELECT id FROM receiving WHERE status = 'pending' LIMIT 5")->fetchAll();
        foreach($pending_deliveries as $d) $header_alerts[] = ['msg'=>"Pending Delivery #{$d['id']}", 'time'=>'Now', 'link'=>'supplier_confirmation.php'];
    } catch(Exception $e){}
    // 9. Credit Warnings
    try {
        $credit_warnings = $pdo->query("SELECT name FROM customers WHERE credit_balance > 0 LIMIT 5")->fetchAll();
        foreach($credit_warnings as $cw) $header_alerts[] = ['msg'=>"Credit Warning: {$cw['name']}", 'time'=>'Now', 'link'=>'customer_credit.php'];
    } catch(Exception $e){}
    // 10. Fuel Variance (keep existing)
    $fuel_readings = read_json('fuel_readings.json', []);
    foreach($fuel_readings as $fr) {
        if(($fr['computed_liters'] ?? 0) < 0) {
             if($role !== 'superadmin' && ($fr['station_id']??'') != $myStationId) continue;
             $header_alerts[] = ['msg'=>"Fuel Variance: Station " . ($fr['station_id']??'?'), 'time'=>$fr['date']??'', 'link'=>'oversight.php'];
        }
    }
}
$header_alerts = array_slice($header_alerts, 0, 5);
$unread_alerts = count($header_alerts);

// --- BADGE LOGIC ---
$badges = [];
$myStationId = user_station_id();
$station_name = '';
$current_date = date('Y-m-d');
$hour = (int)date('H');
$shift = ($hour < 12) ? 'Morning' : (($hour < 18) ? 'Afternoon' : 'Evening');

// Get station name for all non-superadmin users
if ($myStationId && in_array($role, ['admin', 'manager', 'staff'])) {
    try {
        $stmt = $pdo->prepare("SELECT name FROM stations WHERE id = ?");
        $stmt->execute([$myStationId]);
        $station_name = $stmt->fetchColumn() ?: 'Unknown Station';
    } catch (Exception $e) {
        $station_name = 'Unknown Station';
    }
}

// 1. Transactions / Anomalies (JSON)
if (in_array($role, ['superadmin','admin','manager'])) {
    $sales_data = read_json('sales.json', []);
    $anomalies_count = 0;
    $station_anomalies = 0;
    foreach ($sales_data as $s) {
        $amt = (float)($s['total'] ?? 0);
        if ($amt > 10000 || $amt == 0) {
            $anomalies_count++;
            if (($s['station_id'] ?? '') == $myStationId) {
                $station_anomalies++;
            }
        }
    }
    if ($role === 'superadmin') {
        $badges['transactions'] = $anomalies_count;
    } elseif ($role === 'admin' || $role === 'manager') {
        $badges['pos'] = $station_anomalies;
    }
}

// 2. Job Orders & Users (DB)
try {
    if ($role === 'superadmin') {
        $badges['joborder_stats'] = $pdo->query("SELECT COUNT(*) FROM job_orders WHERE status = 'Pending'")->fetchColumn();
        $badges['users'] = $pdo->query("SELECT COUNT(*) FROM users WHERE status = 'inactive'")->fetchColumn();
        
        // Inventory Shortages (Oversight)
        $shortages_count = $pdo->query("SELECT COUNT(*) FROM inventory WHERE stock_level <= 20")->fetchColumn();
        $badges['oversight'] = $shortages_count;

        // Reports aggregates all anomalies/action items
        $badges['reports'] = ($badges['transactions'] ?? 0) + $badges['joborder_stats'] + $shortages_count;
    } elseif ($role === 'admin' || $role === 'manager') {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM job_orders WHERE station_id = ? AND status = 'Pending'");
        $stmt->execute([$myStationId]);
        $badges['joborder'] = $stmt->fetchColumn();
        
        // Inventory Shortages
        $stmtInv = $pdo->prepare("SELECT COUNT(*) FROM inventory WHERE station_id = ? AND stock_level <= 20");
        $stmtInv->execute([$myStationId]);
        $badges['inventory'] = $stmtInv->fetchColumn();

        // Reports Aggregate
        $badges['reports'] = ($badges['pos'] ?? 0) + ($badges['joborder'] ?? 0) + ($badges['inventory'] ?? 0);
    } elseif ($role === 'staff') {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM job_orders WHERE user_id = ? AND status IN ('Pending', 'In Progress')");
        $stmt->execute([$user['id']]);
        $badges['joborder'] = $stmt->fetchColumn();
    }

    // Fetch Stations for Header Filter (Super Admin)
    $header_stations = [];
    if ($role === 'superadmin') {
        try {
            $header_stations = $pdo->query("SELECT id, name FROM stations ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {}
    }
} catch (Exception $e) { /* Tables might not exist yet */ }

// --- SYSTEM STATUS CHECK ---
$db_connection_status = 'OK';
$db_connection_color = 'var(--petron-green)';
// db_connect.php throws an exception, so if we're here, the initial connection was fine.
// This is a fallback check.
if (!isset($pdo) || !$pdo) {
    $db_connection_status = 'Error';
    $db_connection_color = 'var(--petron-red)';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Petron Management System</title>
  <link rel="stylesheet" href="../assets/css/style.css" />
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <style>
    /* Petron Station Global Theme */
    :root {
        --petron-blue: #002F6C;
        --petron-red: #E30613;
        --petron-gray: #CCCCCC;
        --petron-yellow: #FFD700;
        --petron-green: #28A745;
    }
    /* Sidebar Navigation */
    .sidebar { 
        background-color: var(--petron-blue) !important; 
        color: #ffffff !important;
    }
    
    /* Desktop Sidebar Layout (Header + Sidebar Integration, Fixed Footer) */
    @media (min-width: 992px) {
        body { overflow: hidden; }

        .top-header {
            position: fixed;
            top: 0; left: 0;
            width: 100%;
            height: 70px;
            z-index: 1002;
            background-color: #ffffff;
            padding: 0; /* Reset padding to handle split bg */
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .sidebar {
            display: flex;
            flex-direction: column;
            position: fixed;
            top: 70px;
            left: 0;
            bottom: 0; /* Extend to bottom of viewport */
            width: 250px;
            z-index: 1001;
            overflow-y: auto; /* Unlimited vertical scrolling */
            overflow-x: hidden; /* Prevent horizontal scrolling */
            background: var(--sidebar-bg);
            border-right: 1px solid var(--line);
        }
        .sidebar-menu { flex: 1 1 auto; overflow-y: visible; } /* Allow unlimited scrolling */ 

        .main {
            margin-left: 250px;
            margin-top: 70px;
            height: calc(100vh - 70px); /* Viewport - Header only */
            overflow-y: auto; /* Main content scrolls vertically */
            overflow-x: hidden; /* Prevent horizontal scrolling */
            padding: 20px 20px 60px 20px; /* Added bottom padding for fixed footer */
        }
    }

    .brand-title { color: var(--petron-blue) !important; font-weight: bold; font-size: 1.3em; line-height: 1.1; }
    .brand-mark {
        width: 40px; height: 40px;
        margin-right: 10px;
        object-fit: contain;
    }

    /* Page Header Styles - UPPERCASE for All Roles (Super Admin, Admin, Manager, Staff) */
    .page-head, .page-header {
        margin-bottom: 20px;
    }
    
    .page-head h1, .page-header h1,
    .page-head .h1, .page-header .h1,
    .page-title, .page-head .page-title, .page-header .page-title {
        text-transform: uppercase !important;
        font-size: 24px !important;
        font-weight: 700 !important;
        color: var(--petron-blue) !important;
        margin: 0 0 8px 0 !important;
        letter-spacing: 0.5px !important;
    }
    
    .page-subtitle, .page-head .sub, .page-header .page-subtitle {
        text-transform: uppercase !important;
        font-size: 14px !important;
        font-weight: 500 !important;
        color: #666666 !important;
        margin: 0 !important;
        letter-spacing: 0.3px !important;
    }
    
    /* Additional header elements to ensure consistency */
    h1, h2, h3 {
        text-transform: uppercase !important;
        color: var(--petron-blue) !important;
        letter-spacing: 0.3px !important;
    }
    
    h1 {
        font-size: 24px !important;
        font-weight: 700 !important;
    }
    
    h2 {
        font-size: 20px !important;
        font-weight: 600 !important;
    }
    
    h3 {
        font-size: 18px !important;
        font-weight: 600 !important;
    }

    .nav-item { color: #eeeeee !important; transition: all 0.2s; display: flex; align-items: center; padding: 10px 15px; text-decoration: none; }
    .nav-item:hover { background-color: rgba(255,255,255,0.1) !important; color: #ffffff !important; }
    .nav-item.active { background-color: var(--petron-red) !important; color: #ffffff !important; }
    
    /* Sub-menu for sidebar */
    .nav-item .arrow { margin-left: auto; transition: transform 0.2s ease; }
    .nav-item.open > .arrow { transform: rotate(90deg); }
    .sub-menu { display: none; list-style: none; padding: 0; margin: 0; background: rgba(0,0,0,0.2); }
    .sub-menu a { display: block; padding: 10px 15px 10px 49px; color: #eee; text-decoration: none; font-size: 0.9em; }
    .sub-menu a:hover { background: rgba(0,0,0,0.3); }
    .sub-menu a.active { color: white; font-weight: bold; background: rgba(0,0,0,0.1); }

    /* Notifications Dropdown */
    .notification-bell { position: relative; cursor: pointer; color: var(--petron-blue); font-size: 1.2rem; padding: 5px; display: inline-block; }
    .notification-bell .badge { position: absolute; top: -5px; right: -5px; background-color: red; color: white; border-radius: 50%; padding: 2px 5px; font-size: 10px; font-weight: bold; min-width: 15px; text-align: center; pointer-events: none; }
    .notif-dropdown {
        display: none;
        position: absolute;
        right: 0;
        top: 100%;
        background: #fff;
        box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        border-radius: 8px;
        width: 350px;
        z-index: 1100;
        border: 1px solid #eee;
        margin-top: 5px;
    }
    .notif-dropdown.show { display: block !important; }
    
    .notif-dropdown-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 12px 16px;
        border-bottom: 1px solid #eee;
        background: #f8fafc;
        border-radius: 8px 8px 0 0;
    }
    
    .notif-dropdown-footer {
        border-top: 1px solid #eee;
        background: #f8fafc;
        border-radius: 0 0 8px 8px;
    }
    
    .notif-empty {
        padding: 30px 20px;
        text-align: center;
        color: #888;
    }
    
    .notif-item {
        display: flex;
        align-items: center;
        gap: 12px;
        padding: 12px 16px;
        border-bottom: 1px solid #f4f4f4;
        cursor: pointer;
        transition: background-color 0.2s;
        position: relative;
    }
    
    .notif-item:hover {
        background: #f8fafc;
    }
    
    .notif-item.unread {
        background: rgba(37, 99, 235, 0.05);
    }
    
    .notif-item.unread::before {
        content: '';
        position: absolute;
        left: 4px;
        top: 50%;
        transform: translateY(-50%);
        width: 6px;
        height: 6px;
        background: #2563eb;
        border-radius: 50%;
    }
    
    .notif-icon {
        flex-shrink: 0;
    }
    
    .notif-type-icon {
        width: 28px;
        height: 28px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 12px;
        font-weight: bold;
        color: white;
    }
    
    .notif-type-icon i {
        font-size: 12px;
        margin: 0;
        padding: 0;
        line-height: 1;
    }
    
    .notif-type-icon.icon-success {
        background: #16a34a;
    }
    
    .notif-type-icon.icon-warning {
        background: #d97706;
    }
    
    .notif-type-icon.icon-error {
        background: #dc2626;
    }
    
    .notif-type-icon.icon-info {
        background: #2563eb;
    }
    
    .notif-content {
        flex: 1;
        min-width: 0;
    }
    
    .notif-title {
        font-size: 13px;
        font-weight: 500;
        color: #333;
        line-height: 1.3;
        margin-bottom: 2px;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }
    
    .notif-time {
        font-size: 11px;
        color: #888;
    }
    
    .notif-status {
        width: 8px;
        height: 8px;
        border-radius: 50%;
        flex-shrink: 0;
    }
    
    .notif-status.unread {
        background: #2563eb;
    }
    
    .notif-status.read {
        background: transparent;
    }

    /* Top Header */
    .top-header {
        display: flex;
        align-items: center;
        background-color: #ffffff;
        padding: 0 20px;
    }
    .header-left {
        display: flex;
        align-items: center;
        gap: 10px;
        flex-shrink: 0;
    }
    .header-center {
        flex-grow: 1;
        display: flex;
        justify-content: center;
        align-items: center;
    }
    .header-right {
        display: flex;
        align-items: center;
        gap: 20px;
        flex-shrink: 0;
    }
    .profile-access {
        position: relative;
        cursor: pointer;
        display: flex;
        align-items: center;
        gap: 8px;
        font-weight: 600;
        color: var(--petron-blue);
    }
    .profile-dropdown {
        display: none;
        position: absolute;
        right: 0;
        top: 100%;
        background: #fff;
        box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        border-radius: 4px;
        min-width: 180px;
        z-index: 1100;
        border: 1px solid #eee;
        margin-top: 5px;
    }
    .profile-dropdown a {
        display: block;
        padding: 10px 15px;
        text-decoration: none;
        color: #333;
        font-size: 14px;
    }
    .profile-dropdown a:hover {
        background: #f9f9f9;
        color: var(--petron-blue);
    }
    .profile-dropdown.show { display: block !important; }
    .profile-dropdown .dropdown-divider { height: 1px; margin: .5rem 0; overflow: hidden; background-color: #e9ecef; }

    /* Settings Icon */
    .settings-icon {
        color: var(--petron-blue);
        font-size: 1.2rem;
        text-decoration: none;
        cursor: pointer;
        transition: color 0.2s;
    }
    .settings-icon:hover {
        color: var(--petron-red);
    }
  </style>
</head>
<body class="app" data-page="<?php echo htmlspecialchars($page_id); ?>" data-role="<?php echo htmlspecialchars($role); ?>">
  <!-- Debug Info (remove after fixing) -->
  <aside class="sidebar">
    <div class="sidebar-menu">
      <nav class="nav">
<?php
  $items = [];

  if($role === 'superadmin'){
    $items = [
      // Dashboard
      ['id'=>'manager_dashboard','label'=>'Dashboard','ico'=>'fas fa-gauge','href'=>'manager_dashboard.php'],

      // Station Management
      ['id'=>'station_management','label'=>'Station Management','ico'=>'fas fa-building','href'=>'#','sub_items' => [
          ['id'=>'view_stations','label'=>'View Stations','href'=>'view_stations.php'],
          ['id'=>'station_profiles','label'=>'Station Profiles','href'=>'station_profiles.php'],
          ['id'=>'station_status','label'=>'Station Status','href'=>'station_status.php']
      ]],

      // User Management (HQ)
      ['id'=>'user_management','label'=>'User Management (HQ)','ico'=>'fas fa-users-cog','href'=>'#','sub_items' => [
          ['id'=>'create_station_admin','label'=>'Create Station Admin','href'=>'create_station_admin.php'],
          ['id'=>'create_default_roles','label'=>'Auto-create Default Manager & Staff','href'=>'auto_create_defaults.php'],
          ['id'=>'view_all_users','label'=>'View All Users','href'=>'view_all_users.php']
      ]],

      // Nationwide Reports
      ['id'=>'nationwide_reports','label'=>'Nationwide Reports','ico'=>'fas fa-chart-line','href'=>'#','sub_items' => [
          ['id'=>'sales_reports','label'=>'Sales Reports','href'=>'sales_reports.php'],
          ['id'=>'fuel_reports','label'=>'Fuel Reconciliation Reports','href'=>'reconciliation.php'],
          ['id'=>'job_order_reports','label'=>'Job Order Reports','href'=>'joborder.php'],
          ['id'=>'customer_credit_reports','label'=>'Customer Credit Reports','href'=>'customer_credit_reports.php']
      ]],

      // Audit Logs
      ['id'=>'audit_logs','label'=>'Audit Logs','ico'=>'fas fa-clipboard-list','href'=>'#','sub_items' => [
          ['id'=>'user_logs','label'=>'User Logs','href'=>'audit_logs.php?type=user'],
          ['id'=>'transaction_logs','label'=>'Transaction Logs','href'=>'audit_logs.php?type=transaction'],
          ['id'=>'inventory_logs','label'=>'Inventory Logs','href'=>'audit_logs.php?type=inventory']
      ]],

      // System Settings
      ['id'=>'system_settings','label'=>'System Settings','ico'=>'fas fa-cogs','href'=>'#','sub_items' => [
          ['id'=>'service_rates','label'=>'Service Rate Masterlist','href'=>'settings.php?section=service_rates'],
          ['id'=>'calibration_values','label'=>'Fuel Calibration Values','href'=>'settings.php?section=calibration']
      ]],

      // Developer Panel
      ['id'=>'developer_panel','label'=>'Developer Panel','ico'=>'fas fa-code','href'=>'developer_panel.php'],

      ['id'=>'reports','label'=>'Reports','ico'=>'fas fa-file-alt','href'=>'#','sub_items' => [
          ['id'=>'daily_sales','label'=>'Daily Sales','href'=>'reports.php?view=daily_sales'],
          ['id'=>'shift_reports','label'=>'Shift Reports','href'=>'reports.php?view=shift_reports'],
          ['id'=>'inventory_reports','label'=>'Inventory Reports','href'=>'reports.php?view=inventory_reports'],
          ['id'=>'job_order_reports','label'=>'Job Order Reports','href'=>'reports.php?view=job_order_reports'],
          ['id'=>'verification','label'=>'Verification','href'=>'reports.php?view=verification']
      ]]
    ];

  }elseif($role === 'admin'){
    $items = [
      // Dashboard
      ['id'=>'dashboard','label'=>'Dashboard','ico'=>'fas fa-gauge','href'=>'dashboard.php'],

      // Price Management
      ['id'=>'price_management','label'=>'Price Management','ico'=>'fas fa-tag','href'=>'#','sub_items' => [
          ['id'=>'propose_prices','label'=>'Propose Price Changes','href'=>'admin_propose_prices.php'],
      ]],

      // Inventory Management
      ['id'=>'inventory_management','label'=>'Inventory Management','ico'=>'fas fa-box','href'=>'#','sub_items' => [
          ['id'=>'stock_requests','label'=>'Stock Requests','href'=>'stock_requests.php'],
          ['id'=>'approve_stock_requests','label'=>'Approve Stock Requests','href'=>'admin_approve_stock_requests.php'],
          ['id'=>'supplier_po_confirm','label'=>'Confirm PO with Supplier','href'=>'supplier_po_confirmation.php'],
          ['id'=>'supplier_delivery','label'=>'Supplier Delivery Tracking','href'=>'supplier_delivery_tracking.php'],
          ['id'=>'receive_stock','label'=>'Receive & Confirm Stock','href'=>'stock_receiving_confirmation.php'],
          ['id'=>'confirm_supplier_delivery','label'=>'Supplier Confirmation','href'=>'supplier_confirmation.php'],
          ['id'=>'receive_items','label'=>'Receiving & Feedback','href'=>'receiving.php'],
          ['id'=>'view_inventory_history','label'=>'Inventory History','href'=>'inventory_history.php']
      ]],

      // Fuel Finalization (Admin/Owner)
      ['id'=>'fuel_finalization','label'=>'Fuel Reconciliation - Finalize','ico'=>'fas fa-lock','href'=>'reconciliation_admin.php'],

      // Transactions (POS)
      ['id'=>'transactions','label'=>'Transactions (POS)','ico'=>'fas fa-shopping-cart','href'=>'#','sub_items' => [
          ['id'=>'create_transaction','label'=>'New Transaction','href'=>'pos.php'],
          ['id'=>'post_credit_sale','label'=>'Credit (Utang) Transactions','href'=>'credit_transactions.php'],
          ['id'=>'view_transactions','label'=>'Transaction History','href'=>'transactions.php']
      ]],

      // Job Order Management
      ['id'=>'job_order_management','label'=>'Job Order Management','ico'=>'fas fa-wrench','href'=>'#','sub_items' => [
          ['id'=>'view_pending_job_orders','label'=>'Pending Job Orders','href'=>'joborder.php?tab=pending'],
          ['id'=>'view_ongoing_job_orders','label'=>'Ongoing Job Orders','href'=>'joborder.php?tab=ongoing'],
          ['id'=>'view_completed_job_orders','label'=>'Completed Job Orders','href'=>'joborder.php?tab=history']
      ]],

      // Customer Accounts
      ['id'=>'customer_accounts','label'=>'Customer Accounts','ico'=>'fas fa-users','href'=>'#','sub_items' => [
          ['id'=>'view_customers','label'=>'Customer List','href'=>'customers.php'],
          ['id'=>'view_customer_credit','label'=>'Credit Ledger','href'=>'customer_credit.php'],
          ['id'=>'generate_customer_statement','label'=>'Statements of Account','href'=>'customer_statements.php']
      ]],

      // User Management
      ['id'=>'user_management','label'=>'User Management','ico'=>'fas fa-user-cog','href'=>'users.php'],

      // Reports
      ['id'=>'reports','label'=>'Reports','ico'=>'fas fa-file-alt','href'=>'#','sub_items' => [
          ['id'=>'daily_sales','label'=>'Daily Sales','href'=>'reports.php?view=daily_sales'],
          ['id'=>'shift_reports','label'=>'Shift Reports','href'=>'reports.php?view=shift_reports'],
          ['id'=>'inventory_reports','label'=>'Inventory Reports','href'=>'reports.php?view=inventory_reports'],
          ['id'=>'job_order_reports','label'=>'Job Order Reports','href'=>'reports.php?view=job_order_reports'],
          ['id'=>'verification','label'=>'Verification','href'=>'reports.php?view=verification']
      ]]
    ];
 
 } elseif($role === 'manager'){
    $items = [
      ['id'=>'manager_home','label'=>'Home','ico'=>'fas fa-home','href'=>'manager_home.php'],
      ['id'=>'dashboard','label'=>'Dashboard','ico'=>'fas fa-gauge','href'=>'dashboard.php'],

      // Price Approval
      ['id'=>'price_approval','label'=>'Price Management','ico'=>'fas fa-check-double','href'=>'#','sub_items'=>[
          ['id'=>'approve_prices','label'=>'Verify & Approve Prices','href'=>'manager_approve_prices.php'],
      ]],

      // Stock Request Review
      ['id'=>'stock_request_review','label'=>'Stock Requests','ico'=>'fas fa-clipboard-check','href'=>'#','sub_items'=>[
          ['id'=>'review_stock_requests','label'=>'Review Stock Requests','href'=>'manager_review_stock_requests.php'],
      ]],

      ['id'=>'job_orders','label'=>'Job Orders','ico'=>'fas fa-wrench','href'=>'#','sub_items'=>[
          ['id'=>'joborder_pending','label'=>'Pending Job Orders','href'=>'joborder.php?tab=pending'],
          ['id'=>'joborder_history','label'=>'Job Order History','href'=>'joborder.php?tab=history'],
          ['id'=>'joborder_service_breakdown','label'=>'Service Type Breakdown','href'=>'manager_job_analytics.php?view=service_breakdown'],
          ['id'=>'joborder_staff_performance','label'=>'Staff Performance on Jobs','href'=>'manager_job_analytics.php?view=staff_performance'],
          ['id'=>'joborder_completion_time','label'=>'Completion Time Reports','href'=>'manager_job_analytics.php?view=completion_time'],
      ]],

      ['id'=>'fuel_management','label'=>'Fuel Management','ico'=>'fas fa-gas-pump','href'=>'#','sub_items'=>[
          ['id'=>'fuel_reconciliation_validate','label'=>'Validate Reconciliation','href'=>'fuel_reconciliation_validation.php'],
          ['id'=>'fuel_reconciliation','label'=>'Fuel Reconciliation','href'=>'reconciliation.php'],
          ['id'=>'fuel_variance_reports','label'=>'Variance Reports','href'=>'variance_reports.php'],
          ['id'=>'fuel_daily_readings','label'=>'Daily Readings Summary','href'=>'fuel_monitoring.php?view=daily'],
          ['id'=>'fuel_shift_comparison','label'=>'Shift Comparison Reports','href'=>'fuel_monitoring.php?view=shift_compare'],
          ['id'=>'fuel_calibration_logs','label'=>'Calibration Logs','href'=>'fuel_monitoring.php?view=calibration'],
      ]],

      ['id'=>'reports','label'=>'Reports','ico'=>'fas fa-file-alt','href'=>'#','sub_items'=>[
          ['id'=>'shift_sales_reports','label'=>'Shift Sales Reports','href'=>'reports.php?view=shift_reports'],
          ['id'=>'reports_pending_approvals','label'=>'Pending Approvals','href'=>'approvals.php'],
          ['id'=>'staff_performance_reports','label'=>'Individual Performance','href'=>'staff_reports.php?view=performance'],
          ['id'=>'staff_attendance','label'=>'Attendance & Shift Coverage','href'=>'staff_reports.php?view=attendance'],
          ['id'=>'staff_quality_metrics','label'=>'Quality Metrics','href'=>'staff_reports.php?view=quality'],
      ]],

      ['id'=>'staff_management','label'=>'Staff Management','ico'=>'fas fa-users','href'=>'#','sub_items'=>[
          ['id'=>'staff_active','label'=>'Active Staff','href'=>'staff_management.php?view=active'],
          ['id'=>'staff_schedule','label'=>'Shift Schedule','href'=>'staff_management.php?view=schedule'],
          ['id'=>'staff_tasks','label'=>'Task Assignments','href'=>'staff_management.php?view=tasks'],
          ['id'=>'staff_productivity','label'=>'Productivity Metrics','href'=>'staff_management.php?view=productivity'],
          ['id'=>'staff_qc','label'=>'Quality Control Scores','href'=>'staff_management.php?view=qc'],
          ['id'=>'staff_compliance','label'=>'Compliance Tracking','href'=>'staff_management.php?view=compliance'],
      ]],

      ['id'=>'audit_logs','label'=>'Audit & Logs','ico'=>'fas fa-clipboard-list','href'=>'#','sub_items'=>[
          ['id'=>'view_logs','label'=>'System Logs','href'=>'audit_logs.php'],
          ['id'=>'approval_history','label'=>'Approval History','href'=>'approval_history.php'],
          ['id'=>'safety_checks_log','label'=>'Safety Checks Log','href'=>'compliance.php?view=safety'],
          ['id'=>'procedure_adherence','label'=>'Procedure Adherence Reports','href'=>'compliance.php?view=procedures'],
      ]],

      ['id'=>'approvals','label'=>'Approvals','ico'=>'fas fa-check-circle','href'=>'#','sub_items'=>[
          ['id'=>'approve_reports','label'=>'Pending Actions','href'=>'approvals.php'],
          ['id'=>'quick_approvals','label'=>'Quick Approvals Queue','href'=>'approvals.php?view=queue'],
          ['id'=>'approved_recent','label'=>'Recently Approved','href'=>'approval_history.php?view=recent'],
          ['id'=>'approved_history','label'=>'Approval History','href'=>'approval_history.php'],
      ]]
    ];
  } else { // staff
    $items = [
      ['id'=>'staff_home','label'=>'Home','ico'=>'fas fa-home','href'=>'staff_home.php'],
      ['id'=>'staff_dashboard','label'=>'My Dashboard','ico'=>'fas fa-gauge','href'=>'staff_dashboard.php'],

      ['id'=>'transactions','label'=>'Transactions','ico'=>'fas fa-shopping-cart','href'=>'#','sub_items'=>[
          ['id'=>'pos_new','label'=>'New Transaction','href'=>'pos.php'],
          ['id'=>'pos_credit','label'=>'Credit Sales','href'=>'credit_transactions.php'],
          ['id'=>'my_shift_sales','label'=>'Current Shift Sales','href'=>'transactions.php?view=my_shift'],
          ['id'=>'txn_history','label'=>'Transaction History','href'=>'transactions.php?view=my_history'],
          ['id'=>'receipt_reprint','label'=>'Receipt Reprint','href'=>'transactions.php?view=reprint'],
      ]],

      ['id'=>'job_orders','label'=>'Job Orders','ico'=>'fas fa-wrench','href'=>'#','sub_items'=>[
          ['id'=>'job_create','label'=>'Create Job Order','href'=>'joborder.php?tab=create'],
          ['id'=>'job_active','label'=>'My Active Job Orders','href'=>'joborder.php?tab=ongoing'],
          ['id'=>'job_completed','label'=>'My Completed Jobs','href'=>'joborder.php?tab=completed'],
          ['id'=>'parts_usage','label'=>'Parts Usage Log','href'=>'joborder.php?tab=parts'],
      ]],

      ['id'=>'fuel','label'=>'Fuel','ico'=>'fas fa-gas-pump','href'=>'#','sub_items'=>[
          ['id'=>'fuel_encode','label'=>'Encode Fuel Reading','href'=>'fuel_staff.php'],
          ['id'=>'fuel_my_history','label'=>'My Reading History','href'=>'fuel_staff.php?tab=myentries'],
          ['id'=>'fuel_types','label'=>'Available Fuels','href'=>'fuel_types.php'],
      ]],

      ['id'=>'inventory','label'=>'Inventory','ico'=>'fas fa-box','href'=>'#','sub_items'=>[
          ['id'=>'merch_receive','label'=>'Encode Received Items','href'=>'receiving_staff.php'],
          ['id'=>'receive_confirm','label'=>'Receive & Confirm Stock','href'=>'stock_receiving_confirmation.php'],
          ['id'=>'merch_history','label'=>'My Delivery History','href'=>'receiving_staff.php?view=my_history'],
          ['id'=>'merch_stock','label'=>'Stock View','href'=>'inventory.php?view=stock'],
          ['id'=>'parts_available','label'=>'Available Parts','href'=>'inventory.php?view=parts'],
          ['id'=>'low_stock','label'=>'Low Stock Alerts','href'=>'inventory.php?view=low_stock'],
          ['id'=>'stock_requests','label'=>'Stock Requests','href'=>'stock_requests.php'],
      ]],

      ['id'=>'customers','label'=>'Customers','ico'=>'fas fa-users','href'=>'#','sub_items'=>[
          ['id'=>'customer_create','label'=>'Create Customer','href'=>'customers.php?view=create'],
          ['id'=>'my_customers','label'=>'My Customers','href'=>'customers.php?view=my_customers'],
          ['id'=>'record_payment','label'=>'Record Payment','href'=>'customer_credit.php?view=record_payment'],
          ['id'=>'credit_history','label'=>'Credit History','href'=>'customer_credit.php?view=my_history'],
      ]],

      ['id'=>'loyalty','label'=>'Loyalty','ico'=>'fas fa-gift','href'=>'#','sub_items'=>[
          ['id'=>'encode_points','label'=>'Encode Loyalty Points','href'=>'loyalty.php?view=encode'],
          ['id'=>'points_history','label'=>'Points History','href'=>'loyalty.php?view=history'],
          ['id'=>'rewards','label'=>'Available Rewards','href'=>'loyalty.php?view=rewards'],
          ['id'=>'redemption_history','label'=>'Redemption History','href'=>'loyalty.php?view=redemptions'],
      ]],

      ['id'=>'my_reports','label'=>'My Reports','ico'=>'fas fa-file-alt','href'=>'#','sub_items'=>[
          ['id'=>'shift_encoding_summary','label'=>'Shift Encoding Summary','href'=>'staff_reports.php?view=shift_summary'],
          ['id'=>'job_order_summary','label'=>'Job Order Summary','href'=>'staff_reports.php?view=job_summary'],
          ['id'=>'fuel_reading_summary','label'=>'Fuel Reading Summary','href'=>'staff_reports.php?view=fuel_summary'],
          ['id'=>'my_metrics','label'=>'My Metrics','href'=>'staff_reports.php?view=my_metrics'],
          ['id'=>'feedback_ratings','label'=>'Feedback & Ratings','href'=>'staff_reports.php?view=feedback'],
      ]],

      ['id'=>'my_shift','label'=>'My Shift','ico'=>'fas fa-clock','href'=>'#','sub_items'=>[
          ['id'=>'today_schedule','label'=>"Today's Schedule",'href'=>'my_shift.php?view=today'],
          ['id'=>'upcoming_shifts','label'=>'Upcoming Shifts','href'=>'my_shift.php?view=upcoming'],
          ['id'=>'clock_in_out','label'=>'Clock In/Out','href'=>'my_shift.php?view=clock'],
          ['id'=>'hours_worked','label'=>'Hours Worked','href'=>'my_shift.php?view=hours'],
      ]]
    ];
  }

  $base_path = '/group31petron_system_official4/public/';
  
  // Helper function to map hrefs to absolute paths
  function map_hrefs(&$items, $base_path) {
    foreach ($items as &$item) {
      if (isset($item['href']) && !empty($item['href']) && strpos($item['href'], 'http') === false && strpos($item['href'], '#') !== 0) {
        // Make all hrefs absolute paths to /public/
        $item['href'] = $base_path . basename($item['href']);
      }
      
      if (isset($item['sub_items']) && !empty($item['sub_items'])) {
        map_hrefs($item['sub_items'], $base_path);
      }
    }
  }
  
  map_hrefs($items, $base_path);

  foreach($items as $it){
    $has_sub = !empty($it['sub_items']);
    $active = '';
    
    // Check if the main item ID matches the page ID
    if (!$has_sub && $page_id === ($it['id'] ?? '')) {
        $active = 'active';
    }

    // Check if a sub-item is active and apply active state to parent
    if ($has_sub) {
        $is_sub_active = false;
        foreach($it['sub_items'] as $sub_it) {
            $sub_page_id = $sub_it['id'] ?? '';
            $sub_href = $sub_it['href'] ?? '';
            
            // Match page ID, e.g. 'fuel' item on 'fuel.php'
            if ($page_id === $sub_page_id) {
                $is_sub_active = true;
                break;
            }
            // Check if href points to current page (for tabs)
            if (strpos($sub_href, $page_id . '.php') !== false) {
                $is_sub_active = true;
                break;
            }
            // Special case for reports with '?view=...' - only activate the correct parent
            if ($page_id === 'reports' && isset($_GET['view'])) {
                $report_view = str_replace('reports.php?view=', '', $sub_href);
                if ($_GET['view'] === $report_view) {
                    $is_sub_active = true;
                    break;
                }
            }
            // Special case for profile page 'users.php?edit=...'
            if ($page_id === 'users' && isset($_GET['edit']) && $sub_page_id === 'profile') {
                $is_sub_active = true;
                break;
            }
        }
        if ($is_sub_active) {
            $active = 'active open'; // Keep parent open and active
        }
    }

    echo '<div class="nav-item-wrapper">'; // Wrapper for item + submenu
    echo '<a class="nav-item ' . $active . '" href="' . htmlspecialchars($it['href']) . '"' . ($has_sub ? ' data-toggle="sub-menu"' : '') . '>';
    echo '<span class="ico" style="margin-right:10px; width:24px; text-align:center;"><i class="' . htmlspecialchars($it['ico']) . '"></i></span><span style="flex-grow:1;">' . htmlspecialchars($it['label']) . '</span>';
    
    if(isset($badges[$it['id']]) && $badges[$it['id']] > 0){
        echo '<span style="background:#E30613; color:white; padding:0 6px; border-radius:10px; font-size:11px; font-weight:bold; min-width:20px; height:20px; display:flex; align-items:center; justify-content:center; margin-left:10px;">'.$badges[$it['id']].'</span>';
    }

    if ($has_sub) {
        echo '<i class="fas fa-chevron-right arrow"></i>';
    }
    echo '</a>';

    if ($has_sub) {
        $sub_display = (strpos($active, 'open') !== false) ? 'block' : 'none';
        echo '<ul class="sub-menu" style="display: '. $sub_display .';">';
        foreach($it['sub_items'] as $sub_it) {
            $sub_active = '';
            $sub_page_id = $sub_it['id'] ?? '';

            if ($page_id === $sub_page_id) {
                $sub_active = 'active';
            }
            // Active state for tabs
            if (isset($_GET['tab']) && strpos($sub_it['href'], 'tab=' . $_GET['tab']) !== false) {
                $sub_active = 'active';
            }
            if ($page_id === 'users' && isset($_GET['edit']) && $sub_page_id === 'profile') {
                $sub_active = 'active';
            }
            if ($page_id === 'reports' && isset($_GET['view'])) {
                $report_view = str_replace('reports.php?view=', '', $sub_it['href']);
                if ($_GET['view'] === $report_view) {
                    $sub_active = 'active';
                }
            }
            echo '<li><a href="' . htmlspecialchars($sub_it['href']) . '" class="' . $sub_active . '">' . htmlspecialchars($sub_it['label']) . '</a></li>';
        }
        echo '</ul>';
    }
    echo '</div>'; // end wrapper
  }
?>
      </nav>
    </div>

  </aside>

  <main class="main">

    <!-- GLOBAL TOP HEADER -->
    <header class="top-header">
        <div class="header-left">
            <img src="../assets/img/Petron Logo.png" alt="Petron Logo" class="brand-mark" id="petronLogo">
            <div class="brand-text">
                <div class="brand-title">Petron Station Management System</div>
            </div>
            <div style="display: none; flex-direction: column; margin-left: 20px;"> <!-- Hidden in left block, moved logic if needed or kept simple -->
                <div style="font-size: 1.1em; font-weight: bold; color: var(--petron-blue);">
                    <?php
                    if ($role === 'superadmin') {
                        echo 'Super Admin Console';
                    } elseif ($role === 'admin') {
                        echo 'Admin Console';
                    } else {
                        echo 'Staff Console';
                    }
                    ?>
                </div>
                <div id="live-clock" style="font-size: 0.85em; color: #666; font-weight: 500;">
                    <i class="far fa-clock"></i> Loading time...
                </div>
            </div>
        </div>
        <div class="header-center">
        </div>
        <div class="header-right">
            <!-- Global Search Bar -->
            <?php if(in_array($role, ['superadmin', 'admin', 'manager', 'staff'])): ?>
            <form method="get" action="search.php" style="margin-right: 15px;">
                <input type="text" name="q" placeholder="<?php 
                    if($role === 'superadmin') echo 'Search Stations / Admin Accounts / Reports...';
                    elseif($role === 'admin') echo 'Search Transactions / Customers / Inventory...';
                    elseif($role === 'manager') echo 'Search Reports / Staff / Job Orders...';
                    else echo 'Search Transactions / Customers / Products...';
                ?>" style="padding: 6px 10px; border-radius: 4px; border: 1px solid #ccc; font-size: 0.9em; width: 250px;" required>
                <button type="submit" style="padding: 6px 10px; border-radius: 4px; border: 1px solid #ccc; background: var(--petron-blue); color: white; font-size: 0.9em; cursor: pointer;"><i class="fas fa-search"></i></button>
            </form>
            <?php endif; ?>

            <!-- Notification Bell -->
            <?php if(in_array($role, ['staff','admin','manager','superadmin'])): ?>
            <div class="notification-bell" id="notificationBell">
                <i class="fas fa-bell"></i>
                <?php if($unread_alerts > 0): ?>
                <span class="badge"><?php echo $unread_alerts; ?></span>
                <?php endif; ?>

                <div class="notif-dropdown">
                    <div class="notif-dropdown-header">
                        <span style="font-weight:bold; color:#333;">Notifications</span>
                        <a href="/group31petron_system_official4/public/notifications.php" style="font-size:12px; color:var(--petron-blue); text-decoration:none;">View All</a>
                    </div>
                    <div class="notif-list" style="max-height: 300px; overflow-y: auto;">
                        <?php if(empty($header_alerts)): ?>
                            <div class="notif-empty">
                                <div style="font-size: 24px; margin-bottom: 8px;"><i class="fas fa-bell"></i></div>
                                <div style="color: #888; font-size: 14px;">No new notifications</div>
                            </div>
                        <?php else: ?>
                            <?php foreach($header_alerts as $ha): ?>
                                <div class="notif-item" onclick="window.location.href='<?php echo $ha['link']; ?>'">
                                    <div class="notif-icon">
                                        <?php
                                        // Determine notification type and icon
                                        $icon_class = 'info';
                                        $icon = 'fa-info-circle';
                                        if (strpos($ha['msg'], 'Failed') !== false || strpos($ha['msg'], 'Error') !== false) {
                                            $icon_class = 'error';
                                            $icon = 'fa-times-circle';
                                        } elseif (strpos($ha['msg'], 'Low') !== false || strpos($ha['msg'], 'Delay') !== false) {
                                            $icon_class = 'warning';
                                            $icon = 'fa-exclamation-triangle';
                                        } elseif (strpos($ha['msg'], 'Completed') !== false || strpos($ha['msg'], 'Success') !== false) {
                                            $icon_class = 'success';
                                            $icon = 'fa-check-circle';
                                        }
                                        ?>
                                        <span class="notif-type-icon icon-<?php echo $icon_class; ?>">
                                            <i class="fas <?php echo $icon; ?>"></i>
                                        </span>
                                    </div>
                                    <div class="notif-content">
                                        <div class="notif-title"><?php echo htmlspecialchars($ha['msg']); ?></div>
                                        <div class="notif-time"><?php echo htmlspecialchars($ha['time']); ?></div>
                                    </div>
                                    <div class="notif-status unread"></div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                    <div class="notif-dropdown-footer">
                        <a href="/group31petron_system_official4/public/notifications.php" style="display:block; text-align:center; padding:8px; font-size:12px; color:var(--petron-blue); text-decoration:none; border-top:1px solid #eee;">
                            View All Notifications
                        </a>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <!-- Profile Dropdown -->
            <div class="profile-access" id="profileMenu">
                <div style="text-align: right;">
                    <div style="font-weight: 600;">
                        <i class="fas fa-user-circle"></i> <?php echo htmlspecialchars(strtoupper($user['role'])); ?>
                    </div>
                    <?php if ($station_name): ?>
                    <div style="font-size: 11px; color: #666; margin-top: 2px;">
                        <i class="fas fa-building" style="font-size: 10px;"></i> <?php echo htmlspecialchars($station_name); ?>
                    </div>
                    <?php endif; ?>
                </div>
                <i class="fas fa-caret-down" style="font-size:0.8em; color:#888; margin-left: 5px;"></i>

                <div class="profile-dropdown" id="profileDropdown">
                    <a href="/group31petron_system_official4/public/users.php?edit=<?php echo (int)$user['id']; ?>">View Profile</a>
                    <a href="/group31petron_system_official4/public/update_password.php">Change Password</a>
                    <div class="dropdown-divider"></div>
                    <a href="/group31petron_system_official4/public/logout.php" class="logout">Logout</a>
                </div>
            </div>
</header>

    <script>
        
    function updateClock() {
        const now = new Date();
        const options = { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric', hour: '2-digit', minute: '2-digit', second: '2-digit' };
        document.getElementById('live-clock').innerHTML = '<i class="far fa-clock"></i> ' + now.toLocaleDateString('en-US', options);
    }
    setInterval(updateClock, 1000);
    updateClock();

    // Dropdown Toggle Logic
    document.addEventListener('DOMContentLoaded', function() {
        const notifBell = document.getElementById('notificationBell');
        const notifDropdown = document.querySelector('.notif-dropdown');
        const profileAccess = document.querySelector('.profile-access');
        const profileDropdown = document.querySelector('.profile-dropdown');

        // Toggle Notification Dropdown
        if (notifBell && notifDropdown) {
            notifBell.addEventListener('click', function(e) {
                e.preventDefault();
                e.stopPropagation();
                if(profileDropdown) profileDropdown.classList.remove('show'); // Close profile dropdown
                notifDropdown.classList.toggle('show');
            });
        }

        // Toggle Notification Dropdown on Logo Click
        const petronLogo = document.getElementById('petronLogo');
        if (petronLogo && notifDropdown) {
            petronLogo.addEventListener('click', function(e) {
                e.preventDefault();
                e.stopPropagation();
                if(profileDropdown) profileDropdown.classList.remove('show'); // Close profile dropdown
                notifDropdown.classList.toggle('show');
            });
        }

        // Toggle Profile Dropdown
        if (profileAccess && profileDropdown) {
            profileAccess.addEventListener('click', function(e) {
                e.stopPropagation();
                if (notifDropdown) notifDropdown.classList.remove('show'); // Close notification dropdown
                profileDropdown.classList.toggle('show');
            });
        }

        // Close dropdowns when clicking outside
        document.addEventListener('click', function() {
            if (notifDropdown) notifDropdown.classList.remove('show');
            if (profileDropdown) profileDropdown.classList.remove('show');
        });

        // Prevent closing when clicking inside dropdowns
        if (notifDropdown) {
            notifDropdown.addEventListener('click', function(e) { e.stopPropagation(); });
        }
        if (profileDropdown) {
            profileDropdown.addEventListener('click', function(e) { e.stopPropagation(); });
        }

        // Collapsible sidebar logic (Option A: Click-to-Toggle; persistent until clicked again)
        const menuItemsWithSub = document.querySelectorAll('[data-toggle="sub-menu"]');
        menuItemsWithSub.forEach(item => {
            item.addEventListener('click', function(e) {
                e.preventDefault(); // ensure no navigation for dropdown parents
                const parentWrapper = this.closest('.nav-item-wrapper');
                const subMenu = parentWrapper.querySelector('.sub-menu');
                const isOpen = subMenu.style.display === 'block';
                subMenu.style.display = isOpen ? 'none' : 'block';
                this.classList.toggle('open', !isOpen);
            });
        });

        // Auto-suggest for search
        const searchInput = document.getElementById('searchInput');
        const searchSuggestions = document.getElementById('searchSuggestions');
        let debounceTimer;

        if (searchInput && searchSuggestions) {
            searchInput.addEventListener('input', function() {
                clearTimeout(debounceTimer);
                const query = this.value.trim();
                if (query.length < 2) {
                    searchSuggestions.style.display = 'none';
                    return;
                }
                debounceTimer = setTimeout(() => {
                    fetch(`search.php?q=${encodeURIComponent(query)}&ajax=1`)
                        .then(response => response.json())
                        .then(data => {
                            searchSuggestions.innerHTML = '';
                            if (data.length > 0) {
                                const grouped = {};
                                data.forEach(item => {
                                    if (!grouped[item.type]) grouped[item.type] = [];
                                    grouped[item.type].push(item);
                                });
                                Object.keys(grouped).forEach(type => {
                                    const groupDiv = document.createElement('div');
                                    groupDiv.innerHTML = `<strong>${type}s</strong>`;
                                    groupDiv.style.padding = '5px 10px';
                                    groupDiv.style.borderBottom = '1px solid #eee';
                                    groupDiv.style.background = '#f9f9f9';
                                    searchSuggestions.appendChild(groupDiv);
                                    grouped[type].slice(0, 3).forEach(item => {
                                        const itemDiv = document.createElement('div');
                                        itemDiv.innerHTML = `<a href="${item.link}" style="display: block; padding: 5px 10px; text-decoration: none; color: #333;">${item.title} <small>${item.subtitle}</small></a>`;
                                        itemDiv.style.borderBottom = '1px solid #f0f0f0';
                                        searchSuggestions.appendChild(itemDiv);
                                    });
                                });
                                searchSuggestions.style.display = 'block';
                            } else {
                                searchSuggestions.style.display = 'none';
                            }
                        })
                        .catch(err => console.error('Error fetching suggestions:', err));
                }, 300);
            });

            // Hide suggestions on click outside
            document.addEventListener('click', function(e) {
                if (!searchInput.contains(e.target) && !searchSuggestions.contains(e.target)) {
                    searchSuggestions.style.display = 'none';
                }
            });
        }

    });
    </script>