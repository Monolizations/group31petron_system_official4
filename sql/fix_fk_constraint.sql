-- Manual Steps to Fix Foreign Key Constraint Error
-- This script shows how to resolve the MySQL error when changing FK constraints

USE petron_pos_db_secure;

-- ============================================
-- STEP 1: Check Current Foreign Key Constraints
-- ============================================

SELECT 'Current Foreign Key Constraints for sale_items:' as 'Status';

SELECT
    TABLE_NAME,
    CONSTRAINT_NAME,
    REFERENCED_TABLE_NAME,
    COLUMN_NAME,
    REFERENCED_COLUMN_NAME
FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE
WHERE TABLE_SCHEMA = 'petron_pos_db_secure'
  AND TABLE_NAME = 'sale_items'
  AND REFERENCED_TABLE_NAME = 'sales';

-- ============================================
-- STEP 2: Check for Orphaned Records
-- ============================================

SELECT 'Orphaned sale_items records (no corresponding sale):' as 'Status';

SELECT COUNT(*) as orphan_count
FROM sale_items si
LEFT JOIN sales s ON si.sale_id = s.id
WHERE s.id IS NULL;

-- If there are orphaned records, uncomment the next line to delete them:
-- DELETE FROM sale_items WHERE sale_id NOT IN (SELECT id FROM sales);

-- ============================================
-- STEP 3: Drop Foreign Key Constraint
-- ============================================

SELECT 'Dropping foreign key constraint...' as 'Status';

-- Get the FK constraint name
SET @fk_name = (SELECT CONSTRAINT_NAME FROM INFORMATION_SCHEMA.TABLE_CONSTRAINTS
                WHERE TABLE_SCHEMA = 'petron_pos_db_secure'
                AND TABLE_NAME = 'sale_items'
                AND CONSTRAINT_TYPE = 'FOREIGN KEY'
                LIMIT 1);

SELECT CONCAT('Foreign key to drop: ', IFNULL(@fk_name, 'None')) as 'FK Name';

-- Drop the FK (if it exists)
SET @sql_drop = CONCAT('ALTER TABLE sale_items DROP FOREIGN KEY IF EXISTS ', @fk_name);
PREPARE stmt FROM @sql_drop;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SELECT '✅ Foreign key constraint dropped' as 'Status';

-- ============================================
-- STEP 4: Add New Foreign Key Constraint
-- ============================================

SELECT 'Adding new foreign key constraint...' as 'Status';

ALTER TABLE sale_items
ADD CONSTRAINT sale_items_ibfk_1
FOREIGN KEY (sale_id) REFERENCES sales(id)
ON DELETE CASCADE
ON UPDATE CASCADE;

SELECT '✅ New foreign key constraint added' as 'Status';

-- ============================================
-- STEP 5: Verify the Fix
-- ============================================

SELECT 'Verifying foreign key constraint:' as 'Status';

SELECT
    TABLE_NAME,
    CONSTRAINT_NAME,
    REFERENCED_TABLE_NAME,
    REFERENCED_COLUMN_NAME
FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE
WHERE TABLE_SCHEMA = 'petron_pos_db_secure'
  AND TABLE_NAME = 'sale_items'
  AND REFERENCED_TABLE_NAME = 'sales';

-- ============================================
-- STEP 6: Add Missing Columns (Safe Approach)
-- ============================================

SELECT 'Adding missing columns...' as 'Status';

-- Add is_locked column
ALTER TABLE sales ADD COLUMN is_locked TINYINT(1) DEFAULT 0 COMMENT 'Locked after finalization';

-- Add other columns
ALTER TABLE sales ADD COLUMN override_reason TEXT COMMENT 'Reason for Admin override';
ALTER TABLE sales ADD COLUMN override_by INT COMMENT 'Admin user ID who overrode';
ALTER TABLE sales ADD COLUMN override_at TIMESTAMP NULL COMMENT 'When Admin override occurred';
ALTER TABLE sales ADD COLUMN finalized_by INT COMMENT 'Who finalized the transaction';
ALTER TABLE sales ADD COLUMN finalized_at TIMESTAMP NULL COMMENT 'When finalized';

-- Add other columns to sale_items
ALTER TABLE sale_items ADD COLUMN product_name VARCHAR(255) COMMENT 'Product name for sale items';
ALTER TABLE sale_items ADD COLUMN category VARCHAR(255) COMMENT 'Category for sale items';

SELECT '✅ All columns added' as 'Status';

-- ============================================
-- STEP 7: Add Indexes
-- ============================================

SELECT 'Adding indexes...' as 'Status';

ALTER TABLE sales ADD INDEX IF NOT EXISTS idx_sales_status_locked (status, is_locked);
ALTER TABLE sales ADD INDEX IF NOT EXISTS idx_sales_date (sale_date);
ALTER TABLE sales ADD INDEX IF NOT EXISTS idx_sales_user (user_id);
ALTER TABLE sales ADD INDEX IF NOT EXISTS idx_override_by (override_by);

ALTER TABLE sale_items ADD INDEX IF NOT EXISTS idx_sale_items_sale_id (sale_id);

SELECT '✅ Indexes added' as 'Status';

-- ============================================
-- STEP 8: Update Existing Data
-- ============================================

SELECT 'Updating existing records...' as 'Status';

UPDATE sales SET is_locked = 1 WHERE status = 'Completed';

SELECT '✅ Existing records updated' as 'Status';

-- ============================================
-- FINAL VERIFICATION
-- ============================================

SELECT '═════════════════════════════════════════════' as 'FIX COMPLETE';
SELECT '✅ Foreign key constraint fixed' as 'Status';
SELECT '✅ All columns added' as 'Status';
SELECT '✅ Indexes created' as 'Status';
SELECT '✅ Data updated' as 'Status';

SELECT '📊 Final Sales Count:' as 'Count';
SELECT COUNT(*) as total_sales FROM sales;

SELECT '📊 Pending Transactions:' as 'Count';
SELECT COUNT(*) as pending_sales FROM sales WHERE status = 'Pending';

SELECT '📊 Completed & Locked:' as 'Count';
SELECT COUNT(*) as locked_sales FROM sales WHERE is_locked = 1;
