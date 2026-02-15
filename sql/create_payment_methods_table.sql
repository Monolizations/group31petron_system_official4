-- Create payment_methods table for payment method management
-- Created: 2026-02-14
-- Purpose: Centralize payment method options

CREATE TABLE IF NOT EXISTS `payment_methods` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(50) NOT NULL UNIQUE,
  `description` text DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Insert default payment methods
INSERT INTO `payment_methods` (`name`, `description`, `is_active`) VALUES
('Cash', 'Physical cash payment', 1),
('GCash', 'GCash mobile payment', 1),
('Maya', 'Maya mobile payment', 1),
('Credit Card', 'Credit/Debit card payment', 1),
('Bank Transfer', 'Direct bank transfer', 1)
ON DUPLICATE KEY UPDATE `name`=VALUES(`name`), `description`=VALUES(`description`);
