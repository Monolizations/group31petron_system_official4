-- Add columns to received_items table for batch workflow
ALTER TABLE received_items
ADD COLUMN batch_id INT NULL AFTER id,
ADD COLUMN unit_cost DECIMAL(10,2) DEFAULT 0 AFTER quantity,
ADD COLUMN status ENUM('pending', 'received', 'confirmed', 'rejected') DEFAULT 'pending' AFTER created_at,
ADD INDEX (batch_id),
ADD FOREIGN KEY (batch_id) REFERENCES receiving_batches(id) ON DELETE CASCADE;
