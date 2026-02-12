<?php
$page_id = 'users';
require_once __DIR__ . '/../backend/lib.php';
require_once __DIR__ . '/../backend/rbac.php';
require_once __DIR__ . '/../public/db_connect.php';
require_login();
require_permission(VIEW_ALL_USERS);

$me = current_user();
$isSuper = (($me['role'] ?? '') === 'superadmin');
$myStationId = $me['station_id'] ?? null;
$notice = '';

// CSRF Token
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Handle actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $notice = "Error: Invalid request.";
    } else {
        $action = $_POST['action'] ?? '';
        
        if ($action === 'create_station_admin') {
            $station_id = $_POST['station_id'] ?? '';
            $admin_name = trim($_POST['admin_name'] ?? '');
            $admin_username = trim($_POST['admin_username'] ?? '');
            $admin_email = trim($_POST['admin_email'] ?? '');
            $admin_phone = trim($_POST['admin_phone'] ?? '');
            $admin_role = $_POST['role'] ?? 'admin';
            $status = $_POST['status'] ?? 'active';
            
            if (empty($station_id) || empty($admin_name) || empty($admin_username) || empty($admin_email)) {
                $notice = "All required fields must be filled.";
            } elseif (!filter_var($admin_email, FILTER_VALIDATE_EMAIL)) {
                $notice = "Invalid email address.";
            } else {
                try {
                    $station = $pdo->prepare("SELECT name FROM stations WHERE id = ?");
                    $station->execute([$station_id]);
                    $station_name = $station->fetchColumn();
                    
                    if (!$station_name) {
                        $notice = "Invalid station selected.";
                    } else {
                        $chk = $pdo->prepare("SELECT id FROM users WHERE username = ?");
                        $chk->execute([$admin_username]);
                        
                        if ($chk->rowCount() > 0) {
                            $notice = "Username already exists.";
                        } else {
                            $password = password_hash('Admin123!', PASSWORD_DEFAULT);
                            $stmt = $pdo->prepare("INSERT INTO users (username, password, name, email, phone, role, station_id, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
                            $stmt->execute([$admin_username, $password, $admin_name, $admin_email, $admin_phone, $admin_role, $station_id, $status]);
                            
                            log_activity($pdo, $me['id'], 'Create Station Admin', "Created admin '$admin_username' for station '$station_name'");
                            $notice = [
                                'type' => 'success',
                                'message' => "✅ Station Admin created successfully!",
                                'details' => "Default password: Admin123!"
                            ];
                        }
                    }
                } catch (Exception $e) {
                    $notice = "Error: " . $e->getMessage();
                }
            }
        }
    }
}

// Fetch data
$stations = $pdo->query("SELECT id, name FROM stations WHERE status = 'active' ORDER BY name")->fetchAll(PDO::FETCH_KEY_PAIR);

// Users list
$search = trim($_GET['q'] ?? '');
$role_filter = $_GET['role'] ?? '';
$station_filter = $_GET['station'] ?? '';
$status_filter = $_GET['status'] ?? '';
$page = max(1, intval($_GET['page'] ?? 1));
$per_page = 10;
$offset = ($page - 1) * $per_page;

$where = "1";
$params = [];

if ($search !== '') {
    $where .= " AND (u.username LIKE ? OR u.name LIKE ? OR u.email LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

if ($role_filter !== '') {
    $where .= " AND u.role = ?";
    $params[] = $role_filter;
}

if ($station_filter !== '') {
    $where .= " AND u.station_id = ?";
    $params[] = $station_filter;
}

if ($status_filter !== '') {
    $where .= " AND u.status = ?";
    $params[] = $status_filter;
}

if (!$isSuper) {
    $where .= " AND u.station_id = ?";
    $params[] = $myStationId;
}

$countStmt = $pdo->prepare("SELECT COUNT(*) FROM users u WHERE $where");
$countStmt->execute($params);
$total_rows = $countStmt->fetchColumn();
$total_pages = ceil($total_rows / $per_page);

$sql = "SELECT u.*, s.name as station_name 
        FROM users u 
        LEFT JOIN stations s ON u.station_id = s.id 
        WHERE $where 
        ORDER BY u.id DESC 
        LIMIT $per_page OFFSET $offset";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$users = $stmt->fetchAll();

function format_last_login($last_login) {
    if (!$last_login || $last_login === '1970-01-01 00:00:00') {
        return 'Never';
    }
    return date('M d, Y H:i', strtotime($last_login));
}

include __DIR__ . '/../partials/header.php';
?>

<style>
    .main-container { max-height: calc(100vh - 120px); overflow-y: auto; padding: 20px; }
    .page-header { background: linear-gradient(135deg, var(--petron-blue), #001a4d); color: white; padding: 24px; border-radius: 12px; margin-bottom: 24px; box-shadow: 0 8px 24px rgba(0, 47, 108, 0.2); }
    .page-title { font-size: 28px; font-weight: 700; margin: 0; display: flex; align-items: center; gap: 12px; }
    .page-subtitle { font-size: 16px; opacity: 0.9; margin-top: 8px; }
    .action-cards { display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 20px; margin-bottom: 32px; }
    .action-card { background: white; border-radius: 12px; padding: 24px; box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1); transition: all 0.3s ease; border: 2px solid transparent; cursor: pointer; }
    .action-card:hover { transform: translateY(-4px); box-shadow: 0 8px 24px rgba(0, 0, 0, 0.15); border-color: var(--petron-blue); }
    .action-card-icon { font-size: 32px; margin-bottom: 16px; color: var(--petron-blue); }
    .action-card-title { font-size: 18px; font-weight: 600; margin-bottom: 8px; color: #333; }
    .action-card-description { font-size: 14px; color: #666; line-height: 1.5; }
    .filters-section { background: white; border-radius: 12px; padding: 20px; margin-bottom: 24px; box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1); }
    .filters-row { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 16px; align-items: end; }
    .filter-group { display: flex; flex-direction: column; }
    .filter-label { font-size: 14px; font-weight: 600; color: #333; margin-bottom: 6px; }
    .filter-input, .filter-select { padding: 10px 12px; border: 2px solid #e0e0e0; border-radius: 8px; font-size: 14px; transition: all 0.3s ease; }
    .filter-input:focus, .filter-select:focus { outline: none; border-color: var(--petron-blue); box-shadow: 0 0 0 3px rgba(0, 47, 108, 0.1); }
    .users-table-container { background: white; border-radius: 12px; padding: 20px; box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1); overflow: hidden; }
    .table-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; }
    .table-title { font-size: 20px; font-weight: 600; color: #333; }
    .users-table { width: 100%; border-collapse: collapse; font-size: 14px; }
    .users-table th { background: #f8f9fa; padding: 12px; text-align: left; font-weight: 600; color: #333; border-bottom: 2px solid #dee2e6; }
    .users-table td { padding: 12px; border-bottom: 1px solid #f0f0f0; vertical-align: middle; }
    .users-table tr:hover { background: #f8f9ff; }
    .status-badge { padding: 4px 12px; border-radius: 20px; font-size: 12px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px; }
    .status-badge.active { background: #d4edda; color: #155724; }
    .status-badge.inactive { background: #f8d7da; color: #721c24; }
    .status-badge.pending { background: #fff3cd; color: #856404; }
    .role-badge { padding: 4px 8px; border-radius: 6px; font-size: 11px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px; }
    .role-badge.superadmin { background: #dc3545; color: white; }
    .role-badge.admin { background: #007bff; color: white; }
    .role-badge.manager { background: #28a745; color: white; }
    .role-badge.mechanic { background: #fd7e14; color: white; }
    .role-badge.staff { background: #6c757d; color: white; }
    .action-buttons { display: flex; gap: 8px; flex-wrap: wrap; }
    .btn-action { padding: 6px 10px; border: none; border-radius: 6px; font-size: 12px; cursor: pointer; transition: all 0.2s ease; display: inline-flex; align-items: center; gap: 4px; }
    .btn-view { background: #e3f2fd; color: #1976d2; }
    .btn-view:hover { background: #bbdefb; }
    .btn-reset { background: #fff3e0; color: #f57c00; }
    .btn-reset:hover { background: #ffe0b2; }
    .btn-activate { background: #e8f5e8; color: #2e7d32; }
    .btn-activate:hover { background: #c8e6c9; }
    .btn-deactivate { background: #ffebee; color: #c62828; }
    .btn-deactivate:hover { background: #ffcdd2; }
    .btn { padding: 10px 20px; border: none; border-radius: 8px; font-size: 14px; font-weight: 600; cursor: pointer; transition: all 0.3s ease; display: inline-flex; align-items: center; gap: 8px; }
    .btn-primary { background: linear-gradient(135deg, #007bff, #0056b3); color: white; box-shadow: 0 4px 12px rgba(0, 123, 255, 0.3); }
    .btn-primary:hover { transform: translateY(-2px); box-shadow: 0 6px 16px rgba(0, 123, 255, 0.4); }
    .btn-secondary { background: #f8f9fa; color: #666; border: 1px solid #dee2e6; }
    .btn-secondary:hover { background: #e9ecef; color: #495057; }
    .toast { position: fixed; top: 20px; right: 20px; padding: 16px 20px; border-radius: 8px; color: white; font-weight: 500; z-index: 2000; animation: toastSlideIn 0.3s ease; max-width: 400px; }
    .toast.success { background: linear-gradient(135deg, #28a745, #20c997); }
    .toast.error { background: linear-gradient(135deg, #dc3545, #c82333); }
    @keyframes toastSlideIn { from { opacity: 0; transform: translateX(100%); } to { opacity: 1; transform: translateX(0); } }
</style>

<div class="main-container">
    <!-- Page Header -->
    <div class="page-header">
        <h1 class="page-title">
            <i class="fas fa-users-cog"></i>
            User Management (HQ)
        </h1>
        <div class="page-subtitle">Super Admin control center for accounts, roles, and credentials.</div>
    </div>

    <!-- Success Messages -->
    <?php if ($notice !== ''): ?>
        <?php if (is_array($notice)): ?>
            <div class="toast <?php echo $notice['type']; ?>" id="noticeToast">
                <div><?php echo $notice['message']; ?></div>
                <?php if (!empty($notice['details'])): ?>
                    <div style="font-size: 12px; opacity: 0.9; margin-top: 4px;"><?php echo $notice['details']; ?></div>
                <?php endif; ?>
            </div>
            <script>
                setTimeout(() => {
                    const toast = document.getElementById('noticeToast');
                    if (toast) {
                        toast.style.animation = 'toastSlideIn 0.3s ease reverse';
                        setTimeout(() => toast.remove(), 300);
                    }
                }, 5000);
            </script>
        <?php endif; ?>
    <?php endif; ?>

    <!-- Action Cards -->
    <div class="action-cards">
        <div class="action-card" onclick="window.location.href='users.php?action=create_station_admin'">
            <div class="action-card-icon">👤</div>
            <div class="action-card-title">Create Station Admin</div>
            <div class="action-card-description">Create new admin accounts for specific stations with full management access.</div>
        </div>
        
        <div class="action-card" onclick="window.location.href='users.php?action=create_defaults'">
            <div class="action-card-icon">⚙️</div>
            <div class="action-card-title">Auto-create Default Manager & Staff</div>
            <div class="action-card-description">Automatically generate default manager and staff accounts for any station.</div>
        </div>
        
        <div class="action-card" onclick="window.location.href='admin_reset_password.php'">
            <div class="action-card-icon">🔑</div>
            <div class="action-card-title">Reset Password</div>
            <div class="action-card-description">Generate new temporary passwords for any user account with security verification.</div>
        </div>
        
        <div class="action-card" onclick="window.location.href='users.php?action=manage_status'">
            <div class="action-card-icon">✅❌</div>
            <div class="action-card-title">Activate / Deactivate Users</div>
            <div class="action-card-description">Manage user account status with confirmation and audit logging.</div>
        </div>
    </div>

    <!-- Filters Section -->
    <div class="filters-section">
        <div class="filters-row">
            <div class="filter-group">
                <label class="filter-label">Search</label>
                <input type="text" class="filter-input" placeholder="Name, username, or email..." value="<?php echo htmlspecialchars($search); ?>">
            </div>
            
            <div class="filter-group">
                <label class="filter-label">Role</label>
                <select class="filter-select">
                    <option value="">All Roles</option>
                    <option value="superadmin" <?php echo $role_filter === 'superadmin' ? 'selected' : ''; ?>>Super Admin</option>
                    <option value="admin" <?php echo $role_filter === 'admin' ? 'selected' : ''; ?>>Admin</option>
                    <option value="manager" <?php echo $role_filter === 'manager' ? 'selected' : ''; ?>>Manager</option>
                    <option value="mechanic" <?php echo $role_filter === 'mechanic' ? 'selected' : ''; ?>>Mechanic</option>
                    <option value="staff" <?php echo $role_filter === 'staff' ? 'selected' : ''; ?>>Staff</option>
                </select>
            </div>
            
            <?php if ($isSuper): ?>
            <div class="filter-group">
                <label class="filter-label">Station</label>
                <select class="filter-select">
                    <option value="">All Stations</option>
                    <?php foreach ($stations as $id => $name): ?>
                        <option value="<?php echo $id; ?>" <?php echo $station_filter === $id ? 'selected' : ''; ?>><?php echo htmlspecialchars($name); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <?php endif; ?>
            
            <div class="filter-group">
                <label class="filter-label">Status</label>
                <select class="filter-select">
                    <option value="">All Status</option>
                    <option value="active" <?php echo $status_filter === 'active' ? 'selected' : ''; ?>>Active</option>
                    <option value="inactive" <?php echo $status_filter === 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                    <option value="pending" <?php echo $status_filter === 'pending' ? 'selected' : ''; ?>>Pending</option>
                </select>
            </div>
            
            <div class="filter-group">
                <button class="btn btn-primary" onclick="applyFilters()">
                    <i class="fas fa-filter"></i> Apply Filters
                </button>
            </div>
        </div>
    </div>

    <!-- Users Table -->
    <div class="users-table-container">
        <div class="table-header">
            <div class="table-title">📋 View All Users</div>
            <div style="display: flex; gap: 12px;">
                <button class="btn btn-secondary" onclick="exportUsers()">
                    <i class="fas fa-download"></i> Export
                </button>
                <button class="btn btn-primary" onclick="window.location.href='users.php?action=create_station_admin'">
                    <i class="fas fa-user-plus"></i> Create User
                </button>
            </div>
        </div>
        
        <table class="users-table">
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Username</th>
                    <th>Email</th>
                    <th>Role</th>
                    <?php if ($isSuper): ?><th>Station</th><?php endif; ?>
                    <th>Status</th>
                    <th>Last Login</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($users as $user): ?>
                <tr>
                    <td>
                        <div style="font-weight: 600;"><?php echo htmlspecialchars($user['name']); ?></div>
                    </td>
                    <td><?php echo htmlspecialchars($user['username']); ?></td>
                    <td><?php echo htmlspecialchars($user['email'] ?? 'N/A'); ?></td>
                    <td>
                        <span class="role-badge <?php echo $user['role']; ?>">
                            <?php echo ucfirst($user['role']); ?>
                        </span>
                    </td>
                    <?php if ($isSuper): ?>
                    <td><?php echo htmlspecialchars($user['station_name'] ?? 'Head Office'); ?></td>
                    <?php endif; ?>
                    <td>
                        <span class="status-badge <?php echo $user['status']; ?>">
                            <?php echo ucfirst($user['status']); ?>
                        </span>
                    </td>
                    <td><?php echo format_last_login($user['last_login']); ?></td>
                    <td>
                        <div class="action-buttons">
                            <button class="btn-action btn-view" onclick="viewUser(<?php echo $user['id']; ?>)">
                                <i class="fas fa-eye"></i> View
                            </button>
                            <button class="btn-action btn-reset" onclick="resetPassword(<?php echo $user['id']; ?>, '<?php echo htmlspecialchars($user['username']); ?>')">
                                <i class="fas fa-key"></i> Reset
                            </button>
                            <?php if ($user['status'] === 'active'): ?>
                                <button class="btn-action btn-deactivate" onclick="changeStatus(<?php echo $user['id']; ?>, 'inactive', '<?php echo htmlspecialchars($user['username']); ?>')">
                                    <i class="fas fa-ban"></i> Deactivate
                                </button>
                            <?php else: ?>
                                <button class="btn-action btn-activate" onclick="changeStatus(<?php echo $user['id']; ?>, 'active', '<?php echo htmlspecialchars($user['username']); ?>')">
                                    <i class="fas fa-check"></i> Activate
                                </button>
                            <?php endif; ?>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
function applyFilters() {
    const search = document.querySelector('.filter-input').value;
    const selects = document.querySelectorAll('.filter-select');
    const role = selects[0]?.value || '';
    const station = selects[1]?.value || '';
    const status = selects[2]?.value || '';
    
    const params = new URLSearchParams({ q: search, role, station, status });
    window.location.href = `users_final.php?${params.toString()}`;
}

function exportUsers() {
    window.location.href = `users_final.php?export=1&${new URLSearchParams(window.location.search).toString()}`;
}

function viewUser(userId) {
    window.location.href = `users_final.php?view=${userId}`;
}

function resetPassword(userId, username) {
    window.location.href = `admin_reset_password.php?user=${userId}`;
}

function changeStatus(userId, newStatus, username) {
    if (confirm(`Change status for ${username} to ${newStatus}?`)) {
        // Implement status change logic
        console.log('Status change:', { userId, newStatus, username });
    }
}
</script>

<?php include __DIR__ . '/../partials/footer.php'; ?>
