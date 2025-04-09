<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/smartplant/backend/auth/session_handler.php';
// Set current page for sidebar highlighting
$page = "settings";

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

        .settings-card {
            transition: all 0.3s ease;
        }

        .settings-card:hover {
            border-color: #10b981;
        }

        .toggle-checkbox:checked {
            right: 0;
            border-color: #10b981;
        }

        .toggle-checkbox:checked+.toggle-label {
            background-color: #10b981;
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
                        <h2 class="text-2xl font-bold text-gray-800">Indstillinger</h2>
                        <p class="text-gray-600">Tilpas din Smart Plant oplevelse</p>
                    </div>
                    <button class="mt-3 md:mt-0 bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg transition flex items-center">
                        <i class="fas fa-save mr-2"></i> Gem ændringer
                    </button>
                </div>

                <!-- User Profile Card -->
                <div class="bg-white rounded-lg shadow-sm p-5 mb-6">
                    <div class="flex flex-col md:flex-row">
                        <div class="md:w-1/4 flex flex-col items-center mb-6 md:mb-0 md:border-r md:border-gray-200 md:pr-6">
                            <div class="h-24 w-24 rounded-full bg-green-600 flex items-center justify-center text-white text-2xl font-bold mb-3">
                                <?php echo htmlspecialchars($initials); ?>
                            </div>
                            <h4 class="font-bold text-gray-800 text-lg"><?php echo htmlspecialchars($firstname . ' ' . $lastname); ?></h4>
                            <p class="text-gray-600"><?php echo htmlspecialchars($email); ?></p>
                            <p class="text-sm text-gray-500 mt-1">Medlem siden januar 2023</p>

                            <button class="mt-4 text-green-600 hover:text-green-800 font-medium flex items-center">
                                <i class="fas fa-pen mr-1"></i> Rediger profil
                            </button>
                        </div>

                        <div class="md:w-3/4 md:pl-6">
                            <div class="flex items-center justify-between mb-4">
                                <h3 class="font-bold text-gray-800">Din Profil</h3>
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                                <div>
                                    <p class="text-sm text-gray-500">Medlemstype</p>
                                    <p class="font-medium">Standard</p>
                                </div>
                                <div>
                                    <p class="text-sm text-gray-500">Antal planter</p>
                                    <p class="font-medium">8</p>
                                </div>
                                <div>
                                    <p class="text-sm text-gray-500">Registreret siden</p>
                                    <p class="font-medium">25. januar 2023</p>
                                </div>
                                <div>
                                    <p class="text-sm text-gray-500">Status</p>
                                    <p class="font-medium text-green-600">Aktiv</p>
                                </div>
                            </div>

                            <div class="mt-4">
                                <p class="font-medium text-gray-800 mb-2">Plant Expert Niveau</p>
                                <div class="w-full bg-gray-200 rounded-full h-2.5 mb-2">
                                    <div class="bg-green-600 h-2.5 rounded-full" style="width: 25%"></div>
                                </div>
                                <div class="flex justify-between text-xs text-gray-500">
                                    <span>Begynder</span>
                                    <span>Avanceret</span>
                                    <span>Ekspert</span>
                                </div>
                                <p class="text-sm text-gray-500 mt-2">Tilføj flere planter og registrer pleje for at stige i niveau</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Settings Content -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                    <!-- Notification Settings -->
                    <div class="bg-white rounded-lg shadow-sm p-5">
                        <h3 class="font-bold text-gray-800 mb-4">Notifikationer</h3>

                        <div class="space-y-4">
                            <div class="flex items-center justify-between">
                                <div>
                                    <p class="font-medium text-gray-800">Vandingspåmindelser</p>
                                    <p class="text-sm text-gray-600">Få besked når dine planter skal vandes</p>
                                </div>
                                <div class="relative inline-block w-10 mr-2 align-middle select-none">
                                    <input type="checkbox" id="toggle-watering" class="toggle-checkbox absolute block w-6 h-6 rounded-full bg-white border-4 border-gray-300 appearance-none cursor-pointer" checked>
                                    <label for="toggle-watering" class="toggle-label block overflow-hidden h-6 w-10 rounded-full bg-gray-300 cursor-pointer"></label>
                                </div>
                            </div>

                            <div class="flex items-center justify-between">
                                <div>
                                    <p class="font-medium text-gray-800">Lyspåmindelser</p>
                                    <p class="text-sm text-gray-600">Få besked om planters lysbehov</p>
                                </div>
                                <div class="relative inline-block w-10 mr-2 align-middle select-none">
                                    <input type="checkbox" id="toggle-light" class="toggle-checkbox absolute block w-6 h-6 rounded-full bg-white border-4 border-gray-300 appearance-none cursor-pointer" checked>
                                    <label for="toggle-light" class="toggle-label block overflow-hidden h-6 w-10 rounded-full bg-gray-300 cursor-pointer"></label>
                                </div>
                            </div>

                            <div class="flex items-center justify-between">
                                <div>
                                    <p class="font-medium text-gray-800">Temperaturadvarsler</p>
                                    <p class="text-sm text-gray-600">Få besked ved kritiske temperaturændringer</p>
                                </div>
                                <div class="relative inline-block w-10 mr-2 align-middle select-none">
                                    <input type="checkbox" id="toggle-temp" class="toggle-checkbox absolute block w-6 h-6 rounded-full bg-white border-4 border-gray-300 appearance-none cursor-pointer">
                                    <label for="toggle-temp" class="toggle-label block overflow-hidden h-6 w-10 rounded-full bg-gray-300 cursor-pointer"></label>
                                </div>
                            </div>

                            <div class="flex items-center justify-between">
                                <div>
                                    <p class="font-medium text-gray-800">Ugenlige rapporter</p>
                                    <p class="text-sm text-gray-600">Modtag en ugentlig oversigt over dine planters velvære</p>
                                </div>
                                <div class="relative inline-block w-10 mr-2 align-middle select-none">
                                    <input type="checkbox" id="toggle-weekly" class="toggle-checkbox absolute block w-6 h-6 rounded-full bg-white border-4 border-gray-300 appearance-none cursor-pointer" checked>
                                    <label for="toggle-weekly" class="toggle-label block overflow-hidden h-6 w-10 rounded-full bg-gray-300 cursor-pointer"></label>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- App Preferences -->
                    <div class="bg-white rounded-lg shadow-sm p-5">
                        <h3 class="font-bold text-gray-800 mb-4">App Præferencer</h3>

                        <div class="space-y-6">
                            <div>
                                <p class="font-medium text-gray-800 mb-2">Sprog</p>
                                <select class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-green-500">
                                    <option selected>Dansk</option>
                                    <option>English</option>
                                    <option>Deutsch</option>
                                    <option>Svenska</option>
                                </select>
                            </div>

                            <div>
                                <p class="font-medium text-gray-800 mb-2">Temperaturenhed</p>
                                <div class="flex space-x-4">
                                    <label class="flex items-center">
                                        <input type="radio" name="temp-unit" class="h-4 w-4 text-green-600 focus:ring-green-500 border-gray-300" checked>
                                        <span class="ml-2 text-gray-700">Celsius (°C)</span>
                                    </label>
                                    <label class="flex items-center">
                                        <input type="radio" name="temp-unit" class="h-4 w-4 text-green-600 focus:ring-green-500 border-gray-300">
                                        <span class="ml-2 text-gray-700">Fahrenheit (°F)</span>
                                    </label>
                                </div>
                            </div>

                            <div>
                                <p class="font-medium text-gray-800 mb-2">Måleenheder</p>
                                <div class="flex space-x-4">
                                    <label class="flex items-center">
                                        <input type="radio" name="measurement-unit" class="h-4 w-4 text-green-600 focus:ring-green-500 border-gray-300" checked>
                                        <span class="ml-2 text-gray-700">Metrisk (cm)</span>
                                    </label>
                                    <label class="flex items-center">
                                        <input type="radio" name="measurement-unit" class="h-4 w-4 text-green-600 focus:ring-green-500 border-gray-300">
                                        <span class="ml-2 text-gray-700">Imperial (in)</span>
                                    </label>
                                </div>
                            </div>

                            <div>
                                <p class="font-medium text-gray-800 mb-2">Tema</p>
                                <div class="grid grid-cols-3 gap-3">
                                    <div class="border rounded-lg p-3 bg-white text-center cursor-pointer border-green-500">
                                        <div class="h-6 w-full bg-white border border-gray-200 rounded mb-2"></div>
                                        <span class="text-sm text-gray-700">Lys</span>
                                    </div>
                                    <div class="border rounded-lg p-3 bg-white text-center cursor-pointer">
                                        <div class="h-6 w-full bg-gray-900 rounded mb-2"></div>
                                        <span class="text-sm text-gray-700">Mørk</span>
                                    </div>
                                    <div class="border rounded-lg p-3 bg-white text-center cursor-pointer">
                                        <div class="h-6 w-full bg-gradient-to-r from-green-400 to-blue-500 rounded mb-2"></div>
                                        <span class="text-sm text-gray-700">Natur</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Bottom Row -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                    <!-- Data and Privacy -->
                    <div class="bg-white rounded-lg shadow-sm p-5">
                        <h3 class="font-bold text-gray-800 mb-4">Data og Privatliv</h3>

                        <div class="space-y-4">
                            <div class="flex items-center justify-between">
                                <div>
                                    <p class="font-medium text-gray-800">Del anonyme brugsdata</p>
                                    <p class="text-sm text-gray-600">Hjælp os med at forbedre appen ved at dele anonyme brugsstatistikker</p>
                                </div>
                                <div class="relative inline-block w-10 mr-2 align-middle select-none">
                                    <input type="checkbox" id="toggle-data" class="toggle-checkbox absolute block w-6 h-6 rounded-full bg-white border-4 border-gray-300 appearance-none cursor-pointer" checked>
                                    <label for="toggle-data" class="toggle-label block overflow-hidden h-6 w-10 rounded-full bg-gray-300 cursor-pointer"></label>
                                </div>
                            </div>

                            <div class="flex items-center justify-between">
                                <div>
                                    <p class="font-medium text-gray-800">Del plantedata</p>
                                    <p class="text-sm text-gray-600">Del dine plantedata med forskningsmiljøer for at forbedre plantepleje globalt</p>
                                </div>
                                <div class="relative inline-block w-10 mr-2 align-middle select-none">
                                    <input type="checkbox" id="toggle-research" class="toggle-checkbox absolute block w-6 h-6 rounded-full bg-white border-4 border-gray-300 appearance-none cursor-pointer">
                                    <label for="toggle-research" class="toggle-label block overflow-hidden h-6 w-10 rounded-full bg-gray-300 cursor-pointer"></label>
                                </div>
                            </div>

                            <div class="mt-6 space-y-3">
                                <button class="text-blue-600 hover:text-blue-800 text-sm font-medium flex items-center">
                                    <i class="fas fa-download mr-1"></i> Download mine data
                                </button>
                                <button class="text-red-600 hover:text-red-800 text-sm font-medium flex items-center">
                                    <i class="fas fa-trash-alt mr-1"></i> Slet min konto
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Connected Devices -->
                    <div class="bg-white rounded-lg shadow-sm p-5">
                        <div class="flex justify-between items-center mb-4">
                            <h3 class="font-bold text-gray-800">Tilsluttede Enheder</h3>
                            <button class="text-green-600 hover:text-green-800 text-sm font-medium flex items-center">
                                <i class="fas fa-plus mr-1"></i> Tilføj enhed
                            </button>
                        </div>

                        <div class="space-y-4">
                            <div class="flex items-start p-3 border border-gray-200 rounded-lg hover:border-green-500 transition-colors">
                                <div class="h-10 w-10 rounded-full bg-blue-100 flex items-center justify-center mr-4 flex-shrink-0">
                                    <i class="fas fa-tint text-blue-600"></i>
                                </div>
                                <div class="flex-1">
                                    <div class="flex justify-between">
                                        <p class="font-medium text-gray-800">Smart Vandingssensor</p>
                                        <span class="bg-green-100 text-green-800 text-xs px-2 py-1 rounded-full">Aktiv</span>
                                    </div>
                                    <p class="text-sm text-gray-600 mt-1">Batteriniveau: 72% • Sidst synkroniseret: 2 timer siden</p>
                                </div>
                            </div>

                            <div class="flex items-start p-3 border border-gray-200 rounded-lg hover:border-green-500 transition-colors">
                                <div class="h-10 w-10 rounded-full bg-yellow-100 flex items-center justify-center mr-4 flex-shrink-0">
                                    <i class="fas fa-sun text-yellow-600"></i>
                                </div>
                                <div class="flex-1">
                                    <div class="flex justify-between">
                                        <p class="font-medium text-gray-800">Smart Lyssensor</p>
                                        <span class="bg-red-100 text-red-800 text-xs px-2 py-1 rounded-full">Offline</span>
                                    </div>
                                    <p class="text-sm text-gray-600 mt-1">Batteriniveau: 15% • Sidst synkroniseret: 3 dage siden</p>
                                </div>
                            </div>

                            <div class="border border-dashed border-gray-300 rounded-lg p-4 text-center text-gray-500 hover:bg-gray-50 cursor-pointer">
                                <i class="fas fa-plus text-green-600 text-xl mb-2"></i>
                                <p>Tilføj en ny sensor eller enhed</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Account Security -->
                <div class="bg-white rounded-lg shadow-sm p-5 mb-6">
                    <h3 class="font-bold text-gray-800 mb-4">Kontosikkerhed</h3>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <div class="mb-4">
                                <p class="font-medium text-gray-800 mb-1">Adgangskode</p>
                                <p class="text-sm text-gray-600">Sidst ændret for 2 måneder siden</p>
                            </div>
                            <button class="text-blue-600 hover:text-blue-800 text-sm font-medium flex items-center">
                                <i class="fas fa-key mr-1"></i> Skift adgangskode
                            </button>
                        </div>

                        <div>
                            <div class="mb-4">
                                <p class="font-medium text-gray-800 mb-1">To-faktor autentificering</p>
                                <p class="text-sm text-gray-600">Øg sikkerheden ved at aktivere to-faktor autentificering</p>
                            </div>
                            <button class="text-blue-600 hover:text-blue-800 text-sm font-medium flex items-center">
                                <i class="fas fa-shield-alt mr-1"></i> Aktivér to-faktor
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Save Button Mobile -->
                <div class="md:hidden flex justify-center mt-6 mb-4">
                    <button class="bg-green-600 hover:bg-green-700 text-white px-8 py-3 rounded-lg transition flex items-center w-full justify-center">
                        <i class="fas fa-save mr-2"></i> Gem alle ændringer
                    </button>
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