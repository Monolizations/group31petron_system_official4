<?php
/**
 * Migration: Create shifts table for 8-hour shift system
 * Run this file to set up the shifts table
 */

require_once __DIR__ . '/public/db_connect.php';

try {
    // Create shifts table
    $pdo->exec("CREATE TABLE IF NOT EXISTS shifts (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(50) NOT NULL UNIQUE,
        start_time TIME NOT NULL,
        end_time TIME NOT NULL,
        description TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");
    
    // Insert Morning and Evening shifts (8 hours each)
    $stmt = $pdo->prepare("INSERT IGNORE INTO shifts (name, start_time, end_time, description) VALUES 
        ('Morning', '06:00:00', '14:00:00', 'Morning shift 6AM-2PM (8 hours)'),
        ('Evening', '14:00:00', '22:00:00', 'Evening shift 2PM-10PM (8 hours)')");
    $stmt->execute();
    
    echo "✅ Shifts table created successfully!\n";
    echo "✅ Morning shift (6AM-2PM) added\n";
    echo "✅ Evening shift (2PM-10PM) added\n";
    
} catch (PDOException $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
?>