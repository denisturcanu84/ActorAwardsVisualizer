<?php

namespace ActorAwards\Middleware;

class AuthenticationMiddleware
{
    public static function requireLogin(): void
    {
        if (!isset($_SESSION['user_id'])) {
            header('Location: /login');
            exit;
        }
    }
    
    public static function requireAdmin(): void
    {
        self::requireLogin();
        
        if (!isset($_SESSION['is_admin']) || !$_SESSION['is_admin']) {
            header('Location: /login');
            exit;
        }
    }
    
    public static function redirectIfLoggedIn(): void
    {
        if (isset($_SESSION['user_id'])) {
            header('Location: /');
            exit;
        }
    }
}
