-- Create admin_unlocks table for tracking Admin override operations
-- This enables audit trail for all unlock actions by Admin role
-- Created: 2026-02-14
-- Purpose: 100% Hierarchy Compliance - Admin (Owner) override capability

USE petron_pos_db_secure;

CREATE TABLE IF NOT EXISTS `admin_unlocks` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `table_name` varchar(100) NOT NULL COMMENT 'Table containing the locked record',
  `record_id` int(11) NOT NULL COMMENT 'ID of the unlocked record',
  `unlocked_by` int(11) NOT NULL COMMENT 'Admin user ID who performed unlock',
  `unlock_reason` text NOT NULL COMMENT 'Mandatory reason for unlock',
  `previous_status` varchar(50) NOT NULL COMMENT 'Status before unlock',
  `password_verified` tinyint(1) NOT NULL DEFAULT 1 COMMENT 'Whether admin password was verified',
  `ip_address` varchar(45) DEFAULT NULL COMMENT 'IP address of unlock request',
  `unlocked_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'When unlock occurred',
  PRIMARY KEY (`id`),
  KEY `idx_table_record` (`table_name`, `record_id`),
  KEY `idx_unlocked_by` (`unlocked_by`),
  KEY `idx_unlocked_at` (`unlocked_at`),
  CONSTRAINT `fk_admin_unlocks_user` FOREIGN KEY (`unlocked_by`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='Tracks all Admin unlock operations for audit trail';

-- Success message
SELECT 'SUCCESS: admin_unlocks table created for Admin override capability' as status;
