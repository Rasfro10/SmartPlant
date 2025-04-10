<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/smartplant/backend/auth/session_handler.php';
// Set current page for sidebar highlighting
$page = "statistics";

// Include header
include('../../components/header.php');

// Get user's plants from database
$user_id = $_SESSION['id'];
$plants = [];

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

// Get real data for statistics
$total_plants = count($plants);
$total_waterings = 0;
$plant_health_percentage = 0;
$plant_survival_rate = 0;
$plant_health_sum = 0;
$plant_count_with_data = 0;

// Get sensor data for all plants
$plant_data = [];
$plant_performance = [];

foreach ($plants as $plant) {
    // Get latest sensor data for the plant
    $sql = "SELECT * FROM plant_data WHERE plant_id = ? ORDER BY reading_time DESC LIMIT 1";
    $latest_sensor_data = null;

    if ($stmt = $conn->prepare($sql)) {
        $stmt->bind_param("i", $plant['id']);

        if ($stmt->execute()) {
            $result = $stmt->get_result();
            if ($result->num_rows > 0) {
                $latest_sensor_data = $result->fetch_assoc();
            }
        }
        $stmt->close();
    }

    // Get watering count for this plant
    $sql = "SELECT COUNT(*) as watering_count FROM plant_data WHERE plant_id = ? AND watered_at IS NOT NULL";
    $watering_count = 0;

    if ($stmt = $conn->prepare($sql)) {
        $stmt->bind_param("i", $plant['id']);

        if ($stmt->execute()) {
            $result = $stmt->get_result();
            if ($result->num_rows > 0) {
                $row = $result->fetch_assoc();
                $watering_count = $row['watering_count'];
            }
        }
        $stmt->close();
    }

    $total_waterings += $watering_count;

    // Function to convert moisture to percentage
    function convertMoistureToPercentage($rawValue)
    {
        // These values are based on your observed sensor readings
        // Dry soil (needs water): 730-800
        // Wet soil: 370-430
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
            $percentage = map($rawValue, $wetSoilMin, $drySoilMax, 30, 100);
        }

        return round($percentage, 1);
    }

    // Function to map a value from one range to another
    function map($value, $inMin, $inMax, $outMin, $outMax)
    {
        return ($value - $inMin) * ($outMax - $outMin) / ($inMax - $inMin) + $outMin;
    }

    // Function to convert light level
    function convertLightToLevel($rawValue)
    {
        // Adjust the ranges to match your 700-1000 sensor readings
        if ($rawValue < 750) {
            return [
                'level' => 'Lav',
                'percentage' => 30,
                'description' => 'Svagt lys'
            ];
        } elseif ($rawValue < 850) {
            return [
                'level' => 'Moderat',
                'percentage' => 50,
                'description' => 'Normalt indendørslys'
            ];
        } elseif ($rawValue < 950) {
            return [
                'level' => 'Høj',
                'percentage' => 70,
                'description' => 'Kraftigt lys'
            ];
        } else {
            return [
                'level' => 'Meget høj',
                'percentage' => 90,
                'description' => 'Meget kraftigt lys'
            ];
        }
    }

    // Process sensor data if available
    $plant_health = 0;
    $watering_need = 0;
    $status = 3; // Default to critical

    if ($latest_sensor_data) {
        // Convert sensor values to meaningful data
        $moisture_percentage = convertMoistureToPercentage($latest_sensor_data['soil_moisture']);
        $light_info = convertLightToLevel($latest_sensor_data['light_level']);
        $light_percentage = $light_info['percentage'];

        // Calculate plant health based on moisture and temperature
        $moisture_score = $moisture_percentage;
        $temp_score = 0;

        if ($latest_sensor_data['temperature'] >= 18 && $latest_sensor_data['temperature'] <= 25) {
            $temp_score = 100; // Optimal temperature
        } elseif ($latest_sensor_data['temperature'] >= 15 && $latest_sensor_data['temperature'] <= 28) {
            $temp_score = 80; // Good temperature
        } elseif ($latest_sensor_data['temperature'] >= 10 && $latest_sensor_data['temperature'] <= 32) {
            $temp_score = 60; // Acceptable temperature
        } else {
            $temp_score = 40; // Poor temperature
        }

        // Calculate overall health
        $plant_health = round(($moisture_score * 0.6) + ($temp_score * 0.4));

        // Calculate watering need
        if ($moisture_percentage < 30) {
            $watering_need = 3; // High
        } elseif ($moisture_percentage < 60) {
            $watering_need = 2; // Moderate
        } else {
            $watering_need = 1; // Low
        }

        // Determine plant status
        if ($plant_health >= 80) {
            $status = 1; // Healthy
        } elseif ($plant_health >= 50) {
            $status = 2; // Needs attention
        } else {
            $status = 3; // Critical
        }

        // Add to health sum for average calculation
        $plant_health_sum += $plant_health;
        $plant_count_with_data++;

        // Store sensor data
        $plant_data[$plant['id']] = [
            'moisture' => $moisture_percentage,
            'light' => $light_percentage,
            'temperature' => $latest_sensor_data['temperature'],
            'humidity' => $latest_sensor_data['humidity'],
            'health' => $plant_health,
            'last_reading' => $latest_sensor_data['reading_time']
        ];
    }

    // Get recent growth data (simulate with random data for now)
    $growth = rand(1, 20); // Replace with real growth tracking when available

    // Add to plant performance array
    $plant_performance[] = [
        'plant' => $plant,
        'health' => $plant_health,
        'watering_need' => $watering_need,
        'growth' => $growth,
        'status' => $status
    ];
}

// Calculate overall statistics
if ($plant_count_with_data > 0) {
    $plant_health_percentage = round($plant_health_sum / $plant_count_with_data);
    $plant_survival_rate = round(($plant_count_with_data / max(1, $total_plants)) * 100);
} else {
    $plant_health_percentage = 0;
    $plant_survival_rate = 0;
}

// Get new plants in the last 30 days
$thirty_days_ago = date('Y-m-d H:i:s', strtotime('-30 days'));
$sql = "SELECT COUNT(*) as new_plants FROM plants WHERE user_id = ? AND created_at >= ?";
$new_plants = 0;

if ($stmt = $conn->prepare($sql)) {
    $stmt->bind_param("is", $user_id, $thirty_days_ago);

    if ($stmt->execute()) {
        $result = $stmt->get_result();
        if ($result->num_rows > 0) {
            $row = $result->fetch_assoc();
            $new_plants = $row['new_plants'];
        }
    }
    $stmt->close();
}

// Get historical data for change calculations
// For this example, we'll use sample change percentages
// In a real implementation, you would calculate these from historical data
$watering_change = rand(-20, 30);
$health_change = rand(-10, 15);
$survival_change = rand(-5, 5);
$new_plants_change = rand(-30, 50);

// Determine change indicator classes
function getChangeIndicator($change)
{
    if ($change > 0) {
        return [
            'icon' => 'fa-arrow-up',
            'color' => 'text-green-600'
        ];
    } elseif ($change < 0) {
        return [
            'icon' => 'fa-arrow-down',
            'color' => 'text-red-600'
        ];
    } else {
        return [
            'icon' => 'fa-minus',
            'color' => 'text-yellow-600'
        ];
    }
}

$watering_indicator = getChangeIndicator($watering_change);
$health_indicator = getChangeIndicator($health_change);
$survival_indicator = getChangeIndicator($survival_change);
$new_plants_indicator = getChangeIndicator($new_plants_change);

// Get average environmental conditions
$temperature = 0;
$humidity = 0;
$light_level = 'Middel';
$light_level_value = 1; // 0=Lav, 1=Middel, 2=Høj
$temperature_sum = 0;
$humidity_sum = 0;
$light_sum = 0;
$count = 0;

// Calculate averages from plant data
foreach ($plant_data as $data) {
    $temperature_sum += $data['temperature'];
    $humidity_sum += $data['humidity'];
    $light_sum += $data['light'];
    $count++;
}

if ($count > 0) {
    $temperature = round($temperature_sum / $count, 1);
    $humidity = round($humidity_sum / $count);

    $avg_light = $light_sum / $count;
    if ($avg_light < 40) {
        $light_level = 'Lav';
        $light_level_value = 0;
    } elseif ($avg_light > 70) {
        $light_level = 'Høj';
        $light_level_value = 2;
    } else {
        $light_level = 'Middel';
        $light_level_value = 1;
    }
}

// For the change indicators of environmental data, we'll use placeholders
// In a real implementation, compare with historical averages
$temperature_change = rand(-3, 3);
$humidity_change = rand(-15, 15);
$light_change = rand(-1, 1);

$temperature_indicator = getChangeIndicator($temperature_change);
$humidity_indicator = getChangeIndicator($humidity_change);
$light_indicator = getChangeIndicator($light_change);

$light_levels = ['Lav', 'Middel', 'Høj'];
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

        .stats-card {
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }

        .stats-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
        }

        /* Styling for charts */
        .chart-container {
            position: relative;
            height: 300px;
            width: 100%;
        }
    </style>
    <!-- Chart.js for charts -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
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
                <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-6">
                    <div>
                        <h2 class="text-2xl font-bold text-gray-800">Statistik</h2>
                        <p class="text-gray-600">Se indsigt og data om dine planter og pleje.</p>
                    </div>
                    <div class="mt-3 md:mt-0">
                        <select id="time-period" class="bg-white border border-gray-300 rounded-lg px-4 py-2 focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-green-500">
                            <option value="30">Sidste 30 dage</option>
                            <option value="90">Sidste 3 måneder</option>
                            <option value="180">Sidste 6 måneder</option>
                            <option value="365">Sidste år</option>
                            <option value="all">Hele perioden</option>
                        </select>
                    </div>
                </div>

                <?php if (count($plants) > 0): ?>
                    <!-- Key Stats Cards -->
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
                        <div class="bg-white rounded-lg shadow-sm p-4 stats-card">
                            <div class="flex justify-between items-center">
                                <div>
                                    <p class="text-gray-500 text-sm">Antal vandinger</p>
                                    <h3 class="text-2xl font-bold text-gray-800"><?php echo $total_waterings; ?></h3>
                                    <p class="<?php echo $watering_indicator['color']; ?> text-xs mt-1 flex items-center">
                                        <i class="fas <?php echo $watering_indicator['icon']; ?> mr-1"></i>
                                        <?php echo abs($watering_change); ?>% fra forrige periode
                                    </p>
                                </div>
                                <div class="h-12 w-12 rounded-full bg-blue-100 flex items-center justify-center">
                                    <i class="fas fa-tint text-blue-600 text-xl"></i>
                                </div>
                            </div>
                        </div>

                        <div class="bg-white rounded-lg shadow-sm p-4 stats-card">
                            <div class="flex justify-between items-center">
                                <div>
                                    <p class="text-gray-500 text-sm">Planternes sundhed</p>
                                    <h3 class="text-2xl font-bold text-gray-800"><?php echo $plant_health_percentage; ?>%</h3>
                                    <p class="<?php echo $health_indicator['color']; ?> text-xs mt-1 flex items-center">
                                        <i class="fas <?php echo $health_indicator['icon']; ?> mr-1"></i>
                                        <?php echo abs($health_change); ?>% fra forrige periode
                                    </p>
                                </div>
                                <div class="h-12 w-12 rounded-full bg-green-100 flex items-center justify-center">
                                    <i class="fas fa-heartbeat text-green-600 text-xl"></i>
                                </div>
                            </div>
                        </div>

                        <div class="bg-white rounded-lg shadow-sm p-4 stats-card">
                            <div class="flex justify-between items-center">
                                <div>
                                    <p class="text-gray-500 text-sm">Planteoverlevelse</p>
                                    <h3 class="text-2xl font-bold text-gray-800"><?php echo $plant_survival_rate; ?>%</h3>
                                    <p class="<?php echo $survival_indicator['color']; ?> text-xs mt-1 flex items-center">
                                        <i class="fas <?php echo $survival_indicator['icon']; ?> mr-1"></i>
                                        <?php if ($survival_change == 0): ?>
                                            Uændret fra forrige periode
                                        <?php else: ?>
                                            <?php echo abs($survival_change); ?>% fra forrige periode
                                        <?php endif; ?>
                                    </p>
                                </div>
                                <div class="h-12 w-12 rounded-full bg-yellow-100 flex items-center justify-center">
                                    <i class="fas fa-seedling text-yellow-600 text-xl"></i>
                                </div>
                            </div>
                        </div>

                        <div class="bg-white rounded-lg shadow-sm p-4 stats-card">
                            <div class="flex justify-between items-center">
                                <div>
                                    <p class="text-gray-500 text-sm">Nye planter</p>
                                    <h3 class="text-2xl font-bold text-gray-800"><?php echo $new_plants; ?></h3>
                                    <p class="<?php echo $new_plants_indicator['color']; ?> text-xs mt-1 flex items-center">
                                        <i class="fas <?php echo $new_plants_indicator['icon']; ?> mr-1"></i>
                                        <?php echo abs($new_plants_change); ?>% fra forrige periode
                                    </p>
                                </div>
                                <div class="h-12 w-12 rounded-full bg-purple-100 flex items-center justify-center">
                                    <i class="fas fa-plus text-purple-600 text-xl"></i>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Charts Section -->
                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
                        <!-- Watering Frequency Chart -->
                        <div class="bg-white rounded-lg shadow-sm p-5">
                            <h3 class="font-bold text-gray-800 mb-4">Vandingsmønster</h3>
                            <div class="chart-container">
                                <canvas id="wateringChart"></canvas>
                            </div>
                        </div>

                        <!-- Plant Health Tracking -->
                        <div class="bg-white rounded-lg shadow-sm p-5">
                            <h3 class="font-bold text-gray-800 mb-4">Plantesundhed over tid</h3>
                            <div class="chart-container">
                                <canvas id="healthChart"></canvas>
                            </div>
                        </div>
                    </div>

                    <!-- Plant Performance Table -->
                    <div class="bg-white rounded-lg shadow-sm p-5 mb-6">
                        <h3 class="font-bold text-gray-800 mb-4">Plant Ydeevne</h3>
                        <div class="overflow-x-auto">
                            <table class="min-w-full bg-white">
                                <thead>
                                    <tr class="bg-gray-100 border-b">
                                        <th class="py-3 px-4 text-left font-medium text-gray-600">Plante</th>
                                        <th class="py-3 px-4 text-left font-medium text-gray-600">Sundhed</th>
                                        <th class="py-3 px-4 text-left font-medium text-gray-600">Vandingsbehov</th>
                                        <th class="py-3 px-4 text-left font-medium text-gray-600">Vækst</th>
                                        <th class="py-3 px-4 text-left font-medium text-gray-600">Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($plant_performance as $performance): ?>
                                        <tr class="border-b hover:bg-gray-50">
                                            <td class="py-3 px-4">
                                                <div class="flex items-center">
                                                    <div class="h-10 w-10 rounded-full overflow-hidden bg-gray-100 flex-shrink-0 mr-3">
                                                        <img src="../../<?php echo !empty($performance['plant']['image_path']) ? $performance['plant']['image_path'] : 'assets/plants/default.png'; ?>" alt="<?php echo htmlspecialchars($performance['plant']['name']); ?>" class="h-full w-full object-cover" onerror="this.src='../../assets/plants/default.png'">
                                                    </div>
                                                    <div>
                                                        <p class="font-medium text-gray-800"><?php echo htmlspecialchars($performance['plant']['name']); ?></p>
                                                        <p class="text-sm text-gray-500"><?php echo htmlspecialchars($performance['plant']['location']); ?></p>
                                                    </div>
                                                </div>
                                            </td>
                                            <td class="py-3 px-4">
                                                <div class="w-full bg-gray-200 rounded-full h-2.5">
                                                    <div class="<?php echo $performance['health'] > 50 ? 'bg-green-600' : ($performance['health'] > 30 ? 'bg-yellow-600' : 'bg-red-600'); ?> h-2.5 rounded-full" style="width: <?php echo $performance['health']; ?>%"></div>
                                                </div>
                                                <span class="text-sm text-gray-600"><?php echo $performance['health']; ?>%</span>
                                            </td>
                                            <td class="py-3 px-4">
                                                <?php if ($performance['watering_need'] == 1): ?>
                                                    <span class="px-2 py-1 bg-green-100 text-green-800 rounded-full text-xs">Lav</span>
                                                <?php elseif ($performance['watering_need'] == 2): ?>
                                                    <span class="px-2 py-1 bg-yellow-100 text-yellow-800 rounded-full text-xs">Moderat</span>
                                                <?php else: ?>
                                                    <span class="px-2 py-1 bg-red-100 text-red-800 rounded-full text-xs">Højt</span>
                                                <?php endif; ?>
                                            </td>
                                            <td class="py-3 px-4">
                                                <div class="flex items-center">
                                                    <i class="fas fa-arrow-up text-green-600 mr-1"></i>
                                                    <span><?php echo $performance['growth']; ?> cm</span>
                                                </div>
                                            </td>
                                            <td class="py-3 px-4">
                                                <?php if ($performance['status'] == 1): ?>
                                                    <span class="px-2 py-1 bg-green-100 text-green-800 rounded-full text-xs">Sund</span>
                                                <?php elseif ($performance['status'] == 2): ?>
                                                    <span class="px-2 py-1 bg-yellow-100 text-yellow-800 rounded-full text-xs">Behøver opmærksomhed</span>
                                                <?php else: ?>
                                                    <span class="px-2 py-1 bg-red-100 text-red-800 rounded-full text-xs">Kritisk</span>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Environmental Conditions -->
                    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-6">
                        <!-- Temperature -->
                        <div class="bg-white rounded-lg shadow-sm p-5">
                            <div class="flex items-center justify-between mb-4">
                                <h3 class="font-bold text-gray-800">Temperatur</h3>
                                <div class="h-10 w-10 rounded-full bg-red-100 flex items-center justify-center">
                                    <i class="fas fa-temperature-high text-red-600"></i>
                                </div>
                            </div>
                            <div class="flex items-center justify-center">
                                <div class="text-center">
                                    <span class="text-5xl font-bold text-gray-800"><?php echo $temperature; ?>°C</span>
                                    <p class="<?php echo $temperature_indicator['color']; ?> text-sm mt-2 flex items-center justify-center">
                                        <i class="fas <?php echo $temperature_indicator['icon']; ?> mr-1"></i>
                                        <?php echo abs($temperature_change); ?>°C fra sidste måned
                                    </p>
                                    <p class="text-sm text-gray-600 mt-4">
                                        <?php
                                        if ($temperature < 18) {
                                            echo 'Lidt for køligt for de fleste af dine planter';
                                        } elseif ($temperature > 25) {
                                            echo 'Lidt for varmt for nogle af dine planter';
                                        } else {
                                            echo 'Optimal temperatur for de fleste af dine planter';
                                        }
                                        ?>
                                    </p>
                                </div>
                            </div>
                        </div>

                        <!-- Humidity -->
                        <div class="bg-white rounded-lg shadow-sm p-5">
                            <div class="flex items-center justify-between mb-4">
                                <h3 class="font-bold text-gray-800">Luftfugtighed</h3>
                                <div class="h-10 w-10 rounded-full bg-blue-100 flex items-center justify-center">
                                    <i class="fas fa-cloud-rain text-blue-600"></i>
                                </div>
                            </div>
                            <div class="flex items-center justify-center">
                                <div class="text-center">
                                    <span class="text-5xl font-bold text-gray-800"><?php echo $humidity; ?>%</span>
                                    <p class="<?php echo $humidity_indicator['color']; ?> text-sm mt-2 flex items-center justify-center">
                                        <i class="fas <?php echo $humidity_indicator['icon']; ?> mr-1"></i>
                                        <?php echo abs($humidity_change); ?>% fra sidste måned
                                    </p>
                                    <p class="text-sm text-gray-600 mt-4">
                                        <?php
                                        if ($humidity < 40) {
                                            echo 'Lidt lavt for nogle af dine tropiske planter';
                                        } elseif ($humidity > 60) {
                                            echo 'God luftfugtighed for tropiske planter';
                                        } else {
                                            echo 'Passende luftfugtighed for de fleste planter';
                                        }
                                        ?>
                                    </p>
                                </div>
                            </div>
                        </div>

                        <!-- Light Levels -->
                        <div class="bg-white rounded-lg shadow-sm p-5">
                            <div class="flex items-center justify-between mb-4">
                                <h3 class="font-bold text-gray-800">Lysniveau</h3>
                                <div class="h-10 w-10 rounded-full bg-yellow-100 flex items-center justify-center">
                                    <i class="fas fa-sun text-yellow-600"></i>
                                </div>
                            </div>
                            <div class="flex items-center justify-center">
                                <div class="text-center">
                                    <span class="text-5xl font-bold text-gray-800"><?php echo $light_level; ?></span>
                                    <p class="<?php echo $light_indicator['color']; ?> text-sm mt-2 flex items-center justify-center">
                                        <?php if ($light_change == 0): ?>
                                            <i class="fas fa-minus mr-1"></i> Uændret fra sidste måned
                                        <?php elseif ($light_change > 0): ?>
                                            <i class="fas fa-arrow-up mr-1"></i> Bedre fra sidste måned
                                        <?php else: ?>
                                            <i class="fas fa-arrow-down mr-1"></i> Dårligere fra sidste måned
                                        <?php endif; ?>
                                    </p>
                                    <p class="text-sm text-gray-600 mt-4">
                                        <?php
                                        if ($light_level == 'Lav') {
                                            echo 'Overvej at flytte nogle planter til et lysere sted';
                                        } elseif ($light_level == 'Høj') {
                                            echo 'Pas på direkte sollys for skyggeelskende planter';
                                        } else {
                                            echo 'Passende for de fleste af dine planter';
                                        }
                                        ?>
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Tips Based on Stats -->
                    <div class="bg-white rounded-lg shadow-sm p-5 mb-6">
                        <h3 class="font-bold text-gray-800 mb-4">Anbefalinger baseret på data</h3>
                        <div class="space-y-4">
                            <?php
                            // Generate recommendations based on actual data
                            $recommendations = [];

                            foreach ($plant_performance as $performance) {
                                $plant = $performance['plant'];

                                // Check if we need to add a recommendation
                                if ($performance['watering_need'] == 3) {
                                    // High watering need
                                    $recommendations[] = [
                                        'type' => 'water',
                                        'plant' => $plant,
                                        'title' => "Øg vandingsfrekvensen for {$plant['name']}",
                                        'description' => "Din {$plant['name']} viser tegn på udtørring. Prøv at vande den lidt oftere i de næste par uger."
                                    ];
                                } elseif ($performance['health'] < 50) {
                                    // Poor health
                                    $recommendations[] = [
                                        'type' => 'health',
                                        'plant' => $plant,
                                        'title' => "Tjek din {$plant['name']} for sundhedsproblemer",
                                        'description' => "Din {$plant['name']} ser ud til at være under stress. Kontroller for skadedyr, sygdomme eller andre problemer."
                                    ];
                                } elseif ($performance['health'] > 80) {
                                    // Excellent health
                                    $recommendations[] = [
                                        'type' => 'success',
                                        'plant' => $plant,
                                        'title' => "Din {$plant['name']} er i topform!",
                                        'description' => "Fortsæt med din nuværende pleje, den klarer sig fremragende."
                                    ];
                                }
                            }

                            // Add general recommendations based on environmental conditions
                            if ($temperature < 18) {
                                $recommendations[] = [
                                    'type' => 'temperature',
                                    'title' => "Temperaturen er lidt lav",
                                    'description' => "Overvej at øge temperaturen lidt for bedre plantevækst."
                                ];
                            }

                            if ($humidity < 40) {
                                $recommendations[] = [
                                    'type' => 'humidity',
                                    'title' => "Luftfugtigheden er lav",
                                    'description' => "Øg luftfugtigheden ved at sprøjte vand omkring planterne eller brug en luftfugter."
                                ];
                            }

                            if ($light_level == 'Lav') {
                                $recommendations[] = [
                                    'type' => 'light',
                                    'title' => "Lysniveauet er lavt for nogle planter",
                                    'description' => "Overvej at flytte nogle af dine planter til et lysere sted eller tilføj kunstigt lys."
                                ];
                            }

                            // Limit to 3 recommendations
                            shuffle($recommendations);
                            $recommendations = array_slice($recommendations, 0, 3);

                            // Display recommendations
                            foreach ($recommendations as $recommendation) {
                                $icon_class = "";
                                $bg_class = "";

                                switch ($recommendation['type']) {
                                    case 'water':
                                        $icon_class = "fa-tint text-blue-600";
                                        $bg_class = "bg-blue-50";
                                        break;
                                    case 'light':
                                        $icon_class = "fa-sun text-yellow-600";
                                        $bg_class = "bg-yellow-50";
                                        break;
                                    case 'success':
                                        $icon_class = "fa-seedling text-green-600";
                                        $bg_class = "bg-green-50";
                                        break;
                                    case 'health':
                                        $icon_class = "fa-heartbeat text-red-600";
                                        $bg_class = "bg-red-50";
                                        break;
                                    case 'temperature':
                                        $icon_class = "fa-temperature-high text-red-600";
                                        $bg_class = "bg-red-50";
                                        break;
                                    case 'humidity':
                                        $icon_class = "fa-cloud-rain text-blue-600";
                                        $bg_class = "bg-blue-50";
                                        break;
                                    default:
                                        $icon_class = "fa-leaf text-green-600";
                                        $bg_class = "bg-green-50";
                                }
                            ?>
                                <div class="flex items-start p-4 <?php echo $bg_class; ?> rounded-lg">
                                    <div class="h-10 w-10 rounded-full bg-white flex items-center justify-center mr-4 flex-shrink-0">
                                        <i class="fas <?php echo $icon_class; ?>"></i>
                                    </div>
                                    <div>
                                        <p class="font-medium text-gray-800"><?php echo $recommendation['title']; ?></p>
                                        <p class="text-sm text-gray-600 mt-1"><?php echo $recommendation['description']; ?></p>
                                    </div>
                                </div>
                            <?php
                            }

                            // If no recommendations, show a default message
                            if (empty($recommendations)) {
                            ?>
                                <div class="flex items-start p-4 bg-green-50 rounded-lg">
                                    <div class="h-10 w-10 rounded-full bg-white flex items-center justify-center mr-4 flex-shrink-0">
                                        <i class="fas fa-check-circle text-green-600"></i>
                                    </div>
                                    <div>
                                        <p class="font-medium text-gray-800">Alle dine planter ser ud til at trives!</p>
                                        <p class="text-sm text-gray-600 mt-1">Fortsæt med din nuværende pleje. Vi vil give dig besked, hvis der opstår nogen problemer.</p>
                                    </div>
                                </div>
                            <?php
                            }
                            ?>
                        </div>
                    </div>

                <?php else: ?>
                    <!-- No plants message -->
                    <div class="bg-white rounded-lg shadow-sm p-8 text-center">
                        <div class="w-16 h-16 mx-auto bg-gray-100 rounded-full flex items-center justify-center mb-4">
                            <i class="fas fa-seedling text-gray-400 text-2xl"></i>
                        </div>
                        <h3 class="text-xl font-semibold text-gray-700 mb-2">Ingen planter endnu</h3>
                        <p class="text-gray-500 mb-4">Det ser ud til, at du ikke har tilføjet nogen planter endnu. Tilføj planter for at se statistik og anbefalinger.</p>
                        <a href="../../dashboard/mine-planter/add-plants.php" class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg transition inline-flex items-center">
                            <i class="fas fa-plus mr-2"></i> Tilføj din første plante
                        </a>
                    </div>
                <?php endif; ?>
            </main>
        </div>
    </div>

    <!-- Mobile overlay when sidebar is open -->
    <div id="overlay" class="fixed inset-0 bg-black bg-opacity-50 z-20 hidden md:hidden"></div>

    <!-- JavaScript -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const menuToggle = document.getElementById('menu-toggle');
            const sidebar = document.getElementById('sidebar');
            const overlay = document.getElementById('overlay');

            // Toggle mobile menu
            if (menuToggle) {
                menuToggle.addEventListener('click', function() {
                    sidebar.classList.toggle('open');
                    overlay.classList.toggle('hidden');
                    document.body.classList.toggle('overflow-hidden');
                });
            }

            // Close menu when clicking overlay
            if (overlay) {
                overlay.addEventListener('click', function() {
                    sidebar.classList.remove('open');
                    overlay.classList.add('hidden');
                    document.body.classList.remove('overflow-hidden');
                });
            }

            // Handle window resize
            window.addEventListener('resize', function() {
                if (window.innerWidth >= 768) {
                    sidebar.classList.remove('open');
                    overlay.classList.add('hidden');
                    document.body.classList.remove('overflow-hidden');
                }
            });

            // Initialize charts
            initCharts();
        });

        function initCharts() {
            // Sample data for charts - in real implementation, this would come from AJAX/PHP

            // Watering Frequency Chart
            const wateringCtx = document.getElementById('wateringChart').getContext('2d');
            const wateringChart = new Chart(wateringCtx, {
                type: 'bar',
                data: {
                    labels: ['Man', 'Tir', 'Ons', 'Tor', 'Fre', 'Lør', 'Søn'],
                    datasets: [{
                        label: 'Antal vandinger',
                        data: [2, 1, 3, 0, 4, 1, 2],
                        backgroundColor: 'rgba(59, 130, 246, 0.6)',
                        borderColor: 'rgba(59, 130, 246, 1)',
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                precision: 0
                            }
                        }
                    }
                }
            });

            // Plant Health Tracking Chart
            const healthCtx = document.getElementById('healthChart').getContext('2d');
            const healthChart = new Chart(healthCtx, {
                type: 'line',
                data: {
                    labels: ['Jan', 'Feb', 'Mar', 'Apr', 'Maj', 'Jun', 'Jul'],
                    datasets: [{
                        label: 'Gennemsnitlig plantesundhed',
                        data: [65, 70, 68, 72, 75, 80, 82],
                        backgroundColor: 'rgba(16, 185, 129, 0.2)',
                        borderColor: 'rgba(16, 185, 129, 1)',
                        borderWidth: 2,
                        tension: 0.3
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        y: {
                            beginAtZero: false,
                            min: 50,
                            max: 100
                        }
                    }
                }
            });

            // Time period selection handler
            document.getElementById('time-period').addEventListener('change', function() {
                // In a real application, this would fetch new data based on the selected time period
                // For this example, we'll just simulate changing the data

                const period = this.value;
                let labels = [];
                let wateringData = [];
                let healthData = [];

                // Simulate different data for different time periods
                switch (period) {
                    case '30': // 30 days
                        labels = ['Uge 1', 'Uge 2', 'Uge 3', 'Uge 4'];
                        wateringData = [5, 7, 6, 8];
                        healthData = [75, 78, 80, 82];
                        break;
                    case '90': // 3 months
                        labels = ['Jan', 'Feb', 'Mar'];
                        wateringData = [12, 15, 18];
                        healthData = [70, 75, 80];
                        break;
                    case '180': // 6 months
                        labels = ['Jan', 'Feb', 'Mar', 'Apr', 'Maj', 'Jun'];
                        wateringData = [10, 12, 15, 18, 20, 22];
                        healthData = [65, 68, 72, 75, 78, 82];
                        break;
                    case '365': // 1 year
                        labels = ['Jan', 'Feb', 'Mar', 'Apr', 'Maj', 'Jun', 'Jul', 'Aug', 'Sep', 'Okt', 'Nov', 'Dec'];
                        wateringData = [8, 9, 12, 15, 18, 22, 24, 20, 16, 14, 10, 9];
                        healthData = [60, 62, 65, 70, 75, 80, 82, 80, 78, 75, 70, 65];
                        break;
                    default: // all time
                        labels = ['2022', '2023', '2024'];
                        wateringData = [85, 120, 95];
                        healthData = [60, 70, 80];
                }

                // Update charts
                wateringChart.data.labels = labels;
                wateringChart.data.datasets[0].data = wateringData;
                wateringChart.update();

                healthChart.data.labels = labels;
                healthChart.data.datasets[0].data = healthData;
                healthChart.update();
            });
        }
    </script>
</body>

</html>