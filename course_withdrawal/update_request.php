<?php
include("db_connect.php");
session_start();

// Check if the admin is logged in
if (!isset($_SESSION['admin_id'])) {
    header("Location: admin_login.php");
    exit();
}

// Make sure both id and status are provided
if (isset($_GET['id']) && isset($_GET['status'])) {
    $withdrawal_id = $_GET['id'];
    $status = $_GET['status'];

    // Update status of the withdrawal request
    $query = "UPDATE withdrawals SET status='$status' WHERE withdrawal_id='$withdrawal_id'";

    if (mysqli_query($conn, $query)) {
        echo "<script>alert('Request has been updated successfully!'); window.location='admin_dashboard.php';</script>";
    } else {
        echo "<script>alert('Error updating request: " . mysqli_error($conn) . "'); window.location='admin_dashboard.php';</script>";
    }
} else {
    header("Location: admin_dashboard.php");
    exit();
}
?>
