<?php
// This file can be run as a cron job or included in key pages to check for plants needing water

// Include database connection
require_once __DIR__ . '/../../db/db_conn.php';

/**
 * Generates water notifications for plants that need watering based on sensor data
 * Returns the number of notifications created
 */
function generateWaterNotifications() {
    global $conn;
    $notificationsCreated = 0;
    
    // Get all plants with water notifications enabled and their latest sensor data
    $sql = "SELECT p.id, p.name, p.user_id, p.water_notification, 
                   pd.soil_moisture, pd.reading_time
            FROM plants p
            LEFT JOIN (
                SELECT pd1.*
                FROM plant_data pd1
                INNER JOIN (
                    SELECT plant_id, MAX(reading_time) as max_time
                    FROM plant_data
                    GROUP BY plant_id
                ) pd2 ON pd1.plant_id = pd2.plant_id AND pd1.reading_time = pd2.max_time
            ) pd ON p.id = pd.plant_id
            WHERE p.water_notification = 'on'";
            
    $result = $conn->query($sql);
    
    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            // Only proceed if sensor data exists and soil moisture is below 30%
            if ($row['soil_moisture'] !== null) {
                // Convert raw moisture value to percentage (similar to get_sensor_data.php)
                $moistureValue = $row['soil_moisture'];
                $moisturePercentage = convertMoistureToPercentage($moistureValue);
                
                if ($moisturePercentage < 30) {
                    // Check if ANY notification exists for this plant in the last 3 hours 
                    // regardless of whether it was read or not
                    // This prevents immediate new notifications after marking as read
                    $checkSql = "SELECT id FROM notifications 
                                WHERE plant_id = ? 
                                AND notification_type = 'water' 
                                AND created_at > DATE_SUB(NOW(), INTERVAL 3 HOUR)";
                    
                    $checkStmt = $conn->prepare($checkSql);
                    $checkStmt->bind_param("i", $row['id']);
                    $checkStmt->execute();
                    $checkResult = $checkStmt->get_result();
                    
                    // If no notification (read or unread) exists in the last 3 hours, create one
                    if ($checkResult->num_rows == 0) {
                        $message = "Din plante \"" . $row['name'] . "\" trænger til vand.";
                        
                        $insertSql = "INSERT INTO notifications 
                                     (plant_id, notification_type, message, is_read) 
                                     VALUES (?, 'water', ?, 'no')";
                        
                        $insertStmt = $conn->prepare($insertSql);
                        $insertStmt->bind_param("is", $row['id'], $message);
                        
                        if ($insertStmt->execute()) {
                            $notificationsCreated++;
                        }
                        
                        $insertStmt->close();
                    }
                    
                    $checkStmt->close();
                }
            }
        }
    }
    
    return $notificationsCreated;
}

/**
 * Helper function to convert raw moisture value to percentage
 */
function convertMoistureToPercentage($rawValue) {
    // These values should match those in get_sensor_data.php
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
        // Wet soil (30-100%)
        $percentage = map($rawValue, $wetSoilMin, $drySoilMax, 70, 100);
    }

    return round($percentage, 1);
}

/**
 * Helper function to map a value from one range to another
 */
function map($value, $inMin, $inMax, $outMin, $outMax) {
    return ($value - $inMin) * ($outMax - $outMin) / ($inMax - $inMin) + $outMin;
}

// When this file is called directly, generate notifications
if (basename(__FILE__) == basename($_SERVER["SCRIPT_FILENAME"])) {
    generateWaterNotifications();
}
?>