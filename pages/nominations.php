<?php
require_once '../includes/config.php';
require_once '../includes/db.php';
require_once '../includes/functions.php';

// Get all nominations with actor and production details
$query = "SELECT a.*, ac.full_name, ac.profile_path, p.title as production_title, p.poster_path 
          FROM awards a 
          LEFT JOIN actors ac ON a.tmdb_actor_id = ac.tmdb_id 
          LEFT JOIN productions p ON a.tmdb_show_id = p.tmdb_id 
          ORDER BY a.year DESC, a.category";

$nominations = $db->query($query)->fetchAll(PDO::FETCH_ASSOC);

// Get unique years for filter
$years = array_unique(array_column($nominations, 'year'));
rsort($years);

// Get unique categories for filter
$categories = array_unique(array_column($nominations, 'category'));
sort($categories);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Actor Awards - Nominations</title>
    <link rel="stylesheet" href="../assets/css/index.css">
    <link rel="stylesheet" href="../assets/css/navbar.css">
    <link rel="stylesheet" href="../assets/css/footer.css">
    <link rel="stylesheet" href="../assets/css/nominations.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
    <?php include '../includes/navbar.php'; ?>

    <div class="container mt-4">
        <h1 class="text-center mb-4">Award Nominations</h1>

        <!-- Filters Section -->
        <div class="filters-section mb-4">
            <div class="row">
                <div class="col-md-3">
                    <select class="form-select" id="yearFilter">
                        <option value="">All Years</option>
                        <?php foreach ($years as $year): ?>
                            <option value="<?php echo htmlspecialchars($year); ?>"><?php echo htmlspecialchars($year); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <select class="form-select" id="categoryFilter">
                        <option value="">All Categories</option>
                        <?php foreach ($categories as $category): ?>
                            <option value="<?php echo htmlspecialchars($category); ?>"><?php echo htmlspecialchars($category); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <select class="form-select" id="resultFilter">
                        <option value="">All Results</option>
                        <option value="Won">Won</option>
                        <option value="Nominated">Nominated</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <input type="text" class="form-control" id="searchInput" placeholder="Search actor or show...">
                </div>
            </div>
        </div>

        <!-- Statistics Section -->
        <div class="statistics-section mb-4">
            <div class="row">
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-body">
                            <h5 class="card-title">Nominations by Category</h5>
                            <canvas id="categoryChart"></canvas>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-body">
                            <h5 class="card-title">Nominations by Year</h5>
                            <canvas id="yearChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Nominations List -->
        <div class="nominations-list">
            <?php if (empty($nominations)): ?>
                <div class="no-results">
                    <p>No nominations found.</p>
                </div>
            <?php else: ?>
                <?php foreach ($nominations as $nomination): ?>
                    <div class="card mb-3 nomination-card" 
                         data-year="<?php echo htmlspecialchars($nomination['year']); ?>"
                         data-category="<?php echo htmlspecialchars($nomination['category']); ?>"
                         data-result="<?php echo htmlspecialchars($nomination['won']); ?>">
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-2">
                                    <?php if ($nomination['profile_path']): ?>
                                        <img src="https://image.tmdb.org/t/p/w200<?php echo htmlspecialchars($nomination['profile_path']); ?>" 
                                             class="img-fluid rounded" alt="<?php echo htmlspecialchars($nomination['full_name']); ?>">
                                    <?php endif; ?>
                                </div>
                                <div class="col-md-10">
                                    <h5 class="card-title"><?php echo htmlspecialchars($nomination['full_name']); ?></h5>
                                    <p class="card-text">
                                        <strong>Year:</strong> <?php echo htmlspecialchars($nomination['year']); ?><br>
                                        <strong>Category:</strong> <?php echo htmlspecialchars($nomination['category']); ?><br>
                                        <strong>Show:</strong> <?php echo htmlspecialchars($nomination['show']); ?><br>
                                        <strong>Result:</strong> 
                                        <span class="badge <?php echo $nomination['won'] === 'Won' ? 'bg-success' : 'bg-secondary'; ?>">
                                            <?php echo htmlspecialchars($nomination['won']); ?>
                                        </span>
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <?php include '../includes/footer.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Initialize charts and filters
        document.addEventListener('DOMContentLoaded', function() {
            // Filter functionality
            const filters = ['yearFilter', 'categoryFilter', 'resultFilter', 'searchInput'];
            filters.forEach(filterId => {
                document.getElementById(filterId).addEventListener('change', filterNominations);
            });
            document.getElementById('searchInput').addEventListener('input', filterNominations);

            // Initialize charts
            initializeCharts();
        });

        function filterNominations() {
            const year = document.getElementById('yearFilter').value;
            const category = document.getElementById('categoryFilter').value;
            const result = document.getElementById('resultFilter').value;
            const search = document.getElementById('searchInput').value.toLowerCase();

            document.querySelectorAll('.nomination-card').forEach(card => {
                const cardYear = card.dataset.year;
                const cardCategory = card.dataset.category;
                const cardResult = card.dataset.result;
                const cardText = card.textContent.toLowerCase();

                const yearMatch = !year || cardYear === year;
                const categoryMatch = !category || cardCategory === category;
                const resultMatch = !result || cardResult === result;
                const searchMatch = !search || cardText.includes(search);

                card.style.display = yearMatch && categoryMatch && resultMatch && searchMatch ? 'block' : 'none';
            });
        }

        function initializeCharts() {
            // Category Chart
            const categoryCtx = document.getElementById('categoryChart').getContext('2d');
            new Chart(categoryCtx, {
                type: 'pie',
                data: {
                    labels: <?php echo json_encode($categories); ?>,
                    datasets: [{
                        data: <?php 
                            $categoryCounts = array_count_values(array_column($nominations, 'category'));
                            echo json_encode(array_values($categoryCounts));
                        ?>,
                        backgroundColor: [
                            '#4A90E2',
                            '#357ABD',
                            '#243B55',
                            '#E0F7FF',
                            '#2c3e50'
                        ]
                    }]
                },
                options: {
                    responsive: true,
                    plugins: {
                        legend: {
                            position: 'bottom'
                        }
                    }
                }
            });

            // Year Chart
            const yearCtx = document.getElementById('yearChart').getContext('2d');
            new Chart(yearCtx, {
                type: 'bar',
                data: {
                    labels: <?php echo json_encode($years); ?>,
                    datasets: [{
                        label: 'Nominations per Year',
                        data: <?php 
                            $yearCounts = array_count_values(array_column($nominations, 'year'));
                            echo json_encode(array_values($yearCounts));
                        ?>,
                        backgroundColor: '#4A90E2'
                    }]
                },
                options: {
                    responsive: true,
                    scales: {
                        y: {
                            beginAtZero: true
                        }
                    },
                    plugins: {
                        legend: {
                            display: false
                        }
                    }
                }
            });
        }
    </script>
</body>
</html>
