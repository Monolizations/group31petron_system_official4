-- Test data for Fuel Reconciliation Finalization
USE petron_pos_db_secure;

-- Insert test reconciliation record with status='approved' (ready for finalization)
INSERT INTO fuel_reconciliation (
  station_id,
  reconciliation_date,
  fuel_type_id,
  pump_id,
  previous_reading,
  present_reading,
  calibration,
  price_per_liter,
  status,
  verified_by,
  verified_at,
  notes
) VALUES (
  1,
  NOW(),
  1,
  1,
  4850.00,
  5000.00,
  -10.00,
  45.00,
  'Verified',
  2,
  NOW(),
  'Test reconciliation - ready for admin finalization'
);

-- Verify insertion
SELECT 'Test record created successfully! ID:' as status, LAST_INSERT_ID() as reconciliation_id;
