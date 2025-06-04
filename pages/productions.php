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
$selectedYear = $_POST['year'] ?? '';
$selectedCategory = $_POST['category'] ?? '';
$selectedResult = $_POST['result'] ?? '';
$searchQuery = $_POST['search'] ?? '';

// Build the query with filters
$query = "SELECT p.*, p.title as production_title, p.poster_path, 
          COUNT(DISTINCT a.award_id) as award_count,
          COUNT(DISTINCT CASE WHEN a.won = 'True' THEN a.award_id END) as won_count
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

if ($selectedResult !== '') {
    $query .= " AND a.won = ?";
    $params[] = $selectedResult;
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
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Productions - Actor Awards Visualizer</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <?php include __DIR__ . '/../includes/header.php'; ?>
    
    <div class="container">
        <h1>Productions</h1>
        
        <form method="POST" class="filters">
            <div class="filter-group">
                <label for="year">Year:</label>
                <select name="year" id="year">
                    <option value="">All Years</option>
                    <?php for ($year = 2020; $year >= 1990; $year--): ?>
                        <option value="<?php echo $year; ?>" <?php echo $selectedYear == $year ? 'selected' : ''; ?>>
                            <?php echo $year; ?>
                        </option>
                    <?php endfor; ?>
                </select>
            </div>

            <div class="filter-group">
                <label for="category">Category:</label>
                <select name="category" id="category">
                    <option value="">All Categories</option>
                    <?php 
                    $categories = getAwardCategories();
                    foreach ($categories as $category): ?>
                        <option value="<?php echo htmlspecialchars($category); ?>" 
                                <?php echo $selectedCategory == $category ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($category); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="filter-group">
                <label for="result">Result:</label>
                <select name="result" id="result">
                    <option value="">All Results</option>
                    <option value="True" <?php echo $selectedResult == 'True' ? 'selected' : ''; ?>>Won</option>
                    <option value="False" <?php echo $selectedResult == 'False' ? 'selected' : ''; ?>>Nominated</option>
                </select>
            </div>

            <div class="filter-group">
                <label for="search">Search:</label>
                <input type="text" name="search" id="search" value="<?php echo htmlspecialchars($searchQuery); ?>">
            </div>

            <button type="submit" class="btn">Filter</button>
        </form>

        <?php if (empty($productions)): ?>
            <p class="no-results">No productions found matching your criteria.</p>
        <?php else: ?>
            <div class="productions-grid">
                <?php foreach ($productions as $production): ?>
                    <div class="production-card">
                        <a href="<?php echo "https://www.themoviedb.org/tv/" . $production['tmdb_id']; ?>" 
                           target="_blank">
                            <img src="<?php echo "https://image.tmdb.org/t/p/w500" . $production['poster_path']; ?>" 
                                 alt="<?php echo htmlspecialchars($production['production_title']); ?>"
                                 class="production-poster">
                        </a>
                        <div class="production-info">
                            <h3><?php echo htmlspecialchars($production['production_title']); ?></h3>
                            <p>Total Awards: <?php echo $production['award_count']; ?></p>
                            <p>Awards Won: <?php echo $production['won_count']; ?></p>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <?php include __DIR__ . '/../includes/footer.php'; ?>
</body>
</html>
