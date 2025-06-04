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

// Handle CSV export
if (isset($_GET['export'])) {
    switch ($_GET['export']) {
        case 'yearly':
            generateCSV($yearlyStats, 'yearly_statistics.csv');
            break;
        case 'categories':
            generateCSV($categoryStats, 'category_statistics.csv');
            break;
        case 'actors':
            generateCSV($topActors, 'top_actors.csv');
            break;
        case 'productions':
            generateCSV($topProductions, 'top_productions.csv');
            break;
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

    <div class="container">
        <h1 class="text-center">Award Statistics</h1>

        <!-- Yearly Trends -->
        <section class="stats-section">
            <div class="chart-container">
                <div class="chart-title">
                    <h2>Award Trends Over Time</h2>
                    <a href="?export=yearly" class="export-button">Export CSV</a>
                </div>
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
                <div class="stats-table">
                    <table>
                        <thead>
                            <tr>
                                <th>Year</th>
                                <th>Total Nominations</th>
                                <th>Total Wins</th>
                                <th>Win Rate</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($yearlyStats as $stat): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($stat['year']); ?></td>
                                    <td><?php echo $stat['total_nominations']; ?></td>
                                    <td><?php echo $stat['total_wins']; ?></td>
                                    <td><?php echo round(($stat['total_wins'] / $stat['total_nominations']) * 100, 1); ?>%</td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </section>

        <!-- Category Analysis -->
        <section class="stats-section">
            <div class="chart-container">
                <div class="chart-title">
                    <h2>Category Distribution</h2>
                    <a href="?export=categories" class="export-button">Export CSV</a>
                </div>
                <div class="bar-chart">
                    <?php foreach ($categoryStats as $stat): ?>
                        <div class="bar" style="height: <?php echo ($stat['total_nominations'] / $categoryStats[0]['total_nominations']) * 100; ?>%">
                            <div class="bar-label"><?php echo htmlspecialchars($stat['category']); ?></div>
                        </div>
                    <?php endforeach; ?>
                </div>
                <div class="stats-table">
                    <table>
                        <thead>
                            <tr>
                                <th>Category</th>
                                <th>Total Nominations</th>
                                <th>Total Wins</th>
                                <th>Win Rate</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($categoryStats as $stat): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($stat['category']); ?></td>
                                    <td><?php echo $stat['total_nominations']; ?></td>
                                    <td><?php echo $stat['total_wins']; ?></td>
                                    <td><?php echo $stat['win_rate']; ?>%</td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </section>

        <!-- Top Performers -->
        <section class="stats-section">
            <div class="top-performers">
                <!-- Top Actors -->
                <div class="performer-card">
                    <div class="chart-title">
                        <h3>Top Actors</h3>
                        <a href="?export=actors" class="export-button">Export CSV</a>
                    </div>
                    <ul class="performer-list">
                        <?php foreach ($topActors as $actor): ?>
                            <li class="performer-item">
                                <?php if ($actor['profile_path']): ?>
                                    <img src="<?php echo "https://image.tmdb.org/t/p/w92" . htmlspecialchars($actor['profile_path']); ?>" 
                                         alt="<?php echo htmlspecialchars($actor['full_name']); ?>"
                                         class="performer-image">
                                <?php else: ?>
                                    <div class="performer-image" style="background: #eee;"></div>
                                <?php endif; ?>
                                <div class="performer-info">
                                    <div class="performer-name"><?php echo htmlspecialchars($actor['full_name']); ?></div>
                                    <div class="performer-stats">
                                        <?php echo $actor['total_wins']; ?> wins / <?php echo $actor['total_nominations']; ?> nominations
                                    </div>
                                </div>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>

                <!-- Top Productions -->
                <div class="performer-card">
                    <div class="chart-title">
                        <h3>Top Productions</h3>
                        <a href="?export=productions" class="export-button">Export CSV</a>
                    </div>
                    <ul class="performer-list">
                        <?php foreach ($topProductions as $production): ?>
                            <li class="performer-item">
                                <?php if ($production['poster_path']): ?>
                                    <img src="<?php echo "https://image.tmdb.org/t/p/w92" . htmlspecialchars($production['poster_path']); ?>" 
                                         alt="<?php echo htmlspecialchars($production['title']); ?>"
                                         class="performer-image">
                                <?php else: ?>
                                    <div class="performer-image" style="background: #eee;"></div>
                                <?php endif; ?>
                                <div class="performer-info">
                                    <div class="performer-name"><?php echo htmlspecialchars($production['title']); ?></div>
                                    <div class="performer-stats">
                                        <?php echo $production['total_wins']; ?> wins / <?php echo $production['total_nominations']; ?> nominations
                                    </div>
                                </div>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            </div>
        </section>
    </div>

    <?php include '../includes/footer.php'; ?>
</body>
</html> 