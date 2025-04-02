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

        .help-card {
            transition: all 0.3s ease;
        }

        .help-card:hover {
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
            border-color: #10b981;
        }

        .accordion-button {
            transition: all 0.3s ease;
        }

        .accordion-content {
            max-height: 0;
            overflow: hidden;
            transition: max-height 0.3s ease;
        }

        .accordion-content.open {
            max-height: 500px;
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
                        <h2 class="text-2xl font-bold text-gray-800">Hjælp & Support</h2>
                        <p class="text-gray-600">Find svar på dine spørgsmål og få hjælp til Smart Plant</p>
                    </div>
                    <div class="mt-3 md:mt-0">
                        <button class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg transition flex items-center">
                            <i class="fas fa-comment-alt mr-2"></i> Kontakt Support
                        </button>
                    </div>
                </div>

                <!-- Search Bar -->
                <div class="bg-white rounded-lg shadow-sm p-6 mb-6">
                    <h3 class="text-lg font-semibold text-gray-800 mb-4">Hvad kan vi hjælpe dig med?</h3>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <i class="fas fa-search text-gray-400"></i>
                        </div>
                        <input type="text" class="block w-full pl-10 pr-3 py-3 border border-gray-300 rounded-lg leading-5 bg-white placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-green-500 sm:text-sm" placeholder="Søg efter hjælp...">
                    </div>
                </div>

                <!-- Help Categories Grid -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
                    <!-- Category 1 -->
                    <div class="bg-white rounded-lg shadow-sm p-5 help-card border border-transparent">
                        <div class="flex items-center mb-3">
                            <div class="h-12 w-12 rounded-full bg-green-100 flex items-center justify-center mr-4">
                                <i class="fas fa-leaf text-green-600 text-xl"></i>
                            </div>
                            <h3 class="font-bold text-gray-800">Kom i gang</h3>
                        </div>
                        <p class="text-gray-600 mb-4">Grundlæggende information om hvordan du kommer i gang med Smart Plant.</p>
                        <a href="#" class="text-green-600 hover:text-green-800 text-sm font-medium flex items-center">
                            Se vejledninger <i class="fas fa-arrow-right ml-1"></i>
                        </a>
                    </div>

                    <!-- Category 2 -->
                    <div class="bg-white rounded-lg shadow-sm p-5 help-card border border-transparent">
                        <div class="flex items-center mb-3">
                            <div class="h-12 w-12 rounded-full bg-blue-100 flex items-center justify-center mr-4">
                                <i class="fas fa-tint text-blue-600 text-xl"></i>
                            </div>
                            <h3 class="font-bold text-gray-800">Plantepleje</h3>
                        </div>
                        <p class="text-gray-600 mb-4">Tips og råd om vanding, lys og gødning til dine planter.</p>
                        <a href="#" class="text-green-600 hover:text-green-800 text-sm font-medium flex items-center">
                            Se plejeguides <i class="fas fa-arrow-right ml-1"></i>
                        </a>
                    </div>

                    <!-- Category 3 -->
                    <div class="bg-white rounded-lg shadow-sm p-5 help-card border border-transparent">
                        <div class="flex items-center mb-3">
                            <div class="h-12 w-12 rounded-full bg-purple-100 flex items-center justify-center mr-4">
                                <i class="fas fa-mobile-alt text-purple-600 text-xl"></i>
                            </div>
                            <h3 class="font-bold text-gray-800">App Funktioner</h3>
                        </div>
                        <p class="text-gray-600 mb-4">Lær om alle funktionerne i Smart Plant appen.</p>
                        <a href="#" class="text-green-600 hover:text-green-800 text-sm font-medium flex items-center">
                            Udforsk funktioner <i class="fas fa-arrow-right ml-1"></i>
                        </a>
                    </div>
                </div>

                <!-- FAQ Section -->
                <div class="bg-white rounded-lg shadow-sm p-6 mb-6">
                    <h3 class="text-lg font-semibold text-gray-800 mb-6">Ofte stillede spørgsmål</h3>

                    <div class="space-y-4">
                        <!-- FAQ Item 1 -->
                        <div class="border border-gray-200 rounded-lg">
                            <button class="accordion-button w-full text-left p-4 focus:outline-none flex justify-between items-center">
                                <span class="font-medium text-gray-800">Hvordan tilføjer jeg en ny plante?</span>
                                <i class="fas fa-chevron-down text-gray-500 transition-transform"></i>
                            </button>
                            <div class="accordion-content px-4 pb-4">
                                <p class="text-gray-600">
                                    For at tilføje en ny plante, gå til "Mine Planter" sektionen og klik på "Tilføj Plante" knappen i øverste højre hjørne. Herfra kan du enten søge efter din plante i vores database eller tilføje den manuelt ved at indtaste plantens detaljer.
                                </p>
                                <div class="mt-3">
                                    <a href="#" class="text-green-600 hover:text-green-800 text-sm">Se videovejledning</a>
                                </div>
                            </div>
                        </div>

                        <!-- FAQ Item 2 -->
                        <div class="border border-gray-200 rounded-lg">
                            <button class="accordion-button w-full text-left p-4 focus:outline-none flex justify-between items-center">
                                <span class="font-medium text-gray-800">Hvordan indstiller jeg vandingspåmindelser?</span>
                                <i class="fas fa-chevron-down text-gray-500 transition-transform"></i>
                            </button>
                            <div class="accordion-content px-4 pb-4">
                                <p class="text-gray-600">
                                    Når du har tilføjet en plante, kan du indstille vandingspåmindelser ved at gå til plantens detaljer og klikke på "Rediger" knappen. Her finder du muligheden for at indstille vandingsintervaller og få notifikationer, når det er tid til at vande din plante.
                                </p>
                            </div>
                        </div>

                        <!-- FAQ Item 3 -->
                        <div class="border border-gray-200 rounded-lg">
                            <button class="accordion-button w-full text-left p-4 focus:outline-none flex justify-between items-center">
                                <span class="font-medium text-gray-800">Hvordan tilslutter jeg en smart sensor?</span>
                                <i class="fas fa-chevron-down text-gray-500 transition-transform"></i>
                            </button>
                            <div class="accordion-content px-4 pb-4">
                                <p class="text-gray-600">
                                    For at tilslutte en smart sensor, gå til "Indstillinger" og vælg "Tilsluttede Enheder". Klik på "Tilføj enhed" knappen og følg vejledningen for at parre din sensor med appen. Husk at sensoren skal være tændt og i parringstilstand.
                                </p>
                                <div class="mt-3">
                                    <a href="#" class="text-green-600 hover:text-green-800 text-sm">Se kompatible sensorer</a>
                                </div>
                            </div>
                        </div>

                        <!-- FAQ Item 4 -->
                        <div class="border border-gray-200 rounded-lg">
                            <button class="accordion-button w-full text-left p-4 focus:outline-none flex justify-between items-center">
                                <span class="font-medium text-gray-800">Hvordan eksporterer jeg mine plantedata?</span>
                                <i class="fas fa-chevron-down text-gray-500 transition-transform"></i>
                            </button>
                            <div class="accordion-content px-4 pb-4">
                                <p class="text-gray-600">
                                    For at eksportere dine plantedata, gå til "Indstillinger" og under "Data og Privatliv" finder du knappen "Download mine data". Dette vil generere en fil med alle dine plante- og plejeinformationer, som du kan gemme på din enhed.
                                </p>
                            </div>
                        </div>

                        <!-- FAQ Item 5 -->
                        <div class="border border-gray-200 rounded-lg">
                            <button class="accordion-button w-full text-left p-4 focus:outline-none flex justify-between items-center">
                                <span class="font-medium text-gray-800">Kan jeg dele min plantesamling med venner?</span>
                                <i class="fas fa-chevron-down text-gray-500 transition-transform"></i>
                            </button>
                            <div class="accordion-content px-4 pb-4">
                                <p class="text-gray-600">
                                    Ja, du kan dele din plantesamling med venner ved at gå til "Mine Planter" og klikke på "Del" knappen i øverste højre hjørne. Her kan du generere et link eller sende en invitation direkte til en ven via email eller besked.
                                </p>
                            </div>
                        </div>
                    </div>

                    <div class="mt-6 text-center">
                        <a href="#" class="text-green-600 hover:text-green-800 font-medium">
                            Se alle FAQ spørgsmål <i class="fas fa-arrow-right ml-1"></i>
                        </a>
                    </div>
                </div>

                <!-- Contact Support -->
                <div class="bg-green-50 rounded-lg p-6 mb-6">
                    <div class="flex flex-col md:flex-row items-center">
                        <div class="md:w-2/3 mb-4 md:mb-0 md:pr-6">
                            <h3 class="text-lg font-semibold text-gray-800 mb-2">Kunne du ikke finde svar?</h3>
                            <p class="text-gray-600">Vores support team er klar til at hjælpe dig. Send os en besked, og vi vender tilbage hurtigst muligt.</p>
                        </div>
                        <div class="md:w-1/3 flex justify-center md:justify-end">
                            <button class="bg-green-600 hover:bg-green-700 text-white px-6 py-3 rounded-lg transition flex items-center">
                                <i class="fas fa-envelope mr-2"></i> Send besked
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Popular Help Topics -->
                <div class="mb-6">
                    <h3 class="text-lg font-semibold text-gray-800 mb-4">Populære emner</h3>
                    <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-4">
                        <a href="#" class="bg-white rounded-lg p-4 flex items-center hover:bg-green-50 transition border border-gray-200">
                            <i class="fas fa-bug text-red-500 mr-3"></i>
                            <span class="text-gray-700">Skadedyrsbekæmpelse</span>
                        </a>
                        <a href="#" class="bg-white rounded-lg p-4 flex items-center hover:bg-green-50 transition border border-gray-200">
                            <i class="fas fa-sun text-yellow-500 mr-3"></i>
                            <span class="text-gray-700">Lysbehov guide</span>
                        </a>
                        <a href="#" class="bg-white rounded-lg p-4 flex items-center hover:bg-green-50 transition border border-gray-200">
                            <i class="fas fa-tint text-blue-500 mr-3"></i>
                            <span class="text-gray-700">Vandingsfrekvenser</span>
                        </a>
                        <a href="#" class="bg-white rounded-lg p-4 flex items-center hover:bg-green-50 transition border border-gray-200">
                            <i class="fas fa-seedling text-green-500 mr-3"></i>
                            <span class="text-gray-700">Omplantning</span>
                        </a>
                    </div>
                </div>

                <!-- Video Tutorials -->
                <div class="bg-white rounded-lg shadow-sm p-6 mb-6">
                    <div class="flex justify-between items-center mb-4">
                        <h3 class="text-lg font-semibold text-gray-800">Videovejledninger</h3>
                        <a href="#" class="text-green-600 hover:text-green-800 text-sm font-medium">
                            Se alle videoer <i class="fas fa-arrow-right ml-1"></i>
                        </a>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <!-- Video 1 -->
                        <div class="border border-gray-200 rounded-lg overflow-hidden">
                            <div class="relative bg-gray-200 h-40 flex items-center justify-center">
                                <img src="../assets/tutorials/tutorial-1.jpg" alt="Kom i gang tutorial" class="w-full h-full object-cover">
                                <div class="absolute inset-0 bg-black bg-opacity-30 flex items-center justify-center">
                                    <div class="h-12 w-12 rounded-full bg-white bg-opacity-80 flex items-center justify-center">
                                        <i class="fas fa-play text-green-600"></i>
                                    </div>
                                </div>
                            </div>
                            <div class="p-3">
                                <h4 class="font-medium text-gray-800">Kom i gang med Smart Plant</h4>
                                <p class="text-gray-500 text-sm">3:45 min</p>
                            </div>
                        </div>

                        <!-- Video 2 -->
                        <div class="border border-gray-200 rounded-lg overflow-hidden">
                            <div class="relative bg-gray-200 h-40 flex items-center justify-center">
                                <img src="../assets/tutorials/tutorial-2.jpg" alt="Tilføj planter tutorial" class="w-full h-full object-cover">
                                <div class="absolute inset-0 bg-black bg-opacity-30 flex items-center justify-center">
                                    <div class="h-12 w-12 rounded-full bg-white bg-opacity-80 flex items-center justify-center">
                                        <i class="fas fa-play text-green-600"></i>
                                    </div>
                                </div>
                            </div>
                            <div class="p-3">
                                <h4 class="font-medium text-gray-800">Tilføj og administrer planter</h4>
                                <p class="text-gray-500 text-sm">5:12 min</p>
                            </div>
                        </div>

                        <!-- Video 3 -->
                        <div class="border border-gray-200 rounded-lg overflow-hidden">
                            <div class="relative bg-gray-200 h-40 flex items-center justify-center">
                                <img src="../assets/tutorials/tutorial-3.jpg" alt="Sensorer tutorial" class="w-full h-full object-cover">
                                <div class="absolute inset-0 bg-black bg-opacity-30 flex items-center justify-center">
                                    <div class="h-12 w-12 rounded-full bg-white bg-opacity-80 flex items-center justify-center">
                                        <i class="fas fa-play text-green-600"></i>
                                    </div>
                                </div>
                            </div>
                            <div class="p-3">
                                <h4 class="font-medium text-gray-800">Tilslut sensorer til dine planter</h4>
                                <p class="text-gray-500 text-sm">4:30 min</p>
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
            const accordionButtons = document.querySelectorAll('.accordion-button');

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

            // Accordion functionality
            accordionButtons.forEach(button => {
                button.addEventListener('click', function() {
                    const content = this.nextElementSibling;
                    const icon = this.querySelector('i');

                    content.classList.toggle('open');
                    icon.classList.toggle('rotate-180');

                    // Close other accordion items
                    accordionButtons.forEach(otherButton => {
                        if (otherButton !== button) {
                            const otherContent = otherButton.nextElementSibling;
                            const otherIcon = otherButton.querySelector('i');

                            otherContent.classList.remove('open');
                            otherIcon.classList.remove('rotate-180');
                        }
                    });
                });
            });
        });
    </script>
</body>

</html>