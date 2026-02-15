-- Add price approval workflow to products table
-- This migration adds status tracking for the 3-tier price approval system

-- Add columns if they don't exist
ALTER TABLE products ADD COLUMN IF NOT EXISTS price_status ENUM('proposed', 'approved', 'rejected') DEFAULT 'proposed';
ALTER TABLE products ADD COLUMN IF NOT EXISTS proposed_price DECIMAL(10,2);
ALTER TABLE products ADD COLUMN IF NOT EXISTS proposed_cost DECIMAL(10,2);
ALTER TABLE products ADD COLUMN IF NOT EXISTS proposed_by INT;
ALTER TABLE products ADD COLUMN IF NOT EXISTS proposed_at TIMESTAMP;
ALTER TABLE products ADD COLUMN IF NOT EXISTS approved_by INT;
ALTER TABLE products ADD COLUMN IF NOT EXISTS approved_at TIMESTAMP;
ALTER TABLE products ADD COLUMN IF NOT EXISTS rejection_reason VARCHAR(255);

-- Add foreign keys
ALTER TABLE products ADD CONSTRAINT fk_proposed_by FOREIGN KEY (proposed_by) REFERENCES users(id);
ALTER TABLE products ADD CONSTRAINT fk_approved_by FOREIGN KEY (approved_by) REFERENCES users(id);

-- Create audit log table for price changes
CREATE TABLE IF NOT EXISTS price_change_logs (
  id INT AUTO_INCREMENT PRIMARY KEY,
  product_id INT NOT NULL,
  old_cost DECIMAL(10,2),
  old_price DECIMAL(10,2),
  new_cost DECIMAL(10,2),
  new_price DECIMAL(10,2),
  action VARCHAR(50), -- proposed, approved, rejected, batch_pricing
  user_id INT,
  batch_id INT NULL COMMENT 'Related receiving batch if pricing is from batch workflow',
  timestamp TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  notes VARCHAR(255),
  FOREIGN KEY (product_id) REFERENCES products(id),
  FOREIGN KEY (user_id) REFERENCES users(id),
  FOREIGN KEY (batch_id) REFERENCES receiving_batches(id)
);

-- Create price approval queue table
CREATE TABLE IF NOT EXISTS price_approvals (
  id INT AUTO_INCREMENT PRIMARY KEY,
  product_id INT NOT NULL,
  proposed_cost DECIMAL(10,2),
  proposed_price DECIMAL(10,2),
  current_cost DECIMAL(10,2),
  current_price DECIMAL(10,2),
  proposed_by INT NOT NULL,
  proposed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
  manager_id INT,
  reviewed_at TIMESTAMP NULL,
  rejection_reason VARCHAR(255),
  FOREIGN KEY (product_id) REFERENCES products(id),
  FOREIGN KEY (proposed_by) REFERENCES users(id),
  FOREIGN KEY (manager_id) REFERENCES users(id)
);
