<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/smartplant/backend/auth/session_handler.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/smartplant/db/db_conn.php';

// Check if user is logged in
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("location: ../login/index.php");
    exit;
}

// Get user ID from session
$user_id = $_SESSION['id'];
$message = '';
$success = false;

// Function to create a new notification
function create_notification($conn, $plant_id, $type = 'water', $message = null, $scheduled_for = null)
{
    // If no message is provided, create a default one based on type
    if ($message === null) {
        switch ($type) {
            case 'water':
                $message = 'Din plante trænger til vand!';
                break;
            case 'fertilize':
                $message = 'Det er tid til at gøde din plante.';
                break;
            case 'repot':
                $message = 'Din plante skal snart omplantes.';
                break;
            default:
                $message = 'Din plante kræver opmærksomhed.';
        }
    }

    // If no scheduled date is provided, use current time
    if ($scheduled_for === null) {
        $scheduled_for = date('Y-m-d H:i:s');
    }

    // Prepare and execute query
    $sql = "INSERT INTO notifications (plant_id, notification_type, message, scheduled_for) 
            VALUES (?, ?, ?, ?)";

    if ($stmt = $conn->prepare($sql)) {
        $stmt->bind_param("isss", $plant_id, $type, $message, $scheduled_for);
        $result = $stmt->execute();
        $stmt->close();
        return $result;
    }

    return false;
}

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['create_test_notification'])) {
    // Get plant ID from form
    $plant_id = $_POST['plant_id'];
    $notification_type = $_POST['notification_type'];

    // Try to create a notification
    if (create_notification($conn, $plant_id, $notification_type)) {
        $success = true;
        $message = 'Testnotifikation er oprettet!';
    } else {
        $message = 'Der opstod en fejl ved oprettelse af notifikationen.';
    }
}

// Get all plants for this user that have water_notification set to 'on'
$sql = "SELECT id, name, water_notification FROM plants WHERE user_id = ?";
$plants = [];

if ($stmt = $conn->prepare($sql)) {
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();

    while ($row = $result->fetch_assoc()) {
        $plants[] = $row;
    }

    $stmt->close();
}

// Get existing notifications
$notifications = [];
$sql = "SELECT n.*, p.name AS plant_name 
        FROM notifications n 
        JOIN plants p ON n.plant_id = p.id 
        WHERE p.user_id = ? 
        ORDER BY n.scheduled_for DESC";

if ($stmt = $conn->prepare($sql)) {
    $stmt->bind_param("i", $user_id);
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
    <title>Test Notifikationer - Smart Plant</title>
    <style>
        .notification-card {
            transition: all 0.3s ease;
        }

        .notification-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
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
                    <div class="flex justify-between items-center">
                        <div>
                            <h2 class="text-2xl font-bold text-gray-800">Test Notifikationer</h2>
                            <p class="text-gray-600">Opret testnotifikationer for dine planter</p>
                        </div>

                        <a href="../dashboard/" class="text-gray-600 hover:text-gray-800">
                            <i class="fas fa-arrow-left mr-1"></i> Tilbage
                        </a>
                    </div>
                </div>

                <?php if (!empty($message)): ?>
                    <!-- Notification message -->
                    <div class="mb-6 p-4 rounded-lg <?php echo $success ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700'; ?>">
                        <div class="flex items-center">
                            <i class="fas <?php echo $success ? 'fa-check-circle' : 'fa-exclamation-circle'; ?> mr-2"></i>
                            <p><?php echo $message; ?></p>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Create Test Notification Form -->
                <div class="bg-white rounded-lg shadow-sm p-5 mb-6">
                    <h3 class="text-lg font-semibold text-gray-800 mb-4">Opret en testnotifikation</h3>

                    <?php if (count($plants) > 0): ?>
                        <form method="POST" action="">
                            <div class="space-y-4">
                                <!-- Plant Selection -->
                                <div>
                                    <label for="plant_id" class="block text-sm font-medium text-gray-700 mb-1">Vælg plante</label>
                                    <select id="plant_id" name="plant_id" class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-green-500" required>
                                        <?php foreach ($plants as $plant): ?>
                                            <option value="<?php echo $plant['id']; ?>">
                                                <?php echo htmlspecialchars($plant['name']); ?>
                                                <?php if ($plant['water_notification'] == 'on'): ?>
                                                    (Notifikationer aktive)
                                                <?php else: ?>
                                                    (Notifikationer inaktive)
                                                <?php endif; ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>

                                <!-- Notification Type -->
                                <div>
                                    <label for="notification_type" class="block text-sm font-medium text-gray-700 mb-1">Notifikationstype</label>
                                    <select id="notification_type" name="notification_type" class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-green-500" required>
                                        <option value="water" selected>Vanding</option>
                                        <option value="fertilize">Gødning</option>
                                        <option value="repot">Omplantning</option>
                                    </select>
                                </div>

                                <!-- Submit Button -->
                                <div>
                                    <button type="submit" name="create_test_notification" class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg transition flex items-center">
                                        <i class="fas fa-plus mr-2"></i> Opret notifikation
                                    </button>
                                </div>
                            </div>
                        </form>
                    <?php else: ?>
                        <div class="p-4 bg-yellow-100 text-yellow-700 rounded-lg">
                            <p>Du har ingen planter med aktiverede notifikationer. <a href="../dashboard/mine-planter/" class="text-green-600 hover:underline">Tilføj planter</a> først for at teste notifikationer.</p>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Existing Notifications -->
                <div class="bg-white rounded-lg shadow-sm p-5 mb-6">
                    <h3 class="text-lg font-semibold text-gray-800 mb-4">Eksisterende notifikationer</h3>

                    <?php if (count($notifications) > 0): ?>
                        <div class="space-y-4">
                            <?php foreach ($notifications as $notification): ?>
                                <div class="notification-card p-4 border rounded-lg <?php echo $notification['is_read'] == 'yes' ? 'bg-gray-50 border-gray-200' : 'bg-green-50 border-green-200'; ?>">
                                    <div class="flex justify-between">
                                        <div>
                                            <h4 class="font-medium text-gray-800">
                                                <?php echo htmlspecialchars($notification['plant_name']); ?>
                                                <span class="text-sm font-normal text-gray-500">
                                                    (<?php
                                                        switch ($notification['notification_type']) {
                                                            case 'water':
                                                                echo 'Vanding';
                                                                break;
                                                            case 'fertilize':
                                                                echo 'Gødning';
                                                                break;
                                                            case 'repot':
                                                                echo 'Omplantning';
                                                                break;
                                                        }
                                                        ?>)
                                                </span>
                                            </h4>
                                            <p class="text-gray-600"><?php echo htmlspecialchars($notification['message']); ?></p>
                                            <p class="text-sm text-gray-500 mt-1">
                                                Planlagt: <?php echo date('d/m/Y H:i', strtotime($notification['scheduled_for'])); ?>
                                            </p>
                                        </div>

                                        <?php if ($notification['is_read'] == 'no'): ?>
                                            <div class="h-3 w-3 rounded-full bg-green-500"></div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <p class="text-gray-600">Du har ingen notifikationer endnu.</p>
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