<?php
// review_debug.php - Save this as a separate file to check review ownership
require_once "session_config.php";
session_start();

// Security check - only allow logged-in users
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    echo "You must be logged in to use this tool.";
    exit;
}

// Check if we have a review ID
$review_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if (!$review_id) {
    echo "No review ID specified. Add ?id=X to the URL.";
    exit;
}

// Include database connection
require_once "config.php";

echo "<h1>Review Debug Tool</h1>";
echo "<h2>Session Information</h2>";
echo "<pre>";
echo "User ID in session: " . $_SESSION["id"] . "\n";
echo "Username in session: " . $_SESSION["username"] . "\n";
echo "</pre>";

echo "<h2>Review Information</h2>";

// Check if the review exists at all
$review_sql = "SELECT * FROM ratings WHERE id = :review_id";
$review_stmt = $conn->prepare($review_sql);
$review_stmt->bindValue(':review_id', $review_id, SQLITE3_INTEGER);
$review_result = $review_stmt->execute();
$review = $review_result->fetchArray(SQLITE3_ASSOC);

if (!$review) {
    echo "<p>No review found with ID: $review_id</p>";
    
    // Let's look for reviews by this user
    echo "<h2>Reviews by Current User</h2>";
    $user_reviews_sql = "SELECT id, course_id, created_at FROM ratings WHERE user_id = :user_id ORDER BY id DESC LIMIT 10";
    $user_reviews_stmt = $conn->prepare($user_reviews_sql);
    $user_reviews_stmt->bindValue(':user_id', $_SESSION["id"], SQLITE3_INTEGER);
    $user_reviews_result = $user_reviews_stmt->execute();
    
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>Review ID</th><th>Course ID</th><th>Date</th></tr>";
    
    $found_reviews = false;
    while ($user_review = $user_reviews_result->fetchArray(SQLITE3_ASSOC)) {
        $found_reviews = true;
        echo "<tr>";
        echo "<td>" . $user_review['id'] . "</td>";
        echo "<td>" . $user_review['course_id'] . "</td>";
        echo "<td>" . $user_review['created_at'] . "</td>";
        echo "</tr>";
    }
    
    if (!$found_reviews) {
        echo "<tr><td colspan='3'>No reviews found for this user</td></tr>";
    }
    echo "</table>";
    
} else {
    // The review exists, let's show details
    echo "<pre>";
    echo "Review ID: " . $review['id'] . "\n";
    echo "User ID in review: " . $review['user_id'] . "\n";
    echo "Course ID: " . $review['course_id'] . "\n";
    echo "Date: " . $review['created_at'] . "\n";
    echo "</pre>";
    
    // Check if the review belongs to the current user
    if ($review['user_id'] == $_SESSION["id"]) {
        echo "<p style='color:green'>✓ This review belongs to the current logged-in user.</p>";
    } else {
        echo "<p style='color:red'>✗ This review belongs to user ID " . $review['user_id'] . ", not the current logged-in user (ID: " . $_SESSION["id"] . ").</p>";
    }
    
    // Let's show some other reviews by the same user as comparison
    $other_reviews_sql = "SELECT id, course_id, created_at FROM ratings WHERE user_id = :user_id AND id != :review_id ORDER BY id DESC LIMIT 5";
    $other_reviews_stmt = $conn->prepare($other_reviews_sql);
    $other_reviews_stmt->bindValue(':user_id', $review['user_id'], SQLITE3_INTEGER);
    $other_reviews_stmt->bindValue(':review_id', $review_id, SQLITE3_INTEGER);
    $other_reviews_result = $other_reviews_stmt->execute();
    
    echo "<h3>Other reviews by the same user (ID: " . $review['user_id'] . ")</h3>";
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>Review ID</th><th>Course ID</th><th>Date</th></tr>";
    
    $found_other_reviews = false;
    while ($other_review = $other_reviews_result->fetchArray(SQLITE3_ASSOC)) {
        $found_other_reviews = true;
        echo "<tr>";
        echo "<td>" . $other_review['id'] . "</td>";
        echo "<td>" . $other_review['course_id'] . "</td>";
        echo "<td>" . $other_review['created_at'] . "</td>";
        echo "</tr>";
    }
    
    if (!$found_other_reviews) {
        echo "<tr><td colspan='3'>No other reviews found for this user</td></tr>";
    }
    echo "</table>";
}

// Show the table structure
echo "<h2>Table Structure</h2>";
$table_info_sql = "PRAGMA table_info(ratings)";
$table_info_result = $conn->query($table_info_sql);

echo "<table border='1' cellpadding='5'>";
echo "<tr><th>CID</th><th>Name</th><th>Type</th><th>NotNull</th><th>Default</th><th>PK</th></tr>";

while ($column = $table_info_result->fetchArray(SQLITE3_ASSOC)) {
    echo "<tr>";
    echo "<td>" . $column['cid'] . "</td>";
    echo "<td>" . $column['name'] . "</td>";
    echo "<td>" . $column['type'] . "</td>";
    echo "<td>" . $column['notnull'] . "</td>";
    echo "<td>" . $column['dflt_value'] . "</td>";
    echo "<td>" . $column['pk'] . "</td>";
    echo "</tr>";
}
echo "</table>";

$conn->close();
?>

<p><a href="account-section.php">Return to account page</a></p>