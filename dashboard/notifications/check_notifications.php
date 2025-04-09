<?php
$check_interval = 30 * 60; // 30 minutes in seconds

$last_check = isset($_SESSION['last_notification_check']) ? $_SESSION['last_notification_check'] : 0;
$current_time = time();

if (($current_time - $last_check) > $check_interval) {
    // Update the last check time
    $_SESSION['last_notification_check'] = $current_time;
    
    // Include notification generator if not already included
    if (!function_exists('generateWaterNotifications')) {
        require_once __DIR__ . '/generate_water_notifications.php';
    }
    
    // Generate notifications
    generateWaterNotifications();
}
?>