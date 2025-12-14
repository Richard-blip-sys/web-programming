<?php

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require_once 'PHPMailer/src/Exception.php';
require_once 'PHPMailer/src/PHPMailer.php';
require_once 'PHPMailer/src/SMTP.php';
require_once 'config.php';



define('SMTP_HOST', 'smtp.gmail.com');           // Gmail SMTP server
define('SMTP_PORT', 587);                         // Gmail port
define('SMTP_USERNAME', 'chardy2106@gmail.com');  // YOUR Gmail address
define('SMTP_PASSWORD', 'kmgp jfbo ammx atxz');     // YOUR Gmail App Password
define('SMTP_FROM_EMAIL', 'chardy2106@gmail.com');// YOUR Gmail address
define('SMTP_FROM_NAME', 'Course Withdrawal System');
define('SMTP_SECURE', PHPMailer::ENCRYPTION_STARTTLS);



function sendEmail($to, $toName, $subject, $htmlBody, $altBody = '') {
    $mail = new PHPMailer(true);
    
    try {
        // Server settings
        $mail->isSMTP();
        $mail->Host       = SMTP_HOST;
        $mail->SMTPAuth   = true;
        $mail->Username   = SMTP_USERNAME;
        $mail->Password   = SMTP_PASSWORD;
        $mail->SMTPSecure = SMTP_SECURE;
        $mail->Port       = SMTP_PORT;
        
        // Disable debug output in production
        $mail->SMTPDebug = 0; // 0 = off, 2 = debug mode
        
        // Recipients
        $mail->setFrom(SMTP_FROM_EMAIL, SMTP_FROM_NAME);
        $mail->addAddress($to, $toName);
        $mail->addReplyTo(SMTP_FROM_EMAIL, SMTP_FROM_NAME);
        
        // Content
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body    = $htmlBody;
        $mail->AltBody = $altBody ? $altBody : strip_tags($htmlBody);
        
        // Send
        $result = $mail->send();
        
        // Log success
        logEmail($to, $subject, 'sent', 'Email sent successfully');
        
        return true;
        
    } catch (Exception $e) {
        // Log error
        logEmail($to, $subject, 'failed', "Error: {$mail->ErrorInfo}");
        error_log("Email Error: {$mail->ErrorInfo}");
        return false;
    }
}


function logEmail($to, $subject, $status, $message) {
    $logFile = 'email_log.txt';
    $timestamp = date('Y-m-d H:i:s');
    $logEntry = "\n[$timestamp] TO: $to | SUBJECT: $subject | STATUS: $status | $message\n";
    file_put_contents($logFile, $logEntry, FILE_APPEND);
}



function createNotification($user_id, $type, $title, $message, $link = null) {
    $database = new Database();
    $db = $database->getConnection();
    
    try {
        $query = "INSERT INTO notifications (user_id, type, title, message, link, is_read, created_at) 
                  VALUES (:user_id, :type, :title, :message, :link, 0, NOW())";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
        $stmt->bindParam(':type', $type, PDO::PARAM_STR);
        $stmt->bindParam(':title', $title, PDO::PARAM_STR);
        $stmt->bindParam(':message', $message, PDO::PARAM_STR);
        $stmt->bindParam(':link', $link, PDO::PARAM_STR);
        
        return $stmt->execute();
    } catch(PDOException $e) {
        error_log("Notification error: " . $e->getMessage());
        return false;
    }
}



function notifyFacultyNewWithdrawal($withdrawal_id) {
    $database = new Database();
    $db = $database->getConnection();
    
    // Get withdrawal details
    $query = "SELECT 
                wr.request_id,
                wr.reason,
                s.subject_code,
                s.subject_name,
                CONCAT(student.first_name, ' ', student.last_name) as student_name,
                student.email as student_email,
                faculty.user_id as faculty_id,
                faculty.email as faculty_email,
                CONCAT(faculty.first_name, ' ', faculty.last_name) as faculty_name
              FROM withdrawal_requests wr
              JOIN enrollments e ON wr.enrollment_id = e.enrollment_id
              JOIN subjects s ON e.subject_id = s.subject_id
              JOIN users student ON e.student_id = student.user_id
              JOIN users faculty ON e.faculty_id = faculty.user_id
              WHERE wr.request_id = :withdrawal_id";
    
    $stmt = $db->prepare($query);
    $stmt->bindParam(':withdrawal_id', $withdrawal_id, PDO::PARAM_INT);
    $stmt->execute();
    $data = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if(!$data) return false;
    
    // Create in-app notification
    $notifTitle = "New Withdrawal Request";
    $notifMessage = "{$data['student_name']} requested withdrawal from {$data['subject_code']}";
    createNotification($data['faculty_id'], 'withdrawal_request', $notifTitle, $notifMessage, 'faculty_dashboard.php');
    
    // Send email
    $subject = "New Withdrawal Request - {$data['subject_code']}";
    
    $htmlBody = "
    <!DOCTYPE html>
    <html>
    <head>
        <style>
            body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
            .container { max-width: 600px; margin: 0 auto; padding: 20px; }
            .header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 30px; text-align: center; border-radius: 10px 10px 0 0; }
            .header h1 { margin: 0; font-size: 24px; }
            .content { background: #f9f9f9; padding: 30px; border: 1px solid #e0e0e0; }
            .info-box { background: #e0e7ff; padding: 20px; border-radius: 8px; margin: 20px 0; border-left: 4px solid #667eea; }
            .info-box strong { color: #3730a3; }
            .button { display: inline-block; padding: 15px 30px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; text-decoration: none; border-radius: 8px; margin-top: 20px; font-weight: bold; }
            .footer { background: #f1f1f1; padding: 20px; text-align: center; font-size: 12px; color: #666; border-radius: 0 0 10px 10px; }
        </style>
    </head>
    <body>
        <div class='container'>
            <div class='header'>
                <h1>📝 New Withdrawal Request</h1>
            </div>
            <div class='content'>
                <p>Dear Prof. {$data['faculty_name']},</p>
                <p>A new withdrawal request has been submitted and requires your review.</p>
                
                <div class='info-box'>
                    <strong>Student:</strong> {$data['student_name']}<br>
                    <strong>Email:</strong> {$data['student_email']}<br>
                    <strong>Subject:</strong> {$data['subject_code']} - {$data['subject_name']}<br>
                    <strong>Reason:</strong> {$data['reason']}
                </div>
                
                <p>Please log in to your dashboard to review and approve or reject this request.</p>
                
                <a href='http://localhost/withdrawal_system/faculty_dashboard.php' class='button'>Review Request</a>
            </div>
            <div class='footer'>
                <p>This is an automated message from the Course Withdrawal System</p>
                <p>Please do not reply to this email</p>
            </div>
        </div>
    </body>
    </html>
    ";
    
    return sendEmail($data['faculty_email'], $data['faculty_name'], $subject, $htmlBody);
}



function notifyStudentWithdrawalDecision($withdrawal_id, $decision, $notes = '') {
    $database = new Database();
    $db = $database->getConnection();
    
    // Get withdrawal details
    $query = "SELECT 
                s.subject_code,
                s.subject_name,
                CONCAT(student.first_name, ' ', student.last_name) as student_name,
                student.user_id as student_id,
                student.email as student_email,
                CONCAT(faculty.first_name, ' ', faculty.last_name) as faculty_name
              FROM withdrawal_requests wr
              JOIN enrollments e ON wr.enrollment_id = e.enrollment_id
              JOIN subjects s ON e.subject_id = s.subject_id
              JOIN users student ON e.student_id = student.user_id
              JOIN users faculty ON e.faculty_id = faculty.user_id
              WHERE wr.request_id = :withdrawal_id";
    
    $stmt = $db->prepare($query);
    $stmt->bindParam(':withdrawal_id', $withdrawal_id, PDO::PARAM_INT);
    $stmt->execute();
    $data = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if(!$data) return false;
    
    $statusText = ucfirst($decision);
    $statusColor = $decision === 'approved' ? '#10b981' : '#ef4444';
    $statusEmoji = $decision === 'approved' ? '✅' : '❌';
    
    // Create in-app notification
    $notifTitle = "Withdrawal Request {$statusText}";
    $notifMessage = "Your withdrawal request for {$data['subject_code']} has been {$decision}";
    createNotification($data['student_id'], 'withdrawal_decision', $notifTitle, $notifMessage, 'student_dashboard.php');
    
    // Send email
    $subject = "Withdrawal Request {$statusText} - {$data['subject_code']}";
    
    $htmlBody = "
    <!DOCTYPE html>
    <html>
    <head>
        <style>
            body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
            .container { max-width: 600px; margin: 0 auto; padding: 20px; }
            .header { background: {$statusColor}; color: white; padding: 30px; text-align: center; border-radius: 10px 10px 0 0; }
            .header h1 { margin: 0; font-size: 24px; }
            .content { background: #f9f9f9; padding: 30px; border: 1px solid #e0e0e0; }
            .info-box { background: #e0e7ff; padding: 20px; border-radius: 8px; margin: 20px 0; border-left: 4px solid {$statusColor}; }
            .status-badge { display: inline-block; padding: 10px 20px; background: {$statusColor}; color: white; border-radius: 20px; font-weight: bold; margin: 10px 0; }
            .footer { background: #f1f1f1; padding: 20px; text-align: center; font-size: 12px; color: #666; border-radius: 0 0 10px 10px; }
        </style>
    </head>
    <body>
        <div class='container'>
            <div class='header'>
                <h1>{$statusEmoji} Withdrawal Request Update</h1>
            </div>
            <div class='content'>
                <p>Dear {$data['student_name']},</p>
                <p>Your withdrawal request has been reviewed by {$data['faculty_name']}.</p>
                
                <div class='info-box'>
                    <strong>Subject:</strong> {$data['subject_code']} - {$data['subject_name']}<br>
                    <strong>Status:</strong> <span class='status-badge'>{$statusText}</span><br>
                    " . ($notes ? "<strong>Faculty Notes:</strong> " . htmlspecialchars($notes) : "") . "
                </div>
                
                <p>" . ($decision === 'approved' ? 
                    "Your withdrawal has been approved. You have been unenrolled from this subject." : 
                    "Your withdrawal request was not approved. You remain enrolled in this subject.") . "</p>
            </div>
            <div class='footer'>
                <p>This is an automated message from the Course Withdrawal System</p>
                <p>Please do not reply to this email</p>
            </div>
        </div>
    </body>
    </html>
    ";
    
    return sendEmail($data['student_email'], $data['student_name'], $subject, $htmlBody);
}



function getUnreadNotificationCount($user_id) {
    $database = new Database();
    $db = $database->getConnection();
    
    try {
        $query = "SELECT COUNT(*) as count FROM notifications 
                  WHERE user_id = :user_id AND is_read = 0";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result['count'];
    } catch(PDOException $e) {
        return 0;
    }
}



function getUserNotifications($user_id, $limit = 10) {
    $database = new Database();
    $db = $database->getConnection();
    
    try {
        $query = "SELECT * FROM notifications 
                  WHERE user_id = :user_id 
                  ORDER BY created_at DESC 
                  LIMIT :limit";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
        $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch(PDOException $e) {
        return [];
    }
}


function markNotificationRead($notification_id) {
    $database = new Database();
    $db = $database->getConnection();
    
    try {
        $query = "UPDATE notifications SET is_read = 1 WHERE notification_id = :id";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':id', $notification_id, PDO::PARAM_INT);
        return $stmt->execute();
    } catch(PDOException $e) {
        return false;
    }
}



function markAllNotificationsRead($user_id) {
    $database = new Database();
    $db = $database->getConnection();
    
    try {
        $query = "UPDATE notifications SET is_read = 1 WHERE user_id = :user_id";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
        return $stmt->execute();
    } catch(PDOException $e) {
        return false;
    }
}
?>