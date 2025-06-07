<?php
// enabled error reporting for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../../src/config/config.php';
require_once __DIR__ . '/../../src/includes/db.php';
require_once __DIR__ . '/../../src/includes/functions.php';

use Intervention\Image\ImageManagerStatic as Image;
use SVG\SVG;
use SVG\Nodes\Shapes\SVGRect;
use SVG\Nodes\Shapes\SVGLine;
use SVG\Nodes\Texts\SVGText;

// initialize database connection
$db = getDbConnection();

if (isset($_GET['export']) && isset($_GET['format'])) {
    ob_start();
    $exportType = $_GET['export'];
    $format = $_GET['format'];
    
    try {
        switch ($exportType) {
            case 'yearly':
                $data = $db->query("
                    SELECT 
                        year,
                        COUNT(*) as total_nominations,
                        SUM(CASE WHEN won = 'True' THEN 1 ELSE 0 END) as total_wins,
                        ROUND(SUM(CASE WHEN won = 'True' THEN 1 ELSE 0 END) * 100.0 / COUNT(*), 1) as win_rate
                    FROM awards 
                    GROUP BY year 
                    ORDER BY year DESC
                ")->fetchAll(PDO::FETCH_ASSOC);
                $filename = "yearly_statistics";
                break;
                
            case 'category':
                $data = $db->query("
                    SELECT 
                        category,
                        COUNT(*) as total_nominations,
                        SUM(CASE WHEN won = 'True' THEN 1 ELSE 0 END) as total_wins,
                        ROUND(SUM(CASE WHEN won = 'True' THEN 1 ELSE 0 END) * 100.0 / COUNT(*), 1) as win_rate
                    FROM awards 
                    GROUP BY category 
                    ORDER BY total_nominations DESC
                ")->fetchAll(PDO::FETCH_ASSOC);
                $filename = "category_statistics";
                break;
                
            case 'performers':
                $data = $db->query("
                    SELECT 
                        a.full_name,
                        COUNT(*) as total_nominations,
                        SUM(CASE WHEN won = 'True' THEN 1 ELSE 0 END) as total_wins,
                        ROUND(SUM(CASE WHEN won = 'True' THEN 1 ELSE 0 END) * 100.0 / COUNT(*), 1) as win_rate
                    FROM awards a
                    WHERE a.full_name IS NOT NULL AND a.full_name <> ''
                    GROUP BY a.full_name
                    ORDER BY total_wins DESC, total_nominations DESC
                    LIMIT 10
                ")->fetchAll(PDO::FETCH_ASSOC);
                $filename = "top_performers";
                break;
                
            default:
                throw new Exception('Invalid export type');
        }

        if (empty($data)) {
            throw new Exception('No data available for export');
        }
        
        switch ($format) {
            case 'csv':
                while (ob_get_level()) {
                    ob_end_clean();
                }
                
                if (headers_sent($file, $line)) {
                    throw new Exception("Headers already sent in $file on line $line");
                }
                
                // headers for CSV download
                header('Content-Type: text/csv; charset=UTF-8');
                header('Content-Disposition: attachment; filename="' . $filename . '.csv"');
                header('Cache-Control: no-cache, no-store, must-revalidate');
                header('Pragma: no-cache');
                header('Expires: 0');
                header('X-Content-Type-Options: nosniff');
                header('Content-Transfer-Encoding: binary');
                
                // Open output stream
                $output = fopen('php://output', 'w');
                if ($output === false) {
                    throw new Exception('Failed to open output stream');
                }
                
                // Add UTF-8 BOM for Excel compatibility
                fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
                
                // Write headers
                fputcsv($output, array_keys($data[0]));
                
                // Write data
                foreach ($data as $row) {
                    fputcsv($output, $row);
                }
                
                // Close the output stream
                fclose($output);
                exit;
                
            case 'webp':
                while (ob_get_level()) {
                    ob_end_clean();
                }
                
                if (headers_sent($file, $line)) {
                    throw new Exception("Headers already sent in $file on line $line");
                }
                
                // headers for WebP download
                header('Content-Type: image/webp');
                header('Content-Disposition: attachment; filename="' . $filename . '.webp"');
                header('Cache-Control: no-cache, no-store, must-revalidate');
                header('Pragma: no-cache');
                header('Expires: 0');
                header('X-Content-Type-Options: nosniff');
                header('Content-Transfer-Encoding: binary');
                
                // Generate WebP image
                $width = 800;
                $height = 600;
                $padding = 40;
                
                // Create a new image
                $image = Image::canvas($width, $height, '#ffffff');
                
                // Add title
                $image->text(ucfirst($exportType) . ' Statistics', $padding, $padding, function($font) {
                    $font->file(__DIR__ . '/../../public/assets/fonts/Arial.ttf');
                    $font->size(24);
                    $font->color('#243B55');
                });
                
                // Calculate max values for scaling
                $maxValue = 0;
                foreach ($data as $row) {
                    $maxValue = max($maxValue, $row['total_nominations'], $row['total_wins']);
                }
                
                // Draw chart
                $chartStartY = $padding + 50;
                $chartHeight = $height - $chartStartY - $padding;
                $barWidth = ($width - (2 * $padding)) / (count($data) * 3);
                
                foreach ($data as $index => $row) {
                    $x = $padding + ($index * $barWidth * 3);
                    
                    // Draw nominations bar
                    $nomHeight = ($row['total_nominations'] / $maxValue) * $chartHeight;
                    $image->rectangle(
                        $x,
                        $height - $padding - $nomHeight,
                        $x + $barWidth,
                        $height - $padding,
                        function($draw) {
                            $draw->background('#4a90e2');
                        }
                    );
                    
                    // Draw wins bar
                    $winsHeight = ($row['total_wins'] / $maxValue) * $chartHeight;
                    $image->rectangle(
                        $x + $barWidth + 5,
                        $height - $padding - $winsHeight,
                        $x + (2 * $barWidth),
                        $height - $padding,
                        function($draw) {
                            $draw->background('#357abd');
                        }
                    );
                    
                    // Add labels
                    $label = isset($row['year']) ? $row['year'] : 
                            (isset($row['category']) ? substr($row['category'], 0, 10) : 
                            substr($row['full_name'], 0, 10));
                    
                    $image->text($label, $x, $height - $padding + 15, function($font) {
                        $font->file(__DIR__ . '/../../public/assets/fonts/Arial.ttf');
                        $font->size(10);
                        $font->color('#666666');
                        $font->angle(45);
                    });
                }
                
                echo $image->encode('webp', 90);
                exit;
                
            case 'svg':
                while (ob_get_level()) {
                    ob_end_clean();
                }
                
                if (headers_sent($file, $line)) {
                    throw new Exception("Headers already sent in $file on line $line");
                }
                // headers for SVG download
                header('Content-Type: image/svg+xml; charset=UTF-8');
                header('Content-Disposition: attachment; filename="' . $filename . '.svg"');
                header('Cache-Control: no-cache, no-store, must-revalidate');
                header('Pragma: no-cache');
                header('Expires: 0');
                header('X-Content-Type-Options: nosniff');
                header('Content-Transfer-Encoding: binary');
                
                // Generate SVG
                $width = 800;
                $height = 600;
                $padding = 40;
                
                // Create SVG document
                $image = new SVG($width, $height);
                $doc = $image->getDocument();
                
                // Add white background
                $doc->addChild(
                    (new SVGRect(0, 0, $width, $height))
                        ->setStyle('fill', '#ffffff')
                );
                
                // Add title
                $doc->addChild(
                    (new SVGText(ucfirst($exportType) . ' Statistics', $padding, $padding))
                        ->setStyle('font-family', 'Arial')
                        ->setStyle('font-size', '24px')
                        ->setStyle('fill', '#243B55')
                );
                
                // Calculate max values for scaling
                $maxValue = 0;
                foreach ($data as $row) {
                    $maxValue = max($maxValue, $row['total_nominations'], $row['total_wins']);
                }
                
                // Draw chart
                $chartStartY = $padding + 50;
                $chartHeight = $height - $chartStartY - $padding;
                $barWidth = ($width - (2 * $padding)) / (count($data) * 3);
                
                // Draw grid lines
                for ($i = 0; $i <= 5; $i++) {
                    $y = $height - $padding - ($i * $chartHeight / 5);
                    $doc->addChild(
                        (new SVGLine($padding, $y, $width - $padding, $y))
                            ->setStyle('stroke', '#eeeeee')
                            ->setStyle('stroke-width', '1')
                    );
                    
                    $value = round($maxValue * $i / 5);
                    $doc->addChild(
                        (new SVGText($value, $padding - 5, $y))
                            ->setStyle('font-family', 'Arial')
                            ->setStyle('font-size', '10px')
                            ->setStyle('fill', '#666666')
                            ->setStyle('text-anchor', 'end')
                    );
                }
                
                foreach ($data as $index => $row) {
                    $x = $padding + ($index * $barWidth * 3);
                    
                    // Draw nominations bar
                    $nomHeight = ($row['total_nominations'] / $maxValue) * $chartHeight;
                    $doc->addChild(
                        (new SVGRect(
                            $x,
                            $height - $padding - $nomHeight,
                            $barWidth,
                            $nomHeight
                        ))
                        ->setStyle('fill', '#4a90e2')
                    );
                    
                    // Draw wins bar
                    $winsHeight = ($row['total_wins'] / $maxValue) * $chartHeight;
                    $doc->addChild(
                        (new SVGRect(
                            $x + $barWidth + 5,
                            $height - $padding - $winsHeight,
                            $barWidth,
                            $winsHeight
                        ))
                        ->setStyle('fill', '#357abd')
                    );
                    
                    // Add labels
                    $label = isset($row['year']) ? $row['year'] : 
                            (isset($row['category']) ? substr($row['category'], 0, 10) : 
                            substr($row['full_name'], 0, 10));
                    
                    $doc->addChild(
                        (new SVGText($label, $x, $height - $padding + 15))
                            ->setStyle('font-family', 'Arial')
                            ->setStyle('font-size', '10px')
                            ->setStyle('fill', '#666666')
                            ->setStyle('transform', 'rotate(45, ' . $x . ', ' . ($height - $padding + 15) . ')')
                    );
                }
                
                echo $image;
                exit;
                
            default:
                throw new Exception('Invalid format type');
        }
    } catch (Exception $e) {
        // Log the error
        error_log('Export error: ' . $e->getMessage());
        
        // Return error response
        header('HTTP/1.1 500 Internal Server Error');
        echo 'Error during export: ' . $e->getMessage();
        exit;
    }
}

// Get yearly statistics
$yearlyStats = $db->query("
    SELECT 
        year,
        COUNT(*) as total_nominations,
        SUM(CASE WHEN won = 'True' THEN 1 ELSE 0 END) as total_wins,
        ROUND(SUM(CASE WHEN won = 'True' THEN 1 ELSE 0 END) * 100.0 / COUNT(*), 1) as win_rate
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
$actorsQuery = "SELECT 
    a.full_name as name,
    ac.profile_path as image_url,
    COUNT(*) as nominations,
    SUM(CASE WHEN a.won = 'True' THEN 1 ELSE 0 END) as wins
FROM awards a
LEFT JOIN actors ac ON a.tmdb_actor_id = ac.tmdb_id
WHERE a.full_name IS NOT NULL AND a.full_name <> ''
GROUP BY a.full_name, ac.profile_path
ORDER BY wins DESC, nominations DESC
LIMIT 10";

// Get top productions
$productionsQuery = "SELECT 
    p.title,
    p.poster_path as image_url,
    COUNT(*) as nominations,
    SUM(CASE WHEN a.won = 'True' THEN 1 ELSE 0 END) as wins
FROM awards a
JOIN productions p ON a.tmdb_show_id = p.tmdb_id
GROUP BY p.title, p.poster_path
ORDER BY wins DESC, nominations DESC
LIMIT 10";
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Statistics - Actor Awards Visualizer</title>
    <link rel="stylesheet" href="../assets/css/index.css">
    <link rel="stylesheet" href="../assets/css/navbar.css">
    <link rel="stylesheet" href="../assets/css/footer.css">
    <link rel="stylesheet" href="../assets/css/stats.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
    <?php include '../../src/includes/navbar.php'; ?>

    <div class="page-header">
        <div class="container">
            <h1>Statistics</h1>
            <p class="page-description">Explore performance statistics and trends across awards seasons</p>
        </div>
    </div>

    <div class="container">
        <div class="stats-grid">
            <div class="charts-section">
                <div class="stats-section">
                    <div class="section-header">
                        <div class="section-title">
                            <h2>Awards by Year</h2>
                            <p class="section-description">Distribution of awards across years</p>
                        </div>
                        <div class="export-wrapper">
                            <button class="export-button">
                                <i class="fas fa-download"></i>
                                Export
                            </button>
                            <div class="export-dropdown">
                                <a href="/pages/stats.php?export=yearly&format=csv" class="export-option" download>
                                    <i class="fas fa-file-csv"></i>
                                    CSV
                                </a>
                                <a href="/pages/stats.php?export=yearly&format=webp" class="export-option" download>
                                    <i class="fas fa-image"></i>
                                    WebP
                                </a>
                                <a href="/pages/stats.php?export=yearly&format=svg" class="export-option" download>
                                    <i class="fas fa-bezier-curve"></i>
                                    SVG
                                </a>
                            </div>
                        </div>
                    </div>
                    <div class="chart-and-table">
                        <div class="chart-wrapper">
                            <canvas id="awardsChart"></canvas>
                        </div>
                        <div class="table-wrapper collapsed">
                            <table class="stats-table">
                                <thead>
                                    <tr>
                                        <th>Year</th>
                                        <th>Total Awards</th>
                                        <th>Nominations</th>
                                        <th>Win Rate</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($yearlyStats as $stat): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($stat['year']); ?></td>
                                        <td><?php echo $stat['total_wins']; ?></td>
                                        <td><?php echo $stat['total_nominations']; ?></td>
                                        <td><?php echo $stat['win_rate']; ?>%</td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        <div class="show-more-container">
                            <button class="show-more-btn">
                                Show More
                                <i class="fas fa-chevron-down"></i>
                            </button>
                        </div>
                    </div>
                </div>

                <div class="stats-section">
                    <div class="section-header">
                        <div class="section-title">
                            <h2>Category Distribution</h2>
                            <p class="section-description">Awards by category type</p>
                        </div>
                        <div class="export-wrapper">
                            <button class="export-button">
                                <i class="fas fa-download"></i>
                                Export
                            </button>
                            <div class="export-dropdown">
                                <a href="/pages/stats.php?export=category&format=csv" class="export-option" download>
                                    <i class="fas fa-file-csv"></i>
                                    CSV
                                </a>
                                <a href="/pages/stats.php?export=category&format=webp" class="export-option" download>
                                    <i class="fas fa-image"></i>
                                    WebP
                                </a>
                                <a href="/pages/stats.php?export=category&format=svg" class="export-option" download>
                                    <i class="fas fa-bezier-curve"></i>
                                    SVG
                                </a>
                            </div>
                        </div>
                    </div>
                    <div class="chart-and-table">
                        <div class="chart-wrapper">
                            <canvas id="categoriesChart"></canvas>
                        </div>
                        <div class="table-wrapper collapsed">
                            <table class="stats-table">
                                <thead>
                                    <tr>
                                        <th>Category</th>
                                        <th>Total Awards</th>
                                        <th>Nominations</th>
                                        <th>Win Rate</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($categoryStats as $stat): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($stat['category']); ?></td>
                                        <td><?php echo $stat['total_wins']; ?></td>
                                        <td><?php echo $stat['total_nominations']; ?></td>
                                        <td><?php echo $stat['win_rate']; ?>%</td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        <div class="show-more-container">
                            <button class="show-more-btn">
                                Show More
                                <i class="fas fa-chevron-down"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="sidebar-section">
                <div class="performers-section">
                    <div class="section-header">
                        <div class="section-title">
                            <h2>Top Performers</h2>
                            <p class="section-description">Most awarded actors and productions</p>
                        </div>
                        <div class="export-wrapper">
                            <button class="export-button">
                                <i class="fas fa-download"></i>
                                Export
                            </button>
                            <div class="export-dropdown">
                                <a href="/pages/stats.php?export=performers&format=csv" class="export-option" download>
                                    <i class="fas fa-file-csv"></i>
                                    CSV
                                </a>
                                <a href="/pages/stats.php?export=performers&format=webp" class="export-option" download>
                                    <i class="fas fa-image"></i>
                                    WebP
                                </a>
                                <a href="/pages/stats.php?export=performers&format=svg" class="export-option" download>
                                    <i class="fas fa-bezier-curve"></i>
                                    SVG
                                </a>
                            </div>
                        </div>
                    </div>
                    <div class="performers-container">
                        <div class="performer-list">
                            <?php
                            $topActors = $db->query($actorsQuery)->fetchAll(PDO::FETCH_ASSOC);
                            foreach ($topActors as $index => $actor):
                            ?>
                            <div class="performer-item">
                                <span class="performer-rank"><?php echo $index + 1; ?></span>
                                <div class="performer-image">
                                    <?php if ($actor['image_url']): ?>
                                        <img src="https://image.tmdb.org/t/p/w185<?php echo htmlspecialchars($actor['image_url']); ?>" alt="<?php echo htmlspecialchars($actor['name']); ?>">
                                    <?php else: ?>
                                        <div class="no-image">No Image</div>
                                    <?php endif; ?>
                                </div>
                                <div class="performer-info">
                                    <h4 class="performer-name"><?php echo htmlspecialchars($actor['name']); ?></h4>
                                    <div class="performer-stats">
                                        <span class="wins"><?php echo $actor['wins']; ?> wins</span>
                                        <span class="separator">•</span>
                                        <span class="nominations"><?php echo $actor['nominations']; ?> nominations</span>
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>

                <div class="productions-section">
                    <div class="section-header">
                        <div class="section-title">
                            <h2>Top Productions</h2>
                            <p class="section-description">Most awarded TV shows</p>
                        </div>
                    </div>
                    <div class="productions-container">
                        <div class="performer-list">
                            <?php
                            $topProductions = $db->query($productionsQuery)->fetchAll(PDO::FETCH_ASSOC);
                            foreach ($topProductions as $index => $production):
                            ?>
                            <div class="performer-item">
                                <span class="performer-rank"><?php echo $index + 1; ?></span>
                                <div class="performer-image">
                                    <?php if ($production['image_url']): ?>
                                        <img src="https://image.tmdb.org/t/p/w185<?php echo htmlspecialchars($production['image_url']); ?>" alt="<?php echo htmlspecialchars($production['title']); ?>">
                                    <?php else: ?>
                                        <div class="no-image">No Image</div>
                                    <?php endif; ?>
                                </div>
                                <div class="performer-info">
                                    <h4 class="performer-name"><?php echo htmlspecialchars($production['title']); ?></h4>
                                    <div class="performer-stats">
                                        <span class="wins"><?php echo $production['wins']; ?> wins</span>
                                        <span class="separator">•</span>
                                        <span class="nominations"><?php echo $production['nominations']; ?> nominations</span>
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
    // Export dropdown functionality
    document.querySelectorAll('.export-button').forEach(button => {
        button.addEventListener('click', (e) => {
            e.stopPropagation();
            const dropdown = button.nextElementSibling;
            dropdown.classList.toggle('active');
        });
    });

    document.addEventListener('click', (e) => {
        if (!e.target.closest('.export-wrapper')) {
            document.querySelectorAll('.export-dropdown').forEach(dropdown => {
                dropdown.classList.remove('active');
            });
        }
    });

    document.addEventListener('DOMContentLoaded', function() {
        // Initialize show more buttons
        document.querySelectorAll('.show-more-btn').forEach(button => {
            button.addEventListener('click', function() {
                const wrapper = this.closest('.chart-and-table').querySelector('.table-wrapper');
                const isCollapsed = wrapper.classList.contains('collapsed');
                
                if (isCollapsed) {
                    wrapper.style.maxHeight = wrapper.scrollHeight + 'px';
                    wrapper.classList.remove('collapsed');
                    this.innerHTML = 'Show Less <i class="fas fa-chevron-up"></i>';
                    this.classList.add('expanded');
                } else {
                    wrapper.style.maxHeight = '400px';
                    wrapper.classList.add('collapsed');
                    this.innerHTML = 'Show More <i class="fas fa-chevron-down"></i>';
                    this.classList.remove('expanded');
                }
            });
        });
    });
    </script>

    <script src="../assets/js/stats.js"></script>

    <?php include '../../src/includes/footer.php'; ?>
</body>
</html>
