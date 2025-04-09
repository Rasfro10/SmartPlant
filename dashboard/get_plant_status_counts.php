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
require_once '../db/db_conn.php';

// Initialize counters
$needs_water = 0;
$needs_light = 0; 
$healthy = 0;
$total = 0;

// Get user's plants
$user_id = $_SESSION['id'];
$sql = "SELECT p.id, p.light_needs FROM plants p WHERE p.user_id = ?";

if ($stmt = $conn->prepare($sql)) {
    $stmt->bind_param("i", $user_id);
    
    if ($stmt->execute()) {
        $result = $stmt->get_result();
        $plants = [];
        
        while ($plant = $result->fetch_assoc()) {
            $plants[] = $plant;
        }
        
        $total = count($plants);
        
        // For each plant, get its latest sensor data and determine its status
        foreach ($plants as $plant) {
            $plant_id = $plant['id'];
            
            // Get latest sensor data
            $data_sql = "SELECT soil_moisture, light_level FROM plant_data 
                        WHERE plant_id = ? 
                        ORDER BY reading_time DESC LIMIT 1";
                        
            if ($data_stmt = $conn->prepare($data_sql)) {
                $data_stmt->bind_param("i", $plant_id);
                
                if ($data_stmt->execute()) {
                    $data_result = $data_stmt->get_result();
                    
                    if ($data_result->num_rows > 0) {
                        $sensor_data = $data_result->fetch_assoc();
                        
                        // Check moisture (if below 30%, needs water)
                        if ($sensor_data['soil_moisture'] < 30) {
                            $needs_water++;
                        }
                        // Check light (if below 30 and needs medium/high, needs light)
                        elseif ($sensor_data['light_level'] < 30 && 
                                ($plant['light_needs'] == 'Medium' || $plant['light_needs'] == 'Højt')) {
                            $needs_light++;
                        }
                        // Otherwise healthy
                        else {
                            $healthy++;
                        }
                    } else {
                        // No sensor data, count as healthy
                        $healthy++;
                    }
                } else {
                    // Error executing, count as healthy
                    $healthy++;
                }
                
                $data_stmt->close();
            } else {
                // Error preparing, count as healthy
                $healthy++;
            }
        }
    }
    
    $stmt->close();
}

// Return the counts as JSON
header('Content-Type: application/json');
echo json_encode([
    'success' => true,
    'needs_water' => $needs_water,
    'needs_light' => $needs_light,
    'healthy' => $healthy,
    'total' => $total
]);