<?php
// session_config.php - Must be included BEFORE session_start()
// This file configures session parameters

// Only configure sessions if no session has been started yet
if (session_status() === PHP_SESSION_NONE) {
    // Session configuration
    ini_set('session.cookie_httponly', 1); // Prevent JavaScript access to session cookie
    ini_set('session.cookie_path', '/'); // Available across the whole domain
    ini_set('session.cookie_samesite', 'None'); // Allow cross-site cookies
    ini_set('session.use_only_cookies', 1); // Force use of cookies for session
    ini_set('session.gc_maxlifetime', 86400); // Session timeout in seconds (24 hours)
}
?>