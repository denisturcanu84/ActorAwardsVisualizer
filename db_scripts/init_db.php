<?php

require_once __DIR__ . '/../config.php';

try {
    $db = get_db_connection();
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "Starting database initialization...\n";
    
    $db->exec("
    -- ================================================================
    -- CORE TABLES
    -- ================================================================
    
    -- Awards table - Main table containing all award nominations and wins
    CREATE TABLE IF NOT EXISTS awards (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        year TEXT,
        category TEXT,
        full_name TEXT,
        show TEXT,
        won TEXT, -- 'True' or 'False'
        tmdb_actor_id INTEGER,
        tmdb_show_id INTEGER,
        created_at TEXT DEFAULT CURRENT_TIMESTAMP
    );

    -- Actors table - Information about actors
    CREATE TABLE IF NOT EXISTS actors (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        full_name TEXT UNIQUE,
        tmdb_id INTEGER UNIQUE,
        bio TEXT,
        profile_path TEXT,
        popularity REAL,
        gender INTEGER, -- 1: female, 2: male, 0: undefined
        birthday TEXT,
        deathday TEXT,
        place_of_birth TEXT,
        known_for_department TEXT,
        imdb_id TEXT,
        homepage TEXT,
        last_updated TEXT
    );

    -- Productions table - Information about movies/TV shows
    CREATE TABLE IF NOT EXISTS productions (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        title TEXT,
        tmdb_id INTEGER UNIQUE,
        release_year TEXT,
        poster_path TEXT,
        type TEXT, -- 'movie' or 'tv'
        overview TEXT,
        original_language TEXT,
        popularity REAL,
        vote_average REAL,
        vote_count INTEGER,
        runtime INTEGER,
        genres TEXT, -- JSON array of genres
        last_updated TEXT
    );

    -- ================================================================
    -- USER MANAGEMENT TABLES
    -- ================================================================
    
    -- Users table - User authentication and management
    CREATE TABLE IF NOT EXISTS users (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        username TEXT UNIQUE NOT NULL,
        email TEXT UNIQUE NOT NULL,
        password_hash TEXT NOT NULL,
        role TEXT DEFAULT 'user', -- 'user' or 'admin'
        created_at TEXT DEFAULT CURRENT_TIMESTAMP,
        last_login TEXT
    );

    -- Password resets table - For password reset functionality
    CREATE TABLE IF NOT EXISTS password_resets (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        email TEXT NOT NULL,
        token TEXT NOT NULL UNIQUE,
        expires_at TEXT NOT NULL,
        created_at TEXT DEFAULT CURRENT_TIMESTAMP
    );

    -- ================================================================
    -- RELATIONSHIP TABLES
    -- ================================================================
    
    -- Actor credits - Links actors to productions with their roles
    CREATE TABLE IF NOT EXISTS actor_credits (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        actor_id INTEGER,
        production_id INTEGER,
        character TEXT,
        credit_type TEXT, -- 'cast' or 'crew'
        department TEXT,
        job TEXT,
        order_number INTEGER,
        FOREIGN KEY(actor_id) REFERENCES actors(id),
        FOREIGN KEY(production_id) REFERENCES productions(id)
    );

    -- Award categories - Metadata about award categories
    CREATE TABLE IF NOT EXISTS award_categories (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        name TEXT UNIQUE,
        description TEXT,
        first_year TEXT,
        last_year TEXT,
        category_type TEXT -- 'individual', 'ensemble', 'stunt'
    );

    -- ================================================================
    -- STATISTICS TABLES (for performance optimization)
    -- ================================================================
    
    -- Actor statistics - Precomputed statistics for actors
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

    -- Production statistics - Precomputed statistics for productions
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

    -- ================================================================
    -- INDEXES FOR PERFORMANCE
    -- ================================================================
    
    -- Awards table indexes
    CREATE INDEX IF NOT EXISTS idx_awards_actor_id ON awards(tmdb_actor_id);
    CREATE INDEX IF NOT EXISTS idx_awards_show_id ON awards(tmdb_show_id);
    CREATE INDEX IF NOT EXISTS idx_awards_won ON awards(won);
    CREATE INDEX IF NOT EXISTS idx_awards_year ON awards(year);
    CREATE INDEX IF NOT EXISTS idx_awards_category ON awards(category);
    CREATE INDEX IF NOT EXISTS idx_awards_full_name ON awards(full_name);
    
    -- Actors table indexes
    CREATE INDEX IF NOT EXISTS idx_actors_tmdb_id ON actors(tmdb_id);
    CREATE INDEX IF NOT EXISTS idx_actors_name ON actors(full_name);
    
    -- Productions table indexes
    CREATE INDEX IF NOT EXISTS idx_productions_tmdb_id ON productions(tmdb_id);
    CREATE INDEX IF NOT EXISTS idx_productions_title ON productions(title);
    
    -- User table indexes
    CREATE INDEX IF NOT EXISTS idx_users_username ON users(username);
    CREATE INDEX IF NOT EXISTS idx_users_email ON users(email);
    
    -- Password resets indexes
    CREATE INDEX IF NOT EXISTS idx_password_resets_token ON password_resets(token);
    CREATE INDEX IF NOT EXISTS idx_password_resets_email ON password_resets(email);
    CREATE INDEX IF NOT EXISTS idx_password_resets_expires ON password_resets(expires_at);
    
    -- Actor credits indexes
    CREATE INDEX IF NOT EXISTS idx_actor_credits_actor ON actor_credits(actor_id);
    CREATE INDEX IF NOT EXISTS idx_actor_credits_production ON actor_credits(production_id);
    ");

    echo "✓ Database schema created successfully!\n";
    
    // Insert default admin user if it doesn't exist
    $stmt = $db->prepare("SELECT COUNT(*) FROM users WHERE username = 'admin'");
    $stmt->execute();
    $adminExists = $stmt->fetchColumn();
    
    if (!$adminExists) {
        $adminPasswordHash = password_hash('admin123', PASSWORD_DEFAULT);
        $stmt = $db->prepare("INSERT INTO users (username, email, password_hash, role) VALUES (?, ?, ?, ?)");
        $stmt->execute(['admin', 'admin@example.com', $adminPasswordHash, 'admin']);
        echo "✓ Default admin user created (username: admin, password: admin123)\n";
        echo "⚠️  Please change the default password after first login!\n";
    } else {
        echo "ℹ️  Admin user already exists\n";
    }
 
} catch (Exception $e) {
    echo "Error initializing database: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
    exit(1);
}
?>