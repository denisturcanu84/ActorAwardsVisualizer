<nav class="navbar">
    <!-- Main navigation bar with responsive design -->
    <!-- The navbar has 3 main sections:
         1. Brand logo (left side)
         2. Navigation links (center)
         3. User actions (right side) -->
    <div class="navbar-container">
        <a href="/" class="navbar-brand">
            </i> Actor Awards Visualizer
        </a>
        
        <!-- Mobile menu toggle button - appears on small screens -->
        <button class="navbar-toggle" id="navbar-toggle">
            <span class="hamburger-line"></span>
            <span class="hamburger-line"></span>
            <span class="hamburger-line"></span>
        </button>
        
        <!-- Main menu container - shows/hides on mobile via JavaScript -->
        <div class="navbar-menu" id="navbar-menu">
            <div class="navbar-nav">
                <a href="/" class="nav-link"><i class="fas fa-home"></i><span>Home</span></a>
                <a href="/searchActor" class="nav-link"><i class="fas fa-search"></i><span>Search Actor</span></a>
                <a href="/nominations" class="nav-link"><i class="fas fa-trophy"></i><span>Nominations</span></a>
                <a href="/productions" class="nav-link"><i class="fas fa-film"></i><span>Productions</span></a>
                <a href="/stats" class="nav-link"><i class="fas fa-chart-bar"></i><span>Statistics</span></a>
            </div>
            
            <!-- User section - changes based on login status -->
            <div class="navbar-user">
                <!-- Check if user is logged in using session variable -->
                <?php if (isset($_SESSION['user_id'])): ?>
                    <div class="user-info">
                        <a href="/profile.php" class="user-profile-link">
                            <i class="fas fa-user-circle"></i>
                        </a>
                    </div>
                    <div class="user-actions">
                        <?php if (isset($_SESSION['is_admin']) && $_SESSION['is_admin']): ?>
                            <a href="/admin" class="nav-link admin-link"><i class="fas fa-user-shield"></i><span>Admin</span></a>
                        <?php endif; ?>
                        <a href="/logout" class="nav-link logout-link"><i class="fas fa-sign-out-alt"></i><span>Logout</span></a>
                    </div>
                <?php else: ?>
                    <div class="user-actions">
                        <a href="/login" class="nav-link login-link"><i class="fas fa-sign-in-alt"></i><span>Login</span></a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</nav>

<!-- JavaScript for mobile menu toggle functionality -->
<script src="/assets/js/navbar.js" defer></script>
