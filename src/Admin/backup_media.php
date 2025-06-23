<?php
/*
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

AuthenticationMiddleware::requireAdmin();


// -Creates filename with timestamp for unique identification
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
    if (file_exists($dir)) {
        // Create recursive iterator to scan all files in directory tree
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($dir),
            RecursiveIteratorIterator::LEAVES_ONLY
        );

        foreach ($iterator as $file) {
            if (!$file->isDir()) {
                $filePath = $file->getRealPath();
                $relativePath = substr($filePath, strlen(__DIR__ . '/../../'));
                $zip->addFile($filePath, $relativePath);
                $filesAdded++;
            }
        }
    }
}

// Handle empty backup case by adding informational file
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
    header('HTTP/1.1 500 Internal Server Error');
    exit('Backup file not found');
}