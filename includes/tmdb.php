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

function getActorNews($actor_name) {
    $rss = @simplexml_load_file('https://news.google.com/rss/search?q=' . urlencode($actor_name));
    $news = [];
    if ($rss && isset($rss->channel->item)) {
        foreach ($rss->channel->item as $item) {
            $news[] = [
                'title' => (string)$item->title,
                'link' => (string)$item->link,
                'pubDate' => (string)$item->pubDate
            ];
            if (count($news) >= 5) break;
        }
    }
    return $news;
}

function getActorMovies($tmdb_id, $api_key, $limit = 6) {
    $url = 'https://api.themoviedb.org/3/person/' . $tmdb_id . '/movie_credits?api_key=' . $api_key;
    $json = @file_get_contents($url);
    $data = $json ? json_decode($json, true) : [];

    if (!isset($data['cast'])) {
        return [];
    }

    // sortează după popularitate și ia primele filme
    usort($data['cast'], function($a, $b) {
        return ($b['popularity'] ?? 0) <=> ($a['popularity'] ?? 0);
    });
    
    return array_slice($data['cast'], 0, $limit);
}