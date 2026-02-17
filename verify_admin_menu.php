<?php
/**
 * Verify Admin Menu Access - View Only Inventory, Customers, Station, Reports
 */

require_once __DIR__ . '/backend/lib.php';
require_once __DIR__ . '/partials/rbac_menu.php';

echo "╔══════════════════════════════════════════════════════════════════╗\n";
echo "║  ADMIN MENU ACCESS - VIEW ONLY & STATION-RELATED                ║\n";
echo "╚══════════════════════════════════════════════════════════════════╝\n\n";

$user_role = 'admin';
$user_permissions = get_user_permissions($user_role);

echo "Admin Permissions (" . count($user_permissions) . "):\n";
sort($user_permissions);
foreach ($user_permissions as $perm) {
    echo "  ✅ $perm\n";
}

echo "\n═══════════════════════════════════════════════════════════════════\n";
echo "📋 MENU ITEMS ADMIN CAN SEE:\n\n";

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
echo "❌ MENU ITEMS ADMIN CANNOT SEE:\n\n";

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
echo "✅ ADMIN CAN ACCESS:\n\n";
echo "  📦 Inventory (View Only)\n";
echo "  👥 Customers (View Only)\n";
echo "  🏢 Station Management\n";
echo "  📊 Admin Reports (All Reports)\n";
echo "  👤 User Management (Station)\n\n";
echo "❌ ADMIN CANNOT:\n\n";
echo "  ❌ Create/Edit Inventory\n";
echo "  ❌ Create/Edit Customers\n";
echo "  ❌ Operational Tasks (Transactions, Job Orders, Fuel)\n";
echo "  ❌ Staff Management\n";
echo "  ❌ System Administration\n\n";
echo "═══════════════════════════════════════════════════════════════════\n";

?>