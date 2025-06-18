<?php
require_once __DIR__ . '/../../src/bootstrap.php';

use ActorAwards\Middleware\AuthenticationMiddleware;
use ActorAwards\Services\DatabaseService;

// Check if user is admin
AuthenticationMiddleware::requireAdmin();

// Set headers for file download
header('Content-Type: application/octet-stream');
header('Content-Disposition: attachment; filename="actor_awards_backup_' . date('Y-m-d_H-i-s') . '.db"');

// Read the database file
$dbPath = DATABASE_PATH;
if (file_exists($dbPath)) {
    readfile($dbPath);
} else {
    header('HTTP/1.1 404 Not Found');
    exit('Database file not found');
} 