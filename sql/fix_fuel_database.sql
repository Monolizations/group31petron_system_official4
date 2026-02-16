-- ==================================================
-- FUEL MANAGEMENT SYSTEM - DATABASE FIXES
-- ==================================================
-- This script fixes critical database issues preventing
-- the fuel management system from working properly
-- ==================================================

-- 1. CREATE MISSING fuel_variance_reports TABLE
-- ------------------------------------------------
CREATE TABLE IF NOT EXISTS `fuel_variance_reports` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `station_id` int(11) NOT NULL,
  `report_date` date NOT NULL,
  `fuel_type` varchar(50) NOT NULL,
  `expected_stock` decimal(10,2) NOT NULL,
  `actual_stock` decimal(10,2) NOT NULL,
  `variance_liters` decimal(10,2) NOT NULL,
  `variance_percent` decimal(5,2) NOT NULL,
  `reason` text DEFAULT NULL,
  `status` enum('Open','Under Investigation','Resolved') DEFAULT 'Open',
  `investigated_by` int(11) DEFAULT NULL,
  `resolution_notes` text DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_station_date` (`station_id`, `report_date`),
  KEY `idx_fuel_type` (`fuel_type`),
  KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- 2. CREATE fuel_stations VIEW/TABLE for compatibility
-- ---------------------------------------------------
-- The code expects fuel_stations but database has fuel_pumps
-- Create a view to bridge this gap while maintaining existing data

DROP VIEW IF EXISTS `fuel_stations`;

CREATE VIEW `fuel_stations` AS 
SELECT 
    fp.id,
    fp.station_id,
    fp.pump_number,
    ft.name as fuel_type,
    fp.capacity,
    fp.status,
    fp.created_at
FROM fuel_pumps fp
LEFT JOIN fuel_types ft ON fp.fuel_type_id = ft.id;

-- 3. ADD MISSING COLUMNS TO EXISTING TABLES
-- ------------------------------------------

-- Add missing columns to fuel_deliveries if they don't exist
ALTER TABLE `fuel_deliveries` 
ADD COLUMN IF NOT EXISTS `verified_by` int(11) DEFAULT NULL AFTER `received_by`,
ADD COLUMN IF NOT EXISTS `verified_at` datetime DEFAULT NULL AFTER `verified_by`;

-- Add missing columns to fuel_adjustments if they don't exist  
ALTER TABLE `fuel_adjustments`
ADD COLUMN IF NOT EXISTS `approved_by` int(11) DEFAULT NULL AFTER `status`,
ADD COLUMN IF NOT EXISTS `approved_at` datetime DEFAULT NULL AFTER `approved_by`;

-- Add fuel_station_id column to fuel_daily_readings for compatibility
ALTER TABLE `fuel_daily_readings`
ADD COLUMN IF NOT EXISTS `fuel_station_id` int(11) DEFAULT NULL AFTER `pump_id`;

-- Update fuel_station_id to match pump_id for existing records
UPDATE `fuel_daily_readings` 
SET `fuel_station_id` = `pump_id` 
WHERE `fuel_station_id` IS NULL;

-- 4. UPDATE fuel_reconciliation TABLE STRUCTURE
-- ----------------------------------------------
-- Add missing columns that the code expects

ALTER TABLE `fuel_reconciliation`
ADD COLUMN IF NOT EXISTS `opening_stock` decimal(10,2) DEFAULT 0.00 AFTER `reconciliation_date`,
ADD COLUMN IF NOT EXISTS `deliveries` decimal(10,2) DEFAULT 0.00 AFTER `opening_stock`,
ADD COLUMN IF NOT EXISTS `sales` decimal(10,2) DEFAULT 0.00 AFTER `deliveries`,
ADD COLUMN IF NOT EXISTS `adjustments` decimal(10,2) DEFAULT 0.00 AFTER `sales`,
ADD COLUMN IF NOT EXISTS `closing_stock` decimal(10,2) DEFAULT 0.00 AFTER `adjustments`,
ADD COLUMN IF NOT EXISTS `fuel_type` varchar(50) DEFAULT NULL AFTER `fuel_type_id`;

-- Update fuel_type column with values from fuel_types table
UPDATE fuel_reconciliation fr
LEFT JOIN fuel_types ft ON fr.fuel_type_id = ft.id
SET fr.fuel_type = ft.name
WHERE fr.fuel_type IS NULL;

-- 5. CREATE INDEXES for better performance
-- ----------------------------------------

-- Indexes for fuel_daily_readings
CREATE INDEX IF NOT EXISTS `idx_fuel_daily_station_date` ON `fuel_daily_readings` (`station_id`, `reading_date`);
CREATE INDEX IF NOT EXISTS `idx_fuel_daily_status` ON `fuel_daily_readings` (`status`);
CREATE INDEX IF NOT EXISTS `idx_fuel_daily_fuel_station` ON `fuel_daily_readings` (`fuel_station_id`);

-- Indexes for fuel_deliveries  
CREATE INDEX IF NOT EXISTS `idx_fuel_deliveries_station_date` ON `fuel_deliveries` (`station_id`, `delivery_date`);
CREATE INDEX IF NOT EXISTS `idx_fuel_deliveries_status` ON `fuel_deliveries` (`status`);

-- Indexes for fuel_adjustments
CREATE INDEX IF NOT EXISTS `idx_fuel_adjustments_station_date` ON `fuel_adjustments` (`station_id`, `adjustment_date`);
CREATE INDEX IF NOT EXISTS `idx_fuel_adjustments_status` ON `fuel_adjustments` (`status`);

-- Indexes for fuel_reconciliation
CREATE INDEX IF NOT EXISTS `idx_fuel_recon_station_date` ON `fuel_reconciliation` (`station_id`, `reconciliation_date`);
CREATE INDEX IF NOT EXISTS `idx_fuel_recon_fuel_type` ON `fuel_reconciliation` (`fuel_type`);

-- 6. CREATE STORED PROCEDURES FOR COMMON OPERATIONS  
-- --------------------------------------------------

DELIMITER $$

-- Procedure to get station fuel inventory summary
DROP PROCEDURE IF EXISTS `GetStationFuelSummary`$$
CREATE PROCEDURE `GetStationFuelSummary`(IN p_station_id INT)
BEGIN
    SELECT 
        ft.name as fuel_type,
        COUNT(fp.id) as pump_count,
        SUM(fp.capacity) as total_capacity,
        COUNT(CASE WHEN fp.status = 'Active' THEN 1 END) as active_pumps
    FROM fuel_types ft
    LEFT JOIN fuel_pumps fp ON ft.id = fp.fuel_type_id AND fp.station_id = p_station_id
    GROUP BY ft.id, ft.name
    ORDER BY ft.name;
END$$

-- Procedure to calculate daily fuel variance
DROP PROCEDURE IF EXISTS `CalculateDailyVariance`$$
CREATE PROCEDURE `CalculateDailyVariance`(
    IN p_station_id INT,
    IN p_date DATE,
    IN p_fuel_type VARCHAR(50)
)
BEGIN
    DECLARE v_opening_stock DECIMAL(10,2) DEFAULT 0;
    DECLARE v_deliveries DECIMAL(10,2) DEFAULT 0;
    DECLARE v_sales DECIMAL(10,2) DEFAULT 0;
    DECLARE v_adjustments DECIMAL(10,2) DEFAULT 0;
    DECLARE v_expected_stock DECIMAL(10,2) DEFAULT 0;
    
    -- Get opening stock from previous day
    SELECT COALESCE(closing_stock, 0) INTO v_opening_stock
    FROM fuel_reconciliation 
    WHERE station_id = p_station_id 
        AND fuel_type = p_fuel_type 
        AND reconciliation_date = DATE_SUB(p_date, INTERVAL 1 DAY)
    ORDER BY id DESC LIMIT 1;
    
    -- Get deliveries for the day
    SELECT COALESCE(SUM(delivery_liters), 0) INTO v_deliveries
    FROM fuel_deliveries 
    WHERE station_id = p_station_id 
        AND fuel_type = p_fuel_type 
        AND delivery_date = p_date 
        AND status = 'Verified';
    
    -- Get sales for the day
    SELECT COALESCE(SUM(dr.sales_liters), 0) INTO v_sales
    FROM fuel_daily_readings dr
    JOIN fuel_stations fs ON dr.fuel_station_id = fs.id
    WHERE dr.station_id = p_station_id 
        AND fs.fuel_type = p_fuel_type
        AND dr.reading_date = p_date 
        AND dr.status = 'Verified';
    
    -- Get adjustments for the day
    SELECT COALESCE(SUM(CASE WHEN adjustment_type = 'Loss' THEN -liters ELSE liters END), 0) INTO v_adjustments
    FROM fuel_adjustments 
    WHERE station_id = p_station_id 
        AND fuel_type = p_fuel_type 
        AND adjustment_date = p_date 
        AND status = 'Approved';
    
    -- Calculate expected stock
    SET v_expected_stock = v_opening_stock + v_deliveries - v_sales + v_adjustments;
    
    SELECT 
        v_opening_stock as opening_stock,
        v_deliveries as deliveries,
        v_sales as sales,
        v_adjustments as adjustments,
        v_expected_stock as expected_stock;
END$$

DELIMITER ;

-- 7. INSERT SAMPLE DATA FOR TESTING
-- ----------------------------------

-- Insert some sample variance reports for testing
INSERT IGNORE INTO `fuel_variance_reports` 
(`station_id`, `report_date`, `fuel_type`, `expected_stock`, `actual_stock`, `variance_liters`, `variance_percent`, `reason`, `status`) 
VALUES 
(226, '2026-02-10', 'Gasoline', 1000.00, 980.00, -20.00, -2.00, 'Minor evaporation loss', 'Open'),
(226, '2026-02-09', 'Diesel', 800.00, 805.00, 5.00, 0.63, 'Measurement variance', 'Under Investigation');

-- ==================================================
-- VERIFICATION QUERIES
-- ==================================================
-- Run these to verify the fixes worked correctly

-- 1. Check that fuel_stations view works
-- SELECT * FROM fuel_stations LIMIT 5;

-- 2. Check variance reports table
-- SELECT * FROM fuel_variance_reports;

-- 3. Check updated fuel_daily_readings structure
-- DESCRIBE fuel_daily_readings;

-- 4. Check updated fuel_reconciliation structure  
-- DESCRIBE fuel_reconciliation;

-- ==================================================
-- ROLLBACK INSTRUCTIONS (IF NEEDED)
-- ==================================================
-- If you need to undo these changes:
-- DROP VIEW IF EXISTS fuel_stations;
-- DROP TABLE IF EXISTS fuel_variance_reports; 
-- ALTER TABLE fuel_daily_readings DROP COLUMN IF EXISTS fuel_station_id;
-- ALTER TABLE fuel_deliveries DROP COLUMN IF EXISTS verified_by, DROP COLUMN IF EXISTS verified_at;
-- ALTER TABLE fuel_adjustments DROP COLUMN IF EXISTS approved_by, DROP COLUMN IF EXISTS approved_at;
-- DROP PROCEDURE IF EXISTS GetStationFuelSummary;
-- DROP PROCEDURE IF EXISTS CalculateDailyVariance;
-- ==================================================

-- END OF SCRIPT