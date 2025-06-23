<?php
require_once __DIR__ . '/../../src/bootstrap.php';

use ActorAwards\Middleware\AuthenticationMiddleware;
AuthenticationMiddleware::requireLogin();

use ActorAwards\Services\DatabaseService;
use ActorAwards\Services\TmdbService;
use ActorAwards\Utils\Helpers;

$db = DatabaseService::getConnection();
$tmdbService = new TmdbService(TMDB_API_KEY);

$selectedYear = $_GET['year'] ?? $_POST['year'] ?? '';
$selectedCategory = $_GET['category'] ?? $_POST['category'] ?? '';
$selectedResult = $_GET['result'] ?? $_POST['result'] ?? '';
$searchQuery = $_GET['search'] ?? $_POST['search'] ?? '';

$resultBoolean = null;
if ($selectedResult === 'Won') {
    $resultBoolean = 'True';
} elseif ($selectedResult === 'Nominated') {
    $resultBoolean = 'False';
}

$currentPage = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;

// Redirect POST to GET for bookmarkable URLs.
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $redirect_params = [];
    if (!empty($_POST['year'])) $redirect_params['year'] = $_POST['year'];
    if (!empty($_POST['category'])) $redirect_params['category'] = $_POST['category'];
    if (!empty($_POST['result'])) $redirect_params['result'] = $_POST['result'];
    if (!empty($_POST['search'])) $redirect_params['search'] = $_POST['search'];
    
    $redirect_url = '/pages/nominations.php';
    if (!empty($redirect_params)) {
        $redirect_url .= '?' . http_build_query($redirect_params);
    }
    
    header('Location: ' . $redirect_url);
    exit;
}

$query = "SELECT a.*,
                 a.full_name,
                 ac.profile_path,
                 ac.tmdb_id AS actor_tmdb_id,
                 p.title as production_title,
                 p.poster_path AS local_db_poster_path,
                 p.tmdb_id AS production_tmdb_id,
                 p.type AS production_type
          FROM awards a
          LEFT JOIN actors ac ON a.tmdb_actor_id = ac.tmdb_id
          LEFT JOIN productions p ON a.tmdb_show_id = p.tmdb_id
          WHERE a.full_name IS NOT NULL AND a.full_name <> ''";

$params = []; // Bind parameters for the prepared statement.

// Apply filters to the query.
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
    $query .= " AND (a.full_name LIKE ? OR p.title LIKE ?)";
    $params[] = "%{$searchQuery}%";
    $params[] = "%{$searchQuery}%";
}

$itemsPerPage = 10;

$countSql = "SELECT COUNT(*)
             FROM awards a
             LEFT JOIN productions p ON a.tmdb_show_id = p.tmdb_id
             WHERE a.full_name IS NOT NULL AND a.full_name <> ''";

// Add the same filters to the count query.
$countParams = [];
if ($selectedYear) {
    $countSql .= " AND a.year = ?";
    $countParams[] = $selectedYear;
}
if ($selectedCategory) {
    $countSql .= " AND a.category = ?";
    $countParams[] = $selectedCategory;
}
if ($resultBoolean !== null) {
    $countSql .= " AND a.won = ?";
    $countParams[] = $resultBoolean;
}
if ($searchQuery) {
    $countSql .= " AND (a.full_name LIKE ? OR p.title LIKE ?)";
    $countParams[] = "%{$searchQuery}%";
    $countParams[] = "%{$searchQuery}%";
}

// Execute the count query.
$countStmt = $db->prepare($countSql);
$countStmt->execute($countParams);
$totalItems = $countStmt->fetchColumn();
$totalPages = ceil($totalItems / $itemsPerPage);

// Add pagination to the main query.
$offset = ($currentPage - 1) * $itemsPerPage;
$query .= " ORDER BY a.year DESC, a.full_name ASC LIMIT ?, ?";
$params[] = $offset;
$params[] = $itemsPerPage;

// Execute the main query.
$stmt = $db->prepare($query);
$stmt->execute($params);
$nominations = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get distinct values for the filter dropdowns.
$years = $db->query("SELECT DISTINCT year FROM awards ORDER BY year DESC")->fetchAll(PDO::FETCH_COLUMN);
$categories = $db->query("SELECT DISTINCT category FROM awards ORDER BY category ASC")->fetchAll(PDO::FETCH_COLUMN);

// Process results to fetch poster images from TMDB if they are missing locally.
foreach ($nominations as &$nomination) {
    if (empty($nomination['local_db_poster_path']) && !empty($nomination['production_tmdb_id'])) {
        $productionDetails = $tmdbService->getProductionDetails($nomination['production_tmdb_id'], $nomination['production_type']);
        if ($productionDetails && !empty($productionDetails['poster_path'])) {
            $nomination['local_db_poster_path'] = $productionDetails['poster_path'];
            // Save the poster path to the local database for next time.
            $updateStmt = $db->prepare("UPDATE productions SET poster_path = ? WHERE tmdb_id = ?");
            $updateStmt->execute([$productionDetails['poster_path'], $nomination['production_tmdb_id']]);
        }
    }
}
// Make sure the reference is removed.
unset($nomination); 

// Get the base URL for images.
$posterBaseUrl = $tmdbService->getPosterBaseUrl();
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Actor Awards - Nominations</title>
  <link rel="stylesheet" href="../assets/css/common.css">
  <link rel="stylesheet" href="../assets/css/index.css">
  <link rel="stylesheet" href="../assets/css/navbar.css">
  <link rel="stylesheet" href="../assets/css/footer.css">
  <link rel="stylesheet" href="../assets/css/nominations.css">
</head>
<body>
  <?php include '../../src/Views/Components/Navbar.php'; ?>
  
  <div class="page-header">
    <div class="container_header">
      <h1>Award Nominations</h1>
      <p class="page-description">
        Explore the SAG awards nominations and wins. 
        Filter by year, category, or search for specific actors and productions to discover 
        outstanding achievements in film and television.
      </p>
    </div>
  </div>

  <div class="container">
    <div class="main-grid">
      <div class="side-panel">
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
             <a href="/pages/nominations.php" class="reset-button">Reset</a>
           </div>
         </form>
        </div>
      </div>
      
      <div class="content-panel">
        <div class="results-info">
          <p>Showing <?php echo count($nominations); ?> of <?php echo $totalItems; ?> nominations</p>
        </div>

        <div class="nominations-list">
         <?php if (empty($nominations)): ?>
             <div class="no-results">
               <p>No nominations found matching your criteria.</p>
             </div>
           <?php else: ?>
           <?php foreach ($nominations as $nomination): ?>
             <div class="nomination-card">
               <div class="nomination-images">
                 <div class="image-wrapper">
                   <?php
                   $actor_image = null;
                   
                   if (!empty($nomination['profile_path'])) {
                       $actor_image = $tmdbService->getProfileImageUrl($nomination['profile_path']);
                   }
                   
                   // if no image and we have TMDB actor ID, try API
                   if (!$actor_image && !empty($nomination['actor_tmdb_id'])) {
                       $actor_details = $tmdbService->getActorDetails($nomination['actor_tmdb_id']);
                       if (is_array($actor_details) && !empty($actor_details['profile_path'])) {
                           $actor_image = $tmdbService->getProfileImageUrl($actor_details['profile_path']);
                       }
                   }
                   
                   // if still no image, try searching by name
                   if (!$actor_image && !empty($nomination['full_name'])) {
                       $actor_search = $tmdbService->searchActor($nomination['full_name']);
                       if ($actor_search && !empty($actor_search['profile_path'])) {
                           $actor_image = $tmdbService->getProfileImageUrl($actor_search['profile_path']);
                       }
                   }
                   ?>
                   <?php if ($actor_image): ?>
                     <img class="actor-img" src="<?php echo htmlspecialchars($actor_image); ?>" alt="<?php echo htmlspecialchars($nomination['full_name'] ?? 'Actor'); ?>">
                   <?php else: ?>
                     <div class="no-image actor-no-image">No Actor Image</div>
                   <?php endif; ?>
                   <span class="image-label">Actor</span>
                 </div>
               </div>
 
               <div class="nomination-details">
                 <?php
                     $displayName = $nomination['full_name'];
                     $profileUrl = "/actor_profile?"; 
                     if (!empty($nomination['actor_tmdb_id'])) { 
                       $profileUrl .= 'tmdb_id=' . intval($nomination['actor_tmdb_id']); 
                     } elseif (!empty($displayName)) { 
                       $profileUrl .= 'name=' . urlencode($displayName);
                     }
                   ?>
                   <h3>
                     <a href="<?php echo htmlspecialchars($profileUrl); ?>">
                       <?php echo htmlspecialchars($displayName); ?>
                     </a>
                   </h3>
      
                 <div class="nomination-info">
                   <p><strong>Year:</strong> <?php echo htmlspecialchars($nomination['year'] ?? 'N/A'); ?></p>
                   <p><strong>Category:</strong> <?php echo htmlspecialchars($nomination['category'] ?? 'N/A'); ?></p>
                   
                     <?php
                       $showName = $nomination['production_title'] 
                                   ?? ($nomination['show'] ?? 'Unknown Show');
                     ?>
                     <p><strong>Show:</strong>
                       <?php echo htmlspecialchars($showName); ?>
                     </p>
                     
                   <p><strong>Result:</strong>
                     <span class="result-badge <?php echo $nomination['won'] === 'True' ? 'won' : 'nominated'; ?>">
                       <?php echo $nomination['won'] === 'True' ? 'Won' : 'Nominated'; ?>
                     </span>
                   </p>
                 </div>
               </div>

               <div class="image-wrapper">
                 <?php
                 $poster_image = null;
                 
                 // first try database
                 if (!empty($nomination['local_db_poster_path'])) {
                     $poster_image = $tmdbService->getPosterImageUrl($nomination['local_db_poster_path']);
                 }
                 
                 // if no poster and we have production TMDB ID
                 if (!$poster_image && !empty($nomination['production_tmdb_id'])) {
                     $production_type = $nomination['production_type'] ?? 'movie';
                     
                     if (strtolower($production_type) === 'tv') {
                         $details = $tmdbService->getTvShowDetails($nomination['production_tmdb_id']);
                     } else {
                         $details = $tmdbService->getMovieDetails($nomination['production_tmdb_id']);
                     }
                     
                     if (is_array($details) && !empty($details['poster_path'])) {
                         $poster_image = $tmdbService->getPosterImageUrl($details['poster_path']);
                     }
                 }
                 
                 // if still no poster, try searching by title
                 if (!$poster_image) {
                     $search_title = $nomination['production_title'] ?? $nomination['show'] ?? null;
                     if ($search_title) {
                         // try movie search first
                         $movie_search = $tmdbService->searchMovie($search_title);
                         if ($movie_search && !empty($movie_search['poster_path'])) {
                             $poster_image = $tmdbService->getPosterImageUrl($movie_search['poster_path']);
                         } else {
                             // try TV search
                             $tv_search = $tmdbService->searchTvShow($search_title);
                             if ($tv_search && !empty($tv_search['poster_path'])) {
                                 $poster_image = $tmdbService->getPosterImageUrl($tv_search['poster_path']);
                             }
                         }
                     }
                 }
                 ?>
                 <?php if ($poster_image): ?>
                   <img class="poster-img" src="<?php echo htmlspecialchars($poster_image); ?>" alt="<?php echo htmlspecialchars($nomination['production_title'] ?? $nomination['show'] ?? 'Poster'); ?>">
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
          if (!empty($selectedYear)) $base['year'] = $selectedYear;
          if (!empty($selectedCategory)) $base['category'] = $selectedCategory;
          if (!empty($selectedResult)) $base['result'] = $selectedResult;
          if (!empty($searchQuery)) $base['search'] = $searchQuery;
          
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
  <?php include '../../src/Views/Components/Footer.php'; ?>
</body>
</html>
