-- ============================================================================
-- PHASE 1: DATABASE SCHEMA UPDATES FOR FUEL INVENTORY WORKFLOW
-- ============================================================================
-- Complete fuel inventory system with:
-- 1. Supplier normalization (fuel_deliveries -> suppliers)
-- 2. Immutable audit trail (fuel_inventory_logs)
-- 3. Delivery workflow tracking (Encoded -> Verified -> Finalized)
-- 4. Adjustment workflow tracking (Pending -> Approved/Rejected)
--
-- Execute these migrations in order to set up the complete workflow
-- ============================================================================

-- STEP 1: Add supplier_id FK to fuel_deliveries
-- Enables normalization and referential integrity with suppliers table
START TRANSACTION;

ALTER TABLE `fuel_deliveries` 
ADD COLUMN `supplier_id` int(11) NULL AFTER `station_id`,
ADD CONSTRAINT `fk_fuel_deliveries_supplier` 
  FOREIGN KEY (`supplier_id`) 
  REFERENCES `suppliers` (`id`) 
  ON DELETE RESTRICT 
  ON UPDATE CASCADE;

ALTER TABLE `fuel_deliveries` 
ADD KEY `idx_supplier_id` (`supplier_id`);

COMMIT;

-- ============================================================================

-- STEP 2: Create fuel_inventory_logs table
-- Immutable audit trail for all fuel inventory movements
START TRANSACTION;

CREATE TABLE IF NOT EXISTS `fuel_inventory_logs` (
  `id` int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
  
  `station_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  
  `user_id` int(11) NULL COMMENT 'User who initiated the action',
  `approved_by` int(11) NULL COMMENT 'User who approved/finalized the action',
  
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
  
  `quantity_before` decimal(12,2) DEFAULT 0.00 COMMENT 'Stock level before action',
  `quantity_after` decimal(12,2) DEFAULT 0.00 COMMENT 'Stock level after action',
  `quantity_change` decimal(12,2) DEFAULT 0.00 COMMENT 'Net change in stock',
  
  `reference_type` enum('fuel_delivery', 'fuel_daily_reading', 'fuel_adjustment') NOT NULL,
  `reference_id` int(11) NOT NULL COMMENT 'ID of the source transaction',
  
  `status` enum('pending', 'approved', 'finalized', 'rejected', 'cancelled') DEFAULT 'pending',
  `notes` text NULL,
  `approval_reason` text NULL COMMENT 'Reason for approval/rejection',
  
  `created_at` timestamp DEFAULT current_timestamp(),
  `updated_at` timestamp DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  
  CONSTRAINT `fk_fuel_inventory_logs_station` FOREIGN KEY (`station_id`) 
    REFERENCES `stations` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_fuel_inventory_logs_product` FOREIGN KEY (`product_id`) 
    REFERENCES `products` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `fk_fuel_inventory_logs_user` FOREIGN KEY (`user_id`) 
    REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_fuel_inventory_logs_approved_by` FOREIGN KEY (`approved_by`) 
    REFERENCES `users` (`id`) ON DELETE SET NULL,
  
  UNIQUE KEY `unique_reference` (`reference_type`, `reference_id`, `action`),
  
  KEY `idx_station_product` (`station_id`, `product_id`),
  KEY `idx_created_at` (`created_at`),
  KEY `idx_status` (`status`),
  KEY `idx_action` (`action`),
  KEY `idx_reference` (`reference_type`, `reference_id`)
  
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
COMMENT='Immutable audit trail for fuel inventory stock changes';

COMMIT;

-- ============================================================================

-- STEP 3: Enhance fuel_deliveries with workflow columns
-- Adds verification and finalization tracking
START TRANSACTION;

ALTER TABLE `fuel_deliveries` 
ADD COLUMN `verified_by` int(11) NULL COMMENT 'Manager who verified the delivery',
ADD COLUMN `verified_at` timestamp NULL COMMENT 'When delivery was verified',
ADD COLUMN `finalized_by` int(11) NULL COMMENT 'Admin who finalized the delivery',
ADD COLUMN `finalized_at` timestamp NULL COMMENT 'When delivery was finalized',
ADD COLUMN `rejection_reason` text NULL COMMENT 'Reason if delivery was rejected';

ALTER TABLE `fuel_deliveries` 
ADD CONSTRAINT `fk_fuel_deliveries_verified_by` 
  FOREIGN KEY (`verified_by`) 
  REFERENCES `users` (`id`) 
  ON DELETE SET NULL,
ADD CONSTRAINT `fk_fuel_deliveries_finalized_by` 
  FOREIGN KEY (`finalized_by`) 
  REFERENCES `users` (`id`) 
  ON DELETE SET NULL,
ADD CONSTRAINT `fk_fuel_deliveries_received_by` 
  FOREIGN KEY (`received_by`) 
  REFERENCES `users` (`id`) 
  ON DELETE SET NULL;

ALTER TABLE `fuel_deliveries` 
MODIFY COLUMN `status` enum('Encoded', 'Verified', 'Finalized', 'Rejected') DEFAULT 'Encoded';

ALTER TABLE `fuel_deliveries` 
ADD KEY `idx_status_verified` (`status`, `verified_by`),
ADD KEY `idx_finalized_by` (`finalized_by`);

COMMIT;

-- ============================================================================

-- STEP 4: Enhance fuel_adjustments with approval workflow
-- Adds manager approval/rejection tracking
START TRANSACTION;

ALTER TABLE `fuel_adjustments` 
ADD COLUMN `approved_by` int(11) NULL COMMENT 'Manager who approved/rejected the adjustment',
ADD COLUMN `approved_at` timestamp NULL COMMENT 'When adjustment was approved/rejected',
ADD COLUMN `approval_reason` text NULL COMMENT 'Reason for approval or rejection';

ALTER TABLE `fuel_adjustments` 
ADD CONSTRAINT `fk_fuel_adjustments_user` 
  FOREIGN KEY (`user_id`) 
  REFERENCES `users` (`id`) 
  ON DELETE SET NULL,
ADD CONSTRAINT `fk_fuel_adjustments_approved_by` 
  FOREIGN KEY (`approved_by`) 
  REFERENCES `users` (`id`) 
  ON DELETE SET NULL;

ALTER TABLE `fuel_adjustments` 
MODIFY COLUMN `status` enum('Pending', 'Approved', 'Rejected', 'Cancelled') DEFAULT 'Pending';

ALTER TABLE `fuel_adjustments` 
ADD KEY `idx_status_approved_by` (`status`, `approved_by`),
ADD KEY `idx_approved_at` (`approved_at`);

COMMIT;

-- ============================================================================
-- VERIFICATION QUERIES
-- ============================================================================

SELECT '✓ PHASE 1 MIGRATIONS COMPLETE' as status;
SELECT COUNT(*) as fuel_deliveries_count FROM fuel_deliveries;
SELECT COUNT(*) as fuel_adjustments_count FROM fuel_adjustments;
SELECT COUNT(*) as fuel_inventory_logs_count FROM fuel_inventory_logs;
SHOW TABLES LIKE 'fuel%';
