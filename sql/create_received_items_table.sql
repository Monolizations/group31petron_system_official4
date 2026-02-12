-- Create received_items table for staff receiving workflow

CREATE TABLE IF NOT EXISTS received_items (
    id INT PRIMARY KEY AUTO_INCREMENT,
    station_id INT NOT NULL,
    product_id INT NULL,
    item_name VARCHAR(255) NOT NULL,
    quantity DECIMAL(10,2) NOT NULL,
    supplier VARCHAR(255),
    delivery_date DATE NOT NULL,
    received_by INT NOT NULL,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (station_id) REFERENCES stations(id),
    FOREIGN KEY (product_id) REFERENCES products(id),
    FOREIGN KEY (received_by) REFERENCES users(id),
    INDEX (station_id, delivery_date),
    INDEX (received_by)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Create inventory_logs table if not exists (for tracking changes)
CREATE TABLE IF NOT EXISTS inventory_logs (
    id INT PRIMARY KEY AUTO_INCREMENT,
    station_id INT NOT NULL,
    product_id INT NOT NULL,
    user_id INT NOT NULL,
    action VARCHAR(50) NOT NULL,
    quantity_before DECIMAL(10,2) DEFAULT 0,
    quantity_after DECIMAL(10,2) DEFAULT 0,
    quantity_change DECIMAL(10,2) NOT NULL,
    reference_type VARCHAR(50),
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (station_id) REFERENCES stations(id),
    FOREIGN KEY (product_id) REFERENCES products(id),
    FOREIGN KEY (user_id) REFERENCES users(id),
    INDEX (station_id, created_at),
    INDEX (product_id, created_at),
    INDEX (action)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
