<?php
// Check if session is already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if the user is logged in
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("location: ../login/");
    exit;
}

// Include database connection if not already included
if (!isset($conn)) {
    require_once __DIR__ . '/../db/db_conn.php';
}

// Initialize variables
$firstname = $lastname = $email = $initials = "";

// Get user data from database
if (isset($_SESSION["id"])) {
    $sql = "SELECT firstname, lastname, email FROM users WHERE id = ?";

    if ($stmt = $conn->prepare($sql)) {
        // Bind variables to the prepared statement as parameters
        $stmt->bind_param("i", $param_id);

        // Set parameters
        $param_id = $_SESSION["id"];

        // Attempt to execute the prepared statement
        if ($stmt->execute()) {
            // Store result
            $stmt->store_result();

            // Check if user exists
            if ($stmt->num_rows == 1) {
                // Bind result variables
                $stmt->bind_result($firstname, $lastname, $email);
                if ($stmt->fetch()) {
                    // Create initials from first letter of firstname and lastname
                    $initials = substr($firstname, 0, 1) . substr($lastname, 0, 1);
                }
            }
        }

        // Close statement
        $stmt->close();
    }
}

// Check for plants that need water and generate notifications
// Only check every 30 minutes to avoid constant checking
$check_interval = 30 * 60; // 30 minutes in seconds
$last_check = isset($_SESSION['last_notification_check']) ? $_SESSION['last_notification_check'] : 0;
$current_time = time();

if (($current_time - $last_check) > $check_interval) {
    // Update the last check time
    $_SESSION['last_notification_check'] = $current_time;
    
    // Include and run the notification generator
    require_once __DIR__ . '/../dashboard/notifications/generate_water_notifications.php';
    generateWaterNotifications();
}

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

// If $page is not set, default to empty string
if (!isset($page)) {
    $page = "";
}
?>

<!-- Sidebar -->
<aside id="sidebar" class="sidebar bg-green-800 text-white w-64 min-h-screen flex-shrink-0 absolute md:relative z-30">
    <div class="p-4 flex items-center border-b border-green-700">
        <i class="fas fa-leaf text-2xl mr-3"></i>
        <h1 class="text-xl font-bold">Smart Plant</h1>
    </div>

    <div class="p-4">
        <div class="flex items-center space-x-3 mb-6 pb-4 border-b border-green-700">
            <div class="h-10 w-10 rounded-full bg-green-600 flex items-center justify-center">
                <span class="font-bold"><?php echo htmlspecialchars($initials); ?></span>
            </div>
            <div>
                <p class="font-medium"><?php echo htmlspecialchars($firstname . ' ' . $lastname); ?></p>
                <p class="text-xs text-green-300"><?php echo htmlspecialchars($email); ?></p>
            </div>
        </div>

        <nav>
            <ul class="space-y-2">
                <li>
                    <a href="<?= $base ?>dashboard/" class="flex items-center space-x-3 p-3 rounded-lg <?php echo $page === 'dashboard' ? 'bg-green-700 font-medium' : 'hover:bg-green-700 transition-colors'; ?>">
                        <i class="fas fa-tachometer-alt"></i>
                        <span>Dashboard</span>
                    </a>
                </li>
                <li>
                    <a href="<?= $base ?>dashboard/mine-planter/" class="flex items-center space-x-3 p-3 rounded-lg <?php echo $page === 'myPlants' ? 'bg-green-700 font-medium' : 'hover:bg-green-700 transition-colors'; ?>">
                        <i class="fas fa-seedling"></i>
                        <span>Mine Planter</span>
                    </a>
                </li>
                <!-- Notifications Link with Count Badge -->
                <li>
                    <a href="<?= $base ?>dashboard/notifications/" class="flex items-center justify-between p-3 rounded-lg <?php echo $page === 'notifications' ? 'bg-green-700 font-medium' : 'hover:bg-green-700 transition-colors'; ?>">
                        <div class="flex items-center space-x-3">
                            <i class="fas fa-bell"></i>
                            <span>Notifikationer</span>
                        </div>
                        <?php if ($unread_notification_count > 0): ?>
                        <div class="bg-red-500 text-white text-xs font-bold rounded-full h-5 min-w-5 flex items-center justify-center px-1 notification-badge">
                            <?php echo $unread_notification_count; ?>
                        </div>
                        <?php endif; ?>
                    </a>
                </li>
                <li>
                    <a href="<?= $base ?>dashboard/statistik/" class="flex items-center space-x-3 p-3 rounded-lg <?php echo $page === 'statistics' ? 'bg-green-700 font-medium' : 'hover:bg-green-700 transition-colors'; ?>">
                        <i class="fas fa-chart-bar"></i>
                        <span>Statistik</span>
                    </a>
                </li>
            </ul>
        </nav>

        <div class="mt-10 pt-6 border-t border-green-700">
            <ul class="space-y-2">
                <li>
                    <a href="<?= $base ?>dashboard/settings/" class="flex items-center space-x-3 p-3 rounded-lg <?php echo $page === 'settings' ? 'bg-green-700 font-medium' : 'hover:bg-green-700 transition-colors'; ?>">
                        <i class="fas fa-cog"></i>
                        <span>Indstillinger</span>
                    </a>
                </li>
                <li>
                    <a href="<?= $base ?>dashboard/hjaelp/" class="flex items-center space-x-3 p-3 rounded-lg <?php echo $page === 'help' ? 'bg-green-700 font-medium' : 'hover:bg-green-700 transition-colors'; ?>">
                        <i class="fas fa-question-circle"></i>
                        <span>Hjælp</span>
                    </a>
                </li>
                <li>
                    <a href="<?= $base ?>backend/auth/logout.php" class="flex items-center space-x-3 p-3 rounded-lg hover:bg-green-700 transition-colors">
                        <i class="fas fa-sign-out-alt"></i>
                        <span>Log ud</span>
                    </a>
                </li>
            </ul>
        </div>
    </div>
</aside>

<style>
    /* Animation for notification badge */
    @keyframes pulse {
        0% {
            transform: scale(0.95);
        }
        50% {
            transform: scale(1.05);
        }
        100% {
            transform: scale(0.95);
        }
    }
    
    .notification-badge {
        animation: pulse 2s infinite;
    }

    /* Minimum width for badge */
    .min-w-5 {
        min-width: 1.25rem;
    }
</style>

<!-- JavaScript for real-time notification updates -->
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Function to fetch notification count
        function fetchNotificationCount() {
            fetch('<?= $base ?>dashboard/notifications/get_notification_count.php')
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        updateNotificationBadge(data.count);
                    }
                })
                .catch(error => console.error('Error fetching notifications:', error));
        }
        
        // Function to update the notification badge
        function updateNotificationBadge(count) {
            const notificationLink = document.querySelector('a[href*="notifications"]');
            
            if (!notificationLink) return;
            
            // Find existing badge or create a new one
            let badge = notificationLink.querySelector('.notification-badge');
            
            if (count > 0) {
                if (!badge) {
                    // Create new badge if it doesn't exist
                    badge = document.createElement('div');
                    badge.className = 'bg-red-500 text-white text-xs font-bold rounded-full h-5 min-w-5 flex items-center justify-center px-1 notification-badge';
                    notificationLink.appendChild(badge);
                }
                
                // Update the count
                badge.textContent = count;
            } else if (badge) {
                // Remove badge if count is 0
                badge.remove();
            }
        }
        
        // Fetch notifications immediately
        fetchNotificationCount();
        
        // Set up interval to check notifications every 5 seconds
        setInterval(fetchNotificationCount, 5000);
    });
</script>