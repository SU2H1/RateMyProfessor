<?php
// Include database configuration
require_once 'config.php';

// Get search query from GET parameter
$searchQuery = isset($_GET['q']) ? trim($_GET['q']) : '';

// Initialize results array
$results = [
    'professors' => [],
    'courses' => []
];

// Only proceed if we have a search query
if (!empty($searchQuery)) {
    try {
        // Search for professors
        $stmt = $conn->prepare("
            SELECT id, name, department, 
                   COALESCE(overall_rating, (avg_content_quality + avg_difficulty) / 2) as avg_rating,
                   COALESCE(review_count, 0) as review_count
            FROM professors
            WHERE name LIKE :search OR department LIKE :search
            ORDER BY 
                /* Prioritize exact matches to the top */
                CASE WHEN LOWER(name) = LOWER(:exact) THEN 0 ELSE 1 END,
                CASE WHEN LOWER(department) = LOWER(:exact) THEN 0 ELSE 1 END,
                avg_rating DESC, review_count DESC
            LIMIT 10
        ");
        $stmt->bindValue(':search', '%' . $searchQuery . '%', SQLITE3_TEXT);
        $stmt->bindValue(':exact', $searchQuery, SQLITE3_TEXT);
        $result = $stmt->execute();
        
        while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
            // Format the rating if it exists
            if (isset($row['avg_rating']) && $row['avg_rating'] !== null) {
                $row['avg_rating'] = number_format((float)$row['avg_rating'], 1);
            } else {
                $row['avg_rating'] = null;
            }
            
            $results['professors'][] = $row;
        }
        
        // Search for courses
        $stmt = $conn->prepare("
            SELECT c.id, c.name, c.course_code,
                   COALESCE(c.avg_content_quality, 0) as avg_content_quality,
                   COALESCE(c.avg_difficulty, 0) as avg_difficulty,
                   (COALESCE(c.avg_content_quality, 0) + (5 - COALESCE(c.avg_difficulty, 0))) / 2 as avg_rating,
                   COALESCE(c.review_count, 0) as review_count,
                   p.name as professor_name
            FROM courses c
            LEFT JOIN professors p ON c.professor_id = p.id
            WHERE c.name LIKE :search OR c.course_code LIKE :search OR p.name LIKE :search
            ORDER BY 
                /* Prioritize exact matches to the top */
                CASE WHEN LOWER(c.name) = LOWER(:exact) THEN 0 ELSE 1 END,
                CASE WHEN LOWER(c.course_code) = LOWER(:exact) THEN 0 ELSE 1 END,
                avg_rating DESC, review_count DESC
            LIMIT 10
        ");
        $stmt->bindValue(':search', '%' . $searchQuery . '%', SQLITE3_TEXT);
        $stmt->bindValue(':exact', $searchQuery, SQLITE3_TEXT);
        $result = $stmt->execute();
        
        while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
            // Format the rating if it exists
            if (isset($row['avg_rating']) && $row['avg_rating'] !== null) {
                $row['avg_rating'] = number_format((float)$row['avg_rating'], 1);
            } else {
                $row['avg_rating'] = null;
            }
            
            $results['courses'][] = $row;
        }
    } catch (Exception $e) {
        $results['error'] = "Error performing search: " . $e->getMessage();
    }
}

// Return results as JSON
header('Content-Type: application/json');
echo json_encode($results);