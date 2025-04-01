<?php
session_start();
$base = "";
include_once("./Includes/head.php");
?>

<body class="bg-[#B7EFC5] p-6">
    <!-- Main Content -->
    <div class="flex-1 flex flex-col overflow-hidden">
        <!-- Header -->
        <header class="bg-[#208B3A] text-white p-4 flex items-center justify-between">
            <h1 class="text-xl font-semibold">Smart Plant Dashboard</h1>
            <div class="flex items-center space-x-4">
                <a href="">Log ind</a>
                <i class="fas fa-user-circle"></i>
            </div>
        </header>

        <!-- Dashboard Content -->
        <main class="flex justify-center items-center min-h-screen p-6 bg-[#B7EFC5]">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 max-w-4xl mx-auto">
                <!-- Plant Card 1 -->
                <div class="w-full bg-[#2DC653] rounded-lg shadow-lg overflow-hidden">
                    <div class="h-48 bg-cover bg-center" style="background-image: url('./assets/narcissus-carlton-1024x1024.jpg');"></div>
                    <div class="p-6 bg-[#2DC653]">
                        <h2 class="text-2xl font-bold text-white text-center mb-4">Narcissus pseudonarcissus</h2>
                        <div class="grid grid-cols-2 gap-4">
                            <div class="bg-[#92E6A7] p-4 rounded-lg shadow-md flex flex-col items-center">
                                    <h3 class="text-[#1e96fc] text-l font-bold">Moisture</h3>
                                <div class="flex items-center gap-2">
                                    <img src="./assets/icons/water.png" alt="Moisture Icon" class="w-6 h-6">
                                    <p class="text-2xl text-[#1e96fc] mt-2">5%</p>
                                </div>
                            </div>
                            <div class="bg-[#92E6A7] p-4 rounded-lg shadow-md flex flex-col items-center">
                                <h3 class="text-[#f75c03] text-l font-bold">Temperature</h3>
                                <div class="flex items-center gap-2">
                                    <img src="./assets/icons/thermometer.png" alt="Thermometer Icon" class="w-6 h-6">
                                    <p class="text-2xl text-[#f75c03] mt-2">30°C</p>
                                </div>
                            </div>
                            <div class="bg-[#92E6A7] p-4 rounded-lg shadow-md flex flex-col items-center">
                                <h3 class="text-[#6c757d] text-l font-bold">Humidity</h3>
                                <div class="flex items-center gap-2">
                                    <img src="./assets/icons/vaporize.png" alt="Humidity Icon" class="w-6 h-6">
                                    <p class="text-2xl text-[#6c757d] mt-2">5%</p>
                                </div>
                            </div>
                            <div class="bg-[#92E6A7] p-4 rounded-lg shadow-md flex flex-col items-center">
                                <h3 class="text-[#c925fd] text-l font-bold">UV Index</h3>
                                <div class="flex items-center gap-2">
                                    <img src="./assets/icons/uv.png" alt="UV Icon" class="w-6 h-6">
                                    <p class="text-2xl text-[#c925fd] mt-2">4</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- Plant Card 2 -->
                <div class="w-full bg-[#2DC653] rounded-lg shadow-lg overflow-hidden">
                    <div class="h-48 bg-cover bg-center" style="background-image: url('./assets/SHIRLEYTEMPLE.jpg');"></div>
                    <div class="p-6 bg-[#2DC653]">
                        <h2 class="text-2xl font-bold text-white text-center mb-4">Paeonia Shirley Temple</h2>
                        <div class="grid grid-cols-2 gap-4">
                            <div class="bg-[#92E6A7] p-4 rounded-lg shadow-md flex flex-col items-center">
                                    <h3 class="text-[#1e96fc] text-l font-bold">Moisture</h3>
                                <div class="flex items-center gap-2">
                                    <img src="./assets/icons/water.png" alt="Moisture Icon" class="w-6 h-6">
                                    <p class="text-2xl text-[#1e96fc] mt-2">5%</p>
                                </div>
                            </div>
                            <div class="bg-[#92E6A7] p-4 rounded-lg shadow-md flex flex-col items-center">
                                <h3 class="text-[#f75c03] text-l font-bold">Temperature</h3>
                                <div class="flex items-center gap-2">
                                    <img src="./assets/icons/thermometer.png" alt="Thermometer Icon" class="w-6 h-6">
                                    <p class="text-2xl text-[#f75c03] mt-2">30°C</p>
                                </div>
                            </div>
                            <div class="bg-[#92E6A7] p-4 rounded-lg shadow-md flex flex-col items-center">
                                <h3 class="text-[#6c757d] text-l font-bold">Humidity</h3>
                                <div class="flex items-center gap-2">
                                    <img src="./assets/icons/vaporize.png" alt="Humidity Icon" class="w-6 h-6">
                                    <p class="text-2xl text-[#6c757d] mt-2">5%</p>
                                </div>
                            </div>
                            <div class="bg-[#92E6A7] p-4 rounded-lg shadow-md flex flex-col items-center">
                                <h3 class="text-[#c925fd] text-l font-bold">UV Index</h3>
                                <div class="flex items-center gap-2">
                                    <img src="./assets/icons/uv.png" alt="UV Icon" class="w-6 h-6">
                                    <p class="text-2xl text-[#c925fd] mt-2">4</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- Plant Card 3 -->
                <div class="w-full bg-[#2DC653] rounded-lg shadow-lg overflow-hidden">
                    <div class="h-48 bg-cover bg-center" style="background-image: url('./assets/Hyoscyamus-niger-L.jpg');"></div>
                    <div class="p-6 bg-[#2DC653]">
                        <h2 class="text-2xl font-bold text-white text-center mb-4">Hyoscyamus niger L</h2>
                        <div class="grid grid-cols-2 gap-4">
                            <div class="bg-[#92E6A7] p-4 rounded-lg shadow-md flex flex-col items-center">
                                    <h3 class="text-[#1e96fc] text-l font-bold">Moisture</h3>
                                <div class="flex items-center gap-2">
                                    <img src="./assets/icons/water.png" alt="Moisture Icon" class="w-6 h-6">
                                    <p class="text-2xl text-[#1e96fc] mt-2">5%</p>
                                </div>
                            </div>
                            <div class="bg-[#92E6A7] p-4 rounded-lg shadow-md flex flex-col items-center">
                                <h3 class="text-[#f75c03] text-l font-bold">Temperature</h3>
                                <div class="flex items-center gap-2">
                                    <img src="./assets/icons/thermometer.png" alt="Thermometer Icon" class="w-6 h-6">
                                    <p class="text-2xl text-[#f75c03] mt-2">30°C</p>
                                </div>
                            </div>
                            <div class="bg-[#92E6A7] p-4 rounded-lg shadow-md flex flex-col items-center">
                                <h3 class="text-[#6c757d] text-l font-bold">Humidity</h3>
                                <div class="flex items-center gap-2">
                                    <img src="./assets/icons/vaporize.png" alt="Humidity Icon" class="w-6 h-6">
                                    <p class="text-2xl text-[#6c757d] mt-2">5%</p>
                                </div>
                            </div>
                            <div class="bg-[#92E6A7] p-4 rounded-lg shadow-md flex flex-col items-center">
                                <h3 class="text-[#c925fd] text-l font-bold">UV Index</h3>
                                <div class="flex items-center gap-2">
                                    <img src="./assets/icons/uv.png" alt="UV Icon" class="w-6 h-6">
                                    <p class="text-2xl text-[#c925fd] mt-2">4</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- Plant Card 4 -->
                <div class="w-full bg-[#2DC653] rounded-lg shadow-lg overflow-hidden">
                    <div class="h-48 bg-cover bg-center" style="background-image: url('./assets/Otto\'s-Thrill.jpg');"></div>
                    <div class="p-6 bg-[#2DC653]">
                        <h2 class="text-2xl font-bold text-white text-center mb-4">Otto's Thrill'</h2>
                        <div class="grid grid-cols-2 gap-4">
                            <div class="bg-[#92E6A7] p-4 rounded-lg shadow-md flex flex-col items-center">
                                    <h3 class="text-[#1e96fc] text-l font-bold">Moisture</h3>
                                <div class="flex items-center gap-2">
                                    <img src="./assets/icons/water.png" alt="Moisture Icon" class="w-6 h-6">
                                    <p class="text-2xl text-[#1e96fc] mt-2">5%</p>
                                </div>
                            </div>
                            <div class="bg-[#92E6A7] p-4 rounded-lg shadow-md flex flex-col items-center">
                                <h3 class="text-[#f75c03] text-l font-bold">Temperature</h3>
                                <div class="flex items-center gap-2">
                                    <img src="./assets/icons/thermometer.png" alt="Thermometer Icon" class="w-6 h-6">
                                    <p class="text-2xl text-[#f75c03] mt-2">30°C</p>
                                </div>
                            </div>
                            <div class="bg-[#92E6A7] p-4 rounded-lg shadow-md flex flex-col items-center">
                                <h3 class="text-[#6c757d] text-l font-bold">Humidity</h3>
                                <div class="flex items-center gap-2">
                                    <img src="./assets/icons/vaporize.png" alt="Humidity Icon" class="w-6 h-6">
                                    <p class="text-2xl text-[#6c757d] mt-2">5%</p>
                                </div>
                            </div>
                            <div class="bg-[#92E6A7] p-4 rounded-lg shadow-md flex flex-col items-center">
                                <h3 class="text-[#c925fd] text-l font-bold">UV Index</h3>
                                <div class="flex items-center gap-2">
                                    <img src="./assets/icons/uv.png" alt="UV Icon" class="w-6 h-6">
                                    <p class="text-2xl text-[#c925fd] mt-2">4</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
</body>