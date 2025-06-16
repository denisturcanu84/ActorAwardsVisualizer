<?php
// enabled error reporting for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../../src/config/config.php';
require_once __DIR__ . '/../../src/includes/db.php';
require_once __DIR__ . '/../../src/includes/functions.php';
require_once __DIR__ . '/../../src/includes/export_handler.php';
require_once __DIR__ . '/../../src/includes/stats_service.php';
require_once __DIR__ . '/../../src/includes/stats_components.php';

// initialize database connection
$db = getDbConnection();

// Handle export requests
if (isset($_GET['export']) && isset($_GET['format'])) {
    $exportHandler = new ExportHandler($db);
    $exportHandler->handleExport($_GET['export'], $_GET['format']);
}

// Initialize services
$statsService = new StatsService($db);

// Get all statistics data
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
<body>
    <?php include '../../src/includes/navbar.php'; ?>

    <!-- page header -->
    <div class="page-header">
        <div class="container">
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
                renderStatsSection(
                    'Awards by Year',
                    'Distribution of awards across years',
                    'awardsChart',
                    'yearly',
                    $yearlyStats,
                    ['Year', 'Total Awards', 'Nominations', 'Win Rate']
                );
                
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