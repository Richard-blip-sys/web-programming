<?php
require_once 'email_verification.php';
require_once 'config.php';
session_start();

// Check if user has pending verification
if(!isset($_SESSION['pending_verification_user_id'])) {
    header('Location: register.php');
    exit();
}

$user_id = $_SESSION['pending_verification_user_id'];
$email = $_SESSION['pending_verification_email'];
$name = $_SESSION['pending_verification_name'];

$message = '';
$error = '';

// Handle OTP verification
if($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['verify'])) {
    $otp = trim($_POST['otp']);
    
    // Validate OTP format (must be 6 digits)
    if(strlen($otp) === 6 && ctype_digit($otp)) {
        if(verifyOTP($user_id, $otp)) {
            // Clear session variables
            unset($_SESSION['pending_verification_user_id']);
            unset($_SESSION['pending_verification_email']);
            unset($_SESSION['pending_verification_name']);
            
            // Set success message
            $_SESSION['verification_success'] = true;
            
            // Redirect to login
            header('Location: login.php');
            exit();
        } else {
            $error = 'Invalid or expired verification code. Please check your code and try again.';
        }
    } else {
        $error = 'Please enter a valid 6-digit code.';
    }
}

// Check if already verified (in case user refreshes)
$database = new Database();
$db = $database->getConnection();
$checkQuery = "SELECT email_verified FROM users WHERE user_id = :user_id";
$checkStmt = $db->prepare($checkQuery);
$checkStmt->bindParam(':user_id', $user_id);
$checkStmt->execute();
$userCheck = $checkStmt->fetch(PDO::FETCH_ASSOC);

if($userCheck && $userCheck['email_verified'] == 1) {
    // Already verified, redirect to login
    unset($_SESSION['pending_verification_user_id']);
    unset($_SESSION['pending_verification_email']);
    unset($_SESSION['pending_verification_name']);
    $_SESSION['verification_success'] = true;
    header('Location: login.php');
    exit();
}

// Handle resend OTP
if($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['resend'])) {
    if(resendOTP($user_id)) {
        $message = 'New verification code sent to your email!';
    } else {
        $error = 'Failed to resend code. Please try again later.';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verify Your Email</title>
    <link rel="stylesheet" href="style.css">
</head>
<body class="auth-body">
    <div class="verify-container">
        <h1>📧 Verify Your Email</h1>
        <p class="subtitle">Enter the 6-digit code we sent to your email</p>
        
        <div class="email-display">
            Code sent to: <strong><?php echo htmlspecialchars($email); ?></strong>
        </div>
        
        <?php if($message): ?>
            <div class="success"><?php echo htmlspecialchars($message); ?></div>
        <?php endif; ?>
        <?php if($error): ?>
            <div class="error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        
        <div class="info">
            <strong>📬 Check your inbox!</strong><br>
            The verification code has been sent to your email. Please check your inbox (and spam folder) for the 6-digit code.
        </div>
        
        <form method="POST" id="verifyForm">
            <div class="form-group">
                <label for="otp">Enter 6-Digit Code</label>
                <input 
                    type="text" 
                    name="otp" 
                    id="otp" 
                    maxlength="6" 
                    pattern="[0-9]{6}" 
                    inputmode="numeric"
                    placeholder="000000"
                    required
                    autofocus
                >
                <div class="hint">Enter the 6-digit code from your email</div>
            </div>
            
            <button type="submit" name="verify" id="verifyBtn">Verify Email</button>
        </form>
        
        <div class="timer">
            Code expires in: <span id="countdown">10:00</span>
        </div>
        
        <form method="POST">
            <button type="submit" name="resend" class="resend-btn" id="resendBtn">
                📨 Resend Code
            </button>
        </form>
        
        <div class="back-link">
            <a href="register.php">← Back to Registration</a>
        </div>
    </div>
    
    <script>
        const otpInput = document.getElementById('otp');
        const verifyForm = document.getElementById('verifyForm');
        
        // Only allow numbers
        otpInput.addEventListener('keypress', (e) => {
            if(!/[0-9]/.test(e.key)) {
                e.preventDefault();
            }
        });
        
        // Countdown Timer
        let timeLeft = 600; // 10 minutes in seconds
        const countdownEl = document.getElementById('countdown');
        const resendBtn = document.getElementById('resendBtn');
        
        function updateCountdown() {
            const minutes = Math.floor(timeLeft / 60);
            const seconds = timeLeft % 60;
            countdownEl.textContent = `${minutes}:${seconds.toString().padStart(2, '0')}`;
            
            if(timeLeft <= 0) {
                countdownEl.textContent = 'EXPIRED';
                countdownEl.style.color = '#dc2626';
                resendBtn.disabled = false;
            } else {
                timeLeft--;
                setTimeout(updateCountdown, 1000);
            }
        }
        
        updateCountdown();
    </script>
</body>
</html>