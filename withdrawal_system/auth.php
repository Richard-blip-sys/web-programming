<?php
session_start();

function login($username, $password) {
    $database = new Database();
    $db = $database->getConnection();
    
    $query = "SELECT user_id, username, password_hash, user_type, first_name, last_name 
              FROM users WHERE username = :username";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':username', $username);
    $stmt->execute();
    
    if($stmt->rowCount() > 0) {
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if(password_verify($password, $row['password_hash'])) {
            $_SESSION['user_id'] = $row['user_id'];
            $_SESSION['username'] = $row['username'];
            $_SESSION['user_type'] = $row['user_type'];
            $_SESSION['name'] = $row['first_name'] . ' ' . $row['last_name'];
            return true;
        }
    }
    return false;
}

function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function isFaculty() {
    return isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'faculty';
}

function isStudent() {
    return isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'student';
}

function logout() {
    session_destroy();
}
