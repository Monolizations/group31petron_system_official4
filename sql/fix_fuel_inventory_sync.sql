-- ===========================================================
-- FUEL INVENTORY SYNC FIX - Migration Script
-- ===========================================================
-- This script fixes the disconnected fuel type systems:
--   1. fuel_types table (used by pump readings)
--   2. products table (used by inventory)
--
-- After this migration both systems use the same Petron brand
-- names and are properly linked through products.name matching
-- fuel_types.name.
--
-- MUST BE RUN BEFORE deploying the updated PHP code.
-- Safe to run multiple times (uses IF NOT EXISTS / UPSERT).
-- ===========================================================

-- ---------------------------------------------------------
-- 1. RENAME fuel_types TO PETRON BRAND NAMES
-- ---------------------------------------------------------
-- Mapping:
--   Gasoline  (id=1) -> XCS Plus
--   Diesel    (id=2) -> Diesel Max
--   LPG       (id=3) -> Kerosene
--   Premium   (id=4) -> XCS Advance
--   Unleaded  (id=5) -> Turbo Diesel

UPDATE `fuel_types` SET `name` = 'XCS Plus',      `description` = 'Petron XCS Plus gasoline'        WHERE `id` = 1;
UPDATE `fuel_types` SET `name` = 'Diesel Max',     `description` = 'Petron Diesel Max'               WHERE `id` = 2;
UPDATE `fuel_types` SET `name` = 'Kerosene',       `description` = 'Petron Kerosene'                 WHERE `id` = 3;
UPDATE `fuel_types` SET `name` = 'XCS Advance',    `description` = 'Petron XCS Advance gasoline'     WHERE `id` = 4;
UPDATE `fuel_types` SET `name` = 'Turbo Diesel',   `description` = 'Petron Turbo Diesel'             WHERE `id` = 5;

-- ---------------------------------------------------------
-- 2. RENAME EXISTING FUEL PRODUCT & ADD MISSING ONES
-- ---------------------------------------------------------
-- products table currently has only id=1 "Gasoline Premium" with type_id=1 (fuel).
-- Rename it and add the other 4 fuel products.

-- Rename existing product to match new fuel_types name
UPDATE `products` 
SET `name` = 'XCS Plus', 
    `sku` = 'FUEL-XCS-PLUS', 
    `description` = 'Petron XCS Plus gasoline'
WHERE `id` = 1 AND `type_id` = 1;

-- Insert missing fuel products (type_id=1 = fuel)
-- Use INSERT IGNORE to be safe on re-runs (name + type_id has no unique constraint,
-- so we check existence first with a conditional insert pattern)

INSERT INTO `products` (`sku`, `name`, `description`, `type_id`, `category_id`, `cost`, `price`)
SELECT 'FUEL-DIESEL-MAX', 'Diesel Max', 'Petron Diesel Max', 1, 1, 40.00, 50.00
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM `products` WHERE `name` = 'Diesel Max' AND `type_id` = 1);

INSERT INTO `products` (`sku`, `name`, `description`, `type_id`, `category_id`, `cost`, `price`)
SELECT 'FUEL-KEROSENE', 'Kerosene', 'Petron Kerosene', 1, 1, 35.00, 45.00
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM `products` WHERE `name` = 'Kerosene' AND `type_id` = 1);

INSERT INTO `products` (`sku`, `name`, `description`, `type_id`, `category_id`, `cost`, `price`)
SELECT 'FUEL-XCS-ADVANCE', 'XCS Advance', 'Petron XCS Advance gasoline', 1, 1, 50.00, 60.00
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM `products` WHERE `name` = 'XCS Advance' AND `type_id` = 1);

INSERT INTO `products` (`sku`, `name`, `description`, `type_id`, `category_id`, `cost`, `price`)
SELECT 'FUEL-TURBO-DIESEL', 'Turbo Diesel', 'Petron Turbo Diesel', 1, 1, 42.00, 52.00
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM `products` WHERE `name` = 'Turbo Diesel' AND `type_id` = 1);

-- ---------------------------------------------------------
-- 3. CREATE station_inventory ROWS FOR ALL STATIONS x FUELS
-- ---------------------------------------------------------
-- For every station that already has at least one inventory row,
-- ensure it has a row for each fuel product.

INSERT INTO `station_inventory` (`station_id`, `product_id`, `stock_level`, `unit`, `status`)
SELECT s.station_id, p.id, 0.00, 'liters', 'active'
FROM (SELECT DISTINCT station_id FROM station_inventory) s
CROSS JOIN (SELECT id FROM products WHERE type_id = (SELECT id FROM product_types WHERE name = 'fuel')) p
WHERE NOT EXISTS (
    SELECT 1 FROM station_inventory si 
    WHERE si.station_id = s.station_id AND si.product_id = p.id
);

-- ---------------------------------------------------------
-- 4. ADD closing_stock COLUMNS TO station_inventory
-- ---------------------------------------------------------
-- recordDailyClosingStock() references these columns but they
-- don't exist in the base schema.

ALTER TABLE `station_inventory`
ADD COLUMN IF NOT EXISTS `closing_stock` decimal(12,2) DEFAULT NULL AFTER `stock_level`,
ADD COLUMN IF NOT EXISTS `closing_date` date DEFAULT NULL AFTER `closing_stock`,
ADD COLUMN IF NOT EXISTS `closing_shift` varchar(20) DEFAULT NULL AFTER `closing_date`;

-- ---------------------------------------------------------
-- 4b. FIX inventory_transactions.transaction_type ENUM
-- ---------------------------------------------------------
-- The column is ENUM('addition','deduction','adjustment','transfer')
-- but recordStockMovement() and POS code use descriptive types:
-- pump_reading, delivery_finalized, adjustment_approved, pos_sale,
-- reconciliation_sync, manual_adjustment, job_order_fuel.
-- Change to VARCHAR to accept all transaction type strings.

ALTER TABLE `inventory_transactions`
MODIFY COLUMN `transaction_type` varchar(50) NOT NULL DEFAULT 'deduction';

-- Also change reference_id from INT to VARCHAR to support
-- string-based sale IDs (e.g. 'SALE-65a1b2c3d4e5f')
ALTER TABLE `inventory_transactions`
MODIFY COLUMN `reference_id` varchar(50) DEFAULT NULL;

-- ---------------------------------------------------------
-- 5. ADD fuel_type_id COLUMN TO fuel_adjustments
-- ---------------------------------------------------------
-- The table only has fuel_type (varchar). The approve_adjustment
-- action needs fuel_type_id for recordStockMovement(). We add
-- it as a convenience column and backfill from fuel_types.

ALTER TABLE `fuel_adjustments`
ADD COLUMN IF NOT EXISTS `fuel_type_id` int(11) DEFAULT NULL AFTER `fuel_type`,
ADD COLUMN IF NOT EXISTS `approved_by` int(11) DEFAULT NULL AFTER `status`,
ADD COLUMN IF NOT EXISTS `approved_at` datetime DEFAULT NULL AFTER `approved_by`;

-- Backfill fuel_type_id from matching fuel_types.name
-- NOTE: Run this AFTER the fuel_types rename above. Existing
-- adjustment rows may have old names (Gasoline, Diesel, etc.)
-- that no longer match. For old data we attempt a best-effort
-- mapping via the known rename mapping.
UPDATE `fuel_adjustments` fa
JOIN `fuel_types` ft ON ft.name = fa.fuel_type
SET fa.fuel_type_id = ft.id
WHERE fa.fuel_type_id IS NULL;

-- Map old names that didn't match (pre-rename data)
UPDATE `fuel_adjustments` SET `fuel_type_id` = 1, `fuel_type` = 'XCS Plus'      WHERE `fuel_type` = 'Gasoline'  AND `fuel_type_id` IS NULL;
UPDATE `fuel_adjustments` SET `fuel_type_id` = 2, `fuel_type` = 'Diesel Max'    WHERE `fuel_type` = 'Diesel'    AND `fuel_type_id` IS NULL;
UPDATE `fuel_adjustments` SET `fuel_type_id` = 3, `fuel_type` = 'Kerosene'      WHERE `fuel_type` = 'LPG'       AND `fuel_type_id` IS NULL;
UPDATE `fuel_adjustments` SET `fuel_type_id` = 4, `fuel_type` = 'XCS Advance'   WHERE `fuel_type` = 'Premium'   AND `fuel_type_id` IS NULL;
UPDATE `fuel_adjustments` SET `fuel_type_id` = 5, `fuel_type` = 'Turbo Diesel'  WHERE `fuel_type` = 'Unleaded'  AND `fuel_type_id` IS NULL;

-- ---------------------------------------------------------
-- 6. UPDATE fuel_deliveries TO NEW NAMES
-- ---------------------------------------------------------
UPDATE `fuel_deliveries` SET `fuel_type` = 'XCS Plus'     WHERE `fuel_type` = 'Gasoline';
UPDATE `fuel_deliveries` SET `fuel_type` = 'Diesel Max'   WHERE `fuel_type` = 'Diesel';
UPDATE `fuel_deliveries` SET `fuel_type` = 'Kerosene'     WHERE `fuel_type` = 'LPG';
UPDATE `fuel_deliveries` SET `fuel_type` = 'XCS Advance'  WHERE `fuel_type` = 'Premium';
UPDATE `fuel_deliveries` SET `fuel_type` = 'Turbo Diesel' WHERE `fuel_type` = 'Unleaded';

-- ---------------------------------------------------------
-- 7. UPDATE fuel_variance_reports TO NEW NAMES
-- ---------------------------------------------------------
UPDATE `fuel_variance_reports` SET `fuel_type` = 'XCS Plus'     WHERE `fuel_type` = 'Gasoline';
UPDATE `fuel_variance_reports` SET `fuel_type` = 'Diesel Max'   WHERE `fuel_type` = 'Diesel';
UPDATE `fuel_variance_reports` SET `fuel_type` = 'Kerosene'     WHERE `fuel_type` = 'LPG';
UPDATE `fuel_variance_reports` SET `fuel_type` = 'XCS Advance'  WHERE `fuel_type` = 'Premium';
UPDATE `fuel_variance_reports` SET `fuel_type` = 'Turbo Diesel' WHERE `fuel_type` = 'Unleaded';

-- ---------------------------------------------------------
-- 8. UPDATE fuel_reconciliation TO NEW NAMES
-- ---------------------------------------------------------
UPDATE `fuel_reconciliation` SET `fuel_type` = 'XCS Plus'     WHERE `fuel_type` = 'Gasoline';
UPDATE `fuel_reconciliation` SET `fuel_type` = 'Diesel Max'   WHERE `fuel_type` = 'Diesel';
UPDATE `fuel_reconciliation` SET `fuel_type` = 'Kerosene'     WHERE `fuel_type` = 'LPG';
UPDATE `fuel_reconciliation` SET `fuel_type` = 'XCS Advance'  WHERE `fuel_type` = 'Premium';
UPDATE `fuel_reconciliation` SET `fuel_type` = 'Turbo Diesel' WHERE `fuel_type` = 'Unleaded';

-- ---------------------------------------------------------
-- 9. UPDATE fuel_stations VIEW (depends on fuel_types rename)
-- ---------------------------------------------------------
DROP VIEW IF EXISTS `fuel_stations`;
CREATE VIEW `fuel_stations` AS 
SELECT 
    fp.id,
    fp.station_id,
    fp.pump_number,
    ft.name as fuel_type,
    fp.capacity,
    fp.status,
    fp.created_at
FROM fuel_pumps fp
LEFT JOIN fuel_types ft ON fp.fuel_type_id = ft.id;

-- ===========================================================
-- VERIFICATION QUERIES (uncomment to check)
-- ===========================================================
-- SELECT * FROM fuel_types;
-- SELECT id, sku, name, type_id FROM products WHERE type_id = 1;
-- SELECT si.id, si.station_id, p.name, si.stock_level FROM station_inventory si JOIN products p ON si.product_id = p.id WHERE p.type_id = 1;
-- SELECT * FROM fuel_stations LIMIT 10;
-- DESCRIBE station_inventory;
-- SELECT id, fuel_type, fuel_type_id FROM fuel_adjustments LIMIT 10;
-- ===========================================================
