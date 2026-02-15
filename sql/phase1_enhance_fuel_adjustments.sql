-- ============================================================================
-- PHASE 1.4: Enhance fuel_adjustments with Approval Workflow Columns
-- ============================================================================
-- Purpose: Add approval tracking columns for adjustment workflow
-- Enables multi-step approval: Pending → Approved/Rejected → Stock Updated
--
-- Changes:
-- 1. Add approved_by, approved_at columns (manager approval)
-- 2. Update status enum to reflect workflow states
-- 3. Add approval_reason for approvals and rejections
-- ============================================================================

-- Step 1: Add approval tracking columns
ALTER TABLE `fuel_adjustments` 
ADD COLUMN `approved_by` int(11) NULL COMMENT 'Manager who approved/rejected the adjustment',
ADD COLUMN `approved_at` timestamp NULL COMMENT 'When adjustment was approved/rejected',
ADD COLUMN `approval_reason` text NULL COMMENT 'Reason for approval or rejection';

-- Step 2: Add foreign key for tracking user
ALTER TABLE `fuel_adjustments` 
ADD CONSTRAINT `fk_fuel_adjustments_user` 
  FOREIGN KEY (`user_id`) 
  REFERENCES `users` (`id`) 
  ON DELETE SET NULL,
ADD CONSTRAINT `fk_fuel_adjustments_approved_by` 
  FOREIGN KEY (`approved_by`) 
  REFERENCES `users` (`id`) 
  ON DELETE SET NULL;

-- Step 3: Update status enum to include workflow states
-- Change VARCHAR status to ENUM with workflow states
ALTER TABLE `fuel_adjustments` 
MODIFY COLUMN `status` enum('Pending', 'Approved', 'Rejected', 'Cancelled') DEFAULT 'Pending';

-- Step 4: Create index for workflow queries
ALTER TABLE `fuel_adjustments` 
ADD KEY `idx_status_approved_by` (`status`, `approved_by`),
ADD KEY `idx_approved_at` (`approved_at`);

-- Verification
SELECT 'fuel_adjustments workflow columns added successfully' as status;
DESCRIBE fuel_adjustments;
