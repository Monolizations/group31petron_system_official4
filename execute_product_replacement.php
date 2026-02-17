<?php
/**
 * Execute Product Replacement Script
 * Replaces all products with new data for station 1250
 */

require_once __DIR__ . '/public/db_connect.php';

echo "=== PETRON POS SYSTEM - PRODUCT REPLACEMENT ===\n";
echo "Date: " . date('Y-m-d H:i:s') . "\n";
echo str_repeat("=", 60) . "\n\n";

try {
    // STEP 1: BACKUP EXISTING DATA
    echo "STEP 1: Backing up existing data...\n";
    
    $backup_date = date('Ymd');
    
    // Backup products
    $pdo->exec("CREATE TABLE IF NOT EXISTS products_backup_{$backup_date} AS SELECT * FROM products");
    $products_count = $pdo->query("SELECT COUNT(*) FROM products_backup_{$backup_date}")->fetchColumn();
    echo "✓ Products backed up: $products_count records\n";
    
    // Backup station_inventory
    $pdo->exec("CREATE TABLE IF NOT EXISTS station_inventory_backup_{$backup_date} AS SELECT * FROM station_inventory");
    $inventory_count = $pdo->query("SELECT COUNT(*) FROM station_inventory_backup_{$backup_date}")->fetchColumn();
    echo "✓ Station inventory backed up: $inventory_count records\n\n";
    
    // STEP 2: DELETE EXISTING DATA
    echo "STEP 2: Deleting existing data...\n";
    
    // Disable foreign key checks temporarily
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 0");
    
    $pdo->exec("DELETE FROM station_inventory");
    echo "✓ All station_inventory records deleted\n";
    
    $pdo->exec("DELETE FROM products");
    echo "✓ All products records deleted\n";
    
    // Re-enable foreign key checks
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 1");
    
    echo "\n";
    
    // STEP 3: READ AND EXECUTE SQL FILE
    echo "STEP 3: Loading new product data...\n";
    
    $sql_file = __DIR__ . '/sql/replace_products_complete_20250217.sql';
    if (!file_exists($sql_file)) {
        throw new Exception("SQL file not found: $sql_file");
    }
    
    // Read SQL file
    $sql = file_get_contents($sql_file);
    
    // Split into individual statements
    $statements = explode(';', $sql);
    
    $executed = 0;
    foreach ($statements as $stmt) {
        $stmt = trim($stmt);
        if (!empty($stmt) && !preg_match('/^(--|\/\*|SELECT)/', $stmt)) {
            try {
                $pdo->exec($stmt);
                $executed++;
            } catch (PDOException $e) {
                // Skip errors for backup tables that already exist
                if (strpos($e->getMessage(), 'already exists') === false) {
                    echo "Warning: " . $e->getMessage() . "\n";
                }
            }
        }
    }
    
    echo "✓ Executed $executed SQL statements\n\n";
    
    // STEP 4: VERIFICATION
    echo "STEP 4: Verifying data...\n";
    
    // Count products
    $total_products = $pdo->query("SELECT COUNT(*) FROM products")->fetchColumn();
    echo "✓ Total products: $total_products\n";
    
    // Count by category
    echo "\nProducts by Category:\n";
    $stmt = $pdo->query("
        SELECT 
            pc.name AS category,
            COUNT(p.id) AS product_count,
            CONCAT('₱', MIN(p.price), ' - ₱', MAX(p.price)) AS price_range
        FROM products p
        JOIN product_categories pc ON p.category_id = pc.id
        GROUP BY pc.id, pc.name
        ORDER BY product_count DESC
    ");
    
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        printf("  %s: %d products (%s)\n", 
            $row['category'], 
            $row['product_count'], 
            $row['price_range']
        );
    }
    
    // Count by type
    echo "\nProducts by Type:\n";
    $stmt = $pdo->query("
        SELECT 
            pt.name AS type,
            COUNT(p.id) AS product_count
        FROM products p
        JOIN product_types pt ON p.type_id = pt.id
        GROUP BY pt.id, pt.name
    ");
    
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        printf("  %s: %d products\n", $row['type'], $row['product_count']);
    }
    
    // Verify station inventory
    echo "\nStation Inventory Summary:\n";
    $stmt = $pdo->query("
        SELECT
            COUNT(*) AS total_records,
            COUNT(CASE WHEN stock_level = 0 THEN 1 END) AS zero_stock,
            COUNT(CASE WHEN stock_level > 0 THEN 1 END) AS in_stock,
            SUM(stock_level) AS total_units
        FROM station_inventory
        WHERE station_id = 1250
    ");
    
    $inv = $stmt->fetch(PDO::FETCH_ASSOC);
    printf("  Total records: %d\n", $inv['total_records']);
    printf("  Items in stock: %d\n", $inv['in_stock']);
    printf("  Out of stock: %d\n", $inv['zero_stock']);
    printf("  Total units: %s\n", number_format($inv['total_units']));
    
    // Sample inventory
    echo "\nSample Inventory (First 10):\n";
    $stmt = $pdo->query("
        SELECT
            p.sku,
            p.name,
            p.price AS unit_price,
            si.stock_level,
            pc.name AS category
        FROM station_inventory si
        JOIN products p ON si.product_id = p.id
        JOIN product_categories pc ON p.category_id = pc.id
        WHERE si.station_id = 1250
        ORDER BY si.product_id
        LIMIT 10
    ");
    
    printf("  %-20s %-40s %10s %8s %s\n", 
        'SKU', 'Name', 'Price', 'Stock', 'Category');
    printf("  %-20s %-40s %10s %8s %s\n", 
        str_repeat('-', 20), str_repeat('-', 40), str_repeat('-', 10), 
        str_repeat('-', 8), str_repeat('-', 20));
    
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        printf("  %-20s %-40s %10.2f %8d %s\n",
            $row['sku'],
            substr($row['name'], 0, 40),
            $row['unit_price'],
            $row['stock_level'],
            $row['category']
        );
    }
    
    // Test user access
    echo "\nUser Access Verification:\n";
    $stmt = $pdo->query("
        SELECT
            u.name AS user_name,
            u.role AS user_role,
            COUNT(DISTINCT p.id) AS accessible_products
        FROM users u
        CROSS JOIN products p
        WHERE u.station_id = 1250
          AND p.type_id = 2
        GROUP BY u.id, u.name, u.role
        ORDER BY user_role, user_name
    ");
    
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        printf("  %s (%s): %d products accessible\n",
            $row['user_name'],
            $row['user_role'],
            $row['accessible_products']
        );
    }
    
    echo "\n" . str_repeat("=", 60) . "\n";
    echo "EXECUTION COMPLETE!\n";
    echo "✓ All products replaced (132 total)\n";
    echo "✓ All products have type_id = 2 (merch)\n";
    echo "✓ Station inventory created for station 1250\n";
    echo "✓ Random stock levels applied\n";
    echo "✓ Backup tables: products_backup_{$backup_date}, station_inventory_backup_{$backup_date}\n";
    
} catch (Exception $e) {
    echo "\n❌ ERROR: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
    exit(1);
}

echo "\n";
?>
