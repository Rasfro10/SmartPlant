<?php
include("./components/header.php");
?>

<body class="bg-gray-50">
    <!-- Navigation -->
    <nav class="bg-white shadow-md fixed w-full z-10">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-16">
                <div class="flex items-center">
                    <div class="flex-shrink-0 flex items-center">
                        <i class="fas fa-seedling text-secondary text-2xl"></i>
                        <span class="ml-2 text-xl font-semibold text-secondary">Smart Plant</span>
                    </div>
                </div>
                <div class="hidden md:flex items-center space-x-4">
                    <a href="#funktioner"
                        class="text-gray-600 hover:text-secondary px-3 py-2 rounded-md text-sm font-medium">Funktioner</a>
                    <a href="#fordele"
                        class="text-gray-600 hover:text-secondary px-3 py-2 rounded-md text-sm font-medium">Fordele</a>
                    <a href="#saadan"
                        class="text-gray-600 hover:text-secondary px-3 py-2 rounded-md text-sm font-medium">Sådan virker
                        det</a>
                    <a href="login/"
                        class="text-gray-600 hover:text-secondary border border-gray-300 px-4 py-2 rounded-md text-sm font-medium ml-4">Log
                        ind</a>
                    <a href="login/"
                        class="bg-primary hover:bg-secondary text-white px-4 py-2 rounded-md text-sm font-medium transition duration-300">Tilmeld</a>
                </div>
                <div class="flex md:hidden items-center">
                    <button id="mobile-menu-button" class="text-gray-600 hover:text-secondary">
                        <i class="fas fa-bars text-xl"></i>
                    </button>
                </div>
            </div>
        </div>
        <!-- Mobile menu -->
        <div id="mobile-menu" class="hidden md:hidden bg-white border-t">
            <div class="px-2 pt-2 pb-3 space-y-1 sm:px-3">
                <a href="#funktioner"
                    class="block text-gray-600 hover:text-secondary px-3 py-2 rounded-md text-base font-medium">Funktioner</a>
                <a href="#fordele"
                    class="block text-gray-600 hover:text-secondary px-3 py-2 rounded-md text-base font-medium">Fordele</a>
                <a href="#saadan"
                    class="block text-gray-600 hover:text-secondary px-3 py-2 rounded-md text-base font-medium">Sådan
                    virker det</a>
                <a href="login/"
                    class="block text-gray-600 hover:text-secondary border border-gray-300 px-3 py-2 rounded-md text-base font-medium mt-2">Log
                    ind</a>
                <a href="login/"
                    class="block bg-primary hover:bg-secondary text-white px-3 py-2 rounded-md text-base font-medium mt-2 transition duration-300">Tilmeld</a>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <section class="pt-24 pb-12 md:pt-32 md:pb-20 bg-gradient-to-br from-green-50 to-teal-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="lg:flex items-center">
                <div class="lg:w-1/2 lg:pr-12">
                    <h1 class="text-4xl md:text-5xl font-bold text-dark">Intelligente løsninger til dine planter</h1>
                    <p class="mt-4 text-lg text-gray-600">Få besked når dine planter trænger til vand og hold styr på
                        deres trivsel - altid og overalt.</p>
                    <div class="mt-8 flex flex-col sm:flex-row">
                        <a href="#saadan"
                            class="bg-primary hover:bg-secondary text-white font-medium py-3 px-6 rounded-lg shadow-md transition duration-300 text-center mb-4 sm:mb-0 sm:mr-4">Se
                            hvordan det virker</a>
                        <a href="#"
                            class="border border-primary text-primary hover:bg-primary hover:text-white font-medium py-3 px-6 rounded-lg transition duration-300 text-center">Kom
                            i gang</a>
                    </div>
                </div>
                <div class="lg:w-1/2 mt-10 lg:mt-0 flex justify-center">
                    <div class="text-secondary text-9xl">
                        <i class="fas fa-leaf"></i>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Features Section -->
    <section id="funktioner" class="py-12 md:py-20 bg-white">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-12">
                <h2 class="text-3xl md:text-4xl font-bold text-dark">Smarte funktioner</h2>
                <p class="mt-4 text-lg text-gray-600 max-w-3xl mx-auto">Smart Plant overvåger automatisk dine planters
                    miljø og giver dig besked, når de har brug for din opmærksomhed.</p>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                <div class="bg-gray-50 p-6 rounded-lg shadow-md text-center">
                    <div class="w-16 h-16 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-4">
                        <i class="fas fa-tint text-secondary text-2xl"></i>
                    </div>
                    <h3 class="text-xl font-semibold text-dark">Fugtighedsmåling</h3>
                    <p class="mt-2 text-gray-600">Holder øje med jordens fugtighed, så du ved præcis hvornår dine
                        planter trænger til vand.</p>
                </div>
                <div class="bg-gray-50 p-6 rounded-lg shadow-md text-center">
                    <div class="w-16 h-16 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-4">
                        <i class="fas fa-sun text-secondary text-2xl"></i>
                    </div>
                    <h3 class="text-xl font-semibold text-dark">Lysmåling</h3>
                    <p class="mt-2 text-gray-600">Overvåger mængden af lys, så du kan placere dine planter optimalt i
                        forhold til deres lysbehov.</p>
                </div>
                <div class="bg-gray-50 p-6 rounded-lg shadow-md text-center">
                    <div class="w-16 h-16 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-4">
                        <i class="fas fa-temperature-high text-secondary text-2xl"></i>
                    </div>
                    <h3 class="text-xl font-semibold text-dark">Temperaturmåling</h3>
                    <p class="mt-2 text-gray-600">Holder styr på temperaturen omkring dine planter, så de altid har
                        optimale vækstbetingelser.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Benefits Section -->
    <section id="fordele" class="py-12 md:py-20 bg-gray-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="lg:flex items-center">
                <div class="lg:w-1/2 lg:pr-12 order-2 lg:order-1 mt-10 lg:mt-0 flex justify-center">
                    <div class="text-secondary text-9xl">
                        <i class="fas fa-seedling"></i>
                    </div>
                </div>
                <div class="lg:w-1/2 order-1 lg:order-2">
                    <h2 class="text-3xl md:text-4xl font-bold text-dark">Fordele ved Smart Plant</h2>
                    <div class="mt-8 space-y-6">
                        <div class="flex">
                            <div class="flex-shrink-0">
                                <div class="w-10 h-10 bg-green-100 rounded-full flex items-center justify-center">
                                    <i class="fas fa-check text-secondary"></i>
                                </div>
                            </div>
                            <div class="ml-4">
                                <h3 class="text-xl font-semibold text-dark">Brugervenligt overblik</h3>
                                <p class="mt-1 text-gray-600">Se alle dine planters status samlet ét sted med vores
                                    intuitive dashboard.</p>
                            </div>
                        </div>
                        <div class="flex">
                            <div class="flex-shrink-0">
                                <div class="w-10 h-10 bg-green-100 rounded-full flex items-center justify-center">
                                    <i class="fas fa-bell text-secondary"></i>
                                </div>
                            </div>
                            <div class="ml-4">
                                <h3 class="text-xl font-semibold text-dark">Notifikationer</h3>
                                <p class="mt-1 text-gray-600">Få besked præcis når en plante trænger til vand - hverken
                                    for tidligt eller for sent.</p>
                            </div>
                        </div>
                        <div class="flex">
                            <div class="flex-shrink-0">
                                <div class="w-10 h-10 bg-green-100 rounded-full flex items-center justify-center">
                                    <i class="fas fa-mobile-alt text-secondary"></i>
                                </div>
                            </div>
                            <div class="ml-4">
                                <h3 class="text-xl font-semibold text-dark">Nem adgang overalt</h3>
                                <p class="mt-1 text-gray-600">Tjek dine planter fra både computer, tablet og smartphone
                                    - uanset hvor du er.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- How it works Section -->
    <section id="saadan" class="py-12 md:py-20 bg-white">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-12">
                <h2 class="text-3xl md:text-4xl font-bold text-dark">Sådan fungerer det</h2>
                <p class="mt-4 text-lg text-gray-600 max-w-3xl mx-auto">Fire enkle trin til intelligent plantepleje med
                    Smart Plant.</p>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-8">
                <div class="text-center">
                    <div class="w-16 h-16 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-4">
                        <span class="text-secondary font-bold text-xl">1</span>
                    </div>
                    <h3 class="text-xl font-semibold text-dark">Installation</h3>
                    <p class="mt-2 text-gray-600">Placér fugtigheds-, lys- og temperatursensorer i og omkring dine
                        planter.</p>
                </div>
                <div class="text-center">
                    <div class="w-16 h-16 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-4">
                        <span class="text-secondary font-bold text-xl">2</span>
                    </div>
                    <h3 class="text-xl font-semibold text-dark">Dataindsamling</h3>
                    <p class="mt-2 text-gray-600">Sensorerne sender information om planternes tilstand til vores system.
                    </p>
                </div>
                <div class="text-center">
                    <div class="w-16 h-16 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-4">
                        <span class="text-secondary font-bold text-xl">3</span>
                    </div>
                    <h3 class="text-xl font-semibold text-dark">Overvågning</h3>
                    <p class="mt-2 text-gray-600">Følg med i dine planters trivsel via vores brugervenlige dashboard.
                    </p>
                </div>
                <div class="text-center">
                    <div class="w-16 h-16 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-4">
                        <span class="text-secondary font-bold text-xl">4</span>
                    </div>
                    <h3 class="text-xl font-semibold text-dark">Notifikationer</h3>
                    <p class="mt-2 text-gray-600">Modtag besked når dine planter har brug for din opmærksomhed.</p>
                </div>
            </div>
            <div class="mt-16 text-center">
                <div class="text-secondary text-9xl">
                    <i class="fas fa-spa"></i>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="bg-secondary text-white py-6">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-center space-x-2 text-center">
                <p class="text-white">&copy; 2025 Smart Plant.</p>
                <i class="fas fa-seedling text-white/80"></i>
            </div>
        </div>
    </footer>

    <script>
        // Mobile menu toggle
        const mobileMenuButton = document.getElementById('mobile-menu-button');
        const mobileMenu = document.getElementById('mobile-menu');

        mobileMenuButton.addEventListener('click', () => {
            mobileMenu.classList.toggle('hidden');
        });

        // Close mobile menu when clicking on a link
        const mobileLinks = mobileMenu.querySelectorAll('a');
        mobileLinks.forEach(link => {
            link.addEventListener('click', () => {
                mobileMenu.classList.add('hidden');
            });
        });

        // Smooth scrolling for anchor links
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function(e) {
                e.preventDefault();

                const targetId = this.getAttribute('href');
                if (targetId === '#') return;

                const targetElement = document.querySelector(targetId);
                if (targetElement) {
                    window.scrollTo({
                        top: targetElement.offsetTop - 80,
                        behavior: 'smooth'
                    });
                }
            });
        });
    </script>
</body>