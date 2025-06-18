<?php

namespace ActorAwards\Utils;

class Helpers
{
    /**
     * Check if information from database is outdated
     */
    public static function isOutdated(?string $lastUpdated, string $interval = '1 day'): bool
    {
        if (!$lastUpdated) {
            return true;
        }
        
        return strtotime($lastUpdated) < strtotime("-$interval");
    }
    
    /**
     * Get actor news using Google News RSS
     */
    public static function getActorNews(string $actorName): array
    {
        $rss = @simplexml_load_file('https://news.google.com/rss/search?q=' . urlencode($actorName));
        $news = [];
        
        if ($rss && isset($rss->channel->item)) {
            foreach ($rss->channel->item as $item) {
                $news[] = [
                    'title' => (string)$item->title,
                    'link' => (string)$item->link,
                    'pubDate' => (string)$item->pubDate
                ];
                
                if (count($news) >= 5) {
                    break;
                }
            }
        }
        
        return $news;
    }
    
    /**
     * Sanitize output for HTML
     */
    public static function escape(string $string): string
    {
        return htmlspecialchars($string, ENT_QUOTES, 'UTF-8');
    }
    
    /**
     * Validate email format
     */
    public static function isValidEmail(string $email): bool
    {
        return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    }
    
    /**
     * Generate CSRF token
     */
    public static function generateCsrfToken(): string
    {
        if (!isset($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        
        return $_SESSION['csrf_token'];
    }
    
    /**
     * Verify CSRF token
     */
    public static function verifyCsrfToken(string $token): bool
    {
        return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
    }
}
