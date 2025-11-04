<?php
session_start();
include 'db_connect.php';

// Only allow logged-in admins
if (!isset($_SESSION['admin_id'])) {
    header("Location: admin_login.php");
    exit();
}

$admin_id = $_SESSION['admin_id'];

// Fetch all withdrawal requests
$query = "SELECT w.*, s.name AS student_name, c.course_name
          FROM withdrawals w
          JOIN students s ON w.student_id = s.student_id
          JOIN courses c ON w.course_id = c.course_id
          ORDER BY w.request_date DESC";

$result = mysqli_query($conn, $query);
?>
<!DOCTYPE html>
<html>
<head>
    <title>Admin Dashboard</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
<div class="dashboard-box">
    <h2>Admin Dashboard</h2>
    <table>
        <tr>
            <th>Student Name</th>
            <th>Course Name</th>
            <th>Reason</th>
            <th>Status</th>
            <th>Action</th>
        </tr>

        <?php
        while ($row = mysqli_fetch_assoc($result)) {
            echo "<tr>";
            echo "<td>" . htmlspecialchars($row['student_name']) . "</td>";
            echo "<td>" . htmlspecialchars($row['course_name']) . "</td>";
            echo "<td>" . htmlspecialchars($row['reason']) . "</td>";
            echo "<td>" . htmlspecialchars($row['status']) . "</td>";

            echo "<td>";
            if ($row['status'] == 'Pending') {
                echo "<a class='button' href='update_request.php?id=" . $row['withdrawal_id'] . "&status=Approved' onclick=\"return confirm('Are you sure you want to approve this request?');\">Approve</a> ";
                echo "<a class='button reject' href='update_request.php?id=" . $row['withdrawal_id'] . "&status=Rejected' onclick=\"return confirm('Are you sure you want to reject this request?');\">Reject</a>";
            } else {
                echo htmlspecialchars($row['status']);
            }
            echo "</td>";
            echo "</tr>";
        }
        ?>
    </table>

    <form action="logout.php" method="POST">
        <button type="submit" class="logout-btn">Logout</button>
    </form>
</div>
</body>
</html>
