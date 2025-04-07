<?php
// This file can be included in your dashboard to display notifications

// Function to get unread notifications for the current user
function get_unread_notifications($conn, $user_id, $limit = 5)
{
    $notifications = [];
    $sql = "SELECT n.*, p.name AS plant_name, p.image_path 
            FROM notifications n 
            JOIN plants p ON n.plant_id = p.id 
            WHERE p.user_id = ? AND n.is_read = 'no' AND n.scheduled_for <= NOW()
            ORDER BY n.scheduled_for DESC 
            LIMIT ?";

    if ($stmt = $conn->prepare($sql)) {
        $stmt->bind_param("ii", $user_id, $limit);
        $stmt->execute();
        $result = $stmt->get_result();

        while ($row = $result->fetch_assoc()) {
            $notifications[] = $row;
        }

        $stmt->close();
    }

    return $notifications;
}

// Function to get notification count for the bell icon
function get_notification_count($conn, $user_id)
{
    $sql = "SELECT COUNT(*) as count 
            FROM notifications n 
            JOIN plants p ON n.plant_id = p.id 
            WHERE p.user_id = ? AND n.is_read = 'no' AND n.scheduled_for <= NOW()";

    if ($stmt = $conn->prepare($sql)) {
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($row = $result->fetch_assoc()) {
            return $row['count'];
        }

        $stmt->close();
    }

    return 0;
}

// Function to mark notification as read
function mark_notification_read($conn, $notification_id)
{
    $sql = "UPDATE notifications SET is_read = 'yes' WHERE id = ?";

    if ($stmt = $conn->prepare($sql)) {
        $stmt->bind_param("i", $notification_id);
        $result = $stmt->execute();
        $stmt->close();
        return $result;
    }

    return false;
}

// Mark notification as read if requested
if (isset($_POST['mark_read']) && isset($_POST['notification_id'])) {
    $notification_id = $_POST['notification_id'];
    mark_notification_read($conn, $notification_id);

    // Redirect to avoid form resubmission
    header("Location: " . $_SERVER['PHP_SELF']);
    exit;
}

// Get unread notifications for the current user
$notifications = [];
if (isset($_SESSION['id'])) {
    $notifications = get_unread_notifications($conn, $_SESSION['id']);
    $notification_count = get_notification_count($conn, $_SESSION['id']);
}
