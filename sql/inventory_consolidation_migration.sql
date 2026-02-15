-- ============================================
-- INVENTORY CONSOLIDATION MIGRATION SCRIPT
-- Migrate from 'inventory' to 'station_inventory'
-- Date: 2026-02-15
-- ============================================

-- Step 1: Create backup of original inventory table
-- This preserves all data for audit trail and rollback purposes
CREATE TABLE IF NOT EXISTS inventory_legacy_backup AS 
SELECT * FROM inventory;

-- Step 2: Check for conflicts (products that exist in both tables)
-- This identifies data that would cause duplicate key issues
SELECT 'Checking for conflicts...' as step;
SELECT DISTINCT si.station_id, si.product_id, i.stock_level as old_stock, si.stock_level as new_stock
FROM station_inventory si
INNER JOIN inventory i ON i.station_id = si.station_id AND i.product_id = si.product_id;

-- Step 3: Migrate data - only insert records that don't already exist
-- This prevents data loss for unique records in inventory table
INSERT INTO station_inventory 
(station_id, product_id, stock_level, reorder_level, capacity, unit, status, last_updated)
SELECT i.station_id, i.product_id, i.stock_level, i.reorder_level, i.capacity, i.unit, i.status, NOW()
FROM inventory i
WHERE NOT EXISTS (
  SELECT 1 FROM station_inventory si
  WHERE si.station_id = i.station_id
  AND si.product_id = i.product_id
);

-- Step 4: Verify migration success
SELECT 'Migration Results:' as status;
SELECT 'Before Migration' as stage, COUNT(*) as inventory_count FROM inventory_legacy_backup
UNION ALL
SELECT 'After Migration' as stage, COUNT(*) as inventory_count FROM station_inventory;

-- Step 5: Data integrity verification
SELECT 'Integrity Checks:' as status;

-- Check for orphaned products
SELECT 'Orphaned Products:' as check_type, COUNT(*) as count
FROM station_inventory si
LEFT JOIN products p ON si.product_id = p.id
WHERE p.id IS NULL;

-- Check for orphaned stations
SELECT 'Orphaned Stations:' as check_type, COUNT(*) as count
FROM station_inventory si
LEFT JOIN stations s ON si.station_id = s.id
WHERE s.id IS NULL;

-- Final confirmation
SELECT 'Migration Complete!' as status, NOW() as timestamp;
