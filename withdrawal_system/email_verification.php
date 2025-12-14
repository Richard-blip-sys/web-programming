<?php


use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require_once 'config.php';
require_once 'PHPMailer/src/Exception.php';
require_once 'PHPMailer/src/PHPMailer.php';
require_once 'PHPMailer/src/SMTP.php';


if(!defined('SMTP_USERNAME')) {
    define('SMTP_USERNAME', 'chardy2106@gmail.com');  // Your Gmail address
}
if(!defined('SMTP_PASSWORD')) {
    define('SMTP_PASSWORD', 'kmgp jfbo ammx atxz');    // Your Gmail App Password
}
if(!defined('SMTP_FROM_NAME')) {
    define('SMTP_FROM_NAME', 'Course Withdrawal System');
}

/**
 * Generate a 6-digit OTP code
 */
function generateOTP() {
    return sprintf("%06d", mt_rand(0, 999999));
}

/**
 * Send real email via Gmail SMTP
 */
function sendOTPEmail($email, $otp, $name) {
    $mail = new PHPMailer(true);
    
    try {
        // Server settings
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = SMTP_USERNAME;
        $mail->Password   = SMTP_PASSWORD;
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;
        
        // Reduce timeout and enable debugging in development
        $mail->Timeout    = 30;
        $mail->SMTPDebug  = 0; // Set to 2 for debugging
        
        // Recipients
        $mail->setFrom(SMTP_USERNAME, SMTP_FROM_NAME);
        $mail->addAddress($email, $name);
        $mail->addReplyTo(SMTP_USERNAME, SMTP_FROM_NAME);
        
        // Content
        $mail->isHTML(true);
        $mail->CharSet = 'UTF-8';
        $mail->Subject = 'Your Verification Code - Course Withdrawal System';
        
        $mail->Body = "
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset='UTF-8'>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; margin: 0; padding: 0; background: #f4f4f4; }
                .email-wrapper { max-width: 600px; margin: 0 auto; background: #ffffff; }
                .header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 30px; text-align: center; }
                .header h1 { margin: 0; font-size: 28px; }
                .content { padding: 40px 30px; }
                .otp-box { background: #f0f4ff; border: 3px dashed #667eea; border-radius: 10px; padding: 30px; text-align: center; margin: 30px 0; }
                .otp-code { font-size: 48px; font-weight: bold; color: #667eea; letter-spacing: 15px; margin: 10px 0; font-family: 'Courier New', monospace; }
                .info-box { background: #fff3cd; border-left: 4px solid #ffc107; padding: 15px; margin: 20px 0; border-radius: 5px; }
                .footer { background: #f8f9fa; padding: 20px; text-align: center; font-size: 12px; color: #666; }
                .warning { color: #dc3545; font-weight: bold; margin-top: 20px; }
            </style>
        </head>
        <body>
            <div class='email-wrapper'>
                <div class='header'>
                    <h1>🔐 Email Verification</h1>
                </div>
                <div class='content'>
                    <p>Hello <strong>" . htmlspecialchars($name) . "</strong>,</p>
                    <p>Thank you for registering with the <strong>Course Withdrawal System</strong>. To complete your registration and activate your account, please verify your email address.</p>
                    
                    <div class='otp-box'>
                        <p style='margin: 0; color: #666; font-size: 14px;'>Your verification code is:</p>
                        <div class='otp-code'>" . $otp . "</div>
                        <p style='margin: 10px 0 0 0; color: #999; font-size: 12px;'>Enter this code on the verification page</p>
                    </div>
                    
                    <div class='info-box'>
                        <strong>⏰ Important Information:</strong>
                        <ul style='margin: 10px 0 0 0; padding-left: 20px;'>
                            <li>This code will <strong>expire in 10 minutes</strong></li>
                            <li>Use this code only on the official verification page</li>
                            <li>Do not share this code with anyone</li>
                        </ul>
                    </div>
                    
                    <p style='color: #666; font-size: 14px; margin-top: 20px;'>If you didn't create an account with us, please ignore this email or contact our support team if you have concerns.</p>
                    
                    <p class='warning'>⚠️ Security Alert: Never share this verification code with anyone, including our staff!</p>
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
        
        // Plain text alternative
        $mail->AltBody = "Your Verification Code: $otp\n\n"
                       . "Hello $name,\n\n"
                       . "Thank you for registering with the Course Withdrawal System.\n"
                       . "Your verification code is: $otp\n\n"
                       . "This code will expire in 10 minutes.\n\n"
                       . "If you didn't request this code, please ignore this email.\n\n"
                       . "Best regards,\n"
                       . "Course Withdrawal System";
        
        // Send the email
        $result = $mail->send();
        
        // Log success
        logOTPEmail($email, $otp, 'SENT', 'Email successfully sent to ' . $email);
        
        return true;
        
    } catch (Exception $e) {
        // Log detailed error
        $errorMsg = "Mailer Error: {$mail->ErrorInfo}";
        logOTPEmail($email, $otp, 'FAILED', $errorMsg);
        error_log($errorMsg);
        
        // Return false but don't crash the system
        return false;
    }
}

/**
 * Log OTP email attempts for debugging
 */
function logOTPEmail($to, $otp, $status, $message) {
    $logFile = 'email_verification_log.txt';
    $timestamp = date('Y-m-d H:i:s');
    
    $logEntry = "\n" . str_repeat("=", 80) . "\n";
    $logEntry .= "TIMESTAMP: $timestamp\n";
    $logEntry .= "TO: $to\n";
    $logEntry .= "OTP: $otp\n";
    $logEntry .= "STATUS: $status\n";
    $logEntry .= "MESSAGE: $message\n";
    $logEntry .= str_repeat("=", 80) . "\n";
    
    file_put_contents($logFile, $logEntry, FILE_APPEND);
}

/**
 * Create verification record and send OTP
 */
function createVerification($user_id, $email, $name) {
    $database = new Database();
    $db = $database->getConnection();
    
    try {
        // Generate OTP
        $otp = generateOTP();
        $expires_at = date('Y-m-d H:i:s', strtotime('+10 minutes'));
        $ip_address = isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : 'unknown';
        
        // Save to database first
        $query = "INSERT INTO email_verifications (user_id, email, verification_code, expires_at, ip_address) 
                  VALUES (:user_id, :email, :code, :expires_at, :ip)";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':user_id', $user_id);
        $stmt->bindParam(':email', $email);
        $stmt->bindParam(':code', $otp);
        $stmt->bindParam(':expires_at', $expires_at);
        $stmt->bindParam(':ip', $ip_address);
        $stmt->execute();
        
        // Update user record
        $query = "UPDATE users 
                  SET verification_code = :code, 
                      verification_expires = :expires_at 
                  WHERE user_id = :user_id";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':code', $otp);
        $stmt->bindParam(':expires_at', $expires_at);
        $stmt->bindParam(':user_id', $user_id);
        $stmt->execute();
        
        // Now send the email
        $emailSent = sendOTPEmail($email, $otp, $name);
        
        if(!$emailSent) {
            // Email failed but OTP is still valid in database
            error_log("WARNING: OTP generated for user $user_id but email delivery failed. Check SMTP settings.");
        }
        
        // Return true even if email fails - user can still verify if they see the log
        return true;
        
    } catch(PDOException $e) {
        error_log("Database Error in createVerification: " . $e->getMessage());
        return false;
    }
}

/**
 * Verify OTP code - FIXED VERSION
 */
function verifyOTP($user_id, $code) {
    $database = new Database();
    $db = $database->getConnection();
    
    try {
        // First, check if the code exists and hasn't expired
        $query = "SELECT user_id, verification_code, verification_expires 
                  FROM users 
                  WHERE user_id = :user_id";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
        $stmt->execute();
        
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if(!$user) {
            error_log("User not found: $user_id");
            return false;
        }
        
        // Check if code matches
        if($user['verification_code'] !== $code) {
            error_log("Code mismatch. Expected: {$user['verification_code']}, Got: $code");
            return false;
        }
        
        // Check if code is expired
        if(strtotime($user['verification_expires']) < time()) {
            error_log("Code expired. Expires: {$user['verification_expires']}, Now: " . date('Y-m-d H:i:s'));
            return false;
        }
        
        // Code is valid - mark as verified
        $query = "UPDATE users 
                  SET email_verified = 1, 
                      verification_code = NULL, 
                      verification_expires = NULL 
                  WHERE user_id = :user_id";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
        
        if($stmt->execute()) {
            // Log successful verification in email_verifications table
            $query = "UPDATE email_verifications 
                      SET verified_at = NOW() 
                      WHERE user_id = :user_id 
                      AND verification_code = :code";
            $stmt = $db->prepare($query);
            $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
            $stmt->bindParam(':code', $code);
            $stmt->execute();
            
            error_log("User $user_id verified successfully");
            return true;
        }
        
        return false;
        
    } catch(PDOException $e) {
        error_log("Verify Error: " . $e->getMessage());
        return false;
    }
}

/**
 * Resend OTP code
 */
function resendOTP($user_id) {
    $database = new Database();
    $db = $database->getConnection();
    
    try {
        // Get user info
        $query = "SELECT email, first_name, last_name FROM users WHERE user_id = :user_id";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':user_id', $user_id);
        $stmt->execute();
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if($user) {
            $name = $user['first_name'] . ' ' . $user['last_name'];
            return createVerification($user_id, $user['email'], $name);
        }
        
        return false;
    } catch(PDOException $e) {
        error_log("Resend Error: " . $e->getMessage());
        return false;
    }
}

/**
 * Check if email is already verified
 */
function isEmailVerified($user_id) {
    $database = new Database();
    $db = $database->getConnection();
    
    try {
        $query = "SELECT email_verified FROM users WHERE user_id = :user_id";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':user_id', $user_id);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return $result && $result['email_verified'] == 1;
    } catch(PDOException $e) {
        return false;
    }
}
?>