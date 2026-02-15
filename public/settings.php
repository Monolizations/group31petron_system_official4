<?php
$page_id = 'settings';
require_once __DIR__ . '/../backend/lib.php';
require_once __DIR__ . '/../public/db_connect.php';
require_login();

$me = current_user();
$role = $me['role'] ?? 'staff';

// Only allow superadmin
if ($role !== 'superadmin') {
    header('Location: dashboard.php');
    exit;
}

// Get current section
$section = $_GET['section'] ?? 'service_rates';

// Handle form submissions
$notice = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'save_service') {
        $service_id = $_POST['service_id'] ?? '';
        $service_name = $_POST['service_name'] ?? '';
        $category = $_POST['category'] ?? '';
        $rate = (float)($_POST['rate'] ?? 0);
        $status = $_POST['status'] ?? 'active';
        $is_active = $status === 'active' ? 1 : 0;

        // Resolve or create service_category_id from category name
        $stmt = $pdo->prepare("SELECT id FROM service_categories WHERE name = ? LIMIT 1");
        $stmt->execute([$category]);
        $cat = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($cat) {
            $service_category_id = $cat['id'];
        } else {
            $pdo->prepare("INSERT INTO service_categories (name, description) VALUES (?, ?)")
                ->execute([$category, 'Imported category']);
            $service_category_id = $pdo->lastInsertId();
        }
        
        if ($service_id) {
            // Update existing service (map to current schema)
            $stmt = $pdo->prepare("UPDATE service_rates SET service_category_id = ?, rate_name = ?, flat_rate = ?, is_active = ?, updated_at = NOW() WHERE id = ?");
            $stmt->execute([$service_category_id, $service_name, $rate, $is_active, $service_id]);
            $notice = '✅ Service updated successfully';
        } else {
            // Add new service
            $stmt = $pdo->prepare("INSERT INTO service_rates (service_category_id, rate_name, flat_rate, is_active, created_at, updated_at) VALUES (?, ?, ?, ?, NOW(), NOW())");
            $stmt->execute([$service_category_id, $service_name, $rate, $is_active]);
            $notice = '✅ Service added successfully';
        }
        
        // Log activity
        try {
            $pdo->prepare("INSERT INTO activity_logs (user_id, action, details, ip_address) VALUES (?, ?, ?, ?)")
                ->execute([$me['id'], 'Service Rate Update', "$service_name - $category", $_SERVER['REMOTE_ADDR']]);
        } catch(Exception $e) {}
    }
    
    elseif ($action === 'delete_service') {
        $service_id = $_POST['service_id'] ?? '';
        $stmt = $pdo->prepare("DELETE FROM service_rates WHERE id = ?");
        $stmt->execute([$service_id]);
        $notice = '✅ Service deleted successfully';
        
        // Log activity
        try {
            $pdo->prepare("INSERT INTO activity_logs (user_id, action, details, ip_address) VALUES (?, ?, ?, ?)")
                ->execute([$me['id'], 'Service Rate Delete', "Service ID: $service_id", $_SERVER['REMOTE_ADDR']]);
        } catch(Exception $e) {}
    }
    
    elseif ($action === 'save_calibration') {
        $calibration_id = $_POST['calibration_id'] ?? '';
        $fuel_type = $_POST['fuel_type'] ?? '';
        $calibration_constant = (float)($_POST['calibration_constant'] ?? 0);
        $effective_date = $_POST['effective_date'] ?? date('Y-m-d');
        $status = $_POST['status'] ?? 'active';
        
        if ($calibration_id) {
            // Update existing calibration
            $stmt = $pdo->prepare("UPDATE fuel_calibration SET fuel_type = ?, calibration_constant = ?, effective_date = ?, status = ?, updated_at = NOW() WHERE id = ?");
            $stmt->execute([$fuel_type, $calibration_constant, $effective_date, $status, $calibration_id]);
            $notice = '✅ Calibration value updated successfully';
        } else {
            // Add new calibration
            $stmt = $pdo->prepare("INSERT INTO fuel_calibration (fuel_type, calibration_constant, effective_date, status, created_at, updated_at) VALUES (?, ?, ?, ?, NOW(), NOW())");
            $stmt->execute([$fuel_type, $calibration_constant, $effective_date, $status]);
            $notice = '✅ Calibration value added successfully';
        }
        
        // Log activity
        try {
            $pdo->prepare("INSERT INTO activity_logs (user_id, action, details, ip_address) VALUES (?, ?, ?, ?)")
                ->execute([$me['id'], 'Fuel Calibration Update', "$fuel_type - $calibration_constant", $_SERVER['REMOTE_ADDR']]);
        } catch(Exception $e) {}
    }
    
    elseif ($action === 'save_supplier') {
        $supplier_id = $_POST['supplier_id'] ?? '';
        $name = trim($_POST['name'] ?? '');
        $contact_person = trim($_POST['contact_person'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $address = trim($_POST['address'] ?? '');
        $is_default = isset($_POST['is_default']) ? 1 : 0;
        
        if (empty($name)) {
            $notice = '❌ Supplier name is required.';
        } else {
            if ($supplier_id) {
                // Update
                $stmt = $pdo->prepare("UPDATE suppliers SET name=?, contact_person=?, phone=?, email=?, address=? WHERE id=?");
                $stmt->execute([$name, $contact_person, $phone, $email, $address, $supplier_id]);
                
                // Update default if set
                if ($is_default) {
                    $pdo->prepare("UPDATE system_settings SET setting_value=?, updated_at=NOW(), updated_by=? WHERE setting_key='default_supplier_id'")->execute([$supplier_id, $me['id']]);
                }
                $notice = '✅ Supplier updated successfully';
            } else {
                // Create
                $stmt = $pdo->prepare("INSERT INTO suppliers (name, contact_person, phone, email, address, created_at) VALUES (?, ?, ?, ?, ?, NOW())");
                $stmt->execute([$name, $contact_person, $phone, $email, $address]);
                $new_id = $pdo->lastInsertId();
                
                // Set as default if marked
                if ($is_default) {
                    $pdo->prepare("UPDATE system_settings SET setting_value=?, updated_at=NOW(), updated_by=? WHERE setting_key='default_supplier_id'")->execute([$new_id, $me['id']]);
                }
                $notice = '✅ Supplier added successfully';
            }
            
            log_activity($pdo, $me['id'], 'Supplier Management', ($supplier_id ? "Updated supplier: $name" : "Created supplier: $name"), $_SERVER['REMOTE_ADDR']);
        }
    }
    
    elseif ($action === 'delete_supplier') {
        $supplier_id = $_POST['supplier_id'] ?? 0;
        $pdo->prepare("DELETE FROM suppliers WHERE id=?")->execute([$supplier_id]);
        
        // Reset default if deleted supplier was default
        $pdo->prepare("UPDATE system_settings SET setting_value=NULL, updated_at=NOW(), updated_by=? WHERE setting_key='default_supplier_id' AND setting_value=?")->execute([$me['id'], $supplier_id]);
        
        $notice = '✅ Supplier deleted successfully';
        log_activity($pdo, $me['id'], 'Supplier Management', "Deleted supplier ID: $supplier_id", $_SERVER['REMOTE_ADDR']);
    }
    
    elseif ($action === 'set_default_supplier') {
        $supplier_id = $_POST['supplier_id'] ?? 0;
        $pdo->prepare("UPDATE system_settings SET setting_value=?, updated_at=NOW(), updated_by=? WHERE setting_key='default_supplier_id'")->execute([$supplier_id, $me['id']]);
        $notice = '✅ Default supplier updated successfully';
        log_activity($pdo, $me['id'], 'Supplier Management', "Set default supplier ID: $supplier_id", $_SERVER['REMOTE_ADDR']);
    }
}

// Fetch data
$services = [];
$calibrations = [];
$suppliers_list = [];
$default_supplier_id = null;

try {
    $stmt = $pdo->query("SELECT sr.*, sc.name AS category FROM service_rates sr LEFT JOIN service_categories sc ON sr.service_category_id = sc.id ORDER BY sc.name, sr.rate_name");
    $services = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch(Exception $e) {
    // Create table if not exists (schema matching expected structure)
    $pdo->exec("CREATE TABLE IF NOT EXISTS service_rates (
        id INT AUTO_INCREMENT PRIMARY KEY,
        service_category_id INT DEFAULT NULL,
        station_id INT DEFAULT NULL,
        rate_name VARCHAR(100) NOT NULL,
        flat_rate DECIMAL(10,2) NOT NULL,
        estimated_duration INT DEFAULT 60,
        is_active TINYINT(1) DEFAULT 1,
        effective_date DATE DEFAULT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )");
}

try {
    $stmt = $pdo->query("SELECT * FROM fuel_calibration ORDER BY fuel_type, effective_date DESC");
    $calibrations = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch(Exception $e) {
    // Create table if not exists
    $pdo->exec("CREATE TABLE IF NOT EXISTS fuel_calibration (
        id INT AUTO_INCREMENT PRIMARY KEY,
        fuel_type ENUM('Gasoline', 'Diesel', 'LPG') NOT NULL,
        calibration_constant DECIMAL(10,6) NOT NULL,
        effective_date DATE NOT NULL,
        status ENUM('active', 'inactive') DEFAULT 'active',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )");
}

try {
    $stmt = $pdo->query("SELECT * FROM suppliers ORDER BY name");
    $suppliers_list = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch(Exception $e) {
    $suppliers_list = [];
}

try {
    $stmt = $pdo->query("SELECT CAST(setting_value AS UNSIGNED) FROM system_settings WHERE setting_key='default_supplier_id'");
    $default_supplier_id = $stmt->fetchColumn();
} catch(Exception $e) {
    $default_supplier_id = null;
}

include __DIR__ . '/../partials/header.php';
?>

<style>
    /* System Settings Styles */
    .system-settings-container {
        padding: 24px;
        max-width: 1400px;
        margin: 0 auto;
    }
    
    .section-header {
        margin-bottom: 32px;
    }
    
    .section-title {
        font-size: 28px;
        font-weight: 800;
        color: var(--petron-blue);
        margin-bottom: 8px;
    }
    
    .section-subtitle {
        color: var(--muted);
        font-size: 14px;
    }
    
    .filter-card {
        background: var(--card);
        border: 1px solid var(--line);
        border-radius: 16px;
        padding: 24px;
        margin-bottom: 24px;
        box-shadow: var(--shadow);
    }
    
    .filter-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 16px;
        margin-bottom: 16px;
    }
    
    .filter-actions {
        display: flex;
        justify-content: flex-end;
        gap: 12px;
    }
    
    .table-card {
        background: var(--card);
        border: 1px solid var(--line);
        border-radius: 16px;
        box-shadow: var(--shadow);
        overflow: hidden;
    }
    
    .table-header {
        padding: 20px 24px;
        border-bottom: 1px solid var(--line);
        display: flex;
        justify-content: space-between;
        align-items: center;
    }
    
    .table-title {
        font-size: 18px;
        font-weight: 700;
        color: var(--text);
    }
    
    .table-container {
        max-height: 500px;
        overflow-y: auto;
    }
    
    .settings-table {
        width: 100%;
        border-collapse: separate;
        border-spacing: 0;
    }
    
    .settings-table thead th {
        background: #f8fafc;
        padding: 12px 16px;
        text-align: left;
        font-weight: 600;
        color: var(--muted);
        font-size: 12px;
        border-bottom: 1px solid var(--line);
        position: sticky;
        top: 0;
        z-index: 10;
    }
    
    .settings-table tbody td {
        padding: 16px;
        border-bottom: 1px solid var(--line);
        font-size: 14px;
    }
    
    .settings-table tbody tr:hover {
        background: #f8fafc;
    }
    
    .badge {
        display: inline-flex;
        align-items: center;
        padding: 4px 12px;
        border-radius: 999px;
        font-size: 12px;
        font-weight: 600;
        border: 1px solid;
    }
    
    .badge-repair {
        background: rgba(59, 130, 246, 0.1);
        color: #2563eb;
        border-color: rgba(59, 130, 246, 0.2);
    }
    
    .badge-installation {
        background: rgba(16, 185, 129, 0.1);
        color: #059669;
        border-color: rgba(16, 185, 129, 0.2);
    }
    
    .badge-diagnostics {
        background: rgba(245, 158, 11, 0.1);
        color: #d97706;
        border-color: rgba(245, 158, 11, 0.2);
    }
    
    .badge-gasoline {
        background: rgba(239, 68, 68, 0.1);
        color: #dc2626;
        border-color: rgba(239, 68, 68, 0.2);
    }
    
    .badge-diesel {
        background: rgba(107, 114, 128, 0.1);
        color: #374151;
        border-color: rgba(107, 114, 128, 0.2);
    }
    
    .badge-lpg {
        background: rgba(168, 85, 247, 0.1);
        color: #9333ea;
        border-color: rgba(168, 85, 247, 0.2);
    }
    
    .badge-active {
        background: rgba(34, 197, 94, 0.1);
        color: #16a34a;
        border-color: rgba(34, 197, 94, 0.2);
    }
    
    .badge-inactive {
        background: rgba(239, 68, 68, 0.1);
        color: #dc2626;
        border-color: rgba(239, 68, 68, 0.2);
    }
    
    .action-buttons {
        display: flex;
        gap: 8px;
    }
    
    .btn-icon {
        width: 32px;
        height: 32px;
        border-radius: 8px;
        border: 1px solid var(--line);
        background: #fff;
        display: flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        transition: all 0.2s;
    }
    
    .btn-icon:hover {
        background: #f8fafc;
    }
    
    .btn-icon.edit {
        color: var(--petron-blue);
        border-color: rgba(0, 47, 108, 0.2);
    }
    
    .btn-icon.delete {
        color: #dc2626;
        border-color: rgba(220, 38, 38, 0.2);
    }
    
    .modal {
        position: fixed;
        inset: 0;
        background: rgba(0, 0, 0, 0.5);
        display: none;
        align-items: center;
        justify-content: center;
        z-index: 1000;
        padding: 20px;
    }
    
    .modal.show {
        display: flex;
    }
    
    .modal-content {
        background: var(--card);
        border-radius: 16px;
        width: 100%;
        max-width: 600px;
        max-height: 90vh;
        overflow-y: auto;
        box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1);
    }
    
    .modal-header {
        padding: 24px 24px 16px;
        border-bottom: 1px solid var(--line);
        display: flex;
        justify-content: space-between;
        align-items: center;
    }
    
    .modal-title {
        font-size: 20px;
        font-weight: 700;
        color: var(--text);
    }
    
    .modal-close {
        width: 32px;
        height: 32px;
        border-radius: 8px;
        border: none;
        background: #f8fafc;
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
        color: var(--muted);
    }
    
    .modal-body {
        padding: 24px;
    }
    
    .form-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 16px;
        margin-bottom: 24px;
    }
    
    .form-group {
        display: flex;
        flex-direction: column;
        gap: 8px;
    }
    
    .form-label {
        font-size: 14px;
        font-weight: 600;
        color: var(--text);
    }
    
    .form-input, .form-select {
        padding: 10px 14px;
        border: 1px solid var(--line);
        border-radius: 8px;
        font-size: 14px;
        background: #fff;
        outline: none;
        transition: border-color 0.2s;
    }
    
    .form-input:focus, .form-select:focus {
        border-color: var(--petron-blue);
        box-shadow: 0 0 0 3px rgba(0, 47, 108, 0.1);
    }
    
    .modal-footer {
        padding: 16px 24px;
        border-top: 1px solid var(--line);
        display: flex;
        justify-content: flex-end;
        gap: 12px;
    }
    
    .btn {
        padding: 10px 16px;
        border-radius: 8px;
        font-size: 14px;
        font-weight: 600;
        cursor: pointer;
        border: none;
        transition: all 0.2s;
        display: inline-flex;
        align-items: center;
        gap: 8px;
    }
    
    .btn-primary {
        background: var(--petron-blue);
        color: white;
    }
    
    .btn-primary:hover {
        background: #002455;
    }
    
    .btn-secondary {
        background: #f8fafc;
        color: var(--muted);
        border: 1px solid var(--line);
    }
    
    .btn-secondary:hover {
        background: #e2e8f0;
    }
    
    .btn-green {
        background: #16a34a;
        color: white;
    }
    
    .btn-green:hover {
        background: #15803d;
    }
    
    .toast {
        position: fixed;
        top: 20px;
        right: 20px;
        background: white;
        border: 1px solid var(--line);
        border-radius: 12px;
        padding: 16px;
        box-shadow: var(--shadow);
        display: none;
        align-items: center;
        gap: 12px;
        z-index: 2000;
        min-width: 300px;
    }
    
    .toast.show {
        display: flex;
    }
    
    .toast.success {
        border-left: 4px solid #16a34a;
    }
    
    .toast.error {
        border-left: 4px solid #dc2626;
    }
    
    .toast-icon {
        width: 20px;
        height: 20px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 12px;
        color: white;
        flex-shrink: 0;
    }
    
    .toast.success .toast-icon {
        background: #16a34a;
    }
    
    .toast.error .toast-icon {
        background: #dc2626;
    }
    
    .toast-message {
        flex: 1;
        font-size: 14px;
        color: var(--text);
    }
    
    .text-right {
        text-align: right;
    }
    
    .empty-state {
        padding: 60px 20px;
        text-align: center;
        color: var(--muted);
    }
    
    .empty-icon {
        font-size: 48px;
        margin-bottom: 16px;
        opacity: 0.3;
    }
    
    .empty-title {
        font-size: 18px;
        font-weight: 600;
        margin-bottom: 8px;
    }
    
    .empty-icon {
        font-size: 48px;
        margin-bottom: 16px;
        opacity: 0.3;
        color: var(--muted);
    }
    
    .empty-icon i {
        font-size: 48px;
        margin: 0;
        padding: 0;
    }
    
    .section-title {
        font-size: 28px;
        font-weight: 800;
        color: var(--petron-blue);
        margin-bottom: 8px;
        display: flex;
        align-items: center;
        gap: 8px;
    }
    
    .section-title i {
        font-size: 24px;
        margin: 0;
        padding: 0;
    }
    
    .settings-nav {
        background: white;
        border-radius: 12px;
        padding: 8px;
        margin-bottom: 32px;
        display: flex;
        gap: 8px;
        flex-wrap: wrap;
        box-shadow: var(--shadow);
    }
    
    .settings-nav-item {
        padding: 12px 20px;
        border-radius: 8px;
        cursor: pointer;
        font-weight: 600;
        font-size: 14px;
        color: var(--muted);
        transition: all 0.2s;
        display: flex;
        align-items: center;
        gap: 8px;
        text-decoration: none;
        border: 1px solid transparent;
    }
    
    .settings-nav-item:hover {
        background: #f8fafc;
        color: var(--text);
    }
    
    .settings-nav-item.active {
        background: var(--petron-blue);
        color: white;
        border-color: var(--petron-blue);
    }
    
    .settings-nav-item i {
        font-size: 16px;
    }
</style>

<div class="system-settings-container">
    <!-- Settings Navigation -->
    <nav class="settings-nav">
        <a href="?section=service_rates" class="settings-nav-item <?php echo ($section === 'service_rates' || empty($_GET['section'])) ? 'active' : ''; ?>">
            <i class="fas fa-tools"></i> Service Rates
        </a>
        <a href="?section=calibration" class="settings-nav-item <?php echo $section === 'calibration' ? 'active' : ''; ?>">
            <i class="fas fa-gas-pump"></i> Fuel Calibration
        </a>
        <a href="?section=suppliers" class="settings-nav-item <?php echo $section === 'suppliers' ? 'active' : ''; ?>">
            <i class="fas fa-truck"></i> Suppliers
        </a>
    </nav>
    <?php if ($notice): ?>
        <div class="toast success show" id="noticeToast">
            <div class="toast-icon"><i class="fas fa-check"></i></div>
            <div class="toast-message"><?php echo htmlspecialchars($notice); ?></div>
        </div>
    <?php endif; ?>
    
    <?php if ($section === 'service_rates'): ?>
        <!-- Service Rate Masterlist Section -->
        <div class="section-header">
            <h1 class="section-title"><i class="fas fa-tools"></i> Service Rate Masterlist</h1>
            <p class="section-subtitle">Manage pricing for services (repairs, installations, diagnostics)</p>
        </div>
        
        <!-- Filter Card -->
        <div class="filter-card">
            <form method="get">
                <input type="hidden" name="section" value="service_rates">
                <div class="filter-grid">
                    <div class="form-group">
                        <label class="form-label">Service Type</label>
                        <select name="service_type" class="form-select">
                            <option value="">All Categories</option>
                            <option value="Repair" <?php echo ($_GET['service_type'] ?? '') === 'Repair' ? 'selected' : ''; ?>>Repair</option>
                            <option value="Installation" <?php echo ($_GET['service_type'] ?? '') === 'Installation' ? 'selected' : ''; ?>>Installation</option>
                            <option value="Diagnostics" <?php echo ($_GET['service_type'] ?? '') === 'Diagnostics' ? 'selected' : ''; ?>>Diagnostics</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Status</label>
                        <select name="status" class="form-select">
                            <option value="">All Status</option>
                            <option value="active" <?php echo ($_GET['status'] ?? '') === 'active' ? 'selected' : ''; ?>>Active</option>
                            <option value="inactive" <?php echo ($_GET['status'] ?? '') === 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                        </select>
                    </div>
                </div>
                <div class="filter-actions">
                    <button type="button" class="btn btn-secondary" onclick="resetFilters()">Reset</button>
                    <button type="submit" class="btn btn-primary">Apply Filter</button>
                </div>
            </form>
        </div>
        
        <!-- Table Card -->
        <div class="table-card">
            <div class="table-header">
                <h2 class="table-title">Service Rates</h2>
                <button class="btn btn-primary" onclick="openServiceModal()">
                    <i class="fas fa-plus"></i> Add New Service
                </button>
            </div>
            <div class="table-container">
                <?php if (empty($services)): ?>
                    <div class="empty-state">
                        <div class="empty-icon"><i class="fas fa-tools"></i></div>
                        <div class="empty-title">No services found</div>
                        <div class="empty-description">Start by adding your first service rate</div>
                        <button class="btn btn-primary" onclick="openServiceModal()">Add Service</button>
                    </div>
                <?php else: ?>
                    <table class="settings-table">
                        <thead>
                            <tr>
                                <th>Service Name</th>
                                <th>Category</th>
                                <th>Rate (PHP)</th>
                                <th>Status</th>
                                <th>Last Updated</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($services as $service): ?>
                                <?php
                                // Apply filters
                                $service_type_filter = $_GET['service_type'] ?? '';
                                $status_filter = $_GET['status'] ?? '';
                                
                                $service_name_display = $service['rate_name'] ?? $service['service_name'] ?? '';
                                $service_category_display = $service['category'] ?? $service['category_name'] ?? '';
                                $service_rate_value = isset($service['flat_rate']) ? $service['flat_rate'] : ($service['rate'] ?? 0);
                                $service_status_display = (isset($service['is_active']) ? ($service['is_active'] ? 'active' : 'inactive') : ($service['status'] ?? 'inactive'));

                                if ($service_type_filter && $service_category_display !== $service_type_filter) continue;
                                if ($status_filter && $service_status_display !== $status_filter) continue;
                                ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($service_name_display); ?></td>
                                    <td>
                                        <span class="badge badge-<?php echo strtolower($service['category']); ?>">
                                            <?php echo htmlspecialchars($service_category_display); ?>
                                        </span>
                                    </td>
                                    <td class="text-right"><?php echo number_format($service_rate_value, 2); ?></td>
                                    <td>
                                        <span class="badge badge-<?php echo $service_status_display; ?>">
                                            <?php echo ucfirst($service_status_display); ?>
                                        </span>
                                    </td>
                                    <td><?php echo date('M j, Y H:i', strtotime($service['updated_at'] ?? ($service['updated_at'] ?? null))); ?></td>
                                    <td>
                                        <div class="action-buttons">
                                            <button class="btn-icon edit" onclick="editService(<?php echo $service['id']; ?>)">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <button class="btn-icon delete" onclick="deleteService(<?php echo $service['id']; ?>)">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
        </div>
        
    <?php elseif ($section === 'calibration'): ?>
        <!-- Fuel Calibration Values Section -->
        <div class="section-header">
            <h1 class="section-title"><i class="fas fa-gas-pump"></i> Fuel Calibration Values</h1>
            <p class="section-subtitle">Set and adjust calibration constants for fuel dispensing and reconciliation</p>
        </div>
        
        <!-- Filter Card -->
        <div class="filter-card">
            <form method="get">
                <input type="hidden" name="section" value="calibration">
                <div class="filter-grid">
                    <div class="form-group">
                        <label class="form-label">Fuel Type</label>
                        <select name="fuel_type" id="fuel_type_settings_1" class="form-select">
                            <option value="">All Fuel Types</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Station / Branch</label>
                        <select name="station" class="form-select">
                            <option value="">All Stations</option>
                            <!-- Station options would be populated here -->
                        </select>
                    </div>
                </div>
                <div class="filter-actions">
                    <button type="button" class="btn btn-secondary" onclick="resetFilters()">Reset</button>
                    <button type="submit" class="btn btn-primary">Apply Filter</button>
                </div>
            </form>
        </div>
        
        <!-- Table Card -->
        <div class="table-card">
            <div class="table-header">
                <h2 class="table-title">Fuel Calibration Values</h2>
                <button class="btn btn-primary" onclick="openCalibrationModal()">
                    <i class="fas fa-plus"></i> Add Calibration
                </button>
            </div>
            <div class="table-container">
                <?php if (empty($calibrations)): ?>
                    <div class="empty-state">
                        <div class="empty-icon"><i class="fas fa-gas-pump"></i></div>
                        <div class="empty-title">No calibration values found</div>
                        <div class="empty-description">Add your first fuel calibration value</div>
                        <button class="btn btn-primary" onclick="openCalibrationModal()">Add Calibration</button>
                    </div>
                <?php else: ?>
                    <table class="settings-table">
                        <thead>
                            <tr>
                                <th>Fuel Type</th>
                                <th>Calibration Constant</th>
                                <th>Effective Date</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($calibrations as $calibration): ?>
                                <?php
                                // Apply filters
                                $fuel_type_filter = $_GET['fuel_type'] ?? '';
                                
                                if ($fuel_type_filter && $calibration['fuel_type'] !== $fuel_type_filter) continue;
                                ?>
                                <tr>
                                    <td>
                                        <span class="badge badge-<?php echo strtolower($calibration['fuel_type']); ?>">
                                            <?php echo htmlspecialchars($calibration['fuel_type']); ?>
                                        </span>
                                    </td>
                                    <td class="text-right"><?php echo number_format($calibration['calibration_constant'], 6); ?></td>
                                    <td><?php echo date('M j, Y', strtotime($calibration['effective_date'])); ?></td>
                                    <td>
                                        <span class="badge badge-<?php echo $calibration['status']; ?>">
                                            <?php echo ucfirst($calibration['status']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div class="action-buttons">
                                            <button class="btn-icon edit" onclick="editCalibration(<?php echo $calibration['id']; ?>)">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
        </div>
    <?php elseif ($section === 'suppliers'): ?>
        <!-- Supplier Management Section -->
        <div class="section-header">
            <h1 class="section-title"><i class="fas fa-truck"></i> Supplier Management</h1>
            <p class="section-subtitle">Manage merchandise suppliers and configure default supplier</p>
        </div>
        
        <div class="table-card">
            <div class="table-header">
                <h2 class="table-title">Suppliers</h2>
                <button class="btn btn-primary" onclick="openSupplierModal()">
                    <i class="fas fa-plus"></i> Add Supplier
                </button>
            </div>
            <div class="table-container">
                <?php if (empty($suppliers_list)): ?>
                    <div class="empty-state">
                        <div class="empty-icon"><i class="fas fa-truck"></i></div>
                        <div class="empty-title">No suppliers found</div>
                        <div class="empty-description">Add your first supplier to configure merchandise receiving</div>
                        <button class="btn btn-primary" onclick="openSupplierModal()">Add Supplier</button>
                    </div>
                <?php else: ?>
                    <table class="settings-table">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Contact Person</th>
                                <th>Phone</th>
                                <th>Email</th>
                                <th>Default</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($suppliers_list as $supplier): ?>
                                <tr>
                                    <td>
                                        <?php echo htmlspecialchars($supplier['name']); ?>
                                        <?php if ($supplier['id'] == $default_supplier_id): ?>
                                            <span class="badge badge-active">Default</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo htmlspecialchars($supplier['contact_person'] ?? '-'); ?></td>
                                    <td><?php echo htmlspecialchars($supplier['phone'] ?? '-'); ?></td>
                                    <td><?php echo htmlspecialchars($supplier['email'] ?? '-'); ?></td>
                                    <td>
                                        <?php if ($supplier['id'] == $default_supplier_id): ?>
                                            <i class="fas fa-star" style="color: #f59e0b;"></i>
                                        <?php else: ?>
                                            <button class="btn-icon" onclick="setDefaultSupplier(<?php echo $supplier['id']; ?>)" title="Set as Default">
                                                <i class="far fa-star"></i>
                                            </button>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div class="action-buttons">
                                            <button class="btn-icon edit" onclick="editSupplier(<?php echo $supplier['id']; ?>)">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <button class="btn-icon delete" onclick="deleteSupplier(<?php echo $supplier['id']; ?>)">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
        </div>
    <?php endif; ?>
</div>

<!-- Service Modal -->
<div class="modal" id="serviceModal">
    <div class="modal-content">
        <div class="modal-header">
            <h3 class="modal-title" id="serviceModalTitle">Add Service</h3>
            <button class="modal-close" onclick="closeServiceModal()">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <form method="post" id="serviceForm">
            <input type="hidden" name="action" value="save_service">
            <input type="hidden" name="service_id" id="service_id">
            <div class="modal-body">
                <div class="form-grid">
                    <div class="form-group">
                        <label class="form-label">Service Name</label>
                        <input type="text" name="service_name" id="service_name" class="form-input" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Category</label>
                        <select name="category" id="service_category" class="form-select" required>
                            <option value="">Select Category</option>
                            <option value="Repair">Repair</option>
                            <option value="Installation">Installation</option>
                            <option value="Diagnostics">Diagnostics</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Rate (PHP)</label>
                        <input type="number" name="rate" id="service_rate" class="form-input" step="0.01" min="0" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Status</label>
                        <select name="status" id="service_status" class="form-select" required>
                            <option value="active">Active</option>
                            <option value="inactive">Inactive</option>
                        </select>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeServiceModal()">Cancel</button>
                <button type="submit" class="btn btn-primary">Save</button>
            </div>
        </form>
    </div>
</div>

<!-- Calibration Modal -->
<div class="modal" id="calibrationModal">
    <div class="modal-content">
        <div class="modal-header">
            <h3 class="modal-title" id="calibrationModalTitle">Add Calibration Value</h3>
            <button class="modal-close" onclick="closeCalibrationModal()">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <form method="post" id="calibrationForm">
            <input type="hidden" name="action" value="save_calibration">
            <input type="hidden" name="calibration_id" id="calibration_id">
            <div class="modal-body">
                <div class="form-grid">
                    <div class="form-group">
                        <label class="form-label">Fuel Type</label>
                        <select name="fuel_type" id="fuel_type_settings_2" class="form-select" required>
                            <option value="">Select Fuel Type</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Effective Date</label>
                        <input type="date" name="effective_date" id="effective_date" class="form-input" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Calibration Constant</label>
                        <input type="number" name="calibration_constant" id="calibration_constant" class="form-input" step="0.000001" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Status</label>
                        <select name="status" id="calibration_status" class="form-select" required>
                            <option value="active">Active</option>
                            <option value="inactive">Inactive</option>
                        </select>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeCalibrationModal()">Cancel</button>
                <button type="submit" class="btn btn-primary">Save</button>
            </div>
        </form>
    </div>
</div>

<!-- Delete Confirmation Modal -->
<div class="modal" id="deleteModal">
    <div class="modal-content">
        <div class="modal-header">
            <h3 class="modal-title">Confirm Delete</h3>
            <button class="modal-close" onclick="closeDeleteModal()">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <div class="modal-body">
            <p>Are you sure you want to delete this service? This action cannot be undone.</p>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-secondary" onclick="closeDeleteModal()">Cancel</button>
            <form method="post" id="deleteForm" style="display: inline;">
                <input type="hidden" name="action" value="delete_service">
                <input type="hidden" name="service_id" id="delete_service_id">
                <button type="submit" class="btn btn-primary" style="background: #dc2626;">Delete</button>
            </form>
        </div>
    </div>
</div>

<!-- Supplier Modal -->
<div class="modal" id="supplierModal">
    <div class="modal-content">
        <div class="modal-header">
            <h3 class="modal-title" id="supplierModalTitle">Add Supplier</h3>
            <button class="modal-close" onclick="closeSupplierModal()">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <form method="post" id="supplierForm">
            <input type="hidden" name="action" value="save_supplier">
            <input type="hidden" name="supplier_id" id="supplier_id">
            <div class="modal-body">
                <div class="form-grid">
                    <div class="form-group">
                        <label class="form-label">Supplier Name *</label>
                        <input type="text" name="name" id="supplier_name" class="form-input" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Contact Person</label>
                        <input type="text" name="contact_person" id="supplier_contact" class="form-input">
                    </div>
                </div>
                <div class="form-grid">
                    <div class="form-group">
                        <label class="form-label">Phone</label>
                        <input type="text" name="phone" id="supplier_phone" class="form-input">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Email</label>
                        <input type="email" name="email" id="supplier_email" class="form-input">
                    </div>
                </div>
                <div class="form-group">
                    <label class="form-label">Address</label>
                    <textarea name="address" id="supplier_address" class="form-input" rows="3"></textarea>
                </div>
                <div class="form-group">
                    <label class="form-label">
                        <input type="checkbox" name="is_default" id="supplier_default" value="1">
                        Set as Default Supplier
                    </label>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeSupplierModal()">Cancel</button>
                <button type="submit" class="btn btn-primary">Save Supplier</button>
            </div>
        </form>
    </div>
</div>

<script>
// Service Modal Functions
function openServiceModal() {
    document.getElementById('serviceModal').classList.add('show');
    document.getElementById('serviceModalTitle').textContent = 'Add Service';
    document.getElementById('serviceForm').reset();
    document.getElementById('service_id').value = '';
}

function closeServiceModal() {
    document.getElementById('serviceModal').classList.remove('show');
}

function editService(id) {
    // Fetch service data and populate modal
    fetch(`get_service.php?id=${id}`)
        .then(response => response.json())
        .then(data => {
            document.getElementById('serviceModalTitle').textContent = 'Edit Service';
            document.getElementById('service_id').value = data.id;
            document.getElementById('service_name').value = data.service_name;
            document.getElementById('service_category').value = data.category;
            document.getElementById('service_rate').value = data.rate;
            document.getElementById('service_status').value = data.status;
            document.getElementById('serviceModal').classList.add('show');
        })
        .catch(error => {
            console.error('Error fetching service:', error);
            showToast('Error fetching service data', 'error');
        });
}

function deleteService(id) {
    document.getElementById('delete_service_id').value = id;
    document.getElementById('deleteModal').classList.add('show');
}

function closeDeleteModal() {
    document.getElementById('deleteModal').classList.remove('show');
}

// Calibration Modal Functions
function openCalibrationModal() {
    document.getElementById('calibrationModal').classList.add('show');
    document.getElementById('calibrationModalTitle').textContent = 'Add Calibration Value';
    document.getElementById('calibrationForm').reset();
    document.getElementById('calibration_id').value = '';
    document.getElementById('effective_date').value = new Date().toISOString().split('T')[0];
}

function closeCalibrationModal() {
    document.getElementById('calibrationModal').classList.remove('show');
}

function editCalibration(id) {
    // Fetch calibration data and populate modal
    fetch(`get_calibration.php?id=${id}`)
        .then(response => response.json())
        .then(data => {
            document.getElementById('calibrationModalTitle').textContent = 'Edit Calibration Value';
            document.getElementById('calibration_id').value = data.id;
            document.getElementById('fuel_type').value = data.fuel_type;
            document.getElementById('calibration_constant').value = data.calibration_constant;
            document.getElementById('effective_date').value = data.effective_date;
            document.getElementById('calibration_status').value = data.status;
            document.getElementById('calibrationModal').classList.add('show');
        })
        .catch(error => {
            console.error('Error fetching calibration:', error);
            showToast('Error fetching calibration data', 'error');
        });
}

// Utility Functions
function resetFilters() {
    window.location.href = window.location.pathname + '?section=<?php echo $section; ?>';
}

function showToast(message, type = 'success') {
    const toast = document.createElement('div');
    toast.className = `toast ${type} show`;
    toast.innerHTML = `
        <div class="toast-icon">${type === 'success' ? '✓' : '⚠'}</div>
        <div class="toast-message">${message}</div>
    `;
    document.body.appendChild(toast);
    
    setTimeout(() => {
        toast.remove();
    }, 3000);
}

// Close modals when clicking outside
document.addEventListener('click', function(event) {
    if (event.target.classList.contains('modal')) {
        event.target.classList.remove('show');
    }
});

// Supplier Modal Functions
function openSupplierModal() {
    document.getElementById('supplierModal').classList.add('show');
    document.getElementById('supplierModalTitle').textContent = 'Add Supplier';
    document.getElementById('supplierForm').reset();
    document.getElementById('supplier_id').value = '';
}

function closeSupplierModal() {
    document.getElementById('supplierModal').classList.remove('show');
}

function editSupplier(id) {
    // For simplicity, just show modal with supplier list data
    // In production, you might want to fetch supplier data via AJAX
    const suppliers = <?php echo json_encode($suppliers_list); ?>;
    const supplier = suppliers.find(s => s.id === id);
    
    if (supplier) {
        document.getElementById('supplierModalTitle').textContent = 'Edit Supplier';
        document.getElementById('supplier_id').value = supplier.id;
        document.getElementById('supplier_name').value = supplier.name;
        document.getElementById('supplier_contact').value = supplier.contact_person || '';
        document.getElementById('supplier_phone').value = supplier.phone || '';
        document.getElementById('supplier_email').value = supplier.email || '';
        document.getElementById('supplier_address').value = supplier.address || '';
        document.getElementById('supplier_default').checked = (supplier.id === <?php echo $default_supplier_id; ?>);
        document.getElementById('supplierModal').classList.add('show');
    }
}

function deleteSupplier(id) {
    if (confirm('Are you sure you want to delete this supplier?')) {
        const formData = new FormData();
        formData.append('action', 'delete_supplier');
        formData.append('supplier_id', id);
        
        fetch('settings.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.text())
        .then(() => {
            window.location.reload();
        });
    }
}

function setDefaultSupplier(id) {
    if (confirm('Set this supplier as the default for receiving?')) {
        const formData = new FormData();
        formData.append('action', 'set_default_supplier');
        formData.append('supplier_id', id);
        
        fetch('settings.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.text())
        .then(() => {
            window.location.reload();
        });
    }
}

// Auto-hide notice toast
<?php if ($notice): ?>
setTimeout(() => {
    const noticeToast = document.getElementById('noticeToast');
    if (noticeToast) {
        noticeToast.remove();
    }
}, 3000);
<?php endif; ?>
</script>

<script src="../assets/js/data_helper.js"></script>

<script>
document.addEventListener('DOMContentLoaded', function() {
    DataHelper.populateFuelTypes('fuel_type_settings_1', 'All Fuel Types');
    DataHelper.populateFuelTypes('fuel_type_settings_2', 'Select Fuel Type');
});
</script>

<?php include __DIR__ . '/../partials/footer.php'; ?>
