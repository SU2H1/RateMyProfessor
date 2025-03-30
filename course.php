<?php
/**
 * Course Detail Page
 * This file redirects to the course_page_template.php file
 * with consistent handling of professor names and course codes
 */

// Debugging - log all parameters
error_log("Course.php accessed with parameters: " . print_r($_GET, true));

// Get course info from URL - support both formats
$course_id = isset($_GET['id']) ? $_GET['id'] : null;
$course_name = isset($_GET['course']) ? $_GET['course'] : '';
$professor_name = isset($_GET['professor']) ? $_GET['professor'] : '';
$course_code = isset($_GET['course_code']) ? $_GET['course_code'] : '';
$language = isset($_GET['lang']) ? $_GET['lang'] : 'en';
$year = isset($_GET['year']) ? $_GET['year'] : null;

// Load JSON data file to get professor name mappings
$jsonFilePath = __DIR__ . '/sfc_courses.json';
$professorNameForUrl = $professor_name; // Default to whatever was provided
$professorJaToEn = []; // Mapping of Japanese to English names

// Process JSON data to build name mappings
if (!empty($professor_name) && file_exists($jsonFilePath)) {
    $jsonData = file_get_contents($jsonFilePath);
    if ($jsonData !== false) {
        $data = json_decode($jsonData, true);
        if ($data !== null) {
            // Build Japanese to English name mapping
            foreach ($data['courses'] as $course) {
                foreach ($course['professors'] as $prof) {
                    if (isset($prof['name']['ja']) && isset($prof['name']['en'])) {
                        $professorJaToEn[$prof['name']['ja']] = $prof['name']['en'];
                    }
                }
            }
            
            // Check if we have a Japanese name that needs to be mapped to English
            if (isset($professorJaToEn[$professor_name])) {
                $professorNameForUrl = $professorJaToEn[$professor_name];
                error_log("Found English professor name for '{$professor_name}': {$professorNameForUrl}");
            } else {
                // If not found in the mapping, preserve the original name
                error_log("No mapping found for professor name: {$professor_name}");
            }
        }
    }
}

// IMPORTANT: For consistent URL handling, do NOT remove spaces from professor name
// This ensures + signs appear in URLs regardless of language

// Build the URL for the redirect
$redirect_url = 'course_page_template.php?';

// Prioritize course_code over other parameters
if (!empty($course_code)) {
    $redirect_url .= 'course_code=' . urlencode($course_code) . '&';
} 
// Fall back to other parameters for backward compatibility
else {
    if (!empty($course_id)) {
        $redirect_url .= 'id=' . urlencode($course_id) . '&';
    }
    
    if (!empty($course_name)) {
        // Also add course_code parameter for compatibility with new format
        // Try to find course_code from database if available
        $db = new SQLite3('database/ratemyteacher.db');
        $stmt = $db->prepare("SELECT course_code FROM courses WHERE name = :name LIMIT 1");
        $stmt->bindValue(':name', $course_name, SQLITE3_TEXT);
        $result = $stmt->execute();
        $row = $result->fetchArray(SQLITE3_ASSOC);
        
        if ($row && !empty($row['course_code'])) {
            $redirect_url .= 'course_code=' . urlencode($row['course_code']) . '&';
        } else {
            // Keep backward compatibility if we couldn't find the course code
            $redirect_url .= 'course=' . urlencode($course_name) . '&';
        }
    }
}

// Add professor parameter if available
if (!empty($professorNameForUrl)) {
    $redirect_url .= 'professor=' . urlencode($professorNameForUrl) . '&';
}

// Add year parameter if available
if (!empty($year)) {
    $redirect_url .= 'year=' . urlencode($year) . '&';
}

// Add language parameter last
$redirect_url .= 'lang=' . urlencode($language);

// Log the final redirect URL
error_log("Redirecting to: " . $redirect_url);

// Redirect to the template
header("Location: $redirect_url");
exit;