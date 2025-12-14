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
            
            $message = 'Email verified successfully! Redirecting to login...';
            
            // Immediate redirect to login
            echo "<script>
                setTimeout(function() {
                    window.location.href = 'login.php';
                }, 2000);
            </script>";
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
            text-align: center;
        }
        h1 { 
            color: #333; 
            margin-bottom: 10px;
            font-size: 28px;
        }
        .subtitle {
            color: #666;
            font-size: 14px;
            margin-bottom: 30px;
        }
        .email-display {
            background: #f0f4ff;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 30px;
            font-size: 14px;
            color: #333;
        }
        .email-display strong {
            color: #667eea;
        }
        .otp-input-container {
            display: flex;
            justify-content: center;
            gap: 10px;
            margin: 30px 0;
        }
        .otp-input {
            width: 60px;
            height: 70px;
            font-size: 32px;
            text-align: center;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            font-weight: bold;
            color: #667eea;
            transition: all 0.3s;
        }
        .otp-input:focus {
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
            border-radius: 8px; 
            font-size: 16px; 
            cursor: pointer; 
            font-weight: bold; 
            margin-top: 20px;
            transition: transform 0.2s;
        }
        button:hover { 
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(102, 126, 234, 0.4);
        }
        button:disabled {
            opacity: 0.5;
            cursor: not-allowed;
            transform: none;
        }
        .resend-btn {
            background: white;
            color: #667eea;
            border: 2px solid #667eea;
        }
        .resend-btn:hover {
            background: #f0f4ff;
        }
        .success { 
            background: #d1fae5; 
            color: #065f46; 
            padding: 15px; 
            border-radius: 8px; 
            margin-bottom: 20px;
            border-left: 4px solid #10b981;
            text-align: left;
        }
        .error { 
            background: #fee2e2; 
            color: #991b1b; 
            padding: 15px; 
            border-radius: 8px; 
            margin-bottom: 20px;
            border-left: 4px solid #ef4444;
            text-align: left;
        }
        .info {
            background: #e0e7ff;
            color: #3730a3;
            padding: 15px;
            border-radius: 8px;
            margin: 20px 0;
            font-size: 13px;
            text-align: left;
        }
        .timer {
            font-size: 14px;
            color: #666;
            margin-top: 15px;
        }
        .timer span {
            font-weight: bold;
            color: #667eea;
        }
        .back-link {
            margin-top: 20px;
            padding-top: 20px;
            border-top: 1px solid #e0e0e0;
        }
        .back-link a {
            color: #667eea;
            text-decoration: none;
            font-size: 14px;
        }
        .back-link a:hover {
            text-decoration: underline;
        }
        .spinner {
            border: 3px solid #f3f3f3;
            border-top: 3px solid #10b981;
            border-radius: 50%;
            width: 40px;
            height: 40px;
            animation: spin 1s linear infinite;
            margin: 0 auto;
        }
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>📧 Verify Your Email</h1>
        <p class="subtitle">Enter the 6-digit code we sent to your email</p>
        
        <div class="email-display">
            Code sent to: <strong><?php echo htmlspecialchars($email); ?></strong>
        </div>
        
        <?php if($message): ?>
            <div class="success"><?php echo $message; ?></div>
        <?php endif; ?>
        <?php if($error): ?>
            <div class="error"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <div class="info">
            <strong>📬 Check your inbox!</strong><br>
            The verification code has been sent to your email. Please check your inbox (and spam folder) for the 6-digit code.
        </div>
        
        <form method="POST" id="verifyForm">
            <div class="otp-input-container">
                <input type="text" class="otp-input" maxlength="1" pattern="[0-9]" inputmode="numeric" id="otp1" required>
                <input type="text" class="otp-input" maxlength="1" pattern="[0-9]" inputmode="numeric" id="otp2" required>
                <input type="text" class="otp-input" maxlength="1" pattern="[0-9]" inputmode="numeric" id="otp3" required>
                <input type="text" class="otp-input" maxlength="1" pattern="[0-9]" inputmode="numeric" id="otp4" required>
                <input type="text" class="otp-input" maxlength="1" pattern="[0-9]" inputmode="numeric" id="otp5" required>
                <input type="text" class="otp-input" maxlength="1" pattern="[0-9]" inputmode="numeric" id="otp6" required>
            </div>
            
            <input type="hidden" name="otp" id="otpValue">
            
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
        // OTP Input Auto-focus and Auto-submit
        const otpInputs = document.querySelectorAll('.otp-input');
        const otpValue = document.getElementById('otpValue');
        const verifyForm = document.getElementById('verifyForm');
        
        otpInputs.forEach((input, index) => {
            // Auto-focus next input
            input.addEventListener('input', (e) => {
                if(e.target.value.length === 1 && index < otpInputs.length - 1) {
                    otpInputs[index + 1].focus();
                }
                
                // Auto-submit when all 6 digits are entered
                checkComplete();
            });
            
            // Handle backspace
            input.addEventListener('keydown', (e) => {
                if(e.key === 'Backspace' && e.target.value === '' && index > 0) {
                    otpInputs[index - 1].focus();
                }
            });
            
            // Only allow numbers
            input.addEventListener('keypress', (e) => {
                if(!/[0-9]/.test(e.key)) {
                    e.preventDefault();
                }
            });
            
            // Paste support
            input.addEventListener('paste', (e) => {
                e.preventDefault();
                const pastedData = e.clipboardData.getData('text').slice(0, 6);
                const digits = pastedData.split('');
                
                digits.forEach((digit, i) => {
                    if(otpInputs[i]) {
                        otpInputs[i].value = digit;
                    }
                });
                
                if(digits.length === 6) {
                    otpInputs[5].focus();
                    checkComplete();
                }
            });
        });
        
        function checkComplete() {
            const otp = Array.from(otpInputs).map(input => input.value).join('');
            otpValue.value = otp;
            
            if(otp.length === 6 && /^\d{6}$/.test(otp)) {
                // Disable inputs during submission
                otpInputs.forEach(input => input.disabled = true);
                document.getElementById('verifyBtn').disabled = true;
                document.getElementById('verifyBtn').textContent = 'Verifying...';
                
                // Submit after a short delay
                setTimeout(() => verifyForm.submit(), 500);
            }
        }
        
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
        
        // Auto-focus first input on load
        otpInputs[0].focus();
    </script>
</body>
</html>