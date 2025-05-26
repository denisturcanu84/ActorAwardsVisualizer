<?php
// FUNCTII CE TIN DE TMDB API


// returneaza actorul cautat folosind numele
function searchActorTmdb($name, $api_key) {
    $url = 'https://api.themoviedb.org/3/search/person?api_key=' . $api_key . '&query=' . urlencode($name);
    $json = @file_get_contents($url);
    $data = $json ? json_decode($json, true) : [];
    return $data['results'][0] ?? null;
}

// returneaza detalii despre actor folosind ID-ul TMDB
function getActorDetailsTmdb($tmdb_id, $api_key) {
    $url = 'https://api.themoviedb.org/3/person/' . $tmdb_id . '?api_key=' . $api_key;
    $json = @file_get_contents($url);
    return $json ? json_decode($json, true) : [];
}


// returneaza URL-ul pentru imaginea de profil a actorului
function getProfileImageUrl($profile_path) {
    return $profile_path ? 'https://image.tmdb.org/t/p/w400' . $profile_path : null;
}

function getActorMovies($tmdb_id, $api_key, $limit = 4) {
    $url = 'https://api.themoviedb.org/3/person/' . $tmdb_id . '/movie_credits?api_key=' . $api_key;
    $json = @file_get_contents($url);
    $data = $json ? json_decode($json, true) : [];

    if (!isset($data['cast'])) {
        return [];
    }

    // sorteaza dupa popularitate si pastreaza doar primele $limit filme
    usort($data['cast'], function($a, $b) {
        return ($b['popularity'] ?? 0) <=> ($a['popularity'] ?? 0);
    });
    
    return array_slice($data['cast'], 0, $limit);
}


// cauta actorul in baza de date folosind ID-ul TMDB sau numele
function findActorInDatabase($db, $tmdb_id = 0, $name = '') {
    if ($tmdb_id > 0) {
        $stmt = $db->prepare("SELECT * FROM actors WHERE tmdb_id = ?");
        $stmt->execute([$tmdb_id]);
        $actor = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($actor) return $actor;
    }
    
    if (!empty($name)) {
        // potrivire exacta
        $stmt = $db->prepare("SELECT * FROM actors WHERE LOWER(full_name) = LOWER(?)");
        $stmt->execute([trim($name)]);
        $actor = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($actor) return $actor;
        
        // potrivire partiala
        $stmt = $db->prepare("SELECT * FROM actors WHERE LOWER(full_name) LIKE LOWER(?)");
        $stmt->execute(['%' . trim($name) . '%']);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    return null;
}
