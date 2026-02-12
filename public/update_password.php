<?php
$page_id = 'update_password';
require_once __DIR__ . '/../backend/lib.php';
require_once __DIR__ . '/db_connect.php';
require_login();

$me = current_user();
$msg = '';
$error = '';

// Handle password change
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $current_password = $_POST['current_password'] ?? '';
        $new_password = $_POST['new_password'] ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';
        
        // Validation
        if (empty($current_password)) {
            throw new Exception('Current password is required');
        }
        if (empty($new_password)) {
            throw new Exception('New password is required');
        }
        if ($new_password !== $confirm_password) {
            throw new Exception('New passwords do not match');
        }
        if (strlen($new_password) < 6) {
            throw new Exception('New password must be at least 6 characters long');
        }
        if ($current_password === $new_password) {
            throw new Exception('New password must be different from current password');
        }
        
        // Get current user's password from database
        $stmt = $pdo->prepare("SELECT password FROM users WHERE id = ?");
        $stmt->execute([$me['id']]);
        $result = $stmt->fetch();
        
        if (!$result) {
            throw new Exception('User not found');
        }
        
        // Verify current password
        if (!password_verify($current_password, $result['password'])) {
            throw new Exception('Current password is incorrect');
        }
        
        // Hash new password
        $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
        
        // Update password in database
        $updateStmt = $pdo->prepare("UPDATE users SET password = ?, must_change_password = 0, updated_at = NOW() WHERE id = ?");
        $updateStmt->execute([$hashed_password, $me['id']]);
        
        // Set password expiry to 90 days from now
        try {
            $expires = (new DateTime("+90 days"))->format('Y-m-d H:i:s');
            $pdo->prepare("UPDATE users SET password_expires_at = ? WHERE id = ?")
                ->execute([$expires, $me['id']]);
        } catch(Exception $e){}
        
        log_activity($pdo, $me['id'], 'Change Password', 'User changed their own password');
        
        $msg = "✅ Password changed successfully!";
        
    } catch (Exception $e) {
        $error = "❌ " . $e->getMessage();
    }
}

include __DIR__ . '/../partials/header.php';
?>

<style>
.password-container {
    max-width: 500px;
    margin: 40px auto;
}

.card {
    background: white;
    border-radius: 8px;
    padding: 30px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
}

.card-title {
    font-size: 24px;
    font-weight: 700;
    margin-bottom: 10px;
    color: #333;
}

.card-subtitle {
    color: #666;
    font-size: 14px;
    margin-bottom: 30px;
}

.form-group {
    margin-bottom: 20px;
}

.form-label {
    display: block;
    margin-bottom: 8px;
    font-weight: 500;
    color: #333;
    font-size: 14px;
}

.form-input {
    width: 100%;
    padding: 12px;
    border: 1px solid #ddd;
    border-radius: 6px;
    font-size: 14px;
    transition: border-color 0.3s;
    box-sizing: border-box;
}

.form-input:focus {
    outline: none;
    border-color: #007bff;
    box-shadow: 0 0 0 3px rgba(0, 123, 255, 0.1);
}

.password-strength {
    margin-top: 8px;
    font-size: 12px;
    color: #666;
}

.form-group input[type="password"],
.form-group input[type="text"] {
    width: 100%;
}

.button-group {
    display: flex;
    gap: 10px;
    margin-top: 30px;
}

.btn {
    flex: 1;
    padding: 12px 20px;
    border: none;
    border-radius: 6px;
    font-size: 14px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s;
    text-decoration: none;
    display: inline-block;
    text-align: center;
}

.btn-primary {
    background: #007bff;
    color: white;
}

.btn-primary:hover {
    background: #0056b3;
}

.btn-secondary {
    background: #6c757d;
    color: white;
}

.btn-secondary:hover {
    background: #545b62;
}

.alert {
    padding: 15px;
    border-radius: 6px;
    margin-bottom: 20px;
    border-left: 4px solid;
}

.alert-success {
    background-color: #d4edda;
    color: #155724;
    border-left-color: #28a745;
}

.alert-error {
    background-color: #f8d7da;
    color: #721c24;
    border-left-color: #dc3545;
}

.password-requirements {
    background: #f8f9fa;
    border: 1px solid #dee2e6;
    border-radius: 6px;
    padding: 15px;
    margin-top: 20px;
    font-size: 13px;
    color: #666;
}

.password-requirements ul {
    margin: 10px 0 0 20px;
    padding: 0;
}

.password-requirements li {
    margin-bottom: 5px;
}

.password-requirements .valid {
    color: #28a745;
}

.password-requirements .invalid {
    color: #dc3545;
}
</style>

<div class="password-container">
    <div class="card">
        <h1 class="card-title">
            <i class="fas fa-lock" style="margin-right: 10px;"></i>Change Password
        </h1>
        <p class="card-subtitle">Update your password to keep your account secure</p>
        
        <?php if ($msg): ?>
        <div class="alert alert-success">
            <?php echo $msg; ?>
        </div>
        <?php endif; ?>
        
        <?php if ($error): ?>
        <div class="alert alert-error">
            <?php echo $error; ?>
        </div>
        <?php endif; ?>
        
        <form method="POST">
            <div class="form-group">
                <label class="form-label">Current Password</label>
                <input 
                    type="password" 
                    name="current_password" 
                    class="form-input" 
                    placeholder="Enter your current password"
                    required
                >
            </div>
            
            <div class="form-group">
                <label class="form-label">New Password</label>
                <input 
                    type="password" 
                    name="new_password" 
                    class="form-input" 
                    id="new_password"
                    placeholder="Enter your new password"
                    required
                >
                <div class="password-strength">
                    <i class="fas fa-info-circle"></i> Minimum 6 characters
                </div>
            </div>
            
            <div class="form-group">
                <label class="form-label">Confirm Password</label>
                <input 
                    type="password" 
                    name="confirm_password" 
                    class="form-input" 
                    id="confirm_password"
                    placeholder="Confirm your new password"
                    required
                >
            </div>
            
            <div class="password-requirements">
                <strong>Password Requirements:</strong>
                <ul>
                    <li id="req-length">✓ At least 6 characters long</li>
                    <li id="req-different">✓ Different from current password</li>
                    <li id="req-match">✓ Passwords must match</li>
                </ul>
            </div>
            
            <div class="button-group">
                <a href="dashboard.php" class="btn btn-secondary">Cancel</a>
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-check"></i> Change Password
                </button>
            </div>
        </form>
    </div>
</div>

<script>
document.getElementById('new_password').addEventListener('keyup', validatePassword);
document.getElementById('confirm_password').addEventListener('keyup', validatePassword);

function validatePassword() {
    const newPass = document.getElementById('new_password').value;
    const confirmPass = document.getElementById('confirm_password').value;
    
    // Check length
    const lengthReq = document.getElementById('req-length');
    if (newPass.length >= 6) {
        lengthReq.classList.add('valid');
        lengthReq.classList.remove('invalid');
    } else {
        lengthReq.classList.add('invalid');
        lengthReq.classList.remove('valid');
    }
    
    // Check match
    const matchReq = document.getElementById('req-match');
    if (newPass && confirmPass && newPass === confirmPass) {
        matchReq.classList.add('valid');
        matchReq.classList.remove('invalid');
    } else if (newPass && confirmPass) {
        matchReq.classList.add('invalid');
        matchReq.classList.remove('valid');
    }
}
</script>

<?php include __DIR__ . '/../partials/footer.php'; ?>
