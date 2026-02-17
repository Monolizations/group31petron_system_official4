<?php
/**
 * Database Migration: Purchase Order Workflow Enhancement
 * Run this file to update database schema
 */

require_once __DIR__ . '/public/db_connect.php';

echo "🔧 DATABASE MIGRATION: Purchase Order Workflow\n";
echo str_repeat("=", 70) . "\n\n";

try {
    // 1. Update purchase_orders table with new status values
    echo "1. Updating purchase_orders table...\n";
    
    // Check current status enum values
    $stmt = $pdo->query("SHOW COLUMNS FROM purchase_orders WHERE Field = 'status'");
    $column = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($column) {
        echo "   Current status column found\n";
        
        // Add new columns if they don't exist
        $columns_to_add = [
            'rejection_reason' => 'TEXT NULL',
            'approved_by' => 'INT NULL',
            'approved_at' => 'DATETIME NULL',
            'submitted_at' => 'DATETIME NULL',
            'withdrawn_at' => 'DATETIME NULL'
        ];
        
        foreach ($columns_to_add as $col => $def) {
            $check = $pdo->query("SHOW COLUMNS FROM purchase_orders LIKE '$col'");
            if ($check->rowCount() == 0) {
                $pdo->exec("ALTER TABLE purchase_orders ADD COLUMN $col $def");
                echo "   ✅ Added column: $col\n";
            } else {
                echo "   ℹ️  Column already exists: $col\n";
            }
        }
    } else {
        echo "   Creating purchase_orders table...\n";
        $pdo->exec("CREATE TABLE IF NOT EXISTS purchase_orders (
            id INT AUTO_INCREMENT PRIMARY KEY,
            po_number VARCHAR(50) UNIQUE NOT NULL,
            supplier_id INT NOT NULL,
            station_id INT NOT NULL,
            created_by INT NOT NULL,
            status ENUM('Draft', 'Pending Approval', 'Approved', 'Rejected', 
                       'Pending', 'Confirmed', 'Received', 'Cancelled') DEFAULT 'Draft',
            expected_delivery_date DATE,
            remarks TEXT,
            rejection_reason TEXT NULL,
            approved_by INT NULL,
            approved_at DATETIME NULL,
            submitted_at DATETIME NULL,
            withdrawn_at DATETIME NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        )");
        echo "   ✅ Created purchase_orders table\n";
    }
    
    // 2. Create PO activity log table
    echo "\n2. Creating po_activity_log table...\n";
    $pdo->exec("CREATE TABLE IF NOT EXISTS po_activity_log (
        id INT AUTO_INCREMENT PRIMARY KEY,
        po_id INT NOT NULL,
        action VARCHAR(50) NOT NULL,
        performed_by INT NOT NULL,
        details TEXT,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_po_id (po_id),
        INDEX idx_created_at (created_at)
    )");
    echo "   ✅ po_activity_log table ready\n";
    
    // 3. Create user_notifications table
    echo "\n3. Creating user_notifications table...\n";
    $pdo->exec("CREATE TABLE IF NOT EXISTS user_notifications (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        type VARCHAR(50) NOT NULL,
        title VARCHAR(255) NOT NULL,
        message TEXT,
        link VARCHAR(255),
        is_read TINYINT DEFAULT 0,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_user_id (user_id),
        INDEX idx_is_read (is_read),
        INDEX idx_type (type)
    )");
    echo "   ✅ user_notifications table ready\n";
    
    // 4. Update existing POs to have proper status
    echo "\n4. Updating existing PO statuses...\n";
    $pdo->exec("UPDATE purchase_orders SET status = 'Pending' WHERE status IS NULL OR status = ''");
    echo "   ✅ Updated existing POs\n";
    
    // 5. Ensure suppliers table exists
    echo "\n5. Checking suppliers table...\n";
    $pdo->exec("CREATE TABLE IF NOT EXISTS suppliers (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(255) NOT NULL,
        contact_person VARCHAR(255),
        phone VARCHAR(50),
        email VARCHAR(100),
        address TEXT,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    )");
    
    // Add default suppliers if empty
    $count = $pdo->query("SELECT COUNT(*) FROM suppliers")->fetchColumn();
    if ($count == 0) {
        $pdo->exec("INSERT INTO suppliers (name, contact_person, phone) VALUES 
            ('Petron Corporation', 'Sales Team', '1800-10-888-8888'),
            ('Local Merchandise Supplier', 'Contact Person', '0917-123-4567')");
        echo "   ✅ Added default suppliers\n";
    } else {
        echo "   ℹ️  Suppliers already exist\n";
    }
    
    // 6. Ensure purchase_order_items table has proper structure
    echo "\n6. Checking purchase_order_items table...\n";
    $pdo->exec("CREATE TABLE IF NOT EXISTS purchase_order_items (
        id INT AUTO_INCREMENT PRIMARY KEY,
        po_id INT NOT NULL,
        item_name VARCHAR(255) NOT NULL,
        quantity INT NOT NULL,
        unit_price DECIMAL(10,2) NOT NULL,
        total_price DECIMAL(10,2) NOT NULL,
        received_quantity INT DEFAULT 0,
        INDEX idx_po_id (po_id)
    )");
    echo "   ✅ purchase_order_items table ready\n";
    
    echo "\n" . str_repeat("=", 70) . "\n";
    echo "✅ DATABASE MIGRATION COMPLETE!\n\n";
    
    echo "📊 Summary:\n";
    echo "   • purchase_orders table updated with approval workflow fields\n";
    echo "   • po_activity_log table created for audit trail\n";
    echo "   • user_notifications table created for dashboard alerts\n";
    echo "   • Default suppliers added\n";
    echo "\n🚀 Ready for application updates!\n";
    
} catch (Exception $e) {
    echo "\n❌ Error: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}

echo "\n";
?>