-- ============================================================================
-- PHASE 1.2: Create fuel_inventory_logs Table
-- ============================================================================
-- Purpose: Immutable audit trail specifically for fuel inventory stock changes
-- Tracks all movements, approvals, and stock updates with complete history
--
-- Features:
-- 1. Complete reference to source transaction
-- 2. Before/after quantities
-- 3. Multi-stage status tracking
-- 4. Immutable design (no deletes allowed in app)
-- ============================================================================

CREATE TABLE IF NOT EXISTS `fuel_inventory_logs` (
  `id` int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
  
  -- Location & Product
  `station_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  
  -- User Tracking
  `user_id` int(11) NULL COMMENT 'User who initiated the action',
  `approved_by` int(11) NULL COMMENT 'User who approved/finalized the action',
  
  -- Action Type
  `action` enum(
    'delivery_recorded',
    'delivery_verified', 
    'delivery_finalized',
    'reading_recorded',
    'reading_approved',
    'adjustment_requested',
    'adjustment_approved',
    'adjustment_rejected',
    'stock_deducted',
    'stock_added'
  ) NOT NULL DEFAULT 'delivery_recorded',
  
  -- Stock Changes
  `quantity_before` decimal(12,2) DEFAULT 0.00 COMMENT 'Stock level before action',
  `quantity_after` decimal(12,2) DEFAULT 0.00 COMMENT 'Stock level after action',
  `quantity_change` decimal(12,2) DEFAULT 0.00 COMMENT 'Net change in stock',
  
  -- Reference to Source Transaction
  `reference_type` enum('fuel_delivery', 'fuel_daily_reading', 'fuel_adjustment') NOT NULL,
  `reference_id` int(11) NOT NULL COMMENT 'ID of the source transaction',
  
  -- Status & Notes
  `status` enum('pending', 'approved', 'finalized', 'rejected', 'cancelled') DEFAULT 'pending',
  `notes` text NULL,
  `approval_reason` text NULL COMMENT 'Reason for approval/rejection',
  
  -- Timestamps
  `created_at` timestamp DEFAULT current_timestamp(),
  `updated_at` timestamp DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  
  -- Foreign Keys
  CONSTRAINT `fk_fuel_inventory_logs_station` FOREIGN KEY (`station_id`) 
    REFERENCES `stations` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_fuel_inventory_logs_product` FOREIGN KEY (`product_id`) 
    REFERENCES `products` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `fk_fuel_inventory_logs_user` FOREIGN KEY (`user_id`) 
    REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_fuel_inventory_logs_approved_by` FOREIGN KEY (`approved_by`) 
    REFERENCES `users` (`id`) ON DELETE SET NULL,
  
  -- Unique constraint: one log entry per source transaction (prevent duplicates)
  UNIQUE KEY `unique_reference` (`reference_type`, `reference_id`, `action`),
  
  -- Indexes for queries
  KEY `idx_station_product` (`station_id`, `product_id`),
  KEY `idx_created_at` (`created_at`),
  KEY `idx_status` (`status`),
  KEY `idx_action` (`action`),
  KEY `idx_reference` (`reference_type`, `reference_id`)
  
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
COMMENT='Immutable audit trail for fuel inventory stock changes';

-- Verification
SELECT 'fuel_inventory_logs table created successfully' as status;
DESCRIBE fuel_inventory_logs;
