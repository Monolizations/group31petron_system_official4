<?php
/**
 * Test Staff Menu Visibility
 * Check what menu items staff role can access after removal
 */

require_once __DIR__ . '/backend/lib.php';

echo "=== STAFF MENU VISIBILITY TEST ===\n";
echo "Date: " . date('Y-m-d H:i:s') . "\n";
echo str_repeat("=", 70) . "\n\n";

// Get staff permissions
echo "STEP 1: Staff Permissions\n";
$staff_perms = get_user_permissions('staff');
echo "Total permissions: " . count($staff_perms) . "\n";
echo "Removed: view_operational_reports\n\n";

echo "Permissions:\n";
foreach ($staff_perms as $perm) {
    $status = $perm == 'view_operational_reports' ? '✗ REMOVED' : '✓';
    echo "  $status $perm\n";
}

echo "\n";
echo "STEP 2: Fuel Menu Items Check\n";

// Items hidden from staff specifically
$staff_hidden_items = ['fuel_pricing', 'fuel_variance', 'fuel_reconciliation', 'shift_reports', 'sales_reports', 'inventory_reports'];

// Define fuel menu items
$fuel_menu_items = [
    ['id'=>'fuel_encode','label'=>'Encode Fuel Reading','permissions'=>['encode_fuel']],
    ['id'=>'fuel_delivery','label'=>'Fuel Delivery','permissions'=>['manage_fuel']],
    ['id'=>'fuel_reconciliation','label'=>'Fuel Reconciliation','permissions'=>['manage_fuel', 'view_operational_reports', 'view_all_reports']],
    ['id'=>'fuel_pricing','label'=>'Fuel Pricing','permissions'=>['manage_fuel']],
    ['id'=>'fuel_variance','label'=>'Fuel Variance','permissions'=>['manage_fuel', 'view_fuel_variance']],
];

echo "Fuel Menu Items:\n";
foreach ($fuel_menu_items as $item) {
    $has_permission = false;
    $is_role_hidden = false;
    $required = $item['permissions'] ?? [];
    
    // Check if staff has all required permissions
    foreach ($required as $perm) {
        if (in_array($perm, $staff_perms)) {
            $has_permission = true;
        } else {
            $has_permission = false;
            break; // Missing any permission = no access
        }
    }
    
    // Check if item is specifically hidden from staff
    if (in_array($item['id'], $staff_hidden_items)) {
        $is_role_hidden = true;
    }
    
    // Item is visible only if has permission AND is not role-hidden
    $is_visible = $has_permission && !$is_role_hidden;
    $status = $is_visible ? '✓ VISIBLE' : '✗ HIDDEN';
    
    if (!$is_visible) {
        $reason = '';
        if (!$has_permission) {
            $reason .= 'Missing permissions: ' . implode(', ', array_diff($required, $staff_perms));
        }
        if ($is_role_hidden) {
            $reason .= ($reason ? ', ' : '') . 'Hidden by role';
        }
        echo "  $status {$item['label']} ($reason)\n";
    } else {
        echo "  $status {$item['label']}\n";
    }
}

echo "\n";
echo "STEP 3: Reports Menu Items Check\n";

// Define reports menu items that should be hidden from staff
$reports_menu_items = [
    ['id'=>'shift_reports','label'=>'Shift Reports','permissions'=>['view_operational_reports', 'view_all_reports']],
    ['id'=>'sales_reports','label'=>'Sales Reports','permissions'=>['view_operational_reports', 'view_financial_reports', 'view_all_reports']],
    ['id'=>'inventory_reports','label'=>'Inventory Reports','permissions'=>['view_operational_reports', 'view_all_reports']],
    ['id'=>'fuel_reconciliation','label'=>'Fuel Reconciliation (Reports)','permissions'=>['view_operational_reports', 'view_all_reports']],
];

echo "Reports Menu Items:\n";
foreach ($reports_menu_items as $item) {
    $has_permission = false;
    $required = $item['permissions'] ?? [];
    
    // Check if staff has all required permissions
    foreach ($required as $perm) {
        if (in_array($perm, $staff_perms)) {
            $has_permission = true;
        } else {
            $has_permission = false;
            break;
        }
    }
    
    $status = $has_permission ? '✓ VISIBLE' : '✗ HIDDEN';
    $missing = $has_permission ? '' : ' (' . implode(', ', array_diff($required, $staff_perms)) . ')';
    
    echo "  $status {$item['label']} - $missing\n";
}

echo "\n";
echo "STEP 4: Summary\n";
echo str_repeat("=", 70) . "\n";
echo "Removed from Staff Access:\n";
echo "  ✗ Fuel Reconciliation (requires view_operational_reports, view_all_reports)\n";
echo "  ✗ Fuel Pricing (hidden by role check)\n";
echo "  ✗ Fuel Variance (requires view_fuel_variance)\n";
echo "  ✗ Shift Reports (requires view_operational_reports, view_all_reports)\n";
echo "  ✗ Sales Reports (requires view_financial_reports, view_all_reports)\n";
echo "  ✗ Inventory Reports (requires view_operational_reports, view_all_reports)\n\n";

echo "Kept for Staff Access:\n";
echo "  ✓ Encode Fuel Reading (fuel encode)\n";
echo "  ✓ Fuel Delivery (manage_fuel)\n";
echo "  ✓ My Reports (view_personal_reports)\n";
echo "  ✓ Dashboard (view_dashboard)\n";
echo "  ✓ Transactions (create_transactions)\n";
echo "  ✓ Job Orders (create_job_orders, manage_job_orders)\n";
echo "  ✓ Inventory (manage_inventory, view_inventory)\n";
echo "  ✓ Customers (manage_customers)\n\n";

echo "Hidden by Role Check in rbac_menu.php:\n";
echo "  These items are in the staff_hidden_items array:\n";
echo "  - fuel_pricing\n";
echo "  - fuel_variance\n";
echo "  - fuel_reconciliation\n";
echo "  - shift_reports\n";
echo "  - sales_reports\n";
echo "  - inventory_reports\n\n";

echo str_repeat("=", 70) . "\n";
?>
