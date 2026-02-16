<?php
/**
 * Petron Fuel Types Migration Script
 * Updates fuel_types table with Petron-branded names
 */

require_once __DIR__ . '/public/db_connect.php';

echo "=== Petron Fuel Types Migration ===\n\n";

try {
    // Update existing fuel types to Petron branding
    echo "Updating existing fuel types...\n";

    $updates = [
        'Gasoline' => 'Petron Blaze 100 (High Octane Gasoline)',
        'Diesel' => 'Petron Diesel Max (Diesel)',
        'Premium' => 'Petron XCS Plus (Premium Gasoline)',
        'Unleaded' => 'Petron XCS (Unleaded Gasoline)',
        'LPG' => 'Petron LPG (Liquefied Petroleum Gas)'
    ];

    foreach ($updates as $oldName => $newName) {
        $stmt = $pdo->prepare("UPDATE fuel_types SET name = ? WHERE name = ?");
        $result = $stmt->execute([$newName, $oldName]);

        if ($result) {
            $count = $stmt->rowCount();
            if ($count > 0) {
                echo "✓ Updated: '$oldName' -> '$newName'\n";
            } else {
                echo "⚠ No records found for: '$oldName'\n";
            }
        } else {
            echo "✗ Failed to update: '$oldName'\n";
        }
    }

    echo "\nAdding new Petron fuel types...\n";

    // Add additional Petron fuel types if they don't exist
    $newFuels = [
        'Petron Turbo Diesel (High Performance Diesel)',
        'Petron XCS Advance (Advanced Unleaded)',
        'Petron Kerosene (Kerosene)',
        'Petron Radiant Plus (Premium Plus Gasoline)'
    ];

    foreach ($newFuels as $fuelName) {
        $stmt = $pdo->prepare("SELECT id FROM fuel_types WHERE name = ?");
        $stmt->execute([$fuelName]);

        if (!$stmt->fetch()) {
            $insert = $pdo->prepare("INSERT INTO fuel_types (name) VALUES (?)");
            $result = $insert->execute([$fuelName]);

            if ($result) {
                echo "✓ Added: '$fuelName'\n";
            } else {
                echo "✗ Failed to add: '$fuelName'\n";
            }
        } else {
            echo "⚠ Already exists: '$fuelName'\n";
        }
    }

    echo "\n=== Current Fuel Types in Database ===\n";
    $stmt = $pdo->query("SELECT id, name FROM fuel_types ORDER BY name");
    $fuels = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($fuels as $fuel) {
        echo "[{$fuel['id']}] {$fuel['name']}\n";
    }

    echo "\n=== Migration Complete ===\n";
    echo "Total fuel types: " . count($fuels) . "\n";

} catch (PDOException $e) {
    echo "\n✗ Error: " . $e->getMessage() . "\n";
    exit(1);
}
