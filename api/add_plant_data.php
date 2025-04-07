<?php
// Allow cross-origin requests (needed for IoT devices)
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Max-Age: 3600");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

// Include the database connection
require_once '../db/db_conn.php';

// Get the raw POST data
$json_data = file_get_contents('php://input');

// Only allow POST requests
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    http_response_code(405); // Method Not Allowed
    echo json_encode(array("message" => "Method not allowed. Please use POST."));
    exit;
}

// Get posted data
$data = json_decode($json_data, true);

// Check if JSON parsing was successful
if ($data === null) {
    http_response_code(400); // Bad Request
    echo json_encode(array("message" => "Invalid JSON data."));
    exit;
}

// Check if the API key is valid
$api_key = isset($data['api_key']) ? $data['api_key'] : '';
if ($api_key !== "data") {  // Make sure this matches the key in your Arduino code
    http_response_code(401); // Unauthorized
    echo json_encode(array("message" => "Invalid API key."));
    exit;
}

// Validate required fields
if (!isset($data['plant_id']) || !is_numeric($data['plant_id'])) {
    http_response_code(400); // Bad Request
    echo json_encode(array("message" => "Invalid or missing plant_id."));
    exit;
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
    } else {
        http_response_code(500); // Internal Server Error
        echo json_encode(array("message" => "Error: " . $stmt->error));
    }

    $stmt->close();
} else {
    http_response_code(500); // Internal Server Error
    echo json_encode(array("message" => "Error: Unable to prepare statement."));
}

$conn->close();
