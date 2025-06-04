<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/tmdb.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->load();
$api_key = $_ENV['TMDB_API_KEY'] ?? '';

// Initialize database connection
$db = getDbConnection();

// Get filter values from POST
$selectedYear = $_POST['year'] ?? '2020';
$selectedCategory = $_POST['category'] ?? '';
$selectedResult = $_POST['result'] ?? '';
$searchQuery = $_POST['search'] ?? '';

// Convert Won/Nominated to True/False for database query
$resultBoolean = null;
if ($selectedResult === 'Won') {
    $resultBoolean = 'True';
} elseif ($selectedResult === 'Nominated') {
    $resultBoolean = 'False';
}

// Build the query with filters
$query = "SELECT p.*, p.title as production_title, p.poster_path, 
          COUNT(DISTINCT a.id) as award_count,
          COUNT(DISTINCT CASE WHEN a.won = 'True' THEN a.id END) as won_count,
          GROUP_CONCAT(DISTINCT a.category) as categories,
          GROUP_CONCAT(DISTINCT a.year) as years
          FROM productions p 
          LEFT JOIN awards a ON p.tmdb_id = a.tmdb_show_id 
          WHERE 1=1";

$params = [];

if ($selectedYear) {
    $query .= " AND a.year = ?";
    $params[] = $selectedYear;
}

if ($selectedCategory) {
    $query .= " AND a.category = ?";
    $params[] = $selectedCategory;
}

if ($resultBoolean !== null) {
    $query .= " AND a.won = ?";
    $params[] = $resultBoolean;
}

if ($searchQuery) {
    $query .= " AND (p.title LIKE ? OR p.overview LIKE ?)";
    $params[] = "%{$searchQuery}%";
    $params[] = "%{$searchQuery}%";
}

$query .= " GROUP BY p.tmdb_id ORDER BY won_count DESC, award_count DESC";

$stmt = $db->prepare($query);
$stmt->execute($params);
$productions = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get unique years and categories for filters
$query = "SELECT DISTINCT year FROM awards ORDER BY year DESC";
$years = $db->query($query)->fetchAll(PDO::FETCH_COLUMN);

$query = "SELECT DISTINCT category FROM awards ORDER BY category";
$categories = $db->query($query)->fetchAll(PDO::FETCH_COLUMN);

// Calculate statistics
$categoryStats = [];
$yearStats = [];

foreach ($productions as $production) {
    if (!empty($production['categories'])) {
        $prodCategories = explode(',', $production['categories']);
        foreach ($prodCategories as $category) {
            $categoryStats[$category] = ($categoryStats[$category] ?? 0) + 1;
        }
    }
    
    if (!empty($production['years'])) {
        $prodYears = explode(',', $production['years']);
        foreach ($prodYears as $year) {
            $yearStats[$year] = ($yearStats[$year] ?? 0) + 1;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Productions - Actor Awards Visualizer</title>
    <link rel="stylesheet" href="../assets/css/index.css">
    <link rel="stylesheet" href="../assets/css/navbar.css">
    <link rel="stylesheet" href="../assets/css/footer.css">
    <link rel="stylesheet" href="../assets/css/nominations.css">
</head>
<body>
    <?php include '../includes/navbar.php'; ?>

    <div class="container">
        <h1 class="text-center">Award Productions</h1>

        <!-- Filters Section -->
        <div class="filters-section">
            <form class="filters-form" method="POST">
                <div class="filters-grid">
                    <div class="filter-group">
                        <label for="yearFilter">Year:</label>
                        <select id="yearFilter" name="year">
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
                               placeholder="Search production title..."
                               value="<?php echo htmlspecialchars($searchQuery); ?>">
                    </div>
                </div>
                <div class="filter-buttons">
                    <button type="submit" class="filter-button">Apply Filters</button>
                    <a href="productions.php" class="reset-button">Reset</a>
                </div>
            </form>
        </div>

        <!-- Statistics Section -->
        <div class="statistics-section">
            <div class="stats-grid">
                <!-- Category Statistics -->
                <div class="stats-card">
                    <h2>Productions by Category</h2>
                    <div class="stats-table">
                        <table>
                            <thead>
                                <tr>
                                    <th>Category</th>
                                    <th>Count</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($categoryStats as $category => $count): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($category); ?></td>
                                        <td><?php echo $count; ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Year Statistics -->
                <div class="stats-card">
                    <h2>Productions by Year</h2>
                    <div class="stats-table">
                        <table>
                            <thead>
                                <tr>
                                    <th>Year</th>
                                    <th>Count</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($yearStats as $year => $count): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($year); ?></td>
                                        <td><?php echo $count; ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Productions List -->
        <div class="nominations-list">
            <?php if (empty($productions)): ?>
                <div class="no-results">
                    <p>No productions found matching your criteria.</p>
                </div>
            <?php else: ?>
                <?php foreach ($productions as $production): ?>
                    <div class="nomination-card">
                        <div class="nomination-content">
                            <div class="nomination-image">
                                <?php if ($production['poster_path']): ?>
                                    <img src="<?php echo "https://image.tmdb.org/t/p/w500" . htmlspecialchars($production['poster_path']); ?>" 
                                         alt="<?php echo htmlspecialchars($production['production_title']); ?>">
                                <?php else: ?>
                                    <div class="no-image">No Image Available</div>
                                <?php endif; ?>
                            </div>
                            <div class="nomination-details">
                                <h3><?php echo htmlspecialchars($production['production_title']); ?></h3>
                                <div class="nomination-info">
                                    <p><strong>Total Awards:</strong> <?php echo $production['award_count']; ?></p>
                                    <p><strong>Awards Won:</strong> <?php echo $production['won_count']; ?></p>
                                    <?php if (!empty($production['categories'])): ?>
                                        <p><strong>Categories:</strong> <?php echo htmlspecialchars($production['categories']); ?></p>
                                    <?php endif; ?>
                                    <?php if (!empty($production['years'])): ?>
                                        <p><strong>Years:</strong> <?php echo htmlspecialchars($production['years']); ?></p>
                                    <?php endif; ?>
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
