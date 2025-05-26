<?php
require_once __DIR__ . '/../config.php';

$db = get_db_connection();

log_message("Începe popularea creditelor actorilor...");

// Obține conexiunile unice actor-producție din tabela de premii
$query = "SELECT DISTINCT a.id as actor_id, p.id as production_id, aw.full_name, aw.show 
          FROM awards aw
          JOIN actors a ON aw.tmdb_actor_id = a.tmdb_id
          JOIN productions p ON aw.tmdb_show_id = p.tmdb_id
          WHERE aw.tmdb_actor_id IS NOT NULL AND aw.tmdb_show_id IS NOT NULL";
$connections = $db->query($query)->fetchAll(PDO::FETCH_ASSOC);

log_message("S-au găsit " . count($connections) . " conexiuni unice actor-producție");

// Șterge creditele existente
$db->exec("DELETE FROM actor_credits");

// Pregătește interogarea pentru inserare
$stmt = $db->prepare("
    INSERT INTO actor_credits 
    (actor_id, production_id, character, credit_type, department, job, order_number) 
    VALUES (?, ?, ?, ?, ?, ?, ?)
");

$count = 0;
$api_calls = 0;
$start_time = microtime(true);

foreach ($connections as $conn) {
    // Verifică dacă actorul și producția există în baza de date
    if (!$conn['actor_id'] || !$conn['production_id']) {
        continue;
    }
    
    // Obține detalii despre actor și producție
    $actorQuery = $db->prepare("SELECT tmdb_id FROM actors WHERE id = ?");
    $actorQuery->execute([$conn['actor_id']]);
    $actorTMDB = $actorQuery->fetchColumn();
    
    $productionQuery = $db->prepare("SELECT tmdb_id, type FROM productions WHERE id = ?");
    $productionQuery->execute([$conn['production_id']]);
    $production = $productionQuery->fetch(PDO::FETCH_ASSOC);
    
    if (!$actorTMDB || !$production) {
        continue;
    }
    
    // În funcție de tipul producției, obținem creditele actorului
    $endpoint = "/movie/" . $production['tmdb_id'] . "/credits";
    if ($production['type'] === 'tv') {
        $endpoint = "/tv/" . $production['tmdb_id'] . "/credits";
    }
    
    // Gestionare rate limiting
    $api_calls++;
    if ($api_calls >= 35) {  // TMDB permite ~40 cereri/10 secunde
        $elapsed = microtime(true) - $start_time;
        if ($elapsed < 10) {
            $sleep_time = max(0.1, (10 - $elapsed) / 10); // Timpul minim de sleep 0.1 secunde
            usleep($sleep_time * 1000000); // Convertire la microsecunde
        }
        $api_calls = 0;
        $start_time = microtime(true);
    } else {
        // Sleep minim între cereri
        usleep(100000); // 0.1 secunde
    }
    
    $credits = tmdb_request($endpoint);
    
    if ($credits && isset($credits['cast'])) {
        $found = false;
        
        // Caută actorul în distribuție
        foreach ($credits['cast'] as $castMember) {
            if ($castMember['id'] == $actorTMDB) {
                $stmt->execute([
                    $conn['actor_id'],
                    $conn['production_id'],
                    $castMember['character'] ?? 'Unknown',
                    'cast',
                    null,
                    null,
                    $castMember['order'] ?? 0
                ]);
                
                $found = true;
                $count++;
                break;
            }
        }
        
        // Dacă nu este în distribuție, verifică și echipa
        if (!$found && isset($credits['crew'])) {
            foreach ($credits['crew'] as $crewMember) {
                if ($crewMember['id'] == $actorTMDB) {
                    $stmt->execute([
                        $conn['actor_id'],
                        $conn['production_id'],
                        null,
                        'crew',
                        $crewMember['department'] ?? null,
                        $crewMember['job'] ?? null,
                        0
                    ]);
                    
                    $count++;
                    break;
                }
            }
        }
        
        // Dacă nu a fost găsit în API, adăugăm o intrare generică
        if (!$found) {
            $stmt->execute([
                $conn['actor_id'],
                $conn['production_id'],
                'Role in ' . $conn['show'],
                'cast',
                null,
                null,
                0
            ]);
            
            $count++;
        }
        
        if ($count % 20 == 0) {
            log_message("Procesate $count credite actor-producție...");
        }
    }
}

log_message("Populare completă! $count credite actor-producție au fost adăugate.");