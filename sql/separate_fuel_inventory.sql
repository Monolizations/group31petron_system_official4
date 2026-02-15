-- ============================================================================
-- SEPARATE FUEL INVENTORY FROM MERCHANDISE INVENTORY
-- ============================================================================
-- This migration creates a separate fuel_inventory table since fuel is a
-- distinct domain with its own operational tracking (pumps, readings, etc.)
-- and should not be mixed with merchandise inventory.
--
-- Changes:
-- 1. Create new fuel_inventory table with fuel-specific fields
-- 2. Migrate fuel products from station_inventory to fuel_inventory
-- 3. Remove fuel from station_inventory (keep only merchandise)
-- ============================================================================

-- Step 1: Create the new fuel_inventory table
CREATE TABLE IF NOT EXISTS `fuel_inventory` (
  `id` int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `station_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `stock_level` decimal(12,2) DEFAULT 0.00 COMMENT 'Fuel in liters',
  `unit` varchar(50) DEFAULT 'liters' COMMENT 'Always liters for fuel',
  `reorder_level` int(11) DEFAULT 0,
  `capacity` decimal(12,2) DEFAULT 5000.00 COMMENT 'Tank capacity in liters',
  `status` enum('active','inactive') DEFAULT 'active',
  `last_updated` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  
  -- Foreign Keys
  CONSTRAINT `fk_fuel_inventory_station` FOREIGN KEY (`station_id`) 
    REFERENCES `stations` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_fuel_inventory_product` FOREIGN KEY (`product_id`) 
    REFERENCES `products` (`id`) ON DELETE CASCADE,
  
  -- Unique constraint: one fuel product per station
  UNIQUE KEY `unique_fuel_per_station` (`station_id`, `product_id`),
  
  -- Indexes for fast queries
  KEY `idx_station_id` (`station_id`),
  KEY `idx_product_id` (`product_id`),
  KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
COMMENT='Fuel inventory tracking - separate from merchandise inventory';

-- Step 2: Migrate existing fuel inventory data to new table
INSERT INTO fuel_inventory (station_id, product_id, stock_level, unit, reorder_level, capacity, status, last_updated)
SELECT 
  station_id, 
  product_id, 
  stock_level, 
  'liters' as unit,
  reorder_level, 
  capacity, 
  status, 
  last_updated
FROM station_inventory si
WHERE EXISTS (
  SELECT 1 FROM products p 
  WHERE p.id = si.product_id 
  AND p.type_id = 1  -- type_id = 1 is Fuel
)
ON DUPLICATE KEY UPDATE 
  stock_level = VALUES(stock_level),
  capacity = VALUES(capacity),
  status = VALUES(status),
  last_updated = NOW();

-- Step 3: Delete fuel entries from station_inventory (keep only merchandise)
DELETE FROM station_inventory 
WHERE product_id IN (
  SELECT id FROM products WHERE type_id = 1
);

-- Step 4: Verify migration
SELECT 'Fuel Inventory Records:' as info;
SELECT COUNT(*) as total_fuel_records FROM fuel_inventory;

SELECT 'Station Inventory Records (should be merchandise only):' as info;
SELECT COUNT(*) as total_station_inventory FROM station_inventory si 
WHERE EXISTS (SELECT 1 FROM products p WHERE p.id = si.product_id AND p.type_id = 2);

-- Step 5: Show sample data
SELECT 'Sample Fuel Inventory Data:' as info;
SELECT 
  fi.id, 
  fi.station_id, 
  p.name, 
  fi.stock_level, 
  fi.unit, 
  fi.capacity, 
  fi.status
FROM fuel_inventory fi
JOIN products p ON fi.product_id = p.id
LIMIT 5;

SELECT 'Sample Station Inventory Data (Merchandise Only):' as info;
SELECT 
  si.id, 
  si.station_id, 
  p.name, 
  p.type_id,
  si.stock_level, 
  si.unit
FROM station_inventory si
JOIN products p ON si.product_id = p.id
WHERE p.type_id = 2
LIMIT 5;
