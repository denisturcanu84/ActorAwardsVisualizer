<?php
require_once '../includes/config.php';
require_once '../includes/db.php';
require_once '../includes/functions.php';

// Get filter values from POST or set defaults
$selectedYear = $_POST['year'] ?? '';
$selectedCategory = $_POST['category'] ?? '';
$selectedResult = $_POST['result'] ?? '';
$searchQuery = $_POST['search'] ?? '';

// Build the query with filters
$query = "SELECT a.*, ac.full_name, ac.profile_path, p.title as production_title, p.poster_path 
          FROM awards a 
          LEFT JOIN actors ac ON a.tmdb_actor_id = ac.tmdb_id 
          LEFT JOIN productions p ON a.tmdb_show_id = p.tmdb_id 
          WHERE 1=1";

$params = [];

if ($selectedYear) {
    $query .= " AND a.year = :year";
    $params[':year'] = $selectedYear;
}

if ($selectedCategory) {
    $query .= " AND a.category = :category";
    $params[':category'] = $selectedCategory;
}

if ($selectedResult) {
    $query .= " AND a.won = :result";
    $params[':result'] = $selectedResult;
}

if ($searchQuery) {
    $query .= " AND (ac.full_name LIKE :search OR a.show LIKE :search)";
    $params[':search'] = "%$searchQuery%";
}

$query .= " ORDER BY a.year DESC, a.category";

// Prepare and execute the query
$stmt = $db->prepare($query);
$stmt->execute($params);
$nominations = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get unique years and categories for filters
$query = "SELECT DISTINCT year FROM awards ORDER BY year DESC";
$years = $db->query($query)->fetchAll(PDO::FETCH_COLUMN);

$query = "SELECT DISTINCT category FROM awards ORDER BY category";
$categories = $db->query($query)->fetchAll(PDO::FETCH_COLUMN);

// Calculate statistics
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
            <form class="filters-form" method="POST">
                <div class="filters-grid">
                    <div class="filter-group">
                        <label for="yearFilter">Year:</label>
                        <select id="yearFilter" name="year">
                            <option value="">All Years</option>
                            <?php foreach ($years as $year): ?>
                                <option value="<?php echo htmlspecialchars($year); ?>" 
                                    <?php echo $selectedYear === $year ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($year); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="filter-group">
                        <label for="categoryFilter">Category:</label>
                        <select id="categoryFilter" name="category">
                            <option value="">All Categories</option>
                            <?php foreach ($categories as $category): ?>
                                <option value="<?php echo htmlspecialchars($category); ?>"
                                    <?php echo $selectedCategory === $category ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($category); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="filter-group">
                        <label for="resultFilter">Result:</label>
                        <select id="resultFilter" name="result">
                            <option value="">All Results</option>
                            <option value="Won" <?php echo $selectedResult === 'Won' ? 'selected' : ''; ?>>Won</option>
                            <option value="Nominated" <?php echo $selectedResult === 'Nominated' ? 'selected' : ''; ?>>Nominated</option>
                        </select>
                    </div>
                    <div class="filter-group">
                        <label for="searchInput">Search:</label>
                        <input type="text" id="searchInput" name="search" 
                               placeholder="Search actor or show..."
                               value="<?php echo htmlspecialchars($searchQuery); ?>">
                    </div>
                </div>
                <div class="filter-buttons">
                    <button type="submit" class="filter-button">Apply Filters</button>
                    <a href="nominations.php" class="reset-button">Reset</a>
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
                    <p>No nominations found matching your criteria.</p>
                </div>
            <?php else: ?>
                <?php foreach ($nominations as $nomination): ?>
                    <div class="nomination-card">
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
</body>
</html>
