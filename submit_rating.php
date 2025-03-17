<?php
/**
 * Submit Rating API
 * This file handles rating submissions for courses and professors
 */

// Include database configuration
require_once 'config.php';
require_once 'session_config.php';

// Start session to check if user is logged in
session_start();
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'You must be logged in to submit ratings']);
    exit;
}

// Check if the request is a POST request
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Invalid request method']);
    exit;
}

// Silently log data for debugging purposes
error_log("---------- START NEW RATING SUBMISSION ----------");
error_log("POST data: " . json_encode($_POST));
if (isset($_POST['course_name'])) {
    error_log("COURSE NAME FROM POST: '{$_POST['course_name']}'");
}
if (isset($_POST['course_id'])) {
    error_log("COURSE ID FROM POST: '{$_POST['course_id']}'");
}

// Get rating data from POST
$userId = $_SESSION['id'];
$rating = isset($_POST['rating']) ? (int) $_POST['rating'] : 0;
$contentRating = isset($_POST['content_rating']) ? (int) $_POST['content_rating'] : 0;
$difficultyRating = isset($_POST['difficulty_rating']) ? (int) $_POST['difficulty_rating'] : 0;
$grade = isset($_POST['grade']) ? $_POST['grade'] : '';
$textbook = isset($_POST['textbook']) ? $_POST['textbook'] : '';
$firstHalf = isset($_POST['first_half']) ? $_POST['first_half'] : [];
$secondHalf = isset($_POST['second_half']) ? $_POST['second_half'] : [];
$comment = isset($_POST['comment']) ? trim($_POST['comment']) : '';
$courseId = isset($_POST['course_id']) ? $_POST['course_id'] : null;
$courseName = isset($_POST['course_name']) ? $_POST['course_name'] : null;

// Log original course name for debugging
if ($courseName) {
    error_log("Original course name from POST data: '$courseName'");
}

// Get professor name from POST or extract from URL
$professorName = isset($_GET['name']) ? $_GET['name'] : null;

// Extract professor from URL if not provided directly
if (!$professorName && isset($_POST['redirect_url'])) {
    $redirectUrl = $_POST['redirect_url'];
    // Parse URL to extract professor parameter
    $urlParts = parse_url($redirectUrl);
    if (isset($urlParts['query'])) {
        parse_str($urlParts['query'], $queryParams);
        if (isset($queryParams['professor'])) {
            $professorName = $queryParams['professor'];
        }
    }
}

// For logging/debugging
error_log("Review submission - Content Rating: $contentRating, Difficulty: $difficultyRating, Grade: $grade, Textbook: $textbook");
error_log("First half assessments: " . implode(", ", (array)$firstHalf));
error_log("Second half assessments: " . implode(", ", (array)$secondHalf));

// Get attendance value
$attendanceCheck = isset($_POST['attendance_check']) ? $_POST['attendance_check'] : '';

// Get anonymous preference
$isAnonymous = isset($_POST['is_anonymous']) ? (bool)$_POST['is_anonymous'] : false;

// Calculate the overall rating from content and difficulty
$rating = round(($contentRating + (5 - $difficultyRating)) / 2);

// Log anonymous status for debugging
error_log("Review is anonymous: " . ($isAnonymous ? 'Yes' : 'No'));

// Validate content rating
if ($contentRating < 1 || $contentRating > 5) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Invalid content rating value']);
    exit;
}

// Validate difficulty rating
if ($difficultyRating < 1 || $difficultyRating > 5) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Invalid difficulty rating value']);
    exit;
}

// Validate grade
$validGrades = ['S', 'A', 'B', 'C', 'D', 'F', 'P', '未受講'];
if (!in_array($grade, $validGrades)) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Invalid grade value']);
    exit;
}

// Validate textbook if provided
if (!empty($textbook)) {
    $validTextbookOptions = ['required', 'recommended', 'not_needed'];
    if (!in_array($textbook, $validTextbookOptions)) {
        // Set a default value instead of exiting
        $textbook = 'not_needed';
    }
}

// Create database connection
try {
    // Check if database directory exists
    if (!file_exists('database')) {
        mkdir('database', 0755, true);
    }
    
    // Try to connect to database
    $db = new SQLite3('database/ratemyteacher.db');
    
    // Create tables if they don't exist
    $db->exec("
        CREATE TABLE IF NOT EXISTS courses (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            course_id TEXT,
            name TEXT,
            course_code TEXT,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        )
    ");
    
    $db->exec("
        CREATE TABLE IF NOT EXISTS professors (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            name TEXT,
            department TEXT,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        )
    ");
    
    $db->exec("
        CREATE TABLE IF NOT EXISTS ratings (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            user_id INTEGER,
            course_id INTEGER,
            professor_id INTEGER,
            rating INTEGER,
            content_rating INTEGER,
            difficulty_rating INTEGER,
            grade TEXT,
            textbook TEXT,
            attendance_check TEXT,
            first_half TEXT,
            second_half TEXT,
            comment TEXT,
            is_anonymous INTEGER DEFAULT 0,
            created_at DATETIME,
            FOREIGN KEY (course_id) REFERENCES courses(id),
            FOREIGN KEY (professor_id) REFERENCES professors(id)
        )
    ");

    // Check if is_anonymous column exists, add it if not (for backward compatibility)
    $columnsResult = $db->query("PRAGMA table_info(ratings)");
    $hasAnonymousColumn = false;
    while ($col = $columnsResult->fetchArray(SQLITE3_ASSOC)) {
        if ($col['name'] === 'is_anonymous') {
            $hasAnonymousColumn = true;
            break;
        }
    }

    if (!$hasAnonymousColumn) {
        try {
            $db->exec("ALTER TABLE ratings ADD COLUMN is_anonymous INTEGER DEFAULT 0");
            error_log("Added is_anonymous column to ratings table");
        } catch (Exception $e) {
            error_log("Error adding is_anonymous column: " . $e->getMessage());
            // Continue anyway - we'll work with what we have
        }
    }
    
    $db->exec("BEGIN TRANSACTION");
    
    // Get or create course record
    $courseDbId = null;
    if ($courseId) {
        // Get column names for courses table to see what we can query by
        $columns = [];
        $columnsResult = $db->query("PRAGMA table_info('courses')");
        while ($col = $columnsResult->fetchArray(SQLITE3_ASSOC)) {
            $columns[] = $col['name'];
        }
        
        // Check if the course exists - try multiple methods
        error_log("Looking for course with ID: $courseId or name: $courseName");
        
        // First try by exact course name if provided
        if ($courseName) {
            error_log("Looking up course by exact name match: '$courseName'");
            
            $stmt = $db->prepare("
                SELECT id, name FROM courses 
                WHERE name = :name 
                LIMIT 1
            ");
            $stmt->bindValue(':name', $courseName, SQLITE3_TEXT);
            $result = $stmt->execute();
            $course = $result->fetchArray(SQLITE3_ASSOC);
            
            // Log the result for debugging
            if ($course) {
                error_log("Found exact course name match in database: ID={$course['id']}, Name={$course['name']}");
            } else {
                error_log("No exact course name match found in database");
            }
        }
        
        // If not found by name and we have an ID, try that
        if (!$course && $courseId) {
            // Try by course_id column if it exists
            if (in_array('course_id', $columns)) {
                $stmt = $db->prepare("
                    SELECT id, name FROM courses 
                    WHERE course_id = :course_id 
                    LIMIT 1
                ");
                $stmt->bindValue(':course_id', $courseId, SQLITE3_TEXT);
            } else {
                // Fallback to looking up by ID directly
                $stmt = $db->prepare("
                    SELECT id, name FROM courses 
                    WHERE id = :id 
                    LIMIT 1
                ");
                $stmt->bindValue(':id', $courseId, SQLITE3_INTEGER);
            }
            $result = $stmt->execute();
            $course = $result->fetchArray(SQLITE3_ASSOC);
            
            // Log what we found to help with debugging
            if ($course) {
                error_log("Found course by ID in database: ID={$course['id']}, Name={$course['name']}");
            }
        }
    
    if ($course) {
        $courseDbId = $course['id'];
        error_log("Using existing course record with ID: $courseDbId and name: '{$course['name']}'");
    } else {
        error_log("No existing course found, will create new course with name: '$courseName'");
        // Get course info from JSON file
        $jsonData = file_get_contents(__DIR__ . '/sfc_courses.json');
        $data = json_decode($jsonData, true);
        $courseInfo = null;
        
        foreach ($data['courses'] as $c) {
            // Try to match by course_id if available
            if (isset($c['course_id']) && $c['course_id'] === $courseId) {
                $courseInfo = $c;
                break;
            }
            
            // Try to match by name
            if ($courseName) {
                $jaName = $c['translations']['ja']['name'] ?? '';
                $enName = $c['translations']['en']['name'] ?? '';
                
                if ($jaName === $courseName || $enName === $courseName) {
                    $courseInfo = $c;
                    break;
                }
            }
        }
        
        if ($courseInfo) {
            // IMPORTANT: Preserve the original course name from POST data if available
            if (isset($_POST['course_name']) && !empty($_POST['course_name'])) {
                // Keep the original course name from POST
                error_log("PRESERVING original course name from POST: '$courseName'");
            } else {
                // Only use course info as fallback if we don't have a name from POST
                $courseName = $courseInfo['translations']['ja']['name'] ?? 
                             ($courseInfo['translations']['en']['name'] ?? $courseId);
                error_log("Using course name from JSON data: '$courseName'");
            }
            
            // Get column names to see what we can insert
            $columns = [];
            $columnsResult = $db->query("PRAGMA table_info('courses')");
            while ($col = $columnsResult->fetchArray(SQLITE3_ASSOC)) {
                $columns[] = $col['name'];
            }
            
            if (in_array('course_code', $columns)) {
                if (in_array('professor_id', $columns)) {
                    // If we have course_code and professor_id columns
                    $stmt = $db->prepare("
                        INSERT INTO courses (name, course_code, professor_id) 
                        VALUES (:name, :course_code, :professor_id)
                    ");
                    $stmt->bindValue(':name', $courseName, SQLITE3_TEXT);
                    $stmt->bindValue(':course_code', "CODE-" . $courseId, SQLITE3_TEXT);
                    $stmt->bindValue(':professor_id', null, SQLITE3_NULL);
                } else {
                    // If we have course_code but no professor_id
                    $stmt = $db->prepare("
                        INSERT INTO courses (name, course_code) 
                        VALUES (:name, :course_code)
                    ");
                    $stmt->bindValue(':name', $courseName, SQLITE3_TEXT);
                    $stmt->bindValue(':course_code', "CODE-" . $courseId, SQLITE3_TEXT);
                }
            } else {
                // Simple insert with just name
                $stmt = $db->prepare("
                    INSERT INTO courses (name) 
                    VALUES (:name)
                ");
                $stmt->bindValue(':name', $courseName, SQLITE3_TEXT);
            }
            $result = $stmt->execute();
            
            if ($result) {
                $courseDbId = $db->lastInsertRowID();
            } else {
                header('Content-Type: application/json');
                echo json_encode(['error' => 'Failed to create course record']);
                exit;
            }
        } else {
            // Create a new course record with the information we have
            // Parse debug info for course name
            $debugInfo = isset($_POST['debug_info']) ? json_decode($_POST['debug_info'], true) : [];
            
            // FIXED: Always prioritize $_POST['course_name'] and make sure it's not overwritten
            if (isset($_POST['course_name']) && !empty($_POST['course_name'])) {
                $courseName = $_POST['course_name'];
                error_log("Using course name from direct POST: " . $courseName);
            } else if (isset($debugInfo['course_name']) && !empty($debugInfo['course_name'])) {
                $courseName = $debugInfo['course_name'];
                error_log("Using course name from debug info: " . $courseName);
            } else {
                $courseName = "Course $courseId";
                error_log("No course name found, using default: " . $courseName);
            }
            
            // Get column names to see what we can insert
            $columns = [];
            $columnsResult = $db->query("PRAGMA table_info('courses')");
            while ($col = $columnsResult->fetchArray(SQLITE3_ASSOC)) {
                $columns[] = $col['name'];
            }
            
            // Insert the new course based on available columns
            if (in_array('course_code', $columns)) {
                if (in_array('professor_id', $columns)) {
                    // If we have course_code and professor_id columns
                    error_log("CRITICAL: Creating new course with name: '$courseName' and course_id: $courseId");
                    $insertStmt = $db->prepare("
                        INSERT INTO courses (name, course_code, professor_id) 
                        VALUES (:name, :course_code, :professor_id)
                    ");
                    $insertStmt->bindValue(':name', $courseName, SQLITE3_TEXT);
                    $insertStmt->bindValue(':course_code', "CODE-" . $courseId, SQLITE3_TEXT);
                    $insertStmt->bindValue(':professor_id', null, SQLITE3_NULL);
                } else {
                    // If we have course_code but no professor_id
                    error_log("CRITICAL: Creating new course with name: '$courseName' and course_id: $courseId");
                    $insertStmt = $db->prepare("
                        INSERT INTO courses (name, course_code) 
                        VALUES (:name, :course_code)
                    ");
                    $insertStmt->bindValue(':name', $courseName, SQLITE3_TEXT);
                    $insertStmt->bindValue(':course_code', "CODE-" . $courseId, SQLITE3_TEXT);
                }
            } else {
                // Simple insert with just name
                error_log("CRITICAL: Creating new course with name: '$courseName' and course_id: $courseId");
                $insertStmt = $db->prepare("
                    INSERT INTO courses (name) 
                    VALUES (:name)
                ");
                $insertStmt->bindValue(':name', $courseName, SQLITE3_TEXT);
            }
            $insertResult = $insertStmt->execute();
            
            if ($insertResult) {
                $courseDbId = $db->lastInsertRowID();
            } else {
                header('Content-Type: application/json');
                echo json_encode(['error' => 'Failed to create course record']);
                exit;
            }
        }
    }
}

// Get or create professor record
// Initialize with a default professor ID to avoid NOT NULL constraint
$professorDbId = 1; // Default to ID 1 (we'll update this if we find a match)
if ($professorName) {
    // Check if the professor exists
    $stmt = $db->prepare("
        SELECT id FROM professors 
        WHERE name = :name 
        LIMIT 1
    ");
    $stmt->bindValue(':name', $professorName, SQLITE3_TEXT);
    $result = $stmt->execute();
    $professor = $result->fetchArray(SQLITE3_ASSOC);
    
    if ($professor) {
        $professorDbId = $professor['id'];
    } else {
        // Check if we have professor name to work with
        if ($professorName) {
            // Create new professor with simple defaults
            $department = "Unknown Department";
            
            // Create the professor
            $stmt = $db->prepare("
                INSERT INTO professors (name, department) 
                VALUES (:name, :department)
            ");
            $stmt->bindValue(':name', $professorName, SQLITE3_TEXT);
            $stmt->bindValue(':department', $department, SQLITE3_TEXT);
            $result = $stmt->execute();
            
            if ($result) {
                $professorDbId = $db->lastInsertRowID();
            } else {
                // Keep the default ID (1) that we set earlier
            }
        } else {
            // No professor name available, keep using the default ID
        }
    }
}

// Convert array data to JSON for storage
$firstHalfJson = json_encode($firstHalf);
$secondHalfJson = json_encode($secondHalf);

// Get the columns from the ratings table
$ratingColumns = [];
$columnsResult = $db->query("PRAGMA table_info('ratings')");
while ($col = $columnsResult->fetchArray(SQLITE3_ASSOC)) {
    $ratingColumns[] = $col['name'];
}

// Build the INSERT statement dynamically based on available columns
$insertColumns = [];
$insertValues = [];
$bindParams = [];

// Define all possible fields with their values and types
$fields = [
    'user_id' => ['value' => $userId, 'type' => SQLITE3_INTEGER],
    'course_id' => ['value' => $courseDbId, 'type' => $courseDbId === null ? SQLITE3_NULL : SQLITE3_INTEGER],
    'professor_id' => ['value' => $professorDbId, 'type' => $professorDbId === null ? SQLITE3_NULL : SQLITE3_INTEGER],
    'rating' => ['value' => $rating, 'type' => SQLITE3_INTEGER],
    'content_rating' => ['value' => $contentRating, 'type' => SQLITE3_INTEGER],
    'difficulty_rating' => ['value' => $difficultyRating, 'type' => SQLITE3_INTEGER],
    'grade' => ['value' => $grade, 'type' => SQLITE3_TEXT],
    'textbook' => ['value' => $textbook, 'type' => SQLITE3_TEXT],
    'attendance_check' => ['value' => $attendanceCheck, 'type' => SQLITE3_TEXT],
    'first_half' => ['value' => $firstHalfJson, 'type' => SQLITE3_TEXT],
    'second_half' => ['value' => $secondHalfJson, 'type' => SQLITE3_TEXT],
    'comment' => ['value' => $comment, 'type' => SQLITE3_TEXT],
    'is_anonymous' => ['value' => $isAnonymous ? 1 : 0, 'type' => SQLITE3_INTEGER]
    ];

// Special handling for created_at
if (in_array('created_at', $ratingColumns)) {
    $insertColumns[] = 'created_at';
    $insertValues[] = 'datetime(\'now\')';
}

// Add columns that exist in the table
foreach ($fields as $column => $data) {
    if (in_array($column, $ratingColumns)) {
        $insertColumns[] = $column;
        $insertValues[] = ':' . $column;
        $bindParams[$column] = $data;
    }
}

// Construct the SQL
$sql = "INSERT INTO ratings (" . implode(', ', $insertColumns) . ") VALUES (" . implode(', ', $insertValues) . ")";

// Prepare and bind
$stmt = $db->prepare($sql);
foreach ($bindParams as $param => $data) {
    $stmt->bindValue(':' . $param, $data['value'], $data['type']);
}

$result = $stmt->execute();

// Check if the user has provided a redirect URL
$redirectUrl = isset($_POST['redirect_url']) ? $_POST['redirect_url'] : '';

if ($result) {
    $db->exec("COMMIT");
    $newRatingId = $db->lastInsertRowID();
    error_log("Rating committed successfully! New rating ID: $newRatingId for course ID: $courseDbId");
    
    // Include the rating calculation functions
    require_once 'calculate_ratings.php';
    
    // Update the professor's average ratings
    if (isset($professorDbId) && $professorDbId) {
        // Check if the professors table has rating columns
        $hasRatingColumns = false;
        $columnsResult = $db->query("PRAGMA table_info(professors)");
        while ($col = $columnsResult->fetchArray(SQLITE3_ASSOC)) {
            if ($col['name'] === 'avg_content_quality') {
                $hasRatingColumns = true;
                break;
            }
        }
        
        // If the columns don't exist, try to add them
        if (!$hasRatingColumns) {
            try {
                $db->exec("ALTER TABLE professors ADD COLUMN avg_content_quality REAL DEFAULT 0");
                $db->exec("ALTER TABLE professors ADD COLUMN avg_difficulty REAL DEFAULT 0");
                $db->exec("ALTER TABLE professors ADD COLUMN overall_rating REAL DEFAULT 0");
                $db->exec("ALTER TABLE professors ADD COLUMN review_count INTEGER DEFAULT 0");
                $hasRatingColumns = true;
            } catch (Exception $e) {
                error_log("Could not add rating columns to professors table: " . $e->getMessage());
            }
        }
        
        // Update the ratings if columns exist
        if ($hasRatingColumns) {
            // Update professor ratings
            updateStoredProfessorRatings($professorDbId, $db);
        }
    }
    
    // Update the course's average ratings
    if (isset($courseDbId) && $courseDbId) {
        // Check if the courses table has rating columns
        $hasCourseRatingColumns = false;
        $columnsResult = $db->query("PRAGMA table_info(courses)");
        while ($col = $columnsResult->fetchArray(SQLITE3_ASSOC)) {
            if ($col['name'] === 'avg_content_quality') {
                $hasCourseRatingColumns = true;
                break;
            }
        }
        
        // If the columns don't exist, try to add them
        if (!$hasCourseRatingColumns) {
            try {
                $db->exec("ALTER TABLE courses ADD COLUMN avg_content_quality REAL DEFAULT 0");
                $db->exec("ALTER TABLE courses ADD COLUMN avg_difficulty REAL DEFAULT 0");
                $db->exec("ALTER TABLE courses ADD COLUMN overall_rating REAL DEFAULT 0");
                $db->exec("ALTER TABLE courses ADD COLUMN review_count INTEGER DEFAULT 0");
                $hasCourseRatingColumns = true;
            } catch (Exception $e) {
                error_log("Could not add rating columns to courses table: " . $e->getMessage());
            }
        }
        
        // Update the ratings if columns exist
        if ($hasCourseRatingColumns) {
            // Update course ratings
            updateStoredCourseRatings($courseDbId, $db);
        }
    }
    
    // Immediate redirect on success with new_review parameter
    if (!empty($redirectUrl)) {
        // Add a query parameter to indicate new review
        $redirectUrl .= (strpos($redirectUrl, '?') !== false ? '&' : '?') . 'new_review=1&review_id=' . $newRatingId;
        
        // Immediately redirect to the course page
        header('Location: ' . $redirectUrl);
        exit;
    } else {
        // Fall back to JSON response if no redirect URL
        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'message' => 'Rating submitted successfully']);
        exit;
    }
} else {
    $db->exec("ROLLBACK");
    error_log("Rating submission failed");
    
    // Immediate redirect on error
    if (!empty($redirectUrl)) {
        // Add error parameter
        $redirectUrl .= (strpos($redirectUrl, '?') !== false ? '&' : '?') . 'error=submission_failed';
        
        // Immediately redirect back to the course page
        header('Location: ' . $redirectUrl);
        exit;
    } else {
        // Fall back to JSON response if no redirect URL
        header('Content-Type: application/json');
        echo json_encode(['error' => 'Failed to submit rating']);
        exit;
    }
}
} catch (Exception $e) {
    if (isset($db)) {
        $db->exec("ROLLBACK");
    }
    error_log("Database error: " . $e->getMessage());
    
    // Immediate redirect on error
    if (!empty($redirectUrl)) {
        // Add error parameter
        $redirectUrl .= (strpos($redirectUrl, '?') !== false ? '&' : '?') . 'error=database_error';
        
        // Immediately redirect back to the course page
        header('Location: ' . $redirectUrl);
        exit;
    } else {
        // Also output as JSON for API clients
        header('Content-Type: application/json');
        echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
        exit;
    }
}
