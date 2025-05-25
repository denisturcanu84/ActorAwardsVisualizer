<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/tmdb.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->load();
$api_key = $_ENV['TMDB_API_KEY'] ?? '';

if (!isset($_GET['name']) || trim($_GET['name']) === '') {
    die('Actor not specified.');
}
$actor_name = trim($_GET['name']);

$actor = searchActorTmdb($actor_name, $api_key);

if ($actor) {
    $tmdb_id = $actor['id'];
} else {
    die('Actor not found.');
}

$db = getDbConnection();
$actor_db = findActorByTmdbId($db, $tmdb_id);

// daca ar trebui actualizat actorul in baza de date
$shouldUpdate = !$actor_db || isOutdated($actor_db['last_updated']);

if ($shouldUpdate) {
    // fetch detalii actor din TMDB
    $tmdb_data = getActorDetailsTmdb($tmdb_id, $api_key);

    $actor_name = $tmdb_data['name'] ?? '';
    $profile_path = $tmdb_data['profile_path'] ?? '';
    $bio = $tmdb_data['known_for_department'] ?? '';
    $popularity = $tmdb_data['popularity'] ?? '';
    $tmdb_link = "https://www.themoviedb.org/person/$tmdb_id";

    upsertActor($db, [
    'full_name' => $actor_name,
    'tmdb_id' => $tmdb_id,
    'bio' => $bio,
    'profile_path' => $profile_path,
    'popularity' => $popularity
]);

} else {
    $actor_name = $actor_db['full_name'];
    $profile_path = $actor_db['profile_path'];
    $bio = $actor_db['bio'];
    $popularity = $actor_db['popularity'];
    $tmdb_link = "https://www.themoviedb.org/person/$tmdb_id";
}

$awards = getActorAwards($db, $actor_name);
$profile_path = getProfileImageUrl($profile_path);
$consecutive = getConsecutiveNominationYears($db, $actor_name);
$movies = getActorMovies($tmdb_id, $api_key, 4);
$news   = getActorNews($actor_name);

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title><?php echo htmlspecialchars($actor_name); ?> - Actor Profile</title>
    <meta name="viewport" content="width=device-width,initial-scale=1.0">
    <link rel="stylesheet" href="../assets/css/actor_profile.css">
    <link rel="stylesheet" href="../assets/css/navbar.css">
</head>
<body>
    <?php include '../includes/navbar.php'; ?>
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
                            <?php if ($bio): ?>
                                <div class="bio">Known for: <?php echo htmlspecialchars($bio); ?></div>
                            <?php endif; ?>
                            <?php if (!empty($tmdb_data['biography'])): ?>
                                <div class="biography"><?php echo nl2br(htmlspecialchars($tmdb_data['biography'])); ?></div>
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
</body>
</html>
