<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Actor Awards Visualizer</title>
    <link rel="stylesheet" href="/../assets/css/startpage.css">
    <link rel="stylesheet" href="/../assets/css/navbar.css">
</head>
<body>
    <?php include '/../includes/navbar.php'; ?>

    <!-- Main Content -->
    <main class="main-content">
        <!-- Hero Section -->
        <section class="hero">
            <div class="hero-content">
                <h1>Welcome to Actor Awards Visualizer</h1>
                <p class="hero-description">
                    Explore and analyze SAG Awards nominations and wins across different years, 
                    categories, and productions. Discover trends, statistics, and detailed 
                    information about your favorite actors and movies.
                </p>
            </div>
        </section>

        <!-- Navigation Cards -->
        <section class="navigation-cards">
            <div class="container">
                <h2>Explore the Data</h2>
                <div class="cards-grid">
                    <div class="card">
                        <div class="card-icon">🎭</div>
                        <h3>Nominations</h3>
                        <p>Browse nominations by year, category, actor, or production</p>
                        <a href="nominations.php" class="card-button">View Nominations</a>
                    </div>
                    
                    <div class="card">
                        <div class="card-icon">👤</div>
                        <h3>Actors</h3>
                        <p>Explore detailed profiles, biographies, and award histories</p>
                        <a href="actor.php" class="card-button">Browse Actors</a>
                    </div>
                    
                    <div class="card">
                        <div class="card-icon">🎬</div>
                        <h3>Productions</h3>
                        <p>Discover movies and TV shows with their nominations</p>
                        <a href="production.php" class="card-button">View Productions</a>
                    </div>
                    
                    <div class="card">
                        <div class="card-icon">📊</div>
                        <h3>Statistics</h3>
                        <p>Analyze trends, generate charts, and export data</p>
                        <a href="stats.php" class="card-button">View Statistics</a>
                    </div>
                </div>
            </div>
        </section>

        <!-- Quick Stats Preview -->
        <section class="quick-stats">
            <div class="container">
                <h2>Quick Overview</h2>
                <div class="stats-grid">
                    <div class="stat-item">
                        <div class="stat-number">2,500+</div>
                        <div class="stat-label">Total Nominations</div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-number">450+</div>
                        <div class="stat-label">Unique Actors</div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-number">25</div>
                        <div class="stat-label">Award Categories</div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-number">30+</div>
                        <div class="stat-label">Years of Data</div>
                    </div>
                </div>
            </div>
        </section>
    </main>

    <!-- Settings Popup -->
    <div id="settingsPopup" class="popup-overlay">
        <div class="popup-content">
            <div class="popup-header">
                <h3>Settings</h3>
                <button class="close-btn" onclick="closeSettings()">&times;</button>
            </div>
            <div class="popup-body">
                <div class="setting-item">
                    <label for="themeToggle">Theme:</label>
                    <select id="themeToggle" onchange="toggleTheme()">
                        <option value="light">Light Mode</option>
                        <option value="dark">Dark Mode</option>
                    </select>
                </div>
                <div class="setting-item">
                    <button class="auth-btn" onclick="handleAuth()">Sign In</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <footer class="footer">
        <div class="container">
            <p>&copy; 2024 Actor Awards Visualizer. Data provided by TMDB API.</p>
        </div>
    </footer>

    <script>
        // Settings popup functions
        function openSettings() {
            document.getElementById('settingsPopup').style.display = 'flex';
        }

        function closeSettings() {
            document.getElementById('settingsPopup').style.display = 'none';
        }

        // Theme toggle
        function toggleTheme() {
            const theme = document.getElementById('themeToggle').value;
            document.body.className = theme + '-theme';
            localStorage.setItem('theme', theme);
        }

        // Load saved theme
        window.onload = function() {
            const savedTheme = localStorage.getItem('theme') || 'light';
            document.getElementById('themeToggle').value = savedTheme;
            document.body.className = savedTheme + '-theme';
        }

        // Auth handler
        function handleAuth() {
            // For now, just redirect to a sign-in page
            window.location.href = 'auth.php';
        }

        // Close popup when clicking outside
        window.onclick = function(event) {
            const popup = document.getElementById('settingsPopup');
            if (event.target == popup) {
                closeSettings();
            }
        }
    </script>
</body>
</html>