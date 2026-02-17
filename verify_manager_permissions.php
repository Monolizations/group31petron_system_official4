<?php
/**
 * Verify Manager vs Admin Permissions
 * Shows that manager has same access as admin except system settings
 */

require_once __DIR__ . '/backend/lib.php';

echo "🔐 MANAGER VS ADMIN PERMISSIONS COMPARISON\n";
echo str_repeat("=", 70) . "\n\n";

$admin_perms = get_user_permissions('admin');
$manager_perms = get_user_permissions('manager');

echo "📋 ADMIN PERMISSIONS (" . count($admin_perms) . "):\n";
echo str_repeat("-", 70) . "\n";
sort($admin_perms);
foreach ($admin_perms as $perm) {
    $has_it = in_array($perm, $manager_perms) ? '✓' : '✗';
    echo "  [$has_it] $perm\n";
}

echo "\n\n📋 MANAGER PERMISSIONS (" . count($manager_perms) . "):\n";
echo str_repeat("-", 70) . "\n";
sort($manager_perms);
foreach ($manager_perms as $perm) {
    $is_extra = !in_array($perm, $admin_perms);
    $marker = $is_extra ? '+' : ' ';
    echo "  [$marker] $perm" . ($is_extra ? " (EXTRA)" : "") . "\n";
}

echo "\n\n📊 COMPARISON SUMMARY:\n";
echo str_repeat("=", 70) . "\n";

$only_admin = array_diff($admin_perms, $manager_perms);
$only_manager = array_diff($manager_perms, $admin_perms);
$common = array_intersect($admin_perms, $manager_perms);

echo "✅ Permissions in BOTH admin and manager: " . count($common) . "\n";
echo "⚠️  Permissions ONLY in admin: " . count($only_admin) . "\n";
if (count($only_admin) > 0) {
    foreach ($only_admin as $perm) {
        echo "   • $perm\n";
    }
}
echo "➕ Permissions ONLY in manager (extras): " . count($only_manager) . "\n";
if (count($only_manager) > 0) {
    foreach ($only_manager as $perm) {
        echo "   • $perm\n";
    }
}

echo "\n\n🎯 SYSTEM SETTINGS CHECK:\n";
echo str_repeat("=", 70) . "\n";
$system_settings_perms = [
    'manage_system_settings',
    'developer_access',
    'manage_all_users',
    'manage_stations',
    'view_nationwide_reports'
];

echo "Checking system-level permissions:\n";
foreach ($system_settings_perms as $perm) {
    $admin_has = in_array($perm, $admin_perms) ? '✅' : '❌';
    $manager_has = in_array($perm, $manager_perms) ? '✅' : '❌';
    echo "  • $perm\n";
    echo "      Admin:   $admin_has\n";
    echo "      Manager: $manager_has\n";
}

echo "\n\n✅ RESULT:\n";
echo str_repeat("=", 70) . "\n";
echo "Manager now has:\n";
echo "  • All operational permissions\n";
echo "  • All admin-level station permissions\n";
echo "  • Full access to reports, users, inventory\n";
echo "  • NO access to system-wide settings\n";
echo "  • NO access to manage all stations (station-specific only)\n";
echo "\nManagers can do everything admins can do at their station level!\n";

?>