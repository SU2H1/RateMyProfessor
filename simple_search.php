<?php
// A very simple search implementation to diagnose issues
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, X-Requested-With');

// Connect to the database
$db_path = __DIR__ . '/database/ratemyteacher.db';

try {
    // Check if database exists
    if (!file_exists($db_path)) {
        echo json_encode(['error' => 'Database file not found', 'path' => $db_path]);
        exit;
    }
    
    // Create connection
    $db = new SQLite3($db_path);
    
    // Get query parameter
    $query = isset($_GET['query']) ? trim($_GET['query']) : '';
    
    if (empty($query)) {
        echo json_encode(['professors' => [], 'courses' => []]);
        exit;
    }
    
    // Search query - very basic version
    $results = [
        'professors' => [],
        'courses' => []
    ];
    
    // Get professors
    $stmt = $db->prepare("SELECT id, name, department FROM professors WHERE LOWER(name) LIKE LOWER(?) OR LOWER(department) LIKE LOWER(?)");
    $stmt->bindValue(1, "%$query%", SQLITE3_TEXT);
    $stmt->bindValue(2, "%$query%", SQLITE3_TEXT);
    $result = $stmt->execute();
    
    while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
        $results['professors'][] = $row;
    }
    
    // Get courses - simplified query to troubleshoot
    $stmt = $db->prepare("SELECT * FROM courses WHERE LOWER(name) LIKE LOWER(?) OR LOWER(course_code) LIKE LOWER(?) OR LOWER(description) LIKE LOWER(?)");
    $stmt->bindValue(1, "%$query%", SQLITE3_TEXT);
    $stmt->bindValue(2, "%$query%", SQLITE3_TEXT);
    $stmt->bindValue(3, "%$query%", SQLITE3_TEXT);
    $result = $stmt->execute();
    
    while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
        $results['courses'][] = $row;
    }
    
    // Get all courses and professors for debugging
    $all_courses = [];
    $all_profs = [];
    
    $result = $db->query("SELECT id, name, course_code FROM courses");
    while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
        $all_courses[] = $row;
    }
    
    $result = $db->query("SELECT id, name, department FROM professors");
    while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
        $all_profs[] = $row;
    }
    
    // Add debug information
    $results['debug'] = [
        'query' => $query,
        'database_path' => $db_path,
        'database_exists' => file_exists($db_path),
        'all_courses_count' => count($all_courses),
        'all_professors_count' => count($all_profs),
        'first_few_courses' => array_slice($all_courses, 0, 3),
        'first_few_profs' => array_slice($all_profs, 0, 3)
    ];
    
    // Return results
    echo json_encode($results);
    
} catch (Exception $e) {
    echo json_encode(['error' => $e->getMessage()]);
}
?>