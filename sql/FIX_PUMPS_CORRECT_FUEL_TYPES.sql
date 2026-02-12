-- Fix: Use ACTUAL fuel types from system
USE petron_pos_db_secure;

-- Check what fuel types actually exist
SELECT 'Current Fuel Types:' as info;
SELECT id, name FROM fuel_types;

-- Check what station you belong to
SELECT 'Your Station (first staff user):' as info;
SELECT u.id, u.username, u.station_id, s.name as station_name 
FROM users u
LEFT JOIN stations s ON u.station_id = s.id
WHERE u.role = 'staff' LIMIT 1;

-- Add pumps using EXISTING fuel types
-- First, get the fuel type IDs that exist
SELECT 'Getting existing fuel type IDs...' as note;

-- Try to add pumps for all stations with existing fuel types
INSERT IGNORE INTO fuel_pumps (station_id, pump_number, fuel_type_id, status)
SELECT 1 as station_id, 'Pump 1' as pump_number, ft.id as fuel_type_id, 'active' as status
FROM fuel_types ft
WHERE ft.name IN ('Diesel Max', 'XCS Plus', 'Kerosene')
LIMIT 1;

INSERT IGNORE INTO fuel_pumps (station_id, pump_number, fuel_type_id, status)
SELECT 1 as station_id, 'Pump 2' as pump_number, ft.id as fuel_type_id, 'active' as status
FROM fuel_types ft
WHERE ft.name IN ('Diesel Max', 'XCS Plus', 'Kerosene')
LIMIT 1 OFFSET 1;

INSERT IGNORE INTO fuel_pumps (station_id, pump_number, fuel_type_id, status)
SELECT 1 as station_id, 'Pump 3' as pump_number, ft.id as fuel_type_id, 'active' as status
FROM fuel_types ft
WHERE ft.name IN ('Diesel Max', 'XCS Plus', 'Kerosene')
LIMIT 1 OFFSET 2;

SELECT 'Pumps after insert:' as info;
SELECT fp.*, ft.name as fuel_type_name FROM fuel_pumps fp
LEFT JOIN fuel_types ft ON fp.fuel_type_id = ft.id
WHERE fp.station_id = 1;
