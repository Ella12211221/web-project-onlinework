<?php
// Simple and reliable session handler
if (session_status() === PHP_SESSION_NONE) {
    // Start output buffering to catch any accidental output
    ob_start();
    
    // Start session with minimal configuration
    session_start();
}

// Function to safely redirect
function safe_redirect($url) {
    // Clean any output buffer
    if (ob_get_level()) {
        ob_clean();
    }
    
    if (!headers_sent()) {
        header("Location: $url");
        exit();
    } else {
        // If headers already sent, use JavaScript redirect
        echo "<script>window.location.href = '$url';</script>";
        echo "<noscript><meta http-equiv='refresh' content='0;url=$url'></noscript>";
        exit();
    }
}

// Function to check if user is logged in
function is_logged_in() {
    return isset($_SESSION['user_id']);
}

// Function to get user type
function get_user_type() {
    return $_SESSION['user_type'] ?? 'guest';
}

// Function to require login
function require_login($redirect_url = '../auth/login.php') {
    if (!is_logged_in()) {
        safe_redirect($redirect_url);
    }
}

// Function to require admin access
function require_admin($redirect_url = '../dashboard/index.php') {
    if (!is_logged_in() || get_user_type() !== 'admin') {
        safe_redirect($redirect_url);
    }
}
?>