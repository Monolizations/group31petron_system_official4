-- Create shifts table for shift management
-- Created: 2026-02-14
-- Purpose: Centralize shift time definitions

CREATE TABLE IF NOT EXISTS `shifts` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(50) NOT NULL UNIQUE,
  `start_time` time NOT NULL,
  `end_time` time NOT NULL,
  `description` text DEFAULT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Insert default shifts
INSERT INTO `shifts` (`name`, `start_time`, `end_time`, `description`) VALUES
('Morning', '06:00:00', '14:00:00', '6:00 AM - 2:00 PM shift'),
('Afternoon', '14:00:00', '22:00:00', '2:00 PM - 10:00 PM shift'),
('Evening', '22:00:00', '06:00:00', '10:00 PM - 6:00 AM shift')
ON DUPLICATE KEY UPDATE `name`=VALUES(`name`), `start_time`=VALUES(`start_time`), `end_time`=VALUES(`end_time`);
