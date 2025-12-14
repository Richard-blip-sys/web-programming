<?php
// notifications.php - In-app notification system
require_once 'config.php';

// Create notification
function createNotification($user_id, $type, $title, $message, $link = null) {
    $database = new Database();
    $db = $database->getConnection();
    
    try {
        $query = "INSERT INTO notifications (user_id, type, title, message, link) 
                  VALUES (:user_id, :type, :title, :message, :link)";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':user_id', $user_id);
        $stmt->bindParam(':type', $type);
        $stmt->bindParam(':title', $title);
        $stmt->bindParam(':message', $message);
        $stmt->bindParam(':link', $link);
        return $stmt->execute();
    } catch(PDOException $e) {
        return false;
    }
}

// Get unread notification count
function getUnreadCount($user_id) {
    $database = new Database();
    $db = $database->getConnection();
    
    $query = "SELECT COUNT(*) as count FROM notifications 
              WHERE user_id = :user_id AND is_read = 0";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':user_id', $user_id);
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    return $result['count'];
}

// Get recent notifications
function getNotifications($user_id, $limit = 10) {
    $database = new Database();
    $db = $database->getConnection();
    
    $query = "SELECT * FROM notifications 
              WHERE user_id = :user_id 
              ORDER BY created_at DESC 
              LIMIT :limit";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':user_id', $user_id);
    $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Mark notification as read
function markAsRead($notification_id) {
    $database = new Database();
    $db = $database->getConnection();
    
    $query = "UPDATE notifications SET is_read = 1 WHERE notification_id = :notification_id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':notification_id', $notification_id);
    return $stmt->execute();
}

// Mark all as read
function markAllAsRead($user_id) {
    $database = new Database();
    $db = $database->getConnection();
    
    $query = "UPDATE notifications SET is_read = 1 WHERE user_id = :user_id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':user_id', $user_id);
    return $stmt->execute();
}

// Delete notification
function deleteNotification($notification_id) {
    $database = new Database();
    $db = $database->getConnection();
    
    $query = "DELETE FROM notifications WHERE notification_id = :notification_id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':notification_id', $notification_id);
    return $stmt->execute();
}
?>