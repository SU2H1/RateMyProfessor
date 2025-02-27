<?php
// delete_review.php - Handler for deleting user reviews
session_start();

// Check if user is logged in
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("HTTP/1.1 401 Unauthorized");
    echo "You must be logged in to delete a review.";
    exit;
}

// Check if review_id is provided
if (!isset($_POST["review_id"]) || empty($_POST["review_id"])) {
    header("HTTP/1.1 400 Bad Request");
    echo "Review ID is required.";
    exit;
}

require_once "config.php";

$review_id = $_POST["review_id"];
$user_id = $_SESSION["id"];

try {
    // Verify that the review belongs to the current user
    $check_sql = "SELECT id FROM ratings WHERE id = :review_id AND user_id = :user_id";
    $check_stmt = $conn->prepare($check_sql);
    $check_stmt->bindValue(':review_id', $review_id, SQLITE3_INTEGER);
    $check_stmt->bindValue(':user_id', $user_id, SQLITE3_INTEGER);
    $result = $check_stmt->execute();
    
    if (!$result->fetchArray(SQLITE3_ASSOC)) {
        header("HTTP/1.1 403 Forbidden");
        echo "You do not have permission to delete this review.";
        exit;
    }
    
    // Delete the review
    $delete_sql = "DELETE FROM ratings WHERE id = :review_id AND user_id = :user_id";
    $delete_stmt = $conn->prepare($delete_sql);
    $delete_stmt->bindValue(':review_id', $review_id, SQLITE3_INTEGER);
    $delete_stmt->bindValue(':user_id', $user_id, SQLITE3_INTEGER);
    $result = $delete_stmt->execute();
    
    if ($result) {
        // Get the course_id and professor_id before proceeding with update
        $review_info_sql = "SELECT course_id, professor_id FROM ratings WHERE id = :review_id";
        $review_info_stmt = $conn->prepare($review_info_sql);
        $review_info_stmt->bindValue(':review_id', $review_id, SQLITE3_INTEGER);
        $review_info_result = $review_info_stmt->execute();
        $review_info_row = $review_info_result->fetchArray(SQLITE3_ASSOC);
        
        if ($review_info_row) {
            // Include rating calculation functions
            require_once 'calculate_ratings.php';
            
            // Update course ratings if course_id is available
            if (isset($review_info_row['course_id']) && $review_info_row['course_id']) {
                $course_id = $review_info_row['course_id'];
                
                // Check if the courses table has avg_content_quality and avg_difficulty columns
                $has_avg_columns = false;
                $columns_result = $conn->query("PRAGMA table_info(courses)");
                while ($column = $columns_result->fetchArray(SQLITE3_ASSOC)) {
                    if ($column['name'] === 'avg_content_quality') {
                        $has_avg_columns = true;
                        break;
                    }
                }
                
                if ($has_avg_columns) {
                    // Update average ratings for the course
                    updateStoredCourseRatings($course_id, $conn);
                } else {
                    // Log that we couldn't update the average ratings
                    error_log("Couldn't update course ratings - avg_content_quality column not found in courses table");
                }
            } else {
                // Log that we couldn't get the course_id
                error_log("Couldn't update course ratings - course_id not found for review ID: $review_id");
            }
            
            // Update professor ratings if professor_id is available
            if (isset($review_info_row['professor_id']) && $review_info_row['professor_id']) {
                $professor_id = $review_info_row['professor_id'];
                
                // Check if professors table has rating columns
                $has_prof_avg_columns = false;
                $prof_columns_result = $conn->query("PRAGMA table_info(professors)");
                while ($column = $prof_columns_result->fetchArray(SQLITE3_ASSOC)) {
                    if ($column['name'] === 'avg_content_quality') {
                        $has_prof_avg_columns = true;
                        break;
                    }
                }
                
                if ($has_prof_avg_columns) {
                    // Update average ratings for the professor
                    updateStoredProfessorRatings($professor_id, $conn);
                } else {
                    // Log that we couldn't update the professor ratings
                    error_log("Couldn't update professor ratings - avg_content_quality column not found in professors table");
                }
            } else {
                // Log that we couldn't get the professor_id
                error_log("Couldn't update professor ratings - professor_id not found for review ID: $review_id");
            }
        }
        
        echo "Review deleted successfully!";
    } else {
        header("HTTP/1.1 500 Internal Server Error");
        echo "Failed to delete review. Please try again.";
    }
    
} catch (Exception $e) {
    header("HTTP/1.1 500 Internal Server Error");
    echo "An error occurred: " . $e->getMessage();
} finally {
    // Close database connection
    $conn->close();
}
?>