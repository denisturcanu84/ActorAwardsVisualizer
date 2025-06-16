<?php

require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../../src/includes/NominationsHandler.php';

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../..');
$dotenv->load();

// create handler
$handler = new NominationsHandler();

// handle form submission
$handler->handleFormSubmission();

// get filters
$filters = $handler->getFilters();

// get data
$result = $handler->getNominations($filters);
$data = $result['data'];
$total = $result['total'];
$totalPages = $result['totalPages'];
$page = $result['currentPage'];

// get dropdown data
$years = $handler->getYears();
$categories = $handler->getCategories();

// extract filters for easy use in template
$year = $filters['year'];
$cat = $filters['cat'];
$result_filter = $filters['result'];
$search = $filters['search'];
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
    <div class="container">
      <h1>Award Nominations</h1>
      <p class="page-description">
        Explore the SAG awards nominations and wins. 
        Filter by year, category, or search for specific actors and productions to discover 
        outstanding achievements in film and television.
      </p>
    </div>
  </div>

  <div class="container">
    <!-- filters section moved outside main-grid -->
    <div class="filters-section">
      <form class="filters-form" method="POST">
        <div class="filters-grid">
          <div class="filter-group">
            <label for="yearFilter">Year:</label>
            <select id="yearFilter" name="year">
                <option value="">All Years</option>
                <?php foreach ($years as $y): ?>
                    <option value="<?php echo htmlspecialchars($y); ?>" 
                            <?php echo $year == $y ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($y); ?>
                    </option>
                <?php endforeach; ?>
            </select>
          </div>
          <div class="filter-group">
            <label for="categoryFilter">Category:</label>
            <select id="categoryFilter" name="category">
                <option value="">All Categories</option>
                <?php foreach ($categories as $c): ?>
                    <option value="<?php echo htmlspecialchars($c); ?>" 
                            <?php echo $cat == $c ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($c); ?>
                    </option>
                <?php endforeach; ?>
            </select>
          </div>
          <div class="filter-group">
            <label for="resultFilter">Result:</label>
            <select id="resultFilter" name="result">
                <option value="">All Results</option>
                <option value="Won" <?php echo $result_filter == 'Won' ? 'selected' : ''; ?>>Won</option>
                <option value="Nominated" <?php echo $result_filter == 'Nominated' ? 'selected' : ''; ?>>Nominated</option>
            </select>
          </div>
          <div class="filter-group">
            <label for="searchInput">Search:</label>
            <input type="text" id="searchInput" name="search" 
                   value="<?php echo htmlspecialchars($search); ?>" 
                   placeholder="Actor or production name">
          </div>
        </div>
        <div class="filter-buttons">
          <button type="submit" class="filter-button">Apply Filters</button>
          <a href="nominations.php" class="reset-button">Reset</a>
        </div>
      </form>
    </div>

    <div class="main-grid">
      <div class="content-panel">
        <!-- results info -->
        <div class="results-info">
          <p>Showing <?php echo count($data); ?> of <?php echo $total; ?> nominations</p>
        </div>

        <!-- nominations list -->
        <div class="nominations-list">
          <?php if (empty($data)): ?>
              <div class="no-results">
                <p>No nominations found matching your criteria.</p>
              </div>
            <?php else: ?>
            <?php foreach ($data as $item): ?>
              <div class="nomination-card">
                <div class="nomination-images">
                  <div class="image-wrapper">
                    <?php if (!empty($item['profile_path'])): ?>
                      <img src="https://image.tmdb.org/t/p/w185<?php echo htmlspecialchars($item['profile_path']); ?>" 
                           alt="<?php echo htmlspecialchars($item['full_name']); ?>" 
                           class="actor-img">
                    <?php else: ?>
                      <div class="actor-no-image">No Photo</div>
                    <?php endif; ?>
                    <div class="image-label">Actor</div>
                  </div>
                </div>
   
                <div class="nomination-details">
                  <h3>
                    <?php if (!empty($item['actor_tmdb_id'])): ?>
                      <a href="actor_profile.php?id=<?php echo $item['actor_tmdb_id']; ?>">
                        <?php echo htmlspecialchars($item['full_name']); ?>
                      </a>
                    <?php else: ?>
                      <?php echo htmlspecialchars($item['full_name']); ?>
                    <?php endif; ?>
                  </h3>
                  
                  <div class="nomination-info">
                    <p><strong>Category:</strong> <?php echo htmlspecialchars($item['category']); ?></p>
                    <p><strong>Year:</strong> <?php echo htmlspecialchars($item['year']); ?></p>
                    <?php if (!empty($item['production_title'])): ?>
                      <p><strong>Production:</strong> <?php echo htmlspecialchars($item['production_title']); ?></p>
                    <?php endif; ?>
                    <p><strong>Result:</strong> 
                      <span class="result-badge <?php echo $item['won'] === 'True' ? 'won' : 'nominated'; ?>">
                        <?php echo $item['won'] === 'True' ? 'Won' : 'Nominated'; ?>
                      </span>
                    </p>
                  </div>
                </div>

                <div class="image-wrapper">
                  <?php if (!empty($item['local_db_poster_path'])): ?>
                    <img src="https://image.tmdb.org/t/p/w185<?php echo htmlspecialchars($item['local_db_poster_path']); ?>" 
                         alt="<?php echo htmlspecialchars($item['production_title']); ?>" 
                         class="poster-img">
                  <?php else: ?>
                    <div class="poster-no-image">No Poster</div>
                  <?php endif; ?>
                  <div class="image-label">Production</div>
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
          // build base params for pagination
          $base = [];
          if (!empty($year)) $base['year'] = $year;
          if (!empty($cat)) $base['category'] = $cat;
          if (!empty($result_filter)) $base['result'] = $result_filter;
          if (!empty($search)) $base['search'] = $search;
          
          // pagination logic
          $showPages = 7;
          $start = max(1, $page - floor($showPages / 2));
          $end = min($totalPages, $start + $showPages - 1);
          
          if ($end - $start < $showPages - 1) {
              $start = max(1, $end - $showPages + 1);
          }
        ?>
        
        <?php if($page > 1): ?>
          <a href="?<?php echo http_build_query($base + ['page' => $page - 1]); ?>">&laquo; Prev</a>
        <?php endif; ?>

        <?php if($start > 1): ?>
          <a href="?<?php echo http_build_query($base + ['page' => 1]); ?>">1</a>
          <?php if($start > 2): ?>
            <span class="pagination-dots">...</span>
          <?php endif; ?>
        <?php endif; ?>

        <?php for($i = $start; $i <= $end; $i++): ?>
          <a class="<?php echo $i === $page ? 'active' : ''; ?>"
             href="?<?php echo http_build_query($base + ['page' => $i]); ?>">
            <?php echo $i; ?>
          </a>
        <?php endfor; ?>

        <?php if($end < $totalPages): ?>
          <?php if($end < $totalPages - 1): ?>
            <span class="pagination-dots">...</span>
          <?php endif; ?>
          <a href="?<?php echo http_build_query($base + ['page' => $totalPages]); ?>"><?php echo $totalPages; ?></a>
        <?php endif; ?>

        <?php if($page < $totalPages): ?>
          <a href="?<?php echo http_build_query($base + ['page' => $page + 1]); ?>">Next &raquo;</a>
        <?php endif; ?>
      </nav>
    </div>
    <?php endif; ?>
  </div>
  <?php include '../../src/includes/footer.php'; ?>
</body>
</html>
