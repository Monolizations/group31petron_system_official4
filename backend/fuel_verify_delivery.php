<?php
/**
 * Backend Modal: Fuel Delivery Verification
 * Allows managers to verify fuel deliveries recorded by staff
 */

require_once __DIR__ . '/../lib.php';
require_once __DIR__ . '/../public/db_connect.php';

// Check if user is logged in and has manager role
require_login();
$me = current_user();

if (!in_array($me['role'], ['manager', 'admin', 'superadmin'])) {
    echo '<div class="alert alert-danger">Access denied. Only managers, admins, or superadmins can verify deliveries. (Role: '.htmlspecialchars($me['role']).')</div>';
}

$id = $_GET['id'] ?? 0;

if (!$id) {
    echo '<div class="alert alert-danger">Invalid delivery ID.</div>';
    exit;
}

// Fetch delivery details
try {
    $stmt = $pdo->prepare("
        SELECT d.*, u.name as receiver_name, v.name as verifier_name
        FROM fuel_deliveries d 
        LEFT JOIN users u ON d.received_by = u.id 
        LEFT JOIN users v ON d.verified_by = v.id 
        WHERE d.id = ? AND d.station_id = ?
    ");
    $stmt->execute([$id, user_station_id()]);
    $delivery = $stmt->fetch();
    
    if (!$delivery) {
        echo '<div class="alert alert-danger">Delivery not found or access denied.</div>';
        exit;
    }
    
    // Check if delivery is already verified
    if ($delivery['status'] !== 'Pending') {
        echo '<div class="alert alert-warning">This delivery has already been ' . strtolower($delivery['status']) . '.</div>';
        exit;
    }
    
} catch (Exception $e) {
    echo '<div class="alert alert-danger">Database error: ' . htmlspecialchars($e->getMessage()) . '</div>';
    exit;
}
?>

<div class="modal-dialog modal-lg">
    <div class="modal-content">
        <div class="modal-header">
            <h5 class="modal-title">🚛 Verify Fuel Delivery</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        
        <div class="modal-body">
            <!-- Delivery Details -->
            <div class="card mb-3">
                <div class="card-header">
                    <strong>📋 Delivery Information</strong>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <p><strong>Delivery Date:</strong> <?php echo date('M d, Y', strtotime($delivery['delivery_date'])); ?></p>
                            <p><strong>Fuel Type:</strong> <span class="badge bg-primary"><?php echo htmlspecialchars($delivery['fuel_type']); ?></span></p>
                            <p><strong>Supplier:</strong> <?php echo htmlspecialchars($delivery['supplier']); ?></p>
                            <p><strong>Invoice No:</strong> <?php echo htmlspecialchars($delivery['invoice_no'] ?: 'N/A'); ?></p>
                        </div>
                        <div class="col-md-6">
                            <p><strong>Tanker Number:</strong> <?php echo htmlspecialchars($delivery['tanker_number'] ?: 'N/A'); ?></p>
                            <p><strong>Received By:</strong> <?php echo htmlspecialchars($delivery['receiver_name']); ?></p>
                            <p><strong>Recorded:</strong> <?php echo date('M d, Y H:i', strtotime($delivery['created_at'])); ?></p>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Delivery Volume -->
            <div class="card mb-3">
                <div class="card-header">
                    <strong>⛽ Delivery Volume</strong>
                </div>
                <div class="card-body text-center">
                    <div class="metric">
                        <div class="metric-value text-primary"><?php echo number_format($delivery['delivery_liters'], 2); ?> Liters</div>
                        <div class="metric-label">Total Delivered</div>
                    </div>
                    
                    <?php if ($delivery['notes']): ?>
                    <div class="mt-3 text-start">
                        <strong>Delivery Notes:</strong>
                        <p class="text-muted"><?php echo nl2br(htmlspecialchars($delivery['notes'])); ?></p>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Current Inventory Check -->
            <div class="card mb-3">
                <div class="card-header">
                    <strong>📊 Current Inventory Status</strong>
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
                        $stmt->execute([user_station_id(), '%' . $delivery['fuel_type'] . '%']);
                        $inventory = $stmt->fetch();
                        
                        if ($inventory) {
                            $capacity_percent = ($inventory['stock_level'] / $inventory['capacity']) * 100;
                    ?>
                    <div class="row">
                        <div class="col-md-4">
                            <div class="metric">
                                <div class="metric-value"><?php echo number_format($inventory['stock_level'], 2); ?></div>
                                <div class="metric-label">Current Stock (<?php echo $inventory['unit']; ?>)</div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="metric">
                                <div class="metric-value"><?php echo number_format($inventory['capacity'], 2); ?></div>
                                <div class="metric-label">Tank Capacity (<?php echo $inventory['unit']; ?>)</div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="metric">
                                <div class="metric-value <?php echo $capacity_percent > 90 ? 'text-warning' : 'text-success'; ?>"><?php echo number_format($capacity_percent, 1); ?>%</div>
                                <div class="metric-label">Current Fill Level</div>
                            </div>
                        </div>
                    </div>
                    
                    <?php
                            $new_level = $inventory['stock_level'] + $delivery['delivery_liters'];
                            $new_percent = ($new_level / $inventory['capacity']) * 100;
                            
                            if ($new_level > $inventory['capacity']) {
                                echo '<div class="alert alert-danger mt-3">';
                                echo '<i class="fas fa-exclamation-triangle"></i> ';
                                echo '<strong>Warning:</strong> This delivery would exceed tank capacity! ';
                                echo 'New level would be ' . number_format($new_level, 2) . ' ' . $inventory['unit'];
                                echo ' (' . number_format($new_percent, 1) . '% of capacity)';
                                echo '</div>';
                            } else if ($new_percent > 95) {
                                echo '<div class="alert alert-warning mt-3">';
                                echo '<i class="fas fa-exclamation-circle"></i> ';
                                echo '<strong>Caution:</strong> Tank will be nearly full after delivery ';
                                echo '(' . number_format($new_percent, 1) . '% of capacity)';
                                echo '</div>';
                            }
                        } else {
                            echo '<div class="alert alert-info">No inventory record found for ' . htmlspecialchars($delivery['fuel_type']) . '</div>';
                        }
                    } catch (Exception $e) {
                        echo '<div class="alert alert-warning">Could not retrieve inventory information</div>';
                    }
                    ?>
                </div>
            </div>
            
            <!-- Verification Form -->
            <form id="verifyDeliveryForm" method="POST" action="../backend/fuel_process_verification.php">
                <input type="hidden" name="action" value="verify_delivery">
                <input type="hidden" name="id" value="<?php echo $id; ?>">
                
                <div class="card">
                    <div class="card-header">
                        <strong>✅ Manager Verification</strong>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label class="form-label"><strong>Verification Status *</strong></label>
                            <div class="d-flex gap-3">
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="status" id="statusVerified" value="Verified" required>
                                    <label class="form-check-label text-success" for="statusVerified">
                                        <i class="fas fa-check-circle"></i> Verified
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
                                    <label for="actualLiters" class="form-label">Actual Liters Received</label>
                                    <input type="number" step="0.01" class="form-control" id="actualLiters" name="actual_liters" 
                                           value="<?php echo $delivery['delivery_liters']; ?>" min="0">
                                    <small class="form-text text-muted">Adjust if different from recorded amount</small>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="deliveryQuality" class="form-label">Fuel Quality</label>
                                    <select class="form-control" id="deliveryQuality" name="quality">
                                        <option value="Good">Good - No issues</option>
                                        <option value="Fair">Fair - Minor concerns</option>
                                        <option value="Poor">Poor - Quality issues</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="verificationNotes" class="form-label">Manager Notes</label>
                            <textarea class="form-control" id="verificationNotes" name="notes" rows="3" 
                                      placeholder="Enter verification notes, quality observations, or reasons for rejection..."></textarea>
                        </div>
                        
                        <div id="rejectionReason" style="display: none;" class="mb-3">
                            <label for="rejectionSelect" class="form-label text-danger">Reason for Rejection *</label>
                            <select class="form-control" id="rejectionSelect" name="rejection_reason">
                                <option value="">Select reason...</option>
                                <option value="Quantity Mismatch">Quantity mismatch with invoice</option>
                                <option value="Quality Issues">Fuel quality problems</option>
                                <option value="Documentation Issues">Missing or incorrect documentation</option>
                                <option value="Delivery Issues">Delivery procedure not followed</option>
                                <option value="Tank Overflow">Would exceed tank capacity</option>
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
            <button type="submit" form="verifyDeliveryForm" class="btn btn-success" id="submitBtn">
                <i class="fas fa-check"></i> Verify Delivery
            </button>
        </div>
    </div>
</div>

<style>
.metric {
    padding: 20px;
    border-radius: 8px;
    background: #f8f9fa;
    margin-bottom: 15px;
    text-align: center;
}
.metric-value {
    font-size: 24px;
    font-weight: bold;
    margin-bottom: 5px;
}
.metric-label {
    font-size: 12px;
    color: #6c757d;
    text-transform: uppercase;
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
            submitBtn.innerHTML = '<i class="fas fa-times"></i> Reject Delivery';
            submitBtn.className = 'btn btn-danger';
        } else {
            rejectionDiv.style.display = 'none';
            rejectionSelect.required = false;
            rejectionSelect.value = '';
            submitBtn.innerHTML = '<i class="fas fa-check"></i> Verify Delivery';
            submitBtn.className = 'btn btn-success';
        }
    });
});

// Form submission
document.getElementById('verifyDeliveryForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const status = document.querySelector('input[name="status"]:checked')?.value;
    const rejectionReason = document.getElementById('rejectionSelect').value;
    const actualLiters = document.getElementById('actualLiters').value;
    
    if (status === 'Rejected' && !rejectionReason) {
        alert('Please select a reason for rejection.');
        return;
    }
    
    if (!actualLiters || actualLiters <= 0) {
        alert('Please enter a valid amount for actual liters received.');
        return;
    }
    
    // Confirm action
    const action = status === 'Verified' ? 'verify' : 'reject';
    const confirmMsg = `Are you sure you want to ${action} this fuel delivery?`;
    
    if (confirm(confirmMsg)) {
        const formData = new FormData(this);
        
        fetch('../backend/fuel_process_verification.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert(`Delivery ${action}ed successfully!`);
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