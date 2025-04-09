<?php
// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if user is logged in
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

// Include database connection
require_once __DIR__ . '/../../db/db_conn.php';

// Check for plants that need water and generate notifications
require_once __DIR__ . '/generate_water_notifications.php';
generateWaterNotifications();

// Get the unread notification count
$unread_notification_count = 0;
$notification_sql = "SELECT COUNT(*) as count FROM notifications n 
                    JOIN plants p ON n.plant_id = p.id 
                    WHERE p.user_id = ? AND n.is_read = 'no'";
                    
if ($notification_stmt = $conn->prepare($notification_sql)) {
    $notification_stmt->bind_param("i", $_SESSION["id"]);
    
    if ($notification_stmt->execute()) {
        $notification_result = $notification_stmt->get_result();
        if ($row = $notification_result->fetch_assoc()) {
            $unread_notification_count = $row['count'];
        }
    }
    
    $notification_stmt->close();
}

// Return the notification count as JSON
header('Content-Type: application/json');
echo json_encode([
    'success' => true,
    'count' => $unread_notification_count
]);
?>