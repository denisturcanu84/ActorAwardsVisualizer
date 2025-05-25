<nav class="navbar">
    <div class="nav-container">
        <div class="nav-logo">
            <h2>🏆 Actor Awards Visualizer</h2>
        </div>
        <button class="menu-toggle" onclick="toggleMenu()">☰</button>
        <ul class="nav-menu">
            <li><a href="index.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'index.php' ? 'active' : ''; ?>">Home</a></li>
            <li><a href="nominations.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'nominations.php' ? 'active' : ''; ?>">Nominations</a></li>
            <li><a href="stats.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'stats.php' ? 'active' : ''; ?>">Statistics</a></li>
            <li><a href="#" class="nav-link" onclick="openSettings()">Settings</a></li>
        </ul>
    </div>
</nav>

<script>
function toggleMenu() {
    const menu = document.querySelector('.nav-menu');
    menu.classList.toggle('active');
}

// Close menu when clicking outside
document.addEventListener('click', function(event) {
    const menu = document.querySelector('.nav-menu');
    const menuToggle = document.querySelector('.menu-toggle');
    
    if (!menu.contains(event.target) && !menuToggle.contains(event.target)) {
        menu.classList.remove('active');
    }
});
</script>
