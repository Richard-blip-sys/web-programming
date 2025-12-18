<?php
require_once 'config.php';
require_once 'password_reset.php';

session_start();

$message = '';
$error = '';
$step = 1; // 1 = email input, 2 = code verification, 3 = new password

if(isset($_SESSION['reset_step'])) {
    $step = $_SESSION['reset_step'];
}

// Handle email submission
if($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['send_code'])) {
    $email = trim($_POST['email']);
    
    if(empty($email)) {
        $error = 'Please enter your email address.';
    } elseif(!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';
    } else {
        // Check if email exists
        $database = new Database();
        $db = $database->getConnection();
        $query = "SELECT user_id, first_name, last_name FROM users WHERE email = :email";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':email', $email);
        $stmt->execute();
        
        if($stmt->rowCount() > 0) {
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            $name = $user['first_name'] . ' ' . $user['last_name'];
            
            // Generate and send reset code
            if(sendPasswordResetCode($user['user_id'], $email, $name)) {
                $_SESSION['reset_user_id'] = $user['user_id'];
                $_SESSION['reset_email'] = $email;
                $_SESSION['reset_step'] = 2;
                $message = 'Reset code sent to your email!';
                $step = 2;
            } else {
                $error = 'Failed to send reset code. Please try again.';
            }
        } else {
            // For security, don't reveal if email exists
            $error = 'If this email exists, a reset code will be sent.';
        }
    }
}

// Handle code verification
if($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['verify_code'])) {
    $code = trim($_POST['reset_code']);
    $user_id = $_SESSION['reset_user_id'];
    
    if(verifyPasswordResetCode($user_id, $code)) {
        $_SESSION['reset_step'] = 3;
        $_SESSION['reset_verified'] = true;
        $message = 'Code verified! Enter your new password.';
        $step = 3;
    } else {
        $error = 'Invalid or expired reset code.';
    }
}

// Handle password reset
if($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reset_password'])) {
    $password = trim($_POST['password']);
    $confirm_password = trim($_POST['confirm_password']);
    $user_id = $_SESSION['reset_user_id'];
    
    if(empty($password) || empty($confirm_password)) {
        $error = 'Please fill in all fields.';
    } elseif(strlen($password) < 6) {
        $error = 'Password must be at least 6 characters long.';
    } elseif($password !== $confirm_password) {
        $error = 'Passwords do not match.';
    } elseif(!isset($_SESSION['reset_verified'])) {
        $error = 'Invalid session. Please start over.';
    } else {
        if(resetUserPassword($user_id, $password)) {
            // Clear session
            unset($_SESSION['reset_user_id']);
            unset($_SESSION['reset_email']);
            unset($_SESSION['reset_step']);
            unset($_SESSION['reset_verified']);
            
            $_SESSION['password_reset_success'] = true;
            header('Location: login.php');
            exit();
        } else {
            $error = 'Failed to reset password. Please try again.';
        }
    }
}

// Handle resend code
if($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['resend_code'])) {
    $user_id = $_SESSION['reset_user_id'];
    $email = $_SESSION['reset_email'];
    
    $database = new Database();
    $db = $database->getConnection();
    $query = "SELECT first_name, last_name FROM users WHERE user_id = :user_id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':user_id', $user_id);
    $stmt->execute();
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    $name = $user['first_name'] . ' ' . $user['last_name'];
    
    if(sendPasswordResetCode($user_id, $email, $name)) {
        $message = 'New reset code sent!';
    } else {
        $error = 'Failed to resend code.';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password</title>
    <link rel="stylesheet" href="style.css">
</head>
<body class="auth-body">
    <div class="auth-container">
        <h1>🔐 Password Reset</h1>
        
        <?php if($step === 1): ?>
            <p class="subtitle">Enter your email to receive a reset code</p>
            
            <?php if($message): ?>
                <div class="success"><?php echo htmlspecialchars($message); ?></div>
            <?php endif; ?>
            <?php if($error): ?>
                <div class="error"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>
            
            <form method="POST">
                <div class="form-group">
                    <label>Email Address</label>
                    <input type="email" name="email" required autofocus>
                </div>
                <button type="submit" name="send_code">Send Reset Code</button>
            </form>
            
        <?php elseif($step === 2): ?>
            <p class="subtitle">Enter the 6-digit code sent to your email</p>
            
            <div class="email-display">
                Code sent to: <strong><?php echo htmlspecialchars($_SESSION['reset_email']); ?></strong>
            </div>
            
            <?php if($message): ?>
                <div class="success"><?php echo htmlspecialchars($message); ?></div>
            <?php endif; ?>
            <?php if($error): ?>
                <div class="error"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>
            
            <form method="POST">
                <div class="form-group">
                    <label>Reset Code</label>
                    <input type="text" name="reset_code" maxlength="6" pattern="[0-9]{6}" inputmode="numeric" placeholder="000000" required autofocus>
                    <div class="hint">Enter the 6-digit code from your email</div>
                </div>
                <button type="submit" name="verify_code">Verify Code</button>
            </form>
            
            <form method="POST" style="margin-top: 15px;">
                <button type="submit" name="resend_code" class="resend-btn">📨 Resend Code</button>
            </form>
            
        <?php elseif($step === 3): ?>
            <p class="subtitle">Create a new password for your account</p>
            
            <?php if($message): ?>
                <div class="success"><?php echo htmlspecialchars($message); ?></div>
            <?php endif; ?>
            <?php if($error): ?>
                <div class="error"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>
            
            <form method="POST" id="resetForm">
                <div class="form-group">
                    <label>New Password</label>
                    <input type="password" name="password" id="password" required minlength="6">
                    <div class="password-requirements">
                        Must be at least 6 characters long
                    </div>
                </div>
                <div class="form-group">
                    <label>Confirm New Password</label>
                    <input type="password" name="confirm_password" id="confirm_password" required minlength="6">
                </div>
                <div id="passwordMatch" style="font-size: 13px; margin-top: -10px; margin-bottom: 15px;"></div>
                <button type="submit" name="reset_password" id="submitBtn">Reset Password</button>
            </form>
        <?php endif; ?>
        
        <div class="login-link">
            <p><a href="login.php">← Back to Login</a></p>
        </div>
    </div>
    
    <script>
        // Password match validation
        const password = document.getElementById('password');
        const confirmPassword = document.getElementById('confirm_password');
        const matchDiv = document.getElementById('passwordMatch');
        const submitBtn = document.getElementById('submitBtn');
        
        if(password && confirmPassword) {
            function checkPasswordMatch() {
                if(confirmPassword.value === '') {
                    matchDiv.textContent = '';
                    matchDiv.style.color = '';
                    submitBtn.disabled = false;
                    return;
                }
                
                if(password.value === confirmPassword.value) {
                    matchDiv.textContent = '✓ Passwords match';
                    matchDiv.style.color = '#10b981';
                    submitBtn.disabled = false;
                } else {
                    matchDiv.textContent = '✗ Passwords do not match';
                    matchDiv.style.color = '#ef4444';
                    submitBtn.disabled = true;
                }
            }
            
            password.addEventListener('input', checkPasswordMatch);
            confirmPassword.addEventListener('input', checkPasswordMatch);
        }
        
        // Only allow numbers in reset code
        const codeInput = document.querySelector('input[name="reset_code"]');
        if(codeInput) {
            codeInput.addEventListener('keypress', (e) => {
                if(!/[0-9]/.test(e.key)) {
                    e.preventDefault();
                }
            });
        }
    </script>
</body>
</html>