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

// Generate fake statistics data
$total_plants = count($plants);
$total_waterings = rand(15, 40);
$plant_health_percentage = rand(75, 98);
$plant_survival_rate = rand(80, 95);
$new_plants = min($total_plants, rand(1, 5));

// Generate random change percentages
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

// Generate fake plant performance data
$plant_performance = [];
foreach ($plants as $plant) {
    $plant_performance[] = [
        'plant' => $plant,
        'health' => rand(30, 100),
        'watering_need' => rand(1, 3), // 1=Low, 2=Moderate, 3=High
        'growth' => rand(1, 20),
        'status' => rand(1, 3) // 1=Healthy, 2=Needs Attention, 3=Critical
    ];
}

// Environmental conditions (fake data)
$temperature = rand(18, 26);
$temperature_change = rand(-3, 3);
$temperature_indicator = getChangeIndicator($temperature_change);

$humidity = rand(30, 70);
$humidity_change = rand(-15, 15);
$humidity_indicator = getChangeIndicator($humidity_change);

$light_levels = ['Lav', 'Middel', 'Høj'];
$light_level = $light_levels[rand(0, 2)];
$light_change = rand(-1, 1);
$light_indicator = getChangeIndicator($light_change);
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
                        <select class="bg-white border border-gray-300 rounded-lg px-4 py-2 focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-green-500">
                            <option>Sidste 30 dage</option>
                            <option>Sidste 3 måneder</option>
                            <option>Sidste 6 måneder</option>
                            <option>Sidste år</option>
                            <option>Hele perioden</option>
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
                                <div class="flex items-center justify-center h-full">
                                    <div class="text-center">
                                        <i class="fas fa-chart-bar text-5xl text-gray-300 mb-3"></i>
                                        <p class="text-gray-500">Graf over vandingsmønster vil vises her</p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Plant Health Tracking -->
                        <div class="bg-white rounded-lg shadow-sm p-5">
                            <h3 class="font-bold text-gray-800 mb-4">Plantesundhed over tid</h3>
                            <div class="chart-container">
                                <div class="flex items-center justify-center h-full">
                                    <div class="text-center">
                                        <i class="fas fa-chart-line text-5xl text-gray-300 mb-3"></i>
                                        <p class="text-gray-500">Graf over plantesundhed vil vises her</p>
                                    </div>
                                </div>
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
                                                        <img src="../../<?php echo $performance['plant']['image_path']; ?>" alt="<?php echo htmlspecialchars($performance['plant']['name']); ?>" class="h-full w-full object-cover" onerror="this.src='../../assets/plants/default.png'">
                                                    </div>
                                                    <div>
                                                        <p class="font-medium text-gray-800"><?php echo htmlspecialchars($performance['plant']['name']); ?></p>
                                                        <p class="text-sm text-gray-500"><?php echo htmlspecialchars($performance['plant']['location']); ?></p>
                                                    </div>
                                                </div>
                                            </td>
                                            <td class="py-3 px-4">
                                                <div class="w-full bg-gray-200 rounded-full h-2.5">
                                                    <div class="bg-green-600 h-2.5 rounded-full" style="width: <?php echo $performance['health']; ?>%"></div>
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
                    <div class="bg-white rounded-lg shadow-sm p-5">
                        <h3 class="font-bold text-gray-800 mb-4">Anbefalinger baseret på data</h3>
                        <div class="space-y-4">
                            <?php
                            // Generate random tips based on plants
                            $tip_types = ['water', 'light', 'success'];
                            $shown_plants = [];
                            $tips_count = min(3, count($plants));

                            for ($i = 0; $i < $tips_count; $i++) {
                                $plant_index = array_rand($plants);
                                // Avoid showing the same plant twice
                                while (in_array($plant_index, $shown_plants) && count($shown_plants) < count($plants)) {
                                    $plant_index = array_rand($plants);
                                }
                                $shown_plants[] = $plant_index;
                                $plant = $plants[$plant_index];
                                $tip_type = $tip_types[array_rand($tip_types)];

                                switch ($tip_type) {
                                    case 'water':
                            ?>
                                        <div class="flex items-start p-4 bg-blue-50 rounded-lg">
                                            <div class="h-10 w-10 rounded-full bg-blue-100 flex items-center justify-center mr-4 flex-shrink-0">
                                                <i class="fas fa-tint text-blue-600"></i>
                                            </div>
                                            <div>
                                                <p class="font-medium text-gray-800">Øg vandingsfrekvensen for <?php echo htmlspecialchars($plant['name']); ?></p>
                                                <p class="text-sm text-gray-600 mt-1">Din <?php echo htmlspecialchars($plant['name']); ?> viser tegn på udtørring. Prøv at vande den lidt oftere i de næste par uger.</p>
                                            </div>
                                        </div>
                                    <?php
                                        break;
                                    case 'light':
                                    ?>
                                        <div class="flex items-start p-4 bg-yellow-50 rounded-lg">
                                            <div class="h-10 w-10 rounded-full bg-yellow-100 flex items-center justify-center mr-4 flex-shrink-0">
                                                <i class="fas fa-sun text-yellow-600"></i>
                                            </div>
                                            <div>
                                                <p class="font-medium text-gray-800">Flyt <?php echo htmlspecialchars($plant['name']); ?> til et lysere sted</p>
                                                <p class="text-sm text-gray-600 mt-1">Din <?php echo htmlspecialchars($plant['name']); ?> vil trives bedre med mere lys, især i vintermånederne.</p>
                                            </div>
                                        </div>
                                    <?php
                                        break;
                                    case 'success':
                                    ?>
                                        <div class="flex items-start p-4 bg-green-50 rounded-lg">
                                            <div class="h-10 w-10 rounded-full bg-green-100 flex items-center justify-center mr-4 flex-shrink-0">
                                                <i class="fas fa-seedling text-green-600"></i>
                                            </div>
                                            <div>
                                                <p class="font-medium text-gray-800">Din <?php echo htmlspecialchars($plant['name']); ?> er i topform!</p>
                                                <p class="text-sm text-gray-600 mt-1">Fortsæt med din nuværende pleje, den klarer sig fremragende.</p>
                                            </div>
                                        </div>
                            <?php
                                        break;
                                }
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
        });
    </script>
</body>

</html>