-- ============================================================================
-- PHASE 1.1: Add supplier_id Foreign Key to fuel_deliveries
-- ============================================================================
-- Purpose: Normalize supplier data by linking to suppliers table instead of text field
-- Allows for referential integrity, better reporting, and supplier management
--
-- Changes:
-- 1. Add supplier_id column to fuel_deliveries
-- 2. Migrate existing suppliers to suppliers table entries (or use default Petron)
-- 3. Add foreign key constraint
-- 4. Add index for performance
-- ============================================================================

-- Step 1: Add supplier_id column (nullable first for migration)
ALTER TABLE `fuel_deliveries` 
ADD COLUMN `supplier_id` int(11) NULL AFTER `station_id`,
ADD CONSTRAINT `fk_fuel_deliveries_supplier` 
  FOREIGN KEY (`supplier_id`) 
  REFERENCES `suppliers` (`id`) 
  ON DELETE RESTRICT 
  ON UPDATE CASCADE;

-- Step 2: Add index for query performance
ALTER TABLE `fuel_deliveries` 
ADD KEY `idx_supplier_id` (`supplier_id`);

-- Step 3: Log the migration completion
SELECT 'Migration complete: supplier_id added to fuel_deliveries' as status;

-- Step 4: Show structure
DESCRIBE fuel_deliveries;
