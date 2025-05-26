<?php
require_once __DIR__ . '/../config.php';

$db = get_db_connection();

// Verifică dacă fișierul CSV există
if (!file_exists(CSV_PATH)) {
    die("Eroare: Fișierul CSV nu a fost găsit la calea " . CSV_PATH);
}

log_message("Începe importul premiilor din CSV...");

// Șterge datele existente (opțional)
$db->exec("DELETE FROM awards");

// Deschide fișierul CSV
if (($f = fopen(CSV_PATH, 'r')) !== false) {
    // Sari peste antet
    fgetcsv($f, 1000, ',');
    
    // Pregătește interogarea pentru inserare
    $stmt = $db->prepare("INSERT INTO awards (year, category, full_name, show, won, tmdb_actor_id, tmdb_show_id) VALUES (?, ?, ?, ?, ?, ?, ?)");
    
    $count = 0;
    while (($row = fgetcsv($f, 1000, ',')) !== false) {
        // Convertește coloanele tmdb_id goale în NULL
        if (isset($row[5]) && $row[5] === '') $row[5] = null;
        if (isset($row[6]) && $row[6] === '') $row[6] = null;
        
        $stmt->execute($row);
        $count++;
        
        if ($count % 100 == 0) {
            log_message("Procesate $count înregistrări...");
        }
    }
    
    fclose($f);
    log_message("Import complet! $count premii au fost importate.");
} else {
    log_message("Eroare: Nu s-a putut deschide fișierul CSV!");
}