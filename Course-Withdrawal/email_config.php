<?php
// email_config.php - Email notification functions

function sendEmail($to, $subject, $message) {
    // Email headers
    $headers = "MIME-Version: 1.0" . "\r\n";
    $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
    $headers .= "From: Course Withdrawal System <noreply@university.edu>" . "\r\n";
    

    $log = "\n\n" . date('Y-m-d H:i:s') . "\n";
    $log .= "To: $to\n";
    $log .= "Subject: $subject\n";
    $log .= "Message: $message\n";
    $log .= "-----------------------------------\n";
    
    file_put_contents('email_log.txt', $log, FILE_APPEND);
    return true; // Simulate success
}

function notifyFacultyNewWithdrawal($faculty_email, $faculty_name, $student_name, $subject_code, $subject_name, $reason) {
    $subject = "New Withdrawal Request - $subject_code";
    
    $message = "
    <html>
    <head>
        <style>
            body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
            .container { max-width: 600px; margin: 0 auto; padding: 20px; }
            .header { background: #667eea; color: white; padding: 20px; border-radius: 5px 5px 0 0; }
            .content { background: #f9f9f9; padding: 20px; border: 1px solid #ddd; }
            .footer { background: #f1f1f1; padding: 15px; text-align: center; font-size: 12px; color: #666; }
            .button { display: inline-block; padding: 12px 24px; background: #667eea; color: white; text-decoration: none; border-radius: 5px; margin-top: 15px; }
            .info-box { background: #e0e7ff; padding: 15px; border-radius: 5px; margin: 15px 0; }
        </style>
    </head>
    <body>
        <div class='container'>
            <div class='header'>
                <h2>New Withdrawal Request</h2>
            </div>
            <div class='content'>
                <p>Dear Prof. $faculty_name,</p>
                <p>A new withdrawal request has been submitted and requires your review.</p>
                
                <div class='info-box'>
                    <strong>Student:</strong> $student_name<br>
                    <strong>Subject:</strong> $subject_code - $subject_name<br>
                    <strong>Reason:</strong> $reason
                </div>
                
                <p>Please log in to your dashboard to review and approve or reject this request.</p>
                
                <a href='http://localhost/your-project/login.php' class='button'>Review Request</a>
            </div>
            <div class='footer'>
                <p>This is an automated message from the Course Withdrawal System</p>
            </div>
        </div>
    </body>
    </html>
    ";
    
    return sendEmail($faculty_email, $subject, $message);
}

function notifyStudentWithdrawalDecision($student_email, $student_name, $subject_code, $subject_name, $decision, $notes) {
    $subject = "Withdrawal Request " . ucfirst($decision) . " - $subject_code";
    
    $status_color = $decision === 'approved' ? '#10b981' : '#ef4444';
    $status_text = ucfirst($decision);
    
    $message = "
    <html>
    <head>
        <style>
            body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
            .container { max-width: 600px; margin: 0 auto; padding: 20px; }
            .header { background: $status_color; color: white; padding: 20px; border-radius: 5px 5px 0 0; }
            .content { background: #f9f9f9; padding: 20px; border: 1px solid #ddd; }
            .footer { background: #f1f1f1; padding: 15px; text-align: center; font-size: 12px; color: #666; }
            .info-box { background: #e0e7ff; padding: 15px; border-radius: 5px; margin: 15px 0; }
            .status-badge { display: inline-block; padding: 8px 16px; background: $status_color; color: white; border-radius: 20px; font-weight: bold; }
        </style>
    </head>
    <body>
        <div class='container'>
            <div class='header'>
                <h2>Withdrawal Request Update</h2>
            </div>
            <div class='content'>
                <p>Dear $student_name,</p>
                <p>Your withdrawal request has been reviewed.</p>
                
                <div class='info-box'>
                    <strong>Subject:</strong> $subject_code - $subject_name<br>
                    <strong>Status:</strong> <span class='status-badge'>$status_text</span><br>
                    " . ($notes ? "<strong>Faculty Notes:</strong> $notes" : "") . "
                </div>
                
                <p>" . ($decision === 'approved' ? 
                    "Your withdrawal has been approved. You have been unenrolled from this subject." : 
                    "Your withdrawal request was not approved. You remain enrolled in this subject.") . "</p>
            </div>
            <div class='footer'>
                <p>This is an automated message from the Course Withdrawal System</p>
            </div>
        </div>
    </body>
    </html>
    ";
    
    return sendEmail($student_email, $subject, $message);
}
?>