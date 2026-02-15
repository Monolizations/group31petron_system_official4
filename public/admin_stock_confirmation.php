<?php
if (session_status() === PHP_SESSION_NONE) session_start();
$page_id = 'admin_stock_confirmation';
require_once __DIR__ . '/../backend/lib.php';
require_once __DIR__ . '/../public/db_connect.php';
require_login();

$me = current_user();
$station_id = user_station_id();
$role = function_exists('role_key') ? role_key($me['role'] ?? '') : strtolower(trim($me['role'] ?? ''));

// Manager, Admin, or Superadmin only
if (!in_array($role, ['manager', 'admin', 'superadmin'])) {
    header("Location: dashboard.php");
    exit;
}

$msg = '';
$view = $_GET['view'] ?? 'received';
$batch_id = $_GET['batch'] ?? null;

// Handle actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'confirm_stock') {
        $batch_id = (int)($_POST['batch_id'] ?? 0);
        $confirm_items = $_POST['confirm'] ?? []; // Array of item_id => quantity to confirm
        $notes = $_POST['notes'] ?? '';
        
        if (empty($confirm_items) || empty(array_filter($confirm_items, fn($q) => $q > 0))) {
            $msg = "❌ Please select at least one item to confirm.";
        } else {
            try {
                $pdo->beginTransaction();
                
                // Get batch
                $stmt = $pdo->prepare("SELECT * FROM receiving_batches WHERE id = ? AND status = 'received'");
                $stmt->execute([$batch_id]);
                $batch = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if (!$batch) {
                    $msg = "❌ Batch not found or not ready for confirmation.";
                } else {
                    $items_confirmed = 0;
                    $total_quantity = 0;
                    
                    // Process each item
                    foreach ($confirm_items as $item_id => $confirm_qty) {
                        $confirm_qty = (float)$confirm_qty;
                        
                        if ($confirm_qty > 0) {
                            // Get item details
                            $stmt_item = $pdo->prepare("SELECT * FROM received_items WHERE id = ? AND batch_id = ?");
                            $stmt_item->execute([$item_id, $batch_id]);
                            $item = $stmt_item->fetch(PDO::FETCH_ASSOC);
                            
                            if ($item) {
                                // Check if confirming partial or full quantity
                                $original_qty = (float)$item['quantity'];
                                $qty_to_add = min($confirm_qty, $original_qty);
                                
                                // Update item status
                                if ($qty_to_add >= $original_qty) {
                                    // Full confirmation
                                    $stmt_update = $pdo->prepare("UPDATE received_items SET status = 'confirmed' WHERE id = ?");
                                    $stmt_update->execute([$item_id]);
                                    $items_confirmed++;
                                } else {
                                    // Partial confirmation - keep as received
                                    // Note: You could add a 'partially_confirmed' status if needed
                                }
                                
                                // Update or create station_inventory
                                $stmt_inv = $pdo->prepare("
                                    SELECT stock_level FROM station_inventory 
                                    WHERE station_id = ? AND product_id = ?
                                ");
                                $stmt_inv->execute([$station_id, $item['product_id']]);
                                $current_stock = $stmt_inv->fetchColumn();
                                
                                $qty_before = $current_stock ?? 0;
                                $qty_after = $qty_before + $qty_to_add;
                                
                                if ($current_stock !== null) {
                                    // Update existing
                                    $stmt_upd = $pdo->prepare("
                                        UPDATE station_inventory 
                                        SET stock_level = stock_level + ?, last_updated = NOW()
                                        WHERE station_id = ? AND product_id = ?
                                    ");
                                    $stmt_upd->execute([$qty_to_add, $station_id, $item['product_id']]);
                                } else {
                                    // Create new
                                    $stmt_ins = $pdo->prepare("
                                        INSERT INTO station_inventory (station_id, product_id, stock_level, unit, status, last_updated)
                                        VALUES (?, ?, ?, 'pieces', 'active', NOW())
                                    ");
                                    $stmt_ins->execute([$station_id, $item['product_id'], $qty_to_add]);
                                }
                                
                                // Log inventory change
                                $stmt_log = $pdo->prepare("
                                    INSERT INTO inventory_logs (station_id, product_id, user_id, action, quantity_before, quantity_after, quantity_change, reference_type, notes, created_at)
                                    VALUES (?, ?, ?, 'stock_in', ?, ?, ?, 'receiving_batch', ?, NOW())
                                ");
                                $stmt_log->execute([
                                    $station_id,
                                    $item['product_id'],
                                    $me['id'],
                                    $qty_before,
                                    $qty_after,
                                    $qty_to_add,
                                    "Batch: {$batch['batch_number']}, Item: {$item['item_name']}, Confirmed: $qty_to_add/$original_qty. $notes"
                                ]);
                                
                                $total_quantity += $qty_to_add;
                            }
                        }
                    }
                    
                    // Check if all items in batch are confirmed
                    $stmt_count = $pdo->prepare("SELECT COUNT(*) FROM received_items WHERE batch_id = ? AND status != 'confirmed'");
                    $stmt_count->execute([$batch_id]);
                    $remaining_items = $stmt_count->fetchColumn();
                    
                    // Update batch status
                    if ($remaining_items == 0) {
                        // All items confirmed
                        $stmt_batch = $pdo->prepare("
                            UPDATE receiving_batches 
                            SET status = 'confirmed', confirmed_by = ?, confirmed_at = NOW(), updated_at = NOW()
                            WHERE id = ?
                        ");
                        $stmt_batch->execute([$me['id'], $batch_id]);
                        
                        log_activity($pdo, $me['id'], 'Stock Confirmation', "Batch {$batch['batch_number']} fully confirmed. Added $items_confirmed items ($total_quantity pcs) to station_inventory.", $_SERVER['REMOTE_ADDR']);
                        $msg = "✅ Batch {$batch['batch_number']} fully confirmed! All $items_confirmed items added to station_inventory.";
                    } else {
                        // Partially confirmed - keep as received
                        log_activity($pdo, $me['id'], 'Stock Confirmation', "Batch {$batch['batch_number']} partially confirmed. $items_confirmed items ($total_quantity pcs) added to station_inventory.", $_SERVER['REMOTE_ADDR']);
                        $msg = "✅ Partially confirmed $items_confirmed items ($total_quantity pcs) to station_inventory. Batch remains in received status for remaining items.";
                    }
                    
                    $pdo->commit();
                }
            } catch (Exception $e) {
                $pdo->rollBack();
                $msg = "❌ Error: " . $e->getMessage();
            }
        }
    }
    
    elseif ($action === 'return_to_pending') {
        $batch_id = (int)($_POST['batch_id'] ?? 0);
        $reason = trim($_POST['reason'] ?? '');
        
        if (strlen($reason) < 10) {
            $msg = "❌ Reason must be at least 10 characters.";
        } else {
            try {
                $pdo->beginTransaction();
                
                // Get batch
                $stmt = $pdo->prepare("SELECT * FROM receiving_batches WHERE id = ? AND status = 'received'");
                $stmt->execute([$batch_id]);
                $batch = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if (!$batch) {
                    $msg = "❌ Batch not found.";
                } else {
                    // Update batch back to pending
                    $stmt_update = $pdo->prepare("
                        UPDATE receiving_batches 
                        SET status = 'pending', received_by_manager = NULL, received_at = NULL, notes = CONCAT(COALESCE(notes, ''), '\n--- Returned to Pending: ', ?), updated_at = NOW()
                        WHERE id = ?
                    ");
                    $stmt_update->execute([$reason, $batch_id]);
                    
                    // Update all items back to pending
                    $stmt_items = $pdo->prepare("
                        UPDATE received_items 
                        SET status = 'pending'
                        WHERE batch_id = ?
                    ");
                    $stmt_items->execute([$batch_id]);
                    
                    log_activity($pdo, $me['id'], 'Batch Returned to Pending', "Batch {$batch['batch_number']} returned. Reason: $reason", $_SERVER['REMOTE_ADDR']);
                    
                    $pdo->commit();
                    $msg = "✅ Batch {$batch['batch_number']} returned to pending status.";
                }
            } catch (Exception $e) {
                $pdo->rollBack();
                $msg = "❌ Error: " . $e->getMessage();
            }
        }
    }
}

// Fetch batches
$batches = [];
try {
    if ($role === 'superadmin') {
        $stmt = $pdo->query("
            SELECT rb.*, u.name as received_by_name, u2.name as received_by_manager_name, u3.name as confirmed_by_name
            FROM receiving_batches rb
            LEFT JOIN users u ON rb.received_by = u.id
            LEFT JOIN users u2 ON rb.received_by_manager = u2.id
            LEFT JOIN users u3 ON rb.confirmed_by = u3.id
            WHERE rb.status = 'received'
            ORDER BY rb.created_at DESC
        ");
        $batches = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } else {
        $stmt = $pdo->prepare("
            SELECT rb.*, u.name as received_by_name, u2.name as received_by_manager_name, u3.name as confirmed_by_name
            FROM receiving_batches rb
            LEFT JOIN users u ON rb.received_by = u.id
            LEFT JOIN users u2 ON rb.received_by_manager = u2.id
            LEFT JOIN users u3 ON rb.confirmed_by = u3.id
            WHERE rb.station_id = ? AND rb.status = 'received'
            ORDER BY rb.created_at DESC
        ");
        $stmt->execute([$station_id]);
        $batches = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
} catch (Exception $e) {
    $batches = [];
}

// Fetch batch details if viewing specific batch
$batch_details = null;
$batch_items = [];
if ($batch_id) {
    try {
        $stmt = $pdo->prepare("
            SELECT rb.*, u.name as received_by_name, u2.name as received_by_manager_name
            FROM receiving_batches rb
            LEFT JOIN users u ON rb.received_by = u.id
            LEFT JOIN users u2 ON rb.received_by_manager = u2.id
            WHERE rb.id = ? AND rb.station_id = ?
        ");
        $stmt->execute([$batch_id, $station_id]);
        $batch_details = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($batch_details) {
            // Fetch items with current inventory
            $stmt_items = $pdo->prepare("
                SELECT ri.*, 
                       COALESCE(si.stock_level, 0) as current_stock,
                       COALESCE(si.stock_level, 0) + ri.quantity as projected_stock
                FROM received_items ri
                LEFT JOIN station_inventory si ON ri.product_id = si.product_id AND si.station_id = ri.station_id
                WHERE ri.batch_id = ?
            ");
            $stmt_items->execute([$batch_id]);
            $batch_items = $stmt_items->fetchAll(PDO::FETCH_ASSOC);
        }
    } catch (Exception $e) {
        $batch_details = null;
    }
}

include __DIR__ . '/../partials/header.php';
?>

<div style="max-width: 1400px; margin: 0 auto; padding: 24px;">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px;">
        <div>
            <h1 style="font-size: 32px; font-weight: 700; color: var(--petron-blue); margin: 0;">
                <i class="fas fa-warehouse"></i> Stock Confirmation
            </h1>
            <p style="color: var(--muted); margin-top: 4px; font-size: 14px;">Review received batches and confirm stock addition to inventory</p>
        </div>
    </div>
    
    <?php if ($msg): ?>
        <div style="padding: 16px 20px; border-radius: 12px; margin-bottom: 24px; background: <?php echo strpos($msg, '✅') !== false ? '#e6f4ea' : '#fee2e2'; ?>; color: <?php echo strpos($msg, '✅') !== false ? '#065f46' : '#dc2626'; ?>; border: 1px solid <?php echo strpos($msg, '✅') !== false ? '#a7f3d0' : '#fecaca'; ?>; display: flex; align-items: center; gap: 10px;">
            <i class="fas <?php echo strpos($msg, '✅') !== false ? 'fa-check-circle' : 'fa-exclamation-circle'; ?>"></i>
            <?php echo htmlspecialchars($msg); ?>
        </div>
    <?php endif; ?>
    
    <?php if ($batch_details): ?>
        <!-- Batch Detail View -->
        <div style="background: white; border-radius: 12px; padding: 24px; border: 1px solid #e2e8f0; box-shadow: 0 2px 8px rgba(0,0,0,0.08); margin-bottom: 24px;">
            <div style="display: flex; justify-content: space-between; align-items: start; margin-bottom: 20px; padding-bottom: 20px; border-bottom: 2px solid #f1f5f9;">
                <div>
                    <h2 style="font-size: 24px; font-weight: 700; color: #0f172a; margin: 0 0 8px;"><?php echo htmlspecialchars($batch_details['batch_number']); ?></h2>
                    <div style="display: flex; gap: 16px; flex-wrap: wrap; font-size: 14px; color: #64748b;">
                        <div><i class="fas fa-truck"></i> <strong>Supplier:</strong> <?php echo htmlspecialchars($batch_details['supplier']); ?></div>
                        <div><i class="fas fa-calendar"></i> <strong>Delivery Date:</strong> <?php echo date('M d, Y', strtotime($batch_details['delivery_date'])); ?></div>
                        <div><i class="fas fa-user"></i> <strong>Submitted By:</strong> <?php echo htmlspecialchars($batch_details['received_by_name']); ?></div>
                        <div><i class="fas fa-user-check"></i> <strong>Received By:</strong> <?php echo htmlspecialchars($batch_details['received_by_manager_name'] ?? 'N/A'); ?></div>
                    </div>
                </div>
                <span style="background: #bfdbfe; color: #1e3a8a; padding: 6px 12px; border-radius: 999px; font-size: 12px; font-weight: 600; text-transform: uppercase;">
                    Received - Ready for Confirmation
                </span>
            </div>
            
            <?php if ($batch_details['notes']): ?>
                <div style="background: #f8fafc; padding: 12px 16px; border-radius: 8px; margin-bottom: 20px; border-left: 3px solid #eab308;">
                    <strong style="color: #0f172a; font-size: 13px;">Notes:</strong>
                    <p style="color: #64748b; font-size: 14px; margin: 4px 0 0 0;"><?php echo nl2br(htmlspecialchars($batch_details['notes'])); ?></p>
                </div>
            <?php endif; ?>
            
            <h3 style="font-size: 18px; font-weight: 600; color: #0f172a; margin: 0 0 16px;">Items to Add to Inventory</h3>
            
            <table style="width: 100%; border-collapse: collapse; background: white; border-radius: 8px; overflow: hidden; border: 1px solid #e2e8f0; margin-bottom: 20px;">
                <thead>
                    <tr style="background: #f8fafc; border-bottom: 2px solid #e2e8f0;">
                        <th style="padding: 12px 16px; text-align: left; font-size: 12px; font-weight: 600; color: #475569;">#</th>
                        <th style="padding: 12px 16px; text-align: left; font-size: 12px; font-weight: 600; color: #475569;">Item Name</th>
                        <th style="padding: 12px 16px; text-align: right; font-size: 12px; font-weight: 600; color: #475569;">Received</th>
                        <th style="padding: 12px 16px; text-align: right; font-size: 12px; font-weight: 600; color: #475569;">Current Stock</th>
                        <th style="padding: 12px 16px; text-align: right; font-size: 12px; font-weight: 600; color: #475569;">After Confirmation</th>
                        <th style="padding: 12px 16px; text-align: left; font-size: 12px; font-weight: 600; color: #475569;">Confirm Quantity</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($batch_items as $index => $item): ?>
                        <tr style="border-bottom: 1px solid #f1f5f9;">
                            <td style="padding: 12px 16px; font-size: 13px;"><?php echo $index + 1; ?></td>
                            <td style="padding: 12px 16px; font-size: 13px; font-weight: 500; color: #0f172a;"><?php echo htmlspecialchars($item['item_name']); ?></td>
                            <td style="padding: 12px 16px; font-size: 13px; text-align: right; font-weight: 600; color: var(--petron-blue);"><?php echo number_format($item['quantity'], 0); ?></td>
                            <td style="padding: 12px 16px; font-size: 13px; text-align: right; color: #64748b;"><?php echo number_format($item['current_stock'], 0); ?></td>
                            <td style="padding: 12px 16px; font-size: 13px; text-align: right; font-weight: 600; color: #059669;"><?php echo number_format($item['projected_stock'], 0); ?></td>
                            <td style="padding: 12px 16px;">
                                <input type="number" name="confirm[<?php echo $item['id']; ?>]" value="<?php echo number_format($item['quantity'], 0); ?>" min="0" max="<?php echo $item['quantity']; ?>" step="0.01" style="width: 100%; padding: 8px 10px; border: 1px solid #e2e8f0; border-radius: 6px; font-size: 13px; text-align: right;">
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            
            <div style="background: #dbeafe; padding: 12px 16px; border-radius: 8px; margin-bottom: 20px; border-left: 3px solid #2563eb;">
                <strong style="color: #1e3a8a; font-size: 13px;">ℹ️ Partial Confirmation:</strong>
                <p style="color: #1e3a8a; font-size: 14px; margin: 4px 0 0 0;">You can confirm fewer items than received. Confirmed items will be added to station_inventory. Remaining items stay in the batch for later confirmation.</p>
            </div>
            
            <div style="margin-bottom: 16px;">
                <label style="font-size: 13px; font-weight: 600; color: #475569; display: block; margin-bottom: 8px;">Confirmation Notes</label>
                <textarea name="notes" rows="3" placeholder="Any additional notes about this confirmation..." style="width: 100%; padding: 10px 12px; border: 1px solid #e2e8f0; border-radius: 8px; font-size: 14px; resize: none;"></textarea>
            </div>
            
            <!-- Actions -->
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px;">
                <form method="post">
                    <input type="hidden" name="action" value="confirm_stock">
                    <input type="hidden" name="batch_id" value="<?php echo $batch_details['id']; ?>">
                    <button type="submit" style="width: 100%; background: #22c55e; color: white; border: none; padding: 14px 20px; border-radius: 8px; cursor: pointer; font-weight: 600; font-size: 14px; display: flex; align-items: center; justify-content: center; gap: 8px;">
                        <i class="fas fa-check-circle"></i> Confirm Stock
                    </button>
                </form>
                
                <button type="button" onclick="showReturnModal(<?php echo $batch_details['id']; ?>, '<?php echo htmlspecialchars($batch_details['batch_number']); ?>')" style="width: 100%; background: #f59e0b; color: white; border: none; padding: 14px 20px; border-radius: 8px; cursor: pointer; font-weight: 600; font-size: 14px; display: flex; align-items: center; justify-content: center; gap: 8px;">
                    <i class="fas fa-undo"></i> Return to Pending
                </button>
            </div>
        </div>
        
        <a href="?view=received" class="btn ghost" style="display: inline-flex; align-items: center; gap: 8px;">
            <i class="fas fa-arrow-left"></i> Back to List
        </a>
    <?php else: ?>
        <!-- Batch List View -->
        <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(400px, 1fr)); gap: 20px;">
            <?php if (empty($batches)): ?>
                <div style="grid-column: 1 / -1; background: white; border-radius: 12px; padding: 60px 20px; text-align: center; border: 1px solid #e2e8f0; box-shadow: 0 2px 8px rgba(0,0,0,0.08);">
                    <i class="fas fa-inbox" style="font-size: 48px; color: #cbd5e1; margin-bottom: 16px;"></i>
                    <div style="font-size: 18px; font-weight: 600; color: #0f172a; margin-bottom: 8px;">No Batches Ready for Confirmation</div>
                    <div style="color: #64748b; font-size: 14px;">There are no received batches waiting for stock confirmation.</div>
                </div>
            <?php else: ?>
                <?php foreach ($batches as $batch): ?>
                    <div style="background: white; border-radius: 12px; padding: 20px; border: 1px solid #e2e8f0; box-shadow: 0 2px 8px rgba(0,0,0,0.08); cursor: pointer; transition: all 0.2s;" onclick="window.location.href='?view=received&batch=<?php echo $batch['id']; ?>'" onmouseover="this.style.transform='translateY(-2px)'" onmouseout="this.style.transform='translateY(0)'">
                        <div style="display: flex; justify-content: space-between; align-items: start; margin-bottom: 12px;">
                            <div>
                                <div style="font-size: 16px; font-weight: 700; color: #0f172a; margin-bottom: 4px;"><?php echo htmlspecialchars($batch['batch_number']); ?></div>
                                <div style="font-size: 13px; color: #64748b;"><i class="fas fa-truck"></i> <?php echo htmlspecialchars($batch['supplier']); ?></div>
                            </div>
                            <span style="background: #bfdbfe; color: #1e3a8a; padding: 4px 10px; border-radius: 999px; font-size: 11px; font-weight: 600; text-transform: uppercase;">
                                Received
                            </span>
                        </div>
                        
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px; font-size: 13px; color: #64748b;">
                            <div><i class="fas fa-calendar"></i> <?php echo date('M d, Y', strtotime($batch['delivery_date'])); ?></div>
                            <div><i class="fas fa-user-check"></i> <?php echo htmlspecialchars($batch['received_by_manager_name'] ?? 'N/A'); ?></div>
                        </div>
                        
                        <div style="margin-top: 12px; padding-top: 12px; border-top: 1px solid #f1f5f9; font-size: 12px; color: #059669; display: flex; align-items: center; gap: 6px;">
                            <i class="fas fa-check-circle"></i> Received on <?php echo date('M d, Y H:i', strtotime($batch['received_at'])); ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    <?php endif; ?>
</div>

<!-- Return Modal -->
<div id="returnModal" style="display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.5); align-items: center; justify-content: center; z-index: 1000; padding: 20px;">
    <div style="background: white; border-radius: 12px; padding: 24px; max-width: 500px; box-shadow: 0 20px 60px rgba(0,0,0,0.3);">
        <h3 style="font-size: 20px; font-weight: 700; color: #0f172a; margin: 0 0 20px;">Return Batch to Pending</h3>
        <p style="color: #64748b; font-size: 14px; margin-bottom: 16px;">Please provide a reason for returning this batch to pending status (minimum 10 characters).</p>
        
        <form method="post">
            <input type="hidden" name="action" value="return_to_pending">
            <input type="hidden" name="batch_id" id="returnBatchId">
            
            <div style="margin-bottom: 20px;">
                <label style="font-size: 13px; font-weight: 600; color: #475569; display: block; margin-bottom: 8px;">Reason for Return *</label>
                <textarea name="reason" id="returnReason" rows="4" placeholder="Explain why this batch is being returned..." required style="width: 100%; padding: 10px 12px; border: 1px solid #e2e8f0; border-radius: 8px; font-size: 14px; resize: none;"></textarea>
            </div>
            
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px;">
                <button type="button" onclick="closeReturnModal()" style="background: #f3f4f6; color: #64748b; border: none; padding: 12px 20px; border-radius: 8px; cursor: pointer; font-weight: 600; font-size: 14px;">
                    Cancel
                </button>
                <button type="submit" style="background: #f59e0b; color: white; border: none; padding: 12px 20px; border-radius: 8px; cursor: pointer; font-weight: 600; font-size: 14px;">
                    Return to Pending
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function showReturnModal(batchId, batchNumber) {
    document.getElementById('returnBatchId').value = batchId;
    document.getElementById('returnModal').style.display = 'flex';
}

function closeReturnModal() {
    document.getElementById('returnModal').style.display = 'none';
    document.getElementById('returnReason').value = '';
}

document.getElementById('returnModal').addEventListener('click', function(e) {
    if (e.target === this) {
        closeReturnModal();
    }
});
</script>

<?php include __DIR__ . '/../partials/footer.php'; ?>
