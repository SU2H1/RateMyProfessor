<?php
/**
 * Update Homepage Lists
 * 
 * This script retrieves top professors and courses from the database
 * and updates the HTML in home.php to display this real data.
 */

// Include database configuration and rating calculation functions
require_once 'config.php';
require_once 'calculate_ratings.php';

// Create database connection
$db = new SQLite3('database/ratemyteacher.db');

// Ensure the professors table has the needed rating columns
echo "Checking and updating database schema...\n";
$columnsResult = $db->query("PRAGMA table_info(professors)");
$hasRatingColumns = false;
while ($col = $columnsResult->fetchArray(SQLITE3_ASSOC)) {
    if ($col['name'] === 'avg_content_quality') {
        $hasRatingColumns = true;
        break;
    }
}

// Add rating columns if they don't exist
if (!$hasRatingColumns) {
    echo "Adding rating columns to professors table...\n";
    try {
        $db->exec("ALTER TABLE professors ADD COLUMN avg_content_quality REAL DEFAULT 0");
        $db->exec("ALTER TABLE professors ADD COLUMN avg_difficulty REAL DEFAULT 0");
        $db->exec("ALTER TABLE professors ADD COLUMN overall_rating REAL DEFAULT 0");
        $db->exec("ALTER TABLE professors ADD COLUMN review_count INTEGER DEFAULT 0");
        echo "Rating columns added successfully!\n";
    } catch (Exception $e) {
        echo "Error adding rating columns: " . $e->getMessage() . "\n";
    }
}

// Ensure the courses table has the needed rating columns
$columnsResult = $db->query("PRAGMA table_info(courses)");
$hasCourseRatingColumns = false;
while ($col = $columnsResult->fetchArray(SQLITE3_ASSOC)) {
    if ($col['name'] === 'avg_content_quality') {
        $hasCourseRatingColumns = true;
        break;
    }
}

// Add rating columns if they don't exist
if (!$hasCourseRatingColumns) {
    echo "Adding rating columns to courses table...\n";
    try {
        $db->exec("ALTER TABLE courses ADD COLUMN avg_content_quality REAL DEFAULT 0");
        $db->exec("ALTER TABLE courses ADD COLUMN avg_difficulty REAL DEFAULT 0");
        $db->exec("ALTER TABLE courses ADD COLUMN review_count INTEGER DEFAULT 0");
        echo "Course rating columns added successfully!\n";
    } catch (Exception $e) {
        echo "Error adding course rating columns: " . $e->getMessage() . "\n";
    }
}

// Update ALL professor ratings
echo "Updating all professor ratings...\n";
$stmt = $db->prepare("SELECT id FROM professors");
$result = $stmt->execute();
$updatedCount = 0;
$errorCount = 0;
while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
    $professorId = $row['id'];
    if (updateStoredProfessorRatings($professorId, $db)) {
        $updatedCount++;
    } else {
        $errorCount++;
    }
}
echo "Professor ratings update complete: $updatedCount updated, $errorCount failed\n";

// Update ALL course ratings
echo "Updating all course ratings...\n";
$stmt = $db->prepare("SELECT id FROM courses");
$result = $stmt->execute();
$updatedCount = 0;
$errorCount = 0;
while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
    $courseId = $row['id'];
    if (updateStoredCourseRatings($courseId, $db)) {
        $updatedCount++;
    } else {
        $errorCount++;
    }
}
echo "Course ratings update complete: $updatedCount updated, $errorCount failed\n";

// Get top 5 professors by using the updated ratings from the professors table
$topProfessors = [];
$query = "
    SELECT 
        p.id, 
        p.name, 
        p.department, 
        p.overall_rating as avg_rating,
        p.review_count as rating_count
    FROM professors p
    WHERE p.review_count > 0
    ORDER BY p.overall_rating DESC, p.review_count DESC
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

// Get top 5 courses by using the updated ratings from the courses table
$topCourses = [];
$query = "
    SELECT 
        c.id, 
        c.name, 
        c.course_code,
        r.professor_id,
        p.name as professor_name, 
        c.avg_content_quality as avg_rating,
        c.review_count as rating_count
    FROM courses c
    JOIN ratings r ON c.id = r.course_id
    JOIN professors p ON r.professor_id = p.id
    WHERE c.review_count > 0
    GROUP BY c.id
    ORDER BY c.avg_content_quality DESC, c.review_count DESC
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

// Update professor list - match the entire PHP structure including foreach and endforeach
$pattern = '/<div class="professor-list">.*?(?:<\?php\s+endforeach;\s+\?>)?<\/div>\s+<\/div>/s';
$replacement = '<div class="professor-list">' . $professorsHtml . '</div></div>';
$homeContent = preg_replace($pattern, $replacement, $homeContent);

// Update course list - match the entire PHP structure including foreach and endforeach
$pattern = '/<div class="course-list">.*?(?:<\?php\s+endforeach;\s+\?>)?<\/div>\s+<\/div>/s';
$replacement = '<div class="course-list">' . $coursesHtml . '</div></div>';
$homeContent = preg_replace($pattern, $replacement, $homeContent);

// Write updated content back to home.php
file_put_contents($homeFile, $homeContent);

echo "Home page lists updated successfully with real data from the database.\n";
echo "Top professors: " . count($topProfessors) . "\n";
echo "Top courses: " . count($topCourses) . "\n";
echo "All professor and course ratings have been updated using the original formula: (content_quality + (5 - difficulty)) / 2\n";