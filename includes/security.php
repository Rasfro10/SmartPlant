<?php

/**
 * Security functions for SmartPlant application
 */

// Generate CSRF token
function generate_csrf_token()
{
    if (!isset($_SESSION["csrf_token"])) {
        $_SESSION["csrf_token"] = bin2hex(random_bytes(32));
    }
    return $_SESSION["csrf_token"];
}

// Verify CSRF token
function verify_csrf_token($token)
{
    if (!isset($_SESSION["csrf_token"]) || $token !== $_SESSION["csrf_token"]) {
        return false;
    }
    return true;
}

// Rotate CSRF token for added security
function rotate_csrf_token()
{
    $_SESSION["csrf_token"] = bin2hex(random_bytes(32));
    return $_SESSION["csrf_token"];
}

// Sanitize user input
function sanitize_input($data)
{
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
    return $data;
}

/**
 * Get the real client IP address
 * This function checks various server variables to determine the actual client IP
 * It handles cases when the client is behind a proxy, load balancer, etc.
 * 
 * @return string The client's IP address
 */
function get_client_ip()
{
    // Check for shared internet/ISP IP
    if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
        return $_SERVER['HTTP_CLIENT_IP'];
    }

    // Check for IPs passing through proxies
    if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        // HTTP_X_FORWARDED_FOR can include multiple IPs, get the first one
        $ip_array = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
        return trim($ip_array[0]);
    }

    // Check for CloudFlare or other CDN IPs
    if (!empty($_SERVER['HTTP_CF_CONNECTING_IP'])) {
        return $_SERVER['HTTP_CF_CONNECTING_IP'];
    }

    // If neither of the above, use REMOTE_ADDR
    if (!empty($_SERVER['REMOTE_ADDR'])) {
        return $_SERVER['REMOTE_ADDR'];
    }

    // Fallback
    return 'Unknown';
}

// Log authentication related events
function log_auth_event($user_id, $event_type, $description)
{
    global $conn;

    // If the auth_log table doesn't exist yet, create it
    $conn->query("CREATE TABLE IF NOT EXISTS auth_log (
        id INT AUTO_INCREMENT PRIMARY KEY, 
        user_id INT NOT NULL,
        event_type VARCHAR(50) NOT NULL,
        description TEXT,
        ip_address VARCHAR(45),
        user_agent TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");

    // Prepare SQL statement
    $sql = "INSERT INTO auth_log (user_id, event_type, description, ip_address, user_agent) 
            VALUES (?, ?, ?, ?, ?)";

    if ($stmt = $conn->prepare($sql)) {
        // Get client info
        $ip_address = get_client_ip();
        $user_agent = isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : 'Unknown';

        // Bind parameters
        $stmt->bind_param("issss", $user_id, $event_type, $description, $ip_address, $user_agent);

        // Execute the statement
        $stmt->execute();

        // Close statement
        $stmt->close();
    }
}
