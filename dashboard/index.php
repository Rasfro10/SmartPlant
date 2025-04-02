<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/smartplant/backend/auth/session_handler.php';
// Set current page for sidebar highlighting
$page = "dashboard";
include('../components/header.php');
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
                                <h3 class="text-2xl font-bold text-gray-800">2</h3>
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
                                <h3 class="text-2xl font-bold text-gray-800">1</h3>
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
                                <h3 class="text-2xl font-bold text-gray-800">5</h3>
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
                                <h3 class="text-2xl font-bold text-gray-800">8</h3>
                            </div>
                            <div class="h-10 w-10 rounded-full bg-purple-100 flex items-center justify-center">
                                <i class="fas fa-seedling text-purple-600"></i>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- Notifications & Recent Activity -->
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-6">
                    <!-- Notifications -->
                    <div class="bg-white rounded-lg shadow-sm p-4">
                        <h3 class="font-bold text-gray-800 mb-4">Notifikationer</h3>
                        <div class="space-y-3">
                            <div class="flex items-start p-3 bg-red-50 rounded-lg">
                                <div class="h-8 w-8 rounded-full bg-red-100 flex items-center justify-center mr-3">
                                    <i class="fas fa-exclamation-circle text-red-600"></i>
                                </div>
                                <div class="flex-1">
                                    <p class="text-gray-800"><span class="font-medium">Monstera</span> har kritisk behov for vand</p>
                                    <p class="text-gray-500 text-xs">for 2 timer siden</p>
                                </div>
                            </div>

                            <div class="flex items-start p-3 bg-yellow-50 rounded-lg">
                                <div class="h-8 w-8 rounded-full bg-yellow-100 flex items-center justify-center mr-3">
                                    <i class="fas fa-sun text-yellow-600"></i>
                                </div>
                                <div class="flex-1">
                                    <p class="text-gray-800"><span class="font-medium">Ficus</span> trænger til mere lys</p>
                                    <p class="text-gray-500 text-xs">i går kl. 12:15</p>
                                </div>
                            </div>

                            <div class="flex items-start p-3 bg-blue-50 rounded-lg">
                                <div class="h-8 w-8 rounded-full bg-blue-100 flex items-center justify-center mr-3">
                                    <i class="fas fa-thermometer-half text-blue-600"></i>
                                </div>
                                <div class="flex-1">
                                    <p class="text-gray-800">Temperaturen er faldet for <span class="font-medium">Sansevieria</span></p>
                                    <p class="text-gray-500 text-xs">i dag kl. 06:30</p>
                                </div>
                            </div>
                        </div>
                        <a href="#" class="mt-4 text-sm text-green-600 hover:text-green-800 font-medium flex items-center justify-end">
                            Se alle notifikationer <i class="fas fa-arrow-right ml-1"></i>
                        </a>
                    </div>

                    <!-- Recent Activity -->
                    <div class="lg:col-span-2 bg-white rounded-lg shadow-sm p-4">
                        <h3 class="font-bold text-gray-800 mb-4">Seneste Aktiviteter</h3>
                        <div class="space-y-3">
                            <div class="flex items-start border-b border-gray-100 pb-3">
                                <div class="h-8 w-8 rounded-full bg-red-100 flex items-center justify-center mr-3">
                                    <i class="fas fa-exclamation-circle text-red-600"></i>
                                </div>
                                <div class="flex-1">
                                    <p class="text-gray-800"><span class="font-medium">Monstera</span> har kritisk behov for vand</p>
                                    <p class="text-gray-500 text-xs">for 2 timer siden</p>
                                </div>
                            </div>

                            <div class="flex items-start border-b border-gray-100 pb-3">
                                <div class="h-8 w-8 rounded-full bg-green-100 flex items-center justify-center mr-3">
                                    <i class="fas fa-check text-green-600"></i>
                                </div>
                                <div class="flex-1">
                                    <p class="text-gray-800">Du har vandet <span class="font-medium">Calathea</span></p>
                                    <p class="text-gray-500 text-xs">i går kl. 17:30</p>
                                </div>
                            </div>

                            <div class="flex items-start border-b border-gray-100 pb-3">
                                <div class="h-8 w-8 rounded-full bg-yellow-100 flex items-center justify-center mr-3">
                                    <i class="fas fa-sun text-yellow-600"></i>
                                </div>
                                <div class="flex-1">
                                    <p class="text-gray-800"><span class="font-medium">Ficus</span> trænger til mere lys</p>
                                    <p class="text-gray-500 text-xs">i går kl. 12:15</p>
                                </div>
                            </div>

                            <div class="flex items-start">
                                <div class="h-8 w-8 rounded-full bg-blue-100 flex items-center justify-center mr-3">
                                    <i class="fas fa-thermometer-half text-blue-600"></i>
                                </div>
                                <div class="flex-1">
                                    <p class="text-gray-800">Temperaturen er faldet for <span class="font-medium">Sansevieria</span></p>
                                    <p class="text-gray-500 text-xs">i dag kl. 06:30</p>
                                </div>
                            </div>
                        </div>
                        <a href="#" class="mt-4 text-sm text-green-600 hover:text-green-800 font-medium flex items-center justify-end">
                            Se alle aktiviteter <i class="fas fa-arrow-right ml-1"></i>
                        </a>
                    </div>
                </div>

                <!-- Plants Section -->
                <div class="mb-6">
                    <div class="flex justify-between items-center mb-4">
                        <h3 class="font-bold text-gray-800">Mine Planter</h3>
                        <div class="flex space-x-2">
                            <button class="p-2 bg-gray-200 hover:bg-gray-300 rounded-lg text-gray-700">
                                <i class="fas fa-th-large"></i>
                            </button>
                            <button class="p-2 bg-gray-100 hover:bg-gray-200 rounded-lg text-gray-700">
                                <i class="fas fa-list"></i>
                            </button>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
                        <!-- Plant 1 -->
                        <div class="bg-white rounded-lg shadow-sm plant-card">
                            <div class="relative">
                                <img src="../assets/plants/plant-placeholder-1.png" alt="Monstera" class="w-full h-40 object-contain rounded-t-lg">
                                <span class="absolute top-2 right-2 bg-red-500 text-white text-xs px-2 py-1 rounded-full">
                                    Vand nu!
                                </span>
                            </div>
                            <div class="p-4">
                                <h4 class="font-medium text-gray-800">Monstera Deliciosa</h4>
                                <div class="grid grid-cols-2 gap-2 mt-3">
                                    <div class="flex items-center text-sm text-gray-600">
                                        <i class="fas fa-tint text-blue-500 mr-2"></i> 22%
                                    </div>
                                    <div class="flex items-center text-sm text-gray-600">
                                        <i class="fas fa-temperature-high text-red-500 mr-2"></i> 22°C
                                    </div>
                                    <div class="flex items-center text-sm text-gray-600">
                                        <i class="fas fa-sun text-yellow-500 mr-2"></i> God
                                    </div>
                                    <div class="flex items-center text-sm text-gray-600">
                                        <i class="fas fa-clock text-purple-500 mr-2"></i> 3 dage
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Plant 2 -->
                        <div class="bg-white rounded-lg shadow-sm plant-card">
                            <div class="relative">
                                <img src="../assets/plants/plant-placeholder-2.png" alt="Ficus" class="w-full h-40 object-contain rounded-t-lg">
                                <span class="absolute top-2 right-2 bg-yellow-500 text-white text-xs px-2 py-1 rounded-full">
                                    Lysbehov
                                </span>
                            </div>
                            <div class="p-4">
                                <h4 class="font-medium text-gray-800">Ficus Lyrata</h4>
                                <div class="grid grid-cols-2 gap-2 mt-3">
                                    <div class="flex items-center text-sm text-gray-600">
                                        <i class="fas fa-tint text-blue-500 mr-2"></i> 45%
                                    </div>
                                    <div class="flex items-center text-sm text-gray-600">
                                        <i class="fas fa-temperature-high text-red-500 mr-2"></i> 20°C
                                    </div>
                                    <div class="flex items-center text-sm text-gray-600">
                                        <i class="fas fa-sun text-yellow-500 mr-2"></i> Lav
                                    </div>
                                    <div class="flex items-center text-sm text-gray-600">
                                        <i class="fas fa-clock text-purple-500 mr-2"></i> 5 dage
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Plant 3 -->
                        <div class="bg-white rounded-lg shadow-sm plant-card">
                            <div class="relative">
                                <img src="../assets/plants/plant-placeholder-3.png" alt="Calathea" class="w-full h-40 object-contain rounded-t-lg"> <span class="absolute top-2 right-2 bg-green-500 text-white text-xs px-2 py-1 rounded-full">
                                    Sund
                                </span>
                            </div>
                            <div class="p-4">
                                <h4 class="font-medium text-gray-800">Calathea Orbifolia</h4>
                                <div class="grid grid-cols-2 gap-2 mt-3">
                                    <div class="flex items-center text-sm text-gray-600">
                                        <i class="fas fa-tint text-blue-500 mr-2"></i> 65%
                                    </div>
                                    <div class="flex items-center text-sm text-gray-600">
                                        <i class="fas fa-temperature-high text-red-500 mr-2"></i> 21°C
                                    </div>
                                    <div class="flex items-center text-sm text-gray-600">
                                        <i class="fas fa-sun text-yellow-500 mr-2"></i> God
                                    </div>
                                    <div class="flex items-center text-sm text-gray-600">
                                        <i class="fas fa-clock text-purple-500 mr-2"></i> 7 dage
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Plant 4 -->
                        <div class="bg-white rounded-lg shadow-sm plant-card">
                            <div class="relative">
                                <img src="../assets/plants/plant-placeholder-4.png" alt="Snake Plant" class="w-full h-40 object-contain rounded-t-lg">
                                <span class="absolute top-2 right-2 bg-green-500 text-white text-xs px-2 py-1 rounded-full">
                                    Sund
                                </span>
                            </div>
                            <div class="p-4">
                                <h4 class="font-medium text-gray-800">Sansevieria Trifasciata</h4>
                                <div class="grid grid-cols-2 gap-2 mt-3">
                                    <div class="flex items-center text-sm text-gray-600">
                                        <i class="fas fa-tint text-blue-500 mr-2"></i> 52%
                                    </div>
                                    <div class="flex items-center text-sm text-gray-600">
                                        <i class="fas fa-temperature-high text-red-500 mr-2"></i> 23°C
                                    </div>
                                    <div class="flex items-center text-sm text-gray-600">
                                        <i class="fas fa-sun text-yellow-500 mr-2"></i> God
                                    </div>
                                    <div class="flex items-center text-sm text-gray-600">
                                        <i class="fas fa-clock text-purple-500 mr-2"></i> 10 dage
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Weekly Summary Chart -->
                <div class="bg-white rounded-lg shadow-sm p-4">
                    <h3 class="font-bold text-gray-800 mb-4">Ugentlig Oversigt</h3>
                    <div class="h-64 p-4 bg-gray-50 rounded-lg flex items-center justify-center">
                        <div class="text-center text-gray-500">
                            <i class="fas fa-chart-line text-4xl mb-2"></i>
                            <p>Her ville grafen være</p>
                        </div>
                    </div>
                </div>
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
</php>