<?php

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/tmdb.php';
require_once __DIR__ . '/functions.php';

class NominationsHandler {
    private $db;
    private $api_key;
    
    public function __construct() {
        // use existing function
        $this->db = getDbConnection();
        $this->api_key = TMDB_API_KEY;
    }
    
    // get filter data from request
    public function getFilters() {
        return [
            'year' => $_GET['year'] ?? $_POST['year'] ?? '',
            'cat' => $_GET['category'] ?? $_POST['category'] ?? '',
            'result' => $_GET['result'] ?? $_POST['result'] ?? '',
            'search' => $_GET['search'] ?? $_POST['search'] ?? '',
            'page' => isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1
        ];
    }
    
    // handle form submission redirect
    public function handleFormSubmission() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $params = [];
            if (!empty($_POST['year'])) { $params['year'] = $_POST['year']; }
            if (!empty($_POST['category'])) { $params['category'] = $_POST['category']; }
            if (!empty($_POST['result'])) { $params['result'] = $_POST['result']; }
            if (!empty($_POST['search'])) { $params['search'] = $_POST['search']; }
            
            $url = 'nominations';
            if (!empty($params)) {
                $url .= '?' . http_build_query($params);
            }
            
            header('Location: ' . $url);
            exit;
        }
    }
    
    // build where conditions separately
    private function buildWhereConditions($filters) {
        $year = $filters['year'];
        $cat = $filters['cat'];
        $result = $filters['result'];
        $search = $filters['search'];
        
        // convert won/nominated to true/false
        $won = null;
        if ($result === 'Won') {
            $won = 'True';
        } elseif ($result === 'Nominated') {
            $won = 'False';
        }
        
        $conditions = "WHERE a.full_name IS NOT NULL AND a.full_name <> ''";
        $values = [];
        
        // add filters
        if ($year) {
            $conditions .= " AND a.year = ?";
            $values[] = $year;
        }
        
        if ($cat) {
            $conditions .= " AND a.category = ?";
            $values[] = $cat;
        }
        
        if ($won !== null) {
            $conditions .= " AND a.won = ?";
            $values[] = $won;
        }
        
        if ($search) {
            $conditions .= " AND (a.full_name LIKE ? OR p.title LIKE ?)";
            $values[] = "%$search%";
            $values[] = "%$search%";
        }
        
        return ['conditions' => $conditions, 'values' => $values];
    }
    
    // get nominations data with pagination
    public function getNominations($filters, $limit = 10) {
        $page = $filters['page'];
        
        // build where conditions
        $whereData = $this->buildWhereConditions($filters);
        $conditions = $whereData['conditions'];
        $values = $whereData['values'];
        
        // get total count first
        $countSql = "SELECT COUNT(*) 
                     FROM awards a 
                     LEFT JOIN productions p ON a.tmdb_show_id = p.tmdb_id 
                     $conditions";
        
        $stmt = $this->db->prepare($countSql);
        $stmt->execute($values);
        $total = (int)$stmt->fetchColumn();
        $totalPages = (int)ceil($total / $limit);
        
        // build main query
        $sql = "SELECT a.*,
                       a.full_name,
                       ac.profile_path,
                       ac.tmdb_id AS actor_tmdb_id,
                       p.title as production_title,
                       p.poster_path AS local_db_poster_path,
                       p.tmdb_id AS production_tmdb_id,
                       p.type AS production_type
                FROM awards a
                LEFT JOIN actors ac ON a.tmdb_actor_id = ac.tmdb_id
                LEFT JOIN productions p ON a.tmdb_show_id = p.tmdb_id
                $conditions
                ORDER BY a.year DESC, a.category 
                LIMIT ? OFFSET ?";
        
        // add pagination to values
        $paginationValues = $values;
        $paginationValues[] = $limit;
        $paginationValues[] = ($page - 1) * $limit;
        
        // get data
        $stmt = $this->db->prepare($sql);
        $stmt->execute($paginationValues);
        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // fetch missing images from tmdb using existing functions
        $data = $this->fetchMissingImages($data);
        
        return [
            'data' => $data,
            'total' => $total,
            'totalPages' => $totalPages,
            'currentPage' => $page
        ];
    }
    
    // fetch missing images
    private function fetchMissingImages($data) {
        foreach ($data as &$item) {
            // get actor image if missing
            if (empty($item['profile_path']) && !empty($item['full_name'])) {
                $actor = searchActorTmdb($item['full_name'], $this->api_key);
                if ($actor && !empty($actor['profile_path'])) {
                    $item['profile_path'] = $actor['profile_path'];
                }
            }
            
            // get production poster if missing
            if (empty($item['local_db_poster_path']) && !empty($item['production_title'])) {
                // try movie first
                $movie = searchMovieTmdb($item['production_title'], $this->api_key);
                if ($movie && !empty($movie['poster_path'])) {
                    $item['local_db_poster_path'] = $movie['poster_path'];
                } else {
                    // try tv show if movie not found
                    $show = searchTvShowTmdb($item['production_title'], $this->api_key);
                    if ($show && !empty($show['poster_path'])) {
                        $item['local_db_poster_path'] = $show['poster_path'];
                    }
                }
            }
        }
        return $data;
    }
    
    // get years for dropdown
    public function getYears() {
        $sql = "SELECT DISTINCT year FROM awards ORDER BY year DESC";
        return $this->db->query($sql)->fetchAll(PDO::FETCH_COLUMN);
    }
    
    // get categories for dropdown
    public function getCategories() {
        $sql = "SELECT DISTINCT category FROM awards ORDER BY category";
        return $this->db->query($sql)->fetchAll(PDO::FETCH_COLUMN);
    }
}