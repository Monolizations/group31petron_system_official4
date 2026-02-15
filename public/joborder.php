<?php
ob_start(); // Start output buffering to prevent accidental HTML output

require_once __DIR__ . '/../backend/lib.php';
require_once __DIR__ . '/../public/db_connect.php';
require_login();

$u = current_user();
$role = role_key($u['role'] ?? 'staff');
$station_id = user_station_id();



// ---------------------------------------------------------
// AUTO-SEED STAFF (demo / walk-in job orders)
// Creates staff rows if none exist for this station.
// NOTE: users.password is NOT NULL in DB, so we store a hash.
// ---------------------------------------------------------
try {
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE role='staff' AND station_id=?");
    $stmt->execute([$station_id]);
    $staffCount = (int)$stmt->fetchColumn();

    if ($staffCount === 0) {
        $seedStaff = ['Juan Carlo', 'Carla', 'Miguel', 'Andrea', 'Mark'];
        $ins = $pdo->prepare("
            INSERT INTO users (name, username, password, role, status, station_id)
            VALUES (?, ?, ?, 'staff', 'active', ?)
        ");

        foreach ($seedStaff as $name) {
            $username = strtolower(preg_replace('/\s+/', '.', trim($name)));
            $tempPassword = generateSecurePassword();
            $passwordHash = password_hash($tempPassword, PASSWORD_DEFAULT);
            $ins->execute([$name, $username, $passwordHash, 'active', $station_id]);
        }
    }
} catch (Exception $e) {
    // ignore seeding errors silently
}

// ---------------------------------------------------------
// AUTO-SEED MECHANICS (demo / quick start)
// Adds mechanics if none exist for this station.
// ---------------------------------------------------------
try {
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM mechanics WHERE station_id=?");
    $stmt->execute([$station_id]);
    $mechCount = (int)$stmt->fetchColumn();

    if ($mechCount === 0) {
        $seedMechanics = [
            ['Paolo Reyes', 'Engine Specialist', '0917-100-2001'],
            ['Liza Cruz', 'Brake & Suspension', '0917-100-2002'],
            ['Marco Dizon', 'Electrical & Diagnostics', '0917-100-2003'],
            ['Ana Santos', 'Tire & Vulcanizing', '0917-100-2004']
        ];

        $ins = $pdo->prepare("
            INSERT INTO mechanics (station_id, full_name, specialization, contact_number, status)
            VALUES (?, ?, ?, ?, 'active')
        ");

        foreach ($seedMechanics as $mech) {
            $ins->execute([$station_id, $mech[0], $mech[1], $mech[2]]);
        }
    }
} catch (Exception $e) {
    // ignore seeding errors silently
}

$isStaff   = ($role === 'staff');
$isManager = ($role === 'manager');
$isAdmin   = in_array($role, ['admin','superadmin']);
$canReview = ($isManager || $isAdmin);

/* =========================================================
   HANDLE POST ACTIONS - Using Enhanced Job Order Backend
========================================================= */
require_once __DIR__ . '/../backend/job_order_operations.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    ob_clean(); // Clear any output before JSON response
    $action = $_POST['action'] ?? '';
    $jobOrderOps = new JobOrderOperations($pdo, $u, $station_id);

    try {
        /* =======================
           CREATE JOB ORDER (Staff Action)
        ======================= */
        if ($action === 'create_job_order') {
            if (!in_array($role, ['staff','operations_staff'])) {
                json_response(['success'=>false,'message'=>'Permission denied']);
            }
            
            $result = $jobOrderOps->createJobOrder([
                'customer_id' => $_POST['customer_id'] ?? null,
                'customer_name' => $_POST['customer_name'] ?? null,
                'vehicle_plate' => $_POST['vehicle_plate'] ?? null,
                'vehicle_type' => $_POST['vehicle_type'] ?? null,
                'service_category_id' => $_POST['service_category_id'],
                'assigned_mechanic_id' => $_POST['assigned_mechanic_id'],
                'mechanic_name' => $_POST['mechanic_name'] ?? null,
                'service_description' => $_POST['service_description'] ?? 'General Service',
                'estimated_duration' => (int)($_POST['estimated_duration'] ?? 60),
                'notes' => $_POST['notes'] ?? null
            ]);
            
            json_response($result);
        }

        /* =======================
           MANAGER REVIEW - APPROVE
        ======================= */
        if ($action === 'manager_review_approve') {
            if (!$canReview) {
                json_response(['success'=>false,'message'=>'Manager privileges required']);
            }

            $result = $jobOrderOps->managerApproveJobOrder(
                $_POST['job_id'],
                'approve',
                $_POST['remarks'] ?? null
            );

            json_response($result);
        }

        /* =======================
           MANAGER REVIEW - REJECT
        ======================= */
        if ($action === 'manager_review_reject') {
            if (!$canReview) {
                json_response(['success'=>false,'message'=>'Manager privileges required']);
            }

            $result = $jobOrderOps->managerApproveJobOrder(
                $_POST['job_id'],
                'reject',
                $_POST['remarks'] ?? null
            );

            json_response($result);
        }

        /* =======================
           START JOB ORDER
        ======================= */
        if ($action === 'start_job_order') {
            $result = $jobOrderOps->startJobOrder($_POST['job_id']);
            json_response($result);
        }

        /* =======================
           COMPLETE JOB ORDER
        ======================= */
         if ($action === 'complete_job_order') {
             $parts_used = json_decode($_POST['parts_used'] ?? '[]', true);
             
             $result = $jobOrderOps->completeJobOrder(
                 $_POST['job_id'],
                 $parts_used,
                 $_POST['actual_labor_hours'] ?? 0
             );
             
             json_response($result);
         }

          /* =======================
             UPDATE JOB STATUS
          ======================= */
          if ($action === 'update_job_status') {
              $status = $_POST['status'] ?? '';
              $notes = $_POST['notes'] ?? '';
              
              if (!$status) {
                  json_response(['success'=>false,'message'=>'Status is required']);
              }
              
              $result = $jobOrderOps->updateJobStatus(
                  $_POST['job_id'],
                  $status,
                  $notes
              );
              
              json_response($result);
          }

          /* =======================
             CONFIRM PARTS USED
          ======================= */
          if ($action === 'confirm_parts') {
              $parts_used = json_decode($_POST['parts_used'] ?? '[]', true);
              $notes = $_POST['notes'] ?? '';
              
              if (empty($parts_used)) {
                  json_response(['success'=>false,'message'=>'Please add at least one part']);
              }
              
              $result = $jobOrderOps->confirmPartsUsed(
                  $_POST['job_id'],
                  $parts_used,
                  $notes
              );
              
              json_response($result);
          }

      } catch (Exception $e) {
          json_response(['success'=>false,'message'=>$e->getMessage()]);
     }
}

/* =========================================================
   FETCH DATA FOR UI
========================================================= */

// Service categories from database
$service_categories = [];
try {
    $stmt = $pdo->query("SELECT id, name, description, default_parts_cost, default_labor_cost, default_duration FROM service_categories WHERE is_active = 1 ORDER BY name");
    $service_categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    // Fallback to static list if table doesn't exist
    $service_types = [
        'Change Oil', 'Brake Service', 'Vulcanizing', 'Car Wash', 'Battery Check',
        'Engine Tune-up', 'Air Filter Replacement', 'Wheel Alignment', 'Other Service'
    ];
}

// Customers (shared list)
$customers = [];
try {
    $stmt = $pdo->query("SELECT id, name, phone FROM customers WHERE status='active' ORDER BY name");
    $customers = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {}

// Mechanics list (active mechanics for job assignment)
$mechanics_list = [];
try {
    $stmt = $pdo->prepare("
        SELECT m.id, m.full_name, m.specialization, m.status,
               (SELECT COUNT(*) FROM job_orders WHERE assigned_mechanic_id = m.id AND status = 'In Progress') as active_jobs
        FROM mechanics m
        WHERE m.station_id = ? AND m.status = 'active'
        ORDER BY active_jobs ASC, m.full_name ASC
    ");
    $stmt->execute([$station_id]);
    $mechanics_list = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {}

// Products/Parts for inventory (for completion screen)
$products_list = [];
try {
    $stmt = $pdo->prepare("
        SELECT p.id, p.name, p.barcode, si.stock_level, p.cost_price
        FROM products p
        INNER JOIN station_inventory si ON si.product_id = p.id
        WHERE si.station_id = ? AND si.status = 'active' AND si.stock_level > 0
        ORDER BY p.name
    ");
    $stmt->execute([$station_id]);
    $products_list = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {}

// ADMIN: Pending Jobs (require review)
$pending_jobs = [];
if ($canReview) {
    $stmt = $pdo->prepare("
        SELECT jo.*, 
               c.name as customer_name,
               m.full_name as mechanic_name,
               sc.name as service_name,
               u.name as created_by_name,
               jo.estimated_labor_cost + jo.estimated_parts_cost as estimated_total
        FROM job_orders jo
        LEFT JOIN customers c ON c.id = jo.customer_id
        LEFT JOIN mechanics m ON m.id = jo.assigned_mechanic_id
        LEFT JOIN service_categories sc ON sc.id = jo.service_category_id
        LEFT JOIN users u ON u.id = jo.assigned_by
        WHERE jo.station_id = ? AND jo.status = 'Pending'
        ORDER BY jo.requires_approval DESC, jo.created_at ASC
    ");
    $stmt->execute([$station_id]);
    $pending_jobs = $stmt->fetchAll(PDO::FETCH_ASSOC);
}


// Ongoing Jobs (In Progress)
$ongoing_jobs = [];
$stmt = $pdo->prepare("
    SELECT jo.*, 
           c.name as customer_name,
           m.full_name as mechanic_name,
           sc.name as service_name,
           u.name as created_by_name,
           TIMESTAMPDIFF(MINUTE, jo.started_at, NOW()) as elapsed_minutes
    FROM job_orders jo
    LEFT JOIN customers c ON c.id = jo.customer_id
    LEFT JOIN mechanics m ON m.id = jo.assigned_mechanic_id
    LEFT JOIN service_categories sc ON sc.id = jo.service_category_id
    LEFT JOIN users u ON u.id = jo.assigned_by
    WHERE jo.station_id = ? AND jo.status = 'In Progress'
    ORDER BY jo.started_at DESC
");
$stmt->execute([$station_id]);
$ongoing_jobs = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Completed Jobs (History)
$completed_jobs = [];
$stmt = $pdo->prepare("
    SELECT jo.*, 
           c.name as customer_name,
           m.full_name as mechanic_name,
           sc.name as service_name,
           u.name as created_by_name
    FROM job_orders jo
    LEFT JOIN customers c ON c.id = jo.customer_id
    LEFT JOIN mechanics m ON m.id = jo.assigned_mechanic_id
    LEFT JOIN service_categories sc ON sc.id = jo.service_category_id
    LEFT JOIN users u ON u.id = jo.assigned_by
    WHERE jo.station_id = ? AND jo.status IN ('Completed', 'Verified')
    ORDER BY jo.completed_at DESC
    LIMIT 50
");
$stmt->execute([$station_id]);
$completed_jobs = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Rejected Jobs (for tracking)
$rejected_jobs = [];
if ($isAdmin || $isManager) {
    $stmt = $pdo->prepare("
        SELECT jo.*, 
               c.name as customer_name,
               m.full_name as mechanic_name,
               sc.name as service_name,
               u.name as created_by_name,
               reviewer.name as reviewed_by_name
        FROM job_orders jo
        LEFT JOIN customers c ON c.id = jo.customer_id
        LEFT JOIN mechanics m ON m.id = jo.assigned_mechanic_id
        LEFT JOIN service_categories sc ON sc.id = jo.service_category_id
        LEFT JOIN users u ON u.id = jo.assigned_by
        LEFT JOIN users reviewer ON reviewer.id = jo.reviewed_by
        WHERE jo.station_id = ? AND jo.status = 'Rejected'
        ORDER BY jo.reviewed_at DESC
        LIMIT 20
    ");
    $stmt->execute([$station_id]);
    $rejected_jobs = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

include __DIR__ . '/../partials/header.php';
?>

<!-- YOUR EXISTING HTML UI CONTINUES BELOW (UNCHANGED) -->


<style>
.job-management-container {
    padding: 20px;
    background: var(--bg);
    min-height: calc(100vh - 110px);
}

.page-header {
    margin-bottom: 30px;
}

.page-title {
    font-size: 28px;
    font-weight: 600;
    color: var(--text);
    margin-bottom: 8px;
}

.page-subtitle {
    color: var(--muted);
    font-size: 14px;
}

.tabs-container {
    background: var(--card);
    border-radius: 12px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.05);
    overflow: hidden;
    margin-bottom: 30px;
}

.tab-buttons {
    display: flex;
    background: var(--bg);
    border-bottom: 1px solid var(--border);
}

.tab-btn {
    flex: 1;
    padding: 16px 20px;
    background: none;
    border: none;
    font-size: 14px;
    font-weight: 600;
    color: var(--muted);
    cursor: pointer;
    transition: all 0.3s ease;
    position: relative;
}

.tab-btn.active {
    color: var(--blue);
    background: var(--card);
}

.tab-btn.active::after {
    content: '';
    position: absolute;
    bottom: 0;
    left: 0;
    right: 0;
    height: 3px;
    background: var(--blue);
}

.tab-content {
    padding: 30px;
}

.section-title {
    font-size: 20px;
    font-weight: 600;
    color: var(--text);
    margin-bottom: 20px;
}

.form-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 20px;
    margin-bottom: 30px;
}

.form-group {
    display: flex;
    flex-direction: column;
}

.form-group label {
    font-size: 12px;
    font-weight: 600;
    color: var(--muted);
    margin-bottom: 8px;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.form-input, .form-select, .form-textarea {
    padding: 12px 16px;
    border: 1px solid var(--border);
    border-radius: 8px;
    font-size: 14px;
    background: var(--bg);
    color: var(--text);
    transition: all 0.3s ease;
}

.form-input:focus, .form-select:focus, .form-textarea:focus {
    outline: none;
    border-color: var(--blue);
    box-shadow: 0 0 0 3px rgba(0, 47, 108, 0.1);
}

.form-textarea {
    resize: vertical;
    min-height: 80px;
}

.btn {
    padding: 12px 20px;
    border: none;
    border-radius: 8px;
    font-size: 14px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s ease;
    display: inline-flex;
    align-items: center;
    gap: 8px;
}

.btn-primary {
    background: var(--blue);
    color: white;
}

.btn-primary:hover {
    background: #003366;
    transform: translateY(-1px);
}

.btn-success {
    background: #28A745;
    color: white;
}

.btn-success:hover {
    background: #218838;
}

.btn-danger {
    background: #DC3545;
    color: white;
}

.btn-danger:hover {
    background: #C82333;
}

.btn-secondary {
    background: var(--muted);
    color: var(--text);
}

.btn-secondary:hover {
    background: #6c757d;
}

.job-card.clickable {
    transition: all 0.3s ease;
    border: 2px solid transparent;
}

.job-card.clickable:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 25px rgba(0,0,0,0.15);
    border-color: var(--petron-blue);
    cursor: pointer;
}

.job-card.clickable:active {
    transform: translateY(0);
    box-shadow: 0 4px 15px rgba(0,0,0,0.1);
}

.job-card {
    background: var(--card);
    border-radius: 12px;
    padding: 20px;
    margin-bottom: 20px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.05);
    border: 1px solid var(--border);
}

.job-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    margin-bottom: 15px;
}

.job-title {
    font-size: 16px;
    font-weight: 600;
    color: var(--text);
    margin-bottom: 4px;
}

.job-meta {
    font-size: 12px;
    color: var(--muted);
}

.job-details {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 15px;
    margin-bottom: 20px;
}

.job-detail-item {
    display: flex;
    flex-direction: column;
}

.job-detail-label {
    font-size: 11px;
    font-weight: 600;
    color: var(--muted);
    text-transform: uppercase;
    letter-spacing: 0.5px;
    margin-bottom: 4px;
}

.job-detail-value {
    font-size: 14px;
    color: var(--text);
    font-weight: 500;
}

.job-actions {
    display: flex;
    gap: 10px;
    flex-wrap: wrap;
}

.status-badge {
    padding: 4px 8px;
    border-radius: 4px;
    font-size: 11px;
    font-weight: 600;
    text-transform: uppercase;
}

.status-pending {
    background: #FFF3CD;
    color: #856404;
}

.status-in-progress, .status-active {
    background: #D1ECF1;
    color: #0C5460;
}

.status-completed {
    background: #D4EDDA;
    color: #155724;
}

.status-waiting-parts {
    background: #FFF3CD;
    color: #856404;
}

.status-paused {
    background: #F8D7DA;
    color: #721C24;
}

.status-cancelled {
    background: #F8D7DA;
    color: #721C24;
}

.modal {
    display: none;
    position: fixed;
    z-index: 1000;
    left: 0;
    top: 0;
    width: 100%;
    height: 100%;
    background-color: rgba(0,0,0,0.5);
}

.modal-content {
    background-color: var(--card);
    margin: 5% auto;
    padding: 0;
    border-radius: 12px;
    width: 90%;
    max-width: 600px;
    box-shadow: 0 10px 30px rgba(0,0,0,0.3);
}

.modal-header {
    padding: 20px;
    border-bottom: 1px solid var(--border);
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.modal-title {
    font-size: 18px;
    font-weight: 600;
    color: var(--text);
}

.modal-close {
    background: none;
    border: none;
    font-size: 24px;
    cursor: pointer;
    color: var(--muted);
}

.modal-body {
    padding: 20px;
}

.modal-footer {
    padding: 20px;
    border-top: 1px solid var(--border);
    display: flex;
    justify-content: flex-end;
    gap: 12px;
}

.filter-section {
    background: var(--bg);
    padding: 20px;
    border-radius: 8px;
    margin-bottom: 20px;
}

.filter-row {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 15px;
    margin-bottom: 15px;
}

.export-buttons {
    display: flex;
    gap: 10px;
}

.toast {
    position: fixed;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    padding: 8px 16px;
    border-radius: 6px;
    color: white;
    font-weight: 600;
    font-size: 12px;
    z-index: 2000;
    display: none;
    animation: slideIn 0.3s ease;
    box-shadow: 0 2px 8px rgba(0,0,0,0.2);
    min-width: 200px;
    text-align: center;
}

@keyframes slideIn {
    from {
        opacity: 0;
        transform: translate(-50%, -50%) scale(0.9);
    }
    to {
        opacity: 1;
        transform: translate(-50%, -50%) scale(1);
    }
}

.empty-state {
    text-align: center;
    padding: 60px 20px;
    color: var(--muted);
}

.empty-state-icon {
    font-size: 48px;
    margin-bottom: 20px;
    opacity: 0.5;
}

.empty-state-title {
    font-size: 18px;
    font-weight: 600;
    margin-bottom: 8px;
    color: var(--text);
}

.empty-state-text {
    font-size: 14px;
}
</style>

<div class="job-management-container">
    <div class="page-header">
        <h1 class="page-title">
            <?php 
            if (has_role_at_least('admin')) {
                echo 'Admin – Job Order Management Module';
            } else {
                echo 'Job Order Management';
            }
            ?>
        </h1>
        <p class="page-subtitle">Monitor and manage service-related tasks, track job status, and oversee staff assignments</p>
    </div>

    <!-- Tabs Container -->
    <div class="tabs-container">
        <div class="tab-buttons">
            <?php if ($isStaff): ?>
                <button class="tab-btn active" onclick="switchTab('create')">Create Job Order</button>
            <?php endif; ?>
            <?php if ($canReview): ?>
                <button class="tab-btn <?php echo $isStaff ? '' : 'active'; ?>" onclick="switchTab('pending')">Pending Jobs</button>
            <?php endif; ?>
            <button class="tab-btn" onclick="switchTab('ongoing')">Ongoing Jobs</button>
            <button class="tab-btn" onclick="switchTab('history')">Job History</button>
        </div>
        <!-- Create Job Order Tab -->
        <div id="create-tab" class="tab-content">
            <h2 class="section-title">🎯 Create New Job Order</h2>
            <p style="color: var(--muted); margin-bottom: 20px;">Triggered by staff encoding a service transaction. Admin receives job request for approval.</p>

            <form id="createJobForm" onsubmit="createJobOrder(event)">
                <div class="form-grid">
                    <div class="form-group">
                        <label>Customer Name (Optional for walk-in)</label>
                        <select class="form-select" name="customer_id" id="customer_id">
                            <option value="">Walk-in Customer</option>
                            <?php foreach ($customers as $customer): ?>
                                <option value="<?php echo $customer['id']; ?>"><?php echo htmlspecialchars($customer['name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                        <input type="text" class="form-input" name="customer_name" id="customer_name" placeholder="Or type customer name (optional)">
                    </div>

                    <div class="form-group">
                        <label>Service Type <span style="color: red;">*</span></label>
                        <select class="form-select" name="service_category_id" id="service_category_id" required onchange="updateEstimatedCosts()">
                            <option value="">Select Service Type</option>
                            <?php foreach ($service_categories as $category): ?>
                                <option value="<?php echo $category['id']; ?>" 
                                        data-labor="<?php echo $category['default_labor_cost']; ?>"
                                        data-parts="<?php echo $category['default_parts_cost']; ?>"
                                        data-duration="<?php echo $category['default_duration']; ?>">
                                    <?php echo htmlspecialchars($category['name']); ?> 
                                    <?php if ($category['default_labor_cost'] + $category['default_parts_cost'] > 0): ?>
                                        (Est. ₱<?php echo number_format($category['default_labor_cost'] + $category['default_parts_cost'], 2); ?>)
                                    <?php endif; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label>Assigned Mechanic <span style="color: red;">*</span></label>
                        <select class="form-select" name="assigned_mechanic_id" id="assigned_mechanic_id">
                            <option value="">Select Mechanic</option>
                            <?php foreach ($mechanics_list as $mechanic): ?>
                                <option value="<?= $mechanic['id'] ?>" 
                                        <?= $mechanic['active_jobs'] >= 3 ? 'disabled' : '' ?>>
                                    <?= htmlspecialchars($mechanic['full_name']) ?>
                                    <?php if ($mechanic['specialization']): ?>
                                        - <?= htmlspecialchars($mechanic['specialization']) ?>
                                    <?php endif; ?>
                                    <?php if ($mechanic['active_jobs'] > 0): ?>
                                        (<?= $mechanic['active_jobs'] ?> active)
                                    <?php endif; ?>
                                    <?php if ($mechanic['active_jobs'] >= 3): ?>
                                        - FULL
                                    <?php endif; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <input type="text" class="form-input" name="mechanic_name" id="mechanic_name" placeholder="Or type mechanic name">
                        <small style="color: var(--muted); font-size: 11px;">Mechanics with 3+ active jobs are unavailable</small>
                    </div>
                    <div class="form-group">
                        <label>Vehicle Plate Number</label>
                        <input type="text" class="form-input" name="vehicle_plate" id="vehicle_plate" placeholder="ABC-123">
                    </div>

                    <div class="form-group">
                        <label>Vehicle Type</label>
                        <input type="text" class="form-input" name="vehicle_type" id="vehicle_type" placeholder="Sedan, SUV, etc.">
                    </div>

                    <div class="form-group">
                        <label>Required Parts (e.g., Oil Filter, Engine Oil)</label>
                        <input type="text" class="form-input" name="required_parts" id="required_parts" placeholder="Oil Filter, Engine Oil, etc.">
                    </div>

                    <div class="form-group">
                        <label>Service Description</label>
                        <textarea class="form-input" name="service_description" placeholder="Describe the service needed">General Service</textarea>
                    </div>

                    <div class="form-group">
                        <label>Estimated Duration (minutes)</label>
                        <input type="number" class="form-input" name="estimated_duration" value="60" min="15" step="15">
                    </div>

                    <div class="form-group">
                        <label>Estimated Time</label>
                        <input type="text" class="form-input" name="estimated_time" id="estimated_time" placeholder="2 hours, 30 minutes, etc.">
                    </div>
                </div>

                <div class="form-group" style="margin-bottom: 30px;">
                    <label>Additional Notes</label>
                    <textarea class="form-textarea" name="notes" id="notes" placeholder="Additional requirements or special instructions..."></textarea>
                </div>

                <div class="job-actions">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-plus"></i> Create Job Order
                    </button>
                </div>
            </form>
        </div>

        <!-- Pending Job Orders Tab - Manager Supervision -->
         <div id="pending-tab" class="tab-content" style="display: none;">
             <h2 class="section-title">⏳ Manager Review - Pending Job Orders</h2>
             <p style="color: var(--muted); margin-bottom: 20px;">
                 <strong>Manager Supervisory Role:</strong> Review staff-encoded job orders. Validate service type, vehicle information, and mechanic assignment. 
                 Approve to move forward or reject to send back to staff for corrections.
             </p>
            
            <?php if ($canReview): ?>
                <?php if (!empty($pending_jobs)): ?>
                    <div style="background: #fff3cd; border-left: 4px solid #ffc107; padding: 15px; margin-bottom: 20px; border-radius: 5px;">
                        <strong>⚠️ <?php echo count($pending_jobs); ?> job(s) awaiting manager review</strong>
                        <?php 
                        $high_value_count = count(array_filter($pending_jobs, function($j) { return $j['requires_approval']; }));
                        if ($high_value_count > 0): ?>
                            <br><small><?php echo $high_value_count; ?> job(s) are high-value and require approval</small>
                        <?php endif; ?>
                    </div>
                    
                    <?php foreach ($pending_jobs as $job): ?>
                        <div class="job-card" style="border-left: 4px solid <?php echo $job['requires_approval'] ? '#ff6b6b' : '#ffc107'; ?>;">
                            <div class="job-header">
                                <div>
                                    <div class="job-title">
                                        <?php echo htmlspecialchars($job['job_order_number']); ?>
                                        <?php if ($job['requires_approval']): ?>
                                            <span class="status-badge" style="background: #ff6b6b; color: white; margin-left: 10px;">HIGH-VALUE</span>
                                        <?php endif; ?>
                                    </div>
                                    <div class="job-meta">
                                        Created by: <?php echo htmlspecialchars($job['created_by_name'] ?? 'Staff'); ?> • 
                                        <?php echo date('M d, Y H:i A', strtotime($job['created_at'])); ?>
                                    </div>
                                </div>
                                <span class="status-badge status-pending">Pending Review</span>
                            </div>

                            <div class="job-details">
                                <div class="job-detail-item">
                                    <div class="job-detail-label">Customer</div>
                                    <div class="job-detail-value"><?php echo htmlspecialchars($job['customer_name'] ?? 'Walk-in'); ?></div>
                                </div>
                                <div class="job-detail-item">
                                    <div class="job-detail-label">Service Type</div>
                                    <div class="job-detail-value"><?php echo htmlspecialchars($job['service_name'] ?? 'N/A'); ?></div>
                                </div>
                                <div class="job-detail-item">
                                    <div class="job-detail-label">Assigned Mechanic</div>
                                    <div class="job-detail-value"><?php echo htmlspecialchars($job['mechanic_name'] ?? 'Unassigned'); ?></div>
                                </div>
                                <div class="job-detail-item">
                                    <div class="job-detail-label">Vehicle</div>
                                    <div class="job-detail-value">
                                        <?php echo htmlspecialchars($job['vehicle_plate'] ?? 'N/A'); ?>
                                        <?php if ($job['vehicle_type']): ?>
                                            (<?php echo htmlspecialchars($job['vehicle_type']); ?>)
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <div class="job-detail-item">
                                    <div class="job-detail-label">Estimated Cost</div>
                                    <div class="job-detail-value">₱<?php echo number_format($job['estimated_total'] ?? 0, 2); ?></div>
                                </div>
                                <div class="job-detail-item">
                                    <div class="job-detail-label">Duration</div>
                                    <div class="job-detail-value"><?php echo $job['estimated_duration']; ?> minutes</div>
                                </div>
                            </div>

                            <?php if (!empty($job['service_description'])): ?>
                                <div style="margin-bottom: 15px;">
                                    <div class="job-detail-label">Service Description</div>
                                    <div class="job-detail-value"><?php echo nl2br(htmlspecialchars($job['service_description'])); ?></div>
                                </div>
                            <?php endif; ?>

                            <?php if (!empty($job['notes'])): ?>
                                <div style="margin-bottom: 15px;">
                                    <div class="job-detail-label">Notes</div>
                                    <div class="job-detail-value"><?php echo nl2br(htmlspecialchars($job['notes'])); ?></div>
                                </div>
                            <?php endif; ?>

                            <div class="job-actions">
                                <?php if ($isManager): ?>
                                    <button class="btn btn-success" onclick="adminReviewApprove(<?php echo $job['id']; ?>)">
                                        <i class="fas fa-check"></i> Approve
                                    </button>
                                    <button class="btn btn-danger" onclick="adminReviewReject(<?php echo $job['id']; ?>)">
                                        <i class="fas fa-times"></i> Reject
                                    </button>
                                <?php else: ?>
                                    <span style="color: var(--muted); font-size: 12px;">Manager approval required</span>
                                <?php endif; ?>
                                <button class="btn btn-secondary" onclick="viewJobDetails(<?php echo $job['id']; ?>)">
                                    <i class="fas fa-info-circle"></i> Details
                                </button>
                            </div>
                        </div>
                    <?php endforeach; ?>
                    
                <?php else: ?>
                    <div class="empty-state">
                        <div class="empty-state-icon">✅</div>
                        <div class="empty-state-title">All Clear!</div>
                        <div class="empty-state-text">No job orders pending admin review.</div>
                    </div>
                <?php endif; ?>
                
                
            <?php else: ?>
                <div class="empty-state">
                    <div class="empty-state-icon">🔒</div>
                    <div class="empty-state-title">Admin Access Required</div>
                    <div class="empty-state-text">Only administrators can review and approve job orders.</div>
                </div>
            <?php endif; ?>
        </div>


        <!-- Ongoing Job Orders Tab -->
        <div id="ongoing-tab" class="tab-content" style="display: none;">
            <h2 class="section-title">🔄 Ongoing Job Orders</h2>
            <p style="color: var(--muted); margin-bottom: 20px;">Shows all active service jobs in progress. Admin can update status, add remarks, confirm parts used, and mark as completed.</p>

            <?php if (!empty($ongoing_jobs)): ?>
                <?php foreach ($ongoing_jobs as $job): ?>
                    <div class="job-card clickable" onclick="viewJobDetails(<?php echo $job['id']; ?>)" style="cursor: pointer;">
                        <div class="job-header">
                            <div>
                                <div class="job-title">Job #<?php echo $job['id']; ?> - Service ID: <?php echo htmlspecialchars($job['service_category_id']); ?></div>
                                <div class="job-meta">Started: <?php echo date('M d, Y H:i', strtotime($job['created_at'])); ?></div>
                            </div>
                            <span class="status-badge status-<?php echo strtolower(str_replace(' ', '-', $job['status'])); ?>"><?php echo $job['status']; ?></span>
                        </div>

                        <div class="job-details">
                            <div class="job-detail-item">
                                <div class="job-detail-label">Job Order ID</div>
                                <div class="job-detail-value">#<?php echo $job['id']; ?></div>
                            </div>
                            <div class="job-detail-item">
                                <div class="job-detail-label">Customer</div>
                                <div class="job-detail-value"><?php echo htmlspecialchars($job['customer_name'] ?? 'Walk-in'); ?></div>
                            </div>
                            <div class="job-detail-item">
                                <div class="job-detail-label">Service Type</div>
                                <div class="job-detail-value"><?php echo htmlspecialchars($job['service_type']); ?></div>
                            </div>
                            <div class="job-detail-item">
                                <div class="job-detail-label">Assigned Staff</div>
                                <div class="job-detail-value"><?php echo htmlspecialchars($job['mechanic_name'] ?? 'Unassigned'); ?></div>
                            </div>
                            <div class="job-detail-item">
                                <div class="job-detail-label">Start Time</div>
                                <div class="job-detail-value"><?php echo date('M d, Y H:i', strtotime($job['created_at'])); ?></div>
                            </div>
                            <div class="job-detail-item">
                                <div class="job-detail-label">Status</div>
                                <div class="job-detail-value"><?php echo $job['status']; ?></div>
                            </div>
                        </div>

                        <?php if (!empty($job['notes'])): ?>
                            <div style="margin-bottom: 20px;">
                                <div class="job-detail-label">Notes/Progress</div>
                                <div class="job-detail-value"><?php echo nl2br(htmlspecialchars($job['notes'])); ?></div>
                            </div>
                        <?php endif; ?>

                        <div class="job-actions">
                            <div style="display: flex; gap: 10px; align-items: center; flex-wrap: wrap;">
                                <select class="form-select" onchange="handleStatusChange(<?php echo $job['id']; ?>, this)" style="flex: 1; min-width: 200px;">
                                    <option value="">-- Change Status --</option>
                                    <option value="In Progress" <?php echo $job['status'] === 'In Progress' ? 'selected' : ''; ?>>In Progress</option>
                                    <option value="Completed" <?php echo $job['status'] === 'Completed' ? 'selected' : ''; ?>>Mark Completed</option>
                                    <option value="Cancelled" <?php echo $job['status'] === 'Cancelled' ? 'selected' : ''; ?>>Cancel Job</option>
                                </select>
                                <button class="btn btn-primary" onclick="confirmPartsUsed(<?php echo $job['id']; ?>)">
                                    <i class="fas fa-cogs"></i> 📦 Parts Used
                                </button>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="empty-state">
                    <div class="empty-state-icon">🔧</div>
                    <div class="empty-state-title">No Ongoing Job Orders</div>
                    <div class="empty-state-text">All jobs are either completed or waiting for approval.</div>
                </div>
            <?php endif; ?>
        </div>

        <!-- Job Order History Tab (Renamed from Completed) -->
        <div id="history-tab" class="tab-content" style="display: none;">
            <div class="filter-section">
                <div class="filter-row">
                    <div class="form-group">
                        <label>Search</label>
                        <input type="text" class="form-input" id="searchCompleted" placeholder="Search jobs...">
                    </div>
                    <div class="form-group">
                        <label>Staff Filter</label>
                        <select class="form-select" id="staffFilter">
                            <option value="">All Staff</option>
                            <?php foreach ($staff_list as $staff): ?>
                                <option value="<?php echo $staff['id']; ?>"><?php echo htmlspecialchars($staff['name'] ?? $staff['username']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Service Type</label>
                        <select class="form-select" id="serviceFilter">
                            <option value="">All Services</option>
                            <?php foreach ($service_types as $type): ?>
                                <option value="<?php echo htmlspecialchars($type); ?>"><?php echo htmlspecialchars($type); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="export-buttons">
                    <button class="btn btn-secondary" onclick="exportCompletedJobs('excel')">
                        <i class="fas fa-file-excel"></i> 📤 Export Excel
                    </button>
                    <button class="btn btn-secondary" onclick="exportCompletedJobs('pdf')">
                        <i class="fas fa-file-pdf"></i> 📤 Export PDF
                    </button>
                </div>
            </div>

            <h2 class="section-title">✅ Job Order History</h2>
            <p style="color: var(--muted); margin-bottom: 20px;">Finalized service jobs with full details. Admin can export, search/filter, view audit trail, and include in Service Income Reports.</p>

            <div id="completedJobsContainer">
                <?php if (!empty($completed_jobs)): ?>
                    <?php foreach ($completed_jobs as $job): ?>
                        <div class="job-card completed-job clickable" onclick="viewJobDetails(<?php echo $job['id']; ?>)" style="cursor: pointer;" data-job-id="<?php echo $job['id']; ?>" data-customer="<?php echo htmlspecialchars($job['customer_name'] ?? 'Walk-in'); ?>" data-staff="<?php echo $job['assigned_mechanic_id']; ?>" data-service="<?php echo htmlspecialchars($job['service_category_id']); ?>">
                            <div class="job-header">
                                <div>
                                    <div class="job-title">Job #<?php echo $job['id']; ?> - Service ID: <?php echo htmlspecialchars($job['service_category_id']); ?></div>
                                    <div class="job-meta">Completed: <?php echo date('M d, Y H:i', strtotime($job['completed_at'] ?? $job['updated_at'])); ?></div>
                                </div>
                                <span class="status-badge status-completed">Completed</span>
                            </div>

                            <div class="job-details">
                                <div class="job-detail-item">
                                    <div class="job-detail-label">Job Order ID</div>
                                    <div class="job-detail-value">#<?php echo $job['id']; ?></div>
                                </div>
                                <div class="job-detail-item">
                                    <div class="job-detail-label">Customer</div>
                                    <div class="job-detail-value"><?php echo htmlspecialchars($job['customer_name'] ?? 'Walk-in'); ?></div>
                                </div>
                                <div class="job-detail-item">
                                    <div class="job-detail-label">Service Performed</div>
                                    <div class="job-detail-value"><?php echo htmlspecialchars($job['service_type']); ?></div>
                                </div>
                                <div class="job-detail-item">
                                    <div class="job-detail-label">Parts Used</div>
                                    <div class="job-detail-value"><?php echo htmlspecialchars($job['vehicle_type'] ?? 'N/A'); ?></div>
                                </div>
                                <div class="job-detail-item">
                                    <div class="job-detail-label">Job Order #</div>
                                    <div class="job-detail-value"><?php echo htmlspecialchars($job['job_order_number']); ?></div>
                                </div>
                                <div class="job-detail-item">
                                    <div class="job-detail-label">Staff Who Performed</div>
                                    <div class="job-detail-value"><?php echo htmlspecialchars($job['mechanic_name'] ?? 'Unassigned'); ?></div>
                                </div>
                                <div class="job-detail-item">
                                    <div class="job-detail-label">Completion Time</div>
                                    <div class="job-detail-value"><?php echo date('M d, Y H:i', strtotime($job['completed_at'] ?? $job['updated_at'])); ?></div>
                                </div>
                            </div>

                            <?php if (!empty($job['notes'])): ?>
                                <div style="margin-bottom: 20px;">
                                    <div class="job-detail-label">Completion Notes</div>
                                    <div class="job-detail-value"><?php echo nl2br(htmlspecialchars($job['notes'])); ?></div>
                                </div>
                            <?php endif; ?>

                            <div class="job-actions">
                                <button class="btn btn-primary" onclick="viewJobDetails(<?php echo $job['id']; ?>)">
                                    <i class="fas fa-eye"></i> 👁️ View Details
                                </button>
                                <button class="btn btn-secondary" onclick="viewAuditTrail(<?php echo $job['id']; ?>)">
                                    <i class="fas fa-history"></i> 🔍 Audit Trail
                                </button>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="empty-state">
                        <div class="empty-state-icon">✅</div>
                        <div class="empty-state-title">No Completed Job Orders</div>
                        <div class="empty-state-text">Completed jobs will appear here once they are finalized.</div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Status Update Modal -->

<!-- Parts Confirmation Modal -->
<div id="partsModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3 class="modal-title">Confirm Parts Used</h3>
            <button class="modal-close" onclick="closePartsModal()">&times;</button>
        </div>
        <div class="modal-body">
            <div id="partsList">
                <!-- Parts will be loaded here -->
            </div>
            <div class="form-group" style="margin-top: 20px;">
                <label>Additional Notes</label>
                <textarea class="form-textarea" id="partsNotes" placeholder="Any additional notes about parts used..."></textarea>
            </div>
        </div>
        <div class="modal-footer">
            <button class="btn btn-secondary" onclick="closePartsModal()">Cancel</button>
            <button class="btn btn-primary" onclick="confirmPartsConfirmation()">Confirm Parts</button>
        </div>
    </div>
</div>

<!-- Job Completion Modal with Parts Selection -->
<div id="completionModal" class="modal">
    <div class="modal-content" style="max-width: 800px;">
        <div class="modal-header">
            <h3 class="modal-title">🎯 Complete Job Order - Add Parts & Calculate Billing</h3>
            <button class="modal-close" onclick="document.getElementById('completionModal').style.display='none'">&times;</button>
        </div>
        <div class="modal-body">
            <input type="hidden" id="job_id_for_completion">
            
            <div class="form-group">
                <label>Actual Labor Hours</label>
                <input type="number" id="actual_labor_hours" class="form-input" min="0" step="0.5" value="0" placeholder="e.g., 2.5">
                <small style="color: var(--muted);">Leave 0 to use estimated duration</small>
            </div>
            
            <hr style="margin: 20px 0;">
            
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px;">
                <h4>Parts Used (Inventory Deduction)</h4>
                <button type="button" class="btn btn-primary" onclick="addPartRow()">
                    <i class="fas fa-plus"></i> Add Part
                </button>
            </div>
            
            <div id="partsListContainer">
                <!-- Part rows will be added here -->
            </div>
            
            <div style="background: #e3f2fd; padding: 15px; border-radius: 5px; margin-top: 20px;">
                <strong>ℹ️ Inventory Deduction:</strong> Selected parts will be automatically deducted from station inventory upon completion.
            </div>
        </div>
        <div class="modal-footer">
            <button class="btn btn-secondary" onclick="document.getElementById('completionModal').style.display='none'">Cancel</button>
            <button class="btn btn-success" onclick="submitJobCompletion()">
                <i class="fas fa-check"></i> Complete Job & Calculate Billing
            </button>
        </div>
    </div>
</div>

<!-- Job Details Modal -->
<div id="detailsModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3 class="modal-title">Job Order Details</h3>
            <button class="modal-close" onclick="closeDetailsModal()">&times;</button>
        </div>
        <div class="modal-body" id="jobDetailsContent">
            <!-- Job details will be loaded here -->
        </div>
        <div class="modal-footer">
            <button class="btn btn-secondary" onclick="closeDetailsModal()">Close</button>
        </div>
    </div>
</div>

<!-- Toast Notification -->
<div id="toast" class="toast" style="display: none;"></div>

<script>
// ===============================================
// ENHANCED JOB ORDER MANAGEMENT JAVASCRIPT
// Staff-driven workflow with admin supervision
// ===============================================

// Tab switching functionality
function switchTab(tabName) {
    // Hide all tabs
    document.getElementById('create-tab').style.display = 'none';
    document.getElementById('pending-tab').style.display = 'none';
    document.getElementById('ongoing-tab').style.display = 'none';
    document.getElementById('history-tab').style.display = 'none';

    // Remove active class from all buttons
    document.querySelectorAll('.tab-btn').forEach(btn => btn.classList.remove('active'));

    // Show selected tab and activate button
    document.getElementById(tabName + '-tab').style.display = 'block';
    
    // Find the button that called this function and add active class
    if (typeof event !== 'undefined' && event && event.target && event.target.classList && event.target.classList.contains('tab-btn')) {
        event.target.classList.add('active');
    } else {
        // Fallback for programmatic calls
        const buttons = document.querySelectorAll('.tab-btn');
        buttons.forEach(btn => {
            const handler = btn.getAttribute('onclick');
            if (handler && handler.includes(tabName)) {
                btn.classList.add('active');
            }
        });
    }
}

// Update estimated costs when service type changes
function updateEstimatedCosts() {
    const select = document.getElementById('service_category_id');
    const option = select.options[select.selectedIndex];
    
    if (option.value) {
        const labor = parseFloat(option.getAttribute('data-labor')) || 0;
        const parts = parseFloat(option.getAttribute('data-parts')) || 0;
        const duration = parseInt(option.getAttribute('data-duration')) || 60;
        
        // Update duration field
        const durationField = document.querySelector('input[name="estimated_duration"]');
        if (durationField) {
            durationField.value = duration;
        }
        
        // Show estimated cost info
        const total = labor + parts;
        if (total > 0) {
            showToast(`Estimated cost: ₱${total.toFixed(2)} (Labor: ₱${labor.toFixed(2)}, Parts: ₱${parts.toFixed(2)})`, 'info');
        }
    }
}

// Form handling - cost calculations removed

async function createJobOrder(event) {
    event.preventDefault();

    const formData = new FormData(event.target);
    formData.append('action', 'create_job_order');

    try {
        const response = await fetch('joborder.php', {
            method: 'POST',
            body: formData
        });

        const contentType = response.headers.get('content-type') || '';
        if (!contentType.includes('application/json')) {
            const text = await response.text();
            showToast('Server error while creating job order', 'error');
            console.error('Non-JSON response:', text);
            return;
        }

        const result = await response.json();

        if (result.success) {
            let message = result.message || 'Job order created successfully!';
            if (result.requires_approval) {
                message += ' (Pending admin approval)';
            }
            showToast(message, 'success');
            event.target.reset();
            setTimeout(() => location.reload(), 1500);
        } else {
            showToast(result.message, 'error');
        }
    } catch (error) {
        console.error('Error:', error);
        showToast('Error creating job order', 'error');
    }
}

// ===============================================
// ADMIN REVIEW FUNCTIONS
// ===============================================

async function adminReviewApprove(jobId) {
     const remarks = prompt('Enter approval remarks (optional):');
     if (remarks === null) return; // User cancelled
     
     const formData = new FormData();
     formData.append('action', 'manager_review_approve');
     formData.append('job_id', jobId);
     if (remarks) formData.append('remarks', remarks);

    try {
        const response = await fetch('joborder.php', {
            method: 'POST',
            body: formData
        });

        const result = await response.json();

        if (result.success) {
            showToast('Job order approved and validated!', 'success');
            setTimeout(() => location.reload(), 1000);
        } else {
            showToast(result.message || 'Failed to approve job order', 'error');
        }
    } catch (error) {
        console.error('Error:', error);
        showToast('Error approving job order', 'error');
    }
}

async function adminReviewReject(jobId) {
     const remarks = prompt('Enter rejection reason:');
     if (!remarks) {
         showToast('Rejection reason is required', 'error');
         return;
     }
     
     if (!confirm('Are you sure you want to reject this job order? It will be returned to staff.')) return;
     
     const formData = new FormData();
     formData.append('action', 'manager_review_reject');
     formData.append('job_id', jobId);
     formData.append('remarks', remarks);

    try {
        const response = await fetch('joborder.php', {
            method: 'POST',
            body: formData
        });

        const result = await response.json();

        if (result.success) {
            showToast('Job order rejected', 'success');
            setTimeout(() => location.reload(), 1000);
        } else {
            showToast(result.message || 'Failed to reject job order', 'error');
        }
    } catch (error) {
        console.error('Error:', error);
        showToast('Error rejecting job order', 'error');
    }
}


// ===============================================
// JOB ORDER COMPLETION WITH INVENTORY DEDUCTION
// ===============================================

let selectedParts = [];

function completeJobOrder(jobId) {
    selectedParts = [];
    document.getElementById('job_id_for_completion').value = jobId;
    document.getElementById('partsListContainer').innerHTML = '';
    document.getElementById('completionModal').style.display = 'block';
}

function addPartRow() {
    const container = document.getElementById('partsListContainer');
    const row = document.createElement('div');
    row.className = 'part-row';
    row.style.cssText = 'display: grid; grid-template-columns: 2fr 1fr 1fr auto; gap: 10px; margin-bottom: 10px; align-items: end;';
    
    row.innerHTML = `
        <div class="form-group" style="margin: 0;">
            <label>Part/Product</label>
            <select class="form-select part-select" required>
                <option value="">Select Product</option>
                <?php foreach ($products_list as $product): ?>
                    <option value="<?= $product['id'] ?>" data-cost="<?= $product['cost_price'] ?>" data-stock="<?= $product['stock_level'] ?>">
                        <?= htmlspecialchars($product['name']) ?> (Stock: <?= $product['stock_level'] ?>)
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="form-group" style="margin: 0;">
            <label>Quantity</label>
            <input type="number" class="form-input part-quantity" min="1" step="1" value="1" required>
        </div>
        <div class="form-group" style="margin: 0;">
            <label>Unit Cost</label>
            <input type="number" class="form-input part-cost" min="0" step="0.01" required readonly>
        </div>
        <button type="button" class="btn btn-danger" onclick="this.parentElement.remove()" style="height: 40px;">Remove</button>
    `;
    
    // Auto-fill cost when product is selected
    const select = row.querySelector('.part-select');
    const costInput = row.querySelector('.part-cost');
    select.addEventListener('change', function() {
        const option = this.options[this.selectedIndex];
        const cost = option.getAttribute('data-cost') || 0;
        costInput.value = cost;
    });
    
    container.appendChild(row);
}

async function submitJobCompletion() {
    const jobId = document.getElementById('job_id_for_completion').value;
    const actualHours = parseFloat(document.getElementById('actual_labor_hours').value) || 0;
    
    // Collect parts data
    const partRows = document.querySelectorAll('.part-row');
    const parts = [];
    
    for (let row of partRows) {
        const productId = row.querySelector('.part-select').value;
        const quantity = parseInt(row.querySelector('.part-quantity').value);
        const unitCost = parseFloat(row.querySelector('.part-cost').value);
        
        if (productId && quantity > 0) {
            parts.push({
                product_id: productId,
                quantity: quantity,
                unit_cost: unitCost
            });
        }
    }
    
    const formData = new FormData();
    formData.append('action', 'complete_job_order');
    formData.append('job_id', jobId);
    formData.append('parts_used', JSON.stringify(parts));
    formData.append('actual_labor_hours', actualHours);

    try {
        const response = await fetch('joborder.php', {
            method: 'POST',
            body: formData
        });

        const result = await response.json();

        if (result.success) {
            showToast(`Job completed! Total: ₱${result.billing.total_cost.toFixed(2)}`, 'success');
            document.getElementById('completionModal').style.display = 'none';
            setTimeout(() => location.reload(), 1500);
        } else {
            showToast(result.message || 'Failed to complete job order', 'error');
        }
    } catch (error) {
        console.error('Error:', error);
        showToast('Error completing job order', 'error');
    }
}

async function approveJobOrder(jobId) {
    if (!confirm('Are you sure you want to approve this job order? It will move to Ongoing status.')) return;

    const formData = new FormData();
    formData.append('action', 'approve_job_order');
    formData.append('job_id', jobId);

    try {
        const response = await fetch('joborder.php', {
            method: 'POST',
            body: formData
        });

        const result = await response.json();

        if (result.success) {
            showToast('Job order approved!', 'success');
            setTimeout(() => location.reload(), 1500);
        } else {
            showToast(result.message, 'error');
        }
    } catch (error) {
        showToast('Error approving job order', 'error');
    }
}

async function rejectJobOrder(jobId) {
    if (!confirm('Are you sure you want to reject this job order? This action cannot be undone.')) return;

    const formData = new FormData();
    formData.append('action', 'reject_job_order');
    formData.append('job_id', jobId);

    try {
        const response = await fetch('joborder.php', {
            method: 'POST',
            body: formData
        });

        const result = await response.json();

        if (result.success) {
            showToast('Job order rejected!', 'success');
            setTimeout(() => location.reload(), 1500);
        } else {
            showToast(result.message, 'error');
        }
    } catch (error) {
        showToast('Error rejecting job order', 'error');
    }
}

let currentJobId = null;
let currentStatus = null;

function handleStatusChange(jobId, selectElement) {
    const status = selectElement.value;
    
    if (!status) {
        selectElement.value = '';
        return;
    }
    
    let confirmMessage = `Change job status to "${status}"?`;
    
    if (status === 'Completed') {
        confirmMessage = '⚠️ Mark job as COMPLETED?\n\nThis will:\n• Move to History\n• Lock all edits\n• Finalize the order';
    } else if (status === 'Cancelled') {
        confirmMessage = '⚠️ Cancel this job?\n\nThis cannot be easily undone.';
    }
    
    if (confirm(confirmMessage)) {
        updateJobStatus(jobId, status);
    } else {
        selectElement.value = '';
    }
}

function updateJobStatus(jobId, status) {
    if (!status) return; // Don't do anything if empty option selected
    
    submitStatusUpdate(jobId, status, '');
}

async function submitStatusUpdate(jobId, status, notes) {
    const formData = new FormData();
    formData.append('action', 'update_job_status');
    formData.append('job_id', jobId);
    formData.append('status', status);
    formData.append('notes', notes);

    try {
        const response = await fetch('joborder.php', {
            method: 'POST',
            body: formData
        });

        const result = await response.json();

        if (result.success) {
            showToast('Job status updated!', 'success');
            setTimeout(() => location.reload(), 1500);
        } else {
            showToast(result.message, 'error');
        }
    } catch (error) {
        showToast('Error updating job status', 'error');
    }
}

function completeJobOrder(jobId) {
    if (!confirm('Mark this job as completed? This will finalize the job order.')) return;
    updateJobStatus(jobId, 'Completed');
}

function confirmPartsUsed(jobId) {
    currentJobId = jobId;
    
    // Create a table for parts input
    const partsHtml = `
        <div class="form-group">
            <label>Parts Used</label>
            <table style="width: 100%; border-collapse: collapse; margin-bottom: 15px;">
                <thead>
                    <tr style="background: #f5f5f5;">
                        <th style="padding: 10px; text-align: left; border: 1px solid #ddd;">Part Name</th>
                        <th style="padding: 10px; text-align: center; border: 1px solid #ddd;">Quantity</th>
                        <th style="padding: 10px; text-align: right; border: 1px solid #ddd;">Unit Price</th>
                        <th style="padding: 10px; text-align: right; border: 1px solid #ddd;">Total</th>
                        <th style="padding: 10px; text-align: center; border: 1px solid #ddd;">Action</th>
                    </tr>
                </thead>
                <tbody id="partsTableBody">
                    <tr id="partRow-0">
                        <td style="border: 1px solid #ddd; padding: 5px;"><input type="text" class="form-input" placeholder="e.g., Oil Filter" style="width: 100%; margin: 0;"></td>
                        <td style="border: 1px solid #ddd; padding: 5px;"><input type="number" class="form-input" placeholder="1" min="1" style="width: 100%; margin: 0;" value="1"></td>
                        <td style="border: 1px solid #ddd; padding: 5px;"><input type="number" class="form-input" placeholder="0.00" min="0" step="0.01" style="width: 100%; margin: 0;" value="0.00"></td>
                        <td style="border: 1px solid #ddd; padding: 5px; text-align: right; font-weight: bold;">₱0.00</td>
                        <td style="border: 1px solid #ddd; padding: 5px; text-align: center;"><button class="btn btn-sm btn-danger" onclick="removePartRow(0)" style="padding: 5px 10px;">Remove</button></td>
                    </tr>
                </tbody>
            </table>
            <button type="button" class="btn btn-secondary" onclick="addPartRow()" style="width: 100%;">
                <i class="fas fa-plus"></i> Add Another Part
            </button>
        </div>
    `;
    
    document.getElementById('partsList').innerHTML = partsHtml;
    document.getElementById('partsModal').style.display = 'block';
    
    // Attach event listeners for calculations
    attachPartsEventListeners();
}

function addPartRow() {
    const tbody = document.getElementById('partsTableBody');
    const rowCount = tbody.children.length;
    
    const newRow = document.createElement('tr');
    newRow.id = `partRow-${rowCount}`;
    newRow.innerHTML = `
        <td style="border: 1px solid #ddd; padding: 5px;"><input type="text" class="form-input" placeholder="e.g., Engine Oil" style="width: 100%; margin: 0;"></td>
        <td style="border: 1px solid #ddd; padding: 5px;"><input type="number" class="form-input" placeholder="1" min="1" style="width: 100%; margin: 0;" value="1" onchange="calculateRowTotal(this)"></td>
        <td style="border: 1px solid #ddd; padding: 5px;"><input type="number" class="form-input" placeholder="0.00" min="0" step="0.01" style="width: 100%; margin: 0;" value="0.00" onchange="calculateRowTotal(this)"></td>
        <td style="border: 1px solid #ddd; padding: 5px; text-align: right; font-weight: bold;">₱0.00</td>
        <td style="border: 1px solid #ddd; padding: 5px; text-align: center;"><button class="btn btn-sm btn-danger" onclick="removePartRow(${rowCount})" style="padding: 5px 10px;">Remove</button></td>
    `;
    
    tbody.appendChild(newRow);
    attachPartsEventListeners();
}

function removePartRow(rowIndex) {
    const row = document.getElementById(`partRow-${rowIndex}`);
    if (row) {
        row.remove();
        attachPartsEventListeners();
    }
}

function calculateRowTotal(input) {
    const row = input.closest('tr');
    const quantityInput = row.querySelector('td:nth-child(2) input');
    const priceInput = row.querySelector('td:nth-child(3) input');
    const totalCell = row.querySelector('td:nth-child(4)');
    
    const quantity = parseFloat(quantityInput.value) || 0;
    const price = parseFloat(priceInput.value) || 0;
    const total = quantity * price;
    
    totalCell.textContent = '₱' + total.toFixed(2);
}

function attachPartsEventListeners() {
    const tbody = document.getElementById('partsTableBody');
    const rows = tbody.querySelectorAll('tr');
    
    rows.forEach(row => {
        const quantityInput = row.querySelector('td:nth-child(2) input');
        const priceInput = row.querySelector('td:nth-child(3) input');
        
        quantityInput.onchange = () => calculateRowTotal(quantityInput);
        priceInput.onchange = () => calculateRowTotal(priceInput);
    });
}

function closePartsModal() {
    document.getElementById('partsModal').style.display = 'none';
}

async function confirmPartsConfirmation() {
     const notes = document.getElementById('partsNotes').value;
     
     // Read parts from table
     const tbody = document.getElementById('partsTableBody');
     const rows = tbody.querySelectorAll('tr');
     
     const partsUsed = [];
     rows.forEach(row => {
         const partName = row.querySelector('td:nth-child(1) input').value.trim();
         const quantity = parseFloat(row.querySelector('td:nth-child(2) input').value) || 0;
         const unitPrice = parseFloat(row.querySelector('td:nth-child(3) input').value) || 0;
         
         if (partName) {
             partsUsed.push({
                 part_name: partName,
                 quantity: quantity,
                 unit_cost: unitPrice
             });
         }
     });
     
     if (partsUsed.length === 0) {
         showToast('Please add at least one part', 'error');
         return;
     }

     const formData = new FormData();
     formData.append('action', 'confirm_parts');
     formData.append('job_id', currentJobId);
     formData.append('parts_used', JSON.stringify(partsUsed));
     formData.append('notes', notes);

     try {
         const response = await fetch('joborder.php', {
             method: 'POST',
             body: formData
         });

         const result = await response.json();

         if (result.success) {
             showToast('Parts confirmed and inventory updated!', 'success');
             closePartsModal();
             setTimeout(() => location.reload(), 1500);
         } else {
             showToast(result.message, 'error');
         }
     } catch (error) {
         showToast('Error confirming parts', 'error');
     }
}

function viewJobDetails(jobId) {
    console.log('Job clicked:', jobId);
    alert('Job Details for Job #' + jobId + '\n\nThis will show detailed job information in a modal.\n\nFor now, this confirms the click is working!');
    // You can expand this later to show a proper modal with job details
}

// Test function to verify JavaScript is working
function testClick() {
    console.log('Test click working!');
    alert('JavaScript is working! Click test successful.');
}

function closeDetailsModal() {
    document.getElementById('detailsModal').style.display = 'none';
}

function viewAuditTrail(jobId) {
    alert('Audit trail feature would show history of changes to job #' + jobId);
}

function exportCompletedJobs(format) {
    const searchTerm = document.getElementById('searchCompleted').value;
    const staffFilter = document.getElementById('staffFilter').value;
    const serviceFilter = document.getElementById('serviceFilter').value;

    const params = new URLSearchParams({
        export_format: format,
        search: searchTerm,
        staff: staffFilter,
        service: serviceFilter,
        tab: 'history'
    });

    showToast('Exporting ' + format.toUpperCase() + '...', 'info');
    window.location.href = 'job_orders_export.php?' + params.toString();
}

document.getElementById('searchCompleted').addEventListener('input', filterCompletedJobs);
document.getElementById('staffFilter').addEventListener('change', filterCompletedJobs);
document.getElementById('serviceFilter').addEventListener('change', filterCompletedJobs);

function filterCompletedJobs() {
    const searchTerm = document.getElementById('searchCompleted').value.toLowerCase();
    const staffFilter = document.getElementById('staffFilter').value;
    const serviceFilter = document.getElementById('serviceFilter').value;

    const jobs = document.querySelectorAll('.completed-job');

    jobs.forEach(job => {
        const jobId = job.dataset.jobId.toLowerCase();
        const customer = job.dataset.customer.toLowerCase();
        const staff = job.dataset.staff;
        const service = job.dataset.service.toLowerCase();

        const matchesSearch = !searchTerm || jobId.includes(searchTerm) || customer.includes(searchTerm) || service.includes(searchTerm);
        const matchesStaff = !staffFilter || staff === staffFilter;
        const matchesService = !serviceFilter || service === serviceFilter;

        job.style.display = matchesSearch && matchesStaff && matchesService ? 'block' : 'none';
    });
}

function showToast(message, type = 'info') {
    const toast = document.getElementById('toast');
    if (toast) {
        toast.textContent = message;

        if (type === 'success') {
            toast.style.background = '#28A745';
        } else if (type === 'error') {
            toast.style.background = '#DC3545';
        } else if (type === 'info') {
            toast.style.background = '#007bff';
        }

        toast.style.display = 'block';

        setTimeout(() => {
            toast.style.display = 'none';
        }, 3000);
    }
}

window.addEventListener('click', function(event) {
    if (event.target.classList.contains('modal')) {
        event.target.style.display = 'none';
    }
});

document.addEventListener('DOMContentLoaded', function() {
    // Check for tab parameter in URL
    const urlParams = new URLSearchParams(window.location.search);
    const tab = urlParams.get('tab');

    if (tab && ['create', 'pending', 'ongoing', 'history'].includes(tab)) {
        switchTab(tab);
    } else {
        // Set default tab based on user role
        <?php if ($isStaff): ?>
            switchTab('create');
        <?php elseif ($canReview): ?>
            switchTab('pending');
        <?php else: ?>
            switchTab('ongoing');
        <?php endif; ?>
    }
});
</script>

<?php include __DIR__ . '/../partials/footer.php'; ?>
