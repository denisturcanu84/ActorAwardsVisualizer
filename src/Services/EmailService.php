<?php

namespace ActorAwards\Services;

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

// This service handles basically sending reset password emails - can add more later
// It uses the PHPMailer library
class EmailService
{
    private PHPMailer $mailer; // The PHPMailer instance we'll use
    
    // Constructor - runs when we create a new EmailService
    // Sets up PHPMailer and configures it to use our SMTP server
    public function __construct()
    {
        $this->mailer = new PHPMailer(true);
        $this->configureSMTP();
    }
    
    // Private method that does the actual SMTP configuration
    // Gets settings from constants defined in our config
    // Called automatically when the service starts up
    private function configureSMTP(): void
    {
        try {
            // First check if we have all required SMTP settings
            // These should be defined in our environment file
            if (!defined('SMTP_HOST') || !defined('SMTP_USERNAME') || !defined('SMTP_PASSWORD')) {
                error_log("SMTP Configuration Error: SMTP constants not defined");
                return;
            }
            
            // Basic SMTP server connection settings
            // This tells PHPMailer to use SMTP instead of sendmail
            $this->mailer->isSMTP();
            $this->mailer->Host = SMTP_HOST;
            $this->mailer->SMTPAuth = true;
            $this->mailer->Username = SMTP_USERNAME;
            $this->mailer->Password = SMTP_PASSWORD;
            $this->mailer->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $this->mailer->Port = SMTP_PORT;
            
            // Only enable debugging if we're in development mode
            // Shows detailed SMTP conversation in error logs
            if (defined('APP_DEBUG') && APP_DEBUG) {
                $this->mailer->SMTPDebug = SMTP::DEBUG_SERVER;
                $this->mailer->Debugoutput = 'error_log';
            }
            
            // Set default "from" address for all emails
            $this->mailer->setFrom(SMTP_FROM_EMAIL, SMTP_FROM_NAME);
        } catch (Exception $e) {
            error_log("SMTP Configuration Error: " . $e->getMessage());
        }
    }
    
    // Main method to send password reset emails
    // Takes the user's email and a unique reset token
    // Returns true if sent successfully, false if failed
    // The token is used to verify the reset request later
    public function sendPasswordResetEmail(string $email, string $resetToken): bool
    {
        try {
            // Clear any previous recipients to avoid sending to wrong people
            // very important since we reuse the same PHPMailer instance
            $this->mailer->clearAllRecipients();
            
            // Add the recipient's email address
            // We only send to one person for password resets
            $this->mailer->addAddress($email);
            
            $this->mailer->Subject = 'Password Reset Request - Actor Awards Visualizer';
            
            $resetUrl = $this->getBaseUrl() . '/reset-password?step=reset&token=' . urlencode($resetToken);
            
            $this->mailer->Body = $this->getPasswordResetTextTemplate($resetUrl);
            
            return $this->mailer->send();
        } catch (Exception $e) {
            error_log("Email sending failed: " . $e->getMessage());
            return false;
        }
    }

    // well this gets the base url from the environment variable
    private function getBaseUrl(): string
    {
        return getenv('APP_URL') ?: 'http://localhost';
    }

    private function getPasswordResetTextTemplate(string $resetUrl): string
    {
        return "Password Reset Request

    Hello,

    You have requested to reset your password for Actor Awards Visualizer.

    Please click the following link to reset your password:
    {$resetUrl}

    This link will expire in 1 hour.

    If you didn't request this password reset, please ignore this email.

    Best regards,
    Actor Awards Visualizer Team

    ---
    This is an automated email. Please do not reply to this message.";
    }
}
