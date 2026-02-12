-- Quick Test Data for Fuel Reconciliation Flow
-- This creates a Manager-Approved record ready for Admin finalization

USE petron_pos_db_secure;

-- First, ensure we have a fuel type
INSERT IGNORE INTO fuel_types (id, name, created_at) 
VALUES (1, 'Gasoline', NOW());

-- Create a reconciliation record with status='Verified' (Manager approved)
INSERT INTO fuel_reconciliation (
    station_id, 
    fuel_type_id, 
    pump_id,
    reconciliation_date, 
    previous_reading, 
    present_reading, 
    calibration,
    price_per_liter,
    sales_liters,
    sales_amount,
    status,
    created_at
) VALUES (
    1,                      -- station_id (your station)
    1,                      -- fuel_type_id (Gasoline)
    1,                      -- pump_id
    NOW(),                  -- reconciliation_date (today)
    4500.00,                -- previous_reading (starting meter)
    5000.00,                -- present_reading (ending meter)
    -10.00,                 -- calibration (small adjustment)
    65.50,                  -- price_per_liter (PHP 65.50)
    490.00,                 -- sales_liters (5000 - 4500 - 10 = 490)
    32095.00,               -- sales_amount (490 * 65.50)
    'Verified',             -- status (Manager has approved - READY FOR ADMIN!)
    NOW()                   -- created_at
);

-- Check what we just created
SELECT 
    fr.id,
    ft.name as fuel_type,
    fr.reconciliation_date,
    fr.previous_reading,
    fr.present_reading,
    fr.calibration,
    (fr.present_reading - fr.previous_reading - fr.calibration) as system_stock,
    fr.status
FROM fuel_reconciliation fr
LEFT JOIN fuel_types ft ON fr.fuel_type_id = ft.id
WHERE fr.status = 'Verified'
ORDER BY fr.created_at DESC
LIMIT 1;
