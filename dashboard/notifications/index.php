<?php
// Check if session is started
require_once $_SERVER['DOCUMENT_ROOT'] . '/smartplant/backend/auth/session_handler.php';

// Check if user is logged in
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("location: ../../login/index.php");
    exit;
}

// Set current page for sidebar highlighting
$page = "notifications";

// Include header
include('../../components/header.php');

// Include database connection
include('../../db/db_conn.php');

// Include the notification generator to check for new notifications on page load
include('../../dashboard/notifications/generate_water_notifications.php');
generateWaterNotifications();

// Mark notifications as read if requested
if (isset($_GET['mark_read']) && is_numeric($_GET['mark_read'])) {
    $notification_id = (int)$_GET['mark_read'];

    // Make sure the notification belongs to the user
    $check_sql = "SELECT n.id FROM notifications n 
                 JOIN plants p ON n.plant_id = p.id 
                 WHERE n.id = ? AND p.user_id = ?";

    $check_stmt = $conn->prepare($check_sql);
    $check_stmt->bind_param("ii", $notification_id, $_SESSION['id']);
    $check_stmt->execute();
    $check_result = $check_stmt->get_result();

    if ($check_result->num_rows > 0) {
        $mark_sql = "UPDATE notifications SET is_read = 'yes' WHERE id = ?";
        $mark_stmt = $conn->prepare($mark_sql);
        $mark_stmt->bind_param("i", $notification_id);
        $mark_stmt->execute();
        $mark_stmt->close();
    }

    $check_stmt->close();

    // Redirect to remove the GET parameter
    header("Location: index.php");
    exit;
}

// Mark all notifications as read if requested
if (isset($_GET['mark_all_read'])) {
    $mark_all_sql = "UPDATE notifications n 
                    JOIN plants p ON n.plant_id = p.id 
                    SET n.is_read = 'yes' 
                    WHERE p.user_id = ? AND n.is_read = 'no'";

    $mark_all_stmt = $conn->prepare($mark_all_sql);
    $mark_all_stmt->bind_param("i", $_SESSION['id']);
    $mark_all_stmt->execute();
    $mark_all_stmt->close();

    // Redirect to remove the GET parameter
    header("Location: index.php");
    exit;
}

// Get user's notifications
$sql = "SELECT n.*, p.name as plant_name, p.image_path, p.id as plant_id 
        FROM notifications n
        JOIN plants p ON n.plant_id = p.id
        WHERE p.user_id = ?
        ORDER BY n.created_at DESC";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $_SESSION['id']);
$stmt->execute();
$result = $stmt->get_result();
$notifications = [];

while ($row = $result->fetch_assoc()) {
    $notifications[] = $row;
}
$stmt->close();

// Count unread notifications
$unread_count = 0;
foreach ($notifications as $notification) {
    if ($notification['is_read'] == 'no') {
        $unread_count++;
    }
}
?>

<head>
    <style>
        .sidebar {
            transition: transform 0.3s ease;
        }

        @media (max-width: 768px) {
            .sidebar {
                transform: translateX(-100%);
            }

            .sidebar.open {
                transform: translateX(0);
            }
        }

        .notification-item {
            transition: all 0.2s ease;
        }

        .notification-item:hover {
            background-color: #f3f4f6;
        }

        .notification-badge {
            animation: pulse 2s infinite;
        }

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
    </style>
</head>

<body class="bg-gray-100">
    <div class="flex h-screen overflow-hidden">
        <!-- Include the sidebar component -->
        <?php include('../../components/sidebar.php'); ?>

        <!-- Main Content -->
        <div class="flex-1 flex flex-col overflow-hidden">
            <!-- Mobile Header (only visible on mobile) -->
            <header class="bg-white shadow-sm z-20 md:hidden">
                <div class="flex items-center p-4">
                    <!-- Mobile menu toggle -->
                    <button id="menu-toggle" class="focus:outline-none">
                        <i class="fas fa-bars text-gray-600 text-xl"></i>
                    </button>

                    <div class="ml-4">
                        <h1 class="text-xl font-bold text-gray-800">Smart Plant</h1>
                    </div>
                </div>
            </header>

            <!-- Content Area -->
            <main class="flex-1 overflow-y-auto p-4">
                <!-- Page Header -->
                <div class="flex justify-between items-center mb-6">
                    <div>
                        <h2 class="text-2xl font-bold text-gray-800">Notifikationer</h2>
                        <p class="text-gray-600">Få besked om dine planters behov</p>
                    </div>

                    <?php if ($unread_count > 0): ?>
                        <a href="?mark_all_read=1" class="text-sm text-green-600 hover:text-green-800 flex items-center">
                            <i class="fas fa-check-double mr-1"></i> Marker alle som læst
                        </a>
                    <?php endif; ?>
                </div>

                <!-- Notifications List -->
                <div class="bg-white rounded-lg shadow-sm overflow-hidden">
                    <?php if (count($notifications) > 0): ?>

                        <div class="divide-y divide-gray-200">
                            <?php foreach ($notifications as $notification): ?>
                                <div class="notification-item p-4 <?php echo $notification['is_read'] == 'no' ? 'bg-green-50' : ''; ?>">
                                    <div class="flex items-start">
                                        <!-- Plant Image -->
                                        <div class="flex-shrink-0 mr-4">
                                            <a href="../mine-planter/plant-details.php?id=<?php echo $notification['plant_id']; ?>">
                                                <img src="../../<?php echo htmlspecialchars($notification['image_path']); ?>"
                                                    alt="<?php echo htmlspecialchars($notification['plant_name']); ?>"
                                                    class="h-12 w-12 rounded-full object-cover border border-gray-200">
                                            </a>
                                        </div>

                                        <!-- Notification Content -->
                                        <div class="flex-1 min-w-0">
                                            <div class="flex justify-between">
                                                <p class="text-sm font-medium text-gray-900">
                                                    <?php echo htmlspecialchars($notification['plant_name']); ?>
                                                </p>
                                                <span class="text-xs text-gray-500">
                                                    <?php echo date('d. M Y - H:i', strtotime($notification['created_at'])); ?>
                                                </span>
                                            </div>

                                            <p class="text-sm text-gray-700 mt-1">
                                                <?php echo htmlspecialchars($notification['message']); ?>
                                            </p>

                                            <!-- Notification Type Badge -->
                                            <div class="mt-2">
                                                <?php if ($notification['notification_type'] == 'water'): ?>
                                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                                        <i class="fas fa-tint mr-1"></i> Vanding
                                                    </span>
                                                <?php elseif ($notification['notification_type'] == 'fertilize'): ?>
                                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                                        <i class="fas fa-seedling mr-1"></i> Gødning
                                                    </span>
                                                <?php elseif ($notification['notification_type'] == 'repot'): ?>
                                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-amber-100 text-amber-800">
                                                        <i class="fas fa-exchange-alt mr-1"></i> Ompotning
                                                    </span>
                                                <?php endif; ?>
                                            </div>
                                        </div>

                                        <!-- Action Buttons -->
                                        <div class="flex-shrink-0 ml-4 flex">
                                            <?php if ($notification['is_read'] == 'no'): ?>
                                                <a href="?mark_read=<?php echo $notification['id']; ?>" class="text-gray-400 hover:text-gray-600" title="Marker som læst">
                                                    <i class="fas fa-check"></i>
                                                </a>
                                            <?php endif; ?>

                                            <a href="../mine-planter/plant-details.php?id=<?php echo $notification['plant_id']; ?>"
                                                class="ml-3 text-green-500 hover:text-green-700" title="Vis plante">
                                                <i class="fas fa-chevron-right"></i>
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>

                    <?php else: ?>
                        <!-- Empty State -->
                        <div class="p-8 text-center">
                            <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-gray-100 text-gray-400 mb-4">
                                <i class="fas fa-bell text-2xl"></i>
                            </div>
                            <h3 class="text-lg font-medium text-gray-900 mb-1">Ingen notifikationer</h3>
                            <p class="text-gray-500">Du har ingen notifikationer på nuværende tidspunkt.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </main>
        </div>
    </div>

    <!-- Mobile overlay when sidebar is open -->
    <div id="overlay" class="fixed inset-0 bg-black bg-opacity-50 z-20 hidden md:hidden"></div>

    <!-- JavaScript -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Mobile menu functionality
            const menuToggle = document.getElementById('menu-toggle');
            const sidebar = document.getElementById('sidebar');
            const overlay = document.getElementById('overlay');

            menuToggle.addEventListener('click', function() {
                sidebar.classList.toggle('open');
                overlay.classList.toggle('hidden');
                document.body.classList.toggle('overflow-hidden');
            });

            overlay.addEventListener('click', function() {
                sidebar.classList.remove('open');
                overlay.classList.add('hidden');
                document.body.classList.remove('overflow-hidden');
            });

            window.addEventListener('resize', function() {
                if (window.innerWidth >= 768) {
                    sidebar.classList.remove('open');
                    overlay.classList.add('hidden');
                    document.body.classList.remove('overflow-hidden');
                }
            });
        });
    </script>
</body>

</html>