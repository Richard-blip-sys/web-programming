<?php
include("db_connect.php");
session_start();

if (!isset($_SESSION['email'])) {
    header("Location: login.php");
    exit();
}

// Get student info
$email = $_SESSION['email'];
$student_query = mysqli_query($conn, "SELECT * FROM students WHERE email='$email'");
$student = mysqli_fetch_assoc($student_query);
$student_id = $student['student_id'];

// Get all requests made by this student
$result = mysqli_query($conn, "SELECT * FROM withdrawals WHERE student_id='$student_id' ORDER BY request_date DESC");
?>

<!DOCTYPE html>
<html>
<head>
    <title>Track My Requests</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="dashboard-box">
        <h2>My Withdrawal Requests</h2>
        <p>Welcome, <?php echo $student['name']; ?> | <a href="logout.php">Logout</a></p>
        <hr>

        <h3>Request Status</h3>
        <table border="1" cellpadding="8" cellspacing="0">
            <tr>
                <th>ID</th>
                <th>Course ID</th>
                <th>Reason</th>
                <th>Date</th>
                <th>Status</th>
            </tr>

            <?php
            if (mysqli_num_rows($result) > 0) {
                while ($row = mysqli_fetch_assoc($result)) {
                    echo "<tr>";
                    echo "<td>".$row['id']."</td>";
                    echo "<td>".$row['course_id']."</td>";
                    echo "<td>".$row['reason']."</td>";
                    echo "<td>".$row['request_date']."</td>";
                    echo "<td>".$row['status']."</td>";
                    echo "</tr>";
                }
            } else {
                echo "<tr><td colspan='5'>No requests found.</td></tr>";
            }
            ?>
        </table>

        <br>
        <a href="select_course.php">← Back to Course Selection</a>
    </div>
</body>
</html>
