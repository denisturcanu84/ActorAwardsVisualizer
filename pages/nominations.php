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

// Calculate statistics for the charts
$categoryCounts = array_count_values(array_column($nominations, 'category'));
$yearCounts = array_count_values(array_column($nominations, 'year'));
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
</head>
<body>
    <?php include '../includes/navbar.php'; ?>

    <div class="container">
        <h1 class="text-center">Award Nominations</h1>

        <!-- Filters Section -->
        <div class="filters-section">
            <form class="filters-form" id="filtersForm">
                <div class="filters-grid">
                    <div class="filter-group">
                        <label for="yearFilter">Year:</label>
                        <select id="yearFilter" name="year">
                            <option value="">All Years</option>
                            <?php foreach ($years as $year): ?>
                                <option value="<?php echo htmlspecialchars($year); ?>"><?php echo htmlspecialchars($year); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="filter-group">
                        <label for="categoryFilter">Category:</label>
                        <select id="categoryFilter" name="category">
                            <option value="">All Categories</option>
                            <?php foreach ($categories as $category): ?>
                                <option value="<?php echo htmlspecialchars($category); ?>"><?php echo htmlspecialchars($category); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="filter-group">
                        <label for="resultFilter">Result:</label>
                        <select id="resultFilter" name="result">
                            <option value="">All Results</option>
                            <option value="Won">Won</option>
                            <option value="Nominated">Nominated</option>
                        </select>
                    </div>
                    <div class="filter-group">
                        <label for="searchInput">Search:</label>
                        <input type="text" id="searchInput" name="search" placeholder="Search actor or show...">
                    </div>
                </div>
            </form>
        </div>

        <!-- Statistics Section -->
        <div class="statistics-section">
            <div class="stats-grid">
                <!-- Category Statistics -->
                <div class="stats-card">
                    <h2>Nominations by Category</h2>
                    <div class="stats-table">
                        <table>
                            <thead>
                                <tr>
                                    <th>Category</th>
                                    <th>Count</th>
                                    <th>Visual</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($categoryCounts as $category => $count): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($category); ?></td>
                                        <td><?php echo $count; ?></td>
                                        <td>
                                            <div class="bar-container">
                                                <div class="bar" style="width: <?php echo ($count / max($categoryCounts)) * 100; ?>%"></div>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Year Statistics -->
                <div class="stats-card">
                    <h2>Nominations by Year</h2>
                    <div class="stats-table">
                        <table>
                            <thead>
                                <tr>
                                    <th>Year</th>
                                    <th>Count</th>
                                    <th>Visual</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($yearCounts as $year => $count): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($year); ?></td>
                                        <td><?php echo $count; ?></td>
                                        <td>
                                            <div class="bar-container">
                                                <div class="bar" style="width: <?php echo ($count / max($yearCounts)) * 100; ?>%"></div>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
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
                    <div class="nomination-card" 
                         data-year="<?php echo htmlspecialchars($nomination['year']); ?>"
                         data-category="<?php echo htmlspecialchars($nomination['category']); ?>"
                         data-result="<?php echo htmlspecialchars($nomination['won']); ?>">
                        <div class="nomination-content">
                            <div class="nomination-image">
                                <?php if ($nomination['profile_path']): ?>
                                    <img src="https://image.tmdb.org/t/p/w200<?php echo htmlspecialchars($nomination['profile_path']); ?>" 
                                         alt="<?php echo htmlspecialchars($nomination['full_name']); ?>">
                                <?php endif; ?>
                            </div>
                            <div class="nomination-details">
                                <h3><?php echo htmlspecialchars($nomination['full_name']); ?></h3>
                                <div class="nomination-info">
                                    <p><strong>Year:</strong> <?php echo htmlspecialchars($nomination['year']); ?></p>
                                    <p><strong>Category:</strong> <?php echo htmlspecialchars($nomination['category']); ?></p>
                                    <p><strong>Show:</strong> <?php echo htmlspecialchars($nomination['show']); ?></p>
                                    <p><strong>Result:</strong> 
                                        <span class="result-badge <?php echo $nomination['won'] === 'Won' ? 'won' : 'nominated'; ?>">
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

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const filters = ['yearFilter', 'categoryFilter', 'resultFilter', 'searchInput'];
            
            filters.forEach(filterId => {
                const element = document.getElementById(filterId);
                if (element) {
                    element.addEventListener('change', filterNominations);
                }
            });

            const searchInput = document.getElementById('searchInput');
            if (searchInput) {
                searchInput.addEventListener('input', filterNominations);
            }
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
    </script>
</body>
</html>
