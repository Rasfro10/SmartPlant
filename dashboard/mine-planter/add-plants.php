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

    // Check if water notification is enabled (using 'on'/'off' for ENUM)
    $water_notification = isset($_POST['water-notification']) ? 'on' : 'off';

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

    try {
        // Prepare SQL statement for plants table with water_notification column
        $sql = "INSERT INTO plants (user_id, name, location, plant_type, notes, watering_frequency, light_needs, image_path, water_notification) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";

        // Prepare and bind parameters
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("issssssss", $user_id, $name, $location, $plant_type, $notes, $watering_frequency, $light_needs, $image_path, $water_notification);

        // Execute statement
        if (!$stmt->execute()) {
            throw new Exception("Error inserting plant: " . $stmt->error);
        }

        // Get the ID of the newly inserted plant
        $plant_id = $conn->insert_id;

        $stmt->close();

        // Check if a sensor pin was selected
        if (isset($_POST['sensor-pin']) && !empty($_POST['sensor-pin'])) {
            $sensor_pin = $_POST['sensor-pin'];

            // Check if this sensor is already assigned to another plant
            $check_sql = "SELECT plant_id FROM plant_sensors WHERE sensor_pin = ?";
            $check_stmt = $conn->prepare($check_sql);
            $check_stmt->bind_param("s", $sensor_pin);
            $check_stmt->execute();
            $check_result = $check_stmt->get_result();

            if ($check_result->num_rows > 0) {
                // Update existing sensor assignment
                $update_sql = "UPDATE plant_sensors SET plant_id = ? WHERE sensor_pin = ?";
                $update_stmt = $conn->prepare($update_sql);
                $update_stmt->bind_param("is", $plant_id, $sensor_pin);

                if (!$update_stmt->execute()) {
                    $message .= " Men der opstod en fejl ved opdatering af sensor tilknytning.";
                }

                $update_stmt->close();
            } else {
                // Insert new sensor assignment
                $insert_sql = "INSERT INTO plant_sensors (plant_id, sensor_pin) VALUES (?, ?)";
                $insert_stmt = $conn->prepare($insert_sql);
                $insert_stmt->bind_param("is", $plant_id, $sensor_pin);

                if (!$insert_stmt->execute()) {
                    $message .= " Men der opstod en fejl ved tilknytning af sensoren.";
                }

                $insert_stmt->close();
            }

            $check_stmt->close();
        }

        $success = true;
        $message = "Din plante \"$name\" er nu tilføjet til din samling.";

        // If successful, move to step 2 (the success page)
        echo "<script>
            document.addEventListener('DOMContentLoaded', function() {
                document.getElementById('step-1').style.display = 'none';
                document.getElementById('step-2').style.display = 'block';
                
                // Update progress indicators
                document.querySelectorAll('.flex.flex-col.items-center')[0].querySelector('div').classList.remove('bg-green-600');
                document.querySelectorAll('.flex.flex-col.items-center')[0].querySelector('div').classList.add('bg-gray-200');
                document.querySelectorAll('.flex.flex-col.items-center')[0].querySelector('span').classList.add('text-gray-500');
                
                document.querySelectorAll('.flex.flex-col.items-center')[1].querySelector('div').classList.remove('bg-gray-200');
                document.querySelectorAll('.flex.flex-col.items-center')[1].querySelector('div').classList.add('bg-green-600');
                document.querySelectorAll('.flex.flex-col.items-center')[1].querySelector('div').classList.add('text-white');
                document.querySelectorAll('.flex.flex-col.items-center')[1].querySelector('span').classList.remove('text-gray-500');
            });
        </script>";
    } catch (Exception $e) {
        $message = "Der opstod en fejl: " . $e->getMessage();
    }
}

// Get list of already assigned sensors
$assigned_sensors = [];
$sensors_sql = "SELECT sensor_pin, p.name as plant_name FROM plant_sensors ps JOIN plants p ON ps.plant_id = p.id";
$sensors_result = $conn->query($sensors_sql);

if ($sensors_result && $sensors_result->num_rows > 0) {
    while ($row = $sensors_result->fetch_assoc()) {
        $assigned_sensors[$row['sensor_pin']] = $row['plant_name'];
    }
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

                    <!-- Progress Steps - Now only two steps -->
                    <div class="flex justify-between items-center relative mt-8">
                        <div class="absolute left-0 right-0 top-1/2 transform -translate-y-1/2 h-1 bg-gray-200 z-0"></div>

                        <div class="flex flex-col items-center relative z-10 w-1/2">
                            <div class="h-8 w-8 rounded-full bg-green-600 flex items-center justify-center text-white">
                                1
                            </div>
                            <span class="text-sm font-medium mt-2">Tilpas detaljer</span>
                        </div>

                        <div class="flex flex-col items-center relative z-10 w-1/2">
                            <div class="h-8 w-8 rounded-full bg-gray-200 flex items-center justify-center text-gray-500">
                                2
                            </div>
                            <span class="text-sm font-medium mt-2 text-gray-500">Gem plante</span>
                        </div>
                    </div>
                </div>

                <!-- Step 1: Customize Details (Shown initially) -->
                <div id="step-1" class="mb-6">
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
                                            <img id="plant-preview-image" src="../../assets/plants/default.png" alt="Plant Preview" class="max-w-full max-h-full object-contain">
                                        </div>
                                    </div>

                                    <!-- Upload Image -->
                                    <div class="image-upload border border-dashed border-gray-300 rounded-lg p-4 text-center cursor-pointer hover:bg-gray-50">
                                        <i class="fas fa-camera text-gray-500 text-xl mb-2"></i>
                                        <p class="text-sm text-gray-600">Upload et billede af din plante</p>
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
                                            <input type="text" id="plant-name" name="plant-name" class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-green-500" placeholder="F.eks. Min Monstera" required>
                                        </div>

                                        <!-- Location -->
                                        <div>
                                            <label for="plant-location" class="block text-sm font-medium text-gray-700 mb-1">Placering</label>
                                            <select id="plant-location" name="plant-location" class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-green-500" required>
                                                <option value="">Vælg en placering</option>
                                                <option>Stue</option>
                                                <option>Soveværelse</option>
                                                <option>Køkken</option>
                                                <option>Kontor</option>
                                                <option>Badeværelse</option>
                                                <option>Have</option>
                                                <option>Altan</option>
                                            </select>
                                        </div>

                                        <!-- Plant Type -->
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
                                            <select id="watering-frequency" name="watering-frequency" class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-green-500" required>
                                                <option value="">Vælg vandingsfrekvens</option>
                                                <option>Daglig</option>
                                                <option>Hver 2-3 dag</option>
                                                <option>Ugentlig</option>
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
                        <!-- Sensor Assignment -->
                        <div class="bg-white rounded-lg shadow-sm p-5 mb-6">
                            <h3 class="text-lg font-semibold text-gray-800 mb-4">Sensor tilkobling</h3>

                            <div class="space-y-4">
                                <div>
                                    <label for="sensor-pin" class="block text-sm font-medium text-gray-700 mb-1">Tildel fugtighedssensor</label>
                                    <select id="sensor-pin" name="sensor-pin" class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-green-500">
                                        <option value="">Ingen sensor</option>
                                        <?php
                                        // Only show A5 and A6 as options
                                        $available_pins = ['A5', 'A6'];
                                        foreach ($available_pins as $pin):
                                            $pin_assigned = isset($assigned_sensors[$pin]);
                                        ?>
                                            <option value="<?php echo $pin; ?>" <?php echo $pin_assigned ? 'data-assigned="' . htmlspecialchars($assigned_sensors[$pin]) . '"' : ''; ?>>
                                                <?php echo $pin; ?>
                                                <?php if ($pin_assigned): ?>
                                                    (Bruges af: <?php echo htmlspecialchars($assigned_sensors[$pin]); ?>)
                                                <?php endif; ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <p class="text-sm text-gray-500 mt-1">Vælg hvilken pin din fugtighedssensor er tilsluttet på Arduino (A5 eller A6).</p>
                                    <p id="sensor-warning" class="text-sm text-orange-500 mt-1 hidden">Denne sensor er allerede tilknyttet en anden plante. Hvis du fortsætter, vil sensoren blive flyttet til denne plante.</p>
                                </div>
                            </div>
                        </div>

                        <!-- Notifications -->
                        <div class="bg-white rounded-lg shadow-sm p-5 mb-6">
                            <h3 class="text-lg font-semibold text-gray-800 mb-4">Notifikationer</h3>

                            <div class="space-y-4">
                                <div class="flex items-center justify-between">
                                    <div>
                                        <p class="font-medium text-gray-800">Vandingspåmindelser</p>
                                        <p class="text-sm text-gray-600">Få besked når din plante skal vandes</p>
                                    </div>
                                    <label for="toggle-water" class="relative inline-flex items-center cursor-pointer">
                                        <input type="checkbox" name="water-notification" id="toggle-water" class="sr-only peer">
                                        <div class="w-11 h-6 bg-gray-300 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-green-600"></div>
                                    </label>
                                </div>
                            </div>
                        </div>

                        <!-- Save Button -->
                        <div class="flex justify-end">
                            <button type="submit" id="save-plant-btn" class="bg-green-600 hover:bg-green-700 text-white px-6 py-2 rounded-lg transition flex items-center">
                                Gem plante <i class="fas fa-check ml-2"></i>
                            </button>
                        </div>
                    </form>
                </div>

                <!-- Step 2: Success (Hidden initially) -->
                <div id="step-2" class="mb-6" style="display:none">
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

            // Sensor assignment warning
            const sensorPin = document.getElementById('sensor-pin');
            const sensorWarning = document.getElementById('sensor-warning');

            sensorPin.addEventListener('change', function() {
                const selectedOption = this.options[this.selectedIndex];
                if (selectedOption.hasAttribute('data-assigned')) {
                    sensorWarning.classList.remove('hidden');
                } else {
                    sensorWarning.classList.add('hidden');
                }
            });
        });
    </script>
</body>

</html>