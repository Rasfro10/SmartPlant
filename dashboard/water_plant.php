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

// Process watering request
function mark_as_watered($conn) {
    // Check if plant_id is provided
    if (!isset($_POST['plant_id']) || empty($_POST['plant_id'])) {
        return [
            'success' => false,
            'message' => 'Plant ID is required'
        ];
    }

    // Get plant_id
    $plant_id = (int)$_POST['plant_id'];
    $user_id = $_SESSION['id'];
    
    // Check if plant belongs to user
    $sql = "SELECT id, name FROM plants WHERE id = ? AND user_id = ?";
    if ($stmt = $conn->prepare($sql)) {
        $stmt->bind_param("ii", $plant_id, $user_id);
        
        if ($stmt->execute()) {
            $result = $stmt->get_result();
            
            if ($result->num_rows == 0) {
                return [
                    'success' => false,
                    'message' => 'Plant not found or access denied'
                ];
            }
            
            $plant = $result->fetch_assoc();
        } else {
            return [
                'success' => false,
                'message' => 'Database error'
            ];
        }
        
        $stmt->close();
    } else {
        return [
            'success' => false,
            'message' => 'Database error'
        ];
    }
    
    // Insert watering record in plant_data table
    $water_time = date('Y-m-d H:i:s');
    
    // Check if there's already a record for today
    $sql = "SELECT id FROM plant_data WHERE plant_id = ? AND DATE(watered_at) = DATE(?)";
    $record_exists = false;
    $record_id = null;
    
    if ($stmt = $conn->prepare($sql)) {
        $stmt->bind_param("is", $plant_id, $water_time);
        
        if ($stmt->execute()) {
            $result = $stmt->get_result();
            
            if ($result->num_rows > 0) {
                $record_exists = true;
                $record_row = $result->fetch_assoc();
                $record_id = $record_row['id'];
            }
        }
        
        $stmt->close();
    }
    
    if ($record_exists && $record_id) {
        // Update existing record
        $sql = "UPDATE plant_data SET watered_at = ? WHERE id = ?";
        
        if ($stmt = $conn->prepare($sql)) {
            $stmt->bind_param("si", $water_time, $record_id);
            
            if (!$stmt->execute()) {
                return [
                    'success' => false,
                    'message' => 'Failed to update watering record'
                ];
            }
            
            $stmt->close();
        } else {
            return [
                'success' => false,
                'message' => 'Database error'
            ];
        }
    } else {
        // Create a new record with watering data
        // First check if there's any sensor data for this plant
        $last_data = null;
        $sql = "SELECT soil_moisture, light_level, temperature, humidity, pressure FROM plant_data 
                WHERE plant_id = ? ORDER BY reading_time DESC LIMIT 1";
        
        if ($stmt = $conn->prepare($sql)) {
            $stmt->bind_param("i", $plant_id);
            
            if ($stmt->execute()) {
                $result = $stmt->get_result();
                
                if ($result->num_rows > 0) {
                    $last_data = $result->fetch_assoc();
                }
            }
            
            $stmt->close();
        }
        
        // Insert new record
        if ($last_data) {
            // Use the last known sensor values
            $sql = "INSERT INTO plant_data (plant_id, soil_moisture, light_level, temperature, humidity, pressure, watered_at, reading_time) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
                    
            if ($stmt = $conn->prepare($sql)) {
                $stmt->bind_param("iiddddss", $plant_id, $last_data['soil_moisture'], $last_data['light_level'], 
                               $last_data['temperature'], $last_data['humidity'], $last_data['pressure'], 
                               $water_time, $water_time);
                               
                if (!$stmt->execute()) {
                    return [
                        'success' => false,
                        'message' => 'Failed to create watering record'
                    ];
                }
                
                $stmt->close();
            } else {
                return [
                    'success' => false,
                    'message' => 'Database error'
                ];
            }
        } else {
            // No previous data, just insert watering time
            $sql = "INSERT INTO plant_data (plant_id, watered_at, reading_time) 
                    VALUES (?, ?, ?)";
                    
            if ($stmt = $conn->prepare($sql)) {
                $stmt->bind_param("iss", $plant_id, $water_time, $water_time);
                
                if (!$stmt->execute()) {
                    return [
                        'success' => false,
                        'message' => 'Failed to create watering record'
                    ];
                }
                
                $stmt->close();
            } else {
                return [
                    'success' => false,
                    'message' => 'Database error'
                ];
            }
        }
    }
    
    // Return success
    return [
        'success' => true,
        'message' => 'Plant has been marked as watered',
        'plant_name' => $plant['name'],
        'watered_at' => $water_time
    ];
}

// Handle AJAX request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'water_plant') {
    header('Content-Type: application/json');
    echo json_encode(mark_as_watered($conn));
    exit;
} else {
    // Invalid request
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'message' => 'Invalid request'
    ]);
    exit;
}