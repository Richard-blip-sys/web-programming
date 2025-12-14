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
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { 
            font-family: Arial, sans-serif; 
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); 
            min-height: 100vh; 
            padding: 20px; 
            display: flex; 
            align-items: center; 
            justify-content: center; 
        }
        .container { 
            background: white; 
            padding: 40px; 
            border-radius: 10px; 
            box-shadow: 0 10px 40px rgba(0,0,0,0.2); 
            width: 500px;
            max-width: 100%;
        }
        h1 { 
            text-align: center; 
            color: #333; 
            margin-bottom: 10px;
        }
        .subtitle {
            text-align: center;
            color: #666;
            font-size: 14px;
            margin-bottom: 30px;
        }
        .form-group { 
            margin-bottom: 20px; 
        }
        label { 
            display: block; 
            margin-bottom: 5px; 
            color: #555; 
            font-weight: bold;
            font-size: 14px;
        }
        input { 
            width: 100%; 
            padding: 12px; 
            border: 2px solid #e0e0e0; 
            border-radius: 5px; 
            font-size: 14px;
            transition: all 0.3s;
        }
        input:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }
        button { 
            width: 100%; 
            padding: 14px; 
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white; 
            border: none; 
            border-radius: 5px; 
            font-size: 16px; 
            cursor: pointer; 
            font-weight: bold; 
            margin-top: 10px;
            transition: transform 0.2s;
        }
        button:hover { 
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(102, 126, 234, 0.4);
        }
        .success { 
            background: #d1fae5; 
            color: #065f46; 
            padding: 15px; 
            border-radius: 5px; 
            margin-bottom: 20px;
            border-left: 4px solid #10b981;
        }
        .error { 
            background: #fee2e2; 
            color: #991b1b; 
            padding: 15px; 
            border-radius: 5px; 
            margin-bottom: 20px;
            border-left: 4px solid #ef4444;
        }
        .login-link { 
            text-align: center; 
            margin-top: 20px;
            padding-top: 20px;
            border-top: 1px solid #e0e0e0;
        }
        .login-link a { 
            color: #667eea; 
            text-decoration: none;
            font-weight: 600;
        }
        .login-link a:hover {
            text-decoration: underline;
        }
        .password-requirements {
            font-size: 12px;
            color: #666;
            margin-top: 5px;
        }
        .hidden {
            display: none;
        }
    </style>
</head>
<body>
    <div class="container">
        <?php if(!$show_verification): ?>
            <h1>🎓 Student Registration</h1>
            <p class="subtitle">Create your account to get started</p>
            
            <?php if($message): ?>
                <div class="success"><?php echo $message; ?></div>
            <?php endif; ?>
            <?php if($error): ?>
                <div class="error"><?php echo $error; ?></div>
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
                <br><br>
                <strong>Development Mode:</strong> Check <code>email_verification_log.txt</code> for your OTP code.
            </div>
            
            <script>
                // Redirect to verification page
                window.location.href = 'verify_email.php';
            </script>
        <?php endif; ?>
    </div>
</body>
</html>