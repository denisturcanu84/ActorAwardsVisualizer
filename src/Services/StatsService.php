<?php

namespace ActorAwards\Services;
use PDO;
class StatsService
{
    private PDO $db;
    
    public function __construct(PDO $database)
    {
        $this->db = $database;
    }
    
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
    
    public function getTopActors() {
        $query = "SELECT 
            a.full_name as name,
            ac.profile_path as image_url,
            COUNT(*) as nominations,
            SUM(CASE WHEN a.won = 'True' THEN 1 ELSE 0 END) as wins
        FROM awards a
        LEFT JOIN actors ac ON a.tmdb_actor_id = ac.tmdb_id
        WHERE a.full_name IS NOT NULL AND a.full_name <> ''
        GROUP BY a.full_name, ac.profile_path
        ORDER BY wins DESC, nominations DESC
        LIMIT 10";
        
        return $this->db->query($query)->fetchAll(PDO::FETCH_ASSOC);
    }
    
    public function getTopProductions() {
        $query = "SELECT 
            p.title,
            p.poster_path as image_url,
            COUNT(*) as nominations,
            SUM(CASE WHEN a.won = 'True' THEN 1 ELSE 0 END) as wins
        FROM awards a
        JOIN productions p ON a.tmdb_show_id = p.tmdb_id
        GROUP BY p.title, p.poster_path
        ORDER BY wins DESC, nominations DESC
        LIMIT 10";
        
        return $this->db->query($query)->fetchAll(PDO::FETCH_ASSOC);
    }
}
?>