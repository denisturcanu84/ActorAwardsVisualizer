<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';

// Initialize database connection
$db = getDbConnection();

// Get yearly statistics
$yearlyStats = $db->query("
    SELECT 
        year,
        COUNT(*) as total_nominations,
        SUM(CASE WHEN won = 'True' THEN 1 ELSE 0 END) as total_wins
    FROM awards 
    GROUP BY year 
    ORDER BY year DESC
")->fetchAll(PDO::FETCH_ASSOC);

// Get category statistics
$categoryStats = $db->query("
    SELECT 
        category,
        COUNT(*) as total_nominations,
        SUM(CASE WHEN won = 'True' THEN 1 ELSE 0 END) as total_wins,
        ROUND(SUM(CASE WHEN won = 'True' THEN 1 ELSE 0 END) * 100.0 / COUNT(*), 1) as win_rate
    FROM awards 
    GROUP BY category 
    ORDER BY total_nominations DESC
")->fetchAll(PDO::FETCH_ASSOC);

// Get top actors
$topActors = $db->query("
    SELECT 
        a.full_name,
        COUNT(*) as total_nominations,
        SUM(CASE WHEN a.won = 'True' THEN 1 ELSE 0 END) as total_wins,
        ac.profile_path
    FROM awards a
    LEFT JOIN actors ac ON a.tmdb_actor_id = ac.tmdb_id
    GROUP BY a.full_name, ac.profile_path
    ORDER BY total_wins DESC, total_nominations DESC
    LIMIT 10
")->fetchAll(PDO::FETCH_ASSOC);

// Get top productions
$topProductions = $db->query("
    SELECT 
        p.title,
        COUNT(*) as total_nominations,
        SUM(CASE WHEN a.won = 'True' THEN 1 ELSE 0 END) as total_wins,
        p.poster_path
    FROM awards a
    JOIN productions p ON a.tmdb_show_id = p.tmdb_id
    GROUP BY p.title, p.poster_path
    ORDER BY total_wins DESC, total_nominations DESC
    LIMIT 10
")->fetchAll(PDO::FETCH_ASSOC);

// Function to generate CSV
function generateCSV($data, $filename) {
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    
    $output = fopen('php://output', 'w');
    
    // Write headers
    fputcsv($output, array_keys($data[0]));
    
    // Write data
    foreach ($data as $row) {
        fputcsv($output, $row);
    }
    
    fclose($output);
    exit;
}

// Function to generate WebP
function generateWebP($data, $filename) {
    // Start output buffering
    ob_start();
    
    // Create an image
    $width = 800;
    $height = 600;
    $image = imagecreatetruecolor($width, $height);
    
    // Set colors
    $bg = imagecolorallocate($image, 255, 255, 255);
    $textColor = imagecolorallocate($image, 36, 59, 85);
    $barColor = imagecolorallocate($image, 74, 144, 226);
    
    // Fill background
    imagefilledrectangle($image, 0, 0, $width, $height, $bg);
    
    // Add title
    $title = "Award Statistics";
    $fontSize = 5;
    $titleWidth = imagefontwidth($fontSize) * strlen($title);
    $titleX = ($width - $titleWidth) / 2;
    imagestring($image, $fontSize, $titleX, 20, $title, $textColor);
    
    // Determine the value field based on data type
    $valueField = 'total_nominations';
    if (isset($data[0]['total_wins'])) {
        $valueField = 'total_wins';
    }
    
    // Add data visualization
    $barWidth = 40;
    $barSpacing = 20;
    $maxValue = max(array_column($data, $valueField));
    $scale = ($height - 100) / $maxValue;
    
    $x = 50;
    foreach ($data as $row) {
        if (!isset($row[$valueField])) {
            continue; // Skip invalid data
        }
        
        $barHeight = $row[$valueField] * $scale;
        imagefilledrectangle($image, $x, $height - $barHeight - 50, $x + $barWidth, $height - 50, $barColor);
        $x += $barWidth + $barSpacing;
    }
    
    // Clear any previous output
    ob_clean();
    
    // Set headers
    header('Content-Type: image/webp');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    
    // Output WebP
    imagewebp($image);
    imagedestroy($image);
    
    // End output buffering
    ob_end_flush();
    exit;
}

// Function to generate SVG
function generateSVG($data, $filename) {
    // Start output buffering to prevent header issues
    ob_start();
    
    $width = 800;
    $height = 600;
    $padding = 50;
    $barWidth = 40;
    $barSpacing = 20;
    
    // Determine the label and value fields based on data type
    $labelField = 'category';
    $valueField = 'total_nominations';
    
    // Check if this is actor or production data
    if (isset($data[0]['full_name'])) {
        $labelField = 'full_name';
        $valueField = 'total_wins';
    } elseif (isset($data[0]['title'])) {
        $labelField = 'title';
        $valueField = 'total_wins';
    } elseif (isset($data[0]['year'])) {
        $labelField = 'year';
        $valueField = 'total_nominations';
    }
    
    $maxValue = max(array_column($data, $valueField));
    $scale = ($height - 2 * $padding) / $maxValue;
    
    $svg = '<?xml version="1.0" encoding="UTF-8" standalone="no"?>';
    $svg .= '<svg width="' . $width . '" height="' . $height . '" xmlns="http://www.w3.org/2000/svg">';
    
    // Background
    $svg .= '<rect width="100%" height="100%" fill="#ffffff"/>';
    
    // Title
    $svg .= '<text x="50%" y="40" text-anchor="middle" font-family="Arial" font-size="24" fill="#243B55">Award Statistics</text>';
    
    // Bars
    $x = $padding;
    foreach ($data as $row) {
        if (!isset($row[$labelField]) || !isset($row[$valueField])) {
            continue; // Skip invalid data
        }
        
        $barHeight = $row[$valueField] * $scale;
        $y = $height - $padding - $barHeight;
        
        $svg .= '<rect x="' . $x . '" y="' . $y . '" width="' . $barWidth . '" height="' . $barHeight . '" fill="#4A90E2"/>';
        
        // Add label with text wrapping for long names
        $label = htmlspecialchars($row[$labelField]);
        if (strlen($label) > 15) {
            $label = substr($label, 0, 12) . '...';
        }
        $svg .= '<text x="' . ($x + $barWidth/2) . '" y="' . ($height - $padding + 20) . '" text-anchor="middle" font-family="Arial" font-size="12" fill="#243B55">' . $label . '</text>';
        
        // Add value above bar
        $svg .= '<text x="' . ($x + $barWidth/2) . '" y="' . ($y - 5) . '" text-anchor="middle" font-family="Arial" font-size="12" fill="#243B55">' . $row[$valueField] . '</text>';
        
        $x += $barWidth + $barSpacing;
    }
    
    $svg .= '</svg>';
    
    // Clear any previous output
    ob_clean();
    
    // Set headers
    header('Content-Type: image/svg+xml');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    
    // Output SVG
    echo $svg;
    
    // End output buffering
    ob_end_flush();
    exit;
}

// Handle exports
if (isset($_GET['export']) && isset($_GET['format'])) {
    // Start output buffering
    ob_start();
    
    $format = $_GET['format'];
    $type = $_GET['export'];
    
    switch ($type) {
        case 'yearly':
            $data = $yearlyStats;
            $filename = 'yearly_statistics';
            break;
        case 'categories':
            $data = $categoryStats;
            $filename = 'category_statistics';
            break;
        case 'actors':
            $data = $topActors;
            $filename = 'top_actors';
            break;
        case 'productions':
            $data = $topProductions;
            $filename = 'top_productions';
            break;
        default:
            exit('Invalid export type');
    }
    
    // Clear any previous output
    ob_clean();
    
    switch ($format) {
        case 'csv':
            generateCSV($data, $filename . '.csv');
            break;
        case 'webp':
            generateWebP($data, $filename . '.webp');
            break;
        case 'svg':
            generateSVG($data, $filename . '.svg');
            break;
        default:
            exit('Invalid format');
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Statistics - Actor Awards Visualizer</title>
    <link rel="stylesheet" href="../assets/css/navbar.css">
    <link rel="stylesheet" href="../assets/css/footer.css">
    <link rel="stylesheet" href="../assets/css/stats.css">
</head>
<body>
    <?php include '../includes/navbar.php'; ?>

    <main class="main-content">
        <div class="container">
            <header class="page-header">
                <h1>Award Statistics</h1>
                <p class="page-subtitle">Comprehensive analysis of award trends, categories, and top performers</p>
            </header>

            <div class="stats-grid">
                <!-- Yearly Trends Section -->
                <section class="stats-section yearly-section">
                    <div class="section-header">
                        <div class="section-title">
                            <h2>Award Trends Over Time</h2>
                            <p class="section-description">Track nomination and win patterns across years</p>
                        </div>
                        <div class="export-wrapper">
                            <button class="export-button" id="exportYearlyButton">
                                <i class="fas fa-download"></i>
                                Export As...
                            </button>
                            <div class="export-dropdown" id="exportYearlyDropdown">
                                <a href="?export=yearly&format=csv" class="export-option">
                                    <i class="fas fa-file-csv"></i>
                                    Export as CSV
                                </a>
                                <a href="?export=yearly&format=webp" class="export-option">
                                    <i class="fas fa-file-image"></i>
                                    Export as WebP
                                </a>
                                <a href="?export=yearly&format=svg" class="export-option">
                                    <i class="fas fa-file-code"></i>
                                    Export as SVG
                                </a>
                            </div>
                        </div>
                    </div>
                    
                    <div class="chart-and-table">
                        <div class="chart-wrapper">
                            <div class="line-chart">
                                <?php
                                $maxNominations = max(array_column($yearlyStats, 'total_nominations'));
                                $years = array_column($yearlyStats, 'year');
                                $nominations = array_column($yearlyStats, 'total_nominations');
                                $wins = array_column($yearlyStats, 'total_wins');
                                
                                for ($i = 0; $i < count($years); $i++) {
                                    $x = ($i / (count($years) - 1)) * 100;
                                    $y = ($nominations[$i] / $maxNominations) * 100;
                                    echo "<div class='point' style='left: {$x}%; bottom: {$y}%;' title='{$years[$i]}: {$nominations[$i]} nominations'></div>";
                                }
                                ?>
                            </div>
                        </div>
                        
                        <div class="table-wrapper">
                            <div class="table-container">
                                <table class="stats-table">
                                    <thead>
                                        <tr>
                                            <th>Year</th>
                                            <th>Nominations</th>
                                            <th>Wins</th>
                                            <th>Win Rate</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($yearlyStats as $stat): ?>
                                            <tr>
                                                <td data-label="Year"><?php echo htmlspecialchars($stat['year']); ?></td>
                                                <td data-label="Nominations"><?php echo $stat['total_nominations']; ?></td>
                                                <td data-label="Wins"><?php echo $stat['total_wins']; ?></td>
                                                <td data-label="Win Rate"><?php echo round(($stat['total_wins'] / $stat['total_nominations']) * 100, 1); ?>%</td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </section>

                <!-- Category Analysis Section -->
                <section class="stats-section category-section">
                    <div class="section-header">
                        <div class="section-title">
                            <h2>Category Distribution</h2>
                            <p class="section-description">Analysis of nominations and wins by award category</p>
                        </div>
                        <div class="export-wrapper">
                            <button class="export-button" id="exportCategoryButton">
                                <i class="fas fa-download"></i>
                                Export As...
                            </button>
                            <div class="export-dropdown" id="exportCategoryDropdown">
                                <a href="?export=categories&format=csv" class="export-option">
                                    <i class="fas fa-file-csv"></i>
                                    Export as CSV
                                </a>
                                <a href="?export=categories&format=webp" class="export-option">
                                    <i class="fas fa-file-image"></i>
                                    Export as WebP
                                </a>
                                <a href="?export=categories&format=svg" class="export-option">
                                    <i class="fas fa-file-code"></i>
                                    Export as SVG
                                </a>
                            </div>
                        </div>
                    </div>
                    
                    <div class="chart-and-table">
                        <div class="chart-wrapper">
                            <div class="bar-chart">
                                <?php foreach ($categoryStats as $stat): ?>
                                    <div class="bar-container">
                                        <div class="bar" style="height: <?php echo ($stat['total_nominations'] / $categoryStats[0]['total_nominations']) * 100; ?>%">
                                            <div class="bar-value"><?php echo $stat['total_nominations']; ?></div>
                                        </div>
                                        <div class="bar-label"><?php echo htmlspecialchars($stat['category']); ?></div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        
                        <div class="table-wrapper">
                            <div class="table-container">
                                <table class="stats-table">
                                    <thead>
                                        <tr>
                                            <th>Category</th>
                                            <th>Nominations</th>
                                            <th>Wins</th>
                                            <th>Win Rate</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($categoryStats as $stat): ?>
                                            <tr>
                                                <td data-label="Category"><?php echo htmlspecialchars($stat['category']); ?></td>
                                                <td data-label="Nominations"><?php echo $stat['total_nominations']; ?></td>
                                                <td data-label="Wins"><?php echo $stat['total_wins']; ?></td>
                                                <td data-label="Win Rate"><?php echo $stat['win_rate']; ?>%</td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </section>

                <!-- Top Performers Section -->
                <section class="stats-section performers-section">
                    <div class="section-header">
                        <div class="section-title">
                            <h2>Top Performers</h2>
                            <p class="section-description">Leading actors and productions by awards received</p>
                        </div>
                    </div>
                    
                    <div class="performers-grid">
                        <!-- Top Actors -->
                        <div class="performer-card">
                            <div class="card-header">
                                <h3>Top Actors</h3>
                                <a href="?export=actors" class="export-button">Export CSV</a>
                            </div>
                            <div class="performer-list-container">
                                <ul class="performer-list">
                                    <?php foreach ($topActors as $index => $actor): ?>
                                        <li class="performer-item">
                                            <div class="performer-rank"><?php echo $index + 1; ?></div>
                                            <div class="performer-image-container">
                                                <?php if ($actor['profile_path']): ?>
                                                    <img src="<?php echo "https://image.tmdb.org/t/p/w92" . htmlspecialchars($actor['profile_path']); ?>" 
                                                         alt="<?php echo htmlspecialchars($actor['full_name']); ?>"
                                                         class="performer-image">
                                                <?php else: ?>
                                                    <div class="performer-image performer-placeholder"></div>
                                                <?php endif; ?>
                                            </div>
                                            <div class="performer-info">
                                                <div class="performer-name"><?php echo htmlspecialchars($actor['full_name']); ?></div>
                                                <div class="performer-stats">
                                                    <span class="wins"><?php echo $actor['total_wins']; ?> wins</span>
                                                    <span class="separator">•</span>
                                                    <span class="nominations"><?php echo $actor['total_nominations']; ?> nominations</span>
                                                </div>
                                            </div>
                                        </li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                        </div>

                        <!-- Top Productions -->
                        <div class="performer-card">
                            <div class="card-header">
                                <h3>Top Productions</h3>
                                <a href="?export=productions" class="export-button">Export CSV</a>
                            </div>
                            <div class="performer-list-container">
                                <ul class="performer-list">
                                    <?php foreach ($topProductions as $index => $production): ?>
                                        <li class="performer-item">
                                            <div class="performer-rank"><?php echo $index + 1; ?></div>
                                            <div class="performer-image-container">
                                                <?php if ($production['poster_path']): ?>
                                                    <img src="<?php echo "https://image.tmdb.org/t/p/w92" . htmlspecialchars($production['poster_path']); ?>" 
                                                         alt="<?php echo htmlspecialchars($production['title']); ?>"
                                                         class="performer-image">
                                                <?php else: ?>
                                                    <div class="performer-image performer-placeholder"></div>
                                                <?php endif; ?>
                                            </div>
                                            <div class="performer-info">
                                                <div class="performer-name"><?php echo htmlspecialchars($production['title']); ?></div>
                                                <div class="performer-stats">
                                                    <span class="wins"><?php echo $production['total_wins']; ?> wins</span>
                                                    <span class="separator">•</span>
                                                    <span class="nominations"><?php echo $production['total_nominations']; ?> nominations</span>
                                                </div>
                                            </div>
                                        </li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                        </div>
                    </div>
                </section>
            </div>
        </div>
    </main>

    <?php include '../includes/footer.php'; ?>

    <script>
    // Export dropdown functionality
    function setupExportDropdown(buttonId, dropdownId) {
        const button = document.getElementById(buttonId);
        const dropdown = document.getElementById(dropdownId);
        const overlay = document.createElement('div');
        overlay.className = 'export-overlay';
        document.body.appendChild(overlay);

        function toggleDropdown() {
            dropdown.classList.toggle('active');
            overlay.classList.toggle('active');
        }

        button.addEventListener('click', (e) => {
            e.stopPropagation();
            toggleDropdown();
        });

        overlay.addEventListener('click', () => {
            toggleDropdown();
        });

        // Close dropdown when clicking outside
        document.addEventListener('click', (e) => {
            if (!button.contains(e.target) && !dropdown.contains(e.target)) {
                dropdown.classList.remove('active');
                overlay.classList.remove('active');
            }
        });
    }

    // Initialize export dropdowns
    setupExportDropdown('exportYearlyButton', 'exportYearlyDropdown');
    setupExportDropdown('exportCategoryButton', 'exportCategoryDropdown');
    </script>
</body>
</html>
