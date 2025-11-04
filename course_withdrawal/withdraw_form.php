<?php
include("db_connect.php");
session_start();

if (!isset($_SESSION['email'])) {
    header("Location: login.php");
    exit();
}

if (!isset($_POST['course_id'])) {
    header("Location: select_course.php");
    exit();
}

$email = $_SESSION['email'];
$course_id = $_POST['course_id'];

// Get student details
$student_query = mysqli_query($conn, "SELECT * FROM students WHERE email='$email'");
$student = mysqli_fetch_assoc($student_query);

// Get course details
$course_query = mysqli_query($conn, "SELECT * FROM courses WHERE course_id='$course_id'");
$course = mysqli_fetch_assoc($course_query);
?>

<!DOCTYPE html>
<html>
<head>
    <title>Withdrawal Form</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="form-box">
        <h2>Withdrawal Form</h2>
        <p><strong>Student:</strong> <?php echo $student['name']; ?></p>
        <p><strong>Course:</strong> <?php echo $course['course_name']; ?></p>

        <form method="POST" action="submit_request.php">
            <input type="hidden" name="course_id" value="<?php echo $course_id; ?>">
            <label>Reason for Withdrawal:</label>
            <textarea name="reason" rows="4" required></textarea>

            <br><br>
            <input type="submit" name="submit" value="Submit Request">
        </form>
    </div>
</body>
</html>
