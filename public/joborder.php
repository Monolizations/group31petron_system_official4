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
              if (!$canReview) {
                  json_response(['success'=>false,'message'=>'Manager privileges required']);
              }
              
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
       /* =======================
              GET JOB DETAILS
         ======================= */
        if ($action === 'get_job_details') {
            $job_id = $_POST['job_id'] ?? null;

            if (!$job_id) {
                json_response(['success'=>false,'message'=>'Job ID is required']);
            }

            $result = $jobOrderOps->getJobDetailsWithParts($job_id);

            if (!$result) {
                json_response(['success'=>false,'message'=>'Job order not found']);
            }

            json_response(['success'=>true,'data'=>$result]);
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
$products_error = false;
try {
    $stmt = $pdo->prepare("
        SELECT p.id, p.name, p.sku, i.stock_level, p.price
        FROM products p
        INNER JOIN station_inventory i ON i.product_id = p.id AND i.station_id = ?
        WHERE i.status = 'active'
          AND i.stock_level > 0
          AND p.type_id = 2
        ORDER BY p.name
    ");
    $stmt->execute([$station_id]);
    $products_list = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $products_error = true;
    error_log('Failed to load products: ' . $e->getMessage());
}

// Add error warning if products failed to load
$products_warning = '';
if ($products_error) {
    $products_warning = '<div style="background: #fff3cd; border: 1px solid #ffc107; color: #856404; padding: 15px; border-radius: 5px; margin: 20px 0;">
        <strong>⚠️ Warning:</strong> Unable to load products from station inventory. Please contact administrator.
    </div>';
}

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
           u.name as created_by_name,
           (SELECT COUNT(*) FROM job_order_parts jop WHERE jop.job_order_id = jo.id) as parts_count
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

/* Custom Confirm Modal */
#confirmModal {
    z-index: 10000;
}

#confirmModal .modal-content {
    box-shadow: 0 10px 30px rgba(0,0,0,0.3);
    animation: modalFadeIn 0.3s ease-out;
}

@keyframes modalFadeIn {
    from {
        opacity: 0;
        transform: translateY(-20px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

#confirmModal .modal-header {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
}

#confirmModal .modal-title {
    margin: 0;
    color: white;
}

#confirmModal .modal-body {
    padding: 30px;
}

#confirmModal .modal-footer {
    padding: 20px 30px;
    border-top: 1px solid #eee;
}

#confirmModal button {
    min-width: 100px;
    padding: 12px 24px;
    font-size: 14px;
    font-weight: 600;
}

/* Job Details Modal Tables */
#detailsModal table {
    border-collapse: collapse;
    width: 100%;
    font-size: 14px;
}

#detailsModal th {
    background: #f5f5f5;
    color: #333;
    font-weight: 600;
    text-align: left;
    padding: 12px;
    border-bottom: 2px solid #ddd;
}

#detailsModal td {
    padding: 12px;
    border-bottom: 1px solid #e0e0e0;
    vertical-align: top;
}

#detailsModal tfoot td {
    font-weight: 600;
    background: #e8f4fd;
}

#detailsModal tbody tr:hover {
    background: #f9f9f9;
}

#detailsModal .fa-spinner {
    animation: fa-spin 2s infinite linear;
}

@keyframes fa-spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
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
                <button type="button" class="tab-btn active" onclick="switchTab('create')">Create Job Order</button>
            <?php endif; ?>
            <?php if ($canReview): ?>
                <button type="button" class="tab-btn <?php echo $isStaff ? '' : 'active'; ?>" onclick="switchTab('pending')">Pending Jobs</button>
            <?php endif; ?>
            <button type="button" class="tab-btn" onclick="switchTab('ongoing')">Ongoing Jobs</button>
            <button type="button" class="tab-btn" onclick="switchTab('history')">Job History</button>
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
                                <span class="status-badge status-<?php echo strtolower(str_replace(' ', '-', $job['status'])); ?>"><?php echo htmlspecialchars($job['status']); ?></span>
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
                                    <button type="button" class="btn btn-success" onclick="event.stopPropagation(); event.preventDefault(); console.log('Approve button clicked'); confirmApproveJobOrder(<?php echo $job['id']; ?>)">
                                        <i class="fas fa-check"></i> Approve
                                    </button>
                                    <button type="button" class="btn btn-danger" onclick="event.stopPropagation(); event.preventDefault(); console.log('Reject button clicked'); confirmRejectJobOrder(<?php echo $job['id']; ?>)">
                                        <i class="fas fa-times"></i> Reject
                                    </button>
                                <?php else: ?>
                                    <span style="color: var(--muted); font-size: 12px;">Manager approval required</span>
                                <?php endif; ?>
                                <button type="button" class="btn btn-secondary" onclick="event.stopPropagation(); event.preventDefault(); console.log('Details button clicked'); showJobDetails(<?php echo $job['id']; ?>)">
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
                    <div class="job-card clickable" onclick="showJobDetails(<?php echo $job['id']; ?>)" style="cursor: pointer;">
                        <div class="job-header">
                            <div>
                                <div class="job-title">Job #<?php echo $job['id']; ?> - <?php echo htmlspecialchars($job['service_name'] ?? 'N/A'); ?></div>
                                <div class="job-meta">Started: <?php echo date('M d, Y H:i', strtotime($job['started_at'] ?? $job['created_at'])); ?></div>
                            </div>
                            <span class="status-badge status-<?php echo strtolower(str_replace(' ', '-', $job['status'])); ?>"><?php echo htmlspecialchars($job['status']); ?></span>
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
                                <div class="job-detail-value"><?php echo htmlspecialchars($job['service_name'] ?? 'N/A'); ?></div>
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
                                <button type="button" class="btn btn-info" onclick="event.stopPropagation(); event.preventDefault(); console.log('In Progress button clicked'); confirmStatusChange(<?php echo $job['id']; ?>, 'In Progress', '⏳ Keep In Progress?', 'Job will remain in progress for continued work.')">
                                    <i class="fas fa-spinner"></i> In Progress
                                </button>
                                <button type="button" class="btn btn-success" onclick="event.stopPropagation(); event.preventDefault(); console.log('Complete button clicked'); completeJobOrder(<?php echo $job['id']; ?>)">
                                    <i class="fas fa-check-circle"></i> Complete
                                </button>
                                <button type="button" class="btn btn-danger" onclick="event.stopPropagation(); event.preventDefault(); console.log('Cancel button clicked'); confirmStatusChange(<?php echo $job['id']; ?>, 'Cancelled', '❌ Cancel This Job?', 'This action cannot be easily undone. Are you sure?')">
                                    <i class="fas fa-times-circle"></i> Cancel
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
                        <div class="job-card completed-job clickable" onclick="showJobDetails(<?php echo $job['id']; ?>)" style="cursor: pointer;" data-job-id="<?php echo $job['id']; ?>" data-customer="<?php echo htmlspecialchars($job['customer_name'] ?? 'Walk-in'); ?>" data-staff="<?php echo $job['assigned_mechanic_id']; ?>" data-service="<?php echo htmlspecialchars($job['service_category_id']); ?>">
                            <div class="job-header">
                                <div>
                                    <div class="job-title">Job #<?php echo $job['id']; ?> - <?php echo htmlspecialchars($job['service_name'] ?? 'N/A'); ?></div>
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
                                    <div class="job-detail-value"><?php echo htmlspecialchars($job['service_name'] ?? 'N/A'); ?></div>
                                </div>
                                <div class="job-detail-item">
                                    <div class="job-detail-label">Parts Used</div>
                                    <div class="job-detail-value"><?php echo $job['parts_count'] > 0 ? $job['parts_count'] . ' item(s)' : 'None'; ?></div>
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
                                <button type="button" class="btn btn-primary" onclick="event.stopPropagation(); event.preventDefault(); console.log('View Details button clicked'); showJobDetails(<?php echo $job['id']; ?>)">
                                    <i class="fas fa-eye"></i> 👁️ View Details
                                </button>
                                <button type="button" class="btn btn-secondary" onclick="event.stopPropagation(); event.preventDefault(); console.log('Audit Trail button clicked'); viewAuditTrail(<?php echo $job['id']; ?>)">
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
                <button type="button" class="btn btn-primary" onclick="addPartRowToCompletion()">
                    <i class="fas fa-plus"></i> Add Part
                </button>
            </div>
            
            <div id="partsListContainer">
                <!-- Part rows will be added here -->
            </div>
            
            <?= $products_warning ?>
            
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
<div id="detailsModal" class="modal" style="z-index:10001;">
    <div class="modal-content" style="max-width: 850px;">
        <div class="modal-header">
            <h3 class="modal-title"><i class="fas fa-clipboard-list"></i> Job Order Details</h3>
            <button class="modal-close" onclick="closeDetailsModal()">&times;</button>
        </div>
        <div class="modal-body" id="jobDetailsContent" style="padding: 20px;">
            <!-- Job details will be loaded here -->
        </div>
    </div>
</div>

<!-- Custom Confirmation Modal -->
<div id="confirmModal" class="modal" style="display: none; z-index: 10000;">
    <div class="modal-content" style="max-width: 500px;">
        <div class="modal-header">
            <h3 class="modal-title" id="confirmModalTitle">Confirm Action</h3>
        </div>
        <div class="modal-body">
            <p id="confirmModalMessage" style="font-size: 14px; line-height: 1.6;"></p>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-secondary" id="confirmModalCancel" style="margin-right: 10px;">Cancel</button>
            <button type="button" class="btn btn-success" id="confirmModalConfirm">Confirm</button>
        </div>
    </div>
</div>

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
        
        // Show estimated cost info as alert dialog
        const total = labor + parts;
        if (total > 0) {
            const message = `Service Cost Breakdown\n\nLabor Cost: ₱${labor.toFixed(2)}\nParts Cost: ₱${parts.toFixed(2)}\n\nEstimated Total: ₱${total.toFixed(2)}\nEstimated Duration: ${duration} minutes`;
            alert(message);
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
             alert('Server error while creating job order');
             console.error('Non-JSON response:', text);
             return;
         }

        const result = await response.json();

        if (result.success) {
            let message = result.message || 'Job order created successfully!';
            if (result.requires_approval) {
                message += ' (Pending admin approval)';
            }
            alert(message);
            event.target.reset();
            setTimeout(() => location.reload(), 1500);
        } else {
            alert(result.message);
        }
     } catch (error) {
         console.error('Error:', error);
         alert('Error creating job order');
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
            alert('Job order approved and validated!');
            setTimeout(() => location.reload(), 1000);
        } else {
            alert(result.message || 'Failed to approve job order');
        }
    } catch (error) {
        console.error('Error:', error);
        alert('Error approving job order');
    }
}

async function adminReviewReject(jobId) {
      const remarks = prompt('Enter rejection reason:');
      if (!remarks) {
          alert('Rejection reason is required');
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
             alert('Job order rejected');
             setTimeout(() => location.reload(), 1000);
         } else {
             alert(result.message || 'Failed to reject job order');
         }
     } catch (error) {
         console.error('Error:', error);
         alert('Error rejecting job order');
    }
}

async function confirmApproveJobOrder(jobId) {
    console.log('confirmApproveJobOrder called with jobId:', jobId);

    try {
        const confirmed = confirm('Are you sure you want to approve this job order?\n\nThis will move the job to "In Progress" status and the mechanic can begin working on it.');
        console.log('Confirm dialog returned:', confirmed);

        if (!confirmed) {
            console.log('User cancelled approval');
            return;
        }

        const remarks = prompt('Enter approval remarks (optional):');
        console.log('Remarks entered:', remarks);
        if (remarks === null) {
            console.log('User cancelled remarks prompt');
            return;
        }

        const formData = new FormData();
        formData.append('action', 'manager_review_approve');
        formData.append('job_id', jobId);
        if (remarks) formData.append('remarks', remarks);

        console.log('Submitting approve request...');

        const response = await fetch('joborder.php', {
            method: 'POST',
            body: formData
        });

        const result = await response.json();
         console.log('Approve response:', result);

         if (result.success) {
             alert('Job order approved and started!');
             setTimeout(() => location.reload(), 1000);
         } else {
             alert(result.message || 'Failed to approve job order');
         }
     } catch (error) {
         console.error('Error in confirmApproveJobOrder:', error);
         alert('Error approving job order');
    }
}

async function confirmRejectJobOrder(jobId) {
    console.log('confirmRejectJobOrder called with jobId:', jobId);

    try {
        const remarks = prompt('Enter rejection reason:');
         console.log('Remarks entered:', remarks);

         if (!remarks) {
             alert('Rejection reason is required');
             return;
         }

         const confirmed = confirm('Are you sure you want to reject this job order?\n\nThis will return to job to staff with your rejection reason.');
         console.log('Confirm dialog returned:', confirmed);

         if (!confirmed) {
             console.log('User cancelled rejection');
             return;
         }

         const formData = new FormData();
         formData.append('action', 'manager_review_reject');
         formData.append('job_id', jobId);
         formData.append('remarks', remarks);

         console.log('Submitting reject request...');

         const response = await fetch('joborder.php', {
             method: 'POST',
             body: formData
         });

         const result = await response.json();
         console.log('Reject response:', result);

         if (result.success) {
             alert('Job order rejected');
             setTimeout(() => location.reload(), 1000);
         } else {
             alert(result.message || 'Failed to reject job order');
         }
     } catch (error) {
         console.error('Error in confirmRejectJobOrder:', error);
         alert('Error rejecting job order');
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

 function addPartRowToCompletion() {
      const container = document.getElementById('partsListContainer');
     const row = document.createElement('div');
     row.className = 'part-row';
     row.style.cssText = 'display: grid; grid-template-columns: 2fr 1fr 1fr auto; gap: 10px; margin-bottom: 10px; align-items: end;';
     
     row.innerHTML = `
         <div class="form-group" style="margin: 0;">
             <label>Part/Product</label>
             <select class="form-select part-select" required onchange="validatePartStock(this)">
                 <option value="">Select Product</option>
                 <?php foreach ($products_list as $product): ?>
                     <option value="<?= $product['id'] ?>" data-price="<?= $product['price'] ?>" data-stock="<?= $product['stock_level'] ?>" data-name="<?= htmlspecialchars($product['name']) ?>">
                         <?= htmlspecialchars($product['name']) ?> (Stock: <?= $product['stock_level'] ?>)
                     </option>
                 <?php endforeach; ?>
             </select>
         </div>
         <div class="form-group" style="margin: 0;">
             <label>Quantity</label>
             <input type="number" class="form-input part-quantity" min="1" step="1" value="1" required onchange="validatePartStock(this.parentElement.parentElement.querySelector('.part-select'))">
         </div>
         <div class="form-group" style="margin: 0;">
             <label>Unit Cost</label>
             <input type="number" class="form-input part-cost" min="0" step="0.01" required readonly>
         </div>
         <button type="button" class="btn btn-danger" onclick="this.parentElement.remove()" style="height: 40px;">Remove</button>
     `;
     
     container.appendChild(row);
 }

 // Validate stock availability
 function validatePartStock(select) {
     const row = select.closest('.part-row');
     const quantityInput = row.querySelector('.part-quantity');
     const selectedOption = select.options[select.selectedIndex];
     
     // Reset styling
     select.style.borderColor = '';
     quantityInput.style.borderColor = '';
     
     if (selectedOption && select.value) {
         const availableStock = parseInt(selectedOption.getAttribute('data-stock')) || 0;
         const requestedQty = parseInt(quantityInput.value) || 0;
         
         if (requestedQty > availableStock) {
             alert(`Insufficient stock!\n\nProduct: ${selectedOption.getAttribute('data-name')}\nAvailable: ${availableStock}\nRequested: ${requestedQty}\n\nPlease reduce quantity or select a different product.`);
             select.style.borderColor = '#dc3545';
             quantityInput.style.borderColor = '#dc3545';
             return false;
         }
     }
     
     return true;
 }

 // Auto-fill cost and validate stock when product is selected
  document.addEventListener('change', function(event) {
      if (event.target.classList.contains('part-select')) {
          const row = event.target.closest('.part-row');
          const costInput = row.querySelector('.part-cost');
          const selectedOption = event.target.options[event.target.selectedIndex];
          const price = selectedOption.getAttribute('data-price') || 0;
          costInput.value = price;
      }
  }, true);

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
             alert(`Job completed! Total: ₱${result.billing.total_cost.toFixed(2)}`);
             document.getElementById('completionModal').style.display = 'none';
             setTimeout(() => location.reload(), 1500);
         } else {
             alert(result.message || 'Failed to complete job order');
         }
     } catch (error) {
         console.error('Error:', error);
         alert('Error completing job order');
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
             alert('Job order approved!');
             setTimeout(() => location.reload(), 1500);
         } else {
             alert(result.message);
         }
     } catch (error) {
         alert('Error approving job order');
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
             alert('Job order rejected!');
             setTimeout(() => location.reload(), 1500);
         } else {
             alert(result.message);
         }
     } catch (error) {
         alert('Error rejecting job order');
    }
}

let currentJobId = null;
let currentStatus = null;

/**
 * Confirm Status Change with Custom Dialog
 * @param {number} jobId - The job order ID
 * @param {string} newStatus - The new status to set
 * @param {string} title - Dialog title (e.g., "✅ Mark as Completed?")
 * @param {string} message - Dialog message with details
 */
function confirmStatusChange(jobId, newStatus, title, message) {
    console.log('confirmStatusChange called with:', { jobId, newStatus, title, message });

    const confirmModal = document.getElementById('confirmModal');
    const confirmTitle = document.getElementById('confirmModalTitle');
    const confirmMessage = document.getElementById('confirmModalMessage');
    const confirmBtn = document.getElementById('confirmModalConfirm');
    const cancelBtn = document.getElementById('confirmModalCancel');

    // Set modal content
    confirmTitle.textContent = title;
    confirmMessage.textContent = message;

    // Show modal
    console.log('Showing custom confirm modal...');
    confirmModal.style.display = 'block';

    // Store callback for confirm button
    confirmBtn.onclick = function() {
        console.log('User confirmed via modal, updating status to:', newStatus);
        confirmModal.style.display = 'none';
        updateJobStatus(jobId, newStatus);
    };

    cancelBtn.onclick = function() {
        console.log('User cancelled via modal');
        confirmModal.style.display = 'none';
    };
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

    console.log('Submitting status update:', { jobId, status, notes });

    try {
        const response = await fetch('joborder.php', {
            method: 'POST',
            body: formData
        });

        console.log('Response status:', response.status);
        const text = await response.text();
        console.log('Response text:', text);
        
         const result = JSON.parse(text);
         console.log('Parsed result:', result);

         if (result.success) {
             alert('Job status updated!');
             setTimeout(() => location.reload(), 1500);
         } else {
             alert(result.message || 'Failed to update status');
         }
     } catch (error) {
         console.error('Error updating job status:', error);
         alert('Error updating job status: ' + error.message);
    }
}

function completeJobOrder(jobId) {
    selectedParts = [];
    document.getElementById('job_id_for_completion').value = jobId;
    document.getElementById('partsListContainer').innerHTML = '';
    document.getElementById('completionModal').style.display = 'block';
    document.getElementById('actual_labor_hours').value = '0';
}

async function showJobDetails(jobId) {
    console.log('showJobDetails called for jobId:', jobId);

    try {
        // Show loading state
        const modal = document.getElementById('detailsModal');
        const content = document.getElementById('jobDetailsContent');
        content.innerHTML = '<div style="text-align:center; padding:40px;"><i class="fas fa-spinner fa-spin" style="font-size:32px;"></i><br><br>Loading job details...</div>';
        modal.style.display = 'block';

        // Fetch job details from backend
        const formData = new FormData();
        formData.append('action', 'get_job_details');
        formData.append('job_id', jobId);

        console.log('Sending request to backend...');

        const response = await fetch('joborder.php', {
            method: 'POST',
            body: formData
        });

        console.log('Response status:', response.status, 'OK:', response.ok);

        const text = await response.text();
        console.log('Raw response text:', text);

        // Try to parse JSON
        let result;
        try {
            result = JSON.parse(text);
            console.log('Parsed JSON:', result);
        } catch (parseError) {
            console.error('JSON parse error:', parseError);
            console.error('Response that failed to parse:', text);
            content.innerHTML = '<div style="text-align:center; padding:40px; color:var(--error);">Error: Invalid response from server. Please check console for details.</div>';
            return;
        }

        if (!result.success) {
            console.error('Request failed:', result);
            const errorMsg = result.message || 'Error loading job details';
            content.innerHTML = '<div style="text-align:center; padding:40px; color:var(--error);">' + errorMsg + '</div>';
            return;
        }

        if (!result.data) {
            console.error('No job data returned');
            content.innerHTML = '<div style="text-align:center; padding:40px; color:var(--muted);">Job order not found or has no details.</div>';
            return;
        }

        const job = result.data;
        renderJobDetails(job);

    } catch (error) {
        console.error('Error fetching job details:', error);
        const content = document.getElementById('jobDetailsContent');
        content.innerHTML = '<div style="text-align:center; padding:40px; color:var(--error);">Error: ' + error.message + '</div>';
    }
}

function renderJobDetails(job) {
    const content = document.getElementById('jobDetailsContent');
    const parts = job.parts_used || [];
    const actualLaborHours = parseFloat(job.actual_duration || job.estimated_duration || 0);
    const estimatedHours = parseFloat(job.estimated_duration || 0);
    const laborCost = parseFloat(job.actual_labor_cost || 0);
    const partsCost = parseFloat(job.total_parts_cost || 0);
    const totalCost = parseFloat(job.total_cost || 0);

    console.log('Rendering job details:', job);
    console.log('Parts:', parts, 'Count:', parts.length);
    console.log('Labor Hours:', actualLaborHours, 'Estimated:', estimatedHours);
    console.log('Costs - Labor:', laborCost, 'Parts:', partsCost, 'Total:', totalCost);

    // Build parts table HTML
    let partsTableHtml = '';
    if (parts.length > 0) {
        partsTableHtml = parts.map(part => `
            <tr>
                <td>
                    <div style="font-weight:600;">${escapeHtml(part.product_name || 'N/A')}</div>
                </td>
                <td style="text-align:center;">${part.quantity_used}</td>
                <td style="text-align:right;">${formatCurrency(part.unit_cost)}</td>
                <td style="text-align:right; font-weight:600;">${formatCurrency(part.total_cost)}</td>
            </tr>
        `).join('');

        partsTableHtml = `
            <table style="width:100%; border-collapse:collapse; margin-bottom:20px;">
                <thead>
                    <tr style="background:#f5f5f5; border-bottom:2px solid #ddd;">
                        <th style="padding:12px; text-align:left;">Product</th>
                        <th style="padding:12px; text-align:center;">Qty</th>
                        <th style="padding:12px; text-align:right;">Unit Cost</th>
                        <th style="padding:12px; text-align:right;">Total</th>
                    </tr>
                </thead>
                <tbody>
                    ${partsTableHtml}
                </tbody>
                <tfoot>
                    <tr style="background:#e8f4fd; font-weight:600; border-top:2px solid #ddd;">
                        <td colspan="3" style="padding:12px; text-align:right;">Total Parts Cost:</td>
                        <td style="padding:12px; text-align:right;">${formatCurrency(partsCost)}</td>
                    </tr>
                </tfoot>
            </table>
        `;
    } else {
        partsTableHtml = '<div style="background:#fff3cd; padding:15px; border-radius:5px; text-align:center; margin-bottom:20px;"><i class="fas fa-box-open"></i> No parts were used for this job.</div>';
    }

    // Build the complete details HTML
    content.innerHTML = `
        <div style="max-height:600px; overflow-y:auto; padding-right:10px;">
            <!-- Section 1: Job Order Information -->
            <div style="margin-bottom:25px;">
                <h4 style="margin:0 0 15px 0; color:var(--blue); display:flex; align-items:center; gap:10px;">
                    <i class="fas fa-clipboard-list"></i> Job Order Information
                </h4>
                <div style="display:grid; grid-template-columns:repeat(2, 1fr); gap:15px; background:#f9f9f9; padding:20px; border-radius:8px;">
                    <div>
                        <div style="font-size:12px; color:var(--muted); margin-bottom:5px;">Job Order ID</div>
                        <div style="font-weight:600; font-size:18px;">#${job.id}</div>
                    </div>
                    <div>
                        <div style="font-size:12px; color:var(--muted); margin-bottom:5px;">Job Order Number</div>
                        <div style="font-weight:600; font-size:18px;">${escapeHtml(job.job_order_number || 'N/A')}</div>
                    </div>
                    <div>
                        <div style="font-size:12px; color:var(--muted); margin-bottom:5px;">Customer</div>
                        <div style="font-weight:600; font-size:16px;">${escapeHtml(job.customer_name || 'Walk-in')}</div>
                        ${job.customer_phone ? `<div style="font-size:13px; color:var(--muted);"><i class="fas fa-phone"></i> ${escapeHtml(job.customer_phone)}</div>` : ''}
                        ${job.customer_email ? `<div style="font-size:13px; color:var(--muted);"><i class="fas fa-envelope"></i> ${escapeHtml(job.customer_email)}</div>` : ''}
                    </div>
                    <div>
                        <div style="font-size:12px; color:var(--muted); margin-bottom:5px;">Service Type</div>
                        <div style="font-weight:600; font-size:16px;">${escapeHtml(job.service_name || 'N/A')}</div>
                    </div>
                    <div>
                        <div style="font-size:12px; color:var(--muted); margin-bottom:5px;">Assigned Mechanic</div>
                        <div style="font-weight:600; font-size:16px;">${escapeHtml(job.mechanic_name || 'Unassigned')}</div>
                    </div>
                    <div>
                        <div style="font-size:12px; color:var(--muted); margin-bottom:5px;">Status</div>
                        <span class="status-badge status-${getStatusClass(job.status)}" style="display:inline-block; font-size:14px;">${escapeHtml(job.status)}</span>
                    </div>
                    <div>
                        <div style="font-size:12px; color:var(--muted); margin-bottom:5px;">Created</div>
                        <div style="font-weight:600;">${formatDate(job.created_at)}</div>
                    </div>
                    <div>
                        <div style="font-size:12px; color:var(--muted); margin-bottom:5px;">Started</div>
                        <div style="font-weight:600;">${formatDate(job.started_at)}</div>
                    </div>
                    <div>
                        <div style="font-size:12px; color:var(--muted); margin-bottom:5px;">Completed</div>
                        <div style="font-weight:600; color:#28a745;">${formatDate(job.completed_at)}</div>
                    </div>
                    <div>
                        <div style="font-size:12px; color:var(--muted); margin-bottom:5px;">Duration</div>
                        <div style="font-weight:600;">
                            ${actualLaborHours > 0 ? actualLaborHours + ' hrs' : 'N/A'}
                            ${estimatedHours > 0 ? ` <span style="color:var(--muted); font-weight:400;">(Est: ${estimatedHours} hrs)</span>` : ''}
                        </div>
                    </div>
                    <div>
                        <div style="font-size:12px; color:var(--muted); margin-bottom:5px;">Created By</div>
                        <div style="font-weight:600;">${escapeHtml(job.created_by_name || 'N/A')}</div>
                    </div>
                    <div>
                        <div style="font-size:12px; color:var(--muted); margin-bottom:5px;">Reviewed By</div>
                        <div style="font-weight:600;">${escapeHtml(job.reviewed_by_name || 'N/A')}</div>
                    </div>
                </div>
            </div>

            <!-- Section 2: Parts Used -->
            <div style="margin-bottom:25px;">
                <h4 style="margin:0 0 15px 0; color:var(--blue); display:flex; align-items:center; gap:10px;">
                    <i class="fas fa-boxes"></i> Parts Used
                </h4>
                ${partsTableHtml}
            </div>

            <!-- Section 3: Labor & Billing Breakdown -->
            <div style="margin-bottom:25px;">
                <h4 style="margin:0 0 15px 0; color:var(--blue); display:flex; align-items:center; gap:10px;">
                    <i class="fas fa-calculator"></i> Labor & Billing Breakdown
                </h4>
                <table style="width:100%; border-collapse:collapse; background:#f9f9f9; border-radius:8px; overflow:hidden;">
                    <tbody>
                        <tr style="border-bottom:1px solid #e0e0e0;">
                            <td colspan="2" style="padding:15px 20px; text-align:center;">
                                <div style="font-size:12px; color:var(--muted); margin-bottom:5px;">Labor Hours</div>
                                <div style="font-size:18px; font-weight:600;">
                                    ${actualLaborHours} hrs
                                    ${estimatedHours > 0 ? `<span style="color:var(--muted); font-weight:400; margin-left:10px;">(Estimated: ${estimatedHours} hrs)</span>` : ''}
                                </div>
                            </td>
                        </tr>
                        <tr style="border-bottom:1px solid #e0e0e0; background:#fff;">
                            <td style="padding:15px 20px;">
                                <div style="font-size:12px; color:var(--muted); margin-bottom:5px;">Labor Cost</div>
                                <div style="font-size:18px; font-weight:600; color:#007bff;">${formatCurrency(laborCost)}</div>
                            </td>
                            <td style="padding:15px 20px; text-align:right;">
                                <div style="font-size:12px; color:var(--muted); margin-bottom:5px;">Parts Cost</div>
                                <div style="font-size:18px; font-weight:600; color:#dc3545;">${formatCurrency(partsCost)}</div>
                            </td>
                        </tr>
                        <tr style="border-bottom:1px solid #e0e0e0;">
                            <td colspan="2" style="padding:15px 20px; text-align:right;">
                                <div style="font-size:12px; color:var(--muted); margin-bottom:5px;">Total Cost</div>
                                <div style="font-size:24px; font-weight:700; color:#28a745;">${formatCurrency(totalCost)}</div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Section 4: Notes -->
            ${job.notes ? `
            <div style="margin-bottom:25px;">
                <h4 style="margin:0 0 15px 0; color:var(--blue); display:flex; align-items:center; gap:10px;">
                    <i class="fas fa-sticky-note"></i> Notes
                </h4>
                <div style="background:#fff3cd; padding:15px; border-radius:8px; border-left:4px solid #ffc107; line-height:1.6;">
                    ${escapeHtml(job.notes).replace(/\n/g, '<br>')}
                </div>
            </div>
            ` : ''}
        </div>

        <!-- Footer with close button only (read-only) -->
        <div style="text-align:center; padding-top:20px; border-top:1px solid #e0e0e0;">
            <button type="button" class="btn btn-secondary" onclick="closeDetailsModal()" style="padding:10px 40px;">
                <i class="fas fa-times"></i> Close
            </button>
        </div>
    `;
}

// Helper functions for formatting
function formatCurrency(amount) {
    return '₱' + (parseFloat(amount) || 0).toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ',');
}

function formatDate(dateStr) {
    if (!dateStr) return 'N/A';
    const date = new Date(dateStr);
    return date.toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    });
}

function getStatusClass(status) {
    const s = (status || '').toLowerCase().replace(/\s+/g, '-');
    const validStatuses = ['pending', 'in-progress', 'completed', 'cancelled', 'rejected'];
    return validStatuses.includes(s) ? s : 'pending';
}

function escapeHtml(str) {
    if (!str) return '';
    const div = document.createElement('div');
    div.textContent = str;
    return div.innerHTML;
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

    alert('Exporting ' + format.toUpperCase() + '...');
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

window.addEventListener('click', function(event) {
    if (event.target.classList.contains('modal')) {
        event.target.style.display = 'none';
    }
    // Close confirm modal when clicking outside
    const confirmModal = document.getElementById('confirmModal');
    if (confirmModal && event.target === confirmModal) {
        confirmModal.style.display = 'none';
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
