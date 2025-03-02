<?php
// session_config.php - Must be included BEFORE session_start()
// This file configures session parameters

// Set session name to be distinct from other applications
session_name('RateMyTeacherSession');

// Force cookies for all sessions
if (!ini_get('session.use_only_cookies')) {
    ini_set('session.use_only_cookies', 1);
}

// Define session ID hash and length
ini_set('session.sid_bits_per_character', 5); // 5 is most efficient

// Only configure sessions if no session has been started yet
if (session_status() === PHP_SESSION_NONE) {
    // Session configuration
    ini_set('session.cookie_httponly', 1); // Prevent JavaScript access to session cookie
    ini_set('session.cookie_path', '/'); // Available across the whole domain
    ini_set('session.cookie_samesite', 'Lax'); // Allow same-site cookies, more secure than None
    ini_set('session.use_only_cookies', 1); // Force use of cookies for session
    ini_set('session.gc_maxlifetime', 86400); // Session timeout in seconds (24 hours)
    
    // Set the session cookie parameters
    $params = [
        'lifetime' => 86400,
        'path' => '/',
        'domain' => '', // current domain
        'secure' => false, // set to true if using HTTPS
        'httponly' => true,
        'samesite' => 'Lax'
    ];
    
    session_set_cookie_params($params);
    
    // Debug session configuration
    error_log("Session configuration loaded: Name=" . session_name() . 
              ", HTTP Only=" . ini_get('session.cookie_httponly') . 
              ", Path=" . ini_get('session.cookie_path') . 
              ", SameSite=" . ini_get('session.cookie_samesite'));
}
?>