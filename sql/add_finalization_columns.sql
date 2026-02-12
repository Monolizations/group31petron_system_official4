-- Add columns to fuel_reconciliation table for finalization support
-- This enables admin/owner password-locked finalization of reports

USE petron_pos_db_secure;

-- Add finalization columns to existing table
ALTER TABLE fuel_reconciliation ADD COLUMN IF NOT EXISTS physical_stock DECIMAL(10,2) DEFAULT NULL;
ALTER TABLE fuel_reconciliation ADD COLUMN IF NOT EXISTS variance DECIMAL(10,2) DEFAULT NULL;
ALTER TABLE fuel_reconciliation ADD COLUMN IF NOT EXISTS variance_percent DECIMAL(8,2) DEFAULT NULL;
ALTER TABLE fuel_reconciliation ADD COLUMN IF NOT EXISTS finalized_by INT DEFAULT NULL;
ALTER TABLE fuel_reconciliation ADD COLUMN IF NOT EXISTS finalized_at TIMESTAMP NULL;
ALTER TABLE fuel_reconciliation ADD COLUMN IF NOT EXISTS admin_notes LONGTEXT DEFAULT NULL;
ALTER TABLE fuel_reconciliation ADD COLUMN IF NOT EXISTS is_locked TINYINT(1) DEFAULT 0;

-- Finalization complete
SELECT 'SUCCESS: All finalization columns added to fuel_reconciliation table' as status;
