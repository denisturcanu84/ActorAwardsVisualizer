<?php

namespace ActorAwards\Services;
use PDO;
use ActorAwards\Repositories\ActorRepository;
use ActorAwards\Repositories\ProductionRepository;

/**
 * Handles statistical calculations for awards data.
 */
class StatsService
{
    private PDO $db;
    private TmdbService $tmdbService;
    private ActorRepository $actorRepository;
    private ProductionRepository $productionRepository;
    
    /**
     * here it initializes the service with necessary dependencies.
     * It requires services for database access and TMDB API communication,
     * as well as repositories to update local actor and production data.
     */
    public function __construct(PDO $database, TmdbService $tmdbService, ActorRepository $actorRepository, ProductionRepository $productionRepository)
    {
        $this->db = $database;
        $this->tmdbService = $tmdbService;
        $this->actorRepository = $actorRepository;
        $this->productionRepository = $productionRepository;
    }
    
    /**
     * Gets yearly award statistics from 1990-2020
     * Calculates: total wins, total nominations, and win rate per year
     * Win rate is calculated as (wins/nominations)*100 rounded to 1 decimal
     */
    public function getYearlyStats() {
        return $this->db->query("
            SELECT
                year,
                SUM(CASE WHEN won = 'True' THEN 1 ELSE 0 END) as total_wins,
                COUNT(*) as total_nominations,
                ROUND(SUM(CASE WHEN won = 'True' THEN 1 ELSE 0 END) * 100.0 / COUNT(*), 1) as win_rate
            FROM awards
            WHERE SUBSTR(year, 1, 4) LIKE '____'
                AND CAST(SUBSTR(year, 1, 4) AS INTEGER) >= 1990
                AND CAST(SUBSTR(year, 1, 4) AS INTEGER) <= 2020
            GROUP BY year
            ORDER BY year ASC
        ")->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Gets statistics by award category
     * Shows: total wins, nominations and win rate per category
     * Ordered by most nominated categories first
     */
    public function getCategoryStats() {
        return $this->db->query("
            SELECT
                category,
                SUM(CASE WHEN won = 'True' THEN 1 ELSE 0 END) as total_wins,
                COUNT(*) as total_nominations,
                ROUND(SUM(CASE WHEN won = 'True' THEN 1 ELSE 0 END) * 100.0 / COUNT(*), 1) as win_rate
            FROM awards 
            GROUP BY category 
            ORDER BY total_nominations DESC
        ")->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Gets top 10 most awarded actors.
     * Includes: actor name, profile image, total nominations, and wins.
     * If a profile image is missing locally, it fetches it from TMDB and updates the database.
     * Ordered by most wins first, then nominations as a tiebreaker.
     */
    public function getTopActors() {
        $query = "SELECT
            a.full_name as name,
            a.tmdb_actor_id,
            ac.profile_path as image_url,
            COUNT(*) as nominations,
            SUM(CASE WHEN a.won = 'True' THEN 1 ELSE 0 END) as wins
        FROM awards a
        LEFT JOIN actors ac ON a.tmdb_actor_id = ac.tmdb_id
        WHERE a.full_name IS NOT NULL AND a.full_name <> '' AND a.tmdb_actor_id IS NOT NULL
        GROUP BY a.full_name, ac.profile_path, a.tmdb_actor_id
        ORDER BY wins DESC, nominations DESC
        LIMIT 10";
        
        $actors = $this->db->query($query)->fetchAll(PDO::FETCH_ASSOC);

        foreach ($actors as &$actor) {
            if (empty($actor['image_url'])) {
                $tmdbData = $this->tmdbService->getActorDetails($actor['tmdb_actor_id']);
                if ($tmdbData && !empty($tmdbData['profile_path'])) {
                    $actor['image_url'] = $tmdbData['profile_path'];
                    $this->actorRepository->upsert([
                        'tmdb_id' => $actor['tmdb_actor_id'],
                        'full_name' => $actor['name'],
                        'profile_path' => $tmdbData['profile_path'],
                        'bio' => $tmdbData['biography'] ?? '',
                        'popularity' => $tmdbData['popularity'] ?? 0,
                    ]);
                }
            }
        }

        return $actors;
    }
    
    /**
     * Gets top 10 most awarded productions (movies/TV shows).
     * Includes: title, poster image, nominations, and wins.
     * If a poster image is missing locally, it fetches it from TMDB and updates the database.
     * Ordered by most wins first, then nominations as a tiebreaker.
     */
    public function getTopProductions() {
        $query = "SELECT
            p.title,
            a.tmdb_show_id,
            p.poster_path as image_url,
            COUNT(*) as nominations,
            SUM(CASE WHEN a.won = 'True' THEN 1 ELSE 0 END) as wins
        FROM awards a
        JOIN productions p ON a.tmdb_show_id = p.tmdb_id
        WHERE a.tmdb_show_id IS NOT NULL
        GROUP BY p.title, p.poster_path, a.tmdb_show_id
        ORDER BY wins DESC, nominations DESC
        LIMIT 10";
        
        $productions = $this->db->query($query)->fetchAll(PDO::FETCH_ASSOC);

        foreach ($productions as &$production) {
            if (empty($production['image_url'])) {
                $tmdbData = $this->tmdbService->getMovieDetails($production['tmdb_show_id']);
                if ($tmdbData && !empty($tmdbData['poster_path'])) {
                    $production['image_url'] = $tmdbData['poster_path'];
                    $this->productionRepository->upsert([
                        'tmdb_id' => $production['tmdb_show_id'],
                        'title' => $production['title'],
                        'poster_path' => $tmdbData['poster_path'],
                    ]);
                }
            }
        }

        return $productions;
    }
}
?>