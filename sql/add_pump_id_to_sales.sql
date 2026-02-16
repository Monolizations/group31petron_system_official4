-- Migration: Add pump_id to sales table for fuel POS tracking
-- Date: 2026-02-16
-- Description: Adds pump_id foreign key to track which pump was used for fuel sales

-- Add pump_id column to sales table
ALTER TABLE sales
ADD COLUMN pump_id INT NULL DEFAULT NULL AFTER station_id;

-- Add foreign key constraint for pump_id
ALTER TABLE sales
ADD CONSTRAINT fk_sales_pump_id 
FOREIGN KEY (pump_id) REFERENCES fuel_pumps(id) ON DELETE SET NULL;

-- Add index for faster queries
CREATE INDEX idx_sales_pump_id ON sales(pump_id);

-- Verify migration
SELECT COUNT(*) as total_rows FROM sales;
SELECT COLUMN_NAME, IS_NULLABLE, COLUMN_TYPE 
FROM INFORMATION_SCHEMA.COLUMNS 
WHERE TABLE_NAME = 'sales' AND COLUMN_NAME = 'pump_id';

-- Migration complete
-- All existing sales have pump_id = NULL (backward compatible)
-- New sales will require pump_id for fuel transactions
