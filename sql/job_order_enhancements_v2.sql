-- Job Order Management System Enhancements
-- Adding columns for staff-driven, admin-supervised workflow
-- Version 2: Fixed foreign key constraint errors

-- Add new columns to job_orders table
ALTER TABLE `job_orders` 
ADD COLUMN IF NOT EXISTS `requires_approval` TINYINT(1) DEFAULT 0 COMMENT 'Whether job requires admin approval',
ADD COLUMN IF NOT EXISTS `reviewed_by` INT(11) DEFAULT NULL COMMENT 'Admin who reviewed the job',
ADD COLUMN IF NOT EXISTS `reviewed_at` DATETIME DEFAULT NULL COMMENT 'When job was reviewed',
ADD COLUMN IF NOT EXISTS `approved_by` INT(11) DEFAULT NULL COMMENT 'Admin who gave final approval',
ADD COLUMN IF NOT EXISTS `approved_at` DATETIME DEFAULT NULL COMMENT 'When job was approved',
ADD COLUMN IF NOT EXISTS `admin_remarks` TEXT DEFAULT NULL COMMENT 'Admin review remarks',
ADD COLUMN IF NOT EXISTS `estimated_labor_cost` DECIMAL(10,2) DEFAULT 0.00 COMMENT 'Estimated labor cost',
ADD COLUMN IF NOT EXISTS `estimated_parts_cost` DECIMAL(10,2) DEFAULT 0.00 COMMENT 'Estimated parts cost',
ADD COLUMN IF NOT EXISTS `actual_labor_cost` DECIMAL(10,2) DEFAULT 0.00 COMMENT 'Actual labor cost after completion',
ADD COLUMN IF NOT EXISTS `actual_parts_cost` DECIMAL(10,2) DEFAULT 0.00 COMMENT 'Actual parts cost after completion',
ADD COLUMN IF NOT EXISTS `total_cost` DECIMAL(10,2) DEFAULT 0.00 COMMENT 'Total job cost',
ADD COLUMN IF NOT EXISTS `actual_duration` INT(11) DEFAULT NULL COMMENT 'Actual duration in minutes';

-- Add status 'Reviewed' to job_orders status enum if not exists
ALTER TABLE `job_orders` 
MODIFY COLUMN `status` ENUM('Pending','Reviewed','In Progress','Completed','Verified','finalized','Cancelled','Rejected') DEFAULT 'Pending';

-- Drop existing foreign key constraints if they exist (to avoid duplicate key error)
SET @constraint_exists := (SELECT COUNT(*) 
    FROM information_schema.TABLE_CONSTRAINTS 
    WHERE CONSTRAINT_SCHEMA = DATABASE()
    AND TABLE_NAME = 'job_orders' 
    AND CONSTRAINT_NAME = 'fk_job_reviewed_by');

SET @drop_reviewed = IF(@constraint_exists > 0, 
    'ALTER TABLE `job_orders` DROP FOREIGN KEY `fk_job_reviewed_by`', 
    'SELECT "Constraint fk_job_reviewed_by does not exist"');
PREPARE stmt FROM @drop_reviewed;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @constraint_exists := (SELECT COUNT(*) 
    FROM information_schema.TABLE_CONSTRAINTS 
    WHERE CONSTRAINT_SCHEMA = DATABASE()
    AND TABLE_NAME = 'job_orders' 
    AND CONSTRAINT_NAME = 'fk_job_approved_by');

SET @drop_approved = IF(@constraint_exists > 0, 
    'ALTER TABLE `job_orders` DROP FOREIGN KEY `fk_job_approved_by`', 
    'SELECT "Constraint fk_job_approved_by does not exist"');
PREPARE stmt FROM @drop_approved;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Add foreign keys for reviewers and approvers
ALTER TABLE `job_orders`
ADD CONSTRAINT `fk_job_reviewed_by` FOREIGN KEY (`reviewed_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

ALTER TABLE `job_orders`
ADD CONSTRAINT `fk_job_approved_by` FOREIGN KEY (`approved_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

-- Create inventory_transactions table if not exists
CREATE TABLE IF NOT EXISTS `inventory_transactions` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `station_id` INT(11) NOT NULL,
  `product_id` INT(11) NOT NULL,
  `transaction_type` ENUM('addition','deduction','adjustment','transfer') NOT NULL DEFAULT 'deduction',
  `quantity` DECIMAL(10,2) NOT NULL,
  `reference_type` VARCHAR(50) DEFAULT NULL COMMENT 'job_order, sale, etc',
  `reference_id` INT(11) DEFAULT NULL COMMENT 'ID of reference record',
  `notes` TEXT DEFAULT NULL,
  `created_by` INT(11) DEFAULT NULL,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP(),
  PRIMARY KEY (`id`),
  KEY `fk_inv_trans_station` (`station_id`),
  KEY `fk_inv_trans_product` (`product_id`),
  KEY `idx_transaction_type` (`transaction_type`),
  KEY `idx_reference` (`reference_type`, `reference_id`),
  CONSTRAINT `fk_inv_trans_station` FOREIGN KEY (`station_id`) REFERENCES `stations` (`id`),
  CONSTRAINT `fk_inv_trans_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Add indexes for better query performance (if they don't exist)
ALTER TABLE `job_orders`
ADD INDEX IF NOT EXISTS `idx_status` (`status`),
ADD INDEX IF NOT EXISTS `idx_created_at` (`created_at`),
ADD INDEX IF NOT EXISTS `idx_requires_approval` (`requires_approval`),
ADD INDEX IF NOT EXISTS `idx_station_status` (`station_id`, `status`);

-- Insert sample service categories if not exists
INSERT IGNORE INTO `service_categories` (`name`, `description`, `default_parts_cost`, `default_labor_cost`, `default_duration`) VALUES
('Change Oil', 'Engine oil change and filter replacement', 500.00, 200.00, 30),
('Brake Service', 'Brake inspection, pad replacement, and adjustment', 800.00, 300.00, 45),
('Vulcanizing', 'Tire repair and patching services', 200.00, 150.00, 20),
('Car Wash', 'Complete vehicle washing and cleaning', 0.00, 100.00, 30),
('Battery Check', 'Battery testing and replacement if needed', 1500.00, 150.00, 20),
('Engine Tune-up', 'Complete engine diagnostic and tuning', 2000.00, 800.00, 120),
('Air Filter Replacement', 'Air filter inspection and replacement', 300.00, 100.00, 15),
('Wheel Alignment', 'Wheel alignment and balancing', 500.00, 400.00, 60),
('Transmission Service', 'Transmission fluid change and inspection', 1800.00, 1000.00, 90),
('General Inspection', 'Complete vehicle safety inspection', 0.00, 300.00, 45);
