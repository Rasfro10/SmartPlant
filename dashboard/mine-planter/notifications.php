<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/smartplant/backend/auth/session_handler.php';

// Check if user is logged in
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("location: ../../../login/");
    exit;
}

// Set current page for sidebar highlighting
$page = "notifications";

// Include database connection
require_once $_SERVER['DOCUMENT_ROOT'] . '/smartplant/db/db_conn.php';

// Include notification functions
require_once $_SERVER['DOCUMENT_ROOT'] . '/smartplant/components/notifications-display.php';

// Get user_id from session
$user_id = $_SESSION['id'];

// Handle mark as read for all notifications
if (isset($_POST['mark_all_read'])) {
    $sql = "UPDATE notifications n
            JOIN plants p ON n.plant_id = p.id
            SET n.is_read = 'yes'
            WHERE p.user_id = ? AND n.is_read = 'no'";

    if ($stmt = $conn->prepare($sql)) {
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $stmt->close();

        // Redirect to avoid form resubmission
        header("Location: " . $_SERVER['PHP_SELF']);
        exit;
    }
}

// Handle delete notification
if (isset($_POST['delete_notification']) && isset($_POST['notification_id'])) {
    $notification_id = $_POST['notification_id'];

    $sql = "DELETE n FROM notifications n
            JOIN plants p ON n.plant_id = p.id
            WHERE n.id = ? AND p.user_id = ?";

    if ($stmt = $conn->prepare($sql)) {
        $stmt->bind_param("ii", $notification_id, $user_id);
        $stmt->execute();
        $stmt->close();

        // Redirect to avoid form resubmission
        header("Location: " . $_SERVER['PHP_SELF']);
        exit;
    }
}

// Get all notifications for the current user with pagination
$notifications_per_page = 10;
$page_number = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page_number - 1) * $notifications_per_page;

// Get total count
$total_notifications = 0;
$sql = "SELECT COUNT(*) AS count
        FROM notifications n
        JOIN plants p ON n.plant_id = p.id
        WHERE p.user_id = ?";

if ($stmt = $conn->prepare($sql)) {
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($row = $result->fetch_assoc()) {
        $total_notifications = $row['count'];
    }

    $stmt->close();
}

// Calculate total pages
$total_pages = ceil($total_notifications / $notifications_per_page);

// Get notifications for current page
$notifications = [];
$sql = "SELECT n.*, p.name AS plant_name, p.image_path, p.location 
        FROM notifications n 
        JOIN plants p ON n.plant_id = p.id 
        WHERE p.user_id = ?
        ORDER BY n.scheduled_for DESC
        LIMIT ? OFFSET ?";

if ($stmt = $conn->prepare($sql)) {
    $stmt->bind_param("iii", $user_id, $notifications_per_page, $offset);
    $stmt->execute();
    $result = $stmt->get_result();

    while ($row = $result->fetch_assoc()) {
        $notifications[] = $row;
    }

    $stmt->close();
}

// Include header
include($_SERVER['DOCUMENT_ROOT'] . '/smartplant/components/header.php');
?>

<!DOCTYPE html>
<html lang="da">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Notifikationer - Smart Plant</title>
    <style>
        .notification-card {
            transition: all 0.3s ease;
        }

        .notification-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
        }

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
    </style>
</head>

<body class="bg-gray-100">
    <div class="flex h-screen overflow-hidden">
        <!-- Include the sidebar component -->
        <?php include($_SERVER['DOCUMENT_ROOT'] . '/smartplant/components/sidebar.php'); ?>

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
                <div class="mb-6">
                    <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-4">
                        <div>
                            <h2 class="text-2xl font-bold text-gray-800">Notifikationer</h2>
                            <p class="text-gray-600">Administrer notifikationer for dine planter</p>
                        </div>

                        <div class="mt-4 md:mt-0 flex space-x-2">
                            <?php if (count($notifications) > 0): ?>
                                <form method="POST" action="">
                                    <button type="submit" name="mark_all_read" class="text-gray-600 hover:text-gray-800 px-3 py-1 border border-gray-300 rounded-lg hover:bg-gray-100 transition">
                                        <i class="fas fa-check-double mr-1"></i> Marker alle som læst
                                    </button>
                                </form>
                            <?php endif; ?>

                            <a href="/smartplant/notifications/test-notifications.php" class="bg-green-600 hover:bg-green-700 text-white px-3 py-1 rounded-lg transition flex items-center">
                                <i class="fas fa-plus mr-1"></i> Test notifikation
                            </a>
                        </div>
                    </div>
                </div>

                <!-- Notifications List -->
                <div class="bg-white rounded-lg shadow-sm p-5 mb-6">
                    <?php if (count($notifications) > 0): ?>
                        <div class="flex justify-between items-center mb-4">
                            <h3 class="text-lg font-semibold text-gray-800">Alle notifikationer</h3>

                            <!-- Filter controls could be added here in the future -->
                        </div>

                        <!-- Notifications -->
                        <div class="space-y-4">
                            <?php foreach ($notifications as $notification): ?>
                                <div class="notification-card p-4 border rounded-lg <?php echo $notification['is_read'] == 'yes' ? 'bg-gray-50 border-gray-200' : 'bg-green-50 border-green-200'; ?>">
                                    <div class="flex">
                                        <!-- Plant Image -->
                                        <div class="mr-4 hidden md:block">
                                            <div class="h-16 w-16 rounded-lg bg-gray-100 overflow-hidden">
                                                <img src="/smartplant/<?php echo $notification['image_path']; ?>" alt="Plant" class="h-full w-full object-cover" onerror="this.src='/smartplant/assets/plants/default.png'">
                                            </div>
                                        </div>

                                        <!-- Notification Content -->
                                        <div class="flex-1">
                                            <div class="flex flex-col md:flex-row md:justify-between md:items-start">
                                                <div>
                                                    <h4 class="font-medium text-gray-800 flex items-center">
                                                        <?php echo htmlspecialchars($notification['plant_name']); ?>
                                                        <?php if ($notification['is_read'] == 'no'): ?>
                                                            <span class="ml-2 h-2 w-2 rounded-full bg-green-500"></span>
                                                        <?php endif; ?>
                                                    </h4>

                                                    <p class="text-sm text-gray-500">
                                                        Placering: <?php echo htmlspecialchars($notification['location']); ?>
                                                    </p>

                                                    <p class="text-gray-700 mt-1"><?php echo htmlspecialchars($notification['message']); ?></p>
                                                </div>

                                                <div class="mt-2 md:mt-0 md:ml-4 flex md:flex-col items-start">
                                                    <span class="text-sm text-gray-500 mr-4 md:mr-0 md:mb-2">
                                                        <?php echo date('d/m/Y H:i', strtotime($notification['scheduled_for'])); ?>
                                                    </span>

                                                    <div class="flex space-x-2">
                                                        <?php if ($notification['is_read'] == 'no'): ?>
                                                            <form method="POST" action="">
                                                                <input type="hidden" name="notification_id" value="<?php echo $notification['id']; ?>">
                                                                <button type="submit" name="mark_read" class="text-xs text-green-600 hover:text-green-800 hover:underline">
                                                                    Marker som læst
                                                                </button>
                                                            </form>
                                                        <?php endif; ?>

                                                        <form method="POST" action="">
                                                            <input type="hidden" name="notification_id" value="<?php echo $notification['id']; ?>">
                                                            <button type="submit" name="delete_notification" class="text-xs text-red-600 hover:text-red-800 hover:underline">
                                                                Slet
                                                            </button>
                                                        </form>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>

                        <!-- Pagination -->
                        <?php if ($total_pages > 1): ?>
                            <div class="mt-6 flex justify-center">
                                <div class="inline-flex rounded-md shadow-sm">
                                    <?php if ($page_number > 1): ?>
                                        <a href="?page=<?php echo $page_number - 1; ?>" class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-l-md hover:bg-gray-50">
                                            <i class="fas fa-chevron-left"></i>
                                        </a>
                                    <?php else: ?>
                                        <span class="px-4 py-2 text-sm font-medium text-gray-300 bg-white border border-gray-300 rounded-l-md cursor-not-allowed">
                                            <i class="fas fa-chevron-left"></i>
                                        </span>
                                    <?php endif; ?>

                                    <?php
                                    // Calculate range of pages to show
                                    $start_page = max(1, min($page_number - 2, $total_pages - 4));
                                    $end_page = min($total_pages, max($page_number + 2, 5));

                                    // Show first page if not in range
                                    if ($start_page > 1): ?>
                                        <a href="?page=1" class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 hover:bg-gray-50">
                                            1
                                        </a>
                                        <?php if ($start_page > 2): ?>
                                            <span class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300">
                                                ...
                                            </span>
                                        <?php endif; ?>
                                    <?php endif; ?>

                                    <?php for ($i = $start_page; $i <= $end_page; $i++): ?>
                                        <?php if ($i == $page_number): ?>
                                            <span class="px-4 py-2 text-sm font-medium text-white bg-green-600 border border-green-600">
                                                <?php echo $i; ?>
                                            </span>
                                        <?php else: ?>
                                            <a href="?page=<?php echo $i; ?>" class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 hover:bg-gray-50">
                                                <?php echo $i; ?>
                                            </a>
                                        <?php endif; ?>
                                    <?php endfor; ?>

                                    <?php if ($end_page < $total_pages): ?>
                                        <?php if ($end_page < $total_pages - 1): ?>
                                            <span class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300">
                                                ...
                                            </span>
                                        <?php endif; ?>
                                        <a href="?page=<?php echo $total_pages; ?>" class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 hover:bg-gray-50">
                                            <?php echo $total_pages; ?>
                                        </a>
                                    <?php endif; ?>

                                    <?php if ($page_number < $total_pages): ?>
                                        <a href="?page=<?php echo $page_number + 1; ?>" class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-r-md hover:bg-gray-50">
                                            <i class="fas fa-chevron-right"></i>
                                        </a>
                                    <?php else: ?>
                                        <span class="px-4 py-2 text-sm font-medium text-gray-300 bg-white border border-gray-300 rounded-r-md cursor-not-allowed">
                                            <i class="fas fa-chevron-right"></i>
                                        </span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endif; ?>
                    <?php else: ?>
                        <div class="text-center py-8">
                            <div class="inline-block rounded-full p-3 bg-gray-100 text-gray-500 mb-4">
                                <i class="fas fa-bell-slash text-3xl"></i>
                            </div>
                            <h3 class="text-lg font-medium text-gray-800 mb-2">Ingen notifikationer</h3>
                            <p class="text-gray-600 mb-4">Du har ingen notifikationer på nuværende tidspunkt.</p>
                            <a href="/smartplant/notifications/test-notifications.php" class="inline-flex items-center px-4 py-2 bg-green-600 hover:bg-green-700 text-white rounded-lg transition">
                                <i class="fas fa-plus mr-2"></i> Opret testnotifikation
                            </a>
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

            if (menuToggle && sidebar && overlay) {
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
            }
        });
    </script>
</body>

</html>