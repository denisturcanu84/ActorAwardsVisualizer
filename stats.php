<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Actor Awards Statistics</title>
    <link rel="stylesheet" href="assets/css/stats.css">
</head>
<body>
    <div class="container">
        <h1>Actor Awards Statistics</h1>
        
        <!-- Award Trends Section -->
        <section class="stats-section">
            <div class="chart-container">
                <div class="chart-title">
                    <h2>Award Trends Over Time</h2>
                    <a href="export.php?type=trends" class="export-button">Export CSV</a>
                </div>
                <div class="line-chart">
                    <!-- Line chart will be rendered here -->
                </div>
            </div>
        </section>

        <!-- Category Analysis Section -->
        <section class="stats-section">
            <div class="chart-container">
                <div class="chart-title">
                    <h2>Category Analysis</h2>
                    <a href="export.php?type=categories" class="export-button">Export CSV</a>
                </div>
                <div class="bar-chart">
                    <!-- Bar chart will be rendered here -->
                </div>
            </div>
        </section>

        <!-- Top Performers Section -->
        <section class="stats-section">
            <div class="chart-container">
                <div class="chart-title">
                    <h2>Top Performers</h2>
                    <a href="export.php?type=performers" class="export-button">Export CSV</a>
                </div>
                <div class="top-performers">
                    <!-- Top performers will be rendered here -->
                </div>
            </div>
        </section>
    </div>

    <script>
        // JavaScript code for rendering charts and handling data
        // This will be implemented in a separate file
    </script>
</body>
</html> 