<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/tmdb.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->load();
$api_key = $_ENV['TMDB_API_KEY'] ?? '';

if (!isset($_GET['name']) && !isset($_GET['tmdb_id'])) {
    die('Actor not specified.');
}

$actor_name = isset($_GET['name']) ? trim($_GET['name']) : '';
$tmdb_id_param = isset($_GET['tmdb_id']) ? intval($_GET['tmdb_id']) : 0;

$db = getDbConnection();

// Prima dată verificăm dacă actorul există deja în baza de date (verificăm și după tmdb_id acum)
$actor_exists = false;
$actor_db = null;

// Dacă avem un TMDB ID, îl căutăm direct
if ($tmdb_id_param > 0) {
    $actor_query = $db->prepare("SELECT * FROM actors WHERE tmdb_id = ?");
    $actor_query->execute([$tmdb_id_param]);
    $actor_db = $actor_query->fetch(PDO::FETCH_ASSOC);
    if ($actor_db) {
        $actor_exists = true;
    }
}

// Dacă nu am găsit după ID, căutăm după nume
if (!$actor_exists && !empty($actor_name)) {
    // Încercăm întâi o potrivire exactă
    $actor_query = $db->prepare("SELECT * FROM actors WHERE LOWER(full_name) = LOWER(?)");
    $actor_query->execute([trim($actor_name)]);
    $actor_db = $actor_query->fetch(PDO::FETCH_ASSOC);
    
    // Dacă nu găsim, încercăm o potrivire parțială
    if (!$actor_db) {
        $actor_query = $db->prepare("SELECT * FROM actors WHERE LOWER(full_name) LIKE LOWER(?)");
        $actor_query->execute(['%' . trim($actor_name) . '%']);
        $actor_db = $actor_query->fetch(PDO::FETCH_ASSOC);
    }
    
    if ($actor_db) {
        $actor_exists = true;
    }
}

// Dacă actorul există în baza de date, folosim datele existente
if ($actor_exists && $actor_db) {
    $tmdb_id = $actor_db['tmdb_id'];
    $actor_name = $actor_db['full_name'];
    $profile_path = $actor_db['profile_path'];
    $bio = $actor_db['bio'];
    $popularity = $actor_db['popularity'];
    
    // Verificăm dacă datele sunt vechi și necesită actualizare
    if (isOutdated($actor_db['last_updated'], '7 days')) {
        // Actualizează doar la 7 zile
        $tmdb_data = getActorDetailsTmdb($tmdb_id, $api_key);
        if ($tmdb_data) {
            $profile_path = $tmdb_data['profile_path'] ?? $profile_path;
            $bio = $tmdb_data['biography'] ?? $bio;
            $popularity = $tmdb_data['popularity'] ?? $popularity;
            
            // Actualizare în baza de date, dar mai rar
            $update = $db->prepare("UPDATE actors SET bio=?, profile_path=?, popularity=?, last_updated=CURRENT_TIMESTAMP WHERE tmdb_id=?");
            $update->execute([$bio, $profile_path, $popularity, $tmdb_id]);
        }
    }
} else {
    // Dacă actorul nu există în baza de date, facem o căutare TMDB
    if ($tmdb_id_param > 0) {
        // Dacă avem deja un ID TMDB, folosim direct
        $tmdb_id = $tmdb_id_param;
        $tmdb_data = getActorDetailsTmdb($tmdb_id, $api_key);
    } else {
        // Altfel căutăm după nume
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
    
    // Verificăm acum dacă există deja un actor cu acest TMDB ID în baza de date
    $check_query = $db->prepare("SELECT id FROM actors WHERE tmdb_id = ?");
    $check_query->execute([$tmdb_id]);
    $existing = $check_query->fetch(PDO::FETCH_ASSOC);
    
    if ($existing) {
        // Actorul există deja, redirecționăm la pagina cu ID-ul corect
        header("Location: actor_profile.php?tmdb_id=" . $tmdb_id);
        exit;
    }
    
    $actor_name = $tmdb_data['name'] ?? '';
    $profile_path = $tmdb_data['profile_path'] ?? '';
    $bio = $tmdb_data['biography'] ?? '';
    $popularity = $tmdb_data['popularity'] ?? '';
    
    // Doar pentru actori noi, îi adăugăm în baza de date
    try {
        $insert = $db->prepare("INSERT INTO actors (full_name, tmdb_id, bio, profile_path, popularity, last_updated) VALUES (?, ?, ?, ?, ?, CURRENT_TIMESTAMP)");
        $insert->execute([$actor_name, $tmdb_id, $bio, $profile_path, $popularity]);
    } catch (PDOException $e) {
        // Dacă inserarea eșuează, poate că actorul există deja (race condition)
        // Încercăm să îl obținem din nou
        $actor_query = $db->prepare("SELECT * FROM actors WHERE tmdb_id = ?");
        $actor_query->execute([$tmdb_id]);
        $actor_db = $actor_query->fetch(PDO::FETCH_ASSOC);
        
        if ($actor_db) {
            $actor_name = $actor_db['full_name'];
            $profile_path = $actor_db['profile_path'];
            $bio = $actor_db['bio'];
            $popularity = $actor_db['popularity'];
        } else {
            die('Error adding actor to database: ' . $e->getMessage());
        }
    }
}

$tmdb_link = "https://www.themoviedb.org/person/$tmdb_id";
$profile_path = getProfileImageUrl($profile_path);

$awards = getActorAwards($db, $actor_name);
$consecutive = getConsecutiveNominationYears($db, $actor_name);
$movies = getActorMovies($tmdb_id, $api_key, 4);
$news = getActorNews($actor_name);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title><?php echo htmlspecialchars($actor_name); ?> - Actor Profile</title>
    <meta name="viewport" content="width=device-width,initial-scale=1.0">
    <link rel="stylesheet" href="../assets/css/actor_profile.css">
    <link rel="stylesheet" href="../assets/css/navbar.css">
    <link rel="stylesheet" href="../assets/css/footer.css">
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
    <?php include '../includes/footer.php'; ?>
</body>
</html>
