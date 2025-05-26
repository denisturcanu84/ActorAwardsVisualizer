<?php
require_once __DIR__ . '/../config.php';

$db = get_db_connection();

log_message("Începe popularea producțiilor...");

// Extrage producțiile unice din tabela awards care au tmdb_show_id
$query = "SELECT DISTINCT show as title, tmdb_show_id, year as release_year FROM awards 
          WHERE tmdb_show_id IS NOT NULL 
          ORDER BY show";
$productions = $db->query($query)->fetchAll(PDO::FETCH_ASSOC);

log_message("S-au găsit " . count($productions) . " producții unice cu ID TMDB");

// Pregătește interogarea pentru inserare
$stmt = $db->prepare("
    INSERT OR IGNORE INTO productions 
    (title, tmdb_id, release_year, poster_path, type, overview, original_language, 
     popularity, vote_average, vote_count, runtime, genres, last_updated) 
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
");

$count = 0;
foreach ($productions as $production) {
    // Verifică dacă producția există deja
    $check = $db->prepare("SELECT id FROM productions WHERE tmdb_id = ?");
    $check->execute([$production['tmdb_show_id']]);
    if ($check->fetch()) {
        continue;
    }
    
    // Încearcă să obții detalii despre film
    $details = tmdb_request("/movie/" . $production['tmdb_show_id']);
    $type = 'movie';
    
    // Dacă nu e film, încearcă ca serial TV
    if (!$details || isset($details['success']) && $details['success'] === false) {
        $details = tmdb_request("/tv/" . $production['tmdb_show_id']);
        $type = 'tv';
    }
    
    if ($details && !isset($details['success'])) {
        // Procesează genurile ca JSON
        $genres = [];
        if (isset($details['genres']) && is_array($details['genres'])) {
            foreach ($details['genres'] as $genre) {
                $genres[] = $genre['name'];
            }
        }
        
        // Pentru seriale TV, folosește first_air_date în loc de release_date
        $releaseYear = $production['release_year'];
        if ($type === 'tv' && isset($details['first_air_date']) && !empty($details['first_air_date'])) {
            $releaseYear = substr($details['first_air_date'], 0, 4);
        } elseif ($type === 'movie' && isset($details['release_date']) && !empty($details['release_date'])) {
            $releaseYear = substr($details['release_date'], 0, 4);
        }
        
        $stmt->execute([
            $production['title'],
            $production['tmdb_show_id'],
            $releaseYear,
            $details['poster_path'] ?? null,
            $type,
            $details['overview'] ?? null,
            $details['original_language'] ?? null,
            $details['popularity'] ?? null,
            $details['vote_average'] ?? null,
            $details['vote_count'] ?? null,
            $details['runtime'] ?? ($details['episode_run_time'][0] ?? null),
            json_encode($genres),
            date('Y-m-d H:i:s')
        ]);
        
        $count++;
        
        if ($count % 10 == 0) {
            log_message("Procesate $count producții...");
        }
        
        // Pentru a evita limitele de rată API
        sleep(1);
    } else {
        log_message("Nu s-au putut obține detalii pentru producția: " . $production['title']);
    }
}

log_message("Populare completă! $count producții au fost adăugate.");