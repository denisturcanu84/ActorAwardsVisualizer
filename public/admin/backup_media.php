<?php
require_once __DIR__ . '/../../src/config/config.php';
require_once __DIR__ . '/../../src/includes/db.php';
require_once __DIR__ . '/../../src/includes/functions.php';

// Check if user is admin
if (!isset($_SESSION['is_admin']) || !$_SESSION['is_admin']) {
    header('HTTP/1.1 403 Forbidden');
    exit('Access denied');
}

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
foreach ($directories as $dir) {
    if (file_exists($dir)) {
        $files = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($dir),
            RecursiveIteratorIterator::LEAVES_ONLY
        );

        foreach ($files as $file) {
            if (!$file->isDir()) {
                $filePath = $file->getRealPath();
                $relativePath = substr($filePath, strlen(__DIR__ . '/../../'));
                $zip->addFile($filePath, $relativePath);
            }
        }
    }
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