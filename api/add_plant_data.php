<?php
// Allow cross-origin requests (needed for IoT devices)
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Max-Age: 3600");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

// Include the database connection
require_once '../db/db_conn.php';

// Create a log file for debugging
$log_file = '../logs/arduino_log.txt';
file_put_contents($log_file, date('Y-m-d H:i:s') . " - API called\n", FILE_APPEND);

// Get the raw POST data
$json_data = file_get_contents('php://input');
file_put_contents($log_file, date('Y-m-d H:i:s') . " - Received data: " . $json_data . "\n", FILE_APPEND);

// Only allow POST requests
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    http_response_code(405); // Method Not Allowed
    echo json_encode(array("message" => "Method not allowed. Please use POST."));
    file_put_contents($log_file, date('Y-m-d H:i:s') . " - Error: Method not allowed\n", FILE_APPEND);
    exit;
}

// Get posted data
$data = json_decode($json_data, true);

// Check if JSON parsing was successful
if ($data === null) {
    http_response_code(400); // Bad Request
    echo json_encode(array("message" => "Invalid JSON data."));
    file_put_contents($log_file, date('Y-m-d H:i:s') . " - Error: Invalid JSON data\n", FILE_APPEND);
    exit;
}

// Check if the API key is valid
$api_key = isset($data['api_key']) ? $data['api_key'] : '';
if ($api_key !== "data") {  // Make sure this matches the key in your Arduino code
    http_response_code(401); // Unauthorized
    echo json_encode(array("message" => "Invalid API key."));
    file_put_contents($log_file, date('Y-m-d H:i:s') . " - Error: Invalid API key\n", FILE_APPEND);
    exit;
}

// Validate required fields
if (!isset($data['plant_id']) || !is_numeric($data['plant_id'])) {
    http_response_code(400); // Bad Request
    echo json_encode(array("message" => "Invalid or missing plant_id."));
    file_put_contents($log_file, date('Y-m-d H:i:s') . " - Error: Invalid plant_id\n", FILE_APPEND);
    exit;
}

// Check if the plant exists in the database
$plant_id = (int)$data['plant_id'];
$check_sql = "SELECT id FROM plants WHERE id = ?";

if ($check_stmt = $conn->prepare($check_sql)) {
    $check_stmt->bind_param("i", $plant_id);
    $check_stmt->execute();
    $check_stmt->store_result();

    if ($check_stmt->num_rows == 0) {
        http_response_code(404); // Not Found
        echo json_encode(array("message" => "Plant ID not found."));
        file_put_contents($log_file, date('Y-m-d H:i:s') . " - Error: Plant ID not found\n", FILE_APPEND);
        $check_stmt->close();
        exit;
    }
    $check_stmt->close();
}

// Prepare the SQL statement
$sql = "INSERT INTO plant_data 
        (plant_id, soil_moisture, light_level, temperature, humidity, battery_level) 
        VALUES (?, ?, ?, ?, ?, ?)";

if ($stmt = $conn->prepare($sql)) {
    // Set parameters
    $plant_id = (int)$data['plant_id'];
    $soil_moisture = isset($data['soil_moisture']) ? (float)$data['soil_moisture'] : null;
    $light_level = isset($data['light_level']) ? (float)$data['light_level'] : null;
    $temperature = isset($data['temperature']) ? (float)$data['temperature'] : null;
    $humidity = isset($data['humidity']) ? (float)$data['humidity'] : null;
    $battery_level = isset($data['battery_level']) ? (float)$data['battery_level'] : null;

    // Bind parameters
    $stmt->bind_param("iddddd", $plant_id, $soil_moisture, $light_level, $temperature, $humidity, $battery_level);

    // Execute statement
    if ($stmt->execute()) {
        http_response_code(201); // Created
        $response = array(
            "message" => "Data saved successfully.",
            "data_id" => $conn->insert_id
        );
        echo json_encode($response);
        file_put_contents($log_file, date('Y-m-d H:i:s') . " - Success: Data saved, ID " . $conn->insert_id . "\n", FILE_APPEND);
    } else {
        http_response_code(500); // Internal Server Error
        $response = array("message" => "Error: " . $stmt->error);
        echo json_encode($response);
        file_put_contents($log_file, date('Y-m-d H:i:s') . " - Error: DB insert failed - " . $stmt->error . "\n", FILE_APPEND);
    }

    $stmt->close();
} else {
    http_response_code(500); // Internal Server Error
    $response = array("message" => "Error: Unable to prepare statement.");
    echo json_encode($response);
    file_put_contents($log_file, date('Y-m-d H:i:s') . " - Error: Prepare statement failed\n", FILE_APPEND);
}

$conn->close();
