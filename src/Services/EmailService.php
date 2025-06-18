<?php

namespace ActorAwards\Services;

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

class EmailService
{
    private PHPMailer $mailer;
    
    public function __construct()
    {
        $this->mailer = new PHPMailer(true);
        $this->configureSMTP();
    }
    
    private function configureSMTP(): void
    {
        try {
            // Check if SMTP constants are defined
            if (!defined('SMTP_HOST') || !defined('SMTP_USERNAME') || !defined('SMTP_PASSWORD')) {
                error_log("SMTP Configuration Error: SMTP constants not defined");
                return;
            }
            
            // Server settings
            $this->mailer->isSMTP();
            $this->mailer->Host = SMTP_HOST;
            $this->mailer->SMTPAuth = true;
            $this->mailer->Username = SMTP_USERNAME;
            $this->mailer->Password = SMTP_PASSWORD;
            $this->mailer->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $this->mailer->Port = SMTP_PORT;
            
            // Enable debugging for development
            if (defined('APP_DEBUG') && APP_DEBUG) {
                $this->mailer->SMTPDebug = SMTP::DEBUG_SERVER;
                $this->mailer->Debugoutput = 'error_log';
            }
            
            // Default sender
            $this->mailer->setFrom(SMTP_FROM_EMAIL, SMTP_FROM_NAME);
        } catch (Exception $e) {
            error_log("SMTP Configuration Error: " . $e->getMessage());
        }
    }
    
    public function sendPasswordResetEmail(string $email, string $resetToken): bool
    {
        try {
            // Reset any previous recipients
            $this->mailer->clearAllRecipients();
            
            // Recipients
            $this->mailer->addAddress($email);
            
            // Content
            $this->mailer->isHTML(true);
            $this->mailer->Subject = 'Password Reset Request - Actor Awards Visualizer';
            
            $resetUrl = $this->getBaseUrl() . '/reset-password?step=reset&token=' . urlencode($resetToken);
            
            $this->mailer->Body = $this->getPasswordResetTemplate($resetUrl);
            $this->mailer->AltBody = $this->getPasswordResetTextTemplate($resetUrl);
            
            return $this->mailer->send();
        } catch (Exception $e) {
            error_log("Email sending failed: " . $e->getMessage());
            return false;
        }
    }
    
    private function getBaseUrl(): string
    {
        $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
        return $protocol . '://' . $host;
    }
    
    private function getPasswordResetTemplate(string $resetUrl): string
    {
        return '
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
            <title>Password Reset</title>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background: linear-gradient(135deg, #4A90E2 0%, #357ABD 100%); color: white; padding: 20px; text-align: center; border-radius: 10px 10px 0 0; }
                .content { background: #f9f9f9; padding: 30px; border-radius: 0 0 10px 10px; }
                .button { display: inline-block; background: #4A90E2; color: white; padding: 12px 24px; text-decoration: none; border-radius: 5px; margin: 20px 0; }
                .footer { text-align: center; margin-top: 20px; font-size: 12px; color: #666; }
            </style>
        </head>
        <body>
            <div class="container">
                <div class="header">
                    <h1>Password Reset Request</h1>
                </div>
                <div class="content">
                    <p>Hello,</p>
                    <p>You have requested to reset your password for Actor Awards Visualizer. Click the button below to reset your password:</p>
                    <p><a href="' . htmlspecialchars($resetUrl) . '" class="button">Reset Password</a></p>
                    <p>If the button doesn\'t work, copy and paste this link into your browser:</p>
                    <p><a href="' . htmlspecialchars($resetUrl) . '">' . htmlspecialchars($resetUrl) . '</a></p>
                    <p><strong>This link will expire in 1 hour.</strong></p>
                    <p>If you didn\'t request this password reset, please ignore this email.</p>
                    <p>Best regards,<br>Actor Awards Visualizer Team</p>
                </div>
                <div class="footer">
                    <p>This is an automated email. Please do not reply to this message.</p>
                </div>
            </div>
        </body>
        </html>';
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
