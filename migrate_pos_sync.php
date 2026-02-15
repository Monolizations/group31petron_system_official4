<?php
/**
 * Migration Runner: Add POS Sync Columns
 * Adds sync tracking columns to station_inventory and fuel_reconciliation tables
 */

require_once __DIR__ . '/public/db_connect.php';

try {
    echo "Starting migration: Add POS Sync Columns\n";
    echo str_repeat("-", 60) . "\n";
    
    // Check if columns already exist
    $stmt = $pdo->query("DESCRIBE station_inventory");
    $columns = $stmt->fetchAll(PDO::FETCH_COLUMN, 0);
    
    if (in_array('last_synced_at', $columns)) {
        echo "✅ Columns already exist. Migration already applied.\n";
        exit(0);
    }
    
    // 1. Add columns to station_inventory
    echo "\n1. Adding columns to station_inventory...\n";
    $pdo->exec("ALTER TABLE station_inventory ADD COLUMN (
        last_synced_at TIMESTAMP NULL DEFAULT NULL COMMENT 'When POS was last synced with pump system',
        last_synced_by INT NULL COMMENT 'user_id who last synced',
        last_sync_type VARCHAR(50) NULL COMMENT 'Type of sync: reconciliation',
        last_sync_reference_id INT NULL COMMENT 'ID of reconciliation synced',
        in_sync BOOLEAN DEFAULT TRUE COMMENT 'TRUE if POS matches fuel system'
    )");
    echo "   ✅ Columns added to station_inventory\n";
    
    // 2. Create index on station_inventory
    echo "\n2. Creating index on station_inventory...\n";
    $pdo->exec("CREATE INDEX idx_sync_status ON station_inventory(in_sync, last_synced_at)");
    echo "   ✅ Index created\n";
    
    // 3. Add columns to fuel_reconciliation
    echo "\n3. Adding columns to fuel_reconciliation...\n";
    $pdo->exec("ALTER TABLE fuel_reconciliation ADD COLUMN (
        synced_to_pos BOOLEAN DEFAULT FALSE COMMENT 'TRUE if closing stock synced to POS',
        synced_at TIMESTAMP NULL COMMENT 'When synced to POS',
        synced_by INT NULL COMMENT 'user_id who performed sync'
    )");
    echo "   ✅ Columns added to fuel_reconciliation\n";
    
    // 4. Add foreign key constraint
    echo "\n4. Adding foreign key constraint...\n";
    $pdo->exec("ALTER TABLE fuel_reconciliation 
               ADD CONSTRAINT fk_reconciliation_synced_by 
               FOREIGN KEY (synced_by) REFERENCES users(id)");
    echo "   ✅ Foreign key added\n";
    
    // 5. Create index on fuel_reconciliation
    echo "\n5. Creating index on fuel_reconciliation...\n";
    $pdo->exec("CREATE INDEX idx_reconciliation_synced ON fuel_reconciliation(synced_to_pos, synced_at)");
    echo "   ✅ Index created\n";
    
    echo "\n" . str_repeat("-", 60) . "\n";
    echo "✅ Migration completed successfully!\n";
    
} catch (PDOException $e) {
    echo "❌ Migration failed: " . $e->getMessage() . "\n";
    exit(1);
}
?>
