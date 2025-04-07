<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/smartplant/backend/auth/session_handler.php';
// Set current page for sidebar highlighting
$page = "help";
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

        .step-card {
            transition: all 0.3s ease;
        }

        .step-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
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
                <div class="mb-6">
                    <h2 class="text-2xl font-bold text-gray-800">Kom i gang med Smart Plant</h2>
                    <p class="text-gray-600">Følg disse enkle trin for at installere din Arduino-sensor</p>
                </div>

                <!-- Simple Instructions Cards -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
                    <!-- Step 1 -->
                    <div class="bg-white rounded-lg p-6 shadow-sm step-card">
                        <div class="flex items-center mb-4">
                            <div class="h-12 w-12 rounded-full bg-green-100 flex items-center justify-center mr-4">
                                <span class="text-xl font-bold text-green-600">1</span>
                            </div>
                            <h3 class="font-bold text-gray-800">Tilslut strøm</h3>
                        </div>
                        <div class="flex flex-col items-center mb-4">
                            <div class="h-32 w-32 bg-gray-200 rounded-lg flex items-center justify-center mb-4">
                                <i class="fas fa-plug text-gray-500 text-5xl"></i>
                            </div>
                        </div>
                        <p class="text-gray-600">
                            Tilslut din Arduino Opla til en strømkilde ved hjælp af det medfølgende USB-kabel. Vent på at den lyser op.
                        </p>
                    </div>

                    <!-- Step 2 -->
                    <div class="bg-white rounded-lg p-6 shadow-sm step-card">
                        <div class="flex items-center mb-4">
                            <div class="h-12 w-12 rounded-full bg-green-100 flex items-center justify-center mr-4">
                                <span class="text-xl font-bold text-green-600">2</span>
                            </div>
                            <h3 class="font-bold text-gray-800">Installer sensoren</h3>
                        </div>
                        <div class="flex flex-col items-center mb-4">
                            <div class="h-32 w-32 bg-gray-200 rounded-lg flex items-center justify-center mb-4">
                                <i class="fas fa-leaf text-green-500 text-5xl"></i>
                            </div>
                        </div>
                        <p class="text-gray-600">
                            Placer fugtighedssensoren i plantens jord. Sørg for at metalstængerne er helt nede i jorden.
                        </p>
                    </div>

                    <!-- Step 3 -->
                    <div class="bg-white rounded-lg p-6 shadow-sm step-card">
                        <div class="flex items-center mb-4">
                            <div class="h-12 w-12 rounded-full bg-green-100 flex items-center justify-center mr-4">
                                <span class="text-xl font-bold text-green-600">3</span>
                            </div>
                            <h3 class="font-bold text-gray-800">Se data</h3>
                        </div>
                        <div class="flex flex-col items-center mb-4">
                            <div class="h-32 w-32 bg-gray-200 rounded-lg flex items-center justify-center mb-4">
                                <i class="fas fa-tachometer-alt text-blue-500 text-5xl"></i>
                            </div>
                        </div>
                        <p class="text-gray-600">
                            Gå til "Mine Planter" i Smart Plant appen for at se fugtighedsniveauet i din plante.
                        </p>
                    </div>
                </div>

                <!-- Tips Section -->
                <div class="bg-white rounded-lg p-6 shadow-sm mb-6">
                    <h3 class="text-lg font-semibold text-gray-800 mb-4">Tips til optimal brug</h3>
                    <ul class="space-y-2">
                        <li class="flex items-start">
                            <i class="fas fa-check-circle text-green-500 mt-1 mr-2"></i>
                            <span class="text-gray-600">Placer sensoren væk fra kanten af potten for de bedste målinger.</span>
                        </li>
                        <li class="flex items-start">
                            <i class="fas fa-check-circle text-green-500 mt-1 mr-2"></i>
                            <span class="text-gray-600">Sørg for at Arduino enheden ikke bliver våd når du vander planten.</span>
                        </li>
                        <li class="flex items-start">
                            <i class="fas fa-check-circle text-green-500 mt-1 mr-2"></i>
                            <span class="text-gray-600">Oplad enheden regelmæssigt for at sikre kontinuerlige målinger.</span>
                        </li>
                        <li class="flex items-start">
                            <i class="fas fa-check-circle text-green-500 mt-1 mr-2"></i>
                            <span class="text-gray-600">Hvis sensoren ikke viser data, prøv at genstarte Arduino enheden.</span>
                        </li>
                    </ul>
                </div>

                <!-- Understanding Data -->
                <div class="bg-white rounded-lg p-6 shadow-sm">
                    <h3 class="text-lg font-semibold text-gray-800 mb-4">Forstå målingerne</h3>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <!-- Dry -->
                        <div class="p-4 rounded-lg bg-red-50 border border-red-100">
                            <div class="text-center mb-2">
                                <span class="block font-bold text-red-700">0-30%</span>
                                <span class="text-gray-600">Tør jord</span>
                            </div>
                            <p class="text-sm text-gray-600 text-center">
                                Din plante har brug for vand snart.
                            </p>
                        </div>

                        <!-- Ideal -->
                        <div class="p-4 rounded-lg bg-green-50 border border-green-100">
                            <div class="text-center mb-2">
                                <span class="block font-bold text-green-700">31-70%</span>
                                <span class="text-gray-600">Ideel fugtighed</span>
                            </div>
                            <p class="text-sm text-gray-600 text-center">
                                Din plante har den rette mængde vand.
                            </p>
                        </div>

                        <!-- Wet -->
                        <div class="p-4 rounded-lg bg-blue-50 border border-blue-100">
                            <div class="text-center mb-2">
                                <span class="block font-bold text-blue-700">71-100%</span>
                                <span class="text-gray-600">Meget fugtigt</span>
                            </div>
                            <p class="text-sm text-gray-600 text-center">
                                Vent med at vande din plante.
                            </p>
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