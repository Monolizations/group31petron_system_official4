-- Fixed SQL - No created_at column
USE petron_pos_db_secure;

-- 1. Add Fuel Types (without created_at)
INSERT IGNORE INTO fuel_types (id, name) 
VALUES (1, 'Gasoline'), (2, 'Diesel');

-- 2. Create fuel_pumps table if not exists
CREATE TABLE IF NOT EXISTS fuel_pumps (
    id INT AUTO_INCREMENT PRIMARY KEY,
    station_id INT NOT NULL,
    pump_number VARCHAR(50) NOT NULL,
    fuel_type_id INT,
    status VARCHAR(20) DEFAULT 'active'
);

-- 3. Add Pumps
INSERT IGNORE INTO fuel_pumps (station_id, pump_number, fuel_type_id, status)
VALUES 
    (1, 'Pump 1', 1, 'active'),
    (1, 'Pump 2', 1, 'active'),
    (1, 'Pump 3', 2, 'active');

-- 4. Verify
SELECT 'Fuel Types:' as section;
SELECT * FROM fuel_types;

SELECT 'Pumps:' as section;
SELECT * FROM fuel_pumps;

SELECT 'Count:' as section;
SELECT COUNT(*) as pumps_created FROM fuel_pumps WHERE station_id = 1;
