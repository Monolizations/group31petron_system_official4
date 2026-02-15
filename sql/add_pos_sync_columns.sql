-- Migration: Add POS sync columns for reconciliation
-- Purpose: Track when and who synced fuel reconciliation to POS inventory
-- Date: 2026-02-16

-- Add sync columns to station_inventory table
ALTER TABLE station_inventory ADD COLUMN (
    last_synced_at TIMESTAMP NULL DEFAULT NULL COMMENT 'When POS was last synced with pump system',
    last_synced_by INT NULL COMMENT 'user_id who last synced',
    last_sync_type VARCHAR(50) NULL COMMENT 'Type of sync: reconciliation',
    last_sync_reference_id INT NULL COMMENT 'ID of reconciliation synced',
    in_sync BOOLEAN DEFAULT TRUE COMMENT 'TRUE if POS matches fuel system'
);

-- Create index for performance
CREATE INDEX idx_sync_status ON station_inventory(in_sync, last_synced_at);

-- Add sync columns to fuel_reconciliation table
ALTER TABLE fuel_reconciliation ADD COLUMN (
    synced_to_pos BOOLEAN DEFAULT FALSE COMMENT 'TRUE if closing stock synced to POS',
    synced_at TIMESTAMP NULL COMMENT 'When synced to POS',
    synced_by INT NULL COMMENT 'user_id who performed sync',
    FOREIGN KEY (synced_by) REFERENCES users(id)
);

-- Create index for tracking synced reconciliations
CREATE INDEX idx_reconciliation_synced ON fuel_reconciliation(synced_to_pos, synced_at);
