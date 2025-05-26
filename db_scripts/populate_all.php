<?php
require_once __DIR__ . '/../config.php';

log_message("=== ÎNCEPE PROCESUL DE POPULARE A BAZEI DE DATE ===");

// Inițializează schema bazei de date
log_message("\n1. Inițializare schema bazei de date...");
include_once __DIR__ . '/init_full_db.php';

// Importă premiile din CSV
log_message("\n2. Importă premiile din CSV...");
include_once __DIR__ . '/import_awards.php';

// Populează categoriile de premii
log_message("\n3. Populează categoriile de premii...");
include_once __DIR__ . '/populate_award_categories.php';

// Populează actorii
log_message("\n4. Populează actorii...");
include_once __DIR__ . '/populate_actors.php';

// Populează producțiile
log_message("\n5. Populează producțiile...");
include_once __DIR__ . '/populate_productions.php';

// Populează creditele actorilor
log_message("\n6. Populează creditele actorilor...");
include_once __DIR__ . '/populate_actor_credits.php';

// Calculează statisticile
log_message("\n7. Calculează statisticile...");
include_once __DIR__ . '/calculate_statistics.php';

log_message("\n=== PROCESUL DE POPULARE A BAZEI DE DATE S-A ÎNCHEIAT CU SUCCES! ===");