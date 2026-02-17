<?php
/**
 * Migration: Create missing staff_schedules and staff_tasks tables
 * This fixes the error: Table 'petron_pos_db_secure.staff_schedules' doesn't exist
 */

require_once __DIR__ . '/public/db_connect.php';

echo "🔧 FIXING MISSING TABLES\n";
echo str_repeat("=", 60) . "\n\n";

try {
    // Check if staff_schedules table exists
    $stmt = $pdo->query("SHOW TABLES LIKE 'staff_schedules'");
    if ($stmt->rowCount() > 0) {
        echo "✅ Table 'staff_schedules' already exists\n";
    } else {
        echo "➕ Creating staff_schedules table...\n";
        
        $pdo->exec("CREATE TABLE staff_schedules (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            shift VARCHAR(50) NOT NULL,
            scheduled_date DATE NOT NULL,
            status VARCHAR(20) DEFAULT 'scheduled',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            UNIQUE KEY unique_user_date (user_id, scheduled_date),
            INDEX idx_user_id (user_id),
            INDEX idx_scheduled_date (scheduled_date)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        
        echo "  ✅ staff_schedules table created successfully\n";
    }
    
    // Check if staff_tasks table exists
    $stmt = $pdo->query("SHOW TABLES LIKE 'staff_tasks'");
    if ($stmt->rowCount() > 0) {
        echo "✅ Table 'staff_tasks' already exists\n";
    } else {
        echo "➕ Creating staff_tasks table...\n";
        
        $pdo->exec("CREATE TABLE staff_tasks (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            task TEXT NOT NULL,
            priority VARCHAR(20) DEFAULT 'medium',
            status VARCHAR(20) DEFAULT 'pending',
            assigned_date DATE DEFAULT NULL,
            due_date DATE DEFAULT NULL,
            completed_at TIMESTAMP NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_user_id (user_id),
            INDEX idx_status (status)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        
        echo "  ✅ staff_tasks table created successfully\n";
    }
    
    echo "\n" . str_repeat("=", 60) . "\n";
    echo "✅ ALL MISSING TABLES CREATED!\n\n";
    echo "The staff management feature should now work properly.\n";
    echo "You can now use the Staff Management page without errors.\n";
    
} catch (Exception $e) {
    echo "\n❌ Error: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}

echo "\n";
?>