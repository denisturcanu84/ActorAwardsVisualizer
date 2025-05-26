<?php
require_once __DIR__ . '/vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

define('TMDB_API_KEY', $_ENV['TMDB_API_KEY']);
define('TMDB_API_BASE_URL', $_ENV['TMDB_API_BASE_URL']);
define('DATABASE_PATH', __DIR__ . '/' . $_ENV['DATABASE_PATH']);
define('CSV_PATH', __DIR__ . '/' . $_ENV['CSV_PATH']);
