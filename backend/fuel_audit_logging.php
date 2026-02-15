<?php
/**
 * FUEL INVENTORY AUDIT LOGGING
 * 
 * Provides comprehensive audit trail functions for fuel inventory operations
 * Integrates with existing activity_logs and fuel_inventory_logs
 * Ensures immutability and complete traceability
 */

/**
 * Log a fuel inventory action to both activity_logs and fuel_inventory_logs
 * This ensures dual logging for complete audit trail
 * 
 * @param PDO $pdo Database connection
 * @param int $user_id User performing the action
 * @param string $action_type Type of action (see fuel_inventory_logs enum)
 * @param string $reference_type fuel_delivery, fuel_daily_reading, or fuel_adjustment
 * @param int $reference_id ID of the source transaction
 * @param int $station_id Station ID
 * @param int $product_id Fuel product ID
 * @param array $details Additional details
 */
function log_fuel_inventory_action($pdo, $user_id, $action_type, $reference_type, $reference_id, $station_id, $product_id, $details = []) {
    try {
        // Build activity log entry
        $action_label = str_replace('_', ' ', ucwords($action_type, '_'));
        $details_str = json_encode($details);
        
        // Log to activity_logs
        $stmt = $pdo->prepare("
            INSERT INTO activity_logs (
                user_id, action, description, details, 
                page_id, created_at, ip_address, user_agent
            ) VALUES (?, ?, ?, ?, 'fuel_management', NOW(), ?, ?)
        ");
        
        $stmt->execute([
            $user_id,
            $action_label,
            "Fuel {$action_label}: {$reference_type} #{$reference_id}",
            $details_str,
            $_SERVER['REMOTE_ADDR'] ?? 'unknown',
            $_SERVER['HTTP_USER_AGENT'] ?? 'unknown'
        ]);
        
        return true;
    } catch (Exception $e) {
        error_log("Error logging fuel inventory action: " . $e->getMessage());
        return false;
    }
}

/**
 * Get complete audit trail for a fuel inventory transaction
 * Shows all steps from initial recording to final approval
 * 
 * @param PDO $pdo Database connection
 * @param string $reference_type fuel_delivery, fuel_daily_reading, or fuel_adjustment
 * @param int $reference_id ID of the source transaction
 * @return array Complete audit trail with all actions
 */
function get_fuel_audit_trail($pdo, $reference_type, $reference_id) {
    try {
        $stmt = $pdo->prepare("
            SELECT 
                fil.id,
                fil.action,
                fil.status,
                fil.quantity_before,
                fil.quantity_after,
                fil.quantity_change,
                fil.notes,
                fil.approval_reason,
                u1.name as initiated_by_name,
                u2.name as approved_by_name,
                fil.created_at,
                fil.updated_at
            FROM fuel_inventory_logs fil
            LEFT JOIN users u1 ON fil.user_id = u1.id
            LEFT JOIN users u2 ON fil.approved_by = u2.id
            WHERE fil.reference_type = ? AND fil.reference_id = ?
            ORDER BY fil.created_at ASC
        ");
        
        $stmt->execute([$reference_type, $reference_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        error_log("Error getting fuel audit trail: " . $e->getMessage());
        return [];
    }
}

/**
 * Get fuel inventory modification report
 * Shows all stock changes for a station in a date range
 * Useful for reconciliation and variance analysis
 * 
 * @param PDO $pdo Database connection
 * @param int $station_id Station ID
 * @param string $start_date YYYY-MM-DD
 * @param string $end_date YYYY-MM-DD
 * @return array All stock modifications in date range
 */
function get_fuel_stock_modifications($pdo, $station_id, $start_date, $end_date) {
    try {
        $stmt = $pdo->prepare("
            SELECT 
                fil.id,
                fil.action,
                fil.status,
                fil.quantity_before,
                fil.quantity_after,
                fil.quantity_change,
                p.name as fuel_name,
                u1.name as initiated_by_name,
                u2.name as approved_by_name,
                fil.reference_type,
                fil.reference_id,
                fil.created_at,
                fil.updated_at
            FROM fuel_inventory_logs fil
            JOIN products p ON fil.product_id = p.id
            LEFT JOIN users u1 ON fil.user_id = u1.id
            LEFT JOIN users u2 ON fil.approved_by = u2.id
            WHERE fil.station_id = ? 
            AND DATE(fil.created_at) BETWEEN ? AND ?
            ORDER BY fil.created_at DESC
        ");
        
        $stmt->execute([$station_id, $start_date, $end_date]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        error_log("Error getting fuel stock modifications: " . $e->getMessage());
        return [];
    }
}

/**
 * Get user actions for fuel operations
 * Shows what actions a specific user has performed
 * Useful for user activity tracking and compliance
 * 
 * @param PDO $pdo Database connection
 * @param int $user_id User ID
 * @param string $action_type Optional: filter by specific action type
 * @param int $limit Number of records to return
 * @return array User's fuel-related actions
 */
function get_user_fuel_actions($pdo, $user_id, $action_type = null, $limit = 50) {
    try {
        $query = "
            SELECT 
                fil.id,
                fil.action,
                fil.status,
                fil.quantity_change,
                p.name as fuel_name,
                s.name as station_name,
                fil.reference_type,
                fil.reference_id,
                fil.created_at
            FROM fuel_inventory_logs fil
            JOIN products p ON fil.product_id = p.id
            JOIN stations s ON fil.station_id = s.id
            WHERE fil.user_id = ?
        ";
        
        $params = [$user_id];
        
        if ($action_type) {
            $query .= " AND fil.action = ?";
            $params[] = $action_type;
        }
        
        $query .= " ORDER BY fil.created_at DESC LIMIT ?";
        $params[] = $limit;
        
        $stmt = $pdo->prepare($query);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        error_log("Error getting user fuel actions: " . $e->getMessage());
        return [];
    }
}

/**
 * Generate fuel inventory audit report
 * Comprehensive report for compliance and verification
 * 
 * @param PDO $pdo Database connection
 * @param int $station_id Station ID
 * @param string $date YYYY-MM-DD
 * @return array Audit report with summary and details
 */
function generate_fuel_audit_report($pdo, $station_id, $date) {
    try {
        // Get all modifications for the date
        $stmt = $pdo->prepare("
            SELECT 
                fil.action,
                COUNT(*) as count,
                SUM(fil.quantity_change) as total_change,
                MIN(fil.created_at) as first_action,
                MAX(fil.created_at) as last_action
            FROM fuel_inventory_logs fil
            WHERE fil.station_id = ? AND DATE(fil.created_at) = ?
            GROUP BY fil.action
        ");
        
        $stmt->execute([$station_id, $date]);
        $summary = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Get detailed modifications
        $stmt = $pdo->prepare("
            SELECT 
                fil.*,
                p.name as fuel_name,
                u1.name as initiated_by_name,
                u2.name as approved_by_name
            FROM fuel_inventory_logs fil
            JOIN products p ON fil.product_id = p.id
            LEFT JOIN users u1 ON fil.user_id = u1.id
            LEFT JOIN users u2 ON fil.approved_by = u2.id
            WHERE fil.station_id = ? AND DATE(fil.created_at) = ?
            ORDER BY fil.created_at ASC
        ");
        
        $stmt->execute([$station_id, $date]);
        $details = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        return [
            'station_id' => $station_id,
            'date' => $date,
            'summary' => $summary,
            'details' => $details,
            'total_records' => count($details)
        ];
    } catch (Exception $e) {
        error_log("Error generating fuel audit report: " . $e->getMessage());
        return [];
    }
}

/**
 * Verify audit trail integrity
 * Checks that all stock changes are properly logged
 * Can be run periodically to ensure no unauthorized changes
 * 
 * @param PDO $pdo Database connection
 * @param int $station_id Station ID
 * @return array Integrity check results
 */
function verify_fuel_audit_integrity($pdo, $station_id) {
    try {
        $results = [];
        
        // Check 1: All deliveries have corresponding logs
        $stmt = $pdo->prepare("
            SELECT fd.id, fd.status
            FROM fuel_deliveries fd
            LEFT JOIN fuel_inventory_logs fil ON fil.reference_type = 'fuel_delivery' AND fil.reference_id = fd.id
            WHERE fd.station_id = ? AND fd.status = 'Finalized' AND fil.id IS NULL
        ");
        
        $stmt->execute([$station_id]);
        $missing_delivery_logs = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $results['missing_delivery_logs'] = count($missing_delivery_logs);
        
        // Check 2: All approved readings have corresponding logs
        $stmt = $pdo->prepare("
            SELECT fdr.id
            FROM fuel_daily_readings fdr
            LEFT JOIN fuel_inventory_logs fil ON fil.reference_type = 'fuel_daily_reading' AND fil.reference_id = fdr.id
            WHERE fdr.station_id = ? AND fdr.status = 'Approved' AND fil.id IS NULL
        ");
        
        $stmt->execute([$station_id]);
        $missing_reading_logs = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $results['missing_reading_logs'] = count($missing_reading_logs);
        
        // Check 3: All approved adjustments have corresponding logs
        $stmt = $pdo->prepare("
            SELECT fa.id
            FROM fuel_adjustments fa
            LEFT JOIN fuel_inventory_logs fil ON fil.reference_type = 'fuel_adjustment' AND fil.reference_id = fa.id
            WHERE fa.station_id = ? AND fa.status = 'Approved' AND fil.id IS NULL
        ");
        
        $stmt->execute([$station_id]);
        $missing_adjustment_logs = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $results['missing_adjustment_logs'] = count($missing_adjustment_logs);
        
        $results['integrity_ok'] = (
            $results['missing_delivery_logs'] == 0 &&
            $results['missing_reading_logs'] == 0 &&
            $results['missing_adjustment_logs'] == 0
        );
        
        return $results;
    } catch (Exception $e) {
        error_log("Error verifying audit integrity: " . $e->getMessage());
        return ['error' => $e->getMessage()];
    }
}
?>
