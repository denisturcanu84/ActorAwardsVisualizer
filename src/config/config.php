<?php
require_once __DIR__ . '/../../vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../..');
$dotenv->load();

define('TMDB_API_KEY', $_ENV['TMDB_API_KEY']);
define('TMDB_API_BASE_URL', $_ENV['TMDB_API_BASE_URL']);
define('ROOT_DIR', __DIR__ . '/../..');
define('DATABASE_PATH', ROOT_DIR . '/' . $_ENV['DATABASE_PATH']);
define('CSV_PATH', ROOT_DIR . '/' . $_ENV['CSV_PATH']);
