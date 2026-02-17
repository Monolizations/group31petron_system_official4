<?php
/**
 * Run fuel inventory sync migration
 */

require_once __DIR__ . '/public/db_connect.php';

echo "🔧 RUNNING FUEL INVENTORY SYNC MIGRATION\n";
echo str_repeat("=", 60) . "\n\n";

$sql_file = __DIR__ . '/sql/fix_fuel_inventory_sync.sql';

if (!file_exists($sql_file)) {
    echo "❌ SQL file not found: $sql_file\n";
    exit;
}

$sql = file_get_contents($sql_file);

// Split SQL file into individual statements
$statements = array_filter(array_map('trim', explode(';', $sql)));

$success = 0;
$failed = 0;
$errors = [];

foreach ($statements as $statement) {
    // Skip comments and empty lines
    if (empty($statement) || strpos($statement, '--') === 0 || strpos($statement, '/*') === 0) {
        continue;
    }
    
    try {
        $pdo->exec($statement);
        $success++;
        echo "✅ ";
        // Show first few words of the statement
        $words = array_slice(explode(' ', $statement), 0, 4);
        echo implode(' ', $words) . "...\n";
    } catch (PDOException $e) {
        $failed++;
        $errors[] = $e->getMessage();
        echo "⚠️  ";
        $words = array_slice(explode(' ', $statement), 0, 4);
        echo implode(' ', $words) . "... (skipped)\n";
    }
}

echo "\n" . str_repeat("=", 60) . "\n";
echo "📊 Results:\n";
echo "   Successful: $success\n";
echo "   Skipped/Failed: $failed\n";

if (count($errors) > 0 && count($errors) <= 5) {
    echo "\n⚠️  Some statements were skipped (may already exist):\n";
    foreach (array_slice($errors, 0, 5) as $error) {
        echo "   - " . substr($error, 0, 80) . "\n";
    }
}

echo "\n✅ Migration completed!\n";

// Show current state
echo "\n📋 Current Fuel Types:\n";
$stmt = $pdo->query("SELECT id, name FROM fuel_types ORDER BY id");
$fuel_types = $stmt->fetchAll(PDO::FETCH_ASSOC);
foreach ($fuel_types as $ft) {
    echo "   {$ft['id']}: {$ft['name']}\n";
}

echo "\n📋 Fuel Products in Inventory:\n";
$stmt = $pdo->query("
    SELECT p.id, p.name, p.sku 
    FROM products p 
    WHERE p.type_id = (SELECT id FROM product_types WHERE name = 'fuel')
    ORDER BY p.id
");
$products = $stmt->fetchAll(PDO::FETCH_ASSOC);
foreach ($products as $prod) {
    echo "   {$prod['id']}: {$prod['name']} ({$prod['sku']})\n";
}

echo "\n";
?>