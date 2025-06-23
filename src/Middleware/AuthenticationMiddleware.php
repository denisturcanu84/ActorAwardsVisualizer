<?php

namespace ActorAwards\Middleware;

  // Authentication Middleware - Checks user authentication status before allowing access

class AuthenticationMiddleware
{
    // redirects to login page if not logged in
    public static function requireLogin(): void
    {
        // check if user session exists
        if (!isset($_SESSION['user_id'])) {
            header('Location: /login');
            exit;
        }
    }
    
    public static function requireAdmin(): void
    {
        self::requireLogin();
        
        // then check if they're an admin
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
