<?php
/**
 * Displays an actor\'s profile, including biography, awards, movies, and news.
 */
require_once __DIR__ . '/../../src/bootstrap.php';

use ActorAwards\Middleware\AuthenticationMiddleware;

// Make sure the user is logged in.
AuthenticationMiddleware::requireLogin();

require_once __DIR__ . '/../../src/bootstrap.php';

use ActorAwards\Services\DatabaseService;
use ActorAwards\Services\TmdbService;
use ActorAwards\Repositories\ActorRepository;
use ActorAwards\Utils\Helpers;
use ActorAwards\Services\NewsService;

// Initialize services and repositories
$db = DatabaseService::getConnection();
$tmdbService = new TmdbService(TMDB_API_KEY);
$actorRepository = new ActorRepository($db);

// An actor must be specified by name or TMDB ID.
if (!isset($_GET['name']) && !isset($_GET['tmdb_id'])) {
    die('Actor not specified.');
}

$actor_name = isset($_GET['name']) ? trim($_GET['name']) : '';
$tmdb_id_param = isset($_GET['tmdb_id']) ? intval($_GET['tmdb_id']) : 0;

// Try to find the actor in our local database first.
$actor_db = $actorRepository->findActor($tmdb_id_param, $actor_name);

if ($actor_db) {
    // Use cached data from the database.
    $tmdb_id = $actor_db['tmdb_id'];
    $actor_name = $actor_db['full_name'];
    $profile_path = $actor_db['profile_path'];
    $bio = $actor_db['bio'];
    $popularity = $actor_db['popularity'];
    
    // If the cached data is over a week old, refresh it from the TMDB API.
    if (Helpers::isOutdated($actor_db['last_updated'], '7 days')) {
        $tmdb_data = $tmdbService->getActorDetails($tmdb_id);
        if ($tmdb_data) {
            $profile_path = $tmdb_data['profile_path'] ?? $profile_path;
            $bio = $tmdb_data['biography'] ?? $bio;
            $popularity = $tmdb_data['popularity'] ?? $popularity;
            
            // Update the local cache.
            $actorRepository->upsert([
                'popularity' => $popularity
            ]);
        }
    }
} else {
    // If the actor isn\'t in our DB, fetch their data from the TMDB API.
    try {
        // Fetch by TMDB ID if provided, otherwise search by name.
        if ($tmdb_id_param > 0) {
            $tmdb_id = $tmdb_id_param;
            $tmdb_data = $tmdbService->getActorDetails($tmdb_id);
        } else {
            $actor = $tmdbService->searchActor($actor_name);
            if (!$actor) {
                header("Location: /searchActor/not-found");
                exit;
            }
            $tmdb_id = $actor['id'];
            $tmdb_data = $tmdbService->getActorDetails($tmdb_id);
        }
        
        if (!$tmdb_data) {
            die('Could not retrieve actor details from TMDB.');
        }
        
        // Check if the actor (by TMDB ID) is already in our database.
        // This can happen if they were added with a different name variation.
        $actor_db = $actorRepository->findByTmdbId($tmdb_id);
        if ($actor_db) {
            // Redirect to the canonical URL to avoid duplicate content.
            header("Location: /actor_profile?tmdb_id=" . $tmdb_id);
            exit;
        }
        
        // Save the new actor\'s data to our database.
        $actor_name = $tmdb_data['name'] ?? '';
        $profile_path = $tmdb_data['profile_path'] ?? '';
        $bio = $tmdb_data['biography'] ?? '';
        $popularity = $tmdb_data['popularity'] ?? '';
        
        $actorRepository->upsert([
            'tmdb_id' => $tmdb_id,
            'full_name' => $actor_name,
            'bio' => $bio,
            'profile_path' => $profile_path,
            'popularity' => $popularity
        ]);
    } catch (PDOException $e) {
        // If the TMDB API fails, fall back to the local database just in case.
        $actor_db = $actorRepository->findByTmdbId($tmdb_id);
        if ($actor_db) {
            $popularity = $actor_db['popularity'];
        } else {
            // If we have no data at all, we can\'t proceed.
            die('Could not retrieve actor information.');
        }
    }
}

// Prepare data for the view.
$tmdb_link = "https://www.themoviedb.org/person/$tmdb_id";
$profile_path = $tmdbService->getProfileImageUrl($profile_path);

// Gather additional data for the profile page.
$awards = $actorRepository->getAwards($actor_name);
$movies = $tmdbService->getActorMovies($tmdb_id, 6); // Get up to 6 popular movies.
$news = NewsService::getActorNews($actor_name);
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
    <?php include '../../src/Views/Components/Navbar.php'; ?>
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
                        <!-- Awards Display Logic -->
                        <div class="awards-section">
                            <h2>Screen Actors Guild Awards</h2>
                            <!-- Awards are pulled from local database -->
                            <!-- Each award shows year, category, and show (if applicable) -->
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
                        
                        <!-- Movie Credits Display -->
                        <div class="movies-section">
                            <h2>Popular Movies</h2>
                            <!-- Movies pulled from TMDB API -->
                            <!-- Shows poster, title, year and character name -->
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

    <?php include '../../src/Views/Components/Footer.php'; ?>
</body>
</html>
