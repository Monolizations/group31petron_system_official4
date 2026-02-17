<?php
/**
 * Test: Check form data handling
 */

require_once __DIR__ . '/public/db_connect.php';

echo "🧪 TESTING FORM DATA HANDLING\n";
echo str_repeat("=", 70) . "\n\n";

// Simulate form data
$_POST = [
    'action' => 'save_draft',
    'supplier_id' => '1',
    'remarks' => 'Test remarks',
    'items' => [
        0 => [
            'name' => 'Diesel Max',
            'qty' => '1000',
            'price' => '45.50'
        ]
    ]
];

$action = $_POST['action'] ?? '';
$supplier_id = $_POST['supplier_id'] ?? '';
$remarks = $_POST['remarks'] ?? '';
$items = $_POST['items'] ?? [];

echo "Posted data:\n";
echo "  Action: $action\n";
echo "  Supplier ID: $supplier_id\n";
echo "  Remarks: $remarks\n";
echo "  Items count: " . count($items) . "\n\n";

echo "Items array:\n";
print_r($items);
echo "\n";

echo "Processing items:\n";
foreach ($items as $index => $item) {
    echo "  Item $index:\n";
    echo "    - name: " . (isset($item['name']) ? "'{$item['name']}'" : "NOT SET") . "\n";
    echo "    - qty: " . (isset($item['qty']) ? "'{$item['qty']}'" : "NOT SET") . "\n";
    echo "    - price: " . (isset($item['price']) ? "'{$item['price']}'" : "NOT SET") . "\n";
    
    $has_name = !empty($item['name']);
    $has_qty = isset($item['qty']) && $item['qty'] > 0;
    
    echo "    - Validation: name=" . ($has_name ? 'YES' : 'NO') . ", qty=" . ($has_qty ? 'YES' : 'NO') . "\n";
    
    if ($has_name && $has_qty) {
        $total = $item['qty'] * $item['price'];
        echo "    - Would insert: qty={$item['qty']}, price={$item['price']}, total=$total\n";
    }
}

echo "\n" . str_repeat("=", 70) . "\n";
echo "✅ Test complete\n";
echo "\n";

?>