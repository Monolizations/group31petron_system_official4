-- Create receiving_batches table for batch management
CREATE TABLE IF NOT EXISTS receiving_batches (
    id INT AUTO_INCREMENT PRIMARY KEY,
    batch_number VARCHAR(50) UNIQUE NOT NULL,
    station_id INT NOT NULL,
    supplier VARCHAR(255) NOT NULL,
    delivery_date DATE NOT NULL,
    notes TEXT,
    received_by INT NOT NULL COMMENT 'Staff who encoded',
    received_by_manager INT NULL COMMENT 'Manager/Admin who approved',
    confirmed_by INT NULL COMMENT 'Manager/Admin who confirmed stock',
    status ENUM('pending', 'received', 'confirmed', 'rejected') DEFAULT 'pending',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    received_at DATETIME NULL,
    confirmed_at DATETIME NULL,
    rejected_at DATETIME NULL,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (station_id) REFERENCES stations(id),
    FOREIGN KEY (received_by) REFERENCES users(id),
    FOREIGN KEY (received_by_manager) REFERENCES users(id),
    FOREIGN KEY (confirmed_by) REFERENCES users(id),
    INDEX (station_id, status),
    INDEX (status),
    INDEX (batch_number),
    INDEX (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
COMMENT='Receiving batches for multi-item receiving workflow';
