<?php
require_once __DIR__ . '/../../src/bootstrap.php';

use ActorAwards\Middleware\AuthenticationMiddleware;
use ActorAwards\Services\DatabaseService;
use ActorAwards\Services\TmdbService;
use ActorAwards\Repositories\ProductionRepository;

// Make sure the user is logged in.
AuthenticationMiddleware::requireLogin();

// Set up our services.
$db = DatabaseService::getConnection();
$tmdbService = new TmdbService(TMDB_API_KEY);
// The repo needs the TMDB service to grab missing images.
$productionRepository = new ProductionRepository($db, $tmdbService);

// Get filter values from request.
$selectedYear = $_GET['year'] ?? $_POST['year'] ?? '';
$selectedCategory = $_GET['category'] ?? $_POST['category'] ?? '';
$selectedResult = $_GET['result'] ?? $_POST['result'] ?? '';
$searchQuery = $_GET['search'] ?? $_POST['search'] ?? '';

// Convert 'Won'/'Nominated' to DB boolean 'True'/'False'.
$resultBoolean = null;
if ($selectedResult === 'Won') {
    $resultBoolean = 'True';
} elseif ($selectedResult === 'Nominated') {
    $resultBoolean = 'False';
}

// Pagination.
$currentPage = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$itemsPerPage = 10;

// Redirect POST to GET for bookmarkable URLs.
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $redirect_params = [];
    if (!empty($_POST['year'])) { $redirect_params['year'] = $_POST['year']; }
    if (!empty($_POST['category'])) { $redirect_params['category'] = $_POST['category']; }
    if (!empty($_POST['result'])) { $redirect_params['result'] = $_POST['result']; }
    if (!empty($_POST['search'])) { $redirect_params['search'] = $_POST['search']; }

    $redirect_url = '/pages/productions.php';
    if (!empty($redirect_params)) {
        $redirect_url .= '?' . http_build_query($redirect_params);
    }

    header('Location: ' . $redirect_url);
    exit;
}

// The repo does all the heavy lifting now, including fetching missing images.
$filters = [
    'year' => $selectedYear,
    'category' => $selectedCategory,
    'result' => $resultBoolean,
    'search' => $searchQuery
];

// Count total results for pagination.
$totalItems = $productionRepository->countFilteredProductions($filters);
$totalPages = ceil($totalItems / $itemsPerPage);
$offset = ($currentPage - 1) * $itemsPerPage;

// Get productions for the current page.
$productions = $productionRepository->getFilteredProductions($filters, $itemsPerPage, $offset);

$years = $productionRepository->getUniqueAwardYears();
$categories = $productionRepository->getUniqueAwardCategories();
$posterBaseUrl = $tmdbService->getPosterBaseUrl();

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Productions - Actor Awards Visualizer</title>
    <link rel="stylesheet" href="../assets/css/common.css">
    <link rel="stylesheet" href="../assets/css/index.css">
    <link rel="stylesheet" href="../assets/css/navbar.css">
    <link rel="stylesheet" href="../assets/css/footer.css">
    <link rel="stylesheet" href="../assets/css/nominations.css">
</head>
<body>
    <?php include_once __DIR__ . '/../../src/Views/Components/Navbar.php'; ?>

    <!-- page header -->
    <div class="page-header">
        <div class="container_header">
            <h1>Productions</h1>
            <p class="page-description">
                Explore award-winning productions and their achievements.
                Filter by year, category, or search for specific titles to discover
                outstanding films and television shows that have received recognition.
            </p>
        </div>
    </div>

    <div class="container">
        <div class="main-grid">
            <div class="side-panel">
                <!-- Filters Section -->
                <div class="filters-section">
                    <form class="filters-form" method="POST" action="/pages/productions.php">
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
                                       placeholder="Search production title..."
                                       value="<?php echo htmlspecialchars($searchQuery); ?>">
                            </div>
                        </div>
                        <div class="filter-buttons">
                            <button type="submit" class="filter-button">Apply Filters</button>
                            <a href="/pages/productions.php" class="reset-button">Reset</a>
                        </div>
                    </form>
                </div>
            </div>

            <div class="content-panel">
                <!-- results info -->
                <div class="results-info">
                    <p>Showing <?php echo count($productions); ?> of <?php echo $totalItems; ?> productions</p>
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
                                <div class="nomination-details" style="width: 100%;">
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
                                <div class="image-wrapper">
                                    <?php if ($production['poster_path']): ?>
                                        <img class="poster-img" src="<?php echo htmlspecialchars($posterBaseUrl . $production['poster_path']); ?>"
                                             alt="<?php echo htmlspecialchars($production['production_title']); ?>">
                                    <?php else: ?>
                                        <div class="no-image poster-no-image">No Poster</div>
                                    <?php endif; ?>
                                    <span class="image-label">Production</span>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <?php if ($totalPages > 1): ?>
        <div class="pagination-wrapper">
          <nav class="pagination">
            <?php
              // build base parameters for pagination
              $base = [];
              if (!empty($selectedYear)) { $base['year'] = $selectedYear; }
              if (!empty($selectedCategory)) { $base['category'] = $selectedCategory; }
              if (!empty($selectedResult)) { $base['result'] = $selectedResult; }
              if (!empty($searchQuery)) { $base['search'] = $searchQuery; }

              // smart pagination - show limited page numbers
              $showPages = 7; // maximum pages to show
              $startPage = max(1, $currentPage - floor($showPages / 2));
              $endPage = min($totalPages, $startPage + $showPages - 1);

              // adjust start if we're near the end
              if ($endPage - $startPage < $showPages - 1) {
                  $startPage = max(1, $endPage - $showPages + 1);
              }
            ?>

            <?php if($currentPage > 1): ?>
              <a href="?<?php echo http_build_query($base + ['page' => $currentPage - 1]); ?>">&laquo; Prev</a>
            <?php endif; ?>

            <?php if($startPage > 1): ?>
              <a href="?<?php echo http_build_query($base + ['page' => 1]); ?>">1</a>
              <?php if($startPage > 2): ?>
                <span class="pagination-dots">...</span>
              <?php endif; ?>
            <?php endif; ?>

            <?php for($i = $startPage; $i <= $endPage; $i++): ?>
              <a class="<?php echo $i === $currentPage ? 'active' : ''; ?>"
                 href="?<?php echo http_build_query($base + ['page' => $i]); ?>">
                <?php echo $i; ?>
              </a>
            <?php endfor; ?>

            <?php if($endPage < $totalPages): ?>
              <?php if($endPage < $totalPages - 1): ?>
                <span class="pagination-dots">...</span>
              <?php endif; ?>
              <a href="?<?php echo http_build_query($base + ['page' => $totalPages]); ?>"><?php echo $totalPages; ?></a>
            <?php endif; ?>

            <?php if($currentPage < $totalPages): ?>
              <a href="?<?php echo http_build_query($base + ['page' => $currentPage + 1]); ?>">Next &raquo;</a>
            <?php endif; ?>
          </nav>
        </div>
        <?php endif; ?>
    </div>

    <?php include_once __DIR__ . '/../../src/Views/Components/Footer.php'; ?>
</body>
</html>
