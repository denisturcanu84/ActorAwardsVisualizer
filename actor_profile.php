<?php
 ini_set('display_errors', 1);
 ini_set('display_startup_errors', 1);
 error_reporting(E_ALL);

 require_once __DIR__ . '/vendor/autoload.php';
 $dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
 $dotenv->load();

 $api_key = $_ENV['TMDB_API_KEY'];

 // Get actor name from query
 if (!isset($_GET['name']) || trim($_GET['name']) === '') {
     die('Actor not specified.');
 }
 $actor_name = trim($_GET['name']);

 // Fetch actor info from TMDB
 $tmdb_url = 'https://api.themoviedb.org/3/search/person?api_key=' . $api_key . '&query=' . urlencode($actor_name);
 $json = @file_get_contents($tmdb_url);
 $data = $json ? json_decode($json, true) : [];
 $actor = $data['results'][0] ?? null;

 // Get all awards for this actor from database
 $db = new PDO('sqlite:' . __DIR__ . '/database/awards.db');
 $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
 $awards = [];
 $stmt = $db->prepare("SELECT year, category, show FROM awards WHERE UPPER(full_name) = UPPER(?) AND won = 'True'");
 $stmt->execute([$actor_name]);
 $awards = $stmt->fetchAll(PDO::FETCH_ASSOC);

 // Actor image
 $profile_path = $actor && !empty($actor['profile_path']) ? 'https://image.tmdb.org/t/p/w400' . $actor['profile_path'] : null;

 // Actor bio
 $bio = $actor['known_for_department'] ?? '';
 $popularity = $actor['popularity'] ?? '';
 $tmdb_id = $actor['id'] ?? null;
 $tmdb_link = $tmdb_id ? "https://www.themoviedb.org/person/$tmdb_id" : null;
 ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title><?php echo htmlspecialchars($actor_name); ?> - Actor Profile</title>
    <meta name="viewport" content="width=device-width,initial-scale=1.0">
    <link rel="stylesheet" href="assets/css/actor_profile.css">
</head>
<body>
    <div class="container">
        <div class="profile-header">
            <div class="profile-img" style="<?php echo $profile_path ? "background-image:url('$profile_path');" : ''; ?>">
                <?php if (!$profile_path): ?>
                    <span style="display:block;text-align:center;line-height:240px;color:#aaa;">No Image</span>
                <?php endif; ?>
            </div>
            <div class="profile-info">
                <h1><?php echo htmlspecialchars($actor_name); ?></h1>
                <?php if ($bio): ?>
                    <div class="bio">Department: <?php echo htmlspecialchars($bio); ?></div>
                <?php endif; ?>
                <?php if ($popularity): ?>
                    <div class="popularity">Popularity: <?php echo htmlspecialchars(round($popularity, 1)); ?></div>
                <?php endif; ?>
                <?php if ($tmdb_link): ?>
                    <a class="tmdb-link" href="<?php echo $tmdb_link; ?>" target="_blank">View on TMDB</a>
                <?php endif; ?>
            </div>
        </div>
        <div class="awards-section">
            <h2>Awards Won</h2>
            <?php if (count($awards)): ?>
                <ul class="awards-list">
                    <?php foreach ($awards as $a): ?>
                        <li>
                            <span class="award-year"><?php echo htmlspecialchars(substr($a['year'], 0, 4)); ?></span>
                            <span class="award-cat"><?php echo htmlspecialchars($a['category']); ?></span>
                            <?php if ($a['show']): ?>
                                <span class="award-show">for "<?php echo htmlspecialchars($a['show']); ?>"</span>
                            <?php endif; ?>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php else: ?>
                <div>No awards found for this actor.</div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>