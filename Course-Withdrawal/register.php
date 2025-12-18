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
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);
    $confirm_password = trim($_POST['confirm_password']);
    $email = trim($_POST['email']);
    $first_name = trim($_POST['first_name']);
    $last_name = trim($_POST['last_name']);
    
    // Validation
    if(empty($username) || empty($password) || empty($confirm_password) || empty($email) || empty($first_name) || empty($last_name)) {
        $error = 'All fields are required.';
    } elseif(strlen($password) < 6) {
        $error = 'Password must be at least 6 characters long.';
    } elseif($password !== $confirm_password) {
        $error = 'Passwords do not match.';
    } elseif(!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Invalid email address.';
    } else {
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
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Registration</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .password-strength {
            margin-top: 5px;
            font-size: 12px;
            font-weight: 600;
        }
        .strength-weak { color: #ef4444; }
        .strength-medium { color: #f59e0b; }
        .strength-strong { color: #10b981; }
        
        .password-match {
            margin-top: 5px;
            font-size: 13px;
            font-weight: 600;
        }
        .match-success { color: #10b981; }
        .match-error { color: #ef4444; }
    </style>
</head>
<body class="auth-body">
    <div class="auth-container register">
        <?php if(!$show_verification): ?>
            <h1> Student Registration</h1>
            <p class="subtitle">Create your account to get started</p>
            
            <?php if($message): ?>
                <div class="success"><?php echo htmlspecialchars($message); ?></div>
            <?php endif; ?>
            <?php if($error): ?>
                <div class="error"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>
            
            <form method="POST" id="registerForm">
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
                    <input type="password" name="password" id="password" required minlength="6">
                    <div class="password-requirements">
                        Must be at least 6 characters long
                    </div>
                    <div id="passwordStrength" class="password-strength"></div>
                </div>
                <div class="form-group">
                    <label>Confirm Password *</label>
                    <input type="password" name="confirm_password" id="confirm_password" required minlength="6">
                    <div id="passwordMatch" class="password-match"></div>
                </div>
                <button type="submit" name="register" id="submitBtn">Register</button>
            </form>
            
            <div class="login-link">
                <p>Already have an account? <a href="login.php">Login here</a></p>
            </div>
        <?php else: ?>
            <h1> Verify Your Email</h1>
            <p class="subtitle">We sent a 6-digit code to your email</p>
            
            <div class="success">
                Registration successful! Please check your email for the verification code.
            </div>
            
            <script>
                // Redirect to verification page
                window.location.href = 'verify_email.php';
            </script>
        <?php endif; ?>
    </div>
    
    <script>
        const password = document.getElementById('password');
        const confirmPassword = document.getElementById('confirm_password');
        const strengthDiv = document.getElementById('passwordStrength');
        const matchDiv = document.getElementById('passwordMatch');
        const submitBtn = document.getElementById('submitBtn');
        
        // Password strength checker
        password.addEventListener('input', function() {
            const val = this.value;
            let strength = 0;
            
            if(val.length >= 6) strength++;
            if(val.length >= 10) strength++;
            if(/[a-z]/.test(val) && /[A-Z]/.test(val)) strength++;
            if(/\d/.test(val)) strength++;
            if(/[^a-zA-Z\d]/.test(val)) strength++;
            
            if(val.length === 0) {
                strengthDiv.textContent = '';
            } else if(strength <= 2) {
                strengthDiv.textContent = ' Weak password';
                strengthDiv.className = 'password-strength strength-weak';
            } else if(strength <= 3) {
                strengthDiv.textContent = ' Medium password';
                strengthDiv.className = 'password-strength strength-medium';
            } else {
                strengthDiv.textContent = ' Strong password';
                strengthDiv.className = 'password-strength strength-strong';
            }
            
            checkPasswordMatch();
        });
        
        // Password match checker
        confirmPassword.addEventListener('input', checkPasswordMatch);
        
        function checkPasswordMatch() {
            if(confirmPassword.value === '') {
                matchDiv.textContent = '';
                submitBtn.disabled = false;
                return;
            }
            
            if(password.value === confirmPassword.value) {
                matchDiv.textContent = ' Passwords match';
                matchDiv.className = 'password-match match-success';
                submitBtn.disabled = false;
            } else {
                matchDiv.textContent = ' Passwords do not match';
                matchDiv.className = 'password-match match-error';
                submitBtn.disabled = true;
            }
        }
        
        // Form validation
        document.getElementById('registerForm').addEventListener('submit', function(e) {
            if(password.value !== confirmPassword.value) {
                e.preventDefault();
                alert('Passwords do not match!');
            }
        });
    </script>
</body>
</html>