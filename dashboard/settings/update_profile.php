<?php
// Start session
session_start();

// Check if user is logged in
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("location: /smartplant/login/");
    exit;
}

// Require database connection
require_once $_SERVER['DOCUMENT_ROOT'] . '/smartplant/db/db_conn.php';

// Include security functions
require_once $_SERVER['DOCUMENT_ROOT'] . '/smartplant/includes/security.php';

// CSRF protection
if (!isset($_POST['csrf_token']) || !verify_csrf_token($_POST['csrf_token'])) {
    $_SESSION['profile_error'] = "Ugyldig anmodning. Prøv igen.";
    header("location: /smartplant/dashboard/settings/");
    exit;
}

// Validate form data
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Get user ID from session
    $user_id = $_SESSION["id"];
    
    // Get and sanitize form data
    $firstname = sanitize_input($_POST["firstname"]);
    $lastname = sanitize_input($_POST["lastname"]);
    $email = sanitize_input($_POST["email"]);
    
    // Validate name fields
    if (empty($firstname) || empty($lastname)) {
        $_SESSION['profile_error'] = "Fornavn og efternavn skal udfyldes.";
        header("location: /smartplant/dashboard/settings/");
        exit;
    }
    
    // Validate email
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $_SESSION['profile_error'] = "Angiv en gyldig e-mailadresse.";
        header("location: /smartplant/dashboard/settings/");
        exit;
    }
    
    // Check if email already exists for a different user
    $check_email_sql = "SELECT id FROM users WHERE email = ? AND id != ?";
    $check_email_stmt = $conn->prepare($check_email_sql);
    $check_email_stmt->bind_param("si", $email, $user_id);
    $check_email_stmt->execute();
    $check_email_stmt->store_result();
    
    if ($check_email_stmt->num_rows > 0) {
        $_SESSION['profile_error'] = "E-mailadressen er allerede i brug.";
        $check_email_stmt->close();
        header("location: /smartplant/dashboard/settings/");
        exit;
    }
    $check_email_stmt->close();
    
    // Update user data in the database
    $update_sql = "UPDATE users SET firstname = ?, lastname = ?, email = ? WHERE id = ?";
    
    if ($update_stmt = $conn->prepare($update_sql)) {
        $update_stmt->bind_param("sssi", $firstname, $lastname, $email, $user_id);
        
        if ($update_stmt->execute()) {
            // Update email in session if it changed
            if ($_SESSION["email"] !== $email) {
                $_SESSION["email"] = $email;
            }
            
            // Log the profile update
            log_auth_event($user_id, "Profile Update", "Profile information updated");
            
            $_SESSION['profile_success'] = "Dine profiloplysninger er blevet opdateret.";
        } else {
            $_SESSION['profile_error'] = "Der opstod en fejl. Prøv igen senere.";
        }
        
        $update_stmt->close();
    } else {
        $_SESSION['profile_error'] = "Der opstod en fejl. Prøv igen senere.";
    }
    
    // Rotate CSRF token for security
    rotate_csrf_token();
    
    // Redirect back to settings page
    header("location: /smartplant/dashboard/settings/");
    exit;
} else {
    // Not a POST request, redirect to settings page
    header("location: /smartplant/dashboard/settings/");
    exit;
}