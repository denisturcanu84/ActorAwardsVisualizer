<?php

function logError($message) {
    $log_dir = __DIR__ . '/../../logs';
    $log_file = $log_dir . '/error.log';
    
    // Create logs directory if it doesn't exist
    if (!file_exists($log_dir)) {
        mkdir($log_dir, 0755, true);
    }
    
    // Format the log entry
    $timestamp = date('Y-m-d H:i:s');
    $log_entry = "[$timestamp] $message" . PHP_EOL;
    
    // Append to log file
    file_put_contents($log_file, $log_entry, FILE_APPEND);
}

function logAccess($message) {
    $log_dir = __DIR__ . '/../../logs';
    $log_file = $log_dir . '/access.log';
    
    // Create logs directory if it doesn't exist
    if (!file_exists($log_dir)) {
        mkdir($log_dir, 0755, true);
    }
    
    // Format the log entry
    $timestamp = date('Y-m-d H:i:s');
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';
    $log_entry = "[$timestamp] [$ip] [$user_agent] $message" . PHP_EOL;
    
    // Append to log file
    file_put_contents($log_file, $log_entry, FILE_APPEND);
} 