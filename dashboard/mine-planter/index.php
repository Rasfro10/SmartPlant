<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/smartplant/backend/auth/session_handler.php';
// Set current page for sidebar highlighting
$page = "myPlants";

// Get plants from database
$user_id = $_SESSION['id'];
$plants = [];

// Get filter parameters if set
$filter = isset($_GET['filter']) ? $_GET['filter'] : 'all';
$search = isset($_GET['search']) ? $_GET['search'] : '';

// Base query
$sql = "SELECT * FROM plants WHERE user_id = ?";

// Add search condition if search is provided
if (!empty($search)) {
    $sql .= " AND (name LIKE ? OR location LIKE ?)";
}

// Add filter condition
if ($filter == 'needs_water') {
    $sql .= " AND watering_frequency = 'needs_water'";
} elseif ($filter == 'needs_light') {
    $sql .= " AND light_needs = 'needs_light'";
} elseif ($filter == 'healthy') {
    $sql .= " AND (watering_frequency != 'needs_water' AND light_needs != 'needs_light')";
}

// Add order by
$sql .= " ORDER BY created_at DESC";

// Prepare and execute query
if ($stmt = $conn->prepare($sql)) {
    if (!empty($search)) {
        $searchParam = "%$search%";
        $stmt->bind_param("iss", $user_id, $searchParam, $searchParam);
    } else {
        $stmt->bind_param("i", $user_id);
    }

    if ($stmt->execute()) {
        $result = $stmt->get_result();
        while ($plant = $result->fetch_assoc()) {
            $plants[] = $plant;
        }
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

        .plant-card {
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }

        .plant-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
        }

        .dropdown-menu {
            display: none;
            position: absolute;
            right: 0;
            top: 100%;
            background-color: white;
            border: 1px solid #e2e8f0;
            border-radius: 0.375rem;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
            z-index: 10;
            min-width: 120px;
        }

        .dropdown-menu.show {
            display: block;
        }

        .dropdown-item {
            padding: 0.5rem 1rem;
            display: block;
            color: #4a5568;
            font-size: 0.875rem;
            transition: background-color 0.2s;
        }

        .dropdown-item:hover {
            background-color: #f7fafc;
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
                        <h2 class="text-2xl font-bold text-gray-800">Mine Planter</h2>
                        <p class="text-gray-600">Administrer dine planter og hold øje med deres behov.</p>
                    </div>
                    <div class="mt-3 md:mt-0 flex space-x-2">
                        <a href="add-plants.php" class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg transition flex items-center">
                            <i class="fas fa-plus mr-2"></i> Tilføj Plante
                        </a>
                        <div class="relative">
                            <button id="filter-btn" class="bg-gray-200 hover:bg-gray-300 text-gray-700 px-4 py-2 rounded-lg transition flex items-center">
                                <i class="fas fa-filter mr-2"></i> Filter
                            </button>
                            <div id="filter-dropdown" class="dropdown-menu">
                                <a href="?filter=all" class="dropdown-item filter-option <?php echo $filter == 'all' ? 'bg-green-100' : ''; ?>" data-filter="all">Alle planter</a>
                                <a href="?filter=needs_water" class="dropdown-item filter-option <?php echo $filter == 'needs_water' ? 'bg-green-100' : ''; ?>" data-filter="needs_water">Behøver vand</a>
                                <a href="?filter=needs_light" class="dropdown-item filter-option <?php echo $filter == 'needs_light' ? 'bg-green-100' : ''; ?>" data-filter="needs_light">Behøver lys</a>
                                <a href="?filter=healthy" class="dropdown-item filter-option <?php echo $filter == 'healthy' ? 'bg-green-100' : ''; ?>" data-filter="healthy">Sunde planter</a>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Search & Filter Bar -->
                <div class="bg-white rounded-lg shadow-sm p-4 mb-6">
                    <div class="flex flex-col md:flex-row space-y-3 md:space-y-0 md:space-x-4">
                        <div class="flex-1 relative">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <i class="fas fa-search text-gray-400"></i>
                            </div>
                            <input id="search-input" type="text" placeholder="Søg efter planter..." value="<?php echo htmlspecialchars($search); ?>" class="block w-full pl-10 pr-3 py-2 border border-gray-300 rounded-md leading-5 bg-white focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-green-500">
                        </div>
                        <div class="flex space-x-2">
                            <select id="filter-select" class="form-select border border-gray-300 rounded-md py-2 px-3 focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-green-500">
                                <option value="all" <?php echo $filter == 'all' ? 'selected' : ''; ?>>Alle planter</option>
                                <option value="needs_water" <?php echo $filter == 'needs_water' ? 'selected' : ''; ?>>Behøver vand</option>
                                <option value="needs_light" <?php echo $filter == 'needs_light' ? 'selected' : ''; ?>>Behøver lys</option>
                                <option value="healthy" <?php echo $filter == 'healthy' ? 'selected' : ''; ?>>Sunde planter</option>
                            </select>
                            <div class="flex border border-gray-300 rounded-md overflow-hidden">
                                <button id="grid-view" class="p-2 bg-green-500 text-white">
                                    <i class="fas fa-th-large"></i>
                                </button>
                                <button id="list-view" class="p-2 bg-white text-gray-700">
                                    <i class="fas fa-list"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Plants Grid -->
                <div id="plants-container" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4 mb-6">
                    <?php if (count($plants) > 0): ?>
                        <?php foreach ($plants as $plant): ?>
                            <div class="bg-white rounded-lg shadow-sm plant-card plant-item" data-name="<?php echo htmlspecialchars($plant['name']); ?>" data-location="<?php echo htmlspecialchars($plant['location']); ?>">
                                <div class="relative">
                                    <img src="../../<?php echo htmlspecialchars($plant['image_path']); ?>" alt="<?php echo htmlspecialchars($plant['name']); ?>" class="w-full h-48 object-contain rounded-t-lg">
                                    <span class="absolute top-2 right-2 bg-green-500 text-white text-xs px-2 py-1 rounded-full">
                                        Sund
                                    </span>
                                </div>
                                <div class="p-4">
                                    <div class="flex justify-between items-start mb-2">
                                        <h4 class="font-medium text-gray-800 text-lg"><?php echo htmlspecialchars($plant['name']); ?></h4>
                                        <div class="dropdown relative">
                                            <button class="dropdown-toggle text-gray-500 hover:text-gray-700 focus:outline-none" data-plant-id="<?php echo $plant['id']; ?>">
                                                <i class="fas fa-ellipsis-v"></i>
                                            </button>
                                            <div class="dropdown-menu">
                                                <a href="edit-plant.php?id=<?php echo $plant['id']; ?>" class="dropdown-item">
                                                    <i class="fas fa-edit mr-2"></i> Rediger
                                                </a>
                                                <a href="#" class="dropdown-item delete-plant" data-plant-id="<?php echo $plant['id']; ?>">
                                                    <i class="fas fa-trash mr-2"></i> Slet
                                                </a>
                                            </div>
                                        </div>
                                    </div>
                                    <p class="text-gray-500 text-sm mb-3">Tilføjet: <?php echo date('d. F Y', strtotime($plant['created_at'])); ?></p>
                                    <div class="grid grid-cols-2 gap-2">
                                        <div class="flex items-center text-sm text-gray-600">
                                            <i class="fas fa-tint text-blue-500 mr-2"></i>
                                            <?php echo htmlspecialchars($plant['watering_frequency']); ?>
                                        </div>
                                        <div class="flex items-center text-sm text-gray-600">
                                            <i class="fas fa-temperature-high text-red-500 mr-2"></i> 23°C
                                        </div>
                                        <div class="flex items-center text-sm text-gray-600">
                                            <i class="fas fa-sun text-yellow-500 mr-2"></i>
                                            <?php echo htmlspecialchars($plant['light_needs']); ?>
                                        </div>
                                        <div class="flex items-center text-sm text-gray-600">
                                            <i class="fas fa-map-marker-alt text-purple-500 mr-2"></i>
                                            <?php echo htmlspecialchars($plant['location']); ?>
                                        </div>
                                    </div>
                                    <div class="mt-4 flex justify-between">
                                        <button class="water-plant bg-blue-500 hover:bg-blue-600 text-white px-3 py-1 rounded text-sm flex items-center" data-plant-id="<?php echo $plant['id']; ?>">
                                            <i class="fas fa-tint mr-1"></i> Vand
                                        </button>
                                        <a href="edit-plant.php?id=<?php echo $plant['id']; ?>" class="bg-gray-200 hover:bg-gray-300 text-gray-700 px-3 py-1 rounded text-sm flex items-center">
                                            <i class="fas fa-edit mr-1"></i> Rediger
                                        </a>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>

                    <!-- Add Plant Card (always shown) -->
                    <a href="add-plants.php" class="bg-white bg-opacity-50 rounded-lg border-2 border-dashed border-gray-300 flex flex-col items-center justify-center h-full min-h-[320px] cursor-pointer hover:bg-opacity-70 transition">
                        <div class="h-16 w-16 rounded-full bg-green-100 flex items-center justify-center mb-3">
                            <i class="fas fa-plus text-green-600 text-xl"></i>
                        </div>
                        <p class="text-gray-600 font-medium">Tilføj ny plante</p>
                        <p class="text-gray-500 text-sm">Klik for at tilføje</p>
                    </a>
                </div>

                <!-- Empty state - shown when no plants -->
                <?php if (count($plants) == 0): ?>
                    <div id="empty-state" class="text-center p-8 bg-white rounded-lg shadow-sm mb-6">
                        <div class="h-24 w-24 rounded-full bg-gray-100 flex items-center justify-center mx-auto mb-4">
                            <i class="fas fa-seedling text-gray-400 text-3xl"></i>
                        </div>
                        <h3 class="text-xl font-semibold text-gray-700 mb-2">Ingen planter endnu</h3>
                        <p class="text-gray-500 mb-4">Du har ikke tilføjet nogen planter endnu. Kom i gang ved at tilføje din første plante!</p>
                        <a href="add-plants.php" class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg transition inline-flex items-center">
                            <i class="fas fa-plus mr-2"></i> Tilføj din første plante
                        </a>
                    </div>
                <?php endif; ?>

                <!-- Plant care tips -->
                <div class="bg-white rounded-lg shadow-sm p-5 mb-6">
                    <div class="flex flex-col md:flex-row items-start md:items-center justify-between mb-4">
                        <h3 class="font-bold text-gray-800 text-lg">Plejetips til dine planter</h3>
                        <a href="#" class="text-green-600 hover:text-green-800 text-sm font-medium flex items-center mt-2 md:mt-0">
                            Se alle tips <i class="fas fa-arrow-right ml-1"></i>
                        </a>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div class="bg-blue-50 p-4 rounded-lg">
                            <div class="flex items-center mb-3">
                                <div class="h-10 w-10 rounded-full bg-blue-100 flex items-center justify-center mr-3">
                                    <i class="fas fa-tint text-blue-600"></i>
                                </div>
                                <h4 class="font-medium">Vandingstips</h4>
                            </div>
                            <p class="text-sm text-gray-600">Kontroller jorden før vanding. Den bør være tør i de øverste 2-3 cm før de fleste stueplanter vandes igen.</p>
                        </div>

                        <div class="bg-yellow-50 p-4 rounded-lg">
                            <div class="flex items-center mb-3">
                                <div class="h-10 w-10 rounded-full bg-yellow-100 flex items-center justify-center mr-3">
                                    <i class="fas fa-sun text-yellow-600"></i>
                                </div>
                                <h4 class="font-medium">Lystips</h4>
                            </div>
                            <p class="text-sm text-gray-600">De fleste stueplanter foretrækker indirekte lys. Placer dem tæt på et vindue, men undgå direkte sollys.</p>
                        </div>

                        <div class="bg-green-50 p-4 rounded-lg">
                            <div class="flex items-center mb-3">
                                <div class="h-10 w-10 rounded-full bg-green-100 flex items-center justify-center mr-3">
                                    <i class="fas fa-leaf text-green-600"></i>
                                </div>
                                <h4 class="font-medium">Gødningstips</h4>
                            </div>
                            <p class="text-sm text-gray-600">Gød dine planter regelmæssigt i vækstsæsonen (forår og sommer) og reducer eller stop helt i vintermånederne.</p>
                        </div>
                    </div>
                </div>

                <!-- Pagination -->
                <?php if (count($plants) > 0): ?>
                    <div class="flex justify-between items-center">
                        <div class="text-sm text-gray-600">
                            Viser <?php echo count($plants); ?> planter
                        </div>
                        <div class="flex space-x-1">
                            <button class="px-3 py-1 bg-white text-gray-600 rounded border border-gray-300 hover:bg-gray-100">
                                <i class="fas fa-chevron-left"></i>
                            </button>
                            <button class="px-3 py-1 bg-green-600 text-white rounded border border-green-600">
                                1
                            </button>
                            <button class="px-3 py-1 bg-white text-gray-600 rounded border border-gray-300 hover:bg-gray-100">
                                <i class="fas fa-chevron-right"></i>
                            </button>
                        </div>
                    </div>
                <?php endif; ?>
            </main>
        </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <div id="delete-modal" class="fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center hidden">
        <div class="bg-white rounded-lg shadow-lg p-6 max-w-md w-full mx-4">
            <div class="text-center mb-4">
                <div class="h-16 w-16 bg-red-100 rounded-full flex items-center justify-center mx-auto mb-4">
                    <i class="fas fa-exclamation-triangle text-red-600 text-xl"></i>
                </div>
                <h3 class="text-xl font-bold text-gray-900 mb-2">Slet plante</h3>
                <p class="text-gray-600">Er du sikker på, at du vil slette denne plante? Dette kan ikke fortrydes.</p>
            </div>
            <div class="flex space-x-4">
                <button id="cancel-delete" class="flex-1 px-4 py-2 bg-gray-200 text-gray-800 rounded-lg hover:bg-gray-300 transition">
                    Annuller
                </button>
                <button id="confirm-delete" class="flex-1 px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 transition">
                    Slet
                </button>
            </div>
            <input type="hidden" id="delete-plant-id" value="">
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

            // Dropdown toggle functionality
            const dropdownToggles = document.querySelectorAll('.dropdown-toggle');

            dropdownToggles.forEach(toggle => {
                toggle.addEventListener('click', function(e) {
                    e.stopPropagation();

                    // Close all other dropdowns first
                    document.querySelectorAll('.dropdown-menu.show').forEach(menu => {
                        if (menu !== this.nextElementSibling) {
                            menu.classList.remove('show');
                        }
                    });

                    // Toggle this dropdown
                    this.nextElementSibling.classList.toggle('show');
                });
            });

            // Close dropdowns when clicking elsewhere
            document.addEventListener('click', function() {
                document.querySelectorAll('.dropdown-menu.show').forEach(menu => {
                    menu.classList.remove('show');
                });
            });

            // Filter dropdown toggle
            const filterBtn = document.getElementById('filter-btn');
            const filterDropdown = document.getElementById('filter-dropdown');

            if (filterBtn && filterDropdown) {
                filterBtn.addEventListener('click', function(e) {
                    e.stopPropagation();
                    filterDropdown.classList.toggle('show');
                });
            }

            // Real-time search functionality
            const searchInput = document.getElementById('search-input');
            const plantsContainer = document.getElementById('plants-container');
            const plantItems = document.querySelectorAll('.plant-item');

            if (searchInput) {
                searchInput.addEventListener('input', function() {
                    const searchTerm = this.value.toLowerCase();

                    // Real-time filtering
                    plantItems.forEach(plant => {
                        const plantName = plant.getAttribute('data-name').toLowerCase();
                        const plantLocation = plant.getAttribute('data-location').toLowerCase();

                        if (plantName.includes(searchTerm) || plantLocation.includes(searchTerm)) {
                            plant.style.display = '';
                        } else {
                            plant.style.display = 'none';
                        }
                    });
                });

                // Submit search on Enter key
                searchInput.addEventListener('keypress', function(e) {
                    if (e.key === 'Enter') {
                        window.location.href = '?search=' + encodeURIComponent(this.value);
                    }
                });
            }

            // Filter select
            const filterSelect = document.getElementById('filter-select');
            if (filterSelect) {
                filterSelect.addEventListener('change', function() {
                    window.location.href = '?filter=' + this.value;
                });
            }

            // View toggle (grid/list)
            const gridViewBtn = document.getElementById('grid-view');
            const listViewBtn = document.getElementById('list-view');

            if (gridViewBtn && listViewBtn && plantsContainer) {
                gridViewBtn.addEventListener('click', function() {
                    plantsContainer.classList.remove('grid-cols-1');
                    plantsContainer.classList.add('md:grid-cols-2', 'lg:grid-cols-3', 'xl:grid-cols-4');

                    gridViewBtn.classList.add('bg-green-500', 'text-white');
                    gridViewBtn.classList.remove('bg-white', 'text-gray-700');

                    listViewBtn.classList.add('bg-white', 'text-gray-700');
                    listViewBtn.classList.remove('bg-green-500', 'text-white');
                });

                listViewBtn.addEventListener('click', function() {
                    plantsContainer.classList.remove('md:grid-cols-2', 'lg:grid-cols-3', 'xl:grid-cols-4');
                    plantsContainer.classList.add('grid-cols-1');

                    listViewBtn.classList.add('bg-green-500', 'text-white');
                    listViewBtn.classList.remove('bg-white', 'text-gray-700');

                    gridViewBtn.classList.add('bg-white', 'text-gray-700');
                    gridViewBtn.classList.remove('bg-green-500', 'text-white');
                });
            }

            // Delete plant functionality
            const deleteButtons = document.querySelectorAll('.delete-plant');
            const deleteModal = document.getElementById('delete-modal');
            const cancelDeleteBtn = document.getElementById('cancel-delete');
            const confirmDeleteBtn = document.getElementById('confirm-delete');
            const deletePlantIdInput = document.getElementById('delete-plant-id');

            deleteButtons.forEach(button => {
                button.addEventListener('click', function(e) {
                    e.preventDefault();
                    const plantId = this.getAttribute('data-plant-id');
                    deletePlantIdInput.value = plantId;
                    deleteModal.classList.remove('hidden');
                });
            });

            if (cancelDeleteBtn) {
                cancelDeleteBtn.addEventListener('click', function() {
                    deleteModal.classList.add('hidden');
                });
            }

            if (confirmDeleteBtn) {
                confirmDeleteBtn.addEventListener('click', function() {
                    const plantId = deletePlantIdInput.value;
                    if (plantId) {
                        window.location.href = 'delete-plant.php?id=' + plantId;
                    }
                });
            }

            // Water plant functionality
            const waterButtons = document.querySelectorAll('.water-plant');

            waterButtons.forEach(button => {
                button.addEventListener('click', function() {
                    const plantId = this.getAttribute('data-plant-id');
                    // You would typically send an AJAX request here to update the watering date
                    // For now, just show a temporary success indicator
                    this.innerHTML = '<i class="fas fa-check mr-1"></i> Vandet';
                    this.classList.remove('bg-blue-500', 'hover:bg-blue-600');
                    this.classList.add('bg-green-500', 'hover:bg-green-600');

                    setTimeout(() => {
                        this.innerHTML = '<i class="fas fa-tint mr-1"></i> Vand';
                        this.classList.remove('bg-green-500', 'hover:bg-green-600');
                        this.classList.add('bg-blue-500', 'hover:bg-blue-600');
                    }, 2000);
                });
            });
        });
    </script>
</body>

</html>