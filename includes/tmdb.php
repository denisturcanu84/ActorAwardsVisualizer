<?php

function searchActorTmdb($name, $api_key) {
    $url = 'https://api.themoviedb.org/3/search/person?api_key=' . $api_key . '&query=' . urlencode($name);
    $json = @file_get_contents($url);
    $data = $json ? json_decode($json, true) : [];
    return $data['results'][0] ?? null;
}

function getActorDetailsTmdb($tmdb_id, $api_key) {
    $url = 'https://api.themoviedb.org/3/person/' . $tmdb_id . '?api_key=' . $api_key;
    $json = @file_get_contents($url);
    return $json ? json_decode($json, true) : [];
}

function getProfileImageUrl($profile_path) {
    return $profile_path ? 'https://image.tmdb.org/t/p/w400' . $profile_path : null;
}
