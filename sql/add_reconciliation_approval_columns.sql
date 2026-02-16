-- Add Manager Approval Columns to fuel_reconciliation Table
-- These columns are needed for the Manager approval workflow

-- Check if columns exist before adding
SET @dbname = DATABASE();
SET @tablename = 'fuel_reconciliation';

-- Add approved_by column if it doesn't exist
SET @column_exists = (SELECT COUNT(*) 
                      FROM INFORMATION_SCHEMA.COLUMNS 
                      WHERE TABLE_SCHEMA = @dbname 
                      AND TABLE_NAME = @tablename 
                      AND COLUMN_NAME = 'approved_by');

SET @sql = IF(@column_exists = 0, 
              'ALTER TABLE fuel_reconciliation ADD COLUMN approved_by INT DEFAULT NULL AFTER variance_percent',
              'SELECT "approved_by column already exists"');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Add approved_at column if it doesn't exist
SET @column_exists = (SELECT COUNT(*) 
                      FROM INFORMATION_SCHEMA.COLUMNS 
                      WHERE TABLE_SCHEMA = @dbname 
                      AND TABLE_NAME = @tablename 
                      AND COLUMN_NAME = 'approved_at');

SET @sql = IF(@column_exists = 0, 
              'ALTER TABLE fuel_reconciliation ADD COLUMN approved_at DATETIME DEFAULT NULL AFTER approved_by',
              'SELECT "approved_at column already exists"');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Add manager_notes column if it doesn't exist
SET @column_exists = (SELECT COUNT(*) 
                      FROM INFORMATION_SCHEMA.COLUMNS 
                      WHERE TABLE_SCHEMA = @dbname 
                      AND TABLE_NAME = @tablename 
                      AND COLUMN_NAME = 'manager_notes');

SET @sql = IF(@column_exists = 0, 
              'ALTER TABLE fuel_reconciliation ADD COLUMN manager_notes TEXT DEFAULT NULL AFTER approved_at',
              'SELECT "manager_notes column already exists"');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Add foreign key constraint if it doesn't exist (optional)
-- SET @fk_exists = (SELECT COUNT(*) 
--                   FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE 
--                   WHERE TABLE_SCHEMA = @dbname 
--                   AND TABLE_NAME = @tablename 
--                   AND CONSTRAINT_NAME = 'fk_recon_approved_by');
-- 
-- SET @sql = IF(@fk_exists = 0, 
--               'ALTER TABLE fuel_reconciliation ADD CONSTRAINT fk_recon_approved_by FOREIGN KEY (approved_by) REFERENCES users(id)',
--               'SELECT "Foreign key already exists"');
-- PREPARE stmt FROM @sql;
-- EXECUTE stmt;
-- DEALLOCATE PREPARE stmt;

-- Add index for performance
SET @index_exists = (SELECT COUNT(*) 
                     FROM INFORMATION_SCHEMA.STATISTICS 
                     WHERE TABLE_SCHEMA = @dbname 
                     AND TABLE_NAME = @tablename 
                     AND INDEX_NAME = 'idx_recon_status');

SET @sql = IF(@index_exists = 0, 
              'ALTER TABLE fuel_reconciliation ADD INDEX idx_recon_status (status)',
              'SELECT "Index idx_recon_status already exists"');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SELECT 'Manager approval columns added successfully (if they did not exist)' AS result;
