<?php
/**
 * Password Reset Handler
 *
 * This script handles the password reset process in 2 steps:
 * 1. Request reset - User submits email to receive reset link
 * 2. Reset password - User submits new password with valid token
 *
 * Security measures:
 * - CSRF protection on all forms
 * - rate limiting (handled in UserService)
 * - secure token generation
 * - made sure to not reveal if email exists in the system
 */
require_once __DIR__ . '/../src/bootstrap.php';

use ActorAwards\Services\DatabaseService;
use ActorAwards\Services\UserService;
use ActorAwards\Services\EmailService;
use ActorAwards\Utils\Helpers;

$error = ''; // stores error messages for user feedback
$success = ''; // stores success messages
$step = $_GET['step'] ?? 'request'; // tracks current step in reset flow
$token = $_GET['token'] ?? ''; // reset token from email link

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'request_reset') {
        // Step 1: request password reset link
        $email = trim($_POST['email'] ?? '');
        $csrf_token = $_POST['csrf_token'] ?? '';
        
        // validate inputs
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
                
                // request reset - same response regardless of email existence
                $result = $userService->requestPasswordReset($email);
                if ($result) {
                    $success = 'If an account with that email exists, you will receive a password reset link shortly.';
                } else {
                    $success = 'If an account with that email exists, you will receive a password reset link shortly.';
                }
            } catch (Exception $e) {
                error_log('Password reset error: ' . $e->getMessage());
                $success = 'If an account with that email exists, you will receive a password reset link shortly.';
            }
        }
    } elseif ($action === 'reset_password') {
        // Step 2: reset password with valid token
        $new_password = $_POST['password'] ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';
        $csrf_token = $_POST['csrf_token'] ?? '';
        $reset_token = $_POST['token'] ?? '';
        
        // validate inputs
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
                
                // attempt password reset with token
                if ($userService->resetPassword($reset_token, $new_password)) {
                    $success = 'Password reset successfully! You can now <a href="login.php">log in</a> with your new password.';
                    $step = 'complete'; // mark reset as complete
                } else {
                    $error = 'Invalid or expired reset token.';
                }
            } catch (Exception $e) {
                $error = 'An error occurred. Please try again.';
            }
        }
    }
}

// generate new CSRF token for form protection
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
    <link rel="stylesheet" href="/assets/css/resetpass.css">
</head>
<body>
    <?php include __DIR__ . '/../src/Views/Components/Navbar.php'; ?>

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

    <?php include __DIR__ . '/../src/Views/Components/Footer.php'; ?>
</body>
</html>
