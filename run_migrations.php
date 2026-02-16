<?php
/**
 * Migration Runner - Pump Tracking Phase 1
 * 
 * This script executes the necessary database migrations for pump tracking
 * in the fuel POS system.
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

require '../public/db_connect.php';

// Simple authentication check
session_start();
if (!isset($_SESSION['user_id'])) {
    die("You must be logged in to run migrations. Please log in first.");
}

// Only allow admin users to run migrations
// You may want to add additional checks here
$migrations = [];
$executed = [];
$errors = [];

try {
    // Migration 1: Add nozzles table (if not exists)
    echo "<h2>Phase 1: Pump Tracking Migrations</h2>\n";
    echo "<hr>\n";
    
    // Check if nozzles table exists
    $result = $pdo->query("SELECT 1 FROM information_schema.tables WHERE table_schema='petron_pos_db_secure' AND table_name='nozzles'");
    if (!$result->fetch()) {
        echo "<h3>1. Creating nozzles table...</h3>\n";
        $sql_file = __DIR__ . '/../sql/add_nozzles_table.sql';
        if (file_exists($sql_file)) {
            $sql = file_get_contents($sql_file);
            // Execute multi-statement SQL
            foreach (explode(';', $sql) as $statement) {
                $statement = trim($statement);
                if (!empty($statement)) {
                    try {
                        $pdo->exec($statement);
                    } catch (Exception $e) {
                        // Ignore "already exists" errors
                        if (strpos($e->getMessage(), 'already exists') === false) {
                            throw $e;
                        }
                    }
                }
            }
            echo "<p style='color: green;'>✓ Nozzles table migration executed</p>\n";
            $executed[] = 'add_nozzles_table.sql';
        } else {
            $errors[] = "File not found: $sql_file";
        }
    } else {
        echo "<p style='color: blue;'>ℹ Nozzles table already exists</p>\n";
    }
    
    // Migration 2: Add pump_id to sales
    echo "<h3>2. Adding pump_id to sales table...</h3>\n";
    $result = $pdo->query("SELECT 1 FROM information_schema.columns WHERE table_schema='petron_pos_db_secure' AND table_name='sales' AND column_name='pump_id'");
    if (!$result->fetch()) {
        $sql_file = __DIR__ . '/../sql/add_pump_id_to_sales.sql';
        if (file_exists($sql_file)) {
            $sql = file_get_contents($sql_file);
            foreach (explode(';', $sql) as $statement) {
                $statement = trim($statement);
                if (!empty($statement)) {
                    try {
                        $pdo->exec($statement);
                    } catch (Exception $e) {
                        if (strpos($e->getMessage(), 'already exists') === false && 
                            strpos($e->getMessage(), 'Duplicate') === false) {
                            throw $e;
                        }
                    }
                }
            }
            echo "<p style='color: green;'>✓ pump_id added to sales table</p>\n";
            $executed[] = 'add_pump_id_to_sales.sql';
        } else {
            $errors[] = "File not found: $sql_file";
        }
    } else {
        echo "<p style='color: blue;'>ℹ pump_id column already exists in sales</p>\n";
    }
    
    // Migration 3: Add pump_id and nozzle_id to sale_items
    echo "<h3>3. Adding pump_id and nozzle_id to sale_items table...</h3>\n";
    $result = $pdo->query("SELECT 1 FROM information_schema.columns WHERE table_schema='petron_pos_db_secure' AND table_name='sale_items' AND column_name='pump_id'");
    $has_pump = $result->fetch() ? true : false;
    
    $result = $pdo->query("SELECT 1 FROM information_schema.columns WHERE table_schema='petron_pos_db_secure' AND table_name='sale_items' AND column_name='nozzle_id'");
    $has_nozzle = $result->fetch() ? true : false;
    
    if (!$has_pump || !$has_nozzle) {
        $sql_file = __DIR__ . '/../sql/add_pump_tracking_to_sale_items.sql';
        if (file_exists($sql_file)) {
            $sql = file_get_contents($sql_file);
            foreach (explode(';', $sql) as $statement) {
                $statement = trim($statement);
                if (!empty($statement)) {
                    try {
                        $pdo->exec($statement);
                    } catch (Exception $e) {
                        if (strpos($e->getMessage(), 'already exists') === false &&
                            strpos($e->getMessage(), 'Duplicate') === false) {
                            throw $e;
                        }
                    }
                }
            }
            echo "<p style='color: green;'>✓ pump_id and nozzle_id added to sale_items</p>\n";
            $executed[] = 'add_pump_tracking_to_sale_items.sql';
        } else {
            $errors[] = "File not found: $sql_file";
        }
    } else {
        echo "<p style='color: blue;'>ℹ Columns already exist in sale_items</p>\n";
    }
    
    // Summary
    echo "<hr>\n";
    echo "<h3>Migration Summary</h3>\n";
    if (!empty($executed)) {
        echo "<p><strong>Executed Migrations:</strong></p>\n";
        echo "<ul>\n";
        foreach ($executed as $migration) {
            echo "<li>$migration</li>\n";
        }
        echo "</ul>\n";
    }
    
    if (!empty($errors)) {
        echo "<p><strong style='color: red;'>Errors:</strong></p>\n";
        echo "<ul style='color: red;'>\n";
        foreach ($errors as $error) {
            echo "<li>$error</li>\n";
        }
        echo "</ul>\n";
    } else {
        echo "<p style='color: green;'><strong>✓ All migrations completed successfully!</strong></p>\n";
        echo "<p><a href='verify_pump_migration.php'>View Verification Report</a></p>\n";
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'><strong>Error:</strong> " . htmlspecialchars($e->getMessage()) . "</p>\n";
    echo "<p>Please check the SQL syntax and try again.</p>\n";
}

?>
