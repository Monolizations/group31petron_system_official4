-- Quick Fix for Foreign Key Constraint Error (errno: 121)
-- Run this SQL directly in phpMyAdmin if you get "Duplicate key" error

-- Step 1: Drop existing foreign key constraints if they exist
ALTER TABLE `job_orders` DROP FOREIGN KEY IF EXISTS `fk_job_reviewed_by`;
ALTER TABLE `job_orders` DROP FOREIGN KEY IF EXISTS `fk_job_approved_by`;

-- Step 2: Re-add the foreign key constraints
ALTER TABLE `job_orders`
ADD CONSTRAINT `fk_job_reviewed_by` FOREIGN KEY (`reviewed_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

ALTER TABLE `job_orders`
ADD CONSTRAINT `fk_job_approved_by` FOREIGN KEY (`approved_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

-- Done! The foreign key error should be resolved.
