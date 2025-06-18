<nav class="navbar">
    <div class="navbar-container">
        <a href="/" class="navbar-brand">
            <i class="fas fa-film"></i> Actor Awards Visualizer
        </a>
        
        <button class="navbar-toggle" id="navbar-toggle">
            <span class="hamburger-line"></span>
            <span class="hamburger-line"></span>
            <span class="hamburger-line"></span>
        </button>
        
        <div class="navbar-menu" id="navbar-menu">
            <div class="navbar-nav">
                <a href="/" class="nav-link"><i class="fas fa-home"></i><span>Home</span></a>
                <a href="/searchActor" class="nav-link"><i class="fas fa-search"></i><span>Search Actor</span></a>
                <a href="/nominations" class="nav-link"><i class="fas fa-trophy"></i><span>Nominations</span></a>
                <a href="/productions" class="nav-link"><i class="fas fa-film"></i><span>Productions</span></a>
                <a href="/stats" class="nav-link"><i class="fas fa-chart-bar"></i><span>Statistics</span></a>
            </div>
            
            <div class="navbar-user">
                <?php if (isset($_SESSION['user_id'])): ?>
                    <div class="user-info">
                        <i class="fas fa-user-circle"></i>
                        <span class="username">Welcome, <?php echo htmlspecialchars($_SESSION['username']); ?></span>
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

<script>
document.addEventListener('DOMContentLoaded', () => {
    const navbarToggle = document.getElementById('navbar-toggle');
    const navbarMenu = document.getElementById('navbar-menu');

    if (navbarToggle && navbarMenu) {
        navbarToggle.addEventListener('click', () => {
            navbarToggle.classList.toggle('active');
            navbarMenu.classList.toggle('active');
        });
    }
});
</script>
