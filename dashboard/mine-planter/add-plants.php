<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/smartplant/backend/auth/session_handler.php';

// Check if user is logged in
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("location: ../../login/index.php");
    exit;
}

// Set current page for sidebar highlighting
$page = "myPlants";

// Include header
include('../../components/header.php');

// Include database connection
include('../../db/db_conn.php');

// Initialize variables for success message
$success = false;
$message = '';

// Handle form submission when the form is submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Get user_id from session
    $user_id = $_SESSION['id']; // Using 'id' as in your login.php

    // Get data from form
    $name = $_POST['plant-name'];
    $location = $_POST['plant-location'];
    $plant_type = $_POST['plant-type']; // Plant type dropdown
    $notes = $_POST['plant-notes']; // Get notes from form
    $watering_frequency = $_POST['watering-frequency']; // Get watering frequency

    // Get light needs from radio buttons
    $light_needs = "Medium"; // Default value
    if (isset($_POST['light-needs'])) {
        $light_needs = $_POST['light-needs'];
    }

    // Initialize image path variable
    $image_path = "assets/plants/default.png"; // Default placeholder image

    // Handle image upload if provided
    if (isset($_FILES['plant-image']) && $_FILES['plant-image']['error'] == 0) {
        $target_dir = "../../assets/plants/";

        // Generate unique filename
        $filename = uniqid() . "_" . basename($_FILES["plant-image"]["name"]);
        $target_file = $target_dir . $filename;

        // Check if file is an actual image
        $check = getimagesize($_FILES["plant-image"]["tmp_name"]);
        if ($check !== false) {
            // Try to upload file
            if (move_uploaded_file($_FILES["plant-image"]["tmp_name"], $target_file)) {
                $image_path = "assets/plants/" . $filename;
            }
        }
    }

    // Prepare SQL statement including all fields
    $sql = "INSERT INTO plants (user_id, name, location, plant_type, notes, watering_frequency, light_needs, image_path) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";

    // Prepare and bind parameters
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("isssssss", $user_id, $name, $location, $plant_type, $notes, $watering_frequency, $light_needs, $image_path);

    // Execute statement
    if ($stmt->execute()) {
        $success = true;
        $message = "Din plante \"$name\" er nu tilføjet til din samling.";

        // If successful, move to step 3
        echo "<script>
            document.addEventListener('DOMContentLoaded', function() {
                document.getElementById('step-1').style.display = 'none';
                document.getElementById('step-2').style.display = 'none';
                document.getElementById('step-3').style.display = 'block';
                
                // Update progress indicators
                document.querySelectorAll('.flex.flex-col.items-center')[1].querySelector('div').classList.remove('bg-green-600');
                document.querySelectorAll('.flex.flex-col.items-center')[1].querySelector('div').classList.add('bg-gray-200');
                document.querySelectorAll('.flex.flex-col.items-center')[1].querySelector('span').classList.add('text-gray-500');
                
                document.querySelectorAll('.flex.flex-col.items-center')[2].querySelector('div').classList.remove('bg-gray-200');
                document.querySelectorAll('.flex.flex-col.items-center')[2].querySelector('div').classList.add('bg-green-600');
                document.querySelectorAll('.flex.flex-col.items-center')[2].querySelector('div').classList.add('text-white');
                document.querySelectorAll('.flex.flex-col.items-center')[2].querySelector('span').classList.remove('text-gray-500');
            });
        </script>";
    } else {
        $message = "Der opstod en fejl: " . $stmt->error;
    }

    $stmt->close();
}
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

        .plant-item {
            transition: all 0.3s ease;
        }

        .plant-item:hover {
            border-color: #10b981;
            cursor: pointer;
        }

        .plant-item.selected {
            border-color: #10b981;
            background-color: #ecfdf5;
        }

        .image-upload {
            transition: all 0.3s ease;
        }

        .image-upload:hover {
            border-color: #10b981;
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
                <!-- Page Header with Steps -->
                <div class="mb-6">
                    <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-4">
                        <div>
                            <h2 class="text-2xl font-bold text-gray-800">Tilføj ny plante</h2>
                            <p class="text-gray-600">Tilføj en ny plante til din samling</p>
                        </div>

                        <button class="mt-3 md:mt-0 text-gray-600 hover:text-gray-800" onclick="window.history.back()">
                            <i class="fas fa-times mr-1"></i> Annuller
                        </button>
                    </div>

                    <!-- Progress Steps -->
                    <div class="flex justify-between items-center relative mt-8">
                        <div class="absolute left-0 right-0 top-1/2 transform -translate-y-1/2 h-1 bg-gray-200 z-0"></div>

                        <div class="flex flex-col items-center relative z-10 w-1/3">
                            <div class="h-8 w-8 rounded-full bg-green-600 flex items-center justify-center text-white">
                                1
                            </div>
                            <span class="text-sm font-medium mt-2">Vælg plante</span>
                        </div>

                        <div class="flex flex-col items-center relative z-10 w-1/3">
                            <div class="h-8 w-8 rounded-full bg-gray-200 flex items-center justify-center text-gray-500">
                                2
                            </div>
                            <span class="text-sm font-medium mt-2 text-gray-500">Tilpas detaljer</span>
                        </div>

                        <div class="flex flex-col items-center relative z-10 w-1/3">
                            <div class="h-8 w-8 rounded-full bg-gray-200 flex items-center justify-center text-gray-500">
                                3
                            </div>
                            <span class="text-sm font-medium mt-2 text-gray-500">Gem plante</span>
                        </div>
                    </div>
                </div>

                <!-- Step 1: Choose Plant -->
                <div id="step-1" class="mb-6">
                    <!-- Search Box -->
                    <div class="bg-white rounded-lg shadow-sm p-5 mb-6">
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <i class="fas fa-search text-gray-400"></i>
                            </div>
                            <input type="text" class="block w-full pl-10 pr-3 py-3 border border-gray-300 rounded-lg leading-5 bg-white placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-green-500 sm:text-sm" placeholder="Søg efter plantetyper...">
                        </div>

                        <div class="mt-4 flex flex-wrap gap-2">
                            <span class="inline-block bg-gray-100 hover:bg-gray-200 rounded-full px-3 py-1 text-sm font-medium text-gray-700 cursor-pointer">Alle</span>
                            <span class="inline-block bg-gray-100 hover:bg-gray-200 rounded-full px-3 py-1 text-sm font-medium text-gray-700 cursor-pointer">Indendørs</span>
                            <span class="inline-block bg-gray-100 hover:bg-gray-200 rounded-full px-3 py-1 text-sm font-medium text-gray-700 cursor-pointer">Udendørs</span>
                            <span class="inline-block bg-gray-100 hover:bg-gray-200 rounded-full px-3 py-1 text-sm font-medium text-gray-700 cursor-pointer">Let at pleje</span>
                            <span class="inline-block bg-gray-100 hover:bg-gray-200 rounded-full px-3 py-1 text-sm font-medium text-gray-700 cursor-pointer">Sukkulenter</span>
                            <span class="inline-block bg-gray-100 hover:bg-gray-200 rounded-full px-3 py-1 text-sm font-medium text-gray-700 cursor-pointer">Grønne planter</span>
                            <span class="inline-block bg-gray-100 hover:bg-gray-200 rounded-full px-3 py-1 text-sm font-medium text-gray-700 cursor-pointer">Blomstrende</span>
                        </div>
                    </div>

                    <!-- Popular Plants -->
                    <div class="mb-6">
                        <h3 class="text-lg font-semibold text-gray-800 mb-4">Populære planter</h3>
                        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-4">
                            <!-- Plant 1 -->
                            <div class="plant-item border border-gray-200 rounded-lg overflow-hidden">
                                <div class="h-36 bg-gray-100">
                                    <img src="../../assets/plants/plant-placeholder-1.png" alt="Monstera" class="w-full h-full object-contain">
                                </div>
                                <div class="p-3">
                                    <h4 class="font-medium text-gray-800">Monstera Deliciosa</h4>
                                    <p class="text-gray-500 text-sm">Indendørs • Let</p>
                                </div>
                            </div>

                            <!-- Plant 2 -->
                            <div class="plant-item border border-gray-200 rounded-lg overflow-hidden">
                                <div class="h-36 bg-gray-100">
                                    <img src="../../assets/plants/plant-placeholder-2.png" alt="Ficus" class="w-full h-full object-contain">
                                </div>
                                <div class="p-3">
                                    <h4 class="font-medium text-gray-800">Ficus Lyrata</h4>
                                    <p class="text-gray-500 text-sm">Indendørs • Moderat</p>
                                </div>
                            </div>

                            <!-- Plant 3 -->
                            <div class="plant-item border border-gray-200 rounded-lg overflow-hidden">
                                <div class="h-36 bg-gray-100">
                                    <img src="../../assets/plants/plant-placeholder-3.png" alt="Calathea" class="w-full h-full object-contain">
                                </div>
                                <div class="p-3">
                                    <h4 class="font-medium text-gray-800">Calathea Orbifolia</h4>
                                    <p class="text-gray-500 text-sm">Indendørs • Moderat</p>
                                </div>
                            </div>

                            <!-- Plant 4 -->
                            <div class="plant-item border border-gray-200 rounded-lg overflow-hidden">
                                <div class="h-36 bg-gray-100">
                                    <img src="../../assets/plants/plant-placeholder-4.png" alt="Snake Plant" class="w-full h-full object-contain">
                                </div>
                                <div class="p-3">
                                    <h4 class="font-medium text-gray-800">Sansevieria Trifasciata</h4>
                                    <p class="text-gray-500 text-sm">Indendørs • Let</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- All Plants -->
                    <div class="mb-6">
                        <h3 class="text-lg font-semibold text-gray-800 mb-4">Alle planter</h3>
                        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-4">
                            <!-- Row 1 -->
                            <div class="plant-item border border-gray-200 rounded-lg overflow-hidden">
                                <div class="h-36 bg-gray-100">
                                    <img src="../../assets/plants/plant-placeholder-1.png" alt="Aglaonema" class="w-full h-full object-contain">
                                </div>
                                <div class="p-3">
                                    <h4 class="font-medium text-gray-800">Aglaonema</h4>
                                    <p class="text-gray-500 text-sm">Indendørs • Let</p>
                                </div>
                            </div>

                            <div class="plant-item border border-gray-200 rounded-lg overflow-hidden">
                                <div class="h-36 bg-gray-100">
                                    <img src="../../assets/plants/plant-placeholder-2.png" alt="Aloe Vera" class="w-full h-full object-contain">
                                </div>
                                <div class="p-3">
                                    <h4 class="font-medium text-gray-800">Aloe Vera</h4>
                                    <p class="text-gray-500 text-sm">Indendørs • Let</p>
                                </div>
                            </div>

                            <div class="plant-item border border-gray-200 rounded-lg overflow-hidden">
                                <div class="h-36 bg-gray-100">
                                    <img src="../../assets/plants/plant-placeholder-3.png" alt="Anthurium" class="w-full h-full object-contain">
                                </div>
                                <div class="p-3">
                                    <h4 class="font-medium text-gray-800">Anthurium</h4>
                                    <p class="text-gray-500 text-sm">Indendørs • Moderat</p>
                                </div>
                            </div>

                            <div class="plant-item border border-gray-200 rounded-lg overflow-hidden">
                                <div class="h-36 bg-gray-100">
                                    <img src="../../assets/plants/plant-placeholder-4.png" alt="Areca Palm" class="w-full h-full object-contain">
                                </div>
                                <div class="p-3">
                                    <h4 class="font-medium text-gray-800">Areca Palm</h4>
                                    <p class="text-gray-500 text-sm">Indendørs • Moderat</p>
                                </div>
                            </div>

                            <!-- Row 2 (additional plants) -->
                            <div class="plant-item border border-gray-200 rounded-lg overflow-hidden">
                                <div class="h-36 bg-gray-100">
                                    <img src="../../assets/plants/plant-placeholder-1.png" alt="Boston Fern" class="w-full h-full object-contain">
                                </div>
                                <div class="p-3">
                                    <h4 class="font-medium text-gray-800">Boston Fern</h4>
                                    <p class="text-gray-500 text-sm">Indendørs • Moderat</p>
                                </div>
                            </div>

                            <div class="plant-item border border-gray-200 rounded-lg overflow-hidden selected">
                                <div class="h-36 bg-gray-100">
                                    <img src="../../assets/plants/plant-placeholder-2.png" alt="Ficus Elastica" class="w-full h-full object-contain">
                                </div>
                                <div class="p-3">
                                    <h4 class="font-medium text-gray-800">Ficus Elastica</h4>
                                    <p class="text-gray-500 text-sm">Indendørs • Moderat</p>
                                </div>
                            </div>

                            <div class="plant-item border border-gray-200 rounded-lg overflow-hidden">
                                <div class="h-36 bg-gray-100">
                                    <img src="../../assets/plants/plant-placeholder-3.png" alt="Peace Lily" class="w-full h-full object-contain">
                                </div>
                                <div class="p-3">
                                    <h4 class="font-medium text-gray-800">Peace Lily</h4>
                                    <p class="text-gray-500 text-sm">Indendørs • Let</p>
                                </div>
                            </div>

                            <div class="plant-item border border-gray-200 rounded-lg overflow-hidden">
                                <div class="h-36 bg-gray-100">
                                    <img src="../../assets/plants/plant-placeholder-4.png" alt="ZZ Plant" class="w-full h-full object-contain">
                                </div>
                                <div class="p-3">
                                    <h4 class="font-medium text-gray-800">ZZ Plant</h4>
                                    <p class="text-gray-500 text-sm">Indendørs • Let</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Can't Find Your Plant -->
                    <div class="bg-gray-50 rounded-lg border border-gray-200 p-5 mb-6">
                        <div class="flex flex-col md:flex-row items-center">
                            <div class="md:w-3/4 mb-4 md:mb-0">
                                <h3 class="text-lg font-semibold text-gray-800 mb-2">Kan du ikke finde din plante?</h3>
                                <p class="text-gray-600">Hvis du ikke kan finde din plante i vores database, kan du tilføje den manuelt.</p>
                            </div>
                            <div class="md:w-1/4 flex justify-center md:justify-end">
                                <button id="add-manual-btn" class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg transition">
                                    Tilføj manuelt
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Navigation Buttons -->
                    <div class="flex justify-end space-x-4">
                        <button id="next-step-btn" class="bg-green-600 hover:bg-green-700 text-white px-6 py-2 rounded-lg transition flex items-center">
                            Næste <i class="fas fa-arrow-right ml-2"></i>
                        </button>
                    </div>
                </div>

                <!-- Step 2: Customize Details (Hidden initially with style="display:none") -->
                <div id="step-2" class="mb-6" style="display:none">
                    <!-- Form to submit the data to the database -->
                    <form id="plant-form" method="POST" action="" enctype="multipart/form-data">
                        <div class="bg-white rounded-lg shadow-sm p-5 mb-6">
                            <div class="flex flex-col md:flex-row">
                                <!-- Left Column - Basic Info -->
                                <div class="md:w-1/3 md:pr-6 mb-6 md:mb-0">
                                    <h3 class="text-lg font-semibold text-gray-800 mb-4">Plant information</h3>

                                    <!-- Plant Preview -->
                                    <div class="mb-6">
                                        <div class="mx-auto w-48 h-48 rounded-lg bg-gray-100 flex items-center justify-center mb-3">
                                            <img id="plant-preview-image" src="../../assets/plants/plant-placeholder-2.png" alt="Ficus Elastica" class="max-w-full max-h-full object-contain">
                                        </div>

                                        <div class="text-center">
                                            <p class="font-medium text-gray-800">Ficus Elastica</p>
                                            <p class="text-gray-500 text-sm">Gummiplante</p>
                                        </div>
                                    </div>

                                    <!-- Upload Image -->
                                    <div class="image-upload border border-dashed border-gray-300 rounded-lg p-4 text-center cursor-pointer hover:bg-gray-50">
                                        <i class="fas fa-camera text-gray-500 text-xl mb-2"></i>
                                        <p class="text-sm text-gray-600">Upload dit eget billede</p>
                                        <input type="file" id="plant-image" name="plant-image" class="hidden" accept="image/*">
                                    </div>
                                </div>

                                <!-- Right Column - Details Form -->
                                <div class="md:w-2/3 md:pl-6 md:border-l md:border-gray-200">
                                    <h3 class="text-lg font-semibold text-gray-800 mb-4">Detaljer</h3>

                                    <div class="space-y-4">
                                        <!-- Plant Name -->
                                        <div>
                                            <label for="plant-name" class="block text-sm font-medium text-gray-700 mb-1">Navn på din plante</label>
                                            <input type="text" id="plant-name" name="plant-name" class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-green-500" value="Min Ficus" required>
                                        </div>

                                        <!-- Location -->
                                        <div>
                                            <label for="plant-location" class="block text-sm font-medium text-gray-700 mb-1">Placering</label>
                                            <select id="plant-location" name="plant-location" class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-green-500" required>
                                                <option value="">Vælg en placering</option>
                                                <option selected>Stue</option>
                                                <option>Soveværelse</option>
                                                <option>Køkken</option>
                                                <option>Kontor</option>
                                                <option>Badeværelse</option>
                                                <option>Have</option>
                                                <option>Altan</option>
                                            </select>
                                        </div>

                                        <!-- Plant Type (Replacing Acquisition Date) -->
                                        <div>
                                            <label for="plant-type" class="block text-sm font-medium text-gray-700 mb-1">Plantetype</label>
                                            <select id="plant-type" name="plant-type" class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-green-500" required>
                                                <option value="Indendørs" selected>Indendørs</option>
                                                <option value="Udendørs">Udendørs</option>
                                                <option value="Både og">Både indendørs og udendørs</option>
                                            </select>
                                        </div>

                                        <!-- Watering Frequency -->
                                        <div>
                                            <label for="watering-frequency" class="block text-sm font-medium text-gray-700 mb-1">Vandingsfrekvens</label>
                                            <select id="watering-frequency" name="watering-frequency" class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-green-500">
                                                <option>Vælg vandingsfrekvens</option>
                                                <option>Daglig</option>
                                                <option>Hver 2-3 dag</option>
                                                <option selected>Ugentlig</option>
                                                <option>Hver 2. uge</option>
                                                <option>Månedlig</option>
                                            </select>
                                        </div>

                                        <!-- Light Needs -->
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 mb-1">Lysbehov</label>
                                            <div class="flex space-x-4">
                                                <label class="flex items-center">
                                                    <input type="radio" name="light-needs" value="Lavt" class="h-4 w-4 text-green-600 focus:ring-green-500 border-gray-300">
                                                    <span class="ml-2 text-sm text-gray-700">Lavt</span>
                                                </label>
                                                <label class="flex items-center">
                                                    <input type="radio" name="light-needs" value="Medium" class="h-4 w-4 text-green-600 focus:ring-green-500 border-gray-300" checked>
                                                    <span class="ml-2 text-sm text-gray-700">Medium</span>
                                                </label>
                                                <label class="flex items-center">
                                                    <input type="radio" name="light-needs" value="Højt" class="h-4 w-4 text-green-600 focus:ring-green-500 border-gray-300">
                                                    <span class="ml-2 text-sm text-gray-700">Højt</span>
                                                </label>
                                            </div>
                                        </div>

                                        <!-- Notes -->
                                        <div>
                                            <label for="plant-notes" class="block text-sm font-medium text-gray-700 mb-1">Noter</label>
                                            <textarea id="plant-notes" name="plant-notes" rows="3" class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-green-500" placeholder="Tilføj eventuelle noter om din plante..."></textarea>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Notifications -->
                        <div class="bg-white rounded-lg shadow-sm p-5 mb-6">
                            <h3 class="text-lg font-semibold text-gray-800 mb-4">Notifikationer</h3>

                            <div class="space-y-4">
                                <div class="flex items-center justify-between">
                                    <div>
                                        <p class="font-medium text-gray-800">Omplantningspåmindelser</p>
                                        <p class="text-sm text-gray-600">Få besked når din plante skal omplantet</p>
                                    </div>
                                    <div class="relative inline-block w-10 mr-2 align-middle select-none">
                                        <input type="checkbox" id="toggle-repot" class="toggle-checkbox absolute block w-6 h-6 rounded-full bg-white border-4 border-gray-300 appearance-none cursor-pointer">
                                        <label for="toggle-repot" class="toggle-label block overflow-hidden h-6 w-10 rounded-full bg-gray-300 cursor-pointer"></label>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Navigation Buttons -->
                        <div class="flex justify-between">
                            <button type="button" id="prev-step-btn" class="bg-gray-200 hover:bg-gray-300 text-gray-800 px-6 py-2 rounded-lg transition flex items-center">
                                <i class="fas fa-arrow-left mr-2"></i> Tilbage
                            </button>
                            <button type="submit" id="save-plant-btn" class="bg-green-600 hover:bg-green-700 text-white px-6 py-2 rounded-lg transition flex items-center">
                                Gem plante <i class="fas fa-check ml-2"></i>
                            </button>
                        </div>
                    </form>
                </div>

                <!-- Step 3: Success (Hidden initially with style="display:none") -->
                <div id="step-3" class="mb-6" style="display:none">
                    <div class="bg-white rounded-lg shadow-sm p-8 mb-6 text-center">
                        <div class="w-16 h-16 rounded-full bg-green-100 flex items-center justify-center mx-auto mb-6">
                            <i class="fas fa-check text-green-600 text-2xl"></i>
                        </div>

                        <h3 class="text-xl font-bold text-gray-800 mb-2">Planten er tilføjet!</h3>
                        <p class="text-gray-600 mb-6"><?php echo $message ?: 'Din plante er nu tilføjet til din samling.'; ?></p>

                        <div class="flex flex-col md:flex-row justify-center space-y-4 md:space-y-0 md:space-x-4">
                            <a href="../mine-planter/index.php" class="bg-green-600 hover:bg-green-700 text-white px-6 py-2 rounded-lg transition flex items-center justify-center">
                                <i class="fas fa-seedling mr-2"></i> Se min samling
                            </a>
                            <a href="add-plants.php" class="bg-gray-200 hover:bg-gray-300 text-gray-800 px-6 py-2 rounded-lg transition flex items-center justify-center">
                                <i class="fas fa-plus mr-2"></i> Tilføj en ny plante
                            </a>
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
            // Mobile menu functionality
            const menuToggle = document.getElementById('menu-toggle');
            const sidebar = document.getElementById('sidebar');
            const overlay = document.getElementById('overlay');

            menuToggle.addEventListener('click', function() {
                sidebar.classList.toggle('open');
                overlay.classList.toggle('hidden');
                document.body.classList.toggle('overflow-hidden');
            });

            overlay.addEventListener('click', function() {
                sidebar.classList.remove('open');
                overlay.classList.add('hidden');
                document.body.classList.remove('overflow-hidden');
            });

            window.addEventListener('resize', function() {
                if (window.innerWidth >= 768) {
                    sidebar.classList.remove('open');
                    overlay.classList.add('hidden');
                    document.body.classList.remove('overflow-hidden');
                }
            });

            // Step navigation functionality
            const step1 = document.getElementById('step-1');
            const step2 = document.getElementById('step-2');
            const step3 = document.getElementById('step-3');
            const nextStepBtn = document.getElementById('next-step-btn');
            const prevStepBtn = document.getElementById('prev-step-btn');
            const addManualBtn = document.getElementById('add-manual-btn');

            // Plant selection functionality
            const plantItems = document.querySelectorAll('.plant-item');

            plantItems.forEach(item => {
                item.addEventListener('click', function() {
                    // Remove selected class from all plants
                    plantItems.forEach(p => p.classList.remove('selected'));

                    // Add selected class to clicked plant
                    this.classList.add('selected');
                });
            });

            // Next step button (Step 1 -> Step 2)
            nextStepBtn.addEventListener('click', function() {
                // Check if a plant is selected
                const selectedPlant = document.querySelector('.plant-item.selected');

                if (selectedPlant) {
                    // Hide step 1 and show step 2
                    step1.style.display = 'none';
                    step2.style.display = 'block';

                    // Update progress indicators
                    document.querySelectorAll('.flex.flex-col.items-center')[0].querySelector('div').classList.remove('bg-green-600');
                    document.querySelectorAll('.flex.flex-col.items-center')[0].querySelector('div').classList.add('bg-gray-200');
                    document.querySelectorAll('.flex.flex-col.items-center')[0].querySelector('span').classList.add('text-gray-500');

                    document.querySelectorAll('.flex.flex-col.items-center')[1].querySelector('div').classList.remove('bg-gray-200');
                    document.querySelectorAll('.flex.flex-col.items-center')[1].querySelector('div').classList.add('bg-green-600');
                    document.querySelectorAll('.flex.flex-col.items-center')[1].querySelector('div').classList.add('text-white');
                    document.querySelectorAll('.flex.flex-col.items-center')[1].querySelector('span').classList.remove('text-gray-500');

                    // Get plant name and update form
                    const plantName = selectedPlant.querySelector('h4').textContent;
                    const plantType = selectedPlant.querySelector('p').textContent.split('•')[0].trim();

                    // Set form values
                    document.getElementById('plant-name').value = `Min ${plantName}`;

                    // Set plant type dropdown
                    if (plantType === 'Indendørs') {
                        document.getElementById('plant-type').value = 'Indendørs';
                    } else if (plantType === 'Udendørs') {
                        document.getElementById('plant-type').value = 'Udendørs';
                    } else {
                        document.getElementById('plant-type').value = 'Både og';
                    }

                    // Update preview image
                    const previewImage = document.getElementById('plant-preview-image');
                    previewImage.src = selectedPlant.querySelector('img').src;

                    // Update plant info text
                    const plantInfoElements = document.querySelectorAll('#step-2 .text-center p');
                    plantInfoElements[0].textContent = plantName;
                    plantInfoElements[1].textContent = plantType;

                    // Scroll to top
                    window.scrollTo(0, 0);
                } else {
                    alert('Vælg venligst en plante for at fortsætte.');
                }
            });

            // Add Manual button (Step 1 -> Step 2)
            addManualBtn.addEventListener('click', function() {
                // Hide step 1 and show step 2
                step1.style.display = 'none';
                step2.style.display = 'block';

                // Update progress indicators
                document.querySelectorAll('.flex.flex-col.items-center')[0].querySelector('div').classList.remove('bg-green-600');
                document.querySelectorAll('.flex.flex-col.items-center')[0].querySelector('div').classList.add('bg-gray-200');
                document.querySelectorAll('.flex.flex-col.items-center')[0].querySelector('span').classList.add('text-gray-500');

                document.querySelectorAll('.flex.flex-col.items-center')[1].querySelector('div').classList.remove('bg-gray-200');
                document.querySelectorAll('.flex.flex-col.items-center')[1].querySelector('div').classList.add('bg-green-600');
                document.querySelectorAll('.flex.flex-col.items-center')[1].querySelector('div').classList.add('text-white');
                document.querySelectorAll('.flex.flex-col.items-center')[1].querySelector('span').classList.remove('text-gray-500');

                // Reset form values for manual entry
                document.getElementById('plant-name').value = '';
                document.getElementById('plant-location').selectedIndex = 0;
                document.getElementById('plant-preview-image').src = '../../assets/plants/plant-placeholder-1.png';

                // Reset plant info text
                const plantInfoElements = document.querySelectorAll('#step-2 .text-center p');
                plantInfoElements[0].textContent = 'Min Plante';
                plantInfoElements[1].textContent = 'Brugervalgt plante';

                // Scroll to top
                window.scrollTo(0, 0);
            });

            // Previous step button (Step 2 -> Step 1)
            prevStepBtn.addEventListener('click', function() {
                // Hide step 2 and show step 1
                step2.style.display = 'none';
                step1.style.display = 'block';

                // Update progress indicators
                document.querySelectorAll('.flex.flex-col.items-center')[1].querySelector('div').classList.remove('bg-green-600');
                document.querySelectorAll('.flex.flex-col.items-center')[1].querySelector('div').classList.add('bg-gray-200');
                document.querySelectorAll('.flex.flex-col.items-center')[1].querySelector('span').classList.add('text-gray-500');

                document.querySelectorAll('.flex.flex-col.items-center')[0].querySelector('div').classList.remove('bg-gray-200');
                document.querySelectorAll('.flex.flex-col.items-center')[0].querySelector('div').classList.add('bg-green-600');
                document.querySelectorAll('.flex.flex-col.items-center')[0].querySelector('span').classList.remove('text-gray-500');

                // Scroll to top
                window.scrollTo(0, 0);
            });

            // Toggle checkbox styling
            const toggleCheckboxes = document.querySelectorAll('.toggle-checkbox');

            toggleCheckboxes.forEach(checkbox => {
                checkbox.addEventListener('change', function() {
                    const label = this.nextElementSibling;

                    if (this.checked) {
                        label.classList.add('bg-green-600');
                        label.classList.remove('bg-gray-300');
                    } else {
                        label.classList.remove('bg-green-600');
                        label.classList.add('bg-gray-300');
                    }
                });
            });

            // Image upload functionality
            const imageUpload = document.querySelector('.image-upload');
            const fileInput = document.getElementById('plant-image');

            imageUpload.addEventListener('click', function() {
                fileInput.click();
            });

            fileInput.addEventListener('change', function() {
                if (fileInput.files && fileInput.files[0]) {
                    const reader = new FileReader();

                    reader.onload = function(e) {
                        const plantImage = document.getElementById('plant-preview-image');
                        plantImage.src = e.target.result;
                    };

                    reader.readAsDataURL(fileInput.files[0]);
                }
            });
        });
    </script>
</body>

</html>