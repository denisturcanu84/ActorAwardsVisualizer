<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

$search = isset($_GET['name']) ? trim($_GET['name']) : '';
$search_url = '';
if ($search !== '') {
    $search_url = "actor_profile.php?name=" . urlencode($search);
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
    <link rel="stylesheet" href="assets/css/index.css">
</head>
<body>
    <div class="container">
        <h1>Search Actor Profile</h1>
        <form method="get" action="index.php">
            <input type="text" name="name" placeholder="Enter actor's full name" required>
            <br>
            <button type="submit">Search</button>
        </form>
    </div>
</body>
</html>
