<?php
require_once 'student_functions.php';
require_once 'email_verification.php';
require_once 'config.php';

session_start();

$message = '';
$error = '';
$show_verification = false;

// Handle registration
if($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['register'])) {
    $username = $_POST['username'];
    $password = $_POST['password'];
    $email = $_POST['email'];
    $first_name = $_POST['first_name'];
    $last_name = $_POST['last_name'];
    
    // Register the student
    if(registerStudent($username, $password, $email, $first_name, $last_name)) {
        // Get the newly created user ID
        $database = new Database();
        $db = $database->getConnection();
        $query = "SELECT user_id FROM users WHERE username = :username";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':username', $username);
        $stmt->execute();
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if($user) {
            $user_id = $user['user_id'];
            $_SESSION['pending_verification_user_id'] = $user_id;
            $_SESSION['pending_verification_email'] = $email;
            $_SESSION['pending_verification_name'] = $first_name . ' ' . $last_name;
            
            // Create verification code
            if(createVerification($user_id, $email, $first_name . ' ' . $last_name)) {
                $message = 'Account created! Please check your email for the verification code.';
                $show_verification = true;
            } else {
                $error = 'Registration successful but failed to send verification email. Please contact support.';
            }
        }
    } else {
        $error = 'Registration failed. Username or email may already exist.';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Registration</title>
    <link rel="stylesheet" href="style.css">
</head>
<body class="auth-body">
    <div class="auth-container register">
        <?php if(!$show_verification): ?>
            <h1>🎓 Student Registration</h1>
            <p class="subtitle">Create your account to get started</p>
            
            <?php if($message): ?>
                <div class="success"><?php echo htmlspecialchars($message); ?></div>
            <?php endif; ?>
            <?php if($error): ?>
                <div class="error"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>
            
            <form method="POST">
                <div class="form-group">
                    <label>First Name *</label>
                    <input type="text" name="first_name" required>
                </div>
                <div class="form-group">
                    <label>Last Name *</label>
                    <input type="text" name="last_name" required>
                </div>
                <div class="form-group">
                    <label>Email Address *</label>
                    <input type="email" name="email" required>
                </div>
                <div class="form-group">
                    <label>Username *</label>
                    <input type="text" name="username" required>
                </div>
                <div class="form-group">
                    <label>Password *</label>
                    <input type="password" name="password" required minlength="6">
                    <div class="password-requirements">
                        Must be at least 6 characters long
                    </div>
                </div>
                <button type="submit" name="register">Create Account</button>
            </form>
            
            <div class="login-link">
                <p>Already have an account? <a href="login.php">Login here</a></p>
            </div>
        <?php else: ?>
            <h1>📧 Verify Your Email</h1>
            <p class="subtitle">We sent a 6-digit code to your email</p>
            
            <div class="success">
                ✅ Registration successful! Please check your email for the verification code.
            </div>
            
            <script>
                // Redirect to verification page
                window.location.href = 'verify_email.php';
            </script>
        <?php endif; ?>
    </div>
</body>
</html>