<?php

namespace ActorAwards\Services;

use PDO;

// This service manages everything about users
// It handles account creation, login/logout, password resets, and profile stuff
class UserService
{
    private PDO $db; // Database connection
    private EmailService $emailService; // For sending emails
    
    // sets up the service with required dependencies
    public function __construct(PDO $database)
    {
        $this->db = $database;
        $this->emailService = new EmailService();
    }
    
    // creates a new user account the it hashes the password before storing it
    public function createUser(string $username, string $email, string $password): bool
    {
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        
        $stmt = $this->db->prepare("
            INSERT INTO users (username, email, password_hash, created_at)
            VALUES (?, ?, ?, datetime('now'))
        ");
        
        return $stmt->execute([$username, $email, $hashedPassword]);
    }
    
    // This is where the login happens - it checks if username/password match
    // If valid, returns user data; if not, returns null
    // The password verification uses secure hashing to compare without storing plain text
    public function authenticateUser(string $username, string $password): ?array
    {
        $stmt = $this->db->prepare("
            SELECT id, username, email, password_hash, role
            FROM users
            WHERE username = ? OR email = ?
        ");
        
        $stmt->execute([$username, $username]);
        $user = $stmt->fetch();
        
        if ($user && password_verify($password, $user['password_hash'])) {
            $user['is_admin'] = ($user['role'] === 'admin');
            return $user;
        }
        
        return null;
    }
    
    // checks if a username or email is already registered
    public function userExists(string $username, string $email): bool
    {
        $stmt = $this->db->prepare("
            SELECT COUNT(*) 
            FROM users 
            WHERE username = ? OR email = ?
        ");
        
        $stmt->execute([$username, $email]);
        return $stmt->fetchColumn() > 0;
    }
    
    // Gets user details by their ID
    // Used for admin dashboard
    public function getUserById(int $userId): ?array
    {
        $stmt = $this->db->prepare("
            SELECT id, username, email, role, created_at
            FROM users
            WHERE id = ?
        ");
        
        $stmt->execute([$userId]);
        $user = $stmt->fetch();
        
        if ($user) {
            $user['is_admin'] = ($user['role'] === 'admin');
            return $user;
        }
        
        return null;
    }
    
    // Creates a temporary password reset token and stores it
    // Tokens expire after a while for security
    public function createResetToken(string $email, string $token, string $expiry): bool
    {
        // first check if user exists
        $stmt = $this->db->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();
        
        if (!$user) {
            return false;
        }
        
        // create or update reset token
        $stmt = $this->db->prepare("
            INSERT OR REPLACE INTO password_resets (email, token, expires_at, created_at) 
            VALUES (?, ?, ?, datetime('now'))
        ");
        
        return $stmt->execute([$email, $token, $expiry]);
    }
    
    // Final step in the password reset process - actually updates the password
    // Needs a valid, unexpired token 
    // Cleans up by deleting the used token so it can't be reused
    public function resetPassword(string $token, string $newPassword): bool
    {
        // check if token is valid and not expired
        $stmt = $this->db->prepare("
            SELECT email FROM password_resets 
            WHERE token = ? AND expires_at > datetime('now')
        ");
        
        $stmt->execute([$token]);
        $reset = $stmt->fetch();
        
        if (!$reset) {
            return false;
        }
        
        // update user password
        $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
        $stmt = $this->db->prepare("
            UPDATE users SET password_hash = ? WHERE email = ?
        ");
        
        $success = $stmt->execute([$hashedPassword, $reset['email']]);
        
        if ($success) {
            // delete the used token
            $stmt = $this->db->prepare("DELETE FROM password_resets WHERE token = ?");
            $stmt->execute([$token]);
        }
        
        return $success;
    }
    
    // Orchestrates the whole password reset process:
    // 1. Checks if email exists (but pretends it does either way for security)
    // 2. Makes a secure random token
    // 3. Stores token with expiry time
    // 4. Emails the reset link to user
    public function requestPasswordReset(string $email): bool
    {
        // checks if user exists
        $stmt = $this->db->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();
        
        if (!$user) {
            // returns true even if user doesn't exist for security reasons
            // (it doesn't reveal if an email is registered)
            return true;
        }
        
        // generate reset token
        $token = bin2hex(random_bytes(32));
        $expiry = date('Y-m-d H:i:s', strtotime('+1 hour'));
        
        // store token in database
        if ($this->createResetToken($email, $token, $expiry)) {
            // sends email
            return $this->emailService->sendPasswordResetEmail($email, $token);
        }
        
        return false;
    }
    
    /**
     * Fetches all users from the database.
     *
     * @return array A list of all users.
     */
    public function getUsers(): array
    {
        $stmt = $this->db->query("SELECT id, username, email, role, created_at FROM users ORDER BY username");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Deletes a user by their ID.
    public function deleteUser(int $userId): bool
    {
        $stmt = $this->db->prepare("DELETE FROM users WHERE id = ?");
        return $stmt->execute([$userId]);
    }
}
