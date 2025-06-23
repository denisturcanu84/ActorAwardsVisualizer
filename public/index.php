<?php
require_once __DIR__ . '/../src/bootstrap.php'; // loads all required dependencies and configurations
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Actor Awards Visualizer</title>
    <link rel="stylesheet" href="assets/css/common.css">
    <link rel="stylesheet" href="assets/css/index.css">
    <link rel="stylesheet" href="assets/css/navbar.css">
    <link rel="stylesheet" href="assets/css/footer.css">
</head>
<body>
    <?php include '../src/Views/Components/Navbar.php';?>
    
    <!-- page header -->
    <div class="page-header">
        <div class="container_header">
            <h1>Welcome to Actor Awards Visualizer</h1>
            <p class="page-description">
                Explore and analyze SAG Awards nominations and wins across different years, 
                categories, and productions. Discover trends, statistics, and detailed 
                information about your favorite actors & movies.
            </p>
        </div>
    </div>

    <main class="main-content">
        <section class="navigation-cards">
            <div class="container">
                <h2>Explore the Data</h2>
                <div class="cards-grid">
                    <div class="card">
                        <div class="card-icon">🎭</div>
                        <h3>Nominations</h3>
                        <p>Browse nominations by year, category, actor, or productions</p>
                        <a href="/nominations" class="card-button">View Nominations</a>
                    </div>

                    <div class="card">
                        <div class="card-icon">👤</div>
                        <h3>Actors</h3>
                        <p>Explore detailed profiles, biographies, and award histories</p>
                        <a href="/searchActor" class="card-button">Browse Actors</a>
                    </div>
                    
                    <div class="card">
                        <div class="card-icon">🎬</div>
                        <h3>Productions</h3>
                        <p>Discover movies and TV shows with their nominations</p>
                        <a href="/productions" class="card-button">View Productions</a>
                    </div>
                    
                    <div class="card">
                        <div class="card-icon">📊</div>
                        <h3>Statistics</h3>
                        <p>Analyze trends, generate charts, and export data</p>
                        <a href="/stats" class="card-button">View Statistics</a>
                    </div>
                </div>
            </div>
        </section>

        <section class="quick-stats">
            <div class="container">
                <h2>Quick Overview</h2>
                <div class="stats-grid">
                    <div class="stat-item">
                        <div class="stat-number">5,700+</div>
                        <div class="stat-label">Total Nominations</div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-number">600+</div>
                        <div class="stat-label">Unique Actors</div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-number">45+</div>
                        <div class="stat-label">Award Categories</div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-number">60+</div>
                        <div class="stat-label">Years of Data</div>
                    </div>
                </div>
            </div>
        </section>
    </main>

    <?php include '../src/Views/Components/Footer.php'; ?>
    
</body>
</html>