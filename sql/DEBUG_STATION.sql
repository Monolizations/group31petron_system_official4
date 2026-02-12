-- Check user's actual station
USE petron_pos_db_secure;

-- Find your user and station
SELECT 'Your User Info:' as info;
SELECT id, username, role, station_id FROM users WHERE username = 'staff' LIMIT 1;

-- Check what stations exist
SELECT 'Available Stations:' as info;
SELECT id, name FROM stations LIMIT 10;

-- Check pumps at station 1
SELECT 'Pumps at Station 1:' as info;
SELECT * FROM fuel_pumps WHERE station_id = 1;

-- Check ALL pumps
SELECT 'All Pumps:' as info;
SELECT * FROM fuel_pumps LIMIT 20;

-- Check fuel types
SELECT 'Fuel Types:' as info;
SELECT * FROM fuel_types LIMIT 20;
