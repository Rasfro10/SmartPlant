<?php
// Start session
session_start();

// Check if user is logged in
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

// Include database connection
require_once '../../db/db_conn.php';

// Check if plant ID is provided
if (!isset($_GET['plant_id']) || empty($_GET['plant_id'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Plant ID is required']);
    exit;
}

$plant_id = (int)$_GET['plant_id'];
$user_id = $_SESSION['id'];

// Verify that the plant belongs to the user
$sql = "SELECT * FROM plants WHERE id = ? AND user_id = ?";
if ($stmt = $conn->prepare($sql)) {
    $stmt->bind_param("ii", $plant_id, $user_id);

    if ($stmt->execute()) {
        $result = $stmt->get_result();

        if ($result->num_rows == 0) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Plant not found or access denied']);
            exit;
        }

        $plant = $result->fetch_assoc();
    } else {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Database error']);
        exit;
    }

    $stmt->close();
} else {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Database error']);
    exit;
}

// Get latest sensor data
$sql = "SELECT * FROM plant_data WHERE plant_id = ? ORDER BY reading_time DESC LIMIT 1";
$sensor_data = null;

if ($stmt = $conn->prepare($sql)) {
    $stmt->bind_param("i", $plant_id);

    if ($stmt->execute()) {
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $sensor_data = $result->fetch_assoc();
        }
    }

    $stmt->close();
}

// Function to convert raw sensor value to moisture percentage
function convertMoistureToPercentage($rawValue)
{
    // These values are based on your observed sensor readings
    // Dry soil (needs water): 730-800
    // Wet soil: 370-430
    $drySoilMin = 730;
    $drySoilMax = 800;
    $wetSoilMin = 370;
    $wetSoilMax = 430;

    // Ensure the raw value is within the expected range
    $rawValue = max($wetSoilMin, min($drySoilMax, $rawValue));

    // Linear interpolation to convert sensor value to percentage
    if ($rawValue >= $drySoilMin) {
        // Dry soil (0-30%)
        $percentage = map($rawValue, $drySoilMin, $drySoilMax, 0, 30);
    } else {
        // Wet soil (70-100%)
        $percentage = map($rawValue, $wetSoilMin, $drySoilMax, 70, 100);
    }

    return round($percentage, 1);
}

// Function to convert light level to a more meaningful representation
function convertLightToLevel($rawValue) {
    // Adjust the ranges to match your 700-1000 sensor readings
    if ($rawValue < 750) {
        return [
            'level' => 'Lav',
            'percentage' => 30,
            'description' => 'Svagt lys'
        ];
    }
    elseif ($rawValue < 850) {
        return [
            'level' => 'Moderat',
            'percentage' => 50,
            'description' => 'Normalt indendørslys'
        ];
    }
    elseif ($rawValue < 950) {
        return [
            'level' => 'Høj',
            'percentage' => 70,
            'description' => 'Kraftigt lys'
        ];
    }
    else {
        return [
            'level' => 'Meget høj',
            'percentage' => 90,
            'description' => 'Meget kraftigt lys'
        ];
    }
}

// Helper function to map a value from one range to another
function map($value, $inMin, $inMax, $outMin, $outMax)
{
    return ($value - $inMin) * ($outMax - $outMin) / ($inMax - $inMin) + $outMin;
}

// Process sensor data if available
if ($sensor_data) {
    // Convert moisture reading
    $sensor_data['soil_moisture'] = convertMoistureToPercentage($sensor_data['soil_moisture']);

    // Convert light level
    $lightData = convertLightToLevel($sensor_data['light_level']);
    $sensor_data['light_level'] = $lightData['percentage'];
    $sensor_data['light_description'] = $lightData['description'];
}

// Determine plant status
function getPlantStatus($sensorData, $plant)
{
    // Default status if no sensor data
    if (!$sensorData) {
        return [
            'status' => 'unknown',
            'label' => 'Ingen data',
            'class' => 'bg-gray-500'
        ];
    }

    // Check soil moisture (if below 30%, needs water)
    if ($sensorData['soil_moisture'] < 30) {
        return [
            'status' => 'needs_water',
            'label' => 'Behøver vand',
            'class' => 'bg-blue-500'
        ];
    }

    // Check light level based on plant needs
    $lightLevel = $sensorData['light_level'];
    $lightNeeds = $plant['light_needs'];

    // If light level is low and plant needs medium or high light
    if ($lightLevel < 50 && ($lightNeeds == 'Medium' || $lightNeeds == 'Højt')) {
        return [
            'status' => 'needs_light',
            'label' => 'Behøver lys',
            'class' => 'bg-yellow-500'
        ];
    }

    // If all good, plant is healthy
    return [
        'status' => 'healthy',
        'label' => 'Sund',
        'class' => 'bg-green-500'
    ];
}

$status = getPlantStatus($sensor_data, $plant);

// Send response
header('Content-Type: application/json');
echo json_encode([
    'success' => true,
    'plant_id' => $plant_id,
    'sensor_data' => $sensor_data,
    'status' => $status,
    'timestamp' => date('Y-m-d H:i:s')
]);
