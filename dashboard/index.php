<?php
// ini_set('display_errors', 1);
// ini_set('display_startup_errors', 1);
// error_reporting(E_ALL);
require_once $_SERVER['DOCUMENT_ROOT'] . '/smartplant/backend/auth/session_handler.php';

// Set current page for sidebar highlighting
$page = "dashboard";
include('../components/header.php');

// Include notification checker to ensure notifications are generated
require_once __DIR__ . '/notifications/check_notifications.php';

// Get user's plants from database
$user_id = $_SESSION['id'];
$plants = [];
$needs_water = 0;
$needs_light = 0;
$healthy_plants = 0;

// Get plants
$sql = "SELECT * FROM plants WHERE user_id = ?";
if ($stmt = $conn->prepare($sql)) {
    $stmt->bind_param("i", $user_id);

    if ($stmt->execute()) {
        $result = $stmt->get_result();
        while ($plant = $result->fetch_assoc()) {
            $plants[] = $plant;
        }
    }
    $stmt->close();
}

// Helper function to convert raw moisture value to percentage
if (!function_exists('convertMoistureToPercentage')) {
    function convertMoistureToPercentage($rawValue)
    {
        $drySoilMin = 730;
        $drySoilMax = 800;
        $wetSoilMin = 370;
        $wetSoilMax = 430;

        // Ensure the raw value is within the expected range
        $rawValue = max($wetSoilMin, min($drySoilMax, $rawValue));

        // Linear interpolation to convert sensor value to percentage
        if ($rawValue >= $drySoilMin) {
            // Dry soil (0-30%)
            $percentage = map($rawValue, $drySoilMin, $drySoilMax, 0, 30);
        } else {
            // Wet soil (70-100%)
            $percentage = map($rawValue, $wetSoilMin, $drySoilMax, 70, 100);
        }

        return round($percentage, 1);
    }
}
if (!function_exists('map')) {
    function map($value, $inMin, $inMax, $outMin, $outMax)
    {
        return ($value - $inMin) * ($outMax - $outMin) / ($inMax - $inMin) + $outMin;
    }
}

// Get latest sensor data and notifications
$plants_with_data = [];
$plant_status_notifications = [];

foreach ($plants as $plant) {
    // Get latest sensor data
    $sensor_data = null;
    $sql = "SELECT * FROM plant_data WHERE plant_id = ? ORDER BY reading_time DESC LIMIT 1";

    if ($stmt = $conn->prepare($sql)) {
        $stmt->bind_param("i", $plant['id']);

        if ($stmt->execute()) {
            $result = $stmt->get_result();
            if ($row = $result->fetch_assoc()) {
                $sensor_data = $row;

                // Convert raw moisture to percentage
                if (isset($sensor_data['soil_moisture'])) {
                    $rawMoisture = $sensor_data['soil_moisture'];
                    $sensor_data['soil_moisture'] = convertMoistureToPercentage($rawMoisture);
                }
            }
        }
        $stmt->close();
    }

    // Determine plant status
    $status = 'healthy';
    $status_label = 'Sund';
    $status_class = 'bg-green-500';

    if ($sensor_data) {
        if ($sensor_data['soil_moisture'] < 30) {
            $status = 'needs_water';
            $status_label = 'Vand nu!';
            $status_class = 'bg-red-500';
            $needs_water++;

            // Add notification for plants that need water
            $plant_status_notifications[] = [
                'type' => 'water',
                'plant_name' => $plant['name'],
                'message' => 'har kritisk behov for vand',
                'icon' => 'fa-exclamation-circle',
                'icon_bg' => 'bg-red-100',
                'icon_color' => 'text-red-600',
                'bg_color' => 'bg-red-50',
                'time' => $sensor_data['reading_time']
            ];
        } elseif ($sensor_data['light_level'] < 30 && ($plant['light_needs'] == 'Medium' || $plant['light_needs'] == 'Højt')) {
            $status = 'needs_light';
            $status_label = 'Lysbehov';
            $status_class = 'bg-yellow-500';
            $needs_light++;

            // Add notification for plants that need light
            $plant_status_notifications[] = [
                'type' => 'light',
                'plant_name' => $plant['name'],
                'message' => 'trænger til mere lys',
                'icon' => 'fa-sun',
                'icon_bg' => 'bg-yellow-100',
                'icon_color' => 'text-yellow-600',
                'bg_color' => 'bg-yellow-50',
                'time' => $sensor_data['reading_time']
            ];
        } else {
            $healthy_plants++;
        }
    } else {
        $healthy_plants++; // Count as healthy if no sensor data
    }

    // Calculate days until next watering
    $days_until_water = 7; // Default weekly
    if ($plant['watering_frequency'] == 'Daglig') {
        $days_until_water = 1;
    } elseif ($plant['watering_frequency'] == 'Hver 2-3 dag') {
        $days_until_water = 2;
    } elseif ($plant['watering_frequency'] == 'Ugentlig') {
        $days_until_water = 7;
    } elseif ($plant['watering_frequency'] == 'Hver 2. uge') {
        $days_until_water = 14;
    } elseif ($plant['watering_frequency'] == 'Månedlig') {
        $days_until_water = 30;
    }

    // Add plant with data to array
    $plants_with_data[] = [
        'plant' => $plant,
        'sensor_data' => $sensor_data,
        'status' => $status,
        'status_label' => $status_label,
        'status_class' => $status_class,
        'days_until_water' => $days_until_water
    ];
}

// Get database notifications
$db_notifications = [];
$notification_sql = "SELECT n.*, p.name as plant_name 
                   FROM notifications n
                   JOIN plants p ON n.plant_id = p.id
                   WHERE p.user_id = ?
                   ORDER BY n.created_at DESC
                   LIMIT 5";

if ($notification_stmt = $conn->prepare($notification_sql)) {
    $notification_stmt->bind_param("i", $user_id);

    if ($notification_stmt->execute()) {
        $notification_result = $notification_stmt->get_result();
        while ($row = $notification_result->fetch_assoc()) {
            $db_notifications[] = [
                'plant_id' => $row['plant_id'],
                'plant_name' => $row['plant_name'],
                'notification_type' => $row['notification_type'],
                'message' => $row['message'],
                'is_read' => $row['is_read'],
                'scheduled_for' => $row['created_at']
            ];
        }
    }

    $notification_stmt->close();
}

// Sort plant status notifications by time (newest first)
usort($plant_status_notifications, function ($a, $b) {
    return strtotime($b['time']) - strtotime($a['time']);
});

// Combine all notifications for display (prioritize database notifications)
$all_notifications = array_merge($db_notifications, $plant_status_notifications);

// Sort all notifications by time (newest first)
usort($all_notifications, function ($a, $b) {
    $time_a = isset($a['scheduled_for']) ? $a['scheduled_for'] : $a['time'];
    $time_b = isset($b['scheduled_for']) ? $b['scheduled_for'] : $b['time'];
    return strtotime($time_b) - strtotime($time_a);
});

// Get total plant count
$total_plants = count($plants);

// Function to format relative time (e.g., "for 2 timer siden")
function get_time_ago($datetime)
{
    $now = new DateTime();
    $ago = new DateTime($datetime);
    $diff = $now->diff($ago);

    if ($diff->d > 1) {
        return "for " . $diff->d . " dage siden";
    } elseif ($diff->d == 1) {
        return "i går kl. " . $ago->format('H:i');
    } elseif ($diff->h > 0) {
        return "for " . $diff->h . " timer siden";
    } elseif ($diff->i > 0) {
        return "for " . $diff->i . " minutter siden";
    } else {
        return "lige nu";
    }
}

// Get the list of plants for the chart dropdown
$chart_plants = $plants;

// Set first plant as default selected
$default_plant_id = !empty($chart_plants) ? $chart_plants[0]['id'] : '';
$default_plant_name = !empty($chart_plants) ? $chart_plants[0]['name'] : 'Ingen plante valgt';
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

        .plant-card {
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }

        .plant-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
        }

        .chart-container {
            height: 300px;
            width: 100%;
        }

        .dropdown-menu {
            display: none;
            position: absolute;
            right: 0;
            top: 100%;
            background-color: white;
            border: 1px solid #e2e8f0;
            border-radius: 0.375rem;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
            z-index: 10;
            min-width: 10rem;
        }

        .dropdown-menu.show {
            display: block;
        }

        /* Custom scrollbar styling */
        .custom-scrollbar {
            scrollbar-width: thin;
            /* Firefox */
            scrollbar-color: rgba(156, 163, 175, 0.5) rgba(229, 231, 235, 0.5);
            /* Firefox: thumb track */
        }

        /* WebKit browsers (Chrome, Safari, newer versions of Opera) */
        .custom-scrollbar::-webkit-scrollbar {
            width: 6px;
        }

        .custom-scrollbar::-webkit-scrollbar-track {
            background: rgba(229, 231, 235, 0.5);
            border-radius: 10px;
        }

        .custom-scrollbar::-webkit-scrollbar-thumb {
            background-color: rgba(156, 163, 175, 0.5);
            border-radius: 10px;
            border: 2px solid transparent;
        }

        .custom-scrollbar::-webkit-scrollbar-thumb:hover {
            background-color: rgba(107, 114, 128, 0.7);
        }
    </style>
</head>

<body class="bg-gray-100">
    <div class="flex h-screen overflow-hidden">
        <!-- Include the sidebar component -->
        <?php include('../components/sidebar.php'); ?>

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
                <!-- Dashboard Header -->
                <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-6">
                    <div>
                        <h2 class="text-2xl font-bold text-gray-800">Dashboard</h2>
                        <p class="text-gray-600">Velkommen tilbage, <?php echo htmlspecialchars($firstname); ?>. Her er status på dine planter.</p>
                    </div>
                    <a href="./mine-planter/add-plants.php" class="mt-3 md:mt-0 bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg transition flex items-center">
                        <i class="fas fa-plus mr-2"></i> Tilføj Plante
                    </a>
                </div>

                <!-- Status Cards -->
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
                    <div class="bg-white rounded-lg shadow-sm p-4 border-l-4 border-blue-500">
                        <div class="flex justify-between items-center">
                            <div>
                                <p class="text-gray-500 text-sm">Planter der behøver vand</p>
                                <h3 class="text-2xl font-bold text-gray-800"><?php echo $needs_water; ?></h3>
                            </div>
                            <div class="h-10 w-10 rounded-full bg-blue-100 flex items-center justify-center">
                                <i class="fas fa-tint text-blue-600"></i>
                            </div>
                        </div>
                    </div>

                    <div class="bg-white rounded-lg shadow-sm p-4 border-l-4 border-yellow-500">
                        <div class="flex justify-between items-center">
                            <div>
                                <p class="text-gray-500 text-sm">Lysbehov</p>
                                <h3 class="text-2xl font-bold text-gray-800"><?php echo $needs_light; ?></h3>
                            </div>
                            <div class="h-10 w-10 rounded-full bg-yellow-100 flex items-center justify-center">
                                <i class="fas fa-sun text-yellow-600"></i>
                            </div>
                        </div>
                    </div>

                    <div class="bg-white rounded-lg shadow-sm p-4 border-l-4 border-green-500">
                        <div class="flex justify-between items-center">
                            <div>
                                <p class="text-gray-500 text-sm">Sunde planter</p>
                                <h3 class="text-2xl font-bold text-gray-800"><?php echo $healthy_plants; ?></h3>
                            </div>
                            <div class="h-10 w-10 rounded-full bg-green-100 flex items-center justify-center">
                                <i class="fas fa-check text-green-600"></i>
                            </div>
                        </div>
                    </div>

                    <div class="bg-white rounded-lg shadow-sm p-4 border-l-4 border-purple-500">
                        <div class="flex justify-between items-center">
                            <div>
                                <p class="text-gray-500 text-sm">Total</p>
                                <h3 class="text-2xl font-bold text-gray-800"><?php echo $total_plants; ?></h3>
                            </div>
                            <div class="h-10 w-10 rounded-full bg-purple-100 flex items-center justify-center">
                                <i class="fas fa-seedling text-purple-600"></i>
                            </div>
                        </div>
                    </div>
                </div>

                <?php if (count($all_notifications) > 0): ?>
                    <!-- Notifications & Recent Activity -->
                    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-6">
                        <!-- Notifications -->
                        <div class="bg-white rounded-lg shadow-sm p-4">
                            <h3 class="font-bold text-gray-800 mb-4">Notifikationer</h3>
                            <div class="space-y-3 overflow-y-auto max-h-64 pr-1 custom-scrollbar">
                                <?php
                                foreach ($all_notifications as $notification):
                                    // Check if it's a database notification or a sensor-based notification
                                    if (isset($notification['plant_id'])) {
                                        // This is a database notification
                                        $plant_name = $notification['plant_name'];
                                        $time = $notification['scheduled_for'];

                                        // Determine icon and colors based on notification_type
                                        switch ($notification['notification_type']) {
                                            case 'water':
                                                $icon = 'fa-tint';
                                                $icon_bg = 'bg-blue-100';
                                                $icon_color = 'text-blue-600';
                                                $bg_color = 'bg-blue-50';
                                                $message = 'trænger til vand';
                                                break;
                                            case 'fertilize':
                                                $icon = 'fa-leaf';
                                                $icon_bg = 'bg-green-100';
                                                $icon_color = 'text-green-600';
                                                $bg_color = 'bg-green-50';
                                                $message = 'har behov for gødning';
                                                break;
                                            case 'repot':
                                                $icon = 'fa-seedling';
                                                $icon_bg = 'bg-purple-100';
                                                $icon_color = 'text-purple-600';
                                                $bg_color = 'bg-purple-50';
                                                $message = 'skal ompottes';
                                                break;
                                            default:
                                                $icon = 'fa-exclamation-circle';
                                                $icon_bg = 'bg-gray-100';
                                                $icon_color = 'text-gray-600';
                                                $bg_color = 'bg-gray-50';
                                                $message = $notification['message'];
                                        }
                                    } else {
                                        // This is a sensor-based notification
                                        $plant_name = $notification['plant_name'];
                                        $message = $notification['message'];
                                        $icon = $notification['icon'];
                                        $icon_bg = $notification['icon_bg'];
                                        $icon_color = $notification['icon_color'];
                                        $bg_color = $notification['bg_color'];
                                        $time = $notification['time'];
                                    }
                                ?>
                                    <div class="flex items-start p-3 <?php echo $bg_color; ?> rounded-lg">
                                        <div class="h-8 w-8 rounded-full <?php echo $icon_bg; ?> flex items-center justify-center mr-3">
                                            <i class="fas <?php echo $icon; ?> <?php echo $icon_color; ?>"></i>
                                        </div>
                                        <div class="flex-1">
                                            <p class="text-gray-800"><span class="font-medium"><?php echo htmlspecialchars($plant_name); ?></span> <?php echo $message; ?></p>
                                            <p class="text-gray-500 text-xs"><?php echo get_time_ago($time); ?></p>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                            <a href="./notifications/" class="mt-4 text-sm text-green-600 hover:text-green-800 font-medium flex items-center justify-end">
                                Se alle notifikationer <i class="fas fa-arrow-right ml-1"></i>
                            </a>
                        </div>

                        <!-- Recent Activity -->
                        <div class="lg:col-span-2 bg-white rounded-lg shadow-sm p-4">
                            <h3 class="font-bold text-gray-800 mb-4">Seneste Aktiviteter</h3>
                            <div class="space-y-3 overflow-y-auto max-h-64 pr-1 custom-scrollbar">
                                <?php
                                foreach ($all_notifications as $notification):
                                    // Check if it's a database notification or a sensor-based notification
                                    if (isset($notification['plant_id'])) {
                                        // This is a database notification
                                        $plant_name = $notification['plant_name'];
                                        $time = $notification['scheduled_for'];

                                        // Determine icon and colors based on notification_type
                                        switch ($notification['notification_type']) {
                                            case 'water':
                                                $icon = 'fa-tint';
                                                $icon_bg = 'bg-blue-100';
                                                $icon_color = 'text-blue-600';
                                                $message = 'trænger til vand';
                                                break;
                                            case 'fertilize':
                                                $icon = 'fa-leaf';
                                                $icon_bg = 'bg-green-100';
                                                $icon_color = 'text-green-600';
                                                $message = 'har behov for gødning';
                                                break;
                                            case 'repot':
                                                $icon = 'fa-seedling';
                                                $icon_bg = 'bg-purple-100';
                                                $icon_color = 'text-purple-600';
                                                $message = 'skal ompottes';
                                                break;
                                            default:
                                                $icon = 'fa-exclamation-circle';
                                                $icon_bg = 'bg-gray-100';
                                                $icon_color = 'text-gray-600';
                                                $message = $notification['message'];
                                        }
                                    } else {
                                        // This is a sensor-based notification
                                        $plant_name = $notification['plant_name'];
                                        $message = $notification['message'];
                                        $icon = $notification['icon'];
                                        $icon_bg = $notification['icon_bg'];
                                        $icon_color = $notification['icon_color'];
                                        $time = $notification['time'];
                                    }
                                ?>
                                    <div class="flex items-start border-b border-gray-100 pb-3">
                                        <div class="h-8 w-8 rounded-full <?php echo $icon_bg; ?> flex items-center justify-center mr-3">
                                            <i class="fas <?php echo $icon; ?> <?php echo $icon_color; ?>"></i>
                                        </div>
                                        <div class="flex-1">
                                            <p class="text-gray-800"><span class="font-medium"><?php echo htmlspecialchars($plant_name); ?></span> <?php echo $message; ?></p>
                                            <p class="text-gray-500 text-xs"><?php echo get_time_ago($time); ?></p>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                            <a href="./notifications/" class="mt-4 text-sm text-green-600 hover:text-green-800 font-medium flex items-center justify-end">
                                Se alle aktiviteter <i class="fas fa-arrow-right ml-1"></i>
                            </a>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Plants Section -->
                <?php if (count($plants_with_data) > 0): ?>
                    <div class="mb-6">
                        <div class="flex justify-between items-center mb-4">
                            <h3 class="font-bold text-gray-800">Mine Planter</h3>
                            <div class="flex space-x-2">
                                <button id="grid-view-btn" class="p-2 bg-gray-200 hover:bg-gray-300 rounded-lg text-gray-700">
                                    <i class="fas fa-th-large"></i>
                                </button>
                                <button id="list-view-btn" class="p-2 bg-gray-100 hover:bg-gray-200 rounded-lg text-gray-700">
                                    <i class="fas fa-list"></i>
                                </button>
                            </div>
                        </div>

                        <div id="plants-grid" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
                            <?php foreach ($plants_with_data as $plant_data): ?>
                                <div class="bg-white rounded-lg shadow-sm plant-card">
                                    <div class="relative">
                                        <img src="../<?php echo $plant_data['plant']['image_path']; ?>" alt="<?php echo htmlspecialchars($plant_data['plant']['name']); ?>" class="w-full h-40 object-contain rounded-t-lg plant-image" data-plant-id="<?php echo $plant_data['plant']['id']; ?>">
                                        <span class="absolute top-2 right-2 <?php echo $plant_data['status_class']; ?> text-white text-xs px-2 py-1 rounded-full">
                                            <?php echo $plant_data['status_label']; ?>
                                        </span>
                                    </div>
                                    <div class="p-4">
                                        <div class="flex justify-between items-start mb-2">
                                            <h4 class="font-medium text-gray-800"><?php echo htmlspecialchars($plant_data['plant']['name']); ?></h4>
                                            <div class="dropdown relative">
                                                <button class="dropdown-toggle text-gray-500 hover:text-gray-700 focus:outline-none" data-plant-id="<?php echo $plant_data['plant']['id']; ?>">
                                                    <i class="fas fa-ellipsis-v"></i>
                                                </button>
                                                <div class="dropdown-menu hidden absolute right-0 mt-2 w-48 bg-white rounded-md shadow-lg z-10">
                                                    <a href="./mine-planter/plant-detail.php?id=<?php echo $plant_data['plant']['id']; ?>" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                                                        <i class="fas fa-info-circle mr-2"></i> Se detaljer
                                                    </a>
                                                    <a href="./mine-planter/edit-plant.php?id=<?php echo $plant_data['plant']['id']; ?>" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                                                        <i class="fas fa-edit mr-2"></i> Rediger
                                                    </a>
                                                    <a href="#" class="water-plant-btn block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100" data-plant-id="<?php echo $plant_data['plant']['id']; ?>">
                                                        <i class="fas fa-tint mr-2"></i> Marker som vandet
                                                    </a>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="grid grid-cols-2 gap-2 mt-3">
                                            <div class="flex items-center text-sm text-gray-600">
                                                <i class="fas fa-tint text-blue-500 mr-2"></i>
                                                <?php
                                                if ($plant_data['sensor_data'] && isset($plant_data['sensor_data']['soil_moisture'])) {
                                                    echo number_format($plant_data['sensor_data']['soil_moisture'], 1) . '%';
                                                } else {
                                                    echo 'N/A';
                                                }
                                                ?>
                                            </div>
                                            <div class="flex items-center text-sm text-gray-600">
                                                <i class="fas fa-temperature-high text-red-500 mr-2"></i>
                                                <?php
                                                if ($plant_data['sensor_data'] && isset($plant_data['sensor_data']['temperature'])) {
                                                    echo number_format($plant_data['sensor_data']['temperature'], 1) . '°C';
                                                } else {
                                                    echo 'N/A';
                                                }
                                                ?>
                                            </div>
                                            <div class="flex items-center text-sm text-gray-600">
                                                <i class="fas fa-sun text-yellow-500 mr-2"></i>
                                                <?php
                                                if ($plant_data['sensor_data'] && isset($plant_data['sensor_data']['light_level'])) {
                                                    echo number_format($plant_data['sensor_data']['light_level'], 0) . ' units';
                                                } else {
                                                    echo htmlspecialchars($plant_data['plant']['light_needs']);
                                                }
                                                ?>
                                            </div>
                                            <div class="flex items-center text-sm text-gray-600">
                                                <i class="fas fa-clock text-purple-500 mr-2"></i>
                                                <?php echo $plant_data['days_until_water']; ?> dage
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php else: ?>
                    <!-- No plants message -->
                    <div class="bg-white rounded-lg shadow-sm p-8 text-center mb-6">
                        <div class="w-16 h-16 mx-auto bg-gray-100 rounded-full flex items-center justify-center mb-4">
                            <i class="fas fa-seedling text-gray-400 text-2xl"></i>
                        </div>
                        <h3 class="text-xl font-semibold text-gray-700 mb-2">Ingen planter endnu</h3>
                        <p class="text-gray-500 mb-4">Det ser ud til, at du ikke har tilføjet nogen planter endnu. Kom i gang ved at tilføje din første plante.</p>
                        <a href="./mine-planter/add-plants.php" class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg transition inline-flex items-center">
                            <i class="fas fa-plus mr-2"></i> Tilføj din første plante
                        </a>
                    </div>
                <?php endif; ?>

                <!-- Plant Statistics Chart -->
                <div class="bg-white rounded-lg shadow-sm p-4">
                    <h3 class="font-bold text-gray-800 mb-4">Plantestatistik</h3>

                    <div class="mb-4">
                        <label for="plant-select" class="block text-sm font-medium text-gray-700 mb-1">Vælg plante</label>
                        <select id="plant-select" class="w-full sm:w-1/3 border border-gray-300 rounded-lg px-4 py-2 focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-green-500">
                            <?php if (empty($chart_plants)): ?>
                                <option value="">Ingen planter tilgængelige</option>
                            <?php else: ?>
                                <?php foreach ($chart_plants as $plant): ?>
                                    <option value="<?php echo $plant['id']; ?>" <?php echo ($plant['id'] == $default_plant_id) ? 'selected' : ''; ?>><?php echo htmlspecialchars($plant['name']); ?></option>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </select>
                    </div>

                    <?php if (!empty($chart_plants)): ?>
                        <div id="charts-container" class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <div class="bg-gray-50 p-4 rounded-lg">
                                <h4 class="font-medium text-gray-800 mb-2 text-center">Jordfugtighed (%)</h4>
                                <div class="h-60">
                                    <canvas id="moisture-chart"></canvas>
                                </div>
                            </div>

                            <div class="bg-gray-50 p-4 rounded-lg">
                                <h4 class="font-medium text-gray-800 mb-2 text-center">Temperatur (°C)</h4>
                                <div class="h-60">
                                    <canvas id="temperature-chart"></canvas>
                                </div>
                            </div>

                            <div class="bg-gray-50 p-4 rounded-lg">
                                <h4 class="font-medium text-gray-800 mb-2 text-center">Lysniveau</h4>
                                <div class="h-60">
                                    <canvas id="light-chart"></canvas>
                                </div>
                            </div>
                        </div>
                    <?php else: ?>
                        <div id="no-plant-message" class="h-60 bg-gray-50 rounded-lg flex items-center justify-center">
                            <p class="text-gray-500 text-center">
                                <i class="fas fa-seedling text-4xl mb-2 block"></i>
                                Ingen planter at vise statistik for. Tilføj en plante først.
                            </p>
                        </div>
                    <?php endif; ?>
                </div>
            </main>
        </div>
    </div>

    <!-- Mobile overlay when sidebar is open -->
    <div id="overlay" class="fixed inset-0 bg-black bg-opacity-50 z-20 hidden md:hidden"></div>

    <!-- Include Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <!-- JavaScript -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const menuToggle = document.getElementById('menu-toggle');
            const sidebar = document.getElementById('sidebar');
            const overlay = document.getElementById('overlay');

            // Toggle mobile menu
            menuToggle.addEventListener('click', function() {
                sidebar.classList.toggle('open');
                overlay.classList.toggle('hidden');
                document.body.classList.toggle('overflow-hidden');
            });

            // Close menu when clicking overlay
            overlay.addEventListener('click', function() {
                sidebar.classList.remove('open');
                overlay.classList.add('hidden');
                document.body.classList.remove('overflow-hidden');
            });

            // Handle window resize
            window.addEventListener('resize', function() {
                if (window.innerWidth >= 768) {
                    sidebar.classList.remove('open');
                    overlay.classList.add('hidden');
                    document.body.classList.remove('overflow-hidden');
                }
            });

            // Plant image click - redirect to plant details
            const plantImages = document.querySelectorAll('.plant-image');
            plantImages.forEach(image => {
                image.addEventListener('click', function(e) {
                    e.stopPropagation();
                    const plantId = this.getAttribute('data-plant-id');
                    window.location.href = `./mine-planter/plant-detail.php?id=${plantId}`;
                });
            });

            // Dropdown functionality
            const dropdownToggles = document.querySelectorAll('.dropdown-toggle');

            dropdownToggles.forEach(toggle => {
                toggle.addEventListener('click', function(e) {
                    e.stopPropagation();

                    // Close all other dropdowns
                    document.querySelectorAll('.dropdown-menu').forEach(menu => {
                        if (menu !== this.nextElementSibling) {
                            menu.classList.add('hidden');
                        }
                    });

                    // Toggle this dropdown
                    this.nextElementSibling.classList.toggle('hidden');
                });
            });

            // Close dropdowns when clicking elsewhere
            document.addEventListener('click', function() {
                document.querySelectorAll('.dropdown-menu').forEach(menu => {
                    menu.classList.add('hidden');
                });
            });

            // Grid/List view toggle
            const gridViewBtn = document.getElementById('grid-view-btn');
            const listViewBtn = document.getElementById('list-view-btn');
            const plantsGrid = document.getElementById('plants-grid');

            if (gridViewBtn && listViewBtn && plantsGrid) {
                gridViewBtn.addEventListener('click', function() {
                    plantsGrid.classList.remove('grid-cols-1');
                    plantsGrid.classList.add('md:grid-cols-2', 'lg:grid-cols-3', 'xl:grid-cols-4');

                    gridViewBtn.classList.add('bg-gray-200');
                    gridViewBtn.classList.remove('bg-gray-100');

                    listViewBtn.classList.add('bg-gray-100');
                    listViewBtn.classList.remove('bg-gray-200');
                });

                listViewBtn.addEventListener('click', function() {
                    plantsGrid.classList.add('grid-cols-1');
                    plantsGrid.classList.remove('md:grid-cols-2', 'lg:grid-cols-3', 'xl:grid-cols-4');

                    listViewBtn.classList.add('bg-gray-200');
                    listViewBtn.classList.remove('bg-gray-100');

                    gridViewBtn.classList.add('bg-gray-100');
                    gridViewBtn.classList.remove('bg-gray-200');
                });
            }

            // Handle "Mark as watered" button
            const waterButtons = document.querySelectorAll('.water-plant-btn');
            waterButtons.forEach(btn => {
                btn.addEventListener('click', function(e) {
                    e.preventDefault();
                    e.stopPropagation();

                    const plantId = this.getAttribute('data-plant-id');
                    alert('Plante markeret som vandet! (Plant ID: ' + plantId + ')');

                    // Her ville du normalt lave et AJAX kald til serveren for at opdatere databasen
                });
            });

            // Charts functionality
            let moistureChart = null;
            let temperatureChart = null;
            let lightChart = null;

            const moistureCtx = document.getElementById('moisture-chart')?.getContext('2d');
            const temperatureCtx = document.getElementById('temperature-chart')?.getContext('2d');
            const lightCtx = document.getElementById('light-chart')?.getContext('2d');

            // Generate random data for demonstration
            function generateRandomData(count, min, max) {
                const data = [];
                const labels = [];
                const now = new Date();

                for (let i = count - 1; i >= 0; i--) {
                    const date = new Date();
                    date.setDate(now.getDate() - i);
                    labels.push(date.toLocaleDateString('da-DK', {
                        day: 'numeric',
                        month: 'short'
                    }));
                    data.push(Math.floor(Math.random() * (max - min + 1)) + min);
                }

                return {
                    labels,
                    data
                };
            }

            // Create all charts based on selected plant
            function createCharts() {
                if (!moistureCtx || !temperatureCtx || !lightCtx) return;

                // Destroy existing charts if they exist
                if (moistureChart) moistureChart.destroy();
                if (temperatureChart) temperatureChart.destroy();
                if (lightChart) lightChart.destroy();

                // Generate data
                const moistureData = generateRandomData(7, 20, 80);
                const temperatureData = generateRandomData(7, 16, 26);
                const lightData = generateRandomData(7, 100, 800);

                // Create moisture chart
                moistureChart = new Chart(moistureCtx, {
                    type: 'line',
                    data: {
                        labels: moistureData.labels,
                        datasets: [{
                            label: 'Fugtighed (%)',
                            data: moistureData.data,
                            borderColor: 'rgb(59, 130, 246)',
                            backgroundColor: 'rgba(59, 130, 246, 0.2)',
                            tension: 0.4,
                            fill: true
                        }]
                    },
                    options: {
                        scales: {
                            y: {
                                beginAtZero: true,
                                min: 0,
                                max: 100
                            }
                        },
                        plugins: {
                            legend: {
                                display: false
                            }
                        },
                        responsive: true,
                        maintainAspectRatio: false
                    }
                });

                // Create temperature chart
                temperatureChart = new Chart(temperatureCtx, {
                    type: 'line',
                    data: {
                        labels: temperatureData.labels,
                        datasets: [{
                            label: 'Temperatur (°C)',
                            data: temperatureData.data,
                            borderColor: 'rgb(239, 68, 68)',
                            backgroundColor: 'rgba(239, 68, 68, 0.2)',
                            tension: 0.4,
                            fill: true
                        }]
                    },
                    options: {
                        scales: {
                            y: {
                                min: 15,
                                max: 30
                            }
                        },
                        plugins: {
                            legend: {
                                display: false
                            }
                        },
                        responsive: true,
                        maintainAspectRatio: false
                    }
                });

                // Create light chart
                lightChart = new Chart(lightCtx, {
                    type: 'line',
                    data: {
                        labels: lightData.labels,
                        datasets: [{
                            label: 'Lysniveau',
                            data: lightData.data,
                            borderColor: 'rgb(245, 158, 11)',
                            backgroundColor: 'rgba(245, 158, 11, 0.2)',
                            tension: 0.4,
                            fill: true
                        }]
                    },
                    options: {
                        scales: {
                            y: {
                                beginAtZero: true
                            }
                        },
                        plugins: {
                            legend: {
                                display: false
                            }
                        },
                        responsive: true,
                        maintainAspectRatio: false
                    }
                });
            }

            // Handle plant selection change
            const plantSelect = document.getElementById('plant-select');

            if (plantSelect) {
                // Create charts if we have plants
                if (plantSelect.value) {
                    // Auto-create charts with the first selected plant
                    createCharts();

                    // Listen for changes
                    plantSelect.addEventListener('change', function() {
                        createCharts();
                    });
                }
            }

            // Function to refresh sensor data for all plants
            function refreshSensorData() {
                const plantCards = document.querySelectorAll('.plant-card');

                plantCards.forEach(card => {
                    const plantId = card.querySelector('.plant-image').getAttribute('data-plant-id');
                    if (!plantId) return;

                    // Fetch latest sensor data for this plant
                    fetch(`./mine-planter/get_sensor_data.php?plant_id=${plantId}`)
                        .then(response => response.json())
                        .then(data => {
                            if (data.success && data.sensor_data) {
                                // Update soil moisture - make sure to use the converted percentage value
                                const moistureElement = card.querySelector('.fa-tint').parentNode;
                                if (moistureElement && data.sensor_data.soil_moisture) {
                                    moistureElement.innerHTML = `<i class="fas fa-tint text-blue-500 mr-2"></i>${parseFloat(data.sensor_data.soil_moisture).toFixed(1)}%`;
                                }

                                // Update temperature
                                const tempElement = card.querySelector('.fa-temperature-high').parentNode;
                                if (tempElement && data.sensor_data.temperature) {
                                    tempElement.innerHTML = `<i class="fas fa-temperature-high text-red-500 mr-2"></i>${parseFloat(data.sensor_data.temperature).toFixed(1)}°C`;
                                }

                                // Update light level
                                const lightElement = card.querySelector('.fa-sun').parentNode;
                                if (lightElement && data.sensor_data.light_level) {
                                    lightElement.innerHTML = `<i class="fas fa-sun text-yellow-500 mr-2"></i>${Math.round(data.sensor_data.light_level)} units`;
                                }

                                // Update status badge
                                if (data.status) {
                                    const statusBadge = card.querySelector('.plant-image').parentNode.querySelector('span');
                                    if (statusBadge) {
                                        statusBadge.className = `absolute top-2 right-2 ${data.status.class} text-white text-xs px-2 py-1 rounded-full`;
                                        statusBadge.textContent = data.status.label;
                                    }
                                }
                            }
                        })
                        .catch(error => console.error('Error fetching sensor data:', error));
                });

                // Also update the status cards at the top
                updateStatusCounts();
            }

            // Function to update the status count cards (water, light, healthy)
            function updateStatusCounts() {
                fetch('./get_plant_status_counts.php')
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            // Update the counts in the status cards
                            const needsWaterElement = document.querySelector('.border-blue-500 .text-2xl');
                            const needsLightElement = document.querySelector('.border-yellow-500 .text-2xl');
                            const healthyElement = document.querySelector('.border-green-500 .text-2xl');

                            if (needsWaterElement) needsWaterElement.textContent = data.needs_water;
                            if (needsLightElement) needsLightElement.textContent = data.needs_light;
                            if (healthyElement) healthyElement.textContent = data.healthy;
                        }
                    })
                    .catch(error => console.error('Error updating status counts:', error));
            }

            // Call refreshSensorData immediately when page loads
            refreshSensorData();

            // Then start refreshing data every 3 seconds
            setInterval(refreshSensorData, 3000);
        });
    </script>
</body>

</html>