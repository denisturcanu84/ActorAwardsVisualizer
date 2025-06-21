<?php

namespace ActorAwards\Exports;

use PDO;
use Exception;

require_once __DIR__ . '/../../vendor/autoload.php';

// handles exporting data in different formats (CSV, WebP, SVG)
class ExportHandler {
    private $db;
    
    // Sets up the handler with a database connection
    public function __construct($database) {
        $this->db = $database;
    }
    
    public function handleExport($exportType, $format) {
        try {
            // Get the data we want to export based on type
            $data = $this->getExportData($exportType);
            // Figure out what to name the file
            $filename = $this->getFilename($exportType);
            
            // Can't export if there's no data
            if (empty($data)) {
                throw new Exception('No data available for export');
            }
            
            switch ($format) {
                case 'csv':
                    $this->exportCSV($data, $filename);
                    break;
                case 'webp':
                    $this->exportWebP($data, $filename, $exportType);
                    break;
                case 'svg':
                    $this->exportSVG($data, $filename, $exportType);
                    break;
                default:
                    throw new Exception('Invalid format type');
            }
        } catch (Exception $e) {
            error_log('Export error: ' . $e->getMessage());
            error_log('Export error trace: ' . $e->getTraceAsString());
            
            // Clear any output buffer
            while (ob_get_level()) {
                ob_end_clean();
            }
            
            header('HTTP/1.1 500 Internal Server Error');
            header('Content-Type: text/html');
            echo '<pre>Debug Information:
            
Error: ' . htmlspecialchars($e->getMessage()) . '

PHP Version: ' . PHP_VERSION . '
GD Extension: ' . (extension_loaded('gd') ? 'Yes' : 'No') . '
WebP Support: ' . (function_exists('imagewebp') ? 'Yes' : 'No') . '

GD Info:
';
            if (extension_loaded('gd')) {
                print_r(gd_info());
            }
            echo '
Available Image Formats:
';
            if (function_exists('imagetypes')) {
                $formats = imagetypes();
                echo 'IMG_GIF: ' . (($formats & IMG_GIF) ? 'Yes' : 'No') . "\n";
                echo 'IMG_JPG: ' . (($formats & IMG_JPG) ? 'Yes' : 'No') . "\n";
                echo 'IMG_PNG: ' . (($formats & IMG_PNG) ? 'Yes' : 'No') . "\n";
                echo 'IMG_WEBP: ' . (($formats & IMG_WEBP) ? 'Yes' : 'No') . "\n";
            }
            echo '</pre>';
            exit;
        }
    }
    
    // Gets different types of stats from the database
    private function getExportData($exportType) {
        switch ($exportType) {
            // Stats grouped by year
            case 'yearly':
                return $this->db->query("
                    SELECT 
                        year,
                        COUNT(*) as total_nominations,
                        SUM(CASE WHEN won = 'True' THEN 1 ELSE 0 END) as total_wins,
                        ROUND(SUM(CASE WHEN won = 'True' THEN 1 ELSE 0 END) * 100.0 / COUNT(*), 1) as win_rate
                    FROM awards 
                    GROUP BY year 
                    ORDER BY year DESC
                ")->fetchAll(PDO::FETCH_ASSOC);
                
            // Stats grouped by award category
            case 'category':
                return $this->db->query("
                    SELECT 
                        category,
                        COUNT(*) as total_nominations,
                        SUM(CASE WHEN won = 'True' THEN 1 ELSE 0 END) as total_wins,
                        ROUND(SUM(CASE WHEN won = 'True' THEN 1 ELSE 0 END) * 100.0 / COUNT(*), 1) as win_rate
                    FROM awards 
                    GROUP BY category 
                    ORDER BY total_nominations DESC
                ")->fetchAll(PDO::FETCH_ASSOC);
                
            // Stats about top performing actors
            case 'performers':
                return $this->db->query("
                    SELECT 
                        a.full_name,
                        COUNT(*) as total_nominations,
                        SUM(CASE WHEN a.won = 'True' THEN 1 ELSE 0 END) as total_wins,
                        ROUND(SUM(CASE WHEN a.won = 'True' THEN 1 ELSE 0 END) * 100.0 / COUNT(*), 1) as win_rate
                    FROM awards a
                    WHERE a.full_name IS NOT NULL AND a.full_name <> ''
                    GROUP BY a.full_name
                    ORDER BY total_wins DESC, total_nominations DESC
                    LIMIT 10
                ")->fetchAll(PDO::FETCH_ASSOC);
                
            default:
                throw new Exception('Invalid export type');
        }
    }
    
    // Creates different filenames based on export type
    private function getFilename($exportType) {
        switch ($exportType) {
            case 'yearly':
                return "yearly_statistics";
            case 'category':
                return "category_statistics";
            case 'performers':
                return "top_performers";
            default:
                return "statistics";
        }
    }
    
    // Creates a CSV file from the data
    private function exportCSV($data, $filename) {
        // Make sure nothing else is trying to output
        // Clear any output buffer
        while (ob_get_level()) {
            ob_end_clean();
        }
        
        if (headers_sent($file, $line)) {
            throw new Exception("Headers already sent in $file on line $line");
        }
        
        header('Content-Type: text/csv; charset=UTF-8');
        header('Content-Disposition: attachment; filename="' . $filename . '.csv"');
        header('Cache-Control: no-cache, no-store, must-revalidate');
        header('Pragma: no-cache');
        header('Expires: 0');
        header('X-Content-Type-Options: nosniff');
        header('Content-Transfer-Encoding: binary');
        
        $output = fopen('php://output', 'w');
        if ($output === false) {
            throw new Exception('Failed to open output stream');
        }
        
        fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
        fputcsv($output, array_keys($data[0]));
        
        foreach ($data as $row) {
            fputcsv($output, $row);
        }
        
        fclose($output);
        exit;
    }
    
    // Creates a WebP image chart from the data
    private function exportWebP($data, $filename, $exportType) {
        // First check if we have the needed tools
        // Diagnostic information
        if (!extension_loaded('gd')) {
            throw new Exception('GD extension is not loaded');
        }
        
        if (!function_exists('imagecreatetruecolor')) {
            throw new Exception('GD imagecreatetruecolor function not available');
        }
        
        // Check if WebP is supported
        $gdinfo = gd_info();
        if (!isset($gdinfo['WebP Support']) || !$gdinfo['WebP Support']) {
            // Fallback to PNG if WebP is not supported
            $this->exportPNG($data, $filename, $exportType);
            return;
        }
        
        if (!function_exists('imagewebp')) {
            throw new Exception('imagewebp function not available');
        }
        
        // Clear any output buffer
        while (ob_get_level()) {
            ob_end_clean();
        }
        
        if (headers_sent($file, $line)) {
            throw new Exception("Headers already sent in $file on line $line");
        }
        
        try {
            $width = 800;
            $height = 600;
            $padding = 40;
            
            // Create image using GD
            $image = imagecreatetruecolor($width, $height);
            if (!$image) {
                throw new Exception('Failed to create image resource');
            }
            
            // Allocate colors
            $white = imagecolorallocate($image, 255, 255, 255);
            $blue = imagecolorallocate($image, 74, 144, 226);
            $darkblue = imagecolorallocate($image, 53, 122, 189);
            $gray = imagecolorallocate($image, 102, 102, 102);
            $lightgray = imagecolorallocate($image, 238, 238, 238);
            $darkgray = imagecolorallocate($image, 36, 59, 85);
            $black = imagecolorallocate($image, 0, 0, 0);
            
            if ($white === false || $blue === false || $darkblue === false || 
                $gray === false || $lightgray === false || $darkgray === false || $black === false) {
                imagedestroy($image);
                throw new Exception('Failed to allocate colors');
            }
            
            // Fill background
            if (!imagefill($image, 0, 0, $white)) {
                imagedestroy($image);
                throw new Exception('Failed to fill background');
            }
            
            // Add title
            $title = ucfirst($exportType) . ' Statistics';
            imagestring($image, 5, $padding, 20, $title, $darkgray);
            
            // Draw the chart
            $this->drawSimpleChart($image, $data, $width, $height, $padding, $blue, $darkblue, $gray, $lightgray, $black);
            
            // Set headers for WebP
            header('Content-Type: image/webp');
            header('Content-Disposition: attachment; filename="' . $filename . '.webp"');
            header('Cache-Control: no-cache, no-store, must-revalidate');
            header('Pragma: no-cache');
            header('Expires: 0');
            
            // Output WebP image
            $success = imagewebp($image, null, 80);
            
            // Clean up
            imagedestroy($image);
            
            if (!$success) {
                throw new Exception('Failed to generate WebP image');
            }
            
        } catch (Exception $e) {
            if (isset($image) && is_resource($image)) {
                imagedestroy($image);
            }
            throw $e;
        }
        
        exit;
    }
    
    // Creates a PNG image if WebP isn't available
    private function exportPNG($data, $filename, $exportType) {
        // Fallback option when WebP doesn't work
        // Clear any output buffer
        while (ob_get_level()) {
            ob_end_clean();
        }
        
        if (headers_sent($file, $line)) {
            throw new Exception("Headers already sent in $file on line $line");
        }
        
        try {
            $width = 800;
            $height = 600;
            $padding = 40;
            
            $image = imagecreatetruecolor($width, $height);
            if (!$image) {
                throw new Exception('Failed to create image resource');
            }
            
            // Allocate colors
            $white = imagecolorallocate($image, 255, 255, 255);
            $blue = imagecolorallocate($image, 74, 144, 226);
            $darkblue = imagecolorallocate($image, 53, 122, 189);
            $gray = imagecolorallocate($image, 102, 102, 102);
            $lightgray = imagecolorallocate($image, 238, 238, 238);
            $darkgray = imagecolorallocate($image, 36, 59, 85);
            $black = imagecolorallocate($image, 0, 0, 0);
            
            // Fill background
            imagefill($image, 0, 0, $white);
            
            // Add title
            $title = ucfirst($exportType) . ' Statistics (PNG fallback)';
            imagestring($image, 5, $padding, 20, $title, $darkgray);
            
            // Draw the chart
            $this->drawSimpleChart($image, $data, $width, $height, $padding, $blue, $darkblue, $gray, $lightgray, $black);
            
            // Set headers for PNG
            header('Content-Type: image/png');
            header('Content-Disposition: attachment; filename="' . $filename . '.png"');
            header('Cache-Control: no-cache, no-store, must-revalidate');
            header('Pragma: no-cache');
            header('Expires: 0');
            
            // Output PNG image
            $success = imagepng($image, null, 9);
            
            // Clean up
            imagedestroy($image);
            
            if (!$success) {
                throw new Exception('Failed to generate PNG image');
            }
            
        } catch (Exception $e) {
            if (isset($image) && is_resource($image)) {
                imagedestroy($image);
            }
            throw $e;
        }
        
        exit;
    }
    
    // Draws a basic bar chart on the image
    private function drawSimpleChart($image, $data, $width, $height, $padding, $blue, $darkblue, $gray, $lightgray, $black) {
        if (empty($data)) {
            imagestring($image, 3, $padding, $height/2, 'No data available', $black);
            return;
        }
        
        // Limit data to prevent overcrowding
        $displayData = array_slice($data, 0, 8); // Reduced to 8 for better display
        $dataCount = count($displayData);
        
        if ($dataCount == 0) {
            imagestring($image, 3, $padding, $height/2, 'No data to display', $black);
            return;
        }
        
        // Find max value for scaling
        $maxValue = 1; // Prevent division by zero
        foreach ($displayData as $row) {
            $nominations = isset($row['total_nominations']) ? (int)$row['total_nominations'] : 0;
            $wins = isset($row['total_wins']) ? (int)$row['total_wins'] : 0;
            $maxValue = max($maxValue, $nominations, $wins);
        }
        
        // Chart dimensions
        $chartX = $padding + 50;
        $chartY = $padding + 60;
        $chartWidth = $width - $chartX - $padding;
        $chartHeight = $height - $chartY - $padding - 80;
        
        // Bar dimensions
        $barWidth = max(15, min(35, $chartWidth / ($dataCount * 3)));
        $spacing = 5;
        
        // Draw simple grid
        for ($i = 0; $i <= 4; $i++) {
            $y = $chartY + ($i * $chartHeight / 4);
            imageline($image, $chartX, $y, $chartX + $chartWidth, $y, $lightgray);
            
            $value = $maxValue - ($i * $maxValue / 4);
            imagestring($image, 2, $chartX - 45, $y - 6, (string)round($value), $gray);
        }
        
        // Draw axes
        imageline($image, $chartX, $chartY, $chartX, $chartY + $chartHeight, $black);
        imageline($image, $chartX, $chartY + $chartHeight, $chartX + $chartWidth, $chartY + $chartHeight, $black);
        
        // Draw bars
        $x = $chartX + 15;
        foreach ($displayData as $index => $row) {
            if ($x + ($barWidth * 2) + $spacing > $chartX + $chartWidth) {
                break; // Stop if we run out of space
            }
            
            $nominations = isset($row['total_nominations']) ? (int)$row['total_nominations'] : 0;
            $wins = isset($row['total_wins']) ? (int)$row['total_wins'] : 0;
            
            // Draw nominations bar
            if ($nominations > 0) {
                $barHeight = ($nominations / $maxValue) * $chartHeight;
                $barTop = $chartY + $chartHeight - $barHeight;
                imagefilledrectangle($image, 
                    (int)$x, 
                    (int)$barTop, 
                    (int)($x + $barWidth), 
                    (int)($chartY + $chartHeight), 
                    $blue
                );
            }
            
            // Draw wins bar
            if ($wins > 0) {
                $barHeight = ($wins / $maxValue) * $chartHeight;
                $barTop = $chartY + $chartHeight - $barHeight;
                imagefilledrectangle($image, 
                    (int)($x + $barWidth + $spacing), 
                    (int)$barTop, 
                    (int)($x + ($barWidth * 2) + $spacing), 
                    (int)($chartY + $chartHeight), 
                    $darkblue
                );
            }
            
            // Add label
            $label = $this->getLabel($row);
            if (strlen($label) > 6) {
                $label = substr($label, 0, 6) . '..';
            }
            
            // Position label
            $labelX = $x + $barWidth/2;
            $labelY = $chartY + $chartHeight + 20;
            imagestring($image, 1, (int)($labelX - 15), (int)$labelY, $label, $gray);
            
            $x += ($barWidth * 2) + $spacing + 15;
        }
        
        // Add simple legend
        $legendY = $padding + 30;
        imagefilledrectangle($image, $width - 150, $legendY, $width - 130, $legendY + 10, $blue);
        imagestring($image, 2, $width - 125, $legendY, 'Nominations', $black);
        
        imagefilledrectangle($image, $width - 150, $legendY + 20, $width - 130, $legendY + 30, $darkblue);
        imagestring($image, 2, $width - 125, $legendY + 20, 'Wins', $black);
        
        // Add axis labels
        imagestring($image, 2, $chartX + $chartWidth/2 - 30, $height - 40, 'Categories', $black);
        imagestring($image, 2, 10, $chartY + $chartHeight/2, 'Count', $black);
    }
    
    // Creates an SVG chart from the data
    private function exportSVG($data, $filename, $exportType) {
        // Make sure nothing else is trying to output
        // Clear any output buffer
        while (ob_get_level()) {
            ob_end_clean();
        }
        
        if (headers_sent($file, $line)) {
            throw new Exception("Headers already sent in $file on line $line");
        }
        
        header('Content-Type: image/svg+xml; charset=UTF-8');
        header('Content-Disposition: attachment; filename="' . $filename . '.svg"');
        header('Cache-Control: no-cache, no-store, must-revalidate');
        header('Pragma: no-cache');
        header('Expires: 0');
        
        $width = 1000;
        $height = 700;
        $padding = 50;
        
        $svg = $this->createSVG($data, $width, $height, $padding, $exportType);
        
        echo $svg;
        exit;
    }
    
    // Builds the actual SVG markup for the chart
    private function createSVG($data, $width, $height, $padding, $exportType) {
        if (empty($data)) {
            return '<svg width="' . $width . '" height="' . $height . '" xmlns="http://www.w3.org/2000/svg"><text x="50" y="50">No data available</text></svg>';
        }
        
        $maxValue = 0;
        foreach ($data as $row) {
            $maxValue = max($maxValue, (int)$row['total_nominations'], (int)$row['total_wins']);
        }
        
        if ($maxValue == 0) {
            return '<svg width="' . $width . '" height="' . $height . '" xmlns="http://www.w3.org/2000/svg"><text x="50" y="50">No data to display</text></svg>';
        }
        
        $chartStartY = $padding + 70;
        $chartHeight = $height - $chartStartY - $padding - 80;
        $chartWidth = $width - (2 * $padding);
        
        // Calculate bar width
        $dataCount = count($data);
        $maxBars = min($dataCount, 15);
        $barWidth = max(20, min(50, $chartWidth / ($maxBars * 3)));
        $spacing = $barWidth * 0.5;
        
        $displayData = array_slice($data, 0, $maxBars);
        
        $svg = '<svg width="' . $width . '" height="' . $height . '" xmlns="http://www.w3.org/2000/svg">';
        
        // Background
        $svg .= '<rect width="' . $width . '" height="' . $height . '" fill="#ffffff"/>';
        
        // Title
        $title = ucfirst($exportType) . ' Statistics';
        $svg .= '<text x="' . ($width/2) . '" y="30" font-family="Arial, sans-serif" font-size="24" fill="#243B55" text-anchor="middle">' . htmlspecialchars($title) . '</text>';
        
        // Grid lines and Y-axis labels
        for ($i = 0; $i <= 5; $i++) {
            $y = $height - $padding - 60 - ($i * $chartHeight / 5);
            $svg .= '<line x1="' . $padding . '" y1="' . $y . '" x2="' . ($width - $padding) . '" y2="' . $y . '" stroke="#eeeeee" stroke-width="1"/>';
            
            $value = round($maxValue * $i / 5);
            $svg .= '<text x="' . ($padding - 10) . '" y="' . ($y + 4) . '" font-family="Arial, sans-serif" font-size="12" fill="#666666" text-anchor="end">' . $value . '</text>';
        }
        
        // Y-axis
        $svg .= '<line x1="' . $padding . '" y1="' . $chartStartY . '" x2="' . $padding . '" y2="' . ($height - $padding - 60) . '" stroke="#000000" stroke-width="2"/>';
        
        // X-axis
        $svg .= '<line x1="' . $padding . '" y1="' . ($height - $padding - 60) . '" x2="' . ($width - $padding) . '" y2="' . ($height - $padding - 60) . '" stroke="#000000" stroke-width="2"/>';
        
        // Bars
        foreach ($displayData as $index => $row) {
            $x = $padding + 20 + ($index * ($barWidth * 2 + $spacing));
            
            if ($x + ($barWidth * 2) > $width - $padding) break;
            
            // Nominations bar
            $nomHeight = ((int)$row['total_nominations'] / $maxValue) * $chartHeight;
            $y = $height - $padding - 60 - $nomHeight;
            $svg .= '<rect x="' . $x . '" y="' . $y . '" width="' . $barWidth . '" height="' . $nomHeight . '" fill="#4a90e2"/>';
            
            // Wins bar
            $winsHeight = ((int)$row['total_wins'] / $maxValue) * $chartHeight;
            $y_wins = $height - $padding - 60 - $winsHeight;
            $svg .= '<rect x="' . ($x + $barWidth + 5) . '" y="' . $y_wins . '" width="' . $barWidth . '" height="' . $winsHeight . '" fill="#357abd"/>';
            
            // Labels
            $label = $this->getLabel($row);
            if (strlen($label) > 10) {
                $label = substr($label, 0, 10) . '...';
            }
            
            $labelX = $x + $barWidth;
            $labelY = $height - $padding - 40;
            $svg .= '<text x="' . $labelX . '" y="' . $labelY . '" font-family="Arial, sans-serif" font-size="10" fill="#666666" text-anchor="middle">' . htmlspecialchars($label) . '</text>';
        }
        
        // Legend
        $legendY = $padding + 40;
        $svg .= '<rect x="' . ($width - 200) . '" y="' . $legendY . '" width="20" height="15" fill="#4a90e2"/>';
        $svg .= '<text x="' . ($width - 175) . '" y="' . ($legendY + 12) . '" font-family="Arial, sans-serif" font-size="12" fill="#000000">Nominations</text>';
        
        $svg .= '<rect x="' . ($width - 200) . '" y="' . ($legendY + 25) . '" width="20" height="15" fill="#357abd"/>';
        $svg .= '<text x="' . ($width - 175) . '" y="' . ($legendY + 37) . '" font-family="Arial, sans-serif" font-size="12" fill="#000000">Wins</text>';
        
        // Axis labels
        $svg .= '<text x="' . ($width/2) . '" y="' . ($height - 10) . '" font-family="Arial, sans-serif" font-size="14" fill="#000000" text-anchor="middle">Categories/Years</text>';
        $svg .= '<text x="20" y="' . ($height/2) . '" font-family="Arial, sans-serif" font-size="14" fill="#000000" text-anchor="middle" transform="rotate(-90, 20, ' . ($height/2) . ')">Count</text>';
        
        $svg .= '</svg>';
        
        return $svg;
    }
    
    // Figures out what label to use for each data point
    private function getLabel($row) {
        if (isset($row['year'])) {
            return (string)$row['year'];
        } elseif (isset($row['category'])) {
            return (string)$row['category'];
        } elseif (isset($row['full_name'])) {
            return (string)$row['full_name'];
        }
        return 'Unknown';
    }
}
?>