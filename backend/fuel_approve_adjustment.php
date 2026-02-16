<?php
/**
 * Backend Modal: Fuel Adjustment Approval
 * Allows managers to approve fuel adjustments recorded by staff
 */

require_once __DIR__ . '/../lib.php';
require_once __DIR__ . '/../public/db_connect.php';

// Check if user is logged in and has manager role
require_login();
$me = current_user();

if (!in_array($me['role'], ['manager', 'admin', 'superadmin'])) {
    echo '<div class="alert alert-danger">Access denied. Only managers, admins, or superadmins can approve adjustments. (Role: '.htmlspecialchars($me['role']).')</div>';
}

$id = $_GET['id'] ?? 0;

if (!$id) {
    echo '<div class="alert alert-danger">Invalid adjustment ID.</div>';
    exit;
}

// Fetch adjustment details
try {
    $stmt = $pdo->prepare("
        SELECT a.*, u.name as staff_name, ap.name as approver_name
        FROM fuel_adjustments a 
        LEFT JOIN users u ON a.user_id = u.id 
        LEFT JOIN users ap ON a.approved_by = ap.id 
        WHERE a.id = ? AND a.station_id = ?
    ");
    $stmt->execute([$id, user_station_id()]);
    $adjustment = $stmt->fetch();
    
    if (!$adjustment) {
        echo '<div class="alert alert-danger">Adjustment not found or access denied.</div>';
        exit;
    }
    
    // Check if adjustment is already processed
    if ($adjustment['status'] !== 'Pending') {
        echo '<div class="alert alert-warning">This adjustment has already been ' . strtolower($adjustment['status']) . '.</div>';
        exit;
    }
    
} catch (Exception $e) {
    echo '<div class="alert alert-danger">Database error: ' . htmlspecialchars($e->getMessage()) . '</div>';
    exit;
}

// Calculate impact
$impact_color = $adjustment['adjustment_type'] === 'Loss' ? 'danger' : 'success';
$impact_icon = $adjustment['adjustment_type'] === 'Loss' ? 'minus-circle' : 'plus-circle';
$impact_sign = $adjustment['adjustment_type'] === 'Loss' ? '-' : '+';
?>

<div class="modal-dialog modal-lg">
    <div class="modal-content">
        <div class="modal-header">
            <h5 class="modal-title">📋 Approve Fuel Adjustment</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        
        <div class="modal-body">
            <!-- Adjustment Details -->
            <div class="card mb-3">
                <div class="card-header">
                    <strong>📊 Adjustment Information</strong>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <p><strong>Date:</strong> <?php echo date('M d, Y', strtotime($adjustment['adjustment_date'])); ?></p>
                            <p><strong>Fuel Type:</strong> <span class="badge bg-primary"><?php echo htmlspecialchars($adjustment['fuel_type']); ?></span></p>
                            <p><strong>Type:</strong> 
                                <span class="badge bg-<?php echo $impact_color; ?>">
                                    <i class="fas fa-<?php echo $impact_icon; ?>"></i> <?php echo $adjustment['adjustment_type']; ?>
                                </span>
                            </p>
                        </div>
                        <div class="col-md-6">
                            <p><strong>Staff:</strong> <?php echo htmlspecialchars($adjustment['staff_name']); ?></p>
                            <p><strong>Recorded:</strong> <?php echo date('M d, Y H:i', strtotime($adjustment['created_at'])); ?></p>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Adjustment Volume & Reason -->
            <div class="card mb-3">
                <div class="card-header">
                    <strong>⛽ Adjustment Details</strong>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="metric text-center">
                                <div class="metric-value text-<?php echo $impact_color; ?>">
                                    <?php echo $impact_sign . number_format($adjustment['liters'], 2); ?> Liters
                                </div>
                                <div class="metric-label">Volume Adjustment</div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="metric">
                                <div class="metric-label">REASON</div>
                                <div class="metric-reason"><?php echo htmlspecialchars($adjustment['reason']); ?></div>
                            </div>
                        </div>
                    </div>
                    
                    <?php if ($adjustment['notes']): ?>
                    <div class="mt-3">
                        <strong>Staff Notes:</strong>
                        <div class="border rounded p-3 bg-light">
                            <?php echo nl2br(htmlspecialchars($adjustment['notes'])); ?>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Impact Analysis -->
            <div class="card mb-3">
                <div class="card-header">
                    <strong>📈 Impact Analysis</strong>
                </div>
                <div class="card-body">
                    <?php
                    // Get current inventory for this fuel type
                    try {
                        $stmt = $pdo->prepare("
                            SELECT p.name, i.stock_level, i.capacity, i.unit
                            FROM inventory i
                            JOIN products p ON i.product_id = p.id
                            WHERE i.station_id = ? AND p.name LIKE ?
                            LIMIT 1
                        ");
                        $stmt->execute([user_station_id(), '%' . $adjustment['fuel_type'] . '%']);
                        $inventory = $stmt->fetch();
                        
                        if ($inventory) {
                            $new_stock = $adjustment['adjustment_type'] === 'Loss' 
                                ? $inventory['stock_level'] - $adjustment['liters']
                                : $inventory['stock_level'] + $adjustment['liters'];
                            $impact_percent = ($adjustment['liters'] / $inventory['stock_level']) * 100;
                    ?>
                    <div class="row">
                        <div class="col-md-4">
                            <div class="metric text-center">
                                <div class="metric-value"><?php echo number_format($inventory['stock_level'], 2); ?></div>
                                <div class="metric-label">Current Stock</div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="metric text-center">
                                <div class="metric-value text-<?php echo $new_stock >= 0 ? 'success' : 'danger'; ?>">
                                    <?php echo number_format($new_stock, 2); ?>
                                </div>
                                <div class="metric-label">Stock After Adjustment</div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="metric text-center">
                                <div class="metric-value text-<?php echo $impact_color; ?>">
                                    <?php echo number_format($impact_percent, 1); ?>%
                                </div>
                                <div class="metric-label">Impact on Current Stock</div>
                            </div>
                        </div>
                    </div>
                    
                    <?php
                            if ($new_stock < 0) {
                                echo '<div class="alert alert-danger mt-3">';
                                echo '<i class="fas fa-exclamation-triangle"></i> ';
                                echo '<strong>Warning:</strong> This adjustment would result in negative stock!';
                                echo '</div>';
                            } else if ($new_stock < $inventory['stock_level'] * 0.1) {
                                echo '<div class="alert alert-warning mt-3">';
                                echo '<i class="fas fa-exclamation-circle"></i> ';
                                echo '<strong>Low Stock Warning:</strong> Remaining stock will be very low after this adjustment.';
                                echo '</div>';
                            }
                        } else {
                            echo '<div class="alert alert-info">No inventory record found for ' . htmlspecialchars($adjustment['fuel_type']) . '</div>';
                        }
                    } catch (Exception $e) {
                        echo '<div class="alert alert-warning">Could not calculate inventory impact</div>';
                    }
                    ?>
                </div>
            </div>
            
            <!-- Approval Form -->
            <form id="approveAdjustmentForm" method="POST" action="../backend/fuel_process_verification.php">
                <input type="hidden" name="action" value="approve_adjustment">
                <input type="hidden" name="id" value="<?php echo $id; ?>">
                
                <div class="card">
                    <div class="card-header">
                        <strong>✅ Manager Approval</strong>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label class="form-label"><strong>Approval Status *</strong></label>
                            <div class="d-flex gap-3">
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="status" id="statusApproved" value="Approved" required>
                                    <label class="form-check-label text-success" for="statusApproved">
                                        <i class="fas fa-check-circle"></i> Approved
                                    </label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="status" id="statusRejected" value="Rejected" required>
                                    <label class="form-check-label text-danger" for="statusRejected">
                                        <i class="fas fa-times-circle"></i> Rejected
                                    </label>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="adjustedAmount" class="form-label">Approved Amount (Liters)</label>
                                    <input type="number" step="0.01" class="form-control" id="adjustedAmount" name="approved_liters" 
                                           value="<?php echo $adjustment['liters']; ?>" min="0">
                                    <small class="form-text text-muted">You can adjust the amount if needed</small>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="priority" class="form-label">Priority Level</label>
                                    <select class="form-control" id="priority" name="priority">
                                        <option value="Normal">Normal</option>
                                        <option value="High">High - Requires immediate attention</option>
                                        <option value="Critical">Critical - Emergency adjustment</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="approvalNotes" class="form-label">Manager Notes</label>
                            <textarea class="form-control" id="approvalNotes" name="notes" rows="3" 
                                      placeholder="Enter approval notes, additional context, or reasons for rejection..."></textarea>
                        </div>
                        
                        <div id="rejectionReason" style="display: none;" class="mb-3">
                            <label for="rejectionSelect" class="form-label text-danger">Reason for Rejection *</label>
                            <select class="form-control" id="rejectionSelect" name="rejection_reason">
                                <option value="">Select reason...</option>
                                <option value="Insufficient Documentation">Insufficient documentation or justification</option>
                                <option value="Incorrect Amount">Amount seems incorrect or excessive</option>
                                <option value="Wrong Procedure">Proper procedure not followed</option>
                                <option value="Duplicate Entry">Appears to be duplicate adjustment</option>
                                <option value="Requires Investigation">Requires further investigation</option>
                                <option value="Other">Other (specify in notes)</option>
                            </select>
                        </div>
                    </div>
                </div>
            </form>
        </div>
        
        <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                <i class="fas fa-times"></i> Cancel
            </button>
            <button type="submit" form="approveAdjustmentForm" class="btn btn-success" id="submitBtn">
                <i class="fas fa-check"></i> Approve Adjustment
            </button>
        </div>
    </div>
</div>

<style>
.metric {
    padding: 15px;
    border-radius: 8px;
    background: #f8f9fa;
    margin-bottom: 10px;
}
.metric-value {
    font-size: 20px;
    font-weight: bold;
    margin-bottom: 5px;
}
.metric-label {
    font-size: 11px;
    color: #6c757d;
    text-transform: uppercase;
    font-weight: 600;
}
.metric-reason {
    font-size: 14px;
    font-weight: 500;
    color: #495057;
    margin-top: 5px;
}
</style>

<script>
// Show/hide rejection reason field
document.querySelectorAll('input[name="status"]').forEach(radio => {
    radio.addEventListener('change', function() {
        const rejectionDiv = document.getElementById('rejectionReason');
        const rejectionSelect = document.getElementById('rejectionSelect');
        const submitBtn = document.getElementById('submitBtn');
        
        if (this.value === 'Rejected') {
            rejectionDiv.style.display = 'block';
            rejectionSelect.required = true;
            submitBtn.innerHTML = '<i class="fas fa-times"></i> Reject Adjustment';
            submitBtn.className = 'btn btn-danger';
        } else {
            rejectionDiv.style.display = 'none';
            rejectionSelect.required = false;
            rejectionSelect.value = '';
            submitBtn.innerHTML = '<i class="fas fa-check"></i> Approve Adjustment';
            submitBtn.className = 'btn btn-success';
        }
    });
});

// Form submission
document.getElementById('approveAdjustmentForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const status = document.querySelector('input[name="status"]:checked')?.value;
    const rejectionReason = document.getElementById('rejectionSelect').value;
    const adjustedAmount = document.getElementById('adjustedAmount').value;
    
    if (status === 'Rejected' && !rejectionReason) {
        alert('Please select a reason for rejection.');
        return;
    }
    
    if (status === 'Approved' && (!adjustedAmount || adjustedAmount <= 0)) {
        alert('Please enter a valid amount for approved liters.');
        return;
    }
    
    // Confirm action
    const action = status === 'Approved' ? 'approve' : 'reject';
    const confirmMsg = `Are you sure you want to ${action} this fuel adjustment?`;
    
    if (confirm(confirmMsg)) {
        const formData = new FormData(this);
        
        fetch('../backend/fuel_process_verification.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert(`Adjustment ${action}ed successfully!`);
                location.reload();
            } else {
                alert('Error: ' + (data.message || 'Unknown error occurred'));
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Network error occurred. Please try again.');
        });
    }
});
</script>