<?php
// password_reset.php - Password Reset Functions

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require_once 'config.php';
require_once 'PHPMailer/src/Exception.php';
require_once 'PHPMailer/src/PHPMailer.php';
require_once 'PHPMailer/src/SMTP.php';

if(!defined('SMTP_USERNAME')) {
    define('SMTP_USERNAME', 'chardy2106@gmail.com');
}
if(!defined('SMTP_PASSWORD')) {
    define('SMTP_PASSWORD', 'kmgp jfbo ammx atxz');
}

/**
 * Generate 6-digit reset code
 */
function generateResetCode() {
    return sprintf("%06d", mt_rand(0, 999999));
}

/**
 * Send password reset code via email
 */
function sendPasswordResetCode($user_id, $email, $name) {
    $database = new Database();
    $db = $database->getConnection();
    
    try {
        // Generate reset code
        $reset_code = generateResetCode();
        $expires_at = date('Y-m-d H:i:s', strtotime('+15 minutes'));
        
        // Store reset code in database
        $query = "UPDATE users 
                  SET verification_code = :code, 
                      verification_expires = :expires 
                  WHERE user_id = :user_id";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':code', $reset_code);
        $stmt->bindParam(':expires', $expires_at);
        $stmt->bindParam(':user_id', $user_id);
        $stmt->execute();
        
        // Send email
        $mail = new PHPMailer(true);
        
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = SMTP_USERNAME;
        $mail->Password   = SMTP_PASSWORD;
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;
        $mail->SMTPDebug  = 0;
        $mail->Timeout    = 30;
        
        $mail->setFrom(SMTP_USERNAME, 'Course Withdrawal System');
        $mail->addAddress($email, $name);
        $mail->addReplyTo(SMTP_USERNAME, 'Course Withdrawal System');
        
        $mail->isHTML(true);
        $mail->CharSet = 'UTF-8';
        $mail->Subject = 'Password Reset Code - Course Withdrawal System';
        
        $mail->Body = "
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset='UTF-8'>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; margin: 0; padding: 0; background: #f4f4f4; }
                .email-wrapper { max-width: 600px; margin: 0 auto; background: #ffffff; }
                .header { background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%); color: white; padding: 30px; text-align: center; }
                .header h1 { margin: 0; font-size: 28px; }
                .content { padding: 40px 30px; }
                .code-box { background: #fef2f2; border: 3px dashed #ef4444; border-radius: 10px; padding: 30px; text-align: center; margin: 30px 0; }
                .reset-code { font-size: 48px; font-weight: bold; color: #ef4444; letter-spacing: 15px; margin: 10px 0; font-family: 'Courier New', monospace; }
                .warning-box { background: #fff3cd; border-left: 4px solid #ffc107; padding: 15px; margin: 20px 0; border-radius: 5px; }
                .footer { background: #f8f9fa; padding: 20px; text-align: center; font-size: 12px; color: #666; }
            </style>
        </head>
        <body>
            <div class='email-wrapper'>
                <div class='header'>
                    <h1>🔐 Password Reset Request</h1>
                </div>
                <div class='content'>
                    <p>Hello <strong>" . htmlspecialchars($name) . "</strong>,</p>
                    <p>We received a request to reset your password for your <strong>Course Withdrawal System</strong> account.</p>
                    
                    <div class='code-box'>
                        <p style='margin: 0; color: #666; font-size: 14px;'>Your password reset code is:</p>
                        <div class='reset-code'>" . $reset_code . "</div>
                        <p style='margin: 10px 0 0 0; color: #999; font-size: 12px;'>Enter this code on the password reset page</p>
                    </div>
                    
                    <div class='warning-box'>
                        <strong>⏰ Important Information:</strong>
                        <ul style='margin: 10px 0 0 0; padding-left: 20px;'>
                            <li>This code will <strong>expire in 15 minutes</strong></li>
                            <li>Do not share this code with anyone</li>
                            <li>If you didn't request this, please ignore this email</li>
                        </ul>
                    </div>
                    
                    <p style='color: #666; font-size: 14px; margin-top: 20px;'>If you didn't request a password reset, you can safely ignore this email. Your password will remain unchanged.</p>
                </div>
                <div class='footer'>
                    <p><strong>Course Withdrawal System</strong></p>
                    <p>This is an automated message. Please do not reply to this email.</p>
                    <p style='margin-top: 10px;'>© " . date('Y') . " Course Withdrawal System. All rights reserved.</p>
                </div>
            </div>
        </body>
        </html>
        ";
        
        $mail->AltBody = "Password Reset Code: $reset_code\n\n"
                       . "Hello $name,\n\n"
                       . "We received a request to reset your password.\n"
                       . "Your reset code is: $reset_code\n\n"
                       . "This code will expire in 15 minutes.\n\n"
                       . "If you didn't request this, please ignore this email.\n\n"
                       . "Best regards,\n"
                       . "Course Withdrawal System";
        
        $result = $mail->send();
        
        // Log
        logPasswordReset($email, $reset_code, 'SENT', 'Reset code sent successfully');
        
        return true;
        
    } catch (Exception $e) {
        logPasswordReset($email, $reset_code, 'FAILED', "Error: {$mail->ErrorInfo}");
        error_log("Password reset email error: " . $e->getMessage());
        return false;
    }
}

/**
 * Verify password reset code
 */
function verifyPasswordResetCode($user_id, $code) {
    $database = new Database();
    $db = $database->getConnection();
    
    try {
        $query = "SELECT verification_code, verification_expires 
                  FROM users 
                  WHERE user_id = :user_id";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':user_id', $user_id);
        $stmt->execute();
        
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if(!$user) {
            return false;
        }
        
        // Check if code matches
        if($user['verification_code'] !== $code) {
            return false;
        }
        
        // Check if expired
        if(strtotime($user['verification_expires']) < time()) {
            return false;
        }
        
        return true;
        
    } catch(PDOException $e) {
        error_log("Verify reset code error: " . $e->getMessage());
        return false;
    }
}

/**
 * Reset user password
 */
function resetUserPassword($user_id, $new_password) {
    $database = new Database();
    $db = $database->getConnection();
    
    try {
        $password_hash = password_hash($new_password, PASSWORD_DEFAULT);
        
        $query = "UPDATE users 
                  SET password_hash = :password_hash,
                      verification_code = NULL,
                      verification_expires = NULL
                  WHERE user_id = :user_id";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':password_hash', $password_hash);
        $stmt->bindParam(':user_id', $user_id);
        
        return $stmt->execute();
        
    } catch(PDOException $e) {
        error_log("Reset password error: " . $e->getMessage());
        return false;
    }
}

/**
 * Log password reset attempts
 */
function logPasswordReset($email, $code, $status, $message) {
    $logFile = 'password_reset_log.txt';
    $timestamp = date('Y-m-d H:i:s');
    
    $logEntry = "\n" . str_repeat("=", 80) . "\n";
    $logEntry .= "TIMESTAMP: $timestamp\n";
    $logEntry .= "EMAIL: $email\n";
    $logEntry .= "CODE: $code\n";
    $logEntry .= "STATUS: $status\n";
    $logEntry .= "MESSAGE: $message\n";
    $logEntry .= str_repeat("=", 80) . "\n";
    
    file_put_contents($logFile, $logEntry, FILE_APPEND);
}
?>