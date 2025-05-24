<?php
$db = new PDO('sqlite:' . __DIR__ . '/../database/awards.db');
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$db->exec("
    CREATE TABLE IF NOT EXISTS awards (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        year TEXT,
        category TEXT,
        full_name TEXT,
        show TEXT,
        won TEXT
    )
");
echo "Database and table created!";