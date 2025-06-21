<?php

namespace ActorAwards\Services;

class NewsService
{
    /**
     * Fetches recent news articles about an actor from Google News
     * @param string $actorName The actor to search news for
     * @return array List of news items with title, link and publish date
     * @note Only returns up to 5 most recent articles to keep it manageable
     */
    public static function getActorNews(string $actorName): array
    {
        // suppress errors for failed requests and handle them gracefully
        $rss = @simplexml_load_file('https://news.google.com/rss/search?q=' . urlencode($actorName));
        $news = [];
        
        if ($rss && isset($rss->channel->item)) {
            foreach ($rss->channel->item as $item) {
                $news[] = [
                    'title' => (string)$item->title,
                    'link' => (string)$item->link,
                    'pubDate' => (string)$item->pubDate
                ];
                
                // Limit to 8 articles
                if (count($news) >= 8) {
                    break;
                }
            }
        }
        
        return $news;
    }
}
