-- Insert default Petron Supplier
INSERT IGNORE INTO suppliers (name, contact_person, phone, email, address, created_at)
VALUES (
    'Petron Supplier',
    'Petron Supply Chain',
    'N/A',
    'supply@petron.com',
    'Petron Corporation Headquarters',
    NOW()
);

-- Update system_settings with default supplier ID (ID should be 1 if this is the first supplier)
UPDATE system_settings SET setting_value = '1', updated_at = NOW() WHERE setting_key = 'default_supplier_id';
