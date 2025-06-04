<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Actor Awards Statistics</title>
    <link rel="stylesheet" href="assets/css/stats.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <div class="container">
        <h1>Actor Awards Statistics</h1>
        
        <!-- Award Trends Section -->
        <section class="stats-section">
            <div class="chart-container">
                <div class="chart-content">
                    <div class="chart-title">
                        <h2>Award Trends Over Time</h2>
                        <div class="export-options">
                            <a href="export.php?type=trends&format=csv" class="export-button">
                                <i class="fas fa-file-csv"></i> CSV
                            </a>
                            <a href="export.php?type=trends&format=webp" class="export-button">
                                <i class="fas fa-file-image"></i> WebP
                            </a>
                            <a href="export.php?type=trends&format=svg" class="export-button">
                                <i class="fas fa-file-code"></i> SVG
                            </a>
                        </div>
                    </div>
                    <div class="line-chart">
                        <!-- Line chart will be rendered here -->
                    </div>
                </div>
                <div class="visualization-selector">
                    <h3>Visualization Type</h3>
                    <div class="visualization-buttons">
                        <button class="viz-button active" data-viz="line">Line Chart</button>
                        <button class="viz-button" data-viz="bar">Bar Chart</button>
                        <button class="viz-button" data-viz="pie">Pie Chart</button>
                    </div>
                </div>
            </div>
        </section>

        <!-- Category Analysis Section -->
        <section class="stats-section">
            <div class="chart-container">
                <div class="chart-content">
                    <div class="chart-title">
                        <h2>Category Analysis</h2>
                        <div class="export-options">
                            <a href="export.php?type=categories&format=csv" class="export-button">
                                <i class="fas fa-file-csv"></i> CSV
                            </a>
                            <a href="export.php?type=categories&format=webp" class="export-button">
                                <i class="fas fa-file-image"></i> WebP
                            </a>
                            <a href="export.php?type=categories&format=svg" class="export-button">
                                <i class="fas fa-file-code"></i> SVG
                            </a>
                        </div>
                    </div>
                    <div class="bar-chart">
                        <!-- Bar chart will be rendered here -->
                    </div>
                </div>
                <div class="visualization-selector">
                    <h3>Visualization Type</h3>
                    <div class="visualization-buttons">
                        <button class="viz-button" data-viz="line">Line Chart</button>
                        <button class="viz-button active" data-viz="bar">Bar Chart</button>
                        <button class="viz-button" data-viz="pie">Pie Chart</button>
                    </div>
                </div>
            </div>
        </section>

        <!-- Top Performers Section -->
        <section class="stats-section">
            <div class="chart-container">
                <div class="chart-content">
                    <div class="chart-title">
                        <h2>Top Performers</h2>
                        <div class="export-options">
                            <a href="export.php?type=performers&format=csv" class="export-button">
                                <i class="fas fa-file-csv"></i> CSV
                            </a>
                            <a href="export.php?type=performers&format=webp" class="export-button">
                                <i class="fas fa-file-image"></i> WebP
                            </a>
                            <a href="export.php?type=performers&format=svg" class="export-button">
                                <i class="fas fa-file-code"></i> SVG
                            </a>
                        </div>
                    </div>
                    <div class="pie-chart">
                        <!-- Pie chart will be rendered here -->
                    </div>
                </div>
                <div class="visualization-selector">
                    <h3>Visualization Type</h3>
                    <div class="visualization-buttons">
                        <button class="viz-button" data-viz="line">Line Chart</button>
                        <button class="viz-button" data-viz="bar">Bar Chart</button>
                        <button class="viz-button active" data-viz="pie">Pie Chart</button>
                    </div>
                </div>
            </div>
        </section>
    </div>

    <script>
        // Add event listeners for visualization buttons
        document.querySelectorAll('.visualization-buttons').forEach(buttonGroup => {
            buttonGroup.addEventListener('click', (e) => {
                if (e.target.classList.contains('viz-button')) {
                    // Remove active class from all buttons in this group
                    buttonGroup.querySelectorAll('.viz-button').forEach(btn => {
                        btn.classList.remove('active');
                    });
                    // Add active class to clicked button
                    e.target.classList.add('active');
                    
                    // Get the chart container
                    const chartContainer = e.target.closest('.chart-container');
                    const chartContent = chartContainer.querySelector('.chart-content');
                    
                    // Update visualization based on selected type
                    const vizType = e.target.dataset.viz;
                    updateVisualization(chartContent, vizType);
                }
            });
        });

        function updateVisualization(container, type) {
            // This function will be implemented to handle the visualization changes
            console.log(`Updating visualization to ${type}`);
        }
    </script>
</body>
</html> 