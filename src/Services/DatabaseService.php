<?php

namespace ActorAwards\Services;

use PDO;
use PDOException;

// Manages all database connections using Singleton pattern
// This ensures we only have one connection to the database at a time
class DatabaseService
{
    private static ?PDO $connection = null; // Stores our single database connection
    // The ? means it can be null (not connected yet)
    
    // Gets the database connection (creates it if needed)
    // This is the main way to get the database connection in the app
    // in the future we might move to MySQL or another database that requires username/password
    // for now, we use SQLite which doesn't need credentials
    public static function getConnection(): PDO
    {
        if (self::$connection === null) {
            try { 
                // Configure new PDO connection with these settings:
                // 1. Path to SQLite file
                // 2. Username (not needed for SQLite)
                // 3. Password (not needed for SQLite)
                // 4. Important PDO options:
                self::$connection = new PDO(
                    'sqlite:' . DATABASE_PATH, // Path to SQLite database file
                    null, // No username for SQLite
                    null, // No password for SQLite
                    [
                        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, // Makes PDO throw exceptions on errors
                        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC, // Returns data as associative arrays
                        PDO::ATTR_PERSISTENT => false // Creates new connection each time
                    ]
                );
            } catch (PDOException $e) {
                throw new PDOException('Database connection failed: ' . $e->getMessage());
            }
        }
        
        return self::$connection;
    }
    
    public static function closeConnection(): void
    {
        self::$connection = null;
    }
}
