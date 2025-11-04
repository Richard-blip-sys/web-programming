<?php
include("db_connect.php");
session_start();

$message = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = trim($_POST["username"]);
    $password = trim($_POST["password"]);

    // Check login credentials
    $query = mysqli_query($conn, "SELECT * FROM admins WHERE username='$username' AND password='$password'");

if (mysqli_num_rows($query) == 1) {
    $admin = mysqli_fetch_assoc($query);
    $_SESSION['admin_id'] = $admin['admin_id'];
    $_SESSION['admin'] = $admin['username'];
    header("Location: admin_dashboard.php");
    exit();
}
 else {
        $message = "Invalid username or password.";
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Admin Login</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="form-box">
        <h2>Admin Login</h2>

        <?php if ($message != "") echo "<p style='color:red;'>$message</p>"; ?>

        <form method="POST" action="">
            <label>Username</label><br>
            <input type="text" name="username" placeholder="Enter username" required><br><br>

            <label>Password</label><br>
            <input type="password" name="password" placeholder="Enter password" required><br><br>

            <button type="submit">Login</button>
        </form>

        <p><a href="login.php">← Back to Student Login</a></p>
    </div>
</body>
</html>
