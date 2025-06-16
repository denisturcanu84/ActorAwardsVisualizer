<?php
require_once __DIR__ . '/../../src/config/config.php';
require_once __DIR__ . '/../../src/includes/db.php';
require_once __DIR__ . '/../../src/includes/functions.php';

// Check if user is admin
if (!isset($_SESSION['is_admin']) || !$_SESSION['is_admin']) {
    header('HTTP/1.1 403 Forbidden');
    exit('Access denied');
}

// Set headers for file download
header('Content-Type: application/octet-stream');
header('Content-Disposition: attachment; filename="actor_awards_backup_' . date('Y-m-d_H-i-s') . '.db"');

// Get database connection
$db = getDbConnection();

// Read the database file
$dbPath = DATABASE_PATH;
if (file_exists($dbPath)) {
    readfile($dbPath);
} else {
    header('HTTP/1.1 404 Not Found');
    exit('Database file not found');
} 