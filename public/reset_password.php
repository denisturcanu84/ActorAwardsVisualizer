<?php
require_once __DIR__ . '/../src/bootstrap.php';

use ActorAwards\Services\DatabaseService;
use ActorAwards\Services\UserService;
use ActorAwards\Services\EmailService;
use ActorAwards\Utils\Helpers;

$error = '';
$success = '';
$step = $_GET['step'] ?? 'request';
$token = $_GET['token'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'request_reset') {
        $email = trim($_POST['email'] ?? '');
        $csrf_token = $_POST['csrf_token'] ?? '';
        
        if (!Helpers::verifyCsrfToken($csrf_token)) {
            $error = 'Invalid security token. Please try again.';
        } elseif (empty($email)) {
            $error = 'Please enter your email address.';
        } elseif (!Helpers::isValidEmail($email)) {
            $error = 'Please enter a valid email address.';
        } else {
            try {
                $db = DatabaseService::getConnection();
                $userService = new UserService($db);
                
                $result = $userService->requestPasswordReset($email);
                if ($result) {
                    $success = 'If an account with that email exists, you will receive a password reset link shortly.';
                } else {
                    // Don't reveal if email exists or not for security
                    $success = 'If an account with that email exists, you will receive a password reset link shortly.';
                }
            } catch (Exception $e) {
                error_log('Password reset error: ' . $e->getMessage());
                $success = 'If an account with that email exists, you will receive a password reset link shortly.';
            }
        }
    } elseif ($action === 'reset_password') {
        $new_password = $_POST['password'] ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';
        $csrf_token = $_POST['csrf_token'] ?? '';
        $reset_token = $_POST['token'] ?? '';
        
        if (!Helpers::verifyCsrfToken($csrf_token)) {
            $error = 'Invalid security token. Please try again.';
        } elseif (empty($new_password) || empty($confirm_password)) {
            $error = 'Please fill in all fields.';
        } elseif ($new_password !== $confirm_password) {
            $error = 'Passwords do not match.';
        } elseif (strlen($new_password) < 6) {
            $error = 'Password must be at least 6 characters long.';
        } else {
            try {
                $db = DatabaseService::getConnection();
                $userService = new UserService($db);
                
                if ($userService->resetPassword($reset_token, $new_password)) {
                    $success = 'Password reset successfully! You can now <a href="login.php">log in</a> with your new password.';
                    $step = 'complete';
                } else {
                    $error = 'Invalid or expired reset token.';
                }
            } catch (Exception $e) {
                $error = 'An error occurred. Please try again.';
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
    <title>Reset Password - Actor Awards Visualizer</title>
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
            <h1>Reset Password</h1>
            <?php if ($step === 'request'): ?>
                <p>Enter your email address to reset your password</p>
            <?php elseif ($step === 'reset'): ?>
                <p>Enter your new password</p>
            <?php endif; ?>
        </div>
        
        <?php if ($error): ?>
            <div class="error-message"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <?php if ($success): ?>
            <div class="success-message"><?php echo $success; ?></div>
        <?php endif; ?>
        
        <?php if ($step === 'request' && !$success): ?>
            <form method="post">
                <input type="hidden" name="action" value="request_reset">
                <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                
                <div class="form-group">
                    <label for="email">Email Address</label>
                    <input type="email" id="email" name="email" required>
                </div>
                
                <button type="submit" class="auth-button">Send Reset Link</button>
            </form>
        <?php elseif ($step === 'reset' && !$success): ?>
            <form method="post">
                <input type="hidden" name="action" value="reset_password">
                <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                <input type="hidden" name="token" value="<?php echo Helpers::escape($token); ?>">
                
                <div class="form-group">
                    <label for="password">New Password</label>
                    <input type="password" id="password" name="password" required minlength="6">
                </div>
                
                <div class="form-group">
                    <label for="confirm_password">Confirm New Password</label>
                    <input type="password" id="confirm_password" name="confirm_password" required minlength="6">
                </div>
                
                <button type="submit" class="auth-button">Reset Password</button>
            </form>
        <?php endif; ?>
        
        <div class="back-link">
            <a href="login.php">← Back to Login</a>
        </div>
    </div>

    <?php include __DIR__ . '/../src/includes/footer.php'; ?>
</body>
</html>
