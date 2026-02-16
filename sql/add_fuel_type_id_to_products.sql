-- Add fuel_type_id column to products table to link fuel products to fuel_types
ALTER TABLE products 
ADD COLUMN fuel_type_id INT(11) NULL COMMENT 'Link to fuel_types for fuel products' AFTER type_id,
ADD CONSTRAINT `fk_product_fuel_type` FOREIGN KEY (`fuel_type_id`) 
  REFERENCES `fuel_types`(`id`) ON DELETE SET NULL;

-- Update existing fuel products (type_id = 1) based on their names
UPDATE products SET fuel_type_id = 1 WHERE type_id = 1 AND LOWER(name) LIKE '%gasoline%' AND fuel_type_id IS NULL;
UPDATE products SET fuel_type_id = 2 WHERE type_id = 1 AND LOWER(name) LIKE '%diesel%' AND fuel_type_id IS NULL;
UPDATE products SET fuel_type_id = 3 WHERE type_id = 1 AND LOWER(name) LIKE '%lpg%' AND fuel_type_id IS NULL;
UPDATE products SET fuel_type_id = 4 WHERE type_id = 1 AND LOWER(name) LIKE '%premium%' AND fuel_type_id IS NULL;
UPDATE products SET fuel_type_id = 5 WHERE type_id = 1 AND LOWER(name) LIKE '%unleaded%' AND fuel_type_id IS NULL;
