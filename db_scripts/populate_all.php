<?php
require_once __DIR__ . '/../config.php';

echo "=== STARTING COMPLETE DATABASE SETUP PROCESS ===\n";

// Initialize database schema with init_db script
echo "\n1. Initializing database schema...\n";
include_once __DIR__ . '/consolidated_db_init.php';

// Import awards from CSV
echo "\n2. Importing awards from CSV...\n";
include_once __DIR__ . '/import_awards.php';

// Populate award categories
echo "\n3. Populating award categories...\n";
include_once __DIR__ . '/populate_award_categories.php';

// Populate actors
echo "\n4. Populating actors...\n";
include_once __DIR__ . '/populate_actors.php';

// Populate productions
echo "\n5. Populating productions...\n";
include_once __DIR__ . '/populate_productions.php';

// Populate actor credits
echo "\n6. Populating actor credits...\n";
include_once __DIR__ . '/populate_actor_credits.php';

// Calculate statistics
echo "\n7. Calculating statistics...\n";
include_once __DIR__ . '/calculate_statistics.php';

echo "\n=== DATABASE SETUP PROCESS COMPLETED SUCCESSFULLY! ===\n";