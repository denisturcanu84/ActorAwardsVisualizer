<?php

namespace ActorAwards\Services;

use PDO;

class UserService
{
    private PDO $db;
    private EmailService $emailService;
    
    public function __construct(PDO $database)
    {
        $this->db = $database;
        $this->emailService = new EmailService();
    }
    
    public function createUser(string $username, string $email, string $password): bool
    {
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        
        $stmt = $this->db->prepare("
            INSERT INTO users (username, email, password_hash, created_at) 
            VALUES (?, ?, ?, datetime('now'))
        ");
        
        return $stmt->execute([$username, $email, $hashedPassword]);
    }
    
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
            // Add is_admin for backward compatibility
            $user['is_admin'] = ($user['role'] === 'admin');
            return $user;
        }
        
        return null;
    }
    
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
            // Add is_admin for backward compatibility
            $user['is_admin'] = ($user['role'] === 'admin');
            return $user;
        }
        
        return null;
    }
    
    public function createResetToken(string $email, string $token, string $expiry): bool
    {
        // First check if user exists
        $stmt = $this->db->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();
        
        if (!$user) {
            return false;
        }
        
        // Create or update reset token
        $stmt = $this->db->prepare("
            INSERT OR REPLACE INTO password_resets (email, token, expires_at, created_at) 
            VALUES (?, ?, ?, datetime('now'))
        ");
        
        return $stmt->execute([$email, $token, $expiry]);
    }
    
    public function resetPassword(string $token, string $newPassword): bool
    {
        // Check if token is valid and not expired
        $stmt = $this->db->prepare("
            SELECT email FROM password_resets 
            WHERE token = ? AND expires_at > datetime('now')
        ");
        
        $stmt->execute([$token]);
        $reset = $stmt->fetch();
        
        if (!$reset) {
            return false;
        }
        
        // Update user password
        $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
        $stmt = $this->db->prepare("
            UPDATE users SET password_hash = ? WHERE email = ?
        ");
        
        $success = $stmt->execute([$hashedPassword, $reset['email']]);
        
        if ($success) {
            // Delete the used token
            $stmt = $this->db->prepare("DELETE FROM password_resets WHERE token = ?");
            $stmt->execute([$token]);
        }
        
        return $success;
    }
    
    public function requestPasswordReset(string $email): bool
    {
        // Check if user exists
        $stmt = $this->db->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();
        
        if (!$user) {
            // Return true even if user doesn't exist for security reasons
            // (don't reveal whether an email is registered)
            return true;
        }
        
        // Generate reset token
        $token = bin2hex(random_bytes(32));
        $expiry = date('Y-m-d H:i:s', strtotime('+1 hour'));
        
        // Store token in database
        if ($this->createResetToken($email, $token, $expiry)) {
            // Send email
            return $this->emailService->sendPasswordResetEmail($email, $token);
        }
        
        return false;
    }
}
