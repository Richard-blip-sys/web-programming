<?php
session_start();
include 'db_connect.php';

// ✅ Check if the student is logged in using email session
if (!isset($_SESSION['email'])) {
    header("Location: login.php");
    exit();
}

$email = $_SESSION['email'];

// ✅ Get the student_id using email
$student_query = mysqli_query($conn, "SELECT student_id, name FROM students WHERE email='$email'");
$student = mysqli_fetch_assoc($student_query);
$student_id = $student['student_id'];

// ✅ Fetch all approved/rejected requests of this student
$query = "SELECT w.*, c.course_name
          FROM withdrawals w
          JOIN courses c ON w.course_id = c.course_id
          WHERE w.student_id = '$student_id' 
          AND w.status IN ('Approved', 'Rejected')
          ORDER BY w.request_date DESC";

$result = mysqli_query($conn, $query);
?>
<!DOCTYPE html>
<html>
<head>
    <title>Request History</title>
    <link rel="stylesheet" type="text/css" href="style.css">
</head>
<body>
<div class="dashboard-box">
    <h2>Request History</h2>
    <table>
        <tr>
            <th>Course Name</th>
            <th>Reason</th>
            <th>Status</th>
            <th>Date</th>
        </tr>
        <?php if (mysqli_num_rows($result) > 0): ?>
            <?php while ($row = mysqli_fetch_assoc($result)): ?>
            <tr>
                <td><?= htmlspecialchars($row['course_name']) ?></td>
                <td><?= htmlspecialchars($row['reason']) ?></td>
                <td><?= htmlspecialchars($row['status']) ?></td>
                <td><?= htmlspecialchars($row['request_date']) ?></td>
            </tr>
            <?php endwhile; ?>
        <?php else: ?>
            <tr><td colspan="4">No history found.</td></tr>
        <?php endif; ?>
    </table>
    <p><a href="select_course.php" class="button">Back</a></p>
</div>
</body>
</html>
