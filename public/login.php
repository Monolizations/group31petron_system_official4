<?php
ob_start(); // Fix: Prevent "Headers already sent" errors
session_start();

// Include database connection (assuming it is in the root directory)
require_once __DIR__ . '/../public/db_connect.php';

$error = '';

// Check for login error from auth/login.php
if (isset($_SESSION['login_error'])) {
    $error = $_SESSION['login_error'];
    unset($_SESSION['login_error']);
}

// Prevent caching
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

// 1. Check if already logged in
if (isset($_SESSION['user'])) {
    $role = function_exists('role_key') ? role_key($_SESSION['user']['role'] ?? '') : strtolower(trim($_SESSION['user']['role'] ?? 'staff'));
    if ($role === 'superadmin') {
        $redirect_url = 'dashboard.php';
    } elseif ($role === 'admin') {
        $redirect_url = 'home.php';
    } elseif ($role === 'manager') {
        $redirect_url = 'manager_home.php';
    } else {
        $redirect_url = 'staff_home.php';
    }
    header("Location: $redirect_url");
    exit;
}

// Handle logout if requested
if (isset($_GET['logout'])) {
    session_destroy();
    header("Location: login.php");
    exit;
}

// 2. Handle Login Logic
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($username) || empty($password)) {
        $error = "Please enter both username and password.";
    } else {
        try {
            // Prepare statement to prevent SQL injection
            $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ? LIMIT 1");
            $stmt->execute([$username]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

                // Verify password using secure hash verification
                $valid_login = false;
                if ($user) {
                    // Check account status
                    if (($user['status'] ?? 'active') !== 'active') {
                        $error = "Your account is inactive. Please contact the administrator.";
                    }
                    // Verify password hash
                    elseif (password_verify($password, $user['password'])) {
                        $valid_login = true;
                    }
                }

            if ($valid_login) {

                // Update last login
                try {
                    $pdo->prepare("UPDATE users SET status = 'active' WHERE id = ?")->execute([$user['id']]);
                } catch (Exception $e) { /* ignore */ }

                // Normal login success session
                unset($user['password']);
                $_SESSION['user'] = $user;
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['role'] = $user['role'];

                try {
                    $logStmt = $pdo->prepare("INSERT INTO activity_logs (user_id, action, details, ip_address) VALUES (?, 'Login', 'User logged in', ?)");
                    $logStmt->execute([$user['id'], $_SERVER['REMOTE_ADDR']]);
                } catch (Exception $e) { /* Fail silently if logs table missing */ }

                // RBAC Redirect Logic
                $role = strtolower(trim($user['role'] ?? 'staff'));
                if ($role === 'superadmin') {
                    header("Location: dashboard.php");
                } elseif ($role === 'admin') {
                    header("Location: dashboard.php");
                } elseif ($role === 'manager') {
                    header("Location: manager_home.php");
                } else {
                    header("Location: staff_home.php");
                }
                exit;
            } else {
                // Audit Logging: Failed Attempt
                try {
                    $logStmt = $pdo->prepare("INSERT INTO activity_logs (user_id, action, details, ip_address) VALUES (?, 'Login Failed', ?, ?)");
                    $logStmt->execute([($user['id'] ?? 0), "Failed login attempt for username: $username", $_SERVER['REMOTE_ADDR']]);
                } catch (Exception $e) { /* Fail silently */ }

                if (empty($error)) {
                    $error = "Invalid username or password.";
                }
            }
        } catch (PDOException $e) {
            // Log error internally, show generic message to user
            error_log($e->getMessage());
            $error = "System error. Please try again later.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login | Petron Management System</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --petron-blue: #002F6C;
            --petron-red: #E30613;
            --petron-gray: #CCCCCC;
            --bg-color: #f4f6f9;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: var(--petron-blue);
            background-image: linear-gradient(rgba(0, 0, 0, 0.5), rgba(0, 0, 0, 0.5)), url('assets/img/background.jpg');
            background-size: cover;
            background-position: center;
            background-repeat: no-repeat;
            background-attachment: fixed;
            margin: 0;
            height: 100vh;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
        }

        .login-card {
            background: #ffffff;
            width: 100%;
            max-width: 420px;
            padding: 40px;
            border-radius: 8px;
            box-shadow: 0 10px 25px rgba(0,0,0,0.1);
            text-align: center;
        }

        /* Branding */
        .brand-logo {
            max-width: 120px;
            height: auto;
            margin-bottom: 15px;
        }

        .brand-title {
            color: var(--petron-blue);
            font-size: 26px;
            font-weight: 700;
            margin-bottom: 5px;
        }

        .brand-subtitle {
            color: #666;
            font-size: 14px;
            margin-bottom: 30px;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        /* Form Elements */
        .form-group {
            margin-bottom: 20px;
            text-align: left;
        }

        .input-group {
            position: relative;
            display: flex;
            align-items: center;
        }

        .input-icon {
            position: absolute;
            left: 15px;
            color: #999;
            font-size: 18px;
            z-index: 10;
        }

        .form-control {
            width: 100%;
            padding: 12px 15px 12px 45px; /* Space for icon */
            font-size: 16px;
            border: 1px solid #ddd;
            border-radius: 5px;
            transition: border-color 0.2s, box-shadow 0.2s;
            box-sizing: border-box;
        }

        .form-control:focus {
            border-color: var(--petron-blue);
            box-shadow: 0 0 0 3px rgba(0, 47, 108, 0.1);
            outline: none;
        }

        .toggle-password {
            position: absolute;
            right: 15px;
            background: none;
            border: none;
            cursor: pointer;
            color: #999;
            font-size: 18px;
            padding: 0;
        }

        .toggle-password:hover {
            color: var(--petron-blue);
        }

        /* Checkbox */
        .checkbox-group {
            display: flex;
            align-items: center;
            font-size: 14px;
            color: #555;
            margin-bottom: 20px;
        }

        .checkbox-group input {
            margin-right: 10px;
            width: 16px;
            height: 16px;
        }

        /* Button */
        .btn-login {
            width: 100%;
            padding: 14px;
            background-color: var(--petron-blue);
            color: #fff;
            border: none;
            border-radius: 5px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: background-color 0.2s;
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 10px;
        }

        .btn-login:hover {
            background-color: #001f4d;
        }

        .btn-login:disabled {
            background-color: #99aab5;
            cursor: not-allowed;
        }

        /* Utilities */
        .error-banner {
            background-color: #fde8e8;
            color: var(--petron-red);
            padding: 12px;
            border-radius: 5px;
            margin-bottom: 20px;
            font-size: 14px;
            border: 1px solid #fbd5d5;
            text-align: left;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .links {
            margin-top: 25px;
            display: flex;
            flex-direction: column;
            gap: 10px;
            font-size: 14px;
        }

        .links a {
            color: var(--petron-blue);
            text-decoration: none;
            font-weight: 500;
        }

        .links a:hover {
            text-decoration: underline;
        }

        .footer {
            margin-top: 40px;
            color: rgba(255, 255, 255, 0.8);
            font-size: 12px;
        }

        /* Spinner Animation */
        .spinner {
            width: 18px;
            height: 18px;
            border: 2px solid rgba(255,255,255,0.3);
            border-radius: 50%;
            border-top-color: #fff;
            animation: spin 0.8s linear infinite;
            display: none;
        }

        @keyframes spin {
            to { transform: rotate(360deg); }
        }
    </style>
</head>
<body>

    <div class="login-card">
        <!-- Branding -->
        <img src="../assets/img/Petron Logo.png" alt="Petron logo" class="brand-logo">

        <!-- Error Message -->
        <?php if ($error): ?>
            <div class="error-banner" role="alert">
                <span><i class="fas fa-exclamation-triangle"></i></span>
                <span><?php echo htmlspecialchars($error); ?></span>
            </div>
        <?php endif; ?>

        <!-- Login Form -->
        <form method="POST" action="" id="loginForm">
            <div class="form-group">
                <div class="input-group">
                    <span class="input-icon"><i class="fas fa-user"></i></span>
                    <input type="text" name="username" class="form-control" placeholder="Username" required autofocus aria-label="Username">
                </div>
            </div>

            <div class="form-group">
                <div class="input-group">
                    <span class="input-icon"><i class="fas fa-lock"></i></span>
                    <input type="password" name="password" id="password" class="form-control" placeholder="Password" required aria-label="Password">
                    <button type="button" class="toggle-password" id="toggleBtn" aria-label="Show password"><i class="fas fa-eye"></i></button>
                </div>
            </div>

            <div class="checkbox-group">
                <input type="checkbox" name="terms" id="terms" aria-label="Agree to Terms of Use">
                <label for="terms">I agree to the <a href="#" style="color: var(--petron-blue); text-decoration: none; font-weight: 500;">Terms of Use</a></label>
            </div>

            <button type="submit" class="btn-login" id="submitBtn">
                <div class="spinner" id="spinner"></div>
                <span id="btnText">Login</span>
            </button>
        </form>

        <!-- Secondary Links -->
        <div class="links">
            <a href="forgot_password.php">Forgot Password?</a>
        </div>
    </div>

    <div class="footer">
        &copy; 2026 Petron Station & Service Center Management System. All Rights Reserved.
    </div>

    <script>
        // Toggle Password Visibility
        const toggleBtn = document.getElementById('toggleBtn');
        const passwordInput = document.getElementById('password');

        toggleBtn.addEventListener('click', () => {
            const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
            passwordInput.setAttribute('type', type);
            toggleBtn.innerHTML = type === 'password' ? '<i class="fas fa-eye"></i>' : '<i class="fas fa-eye-slash"></i>';
        });

        // Loading State
        const form = document.getElementById('loginForm');
        const submitBtn = document.getElementById('submitBtn');
        const spinner = document.getElementById('spinner');
        const btnText = document.getElementById('btnText');

        form.addEventListener('submit', () => {
            // Disable button and show spinner
            submitBtn.disabled = true;
            spinner.style.display = 'block';
            btnText.textContent = 'Authenticating...';
        });
    </script>

</body>
</html>
