<?php

/**
 * Logout Handler
 * This file handles the logout process and cleans up sessions
 */

// Start session if not already started
session_start();

// Include database connection
require_once $_SERVER['DOCUMENT_ROOT'] . '/smartplant/db/db_conn.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/smartplant/includes/security.php';

// Log the logout event
if (isset($_SESSION["id"])) {
    log_auth_event($_SESSION["id"], "Logout", "User initiated logout");
}

// Delete the session from the database
if (isset($_SESSION["id"])) {
    $user_id = $_SESSION["id"];
    $session_id = session_id();

    $sql = "DELETE FROM user_sessions WHERE user_id = ? AND session_id = ?";

    if ($stmt = $conn->prepare($sql)) {
        $stmt->bind_param("is", $user_id, $session_id);
        $stmt->execute();
        $stmt->close();
    }
}

// Clear all session variables
$_SESSION = array();

// Delete the session cookie
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(
        session_name(),
        '',
        time() - 42000,
        $params["path"],
        $params["domain"],
        $params["secure"],
        $params["httponly"]
    );
}

// Clear login cookies
setcookie("user_login", "", time() - 3600, "/");
setcookie("user_id", "", time() - 3600, "/");
setcookie("session_id", "", time() - 3600, "/");

// Destroy the session
session_destroy();

// Redirect to login page
header("location: /smartplant/login/");
exit;
