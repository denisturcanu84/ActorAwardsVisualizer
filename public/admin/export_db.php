<?php
/**
 * Database Export Script
 *
 * Handles exporting the SQLite database file for backup purposes.
 * - Performs admin authentication check
 * - Streams the database file directly to browser for download
 * - Includes timestamp in filename
 * - Handles file not found errors
 */
require_once __DIR__ . '/../../src/bootstrap.php';

use ActorAwards\Middleware\AuthenticationMiddleware;
use ActorAwards\Services\DatabaseService;

// Security: Verify user has admin privileges before allowing export
AuthenticationMiddleware::requireAdmin();

// File download setup:
// - Set as binary stream (octet-stream)
// - Force download with timestamped filename
header('Content-Type: application/octet-stream');
header('Content-Disposition: attachment; filename="actor_awards_backup_' . date('Y-m-d_H-i-s') . '.db"');

// Database export process:
// - Get path from DATABASE_PATH constant
// - If file exists, stream it directly to output (memory efficient)
// - If not found, return 404 error
$dbPath = DATABASE_PATH;
if (file_exists($dbPath)) {
    readfile($dbPath);  // Streams file contents directly to output buffer
} else {
    header('HTTP/1.1 404 Not Found');
    exit('Database file not found');  // Simple error message for failed export
}