<?php
require_once __DIR__ . '/../config.php';

$db = get_db_connection();

log_message("Începe calcularea statisticilor...");

// Șterge statisticile existente
$db->exec("DELETE FROM actor_statistics");
$db->exec("DELETE FROM production_statistics");

// Calculează statisticile pentru actori
$db->exec("
    INSERT INTO actor_statistics 
    (actor_id, total_nominations, total_wins, first_nomination_year, 
     last_nomination_year, win_ratio, most_nominated_category, most_won_category, updated_at)
    SELECT 
        a.id as actor_id,
        COUNT(aw.id) as total_nominations,
        SUM(CASE WHEN aw.won = 'True' THEN 1 ELSE 0 END) as total_wins,
        MIN(aw.year) as first_nomination_year,
        MAX(aw.year) as last_nomination_year,
        CASE 
            WHEN COUNT(aw.id) > 0 THEN ROUND((SUM(CASE WHEN aw.won = 'True' THEN 1 ELSE 0 END) * 100.0 / COUNT(aw.id)), 2)
            ELSE 0 
        END as win_ratio,
        (
            SELECT category FROM awards 
            WHERE tmdb_actor_id = a.tmdb_id 
            GROUP BY category 
            ORDER BY COUNT(*) DESC LIMIT 1
        ) as most_nominated_category,
        (
            SELECT category FROM awards 
            WHERE tmdb_actor_id = a.tmdb_id AND won = 'True'
            GROUP BY category 
            ORDER BY COUNT(*) DESC LIMIT 1
        ) as most_won_category,
        CURRENT_TIMESTAMP
    FROM actors a
    JOIN awards aw ON a.tmdb_id = aw.tmdb_actor_id
    GROUP BY a.id
");

// Calculează statisticile pentru producții
$db->exec("
    INSERT INTO production_statistics 
    (production_id, total_nominations, total_wins, most_nominated_category, most_won_category, updated_at)
    SELECT 
        p.id as production_id,
        COUNT(aw.id) as total_nominations,
        SUM(CASE WHEN aw.won = 'True' THEN 1 ELSE 0 END) as total_wins,
        (
            SELECT category FROM awards 
            WHERE tmdb_show_id = p.tmdb_id 
            GROUP BY category 
            ORDER BY COUNT(*) DESC LIMIT 1
        ) as most_nominated_category,
        (
            SELECT category FROM awards 
            WHERE tmdb_show_id = p.tmdb_id AND won = 'True'
            GROUP BY category 
            ORDER BY COUNT(*) DESC LIMIT 1
        ) as most_won_category,
        CURRENT_TIMESTAMP
    FROM productions p
    JOIN awards aw ON p.tmdb_id = aw.tmdb_show_id
    GROUP BY p.id
");

log_message("Statistici calculate cu succes!");
