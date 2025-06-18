<?php
require_once __DIR__ . '/../../src/bootstrap.php';

use ActorAwards\Middleware\AuthenticationMiddleware;

// Check if user is admin
AuthenticationMiddleware::requireAdmin();

// Set headers for file download
header('Content-Type: application/zip');
header('Content-Disposition: attachment; filename="media_backup_' . date('Y-m-d_H-i-s') . '.zip"');

// Create a temporary file for the ZIP
$tempFile = tempnam(sys_get_temp_dir(), 'media_backup_');
$zip = new ZipArchive();

if ($zip->open($tempFile, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== TRUE) {
    header('HTTP/1.1 500 Internal Server Error');
    exit('Could not create ZIP file');
}

// Define directories to backup
$directories = [
    __DIR__ . '/../../public/assets/images',
    __DIR__ . '/../../public/assets/uploads'
];

// Add files to ZIP
$filesAdded = 0;
foreach ($directories as $dir) {
    if (file_exists($dir)) {
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

// If no files were found, add a readme file
if ($filesAdded === 0) {
    $zip->addFromString('README.txt', 'No media files found to backup at ' . date('Y-m-d H:i:s'));
}

$zip->close();

// Output the ZIP file
if (file_exists($tempFile)) {
    readfile($tempFile);
    unlink($tempFile); // Delete the temporary file
} else {
    header('HTTP/1.1 500 Internal Server Error');
    exit('Backup file not found');
} 