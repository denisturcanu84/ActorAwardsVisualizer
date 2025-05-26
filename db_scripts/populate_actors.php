<?php
require_once __DIR__ . '/../config.php';

$db = get_db_connection();

log_message("Începe popularea actorilor...");

// Extrage actorii unici din tabela awards care au tmdb_actor_id
$query = "SELECT DISTINCT full_name, tmdb_actor_id FROM awards 
          WHERE tmdb_actor_id IS NOT NULL 
          ORDER BY full_name";
$actors = $db->query($query)->fetchAll(PDO::FETCH_ASSOC);

log_message("S-au găsit " . count($actors) . " actori unici cu ID TMDB");

// Pregătește interogarea pentru inserare
$stmt = $db->prepare("
    INSERT OR IGNORE INTO actors 
    (full_name, tmdb_id, bio, profile_path, popularity, gender, birthday, deathday, 
     place_of_birth, known_for_department, imdb_id, homepage, last_updated) 
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
");

$count = 0;
foreach ($actors as $actor) {
    // Verifică dacă actorul există deja
    $check = $db->prepare("SELECT id FROM actors WHERE tmdb_id = ?");
    $check->execute([$actor['tmdb_actor_id']]);
    if ($check->fetch()) {
        continue;
    }
    
    // Obține detalii despre actor de la TMDB
    $actorDetails = tmdb_request("/person/" . $actor['tmdb_actor_id']);
    
    if ($actorDetails) {
        $stmt->execute([
            $actor['full_name'],
            $actor['tmdb_actor_id'],
            $actorDetails['biography'] ?? null,
            $actorDetails['profile_path'] ?? null,
            $actorDetails['popularity'] ?? null,
            $actorDetails['gender'] ?? null,
            $actorDetails['birthday'] ?? null,
            $actorDetails['deathday'] ?? null,
            $actorDetails['place_of_birth'] ?? null,
            $actorDetails['known_for_department'] ?? null,
            $actorDetails['imdb_id'] ?? null,
            $actorDetails['homepage'] ?? null,
            date('Y-m-d H:i:s')
        ]);
        
        $count++;
        
        if ($count % 10 == 0) {
            log_message("Procesați $count actori...");
        }
        
        // Pentru a evita limitele de rată API
        sleep(1);
    } else {
        log_message("Nu s-au putut obține detalii pentru actorul: " . $actor['full_name']);
    }
}

log_message("Populare completă! $count actori au fost adăugați.");