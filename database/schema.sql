CREATE TABLE awards (
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
CREATE TABLE sqlite_sequence(name,seq);
CREATE TABLE actors (
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
CREATE TABLE productions (
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
CREATE TABLE users (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    username TEXT UNIQUE,
    email TEXT UNIQUE,
    password_hash TEXT,
    role TEXT DEFAULT 'user', -- 'user', 'admin'
    created_at TEXT DEFAULT CURRENT_TIMESTAMP,
    last_login TEXT
);
CREATE TABLE actor_credits (
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
CREATE TABLE award_categories (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name TEXT UNIQUE,
    description TEXT,
    first_year TEXT,
    last_year TEXT,
    category_type TEXT -- 'individual', 'ensemble', 'stunt'
);
CREATE TABLE actor_statistics (
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
CREATE TABLE production_statistics (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    production_id INTEGER UNIQUE,
    total_nominations INTEGER DEFAULT 0,
    total_wins INTEGER DEFAULT 0,
    most_nominated_category TEXT,
    most_won_category TEXT,
    updated_at TEXT DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY(production_id) REFERENCES productions(id)
);
CREATE TABLE password_resets (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    email TEXT NOT NULL,
    token TEXT NOT NULL UNIQUE,
    expires_at TEXT NOT NULL,
    created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP
);
CREATE INDEX idx_awards_actor_id ON awards(tmdb_actor_id);
CREATE INDEX idx_awards_show_id ON awards(tmdb_show_id);
CREATE INDEX idx_awards_won ON awards(won);
CREATE INDEX idx_awards_year ON awards(year);
CREATE INDEX idx_awards_category ON awards(category);
CREATE INDEX idx_actors_tmdb_id ON actors(tmdb_id);
CREATE INDEX idx_actors_name ON actors(full_name);
CREATE INDEX idx_productions_tmdb_id ON productions(tmdb_id);
CREATE INDEX idx_productions_title ON productions(title);
CREATE INDEX idx_actor_credits_actor ON actor_credits(actor_id);
CREATE INDEX idx_actor_credits_production ON actor_credits(production_id);
CREATE INDEX idx_awards_full_name ON awards(full_name);
CREATE INDEX idx_awards_year_category ON awards(year, category);
CREATE INDEX idx_awards_won_year ON awards(won, year);
CREATE INDEX idx_awards_tmdb_actor_id ON awards(tmdb_actor_id);
CREATE INDEX idx_awards_tmdb_show_id ON awards(tmdb_show_id);
CREATE INDEX idx_actors_full_name ON actors(full_name);
CREATE INDEX idx_users_username ON users(username);
CREATE INDEX idx_users_email ON users(email);
CREATE INDEX idx_password_resets_token ON password_resets(token);
CREATE INDEX idx_password_resets_email ON password_resets(email);
CREATE INDEX idx_password_resets_expires ON password_resets(expires_at);
