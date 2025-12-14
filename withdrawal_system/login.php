<?php
require_once 'auth.php';
require_once 'config.php';
require_once 'email_verification.php';

if(isLoggedIn()) {
    header('Location: ' . (isFaculty() ? 'faculty_dashboard.php' : 'student_dashboard.php'));
    exit();
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
        
        // Email verified or faculty - proceed to dashboard
        header('Location: ' . ($_SESSION['user_type'] === 'faculty' ? 'faculty_dashboard.php' : 'student_dashboard.php'));
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
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { 
            font-family: Arial, sans-serif; 
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); 
            min-height: 100vh; 
            display: flex; 
            align-items: center; 
            justify-content: center; 
        }
        .container { 
            background: white; 
            padding: 40px; 
            border-radius: 10px; 
            box-shadow: 0 10px 40px rgba(0,0,0,0.2); 
            width: 400px;
            max-width: 90%;
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
        input[type="text"], 
        input[type="password"] { 
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
            transition: transform 0.2s;
        }
        button:hover { 
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(102, 126, 234, 0.4);
        }
        .error { 
            background: #fee2e2; 
            color: #991b1b; 
            padding: 15px; 
            border-radius: 5px; 
            margin-bottom: 20px;
            border-left: 4px solid #ef4444;
        }
        .register-link { 
            text-align: center; 
            margin-top: 20px;
            padding-top: 20px;
            border-top: 1px solid #e0e0e0;
        }
        .register-link a { 
            color: #667eea; 
            text-decoration: none;
            font-weight: 600;
        }
        .register-link a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🎓 Course Withdrawal System</h1>
        <p class="subtitle">Sign in to your account</p>
        
        <?php if($error): ?>
            <div class="error"><?php echo $error; ?></div>
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
            <button type="submit">Login</button>
        </form>
        
        <div class="register-link">
            <p>Student? <a href="register.php">Create an account</a></p>
        </div>
    </div>
</body>
</html>