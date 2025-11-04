<?php
$host = "localhost";
$user = "root";  // default MySQL username
$pass = "";      // default MySQL password (leave blank unless you set one)
$dbname = "course_withdrawal_db";

// Create connection
$conn = mysqli_connect($host, $user, $pass, $dbname);

// Check connection
if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

// Optional: show message if connected
// echo "Database connected successfully!";
?>
