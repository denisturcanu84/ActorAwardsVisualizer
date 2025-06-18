<?php
require_once __DIR__ . '/../../src/bootstrap.php';

use ActorAwards\Middleware\AuthenticationMiddleware;

// Require user to be logged in
AuthenticationMiddleware::requireLogin();

// Legacy includes for existing functionality
require_once __DIR__ . '/../../src/includes/db.php';
require_once __DIR__ . '/../../src/includes/tmdb.php';
require_once __DIR__ . '/../../src/includes/functions.php';

$api_key = TMDB_API_KEY;

// verificam ce actor a fost cautat
if (!isset($_GET['name']) && !isset($_GET['tmdb_id'])) {
    die('Actor not specified.');
}

$actor_name = isset($_GET['name']) ? trim($_GET['name']) : '';
$tmdb_id_param = isset($_GET['tmdb_id']) ? intval($_GET['tmdb_id']) : 0;
$db = getDbConnection();

$actor_db = findActorInDatabase($db, $tmdb_id_param, $actor_name);

if ($actor_db) {
    // folosim datele din baza de date
    $tmdb_id = $actor_db['tmdb_id'];
    $actor_name = $actor_db['full_name'];
    $profile_path = $actor_db['profile_path'];
    $bio = $actor_db['bio'];
    $popularity = $actor_db['popularity'];
    
    // actualizam datele in baza de date daca sunt vechi
    if (isOutdated($actor_db['last_updated'], '7 days')) {
        $tmdb_data = getActorDetailsTmdb($tmdb_id, $api_key);
        if ($tmdb_data) {
            $profile_path = $tmdb_data['profile_path'] ?? $profile_path;
            $bio = $tmdb_data['biography'] ?? $bio;
            $popularity = $tmdb_data['popularity'] ?? $popularity;
            
            $update = $db->prepare("UPDATE actors SET bio=?, profile_path=?, popularity=?, last_updated=CURRENT_TIMESTAMP WHERE tmdb_id=?");
            $update->execute([$bio, $profile_path, $popularity, $tmdb_id]);
        }
    }
} else {
    // daca actorul nu este in baza de date, incercam sa il cautam cu TMDB API
    try {
        if ($tmdb_id_param > 0) {
            $tmdb_id = $tmdb_id_param;
            $tmdb_data = getActorDetailsTmdb($tmdb_id, $api_key);
        } else {
            $actor = searchActorTmdb($actor_name, $api_key);
            if (!$actor) {
                die('Actor not found in TMDB database.');
            }
            $tmdb_id = $actor['id'];
            $tmdb_data = getActorDetailsTmdb($tmdb_id, $api_key);
        }
        
        if (!$tmdb_data) {
            die('Could not retrieve actor details from TMDB.');
        }
        
        // verificam daca actorul exista deja in baza de date dupa ID-ul TMDB
        $actor_db = findActorInDatabase($db, $tmdb_id);
        if ($actor_db) {
            header("Location: /actor_profile?tmdb_id=" . $tmdb_id);
            exit;
        }
        
        // salvam datele actorului in baza de date
        $actor_name = $tmdb_data['name'] ?? '';
        $profile_path = $tmdb_data['profile_path'] ?? '';
        $bio = $tmdb_data['biography'] ?? '';
        $popularity = $tmdb_data['popularity'] ?? '';
        
        $insert = $db->prepare("INSERT INTO actors (full_name, tmdb_id, bio, profile_path, popularity, last_updated) 
                                VALUES (?, ?, ?, ?, ?, CURRENT_TIMESTAMP)");
        $insert->execute([$actor_name, $tmdb_id, $bio, $profile_path, $popularity]);
    } catch (PDOException $e) {
        // daca apare o eroare la interogarea TMDB, incercam sa gasim actorul in baza de date
        $actor_db = findActorInDatabase($db, $tmdb_id);
        if ($actor_db) {
            $actor_name = $actor_db['full_name'];
            $profile_path = $actor_db['profile_path'];
            $bio = $actor_db['bio'];
            $popularity = $actor_db['popularity'];
        } else {
            die('Error processing actor: ' . $e->getMessage());
        }
    }
}

// pregatim datele pentru afisare
$tmdb_link = "https://www.themoviedb.org/person/$tmdb_id";
$profile_path = getProfileImageUrl($profile_path);

// informatii aditionale
$awards = getActorAwards($db, $actor_name);
$movies = getActorMovies($tmdb_id, $api_key, 4);
$news = getActorNews($actor_name);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title><?php echo htmlspecialchars($actor_name); ?> - Actor Profile</title>
    <meta name="viewport" content="width=device-width,initial-scale=1.0">
    <link rel="stylesheet" href="/assets/css/common.css">
    <link rel="stylesheet" href="/assets/css/actor_profile.css">
    <link rel="stylesheet" href="/assets/css/navbar.css">
    <link rel="stylesheet" href="/assets/css/footer.css">
</head>
<body>
    <?php include '../../src/includes/navbar.php'; ?>
    <div class="page-wrapper" style="padding-top: 60px;">
        <div class="container">
            <div class="main-content">
                <!-- COLOANA STANGA -->
                <div class="left-col">
                    <div class="profile-card">
                        <div class="profile-header">
                            <div class="profile-img" style="<?php echo $profile_path ? "background-image:url('$profile_path');" : ''; ?>">
                                <?php if (!$profile_path): ?>
                                    <span style="display:block;text-align:center;line-height:240px;color:#aaa;">No Image</span>
                                <?php endif; ?>
                            </div>
                            <div class="profile-info">
                                <h1><?php echo htmlspecialchars($actor_name); ?></h1>
                                <?php if (!empty($bio)): ?>
                                    <div class="biography"><?php echo nl2br(htmlspecialchars($bio)); ?></div>
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
                            <h2>Screen Actors Guild Awards</h2>
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
                        
                        <div class="movies-section">
                            <h2>Popular Movies</h2>
                            <?php if (count($movies)): ?>
                                <ul class="movies-list">
                                    <?php foreach ($movies as $movie): ?>
                                        <li class="movie-item">
                                            <a href="https://www.themoviedb.org/movie/<?php echo $movie['id']; ?>" target="_blank" class="movie-link">
                                                <div class="movie-poster">
                                                    <?php if (!empty($movie['poster_path'])): ?>
                                                        <img
                                                            src="https://image.tmdb.org/t/p/w200<?php echo $movie['poster_path']; ?>"
                                                            alt="<?php echo htmlspecialchars($movie['title']); ?>">
                                                    <?php else: ?>
                                                        <div class="no-poster">No Image</div>
                                                    <?php endif; ?>
                                                </div>
                                                <div class="movie-info">
                                                    <h3><?php echo htmlspecialchars($movie['title']); ?></h3>
                                                    <?php if (!empty($movie['release_date'])): ?>
                                                        <span class="movie-year">(<?php echo substr($movie['release_date'],0,4); ?>)</span>
                                                    <?php endif; ?>
                                                    <?php if (!empty($movie['character'])): ?>
                                                        <div class="movie-character">as <?php echo htmlspecialchars($movie['character']); ?></div>
                                                    <?php endif; ?>
                                                </div>
                                            </a>
                                        </li>
                                    <?php endforeach; ?>
                                </ul>
                            <?php else: ?>
                                <div>No movies found for this actor.</div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <!-- COLOANA DREAPTA - NEWS -->
                <div class="right-col news-section">
                    <h2>Latest News</h2>
                    <?php if (count($news)): ?>
                    <ul class="news-list">
                        <?php foreach($news as $item): ?>
                        <li>
                            <a href="<?php echo htmlspecialchars($item['link']); ?>" target="_blank">
                                <?php echo htmlspecialchars($item['title']); ?>
                            </a>
                            <span class="news-date">
                                <?php echo htmlspecialchars(date('Y-m-d', strtotime($item['pubDate']))); ?>
                            </span>
                        </li>
                        <?php endforeach; ?>
                    </ul>
                    <?php else: ?>
                    <div>No news found for this actor.</div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <?php include '../../src/includes/footer.php'; ?>
</body>
</html>
