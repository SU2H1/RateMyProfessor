<?php
// config.php - Configuration file with SQLite database settings and email configuration

// These settings must be applied BEFORE session_start() is called
// Do NOT modify session settings here, as files may include this after session_start()

// Database file path - keep it in a subdirectory for better organization
define('DB_FILE', dirname(__FILE__) . '/database/ratemyteacher.db');
define('EMAIL_FROM', 's23447ks@sfc.keio.ac.jp'); // Your SFC email address
define('EMAIL_NAME', 'SU2H1 Rating');
define('SITE_URL', 'https://ratemyteachersfc.com');

// Create database directory if it doesn't exist
$db_dir = dirname(__FILE__) . '/database';
if (!file_exists($db_dir)) {
    mkdir($db_dir, 0755, true);
}

// Create a .htaccess file to prevent direct access to database file
if (!file_exists($db_dir . '/.htaccess')) {
    file_put_contents($db_dir . '/.htaccess', "Deny from all");
}

// Create or connect to SQLite database
try {
    $conn = new SQLite3(DB_FILE);
    $conn->enableExceptions(true);
    
    // Enable foreign keys
    $conn->exec('PRAGMA foreign_keys = ON');
} catch (Exception $e) {
    die("Database connection failed: " . $e->getMessage());
}

// Function to validate keio.jp email domain
function isValidKeioEmail($email) {
    return (strpos($email, '@keio.jp') !== false);
}

// Function to generate a random token for email verification
function generateToken($length = 32) {
    return bin2hex(random_bytes($length));
}

// Function to send confirmation email
function sendConfirmationEmail($email, $token) {
    $subject = "Confirm your SU2H1 account";
    $verificationLink = SITE_URL . "/verify.php?email=" . urlencode($email) . "&token=" . $token;
    
    $message = "
    <html>
    <head>
        <title>Email Verification</title>
    </head>
    <body>
        <h2>Welcome to SU2H1 Rating!</h2>
        <p>Thank you for registering. Please click the link below to verify your email address:</p>
        <p><a href='{$verificationLink}'>{$verificationLink}</a></p>
        <p>If you did not request this, please ignore this email.</p>
    </body>
    </html>
    ";
    
    // Set headers for HTML email
    $headers = "MIME-Version: 1.0" . "\r\n";
    $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
    $headers .= "From: " . EMAIL_NAME . " <" . EMAIL_FROM . ">" . "\r\n";
    
    // Send email
    return mail($email, $subject, $message, $headers);
}

// Function to send password reset email
function sendPasswordResetEmail($email, $token) {
    $subject = "Reset your SU2H1 password";
    $resetLink = SITE_URL . "/reset-password-confirm.php?email=" . urlencode($email) . "&token=" . $token;
    
    $message = "
    <html>
    <head>
        <title>Password Reset</title>
    </head>
    <body>
        <h2>SU2H1 Rating Password Reset</h2>
        <p>You have requested to reset your password. Please click the link below to set a new password:</p>
        <p><a href='{$resetLink}'>{$resetLink}</a></p>
        <p>This link will expire in 5 minutes.</p>
        <p>If you did not request this, please ignore this email and your password will remain unchanged.</p>
    </body>
    </html>
    ";
    
    // Set headers for HTML email
    $headers = "MIME-Version: 1.0" . "\r\n";
    $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
    $headers .= "From: " . EMAIL_NAME . " <" . EMAIL_FROM . ">" . "\r\n";
    
    // Send email
    return mail($email, $subject, $message, $headers);
}

// Initialize database schema if tables don't exist
function initDatabase() {
    global $conn;
    
    // Users table
    $conn->exec('
    CREATE TABLE IF NOT EXISTS users (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        username TEXT NOT NULL UNIQUE,
        email TEXT NOT NULL UNIQUE,
        password TEXT NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        is_verified INTEGER DEFAULT 0,
        verification_token TEXT DEFAULT NULL,
        token_expiry TIMESTAMP DEFAULT NULL
    )');
    
    // User profiles table
    $conn->exec('
    CREATE TABLE IF NOT EXISTS user_profiles (
        user_id INTEGER PRIMARY KEY,
        display_name TEXT,
        major TEXT,
        year_of_study TEXT,
        bio TEXT,
        avatar TEXT,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    )');
    
    // Professors table
    $conn->exec('
    CREATE TABLE IF NOT EXISTS professors (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        name TEXT NOT NULL,
        department TEXT,
        bio TEXT
    )');
    
    // Courses table
    $conn->exec('
    CREATE TABLE IF NOT EXISTS courses (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        name TEXT NOT NULL,
        course_code TEXT,
        description TEXT,
        professor_id INTEGER,
        FOREIGN KEY (professor_id) REFERENCES professors(id) ON DELETE SET NULL
    )');
    
    // Ratings table
    $conn->exec('
    CREATE TABLE IF NOT EXISTS ratings (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        user_id INTEGER NOT NULL,
        professor_id INTEGER NOT NULL,
        course_id INTEGER NOT NULL,
        rating REAL NOT NULL,
        comment TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
        FOREIGN KEY (professor_id) REFERENCES professors(id) ON DELETE CASCADE,
        FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE CASCADE
    )');
}

// Initialize the database tables
initDatabase();
?>