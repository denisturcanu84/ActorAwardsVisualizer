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
        
        <!-- Main Visualization Section -->
        <section class="stats-section">
            <div class="chart-container">
                <div class="chart-content">
                    <div class="chart-title">
                        <h2>Award Statistics</h2>
                        <div class="export-options">
                            <a href="export.php?type=stats&format=csv" class="export-button">
                                <i class="fas fa-file-csv"></i> CSV
                            </a>
                            <a href="export.php?type=stats&format=webp" class="export-button">
                                <i class="fas fa-file-image"></i> WebP
                            </a>
                            <a href="export.php?type=stats&format=svg" class="export-button">
                                <i class="fas fa-file-code"></i> SVG
                            </a>
                        </div>
                    </div>
                    <div class="visualization-container">
                        <svg id="mainChart" viewBox="0 0 400 400" preserveAspectRatio="xMidYMid meet">
                            <!-- SVG content will be dynamically updated -->
                        </svg>
                    </div>
                </div>
                <div class="visualization-selector">
                    <h3>Visualization Type</h3>
                    <div class="visualization-buttons">
                        <button class="viz-button active" data-viz="pie">
                            <i class="fas fa-chart-pie"></i> Pie Chart
                        </button>
                        <button class="viz-button" data-viz="bar">
                            <i class="fas fa-chart-bar"></i> Bar Chart
                        </button>
                        <button class="viz-button" data-viz="line">
                            <i class="fas fa-chart-line"></i> Line Chart
                        </button>
                    </div>
                </div>
            </div>
        </section>
    </div>

    <script>
        // Sample data - replace with your actual data
        const chartData = {
            labels: ['Drama', 'Comedy', 'Action', 'Romance', 'Thriller'],
            values: [30, 25, 15, 20, 10],
            colors: ['#4A90E2', '#50E3C2', '#F5A623', '#D0021B', '#9013FE']
        };

        // SVG dimensions
        const width = 400;
        const height = 400;
        const centerX = width / 2;
        const centerY = height / 2;
        const radius = Math.min(width, height) / 2 - 40;

        // Function to create pie chart
        function createPieChart() {
            const svg = document.getElementById('mainChart');
            svg.innerHTML = ''; // Clear existing content

            let total = chartData.values.reduce((a, b) => a + b, 0);
            let currentAngle = 0;

            // Create pie segments
            chartData.values.forEach((value, index) => {
                const percentage = value / total;
                const angle = percentage * 360;
                
                // Create path for pie segment
                const path = document.createElementNS('http://www.w3.org/2000/svg', 'path');
                const startX = centerX + radius * Math.cos(currentAngle * Math.PI / 180);
                const startY = centerY + radius * Math.sin(currentAngle * Math.PI / 180);
                const endX = centerX + radius * Math.cos((currentAngle + angle) * Math.PI / 180);
                const endY = centerY + radius * Math.sin((currentAngle + angle) * Math.PI / 180);
                
                const largeArcFlag = angle > 180 ? 1 : 0;
                
                const pathData = [
                    `M ${centerX},${centerY}`,
                    `L ${startX},${startY}`,
                    `A ${radius},${radius} 0 ${largeArcFlag},1 ${endX},${endY}`,
                    'Z'
                ].join(' ');

                path.setAttribute('d', pathData);
                path.setAttribute('fill', chartData.colors[index]);
                path.setAttribute('stroke', 'white');
                path.setAttribute('stroke-width', '2');
                
                // Add hover effect
                path.addEventListener('mouseover', () => {
                    path.style.opacity = '0.8';
                });
                path.addEventListener('mouseout', () => {
                    path.style.opacity = '1';
                });

                svg.appendChild(path);

                // Add label
                const labelAngle = currentAngle + angle / 2;
                const labelRadius = radius + 20;
                const labelX = centerX + labelRadius * Math.cos(labelAngle * Math.PI / 180);
                const labelY = centerY + labelRadius * Math.sin(labelAngle * Math.PI / 180);

                const text = document.createElementNS('http://www.w3.org/2000/svg', 'text');
                text.setAttribute('x', labelX);
                text.setAttribute('y', labelY);
                text.setAttribute('text-anchor', 'middle');
                text.setAttribute('dominant-baseline', 'middle');
                text.setAttribute('fill', '#333');
                text.setAttribute('font-size', '12');
                text.textContent = `${chartData.labels[index]} (${Math.round(percentage * 100)}%)`;
                
                svg.appendChild(text);

                currentAngle += angle;
            });
        }

        // Function to create bar chart
        function createBarChart() {
            const svg = document.getElementById('mainChart');
            svg.innerHTML = '';

            const barWidth = width / chartData.values.length - 20;
            const maxValue = Math.max(...chartData.values);
            const scale = (height - 60) / maxValue;

            // Create bars
            chartData.values.forEach((value, index) => {
                const barHeight = value * scale;
                const x = index * (barWidth + 20) + 10;
                const y = height - barHeight - 30;

                // Create bar
                const rect = document.createElementNS('http://www.w3.org/2000/svg', 'rect');
                rect.setAttribute('x', x);
                rect.setAttribute('y', y);
                rect.setAttribute('width', barWidth);
                rect.setAttribute('height', barHeight);
                rect.setAttribute('fill', chartData.colors[index]);
                rect.setAttribute('rx', '4');
                
                // Add hover effect
                rect.addEventListener('mouseover', () => {
                    rect.style.opacity = '0.8';
                });
                rect.addEventListener('mouseout', () => {
                    rect.style.opacity = '1';
                });

                svg.appendChild(rect);

                // Add label
                const text = document.createElementNS('http://www.w3.org/2000/svg', 'text');
                text.setAttribute('x', x + barWidth / 2);
                text.setAttribute('y', height - 10);
                text.setAttribute('text-anchor', 'middle');
                text.setAttribute('fill', '#333');
                text.setAttribute('font-size', '12');
                text.textContent = chartData.labels[index];
                
                svg.appendChild(text);
            });
        }

        // Function to create line chart
        function createLineChart() {
            const svg = document.getElementById('mainChart');
            svg.innerHTML = '';

            const maxValue = Math.max(...chartData.values);
            const scale = (height - 60) / maxValue;
            const pointSpacing = width / (chartData.values.length - 1);

            // Create line
            const points = chartData.values.map((value, index) => {
                const x = index * pointSpacing;
                const y = height - (value * scale) - 30;
                return `${x},${y}`;
            }).join(' ');

            const polyline = document.createElementNS('http://www.w3.org/2000/svg', 'polyline');
            polyline.setAttribute('points', points);
            polyline.setAttribute('fill', 'none');
            polyline.setAttribute('stroke', '#4A90E2');
            polyline.setAttribute('stroke-width', '3');
            
            svg.appendChild(polyline);

            // Create points and labels
            chartData.values.forEach((value, index) => {
                const x = index * pointSpacing;
                const y = height - (value * scale) - 30;

                // Create point
                const circle = document.createElementNS('http://www.w3.org/2000/svg', 'circle');
                circle.setAttribute('cx', x);
                circle.setAttribute('cy', y);
                circle.setAttribute('r', '6');
                circle.setAttribute('fill', '#4A90E2');
                circle.setAttribute('stroke', 'white');
                circle.setAttribute('stroke-width', '2');
                
                svg.appendChild(circle);

                // Add label
                const text = document.createElementNS('http://www.w3.org/2000/svg', 'text');
                text.setAttribute('x', x);
                text.setAttribute('y', height - 10);
                text.setAttribute('text-anchor', 'middle');
                text.setAttribute('fill', '#333');
                text.setAttribute('font-size', '12');
                text.textContent = chartData.labels[index];
                
                svg.appendChild(text);
            });
        }

        // Initialize with pie chart
        createPieChart();

        // Add event listeners for visualization buttons
        document.querySelectorAll('.visualization-buttons').forEach(buttonGroup => {
            buttonGroup.addEventListener('click', (e) => {
                if (e.target.classList.contains('viz-button')) {
                    // Remove active class from all buttons
                    buttonGroup.querySelectorAll('.viz-button').forEach(btn => {
                        btn.classList.remove('active');
                    });
                    // Add active class to clicked button
                    e.target.classList.add('active');
                    
                    // Get the visualization type
                    const vizType = e.target.dataset.viz;
                    
                    // Update visualization based on type
                    switch(vizType) {
                        case 'pie':
                            createPieChart();
                            break;
                        case 'bar':
                            createBarChart();
                            break;
                        case 'line':
                            createLineChart();
                            break;
                    }
                }
            });
        });
    </script>
</body>
</html> 