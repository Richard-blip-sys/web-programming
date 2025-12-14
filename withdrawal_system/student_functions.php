<?php
// student_functions.php - SIMPLE VERSION WITHOUT NOTIFICATIONS
require_once 'config.php';

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
        $stmt->bindParam(':student_id', $student_id);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch(PDOException $e) {
        return [];
    }
}

function submitWithdrawal($enrollment_id, $reason) {
    $database = new Database();
    $db = $database->getConnection();
    
    try {
        $query = "INSERT INTO withdrawal_requests (enrollment_id, reason, request_date) 
                  VALUES (:enrollment_id, :reason, NOW())";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':enrollment_id', $enrollment_id);
        $stmt->bindParam(':reason', $reason);
        return $stmt->execute();
    } catch(PDOException $e) {
        return false;
    }
}

function getWithdrawalHistory($student_id) {
    $database = new Database();
    $db = $database->getConnection();
    
    try {
        $query = "SELECT wr.request_date, wr.status, wr.reason, wr.review_notes,
                         s.subject_code, s.subject_name
                  FROM withdrawal_requests wr
                  JOIN enrollments e ON wr.enrollment_id = e.enrollment_id
                  JOIN subjects s ON e.subject_id = s.subject_id
                  WHERE e.student_id = :student_id
                  ORDER BY wr.request_date DESC";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':student_id', $student_id);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch(PDOException $e) {
        return [];
    }
}
?>