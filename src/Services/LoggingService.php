<?php

namespace ActorAwards\Services;

class LoggingService
{
    private string $logDir;
    

    // sets up the logging directory - defaults to /logs in project root
    public function __construct(string $logDir = null)
    {
        $this->logDir = $logDir ?? __DIR__ . '/../../logs';
    }
    
    /**
     * Logs error messages to error.log
     * [timestamp] error message
     */
    public function logError(string $message): void
    {
        $this->writeLog('error.log', $message);
    }
    
    /**
     * Logs access events to access.log
     * Tracks who accessed what and when
     * [timestamp] [IP] [UserAgent] action
     * Helps with security monitoring and debugging
     */
    public function logAccess(string $message): void
    {
        $timestamp = date('Y-m-d H:i:s');
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';
        $logEntry = "[$timestamp] [$ip] [$userAgent] $message";
        
        $this->writeLog('access.log', $logEntry);
    }
    
    /**
     * Handles the actual file writing operations
     * - Creates log directory if missing
     * - Formats entries with timestamps
     * - Appends to existing log file
     */
    private function writeLog(string $filename, string $message): void
    {
        $logFile = $this->logDir . '/' . $filename;
        
        // Create logs directory if it doesn't exist
        if (!file_exists($this->logDir)) {
            mkdir($this->logDir, 0755, true);
        }
        
        // Format the log entry
        $timestamp = date('Y-m-d H:i:s');
        $logEntry = "[$timestamp] $message" . PHP_EOL;
        
        // Append to log file
        file_put_contents($logFile, $logEntry, FILE_APPEND);
    }
}
