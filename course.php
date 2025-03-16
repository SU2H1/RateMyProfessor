<?php
/**
 * Course Detail Page
 * This file redirects to the course_page_template.php file
 */

// Debugging - log all parameters
error_log("Course.php accessed with parameters: " . print_r($_GET, true));

// Get course info from URL - support both formats
$course_id = isset($_GET['id']) ? $_GET['id'] : null;
$course_name = isset($_GET['course']) ? $_GET['course'] : '';
$professor_name = isset($_GET['professor']) ? $_GET['professor'] : '';
$language = isset($_GET['lang']) ? $_GET['lang'] : 'en';
$year = isset($_GET['year']) ? $_GET['year'] : null;

// Load JSON data file to get English professor name if Japanese is provided
$professorNameForUrl = $professor_name; // Default to whatever was provided

// Only process if we have a professor name and it might be Japanese
if (!empty($professor_name)) {
    $jsonFilePath = __DIR__ . '/sfc_courses.json';
    if (file_exists($jsonFilePath)) {
        $jsonData = file_get_contents($jsonFilePath);
        if ($jsonData !== false) {
            $data = json_decode($jsonData, true);
            if ($data !== null) {
                // Look for a matching Japanese professor name and get the English version
                foreach ($data['courses'] as $course) {
                    foreach ($course['professors'] as $prof) {
                        if (isset($prof['name']['ja']) && $prof['name']['ja'] === $professor_name) {
                            // Found a match! Use the English name instead
                            $professorNameForUrl = $prof['name']['en'];
                            error_log("Found English professor name for '{$professor_name}': {$professorNameForUrl}");
                            break 2; // Break out of both loops
                        }
                    }
                }
            }
        }
    }
    
    // If we have an English name, remove spaces as per the expected URL format
    if ($professorNameForUrl !== $professor_name) {
        $professorNameForUrl = str_replace(' ', '', $professorNameForUrl);
    }
}

// Build the URL for the redirect
$redirect_url = 'course_page_template.php?';

// Add parameters to the URL
if (!empty($course_id)) {
    $redirect_url .= 'id=' . urlencode($course_id) . '&';
}
if (!empty($course_name)) {
    $redirect_url .= 'course=' . urlencode($course_name) . '&';
}
if (!empty($professorNameForUrl)) {
    $redirect_url .= 'professor=' . urlencode($professorNameForUrl) . '&';
}
if (!empty($year)) {
    $redirect_url .= 'year=' . urlencode($year) . '&';
}
$redirect_url .= 'lang=' . urlencode($language);

// Redirect to the template
header("Location: $redirect_url");
exit;