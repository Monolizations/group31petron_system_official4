-- Quick fix: Assign station_id = 1 to admin user
-- This fixes the "No users found" issue in users.php

-- Update admin user (ID: 2) to have station_id = 1
UPDATE users SET station_id = 1 WHERE id = 2;

-- Verify the fix
SELECT id, username, name, role, station_id FROM users WHERE id = 2;

-- Show users that will now be visible to admin
SELECT id, username, name, role, station_id FROM users WHERE station_id = 1;
