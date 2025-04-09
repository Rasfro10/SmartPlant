<?php
// api/rabbitmq_consumer.php

// Include database connection
require_once __DIR__ . '/../db/db_conn.php';

// RabbitMQ connection parameters
$rabbitmq_host = 'localhost';
$rabbitmq_port = 15672; // RabbitMQ Management API port
$rabbitmq_user = 'admin';
$rabbitmq_pass = 'admin';
$rabbitmq_queue = 'plant-data';
$rabbitmq_vhost = '%2F'; // Default vhost (/)

// Function to get plant ID based on sensor pin
function get_plant_id_by_sensor($conn, $pin)
{
    $pin_str = "A" . $pin; // Convert pin number to format "A6" or "A5"
    $sql = "SELECT plant_id FROM plant_sensors WHERE sensor_pin = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $pin_str);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        return $row['plant_id'];
    }

    return null;
}

// Function to fetch a message from RabbitMQ using the Management API
function get_message_from_queue($host, $port, $user, $pass, $vhost, $queue)
{
    $url = "http://$host:$port/api/queues/$vhost/$queue/get";
    $data = json_encode(['count' => 1, 'ackmode' => 'ack_requeue_false', 'encoding' => 'auto']);

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
    curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Content-Length: ' . strlen($data)
    ]);
    curl_setopt($ch, CURLOPT_USERPWD, "$user:$pass");

    $result = curl_exec($ch);

    if (curl_errno($ch)) {
        echo "cURL Error: " . curl_error($ch) . "\n";
        curl_close($ch);
        return null;
    }

    $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($status == 200) {
        $messages = json_decode($result, true);
        if (is_array($messages) && count($messages) > 0) {
            return $messages[0];
        }
    } else {
        echo "HTTP Error: $status - $result\n";
    }

    return null;
}

try {
    // Check if database connection exists
    if (!isset($conn)) {
        die("Error: Database connection not available. Check your db_conn.php file.\n");
    }

    echo "Starting RabbitMQ consumer for queue: $rabbitmq_queue\n";

    // Loop indefinitely, checking for messages
    while (true) {
        $message = get_message_from_queue(
            $rabbitmq_host,
            $rabbitmq_port,
            $rabbitmq_user,
            $rabbitmq_pass,
            $rabbitmq_vhost,
            $rabbitmq_queue
        );

        if ($message && isset($message['payload'])) {
            $body = $message['payload'];
            echo "Received message: " . $body . "\n";

            // Parse the JSON data
            $data = json_decode($body, true);

            if ($data === null) {
                echo "Error: Invalid JSON data received\n";
                continue;
            }

            // Get the pin number from the data (default to 6 if not specified)
            $pin_number = isset($data['pin']) ? $data['pin'] : 6;

            // Get plant_id based on sensor pin
            $plant_id = get_plant_id_by_sensor($conn, $pin_number);

            if (!$plant_id) {
                echo "No plant found for sensor pin A$pin_number, skipping this reading\n";
                continue;
            }

            try {
                // Initialize variables
                $light_level = isset($data['light_level']) ? $data['light_level'] : null;
                $has_watering = isset($data['watered_at']) && !empty($data['watered_at']);
                
                // Build the SQL query 
                if ($has_watering) {
                    $sql = "INSERT INTO plant_data 
                            (plant_id, soil_moisture, light_level, temperature, humidity, pressure, timestamp, watered_at) 
                            VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
                    
                    // Convert Unix timestamp to MySQL datetime format
                    $watered_date = date('Y-m-d H:i:s', $data['watered_at']);
                    echo "Watering event detected, converted timestamp: $watered_date\n";
                    
                    $stmt = $conn->prepare($sql);
                    $stmt->bind_param(
                        "iidddiss",
                        $plant_id,
                        $data['moisture'],
                        $light_level,
                        $data['temperature'],
                        $data['humidity'],
                        $data['pressure'],
                        $data['timestamp'],
                        $watered_date
                    );
                } else {
                    $sql = "INSERT INTO plant_data 
                            (plant_id, soil_moisture, light_level, temperature, humidity, pressure, timestamp) 
                            VALUES (?, ?, ?, ?, ?, ?, ?)";
                    
                    $stmt = $conn->prepare($sql);
                    $stmt->bind_param(
                        "iiddddi",
                        $plant_id,
                        $data['moisture'],
                        $light_level,
                        $data['temperature'],
                        $data['humidity'],
                        $data['pressure'],
                        $data['timestamp']
                    );
                }

                // Execute the query
                if ($stmt->execute()) {
                    echo "Data saved to database successfully for plant ID: $plant_id\n";
                    if ($has_watering) {
                        echo "Watering event recorded in database at: " . $watered_date . "\n";
                    }
                } else {
                    echo "Database error: " . $stmt->error . "\n";
                }
            } catch (Exception $e) {
                echo "Database error: " . $e->getMessage() . "\n";
            }
        } else {
            // No message found, wait a bit before trying again
            sleep(2);
        }
    }
} catch (Exception $e) {
    die("Error: " . $e->getMessage() . "\n");
}