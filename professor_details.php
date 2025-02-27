<?php
/**
 * Professor Details API
 * 
 * This file handles requests for detailed professor information,
 * including ratings and reviews.
 */

// Include database configuration
require_once 'config.php';

// Start session to check if user is logged in
session_start();
$isLoggedIn = isset($_SESSION["loggedin"]) && $_SESSION["loggedin"] === true;

// Check if professor ID is provided
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Invalid professor ID']);
    exit;
}

$professorId = (int) $_GET['id'];

// Create database connection
$db = new SQLite3('database/ratemyteacher.db');

// Get professor details
$stmt = $db->prepare("
    SELECT 
        p.id,
        p.name,
        p.department,
        p.bio,
        (SELECT AVG(r.rating) FROM ratings r WHERE r.professor_id = p.id) as avg_rating,
        (SELECT COUNT(r.id) FROM ratings r WHERE r.professor_id = p.id) as rating_count,
        (SELECT AVG(r.content_quality) FROM ratings r WHERE r.professor_id = p.id) as avg_content_quality,
        (SELECT AVG(r.difficulty) FROM ratings r WHERE r.professor_id = p.id) as avg_difficulty
    FROM professors p
    WHERE p.id = :id
");
$stmt->bindValue(':id', $professorId, SQLITE3_INTEGER);
$result = $stmt->execute();
$professor = $result->fetchArray(SQLITE3_ASSOC);

if (!$professor) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Professor not found']);
    exit;
}

// Format the rating to 1 decimal place
if ($professor['avg_rating']) {
    $professor['avg_rating'] = number_format((float)$professor['avg_rating'], 1);
}

// Get professor courses
$stmt = $db->prepare("
    SELECT 
        c.id,
        c.name,
        c.course_code,
        c.description
    FROM courses c
    WHERE c.professor_id = :professor_id
    ORDER BY c.name
");
$stmt->bindValue(':professor_id', $professorId, SQLITE3_INTEGER);
$result = $stmt->execute();

$courses = [];
while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
    $courses[] = $row;
}
$professor['courses'] = $courses;

// Get reviews, but only if user is logged in
$professor['reviews'] = [];
if ($isLoggedIn) {
    $stmt = $db->prepare("
        SELECT 
            r.id,
            r.rating,
            r.comment,
            r.created_at,
            u.username,
            c.name as course_name,
            c.course_code
        FROM ratings r
        JOIN users u ON r.user_id = u.id
        JOIN courses c ON r.course_id = c.id
        WHERE r.professor_id = :professor_id
        ORDER BY r.created_at DESC
        LIMIT 20
    ");
    $stmt->bindValue(':professor_id', $professorId, SQLITE3_INTEGER);
    $result = $stmt->execute();
    
    while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
        // Format the date
        $timestamp = strtotime($row['created_at']);
        $row['created_at'] = date('F j, Y', $timestamp);
        
        $professor['reviews'][] = $row;
    }
}

// Add extra fields for display in the modal
$professor['office_hours'] = "Contact department for office hours";
$professor['contact'] = "Contact department for contact information";

// Return the professor data as JSON
header('Content-Type: application/json');
echo json_encode($professor);