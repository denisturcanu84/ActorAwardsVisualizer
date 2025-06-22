<?php
/**
 * Admin Log Viewer Script
 *
 * Retrieves and displays Apache server logs (error and access) for admin monitoring.
 * Performs admin authentication checks before providing log data in JSON format.
 * Only shows the last 50 entries from each log file for security and performance.
 */
require_once __DIR__ . '/../bootstrap.php';

use ActorAwards\Middleware\AuthenticationMiddleware;

// Verify admin privileges before proceeding
// Uses AuthenticationMiddleware to check user role and permissions
AuthenticationMiddleware::requireAdmin();

// Set response content type to JSON for API consistency
header('Content-Type: application/json');

try {
    // Initialize response structure with empty log arrays
    $response = [
        'error_log' => [],  // Will contain Apache error log entries
        'access_log' => []  // Will contain Apache access log entries
    ];

    // Retrieve and process error log
    $error_log_path = '/var/log/apache2/error.log';
    if (file_exists($error_log_path) && is_readable($error_log_path)) {
        // Read last 50 lines from error log and trim whitespace
        $error_log = array_slice(file($error_log_path), -50);
        $response['error_log'] = array_map('trim', $error_log);
    }

    // Retrieve and process access log
    $access_log_path = '/var/log/apache2/access.log';
    if (file_exists($access_log_path) && is_readable($access_log_path)) {
        // Read last 50 lines from access log and trim whitespace
        $access_log = array_slice(file($access_log_path), -50);
        $response['access_log'] = array_map('trim', $access_log);
    }

    // Output the combined log data as JSON
    echo json_encode($response);
} catch (Exception $e) {
    // Handle any exceptions and return error message
    echo json_encode([
        'success' => false,
        'message' => 'Error retrieving logs: ' . $e->getMessage()
    ]);
}