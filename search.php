<?php
/**
 * Search API for Rate My Teacher
 * 
 * This file handles AJAX search requests for professors and courses.
 */

// Include database connection
require_once 'config.php';

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// We'll log all search operations to check what's happening
$logFile = __DIR__ . '/search_log.txt';
file_put_contents($logFile, "Search started at " . date('Y-m-d H:i:s') . "\n", FILE_APPEND);

/**
 * Search for professors and courses based on a query
 * 
 * @param string $query The search query
 * @return array Array containing matching professors and courses
 */

function search($query) {
    global $logFile;
    
    // Load JSON data file for multilingual search
    $jsonFilePath = __DIR__ . '/sfc_courses.json';
    file_put_contents($logFile, "Loading JSON from: $jsonFilePath\n", FILE_APPEND);
    
    if (!file_exists($jsonFilePath)) {
        file_put_contents($logFile, "ERROR: JSON file not found\n", FILE_APPEND);
        return ['professors' => [], 'courses' => []];
    }
    
    $jsonData = file_get_contents($jsonFilePath);
    if ($jsonData === false) {
        file_put_contents($logFile, "ERROR: Could not read JSON file\n", FILE_APPEND);
        return ['professors' => [], 'courses' => []];
    }
    
    $data = json_decode($jsonData, true);
    if ($data === null) {
        file_put_contents($logFile, "ERROR: JSON parsing error: " . json_last_error_msg() . "\n", FILE_APPEND);
        return ['professors' => [], 'courses' => []];
    }
    
    file_put_contents($logFile, "JSON data loaded successfully\n", FILE_APPEND);
    
    // Make query lowercase for case-insensitive searching and clean it up
    $query = trim(strtolower($query));
    // Replace both regular and full-width spaces to make the search more lenient
    $normalizedQuery = str_replace([' ', '　'], '', $query);
    
    // If more than 2 characters, make an additional "short query" to match partial names
     $shortQuery = '';
    if (mb_strlen($query) > 2) {
         // Get the first 2 characters as a very lenient search term
         $shortQuery = mb_substr($query, 0, 2);
         file_put_contents($logFile, "Created short query: '$shortQuery' for more lenient matching\n", FILE_APPEND);
    }
    
    file_put_contents($logFile, "Search query: '$query', Normalized: '$normalizedQuery'\n", FILE_APPEND);
    
    $results = [
        'professors' => [],
        'courses' => []
    ];
    
    // Search in JSON data
    if (isset($data['courses'])) {
        // Get professors from courses
        $professorMatches = [];
        $courseMatches = [];
        
        $courseCount = count($data['courses']);
        file_put_contents($logFile, "Searching through $courseCount courses\n", FILE_APPEND);
        
        foreach ($data['courses'] as $course) {
            // Check course name matches in both languages (more lenient search)
            $courseMatch = false;
            
            // English course name
            $enCourseName = isset($course['translations']['en']['name']) ? 
                strtolower($course['translations']['en']['name']) : '';
            // Normalize English name (remove spaces)
            $enCourseNameNormalized = str_replace([' ', '　'], '', $enCourseName);
            
            // Japanese course name
            $jaCourseName = isset($course['translations']['ja']['name']) ? 
                strtolower($course['translations']['ja']['name']) : '';
            // Normalize Japanese name (remove both regular and full-width spaces)
            $jaCourseNameNormalized = str_replace([' ', '　'], '', $jaCourseName);
            
            // Check if query is in the normalized course names
            if ($enCourseNameNormalized && strpos($enCourseNameNormalized, $normalizedQuery) !== false) {
                $courseMatch = true;
                file_put_contents($logFile, "Match found in EN course name: $enCourseName\n", FILE_APPEND);
            }
            if ($jaCourseNameNormalized && strpos($jaCourseNameNormalized, $normalizedQuery) !== false) {
                $courseMatch = true;
                file_put_contents($logFile, "Match found in JA course name: $jaCourseName\n", FILE_APPEND);
            }
            
            // Try with the short query for more lenient matching if we have one
            if (!$courseMatch && !empty($shortQuery)) {
                if ($enCourseNameNormalized && strpos($enCourseNameNormalized, $shortQuery) !== false) {
                    $courseMatch = true;
                    file_put_contents($logFile, "Match found with short query in EN course name: $enCourseName\n", FILE_APPEND);
                }
                if ($jaCourseNameNormalized && strpos($jaCourseNameNormalized, $shortQuery) !== false) {
                    $courseMatch = true;
                    file_put_contents($logFile, "Match found with short query in JA course name: $jaCourseName\n", FILE_APPEND);
                }
            }
            
            // If any part of the course data matches, add to results
            if ($courseMatch) {
                $courseData = [
                    'id' => $course['course_id'],
                    'name' => $course['translations']['en']['name'],
                    'name_ja' => $course['translations']['ja']['name'],
                    'course_code' => $course['course_id'],
                    'description' => $course['translations']['en']['field'] ?? '',
                    'description_ja' => $course['translations']['ja']['field'] ?? '',
                    'year' => $course['year'],
                    'avg_rating' => null,
                    'rating_count' => 0
                ];
                
                // Add professor info if available
                if (!empty($course['professors']) && isset($course['professors'][0])) {
                    $courseData['professor_name'] = $course['professors'][0]['name']['en'];
                    $courseData['professor_name_ja'] = $course['professors'][0]['name']['ja'];
                    $courseData['professor_name_no_spaces'] = str_replace(' ', '', $course['professors'][0]['name']['en']);
                    $courseData['professor_id'] = md5($course['professors'][0]['name']['en']); // Generate a unique ID
                    $courseData['department'] = $course['professors'][0]['department']['en'];
                    $courseData['department_ja'] = $course['professors'][0]['department']['ja'];
                }
                
                $courseMatches[] = $courseData;
            }
            
            // Check professor name matches - very lenient search
            $profCount = count($course['professors']);
            foreach ($course['professors'] as $professor) {
                $profMatch = false;
                
                // English name processing
                $enProfName = isset($professor['name']['en']) ? 
                    strtolower($professor['name']['en']) : '';
                // Normalize English name (remove both regular and full-width spaces)
                $enProfNameNormalized = str_replace([' ', '　'], '', $enProfName);
                
                // Japanese name processing
                $jaProfName = isset($professor['name']['ja']) ? 
                    strtolower($professor['name']['ja']) : '';
                // Normalize Japanese name (remove both regular and full-width spaces)
                $jaProfNameNormalized = str_replace([' ', '　'], '', $jaProfName);
                
                // Check various ways the query might match professor names
                if (
                    // Match normalized names (no spaces)
                    ($enProfNameNormalized && strpos($enProfNameNormalized, $normalizedQuery) !== false) ||
                    ($jaProfNameNormalized && strpos($jaProfNameNormalized, $normalizedQuery) !== false) ||
                    // Match original query with spaces in names
                    ($enProfName && strpos($enProfName, $query) !== false) ||
                    ($jaProfName && strpos($jaProfName, $query) !== false) ||
                    // If we have a short query, use it for more lenient matching
                    (!empty($shortQuery) && $enProfNameNormalized && strpos($enProfNameNormalized, $shortQuery) !== false) ||
                    (!empty($shortQuery) && $jaProfNameNormalized && strpos($jaProfNameNormalized, $shortQuery) !== false)
                ) {
                    $profMatch = true;
                    file_put_contents($logFile, "Match found in professor name: EN=$enProfName, JA=$jaProfName\n", FILE_APPEND);
                }
                
                // If professor matches and not already in results
                if ($profMatch) {
                    $profId = md5($professor['name']['en']); // Generate a unique ID
                    if (!isset($professorMatches[$profId])) {
                        $professorMatches[$profId] = [
                            'id' => $profId,
                            'name' => $professor['name']['en'],
                            'name_ja' => $professor['name']['ja'],
                            'name_no_spaces' => str_replace(' ', '', $professor['name']['en']),
                            'department' => $professor['department']['en'],
                            'department_ja' => $professor['department']['ja'],
                            'bio' => '',
                            'avg_rating' => null,
                            'rating_count' => 0
                        ];
                    }
                }
            }
        }
        
        // Add unique professors to results
        $results['professors'] = array_values($professorMatches);
        file_put_contents($logFile, "Found " . count($results['professors']) . " matching professors\n", FILE_APPEND);
        
        // Add courses to results
        $results['courses'] = $courseMatches;
        file_put_contents($logFile, "Found " . count($results['courses']) . " matching courses\n", FILE_APPEND);
    }
    
    return $results;
}

// Handle AJAX search requests
if (isset($_GET['query'])) {
    if (!headers_sent()) {
        header('Content-Type: application/json');
    }
    $query = trim($_GET['query']);
    
    file_put_contents($logFile, "AJAX request received with query: $query\n", FILE_APPEND);
    
    if (empty($query)) {
        // Return empty results if query is empty
        echo json_encode(['professors' => [], 'courses' => []]);
        file_put_contents($logFile, "Empty query, returning empty results\n", FILE_APPEND);
    } else {
        $results = search($query);
        
        // Add query for debugging
        $results['query'] = $query;
        
        // Limit results for dropdown to 5 of each type
        if (isset($_GET['limit']) && $_GET['limit'] === 'true') {
            if (count($results['professors']) > 5) {
                $results['professors'] = array_slice($results['professors'], 0, 5);
                $results['professors_more'] = true;
            }
            
            if (count($results['courses']) > 5) {
                $results['courses'] = array_slice($results['courses'], 0, 5);
                $results['courses_more'] = true;
            }
        }
        
        // Make sure we're sending valid JSON
        $json = json_encode($results);
        if ($json === false) {
            // Handle JSON encoding error
            $error = json_last_error_msg();
            file_put_contents($logFile, "JSON encoding error: $error\n", FILE_APPEND);
            echo json_encode([
                'error' => 'JSON encoding error: ' . $error,
                'query' => $query
            ]);
        } else {
            file_put_contents($logFile, "Returning JSON response with " . 
                count($results['professors']) . " professors and " . 
                count($results['courses']) . " courses\n", FILE_APPEND);
            echo $json;
        }
    }
    file_put_contents($logFile, "Request completed\n\n", FILE_APPEND);
    exit;
}

// If accessed directly with a query parameter in the URL, show search results page
if (isset($_GET['q'])) {
    $query = trim($_GET['q']);
    $results = [];
    
    file_put_contents($logFile, "Direct access with query: $query\n", FILE_APPEND);
    
    if (!empty($query)) {
        $results = search($query);
    }
    
    // Get language parameter
    $lang = isset($_GET['lang']) ? $_GET['lang'] : 'ja';
    if ($lang === 'jp') $lang = 'ja'; // Convert jp to ja for consistency
    
    // Start session to check if user is logged in
    session_start();
    $isLoggedIn = isset($_SESSION["loggedin"]) && $_SESSION["loggedin"] === true;
    
    // HTML page for search results
    include 'search_results_template.php';
    file_put_contents($logFile, "Displayed search results page\n\n", FILE_APPEND);
    exit;
}

// If accessed directly without any parameters, return empty results
if (!headers_sent()) {
    header('Content-Type: application/json');
}
file_put_contents($logFile, "Direct access without parameters\n\n", FILE_APPEND);
echo json_encode(['professors' => [], 'courses' => []]);