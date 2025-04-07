<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/smartplant/backend/auth/session_handler.php';

// Check if user is logged in
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

// Get plant ID from query parameter
$plant_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$user_id = $_SESSION['id'];

// Prepare response array
$response = [
    'success' => false,
    'message' => '',
    'plant' => null
];

if ($plant_id > 0) {
    // Query to get plant details including notes
    $sql = "SELECT * FROM plants WHERE id = ? AND user_id = ?";

    if ($stmt = $conn->prepare($sql)) {
        $stmt->bind_param("ii", $plant_id, $user_id);

        if ($stmt->execute()) {
            $result = $stmt->get_result();

            if ($plant = $result->fetch_assoc()) {
                $response['success'] = true;
                $response['plant'] = $plant;
            } else {
                $response['message'] = 'Plant not found or access denied';
            }
        } else {
            $response['message'] = 'Database error';
        }

        $stmt->close();
    } else {
        $response['message'] = 'Could not prepare statement';
    }
} else {
    $response['message'] = 'Invalid plant ID';
}

// Return JSON response
header('Content-Type: application/json');
echo json_encode($response);
