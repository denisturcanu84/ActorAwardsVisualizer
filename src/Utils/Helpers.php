<?php

namespace ActorAwards\Utils;

class Helpers
{
    /**
     * Checks if our cached data is too old and needs refreshing
     * @param string|null $lastUpdated When the data was last updated (null means never)
     * @param string $interval How old is "too old" (default 1 day)
     * @return bool True if data is stale and should be refreshed
     */
    public static function isOutdated(?string $lastUpdated, string $interval = '1 day'): bool
    {
        if (!$lastUpdated) {
            return true;
        }
        
        return strtotime($lastUpdated) < strtotime("-$interval");
    }
    
    /**
     * Makes text safe to display in HTML by escaping special characters
     * @param string $string Text that might contain HTML/JS code
     * @return string Safe text that won't execute as code
     * @important Always use this before outputting user-provided content!
     */
    public static function escape(string $string): string
    {
        return htmlspecialchars($string, ENT_QUOTES, 'UTF-8');
    }
    
    /**
     * Checks if an email address is properly formatted
     * @param string $email The email to validate
     * @return bool True if email looks valid (but doesn't check if it actually exists)
     * @example Helps prevent obviously fake emails during registration
     */
    public static function isValidEmail(string $email): bool
    {
        return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    }
    
    /**
     * Creates a unique security token to prevent form submission attacks
     * @return string The generated token
     * @note Stores token in session so we can verify it later
     * @important Use with verifyCsrfToken() for complete protection
     */
    public static function generateCsrfToken(): string
    {
        if (!isset($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        
        return $_SESSION['csrf_token'];
    }
    
    /**
     * Checks if a submitted form token matches what we expect
     * @param string $token The token from the submitted form
     * @return bool True if token is valid and matches our session
     * @important This prevents attackers from tricking users into submitting malicious forms
     */
    public static function verifyCsrfToken(string $token): bool
    {
        return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
    }
}
