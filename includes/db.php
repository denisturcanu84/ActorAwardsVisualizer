<?php
// FUNCTII CE TIN DE BAZA DE DATE


function getDbConnection($path = null) {
    $dbPath = $path ?? (__DIR__ . '/../database/app.db');
    $db = new PDO('sqlite:' . $dbPath);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    return $db;
}

function findActorByTmdbId($db, $tmdb_id) {
    $stmt = $db->prepare("SELECT * FROM actors WHERE tmdb_id = ?");
    $stmt->execute([$tmdb_id]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}


function getActorAwards($db, $actor_name) {
    $stmt = $db->prepare("SELECT year, category, show FROM awards WHERE UPPER(full_name) = UPPER(?) AND won = 'True'");
    $stmt->execute([$actor_name]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}



