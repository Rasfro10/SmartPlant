<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/smartplant/backend/auth/session_handler.php';
// Set current page for sidebar highlighting
$page = "settings";

// Include header
include('../../components/header.php');

// Get user details from database
$user_id = $_SESSION["id"];
$sql = "SELECT firstname, lastname, email, created_at FROM users WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$stmt->bind_result($firstname, $lastname, $email, $created_at);
$stmt->fetch();
$stmt->close();

// Format created_at date
$member_since = date("d. F Y", strtotime($created_at));

// Get user initials for avatar
$initials = strtoupper(substr($firstname, 0, 1) . substr($lastname, 0, 1));

// Get total plant count
$plant_count_sql = "SELECT COUNT(*) FROM plants WHERE user_id = ?";
$plant_count_stmt = $conn->prepare($plant_count_sql);
$plant_count_stmt->bind_param("i", $user_id);
$plant_count_stmt->execute();
$plant_count_stmt->bind_result($plant_count);
$plant_count_stmt->fetch();
$plant_count_stmt->close();

// Generate CSRF token for forms
$csrf_token = generate_csrf_token();

// Check for success/error messages
$profile_success = isset($_SESSION['profile_success']) ? $_SESSION['profile_success'] : '';
$profile_error = isset($_SESSION['profile_error']) ? $_SESSION['profile_error'] : '';
$password_success = isset($_SESSION['password_success']) ? $_SESSION['password_success'] : '';
$password_error = isset($_SESSION['password_error']) ? $_SESSION['password_error'] : '';

// Clear session messages
unset($_SESSION['profile_success']);
unset($_SESSION['profile_error']);
unset($_SESSION['password_success']);
unset($_SESSION['password_error']);
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

        /* Modal styles */
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
            z-index: 1000;
            overflow: auto;
        }

        .modal-content {
            background-color: #fff;
            margin: 10% auto;
            padding: 20px;
            border-radius: 8px;
            max-width: 500px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        .modal-close {
            float: right;
            font-size: 28px;
            font-weight: bold;
            cursor: pointer;
        }

        .modal-close:hover {
            color: #10b981;
        }

        /* Alert styles */
        .alert {
            padding: 12px 16px;
            border-radius: 4px;
            margin-bottom: 16px;
        }

        .alert-success {
            background-color: #d1fae5;
            color: #065f46;
            border-left: 4px solid #10b981;
        }

        .alert-error {
            background-color: #fee2e2;
            color: #b91c1c;
            border-left: 4px solid #ef4444;
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
                </div>

                <!-- Display success/error messages for profile update -->
                <?php if (!empty($profile_success)): ?>
                    <div class="alert alert-success">
                        <i class="fas fa-check-circle mr-2"></i> <?php echo $profile_success; ?>
                    </div>
                <?php endif; ?>

                <?php if (!empty($profile_error)): ?>
                    <div class="alert alert-error">
                        <i class="fas fa-exclamation-circle mr-2"></i> <?php echo $profile_error; ?>
                    </div>
                <?php endif; ?>

                <!-- Display success/error messages for password change -->
                <?php if (!empty($password_success)): ?>
                    <div class="alert alert-success">
                        <i class="fas fa-check-circle mr-2"></i> <?php echo $password_success; ?>
                    </div>
                <?php endif; ?>

                <?php if (!empty($password_error)): ?>
                    <div class="alert alert-error">
                        <i class="fas fa-exclamation-circle mr-2"></i> <?php echo $password_error; ?>
                    </div>
                <?php endif; ?>

                <!-- User Profile Card -->
                <div class="bg-white rounded-lg shadow-sm p-5 mb-6">
                    <div class="flex flex-col md:flex-row">
                        <div class="md:w-1/4 flex flex-col items-center mb-6 md:mb-0 md:border-r md:border-gray-200 md:pr-6">
                            <div class="h-24 w-24 rounded-full bg-green-600 flex items-center justify-center text-white text-2xl font-bold mb-3">
                                <?php echo htmlspecialchars($initials); ?>
                            </div>
                            <h4 class="font-bold text-gray-800 text-lg"><?php echo htmlspecialchars($firstname . ' ' . $lastname); ?></h4>
                            <p class="text-gray-600"><?php echo htmlspecialchars($email); ?></p>
                            <p class="text-sm text-gray-500 mt-1">Medlem siden <?php echo $member_since; ?></p>

                            <button id="edit-profile-btn" class="mt-4 text-green-600 hover:text-green-800 font-medium flex items-center">
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
                                    <p class="font-medium"><?php echo $plant_count; ?></p>
                                </div>
                                <div>
                                    <p class="text-sm text-gray-500">Registreret siden</p>
                                    <p class="font-medium"><?php echo $member_since; ?></p>
                                </div>
                                <div>
                                    <p class="text-sm text-gray-500">Status</p>
                                    <p class="font-medium text-green-600">Aktiv</p>
                                </div>
                            </div>

                            <div class="mt-4">
                                <p class="font-medium text-gray-800 mb-2">Plant Expert Niveau</p>
                                <div class="w-full bg-gray-200 rounded-full h-2.5 mb-2">
                                    <div class="bg-green-600 h-2.5 rounded-full" style="width: <?php echo min($plant_count * 5, 100); ?>%"></div>
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
                                <p class="text-sm text-gray-600">Skift din adgangskode regelmæssigt for øget sikkerhed</p>
                            </div>
                            <button id="change-password-btn" class="text-blue-600 hover:text-blue-800 text-sm font-medium flex items-center">
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
            </main>
        </div>
    </div>

    <!-- Edit Profile Modal -->
    <div id="edit-profile-modal" class="modal">
        <div class="modal-content">
            <span class="modal-close" id="close-profile-modal">&times;</span>
            <h2 class="text-xl font-bold text-gray-800 mb-4">Rediger Profil</h2>

            <form action="update_profile.php" method="POST">
                <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">

                <div class="mb-4">
                    <label for="firstname" class="block text-gray-700 text-sm font-medium mb-2">Fornavn</label>
                    <input type="text" id="firstname" name="firstname" value="<?php echo htmlspecialchars($firstname); ?>"
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-green-500" required>
                </div>

                <div class="mb-4">
                    <label for="lastname" class="block text-gray-700 text-sm font-medium mb-2">Efternavn</label>
                    <input type="text" id="lastname" name="lastname" value="<?php echo htmlspecialchars($lastname); ?>"
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-green-500" required>
                </div>

                <div class="mb-6">
                    <label for="email" class="block text-gray-700 text-sm font-medium mb-2">Email</label>
                    <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($email); ?>"
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-green-500" required>
                </div>

                <div class="flex justify-end">
                    <button type="button" class="bg-gray-300 hover:bg-gray-400 text-gray-800 font-medium py-2 px-4 rounded-lg mr-2" id="cancel-profile-edit">
                        Annuller
                    </button>
                    <button type="submit" class="bg-green-600 hover:bg-green-700 text-white font-medium py-2 px-4 rounded-lg">
                        Gem ændringer
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Change Password Modal -->
    <div id="change-password-modal" class="modal">
        <div class="modal-content">
            <span class="modal-close" id="close-password-modal">&times;</span>
            <h2 class="text-xl font-bold text-gray-800 mb-4">Skift Adgangskode</h2>

            <form action="change_password.php" method="POST">
                <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">

                <div class="mb-4">
                    <label for="current_password" class="block text-gray-700 text-sm font-medium mb-2">Nuværende adgangskode</label>
                    <input type="password" id="current_password" name="current_password"
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-green-500" required>
                </div>

                <div class="mb-4">
                    <label for="new_password" class="block text-gray-700 text-sm font-medium mb-2">Ny adgangskode</label>
                    <input type="password" id="new_password" name="new_password"
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-green-500" required>
                    <p class="text-xs text-gray-500 mt-1">Adgangskoden skal være mindst 8 tegn og indeholde bogstaver og tal</p>
                </div>

                <div class="mb-6">
                    <label for="confirm_password" class="block text-gray-700 text-sm font-medium mb-2">Bekræft ny adgangskode</label>
                    <input type="password" id="confirm_password" name="confirm_password"
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-green-500" required>
                </div>

                <div class="flex justify-end">
                    <button type="button" class="bg-gray-300 hover:bg-gray-400 text-gray-800 font-medium py-2 px-4 rounded-lg mr-2" id="cancel-password-change">
                        Annuller
                    </button>
                    <button type="submit" class="bg-green-600 hover:bg-green-700 text-white font-medium py-2 px-4 rounded-lg">
                        Skift adgangskode
                    </button>
                </div>
            </form>
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

            // Edit Profile Modal Elements
            const editProfileBtn = document.getElementById('edit-profile-btn');
            const editProfileModal = document.getElementById('edit-profile-modal');
            const closeProfileModal = document.getElementById('close-profile-modal');
            const cancelProfileEdit = document.getElementById('cancel-profile-edit');

            // Change Password Modal Elements
            const changePasswordBtn = document.getElementById('change-password-btn');
            const changePasswordModal = document.getElementById('change-password-modal');
            const closePasswordModal = document.getElementById('close-password-modal');
            const cancelPasswordChange = document.getElementById('cancel-password-change');

            // Toggle mobile menu
            if (menuToggle) {
                menuToggle.addEventListener('click', function() {
                    sidebar.classList.toggle('open');
                    overlay.classList.toggle('hidden');
                    document.body.classList.toggle('overflow-hidden');
                });
            }

            // Close menu when clicking overlay
            if (overlay) {
                overlay.addEventListener('click', function() {
                    sidebar.classList.remove('open');
                    overlay.classList.add('hidden');
                    document.body.classList.remove('overflow-hidden');
                });
            }

            // Edit Profile Modal
            if (editProfileBtn) {
                editProfileBtn.addEventListener('click', function() {
                    editProfileModal.style.display = 'block';
                });
            }

            if (closeProfileModal) {
                closeProfileModal.addEventListener('click', function() {
                    editProfileModal.style.display = 'none';
                });
            }

            if (cancelProfileEdit) {
                cancelProfileEdit.addEventListener('click', function() {
                    editProfileModal.style.display = 'none';
                });
            }

            // Change Password Modal
            if (changePasswordBtn) {
                changePasswordBtn.addEventListener('click', function() {
                    changePasswordModal.style.display = 'block';
                });
            }

            if (closePasswordModal) {
                closePasswordModal.addEventListener('click', function() {
                    changePasswordModal.style.display = 'none';
                });
            }

            if (cancelPasswordChange) {
                cancelPasswordChange.addEventListener('click', function() {
                    changePasswordModal.style.display = 'none';
                });
            }

            // Close modals when clicking outside
            window.addEventListener('click', function(event) {
                if (event.target === editProfileModal) {
                    editProfileModal.style.display = 'none';
                }
                if (event.target === changePasswordModal) {
                    changePasswordModal.style.display = 'none';
                }
            });

            // Handle window resize
            window.addEventListener('resize', function() {
                if (window.innerWidth >= 768) {
                    sidebar.classList.remove('open');
                    overlay.classList.add('hidden');
                    document.body.classList.remove('overflow-hidden');
                }
            });

            // Hide alert messages after 5 seconds
            const alerts = document.querySelectorAll('.alert');
            if (alerts.length > 0) {
                setTimeout(function() {
                    alerts.forEach(function(alert) {
                        alert.style.display = 'none';
                    });
                }, 5000);
            }
        });
    </script>
</body>

</html>