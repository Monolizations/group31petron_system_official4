<?php
/**
 * Fix: Update purchase_orders status column ENUM values
 */

require_once __DIR__ . '/public/db_connect.php';

echo "🔧 FIXING PURCHASE ORDERS STATUS COLUMN\n";
echo str_repeat("=", 70) . "\n\n";

try {
    // Check current ENUM values
    echo "Checking current ENUM values...\n";
    $stmt = $pdo->query("SHOW COLUMNS FROM purchase_orders WHERE Field = 'status'");
    $column = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($column) {
        echo "Current column type: " . $column['Type'] . "\n\n";
        
        // Check if 'Draft' is in the ENUM
        if (strpos($column['Type'], 'Draft') === false) {
            echo "❌ 'Draft' status not found in ENUM!\n";
            echo "Updating ENUM values...\n\n";
            
            // Update the ENUM to include all required values
            $pdo->exec("ALTER TABLE purchase_orders 
                MODIFY COLUMN status ENUM('Draft', 'Pending Approval', 'Approved', 'Rejected', 
                                        'Pending', 'Confirmed', 'Received', 'Cancelled') 
                DEFAULT 'Draft'");
            
            echo "✅ ENUM values updated successfully!\n";
            echo "New values: Draft, Pending Approval, Approved, Rejected, Pending, Confirmed, Received, Cancelled\n";
        } else {
            echo "✅ ENUM values already correct!\n";
        }
    } else {
        echo "❌ Could not find status column!\n";
    }
    
    echo "\n" . str_repeat("=", 70) . "\n";
    echo "✅ FIX COMPLETE!\n";
    echo "\nYou can now create purchase orders without the truncation error.\n";
    
} catch (Exception $e) {
    echo "\n❌ Error: " . $e->getMessage() . "\n";
}

echo "\n";
?>