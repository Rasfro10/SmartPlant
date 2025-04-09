<?php

/**
 * Security functions for SmartPlant
 * Includes CSRF protection and other security utilities
 */

// Generate a CSRF token
function generate_csrf_token()
{
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

// Verify CSRF token
function verify_csrf_token($token)
{
    if (!isset($_SESSION['csrf_token']) || $token !== $_SESSION['csrf_token']) {
        // Token invalid - handle error
        return false;
    }
    return true;
}

// Rotate CSRF token (use after form submission)
function rotate_csrf_token()
{
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    return $_SESSION['csrf_token'];
}

// Sanitize input
function sanitize_input($data)
{
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

// Parse user agent to get useful information
function parse_user_agent($user_agent)
{
    $browser = "Unknown";
    $os = "Unknown";
    $device = "Unknown";

    // Browser detection
    if (strpos($user_agent, 'Chrome') && !strpos($user_agent, 'Chromium') && !strpos($user_agent, 'Edge') && !strpos($user_agent, 'OPR') && !strpos($user_agent, 'Edg')) {
        $browser = 'Chrome';
    } elseif (strpos($user_agent, 'Firefox')) {
        $browser = 'Firefox';
    } elseif (strpos($user_agent, 'Safari') && !strpos($user_agent, 'Chrome')) {
        $browser = 'Safari';
    } elseif (strpos($user_agent, 'Edge') || strpos($user_agent, 'Edg')) {
        $browser = 'Edge';
    } elseif (strpos($user_agent, 'OPR') || strpos($user_agent, 'Opera')) {
        $browser = 'Opera';
    } elseif (strpos($user_agent, 'MSIE') || strpos($user_agent, 'Trident/7')) {
        $browser = 'Internet Explorer';
    }

    // OS detection
    if (strpos($user_agent, 'Windows')) {
        $os = 'Windows';
    } elseif (strpos($user_agent, 'Mac OS X')) {
        $os = 'Mac OS X';
    } elseif (strpos($user_agent, 'Linux')) {
        $os = 'Linux';
    } elseif (strpos($user_agent, 'Android')) {
        $os = 'Android';
    } elseif (strpos($user_agent, 'iOS')) {
        $os = 'iOS';
    }

    // Device detection
    if (strpos($user_agent, 'Mobile')) {
        $device = 'Mobile';
    } elseif (strpos($user_agent, 'Tablet')) {
        $device = 'Tablet';
    } else {
        $device = 'Desktop';
    }

    return [
        'browser' => $browser,
        'os' => $os,
        'device' => $device,
        'full' => $user_agent
    ];
}

// Log authentication events
function log_auth_event($user_id, $event_type, $details = '')
{
    global $conn;

    // Get IP address and user agent
    $ip_address = $_SERVER['REMOTE_ADDR'];
    $user_agent = isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : 'Unknown';

    // Parse user agent for more readable info
    $ua_info = parse_user_agent($user_agent);

    // Handle local IP addresses
    if ($ip_address == '::1' || $ip_address == '127.0.0.1') {
        $ip_display = 'localhost';
    } else {
        $ip_display = $ip_address;
    }

    // Log to file
    $log_dir = $_SERVER['DOCUMENT_ROOT'] . '/smartplant/logs/';
    $log_file = $log_dir . 'auth_' . date('Y-m-d') . '.log';

    $log_message = date('Y-m-d H:i:s') .
        " | User ID: $user_id" .
        " | $event_type" .
        " | IP: $ip_display" .
        " | Browser: {$ua_info['browser']}" .
        " | OS: {$ua_info['os']}" .
        " | Device: {$ua_info['device']}" .
        " | $details\n";

    // Create logs directory if it doesn't exist
    if (!is_dir($log_dir)) {
        mkdir($log_dir, 0755, true);
    }

    // Write to log file if directory exists and is writable
    if (is_dir($log_dir) && is_writable($log_dir)) {
        file_put_contents($log_file, $log_message, FILE_APPEND);
    }
}
