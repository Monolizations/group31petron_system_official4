-- Create test reconciliation for Manager to approve
USE petron_pos_db_secure;

-- Insert a reconciliation record that Manager can see
INSERT INTO fuel_reconciliation (
    station_id,
    fuel_type_id,
    pump_id,
    reconciliation_date,
    previous_reading,
    present_reading,
    calibration,
    sales_liters,
    sales_amount,
    price_per_liter,
    status,
    created_at
) VALUES (
    1,                  -- station_id
    1,                  -- fuel_type_id (first fuel type)
    1,                  -- pump_id
    CURDATE(),          -- today
    4500.00,            -- previous_reading
    5000.00,            -- present_reading
    -10.00,             -- calibration
    490.00,             -- sales_liters (5000 - 4500 - 10)
    32095.00,           -- sales_amount (490 * 65.50)
    65.50,              -- price_per_liter
    'Pending',          -- status (Manager needs to approve)
    NOW()               -- created_at
);

-- Verify it was created
SELECT 'Record Created' as status;
SELECT * FROM fuel_reconciliation WHERE status = 'Pending' ORDER BY created_at DESC LIMIT 1;
