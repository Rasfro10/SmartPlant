<?php
// Check if session is already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if the user is logged in
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("location: ../login/");
    exit;
}

// Include database connection if not already included
if (!isset($conn)) {
    require_once __DIR__ . '/../db/db_conn.php';
}

// Initialize variables
$firstname = $lastname = $email = $initials = "";

// Get user data from database
if (isset($_SESSION["id"])) {
    $sql = "SELECT firstname, lastname, email FROM users WHERE id = ?";

    if ($stmt = $conn->prepare($sql)) {
        // Bind variables to the prepared statement as parameters
        $stmt->bind_param("i", $param_id);

        // Set parameters
        $param_id = $_SESSION["id"];

        // Attempt to execute the prepared statement
        if ($stmt->execute()) {
            // Store result
            $stmt->store_result();

            // Check if user exists
            if ($stmt->num_rows == 1) {
                // Bind result variables
                $stmt->bind_result($firstname, $lastname, $email);
                if ($stmt->fetch()) {
                    // Create initials from first letter of firstname and lastname
                    $initials = substr($firstname, 0, 1) . substr($lastname, 0, 1);
                }
            }
        }

        // Close statement
        $stmt->close();
    }
}

// If $page is not set, default to empty string
if (!isset($page)) {
    $page = "";
}
?>

<!-- Sidebar -->
<aside id="sidebar" class="sidebar bg-green-800 text-white w-64 min-h-screen flex-shrink-0 absolute md:relative z-30">
    <div class="p-4 flex items-center border-b border-green-700">
        <i class="fas fa-leaf text-2xl mr-3"></i>
        <h1 class="text-xl font-bold">Smart Plant</h1>
    </div>

    <div class="p-4">
        <div class="flex items-center space-x-3 mb-6 pb-4 border-b border-green-700">
            <div class="h-10 w-10 rounded-full bg-green-600 flex items-center justify-center">
                <span class="font-bold"><?php echo htmlspecialchars($initials); ?></span>
            </div>
            <div>
                <p class="font-medium"><?php echo htmlspecialchars($firstname . ' ' . $lastname); ?></p>
                <p class="text-xs text-green-300"><?php echo htmlspecialchars($email); ?></p>
            </div>
        </div>

        <nav>
            <ul class="space-y-2">
                <li>
                    <a href="<?= $base ?>dashboard/" class="flex items-center space-x-3 p-3 rounded-lg <?php echo $page === 'dashboard' ? 'bg-green-700 font-medium' : 'hover:bg-green-700 transition-colors'; ?>">
                        <i class="fas fa-tachometer-alt"></i>
                        <span>Dashboard</span>
                    </a>
                </li>
                <li>
                    <a href="<?= $base ?>dashboard/mine-planter/" class="flex items-center space-x-3 p-3 rounded-lg <?php echo $page === 'myPlants' ? 'bg-green-700 font-medium' : 'hover:bg-green-700 transition-colors'; ?>">
                        <i class="fas fa-seedling"></i>
                        <span>Mine Planter</span>
                    </a>
                </li>
                <li>
                    <a href="<?= $base ?>dashboard/statistik/" class="flex items-center space-x-3 p-3 rounded-lg <?php echo $page === 'statistics' ? 'bg-green-700 font-medium' : 'hover:bg-green-700 transition-colors'; ?>">
                        <i class="fas fa-chart-bar"></i>
                        <span>Statistik</span>
                    </a>
                </li>
            </ul>
        </nav>

        <div class="mt-10 pt-6 border-t border-green-700">
            <ul class="space-y-2">
                <li>
                    <a href="<?= $base ?>dashboard/settings/" class="flex items-center space-x-3 p-3 rounded-lg <?php echo $page === 'settings' ? 'bg-green-700 font-medium' : 'hover:bg-green-700 transition-colors'; ?>">
                        <i class="fas fa-cog"></i>
                        <span>Indstillinger</span>
                    </a>
                </li>
                <li>
                    <a href="<?= $base ?>dashboard/hjaelp/" class="flex items-center space-x-3 p-3 rounded-lg <?php echo $page === 'help' ? 'bg-green-700 font-medium' : 'hover:bg-green-700 transition-colors'; ?>">
                        <i class="fas fa-question-circle"></i>
                        <span>Hjælp</span>
                    </a>
                </li>
                <li>
                    <a href="<?= $base ?>backend/auth/logout.php" class="flex items-center space-x-3 p-3 rounded-lg hover:bg-green-700 transition-colors">
                        <i class="fas fa-sign-out-alt"></i>
                        <span>Log ud</span>
                    </a>
                </li>
            </ul>
        </div>
    </div>
</aside>