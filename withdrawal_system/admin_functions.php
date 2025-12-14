<?php
// admin_functions.php - Functions for managing subjects
require_once 'config.php';

function addSubject($subject_code, $subject_name, $description, $units) {
    $database = new Database();
    $db = $database->getConnection();
    
    try {
        $query = "INSERT INTO subjects (subject_code, subject_name, description, units) 
                  VALUES (:subject_code, :subject_name, :description, :units)";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':subject_code', $subject_code);
        $stmt->bindParam(':subject_name', $subject_name);
        $stmt->bindParam(':description', $description);
        $stmt->bindParam(':units', $units);
        return $stmt->execute();
    } catch(PDOException $e) {
        return false;
    }
}

function updateSubject($subject_id, $subject_code, $subject_name, $description, $units) {
    $database = new Database();
    $db = $database->getConnection();
    
    try {
        $query = "UPDATE subjects 
                  SET subject_code = :subject_code, 
                      subject_name = :subject_name, 
                      description = :description, 
                      units = :units 
                  WHERE subject_id = :subject_id";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':subject_id', $subject_id);
        $stmt->bindParam(':subject_code', $subject_code);
        $stmt->bindParam(':subject_name', $subject_name);
        $stmt->bindParam(':description', $description);
        $stmt->bindParam(':units', $units);
        return $stmt->execute();
    } catch(PDOException $e) {
        return false;
    }
}

function deleteSubject($subject_id) {
    $database = new Database();
    $db = $database->getConnection();
    
    try {
        // Check if subject has enrollments
        $check = "SELECT COUNT(*) as count FROM enrollments WHERE subject_id = :subject_id";
        $stmt = $db->prepare($check);
        $stmt->bindParam(':subject_id', $subject_id);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if($result['count'] > 0) {
            return false; // Cannot delete subject with existing enrollments
        }
        
        $query = "DELETE FROM subjects WHERE subject_id = :subject_id";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':subject_id', $subject_id);
        return $stmt->execute();
    } catch(PDOException $e) {
        return false;
    }
}

function getAllSubjectsWithCount() {
    $database = new Database();
    $db = $database->getConnection();
    
    $query = "SELECT s.*, 
              (SELECT COUNT(*) FROM enrollments e WHERE e.subject_id = s.subject_id AND e.status = 'active') as enrolled_count
              FROM subjects s 
              ORDER BY s.subject_code";
    $stmt = $db->prepare($query);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function getSubjectById($subject_id) {
    $database = new Database();
    $db = $database->getConnection();
    
    $query = "SELECT * FROM subjects WHERE subject_id = :subject_id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':subject_id', $subject_id);
    $stmt->execute();
    return $stmt->fetch(PDO::FETCH_ASSOC);
}
?>