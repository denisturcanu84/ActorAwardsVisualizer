<?php

function getDbConnection($path = null) {
    $dbPath = $path ?? (__DIR__ . '/../database/app.db');
    $db = new PDO('sqlite:' . $dbPath);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    return $db;
}
