<?php
$db = new PDO('sqlite:' . __DIR__ . '/../database/app.db');
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

if (($f = fopen(__DIR__ . '/../csv/screen_actor_guild_awards_updated.csv', 'r')) !== false) {
    fgetcsv($f, 1000, ',');
    $stmt = $db->prepare("INSERT INTO awards (year, category, full_name, show, won, tmdb_actor_id, tmdb_show_id) VALUES (?, ?, ?, ?, ?, ?, ?)");
    while (($row = fgetcsv($f, 1000, ',')) !== false) {
        if (isset($row[5]) && $row[5] === '') $row[5] = null;
        if (isset($row[6]) && $row[6] === '') $row[6] = null;
        
        $stmt->execute($row);
    }
    fclose($f);
    echo "Import complete!";
} else {
    echo "CSV not found!";
}
