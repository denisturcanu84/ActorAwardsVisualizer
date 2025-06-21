// This script powers the two charts on the stats page.
// It pulls data from the HTML tables and uses Chart.js to make 'em pretty.

// Grab the canvas elements for our charts.
const awardsChartCtx = document.getElementById('awardsChart').getContext('2d');
const categoriesChartCtx = document.getElementById('categoriesChart').getContext('2d');

// Scrape the data from the stats tables on the page.
// First, the yearly stats. The filter just makes sure we're only getting rows with years.
const yearlyData = Array.from(document.querySelectorAll('.stats-table tbody tr')).filter(row => {
    return !isNaN(parseInt(row.cells[0].textContent));
}).map(row => ({
    year: row.cells[0].textContent,
    awards: parseInt(row.cells[1].textContent),
    nominations: parseInt(row.cells[2].textContent)
}));

// And do the same for the category stats.
const categoryData = Array.from(document.querySelectorAll('.stats-table tbody tr')).filter(row => {
    return isNaN(parseInt(row.cells[0].textContent));
}).map(row => ({
    category: row.cells[0].textContent,
    awards: parseInt(row.cells[1].textContent),
    nominations: parseInt(row.cells[2].textContent)
}));

// --- Chart 1: Yearly Awards & Nominations (Line Chart) ---
// This one shows the trend of wins vs. nominations over the years.
new Chart(awardsChartCtx, {
    type: 'line',
    data: {
        labels: yearlyData.map(d => d.year), // Show 1990 to 2020 chronologically
        datasets: [
            {
                label: 'Wins',
                data: yearlyData.map(d => d.awards),
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
                data: yearlyData.map(d => d.nominations),
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
                    // This just makes sure all the year labels fit without looking like a mess.
                    callback: function(value, index, values) {
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

// The category chart can get pretty crowded, so I'm filtering out any category with less than 5 nominations.
// This keeps it readable.
const filteredCategoryData = categoryData.filter(d => d.nominations >= 5);

// --- Chart 2: Wins vs. Nominations by Category (Bar Chart) ---
// This one compares the wins and nominations for the top categories.
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
                // Hiding the x-axis labels for the category chart because they can get very long and cluttered.
                // The tooltip on hover is enough to see the category name.
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