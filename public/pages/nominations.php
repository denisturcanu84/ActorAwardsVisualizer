<?php
require_once __DIR__ . '/../../src/bootstrap.php';

use ActorAwards\Middleware\AuthenticationMiddleware;

// Require user to be logged in
AuthenticationMiddleware::requireLogin();

// enabled error reporting for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Legacy includes for existing functionality
require_once __DIR__ . '/../../src/includes/db.php';
require_once __DIR__ . '/../../src/includes/tmdb.php';
require_once __DIR__ . '/../../src/includes/functions.php';

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../..');
$dotenv->load();
$api_key = TMDB_API_KEY;

// initialize database connection
$db = getDbConnection();

// get filter values - prioritize GET parameters if they exist, then POST
$selectedYear = $_GET['year'] ?? $_POST['year'] ?? '';
$selectedCategory = $_GET['category'] ?? $_POST['category'] ?? '';
$selectedResult = $_GET['result'] ?? $_POST['result'] ?? '';
$searchQuery = $_GET['search'] ?? $_POST['search'] ?? '';

// convert Won/Nominated to True/False for database query
$resultBoolean = null;
if ($selectedResult === 'Won') {
    $resultBoolean = 'True';
} elseif ($selectedResult === 'Nominated') {
    $resultBoolean = 'False';
}

// get current page
$currentPage = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;

// if POST request (new filters applied), redirect to GET with parameters to maintain state
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

// build the query with filters
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
    $query .= " AND (a.full_name LIKE ? OR p.title LIKE ?)";
    $params[] = "%$searchQuery%";
    $params[] = "%$searchQuery%";
}

// --- pagination setup ----
$itemsPerPage = 10;

// build COUNT query with the same filters
$countSql = "SELECT COUNT(*) 
             FROM awards a 
             LEFT JOIN productions p ON a.tmdb_show_id = p.tmdb_id 
             WHERE a.full_name IS NOT NULL AND a.full_name <> ''";
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
    $countParams[] = "%$searchQuery%";
    $countParams[] = "%$searchQuery%";
}

$countStmt = $db->prepare($countSql);
$countStmt->execute($countParams);
$totalItems = (int)$countStmt->fetchColumn();
$totalPages = (int)ceil($totalItems / $itemsPerPage);

// complete main query with LIMIT/OFFSET
$query .= " ORDER BY a.year DESC, a.category
            LIMIT ? OFFSET ?";
$params[] = $itemsPerPage;
$params[] = ($currentPage - 1) * $itemsPerPage;

// prepare and execute the query
$stmt = $db->prepare($query);
$stmt->execute($params);
$nominations = $stmt->fetchAll(PDO::FETCH_ASSOC);

// get unique years and categories for filters
$query = "SELECT DISTINCT year FROM awards ORDER BY year DESC";
$years = $db->query($query)->fetchAll(PDO::FETCH_COLUMN);

$query = "SELECT DISTINCT category FROM awards ORDER BY category";
$categories = $db->query($query)->fetchAll(PDO::FETCH_COLUMN);
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
  <?php include '../../src/includes/navbar.php'; ?>
  
  <!-- page header -->
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
        <!-- filters section -->
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
        <!-- results info -->
        <div class="results-info">
          <p>Showing <?php echo count($nominations); ?> of <?php echo $totalItems; ?> nominations</p>
        </div>

        <!-- nominations list -->
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
                   
                   // first try database
                   if (!empty($nomination['profile_path'])) {
                       $actor_image = getProfileImageUrl($nomination['profile_path']);
                   }
                   
                   // if no image and we have TMDB actor ID, try API
                   if (!$actor_image && !empty($nomination['actor_tmdb_id'])) {
                       $actor_details = getActorDetailsTmdb($nomination['actor_tmdb_id'], $api_key);
                       if (is_array($actor_details) && !empty($actor_details['profile_path'])) {
                           $actor_image = getProfileImageUrl($actor_details['profile_path']);
                       }
                   }
                   
                   // if still no image, try searching by name
                   if (!$actor_image && !empty($nomination['full_name'])) {
                       $actor_search = searchActorTmdb($nomination['full_name'], $api_key);
                       if ($actor_search && !empty($actor_search['profile_path'])) {
                           $actor_image = getProfileImageUrl($actor_search['profile_path']);
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
                     $poster_image = getPosterImageUrl($nomination['local_db_poster_path']);
                 }
                 
                 // if no poster and we have production TMDB ID
                 if (!$poster_image && !empty($nomination['production_tmdb_id'])) {
                     $production_type = $nomination['production_type'] ?? 'movie';
                     
                     if (strtolower($production_type) === 'tv') {
                         $details = getTvShowDetailsTmdb($nomination['production_tmdb_id'], $api_key);
                     } else {
                         $details = getMovieDetailsTmdb($nomination['production_tmdb_id'], $api_key);
                     }
                     
                     if (is_array($details) && !empty($details['poster_path'])) {
                         $poster_image = getPosterImageUrl($details['poster_path']);
                     }
                 }
                 
                 // if still no poster, try searching by title
                 if (!$poster_image) {
                     $search_title = $nomination['production_title'] ?? $nomination['show'] ?? null;
                     if ($search_title) {
                         // try movie search first
                         $movie_search = searchMovieTmdb($search_title, $api_key);
                         if ($movie_search && !empty($movie_search['poster_path'])) {
                             $poster_image = getPosterImageUrl($movie_search['poster_path']);
                         } else {
                             // try TV search
                             $tv_search = searchTvShowTmdb($search_title, $api_key);
                             if ($tv_search && !empty($tv_search['poster_path'])) {
                                 $poster_image = getPosterImageUrl($tv_search['poster_path']);
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
  <?php include '../../src/includes/footer.php'; ?>
</body>
</html>
