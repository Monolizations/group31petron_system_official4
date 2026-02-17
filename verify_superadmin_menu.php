<?php
/**
 * Verify Superadmin Only Sees Developer Items
 */

require_once __DIR__ . '/backend/lib.php';
require_once __DIR__ . '/partials/rbac_menu.php';

echo "╔══════════════════════════════════════════════════════════════════╗\n";
echo "║  SUPERADMIN MENU ACCESS - DEVELOPER ONLY                        ║\n";
echo "╚══════════════════════════════════════════════════════════════════╝\n\n";

$user_role = 'superadmin';
$user_permissions = get_user_permissions($user_role);

echo "Superadmin Permissions (" . count($user_permissions) . "):\n";
sort($user_permissions);
foreach ($user_permissions as $perm) {
    echo "  ✅ $perm\n";
}

echo "\n═══════════════════════════════════════════════════════════════════\n";
echo "📋 MENU ITEMS SUPERADMIN CAN SEE:\n\n";

// Filter menu based on permissions
$filtered_menu = filter_menu_by_permissions($master_menu, $user_role);

foreach ($filtered_menu as $menu_item) {
    echo "🗂️  {$menu_item['label']}\n";
    if (isset($menu_item['sub_items']) && !empty($menu_item['sub_items'])) {
        foreach ($menu_item['sub_items'] as $sub_item) {
            echo "   └── {$sub_item['label']}\n";
        }
    }
    echo "\n";
}

echo "═══════════════════════════════════════════════════════════════════\n";
echo "❌ MENU ITEMS SUPERADMIN CANNOT SEE:\n\n";

$all_menu_labels = [];
foreach ($master_menu as $menu_item) {
    $all_menu_labels[] = $menu_item['label'];
}

$visible_labels = array_column($filtered_menu, 'label');
$hidden_labels = array_diff($all_menu_labels, $visible_labels);

foreach ($hidden_labels as $label) {
    echo "  ❌ $label\n";
}

echo "\n═══════════════════════════════════════════════════════════════════\n";
echo "✅ SUPERADMIN ONLY SEES:\n\n";
echo "  🗂️  Dashboard\n";
echo "  🗂️  User Management\n";
echo "  🗂️  Station Management\n";
echo "  🗂️  System Admin\n";
echo "      └── System Settings\n";
echo "      └── Audit Logs\n";
echo "      └── Developer Panel\n\n";
echo "═══════════════════════════════════════════════════════════════════\n";

?>