<?php

namespace ActorAwards\Repositories;

use PDO;

/**
 * Repository class for actor data operations
 *
 * This follows the Repository pattern which acts like a middleman between the app and database
 */
class ActorRepository
{
    private PDO $db;
    
    public function __construct(PDO $database)
    {
        $this->db = $database;
    }
    
    /**
     * Finds an actor by their TMDB ID
     * Returns either the actor data or null if not found.
     */
    public function findByTmdbId(int $tmdbId): ?array
    {
        $stmt = $this->db->prepare("SELECT * FROM actors WHERE tmdb_id = ?");
        $stmt->execute([$tmdbId]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }
    
    /**
     * Flexible actor search by either TMDB ID or name
     *
     * This tries multiple ways to find an actor:
     * 1. First checks by TMDB ID if provided (fastest)
     * 2. Then tries exact name match
     * 3. Finally does partial name search if needed
     */
    public function findActor(int $tmdbId = 0, string $name = ''): ?array
    {
        if ($tmdbId > 0) {
            $stmt = $this->db->prepare("SELECT * FROM actors WHERE tmdb_id = ?");
            $stmt->execute([$tmdbId]);
            $actor = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($actor) { return $actor; }
        }
        
        if (!empty($name)) {
            // Exact match
            $stmt = $this->db->prepare("SELECT * FROM actors WHERE LOWER(full_name) = LOWER(?)");
            $stmt->execute([trim($name)]);
            $actor = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($actor) { return $actor; }
            
            // Partial match
            $stmt = $this->db->prepare("SELECT * FROM actors WHERE LOWER(full_name) LIKE LOWER(?)");
            $stmt->execute(['%' . trim($name) . '%']);
            return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
        }
        
        return null;
    }
    
    /**
     * Creates or updates an actor record (upsert operation)
     *
     * First checks if actor exists by TMDB ID, then either:
     * - Updates existing record if found
     * - Inserts new record if not found
     */
    public function upsert(array $actorData): void
    {
        $stmt = $this->db->prepare("SELECT id FROM actors WHERE tmdb_id = ?");
        $stmt->execute([$actorData['tmdb_id']]);
        
        if ($stmt->fetch()) {
            $stmt = $this->db->prepare("
                UPDATE actors
                SET full_name=?, bio=?, profile_path=?, popularity=?, last_updated=CURRENT_TIMESTAMP 
                WHERE tmdb_id=?
            ");
            $stmt->execute([
                $actorData['full_name'],
                $actorData['bio'],
                $actorData['profile_path'],
                $actorData['popularity'],
                $actorData['tmdb_id']
            ]);
        } else {
            $stmt = $this->db->prepare("
                INSERT INTO actors (full_name, tmdb_id, bio, profile_path, popularity, last_updated) 
                VALUES (?, ?, ?, ?, ?, CURRENT_TIMESTAMP)
            ");
            $stmt->execute([
                $actorData['full_name'],
                $actorData['tmdb_id'],
                $actorData['bio'],
                $actorData['profile_path'],
                $actorData['popularity']
            ]);
        }
    }
    

    public function getAwards(string $actorName): array
    {
        $stmt = $this->db->prepare("
            SELECT year, category, show
            FROM awards
            WHERE UPPER(full_name) = UPPER(?) AND won = 'True'
        ");
        $stmt->execute([$actorName]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
