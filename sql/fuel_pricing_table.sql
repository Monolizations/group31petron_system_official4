-- Fuel Pricing Configuration Table
-- This table stores fuel prices per station
-- Separate from products table to allow dynamic fuel pricing changes

CREATE TABLE IF NOT EXISTS `fuel_pricing` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `station_id` int(11) NOT NULL,
  `fuel_type_id` int(11) NOT NULL COMMENT 'Reference to fuel_types table',
  `price_per_liter` decimal(10,2) NOT NULL DEFAULT 0.00 COMMENT 'Price per liter',
  `effective_date` datetime DEFAULT CURRENT_TIMESTAMP COMMENT 'When this price became effective',
  `created_by` int(11) DEFAULT NULL COMMENT 'User who set this price',
  `is_active` tinyint(1) DEFAULT 1 COMMENT 'Whether this price is currently active',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_station_fuel` (`station_id`, `fuel_type_id`, `is_active`),
  KEY `idx_effective_date` (`effective_date`),
  FOREIGN KEY (`fuel_type_id`) REFERENCES `fuel_types`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`station_id`) REFERENCES `stations`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`created_by`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='Fuel pricing configuration per station';

-- Insert default fuel pricing data for existing fuel types
-- This ensures fuel pricing is available from the start
INSERT IGNORE INTO `fuel_pricing` (`station_id`, `fuel_type_id`, `price_per_liter`, `created_by`, `is_active`)
SELECT 
    1 as station_id,
    ft.id as fuel_type_id,
    CASE ft.id
        WHEN 1 THEN 55.00  -- Gasoline
        WHEN 2 THEN 60.00  -- Diesel
        WHEN 3 THEN 45.00  -- LPG
        WHEN 4 THEN 60.00  -- Premium
        WHEN 5 THEN 58.00  -- Unleaded
    END as price_per_liter,
    NULL as created_by,
    1 as is_active
FROM `fuel_types` ft
WHERE NOT EXISTS (
    SELECT 1 FROM `fuel_pricing` fp 
    WHERE fp.fuel_type_id = ft.id AND fp.station_id = 1
);
