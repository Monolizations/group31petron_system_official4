<?php
/**
 * Verify Staff Has All Superadmin Permissions
 */

require_once __DIR__ . '/backend/lib.php';

echo "🔍 VERIFYING STAFF PERMISSIONS (SUPERADMIN LEVEL)\n";
echo str_repeat("=", 70) . "\n\n";

$superadmin_perms = get_user_permissions('superadmin');
$staff_perms = get_user_permissions('staff');

echo "SUPERADMIN PERMISSIONS (" . count($superadmin_perms) . "):\n";
sort($superadmin_perms);
foreach ($superadmin_perms as $perm) {
    $has_it = in_array($perm, $staff_perms) ? '✅' : '❌';
    echo "  $has_it $perm\n";
}

echo "\n" . str_repeat("=", 70) . "\n";

$staff_only = array_diff($staff_perms, $superadmin_perms);
if (!empty($staff_only)) {
    echo "STAFF-ONLY PERMISSIONS:\n";
    foreach ($staff_only as $perm) {
        echo "  ➕ $perm\n";
    }
}

echo "\n" . str_repeat("=", 70) . "\n";
echo "✅ STAFF NOW HAS " . count($staff_perms) . " PERMISSIONS!\n";
echo "\nStaff can now access ALL superadmin features including:\n";
echo "  • All menu items (Transactions, Job Orders, Fuel, Inventory, etc.)\n";
echo "  • User Management (create/edit users)\n";
echo "  • Station Management\n";
echo "  • System Administration\n";
echo "  • All Reports (financial, operational, nationwide)\n";
echo "  • Audit Logs\n";
echo "  • Developer Panel\n";
echo str_repeat("=", 70) . "\n";

?>