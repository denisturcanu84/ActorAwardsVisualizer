<?php
require_once __DIR__ . '/../src/bootstrap.php';

use ActorAwards\Services\DatabaseService;
use ActorAwards\Services\UserService;
use ActorAwards\Utils\Helpers;
use ActorAwards\Middleware\AuthenticationMiddleware;

// Redirect if already logged in
AuthenticationMiddleware::redirectIfLoggedIn();

$error = '';
$success = '';
$current_tab = $_GET['tab'] ?? 'login'; // Default to login tab

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'login') {
        $username = trim($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';
        $csrf_token = $_POST['csrf_token'] ?? '';
        
        if (!Helpers::verifyCsrfToken($csrf_token)) {
            $error = 'Invalid security token. Please try again.';
        } elseif (empty($username) || empty($password)) {
            $error = 'Please fill in all fields.';
        } else {
            try {
                $db = DatabaseService::getConnection();
                $userService = new UserService($db);
                $user = $userService->authenticateUser($username, $password);
                
                if ($user) {
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['username'] = $user['username'];
                    $_SESSION['is_admin'] = (bool)$user['is_admin'];
                    
                    header('Location: /');
                    exit;
                } else {
                    $error = 'Invalid username or password.';
                }
            } catch (Exception $e) {
                $error = 'Login failed. Please try again.';
            }
        }
    } elseif ($action === 'register') {
        $username = trim($_POST['username'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';
        $csrf_token = $_POST['csrf_token'] ?? '';
        
        if (!Helpers::verifyCsrfToken($csrf_token)) {
            $error = 'Invalid security token. Please try again.';
        } elseif (empty($username) || empty($email) || empty($password) || empty($confirm_password)) {
            $error = 'Please fill in all fields.';
        } elseif (!Helpers::isValidEmail($email)) {
            $error = 'Please enter a valid email address.';
        } elseif ($password !== $confirm_password) {
            $error = 'Passwords do not match.';
        } elseif (strlen($password) < 6) {
            $error = 'Password must be at least 6 characters long.';
        } else {
            try {
                $db = DatabaseService::getConnection();
                $userService = new UserService($db);
                
                if ($userService->userExists($username, $email)) {
                    $error = 'Username or email already exists.';
                } else {
                    if ($userService->createUser($username, $email, $password)) {
                        $success = 'Account created successfully! You can now log in.';
                    } else {
                        $error = 'Registration failed. Please try again.';
                    }
                }
            } catch (Exception $e) {
                $error = 'Registration failed. Please try again.';
            }
        }
    }
}

$csrf_token = Helpers::generateCsrfToken();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Actor Awards Visualizer</title>
    <link rel="stylesheet" href="/assets/css/common.css">
    <link rel="stylesheet" href="/assets/css/navbar.css">
    <link rel="stylesheet" href="/assets/css/footer.css">
    <style>
        .auth-container {
            max-width: 400px;
            margin: 100px auto 50px;
            padding: 30px;
            background: var(--card-bg);
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }
        
        .auth-header {
            text-align: center;
            margin-bottom: 30px;
        }
        
        .auth-header h1 {
            color: var(--primary-color);
            margin-bottom: 10px;
        }
        
        .auth-tabs {
            display: flex;
            margin-bottom: 30px;
            border-bottom: 2px solid var(--border-color);
        }
        
        .auth-tab {
            flex: 1;
            padding: 12px;
            text-align: center;
            background: none;
            border: none;
            cursor: pointer;
            font-size: 16px;
            color: var(--text-secondary);
            transition: all 0.3s ease;
        }
        
        .auth-tab.active {
            color: var(--primary-color);
            border-bottom: 2px solid var(--primary-color);
        }
        
        .auth-form {
            display: none;
        }
        
        .auth-form.active {
            display: block;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 5px;
            color: var(--text-primary);
            font-weight: 500;
        }
        
        .form-group input {
            width: 100%;
            padding: 12px;
            border: 1px solid var(--border-color);
            border-radius: 5px;
            font-size: 16px;
            background: var(--bg-color);
            color: var(--text-primary);
        }
        
        .form-group input:focus {
            outline: none;
            border-color: var(--primary-color);
        }
        
        .auth-button {
            width: 100%;
            padding: 12px;
            background: var(--primary-color);
            color: white;
            border: none;
            border-radius: 5px;
            font-size: 16px;
            cursor: pointer;
            transition: background-color 0.3s ease;
        }
        
        .auth-button:hover {
            background: var(--secondary-color);
        }
        
        .error-message {
            background: #fee;
            color: #c33;
            padding: 12px;
            border-radius: 5px;
            margin-bottom: 20px;
            text-align: center;
        }
        
        .success-message {
            background: #efe;
            color: #3a3;
            padding: 12px;
            border-radius: 5px;
            margin-bottom: 20px;
            text-align: center;
        }
        
        .back-link {
            text-align: center;
            margin-top: 20px;
        }
        
        .back-link a {
            color: var(--primary-color);
            text-decoration: none;
        }
        
        .back-link a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <?php include __DIR__ . '/../src/includes/navbar.php'; ?>

    
    <div class="auth-container">
        <div class="auth-header">
            <h1>Actor Awards Visualizer</h1>
            <p>Please log in or create an account to continue</p>
        </div>
        
        <?php if ($error): ?>
            <div class="error-message"><?php echo Helpers::escape($error); ?></div>
        <?php endif; ?>
        
        <?php if ($success): ?>
            <div class="success-message"><?php echo Helpers::escape($success); ?></div>
        <?php endif; ?>
        
        <div class="auth-tabs">
            <button class="auth-tab <?php echo $current_tab === 'login' ? 'active' : ''; ?>" onclick="showTab('login')">Login</button>
            <button class="auth-tab <?php echo $current_tab === 'register' ? 'active' : ''; ?>" onclick="showTab('register')">Register</button>
        </div>
        
        <!-- Login Form -->
        <form class="auth-form <?php echo $current_tab === 'login' ? 'active' : ''; ?>" id="login-form" method="post">
            <input type="hidden" name="action" value="login">
            <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
            
            <div class="form-group">
                <label for="login-username">Username or Email</label>
                <input type="text" id="login-username" name="username" required>
            </div>
            
            <div class="form-group">
                <label for="login-password">Password</label>
                <input type="password" id="login-password" name="password" required>
            </div>
            
            <button type="submit" class="auth-button">Login</button>
            
            <div style="text-align: center; margin-top: 15px;">
                <a href="/reset-password" style="color: var(--primary-color); text-decoration: none; font-size: 14px;">
                    Forgot your password?
                </a>
            </div>
        </form>
        
        <!-- Register Form -->
        <form class="auth-form <?php echo $current_tab === 'register' ? 'active' : ''; ?>" id="register-form" method="post">
            <input type="hidden" name="action" value="register">
            <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
            
            <div class="form-group">
                <label for="register-username">Username</label>
                <input type="text" id="register-username" name="username" required>
            </div>
            
            <div class="form-group">
                <label for="register-email">Email</label>
                <input type="email" id="register-email" name="email" required>
            </div>
            
            <div class="form-group">
                <label for="register-password">Password</label>
                <input type="password" id="register-password" name="password" required minlength="6">
            </div>
            
            <div class="form-group">
                <label for="register-confirm-password">Confirm Password</label>
                <input type="password" id="register-confirm-password" name="confirm_password" required minlength="6">
            </div>
            
            <button type="submit" class="auth-button">Create Account</button>
        </form>
        
        <div class="back-link">
            <a href="/">← Back to Home</a>
        </div>
    </div>

    <?php include __DIR__ . '/../src/includes/footer.php'; ?>

    <script>
        function showTab(tabName) {
            // Hide all forms
            const forms = document.querySelectorAll('.auth-form');
            forms.forEach(form => form.classList.remove('active'));
            
            // Remove active class from all tabs
            const tabs = document.querySelectorAll('.auth-tab');
            tabs.forEach(tab => tab.classList.remove('active'));
            
            // Show selected form
            const selectedForm = document.getElementById(tabName + '-form');
            if (selectedForm) {
                selectedForm.classList.add('active');
            }
            
            // Add active class to selected tab
            const selectedTab = document.querySelector(`[onclick="showTab('${tabName}')"]`);
            if (selectedTab) {
                selectedTab.classList.add('active');
            }
        }
    </script>
</body>
</html>
