<?php
// faculty_functions.php - SIMPLE VERSION WITHOUT NOTIFICATIONS
require_once 'config.php';

function enrollStudent($faculty_id, $student_id, $subject_id) {
    $database = new Database();
    $db = $database->getConnection();
    
    try {
        $query = "INSERT INTO enrollments (student_id, subject_id, faculty_id, status, enrollment_date) 
                  VALUES (:student_id, :subject_id, :faculty_id, 'active', NOW())";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':student_id', $student_id, PDO::PARAM_INT);
        $stmt->bindParam(':subject_id', $subject_id, PDO::PARAM_INT);
        $stmt->bindParam(':faculty_id', $faculty_id, PDO::PARAM_INT);
        return $stmt->execute();
    } catch(PDOException $e) {
        return false;
    }
}

function getSubjects() {
    $database = new Database();
    $db = $database->getConnection();
    
    try {
        $query = "SELECT * FROM subjects ORDER BY subject_code";
        $stmt = $db->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch(PDOException $e) {
        return [];
    }
}

function getStudents() {
    $database = new Database();
    $db = $database->getConnection();
    
    try {
        $query = "SELECT user_id, username, first_name, last_name, email 
                  FROM users WHERE user_type = 'student' 
                  ORDER BY last_name, first_name";
        $stmt = $db->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch(PDOException $e) {
        return [];
    }
}

function getPendingWithdrawals() {
    $database = new Database();
    $db = $database->getConnection();
    
    try {
        $query = "SELECT wr.request_id, 
                         wr.reason, 
                         wr.request_date, 
                         wr.enrollment_id,
                         u.first_name, 
                         u.last_name, 
                         u.email as student_email,
                         s.subject_code, 
                         s.subject_name
                  FROM withdrawal_requests wr
                  INNER JOIN enrollments e ON wr.enrollment_id = e.enrollment_id
                  INNER JOIN users u ON e.student_id = u.user_id
                  INNER JOIN subjects s ON e.subject_id = s.subject_id
                  WHERE wr.status = 'pending'
                  ORDER BY wr.request_date DESC";
        
        $stmt = $db->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch(PDOException $e) {
        return [];
    }
}

function reviewWithdrawal($request_id, $faculty_id, $decision, $notes) {
    $database = new Database();
    $db = $database->getConnection();
    
    try {
        $db->beginTransaction();
        
        // Get withdrawal request details
        $query = "SELECT wr.request_id, wr.enrollment_id
                  FROM withdrawal_requests wr
                  WHERE wr.request_id = :request_id";
        
        $stmt = $db->prepare($query);
        $stmt->bindParam(':request_id', $request_id, PDO::PARAM_INT);
        $stmt->execute();
        $details = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if(!$details) {
            throw new Exception("Withdrawal request not found");
        }
        
        // Update withdrawal request status
        $query = "UPDATE withdrawal_requests 
                  SET status = :status, 
                      reviewed_by = :faculty_id, 
                      review_date = NOW(), 
                      review_notes = :notes
                  WHERE request_id = :request_id";
        
        $stmt = $db->prepare($query);
        $stmt->bindParam(':status', $decision, PDO::PARAM_STR);
        $stmt->bindParam(':faculty_id', $faculty_id, PDO::PARAM_INT);
        $stmt->bindParam(':notes', $notes, PDO::PARAM_STR);
        $stmt->bindParam(':request_id', $request_id, PDO::PARAM_INT);
        
        if(!$stmt->execute()) {
            throw new Exception("Failed to update withdrawal request");
        }
        
        // If approved, update enrollment status to withdrawn
        if($decision === 'approved') {
            $enrollment_id = $details['enrollment_id'];
            $query = "UPDATE enrollments 
                      SET status = 'withdrawn' 
                      WHERE enrollment_id = :enrollment_id";
            
            $stmt = $db->prepare($query);
            $stmt->bindParam(':enrollment_id', $enrollment_id, PDO::PARAM_INT);
            
            if(!$stmt->execute()) {
                throw new Exception("Failed to update enrollment status");
            }
        }
        
        $db->commit();
        return true;
        
    } catch(PDOException $e) {
        if(isset($db)) {
            $db->rollBack();
        }
        return false;
    } catch(Exception $e) {
        if(isset($db)) {
            $db->rollBack();
        }
        return false;
    }
}
?>