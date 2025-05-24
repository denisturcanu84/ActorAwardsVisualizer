<?php
$db = new PDO('sqlite:' . __DIR__ . '/../database/app.db');
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

if (($f = fopen(__DIR__ . '/../csv/screen_actor_guild_awards.csv', 'r')) !== false) {
    fgetcsv($f, 1000, ','); // skip header
    $stmt = $db->prepare("INSERT INTO awards (year, category, full_name, show, won) VALUES (?, ?, ?, ?, ?)");
    while (($row = fgetcsv($f, 1000, ',')) !== false) {
        $stmt->execute($row);
    }
    fclose($f);
    echo "Import complete!";
} else {
    echo "CSV not found!";
}
