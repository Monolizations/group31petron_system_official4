-- Migration: Move calibration_value from nozzles to fuel_pumps
-- Purpose: Calibration is per pump (variance tracking factor), not per nozzle
-- The pump hardware is already internally calibrated; system just tracks variance

-- Step 1: Add calibration_value column to fuel_pumps table
ALTER TABLE fuel_pumps ADD COLUMN calibration_value DECIMAL(10,6) NULL AFTER status;

-- Step 2: Set default calibration values for existing pumps (can be updated later)
UPDATE fuel_pumps SET calibration_value = NULL;

-- Step 3: Drop calibration_value from nozzles table
ALTER TABLE nozzles DROP COLUMN calibration_value;

-- Step 4: Drop last_calibrated_date from nozzles table (no longer needed)
ALTER TABLE nozzles DROP COLUMN last_calibrated_date;

-- Step 5: Verify schema changes
-- SELECT * FROM fuel_pumps LIMIT 1;
-- SELECT * FROM nozzles LIMIT 1;
-- DESCRIBE fuel_pumps;
-- DESCRIBE nozzles;
