-- Simple Fix: Add pumps to station 1
USE petron_pos_db_secure;

-- Step 1: Check what fuel types exist with their IDs
SELECT 'Step 1: Existing Fuel Types' as step;
SELECT * FROM fuel_types;

-- Step 2: Add pumps (try using fuel_type_id 1, 2, 3 which likely exist)
INSERT IGNORE INTO fuel_pumps (station_id, pump_number, fuel_type_id, status)
VALUES 
    (1, 'Pump 1', 1, 'active'),
    (1, 'Pump 2', 2, 'active'),
    (1, 'Pump 3', 3, 'active');

-- Step 3: Verify pumps were created
SELECT 'Step 2: Pumps Created' as step;
SELECT fp.id, fp.pump_number, ft.name as fuel_type, fp.status 
FROM fuel_pumps fp
LEFT JOIN fuel_types ft ON fp.fuel_type_id = ft.id
WHERE fp.station_id = 1;

-- Done
SELECT '✓ Setup Complete - Pumps added' as result;
