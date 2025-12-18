<?php
require_once 'auth.php';
require_once 'config.php';

// Must be logged in as admin
if(!isLoggedIn() || !isAdmin()) {
    header('Location: login.php');
    exit();
}

$message = '';
$error = '';

if($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_password'])) {
    $current_password = trim($_POST['current_password']);
    $new_password = trim($_POST['new_password']);
    $confirm_password = trim($_POST['confirm_password']);
    
    // Validation
    if(empty($current_password) || empty($new_password) || empty($confirm_password)) {
        $error = 'All fields are required.';
    } elseif($new_password !== $confirm_password) {
        $error = 'New passwords do not match.';
    } elseif(strlen($new_password) < 6) {
        $error = 'New password must be at least 6 characters long.';
    } else {
        $database = new Database();
        $db = $database->getConnection();
        
        try {
            // Verify current password
            $query = "SELECT password_hash FROM users WHERE user_id = :user_id";
            $stmt = $db->prepare($query);
            $stmt->bindParam(':user_id', $_SESSION['user_id']);
            $stmt->execute();
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if($user && password_verify($current_password, $user['password_hash'])) {
                // Update password
                $new_hash = password_hash($new_password, PASSWORD_DEFAULT);
                $update = "UPDATE users SET password_hash = :password_hash WHERE user_id = :user_id";
                $stmt = $db->prepare($update);
                $stmt->bindParam(':password_hash', $new_hash);
                $stmt->bindParam(':user_id', $_SESSION['user_id']);
                
                if($stmt->execute()) {
                    $message = 'Password changed successfully!';
                } else {
                    $error = 'Failed to update password.';
                }
            } else {
                $error = 'Current password is incorrect.';
            }
        } catch(PDOException $e) {
            $error = 'Database error: ' . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Change Admin Password</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="header" style="background: linear-gradient(135deg, #1e293b 0%, #334155 100%);">
        <h1> Change Password</h1>
        <div class="header-buttons">
            <a href="admin_dashboard.php" class="back-btn" style="color: #1e293b;"> Back to Dashboard</a>
            <form method="POST" action="logout.php" class="inline-form">
                <button type="submit" class="logout-btn" style="color: #1e293b;">Logout</button>
            </form>
        </div>
    </div>

    <div class="container">
        <div class="dashboard-section" style="max-width: 600px; margin: 50px auto;">
            <div class="section-header">
                <h2> Change Your Password</h2>
                <p class="section-subtitle">Update your admin password</p>
            </div>

            <?php if($message): ?>
                <div class="success auto-dismiss"><?php echo htmlspecialchars($message); ?></div>
            <?php endif; ?>
            <?php if($error): ?>
                <div class="error auto-dismiss"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>

            <form method="POST" class="modern-form">
                <div class="form-group">
                    <label>Current Password *</label>
                    <input type="password" name="current_password" required autofocus>
                </div>

                <div class="form-group">
                    <label>New Password *</label>
                    <input type="password" name="new_password" required minlength="6">
                    <small class="form-help">Minimum 6 characters</small>
                </div>

                <div class="form-group">
                    <label>Confirm New Password *</label>
                    <input type="password" name="confirm_password" required minlength="6">
                </div>

                <button type="submit" name="change_password" class="btn-primary">
                    Change Password
                </button>
            </form>
        </div>
    </div>

    <script>
        // Auto-dismiss messages
        document.querySelectorAll('.auto-dismiss').forEach(msg => {
            setTimeout(() => {
                msg.style.opacity = '0';
                setTimeout(() => msg.remove(), 300);
            }, 5000);
        });
    </script>
</body>
</html>