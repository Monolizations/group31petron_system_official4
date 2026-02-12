-- Create stock_requests table for merchandise restocking requests

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
