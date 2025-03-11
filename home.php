<?php
// Include session configuration before starting the session
require_once 'session_config.php';

// Now start the session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Debug session information
error_log('HOME.PHP - Session ID: ' . session_id());
error_log('HOME.PHP - Session data: ' . print_r($_SESSION, true));
error_log('HOME.PHP - REQUEST_URI: ' . $_SERVER['REQUEST_URI']);

// Check if user is logged in
$isLoggedIn = isset($_SESSION["loggedin"]) && $_SESSION["loggedin"] === true;

// Debug session variables - Only enable this temporarily to troubleshoot
$sessionDebug = "Session Status: " . ($isLoggedIn ? "Logged In" : "Not Logged In") . "\n";
$sessionDebug .= "Session ID: " . session_id() . "\n";
$sessionDebug .= "Session Variables: " . print_r($_SESSION, true) . "\n";
error_log($sessionDebug);

// Get username if logged in
$username = $isLoggedIn ? htmlspecialchars($_SESSION["username"]) : '';

// Include database configuration and rating calculation functions
require_once 'config.php';
require_once 'calculate_ratings.php';

// Function to get top rated professors
function getTopProfessors($limit = 5) {
    global $conn;
    $professors = [];
    
    try {
        // Check if we have any ratings in the database
        $ratingCount = 0;
        $ratingCheckResult = $conn->query("SELECT COUNT(*) as count FROM ratings");
        if ($ratingCheckResult) {
            $ratingCountRow = $ratingCheckResult->fetchArray(SQLITE3_ASSOC);
            $ratingCount = $ratingCountRow['count'];
        }
        
        if ($ratingCount > 0) {
            // If we have ratings, try to get professors with ratings
            // Check if professors table has rating columns
            $hasProfRatingColumns = false;
            $columnsResult = $conn->query("PRAGMA table_info(professors)");
            while ($col = $columnsResult->fetchArray(SQLITE3_ASSOC)) {
                if ($col['name'] === 'avg_content_quality' || $col['name'] === 'overall_rating') {
                    $hasProfRatingColumns = true;
                    break;
                }
            }
            
            if ($hasProfRatingColumns) {
                // Use stored ratings if they exist
                $stmt = $conn->prepare("
                    SELECT id, name, department, 
                           COALESCE(overall_rating, (avg_content_quality + avg_difficulty) / 2) as avg_rating,
                           COALESCE(avg_content_quality, 0) as avg_content_quality,
                           COALESCE(avg_difficulty, 0) as avg_difficulty,
                           COALESCE(review_count, 0) as review_count
                    FROM professors
                    WHERE COALESCE(review_count, 0) > 0
                    ORDER BY avg_rating DESC, review_count DESC
                    LIMIT :limit
                ");
            } else {
                // Calculate ratings on the fly if columns don't exist
                $stmt = $conn->prepare("
                    SELECT p.id, p.name, p.department,
                           (SELECT AVG(r.rating) FROM ratings r WHERE r.professor_id = p.id) as avg_rating,
                           (SELECT COUNT(r.id) FROM ratings r WHERE r.professor_id = p.id) as review_count
                    FROM professors p
                    WHERE EXISTS (SELECT 1 FROM ratings r WHERE r.professor_id = p.id)
                    ORDER BY avg_rating DESC, review_count DESC
                    LIMIT :limit
                ");
            }
            
            $stmt->bindValue(':limit', $limit, SQLITE3_INTEGER);
            $result = $stmt->execute();
            
            while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
                // Format the rating to 1 decimal place if it exists
                if (isset($row['avg_rating']) && $row['avg_rating'] !== null) {
                    $row['avg_rating'] = number_format((float)$row['avg_rating'], 1);
                } else {
                    $row['avg_rating'] = "N/A";
                }
                
                $professors[] = $row;
            }
        }
        
        // If we don't have enough professors with ratings, get professors without ratings
        if (count($professors) < $limit) {
            $remaining = $limit - count($professors);
            
            // Create a string of IDs to exclude
            $excludeIds = array_map(function($prof) {
                return $prof['id'];
            }, $professors);
            
            $excludeClause = count($excludeIds) > 0 ? "WHERE id NOT IN (" . implode(",", $excludeIds) . ")" : "";
            
            $stmt = $conn->prepare("
                SELECT id, name, department
                FROM professors
                $excludeClause
                LIMIT :limit
            ");
            $stmt->bindValue(':limit', $remaining, SQLITE3_INTEGER);
            $result = $stmt->execute();
            
            while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
                // Add default rating
                $row['avg_rating'] = "N/A";
                $professors[] = $row;
            }
        }
    } catch (Exception $e) {
        error_log("Error fetching top professors: " . $e->getMessage());
    }
    
    // If we still don't have enough professors, add placeholder data
    if (count($professors) < $limit) {
        $placeholders = [
            ['name' => 'Dr. Tanaka Hiroshi', 'department' => 'Social Studies', 'avg_rating' => '4.9'],
            ['name' => 'Prof. Nakamura Yuki', 'department' => 'Economics', 'avg_rating' => '4.3'],
            ['name' => 'Dr. Smith Karen', 'department' => 'International Relations', 'avg_rating' => '4.2'],
            ['name' => 'Prof. Watanabe Kenji', 'department' => 'Environmental Studies', 'avg_rating' => '4.1'],
            ['name' => 'Dr. Yamamoto Aki', 'department' => 'Business Administration', 'avg_rating' => '4.0']
        ];
        
        // Add placeholder data until we reach the limit
        $missingCount = $limit - count($professors);
        for ($i = 0; $i < $missingCount && $i < count($placeholders); $i++) {
            $professors[] = $placeholders[$i];
        }
    }
    
    return $professors;
}

// Function to get top rated courses
function getTopCourses($limit = 5) {
    global $conn;
    $courses = [];
    
    try {
        // Check if we have any ratings in the database
        $ratingCount = 0;
        $ratingCheckResult = $conn->query("SELECT COUNT(*) as count FROM ratings");
        if ($ratingCheckResult) {
            $ratingCountRow = $ratingCheckResult->fetchArray(SQLITE3_ASSOC);
            $ratingCount = $ratingCountRow['count'];
        }
        
        if ($ratingCount > 0) {
            // If we have ratings, try to get courses with ratings
            // Check if courses table has rating columns
            $hasCourseRatingColumns = false;
            $columnsResult = $conn->query("PRAGMA table_info(courses)");
            while ($col = $columnsResult->fetchArray(SQLITE3_ASSOC)) {
                if ($col['name'] === 'avg_content_quality') {
                    $hasCourseRatingColumns = true;
                    break;
                }
            }
            
            // The SQL query will depend on the schema
            if ($hasCourseRatingColumns) {
                // Use stored ratings if they exist
                $stmt = $conn->prepare("
                    SELECT c.id, c.name as course_name, c.course_code,
                        COALESCE(c.avg_content_quality, 0) as avg_content_quality,
                        COALESCE(c.avg_difficulty, 0) as avg_difficulty,
                        (COALESCE(c.avg_content_quality, 0) + (5 - COALESCE(c.avg_difficulty, 0))) / 2 as avg_rating,
                        COALESCE(c.review_count, 0) as review_count,
                        p.name as professor_name
                    FROM courses c
                    LEFT JOIN professors p ON c.professor_id = p.id
                    WHERE COALESCE(c.review_count, 0) > 0
                    ORDER BY avg_rating DESC, review_count DESC
                    LIMIT :limit
                ");
            } else {
                // Calculate ratings on the fly
                $stmt = $conn->prepare("
                    SELECT c.id, c.name as course_name, c.course_code,
                           (SELECT AVG(r.rating) FROM ratings r WHERE r.course_id = c.id) as avg_rating,
                           (SELECT COUNT(r.id) FROM ratings r WHERE r.course_id = c.id) as review_count,
                           p.name as professor_name
                    FROM courses c
                    LEFT JOIN professors p ON c.professor_id = p.id
                    WHERE EXISTS (SELECT 1 FROM ratings r WHERE r.course_id = c.id)
                    ORDER BY avg_rating DESC, review_count DESC
                    LIMIT :limit
                ");
            }
            
            $stmt->bindValue(':limit', $limit, SQLITE3_INTEGER);
            $result = $stmt->execute();
            
            while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
                // Ensure we have a professor name
                if (!isset($row['professor_name']) || empty($row['professor_name'])) {
                    // Try to get professor name from ratings
                    $profStmt = $conn->prepare("
                        SELECT p.name 
                        FROM professors p 
                        JOIN ratings r ON p.id = r.professor_id 
                        WHERE r.course_id = :course_id 
                        GROUP BY p.id 
                        ORDER BY COUNT(r.id) DESC 
                        LIMIT 1
                    ");
                    $profStmt->bindValue(':course_id', $row['id'], SQLITE3_INTEGER);
                    $profResult = $profStmt->execute();
                    $profRow = $profResult->fetchArray(SQLITE3_ASSOC);
                    
                    if ($profRow && isset($profRow['name'])) {
                        $row['professor_name'] = $profRow['name'];
                    } else {
                        $row['professor_name'] = "Unknown Professor";
                    }
                }
                
                // Format the rating
                if (isset($row['avg_rating']) && $row['avg_rating'] !== null) {
                    $row['avg_rating'] = number_format((float)$row['avg_rating'], 1);
                } else {
                    $row['avg_rating'] = "N/A";
                }
                
                $courses[] = $row;
            }
        }
        
        // If we don't have enough courses with ratings, get courses without ratings
        if (count($courses) < $limit) {
            $remaining = $limit - count($courses);
            
            // Create a string of IDs to exclude
            $excludeIds = array_map(function($course) {
                return $course['id'];
            }, $courses);
            
            $excludeClause = count($excludeIds) > 0 ? "WHERE c.id NOT IN (" . implode(",", $excludeIds) . ")" : "";
            
            $stmt = $conn->prepare("
                SELECT c.id, c.name as course_name, c.course_code, p.name as professor_name
                FROM courses c
                LEFT JOIN professors p ON c.professor_id = p.id
                $excludeClause
                LIMIT :limit
            ");
            $stmt->bindValue(':limit', $remaining, SQLITE3_INTEGER);
            $result = $stmt->execute();
            
            while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
                // Ensure we have a professor name
                if (!isset($row['professor_name']) || empty($row['professor_name'])) {
                    $row['professor_name'] = "Unknown Professor";
                }
                
                // Add default rating
                $row['avg_rating'] = "N/A";
                $courses[] = $row;
            }
        }
    } catch (Exception $e) {
        error_log("Error fetching top courses: " . $e->getMessage());
    }
    
    // If we still don't have enough courses, add placeholder data
    if (count($courses) < $limit) {
        $placeholders = [
            ['course_name' => 'Global Environmental Policy', 'professor_name' => 'Prof. Watanabe Kenji', 'avg_rating' => '4.8'],
            ['course_name' => 'International Political Economy', 'professor_name' => 'Dr. Smith Karen', 'avg_rating' => '4.7'],
            ['course_name' => 'Corporate Strategy', 'professor_name' => 'Dr. Yamamoto Aki', 'avg_rating' => '4.5'],
            ['course_name' => 'Macroeconomic Theory', 'professor_name' => 'Prof. Nakamura Yuki', 'avg_rating' => '4.4'],
            ['course_name' => 'Public Policy Analysis', 'professor_name' => 'Dr. Tanaka Hiroshi', 'avg_rating' => '4.3']
        ];
        
        // Add placeholder data until we reach the limit
        $missingCount = $limit - count($courses);
        for ($i = 0; $i < $missingCount && $i < count($placeholders); $i++) {
            $courses[] = $placeholders[$i];
        }
    }
    
    return $courses;
}

// Determine current language for data display
$currentLang = isset($_COOKIE['language']) && $_COOKIE['language'] == 'ja' ? 'ja' : 'en';

// Debug language setting
error_log("Current language from cookie: " . $currentLang);

// Get data for the page
$topProfessors = getTopProfessors(5);
$topCourses = getTopCourses(5);

// Store original English names before translation
foreach ($topProfessors as &$professor) {
    // Save the original English professor name
    $professor['english_name'] = $professor['name'] ?? '';
}

foreach ($topCourses as &$course) {
    // Save the original English course name
    $course['english_course_name'] = $course['course_name'] ?? $course['name'] ?? '';
    // Save the original English professor name if available
    if (isset($course['professor_name'])) {
        $course['english_professor_name'] = $course['professor_name'];
    }
}

// If language is Japanese, try to convert names to Japanese if available
if ($currentLang == 'ja') {
    // Add Japanese names from JSON data if available
    $jsonFilePath = __DIR__ . '/sfc_courses.json';
    if (file_exists($jsonFilePath)) {
        $jsonData = file_get_contents($jsonFilePath);
        if ($jsonData !== false) {
            $data = json_decode($jsonData, true);
            if ($data !== null) {
                // Create mapping of professor names to Japanese versions
                $professorJa = [];
                $courseJa = [];
                
                foreach ($data['courses'] as $course) {
                    // Map course name
                    if (isset($course['translations']['ja']['name']) && isset($course['translations']['en']['name'])) {
                        $enName = $course['translations']['en']['name'];
                        $jaName = $course['translations']['ja']['name'];
                        $courseJa[$enName] = $jaName;
                    }
                    
                    // Map professor names
                    foreach ($course['professors'] as $prof) {
                        if (isset($prof['name']['en']) && isset($prof['name']['ja'])) {
                            $enName = $prof['name']['en'];
                            $jaName = $prof['name']['ja'];
                            $professorJa[$enName] = $jaName;
                        }
                    }
                }
                
                // Update professor names to Japanese
                foreach ($topProfessors as &$professor) {
                    if (isset($professor['name']) && isset($professorJa[$professor['name']])) {
                        $professor['name'] = $professorJa[$professor['name']];
                    }
                }
                
                // Update course names to Japanese
                foreach ($topCourses as &$course) {
                    $courseName = isset($course['course_name']) ? $course['course_name'] : (isset($course['name']) ? $course['name'] : '');
                    if (!empty($courseName) && isset($courseJa[$courseName])) {
                        $course['course_name'] = $courseJa[$courseName];
                    }
                }
            }
        }
    }
}

// Get browser language
function getBrowserLanguage() {
    if (isset($_SERVER['HTTP_ACCEPT_LANGUAGE'])) {
        $browserLang = substr($_SERVER['HTTP_ACCEPT_LANGUAGE'], 0, 2);
        return $browserLang == 'ja' ? 'ja' : 'en';
    }
    return 'en'; // Default to English
}

// Check for URL language parameter
if (isset($_GET['lang'])) {
    $lang = $_GET['lang'] === 'ja' ? 'ja' : 'en';
    setcookie('language', $lang, time() + (86400 * 30), "/"); // 30 days
    $_COOKIE['language'] = $lang; // Set for current request
}
// Set language preference if not already set
elseif (!isset($_COOKIE['language'])) {
    $browserLang = getBrowserLanguage();
    setcookie('language', $browserLang, time() + (86400 * 30), "/"); // 30 days
    $_COOKIE['language'] = $browserLang; // Set for current request
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <script async src="https://pagead2.googlesyndication.com/pagead/js/adsbygoogle.js?client=ca-pub-1649331460122770"
    crossorigin="anonymous"></script>
    <title>Rate My Teacher - SU2H1</title>
    <link rel="stylesheet" href="css/style.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Arial', sans-serif;
        }
        
        body {
            background-color: #f5f5f5;
        }
        
        .container {
            width: 100%;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }
        
        .tips-section {
            padding: 0 2rem 2rem;
        }
        
        .tips-list {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 1.5rem;
        }
        
        .tip-item {
            background-color: #f0f8ff;
            padding: 1.5rem;
            border-radius: 8px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            transition: transform 0.2s;
        }
        
        .tip-item:hover {
            transform: translateY(-5px);
        }
        
        header {
            background-color: #1e3a8a;
            color: white;
            padding: 1rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .header-left {
            width: 25%;
        }
        
        .header-center {
            width: 50%;
            text-align: center;
        }
        
        .header-right {
            width: 25%;
            display: flex;
            justify-content: flex-end;
            align-items: center;
        }
        
        .auth-links {
            margin-right: 15px;
        }
        
        .auth-links a, .auth-links span {
            color: white;
            text-decoration: none;
            margin-left: 15px;
            font-size: 14px;
        }
        
        .auth-links a:hover {
            text-decoration: underline;
        }
        
        .welcome-message {
            margin-right: 15px;
            font-size: 14px;
        }
        
        .logo {
            display: flex;
            flex-direction: column;
            align-items: center;
        }
        
        .dropdown {
            position: relative;
            display: inline-block;
        }
        
        .dropbtn {
            background-color: transparent;
            color: white;
            padding: 10px;
            font-size: 16px;
            border: none;
            cursor: pointer;
            display: flex;
            align-items: center;
        }
        
        .dropbtn::after {
            content: "▼";
            font-size: 12px;
            margin-left: 5px;
        }
        
        .dropdown-content {
            display: none;
            position: absolute;
            background-color: #f9f9f9;
            min-width: 200px;
            box-shadow: 0px 8px 16px 0px rgba(0,0,0,0.2);
            z-index: 1;
            border-radius: 4px;
        }
        
        .dropdown-content a {
            color: black;
            padding: 12px 16px;
            text-decoration: none;
            display: block;
            transition: background-color 0.2s;
        }
        
        .dropdown-content a:hover {
            background-color: #f1f1f1;
        }
        
        .dropdown:hover .dropdown-content {
            display: block;
        }
        
        .language-toggle {
            display: flex;
            align-items: center;
        }
        
        .language-toggle button {
            background: none;
            border: 1px solid white;
            color: white;
            padding: 0.5rem 1rem;
            margin-left: 0.5rem;
            cursor: pointer;
            border-radius: 4px;
            min-width: 80px; /* Set a fixed minimum width */
            text-align: center; /* Center the text */
            white-space: nowrap; /* Prevent text wrapping */
        }
        
        .language-toggle button.active {
            background-color: white;
            color: #1e3a8a;
        }
        
        .search-container {
            width: 100%;
            display: flex;
            justify-content: center;
            padding: 2rem;
            background-color: white;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .search-bar {
            width: 60%;
            max-width: 600px;
            position: relative !important;
        }
        
        .search-bar input {
            width: 100%;
            padding: 1rem;
            border: 2px solid #ff0000;
            border-radius: 50px;
            font-size: 1.1rem;
        }
        
        .search-bar button {
            position: absolute;
            right: 10px;
            top: 50%;
            transform: translateY(-50%);
            background-color: #1e3a8a;
            color: white;
            border: none;
            border-radius: 50px;
            padding: 0.5rem 1.5rem;
            cursor: pointer;
        }
        
        main {
            flex: 1;
            display: flex;
            padding: 2rem;
        }
        
        .content-box {
            flex: 1;
            background-color: white;
            margin: 1rem;
            padding: 1.5rem;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .content-box h2 {
            color: #1e3a8a;
            margin-bottom: 1rem;
            border-bottom: 2px solid #f0f0f0;
            padding-bottom: 0.5rem;
        }
        
        .professor-item, .course-item {
            display: flex;
            align-items: center;
            padding: 0.8rem 0;
            border-bottom: 1px solid #f0f0f0;
            cursor: pointer;
            transition: background-color 0.2s;
        }
        
        .professor-item:hover, .course-item:hover {
            background-color: #f9f9f9;
        }
        
        .rating {
            display: flex;
            align-items: center;
            margin-left: auto;
            color: #f5a623;
        }
        
        .rating span {
            margin-left: 0.5rem;
            color: #333;
        }
        
        .no-rating {
            font-style: italic;
            color: #888;
            font-size: 0.9em;
        }
        
        .login-required {
            background-color: rgba(0, 0, 0, 0.05);
            padding: 15px;
            border-radius: 5px;
            text-align: center;
            margin: 10px 0;
        }
        
        .login-required p {
            margin-bottom: 10px;
        }
        
        .login-required .btn {
            display: inline-block;
            background-color: #1e3a8a;
            color: white;
            padding: 8px 16px;
            border-radius: 4px;
            text-decoration: none;
            margin: 0 5px;
        }
        
        /* Modal styles */
        .modal {
            display: none;
            position: fixed;
            z-index: 10;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.4);
        }
        
        .modal-content {
            background-color: white;
            margin: 10% auto;
            padding: 20px;
            border-radius: 8px;
            width: 60%;
            max-width: 700px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
        }
        
        .close-modal {
            color: #aaa;
            float: right;
            font-size: 28px;
            font-weight: bold;
            cursor: pointer;
        }
        
        .close-modal:hover {
            color: black;
        }
        
        .modal-header {
            padding-bottom: 10px;
            margin-bottom: 15px;
            border-bottom: 1px solid #f0f0f0;
        }
        
        .modal-body {
            margin-bottom: 20px;
        }
        
        .detail-section {
            margin-bottom: 15px;
        }
        
        .detail-section h4 {
            color: #1e3a8a;
            margin-bottom: 5px;
        }
        
        .reviews {
            margin-top: 20px;
        }
        
        .review-item {
            background-color: #f9f9f9;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 10px;
        }
        
        .review-header {
            display: flex;
            justify-content: space-between;
            margin-bottom: 10px;
        }
        
        .review-date {
            color: #777;
            font-size: 0.9em;
        }
        
        footer {
            background-color: #1e3a8a;
            color: white;
            text-align: center;
            padding: 1rem;
        }
        
        /* Search results styles */
        .search-results {
            margin-top: 10px;
            max-height: 600px;
            overflow-y: auto;
            position: absolute;
            top: 100%;
            left: 0;
            right: 0;
            background: white;
            border-radius: 0 0 8px 8px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
            z-index: 1000;
            padding: 10px;
            width: 100%;
        }
        
        .loading, .no-results, .error {
            padding: 20px;
            text-align: center;
            font-size: 16px;
        }
        
        .loading {
            color: #555;
        }
        
        .no-results {
            color: #777;
        }
        
        .error {
            color: #d9534f;
        }
        
        .search-section {
            margin-bottom: 20px;
        }
        
        .search-section h3 {
            color: #1e3a8a;
            border-bottom: 1px solid #eee;
            padding-bottom: 5px;
            margin-bottom: 15px;
        }
        
        .search-list {
            display: flex;
            flex-direction: column;
            gap: 10px;
        }
        
        .search-item {
            display: flex;
            justify-content: space-between;
            padding: 12px 15px;
            background-color: #f9f9f9;
            border-radius: 6px;
            cursor: pointer;
            transition: all 0.2s;
            margin-bottom: 5px;
        }
        
        .search-item:hover {
            background-color: #f0f4ff;
            transform: translateY(-2px);
            box-shadow: 0 3px 8px rgba(0,0,0,0.05);
        }
        
        .view-all-results {
            text-align: center;
            padding: 10px;
            border-top: 1px solid #eee;
            margin-top: 10px;
        }
        
        .view-all-results a {
            color: #1e3a8a;
            text-decoration: none;
            font-weight: bold;
        }
        
        .view-all-results a:hover {
            text-decoration: underline;
        }
        
        .search-item-info {
            flex: 1;
        }
        
        .search-item-info h4 {
            margin: 0 0 5px 0;
            font-size: 16px;
            color: #333;
        }
        
        .search-item-info p {
            margin: 0;
            font-size: 14px;
            color: #666;
        }
        
        .search-item-rating {
            display: flex;
            flex-direction: column;
            align-items: flex-end;
            justify-content: center;
        }
        
        .star-rating {
            color: #f5a623;
            font-size: 14px;
        }
        
        .no-rating {
            color: #888;
            font-size: 13px;
            font-style: italic;
        }
        
        .rating-count {
            font-size: 12px;
            color: #777;
            margin-top: 3px;
        }
        
        .close-search-results {
            display: block;
            width: 100%;
            max-width: 200px;
            margin: 20px auto 10px;
            padding: 8px 0;
            background-color: #f0f0f0;
            border: 1px solid #ddd;
            border-radius: 4px;
            color: #333;
            cursor: pointer;
            transition: background-color 0.2s;
        }
        
        .close-search-results:hover {
            background-color: #e0e0e0;
        }
    </style>
</head>
<body>
    <div class="container">
        <header>
            <div class="header-left">
                <div class="dropdown">
                    <?php 
                    // Determine current language
                    $currentLang = isset($_COOKIE['language']) && $_COOKIE['language'] == 'ja' ? 'ja' : 'en';
                    $menuLabel = $currentLang == 'ja' ? 'メニュー' : 'Menu';
                    $accountLabel = $currentLang == 'ja' ? 'マイアカウント' : 'My Account';
                    $logoutLabel = $currentLang == 'ja' ? 'ログアウト' : 'Logout';
                    $loginLabel = $currentLang == 'ja' ? 'ログイン' : 'Login';
                    $registerLabel = $currentLang == 'ja' ? '登録' : 'Register';
                    $topProfessorsLabel = $currentLang == 'ja' ? '人気の教授' : 'Top Professors';
                    $professorsLabel = $currentLang == 'ja' ? '教授一覧' : 'Professors';
                    $topCoursesLabel = $currentLang == 'ja' ? '人気のコース' : 'Top Courses';
                    $coursesLabel = $currentLang == 'ja' ? 'コース一覧' : 'Courses';
                    $tipsLabel = $currentLang == 'ja' ? '裏ワザ' : 'Tips and Tricks';
                    $deleteAccountLabel = $currentLang == 'ja' ? 'アカウント削除' : 'Delete Account';
                    ?>
                    <button class="dropbtn"><?php echo $menuLabel; ?></button>
                    <div class="dropdown-content">
                        <?php if ($isLoggedIn): ?>
                            <a href="account-section.php" id="account-link"><?php echo $accountLabel; ?></a>
                            <a href="logout.php"><?php echo $logoutLabel; ?></a>
                        <?php else: ?>
                            <a href="login.php" id="account-link"><?php echo $loginLabel; ?></a>
                            <a href="register.php"><?php echo $registerLabel; ?></a>
                        <?php endif; ?>
                        <a href="#popular-professors"><?php echo $topProfessorsLabel; ?></a>
                        <a href="ratemyteacher-instructions.php?section=professors&lang=<?php echo $currentLang; ?>"><?php echo $professorsLabel; ?></a>
                        <a href="#top-courses"><?php echo $topCoursesLabel; ?></a>
                        <a href="ratemyteacher-instructions.php?section=courses&lang=<?php echo $currentLang; ?>"><?php echo $coursesLabel; ?></a>
                        <a href="tipsandtricks.php"><?php echo $tipsLabel; ?></a>
                        <?php if ($isLoggedIn): ?>
                            <a href="delete_account.php" class="delete-account"><?php echo $deleteAccountLabel; ?></a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <div class="header-center">
                <div class="logo">
                    <h1><a href="home.php" style="color: white; text-decoration: none;"><?php echo $currentLang == 'ja' ? 'Rate My Teacher' : 'Rate My Teacher'; ?></a></h1>
                </div>
            </div>
            
            <div class="header-right">
                <?php if ($isLoggedIn): ?>
                    <span class="welcome-message"><?php echo $currentLang == 'ja' ? 'ようこそ、' : 'Welcome, '; ?><?php echo $username; ?>!</span>
                <?php endif; ?>
                <div class="auth-links">
                    <?php if ($isLoggedIn): ?>
                        <a href="logout.php"><?php echo $logoutLabel; ?></a>
                    <?php else: ?>
                        <a href="login.php"><?php echo $loginLabel; ?></a>
                        <a href="register.php"><?php echo $registerLabel; ?></a>
                    <?php endif; ?>
                </div>
                <div class="language-toggle">
                    <a href="?lang=en" class="<?php echo (!isset($_COOKIE['language']) || $_COOKIE['language'] == 'en') ? 'active' : ''; ?>" 
                    style="display:inline-block; width:80px; text-align:center; padding:8px 0; margin-right:5px; 
                            background:<?php echo (!isset($_COOKIE['language']) || $_COOKIE['language'] == 'en') ? 'white' : 'transparent'; ?>; 
                            color:<?php echo (!isset($_COOKIE['language']) || $_COOKIE['language'] == 'en') ? '#1e3a8a' : 'white'; ?>; 
                            text-decoration:none; border:1px solid white; border-radius:4px;"
                    onclick="document.cookie='language=en; path=/; max-age=2592000'; return true;">
                    English
                    </a>
                    <a href="?lang=ja" class="<?php echo (isset($_COOKIE['language']) && $_COOKIE['language'] == 'ja') ? 'active' : ''; ?>" 
                    style="display:inline-block; width:80px; text-align:center; padding:8px 0; 
                            background:<?php echo (isset($_COOKIE['language']) && $_COOKIE['language'] == 'ja') ? 'white' : 'transparent'; ?>; 
                            color:<?php echo (isset($_COOKIE['language']) && $_COOKIE['language'] == 'ja') ? '#1e3a8a' : 'white'; ?>; 
                            text-decoration:none; border:1px solid white; border-radius:4px;"
                    onclick="document.cookie='language=ja; path=/; max-age=2592000'; return true;">
                    日本語
                    </a>
                </div>

        </header>
        
        <div class="search-container">
            <div class="search-bar" style="position: relative !important;">
                <input type="text" id="searchInput" placeholder="<?php echo $currentLang == 'ja' ? '教授やコースを検索...' : 'Search for professors or courses...'; ?>" style="width: 100%; padding: 10px; border-radius: 5px; border: 1px solid #ccc;">
                <button id="searchButton" style="position: absolute; right: 10px; top: 50%; transform: translateY(-50%); background: #1e3a8a; color: white; border: none; padding: 5px 15px; border-radius: 5px; cursor: pointer;"><?php echo $currentLang == 'ja' ? '検索' : 'Search'; ?></button>
                <div id="searchResults" class="search-results" style="display: none; position: absolute; top: 100%; left: 0; width: 100%; background-color: white; border: 1px solid #ddd; box-shadow: 0 2px 10px rgba(0,0,0,0.1); z-index: 1000; max-height: 400px; overflow-y: auto; margin-top: 5px; border-radius: 5px;">
                    <!-- Search results will be displayed here -->
                </div>
            </div>
        </div>
        
        <main style="flex: 1; display: flex; padding: 2rem;">
            <div id="popular-professors" class="content-box" style="flex: 1; background-color: white; margin: 1rem; padding: 1.5rem; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1);">
                <h2><?php echo $currentLang == 'ja' ? '人気の教授' : 'Popular Professors'; ?></h2>
                <div class="professor-list">
                    <?php foreach ($topProfessors as $professor): ?>
                    <a href="professor_page_template.php?name=<?php echo urlencode(str_replace(' ', '', $professor['english_name'])); ?>&lang=<?php echo $currentLang; ?>" style="text-decoration: none; color: inherit;">
                        <div class="professor-item" data-id="<?php echo $isLoggedIn ? $professor['id'] : 'login-required'; ?>">
                            <div>
                                <h3><?php echo htmlspecialchars($professor['name']); ?></h3>
                                <p><?php echo htmlspecialchars($professor['department'] ?? 'Unknown Department'); ?></p>
                            </div>
                            <div class="rating">
                                <?php 
                                if ($professor['avg_rating'] !== 'N/A' && $professor['avg_rating'] > 0) {
                                    $fullStars = floor($professor['avg_rating']);
                                    $hasHalfStar = $professor['avg_rating'] - $fullStars >= 0.5;
                                    echo str_repeat('★', $fullStars);
                                    echo $hasHalfStar ? '½' : '';
                                    echo str_repeat('☆', 5 - $fullStars - ($hasHalfStar ? 1 : 0));
                                    echo ' <span>' . $professor['avg_rating'] . '</span>';
                                } else {
                                    echo '☆☆☆☆☆ <span>No ratings</span>';
                                }
                                ?>
                            </div>
                        </div>
                    </a>
                    <?php endforeach; ?>
                </div>
            </div>
            
            <div id="top-courses" class="content-box" style="flex: 1; background-color: white; margin: 1rem; padding: 1.5rem; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1);">
                <h2><?php echo $currentLang == 'ja' ? '人気のコース' : 'Top Courses'; ?></h2>
                <div class="course-list">


                    <?php foreach ($topCourses as $course): ?>
                    <a href="course.php?course=<?php echo urlencode($course['english_course_name']); ?>&professor=<?php echo urlencode($course['professor_name'] ?? ''); ?>&lang=<?php echo $currentLang; ?>" style="text-decoration: none; color: inherit;">
                        <div class="course-item" data-id="<?php echo $isLoggedIn ? $course['id'] : 'login-required'; ?>">
                            <div>
                                <h3><?php echo htmlspecialchars($course['course_name'] ?? $course['name']); ?></h3>
                                <p><strong>Professor:</strong> <?php echo htmlspecialchars($course['professor_name'] ?? 'Unknown Professor'); ?></p>
                            </div>
                            <div class="rating">
                                <?php 
                                if ($course['avg_rating'] !== 'N/A' && $course['avg_rating'] > 0) {
                                    $fullStars = floor($course['avg_rating']);
                                    $hasHalfStar = $course['avg_rating'] - $fullStars >= 0.5;
                                    echo str_repeat('★', $fullStars);
                                    echo $hasHalfStar ? '½' : '';
                                    echo str_repeat('☆', 5 - $fullStars - ($hasHalfStar ? 1 : 0));
                                    echo ' <span>' . $course['avg_rating'] . '</span>';
                                } else {
                                    echo '☆☆☆☆☆ <span>No ratings</span>';
                                }
                                ?>
                            </div>
                        </div>
                    </a>
                    <?php endforeach; ?>


                    
                </div>
            </div>
        </main>
        
        <!-- Tips link redirects to tipsandtricks.php -->
        
        <!-- Modals for professor details -->
        <div id="professorModal" class="modal">
            <div class="modal-content">
                <span class="close-modal">&times;</span>
                <div class="modal-header">
                    <h2 id="modalProfessorName"></h2>
                    <div class="rating">
                        <span id="modalProfessorRating"></span>
                    </div>
                </div>
                <div class="modal-body">
                    <div class="detail-section">
                        <h4>Department</h4>
                        <p id="modalProfessorDepartment"></p>
                    </div>
                    <div class="detail-section">
                        <h4>Office Hours</h4>
                        <p id="modalProfessorOfficeHours"></p>
                    </div>
                    <div class="detail-section">
                        <h4>Contact</h4>
                        <p id="modalProfessorContact"></p>
                    </div>
                    <div class="reviews">
                        <h4>Reviews</h4>
                        <div id="professorReviews">
                            <!-- Reviews will be loaded dynamically -->
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Modals for course details -->
        <div id="courseModal" class="modal">
            <div class="modal-content">
                <span class="close-modal">&times;</span>
                <div class="modal-header">
                    <h2 id="modalCourseName"></h2>
                    <div class="rating">
                        <span id="modalCourseRating"></span>
                    </div>
                </div>
                <div class="modal-body">
                    <div class="detail-section">
                        <h4>Professor</h4>
                        <p id="modalCourseProfessor"></p>
                    </div>
                    <div class="detail-section">
                        <h4>Department</h4>
                        <p id="modalCourseDepartment"></p>
                    </div>
                    <div class="detail-section">
                        <h4>Description</h4>
                        <p id="modalCourseDescription"></p>
                    </div>
                    <div class="reviews">
                        <h4>Reviews</h4>
                        <div id="courseReviews">
                            <!-- Reviews will be loaded dynamically -->
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <footer>
        <p>&copy; 2025 Rate My Teacher. All rights reserved.</p>
    <p style="margin-top: 10px;">
        <a href="ToS.php?lang=<?php echo $currentLang; ?>" style="color: white; text-decoration: underline;">
            <?php echo $currentLang == 'ja' ? '利用規約' : 'Terms and Conditions'; ?>
        </a>
    </p>
        </footer>
    </div>
    
    <script>
        // Search functionality
        const searchButton = document.querySelector('.search-bar button');
        const searchInput = document.querySelector('.search-bar input');

        // Function to generate rating stars
        function generateStarRating(rating) {
            if (!rating || rating === 'N/A') return '<span class="no-rating">No ratings yet</span>';
            
            const fullStar = '★';
            const emptyStar = '☆';
            const numFullStars = Math.floor(parseFloat(rating));
            const hasHalfStar = parseFloat(rating) % 1 >= 0.5;
            
            let stars = fullStar.repeat(numFullStars);
            if (hasHalfStar) stars += '½';
            stars += emptyStar.repeat(5 - numFullStars - (hasHalfStar ? 1 : 0));
            
            return `<span class="star-rating">${stars} <span>${rating}</span></span>`;
        }

        // Function to display search results
        function displaySearchResults(data, searchTerm) {
            const searchResultsDiv = document.getElementById('searchResults');
            searchResultsDiv.innerHTML = '';
    
    
        // Get current language from cookie
        const currentLang = document.cookie.split('; ')
            .find(row => row.startsWith('language='))
            ?.split('=')[1] || 'en';
        
        // Language-specific labels
        const labels = {
            professors: currentLang === 'ja' ? '教授' : 'Professors',
            courses: currentLang === 'ja' ? 'コース' : 'Courses',
            department: currentLang === 'ja' ? '学部' : 'Department',
            departmentNotSpecified: currentLang === 'ja' ? '学部未指定' : 'Department not specified',
            professor: currentLang === 'ja' ? '教授' : 'Professor',
            code: currentLang === 'ja' ? 'コード' : 'Code',
            noRatingsYet: currentLang === 'ja' ? 'まだ評価がありません' : 'No ratings yet',
            review: currentLang === 'ja' ? 'レビュー' : 'review',
            reviews: currentLang === 'ja' ? 'レビュー' : 'reviews',
            viewAllResults: currentLang === 'ja' ? 'すべての結果を見る' : 'View all results',
            close: currentLang === 'ja' ? '閉じる' : 'Close',
            noResults: currentLang === 'ja' ? `"${searchTerm}"の検索結果はありません` : `No results found for "${searchTerm}"`
        };

            // Create container for results
            const resultsContainer = document.createElement('div');
            resultsContainer.className = 'search-results-container';
            
            // Check if we have any results
            const hasProfessors = data.professors && data.professors.length > 0;
            const hasCourses = data.courses && data.courses.length > 0;
            
            if (!hasProfessors && !hasCourses) {
                searchResultsDiv.innerHTML = `<div class="no-results">No results found for "${searchTerm}"</div>`;
                searchResultsDiv.style.display = 'block'; // Ensure dropdown is visible
                return;
            }
            
            // Add professors section if we have professors
            if (hasProfessors) {
                const profSection = document.createElement('div');
                profSection.className = 'search-section';
                profSection.innerHTML = `<h3>Professors (${data.professors.length})</h3>`;
                
                const profList = document.createElement('div');
                profList.className = 'search-list professor-search-list';
                
                data.professors.forEach(prof => {
                    const profItem = document.createElement('div');
                    profItem.className = 'search-item professor-search-item';
                    profItem.setAttribute('data-id', prof.id);
                    
                    const ratingHtml = generateStarRating(prof.avg_rating);
                    profItem.innerHTML = `
                        <div class="search-item-info">
                            <h4>${prof.name}</h4>
                            <p>${prof.department || 'Department not specified'}</p>
                        </div>
                        <div class="search-item-rating">
                            ${ratingHtml}
                            ${prof.review_count ? `<div class="rating-count">${prof.review_count} review${prof.review_count !== 1 ? 's' : ''}</div>` : ''}
                        </div>
                    `;
                    
                    // Add click event to view professor page
                    profItem.addEventListener('click', () => {
                        // Redirect to professor page
                        window.location.href = `professor_page_template.php?name=${encodeURIComponent(prof.name)}&lang=${currentLang}`;
                    });
                    
                    profList.appendChild(profItem);
                });
                
                profSection.appendChild(profList);
                resultsContainer.appendChild(profSection);
            }
            
            // Add courses section if we have courses
            if (hasCourses) {
                const courseSection = document.createElement('div');
                courseSection.className = 'search-section';
                courseSection.innerHTML = `<h3>Courses (${data.courses.length})</h3>`;
                
                const courseList = document.createElement('div');
                courseList.className = 'search-list course-search-list';
                
                data.courses.forEach(course => {
                    const courseItem = document.createElement('div');
                    courseItem.className = 'search-item course-search-item';
                    courseItem.setAttribute('data-id', course.id);
                    
                    const ratingHtml = generateStarRating(course.avg_rating);
                    courseItem.innerHTML = `
                        <div class="search-item-info">
                            <h4>${course.name}</h4>
                            <p>${course.professor_name ? `Professor: ${course.professor_name}` : ''} ${course.course_code ? `· Code: ${course.course_code}` : ''}</p>
                        </div>
                        <div class="search-item-rating">
                            ${ratingHtml}
                            ${course.review_count ? `<div class="rating-count">${course.review_count} review${course.review_count !== 1 ? 's' : ''}</div>` : ''}
                        </div>
                    `;
                    
                    // Add click event to view course page
                    courseItem.addEventListener('click', () => {
                        // Get current language from cookie
                        const currentLang = document.cookie.split('; ')
                            .find(row => row.startsWith('language='))
                            ?.split('=')[1] || 'en';
                        
                        // Redirect to course page
                        window.location.href = `course.php?course=${encodeURIComponent(course.name)}&professor=${encodeURIComponent(course.professor_name || '')}&lang=${currentLang}`;
                    });
                    
                    courseList.appendChild(courseItem);
                });
                
                courseSection.appendChild(courseList);
                resultsContainer.appendChild(courseSection);
            }
            
            // Add "View all results" link
            const viewAllLink = document.createElement('div');
            viewAllLink.className = 'view-all-results';
            viewAllLink.innerHTML = `<a href="search.php?q=${encodeURIComponent(searchTerm)}&lang=${document.cookie.split('; ').find(row => row.startsWith('language='))?.split('=')[1] || 'en'}">View all results</a>`;
            resultsContainer.appendChild(viewAllLink);
            
            // Add a close button
            //const closeButton = document.createElement('button');
            //closeButton.className = 'close-search-results';
            //closeButton.textContent = document.cookie.includes('language=ja') ? '閉じる' : 'Close';
            //closeButton.addEventListener('click', () => {
            //    searchResultsDiv.style.display = 'none';
            //});
            //resultsContainer.appendChild(closeButton);
            
            searchResultsDiv.appendChild(resultsContainer);
            searchResultsDiv.style.display = 'block';
        }

        // Initialize the search bar by running a test search after page load
        document.addEventListener('DOMContentLoaded', function() {
            console.log("DOM fully loaded");
            
            // Test click events on professors and courses
            console.log("Adding test click handlers to professors and courses");
            
            const profItems = document.querySelectorAll('.professor-item');
            console.log(`Found ${profItems.length} professor items`);
            profItems.forEach((item, i) => {
                console.log(`Professor item ${i+1} id: ${item.getAttribute('data-id')}`);
                
                // Add click event handler
                item.addEventListener('click', () => {
                    const profId = item.getAttribute('data-id');
                    console.log("Professor clicked, ID:", profId);
                    
                    // Check if login is required
                    if (profId === 'login-required') {
                        showLoginPrompt();
                        return;
                    }
                    
                    // Check if it's a real professor ID (numeric) or a placeholder
                    if (!isNaN(profId)) {
                        console.log("Redirecting professor with ID:", profId);
                        
                        // Get current language directly from the cookie instead of the variable
                        const currentLang = document.cookie.split('; ')
                            .find(row => row.startsWith('language='))
                            ?.split('=')[1] || 'en';
                        
                        console.log("Language for redirect (from cookie):", currentLang);
                        
                        // Get professor name from the HTML
                        const profName = item.querySelector('h3').textContent;
                        console.log("Professor name from HTML:", profName);
                        
                        // Create the redirect URL with the current language - no ID needed
                        const redirectUrl = `professor_page_template.php?name=${encodeURIComponent(profName)}&lang=${currentLang}`;
                        console.log("Redirecting to:", redirectUrl);
                        
                        // Perform the redirect
                        window.location.href = redirectUrl;
                        return;
                    }
                });
            });
            
            const courseItems = document.querySelectorAll('.course-item');
            console.log(`Found ${courseItems.length} course items`);
            courseItems.forEach((item, i) => {
                console.log(`Course item ${i+1} id: ${item.getAttribute('data-id')}`);
                
                // Add click event handler
                item.addEventListener('click', () => {
                    const courseId = item.getAttribute('data-id');
                    console.log("Course clicked, ID:", courseId);
                    
                    // Check if login is required
                    if (courseId === 'login-required') {
                        showLoginPrompt();
                        return;
                    }
                    
                    // Check if it's a real course ID (numeric) or a placeholder
                    if (!isNaN(courseId)) {
                        console.log("Redirecting course with ID:", courseId);
                        
                        // Get current language directly from the cookie instead of the variable
                        const currentLang = document.cookie.split('; ')
                            .find(row => row.startsWith('language='))
                            ?.split('=')[1] || 'en';
                            
                        console.log("Language for redirect (from cookie):", currentLang);
                        
                        // Get course name from the HTML
                        const courseName = item.querySelector('h3').textContent;
                        console.log("Course name from HTML:", courseName);
                        
                        // Create the redirect URL with the current language - using course.php instead
                        const redirectUrl = `course.php?course=${encodeURIComponent(courseName)}&lang=${currentLang}`;
                        console.log("Redirecting to:", redirectUrl);
                        
                        // Perform the redirect
                        location.href = redirectUrl;
                        return;
                    }
                });
            });
            
            // Update UI elements based on current language
            updateUILanguage();
            
            // Set up live search functionality
            let searchTimeout;
            searchInput.addEventListener('input', () => {
                const searchTerm = searchInput.value.trim();
                clearTimeout(searchTimeout);
                
                if (searchTerm.length >= 2) {
                    searchTimeout = setTimeout(() => {
                        performSearch(searchTerm);
                        console.log("Searching for: " + searchTerm);
                    }, 300);
                } else if (searchTerm.length === 0) {
                    document.getElementById('searchResults').style.display = 'none';
                }
            });
            
            // Clear any pre-filled search values
            searchInput.value = "";
            document.getElementById('searchResults').style.display = 'none';
            
            // Set up search button click event
            searchButton.addEventListener('click', () => {
                const searchTerm = searchInput.value.trim();
                if (searchTerm) {
                    performSearch(searchTerm);
                }
            });
            
            // Allow search on Enter key press
            searchInput.addEventListener('keypress', (e) => {
                if (e.key === 'Enter') {
                    searchButton.click();
                }
            });
            
            // Close search results when clicking outside
            document.addEventListener('click', (e) => {
                const searchResults = document.getElementById('searchResults');
                const searchContainer = document.querySelector('.search-container');
                
                if (!searchContainer.contains(e.target) && searchResults.style.display === 'block') {
                    searchResults.style.display = 'none';
                }
            });
        });
        
        // Language translations
        const translations = {
            english: {
                searchPlaceholder: "Search for professors or courses...",
                searchButton: "Search",
                menuDropdown: "Menu",
                login: "Login",
                register: "Register",
                myAccount: "My Account",
                logout: "Logout",
                popularProfessors: "Popular Professors",
                topCourses: "Top Courses",
                tips: "Tips and Tricks",
                studyLocations: "Study Locations",
                studyLocationsDesc: "The 𝝮 (Omega) Building has private study rooms that can be reserved through the student portal.",
                courseRegistration: "Course Registration",
                courseRegistrationDesc: "Register for popular courses within the first few hours of the registration window opening.",
                transportation: "Transportation",
                transportationDesc: "The campus shuttle runs every 15 minutes during peak hours and provides direct access to the train station.",
                bestCafeterias: "Best Cafeterias",
                bestCafeteriasDesc: "The cafeteria in the Main Building has the widest variety of food options and shortest wait times.",
                loginRequired: "You need to be logged in to view this content.",
                loginButton: "Login",
                registerButton: "Register",
                footer: "© 2025 Rate My Teacher. All rights reserved.",
                viewDetails: "View Details",
                close: "Close",
                reviews: "Reviews",
                department: "Department",
                officeHours: "Office Hours",
                contact: "Contact",
                professor: "Professor",
                description: "Description"
            },
            japanese: {
                searchPlaceholder: "教授やコースを検索...",
                searchButton: "検索",
                menuDropdown: "メニュー",
                login: "ログイン",
                register: "登録",
                myAccount: "マイアカウント",
                logout: "ログアウト",
                popularProfessors: "人気の教授",
                topCourses: "人気のコース",
                tips: "裏ワザ",
                studyLocations: "勉強場所",
                studyLocationsDesc: "𝝮（オメガ）棟には、学生ポータルから予約できる個人学習室があります。",
                courseRegistration: "履修登録",
                courseRegistrationDesc: "人気のコースは、登録期間が始まってから最初の数時間以内に登録してください。",
                transportation: "交通機関",
                transportationDesc: "キャンパスシャトルは、ピーク時には15分ごとに運行し、駅への直接アクセスを提供しています。",
                bestCafeterias: "おすすめの食堂",
                bestCafeteriasDesc: "本館の食堂は、最も多様な食事オプションと最短の待ち時間があります。",
                loginRequired: "このコンテンツを閲覧するにはログインが必要です。",
                loginButton: "ログイン",
                registerButton: "登録",
                footer: "© 2025 レートマイティーチャー - SU2H1. 全著作権所有。",
                viewDetails: "詳細を表示",
                close: "閉じる",
                reviews: "レビュー",
                department: "学科",
                officeHours: "オフィスアワー",
                contact: "連絡先",
                professor: "教授",
                description: "説明"
            }
        };

        // Function to update UI elements based on current language
        function updateUILanguage() {
            console.log("Updating UI for language:", currentLanguage);
            const translation = translations[translationLanguage];
            
            // Update dropdown menu
            if (document.querySelector('.dropbtn')) {
                document.querySelector('.dropbtn').textContent = translation.menuDropdown;
            }
            
            // Update dropdown menu items based on login status
            const menuItems = document.querySelectorAll('.dropdown-content a');
            if (isLoggedIn) {
                menuItems[0].textContent = translation.myAccount;
                menuItems[1].textContent = translation.logout;
            } else {
                menuItems[0].textContent = translation.login;
                menuItems[1].textContent = translation.register;
            }
            menuItems[2].textContent = translation.popularProfessors;
            menuItems[3].textContent = translation.topCourses;
            menuItems[4].textContent = translation.tips;
            
            // Update headings
            document.querySelectorAll('.content-box h2')[0].textContent = translation.popularProfessors;
            document.querySelectorAll('.content-box h2')[1].textContent = translation.topCourses;
            if (document.querySelectorAll('.content-box h2')[2]) {
                document.querySelectorAll('.content-box h2')[2].textContent = translation.tips;
            }
            
            // Update login required messages
            const loginMessages = document.querySelectorAll('.login-required p');
            const loginButtons = document.querySelectorAll('.login-required .btn');
            
            if (loginMessages.length > 0) {
                loginMessages.forEach(message => {
                    message.textContent = translation.loginRequired;
                });
                
                if (loginButtons.length > 0) {
                    for (let i = 0; i < loginButtons.length; i += 2) {
                        loginButtons[i].textContent = translation.loginButton;
                        loginButtons[i+1].textContent = translation.registerButton;
                    }
                }
            }
            
            // Update tips and tricks if visible
            const tipItems = document.querySelectorAll('.tip-item h3');
            const tipDescs = document.querySelectorAll('.tip-item p');
            
            if (tipItems.length > 0) {
                tipItems[0].textContent = translation.studyLocations;
                tipDescs[0].textContent = translation.studyLocationsDesc;
                tipItems[1].textContent = translation.courseRegistration;
                tipDescs[1].textContent = translation.courseRegistrationDesc;
                tipItems[2].textContent = translation.transportation;
                tipDescs[2].textContent = translation.transportationDesc;
                tipItems[3].textContent = translation.bestCafeterias;
                tipDescs[3].textContent = translation.bestCafeteriasDesc;
            }
            
            // Update footer
            document.querySelector('footer p').textContent = translation.footer;
            
            // Update modal texts
            document.querySelectorAll('.modal .close-modal').forEach(closeBtn => {
                closeBtn.setAttribute('title', translation.close);
            });
            document.querySelectorAll('.modal .reviews h4').forEach(reviewsHeader => {
                reviewsHeader.textContent = translation.reviews;
            });
            document.querySelectorAll('#professorModal .detail-section h4')[0].textContent = translation.department;
            document.querySelectorAll('#professorModal .detail-section h4')[1].textContent = translation.officeHours;
            document.querySelectorAll('#professorModal .detail-section h4')[2].textContent = translation.contact;
            document.querySelectorAll('#courseModal .detail-section h4')[0].textContent = translation.professor;
            document.querySelectorAll('#courseModal .detail-section h4')[1].textContent = translation.department;
            document.querySelectorAll('#courseModal .detail-section h4')[2].textContent = translation.description;
        }
        
        // Get current language from cookie - match PHP naming convention
        let currentLanguage = <?php echo (isset($_COOKIE['language']) && $_COOKIE['language'] == 'ja') ? "'ja'" : "'en'"; ?>;
        
        // For backwards compatibility with existing translations object
        let translationLanguage = currentLanguage === 'ja' ? 'japanese' : 'english';
        
        // Modal functionality
        const professorModal = document.getElementById('professorModal');
        const courseModal = document.getElementById('courseModal');
        const closeButtons = document.querySelectorAll('.close-modal');
        
        // Close modal when clicking the close button
        closeButtons.forEach(button => {
            button.addEventListener('click', () => {
                professorModal.style.display = 'none';
                courseModal.style.display = 'none';
            });
        });
        
        // Close modal when clicking outside the modal content
        window.addEventListener('click', (event) => {
            if (event.target === professorModal) {
                professorModal.style.display = 'none';
            }
            if (event.target === courseModal) {
                courseModal.style.display = 'none';
            }
        });
        
        // Function to show login prompt
        function showLoginPrompt() {
            // Create login prompt modal
            const loginModal = document.createElement('div');
            loginModal.style.cssText = 'position: fixed; z-index: 100; left: 0; top: 0; width: 100%; height: 100%; background-color: rgba(0,0,0,0.4); display: flex; align-items: center; justify-content: center;';
            
            const modalContent = document.createElement('div');
            modalContent.style.cssText = 'background-color: white; padding: 30px; border-radius: 8px; max-width: 400px; text-align: center; box-shadow: 0 5px 15px rgba(0,0,0,0.2);';
            
            // Check current language
            const isJapanese = currentLanguage === 'ja';
            
            const title = document.createElement('h3');
            title.textContent = isJapanese ? 'ログインが必要です' : 'Login Required';
            title.style.cssText = 'color: #1e3a8a; margin-top: 0;';
            
            const message = document.createElement('p');
            message.textContent = isJapanese 
                ? '詳細な評価やレビューを閲覧するにはログインが必要です。' 
                : 'You need to be logged in to view detailed ratings and reviews.';
            message.style.cssText = 'margin-bottom: 20px;';
            
            const buttonContainer = document.createElement('div');
            
            const loginButton = document.createElement('a');
            loginButton.textContent = isJapanese ? 'ログイン' : 'Login';
            loginButton.href = 'login.php';
            loginButton.style.cssText = 'display: inline-block; background-color: #1e3a8a; color: white; padding: 10px 20px; margin-right: 10px; text-decoration: none; border-radius: 4px;';
            
            const registerButton = document.createElement('a');
            registerButton.textContent = isJapanese ? '登録' : 'Register';
            registerButton.href = 'register.php';
            registerButton.style.cssText = 'display: inline-block; background-color: #6c757d; color: white; padding: 10px 20px; text-decoration: none; border-radius: 4px;';
            
            const closeButton = document.createElement('button');
            closeButton.textContent = isJapanese ? '閉じる' : 'Close';
            closeButton.style.cssText = 'display: block; width: 100%; background-color: #f8f9fa; border: 1px solid #ddd; color: #333; padding: 10px; margin-top: 15px; border-radius: 4px; cursor: pointer;';
            closeButton.onclick = function() {
                document.body.removeChild(loginModal);
            };
            
            buttonContainer.appendChild(loginButton);
            buttonContainer.appendChild(registerButton);
            
            modalContent.appendChild(title);
            modalContent.appendChild(message);
            modalContent.appendChild(buttonContainer);
            modalContent.appendChild(closeButton);
            
            loginModal.appendChild(modalContent);
            document.body.appendChild(loginModal);
            
            // Close modal when clicking outside
            loginModal.addEventListener('click', function(event) {
                if (event.target === loginModal) {
                    document.body.removeChild(loginModal);
                }
            });
        }
        
        function updateLanguageVariables() {
            // Read cookie directly
            const languageCookie = document.cookie.split('; ')
                .find(row => row.startsWith('language='));
            
            if (languageCookie) {
                currentLanguage = languageCookie.split('=')[1];
                translationLanguage = currentLanguage === 'ja' ? 'japanese' : 'english';
                console.log("Language variables updated:", currentLanguage, translationLanguage);
            }
        }

        // Add event listener for language toggle links
        document.querySelectorAll('.language-toggle a').forEach(link => {
            link.addEventListener('click', function(e) {
                // After click, update language variables after a short delay to allow cookie to set
                setTimeout(updateLanguageVariables, 100);
            });
        });

        // Also update language variables when page loads
        document.addEventListener('DOMContentLoaded', updateLanguageVariables);

    // Enhanced performSearch function with language support
    function performSearch(searchTerm) {
        // Don't search for very short terms
        if (searchTerm.length < 2) {
            document.getElementById('searchResults').style.display = 'none';
            return;
        }
        
        // Get references to DOM elements
        const searchResultsDiv = document.getElementById('searchResults');
        
        // Get current language from cookie
        const currentLang = document.cookie.split('; ')
            .find(row => row.startsWith('language='))
            ?.split('=')[1] || 'en';
        
        // Get loading text based on language
        const loadingText = currentLang === 'ja' ? '検索中...' : 'Searching...';
        
        // Clear previous results and show loading indicator
        searchResultsDiv.innerHTML = `<div class="loading-container"><div class="search-loading"></div><span>${loadingText}</span></div>`;
        searchResultsDiv.style.display = 'block';
        
        console.log(`Making search request for: "${searchTerm}" with language: ${currentLang}`);
        
        // Make an AJAX request to search the database with the language parameter
        fetch(`search_api.php?q=${encodeURIComponent(searchTerm)}&lang=${currentLang}`)
            .then(response => {
                if (!response.ok) {
                    throw new Error('Network response was not ok');
                }
                return response.json().catch(err => {
                    throw new Error('Failed to parse response as JSON');
                });
            })
            .then(data => {
                // Only update if the search input still contains the search term
                // This prevents outdated results from showing for a previous search
                const currentSearchTerm = document.querySelector('#searchInput').value.trim();
                if (currentSearchTerm === searchTerm) {
                    displaySearchResults(data, searchTerm);
                }
            })
            .catch(error => {
                console.error('Search error:', error);
                const errorText = currentLang === 'ja' 
                    ? `検索エラー: ${error.message}。後でもう一度お試しください。` 
                    : `Error performing search: ${error.message}. Please try again later.`;
                searchResultsDiv.innerHTML = `<div class="error">${errorText}</div>`;
            });
    }

    // Set up live search functionality with Japanese character support
    document.addEventListener('DOMContentLoaded', function() {
        const searchInput = document.querySelector('#searchInput');
        const searchButton = document.querySelector('#searchButton');
        const searchResultsDiv = document.getElementById('searchResults');
        
        // Update placeholder text based on current language
        function updateSearchPlaceholder() {
            const currentLang = document.cookie.split('; ')
                .find(row => row.startsWith('language='))
                ?.split('=')[1] || 'en';
            
            searchInput.placeholder = currentLang === 'ja' 
                ? '教授やコースを検索...' 
                : 'Search for professors or courses...';
            
            searchButton.textContent = currentLang === 'ja' ? '検索' : 'Search';
        }
        
        // Configure meta tag to ensure proper UTF-8 encoding
        const metaCharset = document.querySelector('meta[charset]');
        if (metaCharset) {
            metaCharset.setAttribute('charset', 'UTF-8');
        } else {
            const meta = document.createElement('meta');
            meta.setAttribute('charset', 'UTF-8');
            document.head.appendChild(meta);
        }
        
        // Call once when page loads
        updateSearchPlaceholder();
        
        // Update when language toggles are clicked
        document.querySelectorAll('.language-toggle a').forEach(link => {
            link.addEventListener('click', function() {
                // After a small delay to allow cookie to be set
                setTimeout(updateSearchPlaceholder, 100);
            });
        });
        
        // Debounce function to limit how often searches are performed while typing
        function debounce(func, wait) {
            let timeout;
            return function() {
                const context = this;
                const args = arguments;
                clearTimeout(timeout);
                timeout = setTimeout(() => {
                    func.apply(context, args);
                }, wait);
            };
        }
        
        // Configure live search with debounce
        // Use a shorter delay for Japanese input (200ms)
        const debouncedSearch = debounce(function(searchTerm) {
            if (searchTerm.length >= 1) {
                // Always search for Japanese characters, even if only 1 character
                const hasJapaneseChars = /[\u3000-\u303F]|[\u3040-\u309F]|[\u30A0-\u30FF]|[\uFF00-\uFFEF]|[\u4E00-\u9FAF]/u.test(searchTerm);
                
                if (hasJapaneseChars || searchTerm.length >= 2) {
                    performSearch(searchTerm);
                }
            } else {
                searchResultsDiv.style.display = 'none';
            }
        }, 200);
        
        // Listen for input events (typing)
        searchInput.addEventListener('input', function() {
            const searchTerm = this.value.trim();
            debouncedSearch(searchTerm);
            
            // If search field is cleared, hide results immediately
            if (searchTerm.length === 0) {
                searchResultsDiv.style.display = 'none';
            }
        });
        
        // Listen for composition events (for IME input methods like Japanese)
        let isComposing = false;
        
        searchInput.addEventListener('compositionstart', function() {
            isComposing = true;
        });
        
        searchInput.addEventListener('compositionend', function() {
            isComposing = false;
            // Trigger search after composition ends
            const searchTerm = this.value.trim();
            debouncedSearch(searchTerm);
        });
        
        // Maintain the search button functionality
        searchButton.addEventListener('click', function() {
            if (!isComposing) {
                const searchTerm = searchInput.value.trim();
                if (searchTerm.length >= 1) {
                    performSearch(searchTerm);
                }
            }
        });
        
        // Allow search on Enter key press
        searchInput.addEventListener('keypress', function(e) {
            if (e.key === 'Enter' && !isComposing) {
                const searchTerm = this.value.trim();
                if (searchTerm.length >= 1) {
                    performSearch(searchTerm);
                }
            }
        });
        
        // Hide search results when clicking outside
        document.addEventListener('click', function(e) {
            if (!searchInput.contains(e.target) && 
                !searchButton.contains(e.target) && 
                !searchResultsDiv.contains(e.target)) {
                searchResultsDiv.style.display = 'none';
            }
        });
    });
    </script>
</body>
</html>
