<?php
/**
 * Course Detail Page Template
 * This page displays detailed information about a specific course
 */

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Set up an error handler to catch fatal errors
function fatal_error_handler() {
    $error = error_get_last();
    if ($error !== NULL && $error['type'] === E_ERROR) {
        echo "<h1>Fatal Error</h1>";
        echo "<pre>";
        print_r($error);
        echo "</pre>";
        
        // If we have a database connection, close it properly
        global $db;
        if ($db) {
            $db->close();
        }
    }
}
register_shutdown_function('fatal_error_handler');

// Include database configuration
require_once 'config.php';

// Start session to check if user is logged in
require_once 'session_config.php'; //NEW
session_start();
$isLoggedIn = isset($_SESSION["loggedin"]) && $_SESSION["loggedin"] === true;

// Add these variables right after $isLoggedIn is defined
$email = $password = "";
$email_err = $password_err = $login_err = "";




function normalizeText($text) {
    if (empty($text)) return $text;
    
    // Convert to UTF-8 if it's not already
    $text = mb_convert_encoding($text, 'UTF-8', 'AUTO');
    
    // Normalize Unicode characters (e.g., combining marks)
    if (function_exists('normalizer_normalize')) {
        $text = normalizer_normalize($text, Normalizer::FORM_KC);
    }
    
    // Convert full-width characters to half-width where applicable
    if (function_exists('mb_convert_kana')) {
        $text = mb_convert_kana($text, 'a', 'UTF-8');
    }
    
    return $text;
}

// Debug information - uncomment to see on page
echo "<!-- Debug Information\n";
echo "REQUEST: " . print_r($_GET, true) . "\n";
echo "-->\n";

// Also output to error log
error_log("REQUEST: " . print_r($_GET, true));

// Load JSON data file
$jsonFilePath = __DIR__ . '/sfc_courses.json';
if (!file_exists($jsonFilePath)) {
    echo "Error: JSON data file not found at: $jsonFilePath";
    exit;
}

$jsonData = file_get_contents($jsonFilePath);
if ($jsonData === false) {
    echo "Error: Could not read JSON data file";
    exit;
}

$data = json_decode($jsonData, true);
if ($data === null) {
    echo "Error: Could not parse JSON data. JSON error: " . json_last_error_msg();
    exit;
}

// Get course parameters from the URL
$courseParam = isset($_GET['course']) ? urldecode($_GET['course']) : null;
// Fix potential encoding issues with course name
if ($courseParam) {
    // If we detect broken UTF-8 characters or specific problematic encodings
    if (strpos($courseParam, '�') !== false || !mb_check_encoding($courseParam, 'UTF-8')) {
        error_log("Detected encoding issues with course parameter: " . bin2hex($courseParam));
        // Try to fix by manually setting common course names
        if (strpos($courseParam, 'パーソ') !== false && strpos($courseParam, 'リティ発達論') !== false) {
            $courseParam = 'パーソナリティ発達論';
            error_log("Fixed course name to: パーソナリティ発達論");
        }
    }
}
$professorParam = isset($_GET['professor']) ? $_GET['professor'] : null;
$year = isset($_GET['year']) ? $_GET['year'] : null;

// Debug original and processed course parameter
error_log("Original course parameter: " . (isset($_GET['course']) ? $_GET['course'] : 'null'));
error_log("Processed course parameter: " . ($courseParam ?? 'null'));

// Force a clean reload if we're viewing from a review submission
if (isset($_GET['new_review']) && $_GET['new_review'] == 1) {
    // Set a no-cache header
    header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
    header("Pragma: no-cache");
    header("Expires: 0");
}

// No longer doing any special case handling for course names
// Treat all courses consistently

// Debug output
error_log("Course: " . ($courseParam ?? 'none'));
error_log("Professor: " . ($professorParam ?? 'none'));
error_log("Year: " . ($year ?? 'none'));

// Default to Japanese, but allow language switching
$lang = isset($_GET['lang']) ? $_GET['lang'] : 'ja';
// Convert "jp" to "ja" for internal consistency
if ($lang === 'jp') $lang = 'ja';

// We need at least course name
if (!$courseParam) {
    echo "Course name is required.";
    exit;
}

// Define courseId variable (it was removed earlier but still referenced)
$courseId = null;

// Debug info for URL parameters
error_log("URL Parameters - course: " . ($courseParam ?? 'null') . ", professor: " . ($professorParam ?? 'null') . ", year: " . ($year ?? 'null'));
echo "<!-- Debug info: Course param: " . htmlspecialchars($courseParam ?? 'null') . " -->";
echo "<!-- Debug info: Professor param: " . htmlspecialchars($professorParam ?? 'null') . " -->";

// Special handling if we have a new_review parameter
if (isset($_GET['new_review']) && $_GET['new_review'] == 1) {
    error_log("New review detected! Review ID: " . ($_GET['review_id'] ?? 'unknown'));
    echo "<!-- New review detected! Review ID: " . ($_GET['review_id'] ?? 'unknown') . " -->";
}

// Find the course in our JSON data
$course = null;

// Initialize dbCourse variable
$dbCourse = null;

// First try to find by direct database ID if provided
if ($courseId && is_numeric($courseId)) {
    try {
        error_log("Attempting to connect to database to find course by ID: $courseId");
        $db = new SQLite3('database/ratemyteacher.db');
        
        // Try to find the course in the database
        $stmt = $db->prepare("SELECT id, name, course_code FROM courses WHERE id = :id LIMIT 1");
        $stmt->bindValue(':id', $courseId, SQLITE3_INTEGER);
        $result = $stmt->execute();
        $dbCourse = $result->fetchArray(SQLITE3_ASSOC);
        
        if ($dbCourse) {
            error_log("Found course in database by ID: $courseId, Name: " . ($dbCourse['name'] ?? 'Unknown'));
            
            // If we found the course in DB, see if we can find it in JSON data for additional info
            foreach ($data['courses'] as $c) {
                if (isset($c['course_id']) && $c['course_id'] === $dbCourse['course_code']) {
                    $course = $c;
                    error_log("Found matching course in JSON data by course_code: " . $dbCourse['course_code']);
                    break;
                }
            }
        }
    } catch (Exception $e) {
        error_log("Database error when looking up course by ID: " . $e->getMessage());
    }
}

// Process login form submission (add this right after setting the variables above)
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['login_submit'])) {
    // Check if email is empty
    if (empty(trim($_POST["email"]))) {
        $email_err = "Please enter email.";
    } else {
        $email = trim($_POST["email"]);
    }
    
    // Check if password is empty
    if (empty(trim($_POST["password"]))) {
        $password_err = "Please enter your password.";
    } else {
        $password = trim($_POST["password"]);
    }
    
    // Validate credentials
    if (empty($email_err) && empty($password_err)) {
        try {

            // For SQLite3, we need to use a different approach than mysqli
            $db = new SQLite3('database/ratemyteacher.db');
            $sql = "SELECT id, username, email, password, is_verified FROM users WHERE email = :email";
    
            $loginStmt = $db->prepare($sql);
            if ($loginStmt) {
                // Bind parameters
                $loginStmt->bindValue(':email', $email, SQLITE3_TEXT);
                
                // Execute the statement
                $loginResult = $loginStmt->execute();
                
                // Check if we found a user
                if ($loginRow = $loginResult->fetchArray(SQLITE3_ASSOC)) {
                    // Check password
                    if (password_verify($password, $loginRow['password'])) {
                        // Auto-verify all accounts for development
                        if ($loginRow['is_verified'] == 0) {
                            // Update the user to be verified
                            $verify_sql = "UPDATE users SET is_verified = 1 WHERE id = :id";
                            $verify_stmt = $db->prepare($verify_sql);
                            $verify_stmt->bindValue(':id', $loginRow['id'], SQLITE3_INTEGER);
                            $verify_stmt->execute();
                        }
                        
                        // Store data in session variables
                        $_SESSION["loggedin"] = true;
                        $_SESSION["id"] = $loginRow['id'];
                        $_SESSION["username"] = $loginRow['username'];
                        $_SESSION["email"] = $loginRow['email'];
                        
                        // Make sure session data is saved
                        session_write_close();
                        
                        // Redirect to the same page to refresh with logged in state
                        header("Location: " . $_SERVER['REQUEST_URI']);
                        exit();
                    } else {
                        // Password is not valid
                        $login_err = "Invalid email or password.";
                    }
                } else {
                    // Email doesn't exist
                    $login_err = "Invalid email or password.";
                }
            } else {
                $login_err = "Something went wrong. Please try again later.";
            }
        } catch (Exception $e) {
            $login_err = "Error: " . $e->getMessage();
        }
    }
}

// If course not yet found in JSON data but we have course name parameter, try to find it
if (!$course && $courseParam) {
    error_log("Searching for course by name parameter: $courseParam");
    
    // First, check if this course name exists in database
    try {
        if (!isset($db) || $db === null) {
            $db = new SQLite3('database/ratemyteacher.db');
        }
        
        // Look for course by name in database - try exact and partial match
        $stmt = $db->prepare("SELECT id, name, course_code FROM courses WHERE name = :name OR name LIKE :like_name LIMIT 1");
        $stmt->bindValue(':name', $courseParam, SQLITE3_TEXT);
        $stmt->bindValue(':like_name', '%' . $courseParam . '%', SQLITE3_TEXT);
        $result = $stmt->execute();
        $dbCourseByName = $result->fetchArray(SQLITE3_ASSOC);
// Find this code section in course_page_template.php, around line 245-270
// Before this block:
    $courseName = '';

    $stmt = $db->prepare("SELECT id, name, course_code FROM courses WHERE name = :name OR name LIKE :like_name LIMIT 1");
    $stmt->bindValue(':name', $courseName, SQLITE3_TEXT);
    $stmt->bindValue(':like_name', '%' . $courseName . '%', SQLITE3_TEXT);
    
    // ADD THE JAPANESE TO ENGLISH MAPPING CODE JUST BEFORE THAT:
    
    // Create a mapping of Japanese to English names for both courses and professors
    $jaToEnCourseMap = [];
    $jaToEnProfMap = [];
    
    foreach ($data['courses'] as $c) {
        if (isset($c['translations']['ja']['name']) && isset($c['translations']['en']['name'])) {
            $jaToEnCourseMap[$c['translations']['ja']['name']] = $c['translations']['en']['name'];
        }
        
        foreach ($c['professors'] as $prof) {
            if (isset($prof['name']['ja']) && isset($prof['name']['en'])) {
                $jaToEnProfMap[$prof['name']['ja']] = $prof['name']['en'];
            }
        }
    }
    
    // If we have Japanese course or professor names, also look for their English equivalents
    $altCourseName = isset($jaToEnCourseMap[$courseParam]) ? $jaToEnCourseMap[$courseParam] : null;
    $altProfessorName = isset($jaToEnProfMap[$professorParam]) ? $jaToEnProfMap[$professorParam] : null;
    
    // Debug the language mapping
    error_log("Original course param: $courseParam, Alternative English name: " . ($altCourseName ?? 'none'));
    if ($professorParam) {
        error_log("Original professor param: $professorParam, Alternative English name: " . ($altProfessorName ?? 'none'));
    }
    
    // THEN REPLACE THE ORIGINAL DATABASE QUERY WITH THIS ENHANCED VERSION:
    $stmt = $db->prepare("
        SELECT id, name, course_code FROM courses 
        WHERE name = :name 
        OR name LIKE :like_name
        OR (:alt_name IS NOT NULL AND (name = :alt_name OR name LIKE :alt_like_name))
        LIMIT 1
    ");

    
    $stmt->bindValue(':name', $courseName, SQLITE3_TEXT);
    $stmt->bindValue(':like_name', '%' . $courseName . '%', SQLITE3_TEXT);
    $stmt->bindValue(':alt_name', $altCourseName, SQLITE3_TEXT);
    $stmt->bindValue(':alt_like_name', $altCourseName ? '%' . $altCourseName . '%' : null, SQLITE3_TEXT);

        if ($dbCourseByName) {
            error_log("Found course in database by name: $courseParam, ID: " . $dbCourseByName['id']);
            $dbCourse = $dbCourseByName;
            $courseId = $dbCourseByName['id']; // Update courseId for later use
            
            // If dbCourse was found by name but not by ID earlier, find in JSON
            if (!$course) {
                foreach ($data['courses'] as $c) {
                    if (isset($c['course_id']) && $c['course_id'] === $dbCourseByName['course_code']) {
                        $course = $c;
                        error_log("Found matching course in JSON data by course_code: " . $dbCourseByName['course_code']);
                        break;
                    }
                }
            }
        }
    } catch (Exception $e) {
        error_log("Database error when looking up course by name: " . $e->getMessage());
    }
    
    // If still not found in database or JSON, search in JSON data
    if (!$course) {
        error_log("Course not found in database, searching JSON data");
        foreach ($data['courses'] as $c) {
            $matchesCourseName = false;
            $matchesProfessor = !$professorParam; // If no professor specified, count as match
            
            // Debug to find the course we're looking for
            error_log("Checking course: " . ($c['translations']['en']['name'] ?? 'Unknown'));
            error_log("Looking for: $courseParam");
            
            // Check course name in both languages - exact match with the param
            if (isset($c['translations']['ja']['name']) && 
                strcasecmp($c['translations']['ja']['name'], normalizeText($courseParam)) === 0) {
                $matchesCourseName = true;
                error_log("Found matching course name (JA)");
            } else if (isset($c['translations']['en']['name']) && 
                strcasecmp($c['translations']['en']['name'], $courseParam) === 0) {
                $matchesCourseName = true;
                error_log("Found matching course name (EN)");
            }
            
            // Check professor name if specified - exact match with professor's English name without spaces
            if ($professorParam) {
                foreach ($c['professors'] as $prof) {
                    // Check English name (without spaces)
                    $profNameUrlEn = isset($prof['name']['en']) ? str_replace(' ', '', $prof['name']['en']) : '';
                    
                    // Check Japanese name 
                    $profNameUrlJa = isset($prof['name']['ja']) ? $prof['name']['ja'] : '';
                    
                    error_log("Comparing professor: EN='$profNameUrlEn', JA='$profNameUrlJa' with '$professorParam'");
                    
                    // Case-insensitive comparison with both English and Japanese names
                    if (strcasecmp($profNameUrlEn, normalizeText($professorParam)) === 0 || 
                        strcasecmp($profNameUrlJa, normalizeText($professorParam))=== 0) {
                        $matchesProfessor = true;
                        error_log("Found matching professor");
                        break;
                    }
                }
            }
            
            // Check year match
            $yearMatches = ($year === null || $c['year'] === $year);
            error_log("Year match: " . ($yearMatches ? 'Yes' : 'No') . " (Looking for: $year, Course year: {$c['year']})");
            
            // If all parameters match, this is our course
            if ($matchesCourseName && $matchesProfessor && $yearMatches) {
                $course = $c;
                error_log("FOUND MATCHING COURSE!");
                
                // Now check if this course exists in the database
                try {
                    if (!isset($db) || $db === null) {
                        $db = new SQLite3('database/ratemyteacher.db');
                    }
                    
                    // Try to find by name
                    $courseName = $lang === 'ja' ? 
                        ($c['translations']['ja']['name'] ?? $c['translations']['en']['name']) : 
                        ($c['translations']['en']['name'] ?? $c['translations']['ja']['name']);
                    
                    $stmt = $db->prepare("SELECT id, name, course_code FROM courses WHERE name = :name OR name LIKE :like_name LIMIT 1");
                    $stmt->bindValue(':name', $courseName, SQLITE3_TEXT);
                    $stmt->bindValue(':like_name', '%' . $courseName . '%', SQLITE3_TEXT);
                    $result = $stmt->execute();
                    $foundDbCourse = $result->fetchArray(SQLITE3_ASSOC);
                    
                    if ($foundDbCourse) {
                        $dbCourse = $foundDbCourse;
                        $courseId = $foundDbCourse['id']; // Update courseId for later use
                        error_log("Found database entry for JSON course: ID=" . $foundDbCourse['id']);
                    } else if (isset($c['course_id'])) {
                        // Try to find by course_code
                        $stmt = $db->prepare("SELECT id, name, course_code FROM courses WHERE course_code = :code LIMIT 1");
                        $stmt->bindValue(':code', $c['course_id'], SQLITE3_TEXT);
                        $result = $stmt->execute();
                        $foundDbCourse = $result->fetchArray(SQLITE3_ASSOC);
                        
                        if ($foundDbCourse) {
                            $dbCourse = $foundDbCourse;
                            $courseId = $foundDbCourse['id']; // Update courseId for later use
                            error_log("Found database entry for JSON course by code: ID=" . $foundDbCourse['id']);
                        }
                    }
                } catch (Exception $e) {
                    error_log("Database error when cross-referencing JSON course: " . $e->getMessage());
                }
                
                break;
            }
        }
        
        // If still not found, try a less strict search (partial course name match)
        if (!$course) {
            error_log("No exact match found, trying partial match");
            foreach ($data['courses'] as $c) {
                $matchesProfessor = !$professorParam; // If no professor specified, count as match
                
                // Check if course name contains the search term
                $courseNameJa = $c['translations']['ja']['name'] ?? '';
                $courseNameEn = $c['translations']['en']['name'] ?? '';
                
                // For course page template, we MUST use exact matching to avoid confusion
                $containsCourseName = 
                    (strcasecmp($courseNameJa, $courseParam) === 0) || 
                    (strcasecmp($courseNameEn, $courseParam) === 0);
                    
                if ($containsCourseName) {
                    error_log("Found partial course name match: $courseNameEn");
                }
                
                // Check professor name if specified
                if ($professorParam) {
                    foreach ($c['professors'] as $prof) {
                        $profNameUrl = isset($prof['name']['en']) ? str_replace(' ', '', $prof['name']['en']) : '';
                        if ($profNameUrl === $professorParam) {
                            $matchesProfessor = true;
                            break;
                        }
                    }
                }
                
                // Check year match
                $yearMatches = ($year === null || $c['year'] === $year);
                
                // If all parameters match, this is our course
                if ($containsCourseName && $matchesProfessor && $yearMatches) {
                    $course = $c;
                    error_log("FOUND COURSE BY PARTIAL MATCH!");
                    
                    // Now check if this course exists in the database (same as above)
                    try {
                        if (!isset($db) || $db === null) {
                            $db = new SQLite3('database/ratemyteacher.db');
                        }
                        
                        // Try to find by name
                        $courseName = $lang === 'ja' ? 
                            ($c['translations']['ja']['name'] ?? $c['translations']['en']['name']) : 
                            ($c['translations']['en']['name'] ?? $c['translations']['ja']['name']);
                        
                        $stmt = $db->prepare("SELECT id, name, course_code FROM courses WHERE name = :name OR name LIKE :like_name LIMIT 1");
                        $stmt->bindValue(':name', $courseName, SQLITE3_TEXT);
                        $stmt->bindValue(':like_name', '%' . $courseName . '%', SQLITE3_TEXT);
                        $result = $stmt->execute();
                        $foundDbCourse = $result->fetchArray(SQLITE3_ASSOC);
                        
                        if ($foundDbCourse) {
                            $dbCourse = $foundDbCourse;
                            $courseId = $foundDbCourse['id']; // Update courseId for later use
                            error_log("Found database entry for JSON course: ID=" . $foundDbCourse['id']);
                        } else if (isset($c['course_id'])) {
                            // Try to find by course_code
                            $stmt = $db->prepare("SELECT id, name, course_code FROM courses WHERE course_code = :code LIMIT 1");
                            $stmt->bindValue(':code', $c['course_id'], SQLITE3_TEXT);
                            $result = $stmt->execute();
                            $foundDbCourse = $result->fetchArray(SQLITE3_ASSOC);
                            
                            if ($foundDbCourse) {
                                $dbCourse = $foundDbCourse;
                                $courseId = $foundDbCourse['id']; // Update courseId for later use
                                error_log("Found database entry for JSON course by code: ID=" . $foundDbCourse['id']);
                            }
                        }
                    } catch (Exception $e) {
                        error_log("Database error when cross-referencing JSON course: " . $e->getMessage());
                    }
                    
                    break;
                }
            }
        }
    }
}

// Try to connect to database to find course info if we don't have it yet
try {
    error_log("Attempting to connect to database");
    $db = new SQLite3('database/ratemyteacher.db');
    error_log("Database connection successful");

    // Check database structure
    $tables = [];
    $result = $db->query("SELECT name FROM sqlite_master WHERE type='table'");
    while ($table = $result->fetchArray(SQLITE3_ASSOC)) {
        $tables[] = $table['name'];
    }
    
    // Debug info
    error_log("Database tables: " . implode(", ", $tables));
    
    // Look for course in database if we have numeric ID and haven't found it yet
    if ($courseId && is_numeric($courseId) && in_array('courses', $tables) && !$dbCourse) {
        $stmt = $db->prepare("SELECT id, name, course_code FROM courses WHERE id = :id LIMIT 1");
        $stmt->bindValue(':id', $courseId, SQLITE3_INTEGER);
        $result = $stmt->execute();
        $dbCourse = $result->fetchArray(SQLITE3_ASSOC);
        error_log("Looking for course by direct ID: $courseId, found: " . ($dbCourse ? "yes" : "no"));
    }
} catch (Exception $e) {
    error_log("Database error: " . $e->getMessage());
    $dbCourse = null;
    $db = null;
}

// When coming directly from account page via ID, we can skip the JSON data lookup
$skipJsonRequirement = ($courseId && is_numeric($courseId) && $dbCourse);

// No special case handling for any courses - all courses are treated equally

// If course not found and not viewing by direct ID, show error with more information
if (!$course && !$skipJsonRequirement) {
    echo "<!DOCTYPE html>
<html>
<head>
    <meta charset=\"UTF-8\">
    <meta name=\"viewport\" content=\"width=device-width, initial-scale=1.0\">
    <title>Course Not Found</title>
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; padding: 20px; max-width: 800px; margin: 0 auto; }
        .error-container { background: #f8d7da; border: 1px solid #f5c6cb; padding: 20px; border-radius: 5px; margin-bottom: 20px; }
        .debug-container { background: #e2e3e5; border: 1px solid #d6d8db; padding: 20px; border-radius: 5px; margin-top: 20px; }
        h1 { color: #721c24; }
        pre { background: #f8f9fa; padding: 10px; border-radius: 5px; overflow-x: auto; }
    </style>
</head>
<body>
    <div class=\"error-container\">
        <h1>Course Not Found</h1>
        <p>Sorry, we couldn't find the requested course with the provided parameters.</p>
        <p>Please check the URL and try again.</p>
        <p><a href=\"home.php\">Return to Home Page</a></p>
    </div>
    
    <div class=\"debug-container\">
        <h2>Debug Information</h2>
        <h3>Parameters Received:</h3>
        <pre>Course: " . htmlspecialchars($courseParam ?? 'Not provided') . "
Professor: " . htmlspecialchars($professorParam ?? 'Not provided') . "
Year: " . htmlspecialchars($year ?? 'Not provided') . "
Language: " . htmlspecialchars($lang ?? 'Not provided') . "
ID: " . htmlspecialchars($courseId ?? 'Not provided') . "</pre>
        
        <h3>First 5 Courses in Database:</h3>
        <pre>";
    $count = 0;
    foreach ($data['courses'] as $c) {
        if ($count++ >= 5) break;
        echo "ID: " . htmlspecialchars($c['course_id'] ?? 'Unknown') . "\n";
        echo "Year: " . htmlspecialchars($c['year'] ?? 'Unknown') . "\n";
        echo "Name (EN): " . htmlspecialchars($c['translations']['en']['name'] ?? 'Unknown') . "\n";
        echo "Name (JA): " . htmlspecialchars($c['translations']['ja']['name'] ?? 'Unknown') . "\n";
        echo "Professors: ";
        foreach ($c['professors'] as $prof) {
            echo htmlspecialchars($prof['name']['en'] ?? 'Unknown') . ", ";
        }
        echo "\n\n";
    }
    echo "</pre>
    </div>
</body>
</html>";
    exit;
}

// If viewing from account page (direct ID), create a minimal course object for display
if ($skipJsonRequirement && !$course) {
    // Get course details from database
    try {
        $stmt = $db->prepare("SELECT name, course_code, description, semester FROM courses WHERE id = :id LIMIT 1");
        $stmt->bindValue(':id', $courseId, SQLITE3_INTEGER);
        $result = $stmt->execute();
        $courseDetails = $result->fetchArray(SQLITE3_ASSOC);
        
        if ($courseDetails) {
            // Extract field information from description if possible
            $fieldInfo = 'Fundamental Subjects - Interdisciplinary Subjects';
            
            // Try to extract field info from description
            if (!empty($courseDetails['description'])) {
                // Parse description for field info
                if (preg_match('/Japanese Field:\s*([^\n]+)/i', $courseDetails['description'], $matches)) {
                    $jaField = trim($matches[1]);
                    $fieldInfo = $jaField;
                }
                if (preg_match('/English Field:\s*([^\n]+)/i', $courseDetails['description'], $matches)) {
                    $enField = trim($matches[1]);
                    if ($lang === 'en') {
                        $fieldInfo = $enField;
                    }
                }
            }
            
            // Special handling for course ID 30 (パーソナリティ発達論)
            if ($courseId == 30) {
                $fieldInfo = $lang === 'ja' ? '基盤科目-超域科目' : 'Fundamental Subjects - Interdisciplinary Subjects';
                
                // Check if professor Yoko Hamada exists
                $profStmt = $db->prepare("SELECT id, name FROM professors WHERE name LIKE '%Hamada%' OR name LIKE '%Yoko%' LIMIT 1");
                $profResult = $profStmt->execute();
                $professorDetails = $profResult->fetchArray(SQLITE3_ASSOC);
                
                // Create a professor entry if found
                $professors = [];
                if ($professorDetails) {
                    $professors[] = [
                        'id' => $professorDetails['id'],
                        'name' => [
                            'en' => 'Yoko Hamada',
                            'ja' => '濱田 陽子'
                        ],
                        'department' => [
                            'en' => 'Faculty of Environment and Information Studies',
                            'ja' => '環境情報学部'
                        ]
                    ];
                }
            } else {
                // Default empty professors array for other courses
                $professors = [];
            }
            
            // Create a minimal course object
            $course = [
                'course_id' => $courseDetails['course_code'] ?? $courseId,
                'year' => '2023&2024',
                'semester' => $courseDetails['semester'] ?? 'spring',  // Include semester from database, default to spring if not set
                'translations' => [
                    'en' => ['name' => $courseDetails['name'], 'field' => $fieldInfo],
                    'ja' => ['name' => $courseDetails['name'], 'field' => $fieldInfo]
                ],
                'professors' => $professors
            ];
            
            // Set translation to current language
            $translation = $course['translations'][$lang];
            
            error_log("Created enhanced course object for DB ID: $courseId, Name: {$courseDetails['name']}");
        } else {
            error_log("Failed to get course details for ID: $courseId");
        }
    } catch (Exception $e) {
        error_log("Error getting course details: " . $e->getMessage());
    }
}

// Language was already set above

// Get translation for this language
$translation = $course['translations'][$lang] ?? null;
if (!$translation) {
    echo "Translation not available for selected language.";
    exit;
}

// Connect to database to get ratings (only if we don't already have a connection)
if (!isset($db) || $db === null) {
    try {
        error_log("Attempting to connect to database");
        $db = new SQLite3('database/ratemyteacher.db');
        error_log("Database connection successful");
    } catch (Exception $e) {
        error_log("Database connection error: " . $e->getMessage());
        $db = null;
    }
} 

// If we have a database connection, proceed with querying
if ($db) {
    try {
        error_log("Using database connection");
        
        // Let's check database structure to avoid errors
        $tables = [];
        $result = $db->query("SELECT name FROM sqlite_master WHERE type='table'");
        while ($table = $result->fetchArray(SQLITE3_ASSOC)) {
            $tables[] = $table['name'];
        }
        
        // Debug info
        error_log("Database tables: " . implode(", ", $tables));
        
        // Only proceed if courses table exists
        $dbCourse = null;
        if (in_array('courses', $tables)) {
        // Check columns in courses table to avoid "no such column" errors
        $columns = [];
        $result = $db->query("PRAGMA table_info(courses)");
        while ($column = $result->fetchArray(SQLITE3_ASSOC)) {
            $columns[] = $column['name'];
        }
        
        error_log("Courses table columns: " . implode(", ", $columns));
        
        // Attempt to find the course in the database using appropriate columns
        // First try to find by direct ID match (when linked from account page)
        if ($courseId && is_numeric($courseId)) {
            $stmt = $db->prepare("SELECT id FROM courses WHERE id = :id LIMIT 1");
            $stmt->bindValue(':id', $courseId, SQLITE3_INTEGER);
            $result = $stmt->execute();
            $dbCourse = $result->fetchArray(SQLITE3_ASSOC);
            error_log("Looking up course by direct ID: $courseId, found: " . ($dbCourse ? "yes" : "no"));
        }
        
        // If not found by direct ID, try course_id field
        if (!$dbCourse && in_array('course_id', $columns) && $courseId) {
            $stmt = $db->prepare("SELECT id FROM courses WHERE course_id = :course_id LIMIT 1");
            $stmt->bindValue(':course_id', $courseId, SQLITE3_TEXT);
            $result = $stmt->execute();
            $dbCourse = $result->fetchArray(SQLITE3_ASSOC);
            error_log("Looking up course by course_id field: $courseId, found: " . ($dbCourse ? "yes" : "no"));
        } 
        
        // If still not found, try by name
        if (!$dbCourse && in_array('name', $columns) && isset($translation['name'])) {
            $stmt = $db->prepare("SELECT id FROM courses WHERE name = :name LIMIT 1");
            $stmt->bindValue(':name', $translation['name'], SQLITE3_TEXT);
            $result = $stmt->execute();
            $dbCourse = $result->fetchArray(SQLITE3_ASSOC);
            error_log("Looking up course by name: " . $translation['name'] . ", found: " . ($dbCourse ? "yes" : "no"));
        } 
        
        if (!$dbCourse) {
            error_log("Could not find course in database. Course ID: $courseId");
        }
    } else {
        error_log("Courses table not found in database");
    }
    } catch (Exception $e) {
        error_log("Database error: " . $e->getMessage());
        $dbCourse = null;
        $db = null;
    }
}

$hasRatings = false;
$avgRating = 0;
$ratingCount = 0;
$reviews = [];

// Get a courseId variable for backward compatibility with existing code
$courseId = $dbCourse['id'] ?? null;

// If course exists in the database and database is connected, get ratings
if ($dbCourse && $db) {
    try {
        $courseDbId = $dbCourse['id'];
        
        // Check if ratings table exists
        $tablesResult = $db->query("SELECT name FROM sqlite_master WHERE type='table' AND name='ratings'");
        $ratingsTableExists = $tablesResult->fetchArray(SQLITE3_ASSOC) !== false;
        
        if ($ratingsTableExists) {
            // Check columns in ratings table
            $columns = [];
            $result = $db->query("PRAGMA table_info(ratings)");
            while ($column = $result->fetchArray(SQLITE3_ASSOC)) {
                $columns[] = $column['name'];
            }
            
            error_log("Ratings table columns: " . implode(", ", $columns));
            
            // Only proceed if needed columns exist
            if (in_array('course_id', $columns) && in_array('rating', $columns)) {
                // For debugging purposes, check if there are any ratings at all
                $checkStmt = $db->prepare("SELECT COUNT(*) as total FROM ratings");
                $checkResult = $checkStmt->execute();
                $totalRatings = $checkResult->fetchArray(SQLITE3_ASSOC)['total'];
                error_log("DEBUG: Total ratings in database: $totalRatings");
                echo "<!-- DEBUG: Total ratings in database: $totalRatings -->";
                
                // Simplified approach - just get all ratings for this course
                $stmt = $db->prepare("
                    SELECT AVG(rating) as avg_rating, COUNT(id) as rating_count 
                    FROM ratings 
                    WHERE course_id = :course_id
                ");
                $stmt->bindValue(':course_id', $courseDbId, SQLITE3_INTEGER);
                error_log("Using only course_id filter to get all ratings for this course");
                $result = $stmt->execute();
                $ratingData = $result->fetchArray(SQLITE3_ASSOC);
                
                error_log("DEBUG: Found " . ($ratingData ? $ratingData['rating_count'] : 0) . " ratings for course ID: $courseDbId");
                echo "<!-- DEBUG: Rating data for courseDbId $courseDbId: " . print_r($ratingData, true) . " -->";
                
                if ($ratingData && $ratingData['rating_count'] > 0) {
                    $hasRatings = true;
                    $avgRating = number_format((float)$ratingData['avg_rating'], 1);
                    $ratingCount = $ratingData['rating_count'];
                    
                    // Wrap this section in a try/catch to isolate errors
                    try {
                        // Get reviews if user is logged in
                        // Debug reviews
                        error_log("Fetching reviews for course ID: $courseDbId");
                        
                        // Always try to fetch reviews (not just when logged in)
                        // Check users table
                        $usersTableExists = $db->query("SELECT name FROM sqlite_master WHERE type='table' AND name='users'")->fetchArray(SQLITE3_ASSOC) !== false;
                        
                        if ($usersTableExists) {
                            // First, check how many raw ratings we have for this course
                            $countStmt = $db->prepare("SELECT COUNT(*) as count FROM ratings WHERE course_id = :course_id");
                            $countStmt->bindValue(':course_id', $courseDbId, SQLITE3_INTEGER);
                            $countResult = $countStmt->execute();
                            $ratingCount = $countResult->fetchArray(SQLITE3_ASSOC)['count'];
                            
                            error_log("DEBUG: Found $ratingCount ratings for course ID $courseDbId");
                            echo "<!-- DEBUG: Found $ratingCount ratings for course ID $courseDbId -->";
                            
                            // Simplify the process - just get raw ratings
                            error_log("Fetching raw ratings for course ID: $courseDbId");
                            
                            try {
                                // Create simple reviews with minimal error potential
                                $reviews = [];
                                
                                // First check what columns are available in the ratings table
                                $availableColumns = $columns; // We already have this from earlier PRAGMA query
                                
                                // Build the SQL query based on available columns
                                $selectColumns = "id, rating"; // These should always exist
                                
                                // Add optional columns if they exist
                                if (in_array('content_rating', $availableColumns)) $selectColumns .= ", content_rating";
                                if (in_array('difficulty_rating', $availableColumns)) $selectColumns .= ", difficulty_rating";
                                if (in_array('grade', $availableColumns)) $selectColumns .= ", grade";
                                if (in_array('comment', $availableColumns)) $selectColumns .= ", comment";
                                if (in_array('textbook', $availableColumns)) $selectColumns .= ", textbook";
                                if (in_array('attendance_check', $availableColumns)) $selectColumns .= ", attendance_check";
                                if (in_array('first_half', $availableColumns)) $selectColumns .= ", first_half";
                                if (in_array('second_half', $availableColumns)) $selectColumns .= ", second_half";
                                if (in_array('user_id', $availableColumns)) $selectColumns .= ", user_id";
                                if (in_array('created_at', $availableColumns)) $selectColumns .= ", created_at";
                                if (in_array('is_anonymous', $availableColumns)) $selectColumns .= ", is_anonymous";
                                
                                // Modify SQL to specifically look for reviews for this exact course with professor
                                // We need to get professor_id to make this work
                                $professorId = null;
                                
                                // Try to get the professor ID based on the professor parameter
                                if ($professorParam) {
                                    $profStmt = $db->prepare("
                                        SELECT id FROM professors 
                                        WHERE REPLACE(name, ' ', '') = :name 
                                        LIMIT 1
                                    ");
                                    $profStmt->bindValue(':name', $professorParam, SQLITE3_TEXT);
                                    $profResult = $profStmt->execute();
                                    $profRow = $profResult->fetchArray(SQLITE3_ASSOC);
                                    
                                    if ($profRow) {
                                        $professorId = $profRow['id'];
                                        error_log("Found professor ID: $professorId for $professorParam");
                                    }
                                }
                                
                                // We're going to use professor filtering when available
                                // This will ensure reviews are properly filtered by both course and professor
                                $sql = "SELECT $selectColumns FROM ratings WHERE course_id = :course_id";
                                
                                // Add professor filtering if we have a professor ID
                                if ($professorId) {
                                    $sql .= " AND professor_id = :professor_id";
                                    error_log("Including professor filter in query with ID: $professorId");
                                }
                                
                                $sql .= " ORDER BY created_at DESC";
                                error_log("SQL Query for reviews: $sql");
                                
                                // Get ratings without any joins but get all rating fields
                                $simpleStmt = $db->prepare($sql);
                                
                                // Check if prepare was successful
                                if ($simpleStmt === false) {
                                    error_log("Error preparing statement: " . $db->lastErrorMsg());
                                    throw new Exception("Database prepare error: " . $db->lastErrorMsg());
                                }
                                
                                $simpleStmt->bindValue(':course_id', $courseDbId, SQLITE3_INTEGER);
                                
                                // Bind professor ID if we're filtering by professor
                                if ($professorId) {
                                    $simpleStmt->bindValue(':professor_id', $professorId, SQLITE3_INTEGER);
                                }
                                $simpleResult = $simpleStmt->execute();
                                
                                // Process each rating
                                while ($row = $simpleResult->fetchArray(SQLITE3_ASSOC)) {
                                    // Format the date if possible
                                    $formattedDate = 'Unknown Date';
                                    if (isset($row['created_at']) && !empty($row['created_at'])) {
                                        try {
                                            $timestamp = strtotime($row['created_at']);
                                            if ($timestamp !== false) {
                                                $formattedDate = date('F j, Y', $timestamp);
                                            }
                                        } catch (Exception $dateEx) {
                                            error_log("Error formatting date: " . $dateEx->getMessage());
                                        }
                                    }
                                    
                                    // Check if review is anonymous
                                    $isAnonymous = isset($row['is_anonymous']) && $row['is_anonymous'] == 1;

                                    // Get actual username if not anonymous
                                    $username = 'Student'; // Default for anonymous reviews
                                    if (!$isAnonymous && isset($row['user_id']) && $row['user_id'] > 0) {
                                        try {
                                            $userStmt = $db->prepare("SELECT username FROM users WHERE id = :user_id LIMIT 1");
                                            $userStmt->bindValue(':user_id', $row['user_id'], SQLITE3_INTEGER);
                                            $userResult = $userStmt->execute();
                                            $userData = $userResult->fetchArray(SQLITE3_ASSOC);
                                            if ($userData && isset($userData['username'])) {
                                                $username = $userData['username'];
                                            }
                                        } catch (Exception $e) {
                                            error_log("Error getting username: " . $e->getMessage());
                                            // Keep default username if there's an error
                                        }
                                    }

                                    // Add to reviews array with username and user_id for deletion
                                    $reviewData = [
                                        'id' => $row['id'],
                                        'rating' => $row['rating'] ?? 3, // Default to 3 if missing
                                        'created_at' => $formattedDate,
                                        'username' => $username,
                                        'user_id' => isset($row['user_id']) ? $row['user_id'] : 0,  // Include user_id for permission checking
                                        'is_anonymous' => $isAnonymous // Include anonymous flag
                                    ];
                                    
                                    // Add optional fields if they exist (cast numeric values to appropriate types)
                                    if (array_key_exists('content_rating', $row)) $reviewData['content_rating'] = (float)$row['content_rating'];
                                    if (array_key_exists('difficulty_rating', $row)) $reviewData['difficulty_rating'] = (float)$row['difficulty_rating'];
                                    if (array_key_exists('grade', $row)) $reviewData['grade'] = $row['grade'];
                                    if (array_key_exists('comment', $row)) $reviewData['comment'] = $row['comment'];
                                    if (array_key_exists('textbook', $row)) $reviewData['textbook'] = $row['textbook'];
                                    if (array_key_exists('attendance_check', $row)) $reviewData['attendance_check'] = $row['attendance_check'];
                                    if (array_key_exists('first_half', $row)) $reviewData['first_half'] = $row['first_half'];
                                    if (array_key_exists('second_half', $row)) $reviewData['second_half'] = $row['second_half'];
                                    
                                    // Debug review data
                                    error_log("Review data for ID " . $row['id'] . ": " . print_r($reviewData, true));
                                    
                                    $reviews[] = $reviewData;
                                }
                                
                                error_log("DEBUG: Processed " . count($reviews) . " reviews for display");
                            } catch (Exception $ratingEx) {
                                error_log("Error processing ratings: " . $ratingEx->getMessage());
                                // Create a sample review to show the error
                                $reviews = [[
                                    'id' => 0,
                                    'rating' => 3,
                                    'comment' => 'Error loading reviews: ' . $ratingEx->getMessage(),
                                    'created_at' => date('F j, Y'),
                                    'username' => 'System'
                                ]];
                            }
                        } else {
                            error_log("Users table not found in database");
                        }
                    } catch (Exception $reviewEx) {
                        error_log("Error in review section: " . $reviewEx->getMessage());
                        // Create a fallback review to display the error
                        $reviews = [[
                            'id' => 0, 
                            'rating' => 3,
                            'comment' => 'Error loading reviews section: ' . $reviewEx->getMessage(),
                            'created_at' => date('F j, Y'),
                            'username' => 'System'
                        ]];
                    }
                }
            } else {
                error_log("Required columns not found in ratings table");
            }
        } else {
            error_log("Ratings table not found in database");
        }
    } catch (Exception $e) {
        error_log("Error fetching ratings: " . $e->getMessage());
    }
}

// Create the star rating display
function generateStarRating($rating) {
    $fullStars = floor($rating);
    $halfStar = $rating - $fullStars >= 0.5;
    $emptyStars = 5 - $fullStars - ($halfStar ? 1 : 0);
    
    $html = '';
    
    // Full stars
    for ($i = 0; $i < $fullStars; $i++) {
        $html .= '★';
    }
    
    // Half star
    if ($halfStar) {
        $html .= '★';
    }
    
    // Empty stars
    for ($i = 0; $i < $emptyStars; $i++) {
        $html .= '☆';
    }
    
    return $html;
}

// Get the opposite language for language switcher
$oppositeLang = $lang === 'ja' ? 'en' : 'ja';
$oppositeLabel = $lang === 'ja' ? 'English' : '日本語';

// Page title based on course name
$pageTitle = htmlspecialchars($translation['name']);

// Get semester info
$semester = $lang === 'ja' ? '春学期' : 'Spring Semester'; // Default value

// Get semester from database if available
if (isset($course['semester'])) {
    // Format the semester based on the value and language
    if ($course['semester'] === 'spring') {
        $semester = $lang === 'ja' ? '春学期' : 'Spring Semester';
    } elseif ($course['semester'] === 'fall') {
        $semester = $lang === 'ja' ? '秋学期' : 'Fall Semester';
    } else {
        // For any other value, just display it with the word "Semester"
        $semester = ucfirst($course['semester']) . ($lang === 'ja' ? '学期' : ' Semester');
    }
}

// Set up category scores for display
$categoryScores = [
    'content' => [
        'score' => 0, 
        'percent' => 0, 
        'label' => $lang === 'ja' ? '授業の質' : 'Content Quality',
        'description' => $lang === 'ja' ? '高いほど良い' : 'Higher is better',
        'color' => '#6c757d' // Default grey (no reviews)
    ],
    'difficulty' => [
        'score' => 0, 
        'percent' => 0, 
        'raw_score' => 0,
        'label' => $lang === 'ja' ? '難易度' : 'Difficulty',
        'description' => $lang === 'ja' ? '高いほど難しい' : 'Higher = more difficult',
        'color' => '#6c757d' // Default grey (no reviews)
    ]
];

// If we have reviews, calculate average content and difficulty ratings
if (!empty($reviews)) {
    $totalContent = 0;
    $totalDifficulty = 0;
    $reviewsWithRatings = 0;
    
    // We'll calculate scores based on properly filtered reviews
    // The reviews array has already been filtered to specific course+professor
    foreach ($reviews as $review) {
        if (isset($review['content_rating'])) {
            $totalContent += $review['content_rating'];
            $totalDifficulty += $review['difficulty_rating'] ?? $review['content_rating'];
            $reviewsWithRatings++;
        }
    }
    
    if ($reviewsWithRatings > 0) {
        // Content quality: higher is better
        $categoryScores['content']['score'] = round($totalContent / $reviewsWithRatings, 1);
        $categoryScores['content']['percent'] = min(100, ($categoryScores['content']['score'] / 5) * 100);
        
        // Content quality colors: Red (bad) -> Orange (average) -> Green (good)
        if ($categoryScores['content']['score'] >= 3.5) {
            $categoryScores['content']['color'] = '#28a745'; // green for good quality (3.5-5)
        } else if ($categoryScores['content']['score'] >= 2.5) {
            $categoryScores['content']['color'] = '#fd7e14'; // orange for average quality (2.5-3.5)
        } else {
            $categoryScores['content']['color'] = '#dc3545'; // red for poor quality (1-2.5)
        }
        
        // Difficulty: for difficulty, higher score = more difficult = worse
        $categoryScores['difficulty']['score'] = round($totalDifficulty / $reviewsWithRatings, 1);
        $categoryScores['difficulty']['raw_score'] = $categoryScores['difficulty']['score'];
        
        // For difficulty, show the actual percentage of difficulty (1/5 = 20% filled, 5/5 = 100% filled)
        $difficultyPercent = min(100, ($categoryScores['difficulty']['score'] / 5) * 100);
        
        // Difficulty colors: Green (easy) -> Orange (medium) -> Red (hard)
        if ($categoryScores['difficulty']['score'] <= 2.5) {
            $categoryScores['difficulty']['color'] = '#28a745'; // green for easy (1-2.5)
        } else if ($categoryScores['difficulty']['score'] <= 3.5) {
            $categoryScores['difficulty']['color'] = '#fd7e14'; // orange for medium (2.5-3.5)
        } else {
            $categoryScores['difficulty']['color'] = '#dc3545'; // red for hard (3.5-5)
        }
        
        // Show the actual difficulty percentage (1 = 20% filled, 5 = 100% filled)
        $categoryScores['difficulty']['percent'] = $difficultyPercent;
    } else {
        // If we don't have reviews with ratings but we have professor_id and course_id
        // Try to get content quality and difficulty direct from ratings table
        if (isset($professorId) && $professorId && isset($db) && $db) {
            try {
                $contentStmt = $db->prepare("
                    SELECT AVG(content_rating) as avg_content, AVG(difficulty_rating) as avg_difficulty
                    FROM ratings
                    WHERE course_id = :course_id AND professor_id = :professor_id
                    AND content_rating IS NOT NULL AND difficulty_rating IS NOT NULL
                ");
                $contentStmt->bindValue(':course_id', $courseDbId, SQLITE3_INTEGER);
                $contentStmt->bindValue(':professor_id', $professorId, SQLITE3_INTEGER);
                $contentResult = $contentStmt->execute();
                $contentData = $contentResult->fetchArray(SQLITE3_ASSOC);
                
                if ($contentData && $contentData['avg_content'] > 0) {
                    // Content quality: higher is better
                    $categoryScores['content']['score'] = round($contentData['avg_content'], 1);
                    $categoryScores['content']['percent'] = min(100, ($categoryScores['content']['score'] / 5) * 100);
                    
                    // Content quality colors: Red (bad) -> Orange (average) -> Green (good)
                    if ($categoryScores['content']['score'] >= 3.5) {
                        $categoryScores['content']['color'] = '#28a745'; // green for good quality (3.5-5)
                    } else if ($categoryScores['content']['score'] >= 2.5) {
                        $categoryScores['content']['color'] = '#fd7e14'; // orange for average quality (2.5-3.5)
                    } else {
                        $categoryScores['content']['color'] = '#dc3545'; // red for poor quality (1-2.5)
                    }
                    
                    // Difficulty
                    $categoryScores['difficulty']['score'] = round($contentData['avg_difficulty'], 1);
                    $categoryScores['difficulty']['raw_score'] = $categoryScores['difficulty']['score'];
                    
                    // For difficulty, show the actual percentage of difficulty (1/5 = 20% filled, 5/5 = 100% filled)
                    $difficultyPercent = min(100, ($categoryScores['difficulty']['score'] / 5) * 100);
                    
                    // Difficulty colors: Green (easy) -> Orange (medium) -> Red (hard)
                    if ($categoryScores['difficulty']['score'] <= 2.5) {
                        $categoryScores['difficulty']['color'] = '#28a745'; // green for easy (1-2.5)
                    } else if ($categoryScores['difficulty']['score'] <= 3.5) {
                        $categoryScores['difficulty']['color'] = '#fd7e14'; // orange for medium (2.5-3.5)
                    } else {
                        $categoryScores['difficulty']['color'] = '#dc3545'; // red for hard (3.5-5)
                    }
                    
                    // Show the actual difficulty percentage (1 = 20% filled, 5 = 100% filled)
                    $categoryScores['difficulty']['percent'] = $difficultyPercent;
                }
            } catch (Exception $e) {
                error_log("Error getting content/difficulty ratings: " . $e->getMessage());
            }
        }
    }
}

// Initialize grade distribution and count variables
$gradeDistribution = [
    'S' => 0,
    'A' => 0,
    'B' => 0,
    'C' => 0,
    'D' => 0,
    'F' => 0,
    'P' => 0
];
$totalGradeCount = 0;
$failureCount = 0;
$failureRate = 0;

// If we have a database connection and course ID, get grade distribution data
if ($dbCourse && $db) {
    try {
        $courseDbId = $dbCourse['id'];
        
        // If we have reviews, use them to calculate grade distribution
        // This ensures we display the data immediately after review submission
        if (!empty($reviews)) {
            foreach ($reviews as $review) {
                if (!empty($review['grade']) && isset($gradeDistribution[$review['grade']])) {
                    $gradeDistribution[$review['grade']]++;
                    $totalGradeCount++;
                    
                    // Count failures
                    if ($review['grade'] === 'D' || $review['grade'] === 'F') {
                        $failureCount++;
                    }
                }
            }
            
            // Calculate failure rate
            if ($totalGradeCount > 0) {
                $failureRate = round(($failureCount / $totalGradeCount) * 100);
                
                // Convert counts to percentages
                foreach ($gradeDistribution as $grade => $count) {
                    $gradeDistribution[$grade] = round(($count / $totalGradeCount) * 100);
                }
            }
        } else {
            // As a fallback, check if the ratings table has a grade column
            $hasGradeColumn = false;
            $ratingColumns = [];
            $columnsResult = $db->query("PRAGMA table_info('ratings')");
            while ($col = $columnsResult->fetchArray(SQLITE3_ASSOC)) {
                $ratingColumns[] = $col['name'];
                if ($col['name'] === 'grade') {
                    $hasGradeColumn = true;
                }
            }
            
            error_log("Rating columns: " . implode(", ", $ratingColumns));
            echo "<!-- Rating columns: " . implode(", ", $ratingColumns) . " -->";
            
            // Only attempt to query grades if the column exists
            if ($hasGradeColumn) {
                // Build SQL with optional professor filter for grade distribution
                $gradeSql = "
                    SELECT grade, COUNT(*) as count
                    FROM ratings
                    WHERE course_id = :course_id 
                      AND grade IS NOT NULL 
                      AND grade != ''
                ";
                
                // Add professor filtering if we have a professor ID
                if (isset($professorId) && $professorId) {
                    $gradeSql .= " AND professor_id = :professor_id";
                    error_log("Including professor filter in grade distribution query with ID: $professorId");
                }
                
                $gradeSql .= " GROUP BY grade";
                
                $stmt = $db->prepare($gradeSql);
                $stmt->bindValue(':course_id', $courseDbId, SQLITE3_INTEGER);
                
                // Bind professor ID if we're filtering by professor
                if (isset($professorId) && $professorId) {
                    $stmt->bindValue(':professor_id', $professorId, SQLITE3_INTEGER);
                }
                
                error_log("Grade distribution SQL: $gradeSql");
                $result = $stmt->execute();
            } else {
                error_log("No grade column in ratings table, skipping grade distribution");
                // Skip grade distribution calculation
            }
            
            // Process each grade group if we have grade data
            if ($hasGradeColumn && isset($result)) {
                while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
                    $grade = $row['grade'];
                    $count = $row['count'];
                    
                    // Only count valid grades
                    if (isset($gradeDistribution[$grade])) {
                        $gradeDistribution[$grade] = $count;
                        $totalGradeCount += $count;
                        
                        // Count failures (D and F)
                        if ($grade === 'D' || $grade === 'F') {
                            $failureCount += $count;
                        }
                    }
                }
                
                // Calculate failure rate if we have any grades
                if ($totalGradeCount > 0) {
                    $failureRate = round(($failureCount / $totalGradeCount) * 100);
                }
                
                // Convert counts to percentages for display
                if ($totalGradeCount > 0) {
                    foreach ($gradeDistribution as $grade => $count) {
                        // Calculate percentage and round to nearest whole number
                        $gradeDistribution[$grade] = round(($count / $totalGradeCount) * 100);
                    }
                }
            } else {
                // No grade data available from database query, but we might have data from reviews
                if (empty($reviews)) {
                    error_log("No grade data available for distribution");
                }
            }
        }
    } catch (Exception $e) {
        error_log("Error calculating grade distribution: " . $e->getMessage());
    }
}

// No sample reviews
$sampleReviews = [];
?>
<!DOCTYPE html>
<html lang="<?php echo $lang; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <script async src="https://pagead2.googlesyndication.com/pagead/js/adsbygoogle.js?client=ca-pub-1649331460122770"
    crossorigin="anonymous"></script>
    <title><?php echo $pageTitle; ?> - Rate My Teacher</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Arial', sans-serif;
        }
        
        :root {
            --primary-color: #1e3a8a;
            --secondary-color: #6c757d;
            --light-gray: #f8f9fa;
            --dark-gray: #343a40;
            --success-color: #28a745;
            --warning-color: #ffc107;
        }
        
        body {
            font-family: 'Arial', sans-serif;
            line-height: 1.6;
            color: #333;
            margin: 0;
            padding: 0;
            background-color: #f5f5f5;
        }
        
        .container {
            max-width: 1000px;
            margin: 0 auto;
            padding: 20px;
        }
        
        header {
            background-color: var(--primary-color);
            color: white;
            padding: 10px 0;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        
        .navbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0 20px;
        }
        
        .logo {
            font-size: 24px;
            font-weight: bold;
        }
        
        .logo a {
            color: white;
            text-decoration: none;
        }
        
        .nav-links {
            display: flex;
            align-items: center;
        }
        
        .nav-links a {
            color: white;
            text-decoration: none;
            margin-left: 20px;
            transition: opacity 0.3s;
        }
        
        .nav-links a:hover {
            opacity: 0.8;
        }
        
        .language-toggle {
            display: flex;
            align-items: center;
            margin-left: 20px;
        }
        
        .language-toggle button {
            background: transparent;
            border: 1px solid white;
            color: white;
            padding: 5px 10px;
            margin: 0 5px;
            cursor: pointer;
            border-radius: 4px;
            transition: background-color 0.3s;
        }
        
        .language-toggle button.active {
            background-color: white;
            color: var(--primary-color);
        }
        
        .course-header {
            background-color: white;
            border-radius: 8px;
            padding: 20px;
            margin: 20px 0;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }
        
        .course-title {
            font-size: 32px;
            margin-bottom: 5px;
            color: var(--dark-gray);
        }
        
        .course-subtitle {
            color: var(--secondary-color);
            margin-bottom: 15px;
            display: flex;
            flex-wrap: wrap;
            gap: 15px;
        }
        
        .course-subtitle span {
            display: flex;
            align-items: center;
        }
        
        .course-subtitle svg {
            margin-right: 5px;
        }
        
        .rating-overview {
            display: flex;
            margin: 30px 0;
            gap: 20px;
            flex-wrap: wrap;
        }
        
        .overall-rating {
            flex: 0 0 200px;
            text-align: center;
            padding: 20px;
            background-color: white;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }
        
        .rating-number {
            font-size: 64px;
            font-weight: bold;
            color: var(--primary-color);
            line-height: 1;
        }
        
        .stars {
            color: var(--warning-color);
            font-size: 24px;
            margin: 10px 0;
        }
        
        .rating-label {
            color: var(--secondary-color);
            font-size: 14px;
        }
        
        .rating-details {
            flex: 1;
            display: flex;
            flex-direction: column;
            gap: 15px;
            background-color: white;
            border-radius: 8px;
            padding: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }
        
        .rating-category {
            display: flex;
            align-items: center;
        }
        
        .category-name {
            flex: 0 0 150px;
            font-weight: 500;
        }
        
        .progress-bar {
            height: 10px;
            flex: 1;
            background-color: #e9ecef;
            border-radius: 5px;
            overflow: hidden;
        }
        
        .progress {
            height: 100%;
            background-color: var(--primary-color);
        }
        
        .category-score {
            flex: 0 0 40px;
            text-align: right;
            font-weight: 500;
            color: var(--primary-color);
        }
        
        .professor-info {
            background-color: white;
            border-radius: 8px;
            padding: 20px;
            margin: 20px 0;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }
        
        .section-title {
            font-size: 24px;
            margin-bottom: 20px;
            color: var(--dark-gray);
            border-bottom: 2px solid var(--light-gray);
            padding-bottom: 10px;
        }
        
        .professors {
            display: flex;
            gap: 20px;
            flex-wrap: wrap;
        }
        
        .professor-card {
            flex: 1 0 200px;
            max-width: 300px;
            padding: 15px;
            border-radius: 8px;
            border: 1px solid var(--light-gray);
            background-color: white;
            transition: transform 0.3s, box-shadow 0.3s;
        }
        
        .professor-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        
        .professor-card a {
            text-decoration: none;
            color: var(--dark-gray);
        }
        
        .professor-name {
            font-size: 18px;
            font-weight: bold;
            margin-bottom: 5px;
        }
        
        .professor-department {
            color: var(--secondary-color);
            font-size: 14px;
            margin-bottom: 10px;
        }
        
        .professor-rating {
            display: flex;
            align-items: center;
            color: var(--primary-color);
            font-weight: bold;
        }
        
        .professor-rating .stars {
            font-size: 16px;
            margin: 0 5px 0 0;
        }
        
        .reviews {
            background-color: white;
            border-radius: 8px;
            padding: 20px;
            margin: 20px 0;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }
        
        .review-card {
            border-bottom: 1px solid var(--light-gray);
            padding: 20px 0;
            margin-bottom: 10px;
        }
        
        .review-card:last-child {
            border-bottom: none;
        }
        
        .review-header {
            display: flex;
            justify-content: space-between;
            margin-bottom: 10px;
        }
        
        .reviewer {
            font-weight: bold;
        }
        
        .review-date {
            color: var(--secondary-color);
            font-size: 14px;
        }
        
        .review-rating {
            color: var(--warning-color);
            margin: 10px 0;
        }
        
        .review-content {
            line-height: 1.6;
        }
        
        .review-tags {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin-top: 15px;
        }
        
        .tag {
            background-color: var(--light-gray);
            color: var(--secondary-color);
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 12px;
        }
        
        .add-review-btn {
            display: inline-block;
            background-color: var(--primary-color);
            color: white;
            padding: 10px 20px;
            border-radius: 5px;
            text-decoration: none;
            font-weight: bold;
            margin-top: 20px;
            transition: background-color 0.3s;
        }
        
        .add-review-btn:hover {
            background-color: #3a70c5;
        }
        
        .course-info {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }
        
/* More aggressive fix for the grade distribution title and chart */
        .info-card {
            background-color: white;
            border-radius: 8px;
            padding: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            overflow: visible;
        }

        .info-title {
            font-size: 18px;
            font-weight: bold;
            margin-bottom: 60px; /* Drastically increased space below title */
            color: var(--dark-gray);
            border-bottom: 2px solid var(--light-gray);
            padding-bottom: 10px;
            position: relative;
        }

        /* Add this completely new section for title container */
        .grade-title-container {
            position: relative;
            padding-bottom: 70px; /* Large padding to push the chart down */
            margin-bottom: 20px;
        }

        .grades-chart {
            display: flex;
            height: 180px;
            align-items: flex-end;
            margin-top: 80px; /* Very large margin */
            padding-bottom: 40px;
            border-bottom: 2px solid var(--light-gray);
            position: relative;
            clear: both; /* Force it to clear previous elements */
        }

/* Modify the CSS to place percentage values in the middle of the bars */
        .grade-bar {
            flex: 1;
            margin: 0 5px;
            background-color: var(--primary-color);
            position: relative;
            min-height: 5px;
            display: flex;
            justify-content: center;
            align-items: center; /* For vertical centering */
        }

        .grade-percentage {
            position: absolute;
            left: 50%;
            top: 50%;
            transform: translate(-50%, -50%);
            font-weight: bold;
            color: white;
            white-space: nowrap;
            font-size: 14px;
            text-shadow: 0px 0px 3px rgba(0, 0, 0, 0.5);
            z-index: 2;
            background-color: transparent; /* Remove any background */
            padding: 0; /* Remove any padding */
            box-shadow: none; /* Remove any shadow */
            border: none; /* Remove any border */
        }

        /* For percentages above bars (for small bars) */
        .grade-bar[style*="height: 5px"] .grade-percentage,
        .grade-bar[style*="height: 0px"] .grade-percentage {
            top: -25px;
            color: var(--primary-color);
            text-shadow: none;
            background-color: transparent; /* Ensure no background for small bars too */
            padding: 0;
            box-shadow: none;
            border: none;
}


        /* Remove the old top margin spacing since we don't need it anymore */
        .grades-chart {
            display: flex;
            height: 180px;
            align-items: flex-end;
            margin-top: 30px; /* Reduced from previous large values */
            padding-bottom: 40px;
            border-bottom: 2px solid var(--light-gray);
            position: relative;
        }

        /* Adjust title spacing to normal */
        .info-title {
            font-size: 18px;
            font-weight: bold;
            margin-bottom: 20px; /* Return to normal spacing */
            color: var(--dark-gray);
            border-bottom: 2px solid var(--light-gray);
            padding-bottom: 10px;
        }


        footer {
            background-color: #1e3a8a;
            color: white;
            text-align: center;
            padding: 1rem;
            margin-top: auto;
        }
        
        .professor-card {
            background-color: #f9f9f9;
            border-radius: 8px;
            padding: 15px;
            min-width: 200px;
            transition: transform 0.2s, box-shadow 0.2s;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            margin-bottom: 15px;
        }
        
        .professor-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        
        .professor-name {
            font-weight: bold;
            font-size: 1.1em;
            color: #1e3a8a;
            margin-bottom: 5px;
        }
        
        .professor-department {
            color: #666;
            font-size: 0.9em;
            margin-bottom: 10px;
        }
        
        .professor-rating {
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        
        .stars {
            color: #ffc107;
            font-size: 0.9em;
        }
        
        .footer-content {
            display: flex;
            flex-wrap: wrap;
            justify-content: space-between;
            gap: 30px;
            max-width: 1000px;
            margin: 0 auto;
            padding: 0 20px;
        }
        
        .footer-section {
            flex: 1;
            min-width: 200px;
        }
        
        .footer-title {
            font-size: 18px;
            margin-bottom: 15px;
            color: white;
            border-bottom: 1px solid rgba(255,255,255,0.1);
            padding-bottom: 5px;
        }
        
        .footer-links a {
            display: block;
            color: rgba(255,255,255,0.7);
            text-decoration: none;
            margin-bottom: 8px;
            transition: color 0.3s;
        }
        
        .footer-links a:hover {
            color: white;
        }
        
        .copyright {
            text-align: center;
            padding-top: 20px;
            margin-top: 20px;
            border-top: 1px solid rgba(255,255,255,0.1);
            color: rgba(255,255,255,0.5);
            font-size: 14px;
        }
        
        @media (max-width: 768px) {
            .navbar {
                flex-direction: column;
                align-items: flex-start;
                padding: 10px 20px;
            }
            
            .nav-links {
                margin-top: 10px;
            }
            
            .nav-links a {
                margin-left: 0;
                margin-right: 15px;
            }
            
            .rating-overview {
                flex-direction: column;
            }
            
            .overall-rating {
                flex: 0 0 auto;
            }
            
            .course-title {
                font-size: 24px;
            }
            
            .section-title {
                font-size: 20px;
            }
        }
        
        /* Unified Color Scheme */
        :root {
            --primary-color: #1e3a8a;
            --primary-light: #f0f4ff;
            --secondary-color: #6c757d;
            --light-gray: #f8f9fa;
            --dark-gray: #343a40;
            --success-color: #28a745;
            --warning-color: #ffc107;
            --box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            --hover-shadow: 0 5px 15px rgba(0,0,0,0.1);
            --border-color: #f0f0f0;
        }

        /* Global Style Improvements */
        body {
            background-color: #f5f5f5;
            font-family: 'Arial', sans-serif;
            line-height: 1.6;
            color: #333;
        }

        /* Container Styles */
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 2rem;
        }

        /* Content Box Styling */
        .content-box {
            background-color: white;
            border-radius: 8px;
            padding: 1.5rem;
            margin: 1rem 0;
            box-shadow: var(--box-shadow);
        }

        /* Course Header Styling */
        .course-title {
            color: var(--primary-color);
            font-size: 1.8rem;
            margin-bottom: 1rem;
            border-bottom: 2px solid var(--border-color);
            padding-bottom: 0.5rem;
        }

        .course-subtitle {
            color: var(--secondary-color);
            display: flex;
            flex-wrap: wrap;
            gap: 20px;
            margin-bottom: 20px;
        }

        /* Rating Styles */
        .overall-rating {
            background-color: var(--primary-light);
            padding: 20px;
            border-radius: 8px;
            text-align: center;
            box-shadow: var(--box-shadow);
        }

        .rating-number {
            color: var(--primary-color);
            font-size: 42px;
            font-weight: bold;
        }

        .stars {
            color: var(--warning-color);
        }

        /* Professor Card Styling */
        .professor-card {
            background-color: #f9f9f9;
            border-radius: 8px;
            padding: 15px;
            min-width: 200px;
            transition: transform 0.2s, box-shadow 0.2s;
            box-shadow: var(--box-shadow);
            margin-bottom: 15px;
            cursor: pointer;
        }

        .professor-card:hover {
            transform: translateY(-3px);
            box-shadow: var(--hover-shadow);
        }

        .professor-name {
            font-weight: bold;
            font-size: 1.1em;
            color: var(--primary-color);
            margin-bottom: 5px;
        }

        .professor-department {
            color: #666;
            font-size: 0.9em;
            margin-bottom: 10px;
        }

        /* Section Titles */
        .section-title {
            color: var(--primary-color);
            font-size: 1.4rem;
            margin-bottom: 20px;
            border-bottom: 2px solid var(--border-color);
            padding-bottom: 10px;
        }

        /* Review Cards */
        .review-card {
            border-bottom: 1px solid var(--border-color);
            padding: 20px 0;
            margin-bottom: 10px;
        }

        /* Button Styles */
        .add-review-btn, .primary-btn, #rateButton, #writeReviewButton {
            background-color: var(--primary-color) !important;
            color: white !important;
            padding: 10px 20px !important;
            border-radius: 5px !important;
            text-decoration: none !important;
            font-weight: bold !important;
            border: none !important;
            cursor: pointer !important;
            transition: background-color 0.3s !important;
            display: inline-block !important;
        }

        .add-review-btn:hover, .primary-btn:hover, #rateButton:hover, #writeReviewButton:hover {
            background-color: #2a4db0 !important;
        }

        .content-blur {
        filter: blur(5px);
        pointer-events: none;
        user-select: none;
        }
    
        /* Login overlay styles */
        .login-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.7);
            display: flex;
            justify-content: center;
            align-items: center;
            z-index: 1000;
        }
        
        .login-container {
            background-color: white;
            border-radius: 8px;
            padding: 30px;
            width: 90%;
            max-width: 400px;
            box-shadow: 0 0 20px rgba(0, 0, 0, 0.3);
        }
        
        .login-title {
            color: #1e3a8a;
            margin-bottom: 20px;
            font-size: 24px;
            text-align: center;
        }
        
        .login-form .form-group {
            margin-bottom: 15px;
        }
        
        .login-form label {
            display: block;
            margin-bottom: 5px;
            font-weight: 500;
        }
        
        .login-form .form-control {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 16px;
        }
        
        .login-form .help-block {
            color: #dc3545;
            font-size: 14px;
            margin-top: 5px;
        }
        
        .login-form .btn-primary {
            background-color: #1e3a8a;
            color: white;
            padding: 12px 20px;
            border: none;
            border-radius: 4px;
            width: 100%;
            font-size: 16px;
            font-weight: bold;
            cursor: pointer;
            margin-top: 10px;
        }
        
        .login-form .btn-secondary {
            background-color: #6c757d;
            color: white;
            padding: 12px 20px;
            border: none;
            border-radius: 4px;
            width: 100%;
            font-size: 16px;
            font-weight: bold;
            cursor: pointer;
            margin-top: 10px;
        }
        
        .login-actions {
            display: flex;
            justify-content: space-between;
            margin-top: 20px;
            font-size: 14px;
        }
        
        .login-error {
            background-color: #f8d7da;
            color: #721c24;
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 4px;
            font-size: 14px;
        }
        
        .or-divider {
            display: flex;
            align-items: center;
            text-align: center;
            margin: 20px 0;
        }
        
        .or-divider::before,
        .or-divider::after {
            content: "";
            flex: 1;
            border-bottom: 1px solid #ddd;
        }
        
        .or-divider span {
            padding: 0 10px;
            color: #6c757d;
        }
    </style>
</head>
<body>
    <header style="background-color: #1e3a8a; color: white; padding: 0.5rem 1rem; display: flex; justify-content: space-between; align-items: center; height: 60px;">
        <div class="header-left" style="width: 25%;">
            <div class="dropdown" style="position: relative; display: inline-block;">
                <button class="dropbtn" style="background-color: transparent; color: white; padding: 10px; font-size: 16px; border: none; cursor: pointer; display: flex; align-items: center;">Menu <span style="margin-left: 5px; font-size: 12px;">▼</span></button>
                <div class="dropdown-content" style="position: absolute; background-color: white; min-width: 160px; box-shadow: 0 8px 16px rgba(0,0,0,0.2); z-index: 1; border-radius: 4px; overflow: hidden; display: none;">
                    <?php if ($isLoggedIn): ?>
                        <a href="account-section.php" style="color: black; padding: 12px 16px; text-decoration: none; display: block; border-bottom: 1px solid #f1f1f1;">My Account</a>
                        <a href="logout.php" style="color: black; padding: 12px 16px; text-decoration: none; display: block; border-bottom: 1px solid #f1f1f1;">Logout</a>
                    <?php else: ?>
                        <a href="login.php" style="color: black; padding: 12px 16px; text-decoration: none; display: block; border-bottom: 1px solid #f1f1f1;">Login</a>
                        <a href="register.php" style="color: black; padding: 12px 16px; text-decoration: none; display: block; border-bottom: 1px solid #f1f1f1;">Register</a>
                    <?php endif; ?>
                    <a href="home.php#popular-professors" style="color: black; padding: 12px 16px; text-decoration: none; display: block; border-bottom: 1px solid #f1f1f1;">Top Professors</a>
                    <a href="ratemyteacher-instructions.php?section=professors&lang=<?php echo $lang; ?>" style="color: black; padding: 12px 16px; text-decoration: none; display: block; border-bottom: 1px solid #f1f1f1;">Professors</a>
                    <a href="home.php#top-courses" style="color: black; padding: 12px 16px; text-decoration: none; display: block; border-bottom: 1px solid #f1f1f1;">Top Courses</a>
                    <a href="ratemyteacher-instructions.php?section=courses&lang=<?php echo $lang; ?>" style="color: black; padding: 12px 16px; text-decoration: none; display: block; border-bottom: 1px solid #f1f1f1;">Courses</a>
                    <a href="tipsandtricks.php" style="color: black; padding: 12px 16px; text-decoration: none; display: block; border-bottom: 1px solid #f1f1f1;">Tips and Tricks</a>
                </div>
            </div>
        </div>
        
        <div class="header-center" style="width: 50%; text-align: center;">
            <div class="logo" style="display: flex; flex-direction: column; align-items: center;">
                <h1 style="font-size: 1.5rem; margin: 0;"><a href="home.php" style="color: white; text-decoration: none;">Rate My Teacher</a></h1>
            </div>
        </div>
        
        <div class="header-right" style="width: 25%; display: flex; justify-content: flex-end; align-items: center;">
            <?php if ($isLoggedIn): ?>
                <span class="welcome-message" style="margin-right: 15px; font-size: 14px;">Welcome, <?php echo htmlspecialchars($_SESSION["username"]); ?>!</span>
            <?php endif; ?>
            <div class="auth-links" style="margin-right: 15px;">
                <?php if ($isLoggedIn): ?>
                    <a href="logout.php" style="color: white; text-decoration: none; margin-left: 15px; font-size: 14px;">Logout</a>
                <?php else: ?>
                    <a href="login.php" style="color: white; text-decoration: none; margin-left: 15px; font-size: 14px;">Login</a>
                    <a href="register.php" style="color: white; text-decoration: none; margin-left: 15px; font-size: 14px;">Register</a>
                <?php endif; ?>
            </div>
            <div class="language-toggle" style="display: flex; align-items: center;">
                <a href="?<?php $queryParams = $_GET; $queryParams['lang'] = 'en'; echo http_build_query($queryParams); ?>" style="display:inline-block; width:80px; text-align:center; padding:8px 0; margin-right:5px; background:<?php echo $lang == 'en' ? 'white' : 'transparent'; ?>; color:<?php echo $lang == 'en' ? '#1e3a8a' : 'white'; ?>; text-decoration:none; border:1px solid white; border-radius:4px;">English</a>
                <a href="?<?php $queryParams = $_GET; $queryParams['lang'] = 'ja'; echo http_build_query($queryParams); ?>" style="display:inline-block; width:80px; text-align:center; padding:8px 0; background:<?php echo $lang == 'ja' ? 'white' : 'transparent'; ?>; color:<?php echo $lang == 'ja' ? '#1e3a8a' : 'white'; ?>; text-decoration:none; border:1px solid white; border-radius:4px;">日本語</a>
            </div>
        </div>
    </header>
    <?php if (!$isLoggedIn): ?>
        <div class="login-overlay">
            <div class="login-container">
                <h2 class="login-title"><?php echo $lang === 'ja' ? 'ログインして続行' : 'Login to Continue'; ?></h2>
                
                <?php if (!empty($login_err)): ?>
                    <div class="login-error"><?php echo $login_err; ?></div>
                <?php endif; ?>
                
                <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"] . '?' . $_SERVER['QUERY_STRING']); ?>" method="post" class="login-form">
                    <div class="form-group">
                        <label><?php echo $lang === 'ja' ? 'メールアドレス' : 'Email'; ?></label>
                        <input type="email" name="email" class="form-control" value="<?php echo $email; ?>" required>
                        <span class="help-block"><?php echo $email_err; ?></span>
                    </div>
                    <div class="form-group">
                        <label><?php echo $lang === 'ja' ? 'パスワード' : 'Password'; ?></label>
                        <input type="password" name="password" class="form-control" required>
                        <span class="help-block"><?php echo $password_err; ?></span>
                    </div>
                    <div class="form-group">
                        <button type="submit" name="login_submit" class="btn-primary">
                            <?php echo $lang === 'ja' ? 'ログイン' : 'Login'; ?>
                        </button>
                    </div>
                    
                    <div class="or-divider">
                        <span><?php echo $lang === 'ja' ? 'または' : 'OR'; ?></span>
                    </div>
                    
                    <div class="form-group">
                        <a href="register.php?redirect=<?php echo urlencode($_SERVER['REQUEST_URI']); ?>" class="btn-secondary" style="display: block; text-align: center; text-decoration: none;">
                            <?php echo $lang === 'ja' ? '新規登録' : 'Register'; ?>
                        </a>
                    </div>
                    
                    <div class="login-actions">
                        <a href="reset-password.php"><?php echo $lang === 'ja' ? 'パスワードをお忘れですか？' : 'Forgot Password?'; ?></a>
                        <a href="home.php"><?php echo $lang === 'ja' ? 'ホームに戻る' : 'Back to Home'; ?></a>
                    </div>
                </form>
            </div>
        </div>
        <?php endif; ?>
    <div class="container" style="width: 100%; min-height: 100vh; display: flex; flex-direction: column; max-width: 1200px; margin: 0 auto; padding: 2rem;">
        <div class="<?php echo (!$isLoggedIn) ? 'content-blur' : ''; ?>">
        <div class="content-box" style="flex: 1; background-color: white; margin: 1rem; padding: 1.5rem; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1);">
            <div class="course-header">
                <h1 class="course-title" style="color: #1e3a8a; margin-bottom: 1rem; border-bottom: 2px solid #f0f0f0; padding-bottom: 0.5rem;"><?php echo htmlspecialchars($translation['name']); ?></h1>
                <div class="course-subtitle" style="display: flex; flex-wrap: wrap; gap: 20px; margin-bottom: 20px; color: #666;">
                    <span style="display: flex; align-items: center;">
                        <svg width="16" height="16" viewBox="0 0 16 16" fill="currentColor" style="margin-right: 5px;">
                            <path d="M8 2a6 6 0 100 12A6 6 0 008 2zm0 11a5 5 0 110-10 5 5 0 010 10z"/>
                            <path d="M8 4a.5.5 0 01.5.5v3.5H11a.5.5 0 010 1H8a.5.5 0 01-.5-.5V4.5A.5.5 0 018 4z"/>
                        </svg>
                        <?php echo $semester; ?>
                    </span>
                    <span style="display: flex; align-items: center;">
                        <svg width="16" height="16" viewBox="0 0 16 16" fill="currentColor" style="margin-right: 5px;">
                            <path d="M3.5 0a.5.5 0 01.5.5V1h8V.5a.5.5 0 011 0V1h1a2 2 0 012 2v11a2 2 0 01-2 2H2a2 2 0 01-2-2V3a2 2 0 012-2h1V.5a.5.5 0 01.5-.5zM2 2a1 1 0 00-1 1v11a1 1 0 001 1h12a1 1 0 001-1V3a1 1 0 00-1-1H2z"/>
                            <path d="M2.5 4a.5.5 0 01.5-.5h10a.5.5 0 010 1H3a.5.5 0 01-.5-.5z"/>
                        </svg>
                        <?php echo htmlspecialchars($course['year']); ?>
                    </span>
                    <span style="display: flex; align-items: center;">
                        <svg width="16" height="16" viewBox="0 0 16 16" fill="currentColor" style="margin-right: 5px;">
                            <path d="M1 2.828c.885-.37 2.154-.769 3.388-.893 1.33-.134 2.458.063 3.112.752v9.746c-.935-.53-2.12-.603-3.213-.493-1.18.12-2.37.461-3.287.811V2.828zm7.5-.141c.654-.689 1.782-.886 3.112-.752 1.234.124 2.503.523 3.388.893v9.923c-.918-.35-2.107-.692-3.287-.81-1.094-.111-2.278-.039-3.213.492V2.687zM8 1.783C7.015.936 5.587.81 4.287.94c-1.514.153-3.042.672-3.994 1.105A.5.5 0 000 2.5v11a.5.5 0 00.707.455c.882-.4 2.303-.881 3.68-1.02 1.409-.142 2.59.087 3.223.877a.5.5 0 00.78 0c.633-.79 1.814-1.019 3.222-.877 1.378.139 2.8.62 3.681 1.02A.5.5 0 0016 13.5v-11a.5.5 0 00-.293-.455c-.952-.433-2.48-.952-3.994-1.105C10.413.809 8.985.936 8 1.783z"/>
                        </svg>
                        <?php echo htmlspecialchars($translation['field']); ?>
                    </span>
                </div>
            </div>

        <div class="rating-overview" style="display: flex; flex-wrap: wrap; gap: 30px; margin-bottom: 30px;">
            <div class="rating-details" style="flex-grow: 1; min-width: 300px;">
                <?php foreach ($categoryScores as $key => $category): ?>
                <div class="rating-category" style="display: flex; align-items: center; margin-bottom: 12px;">
                    <div class="category-name" style="width: 200px; font-size: 0.95em; color: #444;">
                        <?php echo $category['label']; ?>
                        <div style="font-size: 0.8em; color: #666;">
                            <?php echo $category['description']; ?>
                        </div>
                    </div>
                    <div class="progress-bar" style="flex-grow: 1; height: 8px; background-color: #e9ecef; border-radius: 4px; margin: 0 15px; overflow: hidden;">
                        <div class="progress" style="width: <?php echo $category['percent']; ?>%; height: 100%; background-color: <?php echo $category['color'] ?? '#1e3a8a'; ?>;"></div>
                    </div>
                    <div class="category-score" style="width: 40px; text-align: right; font-weight: bold; color: #1e3a8a;">
                        <?php echo isset($category['raw_score']) ? $category['raw_score'] : $category['score']; ?>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>

        <div class="professor-info" style="margin-bottom: 30px;">
            <h2 class="section-title" style="color: #1e3a8a; margin-bottom: 20px; border-bottom: 2px solid #f0f0f0; padding-bottom: 10px;"><?php echo $lang === 'ja' ? '担当教員' : 'Professors'; ?></h2>
            <div class="professors">
                <?php foreach ($course['professors'] as $professor): ?>
                    <div class="professor-card" onclick="window.location.href='professor_page_template.php?name=<?php echo urlencode(str_replace(' ', '', $professor['name']['en'])); ?>&lang=<?php echo $lang; ?>'">
                    <div class="professor-name"><?php echo htmlspecialchars($professor['name'][$lang]); ?></div>
                    <div class="professor-department"><?php echo htmlspecialchars($professor['department'][$lang]); ?></div>
                    <?php
                    // Try to get professor rating from database
                    $profRating = '?';
                    $profStars = '☆☆☆☆☆';
                    
                    if (isset($db)) {
                        // Try to find professor in database
                        $profName = str_replace(' ', '', $professor['name']['en']);
                        $stmt = $db->prepare("SELECT id, overall_rating FROM professors WHERE REPLACE(name, ' ', '') = :name OR REPLACE(name, ' ', '') LIKE :name_like LIMIT 1");
                        $stmt->bindValue(':name', $profName, SQLITE3_TEXT);
                        $stmt->bindValue(':name_like', '%' . $profName . '%', SQLITE3_TEXT);
                        $result = $stmt->execute();
                        $profRow = $result->fetchArray(SQLITE3_ASSOC);
                        
                        if ($profRow && isset($profRow['overall_rating']) && $profRow['overall_rating'] > 0) {
                            $profRating = round($profRow['overall_rating'], 1);
                            // Generate star display
                            $fullStars = floor($profRating);
                            $halfStar = ($profRating - $fullStars) >= 0.5;
                            $emptyStars = 5 - $fullStars - ($halfStar ? 1 : 0);
                            
                            $profStars = str_repeat('★', $fullStars) . ($halfStar ? '★' : '') . str_repeat('☆', $emptyStars);
                        }
                    }
                    ?>
                    <div class="professor-rating">
                        <div class="stars" style="color: #ffc107; font-size: 16px;"><?php echo $profStars; ?></div>
                        <span><?php echo $profRating; ?></span>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Replace your existing course-info grid with this centered layout -->
        <div style="text-align: center; display: flex; flex-direction: column; align-items: center; margin-bottom: 30px;">
            <h2 class="section-title" style="color: #1e3a8a; margin-bottom: 20px; border-bottom: 2px solid #f0f0f0; padding-bottom: 10px; width: 100%; text-align: center;">
                <?php echo $lang === 'ja' ? '成績分布と落単率' : 'Grade Distribution and Failure Rate'; ?>
            </h2>
            
            <div style="display: flex; flex-wrap: wrap; justify-content: center; gap: 30px; width: 100%; max-width: 1000px;">
                <!-- Grade Distribution -->
                <div style="flex: 1; min-width: 300px; max-width: 500px;">
                    <h3 style="font-size: 18px; font-weight: bold; margin-bottom: 20px; color: var(--dark-gray); text-align: center;">
                        <?php echo $lang === 'ja' ? '成績分布' : 'Grade Distribution'; ?>
                    </h3>
                    <?php if ($totalGradeCount > 0): ?>
                    <div style="display: flex; height: 200px; align-items: flex-end; margin-top: 20px; padding-bottom: 20px; border-bottom: 2px solid var(--light-gray);">
                        <?php 
                        // Define grade colors
                        $gradeColors = [
                            'S' => '#FFD700', // Yellow
                            'A' => '#28a745', // Green
                            'B' => '#1e3a8a', // Blue
                            'C' => '#fd7e14', // Orange
                            'D' => '#dc3545', // Red
                            'P' => '#1e3a8a', // Blue
                            'F' => '#dc3545', // Red
                        ];
                        
                        foreach ($gradeDistribution as $grade => $percent): 
                            // Calculate bar height (minimum 5px for visibility even at 0%)
                            $barHeight = max(5, $percent * 2);
                        ?>
                        <div class="grade-bar" data-grade="<?php echo $grade; ?>" style="flex: 1; margin: 0 5px; background-color: <?php echo $gradeColors[$grade]; ?>; height: <?php echo $barHeight; ?>px; position: relative; display: flex; justify-content: center; align-items: center;">
                            <?php if ($percent > 0): // Only show percentage if there's any data ?>
                                <?php if ($barHeight > 30): // Only show percentage inside if bar is tall enough ?>
                                    <div class="grade-percentage" style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); color: white; font-weight: bold; text-shadow: 0px 0px 3px rgba(0, 0, 0, 0.5); background-color: transparent; padding: 0; box-shadow: none; border: none; font-size: 14px;">
                                        <?php echo $percent; ?>%
                                    </div>
                                <?php else: // Show percentage above the bar if it's too small ?>
                                    <div class="grade-percentage" style="position: absolute; top: -25px; left: 50%; transform: translateX(-50%); color: <?php echo $gradeColors[$grade]; ?>; font-weight: bold; background-color: transparent; padding: 0; box-shadow: none; border: none; font-size: 14px;">
                                        <?php echo $percent; ?>%
                                    </div>
                                <?php endif; ?>
                            <?php endif; ?>
                            
                            <div class="grade-label" style="position: absolute; bottom: -30px; left: 50%; transform: translateX(-50%); font-weight: bold;">
                                <?php echo $grade; ?>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <div style="text-align: center; margin-top: 30px; color: #666; font-size: 14px;">
                        <?php echo $lang === 'ja' ? '総回答数' : 'Total responses'; ?>: <?php echo $totalGradeCount; ?>
                    </div>
                    <?php else: ?>
                    <div style="text-align: center; padding: 40px 0;">
                        <div style="color: #6c757d; font-size: 18px;">
                            <?php echo $lang === 'ja' ? 'まだ成績データがありません' : 'No grade data available yet'; ?>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>

                <!-- Failure Rate -->
                <div style="flex: 1; min-width: 300px; max-width: 500px;">
                    <h3 style="font-size: 18px; font-weight: bold; margin-bottom: 20px; color: var(--dark-gray); text-align: center;">
                        <?php echo $lang === 'ja' ? '落単率' : 'Failure Rate'; ?>
                    </h3>
                    <?php if ($totalGradeCount > 0): ?>
                    <div style="text-align: center; padding: 40px 0;">
                        <div style="font-size: 64px; font-weight: bold; color: <?php 
                            if ($failureRate <= 25) {
                                echo '#2ecc71'; // Green for low failure rate (0-25%)
                            } elseif ($failureRate <= 50) {
                                echo '#3498db'; // Blue for medium-low failure rate (26-50%)
                            } elseif ($failureRate <= 75) {
                                echo '#f39c12'; // Orange for medium-high failure rate (51-75%)
                            } else {
                                echo '#e74c3c'; // Red for high failure rate (76-100%)
                            }
                        ?>;">
                            <?php echo $failureRate; ?>%
                        </div>
                        <div style="font-size: 16px; color: #666; margin-top: 10px;">
                            <?php echo $lang === 'ja' ? 'D または F の割合' : 'Percentage of D or F grades'; ?>
                        </div>
                    </div>
                    <?php else: ?>
                    <div style="text-align: center; padding: 40px 0;">
                        <div style="font-size: 64px; font-weight: bold; color: #4a86e8;">-</div>
                        <div style="font-size: 16px; color: #6c757d; margin-top: 10px;">
                            <?php echo $lang === 'ja' ? 'データなし' : 'No data available'; ?>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

<!-- Student Reviews section follows after the grade distribution and failure rate -->

        <div class="reviews">
            <h2 class="section-title"><?php echo $lang === 'ja' ? '学生のレビュー' : 'Student Reviews'; ?></h2>
            <?php if (!empty($reviews)): ?>
                <?php foreach ($reviews as $review): ?>
                <div class="review-card" id="review-<?php echo $review['id']; ?>">
                    <div class="review-header">
                        <div class="reviewer"><?php echo htmlspecialchars($review['username']); ?></div>
                        <div class="review-date">
                            <?php echo $review['created_at']; ?>
                            
                            <?php if ($isLoggedIn && isset($_SESSION['id']) && $_SESSION['id'] === $review['user_id']): ?>
                            <!-- Delete button only shown for the user's own reviews -->
                            <button onclick="deleteReview(<?php echo $review['id']; ?>)" class="delete-review-btn" style="margin-left: 10px; background-color: #dc3545; color: white; border: none; border-radius: 4px; padding: 2px 8px; font-size: 12px; cursor: pointer;">
                                <?php echo $lang === 'ja' ? '削除' : 'Delete'; ?>
                            </button>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <!-- Ratings Section -->
                    <div class="review-rating" style="margin-bottom: 15px;">
                        <!-- Overall Rating -->
                        <div style="font-weight: bold; color: #333; margin-bottom: 5px;">
                            <?php echo $lang === 'ja' ? '総合評価:' : 'Overall Rating:'; ?> 
                            <span style="color: #1e3a8a; font-size: 18px;"><?php echo $review['rating']; ?>/5</span>
                        </div>
                        <div style="color: #ffc107; font-size: 18px;">
                            <?php echo generateStarRating($review['rating']); ?>
                        </div>
                        
                        <!-- Content & Difficulty Ratings -->
                        <div style="display: flex; flex-wrap: wrap; gap: 20px; margin-top: 10px; font-size: 14px;">
                            <?php if (isset($review['content_rating'])): ?>
                            <div>
                                <span><?php echo $lang === 'ja' ? '授業内容:' : 'Content:'; ?></span>
                                <span style="color: #1e3a8a; font-weight: bold;"><?php echo $review['content_rating']; ?>/5</span>
                            </div>
                            <?php endif; ?>
                            
                            <?php if (isset($review['difficulty_rating'])): ?>
                            <div>
                                <span><?php echo $lang === 'ja' ? '難易度:' : 'Difficulty:'; ?></span>
                                <span style="color: #1e3a8a; font-weight: bold;"><?php echo $review['difficulty_rating']; ?>/5</span>
                            </div>
                            <?php endif; ?>
                            
                            <?php if (!empty($review['grade'])): ?>
                            <div>
                                <span><?php echo $lang === 'ja' ? '成績:' : 'Grade:'; ?></span>
                                <span style="color: #1e3a8a; font-weight: bold;"><?php echo $review['grade']; ?></span>
                            </div>
                            <?php endif; ?>
                            
                            <?php if (!empty($review['textbook'])): ?>
                            <div>
                                <span><?php echo $lang === 'ja' ? '教科書:' : 'Textbook:'; ?></span>
                                <span style="color: #1e3a8a; font-weight: bold;">
                                    <?php 
                                    echo $lang === 'ja' ? 
                                        match($review['textbook']) {
                                            'required' => '必須',
                                            'recommended' => '推奨',
                                            'not_needed' => '不要',
                                            default => $review['textbook'],
                                        } : 
                                        match($review['textbook']) {
                                            'required' => 'Required',
                                            'recommended' => 'Recommended',
                                            'not_needed' => 'Not Needed',
                                            default => $review['textbook'],
                                        }; 
                                    ?>
                                </span>
                            </div>
                            <?php endif; ?>
                            
                            <?php if (!empty($review['attendance_check'])): ?>
                            <div>
                                <span><?php echo $lang === 'ja' ? '出席確認:' : 'Attendance:'; ?></span>
                                <span style="color: #1e3a8a; font-weight: bold;">
                                    <?php 
                                    echo $lang === 'ja' ? 
                                        match($review['attendance_check']) {
                                            'always' => '毎回取る',
                                            'sometimes' => '時々取る',
                                            'never' => '取らない',
                                            default => $review['attendance_check'],
                                        } : 
                                        match($review['attendance_check']) {
                                            'always' => 'Always',
                                            'sometimes' => 'Sometimes',
                                            'never' => 'Never',
                                            default => $review['attendance_check'],
                                        }; 
                                    ?>
                                </span>
                            </div>
                            <?php endif; ?>
                        </div>
                        
                        <!-- Assessment Methods -->
                        <?php
                        // Parse first_half and second_half JSON if they exist
                        $firstHalf = [];
                        $secondHalf = [];
                        
                        error_log("first_half value: " . (isset($review['first_half']) ? $review['first_half'] : 'not set'));
                        error_log("second_half value: " . (isset($review['second_half']) ? $review['second_half'] : 'not set'));
                        
                        if (!empty($review['first_half'])) {
                            if (is_string($review['first_half'])) {
                                try {
                                    $decoded = json_decode($review['first_half'], true);
                                    if ($decoded !== null) {
                                        $firstHalf = $decoded;
                                    } else {
                                        // If JSON decode fails, try as a simple string
                                        $firstHalf = [$review['first_half']];
                                        error_log("JSON decode failed for first_half, using as string: " . $review['first_half']);
                                    }
                                } catch (Exception $e) {
                                    $firstHalf = [$review['first_half']];
                                    error_log("Exception decoding first_half: " . $e->getMessage());
                                }
                            } elseif (is_array($review['first_half'])) {
                                $firstHalf = $review['first_half'];
                            }
                        }
                        
                        if (!empty($review['second_half'])) {
                            if (is_string($review['second_half'])) {
                                try {
                                    $decoded = json_decode($review['second_half'], true);
                                    if ($decoded !== null) {
                                        $secondHalf = $decoded;
                                    } else {
                                        // If JSON decode fails, try as a simple string
                                        $secondHalf = [$review['second_half']];
                                        error_log("JSON decode failed for second_half, using as string: " . $review['second_half']);
                                    }
                                } catch (Exception $e) {
                                    $secondHalf = [$review['second_half']];
                                    error_log("Exception decoding second_half: " . $e->getMessage());
                                }
                            } elseif (is_array($review['second_half'])) {
                                $secondHalf = $review['second_half'];
                            }
                        }
                        
                        error_log("Parsed first_half: " . print_r($firstHalf, true));
                        error_log("Parsed second_half: " . print_r($secondHalf, true));
                        
                        // Only display assessment methods if we have data
                        if (!empty($firstHalf) || !empty($secondHalf)):
                        ?>
                        <div style="margin-top: 15px; font-size: 14px;">
                            <?php if (!empty($firstHalf)): ?>
                            <div style="margin-bottom: 8px;">
                                <span style="font-weight: bold; color: #666;"><?php echo $lang === 'ja' ? '授業前半:' : 'First Half:'; ?></span>
                                <span>
                                    <?php 
                                    $firstHalfLabels = [];
                                    foreach ($firstHalf as $method) {
                                        $firstHalfLabels[] = $lang === 'ja' ? 
                                            match($method) {
                                                'report' => 'レポート',
                                                'test' => 'テスト',
                                                'presentation' => '発表',
                                                'project' => 'プロジェクト',
                                                'nothing' => 'なし',
                                                default => $method,
                                            } : 
                                            match($method) {
                                                'report' => 'Report',
                                                'test' => 'Test',
                                                'presentation' => 'Presentation',
                                                'project' => 'Project',
                                                'nothing' => 'Nothing',
                                                default => $method,
                                            };
                                    }
                                    echo implode(', ', $firstHalfLabels);
                                    ?>
                                </span>
                            </div>
                            <?php endif; ?>
                            
                            <?php if (!empty($secondHalf)): ?>
                            <div>
                                <span style="font-weight: bold; color: #666;"><?php echo $lang === 'ja' ? '授業後半:' : 'Second Half:'; ?></span>
                                <span>
                                    <?php 
                                    $secondHalfLabels = [];
                                    foreach ($secondHalf as $method) {
                                        $secondHalfLabels[] = $lang === 'ja' ? 
                                            match($method) {
                                                'report' => 'レポート',
                                                'test' => 'テスト',
                                                'presentation' => '発表',
                                                'project' => 'プロジェクト',
                                                'nothing' => 'なし',
                                                default => $method,
                                            } : 
                                            match($method) {
                                                'report' => 'Report',
                                                'test' => 'Test',
                                                'presentation' => 'Presentation',
                                                'project' => 'Project',
                                                'nothing' => 'Nothing',
                                                default => $method,
                                            };
                                    }
                                    echo implode(', ', $secondHalfLabels);
                                    ?>
                                </span>
                            </div>
                            <?php endif; ?>
                        </div>
                        <?php endif; ?>
                    </div>
                    
                    <!-- Review Content -->
                    <?php if (!empty($review['comment'])): ?>
                    <div class="review-content" style="margin-bottom: 10px;">
                        <?php echo nl2br(htmlspecialchars($review['comment'])); ?>
                    </div>
                    <?php endif; ?>
                    
                    <div class="review-tags">
                        <span class="tag"><?php echo $lang === 'ja' ? '学生レビュー' : 'Student Review'; ?></span>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div style="text-align: center; padding: 40px 20px; color: var(--secondary-color); background-color: white; border-radius: 8px; margin-bottom: 20px;">
                    <div style="font-size: 64px; margin-bottom: 10px;">
                        <i class="far fa-comment-dots"></i>
                    </div>
                    <h3><?php echo $lang === 'ja' ? 'まだレビューがありません' : 'No Reviews Yet'; ?></h3>
                    <p><?php echo $lang === 'ja' ? 'この講義の最初のレビューを投稿しましょう！' : 'Be the first to review this course!'; ?></p>
                </div>
            <?php endif; ?>
            
            <a href="#rating-form" class="add-review-btn">
                <?php echo $lang === 'ja' ? 'レビューを投稿する' : 'Post a Review'; ?>
            </a>
        </div>
        
        <?php if ($isLoggedIn): ?>
        <div class="info-card" style="margin-top: 20px;" id="rating-form">
            <h3 class="info-title"><?php echo $lang === 'ja' ? 'この講義を評価する' : 'Rate This Course'; ?></h3>
            
            <?php if (isset($_GET['error'])): ?>
            <div style="background-color: #ffebee; color: #c62828; padding: 12px; border-radius: 5px; margin-bottom: 15px; border-left: 4px solid #c62828;">
                <strong><?php echo $lang === 'ja' ? 'エラー:' : 'Error:'; ?></strong> 
                <?php 
                    $errorMessage = '';
                    switch($_GET['error']) {
                        case 'submission_failed':
                            $errorMessage = $lang === 'ja' ? 'レビューの送信に失敗しました。もう一度お試しください。' : 'Failed to submit your review. Please try again.';
                            break;
                        case 'database_error':
                            $errorMessage = $lang === 'ja' ? 'データベースエラーが発生しました。管理者にお問い合わせください。' : 'A database error occurred. Please contact the administrator.';
                            break;
                        default:
                            $errorMessage = $lang === 'ja' ? '不明なエラーが発生しました。' : 'An unknown error occurred.';
                    }
                    echo $errorMessage;
                ?>
            </div>
            <?php endif; ?>
            <form action="submit_rating.php" method="post" style="padding: 15px 0;">
                <!-- Include both course_id and course_name for better identification -->
                <input type="hidden" name="course_id" value="<?php echo $courseId ?? $course['course_id'] ?? ''; ?>">
                <input type="hidden" name="course_name" value="<?php echo htmlspecialchars($translation['name'] ?? ''); ?>">
                <!-- Store current URL for redirect after submission -->
                <input type="hidden" name="redirect_url" value="<?php echo htmlspecialchars($_SERVER['REQUEST_URI']); ?>">
                <!-- Debug info -->
                <input type="hidden" name="debug_info" value="<?php echo htmlspecialchars(json_encode(['course_id' => $courseId, 'course_name' => $translation['name'] ?? ''])); ?>">
                

                <!-- Content and Difficulty Ratings -->
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 30px;">
                    <div>
                        <label style="display: block; margin-bottom: 10px; font-weight: 500;">
                            <?php echo $lang === 'ja' ? '授業の質:' : 'Content Quality:'; ?>
                        </label>
                        <select name="content_rating" style="width: 100%; padding: 12px; border: 1px solid #ddd; border-radius: 5px; background-color: white; font-size: 16px;" required>
                            <option value=""><?php echo $lang === 'ja' ? '選択してください' : 'Select...'; ?></option>
                            <?php for($i = 5; $i >= 1; $i--): ?>
                            <option value="<?php echo $i; ?>">
                                <?php echo $i; ?> - <?php echo $lang === 'ja' ? 
                                    match($i) {
                                        5 => '非常に良い',
                                        4 => '良い',
                                        3 => '普通',
                                        2 => 'やや悪い',
                                        1 => '悪い',
                                        default => '',
                                    } : 
                                    match($i) {
                                        5 => 'Excellent',
                                        4 => 'Good',
                                        3 => 'Average',
                                        2 => 'Below Average',
                                        1 => 'Poor',
                                        default => '',
                                    }; 
                                ?>
                            </option>
                            <?php endfor; ?>
                        </select>
                    </div>
                    <div>
                        <label style="display: block; margin-bottom: 10px; font-weight: 500;">
                            <?php echo $lang === 'ja' ? '授業難易度:' : 'Difficulty:'; ?>
                        </label>
                        <select name="difficulty_rating" style="width: 100%; padding: 12px; border: 1px solid #ddd; border-radius: 5px; background-color: white; font-size: 16px;" required>
                            <option value=""><?php echo $lang === 'ja' ? '選択してください' : 'Select...'; ?></option>
                            <option value="5"><?php echo $lang === 'ja' ? '非常に難しい' : 'Very Difficult'; ?></option>
                            <option value="4"><?php echo $lang === 'ja' ? '難しい' : 'Difficult'; ?></option>
                            <option value="3"><?php echo $lang === 'ja' ? '普通' : 'Average'; ?></option>
                            <option value="2"><?php echo $lang === 'ja' ? '簡単' : 'Easy'; ?></option>
                            <option value="1"><?php echo $lang === 'ja' ? '非常に簡単' : 'Very Easy'; ?></option>
                        </select>
                    </div>
                </div>
                
                <!-- Attendance -->
                <div style="margin-bottom: 30px;">
                    <label style="display: block; margin-bottom: 10px; font-weight: 500;">
                        <?php echo $lang === 'ja' ? '出席確認:' : 'Takes Attendance:'; ?>
                    </label>
                    <select name="attendance_check" style="width: 100%; padding: 12px; border: 1px solid #ddd; border-radius: 5px; background-color: white; font-size: 16px;" required>
                        <option value=""><?php echo $lang === 'ja' ? '選択してください' : 'Select...'; ?></option>
                        <option value="always"><?php echo $lang === 'ja' ? '毎回取る' : 'Always'; ?></option>
                        <option value="sometimes"><?php echo $lang === 'ja' ? '時々取る' : 'Sometimes'; ?></option>
                        <option value="never"><?php echo $lang === 'ja' ? '取らない' : 'Never'; ?></option>
                    </select>
                </div>

                <!-- Grade Received -->
                <div style="margin-bottom: 30px;">
                    <label style="display: block; margin-bottom: 10px; font-weight: 500;">
                        <?php echo $lang === 'ja' ? '成績:' : 'Grade Received:'; ?>
                    </label>
                    <div style="display: flex; flex-wrap: wrap; gap: 10px;">
                        <?php foreach(['S', 'A', 'B', 'C', 'D', 'F', 'P'] as $grade): ?>
                        <label style="cursor: pointer; display: inline-block; padding: 10px 15px; border: 1px solid #ddd; border-radius: 5px; transition: all 0.3s;">
                            <input type="radio" name="grade" value="<?php echo $grade; ?>" style="margin-right: 5px;" required>
                            <?php echo $grade; ?>
                        </label>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Course Attributes -->
                <div style="margin-bottom: 30px;">
                    <label style="display: block; margin-bottom: 10px; font-weight: 500;">
                        <?php echo $lang === 'ja' ? '授業の特徴:' : 'Course Attributes:'; ?>
                    </label>
                    
                    <div style="margin-bottom: 20px;">
                        <p style="margin-bottom: 10px; font-weight: 500;"><?php echo $lang === 'ja' ? '教科書:' : 'Textbook:'; ?></p>
                        <div style="display: flex; gap: 15px;">
                            <label style="cursor: pointer; display: inline-block;">
                                <input type="radio" name="textbook" value="required" required>
                                <?php echo $lang === 'ja' ? '必須' : 'Required'; ?>
                            </label>
                            <label style="cursor: pointer; display: inline-block;">
                                <input type="radio" name="textbook" value="recommended">
                                <?php echo $lang === 'ja' ? '推奨' : 'Recommended'; ?>
                            </label>
                            <label style="cursor: pointer; display: inline-block;">
                                <input type="radio" name="textbook" value="not_needed">
                                <?php echo $lang === 'ja' ? '不要' : 'Not needed'; ?>
                            </label>
                        </div>
                    </div>
                    
                    <div style="margin-bottom: 15px;">
                        <p style="margin-bottom: 10px; font-weight: 500;"><?php echo $lang === 'ja' ? '授業前半:' : 'First Half:'; ?></p>
                        <div style="display: flex; flex-wrap: wrap; gap: 15px;">
                            <label style="cursor: pointer; display: inline-block;">
                                <input type="checkbox" name="first_half[]" value="report">
                                <?php echo $lang === 'ja' ? 'レポート' : 'Report'; ?>
                            </label>
                            <label style="cursor: pointer; display: inline-block;">
                                <input type="checkbox" name="first_half[]" value="test">
                                <?php echo $lang === 'ja' ? 'テスト' : 'Test'; ?>
                            </label>
                            <label style="cursor: pointer; display: inline-block;">
                                <input type="checkbox" name="first_half[]" value="presentation">
                                <?php echo $lang === 'ja' ? '発表' : 'Presentation'; ?>
                            </label>
                            <label style="cursor: pointer; display: inline-block;">
                                <input type="checkbox" name="first_half[]" value="project">
                                <?php echo $lang === 'ja' ? 'プロジェクト' : 'Project'; ?>
                            </label>
                            <label style="cursor: pointer; display: inline-block;">
                                <input type="checkbox" name="first_half[]" value="nothing">
                                <?php echo $lang === 'ja' ? 'なし' : 'Nothing'; ?>
                            </label>
                        </div>
                    </div>
                    
                    <div>
                        <p style="margin-bottom: 10px; font-weight: 500;"><?php echo $lang === 'ja' ? '授業後半:' : 'Second Half:'; ?></p>
                        <div style="display: flex; flex-wrap: wrap; gap: 15px;">
                            <label style="cursor: pointer; display: inline-block;">
                                <input type="checkbox" name="second_half[]" value="report">
                                <?php echo $lang === 'ja' ? 'レポート' : 'Report'; ?>
                            </label>
                            <label style="cursor: pointer; display: inline-block;">
                                <input type="checkbox" name="second_half[]" value="test">
                                <?php echo $lang === 'ja' ? 'テスト' : 'Test'; ?>
                            </label>
                            <label style="cursor: pointer; display: inline-block;">
                                <input type="checkbox" name="second_half[]" value="presentation">
                                <?php echo $lang === 'ja' ? '発表' : 'Presentation'; ?>
                            </label>
                            <label style="cursor: pointer; display: inline-block;">
                                <input type="checkbox" name="second_half[]" value="project">
                                <?php echo $lang === 'ja' ? 'プロジェクト' : 'Project'; ?>
                            </label>
                            <label style="cursor: pointer; display: inline-block;">
                                <input type="checkbox" name="second_half[]" value="nothing">
                                <?php echo $lang === 'ja' ? 'なし' : 'Nothing'; ?>
                            </label>
                        </div>
                    </div>
                </div>
                
                <!-- Comment (Optional) -->
                <div style="margin-bottom: 30px;">
                    <label for="comment" style="display: block; margin-bottom: 10px; font-weight: 500;">
                        <?php echo $lang === 'ja' ? 'コメント (任意):' : 'Comment (Optional):'; ?>
                    </label>
                    <textarea name="comment" id="comment" rows="5" 
                        style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 5px; resize: vertical;"
                        placeholder="<?php echo $lang === 'ja' ? 'この講義についての感想や意見を書いてください...' : 'Share your thoughts about this course...'; ?>"></textarea>
                </div>

                <!-- Anonymous Review Option -->
                <div style="margin-bottom: 30px;">
                    <label style="display: flex; align-items: center; cursor: pointer;">
                        <input type="checkbox" name="is_anonymous" value="1" style="margin-right: 10px;">
                        <span style="font-weight: 500;">
                            <?php echo $lang === 'ja' ? '匿名で投稿する' : 'Post anonymously'; ?>
                        </span>
                    </label>
                    <div style="margin-top: 5px; font-size: 14px; color: #666;">
                        <?php echo $lang === 'ja' ? 'チェックすると、投稿者名が「Student」として表示されます。' : 'If checked, your review will be displayed as "Student" instead of your username.'; ?>
                    </div>
                </div>
                
                <!-- Submit Button -->
                <div>
                    <button type="submit" class="add-review-btn" style="border: none; cursor: pointer;">
                        <?php echo $lang === 'ja' ? '評価を送信' : 'Submit Rating'; ?>
                    </button>
                </div>
            </form>
        </div>
        <?php endif; ?>
    </div>

    <style>
        /* Add hover effect for dropdown menu */
        .dropdown:hover .dropdown-content {
            display: block !important;
        }
    </style>
    <script>
    </script>

<!-- 1. First, find and close your content div before the footer -->
</div> <!-- End of the content wrapper div -->
</div> <!-- End of the container div -->

<?php
// This completes the modifications needed for the blur effect and login overlay
?>

    <!-- 2. Replace the footer with this implementation that sits outside any containers -->
    <footer style="background-color: #1e3a8a; color: white; text-align: center; padding: 1rem; width: 100%;">
        <p>2025 Rate My Teacher</p>
        <div style="display: flex; flex-wrap: wrap; justify-content: center; margin-top: 15px; gap: 25px;">
            <a href="ToS.php?lang=<?php echo $lang; ?>" style="color: white; text-decoration: underline;">
                <?php echo $lang == 'ja' ? '利用規約' : 'Terms and Conditions'; ?>
            </a>
            <a href="privacy_policy.php?lang=<?php echo $lang; ?>" style="color: white; text-decoration: underline;">
                <?php echo $lang == 'ja' ? 'プライバシーポリシー' : 'Privacy Policy'; ?>
            </a>
            <a href="about_us.php?lang=<?php echo $lang; ?>" style="color: white; text-decoration: underline;">
                <?php echo $lang == 'ja' ? '私たちについて' : 'About Us'; ?>
            </a>
        </div>
    </footer>
    </body>
    </html>

    <script>
        // Function to handle review deletion
        function deleteReview(reviewId) {
            if (!confirm('<?php echo $lang === "ja" ? "このレビューを削除してもよろしいですか？" : "Are you sure you want to delete this review?"; ?>')) {
                return; // User cancelled
            }
            
            // Send AJAX request to delete the review
            fetch('delete_review.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'review_id=' + reviewId
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Remove the review from the page
                    const reviewElement = document.getElementById('review-' + reviewId);
                    if (reviewElement) {
                        reviewElement.style.backgroundColor = '#ffebee';
                        reviewElement.style.opacity = '0.5';
                        reviewElement.innerHTML = '<div style="padding: 20px; text-align: center;">' + 
                            '<?php echo $lang === "ja" ? "レビューが削除されました" : "Review has been deleted"; ?>' +
                            '</div>';
                        
                        // After a short delay, remove the element entirely
                        setTimeout(() => {
                            reviewElement.style.display = 'none';
                        }, 2000);
                    }
                } else {
                    // Show error message
                    alert(data.error || '<?php echo $lang === "ja" ? "レビューの削除中にエラーが発生しました" : "An error occurred while deleting the review"; ?>');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('<?php echo $lang === "ja" ? "エラーが発生しました" : "An error occurred"; ?>');
            });
        }
        
        // Debug function to help identify search issues
        function debug(message) {
            console.log(`[Search Debug] ${message}`);
        }
        
        document.addEventListener('DOMContentLoaded', function() {
            // Add click event to review button if not logged in
            const reviewBtn = document.querySelector('.add-review-btn');
            if (reviewBtn && !reviewBtn.getAttribute('href').startsWith('#')) {
                reviewBtn.addEventListener('click', function(e) {
                    e.preventDefault();
                    alert('<?php echo $lang === 'ja' ? 'レビューを投稿するにはログインが必要です。' : 'You need to log in to post a review.'; ?>');
                    window.location.href = 'login.php';
                });
            }

            // Rating form enhancements for logged in users
            const ratingInputs = document.querySelectorAll('input[name="rating"]');
            if (ratingInputs.length > 0) {
                ratingInputs.forEach(input => {
                    const label = input.closest('label');
                    label.addEventListener('click', function() {
                        // Reset all labels
                        ratingInputs.forEach(inp => {
                            inp.closest('label').style.backgroundColor = '';
                            inp.closest('label').style.borderColor = '#ddd';
                        });
                        // Highlight selected label
                        this.style.backgroundColor = '#f8f9fa';
                        this.style.borderColor = '#4a86e8';
                    });
                });
            }

            // Grade selection enhancement
            const gradeInputs = document.querySelectorAll('input[name="grade"]');
            if (gradeInputs.length > 0) {
                gradeInputs.forEach(input => {
                    const label = input.closest('label');
                    label.addEventListener('click', function() {
                        // Reset all labels
                        gradeInputs.forEach(inp => {
                            inp.closest('label').style.backgroundColor = '';
                            inp.closest('label').style.borderColor = '#ddd';
                        });
                        // Highlight selected label
                        this.style.backgroundColor = '#f8f9fa';
                        this.style.borderColor = '#4a86e8';
                    });
                });
            }
            
            // Live search functionality
            const searchInput = document.getElementById('searchInput');
            const searchResults = document.getElementById('searchResults');
            
            if (searchInput && searchResults) {
                let searchTimeout;
                
                searchInput.addEventListener('input', function() {
                    clearTimeout(searchTimeout);
                    const query = this.value.trim();
                    
                    debug(`Search input event: query="${query}"`);
                    
                    if (query.length < 2) {
                        searchResults.style.display = 'none';
                        debug("Query too short, hiding results");
                        return;
                    }
                    
                    searchTimeout = setTimeout(function() {
                        const url = `search.php?query=${encodeURIComponent(query)}&limit=true&lang=<?php echo $lang; ?>`;
                        debug(`Making fetch request to: ${url}`);
                        
                        fetch(url)
                            .then(response => {
                                debug(`Received response: status=${response.status}`);
                                return response.text(); // Get raw text first for debugging
                            })
                            .then(text => {
                                try {
                                    debug(`Response text: ${text.substring(0, 100)}...`);
                                    const data = JSON.parse(text);
                                    debug(`Parsed JSON: ${data.professors.length} professors, ${data.courses.length} courses`);
                                    return data;
                                } catch (e) {
                                    debug(`ERROR parsing JSON: ${e.message}`);
                                    console.error("Full response text:", text);
                                    return { professors: [], courses: [] };
                                }
                            })
                            .then(data => {
                                searchResults.innerHTML = '';
                                
                                if (data.professors.length === 0 && data.courses.length === 0) {
                                    searchResults.innerHTML = `<p style="padding: 10px; text-align: center; color: #666;"><?php echo $lang === 'ja' ? '結果が見つかりませんでした' : 'No results found'; ?></p>`;
                                    searchResults.style.display = 'block';
                                    return;
                                }
                                
                                // Display professors
                                if (data.professors.length > 0) {
                                    const profSection = document.createElement('div');
                                    profSection.innerHTML = `<h3 style="margin: 10px; font-size: 16px; color: #666;"><?php echo $lang === 'ja' ? '教授' : 'Professors'; ?> (${data.professors.length})</h3>`;
                                    
                                    data.professors.forEach(prof => {
                                        const item = document.createElement('div');
                                        item.style.padding = '10px';
                                        item.style.borderBottom = '1px solid #eee';
                                        item.style.cursor = 'pointer';
                                        
                                        // Use Japanese name if in Japanese mode and available
                                        const displayName = '<?php echo $lang; ?>' === 'ja' && prof.name_ja ? prof.name_ja : prof.name;
                                        const displayDept = '<?php echo $lang; ?>' === 'ja' && prof.department_ja ? prof.department_ja : prof.department;
                                        
                                        // For debug purposes, show both names during development
                                        item.innerHTML = `
                                            <div style="font-weight: bold;">${displayName}</div>
                                            <div style="font-size: 13px; color: #666;">${displayDept || ''}</div>
                                            <div style="font-size: 10px; color: #999; margin-top: 4px;">
                                                EN: ${prof.name || ''} | JA: ${prof.name_ja || ''}
                                            </div>
                                        `;
                                        
                                        item.addEventListener('click', () => {
                                            // Make sure to remove all spaces from professor name for URL
                                            const nameForUrl = prof.name.replace(/\s+/g, '');
                                            window.location.href = `professor_page_template.php?name=${encodeURIComponent(nameForUrl)}&lang=<?php echo $lang; ?>`;
                                            debug(`Navigating to professor: ${nameForUrl}`);
                                        });
                                        
                                        profSection.appendChild(item);
                                    });
                                    
                                    searchResults.appendChild(profSection);
                                }
                                
                                // Display courses
                                if (data.courses.length > 0) {
                                    const courseSection = document.createElement('div');
                                    courseSection.innerHTML = `<h3 style="margin: 10px; font-size: 16px; color: #666;"><?php echo $lang === 'ja' ? 'コース' : 'Courses'; ?> (${data.courses.length})</h3>`;
                                    
                                    data.courses.forEach(course => {
                                        const item = document.createElement('div');
                                        item.style.padding = '10px';
                                        item.style.borderBottom = '1px solid #eee';
                                        item.style.cursor = 'pointer';
                                        
                                        // Use Japanese name if in Japanese mode and available
                                        const displayName = '<?php echo $lang; ?>' === 'ja' && course.name_ja ? course.name_ja : course.name;
                                        const displayProf = '<?php echo $lang; ?>' === 'ja' && course.professor_name_ja ? course.professor_name_ja : course.professor_name;
                                        
                                        item.innerHTML = `
                                            <div style="font-weight: bold;">${displayName}</div>
                                            <div style="font-size: 13px; color: #666;">
                                                ${displayProf ? ('<?php echo $lang === 'ja' ? '担当教員: ' : 'Taught by: '; ?>' + displayProf) : ''}
                                            </div>
                                            <div style="font-size: 10px; color: #999; margin-top: 4px;">
                                                EN: ${course.name || ''} | JA: ${course.name_ja || ''}
                                            </div>
                                        `;
                                        
                                        item.addEventListener('click', () => {
                                            // Use the name in the matching language
                                            const nameForUrl = '<?php echo $lang; ?>' === 'ja' && course.name_ja ? course.name_ja : course.name;
                                            // Add professor if available
                                            let url = `course_page_template.php?course=${encodeURIComponent(nameForUrl)}`;
                                            if (course.professor_name) {
                                                // Remove spaces from professor name for URL
                                                const profNameForUrl = course.professor_name.replace(/\s+/g, '');
                                                url += `&professor=${encodeURIComponent(profNameForUrl)}`;
                                            }
                                            url += `&lang=<?php echo $lang; ?>`;
                                            window.location.href = url;
                                            debug(`Navigating to course: ${nameForUrl}`);
                                        });
                                        
                                        courseSection.appendChild(item);
                                    });
                                    
                                    searchResults.appendChild(courseSection);
                                }
                                
                                // Add "See all results" link
                                const footer = document.createElement('div');
                                footer.style.padding = '10px';
                                footer.style.textAlign = 'center';
                                footer.style.borderTop = '1px solid #eee';
                                
                                const link = document.createElement('a');
                                link.href = `search.php?q=${encodeURIComponent(query)}&lang=<?php echo $lang; ?>`;
                                link.style.color = '#4a86e8';
                                link.style.textDecoration = 'none';
                                link.style.fontWeight = 'bold';
                                link.textContent = '<?php echo $lang === 'ja' ? 'すべての結果を表示' : 'See all results'; ?>';
                                
                                footer.appendChild(link);
                                searchResults.appendChild(footer);
                                
                                searchResults.style.display = 'block';
                            })
                            .catch(error => {
                                console.error('Error fetching search results:', error);
                            });
                    }, 300);
                });
                
                // Hide search results when clicking outside
                document.addEventListener('click', function(e) {
                    if (!searchInput.contains(e.target) && !searchResults.contains(e.target)) {
                        searchResults.style.display = 'none';
                    }
                });
                
                // Hide search results when pressing Escape
                document.addEventListener('keydown', function(e) {
                    if (e.key === 'Escape') {
                        searchResults.style.display = 'none';
                    }
                });
            }
        });
    </script>
</body>
</html>
