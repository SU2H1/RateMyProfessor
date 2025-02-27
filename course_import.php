<?php
/**
 * Course Importer for University Course Data
 * 
 * This script imports the scraped course data from JSON into the SQLite database.
 * It handles duplicates and ensures proper relationships between courses and professors.
 */

// Include database configuration
require_once 'config.php';

// Check if database connection is working
try {
    // Create a new SQLite3 database connection
    $db = new SQLite3('database/ratemyteacher.db');
    
    // Enable foreign keys
    $db->exec('PRAGMA foreign_keys = ON');
    
    echo "Database connection successful.\n";
} catch (Exception $e) {
    die("Database connection failed: " . $e->getMessage() . "\n");
}

// Check if JSON file exists
$jsonFile = __DIR__ . '/sfc_courses.json';
if (!file_exists($jsonFile)) {
    die("Scraped course data not found. Please run course_scraper.php first.\n");
}

// Load JSON data
$jsonData = file_get_contents($jsonFile);
$data = json_decode($jsonData, true);

if (json_last_error() !== JSON_ERROR_NONE) {
    die("Error decoding JSON: " . json_last_error_msg() . "\n");
}

if (!$data || !isset($data['courses'])) {
    die("Invalid JSON data format. The file doesn't contain a 'courses' key.\n");
}

echo "Successfully loaded JSON with " . count($data['courses']) . " courses.\n";

// Start a transaction for better performance and atomicity
$db->exec('BEGIN TRANSACTION');

// First, extract all professors from the courses
echo "Extracting professors from course data...\n";
$allProfessors = [];
$professorMap = []; // Maps professor names (both ja and en) to database IDs

try {
    // Extract unique professors from all courses
    foreach ($data['courses'] as $course) {
        foreach ($course['professors'] as $professor) {
            $jaName = $professor['name']['ja'];
            $enName = $professor['name']['en'];
            $jaDept = $professor['department']['ja'];
            $enDept = $professor['department']['en'];
            
            // Use both Japanese and English names as keys
            $jaKey = mb_strtolower($jaName, 'UTF-8');
            $enKey = strtolower($enName);
            
            if (!isset($allProfessors[$jaKey])) {
                $allProfessors[$jaKey] = [
                    'name_ja' => $jaName,
                    'name_en' => $enName,
                    'department_ja' => $jaDept,
                    'department_en' => $enDept
                ];
            }
            
            // Also add entry for English name for easier lookup
            if ($jaKey !== $enKey && !isset($allProfessors[$enKey])) {
                $allProfessors[$enKey] = [
                    'name_ja' => $jaName,
                    'name_en' => $enName,
                    'department_ja' => $jaDept,
                    'department_en' => $enDept
                ];
            }
        }
    }
    
    echo "Found " . count($allProfessors) . " unique professors.\n";
    
    // Import professors
    echo "Importing professors...\n";
    $processedProfessors = []; // Track which professors we've already processed
    
    foreach ($allProfessors as $key => $professor) {
        // Skip if we've already processed this professor
        if (isset($processedProfessors[$key])) {
            continue;
        }
        
        $jaName = $professor['name_ja'];
        $enName = $professor['name_en'];
        
        // Try to find professor by Japanese or English name
        $stmt = $db->prepare('SELECT id FROM professors WHERE name = :name_ja OR name = :name_en');
        $stmt->bindValue(':name_ja', $jaName, SQLITE3_TEXT);
        $stmt->bindValue(':name_en', $enName, SQLITE3_TEXT);
        $result = $stmt->execute();
        $existingProf = $result->fetchArray(SQLITE3_ASSOC);
        
        // Combine departments for bio field
        $bio = "Japanese: {$professor['department_ja']}\nEnglish: {$professor['department_en']}";
        
        if ($existingProf) {
            // Professor already exists, update information
            $professorId = $existingProf['id'];
            $updateStmt = $db->prepare('UPDATE professors SET department = :department, bio = :bio WHERE id = :id');
            $updateStmt->bindValue(':department', $professor['department_en'], SQLITE3_TEXT);
            $updateStmt->bindValue(':bio', $bio, SQLITE3_TEXT);
            $updateStmt->bindValue(':id', $professorId, SQLITE3_INTEGER);
            $updateStmt->execute();
            
            echo "  - Updated professor: {$enName} / {$jaName} (ID: $professorId)\n";
        } else {
            // Insert new professor
            $insertStmt = $db->prepare('INSERT INTO professors (name, department, bio) VALUES (:name, :department, :bio)');
            $insertStmt->bindValue(':name', $enName, SQLITE3_TEXT); // Primary name is English
            $insertStmt->bindValue(':department', $professor['department_en'], SQLITE3_TEXT);
            $insertStmt->bindValue(':bio', $bio, SQLITE3_TEXT);
            $insertStmt->execute();
            
            $professorId = $db->lastInsertRowID();
            echo "  - Added new professor: {$enName} / {$jaName} (ID: $professorId)\n";
        }
        
        // Store the mapping from both Japanese and English names to database ID
        $professorMap[mb_strtolower($jaName, 'UTF-8')] = $professorId;
        $professorMap[strtolower($enName)] = $professorId;
        
        // Mark this professor as processed
        $processedProfessors[$key] = true;
    }
    
    echo "Imported professors into database.\n";
    
    // Now import courses
    echo "Importing courses...\n";
    $courseCount = 0;
    
    foreach ($data['courses'] as $course) {
        $courseCode = $course['course_id'];
        $year = $course['year'];
        $jaName = $course['translations']['ja']['name'];
        $enName = $course['translations']['en']['name'];
        $jaField = $course['translations']['ja']['field'];
        $enField = $course['translations']['en']['field'];
        $jaCredits = $course['translations']['ja']['credits'];
        $enCredits = $course['translations']['en']['credits'];
        
        // Combine information into a description
        $description = "Japanese Name: $jaName\n" .
                      "English Name: $enName\n" .
                      "Japanese Field: $jaField\n" .
                      "English Field: $enField\n" .
                      "Japanese Credits: $jaCredits\n" .
                      "English Credits: $enCredits\n" .
                      "Year: $year";
        
        // Get the primary professor for this course (first one in the list)
        $primaryProfessor = $course['professors'][0];
        $primaryProfessorName = $primaryProfessor['name']['en']; // Use English name
        $primaryProfessorId = $professorMap[strtolower($primaryProfessorName)] ?? null;
        
        if (!$primaryProfessorId) {
            echo "  - Error: Professor not found for course '{$enName}'\n";
            continue;
        }
        
        // Check if course already exists
        $stmt = $db->prepare('
            SELECT c.id 
            FROM courses c
            WHERE c.course_code = :course_code 
            AND c.professor_id = :professor_id
        ');
        $stmt->bindValue(':course_code', $courseCode, SQLITE3_TEXT);
        $stmt->bindValue(':professor_id', $primaryProfessorId, SQLITE3_INTEGER);
        $result = $stmt->execute();
        $existingCourse = $result->fetchArray(SQLITE3_ASSOC);
        
        if ($existingCourse) {
            // Course already exists, update information
            $courseId = $existingCourse['id'];
            $updateStmt = $db->prepare('
                UPDATE courses 
                SET name = :name, description = :description
                WHERE id = :id
            ');
            $updateStmt->bindValue(':name', $enName, SQLITE3_TEXT);
            $updateStmt->bindValue(':description', $description, SQLITE3_TEXT);
            $updateStmt->bindValue(':id', $courseId, SQLITE3_INTEGER);
            $updateStmt->execute();
            
            echo "  - Updated course: {$enName} (ID: $courseId)\n";
        } else {
            // Insert new course
            $insertStmt = $db->prepare('
                INSERT INTO courses (name, course_code, description, professor_id) 
                VALUES (:name, :course_code, :description, :professor_id)
            ');
            $insertStmt->bindValue(':name', $enName, SQLITE3_TEXT);
            $insertStmt->bindValue(':course_code', $courseCode, SQLITE3_TEXT);
            $insertStmt->bindValue(':description', $description, SQLITE3_TEXT);
            $insertStmt->bindValue(':professor_id', $primaryProfessorId, SQLITE3_INTEGER);
            $insertStmt->execute();
            
            $courseId = $db->lastInsertRowID();
            echo "  - Added new course: {$enName} (ID: $courseId)\n";
            $courseCount++;
        }
        
        // TODO: If you have a course_professors junction table, you could add all professors
        // associated with this course here
    }
    
    echo "Imported $courseCount new courses.\n";
    
    // Commit the transaction
    $db->exec('COMMIT');
    echo "Import completed successfully!\n";
    
} catch (Exception $e) {
    // Rollback the transaction on error
    $db->exec('ROLLBACK');
    echo "Error during import: " . $e->getMessage() . "\n";
}

// Now, update the search functionality to use the actual database
echo "Updating search functionality to use the real database...\n";

// Create a sample script to show how to add search functionality
$searchExampleFile = __DIR__ . '/search_example.php';
file_put_contents($searchExampleFile, '<?php
/**
 * Search Example
 * 
 * This file demonstrates how to implement search functionality
 * for professors and courses from the database.
 */

// Include database connection
require_once \'config.php\';

/**
 * Search for professors and courses based on a query
 * 
 * @param string $query The search query
 * @return array Array containing matching professors and courses
 */
function search($query) {
    $db = new SQLite3(\'database/ratemyteacher.db\');
    $results = [
        \'professors\' => [],
        \'courses\' => []
    ];
    
    // Search professors
    $stmt = $db->prepare("
        SELECT id, name, department, bio 
        FROM professors 
        WHERE name LIKE :query OR department LIKE :query
        ORDER BY name
    ");
    $stmt->bindValue(\':query\', "%$query%", SQLITE3_TEXT);
    $result = $stmt->execute();
    
    while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
        $results[\'professors\'][] = $row;
    }
    
    // Search courses
    $stmt = $db->prepare("
        SELECT c.id, c.name, c.course_code, c.description, p.name as professor_name, p.id as professor_id
        FROM courses c
        JOIN professors p ON c.professor_id = p.id
        WHERE c.name LIKE :query OR c.course_code LIKE :query OR p.name LIKE :query
        ORDER BY c.name
    ");
    $stmt->bindValue(\':query\', "%$query%", SQLITE3_TEXT);
    $result = $stmt->execute();
    
    while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
        $results[\'courses\'][] = $row;
    }
    
    return $results;
}

// Example usage in AJAX handler
if (isset($_GET[\'ajax\']) && $_GET[\'ajax\'] === \'search\') {
    header(\'Content-Type: application/json\');
    $query = $_GET[\'query\'] ?? \'\';
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
document.getElementById(\'searchForm\').addEventListener(\'submit\', function(e) {
    e.preventDefault();
    
    const query = document.getElementById(\'searchInput\').value.trim();
    if (query) {
        fetch(`search_example.php?ajax=search&query=${encodeURIComponent(query)}`)
            .then(response => response.json())
            .then(data => {
                const resultsDiv = document.getElementById(\'searchResults\');
                resultsDiv.innerHTML = \'\';
                
                // Display professors
                if (data.professors.length > 0) {
                    const profSection = document.createElement(\'div\');
                    profSection.innerHTML = `<h3>Professors (${data.professors.length})</h3>`;
                    
                    const profList = document.createElement(\'ul\');
                    data.professors.forEach(prof => {
                        const item = document.createElement(\'li\');
                        item.innerHTML = `<strong>${prof.name}</strong> - ${prof.department}`;
                        profList.appendChild(item);
                    });
                    
                    profSection.appendChild(profList);
                    resultsDiv.appendChild(profSection);
                }
                
                // Display courses
                if (data.courses.length > 0) {
                    const courseSection = document.createElement(\'div\');
                    courseSection.innerHTML = `<h3>Courses (${data.courses.length})</h3>`;
                    
                    const courseList = document.createElement(\'ul\');
                    data.courses.forEach(course => {
                        const item = document.createElement(\'li\');
                        item.innerHTML = `<strong>${course.name}</strong> (${course.course_code}) - Taught by ${course.professor_name}`;
                        courseList.appendChild(item);
                    });
                    
                    courseSection.appendChild(courseList);
                    resultsDiv.appendChild(courseSection);
                }
                
                // No results message
                if (data.professors.length === 0 && data.courses.length === 0) {
                    resultsDiv.innerHTML = \'<p>No results found for "\' + query + \'"</p>\';
                }
            })
            .catch(error => {
                console.error(\'Search error:\', error);
            });
    }
});
</script>');

echo "Created search example at $searchExampleFile\n";
echo "Next steps:\n";
echo "1. Integrate the search functionality into home.php\n";
echo "2. Update your professor and course detail pages to use real data\n";
echo "3. Customize the scraper to match your university's actual website structure\n";