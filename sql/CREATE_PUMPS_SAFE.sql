-- Disable foreign key checks, recreate pumps table
USE petron_pos_db_secure;

-- Disable foreign key checks temporarily
SET FOREIGN_KEY_CHECKS = 0;

-- Drop the table (now allowed)
DROP TABLE IF EXISTS fuel_pumps;

-- Re-enable foreign key checks
SET FOREIGN_KEY_CHECKS = 1;

-- Create fresh table
CREATE TABLE fuel_pumps (
    id INT AUTO_INCREMENT PRIMARY KEY,
    station_id INT NOT NULL,
    pump_number VARCHAR(50) NOT NULL,
    fuel_type_id INT,
    status VARCHAR(20) DEFAULT 'active'
);

-- Add pumps
INSERT INTO fuel_pumps (station_id, pump_number, fuel_type_id, status)
VALUES 
    (1, 'Pump 1', 1, 'active'),
    (1, 'Pump 2', 2, 'active'),
    (1, 'Pump 3', 3, 'active');

-- Verify
SELECT '✓ Pumps Created' as status;
SELECT fp.id, fp.pump_number, ft.name as fuel_type, fp.status 
FROM fuel_pumps fp
LEFT JOIN fuel_types ft ON fp.fuel_type_id = ft.id;
