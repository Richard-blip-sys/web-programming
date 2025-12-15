<?php
/**
 * FIXED AUTHENTICATION SYSTEM
 * Replace your current auth.php with this code
 */

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once 'config.php';

/**
 * Login function - FIXED VERSION
 */
function login($username, $password) {
    try {
        $database = new Database();
        $db = $database->getConnection();
        
        // Trim input
        $username = trim($username);
        $password = trim($password);
        
        // Get user from database
        $query = "SELECT user_id, username, password_hash, user_type, first_name, last_name, email_verified 
                  FROM users 
                  WHERE username = :username 
                  LIMIT 1";
        
        $stmt = $db->prepare($query);
        $stmt->bindParam(':username', $username, PDO::PARAM_STR);
        $stmt->execute();
        
        if($stmt->rowCount() > 0) {
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            
            // Debug log (remove after testing)
            error_log("Login attempt - Username: " . $username);
            error_log("User type: " . $row['user_type']);
            error_log("Email verified: " . $row['email_verified']);
            
            // Verify password
            if(password_verify($password, $row['password_hash'])) {
                
                // For admin and faculty, skip email verification check
                if($row['user_type'] === 'admin' || $row['user_type'] === 'faculty') {
                    // Set session variables
                    $_SESSION['user_id'] = $row['user_id'];
                    $_SESSION['username'] = $row['username'];
                    $_SESSION['user_type'] = $row['user_type'];
                    $_SESSION['name'] = $row['first_name'] . ' ' . $row['last_name'];
                    $_SESSION['login_time'] = time();
                    
                    error_log("Login SUCCESS - User: " . $username . " | Type: " . $row['user_type']);
                    return true;
                }
                
                // For students, check email verification
                if($row['user_type'] === 'student') {
                    if($row['email_verified'] == 1) {
                        $_SESSION['user_id'] = $row['user_id'];
                        $_SESSION['username'] = $row['username'];
                        $_SESSION['user_type'] = $row['user_type'];
                        $_SESSION['name'] = $row['first_name'] . ' ' . $row['last_name'];
                        $_SESSION['login_time'] = time();
                        
                        error_log("Login SUCCESS - Student: " . $username);
                        return true;
                    } else {
                        // Student needs email verification
                        error_log("Login FAILED - Student email not verified: " . $username);
                        return false;
                    }
                }
            } else {
                error_log("Login FAILED - Invalid password for: " . $username);
            }
        } else {
            error_log("Login FAILED - User not found: " . $username);
        }
        
        return false;
        
    } catch(PDOException $e) {
        error_log("Login ERROR: " . $e->getMessage());
        return false;
    }
}

/**
 * Check if user is logged in
 */
function isLoggedIn() {
    return isset($_SESSION['user_id']) && isset($_SESSION['user_type']);
}

/**
 * Check if user is admin
 */
function isAdmin() {
    return isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'admin';
}

/**
 * Check if user is faculty
 */
function isFaculty() {
    return isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'faculty';
}

/**
 * Check if user is student
 */
function isStudent() {
    return isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'student';
}

/**
 * Logout function
 */
function logout() {
    // Unset all session variables
    $_SESSION = array();
    
    // Destroy session cookie
    if (isset($_COOKIE[session_name()])) {
        setcookie(session_name(), '', time()-3600, '/');
    }
    
    // Destroy session
    session_destroy();
}

/**
 * Get current user info
 */
function getCurrentUser() {
    if(!isLoggedIn()) {
        return null;
    }
    
    return [
        'user_id' => $_SESSION['user_id'],
        'username' => $_SESSION['username'],
        'user_type' => $_SESSION['user_type'],
        'name' => $_SESSION['name']
    ];
}

/**
 * Require admin access
 */
function requireAdmin() {
    if(!isLoggedIn() || !isAdmin()) {
        header('Location: login.php');
        exit();
    }
}

/**
 * Require faculty access
 */
function requireFaculty() {
    if(!isLoggedIn() || !isFaculty()) {
        header('Location: login.php');
        exit();
    }
}

/**
 * Require student access
 */
function requireStudent() {
    if(!isLoggedIn() || !isStudent()) {
        header('Location: login.php');
        exit();
    }
}
