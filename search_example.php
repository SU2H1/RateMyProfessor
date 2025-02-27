<?php
/**
 * Search Example
 * 
 * This file demonstrates how to implement search functionality
 * for professors and courses from the database.
 */

// Include database connection
require_once 'config.php';

/**
 * Search for professors and courses based on a query
 * 
 * @param string $query The search query
 * @return array Array containing matching professors and courses
 */
function search($query) {
    $db = new SQLite3('database/ratemyteacher.db');
    $results = [
        'professors' => [],
        'courses' => []
    ];
    
    // Search professors
    $stmt = $db->prepare("
        SELECT id, name, department, bio 
        FROM professors 
        WHERE name LIKE :query OR department LIKE :query
        ORDER BY name
    ");
    $stmt->bindValue(':query', "%$query%", SQLITE3_TEXT);
    $result = $stmt->execute();
    
    while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
        $results['professors'][] = $row;
    }
    
    // Search courses
    $stmt = $db->prepare("
        SELECT c.id, c.name, c.course_code, c.description, p.name as professor_name, p.id as professor_id
        FROM courses c
        JOIN professors p ON c.professor_id = p.id
        WHERE c.name LIKE :query OR c.course_code LIKE :query OR p.name LIKE :query
        ORDER BY c.name
    ");
    $stmt->bindValue(':query', "%$query%", SQLITE3_TEXT);
    $result = $stmt->execute();
    
    while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
        $results['courses'][] = $row;
    }
    
    return $results;
}

// Example usage in AJAX handler
if (isset($_GET['ajax']) && $_GET['ajax'] === 'search') {
    header('Content-Type: application/json');
    $query = $_GET['query'] ?? '';
    $results = search($query);
    echo json_encode($results);
    exit;
}
?>

<!-- Sample search form HTML -->
<form id="searchForm" method="GET">
    <input type="text" name="q" id="searchInput" placeholder="Search professors or courses...">
    <button type="submit">Search</button>
</form>

<div id="searchResults">
    <!-- Results will be shown here -->
</div>

<script>
// Sample JavaScript for AJAX search
document.getElementById('searchForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const query = document.getElementById('searchInput').value.trim();
    if (query) {
        fetch(`search_example.php?ajax=search&query=${encodeURIComponent(query)}`)
            .then(response => response.json())
            .then(data => {
                const resultsDiv = document.getElementById('searchResults');
                resultsDiv.innerHTML = '';
                
                // Display professors
                if (data.professors.length > 0) {
                    const profSection = document.createElement('div');
                    profSection.innerHTML = `<h3>Professors (${data.professors.length})</h3>`;
                    
                    const profList = document.createElement('ul');
                    data.professors.forEach(prof => {
                        const item = document.createElement('li');
                        item.innerHTML = `<strong>${prof.name}</strong> - ${prof.department}`;
                        profList.appendChild(item);
                    });
                    
                    profSection.appendChild(profList);
                    resultsDiv.appendChild(profSection);
                }
                
                // Display courses
                if (data.courses.length > 0) {
                    const courseSection = document.createElement('div');
                    courseSection.innerHTML = `<h3>Courses (${data.courses.length})</h3>`;
                    
                    const courseList = document.createElement('ul');
                    data.courses.forEach(course => {
                        const item = document.createElement('li');
                        item.innerHTML = `<strong>${course.name}</strong> (${course.course_code}) - Taught by ${course.professor_name}`;
                        courseList.appendChild(item);
                    });
                    
                    courseSection.appendChild(courseList);
                    resultsDiv.appendChild(courseSection);
                }
                
                // No results message
                if (data.professors.length === 0 && data.courses.length === 0) {
                    resultsDiv.innerHTML = '<p>No results found for "' + query + '"</p>';
                }
            })
            .catch(error => {
                console.error('Search error:', error);
            });
    }
});
</script>