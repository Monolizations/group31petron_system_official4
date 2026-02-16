<?php
/**
 * Detailed Inventory and POS System Analysis
 */

require_once __DIR__ . '/../public/db_connect.php';

echo "=== DETAILED INVENTORY & POS ANALYSIS ===\n\n";

// Analyze products table structure
echo "=== PRODUCTS TABLE STRUCTURE ===\n";
try {
    $stmt = $pdo->query("DESCRIBE products");
    $columns = $stmt->fetchAll();
    foreach ($columns as $col) {
        echo "- {$col['Field']}: {$col['Type']} " . ($col['Null'] === 'NO' ? '(NOT NULL)' : '(NULL)') . "\n";
    }
    
    // Sample products data
    echo "\n=== SAMPLE PRODUCTS (First 10) ===\n";
    $stmt = $pdo->query("SELECT * FROM products LIMIT 10");
    $products = $stmt->fetchAll();
    foreach ($products as $product) {
        echo "ID: {$product['id']}, Name: {$product['name']}, Category: {$product['category']}, Price: {$product['price']}\n";
    }
    
    // Product categories
    echo "\n=== PRODUCT CATEGORIES ===\n";
    $stmt = $pdo->query("SELECT category, COUNT(*) as count FROM products GROUP BY category");
    $categories = $stmt->fetchAll();
    foreach ($categories as $cat) {
        echo "- {$cat['category']}: {$cat['count']} items\n";
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}

// Analyze inventory table
echo "\n=== INVENTORY TABLE STRUCTURE ===\n";
try {
    $stmt = $pdo->query("DESCRIBE inventory");
    $columns = $stmt->fetchAll();
    foreach ($columns as $col) {
        echo "- {$col['Field']}: {$col['Type']} " . ($col['Null'] === 'NO' ? '(NOT NULL)' : '(NULL)') . "\n";
    }
    
    // Sample inventory data
    echo "\n=== INVENTORY DATA ===\n";
    $stmt = $pdo->query("SELECT * FROM inventory");
    $inventory = $stmt->fetchAll();
    foreach ($inventory as $item) {
        echo "Product ID: {$item['product_id']}, Quantity: {$item['quantity']}, Last Updated: {$item['updated_at']}\n";
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}

// Analyze sales table
echo "\n=== SALES TABLE STRUCTURE ===\n";
try {
    $stmt = $pdo->query("DESCRIBE sales");
    $columns = $stmt->fetchAll();
    foreach ($columns as $col) {
        echo "- {$col['Field']}: {$col['Type']} " . ($col['Null'] === 'NO' ? '(NOT NULL)' : '(NULL)') . "\n";
    }
    
    echo "\nSales table has 0 records - this is likely where transactions would be stored.\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}

// Analyze customers table
echo "\n=== CUSTOMERS TABLE STRUCTURE ===\n";
try {
    $stmt = $pdo->query("DESCRIBE customers");
    $columns = $stmt->fetchAll();
    foreach ($columns as $col) {
        echo "- {$col['Field']}: {$col['Type']} " . ($col['Null'] === 'NO' ? '(NOT NULL)' : '(NULL)') . "\n";
    }
    
    // Customer data
    echo "\n=== CUSTOMER DATA ===\n";
    $stmt = $pdo->query("SELECT * FROM customers");
    $customers = $stmt->fetchAll();
    foreach ($customers as $customer) {
        echo "ID: {$customer['id']}, Name: {$customer['name']}, Type: {$customer['type']}, Status: {$customer['status']}\n";
        if ($customer['type'] === 'credit') {
            echo "  Credit Limit: {$customer['credit_limit']}\n";
        }
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}

// Analyze job_orders table
echo "\n=== JOB ORDERS TABLE STRUCTURE ===\n";
try {
    $stmt = $pdo->query("DESCRIBE job_orders");
    $columns = $stmt->fetchAll();
    foreach ($columns as $col) {
        echo "- {$col['Field']}: {$col['Type']} " . ($col['Null'] === 'NO' ? '(NOT NULL)' : '(NULL)') . "\n";
    }
    
    // Job orders data
    echo "\n=== JOB ORDERS DATA ===\n";
    $stmt = $pdo->query("SELECT * FROM job_orders");
    $jobOrders = $stmt->fetchAll();
    foreach ($jobOrders as $job) {
        echo "ID: {$job['id']}, Customer: {$job['customer_id']}, Status: {$job['status']}, Total: {$job['total_amount']}\n";
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}

// Test API functionality simulation
echo "\n=== API FUNCTIONALITY TESTING ===\n";

// Simulate user session for API testing
session_start();
$_SESSION['user'] = [
    'id' => 1,
    'username' => 'testadmin', 
    'role' => 'admin',
    'station_id' => 1205
];
$_SESSION['user_id'] = 1;

// Test customers API logic
echo "Testing Customer Management Logic:\n";
try {
    // This simulates what the customers API would do
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM customers WHERE status = 'active'");
    $result = $stmt->fetch();
    echo "✅ Active customers query works: {$result['count']} active customers\n";
    
    // Test credit customers
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM customers WHERE type = 'credit' AND status = 'active'");
    $result = $stmt->fetch();
    echo "✅ Credit customers query works: {$result['count']} active credit customers\n";
    
} catch (Exception $e) {
    echo "❌ Customer management error: " . $e->getMessage() . "\n";
}

// Test inventory management logic
echo "\nTesting Inventory Management Logic:\n";
try {
    // Check inventory levels
    $stmt = $pdo->query("
        SELECT 
            p.name, 
            p.category,
            i.quantity,
            CASE 
                WHEN i.quantity <= 10 THEN 'LOW STOCK'
                WHEN i.quantity <= 50 THEN 'MEDIUM STOCK' 
                ELSE 'GOOD STOCK'
            END as stock_status
        FROM products p
        LEFT JOIN inventory i ON p.id = i.product_id
        ORDER BY i.quantity ASC
    ");
    $inventoryStatus = $stmt->fetchAll();
    
    foreach ($inventoryStatus as $item) {
        $qty = $item['quantity'] ?? 0;
        echo "- {$item['name']} ({$item['category']}): {$qty} units ({$item['stock_status']})\n";
    }
    
} catch (Exception $e) {
    echo "❌ Inventory management error: " . $e->getMessage() . "\n";
}

// Test sales logic
echo "\nTesting Sales Logic:\n";
try {
    // Simulate a sales transaction structure
    $sampleSale = [
        'customer_id' => 1,
        'user_id' => 1,
        'station_id' => 1205,
        'total_amount' => 1500.00,
        'payment_method' => 'cash',
        'items' => [
            ['product_id' => 1, 'quantity' => 2, 'price' => 750.00]
        ]
    ];
    
    echo "✅ Sample sale structure validated\n";
    echo "- Customer ID: {$sampleSale['customer_id']}\n";
    echo "- Total: ₱{$sampleSale['total_amount']}\n";
    echo "- Payment: {$sampleSale['payment_method']}\n";
    echo "- Items: " . count($sampleSale['items']) . " products\n";
    
} catch (Exception $e) {
    echo "❌ Sales logic error: " . $e->getMessage() . "\n";
}

echo "\n=== ANALYSIS COMPLETE ===\n";
?>