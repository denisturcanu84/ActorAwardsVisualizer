<?php
/**
 * Media Backup Script
 *
 * Handles creation of ZIP archives containing media assets for backup purposes.
 * - Backs up images and uploads from public/assets
 * - Includes admin authentication checks
 * - Creates temporary ZIP files for download
 * - Handles errors and empty backup cases
 */
require_once __DIR__ . '/../../src/bootstrap.php';

use ActorAwards\Middleware\AuthenticationMiddleware;

// Verify admin privileges before allowing backup operation
// Uses AuthenticationMiddleware to check user role and session
AuthenticationMiddleware::requireAdmin();

// Configure download headers for ZIP file
// - Sets MIME type to application/zip
// - Creates filename with timestamp for unique identification
header('Content-Type: application/zip');
header('Content-Disposition: attachment; filename="media_backup_' . date('Y-m-d_H-i-s') . '.zip"');

// Create temporary ZIP file in system temp directory
// - Uses unique filename with 'media_backup_' prefix
// - Temporary files are automatically cleaned up by the system
$tempFile = tempnam(sys_get_temp_dir(), 'media_backup_');
$zip = new ZipArchive();

if ($zip->open($tempFile, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== TRUE) {
    header('HTTP/1.1 500 Internal Server Error');
    exit('Could not create ZIP file');
}

// List of directories to include in backup
// Currently backs up:
// - public/assets/images (static media)
// - public/assets/uploads (user uploads)
$directories = [
    __DIR__ . '/../../public/assets/images',
    __DIR__ . '/../../public/assets/uploads'
];

// Initialize ZIP archive and file counter
// $filesAdded tracks successful additions for empty backup detection
$filesAdded = 0;
foreach ($directories as $dir) {
    // Only process directory if it exists
    if (file_exists($dir)) {
        // Create recursive iterator to scan all files in directory tree
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($dir),
            RecursiveIteratorIterator::LEAVES_ONLY
        );

        foreach ($iterator as $file) {
            // Skip directories, only process files
            if (!$file->isDir()) {
                $filePath = $file->getRealPath();
                // Store files with relative paths inside ZIP
                $relativePath = substr($filePath, strlen(__DIR__ . '/../../'));
                $zip->addFile($filePath, $relativePath);
                $filesAdded++; // Increment counter for each added file
            }
        }
    }
}

// Handle empty backup case by adding informational file
// Ensures ZIP is valid even when no media files exist
if ($filesAdded === 0) {
    $zip->addFromString('README.txt', 'No media files found to backup at ' . date('Y-m-d H:i:s'));
}

$zip->close();

// Finalize and output the ZIP archive
if (file_exists($tempFile)) {
    // Stream ZIP to browser
    readfile($tempFile);
    // Clean up temporary file after download
    unlink($tempFile);
} else {
    // Handle ZIP creation failure
    header('HTTP/1.1 500 Internal Server Error');
    exit('Backup file not found');
}