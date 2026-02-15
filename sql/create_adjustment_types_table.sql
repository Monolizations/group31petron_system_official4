-- Create adjustment_types table for fuel adjustment category management
-- Created: 2026-02-14
-- Purpose: Centralize fuel adjustment type definitions

CREATE TABLE IF NOT EXISTS `adjustment_types` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(50) NOT NULL UNIQUE,
  `description` text DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Insert default adjustment types
INSERT INTO `adjustment_types` (`name`, `description`, `is_active`) VALUES
('Loss', 'Fuel loss due to spillage, leakage, or other reasons', 1),
('Transfer', 'Fuel transfer between tanks or stations', 1),
('Consumption', 'Fuel used for equipment or testing', 1),
('Other', 'Other fuel adjustment reasons', 1)
ON DUPLICATE KEY UPDATE `name`=VALUES(`name`), `description`=VALUES(`description`);
