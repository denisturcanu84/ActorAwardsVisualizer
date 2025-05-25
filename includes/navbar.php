<nav class="navbar">
    <a href="/" class="logo">Actor Awards Visualizer</a>
    <button class="menu-button" aria-label="Toggle navigation menu">☰</button>
    <div class="nav-links">
        <a href="/">Home</a>
        <a href="/pages/searchActor.php">Search Actor</a>
        <a href="/nominations.php">Nominations</a>
        <a href="/stats.php">Statistics</a>
    </div>
</nav>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const menuButton = document.querySelector('.menu-button');
    const navLinks = document.querySelector('.nav-links');
    
    menuButton.addEventListener('click', function() {
        navLinks.classList.toggle('active');
    });

    // Close menu when clicking outside
    document.addEventListener('click', function(event) {
        if (!event.target.closest('.navbar')) {
            navLinks.classList.remove('active');
        }
    });
});
</script>
