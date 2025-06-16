<?php
require_once '../src/includes/functions.php';

// Check if user is admin
session_start();
if (!isset($_SESSION['is_admin']) || !$_SESSION['is_admin']) {
    header('HTTP/1.1 403 Forbidden');
    exit(json_encode(['success' => false, 'message' => 'Access denied']));
}

// Set JSON response header
header('Content-Type: application/json');

try {
    $response = [
        'error_log' => [],
        'access_log' => []
    ];

    // Get error log
    $error_log_path = '/var/log/apache2/error.log';
    if (file_exists($error_log_path) && is_readable($error_log_path)) {
        $error_log = array_slice(file($error_log_path), -50);
        $response['error_log'] = array_map('trim', $error_log);
    }

    // Get access log
    $access_log_path = '/var/log/apache2/access.log';
    if (file_exists($access_log_path) && is_readable($access_log_path)) {
        $access_log = array_slice(file($access_log_path), -50);
        $response['access_log'] = array_map('trim', $access_log);
    }

    echo json_encode($response);
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error retrieving logs: ' . $e->getMessage()
    ]);
} 