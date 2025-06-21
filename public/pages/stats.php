<?php
require_once __DIR__ . '/../../src/bootstrap.php';

use ActorAwards\Middleware\AuthenticationMiddleware;
AuthenticationMiddleware::requireLogin();

// debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

use ActorAwards\Services\DatabaseService;
use ActorAwards\Services\StatsService;
use ActorAwards\Services\TmdbService;
use ActorAwards\Repositories\ActorRepository;
use ActorAwards\Repositories\ProductionRepository;
use ActorAwards\Exports\ExportHandler;

// This is for the stat card rendering functions.
require_once __DIR__ . '/../../src/Views/Components/StatsComponents.php';

$db = DatabaseService::getConnection();

// Handle requests to export data as CSV/JSON.
if (isset($_GET['export']) && isset($_GET['format'])) {
    $exportHandler = new ExportHandler($db);
    $exportHandler->handleExport($_GET['export'], $_GET['format']);
}

// Set up all the services needed for the stats page.
// StatsService needs the other services to do its job (like fetching images).
$tmdbService = new TmdbService(TMDB_API_KEY);
$actorRepository = new ActorRepository($db);
$productionRepository = new ProductionRepository($db);
$statsService = new StatsService($db, $tmdbService, $actorRepository, $productionRepository);

// Get all the stats data for the page.
$yearlyStats = $statsService->getYearlyStats();
$categoryStats = $statsService->getCategoryStats();
$topActors = $statsService->getTopActors();
$topProductions = $statsService->getTopProductions();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Statistics - Actor Awards Visualizer</title>
    <link rel="stylesheet" href="../assets/css/common.css">
    <link rel="stylesheet" href="../assets/css/index.css">
    <link rel="stylesheet" href="../assets/css/navbar.css">
    <link rel="stylesheet" href="../assets/css/footer.css">
    <link rel="stylesheet" href="../assets/css/stats.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body class="stats-page">
    <?php include '../../src/Views/Components/Navbar.php'; ?>

    <!-- page header -->
    <div class="page-header">
        <div class="container_header">
            <h1>Statistics</h1>
            <p class="page-description">
                Dive into comprehensive statistics and insights about SAG awards. 
                Explore trends, analyze performance data, and discover patterns across 
                different categories and years.
            </p>
        </div>
    </div>

    <div class="container">
        <div class="stats-grid">
            <div class="charts-section">
                <?php 
                // Prepare and render chart for yearly stats:
                // - Uses Chart.js for visualization
                // - Data formatted for bar/line charts
                // - Includes table view toggle
                renderStatsSection(
                    'Awards by Year',
                    'Distribution of awards across years',
                    'awardsChart',
                    'yearly',
                    $yearlyStats,
                    ['Year', 'Total Awards', 'Nominations', 'Win Rate']
                );
                
                // Prepare and render category breakdown:
                // - Groups awards by category type
                // - Shows win rates per category
                // - Uses pie/bar chart visualization
                renderStatsSection(
                    'Category Distribution',
                    'Awards by category type',
                    'categoriesChart',
                    'category',
                    $categoryStats,
                    ['Category', 'Total Awards', 'Nominations', 'Win Rate']
                );
                ?>
            </div>
            
            <div class="sidebar-section">
                <?php 
                renderPerformersList(
                    'Top Performers',
                    'Most awarded actors and productions',
                    'performers',
                    $topActors
                );
                
                renderPerformersList(
                    'Top Productions',
                    'Most awarded TV shows',
                    null,
                    $topProductions
                );
                ?>
            </div>
        </div>
    </div>

    <script>
    // JavaScript integration points:
    // 1. Export dropdown UI functionality
    // 2. Chart initialization in stats.js
    // 3. Table expand/collapse toggles
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
                    this.textContent = 'Show Less';
                    this.classList.add('expanded');
                } else {
                    wrapper.style.maxHeight = '400px';
                    wrapper.classList.add('collapsed');
                    this.textContent = 'Show More';
                    this.classList.remove('expanded');
                }
            });
        });
    });
    </script>

    <!-- Main chart initialization script -->
    <!-- Handles: -->
    <!-- - Chart.js configuration -->
    <!-- - Data binding from PHP variables -->
    <!-- - Interactive chart behaviors -->
    <script src="../assets/js/stats-chart.js"></script>

    <?php include '../../src/Views/Components/Footer.php'; ?>
</body>
</html>