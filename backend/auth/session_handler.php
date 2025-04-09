<?php
// Start session
session_start();

// Require database connection
require_once $_SERVER['DOCUMENT_ROOT'] . '/smartplant/db/db_conn.php';

// Include security functions
require_once $_SERVER['DOCUMENT_ROOT'] . '/smartplant/includes/security.php';

// Function to clean up expired sessions
function cleanup_expired_sessions($conn) {
    $sql = "DELETE FROM user_sessions WHERE expires_at < NOW()";
    $conn->query($sql);
}

// Clean up expired sessions occasionally
if (mt_rand(1, 100) == 1) {
    cleanup_expired_sessions($conn);
}

// Function to validate current session
function validate_session($conn, $session_id, $user_id) {
    $current_time = date('Y-m-d H:i:s');
    
    $sql = "SELECT id FROM user_sessions 
            WHERE session_id = ? AND user_id = ? AND expires_at > ?";
    
    if ($stmt = $conn->prepare($sql)) {
        $stmt->bind_param("sis", $session_id, $user_id, $current_time);
        $stmt->execute();
        $stmt->store_result();
        
        $is_valid = ($stmt->num_rows > 0);
        $stmt->close();
        
        return $is_valid;
    }
    
    return false;
}

// Function to update session expiry and last activity
function update_session_expiry($conn, $session_id, $expiry_duration = 7200) {
    $new_expiry = date('Y-m-d H:i:s', time() + $expiry_duration);
    
    // First check if last_activity column exists
    $check_column = $conn->query("SHOW COLUMNS FROM `user_sessions` LIKE 'last_activity'");
    
    if ($check_column->num_rows > 0) {
        // Column exists, use it
        $sql = "UPDATE user_sessions SET 
                expires_at = ?, 
                last_activity = NOW() 
                WHERE session_id = ?";
    } else {
        // Column doesn't exist, just update expires_at
        $sql = "UPDATE user_sessions SET 
                expires_at = ?
                WHERE session_id = ?";
    }
    
    if ($stmt = $conn->prepare($sql)) {
        $stmt->bind_param("ss", $new_expiry, $session_id);
        $stmt->execute();
        $stmt->close();
    }
}

// Check if user is logged in via session
if (isset($_SESSION["loggedin"]) && $_SESSION["loggedin"] === true) {
    // Verify the session in the database
    $current_session_id = session_id();
    $user_id = $_SESSION["id"];
    
    if (!validate_session($conn, $current_session_id, $user_id)) {
        // Session not found in database or expired
        session_unset();
        session_destroy();
        
        // Clear cookies if they exist
        if (isset($_COOKIE["user_login"])) {
            setcookie("user_login", "", time() - 3600, "/");
            setcookie("user_id", "", time() - 3600, "/");
            setcookie("session_id", "", time() - 3600, "/");
        }
        
        // Log the session expiration
        log_auth_event($user_id, "Session Expired", "Session ID: {$current_session_id}");
        
        header("location: /smartplant/login/");
        exit;
    }
    
    // Session is valid, extend its lifetime
    update_session_expiry($conn, $current_session_id);
    
} else {
    // If not logged in via session, check if we have cookies
    if (isset($_COOKIE["user_login"]) && isset($_COOKIE["user_id"]) && isset($_COOKIE["session_id"])) {
        $email = $_COOKIE["user_login"];
        $user_id = $_COOKIE["user_id"];
        $session_id = $_COOKIE["session_id"];
        
        // Verify session in database
        if (validate_session($conn, $session_id, $user_id)) {
            // Validate user data against database
            $sql = "SELECT id, email FROM users WHERE id = ? AND email = ?";
            
            if ($stmt = $conn->prepare($sql)) {
                $stmt->bind_param("is", $user_id, $email);
                
                if ($stmt->execute()) {
                    $stmt->store_result();
                    
                    if ($stmt->num_rows == 1) {
                        // User verified, recreate session
                        $stmt->bind_result($id, $db_email);
                        $stmt->fetch();
                        
                        // Generate a new session ID for security
                        session_regenerate_id(true);
                        $new_session_id = session_id();
                        
                        $_SESSION["loggedin"] = true;
                        $_SESSION["id"] = $id;
                        $_SESSION["email"] = $db_email;
                        
                        // Update session ID in database
                        $update_sql = "UPDATE user_sessions SET session_id = ? WHERE session_id = ? AND user_id = ?";
                        
                        if ($update_stmt = $conn->prepare($update_sql)) {
                            $update_stmt->bind_param("ssi", $new_session_id, $session_id, $user_id);
                            $update_stmt->execute();
                            $update_stmt->close();
                        }
                        
                        // Update expiry in database
                        update_session_expiry($conn, $new_session_id, 86400 * 30);
                        
                        // Renew cookies for another 30 days
                        setcookie("user_login", $db_email, time() + (86400 * 30), "/", "", isset($_SERVER['HTTPS']), true);
                        setcookie("user_id", $id, time() + (86400 * 30), "/", "", isset($_SERVER['HTTPS']), true);
                        setcookie("session_id", $new_session_id, time() + (86400 * 30), "/", "", isset($_SERVER['HTTPS']), true);
                        
                        // Log the auto-login
                        log_auth_event($id, "Auto Login", "Cookie-based login");
                    } else {
                        // Invalid cookie data, clear cookies
                        setcookie("user_login", "", time() - 3600, "/");
                        setcookie("user_id", "", time() - 3600, "/");
                        setcookie("session_id", "", time() - 3600, "/");
                        
                        // Delete session from database
                        $delete_sql = "DELETE FROM user_sessions WHERE session_id = ?";
                        if ($delete_stmt = $conn->prepare($delete_sql)) {
                            $delete_stmt->bind_param("s", $session_id);
                            $delete_stmt->execute();
                            $delete_stmt->close();
                        }
                        
                        // Log the failed auto-login
                        log_auth_event($user_id, "Failed Auto Login", "Invalid user data");
                        
                        // Redirect to login page
                        header("location: /smartplant/login/");
                        exit;
                    }
                }
                
                $stmt->close();
            }
        } else {
            // Invalid or expired session, clear cookies
            setcookie("user_login", "", time() - 3600, "/");
            setcookie("user_id", "", time() - 3600, "/");
            setcookie("session_id", "", time() - 3600, "/");
            
            // Log the failed auto-login
            log_auth_event($user_id, "Failed Auto Login", "Invalid or expired session");
            
            // Redirect to login page
            header("location: /smartplant/login/");
            exit;
        }
    } else {
        // No session or cookies, redirect to login
        header("location: /smartplant/login/");
        exit;
    }
}
?>