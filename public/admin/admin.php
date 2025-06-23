<?php
require_once __DIR__ . '/../../src/bootstrap.php';

use ActorAwards\Middleware\AuthenticationMiddleware;
use ActorAwards\Services\DatabaseService;
use ActorAwards\Services\UserService;
use ActorAwards\Services\StatsService;
use ActorAwards\Services\LoggingService;
use ActorAwards\Services\TmdbService;
use ActorAwards\Repositories\ActorRepository;
use ActorAwards\Repositories\ProductionRepository;

// Require admin access
AuthenticationMiddleware::requireAdmin();

// Initialize services
$db = DatabaseService::getConnection();
$userService = new UserService($db);
$tmdbService = new TmdbService(TMDB_API_KEY);
$actorRepository = new ActorRepository($db);
$productionRepository = new ProductionRepository($db, $tmdbService);
$statsService = new StatsService($db, $tmdbService, $actorRepository, $productionRepository);
$loggingService = new LoggingService();

// Log admin access
$loggingService->logAccess('Admin dashboard accessed');

$message = '';
$messageType = '';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    switch ($action) {
        case 'download_db':
            $loggingService->logAccess('Database download requested');
            require_once __DIR__ . '/../../src/Admin/export_db.php';
            exit;
            
        case 'backup_media':
            $loggingService->logAccess('Media backup requested');
            require_once __DIR__ . '/../../src/Admin/backup_media.php';
            exit;
            
        case 'create_user':
            $username = trim($_POST['username'] ?? '');
            $email = trim($_POST['email'] ?? '');
            $password = $_POST['password'] ?? '';
            $role = $_POST['role'] ?? 'user';
            
            if (empty($username) || empty($email) || empty($password)) {
                $message = 'All fields are required for user creation.';
                $messageType = 'error';
            } elseif ($userService->userExists($username, $email)) {
                $message = 'Username or email already exists.';
                $messageType = 'error';
            } else {
                // Create user directly with role
                $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $db->prepare("
                    INSERT INTO users (username, email, password_hash, role, created_at) 
                    VALUES (?, ?, ?, ?, datetime('now'))
                ");
                
                if ($stmt->execute([$username, $email, $hashedPassword, $role])) {
                    $message = "User '$username' created successfully.";
                    $messageType = 'success';
                    $loggingService->logAccess("Admin created user: $username");
                } else {
                    $message = 'Failed to create user.';
                    $messageType = 'error';
                }
            }
            break;
            
        case 'delete_user':
            $userId = (int)($_POST['user_id'] ?? 0);
            if ($userId > 0) {
                $user = $userService->getUserById($userId);
                if ($user && $user['role'] !== 'admin') {
                    if ($userService->deleteUser($userId)) {
                        $message = "User deleted successfully.";
                        $messageType = 'success';
                        $loggingService->logAccess("Admin deleted user: {$user['username']}");
                    } else {
                        $message = 'Failed to delete user.';
                        $messageType = 'error';
                    }
                } elseif ($user['role'] === 'admin') {
                    $message = 'Admins cannot be deleted.';
                    $messageType = 'error';
                }
            }
            break;
    }
}

// Fetch data for display
$users = $userService->getUsers();
$totalUsers = count($users);
$adminCount = count(array_filter($users, fn($u) => $u['role'] === 'admin'));

// Get system stats
$disk_free = disk_free_space('/');
$disk_total = disk_total_space('/');
$disk_used_percent = round(($disk_total - $disk_free) / $disk_total * 100, 2);
$memory_usage = memory_get_usage(true);
$memory_limit = return_bytes(ini_get('memory_limit'));
$memory_used_percent = round(($memory_usage / $memory_limit) * 100, 2);

// Get recent errors from error log
$error_log = [];
$error_log_path = __DIR__ . '/../../logs/error.log';
if (file_exists($error_log_path) && is_readable($error_log_path)) {
    $error_log = array_slice(file($error_log_path), -50);
    $error_log = array_map('trim', $error_log);
} else {
    // Create logs directory if it doesn't exist
    if (!file_exists(__DIR__ . '/../../logs')) {
        mkdir(__DIR__ . '/../../logs', 0755, true);
    }
    // Create empty error log file
    file_put_contents($error_log_path, '');
    $error_log = ['No errors logged yet.'];
}

// Get access log summary
$access_log = [];
$access_log_path = __DIR__ . '/../../logs/access.log';
if (file_exists($access_log_path) && is_readable($access_log_path)) {
    $access_log = array_slice(file($access_log_path), -50);
    $access_log = array_map('trim', $access_log);
} else {
    // Create empty access log file
    file_put_contents($access_log_path, '');
    $access_log = ['No access logs yet.'];
}

// Get database stats using services
$total_actors = $db->query("SELECT COUNT(*) FROM actors")->fetchColumn();
$total_awards = $db->query("SELECT COUNT(*) FROM awards")->fetchColumn();
$total_productions = $db->query("SELECT COUNT(*) FROM productions")->fetchColumn();
$total_users = $db->query("SELECT COUNT(*) FROM users")->fetchColumn();

// Helper function to convert memory limit string to bytes
function return_bytes($val) {
    $val = trim($val);
    $last = strtolower($val[strlen($val)-1]);
    $val = (int)$val;
    switch($last) {
        case 'g': $val *= 1024;
        case 'm': $val *= 1024;
        case 'k': $val *= 1024;
    }
    return $val;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Actor Awards Visualizer</title>
    <link rel="stylesheet" href="/../assets/css/common.css">
    <link rel="stylesheet" href="/../assets/css/admin.css">
    <link rel="stylesheet" href="/../assets/css/navbar.css">
    <link rel="stylesheet" href="/../assets/css/footer.css">
</head>
<body>
    <?php include __DIR__ . "/../../src/Views/Components/Navbar.php"; ?>

    <!-- Admin Header - Full Width -->
    <div class="admin-header">
        <div class="admin-header-content">
            <h1>Admin Dashboard</h1>
            <?php if (!empty($message)): ?>
                <div class="admin-message <?php echo $messageType; ?>"><?php echo htmlspecialchars($message); ?></div>
            <?php endif; ?>
        </div>
    </div>

    <div class="admin-container">

        <!-- Quick Stats Overview -->
        <section class="admin-section">
            <h2>System Overview</h2>
            <div class="admin-section-content">
                <div class="stats-overview">
                    <div class="stat-card">
                        <h3>Total Users</h3>
                        <p class="stat-value"><?php echo number_format($total_users); ?></p>
                    </div>
                    <div class="stat-card">
                        <h3>Total Actors</h3>
                        <p class="stat-value"><?php echo number_format($total_actors); ?></p>
                    </div>
                    <div class="stat-card">
                        <h3>Total Awards</h3>
                        <p class="stat-value"><?php echo number_format($total_awards); ?></p>
                    </div>
                    <div class="stat-card">
                        <h3>Total Productions</h3>
                        <p class="stat-value"><?php echo number_format($total_productions); ?></p>
                    </div>
                </div>
            </div>
        </section>

        <!-- User Management Section -->
        <section class="admin-section">
            <h2>User Management</h2>
            <div class="admin-section-content">
                <!-- Create User Form -->
                <div class="user-form-container">
                    <h4>Create New User</h4>
                    <form method="post" class="user-form">
                        <input type="hidden" name="action" value="create_user">
                        <div class="form-row">
                            <input type="text" name="username" placeholder="Username" required>
                            <input type="email" name="email" placeholder="Email" required>
                        </div>
                        <div class="form-row">
                            <input type="password" name="password" placeholder="Password" required>
                            <select name="role" required>
                                <option value="user">User</option>
                                <option value="admin">Admin</option>
                            </select>
                        </div>
                        <button type="submit" class="admin-button">Create User</button>
                    </form>
                </div>
                
                <!-- Users List -->
                <div class="users-table-container">
                    <table class="users-table">
                        <thead>
                            <tr>
                                <th>Username</th>
                                <th>Email</th>
                                <th>Role</th>
                                <th>Created</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($users as $user): ?>
                            <tr id="user-row-<?php echo $user['id']; ?>">
                                <td data-label="Username">
                                    <span class="user-display" id="username-display-<?php echo $user['id']; ?>"><?php echo htmlspecialchars($user['username']); ?></span>
                                    <input type="text" class="user-edit-input" id="username-input-<?php echo $user['id']; ?>" value="<?php echo htmlspecialchars($user['username']); ?>" style="display: none;">
                                </td>
                                <td data-label="Email">
                                    <span class="user-display" id="email-display-<?php echo $user['id']; ?>"><?php echo htmlspecialchars($user['email']); ?></span>
                                    <input type="email" class="user-edit-input" id="email-input-<?php echo $user['id']; ?>" value="<?php echo htmlspecialchars($user['email']); ?>" style="display: none;">
                                </td>
                                <td data-label="Role">
                                    <span class="user-display" id="role-display-<?php echo $user['id']; ?>">
                                        <span class="role-badge <?php echo $user['role']; ?>"><?php echo ucfirst($user['role']); ?></span>
                                    </span>
                                    <select class="user-edit-input" id="role-input-<?php echo $user['id']; ?>" style="display: none;">
                                        <option value="user" <?php echo $user['role'] === 'user' ? 'selected' : ''; ?>>User</option>
                                        <option value="admin" <?php echo $user['role'] === 'admin' ? 'selected' : ''; ?>>Admin</option>
                                    </select>
                                </td>
                                <td data-label="Created"><?php echo date('M j, Y', strtotime($user['created_at'])); ?></td>
                                <td class="user-actions" data-label="Actions">
                                    <a href="/admin/user/edit/<?php echo $user['id']; ?>/" class="btn btn-edit">Edit</a>
                                    <?php if ($user['role'] !== 'admin'): ?>
                                        <form method="POST" action="/admin.php" style="display: inline-block;">
                                            <input type="hidden" name="action" value="delete_user">
                                            <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                            <button type="submit" class="btn btn-delete" onclick="return confirm('Are you sure you want to delete this user?');">Delete</button>
                                        </form>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </section>

        <!-- Backups & Maintenance Section -->
        <section class="admin-section">
            <h2>Backups & Maintenance</h2>
            <div class="admin-section-content">
                <div class="action-buttons">
                    <form method="post" style="display: contents;">
                        <input type="hidden" name="action" value="download_db">
                        <button type="submit" class="admin-button">Download Database</button>
                    </form>
                    
                    <form method="post" style="display: contents;">
                        <input type="hidden" name="action" value="backup_media">
                        <button type="submit" class="admin-button">Create Media Backup</button>
                    </form>
                </div>
            </div>
        </section>

        <!-- System Monitoring Section -->
        <section class="admin-section">
            <h2>System Monitoring</h2>
            <div class="admin-section-content">
                <div class="log-container">
                    <h4>Error Log</h4>
                    <div class="log-content">
                        <?php foreach ($error_log as $line): ?>
                            <div class="log-line"><?php echo htmlspecialchars($line); ?></div>
                        <?php endforeach; ?>
                    </div>
                </div>
                
                <div class="log-container">
                    <h4>Access Log</h4>
                    <div class="log-content">
                        <?php foreach ($access_log as $line): ?>
                            <div class="log-line"><?php echo htmlspecialchars($line); ?></div>
                        <?php endforeach; ?>
                    </div>
                </div>
                
                <div class="refresh-button-container">
                    <form method="post" style="display: inline;">
                        <input type="hidden" name="action" value="refresh_logs">
                        <button type="submit" class="admin-button">Refresh Logs</button>
                    </form>
                </div>
            </div>
        </section>

        <!-- System Health Section -->
        <section class="admin-section">
            <h2>System Health</h2>
            <div class="admin-section-content">
                <div class="health-indicators">
                    <div class="health-item">
                        <h4>Disk Space</h4>
                        <div class="progress-bar">
                            <div class="progress" style="width: <?php echo $disk_used_percent; ?>%"></div>
                        </div>
                        <p class="health-value"><?php echo $disk_used_percent; ?>% used</p>
                    </div>
                    <div class="health-item">
                        <h4>Memory Usage</h4>
                        <div class="progress-bar">
                            <div class="progress" style="width: <?php echo $memory_used_percent; ?>%"></div>
                        </div>
                        <p class="health-value"><?php echo round($memory_usage / 1024 / 1024, 2); ?> MB used</p>
                    </div>
                </div>
                
                <div class="refresh-button-container">
                    <form method="post" style="display: inline;">
                        <input type="hidden" name="action" value="refresh_health">
                        <button type="submit" class="admin-button">Refresh Health</button>
                    </form>
                </div>
            </div>
        </section>
    </div>

    <?php include __DIR__ . '/../../src/Views/Components/Footer.php'; ?>

</body>
</html>