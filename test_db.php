<?php
// Simple test script to check database connectivity

// Display errors
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Database file path
define('DB_FILE', dirname(__FILE__) . '/database/ratemyteacher.db');

echo "<h1>Database Test</h1>";

try {
    echo "<p>Connecting to SQLite database at: " . DB_FILE . "</p>";
    
    // Check if file exists
    if (!file_exists(DB_FILE)) {
        die("<p>Error: Database file does not exist!</p>");
    }
    
    echo "<p>Database file exists.</p>";
    
    // Connect to SQLite database
    $db = new SQLite3(DB_FILE);
    $db->enableExceptions(true);
    
    echo "<p>Successfully connected to database.</p>";
    
    // Test query
    $query = "SELECT COUNT(*) as count FROM users";
    $result = $db->query($query);
    
    if ($result) {
        $row = $result->fetchArray(SQLITE3_ASSOC);
        echo "<p>Number of users in database: " . $row['count'] . "</p>";
        
        // Check if a specific email exists
        if (isset($_GET['email'])) {
            $email = $_GET['email'];
            $stmt = $db->prepare("SELECT id, email, is_verified, verification_token FROM users WHERE email = :email");
            $stmt->bindValue(':email', $email, SQLITE3_TEXT);
            $result = $stmt->execute();
            
            if ($row = $result->fetchArray(SQLITE3_ASSOC)) {
                echo "<p>Found user with email '{$email}':</p>";
                echo "<ul>";
                echo "<li>ID: " . $row['id'] . "</li>";
                echo "<li>Email: " . $row['email'] . "</li>";
                echo "<li>Verified: " . ($row['is_verified'] ? 'Yes' : 'No') . "</li>";
                echo "<li>Token: " . $row['verification_token'] . "</li>";
                echo "</ul>";
                
                // Option to verify this user directly
                echo "<p><a href='test_db.php?verify=" . $row['id'] . "'>Verify this user</a></p>";
            } else {
                echo "<p>No user found with email '{$email}'</p>";
            }
        }
        
        // Option to verify a user by ID
        if (isset($_GET['verify'])) {
            $id = (int)$_GET['verify'];
            $stmt = $db->prepare("UPDATE users SET is_verified = 1 WHERE id = :id");
            $stmt->bindValue(':id', $id, SQLITE3_INTEGER);
            $result = $stmt->execute();
            
            if ($result) {
                echo "<p>Successfully verified user with ID {$id}!</p>";
            } else {
                echo "<p>Failed to verify user.</p>";
            }
        }
        
    } else {
        echo "<p>Failed to query database: " . $db->lastErrorMsg() . "</p>";
    }
    
    $db->close();
    
} catch (Exception $e) {
    echo "<p>Error: " . $e->getMessage() . "</p>";
}
?>

<form method="get">
    <p>
        <label for="email">Check email:</label>
        <input type="email" name="email" id="email">
        <button type="submit">Check</button>
    </p>
</form>