<?php

/**
 * This script should be run periodically to generate notifications for plants that need watering
 * You can set up a cron job to run this script daily
 * 
 * Example cron job:
 * 0 8 * * * php /path/to/smartplant/generate-notifications.php
 */

// If running via command line, set the document root
if (php_sapi_name() === 'cli') {
    // Adjust this path to match your server setup
    $_SERVER['DOCUMENT_ROOT'] = dirname(__DIR__, 1);
}

// Include database connection
require_once $_SERVER['DOCUMENT_ROOT'] . '/smartplant/db/db_conn.php';

// Function to create a new notification
function create_notification($conn, $plant_id, $type = 'water', $message = null, $scheduled_for = null)
{
    // If no message is provided, create a default one based on type
    if ($message === null) {
        switch ($type) {
            case 'water':
                $message = 'Din plante trænger til vand!';
                break;
            case 'fertilize':
                $message = 'Det er tid til at gøde din plante.';
                break;
            case 'repot':
                $message = 'Din plante skal snart omplantes.';
                break;
            default:
                $message = 'Din plante kræver opmærksomhed.';
        }
    }

    // If no scheduled date is provided, use current time
    if ($scheduled_for === null) {
        $scheduled_for = date('Y-m-d H:i:s');
    }

    // Prepare and execute query
    $sql = "INSERT INTO notifications (plant_id, notification_type, message, scheduled_for) 
            VALUES (?, ?, ?, ?)";

    if ($stmt = $conn->prepare($sql)) {
        $stmt->bind_param("isss", $plant_id, $type, $message, $scheduled_for);
        $result = $stmt->execute();
        $stmt->close();
        return $result;
    }

    return false;
}

// Find plants that need watering based on their watering frequency
function check_plants_needing_water($conn)
{
    $plants_to_notify = [];
    $total_notifications = 0;

    // Get all plants with water_notification enabled
    $sql = "SELECT p.id, p.name, p.watering_frequency, u.email, 
            (SELECT MAX(n.scheduled_for) FROM notifications n WHERE n.plant_id = p.id AND n.notification_type = 'water') as last_notification
            FROM plants p
            JOIN users u ON p.user_id = u.id
            WHERE p.water_notification = 'on'";

    if ($stmt = $conn->prepare($sql)) {
        $stmt->execute();
        $result = $stmt->get_result();

        while ($plant = $result->fetch_assoc()) {
            $should_notify = false;
            $days_since_last = PHP_INT_MAX;

            // If there's no previous notification, we should notify
            if ($plant['last_notification'] === null) {
                $should_notify = true;
            } else {
                // Calculate days since last notification
                $last_notification = new DateTime($plant['last_notification']);
                $today = new DateTime();
                $days_since_last = $today->diff($last_notification)->days;

                // Determine if we should notify based on watering frequency
                switch ($plant['watering_frequency']) {
                    case 'Daglig':
                        $should_notify = $days_since_last >= 1;
                        break;
                    case 'Hver 2-3 dag':
                        $should_notify = $days_since_last >= 2;
                        break;
                    case 'Ugentlig':
                        $should_notify = $days_since_last >= 7;
                        break;
                    case 'Hver 2. uge':
                        $should_notify = $days_since_last >= 14;
                        break;
                    case 'Månedlig':
                        $should_notify = $days_since_last >= 30;
                        break;
                    default:
                        // If no watering frequency is set, default to weekly
                        $should_notify = $days_since_last >= 7;
                }
            }

            if ($should_notify) {
                $message = "Din plante '{$plant['name']}' trænger til vand!";

                // Create notification
                if (create_notification($conn, $plant['id'], 'water', $message)) {
                    $plants_to_notify[] = [
                        'id' => $plant['id'],
                        'name' => $plant['name'],
                        'email' => $plant['email']
                    ];
                    $total_notifications++;
                }
            }
        }

        $stmt->close();
    }

    return [
        'plants' => $plants_to_notify,
        'total' => $total_notifications
    ];
}

// Run the check
$result = check_plants_needing_water($conn);

// Output results if running from command line
if (php_sapi_name() === 'cli') {
    echo "Generated {$result['total']} watering notifications\n";

    if ($result['total'] > 0) {
        echo "Plants that need watering:\n";
        foreach ($result['plants'] as $plant) {
            echo "- {$plant['name']} (User: {$plant['email']})\n";
        }
    }
}

// Close database connection
$conn->close();
