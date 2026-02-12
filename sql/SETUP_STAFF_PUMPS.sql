-- Complete Setup for Staff Fuel Recording Page
-- Run this in phpMyAdmin SQL tab

USE petron_pos_db_secure;

-- 1. Add Fuel Types
INSERT IGNORE INTO fuel_types (id, name, created_at) 
VALUES 
    (1, 'Gasoline', NOW()), 
    (2, 'Diesel', NOW());

-- 2. Create fuel_pumps table if not exists
CREATE TABLE IF NOT EXISTS fuel_pumps (
    id INT AUTO_INCREMENT PRIMARY KEY,
    station_id INT NOT NULL,
    pump_number VARCHAR(50) NOT NULL UNIQUE,
    fuel_type_id INT,
    status VARCHAR(20) DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (station_id) REFERENCES stations(id) ON DELETE CASCADE,
    FOREIGN KEY (fuel_type_id) REFERENCES fuel_types(id) ON DELETE SET NULL
);

-- 3. Add Pumps (test data)
INSERT IGNORE INTO fuel_pumps (station_id, pump_number, fuel_type_id, status)
VALUES 
    (1, 'Pump 1', 1, 'active'),
    (1, 'Pump 2', 1, 'active'),
    (1, 'Pump 3', 2, 'active');

-- 4. Verify Setup
SELECT 'Fuel Types Loaded' as Status;
SELECT * FROM fuel_types ORDER BY id;

SELECT 'Pumps Loaded' as Status;
SELECT fp.id, fp.pump_number, ft.name as fuel_type, fp.status
FROM fuel_pumps fp
LEFT JOIN fuel_types ft ON fp.fuel_type_id = ft.id
ORDER BY fp.id;
