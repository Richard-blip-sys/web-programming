<?php
// student_functions.php - WITH EMAIL & NOTIFICATION SUPPORT
require_once 'config.php';
require_once 'email_system.php';

function registerStudent($username, $password, $email, $first_name, $last_name) {
    $database = new Database();
    $db = $database->getConnection();
    
    try {
        $password_hash = password_hash($password, PASSWORD_DEFAULT);
        $query = "INSERT INTO users (username, password_hash, email, first_name, last_name, user_type) 
                  VALUES (:username, :password_hash, :email, :first_name, :last_name, 'student')";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':username', $username);
        $stmt->bindParam(':password_hash', $password_hash);
        $stmt->bindParam(':email', $email);
        $stmt->bindParam(':first_name', $first_name);
        $stmt->bindParam(':last_name', $last_name);
        return $stmt->execute();
    } catch(PDOException $e) {
        error_log("Register error: " . $e->getMessage());
        return false;
    }
}

function getStudentEnrollments($student_id) {
    $database = new Database();
    $db = $database->getConnection();
    
    try {
        $query = "SELECT e.enrollment_id, s.subject_code, s.subject_name, 
                         s.units, e.enrollment_date, e.status,
                         u.first_name as faculty_first, u.last_name as faculty_last
                  FROM enrollments e
                  JOIN subjects s ON e.subject_id = s.subject_id
                  JOIN users u ON e.faculty_id = u.user_id
                  WHERE e.student_id = :student_id AND e.status = 'active'
                  ORDER BY s.subject_code";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':student_id', $student_id, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch(PDOException $e) {
        error_log("Get enrollments error: " . $e->getMessage());
        return [];
    }
}

function submitWithdrawal($enrollment_id, $reason) {
    $database = new Database();
    $db = $database->getConnection();
    
    try {
        // Start transaction
        $db->beginTransaction();
        
        // Verify the enrollment exists and is active
        $checkQuery = "SELECT enrollment_id FROM enrollments 
                       WHERE enrollment_id = :enrollment_id AND status = 'active'";
        $checkStmt = $db->prepare($checkQuery);
        $checkStmt->bindParam(':enrollment_id', $enrollment_id, PDO::PARAM_INT);
        $checkStmt->execute();
        
        if($checkStmt->rowCount() == 0) {
            $db->rollBack();
            error_log("Enrollment not found or not active: " . $enrollment_id);
            return false;
        }
        
        // Check for duplicate pending requests
        $duplicateCheck = "SELECT request_id FROM withdrawal_requests 
                          WHERE enrollment_id = :enrollment_id AND status = 'pending'";
        $dupStmt = $db->prepare($duplicateCheck);
        $dupStmt->bindParam(':enrollment_id', $enrollment_id, PDO::PARAM_INT);
        $dupStmt->execute();
        
        if($dupStmt->rowCount() > 0) {
            $db->rollBack();
            error_log("Duplicate pending request for enrollment: " . $enrollment_id);
            return false;
        }
        
        // Insert the withdrawal request
        $query = "INSERT INTO withdrawal_requests (enrollment_id, reason, request_date, status) 
                  VALUES (:enrollment_id, :reason, NOW(), 'pending')";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':enrollment_id', $enrollment_id, PDO::PARAM_INT);
        $stmt->bindParam(':reason', $reason, PDO::PARAM_STR);
        
        if($stmt->execute()) {
            $withdrawal_id = $db->lastInsertId();
            $db->commit();
            
            // Send email and notification to faculty (non-blocking)
            try {
                notifyFacultyNewWithdrawal($withdrawal_id);
            } catch(Exception $e) {
                // Log error but don't fail the withdrawal submission
                error_log("Notification error: " . $e->getMessage());
            }
            
            error_log("Withdrawal request created successfully for enrollment: " . $enrollment_id);
            return true;
        } else {
            $db->rollBack();
            error_log("Failed to insert withdrawal request");
            return false;
        }
        
    } catch(PDOException $e) {
        if(isset($db)) {
            $db->rollBack();
        }
        error_log("Submit withdrawal error: " . $e->getMessage());
        return false;
    }
}

function getWithdrawalHistory($student_id) {
    $database = new Database();
    $db = $database->getConnection();
    
    try {
        $query = "SELECT wr.request_id, wr.request_date, wr.status, wr.reason, 
                         wr.review_notes, wr.review_date,
                         s.subject_code, s.subject_name
                  FROM withdrawal_requests wr
                  JOIN enrollments e ON wr.enrollment_id = e.enrollment_id
                  JOIN subjects s ON e.subject_id = s.subject_id
                  WHERE e.student_id = :student_id
                  ORDER BY wr.request_date DESC";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':student_id', $student_id, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch(PDOException $e) {
        error_log("Get withdrawal history error: " . $e->getMessage());
        return [];
    }
}

function hasPendingRequest($enrollment_id) {
    $database = new Database();
    $db = $database->getConnection();
    
    try {
        $query = "SELECT COUNT(*) as count FROM withdrawal_requests 
                  WHERE enrollment_id = :enrollment_id AND status = 'pending'";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':enrollment_id', $enrollment_id, PDO::PARAM_INT);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result['count'] > 0;
    } catch(PDOException $e) {
        error_log("Check pending request error: " . $e->getMessage());
        return false;
    }
}
?>