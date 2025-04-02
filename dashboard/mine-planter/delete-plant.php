<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/smartplant/backend/auth/session_handler.php';

// Check if plant ID is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    // Redirect back to plants list if no ID is provided
    header("Location: index.php");
    exit;
}

$plant_id = (int)$_GET['id'];
$user_id = $_SESSION['id'];
$success = false;
$message = "";

// First verify that the plant belongs to the current user
$sql = "SELECT id FROM plants WHERE id = ? AND user_id = ?";

if ($stmt = $conn->prepare($sql)) {
    $stmt->bind_param("ii", $plant_id, $user_id);

    if ($stmt->execute()) {
        $stmt->store_result();

        if ($stmt->num_rows > 0) {
            // The plant exists and belongs to this user, proceed with deletion
            $stmt->close();

            // Prepare delete statement
            $delete_sql = "DELETE FROM plants WHERE id = ? AND user_id = ?";

            if ($delete_stmt = $conn->prepare($delete_sql)) {
                $delete_stmt->bind_param("ii", $plant_id, $user_id);

                if ($delete_stmt->execute()) {
                    $success = true;
                    $message = "Planten er blevet slettet.";
                } else {
                    $message = "Der opstod en fejl ved sletning af planten: " . $delete_stmt->error;
                }

                $delete_stmt->close();
            } else {
                $message = "Der opstod en fejl ved forberedelse af sletnings-forespørgslen.";
            }
        } else {
            $message = "Planten findes ikke eller tilhører ikke dig.";
        }
    } else {
        $message = "Der opstod en fejl ved forespørgslen: " . $stmt->error;
    }

    if (isset($stmt) && $stmt instanceof mysqli_stmt) {
        $stmt->close();
    }
}

// Redirect back with status message
header("Location: index.php?status=" . ($success ? "success" : "error") . "&message=" . urlencode($message));
exit;
