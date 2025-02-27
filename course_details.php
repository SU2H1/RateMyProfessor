<?php
/**
 * Course Details API
 * 
 * This file handles requests for detailed course information,
 * including ratings and reviews.
 */

// Include database configuration
require_once 'config.php';

// Start session to check if user is logged in
session_start();
$isLoggedIn = isset($_SESSION["loggedin"]) && $_SESSION["loggedin"] === true;

// Check if course ID is provided
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Invalid course ID']);
    exit;
}

$courseId = (int) $_GET['id'];

// Create database connection
$db = new SQLite3('database/ratemyteacher.db');

// Get course details with professor information
$stmt = $db->prepare("
    SELECT 
        c.id,
        c.name,
        c.course_code,
        c.description,
        p.id as professor_id,
        p.name as professor_name,
        p.department,
        (SELECT AVG(r.rating) FROM ratings r WHERE r.course_id = c.id) as avg_rating,
        (SELECT COUNT(r.id) FROM ratings r WHERE r.course_id = c.id) as rating_count
    FROM courses c
    JOIN professors p ON c.professor_id = p.id
    WHERE c.id = :id
");
$stmt->bindValue(':id', $courseId, SQLITE3_INTEGER);
$result = $stmt->execute();
$course = $result->fetchArray(SQLITE3_ASSOC);

if (!$course) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Course not found']);
    exit;
}

// Format the rating to 1 decimal place
if ($course['avg_rating']) {
    $course['avg_rating'] = number_format((float)$course['avg_rating'], 1);
}

// Get other courses by the same professor
$stmt = $db->prepare("
    SELECT 
        id,
        name,
        course_code
    FROM courses
    WHERE professor_id = :professor_id AND id != :course_id
    ORDER BY name
    LIMIT 5
");
$stmt->bindValue(':professor_id', $course['professor_id'], SQLITE3_INTEGER);
$stmt->bindValue(':course_id', $courseId, SQLITE3_INTEGER);
$result = $stmt->execute();

$relatedCourses = [];
while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
    $relatedCourses[] = $row;
}
$course['related_courses'] = $relatedCourses;

// Get reviews, but only if user is logged in
$course['reviews'] = [];
if ($isLoggedIn) {
    $stmt = $db->prepare("
        SELECT 
            r.id,
            r.rating,
            r.comment,
            r.created_at,
            u.username
        FROM ratings r
        JOIN users u ON r.user_id = u.id
        WHERE r.course_id = :course_id
        ORDER BY r.created_at DESC
        LIMIT 20
    ");
    $stmt->bindValue(':course_id', $courseId, SQLITE3_INTEGER);
    $result = $stmt->execute();
    
    while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
        // Format the date
        $timestamp = strtotime($row['created_at']);
        $row['created_at'] = date('F j, Y', $timestamp);
        
        $course['reviews'][] = $row;
    }
}

// Return the course data as JSON
header('Content-Type: application/json');
echo json_encode($course);