<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/smartplant/backend/auth/session_handler.php';
// Set current page for sidebar highlighting
$page = "statistics";

// Include header
include('../../components/header.php');
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

                <!-- Key Stats Cards -->
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
                    <div class="bg-white rounded-lg shadow-sm p-4 stats-card">
                        <div class="flex justify-between items-center">
                            <div>
                                <p class="text-gray-500 text-sm">Antal vandinger</p>
                                <h3 class="text-2xl font-bold text-gray-800">26</h3>
                                <p class="text-green-600 text-xs mt-1 flex items-center">
                                    <i class="fas fa-arrow-up mr-1"></i> 12% fra forrige periode
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
                                <h3 class="text-2xl font-bold text-gray-800">92%</h3>
                                <p class="text-green-600 text-xs mt-1 flex items-center">
                                    <i class="fas fa-arrow-up mr-1"></i> 5% fra forrige periode
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
                                <h3 class="text-2xl font-bold text-gray-800">85%</h3>
                                <p class="text-yellow-600 text-xs mt-1 flex items-center">
                                    <i class="fas fa-minus mr-1"></i> Uændret fra forrige periode
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
                                <h3 class="text-2xl font-bold text-gray-800">3</h3>
                                <p class="text-red-600 text-xs mt-1 flex items-center">
                                    <i class="fas fa-arrow-down mr-1"></i> 25% fra forrige periode
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
                                <tr class="border-b hover:bg-gray-50">
                                    <td class="py-3 px-4">
                                        <div class="flex items-center">
                                            <div class="h-10 w-10 rounded-full overflow-hidden bg-gray-100 flex-shrink-0 mr-3">
                                                <img src="../../assets/plants/plant-placeholder-1.png" alt="Monstera" class="h-full w-full object-cover">
                                            </div>
                                            <div>
                                                <p class="font-medium text-gray-800">Monstera Deliciosa</p>
                                                <p class="text-sm text-gray-500">Stue</p>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="py-3 px-4">
                                        <div class="w-full bg-gray-200 rounded-full h-2.5">
                                            <div class="bg-green-600 h-2.5 rounded-full" style="width: 45%"></div>
                                        </div>
                                        <span class="text-sm text-gray-600">45%</span>
                                    </td>
                                    <td class="py-3 px-4">
                                        <span class="px-2 py-1 bg-red-100 text-red-800 rounded-full text-xs">Højt</span>
                                    </td>
                                    <td class="py-3 px-4">
                                        <div class="flex items-center">
                                            <i class="fas fa-arrow-up text-green-600 mr-1"></i>
                                            <span>12 cm</span>
                                        </div>
                                    </td>
                                    <td class="py-3 px-4">
                                        <span class="px-2 py-1 bg-yellow-100 text-yellow-800 rounded-full text-xs">Behøver opmærksomhed</span>
                                    </td>
                                </tr>
                                <tr class="border-b hover:bg-gray-50">
                                    <td class="py-3 px-4">
                                        <div class="flex items-center">
                                            <div class="h-10 w-10 rounded-full overflow-hidden bg-gray-100 flex-shrink-0 mr-3">
                                                <img src="../../assets/plants/plant-placeholder-2.png" alt="Ficus" class="h-full w-full object-cover">
                                            </div>
                                            <div>
                                                <p class="font-medium text-gray-800">Ficus Lyrata</p>
                                                <p class="text-sm text-gray-500">Kontor</p>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="py-3 px-4">
                                        <div class="w-full bg-gray-200 rounded-full h-2.5">
                                            <div class="bg-green-600 h-2.5 rounded-full" style="width: 78%"></div>
                                        </div>
                                        <span class="text-sm text-gray-600">78%</span>
                                    </td>
                                    <td class="py-3 px-4">
                                        <span class="px-2 py-1 bg-yellow-100 text-yellow-800 rounded-full text-xs">Moderat</span>
                                    </td>
                                    <td class="py-3 px-4">
                                        <div class="flex items-center">
                                            <i class="fas fa-arrow-up text-green-600 mr-1"></i>
                                            <span>8 cm</span>
                                        </div>
                                    </td>
                                    <td class="py-3 px-4">
                                        <span class="px-2 py-1 bg-green-100 text-green-800 rounded-full text-xs">Sund</span>
                                    </td>
                                </tr>
                                <tr class="hover:bg-gray-50">
                                    <td class="py-3 px-4">
                                        <div class="flex items-center">
                                            <div class="h-10 w-10 rounded-full overflow-hidden bg-gray-100 flex-shrink-0 mr-3">
                                                <img src="../../assets/plants/plant-placeholder-3.png" alt="Calathea" class="h-full w-full object-cover">
                                            </div>
                                            <div>
                                                <p class="font-medium text-gray-800">Sansevieria Trifasciata</p>
                                                <p class="text-sm text-gray-500">Soveværelse</p>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="py-3 px-4">
                                        <div class="w-full bg-gray-200 rounded-full h-2.5">
                                            <div class="bg-green-600 h-2.5 rounded-full" style="width: 96%"></div>
                                        </div>
                                        <span class="text-sm text-gray-600">96%</span>
                                    </td>
                                    <td class="py-3 px-4">
                                        <span class="px-2 py-1 bg-green-100 text-green-800 rounded-full text-xs">Lav</span>
                                    </td>
                                    <td class="py-3 px-4">
                                        <div class="flex items-center">
                                            <i class="fas fa-arrow-up text-green-600 mr-1"></i>
                                            <span>5 cm</span>
                                        </div>
                                    </td>
                                    <td class="py-3 px-4">
                                        <span class="px-2 py-1 bg-green-100 text-green-800 rounded-full text-xs">Sund</span>
                                    </td>
                                </tr>
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
                                <span class="text-5xl font-bold text-gray-800">22°C</span>
                                <p class="text-green-600 text-sm mt-2 flex items-center justify-center">
                                    <i class="fas fa-arrow-up mr-1"></i> 1.5°C fra sidste måned
                                </p>
                                <p class="text-sm text-gray-600 mt-4">Optimal temperatur for de fleste af dine planter</p>
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
                                <span class="text-5xl font-bold text-gray-800">42%</span>
                                <p class="text-red-600 text-sm mt-2 flex items-center justify-center">
                                    <i class="fas fa-arrow-down mr-1"></i> 8% fra sidste måned
                                </p>
                                <p class="text-sm text-gray-600 mt-4">Lidt lavt for nogle af dine tropiske planter</p>
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
                                <span class="text-5xl font-bold text-gray-800">Middel</span>
                                <p class="text-yellow-600 text-sm mt-2 flex items-center justify-center">
                                    <i class="fas fa-minus mr-1"></i> Uændret fra sidste måned
                                </p>
                                <p class="text-sm text-gray-600 mt-4">Passende for de fleste af dine planter</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Tips Based on Stats -->
                <div class="bg-white rounded-lg shadow-sm p-5">
                    <h3 class="font-bold text-gray-800 mb-4">Anbefalinger baseret på data</h3>
                    <div class="space-y-4">
                        <div class="flex items-start p-4 bg-blue-50 rounded-lg">
                            <div class="h-10 w-10 rounded-full bg-blue-100 flex items-center justify-center mr-4 flex-shrink-0">
                                <i class="fas fa-tint text-blue-600"></i>
                            </div>
                            <div>
                                <p class="font-medium text-gray-800">Øg vandingsfrekvensen for Monstera</p>
                                <p class="text-sm text-gray-600 mt-1">Din Monstera viser tegn på udtørring. Prøv at vande den lidt oftere i de næste par uger.</p>
                            </div>
                        </div>

                        <div class="flex items-start p-4 bg-yellow-50 rounded-lg">
                            <div class="h-10 w-10 rounded-full bg-yellow-100 flex items-center justify-center mr-4 flex-shrink-0">
                                <i class="fas fa-sun text-yellow-600"></i>
                            </div>
                            <div>
                                <p class="font-medium text-gray-800">Flyt Ficus til et lysere sted</p>
                                <p class="text-sm text-gray-600 mt-1">Din Ficus vil trives bedre med mere lys, især i vintermånederne.</p>
                            </div>
                        </div>

                        <div class="flex items-start p-4 bg-green-50 rounded-lg">
                            <div class="h-10 w-10 rounded-full bg-green-100 flex items-center justify-center mr-4 flex-shrink-0">
                                <i class="fas fa-seedling text-green-600"></i>
                            </div>
                            <div>
                                <p class="font-medium text-gray-800">Din Sansevieria er i topform!</p>
                                <p class="text-sm text-gray-600 mt-1">Fortsæt med din nuværende pleje, den klarer sig fremragende.</p>
                            </div>
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

</html>