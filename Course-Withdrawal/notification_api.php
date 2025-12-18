<?php
// notification_api.php - Handle AJAX requests for notifications
session_start();
require_once 'config.php';
require_once 'notifications.php';

header('Content-Type: application/json');

if(!isset($_SESSION['user_id'])) {
    echo json_encode(['error' => 'Not authenticated']);
    exit();
}

$action = isset($_GET['action']) ? $_GET['action'] : '';

switch($action) {
    case 'get_count':
        $count = getUnreadCount($_SESSION['user_id']);
        echo json_encode(['count' => $count]);
        break;
        
    case 'get_notifications':
        $notifications = getNotifications($_SESSION['user_id'], 10);
        echo json_encode(['notifications' => $notifications]);
        break;
        
    case 'mark_read':
        $id = isset($_POST['id']) ? $_POST['id'] : 0;
        $success = markAsRead($id);
        echo json_encode(['success' => $success]);
        break;
        
    case 'mark_all_read':
        $success = markAllAsRead($_SESSION['user_id']);
        echo json_encode(['success' => $success]);
        break;
        
    case 'delete':
        $id = isset($_POST['id']) ? $_POST['id'] : 0;
        $success = deleteNotification($id);
        echo json_encode(['success' => $success]);
        break;
        
    default:
        echo json_encode(['error' => 'Invalid action']);
}
?>