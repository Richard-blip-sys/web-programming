<!DOCTYPE html>
<html>
<head>
    <title>Student Login</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="form-box">
        <h2>Student Login</h2>

        <?php
        include("db_connect.php");
        session_start();

        if ($_SERVER["REQUEST_METHOD"] == "POST") {
            $email = $_POST['email'];
            $password = $_POST['password'];

            $query = mysqli_query($conn, "SELECT * FROM students WHERE email='$email' AND password='$password'");
            if (mysqli_num_rows($query) == 1) {
                $_SESSION['email'] = $email;
                header("Location: select_course.php");
                exit();
            } else {
                echo "<p style='color:red;'>Invalid email or password.</p>";
            }
        }
        ?>

        <form method="POST" action="">
            <label>Email</label><br>
            <input type="email" name="email" placeholder="Enter your email" required><br><br>

            <label>Password</label><br>
            <input type="password" name="password" placeholder="Enter your password" required><br><br>

            <button type="submit">Login</button>
        </form>

        <p>Don’t have an account? <a href="register.php">Create one here</a>.</p>
    </div>
</body>
</html>
