<?php
/**
 * Security & Validation Enforcement Layer
 * Implements strict RBAC, soft delete policy, and audit logging
 */

require_once __DIR__ . '/lib.php';

class SecurityValidator {
    
    private $pdo;
    private $user;
    private $station_id;
    
    public function __construct($pdo, $user, $station_id) {
        $this->pdo = $pdo;
        $this->user = $user;
        $this->station_id = $station_id;
    }
    
    /**
     * Enforce RBAC on Operation
     * Throws exception if user lacks required role
     */
    public function enforceRole($required_roles) {
        $user_role = role_key($this->user['role'] ?? 'staff');
        
        if (!is_array($required_roles)) {
            $required_roles = [$required_roles];
        }
        
        if (!in_array($user_role, $required_roles)) {
            throw new Exception(
                sprintf('Insufficient permissions. Required: %s, Your role: %s',
                    implode(' or ', $required_roles),
                    $user_role
                )
            );
        }
    }
    
    /**
     * Verify Password (for sensitive operations)
     * Used when admin needs to confirm with manager password
     */
    public function verifyPassword($password, $target_user_id = null) {
        $target_id = $target_user_id ?: $this->user['id'];
        
        $stmt = $this->pdo->prepare("SELECT password FROM users WHERE id = ?");
        $stmt->execute([$target_id]);
        $stored_hash = $stmt->fetchColumn();
        
        if (!$stored_hash) {
            throw new Exception('User not found');
        }
        
        if (!password_verify($password, $stored_hash)) {
            throw new Exception('Invalid password');
        }
        
        return true;
    }
    
    /**
     * Check Edit Permission on Resource
     * Staff cannot edit after manager approval
     * Nobody can edit finalized records
     */
    public function canEdit($resource_table, $resource_id) {
        $user_role = role_key($this->user['role'] ?? 'staff');
        
        // Query resource status
        $stmt = $this->pdo->prepare("
            SELECT status, staff_editable, billing_locked, is_locked 
            FROM {$resource_table}
            WHERE id = ? AND station_id = ?
        ");
        $stmt->execute([$resource_id, $this->station_id]);
        $resource = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$resource) {
            throw new Exception('Resource not found');
        }
        
        // Nobody edits locked/finalized records
        if ($resource['is_locked'] || $resource['billing_locked']) {
            throw new Exception('This record is finalized and cannot be edited');
        }
        
        // Staff cannot edit after approval
        if ($user_role === 'staff' && !$resource['staff_editable']) {
            throw new Exception('Staff cannot edit this record after manager approval');
        }
        
        return true;
    }
    
    /**
     * Soft Delete Record
     * Mark as deleted instead of permanent removal
     */
    public function softDelete($table, $resource_id) {
        try {
            $stmt = $this->pdo->prepare("
                UPDATE {$table}
                SET is_deleted = 1,
                    deleted_at = NOW()
                WHERE id = ? AND station_id = ?
            ");
            $stmt->execute([$resource_id, $this->station_id]);
            
            if ($stmt->rowCount() === 0) {
                throw new Exception('Resource not found or already deleted');
            }
            
            log_activity(
                $this->pdo,
                $this->user['id'],
                'Resource Soft Deleted',
                sprintf('%s ID %d marked as deleted', $table, $resource_id)
            );
            
            return true;
            
        } catch (Exception $e) {
            throw $e;
        }
    }
    
    /**
     * Check Inventory Sufficiency
     * Before deducting, verify stock is available
     */
    public function checkInventorySufficiency($product_id, $quantity) {
        $stmt = $this->pdo->prepare("
            SELECT stock_level FROM station_inventory
            WHERE station_id = ? AND product_id = ?
        ");
        $stmt->execute([$this->station_id, $product_id]);
        $inventory = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$inventory) {
            throw new Exception('Product not found in inventory');
        }
        
        if ($inventory['stock_level'] < $quantity) {
            throw new Exception(
                sprintf('Insufficient inventory. Need %d but only %.2f available',
                    $quantity,
                    $inventory['stock_level']
                )
            );
        }
        
        return true;
    }
    
    /**
     * Verify Station Access
     * User must belong to accessed station (unless Super Admin)
     */
    public function verifyStationAccess($target_station_id) {
        $user_role = role_key($this->user['role'] ?? 'staff');
        
        // Super Admin can access any station
        if ($user_role === 'superadmin') {
            return true;
        }
        
        // Others must belong to the station
        if ($this->user['station_id'] != $target_station_id) {
            throw new Exception('Access denied to this station');
        }
        
        return true;
    }
    
    /**
     * Validate Numeric Range
     */
    public function validateRange($value, $min, $max, $field_name) {
        if ($value < $min || $value > $max) {
            throw new Exception(
                sprintf('%s must be between %.2f and %.2f', $field_name, $min, $max)
            );
        }
    }
    
    /**
     * Validate Required Fields
     */
    public function validateRequired($data, $required_fields) {
        foreach ($required_fields as $field) {
            if (empty($data[$field])) {
                throw new Exception("Missing required field: {$field}");
            }
        }
    }
    
    /**
     * Get Active Records (Exclude soft-deleted)
     */
    public function getActive($table, $where = [], $order_by = 'id DESC') {
        $sql = "SELECT * FROM {$table} WHERE is_deleted = 0";
        $params = [];
        
        foreach ($where as $field => $value) {
            $sql .= " AND {$field} = ?";
            $params[] = $value;
        }
        
        $sql .= " ORDER BY {$order_by}";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Create Audit Trail Entry
     * Log all critical operations
     */
    public function auditLog($action, $resource_type, $resource_id, $details = null) {
        try {
            $role = role_key($this->user['role'] ?? 'staff');
            
            $stmt = $this->pdo->prepare("
                INSERT INTO audit_log
                (user_id, user_role, action, resource_type, resource_id, details, created_at)
                VALUES (?, ?, ?, ?, ?, ?, NOW())
            ");
            
            $stmt->execute([
                $this->user['id'],
                $role,
                $action,
                $resource_type,
                $resource_id,
                $details
            ]);
            
            return true;
            
        } catch (Exception $e) {
            error_log("Audit log failed: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Check Workflow Status Progression
     * Ensure status transitions are valid
     */
    public function validateStatusTransition($current_status, $new_status, $allowed_transitions) {
        if (!isset($allowed_transitions[$current_status])) {
            throw new Exception("Invalid current status: {$current_status}");
        }
        
        if (!in_array($new_status, $allowed_transitions[$current_status])) {
            throw new Exception(
                sprintf("Cannot transition from %s to %s", $current_status, $new_status)
            );
        }
        
        return true;
    }
}

// Define valid workflow transitions
const JOB_ORDER_TRANSITIONS = [
    'Pending' => ['Approved', 'Rejected'],
    'Approved' => ['In Progress', 'Rejected'],
    'Rejected' => ['Pending'],
    'In Progress' => ['Completed'],
    'Completed' => ['Archived'],
    'Archived' => []
];

const REPORT_TRANSITIONS = [
    'Pending Verification' => ['Verified', 'Rejected'],
    'Verified' => ['Finalized'],
    'Rejected' => ['Pending Verification'],
    'Finalized' => ['Archived'],
    'Archived' => []
];

const RECEIPT_TRANSITIONS = [
    'Pending Confirmation' => ['Confirmed', 'Rejected'],
    'Confirmed' => ['Archived'],
    'Rejected' => ['Pending Confirmation'],
    'Archived' => []
];
