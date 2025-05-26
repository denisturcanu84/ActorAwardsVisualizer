<?php
require_once __DIR__ . '/../config.php';

$db = get_db_connection();

log_message("Începe popularea categoriilor de premii...");

// Extrage categoriile unice din tabela de premii
$query = "SELECT DISTINCT category FROM awards ORDER BY category";
$categories = $db->query($query)->fetchAll(PDO::FETCH_COLUMN);

// Determinăm tipul pentru fiecare categorie
$categoryTypes = [
    'CAST IN A MOTION PICTURE' => 'ensemble',
    'ENSEMBLE IN A COMEDY SERIES' => 'ensemble',
    'ENSEMBLE IN A DRAMA SERIES' => 'ensemble',
    'FEMALE ACTOR IN A COMEDY SERIES' => 'individual',
    'FEMALE ACTOR IN A DRAMA SERIES' => 'individual',
    'FEMALE ACTOR IN A LEADING ROLE' => 'individual',
    'FEMALE ACTOR IN A SUPPORTING ROLE' => 'individual',
    'FEMALE ACTOR IN A TELEVISION MOVIE OR LIMITED SERIES' => 'individual',
    'MALE ACTOR IN A COMEDY SERIES' => 'individual',
    'MALE ACTOR IN A DRAMA SERIES' => 'individual',
    'MALE ACTOR IN A LEADING ROLE' => 'individual',
    'MALE ACTOR IN A SUPPORTING ROLE' => 'individual',
    'MALE ACTOR IN A TELEVISION MOVIE OR LIMITED SERIES' => 'individual',
    'STUNT ENSEMBLE IN A COMEDY OR DRAMA SERIES' => 'stunt',
    'STUNT ENSEMBLE IN A MOTION PICTURE' => 'stunt',
];

// Șterge datele existente
$db->exec("DELETE FROM award_categories");

// Pregătește interogarea pentru inserare
$stmt = $db->prepare("INSERT OR IGNORE INTO award_categories (name, category_type) VALUES (?, ?)");

$count = 0;
foreach ($categories as $category) {
    $category = trim($category);
    $type = $categoryTypes[$category] ?? 'individual';
    
    $stmt->execute([$category, $type]);
    $count++;
}

// Actualizăm anii pentru fiecare categorie (primul și ultimul an)
$db->exec("
    UPDATE award_categories SET 
    first_year = (
        SELECT MIN(year) FROM awards WHERE category = award_categories.name
    ),
    last_year = (
        SELECT MAX(year) FROM awards WHERE category = award_categories.name
    )
");

log_message("Populare completă! $count categorii de premii au fost adăugate.");