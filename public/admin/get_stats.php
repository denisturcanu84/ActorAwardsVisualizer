<?php
require_once '../src/includes/db.php';
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
    // Get database stats
    $db = getDatabase();
    $total_actors = $db->query("SELECT COUNT(*) FROM actors")->fetchColumn();
    $total_awards = $db->query("SELECT COUNT(*) FROM awards")->fetchColumn();
    $total_productions = $db->query("SELECT COUNT(*) FROM productions")->fetchColumn();

    // Get system stats
    $disk_free = disk_free_space('/');
    $disk_total = disk_total_space('/');
    $disk_used_percent = round(($disk_total - $disk_free) / $disk_total * 100, 2);
    
    $memory_usage = memory_get_usage(true);
    $memory_limit = ini_get('memory_limit');
    $memory_limit_bytes = return_bytes($memory_limit);
    $memory_used_percent = round($memory_usage / $memory_limit_bytes * 100, 2);
    $memory_used_mb = round($memory_usage / 1024 / 1024, 2);

    echo json_encode([
        'success' => true,
        'total_actors' => $total_actors,
        'total_awards' => $total_awards,
        'total_productions' => $total_productions,
        'disk_used_percent' => $disk_used_percent,
        'memory_used_percent' => $memory_used_percent,
        'memory_used_mb' => $memory_used_mb
    ]);
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error retrieving stats: ' . $e->getMessage()
    ]);
}

// Helper function to convert memory limit string to bytes
function return_bytes($val) {
    $val = trim($val);
    $last = strtolower($val[strlen($val)-1]);
    $val = substr($val, 0, -1);
    switch($last) {
        case 'g':
            $val *= 1024;
        case 'm':
            $val *= 1024;
        case 'k':
            $val *= 1024;
    }
    return $val;
} 