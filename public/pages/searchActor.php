<?php
require_once __DIR__ . '/../../src/bootstrap.php';

use ActorAwards\Middleware\AuthenticationMiddleware;

// Make sure the user is logged in.
AuthenticationMiddleware::requireLogin();

// Grab the search term from the URL.
$search = isset($_GET['name']) ? trim($_GET['name']) : '';

// The profile page handles the actual search logic so it will redirect there.
if ($search !== '') {
    $search_url = "/actor_profile?name=" . urlencode($search);
    header("Location: $search_url");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Actor Awards Visualizer - Search</title>
    <meta name="viewport" content="width=device-width,initial-scale=1.0">
    <link rel="stylesheet" href="/assets/css/common.css">
    <link rel="stylesheet" href="/assets/css/searchActor.css">
    <link rel="stylesheet" href="/assets/css/navbar.css">
    <link rel="stylesheet" href="/assets/css/footer.css">
</head>
<body>
    <?php include '../../src/Views/Components/Navbar.php'; ?>

    <!-- page header -->
    <div class="page-header">
        <div class="container_header">
            <h1>Search Actor</h1>
            <p class="page-description">
                Find detailed information about actors and their award history. 
                Enter an actor's name to view their profile, nominations, wins, 
                and career achievements.
            </p>
        </div>
    </div>

    <div class="container">
        <form method="get" action="/searchActor" class="search-form">
            <input type="text" name="name" placeholder="Enter actor's full name" required>
            <button type="submit">Search</button>
        </form>
    </div>

    <?php include '../../src/Views/Components/Footer.php'; ?>
</body>
</html>