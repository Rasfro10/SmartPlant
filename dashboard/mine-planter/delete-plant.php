<?php
// Enable detailed error reporting for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Start or resume the session
session_start();

// Check if user is logged in
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    // Redirect to login page if not logged in
    header("Location: ../../login/index.php");
    exit;
}

// Include database connection
require_once $_SERVER['DOCUMENT_ROOT'] . '/smartplant/db/db_conn.php';

// Log file path (ensure the directory exists and is writable)
$log_file = $_SERVER['DOCUMENT_ROOT'] . '/smartplant/logs/plant_deletion.log';

/**
 * Log error messages to a file
 * @param string $message Error message to log
 */
function logError($message)
{
    global $log_file;
    $timestamp = date('[Y-m-d H:i:s]');
    $log_message = $timestamp . " " . $message . PHP_EOL;

    // Attempt to write to log file
    error_log($log_message, 3, $log_file);
}

// Initialize response variables
$success = false;
$message = "";

try {
    // Validate and sanitize plant ID
    if (!isset($_GET['id']) || empty($_GET['id'])) {
        throw new Exception("Intet plant-ID angivet.");
    }

    // Sanitize the plant ID
    $plant_id = filter_var($_GET['id'], FILTER_VALIDATE_INT);

    if ($plant_id === false || $plant_id === null) {
        throw new Exception("Ugyldigt plant-ID.");
    }

    // Get the current user's ID
    $user_id = $_SESSION['id'];

    // Start a database transaction
    $conn->begin_transaction();

    // First, verify the plant belongs to the current user
    $verify_sql = "SELECT id FROM plants WHERE id = ? AND user_id = ?";
    $verify_stmt = $conn->prepare($verify_sql);

    if (!$verify_stmt) {
        throw new Exception("Kunne ikke forberede verificeringsforespørgsel: " . $conn->error);
    }

    $verify_stmt->bind_param("ii", $plant_id, $user_id);

    if (!$verify_stmt->execute()) {
        throw new Exception("Kunne ikke udføre verificeringsforespørgsel: " . $verify_stmt->error);
    }

    $verify_stmt->store_result();

    if ($verify_stmt->num_rows === 0) {
        throw new Exception("Planten eksisterer ikke eller tilhører ikke dig.");
    }

    $verify_stmt->close();

    // Delete associated sensor data
    $delete_sensor_sql = "DELETE FROM plant_sensors WHERE plant_id = ?";
    $delete_sensor_stmt = $conn->prepare($delete_sensor_sql);

    if (!$delete_sensor_stmt) {
        throw new Exception("Kunne ikke forberede sensor-sletningsforespørgsel: " . $conn->error);
    }

    $delete_sensor_stmt->bind_param("i", $plant_id);

    if (!$delete_sensor_stmt->execute()) {
        throw new Exception("Kunne ikke slette plantens sensorer: " . $delete_sensor_stmt->error);
    }

    $delete_sensor_stmt->close();

    // Delete plant data records
    $delete_data_sql = "DELETE FROM plant_data WHERE plant_id = ?";
    $delete_data_stmt = $conn->prepare($delete_data_sql);

    if (!$delete_data_stmt) {
        throw new Exception("Kunne ikke forberede plant data-sletningsforespørgsel: " . $conn->error);
    }

    $delete_data_stmt->bind_param("i", $plant_id);

    if (!$delete_data_stmt->execute()) {
        throw new Exception("Kunne ikke slette plantens data: " . $delete_data_stmt->error);
    }

    $delete_data_stmt->close();

    // Delete the plant record
    $delete_plant_sql = "DELETE FROM plants WHERE id = ? AND user_id = ?";
    $delete_plant_stmt = $conn->prepare($delete_plant_sql);

    if (!$delete_plant_stmt) {
        throw new Exception("Kunne ikke forberede plant-sletningsforespørgsel: " . $conn->error);
    }

    $delete_plant_stmt->bind_param("ii", $plant_id, $user_id);

    if (!$delete_plant_stmt->execute()) {
        throw new Exception("Kunne ikke slette planten: " . $delete_plant_stmt->error);
    }

    $delete_plant_stmt->close();

    // Commit the transaction
    $conn->commit();

    // Deletion successful
    $success = true;
    $message = "Planten er succesfuldt slettet.";

    // Optional: Log successful deletion
    logError("Plant ID {$plant_id} deleted successfully by User ID {$user_id}");
} catch (Exception $e) {
    // Rollback the transaction in case of any error
    $conn->rollback();

    // Set error message
    $message = $e->getMessage();

    // Log the full error details
    logError("Plant deletion error for Plant ID {$plant_id}: " . $e->getMessage());
    logError("Stack trace: " . $e->getTraceAsString());
}

// Close the database connection
$conn->close();

// Store the message in the session
$_SESSION['message'] = $message;
$_SESSION['message_type'] = $success ? 'success' : 'error';

// Redirect back to the plants list
header("Location: index.php");
exit;
