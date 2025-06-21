<?php

/**
 * Sets up everything the app needs to run
 * Like loading files, connecting to services, etc
 */
declare(strict_types=1);

// First try to load everything automatically with Composer
// If that doesn't work, we'll load each file manually
if (file_exists(__DIR__ . '/../vendor/autoload.php')) {
    require_once __DIR__ . '/../vendor/autoload.php';
} else {
    // Fallback to manual loading if composer autoload doesn't exist
    require_once __DIR__ . '/Services/DatabaseService.php';
    require_once __DIR__ . '/Services/EmailService.php';
    require_once __DIR__ . '/Services/UserService.php';
    require_once __DIR__ . '/Services/StatsService.php';
    require_once __DIR__ . '/Services/TmdbService.php';
    require_once __DIR__ . '/Services/NewsService.php';
    require_once __DIR__ . '/Services/LoggingService.php';
    require_once __DIR__ . '/Repositories/ActorRepository.php';
    require_once __DIR__ . '/Middleware/AuthenticationMiddleware.php';
    require_once __DIR__ . '/Utils/Helpers.php';
    require_once __DIR__ . '/Exports/ExportHandler.php';
}

// gets the settings from the .env file
if (class_exists('Dotenv\Dotenv') && file_exists(__DIR__ . '/../.env')) {
    $dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/..');
    $dotenv->load();
}

define('TMDB_API_KEY', $_ENV['TMDB_API_KEY'] ?? '');
define('TMDB_API_BASE_URL', $_ENV['TMDB_API_BASE_URL'] ?? 'https://api.themoviedb.org/3');
define('ROOT_DIR', __DIR__ . '/..');
define('DATABASE_PATH', ROOT_DIR . '/' . ($_ENV['DATABASE_PATH'] ?? 'database/app.db'));
define('CSV_PATH', ROOT_DIR . '/' . ($_ENV['CSV_PATH'] ?? 'csv/screen_actor_guild_awards.csv'));

// email smtp settings - defaults to Gmail's SMTP
define('SMTP_HOST', $_ENV['SMTP_HOST'] ?? 'smtp.gmail.com');
define('SMTP_PORT', $_ENV['SMTP_PORT'] ?? 587);
define('SMTP_USERNAME', $_ENV['SMTP_USERNAME'] ?? '');
define('SMTP_PASSWORD', $_ENV['SMTP_PASSWORD'] ?? '');
define('SMTP_FROM_EMAIL', $_ENV['SMTP_FROM_EMAIL'] ?? '');
define('SMTP_FROM_NAME', $_ENV['SMTP_FROM_NAME'] ?? 'Actor Awards Visualizer');

// start a session to remember who's logged in
// only starts if one isn't already running
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
