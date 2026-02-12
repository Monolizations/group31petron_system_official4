-- Job Order Workflow Enhancement Migration
-- Implements three-tier approval + inventory locking + billing protection

-- Add workflow control columns if not exist
ALTER TABLE job_orders ADD COLUMN IF NOT EXISTS staff_editable TINYINT DEFAULT 1 AFTER requires_approval;
ALTER TABLE job_orders ADD COLUMN IF NOT EXISTS billing_locked TINYINT DEFAULT 0 AFTER total_cost;

-- Add approval tracking columns
ALTER TABLE job_orders ADD COLUMN IF NOT EXISTS approved_by INT NULL AFTER finalized_at;
ALTER TABLE job_orders ADD COLUMN IF NOT EXISTS approved_at TIMESTAMP NULL AFTER approved_by;
ALTER TABLE job_orders ADD COLUMN IF NOT EXISTS manager_remarks TEXT NULL AFTER approved_at;

-- Add rejection tracking
ALTER TABLE job_orders ADD COLUMN IF NOT EXISTS rejected_by INT NULL AFTER manager_remarks;
ALTER TABLE job_orders ADD COLUMN IF NOT EXISTS rejected_at TIMESTAMP NULL AFTER rejected_by;

-- Add finalization tracking
ALTER TABLE job_orders ADD COLUMN IF NOT EXISTS finalized_by INT NULL AFTER rejected_at;
ALTER TABLE job_orders ADD COLUMN IF NOT EXISTS finalized_at TIMESTAMP NULL AFTER finalized_by;

-- Add soft delete flag
ALTER TABLE job_orders ADD COLUMN IF NOT EXISTS is_deleted TINYINT DEFAULT 0 AFTER finalized_at;
ALTER TABLE job_orders ADD COLUMN IF NOT EXISTS deleted_at TIMESTAMP NULL AFTER is_deleted;

-- Create audit logging table if not exist
CREATE TABLE IF NOT EXISTS audit_log (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    user_role VARCHAR(50),
    action VARCHAR(100) NOT NULL,
    resource_type VARCHAR(50),
    resource_id INT,
    details TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id),
    INDEX (user_id, created_at),
    INDEX (action, created_at),
    INDEX (resource_type, resource_id)
);

-- Update job_orders status enum to include new statuses
-- (Use raw SQL if column exists, otherwise create with correct values)
ALTER TABLE job_orders MODIFY COLUMN status ENUM(
    'Pending', 
    'Approved', 
    'Rejected',
    'In Progress', 
    'Completed',
    'Archived'
) DEFAULT 'Pending';

-- Create inventory_transactions table if not exist (for deduction tracking)
CREATE TABLE IF NOT EXISTS inventory_transactions (
    id INT PRIMARY KEY AUTO_INCREMENT,
    station_id INT NOT NULL,
    product_id INT NOT NULL,
    transaction_type ENUM('addition', 'deduction', 'adjustment', 'write_off') NOT NULL,
    quantity DECIMAL(10,2) NOT NULL,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    created_by INT,
    FOREIGN KEY (station_id) REFERENCES stations(id),
    FOREIGN KEY (product_id) REFERENCES products(id),
    FOREIGN KEY (created_by) REFERENCES users(id),
    INDEX (station_id, created_at),
    INDEX (transaction_type),
    INDEX (product_id)
);

-- Ensure station_inventory has stock_level and safety constraints
ALTER TABLE station_inventory ADD COLUMN IF NOT EXISTS stock_level DECIMAL(10,2) DEFAULT 0 AFTER quantity;
ALTER TABLE station_inventory ADD COLUMN IF NOT EXISTS min_stock DECIMAL(10,2) DEFAULT 0;
ALTER TABLE station_inventory ADD COLUMN IF NOT EXISTS last_counted_at TIMESTAMP NULL;

-- Create job_order_parts table if not exist (detailed parts usage)
CREATE TABLE IF NOT EXISTS job_order_parts (
    id INT PRIMARY KEY AUTO_INCREMENT,
    job_order_id INT NOT NULL,
    product_id INT NOT NULL,
    quantity_used DECIMAL(10,2) NOT NULL,
    unit_cost DECIMAL(10,2) NOT NULL,
    total_cost DECIMAL(10,2) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (job_order_id) REFERENCES job_orders(id),
    FOREIGN KEY (product_id) REFERENCES products(id),
    INDEX (job_order_id),
    INDEX (product_id)
);
