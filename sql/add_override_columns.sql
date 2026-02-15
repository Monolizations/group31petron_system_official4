-- Add override columns to finalized tables for Admin unlock capability
-- Created: 2026-02-14
-- Purpose: 100% Hierarchy Compliance - Support Admin override mechanism

USE petron_pos_db_secure;

-- Add override columns to fuel_reconciliation table
ALTER TABLE fuel_reconciliation
  ADD COLUMN IF NOT EXISTS `override_reason` text DEFAULT NULL COMMENT 'Reason for Admin override of locked record',
  ADD COLUMN IF NOT EXISTS `override_by` int(11) DEFAULT NULL COMMENT 'Admin user ID who overrode the record',
  ADD COLUMN IF NOT EXISTS `override_at` timestamp NULL DEFAULT NULL COMMENT 'When Admin override occurred',
  ADD INDEX `idx_override_by` (`override_by`);

-- Add override columns to shift_reports table
ALTER TABLE shift_reports
  ADD COLUMN IF NOT EXISTS `override_reason` text DEFAULT NULL COMMENT 'Reason for Admin override of locked record',
  ADD COLUMN IF NOT EXISTS `override_by` int(11) DEFAULT NULL COMMENT 'Admin user ID who overrode the record',
  ADD COLUMN IF NOT EXISTS `override_at` timestamp NULL DEFAULT NULL COMMENT 'When Admin override occurred',
  ADD INDEX `idx_override_by` (`override_by`);

-- Add override columns to job_orders table
ALTER TABLE job_orders
  ADD COLUMN IF NOT EXISTS `override_reason` text DEFAULT NULL COMMENT 'Reason for Admin override of locked record',
  ADD COLUMN IF NOT EXISTS `override_by` int(11) DEFAULT NULL COMMENT 'Admin user ID who overrode the record',
  ADD COLUMN IF NOT EXISTS `override_at` timestamp NULL DEFAULT NULL COMMENT 'When Admin override occurred',
  ADD INDEX `idx_override_by` (`override_by`);

-- Success message
SELECT 'SUCCESS: Override columns added to fuel_reconciliation, shift_reports, and job_orders tables' as status;
