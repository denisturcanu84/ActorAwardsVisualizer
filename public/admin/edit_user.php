<?php
define('ADMIN_DASHBOARD_URL', '/admin.php');

require_once __DIR__ . '/../../src/bootstrap.php';

use ActorAwards\Middleware\AuthenticationMiddleware;
use ActorAwards\Services\DatabaseService;
use ActorAwards\Services\UserService;
use ActorAwards\Services\LoggingService;

AuthenticationMiddleware::requireAdmin();

$db = DatabaseService::getConnection();
$userService = new UserService($db);
$loggingService = new LoggingService();

$message = '';
$messageType = '';
$user = null;

// gets the user ID from the clean URL
// exemplu -> /admin/user/edit/123 -> $_GET['id'] = 123
$userId = (int)($_GET['id'] ?? 0);

if ($userId <= 0) {
    header("Location: " . ADMIN_DASHBOARD_URL);
    exit;
}

// handles form submission -> updating the user
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // CSRF check
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        die('Invalid CSRF token');
    }

    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $role = $_POST['role'] ?? 'user';

    if (!empty($username) && !empty($email)) {
        // checks if username or email is already taken by another user
        $stmt = $db->prepare("SELECT COUNT(*) FROM users WHERE (username = ? OR email = ?) AND id != ?");
        $stmt->execute([$username, $email, $userId]);

        if ($stmt->fetchColumn() > 0) {
            $message = 'Username or email already exists for another user.';
            $messageType = 'error';
            // keeps submitted values to repopulate the form
            $user = ['id' => $userId, 'username' => $username, 'email' => $email, 'role' => $role];
        } else {
            // updates the user in the database
            $stmt = $db->prepare("UPDATE users SET username = ?, email = ?, role = ? WHERE id = ?");
            if ($stmt->execute([$username, $email, $role, $userId])) {
                $loggingService->logAccess("Admin updated user ID: $userId");
                // redirects back to the admin page with a success message
                $_SESSION['admin_message'] = "User updated successfully.";
                $_SESSION['admin_message_type'] = 'success';
                header("Location: " . ADMIN_DASHBOARD_URL);
                exit;
            } else {
                $message = 'Failed to update user.';
                $messageType = 'error';
            }
        }
    } else {
        $message = 'Username and email are required.';
        $messageType = 'error';
    }
}

if (!$user) {
    $user = $userService->getUserById($userId);
    if (!$user) {
        // user not found, redirect back to the admin dashboard with an error message
        $_SESSION['admin_message'] = 'User not found.';
        $_SESSION['admin_message_type'] = 'error';
        header("Location: " . ADMIN_DASHBOARD_URL);
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit User - Admin</title>
    <link rel="stylesheet" href="/assets/css/common.css">
    <link rel="stylesheet" href="/assets/css/admin.css">
    <link rel="stylesheet" href="/assets/css/navbar.css">
    <link rel="stylesheet" href="/assets/css/footer.css">
</head>
<body>
    <?php require_once __DIR__ . "/../../src/Views/Components/Navbar.php"; ?>

    <div class="admin-header">
        <div class="admin-header-content">
            <h1>Edit User: <?php echo htmlspecialchars($user['username']); ?></h1>
            <?php if (!empty($message)): ?>
                <div class="admin-message <?php echo $messageType; ?>"><?php echo htmlspecialchars($message); ?></div>
            <?php endif; ?>
        </div>
    </div>

    <div class="admin-container">
        <section class="admin-section">
            <h2>User Details</h2>
            <div class="admin-section-content">
                <div class="user-form-container">
                    <form method="post" action="/user/edit/<?php echo $user['id']; ?>" class="user-form">
                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                        <div class="form-row">
                            <label for="username">Username</label>
                            <input type="text" id="username" name="username" value="<?php echo htmlspecialchars($user['username']); ?>" required>
                        </div>
                        <div class="form-row">
                            <label for="email">Email</label>
                            <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" required>
                        </div>
                        <div class="form-row">
                            <label for="role">Role</label>
                            <select id="role" name="role" required>
                                <option value="user" <?php echo $user['role'] === 'user' ? 'selected' : ''; ?>>User</option>
                                <option value="admin" <?php echo $user['role'] === 'admin' ? 'selected' : ''; ?>>Admin</option>
                            </select>
                        </div>
                        <div class="form-buttons">
                            <button type="submit" class="admin-button">Save Changes</button>
                            <a href="<?php echo ADMIN_DASHBOARD_URL; ?>" class="admin-button cancel-btn">Cancel</a>
                        </div>
                    </form>
                </div>
            </div>
        </section>
    </div>

    <?php require_once __DIR__ . '/../../src/Views/Components/Footer.php'; ?>
</body>
</html>
