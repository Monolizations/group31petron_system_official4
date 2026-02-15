-- Create service_categories table for service category management
-- Created: 2026-02-14
-- Purpose: Centralize service type definitions for job orders and dashboard

CREATE TABLE IF NOT EXISTS `service_categories` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL UNIQUE,
  `description` text DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Insert default service categories
INSERT INTO `service_categories` (`name`, `description`, `is_active`) VALUES
('Change Oil', 'Engine oil change service', 1),
('Brake Service', 'Brake system maintenance and repair', 1),
('Vulcanizing', 'Tire repair and vulcanizing', 1),
('Car Wash', 'Vehicle washing and cleaning', 1),
('Battery Check', 'Battery inspection and testing', 1),
('Engine Tune-up', 'Engine performance tuning', 1),
('Air Filter Replacement', 'Air filter replacement service', 1),
('Wheel Alignment', 'Wheel alignment and balancing', 1),
('Other Service', 'Other service types not listed', 1)
ON DUPLICATE KEY UPDATE `name`=VALUES(`name`), `description`=VALUES(`description`);
