<?php
/**
 * Audit Logs Setup Script
 * This script creates the audit_logs table and populates it with sample data
 * Run this once to initialize the audit logging system
 */

require_once __DIR__ . '/../public/db_connect.php';

$success = false;
$message = '';

try {
    // Check if table already exists
    $check_sql = "SHOW TABLES LIKE 'audit_logs'";
    $check_stmt = $pdo->query($check_sql);
    $table_exists = $check_stmt->rowCount() > 0;
    
    if (!$table_exists) {
        // Create the audit_logs table
        $create_sql = "CREATE TABLE IF NOT EXISTS audit_logs (
            id INT PRIMARY KEY AUTO_INCREMENT,
            user_id INT,
            log_type VARCHAR(50) NOT NULL COMMENT 'user, transaction, inventory, system',
            action_type VARCHAR(100) NOT NULL COMMENT 'Login, Logout, Create, Update, Delete, View',
            action_details TEXT,
            entity_type VARCHAR(100) COMMENT 'users, sales, inventory, customers, etc',
            entity_id INT,
            old_values JSON COMMENT 'For tracking what changed',
            new_values JSON COMMENT 'For tracking what changed',
            ip_address VARCHAR(45),
            user_agent TEXT,
            status VARCHAR(20) COMMENT 'Success, Failed, Pending',
            error_message TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
            INDEX idx_user_id (user_id),
            INDEX idx_log_type (log_type),
            INDEX idx_action_type (action_type),
            INDEX idx_created_at (created_at),
            INDEX idx_entity (entity_type, entity_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
        
        $pdo->exec($create_sql);
        $message .= "✓ Created audit_logs table\n";
    } else {
        $message .= "⚠ audit_logs table already exists\n";
    }
    
    // Check if table is empty
    $count_sql = "SELECT COUNT(*) as count FROM audit_logs";
    $count_stmt = $pdo->query($count_sql);
    $result = $count_stmt->fetch(PDO::FETCH_ASSOC);
    $record_count = $result['count'] ?? 0;
    
    if ($record_count === 0) {
        // Get sample user IDs
        $users_sql = "SELECT id FROM users WHERE status = 'active' LIMIT 5";
        $users_stmt = $pdo->query($users_sql);
        $users = $users_stmt->fetchAll(PDO::FETCH_COLUMN);
        
        if (!empty($users)) {
            // Insert sample audit logs
            $sample_logs = [];
            $today = new DateTime();
            
            // User activity logs
            for ($i = 0; $i < 5; $i++) {
                $user_id = $users[array_rand($users)];
                $date = clone $today;
                $date->sub(new DateInterval('P' . $i . 'D'));
                
                $sample_logs[] = [
                    'user_id' => $user_id,
                    'log_type' => 'user',
                    'action_type' => 'Login',
                    'action_details' => 'User logged in successfully',
                    'entity_type' => 'users',
                    'entity_id' => $user_id,
                    'ip_address' => '192.168.1.' . rand(100, 200),
                    'user_agent' => 'Mozilla/5.0 (Windows)',
                    'status' => 'Success',
                    'created_at' => $date->format('Y-m-d 08:00:00')
                ];
            }
            
            // Transaction logs
            for ($i = 0; $i < 8; $i++) {
                $user_id = $users[array_rand($users)];
                $date = clone $today;
                $date->sub(new DateInterval('P' . ($i % 5) . 'D'));
                $time_offset = sprintf('%02d:%02d:00', rand(9, 22), rand(0, 59));
                
                $sample_logs[] = [
                    'user_id' => $user_id,
                    'log_type' => 'transaction',
                    'action_type' => 'Sale',
                    'action_details' => 'Sale transaction completed - Amount: ₱' . number_format(rand(1000, 50000), 2),
                    'entity_type' => 'sales',
                    'entity_id' => $i + 1,
                    'ip_address' => '192.168.1.' . rand(100, 200),
                    'user_agent' => 'POS Terminal',
                    'status' => 'Success',
                    'created_at' => $date->format('Y-m-d') . ' ' . $time_offset,
                    'new_values' => json_encode(['amount' => rand(1000, 50000), 'type' => 'Sale'])
                ];
            }
            
            // Inventory logs
            for ($i = 0; $i < 6; $i++) {
                $user_id = $users[array_rand($users)];
                $date = clone $today;
                $date->sub(new DateInterval('P' . ($i % 5) . 'D'));
                $time_offset = sprintf('%02d:%02d:00', rand(9, 22), rand(0, 59));
                
                $products = ['Diesel', 'Gasoline', 'Premium', 'XCS Plus', 'Engine Oil'];
                $product = $products[array_rand($products)];
                
                $sample_logs[] = [
                    'user_id' => $user_id,
                    'log_type' => 'inventory',
                    'action_type' => 'Stock Adjustment',
                    'action_details' => sprintf('Stock adjusted - %s: %d units', $product, rand(100, 5000)),
                    'entity_type' => 'inventory',
                    'entity_id' => $i + 1,
                    'ip_address' => '192.168.1.' . rand(100, 200),
                    'user_agent' => 'Mozilla/5.0',
                    'status' => 'Success',
                    'created_at' => $date->format('Y-m-d') . ' ' . $time_offset,
                    'new_values' => json_encode(['product_name' => $product, 'quantity' => rand(100, 5000)])
                ];
            }
            
            // Insert all sample logs
            $insert_sql = "INSERT INTO audit_logs (user_id, log_type, action_type, action_details, entity_type, entity_id, ip_address, user_agent, status, created_at, new_values) 
                          VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            
            $insert_stmt = $pdo->prepare($insert_sql);
            $inserted_count = 0;
            
            foreach ($sample_logs as $log) {
                $insert_stmt->execute([
                    $log['user_id'],
                    $log['log_type'],
                    $log['action_type'],
                    $log['action_details'],
                    $log['entity_type'],
                    $log['entity_id'],
                    $log['ip_address'],
                    $log['user_agent'],
                    $log['status'],
                    $log['created_at'],
                    $log['new_values'] ?? null
                ]);
                $inserted_count++;
            }
            
            $message .= "✓ Inserted " . $inserted_count . " sample audit logs\n";
        } else {
            $message .= "⚠ No active users found to create sample logs\n";
        }
    } else {
        $message .= "ℹ audit_logs table already contains " . $record_count . " records\n";
    }
    
    $success = true;
    
} catch (Exception $e) {
    $success = false;
    $message = "✗ Error: " . $e->getMessage();
}

// Return JSON response
header('Content-Type: application/json');
echo json_encode([
    'success' => $success,
    'message' => $message
]);
