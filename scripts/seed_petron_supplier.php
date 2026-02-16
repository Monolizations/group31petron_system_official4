<?php
/**
 * Seed script: Add default Petron Supplier
 * 
 * This script adds the "Petron Supplier" to the suppliers table and sets it as the default supplier.
 * Purpose: Provide a pre-configured default supplier for the receiving staff workflow.
 */

require_once __DIR__ . '/../public/db_connect.php';

try {
    // Check if Petron Supplier already exists
    $stmt = $pdo->query("SELECT id FROM suppliers WHERE name = 'Petron Supplier' LIMIT 1");
    $existing = $stmt->fetchColumn();
    
    if ($existing) {
        echo "✓ Petron Supplier already exists (ID: {$existing})\n";
        $supplier_id = $existing;
    } else {
        // Insert Petron Supplier
        $stmt = $pdo->prepare("
            INSERT INTO suppliers (name, contact_person, phone, email, status)
            VALUES (?, ?, ?, ?, 'active')
        ");
        $stmt->execute([
            'Petron Supplier',
            'Supply Team',
            '+63-2-8123-0000',
            'supply@petron.ph'
        ]);
        $supplier_id = $pdo->lastInsertId();
        echo "✓ Inserted Petron Supplier (ID: {$supplier_id})\n";
    }
    
    // Set as default supplier
    $stmt = $pdo->prepare("
        INSERT INTO system_settings (setting_key, setting_value, description)
        VALUES ('default_supplier_id', ?, 'Default supplier for receiving staff')
        ON DUPLICATE KEY UPDATE setting_value = ?
    ");
    $stmt->execute([(string)$supplier_id, (string)$supplier_id]);
    echo "✓ Set Petron Supplier as default supplier\n";
    
    // Verify
    $stmt = $pdo->prepare("SELECT name FROM suppliers WHERE id = ?");
    $stmt->execute([$supplier_id]);
    $name = $stmt->fetchColumn();
    echo "\n✓ Success! Default supplier is now: {$name}\n";
    
} catch (Exception $e) {
    echo "✗ Error: " . $e->getMessage() . "\n";
    exit(1);
}
