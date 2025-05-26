<?php
require_once __DIR__ . '/vendor/autoload.php';

// incarca variabilele de mediu din fisierul .env
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

// Configurare aplicație
define('TMDB_API_KEY', $_ENV['TMDB_API_KEY']);
define('TMDB_API_BASE_URL', $_ENV['TMDB_API_BASE_URL']);
define('DATABASE_PATH', __DIR__ . '/' . $_ENV['DATABASE_PATH']);
define('CSV_PATH', __DIR__ . '/' . $_ENV['CSV_PATH']);

// Funcție helper pentru cereri API TMDB
function tmdb_request($endpoint, $params = []) {
    $url = TMDB_API_BASE_URL . $endpoint;
    $params['api_key'] = TMDB_API_KEY;
    
    // Adaugă parametrii la URL
    $url .= '?' . http_build_query($params);
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HEADER, false);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    
    // Execută cererea
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    // Verifică răspunsul
    if ($httpCode == 200) {
        return json_decode($response, true);
    }
    
    return null;
}

// Asigură-te că directorul pentru baza de date există
function ensure_db_directory_exists() {
    $dir = dirname(DATABASE_PATH);
    if (!file_exists($dir)) {
        mkdir($dir, 0777, true);
    }
}

// Conectare la baza de date SQLite
function get_db_connection() {
    ensure_db_directory_exists();
    
    $db = new PDO('sqlite:' . DATABASE_PATH);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    return $db;
}

// Funcție pentru logging
function log_message($message) {
    echo date('Y-m-d H:i:s') . " - $message" . PHP_EOL;
}
