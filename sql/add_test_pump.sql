-- Add test pump and fuel type for staff to use
USE petron_pos_db_secure;

-- Add fuel type if not exists
INSERT IGNORE INTO fuel_types (id, name, created_at) 
VALUES (1, 'Gasoline', NOW()),
       (2, 'Diesel', NOW());

-- Add a pump (if fuel_pumps table exists)
-- Check if table exists first
CREATE TABLE IF NOT EXISTS fuel_pumps (
    id INT AUTO_INCREMENT PRIMARY KEY,
    station_id INT NOT NULL,
    pump_number VARCHAR(50) NOT NULL,
    fuel_type_id INT,
    status VARCHAR(20) DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_pump (station_id, pump_number)
);

-- Insert test pumps
INSERT IGNORE INTO fuel_pumps (station_id, pump_number, fuel_type_id, status)
VALUES 
    (1, 'Pump 1', 1, 'active'),
    (1, 'Pump 2', 1, 'active'),
    (1, 'Pump 3', 2, 'active');

-- Verify
SELECT * FROM fuel_pumps;
SELECT * FROM fuel_types;
