-- Create inventory_adjustments table for stock adjustment requests
-- Used by staff to request adjustments and managers to approve them

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
