// Get the chart canvases
const awardsChartCtx = document.getElementById('awardsChart').getContext('2d');
const categoriesChartCtx = document.getElementById('categoriesChart').getContext('2d');

// Get the data from the tables
const yearlyData = Array.from(document.querySelectorAll('.stats-table tbody tr')).filter(row => {
    // Only get rows from the yearly stats table (check if first cell is a year)
    return !isNaN(parseInt(row.cells[0].textContent));
}).map(row => ({
    year: row.cells[0].textContent,
    awards: parseInt(row.cells[1].textContent),
    nominations: parseInt(row.cells[2].textContent)
}));

const categoryData = Array.from(document.querySelectorAll('.stats-table tbody tr')).filter(row => {
    // Only get rows from the category stats table (check if first cell is not a year)
    return isNaN(parseInt(row.cells[0].textContent));
}).map(row => ({
    category: row.cells[0].textContent,
    awards: parseInt(row.cells[1].textContent),
    nominations: parseInt(row.cells[2].textContent)
}));

// Create the awards by year chart
new Chart(awardsChartCtx, {
    type: 'line',
    data: {
        labels: yearlyData.map(d => d.year).reverse(), // Reverse to show oldest to newest
        datasets: [
            {
                label: 'Awards',
                data: yearlyData.map(d => d.awards).reverse(),
                borderColor: '#4A90E2',
                backgroundColor: 'rgba(74, 144, 226, 0.1)',
                borderWidth: 2,
                fill: true
            },
            {
                label: 'Nominations',
                data: yearlyData.map(d => d.nominations).reverse(),
                borderColor: '#82C4E8',
                backgroundColor: 'rgba(130, 196, 232, 0.1)',
                borderWidth: 2,
                fill: true
            }
        ]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: {
                position: 'top'
            },
            title: {
                display: false
            }
        },
        scales: {
            y: {
                beginAtZero: true,
                ticks: {
                    precision: 0
                }
            }
        }
    }
});

// Create the category distribution chart
new Chart(categoriesChartCtx, {
    type: 'bar',
    data: {
        labels: categoryData.map(d => d.category),
        datasets: [
            {
                label: 'Awards',
                data: categoryData.map(d => d.awards),
                backgroundColor: 'rgba(74, 144, 226, 0.8)',
                borderColor: '#4A90E2',
                borderWidth: 1
            },
            {
                label: 'Nominations',
                data: categoryData.map(d => d.nominations),
                backgroundColor: 'rgba(130, 196, 232, 0.8)',
                borderColor: '#82C4E8',
                borderWidth: 1
            }
        ]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: {
                position: 'top'
            },
            title: {
                display: false
            }
        },
        scales: {
            y: {
                beginAtZero: true,
                ticks: {
                    precision: 0
                }
            },
            x: {
                ticks: {
                    maxRotation: 45,
                    minRotation: 45
                }
            }
        }
    }
}); 