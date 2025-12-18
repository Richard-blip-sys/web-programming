<?php
require_once 'auth.php';
require_once 'config.php';
require_once 'email_verification.php';

if(isLoggedIn()) {
    if(isAdmin()) {
        header('Location: admin_dashboard.php');
    } elseif(isFaculty()) {
        header('Location: faculty_dashboard.php');
    } else {
        header('Location: student_dashboard.php');
    }
    exit();
}

// Check for verification success message
$verificationSuccess = false;
if(isset($_SESSION['verification_success'])) {
    $verificationSuccess = true;
    unset($_SESSION['verification_success']);
}

// Check for password reset success
$passwordResetSuccess = false;
if(isset($_SESSION['password_reset_success'])) {
    $passwordResetSuccess = true;
    unset($_SESSION['password_reset_success']);
}

$error = '';
if($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'];
    $password = $_POST['password'];
    
    if(login($username, $password)) {
        // Check if student needs email verification
        if($_SESSION['user_type'] === 'student') {
            $user_id = $_SESSION['user_id'];
            
            if(!isEmailVerified($user_id)) {
                // Get user email
                $database = new Database();
                $db = $database->getConnection();
                $query = "SELECT email, first_name, last_name FROM users WHERE user_id = :user_id";
                $stmt = $db->prepare($query);
                $stmt->bindParam(':user_id', $user_id);
                $stmt->execute();
                $user = $stmt->fetch(PDO::FETCH_ASSOC);
                
                // Set session for verification
                $_SESSION['pending_verification_user_id'] = $user_id;
                $_SESSION['pending_verification_email'] = $user['email'];
                $_SESSION['pending_verification_name'] = $user['first_name'] . ' ' . $user['last_name'];
                
                // Clear login session
                session_destroy();
                session_start();
                $_SESSION['pending_verification_user_id'] = $user_id;
                $_SESSION['pending_verification_email'] = $user['email'];
                $_SESSION['pending_verification_name'] = $user['first_name'] . ' ' . $user['last_name'];
                
                // Resend verification code
                resendOTP($user_id);
                
                header('Location: verify_email.php');
                exit();
            }
        }
        
        // Email verified or faculty/admin - proceed to dashboard
        if($_SESSION['user_type'] === 'admin') {
            header('Location: admin_dashboard.php');
        } elseif($_SESSION['user_type'] === 'faculty') {
            header('Location: faculty_dashboard.php');
        } else {
            header('Location: student_dashboard.php');
        }
        exit();
    } else {
        $error = 'Invalid username or password';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Course Withdrawal System</title>
    <link rel="stylesheet" href="style.css">
</head>
<body class="auth-body">
    <div class="auth-container">
        <h1> Course Withdrawal </h1>
        <p class="subtitle">Sign in to your account</p>
        
        <?php if($verificationSuccess): ?>
            <div class="success">
                Email verified successfully! You can now login with your credentials.
            </div>
        <?php endif; ?>
        
        <?php if($passwordResetSuccess): ?>
            <div class="success">
                Password reset successful! You can now login with your new password.
            </div>
        <?php endif; ?>
        
        <?php if($error): ?>
            <div class="error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        
        <form method="POST">
            <div class="form-group">
                <label>Username</label>
                <input type="text" name="username" required autofocus>
            </div>
            <div class="form-group">
                <label>Password</label>
                <input type="password" name="password" required>
            </div>
            
            <div style="text-align: right; margin-bottom: 15px;">
                <a href="forgot_password.php" style="color: #667eea; text-decoration: none; font-size: 14px;">Forgot Password?</a>
            </div>
            
            <button type="submit">Login</button>
        </form>
        
        <div class="register-link">
            <p>Don't have an account? <a href="register.php">Register here</a></p>
        </div>
    </div>
</body>
</html>