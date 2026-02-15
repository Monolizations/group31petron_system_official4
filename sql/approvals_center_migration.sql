-- Master Migration Script for Approvals Center
-- Creates all missing tables required by approvals_center.php
-- Date: 2026-02-15

-- 1. Create inventory_adjustments table for stock adjustment requests
CREATE TABLE IF NOT EXISTS inventory_adjustments (
    id INT PRIMARY KEY AUTO_INCREMENT,
    station_id INT NOT NULL,
    product_id INT NOT NULL,
    requested_by INT NOT NULL,
    adjustment_type ENUM('damage', 'lost', 'found', 'expiration', 'count_variance', 'other') NOT NULL,
    qty DECIMAL(10,2) NOT NULL,
    reason TEXT NOT NULL,
    notes TEXT,
    status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
    processed_by INT NULL,
    processed_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (station_id) REFERENCES stations(id),
    FOREIGN KEY (product_id) REFERENCES products(id),
    FOREIGN KEY (requested_by) REFERENCES users(id),
    FOREIGN KEY (processed_by) REFERENCES users(id),
    INDEX (station_id, status),
    INDEX (created_at),
    INDEX (adjustment_type)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- 2. Create stock_requests table (if not exists - may already be created)
CREATE TABLE IF NOT EXISTS stock_requests (
    id INT PRIMARY KEY AUTO_INCREMENT,
    station_id INT NOT NULL,
    requested_by INT NOT NULL,
    type ENUM('fuel', 'merch') NOT NULL DEFAULT 'merch',
    product_name VARCHAR(255) NOT NULL,
    qty DECIMAL(10,2) NOT NULL,
    notes TEXT,
    status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
    processed_by INT NULL,
    processed_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (station_id) REFERENCES stations(id),
    FOREIGN KEY (requested_by) REFERENCES users(id),
    FOREIGN KEY (processed_by) REFERENCES users(id),
    INDEX (station_id, status),
    INDEX (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- 3. Create price_changes table for price approval requests
CREATE TABLE IF NOT EXISTS price_changes (
    id INT PRIMARY KEY AUTO_INCREMENT,
    station_id INT NOT NULL,
    product_id INT NOT NULL,
    proposed_by INT NOT NULL,
    old_cost DECIMAL(10,2),
    old_price DECIMAL(10,2),
    new_cost DECIMAL(10,2),
    new_price DECIMAL(10,2),
    reason TEXT,
    notes TEXT,
    status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
    reviewed_by INT NULL,
    reviewed_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (station_id) REFERENCES stations(id),
    FOREIGN KEY (product_id) REFERENCES products(id),
    FOREIGN KEY (proposed_by) REFERENCES users(id),
    FOREIGN KEY (reviewed_by) REFERENCES users(id),
    INDEX (station_id, status),
    INDEX (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Summary of changes:
-- ✓ inventory_adjustments: NEW table for stock adjustment approvals
-- ✓ stock_requests: NEW table for merchandise restocking requests  
-- ✓ price_changes: NEW table for price change approvals
-- ✓ fuel_daily_readings: EXISTING - used for fuel reading approvals
-- ✓ fuel_deliveries: EXISTING - used for delivery verifications
-- ✓ job_orders: EXISTING - used for job order approvals
