<?php
require_once __DIR__ . '/../config.php';

$db = get_db_connection();

$db->exec("
-- Tabele principale
CREATE TABLE IF NOT EXISTS awards (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    year TEXT,
    category TEXT,
    full_name TEXT,
    show TEXT,
    won TEXT, -- 'True' sau 'False'
    tmdb_actor_id INTEGER,
    tmdb_show_id INTEGER,
    created_at TEXT DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS actors (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    full_name TEXT UNIQUE,
    tmdb_id INTEGER UNIQUE,
    bio TEXT,
    profile_path TEXT,
    popularity REAL,
    gender INTEGER, -- 1: femeie, 2: bărbat, 0: nedefinit
    birthday TEXT,
    deathday TEXT,
    place_of_birth TEXT,
    known_for_department TEXT,
    imdb_id TEXT,
    homepage TEXT,
    last_updated TEXT
);

CREATE TABLE IF NOT EXISTS productions (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    title TEXT,
    tmdb_id INTEGER UNIQUE,
    release_year TEXT,
    poster_path TEXT,
    type TEXT, -- 'movie' sau 'tv'
    overview TEXT,
    original_language TEXT,
    popularity REAL,
    vote_average REAL,
    vote_count INTEGER,
    runtime INTEGER,
    genres TEXT, -- JSON array de genuri
    last_updated TEXT
);

CREATE TABLE IF NOT EXISTS news_sources (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name TEXT,
    api_url TEXT,
    api_key TEXT
);

CREATE TABLE IF NOT EXISTS news (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    actor_id INTEGER,
    title TEXT,
    url TEXT,
    published_at TEXT,
    source TEXT,
    FOREIGN KEY(actor_id) REFERENCES actors(id)
);

CREATE TABLE IF NOT EXISTS users (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    username TEXT UNIQUE,
    email TEXT UNIQUE,
    password_hash TEXT,
    role TEXT DEFAULT 'user', -- 'user', 'admin'
    created_at TEXT DEFAULT CURRENT_TIMESTAMP,
    last_login TEXT
);

CREATE TABLE IF NOT EXISTS favorites (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER,
    actor_id INTEGER,
    FOREIGN KEY(user_id) REFERENCES users(id),
    FOREIGN KEY(actor_id) REFERENCES actors(id)
);

CREATE TABLE IF NOT EXISTS exports (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER,
    type TEXT,
    created_at TEXT,
    file_path TEXT,
    FOREIGN KEY(user_id) REFERENCES users(id)
);

-- Tabele noi

CREATE TABLE IF NOT EXISTS actor_credits (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    actor_id INTEGER,
    production_id INTEGER,
    character TEXT,
    credit_type TEXT, -- 'cast' sau 'crew'
    department TEXT,
    job TEXT,
    order_number INTEGER,
    FOREIGN KEY(actor_id) REFERENCES actors(id),
    FOREIGN KEY(production_id) REFERENCES productions(id)
);

CREATE TABLE IF NOT EXISTS award_categories (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name TEXT UNIQUE,
    description TEXT,
    first_year TEXT,
    last_year TEXT,
    category_type TEXT -- 'individual', 'ensemble', 'stunt'
);

CREATE TABLE IF NOT EXISTS actor_statistics (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    actor_id INTEGER UNIQUE,
    total_nominations INTEGER DEFAULT 0,
    total_wins INTEGER DEFAULT 0,
    first_nomination_year TEXT,
    last_nomination_year TEXT,
    win_ratio REAL DEFAULT 0,
    most_nominated_category TEXT,
    most_won_category TEXT,
    updated_at TEXT DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY(actor_id) REFERENCES actors(id)
);

CREATE TABLE IF NOT EXISTS production_statistics (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    production_id INTEGER UNIQUE,
    total_nominations INTEGER DEFAULT 0,
    total_wins INTEGER DEFAULT 0,
    most_nominated_category TEXT,
    most_won_category TEXT,
    updated_at TEXT DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY(production_id) REFERENCES productions(id)
);

-- Indecși pentru performanță
CREATE INDEX IF NOT EXISTS idx_awards_actor_id ON awards(tmdb_actor_id);
CREATE INDEX IF NOT EXISTS idx_awards_show_id ON awards(tmdb_show_id);
CREATE INDEX IF NOT EXISTS idx_awards_won ON awards(won);
CREATE INDEX IF NOT EXISTS idx_awards_year ON awards(year);
CREATE INDEX IF NOT EXISTS idx_awards_category ON awards(category);
CREATE INDEX IF NOT EXISTS idx_actors_tmdb_id ON actors(tmdb_id);
CREATE INDEX IF NOT EXISTS idx_actors_name ON actors(full_name);
CREATE INDEX IF NOT EXISTS idx_productions_tmdb_id ON productions(tmdb_id);
CREATE INDEX IF NOT EXISTS idx_productions_title ON productions(title);
CREATE INDEX IF NOT EXISTS idx_actor_credits_actor ON actor_credits(actor_id);
CREATE INDEX IF NOT EXISTS idx_actor_credits_production ON actor_credits(production_id);

-- Vizualizări pentru analiză
CREATE VIEW IF NOT EXISTS top_awarded_actors AS
SELECT 
    a.id as actor_id,
    a.full_name, 
    COUNT(*) as award_count,
    a.profile_path,
    a.popularity
FROM awards aw
JOIN actors a ON aw.tmdb_actor_id = a.tmdb_id
WHERE aw.won = 'True'
GROUP BY a.id
ORDER BY award_count DESC;

CREATE VIEW IF NOT EXISTS top_awarded_productions AS
SELECT 
    p.id as production_id,
    p.title, 
    COUNT(*) as award_count,
    p.poster_path,
    p.type,
    p.release_year
FROM awards aw
JOIN productions p ON aw.tmdb_show_id = p.tmdb_id
WHERE aw.won = 'True'
GROUP BY p.id
ORDER BY award_count DESC;

CREATE VIEW IF NOT EXISTS awards_by_year AS
SELECT 
    year,
    COUNT(*) as total_nominations,
    SUM(CASE WHEN won = 'True' THEN 1 ELSE 0 END) as winners
FROM awards
GROUP BY year
ORDER BY year;

CREATE VIEW IF NOT EXISTS awards_by_category AS
SELECT 
    category,
    COUNT(*) as total,
    SUM(CASE WHEN won = 'True' THEN 1 ELSE 0 END) as winners
FROM awards
GROUP BY category
ORDER BY total DESC;
");

log_message("Structura bazei de date a fost inițializată cu succes!");
