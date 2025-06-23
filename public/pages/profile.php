<?php
require_once __DIR__ . '/../../src/bootstrap.php';

use ActorAwards\Middleware\AuthenticationMiddleware;
use ActorAwards\Services\UserService;

AuthenticationMiddleware::requireLogin();

$db = \ActorAwards\Services\DatabaseService::getConnection();
$userService = new UserService($db);

$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete_account') {
    $userId = $_SESSION['user_id'];
    $user = $userService->getUserById($userId);

    if ($user && $user['role'] !== 'admin') {
        if ($userService->deleteUser($userId)) {
            // Log user out and redirect to homepage
            session_destroy();
            header('Location: /?message=account_deleted');
            exit;
        } else {
            $message = 'There was an error deleting your account. Please try again.';
            $messageType = 'error';
        }
    } else {
        $message = 'Admins cannot delete their own accounts from this page.';
        $messageType = 'error';
    }
}

$user = $userService->getUserById($_SESSION['user_id']);

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Profile - Actor Awards Visualizer</title>
    <link rel="stylesheet" href="/assets/css/common.css">
    <link rel="stylesheet" href="/assets/css/navbar.css">
    <link rel="stylesheet" href="/assets/css/footer.css">
    <link rel="stylesheet" href="/assets/css/profile.css">
</head>
<body>
    <?php include_once __DIR__ . '/../../src/Views/Components/Navbar.php'; ?>

    <div class="page-header">
        <h1>Hello, <?php echo htmlspecialchars($user['username']); ?></h1>
    </div>

    <div class="container profile-container">
        <?php if (!empty($message)): ?>
            <div class="message <?php echo $messageType; ?>"><?php echo htmlspecialchars($message); ?></div>
        <?php endif; ?>

        <div class="profile-card">
            <h2>Profile Information</h2>
            <div class="info-grid">
                <div class="info-item">
                    <span class="info-label">Username</span>
                    <span class="info-value"><?php echo htmlspecialchars($user['username']); ?></span>
                </div>
                <div class="info-item">
                    <span class="info-label">Email</span>
                    <span class="info-value"><?php echo htmlspecialchars($user['email']); ?></span>
                </div>
                <div class="info-item">
                    <span class="info-label">Member Since</span>
                    <span class="info-value"><?php echo date('F j, Y', strtotime($user['created_at'])); ?></span>
                </div>
                <div class="info-item">
                    <span class="info-label">&nbsp;</span>
                    <form method="POST" onsubmit="return confirm('Are you absolutely sure you want to delete your account? This action is irreversible.');">
                        <input type="hidden" name="action" value="delete_account">
                        <button type="submit" class="btn-delete">Delete My Account</button>
                    </form>
                </div>
            </div>
        </div>

    </div>

    <?php include_once __DIR__ . '/../../src/Views/Components/Footer.php'; ?>
</body>
</html>
