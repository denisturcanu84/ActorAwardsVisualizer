<?php
require_once __DIR__ . '/../src/config/config.php';
require_once __DIR__ . '/../src/includes/db.php';
require_once __DIR__ . '/../src/includes/functions.php';
require_once __DIR__ . '/../src/includes/logging.php';

// Check if user is admin (you'll need to implement proper authentication)
session_start();
if (!isset($_SESSION['is_admin']) || !$_SESSION['is_admin']) {
    header('Location: /login.php');
    exit;
}

// Log admin access
logAccess('Admin dashboard accessed');

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    switch ($action) {
        case 'download_db':
            logAccess('Database download requested');
            require_once __DIR__ . '/admin/export_db.php';
            exit;
            
        case 'backup_media':
            logAccess('Media backup requested');
            require_once __DIR__ . '/admin/backup_media.php';
            exit;
            
        case 'refresh_logs':
            logAccess('Logs refreshed');
            // Just refresh the page to show updated logs
            header('Location: ' . $_SERVER['PHP_SELF']);
            exit;
            
        case 'refresh_stats':
            logAccess('Stats refreshed');
            // Just refresh the page to show updated stats
            header('Location: ' . $_SERVER['PHP_SELF']);
            exit;
            
        case 'refresh_health':
            logAccess('Health data refreshed');
            // Just refresh the page to show updated health data
            header('Location: ' . $_SERVER['PHP_SELF']);
            exit;
    }
}

// Get system stats
$disk_free = disk_free_space('/');
$disk_total = disk_total_space('/');
$disk_used_percent = round(($disk_total - $disk_free) / $disk_total * 100, 2);
$memory_usage = memory_get_usage(true);
$memory_limit = return_bytes(ini_get('memory_limit'));
$memory_used_percent = round(($memory_usage / $memory_limit) * 100, 2);

// Get recent errors from error log
$error_log = [];
$error_log_path = __DIR__ . '/../logs/error.log';
if (file_exists($error_log_path) && is_readable($error_log_path)) {
    $error_log = array_slice(file($error_log_path), -50);
    $error_log = array_map('trim', $error_log);
} else {
    // Create logs directory if it doesn't exist
    if (!file_exists(__DIR__ . '/../logs')) {
        mkdir(__DIR__ . '/../logs', 0755, true);
    }
    // Create empty error log file
    file_put_contents($error_log_path, '');
    $error_log = ['No errors logged yet.'];
}

// Get access log summary
$access_log = [];
$access_log_path = __DIR__ . '/../logs/access.log';
if (file_exists($access_log_path) && is_readable($access_log_path)) {
    $access_log = array_slice(file($access_log_path), -50);
    $access_log = array_map('trim', $access_log);
} else {
    // Create empty access log file
    file_put_contents($access_log_path, '');
    $access_log = ['No access logs yet.'];
}

// Get database stats
$db = getDbConnection();
$total_actors = $db->query("SELECT COUNT(*) FROM actors")->fetchColumn();
$total_awards = $db->query("SELECT COUNT(*) FROM awards")->fetchColumn();
$total_productions = $db->query("SELECT COUNT(*) FROM productions")->fetchColumn();

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
    <link rel="stylesheet" href="/assets/css/common.css">
    <link rel="stylesheet" href="/assets/css/admin.css">
    <link rel="stylesheet" href="/assets/css/navbar.css">
</head>
<body>
    <?php include __DIR__ . '/../src/includes/navbar.php'; ?>

    <div class="container">
        <div class="admin-header">
            <h1>Admin Dashboard</h1>
            <?php if (isset($message)): ?>
                <div class="admin-message"><?php echo htmlspecialchars($message); ?></div>
            <?php endif; ?>
        </div>

        <div class="admin-grid">
            <!-- Backups & Maintenance Section -->
            <section class="admin-section">
                <h2>Backups & Maintenance</h2>
                <div class="admin-card">
                    <div class="admin-actions">
                        <form method="post" class="admin-form">
                            <input type="hidden" name="action" value="download_db">
                            <button type="submit" class="admin-button">Download Database</button>
                        </form>
                        
                        <form method="post" class="admin-form">
                            <input type="hidden" name="action" value="backup_media">
                            <button type="submit" class="admin-button">Create Media Backup</button>
                        </form>
                    </div>
                </div>
            </section>

            <!-- System Monitoring Section -->
            <section class="admin-section">
                <h2>System Monitoring</h2>
                <div class="admin-card">
                    <div class="log-container">
                        <h3>Error Log</h3>
                        <div class="log-content">
                            <?php foreach ($error_log as $line): ?>
                                <div class="log-line"><?php echo htmlspecialchars($line); ?></div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <div class="log-container">
                        <h3>Access Log</h3>
                        <div class="log-content">
                            <?php foreach ($access_log as $line): ?>
                                <div class="log-line"><?php echo htmlspecialchars($line); ?></div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <form method="post" class="admin-form refresh-form">
                        <input type="hidden" name="action" value="refresh_logs">
                        <button type="submit" class="admin-button">Refresh Logs</button>
                    </form>
                </div>
            </section>

            <!-- Quick Stats Section -->
            <section class="admin-section">
                <h2>Quick Stats</h2>
                <div class="admin-card">
                    <div class="stats-grid">
                        <div class="stat-item">
                            <h3>Total Actors</h3>
                            <p class="stat-value"><?php echo number_format($total_actors); ?></p>
                        </div>
                        <div class="stat-item">
                            <h3>Total Awards</h3>
                            <p class="stat-value"><?php echo number_format($total_awards); ?></p>
                        </div>
                        <div class="stat-item">
                            <h3>Total Productions</h3>
                            <p class="stat-value"><?php echo number_format($total_productions); ?></p>
                        </div>
                    </div>
                    <form method="post" class="admin-form refresh-form">
                        <input type="hidden" name="action" value="refresh_stats">
                        <button type="submit" class="admin-button">Refresh Stats</button>
                    </form>
                </div>
            </section>

            <!-- System Health Section -->
            <section class="admin-section">
                <h2>System Health</h2>
                <div class="admin-card">
                    <div class="health-grid">
                        <div class="health-item">
                            <h3>Disk Space</h3>
                            <div class="progress-bar">
                                <div class="progress" style="width: <?php echo $disk_used_percent; ?>%"></div>
                            </div>
                            <p class="health-value"><?php echo $disk_used_percent; ?>% used</p>
                        </div>
                        <div class="health-item">
                            <h3>Memory Usage</h3>
                            <div class="progress-bar">
                                <div class="progress" style="width: <?php echo $memory_used_percent; ?>%"></div>
                            </div>
                            <p class="health-value"><?php echo round($memory_usage / 1024 / 1024, 2); ?> MB used</p>
                        </div>
                    </div>
                    <form method="post" class="admin-form refresh-form">
                        <input type="hidden" name="action" value="refresh_health">
                        <button type="submit" class="admin-button">Refresh Health</button>
                    </form>
                </div>
            </section>
        </div>
    </div>

    <?php include __DIR__ . '/../src/includes/footer.php'; ?>
</body>
</html> 