-- ============================================================================
-- PHASE 1.3: Enhance fuel_deliveries with Workflow Columns
-- ============================================================================
-- Purpose: Add verification and finalization workflow tracking columns
-- Enables multi-step approval: Encoded → Verified → Finalized → Stock Updated
--
-- Changes:
-- 1. Add verified_by, verified_at columns (manager verification)
-- 2. Add finalized_by, finalized_at columns (admin finalization)
-- 3. Update status enum to reflect workflow states
-- 4. Add approved_reason for rejections
-- ============================================================================

-- Step 1: Add workflow tracking columns
ALTER TABLE `fuel_deliveries` 
ADD COLUMN `verified_by` int(11) NULL COMMENT 'Manager who verified the delivery',
ADD COLUMN `verified_at` timestamp NULL COMMENT 'When delivery was verified',
ADD COLUMN `finalized_by` int(11) NULL COMMENT 'Admin who finalized the delivery',
ADD COLUMN `finalized_at` timestamp NULL COMMENT 'When delivery was finalized',
ADD COLUMN `rejection_reason` text NULL COMMENT 'Reason if delivery was rejected';

-- Step 2: Add foreign keys for tracking users
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

-- Step 3: Update status enum to include workflow states
-- Change VARCHAR status to ENUM with workflow states
ALTER TABLE `fuel_deliveries` 
MODIFY COLUMN `status` enum('Encoded', 'Verified', 'Finalized', 'Rejected') DEFAULT 'Encoded';

-- Step 4: Create index for workflow queries
ALTER TABLE `fuel_deliveries` 
ADD KEY `idx_status_verified` (`status`, `verified_by`),
ADD KEY `idx_finalized_by` (`finalized_by`);

-- Verification
SELECT 'fuel_deliveries workflow columns added successfully' as status;
DESCRIBE fuel_deliveries;
