<?php
/**
 * email_verification.php
 * Email verification functions for OTP system
 */

require_once 'config.php';

/**
 * Generate a 6-digit OTP code
 */
function generateOTP() {
    return sprintf("%06d", mt_rand(0, 999999));
}

/**
 * Send OTP email to user
 */
function sendOTPEmail($email, $otp, $name) {
    $subject = "Your Verification Code - Course Withdrawal System";
    
    $message = "
    <!DOCTYPE html>
    <html>
    <head>
        <style>
            body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; margin: 0; padding: 0; }
            .container { max-width: 600px; margin: 0 auto; padding: 20px; }
            .header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 30px; text-align: center; border-radius: 10px 10px 0 0; }
            .header h1 { margin: 0; font-size: 28px; }
            .content { background: #ffffff; padding: 40px 30px; border: 1px solid #e0e0e0; }
            .otp-box { background: #f0f4ff; border: 2px dashed #667eea; border-radius: 10px; padding: 30px; text-align: center; margin: 30px 0; }
            .otp-code { font-size: 48px; font-weight: bold; color: #667eea; letter-spacing: 10px; margin: 10px 0; font-family: 'Courier New', monospace; }
            .info { background: #fff3cd; border-left: 4px solid #ffc107; padding: 15px; margin: 20px 0; }
            .footer { background: #f8f9fa; padding: 20px; text-align: center; font-size: 12px; color: #666; border-radius: 0 0 10px 10px; }
            .warning { color: #dc3545; font-weight: bold; }
        </style>
    </head>
    <body>
        <div class='container'>
            <div class='header'>
                <h1>🔐 Email Verification</h1>
            </div>
            <div class='content'>
                <p>Hello <strong>$name</strong>,</p>
                <p>Thank you for registering with the Course Withdrawal System. To complete your registration, please verify your email address.</p>
                
                <div class='otp-box'>
                    <p style='margin: 0; color: #666; font-size: 14px;'>Your verification code is:</p>
                    <div class='otp-code'>$otp</div>
                    <p style='margin: 0; color: #999; font-size: 12px;'>Enter this code on the verification page</p>
                </div>
                
                <div class='info'>
                    <strong>⏰ Important:</strong> This code will expire in <strong>10 minutes</strong>.
                </div>
                
                <p style='color: #666; font-size: 14px;'>If you didn't request this code, please ignore this email or contact support if you have concerns.</p>
                
                <p class='warning'>⚠️ Never share this code with anyone!</p>
            </div>
            <div class='footer'>
                <p>This is an automated message from the Course Withdrawal System</p>
                <p>© " . date('Y') . " Course Withdrawal System. All rights reserved.</p>
            </div>
        </div>
    </body>
    </html>
    ";
    
    // For development: Log to file instead of sending
    $log = "\n\n" . str_repeat("=", 80) . "\n";
    $log .= "DATE: " . date('Y-m-d H:i:s') . "\n";
    $log .= "TO: $email\n";
    $log .= "NAME: $name\n";
    $log .= "OTP CODE: $otp\n";
    $log .= "SUBJECT: $subject\n";
    $log .= str_repeat("=", 80) . "\n";
    
    file_put_contents('email_verification_log.txt', $log, FILE_APPEND);
    
    // TODO: In production, use PHPMailer to actually send the email
    // For now, we're just logging it
    
    return true; // Simulate success
}

/**
 * Create verification record for a user
 */
function createVerification($user_id, $email, $name) {
    $database = new Database();
    $db = $database->getConnection();
    
    try {
        // Generate OTP
        $otp = generateOTP();
        $expires_at = date('Y-m-d H:i:s', strtotime('+10 minutes'));
        $ip_address = $_SERVER['REMOTE_ADDR'];
        
        // Save to database
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
        
        // Send OTP email
        sendOTPEmail($email, $otp, $name);
        
        return true;
    } catch(PDOException $e) {
        error_log("Verification Error: " . $e->getMessage());
        return false;
    }
}

/**
 * Verify OTP code
 */
function verifyOTP($user_id, $code) {
    $database = new Database();
    $db = $database->getConnection();
    
    try {
        // Check if code exists and is not expired
        $query = "SELECT * FROM users 
                  WHERE user_id = :user_id 
                  AND verification_code = :code 
                  AND verification_expires > NOW()";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':user_id', $user_id);
        $stmt->bindParam(':code', $code);
        $stmt->execute();
        
        if($stmt->rowCount() > 0) {
            // Code is valid - mark as verified
            $query = "UPDATE users 
                      SET email_verified = 1, 
                          verification_code = NULL, 
                          verification_expires = NULL 
                      WHERE user_id = :user_id";
            $stmt = $db->prepare($query);
            $stmt->bindParam(':user_id', $user_id);
            $stmt->execute();
            
            // Log verification
            $query = "UPDATE email_verifications 
                      SET verified_at = NOW() 
                      WHERE user_id = :user_id 
                      AND verification_code = :code";
            $stmt = $db->prepare($query);
            $stmt->bindParam(':user_id', $user_id);
            $stmt->bindParam(':code', $code);
            $stmt->execute();
            
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