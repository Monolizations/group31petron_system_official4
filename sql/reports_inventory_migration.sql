-- Reports & Inventory Tables Migration

-- Shift Reports Table
CREATE TABLE IF NOT EXISTS shift_reports (
    id INT PRIMARY KEY AUTO_INCREMENT,
    station_id INT NOT NULL,
    report_date DATE NOT NULL,
    shift_type ENUM('morning', 'afternoon', 'night', 'full_day') DEFAULT 'full_day',
    sales_total DECIMAL(12,2) DEFAULT 0,
    job_orders_count INT DEFAULT 0,
    job_orders_revenue DECIMAL(12,2) DEFAULT 0,
    inventory_received DECIMAL(10,2) DEFAULT 0,
    inventory_deducted DECIMAL(10,2) DEFAULT 0,
    fuel_variance DECIMAL(12,2) DEFAULT 0,
    status ENUM('Pending Verification', 'Verified', 'Rejected', 'Finalized', 'Archived') DEFAULT 'Pending Verification',
    generated_by INT NOT NULL,
    verified_by INT NULL,
    verified_at TIMESTAMP NULL,
    rejected_by INT NULL,
    rejected_at TIMESTAMP NULL,
    finalized_by INT NULL,
    finalized_at TIMESTAMP NULL,
    manager_remarks TEXT,
    is_locked TINYINT DEFAULT 0,
    is_deleted TINYINT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (station_id) REFERENCES stations(id),
    FOREIGN KEY (generated_by) REFERENCES users(id),
    FOREIGN KEY (verified_by) REFERENCES users(id),
    FOREIGN KEY (rejected_by) REFERENCES users(id),
    FOREIGN KEY (finalized_by) REFERENCES users(id),
    UNIQUE KEY (station_id, report_date, shift_type),
    INDEX (status, created_at),
    INDEX (station_id, report_date)
);

-- Supplier Receipts Table
CREATE TABLE IF NOT EXISTS supplier_receipts (
    id INT PRIMARY KEY AUTO_INCREMENT,
    station_id INT NOT NULL,
    supplier_id INT NOT NULL,
    po_reference VARCHAR(100),
    recorded_by INT NOT NULL,
    status ENUM('Pending Confirmation', 'Confirmed', 'Rejected', 'Archived') DEFAULT 'Pending Confirmation',
    confirmed_by INT NULL,
    confirmed_at TIMESTAMP NULL,
    admin_remarks TEXT,
    is_deleted TINYINT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (station_id) REFERENCES stations(id),
    FOREIGN KEY (supplier_id) REFERENCES suppliers(id),
    FOREIGN KEY (recorded_by) REFERENCES users(id),
    FOREIGN KEY (confirmed_by) REFERENCES users(id),
    INDEX (station_id, status),
    INDEX (created_at)
);

-- Receipt Items Table
CREATE TABLE IF NOT EXISTS receipt_items (
    id INT PRIMARY KEY AUTO_INCREMENT,
    receipt_id INT NOT NULL,
    product_id INT NOT NULL,
    quantity DECIMAL(10,2) NOT NULL,
    unit_price DECIMAL(10,2) NOT NULL,
    total_price DECIMAL(12,2) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (receipt_id) REFERENCES supplier_receipts(id),
    FOREIGN KEY (product_id) REFERENCES products(id),
    INDEX (receipt_id),
    INDEX (product_id)
);

-- Fuel Reconciliation Table
CREATE TABLE IF NOT EXISTS fuel_reconciliation (
    id INT PRIMARY KEY AUTO_INCREMENT,
    station_id INT NOT NULL,
    reconciliation_date DATE NOT NULL,
    current_reading DECIMAL(10,2) NOT NULL,
    previous_reading DECIMAL(10,2) NOT NULL,
    calibration_adjustment DECIMAL(10,2) DEFAULT 0,
    liters_variance DECIMAL(10,2),
    monetary_variance DECIMAL(12,2),
    variance_percentage DECIMAL(5,2),
    is_acceptable TINYINT DEFAULT 1,
    verified_by INT NOT NULL,
    manager_remarks TEXT,
    is_deleted TINYINT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (station_id) REFERENCES stations(id),
    FOREIGN KEY (verified_by) REFERENCES users(id),
    UNIQUE KEY (station_id, reconciliation_date),
    INDEX (is_acceptable, created_at)
);

-- Fuel Readings Table (ensure exists)
CREATE TABLE IF NOT EXISTS fuel_readings (
    id INT PRIMARY KEY AUTO_INCREMENT,
    station_id INT NOT NULL,
    reading_liters DECIMAL(10,2) NOT NULL,
    calibration_adjustment DECIMAL(10,2) DEFAULT 0,
    price_per_liter DECIMAL(8,2) DEFAULT 50,
    recorded_by INT NOT NULL,
    reading_time TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    is_deleted TINYINT DEFAULT 0,
    FOREIGN KEY (station_id) REFERENCES stations(id),
    FOREIGN KEY (recorded_by) REFERENCES users(id),
    INDEX (station_id, reading_time)
);

-- Suppliers Table (if not exist)
CREATE TABLE IF NOT EXISTS suppliers (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(255) NOT NULL,
    contact_person VARCHAR(100),
    phone VARCHAR(20),
    email VARCHAR(100),
    address TEXT,
    status ENUM('active', 'inactive', 'archived') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX (status)
);

-- Create indexes for audit logging
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
