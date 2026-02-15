-- ============================================================================
-- CLEAN UP TEST DATA AND ZERO-PRICING MERCHANDISE PRODUCTS
-- ============================================================================
-- This script removes test data while respecting foreign key constraints
-- ============================================================================

-- Disable foreign key checks temporarily
SET FOREIGN_KEY_CHECKS = 0;

-- Step 1: Delete inventory-related records for test products
DELETE FROM inventory_transactions 
WHERE product_id IN (
  SELECT id FROM products WHERE name IN ('aboabo', 'buns1', 'dassfasfa', 'wd12332')
  AND type_id = 2
);

DELETE FROM inventory_logs 
WHERE product_id IN (
  SELECT id FROM products WHERE name IN ('aboabo', 'buns1', 'dassfasfa', 'wd12332')
  AND type_id = 2
);

DELETE FROM station_inventory 
WHERE product_id IN (
  SELECT id FROM products WHERE name IN ('aboabo', 'buns1', 'dassfasfa', 'wd12332')
  AND type_id = 2
);

-- Step 2: Delete the test products
DELETE FROM products 
WHERE name IN ('aboabo', 'buns1', 'dassfasfa', 'wd12332')
AND type_id = 2;

-- Step 3: Delete zero-pricing products (excluding those with active inventory)
-- Get list of product IDs that have zero pricing and no active inventory
DELETE FROM inventory_logs 
WHERE product_id IN (
  SELECT p.id FROM products p
  LEFT JOIN station_inventory si ON p.id = si.product_id
  WHERE p.type_id = 2 
  AND p.price = 0.00 
  AND p.cost = 0.00
  AND si.id IS NULL
);

DELETE FROM inventory_transactions 
WHERE product_id IN (
  SELECT p.id FROM products p
  LEFT JOIN station_inventory si ON p.id = si.product_id
  WHERE p.type_id = 2 
  AND p.price = 0.00 
  AND p.cost = 0.00
  AND si.id IS NULL
);

DELETE FROM station_inventory 
WHERE product_id IN (
  SELECT p.id FROM products p
  LEFT JOIN station_inventory si ON p.id = si.product_id
  WHERE p.type_id = 2 
  AND p.price = 0.00 
  AND p.cost = 0.00
  AND si.id IS NULL
);

DELETE FROM products 
WHERE type_id = 2 
AND price = 0.00 
AND cost = 0.00
AND id NOT IN (
  SELECT DISTINCT product_id FROM station_inventory
);

-- Re-enable foreign key checks
SET FOREIGN_KEY_CHECKS = 1;

-- Verify cleanup
SELECT 'Cleanup Complete' as status;
SELECT COUNT(*) as remaining_merchandise_products 
FROM products 
WHERE type_id = 2;

SELECT 'Sample of Remaining Merchandise Products:' as info;
SELECT id, sku, name, price, cost 
FROM products 
WHERE type_id = 2 
ORDER BY id 
LIMIT 10;
