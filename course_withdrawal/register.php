<?php
include("db_connect.php");

$message = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = trim($_POST["name"]);
    $email = trim($_POST["email"]);
    $password = trim($_POST["password"]);

    // Check if all fields are filled
    if (empty($name) || empty($email) || empty($password)) {
        $message = "All fields are required.";
    } else {
        // Check if email already exists
        $check = mysqli_query($conn, "SELECT * FROM students WHERE email='$email'");
        if (mysqli_num_rows($check) > 0) {
            $message = "This email is already registered.";
        } else {
            // Insert into database
            $query = "INSERT INTO students (name, email, password) VALUES ('$name', '$email', '$password')";
            if (mysqli_query($conn, $query)) {
                $message = "Account created successfully! You can now log in.";
            } else {
                $message = "Error: Could not create account.";
            }
        }
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Student Registration</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="form-box">
        <h2>Create Account</h2>
        <?php if ($message != "") echo "<p style='color:red;'>$message</p>"; ?>

        <form method="POST" action="">
            <label>Full Name</label><br>
            <input type="text" name="name" placeholder="Enter your full name"><br><br>

            <label>Email</label><br>
            <input type="email" name="email" placeholder="Enter your email"><br><br>

            <label>Password</label><br>
            <input type="password" name="password" placeholder="Enter password"><br><br>

            <button type="submit">Register</button>
        </form>

        <p>Already have an account? <a href="login.php">Login here</a>.</p>
    </div>
</body>
</html>
