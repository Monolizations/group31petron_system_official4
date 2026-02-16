<?php
/**
 * Backend Processing: Fuel Verification Actions
 * Handles all manager/admin verification and approval actions
 */

require_once __DIR__ . '/../lib.php';
require_once __DIR__ . '/../public/db_connect.php';

// Set JSON response header
header('Content-Type: application/json');

// Check if user is logged in
require_login();
$me = current_user();

$response = ['success' => false, 'message' => ''];

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Invalid request method');
    }

    $action = $_POST['action'] ?? '';
    
    switch ($action) {
        case 'verify_reading':
            handleVerifyReading();
            break;
            
        case 'verify_delivery':
            handleVerifyDelivery();
            break;
            
        case 'approve_adjustment':
            handleApproveAdjustment();
            break;
            
        case 'investigate_variance':
            handleInvestigateVariance();
            break;
            
        default:
            throw new Exception('Invalid action specified');
    }
    
} catch (Exception $e) {
    $response['message'] = $e->getMessage();
    http_response_code(400);
}

echo json_encode($response);
exit;

/**
 * Handle pump reading verification by manager
 */
function handleVerifyReading() {
    global $pdo, $me, $response;
    
    // Check role permission
    if (!in_array(strtolower($me['role']), ['manager', 'admin', 'superadmin'])) {
        throw new Exception('Only managers, admins, or superadmins can verify readings');
    }
    
    $id = $_POST['id'] ?? 0;
    $status = $_POST['status'] ?? '';
    $notes = $_POST['notes'] ?? '';
    $rejection_reason = $_POST['rejection_reason'] ?? '';
    
    if (!$id || !in_array($status, ['Verified', 'Rejected'])) {
        throw new Exception('Invalid parameters provided');
    }
    
    if ($status === 'Rejected' && !$rejection_reason) {
        throw new Exception('Rejection reason is required');
    }
    
    // Build notes
    $manager_notes = "[Manager Verification by {$me['name']}]\n";
    if ($status === 'Rejected') {
        $manager_notes .= "REJECTED: $rejection_reason\n";
    }
    if ($notes) {
        $manager_notes .= "Notes: $notes\n";
    }
    $manager_notes .= "Date: " . date('Y-m-d H:i:s') . "\n";
    
    // Update reading
    $pdo->beginTransaction();
    
    try {
        // Update the reading status
        $stmt = $pdo->prepare("
            UPDATE fuel_daily_readings 
            SET status = ?, notes = CONCAT(COALESCE(notes,''), ?, '\n')
            WHERE id = ? AND station_id = ? AND status = 'Pending'
        ");
        $stmt->execute([$status, $manager_notes, $id, user_station_id()]);
        
        if ($stmt->rowCount() === 0) {
            throw new Exception('Reading not found or already processed');
        }
        
        // Log the activity
        log_activity($pdo, $me['id'], 
            'Manager ' . ($status === 'Verified' ? 'Verified' : 'Rejected') . ' Reading', 
            "Reading ID: $id - $status" . ($rejection_reason ? " ($rejection_reason)" : ''),
            'fuel_management'
        );
        
        $pdo->commit();
        
        $response['success'] = true;
        $response['message'] = "Reading $status successfully";
        
    } catch (Exception $e) {
        $pdo->rollBack();
        throw $e;
    }
}

/**
 * Handle delivery verification by manager
 */
function handleVerifyDelivery() {
    global $pdo, $me, $response;
    
    // Check role permission
    if ($me['role'] !== 'manager') {
        throw new Exception('Only managers can verify deliveries');
    }
    
    $id = $_POST['id'] ?? 0;
    $status = $_POST['status'] ?? '';
    $notes = $_POST['notes'] ?? '';
    $actual_liters = floatval($_POST['actual_liters'] ?? 0);
    $quality = $_POST['quality'] ?? 'Good';
    $rejection_reason = $_POST['rejection_reason'] ?? '';
    
    if (!$id || !in_array($status, ['Verified', 'Rejected'])) {
        throw new Exception('Invalid parameters provided');
    }
    
    if ($status === 'Rejected' && !$rejection_reason) {
        throw new Exception('Rejection reason is required');
    }
    
    if ($status === 'Verified' && $actual_liters <= 0) {
        throw new Exception('Valid actual liters amount is required');
    }
    
    // Build verification notes
    $manager_notes = "[Manager Verification by {$me['name']}]\n";
    if ($status === 'Rejected') {
        $manager_notes .= "REJECTED: $rejection_reason\n";
    } else {
        $manager_notes .= "VERIFIED: Actual amount: $actual_liters liters\n";
        $manager_notes .= "Quality assessment: $quality\n";
    }
    if ($notes) {
        $manager_notes .= "Notes: $notes\n";
    }
    $manager_notes .= "Date: " . date('Y-m-d H:i:s') . "\n";
    
    $pdo->beginTransaction();
    
    try {
        // Update delivery with actual amount and verification details
        $stmt = $pdo->prepare("
            UPDATE fuel_deliveries 
            SET status = ?, verified_by = ?, verified_at = NOW(),
                delivery_liters = ?, 
                notes = CONCAT(COALESCE(notes,''), ?, '\n')
            WHERE id = ? AND station_id = ? AND status = 'Pending'
        ");
        $stmt->execute([$status, $me['id'], 
                       $status === 'Verified' ? $actual_liters : null, 
                       $manager_notes, $id, user_station_id()]);
        
        if ($stmt->rowCount() === 0) {
            throw new Exception('Delivery not found or already processed');
        }
        
        // Log the activity
        log_activity($pdo, $me['id'], 
            'Manager ' . ($status === 'Verified' ? 'Verified' : 'Rejected') . ' Delivery', 
            "Delivery ID: $id - $status" . 
            ($status === 'Verified' ? " ($actual_liters liters)" : '') .
            ($rejection_reason ? " ($rejection_reason)" : ''),
            'fuel_management'
        );
        
        $pdo->commit();
        
        $response['success'] = true;
        $response['message'] = "Delivery $status successfully";
        
    } catch (Exception $e) {
        $pdo->rollBack();
        throw $e;
    }
}

/**
 * Handle adjustment approval by manager
 */
function handleApproveAdjustment() {
    global $pdo, $me, $response;
    
    // Check role permission
    if ($me['role'] !== 'manager') {
        throw new Exception('Only managers can approve adjustments');
    }
    
    $id = $_POST['id'] ?? 0;
    $status = $_POST['status'] ?? '';
    $notes = $_POST['notes'] ?? '';
    $approved_liters = floatval($_POST['approved_liters'] ?? 0);
    $priority = $_POST['priority'] ?? 'Normal';
    $rejection_reason = $_POST['rejection_reason'] ?? '';
    
    if (!$id || !in_array($status, ['Approved', 'Rejected'])) {
        throw new Exception('Invalid parameters provided');
    }
    
    if ($status === 'Rejected' && !$rejection_reason) {
        throw new Exception('Rejection reason is required');
    }
    
    if ($status === 'Approved' && $approved_liters <= 0) {
        throw new Exception('Valid approved amount is required');
    }
    
    // Build approval notes
    $manager_notes = "[Manager Approval by {$me['name']}]\n";
    if ($status === 'Rejected') {
        $manager_notes .= "REJECTED: $rejection_reason\n";
    } else {
        $manager_notes .= "APPROVED: Amount: $approved_liters liters\n";
        $manager_notes .= "Priority: $priority\n";
    }
    if ($notes) {
        $manager_notes .= "Notes: $notes\n";
    }
    $manager_notes .= "Date: " . date('Y-m-d H:i:s') . "\n";
    
    $pdo->beginTransaction();
    
    try {
        // Update adjustment with approved amount and approval details
        $stmt = $pdo->prepare("
            UPDATE fuel_adjustments 
            SET status = ?, approved_by = ?, approved_at = NOW(),
                liters = ?,
                notes = CONCAT(COALESCE(notes,''), ?, '\n')
            WHERE id = ? AND station_id = ? AND status = 'Pending'
        ");
        $stmt->execute([$status, $me['id'], 
                       $status === 'Approved' ? $approved_liters : null,
                       $manager_notes, $id, user_station_id()]);
        
        if ($stmt->rowCount() === 0) {
            throw new Exception('Adjustment not found or already processed');
        }
        
        // Log the activity
        log_activity($pdo, $me['id'], 
            'Manager ' . ($status === 'Approved' ? 'Approved' : 'Rejected') . ' Adjustment', 
            "Adjustment ID: $id - $status" . 
            ($status === 'Approved' ? " ($approved_liters liters, $priority priority)" : '') .
            ($rejection_reason ? " ($rejection_reason)" : ''),
            'fuel_management'
        );
        
        $pdo->commit();
        
        $response['success'] = true;
        $response['message'] = "Adjustment $status successfully";
        
    } catch (Exception $e) {
        $pdo->rollBack();
        throw $e;
    }
}

/**
 * Handle variance investigation by admin
 */
function handleInvestigateVariance() {
    global $pdo, $me, $response;
    
    // Check role permission
    if (!in_array($me['role'], ['admin', 'superadmin'])) {
        throw new Exception('Only administrators can investigate variances');
    }
    
    $id = $_POST['id'] ?? 0;
    $status = $_POST['status'] ?? '';
    $notes = $_POST['notes'] ?? '';
    $root_cause = $_POST['root_cause'] ?? '';
    $priority = $_POST['priority'] ?? 'Normal';
    $corrective_actions = $_POST['corrective_actions'] ?? '';
    
    if (!$id || !in_array($status, ['Under Investigation', 'Resolved'])) {
        throw new Exception('Invalid parameters provided');
    }
    
    if (!$notes) {
        throw new Exception('Investigation notes are required');
    }
    
    // Build investigation notes
    $investigation_notes = "[Investigation by {$me['name']}]\n";
    $investigation_notes .= "Status: $status\n";
    if ($root_cause) {
        $investigation_notes .= "Root Cause: $root_cause\n";
    }
    $investigation_notes .= "Priority: $priority\n";
    $investigation_notes .= "Investigation Notes:\n$notes\n";
    if ($corrective_actions) {
        $investigation_notes .= "Corrective Actions:\n$corrective_actions\n";
    }
    $investigation_notes .= "Date: " . date('Y-m-d H:i:s') . "\n";
    
    $pdo->beginTransaction();
    
    try {
        // Update variance report
        $stmt = $pdo->prepare("
            UPDATE fuel_variance_reports 
            SET status = ?, investigated_by = ?, 
                resolution_notes = ?, updated_at = NOW()
            WHERE id = ?
        ");
        $stmt->execute([$status, $me['id'], $investigation_notes, $id]);
        
        if ($stmt->rowCount() === 0) {
            throw new Exception('Variance report not found');
        }
        
        // Log the activity
        log_activity($pdo, $me['id'], 
            'Variance Investigation Updated', 
            "Variance ID: $id - Status: $status" . 
            ($root_cause ? " (Root Cause: $root_cause)" : ''),
            'fuel_management'
        );
        
        $pdo->commit();
        
        $response['success'] = true;
        $response['message'] = "Variance investigation updated successfully";
        
    } catch (Exception $e) {
        $pdo->rollBack();
        throw $e;
    }
}
?>