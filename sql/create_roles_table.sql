-- Create roles table for user role management
-- Created: 2026-02-14
-- Purpose: Centralize user roles management

CREATE TABLE IF NOT EXISTS `roles` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(50) NOT NULL UNIQUE,
  `description` text DEFAULT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Insert default roles
INSERT INTO `roles` (`name`, `description`) VALUES
('Super Admin', 'Full system access with all permissions'),
('Admin', 'Administrator with station management and user management'),
('Manager', 'Station manager with operational oversight'),
('Staff', 'Regular staff member with limited access'),
('Operations Staff', 'Operations team member with inventory management')
ON DUPLICATE KEY UPDATE `description`=VALUES(`description`);

-- Update users table to reference roles table (future enhancement)
-- ALTER TABLE users ADD COLUMN role_id INT(11) NULL AFTER role;
-- ALTER TABLE users ADD FOREIGN KEY (role_id) REFERENCES roles(id) ON DELETE SET NULL;
