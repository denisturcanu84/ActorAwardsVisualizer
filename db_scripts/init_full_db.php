<?php
$db = new PDO('sqlite:' . __DIR__ . '/../database/app.db');
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$db->exec("
CREATE TABLE IF NOT EXISTS awards (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    year TEXT,
    category TEXT,
    full_name TEXT,
    show TEXT,
    won TEXT,
    tmdb_actor_id INTEGER,
    tmdb_show_id INTEGER
);

CREATE TABLE IF NOT EXISTS actors (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    full_name TEXT UNIQUE,
    tmdb_id INTEGER,
    bio TEXT,
    profile_path TEXT,
    popularity REAL
);

CREATE TABLE IF NOT EXISTS productions (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    title TEXT,
    tmdb_id INTEGER,
    release_year TEXT,
    poster_path TEXT,
    type TEXT
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
    password_hash TEXT
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
");

echo "All tables created successfully!";
