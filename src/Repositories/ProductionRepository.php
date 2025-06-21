<?php

namespace ActorAwards\Repositories;

use ActorAwards\Services\TmdbService;
use PDO;
use PDOException;

/**
 * Repository for handling production data, interacting with the database and TMDB service.
 * Manages creating, finding, and updating production records, including fetching missing images.
 */
class ProductionRepository
{
    private PDO $db;
    private ?TmdbService $tmdbService;

    private const TMDB_ID_PARAM = ':tmdb_id';

    /**
     * Sets up the repository with a database connection and optional TMDB service.
     * @param PDO $db The database connection.
     * @param TmdbService|null $tmdbService Service for fetching data from TMDB.
     */
    public function __construct(PDO $db, ?TmdbService $tmdbService = null)
    {
        $this->db = $db;
        $this->tmdbService = $tmdbService;
    }

    /**
     * Finds a production by its TMDB ID.
     * @return array|null Production data or null if not found.
     */
    public function findByTmdbId(int $tmdbId): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM productions WHERE tmdb_id = ' . self::TMDB_ID_PARAM);
        $stmt->execute([self::TMDB_ID_PARAM => $tmdbId]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ?: null;
    }

    /**
     * Creates or updates a production record from TMDB data.
     * This keeps the local database synchronized with external information.
     */
    public function upsert(array $productionData): void
    {
        $sql = $this->findByTmdbId($productionData['tmdb_id'])
            ? 'UPDATE productions SET title = :title, poster_path = :poster_path, last_updated = CURRENT_TIMESTAMP WHERE tmdb_id = ' . self::TMDB_ID_PARAM
            : 'INSERT INTO productions (tmdb_id, title, poster_path, last_updated) VALUES (' . self::TMDB_ID_PARAM . ', :title, :poster_path, CURRENT_TIMESTAMP)';

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            self::TMDB_ID_PARAM => $productionData['tmdb_id'],
            ':title' => $productionData['title'],
            ':poster_path' => $productionData['poster_path'],
        ]);
    }

    /**
     * Builds the SQL query and parameters for fetching filtered productions.
     * @param array $filters The filters to apply.
     * @return array An array containing the 'sql' query string and 'params' array.
     */
    private function buildFilteredProductionsQuery(array $filters): array
    {
        $sql = "SELECT p.id, p.tmdb_id, p.title as production_title, p.poster_path,
                       COUNT(DISTINCT a.id) as award_count,
                       COUNT(DISTINCT CASE WHEN a.won = 'True' THEN a.id END) as won_count,
                       GROUP_CONCAT(DISTINCT a.category) as categories,
                       GROUP_CONCAT(DISTINCT a.year) as years
                FROM productions p
                LEFT JOIN awards a ON p.tmdb_id = a.tmdb_show_id
                WHERE 1=1";
        $params = [];

        if (!empty($filters['year'])) {
            $sql .= " AND a.year = ?";
            $params[] = $filters['year'];
        }
        if (!empty($filters['category'])) {
            $sql .= " AND a.category = ?";
            $params[] = $filters['category'];
        }
        if (isset($filters['result'])) {
            $sql .= " AND a.won = ?";
            $params[] = $filters['result'];
        }
        if (!empty($filters['search'])) {
            $sql .= " AND p.title LIKE ?";
            $params[] = "%{$filters['search']}%";
        }

        $sql .= " GROUP BY p.id, p.tmdb_id, p.title, p.poster_path ORDER BY won_count DESC, award_count DESC";
        return ['sql' => $sql, 'params' => $params];
    }

    /**
     * Fetches missing poster images for a list of productions using the TMDB service.
     * @param array $productions A list of productions to check.
     * @param array $filters The original filters, used to provide context (like the year).
     */
    private function fetchMissingPosters(array &$productions, array $filters): void
    {
        if (!$this->tmdbService) {
            return;
        }

        foreach ($productions as &$production) {
            if (empty($production['poster_path'])) {
                $year = $filters['year'] ?? (!empty($production['years']) ? explode(',', $production['years'])[0] : null);
                $tmdbInfo = $this->tmdbService->findProduction($production['production_title'], $year);
                if ($tmdbInfo && !empty($tmdbInfo['poster_path'])) {
                    $this->updateProductionPoster($production['id'], $tmdbInfo['poster_path'], $tmdbInfo['id']);
                    $production['poster_path'] = $tmdbInfo['poster_path'];
                }
            }
        }
    }

    /**
     * Retrieves productions based on filters and fetches missing images.
     * @param array $filters An associative array of filters.
     * @param int|null $limit
     * @param int|null $offset
     * @return array A list of productions.
     */
    public function getFilteredProductions(array $filters, ?int $limit = null, ?int $offset = null): array
    {
        ['sql' => $sql, 'params' => $params] = $this->buildFilteredProductionsQuery($filters);

        if ($limit !== null && $offset !== null) {
            $sql .= " LIMIT ? OFFSET ?";
            $params[] = $limit;
            $params[] = $offset;
        }

        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            $productions = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $this->fetchMissingPosters($productions, $filters);
            return $productions;
        } catch (PDOException $e) {
            error_log("Database error in getFilteredProductions: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Counts the total number of productions matching the filters.
     * @param array $filters
     * @return int
     */
    public function countFilteredProductions(array $filters): int
    {
        ['sql' => $sql, 'params' => $params] = $this->buildFilteredProductionsQuery($filters);

        $orderByPos = strripos($sql, ' ORDER BY ');
        if ($orderByPos !== false) {
            $sql = substr($sql, 0, $orderByPos);
        }

        $countSql = "SELECT COUNT(*) FROM ({$sql}) AS subquery";

        try {
            $stmt = $this->db->prepare($countSql);
            $stmt->execute($params);
            return (int)$stmt->fetchColumn();
        } catch (PDOException $e) {
            error_log("Database error in countFilteredProductions: " . $e->getMessage());
            return 0;
        }
    }

    /**
     * Updates the poster path and TMDB ID for a production.
     * @return bool True on success, false on failure.
     */
    public function updateProductionPoster(int $productionId, string $posterPath, int $tmdbId): bool
    {
        $sql = "UPDATE productions SET poster_path = :poster_path, tmdb_id = :tmdb_id WHERE id = :id";
        try {
            $stmt = $this->db->prepare($sql);
            return $stmt->execute([
                ':poster_path' => $posterPath,
                self::TMDB_ID_PARAM => $tmdbId,
                ':id' => $productionId
            ]);
        } catch (PDOException $e) {
            error_log("Failed to update production poster: " . $e->getMessage());
            return false;
        }
    }

    public function getUniqueAwardYears(): array
    {
        $query = "SELECT DISTINCT year FROM awards ORDER BY year DESC";
        return $this->db->query($query)->fetchAll(PDO::FETCH_COLUMN);
    }

    public function getUniqueAwardCategories(): array
    {
        $query = "SELECT DISTINCT category FROM awards ORDER BY category";
        return $this->db->query($query)->fetchAll(PDO::FETCH_COLUMN);
    }
}
