<?php
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

function upsertActor($db, $actor_data) {
    // $actor_data = [full_name, tmdb_id, bio, profile_path, popularity]
    $stmt = $db->prepare("SELECT id FROM actors WHERE tmdb_id = ?");
    $stmt->execute([$actor_data['tmdb_id']]);
    if ($stmt->fetch()) {
        $stmt = $db->prepare("UPDATE actors SET full_name=?, bio=?, profile_path=?, popularity=?, last_updated=CURRENT_TIMESTAMP WHERE tmdb_id=?");
        $stmt->execute([$actor_data['full_name'], $actor_data['bio'], $actor_data['profile_path'], $actor_data['popularity'], $actor_data['tmdb_id']]);
    } else {
        $stmt = $db->prepare("INSERT INTO actors (full_name, tmdb_id, bio, profile_path, popularity, last_updated) VALUES (?, ?, ?, ?, ?, CURRENT_TIMESTAMP)");
        $stmt->execute([$actor_data['full_name'], $actor_data['tmdb_id'], $actor_data['bio'], $actor_data['profile_path'], $actor_data['popularity']]);
    }
}

function getActorAwards($db, $actor_name) {
    $stmt = $db->prepare("SELECT year, category, show FROM awards WHERE UPPER(full_name) = UPPER(?) AND won = 'True'");
    $stmt->execute([$actor_name]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// testing, not working properly
function getConsecutiveNominationYears($db, $actor_name) {
    $stmt = $db->prepare("SELECT DISTINCT year FROM awards WHERE UPPER(full_name) = UPPER(?) ORDER BY year ASC");
    $stmt->execute([$actor_name]);
    $years = array_map('intval', array_column($stmt->fetchAll(PDO::FETCH_ASSOC), 'year'));
    if (empty($years)) {
        return 0;
    }

    $maxStreak = $streak = 1;
    for ($i = 1; $i < count($years); $i++) {
        if ($years[$i] == $years[$i-1] + 1) {
            $streak++;
            if ($streak > $maxStreak) {
                $maxStreak = $streak;
            }
        } else {
            $streak = 1;
        }
    }
    return $maxStreak;
}


