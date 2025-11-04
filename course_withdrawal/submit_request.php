<?php
include("db_connect.php");
session_start();

if (!isset($_SESSION['email'])) {
    header("Location: login.php");
    exit();
}

if (isset($_POST['submit'])) {
    $email = $_SESSION['email'];
    $course_id = $_POST['course_id'];
    $reason = mysqli_real_escape_string($conn, $_POST['reason']); // ✅ Escape apostrophes safely

    // Get student info
    $student_query = mysqli_query($conn, "SELECT * FROM students WHERE email='$email'");
    $student = mysqli_fetch_assoc($student_query);
    $student_id = $student['student_id'];

    // ✅ Correct table name
    $insert = "INSERT INTO withdrawals (student_id, course_id, reason, status, request_date)
               VALUES ('$student_id', '$course_id', '$reason', 'Pending', NOW())";

    if (mysqli_query($conn, $insert)) {
        $message = "✅ Your withdrawal request has been submitted successfully!";
    } else {
        $message = "❌ Error: " . mysqli_error($conn);
    }
} else {
    header("Location: select_course.php");
    exit();
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Request Submitted</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="form-box">
        <h2>Course Withdrawal Request</h2>
        <p><?php echo $message; ?></p>
        <a href="select_course.php">← Go Back to Course Selection</a>
    </div>
</body>
</html>
