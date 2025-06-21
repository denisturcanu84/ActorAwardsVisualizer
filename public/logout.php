<?php
require_once __DIR__ . '/../src/bootstrap.php';

// clears all session variables to prevent data leakage
$_SESSION = array();

// if session cookies are used, delete the session cookie
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// completely destroy the session data
session_destroy();

// redirect to the home page
header('Location: /');
exit;
