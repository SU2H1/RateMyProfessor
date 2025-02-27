<?php
/**
 * Update Homepage Lists
 * 
 * This script retrieves top professors and courses from the database
 * and updates the HTML in home.php to display this real data.
 */

// Include database configuration
require_once 'config.php';

// Create database connection
$db = new SQLite3('database/ratemyteacher.db');

// Get top 5 professors by average rating
$topProfessors = [];
$query = "
    SELECT 
        p.id, 
        p.name, 
        p.department, 
        AVG(r.rating) as avg_rating,
        COUNT(r.id) as rating_count
    FROM professors p
    JOIN ratings r ON p.id = r.professor_id
    GROUP BY p.id
    HAVING COUNT(r.id) >= 3
    ORDER BY avg_rating DESC, rating_count DESC
    LIMIT 5
";
$result = $db->query($query);

while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
    $topProfessors[] = [
        'id' => $row['id'],
        'name' => $row['name'],
        'department' => $row['department'],
        'avg_rating' => number_format((float)$row['avg_rating'], 1)
    ];
}

// If we don't have 5 professors with ratings yet, get some without ratings
if (count($topProfessors) < 5) {
    $limit = 5 - count($topProfessors);
    $existingIds = array_map(function($prof) { return $prof['id']; }, $topProfessors);
    $existingIdsStr = implode(',', $existingIds ?: [0]);
    
    $query = "
        SELECT id, name, department
        FROM professors
        WHERE id NOT IN ($existingIdsStr)
        ORDER BY id
        LIMIT $limit
    ";
    $result = $db->query($query);
    
    while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
        $topProfessors[] = [
            'id' => $row['id'],
            'name' => $row['name'],
            'department' => $row['department'],
            'avg_rating' => null
        ];
    }
}

// Get top 5 courses by average rating
$topCourses = [];
$query = "
    SELECT 
        c.id, 
        c.name, 
        c.course_code,
        p.id as professor_id,
        p.name as professor_name, 
        AVG(r.rating) as avg_rating,
        COUNT(r.id) as rating_count
    FROM courses c
    JOIN professors p ON c.professor_id = p.id
    JOIN ratings r ON c.id = r.course_id
    GROUP BY c.id
    HAVING COUNT(r.id) >= 3
    ORDER BY avg_rating DESC, rating_count DESC
    LIMIT 5
";
$result = $db->query($query);

while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
    $topCourses[] = [
        'id' => $row['id'],
        'name' => $row['name'],
        'course_code' => $row['course_code'],
        'professor_id' => $row['professor_id'],
        'professor_name' => $row['professor_name'],
        'avg_rating' => number_format((float)$row['avg_rating'], 1)
    ];
}

// If we don't have 5 courses with ratings yet, get some without ratings
if (count($topCourses) < 5) {
    $limit = 5 - count($topCourses);
    $existingIds = array_map(function($course) { return $course['id']; }, $topCourses);
    $existingIdsStr = implode(',', $existingIds ?: [0]);
    
    $query = "
        SELECT 
            c.id, 
            c.name, 
            c.course_code,
            p.id as professor_id,
            p.name as professor_name
        FROM courses c
        JOIN professors p ON c.professor_id = p.id
        WHERE c.id NOT IN ($existingIdsStr)
        ORDER BY c.id
        LIMIT $limit
    ";
    $result = $db->query($query);
    
    while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
        $topCourses[] = [
            'id' => $row['id'],
            'name' => $row['name'],
            'course_code' => $row['course_code'],
            'professor_id' => $row['professor_id'],
            'professor_name' => $row['professor_name'],
            'avg_rating' => null
        ];
    }
}

// Generate HTML for professors
$professorsHtml = '';
foreach ($topProfessors as $professor) {
    $rating = $professor['avg_rating'] ? $professor['avg_rating'] : 'No ratings';
    $stars = '';
    
    if ($professor['avg_rating']) {
        $fullStars = floor($professor['avg_rating']);
        $hasHalfStar = $professor['avg_rating'] - $fullStars >= 0.5;
        
        $stars = str_repeat('★', $fullStars);
        $stars .= $hasHalfStar ? '½' : '';
        $stars .= str_repeat('☆', 5 - $fullStars - ($hasHalfStar ? 1 : 0));
    } else {
        $stars = '☆☆☆☆☆';
    }
    
    $professorsHtml .= <<<HTML
    <div class="professor-item" data-id="<?php echo \$isLoggedIn ? '{$professor['id']}' : 'login-required'; ?>">
        <div>
            <h3>{$professor['name']}</h3>
            <p>{$professor['department']}</p>
        </div>
        <div class="rating">
            {$stars} <span>{$rating}</span>
        </div>
    </div>
HTML;
}

// Generate HTML for courses
$coursesHtml = '';
foreach ($topCourses as $course) {
    $rating = $course['avg_rating'] ? $course['avg_rating'] : 'No ratings';
    $stars = '';
    
    if ($course['avg_rating']) {
        $fullStars = floor($course['avg_rating']);
        $hasHalfStar = $course['avg_rating'] - $fullStars >= 0.5;
        
        $stars = str_repeat('★', $fullStars);
        $stars .= $hasHalfStar ? '½' : '';
        $stars .= str_repeat('☆', 5 - $fullStars - ($hasHalfStar ? 1 : 0));
    } else {
        $stars = '☆☆☆☆☆';
    }
    
    $courseCode = $course['course_code'] ? " ({$course['course_code']})" : '';
    
    $coursesHtml .= <<<HTML
    <div class="course-item" data-id="<?php echo \$isLoggedIn ? '{$course['id']}' : 'login-required'; ?>">
        <div>
            <h3>{$course['name']}{$courseCode}</h3>
            <p>{$course['professor_name']}</p>
        </div>
        <div class="rating">
            {$stars} <span>{$rating}</span>
        </div>
    </div>
HTML;
}

// Read home.php
$homeFile = __DIR__ . '/home.php';
$homeContent = file_get_contents($homeFile);

// Update professor list
$pattern = '/<div class="professor-list">(.*?)<\/div>\s+<\/div>/s';
$replacement = '<div class="professor-list">' . $professorsHtml . '</div></div>';
$homeContent = preg_replace($pattern, $replacement, $homeContent);

// Update course list
$pattern = '/<div class="course-list">(.*?)<\/div>\s+<\/div>/s';
$replacement = '<div class="course-list">' . $coursesHtml . '</div></div>';
$homeContent = preg_replace($pattern, $replacement, $homeContent);

// Write updated content back to home.php
file_put_contents($homeFile, $homeContent);

echo "Home page lists updated successfully with real data from the database.\n";
echo "Top professors: " . count($topProfessors) . "\n";
echo "Top courses: " . count($topCourses) . "\n";