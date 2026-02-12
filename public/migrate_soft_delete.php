<?php
// Run this file to execute the soft delete migration
require_once __DIR__ . '/../public/db_connect.php';

try {
    echo "Starting migration: Add Soft Delete Support for Users\n\n";
    
    // 1. Add is_deleted column to users table
    echo "1. Adding is_deleted, deleted_at, deleted_by columns to users table...\n";
    $pdo->exec("ALTER TABLE `users` 
                ADD COLUMN `is_deleted` TINYINT(1) NOT NULL DEFAULT 0 AFTER `status`,
                ADD COLUMN `deleted_at` DATETIME NULL DEFAULT NULL AFTER `is_deleted`,
                ADD COLUMN `deleted_by` INT(11) NULL DEFAULT NULL AFTER `deleted_at`");
    echo "   ✓ Columns added successfully\n\n";
    
    // Add index
    echo "2. Adding index for is_deleted column...\n";
    $pdo->exec("ALTER TABLE `users` ADD INDEX `idx_is_deleted` (`is_deleted`)");
    echo "   ✓ Index added successfully\n\n";
    
    // 2. Create user_deletions audit log table
    echo "3. Creating user_deletions audit log table...\n";
    $pdo->exec("CREATE TABLE IF NOT EXISTS `user_deletions` (
      `id` INT(11) NOT NULL AUTO_INCREMENT,
      `user_id` INT(11) NOT NULL,
      `username` VARCHAR(50) NOT NULL,
      `name` VARCHAR(100) DEFAULT NULL,
      `role` ENUM('superadmin','admin','manager','staff') NOT NULL,
      `station_id` INT(11) DEFAULT NULL,
      `deleted_by` INT(11) NOT NULL,
      `deleted_by_username` VARCHAR(50) NOT NULL,
      `deleted_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
      `reason` TEXT DEFAULT NULL,
      `ip_address` VARCHAR(45) DEFAULT NULL,
      PRIMARY KEY (`id`),
      KEY `idx_user_id` (`user_id`),
      KEY `idx_deleted_by` (`deleted_by`),
      KEY `idx_deleted_at` (`deleted_at`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci");
    echo "   ✓ Table created successfully\n\n";
    
    // 3. Add foreign key constraint
    echo "4. Adding foreign key constraint...\n";
    try {
        $pdo->exec("ALTER TABLE `users` 
                   ADD CONSTRAINT `fk_users_deleted_by` 
                   FOREIGN KEY (`deleted_by`) REFERENCES `users`(`id`) 
                   ON DELETE SET NULL");
        echo "   ✓ Foreign key added successfully\n\n";
    } catch (PDOException $e) {
        if (strpos($e->getMessage(), 'Duplicate key') !== false) {
            echo "   ⚠ Foreign key already exists, skipping\n\n";
        } else {
            throw $e;
        }
    }
    
    echo "✅ Migration completed successfully!\n";
    echo "Soft delete system is now ready.\n";
    
} catch (PDOException $e) {
    echo "❌ Migration failed: " . $e->getMessage() . "\n";
    exit(1);
}
