<?php
require_once __DIR__ . '/public/db_connect.php';

echo "=== CHECKING BACKUP FOR FUEL PRODUCTS ===\n\n";

try {
    // Check if backup table exists
    $exists = $pdo->query("SHOW TABLES LIKE 'products_backup_20250217'")->fetchColumn();
    if (!$exists) {
        echo "Backup table not found!\n";
        exit(1);
    }
    
    // Count products by type in backup
    $stmt = $pdo->query("
        SELECT
            type_id,
            COUNT(*) as count,
            GROUP_CONCAT(DISTINCT category_id) as categories
        FROM products_backup_20250217
        GROUP BY type_id
        ORDER BY type_id
    ");
    
    echo "Products by Type in Backup:\n";
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $type_name = $row['type_id'] == 1 ? 'fuel' : ($row['type_id'] == 2 ? 'merch' : 'service');
        echo "  Type $type_name (type_id={$row['type_id']}): {$row['count']} products\n";
        echo "    Categories: {$row['categories']}\n";
    }
    
    echo "\n";
    
    // Get fuel products specifically
    $stmt = $pdo->query("
        SELECT
            id, sku, name, description,
            type_id, category_id,
            cost, price,
            created_at, updated_at
        FROM products_backup_20250217
        WHERE type_id = 1
        ORDER BY id
    ");
    
    $fuel_products = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (count($fuel_products) == 0) {
        echo "❌ No fuel products found in backup!\n";
        echo "\nChecking current product types table:\n";
        
        $stmt = $pdo->query("SELECT * FROM product_types");
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            echo "  ID {$row['id']}: {$row['name']} - {$row['description']}\n";
        }
        
        echo "\nChecking current products:\n";
        $stmt = $pdo->query("SELECT type_id, COUNT(*) as count FROM products GROUP BY type_id");
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $type_name = $row['type_id'] == 1 ? 'fuel' : ($row['type_id'] == 2 ? 'merch' : 'service');
            echo "  Type $type_name (type_id={$row['type_id']}): {$row['count']} products\n";
        }
    } else {
        echo "✓ Found " . count($fuel_products) . " fuel products in backup:\n\n";
        
        printf("%-5s %-20s %-40s %-10s %-10s\n", 'ID', 'SKU', 'Name', 'Cost', 'Price');
        echo str_repeat('-', 90) . "\n";
        
        foreach ($fuel_products as $product) {
            printf("%-5s %-20s %-40s %-10.2f %-10.2f\n",
                $product['id'],
                $product['sku'],
                substr($product['name'], 0, 40),
                $product['cost'],
                $product['price']
            );
        }
        
        // Check fuel categories
        echo "\nFuel Categories:\n";
        $stmt = $pdo->query("
            SELECT DISTINCT
                pc.id, pc.name
            FROM products_backup_20250217 p
            JOIN product_categories pc ON p.category_id = pc.id
            WHERE p.type_id = 1
            ORDER BY pc.id
        ");
        
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            echo "  Category ID {$row['id']}: {$row['name']}\n";
        }
        
        // Count fuel types
        echo "\nFuel Types in Database:\n";
        $stmt = $pdo->query("
            SELECT ft.id, ft.name
            FROM fuel_types ft
            ORDER BY ft.id
        ");
        
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            echo "  ID {$row['id']}: {$row['name']}\n";
        }
    }
    
} catch (Exception $e) {
    echo "❌ ERROR: " . $e->getMessage() . "\n";
    exit(1);
}

echo "\n";
?>
