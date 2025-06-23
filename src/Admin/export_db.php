<?php
require_once __DIR__ . '/../../src/bootstrap.php';

use ActorAwards\Middleware\AuthenticationMiddleware;
use ActorAwards\Services\DatabaseService;
AuthenticationMiddleware::requireAdmin();

// File download setup:
// - Set as binary stream (octet-stream)
// - Force download with timestamped filename
header('Content-Type: application/octet-stream');
header('Content-Disposition: attachment; filename="actor_awards_backup_' . date('Y-m-d_H-i-s') . '.db"');

$dbPath = DATABASE_PATH;
if (file_exists($dbPath)) {
    readfile($dbPath);  // Streams file contents directly to output buffer
} else {
    header('HTTP/1.1 404 Not Found');
    exit('Database file not found'); 
}