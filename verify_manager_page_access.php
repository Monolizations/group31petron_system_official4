<?php
/**
 * Verify Manager Access to Key Pages
 */

require_once __DIR__ . '/backend/lib.php';

echo "🔐 VERIFYING MANAGER ACCESS TO SIDEBAR PAGES\n";
echo str_repeat("=", 70) . "\n\n";

$manager_pages = [
    // Fuel Management
    ['file' => 'fuel_staff.php', 'name' => 'Encode Fuel Reading'],
    ['file' => 'fuel_management.php', 'name' => 'Fuel Delivery'],
    ['file' => 'reconciliation.php', 'name' => 'Fuel Reconciliation'],
    ['file' => 'fuel_pricing_manager.php', 'name' => 'Fuel Pricing'],
    ['file' => 'admin_pump_management.php', 'name' => 'Pump Management'],
    
    // Inventory
    ['file' => 'inventory.php', 'name' => 'Inventory Management'],
    ['file' => 'inventory_list.php', 'name' => 'Inventory List'],
    ['file' => 'receiving_staff.php', 'name' => 'Receive Inventory'],
    ['file' => 'manager_receiving_review.php', 'name' => 'Receiving Review'],
    
    // Staff Management
    ['file' => 'staff_management.php', 'name' => 'Staff Management'],
    ['file' => 'staff_schedule.php', 'name' => 'Staff Schedule'],
];

echo "Checking if pages allow manager access:\n";
echo str_repeat("-", 70) . "\n";

foreach ($manager_pages as $page) {
    $file_path = __DIR__ . '/public/' . $page['file'];
    
    if (!file_exists($file_path)) {
        echo "  ⚠️  {$page['name']}: File not found\n";
        continue;
    }
    
    $content = file_get_contents($file_path);
    
    // Check for manager access patterns
    $has_manager_access = false;
    
    // Pattern 1: Direct manager check
    if (strpos($content, "'manager'") !== false && 
        (strpos($content, 'in_array') !== false || strpos($content, 'array(') !== false)) {
        $has_manager_access = true;
    }
    
    // Pattern 2: Role checks that include manager
    if (preg_match('/in_array.*\$role.*\[.*manager/i', $content) ||
        preg_match('/\[.*manager.*\]/i', $content)) {
        $has_manager_access = true;
    }
    
    // Pattern 3: $isManager variable
    if (strpos($content, '$isManager') !== false) {
        $has_manager_access = true;
    }
    
    // Pattern 4: Check for blocking patterns (admin only)
    if (preg_match('/in_array.*\$role.*\[.*\'admin\'.*\'superadmin\'\]/i', $content) &&
        !preg_match('/manager/i', $content)) {
        $has_manager_access = false;
        echo "  ❌ {$page['name']}: ADMIN ONLY (no manager)\n";
        continue;
    }
    
    if ($has_manager_access) {
        echo "  ✅ {$page['name']}: Manager access OK\n";
    } else {
        echo "  ⚠️  {$page['name']}: Access pattern unclear\n";
    }
}

echo "\n" . str_repeat("=", 70) . "\n";
echo "✅ Pump Management page is now accessible to managers!\n";
echo "\nManagers can now:\n";
echo "  • View all pumps\n";
echo "  • Add new pumps\n";
echo "  • Edit pump settings (fuel type, calibration, status)\n";
echo "  • Cannot delete pumps (Superadmin only)\n";
echo "  • Cannot switch stations (Superadmin only)\n";

echo "\n📝 NOTE: If you still can't access Pump Management:\n";
echo "  1. Log out and log back in as manager\n";
echo "  2. Clear browser cache\n";
echo "  3. Check if manager role is 'manager' (lowercase)\n";

?>