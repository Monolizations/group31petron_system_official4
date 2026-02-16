-- Migration: Add nozzles table for pump nozzle management
-- Date: 2026-02-16
-- Description: Creates nozzles table to track individual nozzles per pump with fuel type and calibration

-- Create nozzles table
CREATE TABLE IF NOT EXISTS nozzles (
    id INT PRIMARY KEY AUTO_INCREMENT,
    pump_id INT NOT NULL,
    nozzle_number VARCHAR(20) NOT NULL,
    fuel_type_id INT NOT NULL,
    calibration_value DECIMAL(10,6) NOT NULL,
    status ENUM('active', 'inactive') DEFAULT 'active',
    notes TEXT,
    last_calibrated_date DATE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (pump_id) REFERENCES fuel_pumps(id) ON DELETE CASCADE,
    FOREIGN KEY (fuel_type_id) REFERENCES fuel_types(id),
    UNIQUE KEY unique_pump_nozzle (pump_id, nozzle_number)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Migrate existing pumps: create 1 default nozzle per pump
-- This sets nozzle_number to "1" and copies fuel_type_id from pump
-- Calibration value set to 0.000000 - REQUIRES MANUAL UPDATE
INSERT INTO nozzles (pump_id, nozzle_number, fuel_type_id, calibration_value, status, created_at, updated_at)
SELECT id, "1", fuel_type_id, 0.000000, 'active', NOW(), NOW()
FROM fuel_pumps
WHERE id NOT IN (SELECT DISTINCT pump_id FROM nozzles);

-- Add index for pump_id for faster nozzle queries
CREATE INDEX idx_pump_id ON nozzles(pump_id);

-- Add index for fuel_type_id for joins
CREATE INDEX idx_fuel_type_id ON nozzles(fuel_type_id);

-- Note: Keep fuel_pumps.fuel_type_id column for backward compatibility
-- Future migrations can remove it after confirming all code updated
