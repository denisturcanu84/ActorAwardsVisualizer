<?php
// Load application bootstrap file containing autoloader and config
require_once __DIR__ . '/../src/bootstrap.php';

use ActorAwards\Middleware\AuthenticationMiddleware;
use ActorAwards\Services\DatabaseService;

AuthenticationMiddleware::requireAdmin();

// Set response type to JSON for API endpoint
header('Content-Type: application/json');

try {
    // Establish database connection using centralized service
    $db = DatabaseService::getConnection();
    
    // Database statistics: Count records in key tables
    $total_actors = $db->query("SELECT COUNT(*) FROM actors")->fetchColumn();
    $total_awards = $db->query("SELECT COUNT(*) FROM awards")->fetchColumn();
    $total_productions = $db->query("SELECT COUNT(*) FROM productions")->fetchColumn();

    // System statistics: Calculate disk and memory usage
    $disk_free = disk_free_space('/');  // Free space in bytes
    $disk_total = disk_total_space('/'); // Total disk capacity
    $disk_used_percent = round(($disk_total - $disk_free) / $disk_total * 100, 2);
    
    // Memory statistics: Current usage vs PHP's memory limit
    $memory_usage = memory_get_usage(true); // Current memory usage in bytes
    $memory_limit = ini_get('memory_limit'); // PHP memory limit string (e.g. '128M')
    $memory_limit_bytes = return_bytes($memory_limit); // Convert to bytes
    $memory_used_percent = round($memory_usage / $memory_limit_bytes * 100, 2);
    $memory_used_mb = round($memory_usage / 1024 / 1024, 2); // Convert to MB

    // Format all statistics into JSON response
    echo json_encode([
        'success' => true,  // Operation status flag
        'total_actors' => $total_actors,  // Count of actors in database
        'total_awards' => $total_awards,  // Count of awards
        'total_productions' => $total_productions,  // Count of productions
        'disk_used_percent' => $disk_used_percent,  // Disk usage percentage
        'memory_used_percent' => $memory_used_percent,  // Memory usage percentage
        'memory_used_mb' => $memory_used_mb  // Memory usage in MB
    ]);
} catch (Exception $e) {
    // Error handling: Return error details in JSON format
    echo json_encode([
        'success' => false,  // Operation failed
        'message' => 'Error retrieving stats: ' . $e->getMessage()  // Error details
    ]);
}

/**
 * Converts PHP memory limit string (e.g. '128M') to bytes
 * @param string $val Memory limit string (e.g. '128M', '2G')
 * @return int Memory limit in bytes
 */
function return_bytes($val) {
    $val = trim($val);
    $last = strtolower($val[strlen($val)-1]); // Get last character (K/M/G)
    $val = substr($val, 0, -1); // Remove suffix
    switch($last) {
        case 'g': $val *= 1024; // Convert GB to MB
        case 'm': $val *= 1024; // Convert MB to KB
        case 'k': $val *= 1024; // Convert KB to bytes
    }
    return $val;
}
