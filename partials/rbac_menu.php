<?php
// RBAC-Based Menu Generation
// Master menu array with all possible items and their permission requirements
$master_menu = [
    // Dashboard - Everyone gets some form of dashboard
    ['id'=>'dashboard','label'=>'Dashboard','ico'=>'fas fa-gauge','href'=>'dashboard.php', 'permissions'=>['view_dashboard'], 'station_specific'=>false],
    
    // Transactions & POS - Managers and Staff (day-to-day operations)
    ['id'=>'transactions','label'=>'Transactions','ico'=>'fas fa-shopping-cart','href'=>'#','permissions'=>['create_transactions', 'approve_transactions'],'station_specific'=>true,'sub_items'=>[
        ['id'=>'pos_new','label'=>'New Transaction','href'=>'pos.php','permissions'=>['create_transactions']],
        ['id'=>'pos_approval','label'=>'Transaction Approval','href'=>'pos.php?view=approval','permissions'=>['approve_transactions']],
        ['id'=>'txn_history','label'=>'Transaction History','href'=>'transactions.php','permissions'=>['create_transactions', 'approve_transactions']],
        ['id'=>'receipt_reprint','label'=>'Receipt Reprint','href'=>'transactions.php?view=reprint','permissions'=>['create_transactions']],
    ]],
    
    // Job Orders - Managers handle operations, Staff create
    ['id'=>'job_orders','label'=>'Job Orders','ico'=>'fas fa-wrench','href'=>'joborder.php','permissions'=>['manage_job_orders', 'create_job_orders'],'station_specific'=>true,'sub_items'=>[
        ['id'=>'job_create','label'=>'Create Job Order','href'=>'joborder.php?tab=create','permissions'=>['create_job_orders', 'manage_job_orders']],
        ['id'=>'job_manage','label'=>'Manage Job Orders','href'=>'joborder.php','permissions'=>['manage_job_orders']],
        ['id'=>'job_history','label'=>'Job Order History','href'=>'joborder.php?tab=history','permissions'=>['manage_job_orders']],
    ]],
    
    // Fuel Management - Managers handle operations, Staff do encoding
    ['id'=>'fuel','label'=>'Fuel Management','ico'=>'fas fa-gas-pump','href'=>'#','permissions'=>['manage_fuel', 'encode_fuel'],'station_specific'=>true,'sub_items'=>[
        ['id'=>'fuel_encode','label'=>'Encode Fuel Reading','href'=>'fuel_staff.php','permissions'=>['encode_fuel']],
        ['id'=>'fuel_delivery','label'=>'Fuel Delivery','href'=>'fuel_management.php','permissions'=>['manage_fuel']],
        ['id'=>'fuel_reconciliation','label'=>'Fuel Reconciliation','href'=>'reconciliation.php','permissions'=>['manage_fuel']],
        ['id'=>'fuel_pricing','label'=>'Fuel Pricing','href'=>'fuel_pricing_manager.php','permissions'=>['manage_fuel']],
        ['id'=>'pump_management','label'=>'Pump Management','href'=>'admin_pump_management.php','permissions'=>['manage_fuel']],
    ]],
    
    // Inventory - All roles have some level of access
    ['id'=>'inventory','label'=>'Inventory','ico'=>'fas fa-box','href'=>'inventory.php','permissions'=>['manage_inventory', 'view_inventory', 'receive_inventory', 'create_po'],'station_specific'=>true,'sub_items'=>[
        ['id'=>'inventory_manage','label'=>'Inventory Management','href'=>'inventory.php','permissions'=>['manage_inventory', 'view_inventory']],
        ['id'=>'inventory_list','label'=>'Inventory List','href'=>'inventory_list.php','permissions'=>['manage_inventory', 'view_inventory', 'receive_inventory']],
        ['id'=>'receive_inventory','label'=>'Receive Inventory','href'=>'receiving_staff.php','permissions'=>['receive_inventory']],
        ['id'=>'receiving_review','label'=>'Receiving Review','href'=>'manager_receiving_review.php','permissions'=>['manage_inventory']],
        ['id'=>'stock_confirmation','label'=>'Stock Confirmation','href'=>'admin_stock_confirmation.php','permissions'=>['manage_inventory']],
        ['id'=>'create_po','label'=>'Create Purchase Order','href'=>'purchase_order.php','permissions'=>['create_po']],
        ['id'=>'my_pos','label'=>'My Purchase Orders','href'=>'view_po.php?mode=my','permissions'=>['create_po']],
        ['id'=>'review_po','label'=>'Review Purchase Orders','href'=>'manager_po_review.php','permissions'=>['manage_inventory']],
    ]],
    
    // Customer Management
    ['id'=>'customers','label'=>'Customers','ico'=>'fas fa-users','href'=>'customers.php','permissions'=>['manage_customers', 'manage_customers_basic'],'station_specific'=>true,'sub_items'=>[
        ['id'=>'customer_list','label'=>'Customer List','href'=>'customers.php','permissions'=>['manage_customers', 'manage_customers_basic']],
        ['id'=>'customer_create','label'=>'Create Customer','href'=>'customers.php?view=create','permissions'=>['manage_customers']],
    ]],
    
    // Staff Management - Managers only (they run day-to-day)
    ['id'=>'staff','label'=>'Staff Management','ico'=>'fas fa-users-cog','href'=>'#','permissions'=>['manage_staff'],'station_specific'=>true,'sub_items'=>[
        ['id'=>'staff_schedule','label'=>'Staff Schedule','href'=>'staff_management.php?view=schedule','permissions'=>['manage_staff']],
        ['id'=>'staff_performance','label'=>'Staff Performance','href'=>'staff_reports.php?view=performance','permissions'=>['manage_staff']],
    ]],
    
    // Reports - Different access levels
    ['id'=>'reports','label'=>'Reports','ico'=>'fas fa-file-alt','href'=>'#','permissions'=>['view_personal_reports', 'view_operational_reports', 'view_financial_reports', 'view_all_reports'],'station_specific'=>true,'sub_items'=>[
        // Personal reports (Staff)
        ['id'=>'my_reports','label'=>'My Reports','href'=>'staff_reports.php','permissions'=>['view_personal_reports']],
        // Operational reports (Manager)
        ['id'=>'shift_reports','label'=>'Shift Reports','href'=>'reports.php?view=shift_reports','permissions'=>['view_operational_reports', 'view_all_reports']],
        ['id'=>'sales_reports','label'=>'Sales Reports','href'=>'reports.php?view=daily_sales','permissions'=>['view_operational_reports', 'view_financial_reports', 'view_all_reports']],
        ['id'=>'inventory_reports','label'=>'Inventory Reports','href'=>'reports.php?view=inventory_reports','permissions'=>['view_operational_reports', 'view_all_reports']],
        ['id'=>'fuel_reconciliation','label'=>'Fuel Reconciliation','href'=>'reconciliation.php','permissions'=>['view_operational_reports', 'view_all_reports']],
        // Financial reports (Admin)
        ['id'=>'profit_loss','label'=>'Profit & Loss','href'=>'reports.php?view=profit_loss','permissions'=>['view_financial_reports', 'view_all_reports']],
    ]],
    
    // User Management - Admin and Superadmin
    ['id'=>'users','label'=>'User Management','ico'=>'fas fa-user-cog','href'=>'users.php','permissions'=>['manage_users_station', 'manage_all_users'],'station_specific'=>false,'sub_items'=>[
        ['id'=>'user_list','label'=>'Manage Users','href'=>'users.php','permissions'=>['manage_users_station', 'manage_all_users']],
        ['id'=>'create_users','label'=>'Create Users','href'=>'users.php?view=create','permissions'=>['manage_all_users']],
    ]],
    
    // Station Management - Superadmin only
    ['id'=>'stations','label'=>'Station Management','ico'=>'fas fa-building','href'=>'#','permissions'=>['manage_stations'],'station_specific'=>false,'sub_items'=>[
        ['id'=>'view_stations','label'=>'View Stations','href'=>'view_stations.php','permissions'=>['manage_stations']],
        ['id'=>'station_profiles','label'=>'Station Profiles','href'=>'station_profiles.php','permissions'=>['manage_stations']],
    ]],
    
    // System Administration - Superadmin only
    ['id'=>'system','label'=>'System Admin','ico'=>'fas fa-cogs','href'=>'#','permissions'=>['manage_system_settings'],'station_specific'=>false,'sub_items'=>[
        ['id'=>'system_settings','label'=>'System Settings','href'=>'settings.php','permissions'=>['manage_system_settings']],
        ['id'=>'audit_logs','label'=>'Audit Logs','href'=>'audit_logs.php','permissions'=>['view_audit_logs']],
        ['id'=>'developer_panel','label'=>'Developer Panel','href'=>'developer_panel.php','permissions'=>['developer_access']],
    ]],
];

// Filter menu items based on user role and permissions
function filter_menu_by_permissions($menu_items, $user_role) {
    $filtered_menu = [];
    $user_permissions = get_user_permissions($user_role);
    
    foreach ($menu_items as $item) {
        // Check if user has permission for this menu item
        $required_permissions = $item['permissions'] ?? [];
        $has_permission = false;
        
        // If no permissions required, allow access
        if (empty($required_permissions)) {
            $has_permission = true;
        } else {
            // Check if user has any of the required permissions
            foreach ($required_permissions as $permission) {
                if (in_array($permission, $user_permissions)) {
                    $has_permission = true;
                    break;
                }
            }
        }
        
        if ($has_permission) {
            $filtered_item = $item;
            
            // Filter sub-items if they exist
            if (!empty($item['sub_items'])) {
                $filtered_sub_items = [];
                foreach ($item['sub_items'] as $sub_item) {
                    $sub_required_permissions = $sub_item['permissions'] ?? [];
                    $has_sub_permission = false;
                    
                    if (empty($sub_required_permissions)) {
                        $has_sub_permission = true;
                    } else {
                        foreach ($sub_required_permissions as $permission) {
                            if (in_array($permission, $user_permissions)) {
                                $has_sub_permission = true;
                                break;
                            }
                        }
                    }
                    
                    if ($has_sub_permission) {
                        $filtered_sub_items[] = $sub_item;
                    }
                }
                
                // Only include parent if it has sub-items or is directly accessible
                if (!empty($filtered_sub_items) || !empty($item['href']) && $item['href'] !== '#') {
                    $filtered_item['sub_items'] = $filtered_sub_items;
                    $filtered_menu[] = $filtered_item;
                }
            } else {
                $filtered_menu[] = $filtered_item;
            }
        }
    }
    
    return $filtered_menu;
}

// Get filtered menu items for current user
$items = filter_menu_by_permissions($master_menu, $role);

// Visual indicators removed to keep UI clean
// Future enhancement: Could add subtle CSS classes instead of emoji icons
?>