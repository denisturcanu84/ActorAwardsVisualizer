// Get the chart canvases
const awardsChartCtx = document.getElementById('awardsChart').getContext('2d');
const categoriesChartCtx = document.getElementById('categoriesChart').getContext('2d');

// Get the data from the tables
const yearlyData = Array.from(document.querySelectorAll('.stats-table tbody tr')).filter(row => {
    // Only get rows from the yearly stats table (check if first cell is a year)
    return !isNaN(parseInt(row.cells[0].textContent));
}).map(row => ({
    year: row.cells[0].textContent,
    awards: parseInt(row.cells[1].textContent), // Total Wins (column 1)
    nominations: parseInt(row.cells[2].textContent) // Total Nominations (column 2)
}));

const categoryData = Array.from(document.querySelectorAll('.stats-table tbody tr')).filter(row => {
    // Only get rows from the category stats table (check if first cell is not a year)
    return isNaN(parseInt(row.cells[0].textContent));
}).map(row => ({
    category: row.cells[0].textContent,
    awards: parseInt(row.cells[1].textContent), // Total Wins (column 1, should be smaller)
    nominations: parseInt(row.cells[2].textContent) // Total Nominations (column 2, should be larger)
}));

// Create the awards by year chart
new Chart(awardsChartCtx, {
    type: 'line',
    data: {
        labels: yearlyData.map(d => d.year), // No reverse - show 1990 to 2020
        datasets: [
            {
                label: 'Wins',
                data: yearlyData.map(d => d.awards), // No reverse
                borderColor: '#4A90E2',
                backgroundColor: 'rgba(74, 144, 226, 0.1)',
                borderWidth: 3,
                fill: true,
                tension: 0.1,
                pointBackgroundColor: '#4A90E2',
                pointBorderColor: '#fff',
                pointBorderWidth: 2,
                pointRadius: 4,
                pointHoverRadius: 6
            },
            {
                label: 'Total Nominations',
                data: yearlyData.map(d => d.nominations), // No reverse
                borderColor: '#82C4E8',
                backgroundColor: 'rgba(130, 196, 232, 0.1)',
                borderWidth: 3,
                fill: true,
                tension: 0.1,
                pointBackgroundColor: '#82C4E8',
                pointBorderColor: '#fff',
                pointBorderWidth: 2,
                pointRadius: 4,
                pointHoverRadius: 6
            }
        ]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        layout: {
            padding: 0
        },
        plugins: {
            legend: {
                position: 'top',
                labels: {
                    usePointStyle: true,
                    padding: 15,
                    font: {
                        size: 12
                    }
                }
            },
            title: {
                display: false
            }
        },
        scales: {
            y: {
                beginAtZero: true,
                ticks: {
                    precision: 0,
                    font: {
                        size: 11
                    }
                },
                grid: {
                    color: 'rgba(0,0,0,0.1)'
                }
            },
            x: {
                position: 'bottom',
                ticks: {
                    maxRotation: 90,
                    minRotation: 90,
                    font: {
                        size: 10
                    },
                    padding: -66,
                    maxTicksLimit: 31,
                    callback: function(value, index, values) {
                        // Show every year but smaller font if many data points
                        return this.getLabelForValue(value);
                    }
                },
                grid: {
                    color: 'rgba(0,0,0,0.05)',
                    offset: false,
                    drawOnChartArea: true,
                    drawTicks: false,
                    lineWidth: 0
                },
                offset: false,
                border: {
                    display: false
                },
                afterFit: function(scale) {
                    scale.height = scale.height - 10;
                }
            }
        },
        interaction: {
            mode: 'index',
            intersect: false
        }
    }
});

// Filter category data to only include categories with 5+ nominations
const filteredCategoryData = categoryData.filter(d => d.nominations >= 5);

// Create the category distribution chart
new Chart(categoriesChartCtx, {
    type: 'bar',
    data: {
        labels: filteredCategoryData.map(d => d.category),
        datasets: [
            {
                label: 'Wins',
                data: filteredCategoryData.map(d => d.awards),
                backgroundColor: 'rgba(74, 144, 226, 0.8)',
                borderColor: '#4A90E2',
                borderWidth: 2
            },
            {
                label: 'Total Nominations',
                data: filteredCategoryData.map(d => d.nominations),
                backgroundColor: 'rgba(130, 196, 232, 0.8)',
                borderColor: '#82C4E8',
                borderWidth: 2
            }
        ]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        layout: {
            padding: 0
        },
        plugins: {
            legend: {
                position: 'top',
                labels: {
                    usePointStyle: true,
                    padding: 15,
                    font: {
                        size: 12
                    }
                }
            },
            title: {
                display: false
            }
        },
        scales: {
            y: {
                beginAtZero: true,
                ticks: {
                    precision: 0,
                    font: {
                        size: 11
                    }
                },
                grid: {
                    color: 'rgba(0,0,0,0.1)'
                }
            },
            x: {
                display: false,
                ticks: {
                    display: false
                },
                grid: {
                    display: false
                },
                border: {
                    display: false
                }
            }
        },
        interaction: {
            mode: 'index',
            intersect: false
        }
    }
}); 