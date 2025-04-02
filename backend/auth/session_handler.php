<?php
// Start session
session_start();

require_once $_SERVER['DOCUMENT_ROOT'] . '/smartplant/db/db_conn.php';

// Check if user is logged in via session
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    // If not logged in via session, check if we have cookies
    if (isset($_COOKIE["user_login"]) && isset($_COOKIE["user_id"])) {
        $email = $_COOKIE["user_login"];
        $user_id = $_COOKIE["user_id"];

        // Verify cookie data against database
        $sql = "SELECT id, email FROM users WHERE id = ? AND email = ?";

        if ($stmt = $conn->prepare($sql)) {
            $stmt->bind_param("is", $user_id, $email);

            if ($stmt->execute()) {
                $stmt->store_result();

                if ($stmt->num_rows == 1) {
                    // User verified, recreate session
                    $stmt->bind_result($id, $email);
                    $stmt->fetch();

                    $_SESSION["loggedin"] = true;
                    $_SESSION["id"] = $id;
                    $_SESSION["email"] = $email;

                    // Renew cookies for another 30 days
                    setcookie("user_login", $email, time() + (86400 * 30), "/");
                    setcookie("user_id", $id, time() + (86400 * 30), "/");
                } else {
                    // Invalid cookie data, clear cookies
                    setcookie("user_login", "", time() - 3600, "/");
                    setcookie("user_id", "", time() - 3600, "/");

                    // Redirect to login page
                    header("location: /smartplant/login/");
                    exit;
                }
            }

            $stmt->close();
        }
    } else {
        // No session or cookies, redirect to login
        header("location: /smartplant/login/");
        exit;
    }
}
