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

// Log file
$logFile = __DIR__ . '/search_log.txt';
file_put_contents($logFile, "Search started at " . date('Y-m-d H:i:s') . "\n", FILE_APPEND);

/**
 * Push exact match to the top of the array
 * 
 * @param array $results The search results array
 * @param string $query The exact query string
 */
function prioritizeExactMatch(&$results, $query) {
    foreach (['professors', 'courses'] as $key) {
        foreach ($results[$key] as $index => $item) {
            if (
                (isset($item['name']) && strtolower($item['name']) === strtolower($query)) ||
                (isset($item['name_ja']) && strtolower($item['name_ja']) === strtolower($query))
            ) {
                // Move exact match to the front
                array_unshift($results[$key], array_splice($results[$key], $index, 1)[0]);
                file_put_contents($logFile, "Exact match found and prioritized: {$item['name']}\n", FILE_APPEND);
                break;
            }
        }
    }
}

/**
 * Search for professors and courses based on a query
 * 
 * @param string $query The search query
 * @return array Array containing matching professors and courses
 */
function search($query) {
    global $logFile;
    
    $jsonFilePath = __DIR__ . '/sfc_courses.json';
    if (!file_exists($jsonFilePath)) {
        return ['professors' => [], 'courses' => []];
    }
    
    $jsonData = file_get_contents($jsonFilePath);
    $data = json_decode($jsonData, true);
    
    $query = trim(strtolower($query));
    $normalizedQuery = str_replace([' ', '　'], '', $query);
    
    $results = ['professors' => [], 'courses' => []];
    
    foreach ($data['courses'] as $course) {
        $courseMatch = false;
        $enCourseName = strtolower($course['translations']['en']['name'] ?? '');
        $jaCourseName = strtolower($course['translations']['ja']['name'] ?? '');
        
        if (strpos(str_replace([' ', '　'], '', $enCourseName), $normalizedQuery) !== false ||
            strpos(str_replace([' ', '　'], '', $jaCourseName), $normalizedQuery) !== false) {
            $courseMatch = true;
        }
        
        if ($courseMatch) {
            $results['courses'][] = [
                'id' => $course['course_id'],
                'name' => $course['translations']['en']['name'],
                'name_ja' => $course['translations']['ja']['name'],
                'course_code' => $course['course_id'],
            ];
        }
        
        foreach ($course['professors'] as $professor) {
            $profMatch = false;
            $enProfName = strtolower($professor['name']['en'] ?? '');
            $jaProfName = strtolower($professor['name']['ja'] ?? '');
            
            if (strpos(str_replace([' ', '　'], '', $enProfName), $normalizedQuery) !== false ||
                strpos(str_replace([' ', '　'], '', $jaProfName), $normalizedQuery) !== false) {
                $profMatch = true;
            }
            
            if ($profMatch) {
                $results['professors'][] = [
                    'id' => md5($professor['name']['en']),
                    'name' => $professor['name']['en'],
                    'name_ja' => $professor['name']['ja'],
                ];
            }
        }
    }
    
    prioritizeExactMatch($results, $query);
    
    return $results;    
}

    // Handle AJAX search requests
    if (isset($_GET['query'])) {
    header('Content-Type: application/json');
    $query = trim($_GET['query']);

    $results = empty($query) ? ['professors' => [], 'courses' => []] : search($query);
    echo json_encode($results);
    exit;   
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
    
    file_put_contents($logFile, "Request completed\n\n", FILE_APPEND);
    exit;


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