-- Create price_changes table for price approval requests
-- Simplified version for approvals_center.php dashboard

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
