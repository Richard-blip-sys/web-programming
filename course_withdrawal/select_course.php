<?php
include("db_connect.php");
session_start();

if (!isset($_SESSION['email'])) {
    header("Location: login.php");
    exit();
}

// Get the logged-in student's info
$email = $_SESSION['email'];
$student_query = mysqli_query($conn, "SELECT * FROM students WHERE email='$email'");
$student = mysqli_fetch_assoc($student_query);
?>

<!DOCTYPE html>
<html>
<head>
    <title>Select Course</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="form-box">
        <h2>Welcome, <?php echo $student['name']; ?>!</h2>
        <h3>Select a Course to Withdraw</h3>

        <form method="POST" action="withdraw_form.php">
            <label>Choose Course:</label>
            <select name="course_id" required>
                <?php
                $courses = mysqli_query($conn, "SELECT * FROM courses");
                while ($row = mysqli_fetch_assoc($courses)) {
                    echo "<option value='{$row['course_id']}'>{$row['course_name']}</option>";
                }
                ?>
            </select>

            <br><br>
            <input type="submit" value="Continue">
        </form>

        <p><a href="track_request.php" class="button">Track My Requests</a></p>
        <p><a href="history.php" class="button">View Request History</a></p>


    </div>
</body>
</html>
