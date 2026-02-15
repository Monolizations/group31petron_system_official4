<?php
/**
 * Create unlock_history table
 * Run this script to create the unlock_history table in the database
 */

require_once __DIR__ . '/public/db_connect.php';

$sql = "CREATE TABLE IF NOT EXISTS `unlock_history` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `admin_id` int(11) NOT NULL COMMENT 'User ID of admin who performed unlock',
  `admin_name` varchar(100) NOT NULL COMMENT 'Name of admin (for audit)',
  `admin_role` varchar(50) NOT NULL COMMENT 'Role of admin (admin/superadmin)',
  `record_type` varchar(50) NOT NULL COMMENT 'Type of record (fuel_reconciliation, job_order, inventory, etc.)',
  `record_id` int(11) NOT NULL COMMENT 'ID of the unlocked record',
  `record_description` text COMMENT 'Description of what was unlocked',
  `reason` text NOT NULL COMMENT 'Reason provided for unlock (required)',
  `station_id` int(11) DEFAULT NULL COMMENT 'Station ID (if applicable)',
  `unlock_date` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'When the unlock was performed',
  `ip_address` varchar(45) DEFAULT NULL COMMENT 'IP address of admin at time of unlock',
  `session_id` varchar(100) DEFAULT NULL COMMENT 'Session ID for tracking',
  `password_verified` tinyint(1) DEFAULT 1 COMMENT 'Was password verified (1=yes, 0=no)',
  `status` enum('success','failed') DEFAULT 'success' COMMENT 'Unlock status',
  PRIMARY KEY (`id`),
  KEY `idx_admin_id` (`admin_id`),
  KEY `idx_record_type` (`record_type`),
  KEY `idx_record_id` (`record_id`),
  KEY `idx_unlock_date` (`unlock_date`),
  KEY `idx_station_id` (`station_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='Audit trail for admin record unlocks'";

try {
    $pdo->exec($sql);
    echo "✅ unlock_history table created successfully!\n";
} catch (PDOException $e) {
    echo "❌ Error creating table: " . $e->getMessage() . "\n";
}
?>
