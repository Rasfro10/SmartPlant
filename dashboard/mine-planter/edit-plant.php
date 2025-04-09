<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/smartplant/backend/auth/session_handler.php';
// Set current page for sidebar highlighting
$page = "myPlants";

// Check if plant ID is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    // Redirect back to plants list if no ID is provided
    header("Location: index.php");
    exit;
}

$plant_id = (int)$_GET['id'];
$user_id = $_SESSION['id'];
$success = false;
$message = "";
$plant = null;

// Fetch plant data
$sql = "SELECT * FROM plants WHERE id = ? AND user_id = ?";

if ($stmt = $conn->prepare($sql)) {
    $stmt->bind_param("ii", $plant_id, $user_id);

    if ($stmt->execute()) {
        $result = $stmt->get_result();

        if ($row = $result->fetch_assoc()) {
            $plant = $row;
        } else {
            // Plant not found or doesn't belong to user
            header("Location: index.php?error=1&message=" . urlencode("Planten findes ikke eller tilhører ikke dig."));
            exit;
        }
    } else {
        // Error in query
        header("Location: index.php?error=1&message=" . urlencode("Der opstod en fejl ved hentning af planteinformation."));
        exit;
    }

    $stmt->close();
}

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Get data from form
    $name = $_POST['plant-name'];
    $location = $_POST['plant-location'];
    $plant_type = $_POST['plant-type'];
    $notes = $_POST['plant-notes'];
    $watering_frequency = $_POST['watering-frequency'];

    // Get light needs from radio buttons
    $light_needs = "Medium"; // Default value
    if (isset($_POST['light-needs'])) {
        $light_needs = $_POST['light-needs'];
    }

    // Initialize image path variable (keep existing image by default)
    $image_path = $plant['image_path'];

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

    // Prepare UPDATE statement including all fields
    $sql = "UPDATE plants SET name = ?, location = ?, plant_type = ?, notes = ?, watering_frequency = ?, light_needs = ?, image_path = ? WHERE id = ? AND user_id = ?";

    // Prepare and bind parameters
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sssssssii", $name, $location, $plant_type, $notes, $watering_frequency, $light_needs, $image_path, $plant_id, $user_id);

    // Execute statement
    if ($stmt->execute()) {
        $success = true;
        $message = "Planten er blevet opdateret.";

        // Refresh plant data
        $plant['name'] = $name;
        $plant['location'] = $location;
        $plant['plant_type'] = $plant_type;
        $plant['notes'] = $notes;
        $plant['watering_frequency'] = $watering_frequency;
        $plant['light_needs'] = $light_needs;
        $plant['image_path'] = $image_path;
    } else {
        $message = "Der opstod en fejl ved opdatering af planten: " . $stmt->error;
    }

    $stmt->close();
}

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
                <!-- Page Header -->
                <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-6">
                    <div>
                        <h2 class="text-2xl font-bold text-gray-800">Rediger Plante</h2>
                        <p class="text-gray-600">Opdater information om din plante.</p>
                    </div>
                    <div class="mt-3 md:mt-0">
                        <a href="index.php" class="bg-gray-200 hover:bg-gray-300 text-gray-700 px-4 py-2 rounded-lg transition flex items-center">
                            <i class="fas fa-arrow-left mr-2"></i> Tilbage til planter
                        </a>
                    </div>
                </div>

                <?php if ($success): ?>
                    <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-6 rounded" role="alert">
                        <div class="flex items-center">
                            <div class="py-1">
                                <i class="fas fa-check-circle mr-2"></i>
                            </div>
                            <div>
                                <p><?php echo $message; ?></p>
                            </div>
                        </div>
                    </div>
                <?php elseif (!empty($message)): ?>
                    <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-6 rounded" role="alert">
                        <div class="flex items-center">
                            <div class="py-1">
                                <i class="fas fa-exclamation-circle mr-2"></i>
                            </div>
                            <div>
                                <p><?php echo $message; ?></p>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Edit Plant Form -->
                <form method="POST" enctype="multipart/form-data">
                    <div class="bg-white rounded-lg shadow-sm p-5 mb-6">
                        <div class="flex flex-col md:flex-row">
                            <!-- Left Column - Basic Info -->
                            <div class="md:w-1/3 md:pr-6 mb-6 md:mb-0">
                                <h3 class="text-lg font-semibold text-gray-800 mb-4">Plant information</h3>

                                <!-- Plant Preview -->
                                <div class="mb-6">
                                    <div class="mx-auto w-48 h-48 rounded-lg bg-gray-100 flex items-center justify-center mb-3">
                                        <img id="plant-preview-image" src="../../<?php echo htmlspecialchars($plant['image_path']); ?>" alt="<?php echo htmlspecialchars($plant['name']); ?>" class="max-w-full max-h-full object-contain">
                                    </div>

                                    <div class="text-center">
                                        <p class="font-medium text-gray-800"><?php echo htmlspecialchars($plant['name']); ?></p>
                                        <p class="text-gray-500 text-sm"><?php echo htmlspecialchars($plant['plant_type']); ?></p>
                                    </div>
                                </div>

                                <!-- Upload Image -->
                                <div class="image-upload border border-dashed border-gray-300 rounded-lg p-4 text-center cursor-pointer hover:bg-gray-50">
                                    <i class="fas fa-camera text-gray-500 text-xl mb-2"></i>
                                    <p class="text-sm text-gray-600">Upload nyt billede</p>
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
                                        <input type="text" id="plant-name" name="plant-name" class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-green-500" value="<?php echo htmlspecialchars($plant['name']); ?>" required>
                                    </div>

                                    <!-- Location -->
                                    <div>
                                        <label for="plant-location" class="block text-sm font-medium text-gray-700 mb-1">Placering</label>
                                        <select id="plant-location" name="plant-location" class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-green-500" required>
                                            <option value="">Vælg en placering</option>
                                            <option value="Stue" <?php echo $plant['location'] == 'Stue' ? 'selected' : ''; ?>>Stue</option>
                                            <option value="Soveværelse" <?php echo $plant['location'] == 'Soveværelse' ? 'selected' : ''; ?>>Soveværelse</option>
                                            <option value="Køkken" <?php echo $plant['location'] == 'Køkken' ? 'selected' : ''; ?>>Køkken</option>
                                            <option value="Kontor" <?php echo $plant['location'] == 'Kontor' ? 'selected' : ''; ?>>Kontor</option>
                                            <option value="Badeværelse" <?php echo $plant['location'] == 'Badeværelse' ? 'selected' : ''; ?>>Badeværelse</option>
                                            <option value="Have" <?php echo $plant['location'] == 'Have' ? 'selected' : ''; ?>>Have</option>
                                            <option value="Altan" <?php echo $plant['location'] == 'Altan' ? 'selected' : ''; ?>>Altan</option>
                                        </select>
                                    </div>

                                    <!-- Plant Type -->
                                    <div>
                                        <label for="plant-type" class="block text-sm font-medium text-gray-700 mb-1">Plantetype</label>
                                        <select id="plant-type" name="plant-type" class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-green-500" required>
                                            <option value="Indendørs" <?php echo $plant['plant_type'] == 'Indendørs' ? 'selected' : ''; ?>>Indendørs</option>
                                            <option value="Udendørs" <?php echo $plant['plant_type'] == 'Udendørs' ? 'selected' : ''; ?>>Udendørs</option>
                                            <option value="Både og" <?php echo $plant['plant_type'] == 'Både og' ? 'selected' : ''; ?>>Både indendørs og udendørs</option>
                                        </select>
                                    </div>

                                    <!-- Watering Frequency -->
                                    <div>
                                        <label for="watering-frequency" class="block text-sm font-medium text-gray-700 mb-1">Vandingsfrekvens</label>
                                        <select id="watering-frequency" name="watering-frequency" class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-green-500">
                                            <option value="Daglig" <?php echo $plant['watering_frequency'] == 'Daglig' ? 'selected' : ''; ?>>Daglig</option>
                                            <option value="Hver 2-3 dag" <?php echo $plant['watering_frequency'] == 'Hver 2-3 dag' ? 'selected' : ''; ?>>Hver 2-3 dag</option>
                                            <option value="Ugentlig" <?php echo $plant['watering_frequency'] == 'Ugentlig' ? 'selected' : ''; ?>>Ugentlig</option>
                                            <option value="Hver 2. uge" <?php echo $plant['watering_frequency'] == 'Hver 2. uge' ? 'selected' : ''; ?>>Hver 2. uge</option>
                                            <option value="Månedlig" <?php echo $plant['watering_frequency'] == 'Månedlig' ? 'selected' : ''; ?>>Månedlig</option>
                                        </select>
                                    </div>

                                    <!-- Light Needs -->
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">Lysbehov</label>
                                        <div class="flex space-x-4">
                                            <label class="flex items-center">
                                                <input type="radio" name="light-needs" value="Lavt" class="h-4 w-4 text-green-600 focus:ring-green-500 border-gray-300" <?php echo $plant['light_needs'] == 'Lavt' ? 'checked' : ''; ?>>
                                                <span class="ml-2 text-sm text-gray-700">Lavt</span>
                                            </label>
                                            <label class="flex items-center">
                                                <input type="radio" name="light-needs" value="Medium" class="h-4 w-4 text-green-600 focus:ring-green-500 border-gray-300" <?php echo $plant['light_needs'] == 'Medium' ? 'checked' : ''; ?>>
                                                <span class="ml-2 text-sm text-gray-700">Medium</span>
                                            </label>
                                            <label class="flex items-center">
                                                <input type="radio" name="light-needs" value="Højt" class="h-4 w-4 text-green-600 focus:ring-green-500 border-gray-300" <?php echo $plant['light_needs'] == 'Højt' ? 'checked' : ''; ?>>
                                                <span class="ml-2 text-sm text-gray-700">Højt</span>
                                            </label>
                                        </div>
                                    </div>

                                    <!-- Notes -->
                                    <div>
                                        <label for="plant-notes" class="block text-sm font-medium text-gray-700 mb-1">Noter</label>
                                        <textarea id="plant-notes" name="plant-notes" rows="3" class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-green-500" placeholder="Tilføj eventuelle noter om din plante..."><?php echo htmlspecialchars($plant['notes']); ?></textarea>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Action Buttons -->
                    <div class="flex justify-between">
                        <a href="index.php" class="bg-gray-200 hover:bg-gray-300 text-gray-800 px-6 py-2 rounded-lg transition flex items-center">
                            <i class="fas fa-times mr-2"></i> Annuller
                        </a>
                        <button type="submit" class="bg-green-600 hover:bg-green-700 text-white px-6 py-2 rounded-lg transition flex items-center">
                            <i class="fas fa-save mr-2"></i> Gem ændringer
                        </button>
                    </div>
                </form>
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