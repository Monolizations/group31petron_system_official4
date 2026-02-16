-- Migration: Add pump_id and nozzle_id to sale_items table for fuel POS tracking
-- Date: 2026-02-16
-- Description: Adds pump_id and nozzle_id foreign keys to track which pump/nozzle was used for each fuel item

-- Add pump_id column to sale_items table
ALTER TABLE sale_items
ADD COLUMN pump_id INT NULL DEFAULT NULL AFTER product_id;

-- Add nozzle_id column to sale_items table
ALTER TABLE sale_items
ADD COLUMN nozzle_id INT NULL DEFAULT NULL AFTER pump_id;

-- Add foreign key constraint for pump_id
ALTER TABLE sale_items
ADD CONSTRAINT fk_sale_items_pump_id 
FOREIGN KEY (pump_id) REFERENCES fuel_pumps(id) ON DELETE SET NULL;

-- Add foreign key constraint for nozzle_id
ALTER TABLE sale_items
ADD CONSTRAINT fk_sale_items_nozzle_id 
FOREIGN KEY (nozzle_id) REFERENCES nozzles(id) ON DELETE SET NULL;

-- Add indexes for faster queries
CREATE INDEX idx_sale_items_pump_id ON sale_items(pump_id);
CREATE INDEX idx_sale_items_nozzle_id ON sale_items(nozzle_id);

-- Verify migration
SELECT COUNT(*) as total_items FROM sale_items;
SELECT COLUMN_NAME, IS_NULLABLE, COLUMN_TYPE 
FROM INFORMATION_SCHEMA.COLUMNS 
WHERE TABLE_NAME = 'sale_items' AND COLUMN_NAME IN ('pump_id', 'nozzle_id');

-- Migration complete
-- All existing sale_items have pump_id = NULL and nozzle_id = NULL (backward compatible)
-- New fuel transactions will require pump_id and nozzle_id
