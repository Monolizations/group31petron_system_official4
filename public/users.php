<?php
$page_id = 'users';
require_once __DIR__ . '/../backend/lib.php';
require_once __DIR__ . '/db_connect.php';
require_login();

$me = current_user();
$my_role = role_key($me['role'] ?? 'staff');
$my_station_id = user_station_id();

// Access Control: Only Admin, Super Admin, and Manager can access
if (!in_array($my_role, ['admin', 'superadmin', 'manager'])) {
    header("Location: dashboard.php");
    exit;
}

$msg = '';

// Helper function to generate random password
function generate_random_password($length = 12) {
    $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*';
    $password = '';
    for ($i = 0; $i < $length; $i++) {
        $password .= $chars[rand(0, strlen($chars) - 1)];
    }
    return $password;
}

// --- ACTION HANDLER ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    try {
        // 1. Add User
        if ($action === 'add_user') {
            $name = trim($_POST['name']);
            $username = trim($_POST['username']);
            $role = role_key($_POST['role'] ?? 'staff');
            $phone = trim($_POST['phone']);
            $email = trim($_POST['email']);
            $password = $_POST['password'] ?: 'Petron123!'; // Default if empty
            
            // Validation
            if (empty($name) || empty($username)) throw new Exception("Name and Username are required.");
            
            // Check username
            $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ?");
            $stmt->execute([$username]);
            if ($stmt->fetch()) throw new Exception("Username already exists.");
            
            $hashed = password_hash($password, PASSWORD_DEFAULT);
            $station_target = ($my_role === 'superadmin' && !empty($_POST['station_id'])) ? $_POST['station_id'] : $my_station_id;
            
            $stmt = $pdo->prepare("INSERT INTO users (name, username, role, email, password, station_id, status, created_at) VALUES (?, ?, ?, ?, ?, ?, 'active', NOW())");
            $stmt->execute([$name, $username, $role, $email, $hashed, $station_target]);
            
            log_activity($pdo, $me['id'], 'Add User', "Created user $username ($role)");
            $msg = "✅ User added successfully.";
        }
        
        // 2. Edit User
        elseif ($action === 'edit_user') {
            $id = $_POST['user_id'];
            $name = trim($_POST['name']);
            $role = trim($_POST['role'] ?? 'staff');
            $phone = trim($_POST['phone']);
            $email = trim($_POST['email']);
            $changePassword = isset($_POST['changePassword']) && $_POST['changePassword'] === 'on';
            
            // Normalize role to standard format
            $role = strtolower($role);
            if (!in_array($role, ['staff', 'manager', 'admin', 'superadmin'])) {
                throw new Exception('Invalid role selected');
            }
            
            // Security: Prevent non-superadmins from assigning admin/superadmin roles
            if ($my_role !== 'superadmin' && in_array($role, ['admin', 'superadmin'])) {
                throw new Exception('You cannot assign admin or super admin roles');
            }
            
            // Security check: Ensure user belongs to my station (unless superadmin)
            if ($my_role !== 'superadmin') {
                $chk = $pdo->prepare("SELECT id, station_id FROM users WHERE id = ? AND station_id = ?");
                $chk->execute([$id, $my_station_id]);
                if (!$chk->fetch()) throw new Exception("Unauthorized access to user.");
            }
            
            // Update user details
            $stmt = $pdo->prepare("UPDATE users SET name = ?, role = ?, email = ? WHERE id = ?");
            $stmt->execute([$name, $role, $email, $id]);
            
            // Update password if checkbox is checked
            if ($changePassword) {
                $new_password = trim($_POST['new_password'] ?? '');
                
                // If no password provided, generate one
                if (empty($new_password)) {
                    $new_password = generate_random_password();
                }
                
                $hashed = password_hash($new_password, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("UPDATE users SET password = ?, must_change_password = 1 WHERE id = ?");
                $stmt->execute([$hashed, $id]);
                
                // Set password expiry
                try {
                    $expires = (new DateTime("+90 days"))->format('Y-m-d H:i:s');
                    $pdo->prepare("UPDATE users SET password_expires_at = ? WHERE id = ?")
                        ->execute([$expires, $id]);
                } catch(Exception $e){}
                
                $msg = "✅ User details and password updated successfully. New password: $new_password";
                log_activity($pdo, $me['id'], 'Edit User + Password', "Updated details and password for user #$id");
            } else {
                $msg = "✅ User details updated.";
                log_activity($pdo, $me['id'], 'Edit User', "Updated details for user #$id");
            }
        }
        
        // 3. Reset Password
        elseif ($action === 'reset_password') {
            $id = $_POST['user_id'];
            $new_pass = $_POST['new_password'] ?: 'Petron123!';
            
            if ($my_role !== 'superadmin') {
                $chk = $pdo->prepare("SELECT id FROM users WHERE id = ? AND station_id = ?");
                $chk->execute([$id, $my_station_id]);
                if (!$chk->fetch()) throw new Exception("Unauthorized access to user.");
            }
            
            $hashed = password_hash($new_pass, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
            $stmt->execute([$hashed, $id]);
            
            log_activity($pdo, $me['id'], 'Reset Password', "Reset password for user #$id");
            $msg = "✅ Password reset successfully. Temporary password: $new_pass";
        }
        
        // 4. Deactivate/Activate User
        elseif ($action === 'toggle_status') {
            $id = $_POST['user_id'];
            $new_status = $_POST['new_status']; // 'active' or 'inactive'
            
            if ($my_role !== 'superadmin') {
                $chk = $pdo->prepare("SELECT id FROM users WHERE id = ? AND station_id = ?");
                $chk->execute([$id, $my_station_id]);
                if (!$chk->fetch()) throw new Exception("Unauthorized access to user.");
            }
            
            // Prevent deactivating self
            if ($id == $me['id']) throw new Exception("You cannot deactivate your own account.");
            
            $stmt = $pdo->prepare("UPDATE users SET status = ? WHERE id = ?");
            $stmt->execute([$new_status, $id]);
            
            log_activity($pdo, $me['id'], 'Change Status', "Changed user #$id status to $new_status");
            $msg = "✅ User status updated to $new_status.";
        }
        
    } catch (Exception $e) {
        $msg = "❌ " . $e->getMessage();
    }
}

// --- FETCH USERS ---
$users = [];
if ($my_role === 'superadmin') {
    $stmt = $pdo->query("SELECT u.*, s.name as station_name FROM users u LEFT JOIN stations s ON u.station_id = s.id ORDER BY u.created_at DESC");
    $users = $stmt->fetchAll();
    // Fetch stations for dropdown
    $stations = $pdo->query("SELECT id, name FROM stations")->fetchAll();
} else {
    $stmt = $pdo->prepare("SELECT * FROM users WHERE station_id = ? ORDER BY role, name");
    $stmt->execute([$my_station_id]);
    $users = $stmt->fetchAll();
}

include __DIR__ . '/../partials/header.php';
?>

<div class="page-head">
    <div>
        <h1 class="h1">User Management</h1>
        <div class="sub">Manage Manager and Staff accounts, control access, and maintain security.</div>
    </div>
    <div class="actions">
        <button class="btn dark" onclick="openAddModal()">
            <i class="fas fa-user-plus"></i> Add User
        </button>
    </div>
</div>

<?php if($msg): ?>
<div class="card" style="padding:15px; margin-bottom:20px; background: <?php echo strpos($msg, '❌') !== false ? '#f8d7da' : '#d4edda'; ?>; color: <?php echo strpos($msg, '❌') !== false ? '#721c24' : '#155724'; ?>;">
    <?php echo $msg; ?>
</div>
<?php endif; ?>

<!-- User List Table -->
<div class="card">
    <div class="table-wrap">
        <table class="table">
            <thead>
                <tr>
                    <th>Name / Username</th>
                    <th>Role</th>
                    <th>Contact Info</th>
                    <?php if($my_role === 'superadmin'): ?><th>Station</th><?php endif; ?>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach($users as $u): 
                    $statusClass = $u['status'] === 'active' ? 'success' : 'danger';
                    $roleKey = role_key($u['role'] ?? 'staff');
                    $roleLabel = normalize_role($u['role'] ?? $roleKey);
                    if ($roleLabel === '') { $roleLabel = ucfirst($roleKey); }
                    $roleClass = in_array($roleKey, ['manager','admin','superadmin'], true) ? 'primary' : 'secondary';
                ?>
                <tr>
                    <td>
                        <div style="font-weight:bold;"><?php echo htmlspecialchars($u['name']); ?></div>
                        <div class="muted" style="font-size:0.85em;">@<?php echo htmlspecialchars($u['username']); ?></div>
                    </td>
                    <td><span class="badge bg-<?php echo $roleClass; ?>"><?php echo htmlspecialchars($roleLabel); ?></span></td>
                    <td>
                        <div><i class="fas fa-phone fa-xs"></i> <?php echo htmlspecialchars($u['phone'] ?? 'N/A'); ?></div>
                        <div><i class="fas fa-envelope fa-xs"></i> <?php echo htmlspecialchars($u['email'] ?? 'N/A'); ?></div>
                    </td>
                    <?php if($my_role === 'superadmin'): ?>
                        <td><?php echo htmlspecialchars($u['station_name'] ?? 'Unassigned'); ?></td>
                    <?php endif; ?>
                    <td><span class="badge bg-<?php echo $statusClass; ?>"><?php echo ucfirst($u['status']); ?></span></td>
                    <td>
                        <div style="display:flex; gap:5px;">
                            <button class="btn small ghost" onclick="openEditModal(<?php echo htmlspecialchars(json_encode($u)); ?>)" title="Edit User">
                                <i class="fas fa-edit"></i>
                            </button>
                            <button class="btn small ghost" onclick="console.log('Reset button clicked'); openResetModal(<?php echo $u['id']; ?>, '<?php echo htmlspecialchars($u['username']); ?>')" title="Reset Password">
                                <i class="fas fa-key"></i>
                            </button>
                            <?php if($u['id'] != $me['id']): ?>
                                <?php if($u['status'] === 'active'): ?>
                                    <button class="btn small danger" onclick="toggleStatus(<?php echo $u['id']; ?>, 'inactive')" title="Deactivate">
                                        <i class="fas fa-ban"></i>
                                    </button>
                                <?php else: ?>
                                    <button class="btn small success" onclick="toggleStatus(<?php echo $u['id']; ?>, 'active')" title="Activate">
                                        <i class="fas fa-check"></i>
                                    </button>
                                <?php endif; ?>
                            <?php endif; ?>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php if(empty($users)): ?>
                    <tr><td colspan="6" style="text-align:center; padding:20px;">No users found.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- MODAL: Add User -->
<div class="modal" id="addModal">
    <div class="modal-content">
        <div class="modal-header">
            <h3 class="modal-title">Add New User</h3>
            <button class="modal-close" onclick="closeModal('addModal')">&times;</button>
        </div>
        <form method="post">
            <div class="modal-body">
                <input type="hidden" name="action" value="add_user">
                
                <div class="form-group mb-3">
                    <label class="lbl">Full Name</label>
                    <input type="text" name="name" class="inp full" required>
                </div>
                <div class="form-group mb-3">
                    <label class="lbl">Username</label>
                    <input type="text" name="username" class="inp full" required>
                </div>
                <div class="form-group mb-3">
                    <label class="lbl">Role</label>
                    <select name="role" class="inp full" required>
                        <option value="staff">Staff</option>
                        <option value="manager">Manager</option>
                    </select>
                </div>
                <div class="grid-2 mb-3" style="gap:10px;">
                    <div>
                        <label class="lbl">Phone</label>
                        <input type="text" name="phone" class="inp full">
                    </div>
                    <div>
                        <label class="lbl">Email</label>
                        <input type="email" name="email" class="inp full">
                    </div>
                </div>
                <?php if($my_role === 'superadmin'): ?>
                <div class="form-group mb-3">
                    <label class="lbl">Station</label>
                    <select name="station_id" class="inp full" required>
                        <?php foreach($stations as $s): ?>
                            <option value="<?php echo $s['id']; ?>"><?php echo htmlspecialchars($s['name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <?php endif; ?>
                <div class="form-group mb-3">
                    <label class="lbl">Password</label>
                    <input type="password" name="password" class="inp full" placeholder="Leave empty for 'Petron123!'">
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn ghost" onclick="closeModal('addModal')">Cancel</button>
                <button type="submit" class="btn primary">Create User</button>
            </div>
        </form>
    </div>
</div>

<!-- MODAL: Edit User -->
<div class="modal" id="editModal">
    <div class="modal-content">
        <div class="modal-header">
            <h3 class="modal-title">Edit User</h3>
            <button class="modal-close" onclick="closeModal('editModal')">&times;</button>
        </div>
        <form method="post">
            <div class="modal-body">
                <input type="hidden" name="action" value="edit_user">
                <input type="hidden" name="user_id" id="edit_user_id">
                
                <div class="form-group mb-3">
                    <label class="lbl">Full Name</label>
                    <input type="text" name="name" id="edit_name" class="inp full" required>
                </div>
                <div class="form-group mb-3">
                    <label class="lbl">Role</label>
                    <select name="role" id="edit_role" class="inp full" required>
                        <option value="">-- Select Role --</option>
                        <option value="staff">Staff</option>
                        <option value="manager">Manager</option>
                        <?php if ($my_role === 'superadmin'): ?>
                        <option value="admin">Admin</option>
                        <option value="superadmin">Super Admin</option>
                        <?php endif; ?>
                    </select>
                </div>
                <div class="grid-2 mb-3" style="gap:10px;">
                    <div>
                        <label class="lbl">Phone</label>
                        <input type="text" name="phone" id="edit_phone" class="inp full">
                    </div>
                    <div>
                        <label class="lbl">Email</label>
                        <input type="email" name="email" id="edit_email" class="inp full">
                    </div>
                </div>
                
                <!-- Password Change Section -->
                <div class="form-group mb-3">
                    <label class="lbl">
                        <input type="checkbox" id="changePassword" name="changePassword" onchange="togglePasswordField()">
                        <span style="margin-left: 8px;">Change Password</span>
                    </label>
                </div>
                
                <div id="passwordFieldGroup" class="form-group mb-3" style="display: none;">
                    <label class="lbl">New Password</label>
                    <div style="display: flex; gap: 10px; align-items: center;">
                        <input type="password" name="new_password" id="edit_password" class="inp full" placeholder="Enter new password or leave empty for auto-generate">
                        <button type="button" class="btn small ghost" onclick="generatePassword()" title="Generate random password">
                            <i class="fas fa-dice"></i> Generate
                        </button>
                    </div>
                    <small style="color: #666; margin-top: 5px; display: block;">Leave empty to generate a secure password automatically</small>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn ghost" onclick="closeModal('editModal')">Cancel</button>
                <button type="submit" class="btn primary">Save Changes</button>
            </div>
        </form>
    </div>
</div>

<!-- MODAL: Reset Password -->
<div class="modal" id="resetModal">
    <div class="modal-content" style="max-width: 400px;">
        <div class="modal-header">
            <h3 class="modal-title">Reset Password</h3>
            <button class="modal-close" onclick="closeModal('resetModal')">&times;</button>
        </div>
        <form method="post">
            <div class="modal-body">
                <input type="hidden" name="action" value="reset_password">
                <input type="hidden" name="user_id" id="reset_user_id">
                <p>Reset password for <strong id="reset_username"></strong>?</p>
                <div class="form-group mt-3">
                    <label class="lbl">New Password</label>
                    <input type="text" name="new_password" class="inp full" value="Petron123!" required>
                    <small class="muted">Default: Petron123!</small>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn ghost" onclick="closeModal('resetModal')">Cancel</button>
                <button type="submit" class="btn warning">Reset Password</button>
            </div>
        </form>
    </div>
</div>

<!-- FORM: Toggle Status (Hidden) -->
<form method="post" id="statusForm" style="display:none;">
    <input type="hidden" name="action" value="toggle_status">
    <input type="hidden" name="user_id" id="status_user_id">
    <input type="hidden" name="new_status" id="status_new_val">
</form>

<script>
function openAddModal() {
    document.getElementById('addModal').classList.add('show');
}

function openEditModal(user) {
    document.getElementById('edit_user_id').value = user.id;
    document.getElementById('edit_name').value = user.name;
    
    // Normalize role to lowercase for proper dropdown matching
    const normalizedRole = (user.role || '').toLowerCase().trim();
    document.getElementById('edit_role').value = normalizedRole;
    
    // Debug: log if role not set
    if (!normalizedRole) {
        console.warn('Warning: Role not found for user', user);
    }
    
    document.getElementById('edit_phone').value = user.phone || '';
    document.getElementById('edit_email').value = user.email || '';
    
    // Reset password checkbox and fields
    document.getElementById('changePassword').checked = false;
    document.getElementById('edit_password').value = '';
    document.getElementById('passwordFieldGroup').style.display = 'none';
    
    document.getElementById('editModal').classList.add('show');
}

function openResetModal(id, username) {
    console.log('openResetModal called with:', { id, username });
    document.getElementById('reset_user_id').value = id;
    document.getElementById('reset_username').innerText = username;
    console.log('Showing reset modal');
    document.getElementById('resetModal').classList.add('show');
}

function toggleStatus(id, newStatus) {
    if(confirm('Are you sure you want to ' + (newStatus === 'active' ? 'activate' : 'deactivate') + ' this user?')) {
        document.getElementById('status_user_id').value = id;
        document.getElementById('status_new_val').value = newStatus;
        document.getElementById('statusForm').submit();
    }
}

function closeModal(id) {
    document.getElementById(id).classList.remove('show');
}

function togglePasswordField() {
    const checkbox = document.getElementById('changePassword');
    const passwordGroup = document.getElementById('passwordFieldGroup');
    if (checkbox.checked) {
        passwordGroup.style.display = 'block';
    } else {
        passwordGroup.style.display = 'none';
        document.getElementById('edit_password').value = '';
    }
}

function generatePassword() {
    const chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*';
    let password = '';
    for (let i = 0; i < 12; i++) {
        password += chars.charAt(Math.floor(Math.random() * chars.length));
    }
    document.getElementById('edit_password').value = password;
    alert('Generated password: ' + password);
}
</script>

<style>
    .badge { padding: 4px 8px; border-radius: 4px; font-size: 0.8em; color: white; }
    .bg-primary { background: #007bff; }
    .bg-secondary { background: #6c757d; }
    .bg-success { background: #28a745; }
    .bg-danger { background: #dc3545; }
    .btn.small { padding: 4px 8px; font-size: 0.85em; }
    .grid-2 { display: grid; grid-template-columns: 1fr 1fr; }
    .inp.full { width: 100%; }
    .mb-3 { margin-bottom: 1rem; }
    .mt-3 { margin-top: 1rem; }
</style>

<?php include __DIR__ . '/../partials/footer.php'; ?>
