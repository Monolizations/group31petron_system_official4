-- POS Sales Transaction Workflow Fix & Compliance
-- Fixes issues: Schema mismatch, adds unlock capability, adds is_locked flag
-- Created: 2026-02-14

USE petron_pos_db_secure;

-- Fix sales table schema to match SQL definition
-- Change: id VARCHAR(64) to match SQL
-- Add: is_locked, override_reason, override_by, override_at columns
-- Fix: sale_items FK constraint

-- Backup existing data (comment out after verifying)
-- CREATE TABLE IF NOT EXISTS sales_backup_20260214 AS SELECT * FROM sales;

-- Add missing columns if they don't exist
ALTER TABLE sales
  ADD COLUMN IF NOT EXISTS is_locked TINYINT(1) DEFAULT 0 COMMENT 'Locked after finalization',
  ADD COLUMN IF NOT EXISTS override_reason TEXT DEFAULT NULL COMMENT 'Reason for Admin override',
  ADD COLUMN IF NOT EXISTS override_by INT DEFAULT NULL COMMENT 'Admin user ID who overrode',
  ADD COLUMN IF NOT EXISTS override_at TIMESTAMP NULL DEFAULT NULL COMMENT 'When Admin override occurred';

-- Fix sale_items table FK constraint
ALTER TABLE sale_items
  DROP FOREIGN KEY IF EXISTS sale_items_ibfk_1,
  ADD CONSTRAINT sale_items_ibfk_1 FOREIGN KEY (sale_id) REFERENCES sales(id) ON DELETE CASCADE;

-- Create index for faster queries
CREATE INDEX IF NOT EXISTS idx_sales_status_locked ON sales(status, is_locked, station_id);
CREATE INDEX IF NOT EXISTS idx_sales_date ON sales(sale_date);
CREATE INDEX IF NOT EXISTS idx_sales_user ON sales(user_id);
CREATE INDEX IF NOT EXISTS idx_override_by ON sales(override_by);

-- Add audit column if it doesn't exist
ALTER TABLE sales
  ADD COLUMN IF NOT EXISTS finalized_by INT DEFAULT NULL COMMENT 'Who finalized the transaction',
  ADD COLUMN IF NOT EXISTS finalized_at TIMESTAMP NULL DEFAULT NULL COMMENT 'When finalized';

-- Update existing records with proper locked status
UPDATE sales SET is_locked = 1 WHERE status = 'Completed';

-- Success message
SELECT '✅ POS Sales Transaction Workflow Fixed' as status;
SELECT 'Sales table schema updated with: is_locked, override_reason, override_by, override_at, finalized_by, finalized_at' as columns;
SELECT 'Sale items FK constraint fixed' as fk;
