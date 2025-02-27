<?php
// Simple CORS-enabled test script
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, X-Requested-With');
header('Content-Type: application/json');

// Query parameter
$query = isset($_GET['query']) ? trim($_GET['query']) : '';

// Return simple mock data
$result = [
    'professors' => [
        ['id' => 1, 'name' => 'Test Professor', 'department' => 'Test Department'],
        ['id' => 2, 'name' => 'Another Professor', 'department' => 'Another Department']
    ],
    'courses' => [
        ['id' => 1, 'name' => 'Test Course', 'course_code' => 'TEST101'],
        ['id' => 2, 'name' => 'Another Course', 'course_code' => 'TEST102']
    ],
    'query' => $query,
    'timestamp' => time()
];

echo json_encode($result);
?>