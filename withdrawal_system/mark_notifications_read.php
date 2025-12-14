<?php
session_start();
require_once 'email_system.php';

if(isset($_SESSION['user_id'])) {
    markAllNotificationsRead($_SESSION['user_id']);
}
?>