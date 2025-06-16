<?php
require_once __DIR__ . '/../src/includes/logging.php';
session_start();

// Check if already logged in
if (isset($_SESSION['is_admin']) && $_SESSION['is_admin']) {
    logAccess('Admin already logged in, redirecting to dashboard');
    header('Location: /admin.php');
    exit;
}

$error = '';

// Handle login form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';

    // Log the login attempt
    logAccess("Login attempt for username: $username");

    // Hardcoded credentials check
    if ($username === 'admin' && $password === 'admin123') {
        $_SESSION['is_admin'] = true;
        logAccess('Successful login');
        header('Location: /admin.php');
        exit;
    } else {
        $error = 'Invalid username or password';
        logError("Failed login attempt for username: $username");
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login - Actor Awards Visualizer</title>
    <link rel="stylesheet" href="/assets/css/common.css">
    <link rel="stylesheet" href="/assets/css/navbar.css">
    <style>
        .login-container {
            max-width: 400px;
            margin: 100px auto;
            padding: 2rem;
            background: var(--card-background);
            border-radius: var(--border-radius);
            box-shadow: var(--card-shadow);
        }

        .login-header {
            text-align: center;
            margin-bottom: 2rem;
        }

        .login-header h1 {
            font-size: 2rem;
            color: var(--text-color);
            margin-bottom: 0.5rem;
        }

        .login-form {
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }

        .form-group {
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
        }

        .form-group label {
            font-weight: 500;
            color: var(--text-color);
        }

        .form-group input {
            padding: 0.75rem;
            border: 1px solid var(--border-color);
            border-radius: var(--border-radius);
            font-size: 1rem;
        }

        .login-button {
            background: var(--primary-color);
            color: white;
            padding: 0.75rem;
            border: none;
            border-radius: var(--border-radius);
            font-size: 1rem;
            font-weight: 500;
            cursor: pointer;
            transition: var(--transition);
        }

        .login-button:hover {
            background: var(--primary-hover);
        }

        .error-message {
            color: #dc3545;
            text-align: center;
            margin-bottom: 1rem;
        }
    </style>
</head>
<body>
    <?php include __DIR__ . '/../src/includes/navbar.php'; ?>

    <div class="container">
        <div class="login-container">
            <div class="login-header">
                <h1>Admin Login</h1>
                <p>Enter your credentials to access the admin dashboard</p>
            </div>

            <?php if ($error): ?>
                <div class="error-message"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>

            <form method="post" class="login-form">
                <div class="form-group">
                    <label for="username">Username</label>
                    <input type="text" id="username" name="username" required>
                </div>

                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" required>
                </div>

                <button type="submit" class="login-button">Login</button>
            </form>
        </div>
    </div>

    <?php include __DIR__ . '/../src/includes/footer.php'; ?>
</body>
</html>
