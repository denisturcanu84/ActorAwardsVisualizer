<?php
// FUNCTII UTILE PENTRU APLICATIE

// verifica daca informatiile din db sunt mai vechi
function isOutdated($last_updated, $interval = '1 day') {
    if (!$last_updated){
        return true;
    }
    return strtotime($last_updated) < strtotime("-$interval");
}

// foloseste Google News RSS pentru a obtine ultimele stiri despre actor
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