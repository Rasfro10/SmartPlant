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
    $_SESSION['password_error'] = "Ugyldig anmodning. Prøv igen.";
    header("location: /smartplant/dashboard/settings/");
    exit;
}

// Validate form data
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Get user ID from session
    $user_id = $_SESSION["id"];
    
    // Get form data
    $current_password = $_POST["current_password"];
    $new_password = $_POST["new_password"];
    $confirm_password = $_POST["confirm_password"];
    
    // Validate password fields are not empty
    if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
        $_SESSION['password_error'] = "Alle felter skal udfyldes.";
        header("location: /smartplant/dashboard/settings/");
        exit;
    }
    
    // Validate new password
    if (strlen($new_password) < 8) {
        $_SESSION['password_error'] = "Din nye adgangskode skal være mindst 8 tegn lang.";
        header("location: /smartplant/dashboard/settings/");
        exit;
    }
    
    // Check if new password contains both letters and numbers
    if (!preg_match('/[A-Za-z]/', $new_password) || !preg_match('/[0-9]/', $new_password)) {
        $_SESSION['password_error'] = "Din nye adgangskode skal indeholde både bogstaver og tal.";
        header("location: /smartplant/dashboard/settings/");
        exit;
    }
    
    // Check if new passwords match
    if ($new_password !== $confirm_password) {
        $_SESSION['password_error'] = "De nye adgangskoder stemmer ikke overens.";
        header("location: /smartplant/dashboard/settings/");
        exit;
    }
    
    // Verify current password
    $sql = "SELECT password FROM users WHERE id = ?";
    
    if ($stmt = $conn->prepare($sql)) {
        $stmt->bind_param("i", $user_id);
        
        if ($stmt->execute()) {
            $stmt->store_result();
            
            if ($stmt->num_rows == 1) {
                $stmt->bind_result($hashed_password);
                $stmt->fetch();
                
                if (!password_verify($current_password, $hashed_password)) {
                    $_SESSION['password_error'] = "Den nuværende adgangskode er forkert.";
                    $stmt->close();
                    header("location: /smartplant/dashboard/settings/");
                    exit;
                }
            }
        }
        
        $stmt->close();
    }
    
    // Hash the new password
    $hashed_new_password = password_hash($new_password, PASSWORD_DEFAULT);
    
    // Update password in the database
    $update_sql = "UPDATE users SET password = ? WHERE id = ?";
    
    if ($update_stmt = $conn->prepare($update_sql)) {
        $update_stmt->bind_param("si", $hashed_new_password, $user_id);
        
        if ($update_stmt->execute()) {
            // Log the password change
            log_auth_event($user_id, "Password Change", "Password changed successfully");
            
            $_SESSION['password_success'] = "Din adgangskode er blevet ændret.";
        } else {
            $_SESSION['password_error'] = "Der opstod en fejl. Prøv igen senere.";
        }
        
        $update_stmt->close();
    } else {
        $_SESSION['password_error'] = "Der opstod en fejl. Prøv igen senere.";
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