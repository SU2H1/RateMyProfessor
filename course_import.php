<?php
/**
 * Course Importer for University Course Data
 * 
 * This script imports the scraped course data from JSON into the SQLite database.
 * It handles duplicates and ensures proper relationships between courses and professors.
 * Now includes semester information in the database schema and import process.
 */

// Include database configuration
require_once 'config.php';
$db = new SQLite3(__DIR__ . '/database/ratemyteacher.db');
if (!is_dir('database')) {
    mkdir('database', 0755, true);
}
// Check if database connection is working
try {
    // Create a new SQLite3 database connection
    $db = new SQLite3(__DIR__ . '/database/ratemyteacher.db');
    $dbDir = __DIR__ . '/database';

// Check if directory exists and is writable
if (is_dir($dbDir)) {
    if (!is_writable($dbDir)) {
        die("Database directory exists but is not writable: $dbDir\n");
    }
} else {
    // Try to create directory
    if (!mkdir($dbDir, 0755, true)) {
        die("Failed to create database directory: $dbDir\n");
    }
    echo "Created database directory.\n";
}

$dbPath = $dbDir . '/ratemyteacher.db';

// Check if database file exists and is writable
if (file_exists($dbPath) && !is_writable($dbPath)) {
    die("Database file exists but is not writable: $dbPath\n");
}
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

// Make sure we have the semester field in the courses table
try {
    // Check if the semester field exists in the courses table
    $result = $db->query("PRAGMA table_info(courses)");
    $columnExists = false;
    
    while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
        if ($row['name'] === 'semester') {
            $columnExists = true;
            break;
        }
    }
    
    // Add the semester field if it doesn't exist
    if (!$columnExists) {
        echo "Adding 'semester' field to the courses table...\n";
        $db->exec("ALTER TABLE courses ADD COLUMN semester TEXT");
        echo "Added 'semester' field to the courses table.\n";
    } else {
        echo "The 'semester' field already exists in the courses table.\n";
    }
} catch (Exception $e) {
    echo "Error checking or adding semester field: " . $e->getMessage() . "\n";
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
        $semester = $course['semester']; // Get semester information
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
                      "Year: $year\n" .
                      "Semester: $semester";
        
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
                SET name = :name, description = :description, semester = :semester
                WHERE id = :id
            ');
            $updateStmt->bindValue(':name', $enName, SQLITE3_TEXT);
            $updateStmt->bindValue(':description', $description, SQLITE3_TEXT);
            $updateStmt->bindValue(':semester', $semester, SQLITE3_TEXT);
            $updateStmt->bindValue(':id', $courseId, SQLITE3_INTEGER);
            $updateStmt->execute();
            
            echo "  - Updated course: {$enName} (ID: $courseId)\n";
        } else {
            // Insert new course
            $insertStmt = $db->prepare('
                INSERT INTO courses (name, course_code, description, professor_id, semester) 
                VALUES (:name, :course_code, :description, :professor_id, :semester)
            ');
            $insertStmt->bindValue(':name', $enName, SQLITE3_TEXT);
            $insertStmt->bindValue(':course_code', $courseCode, SQLITE3_TEXT);
            $insertStmt->bindValue(':description', $description, SQLITE3_TEXT);
            $insertStmt->bindValue(':professor_id', $primaryProfessorId, SQLITE3_INTEGER);
            $insertStmt->bindValue(':semester', $semester, SQLITE3_TEXT);
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
// This code should be placed after the main import transaction is committed
// Create a separate transaction for adding columns
$db->exec('BEGIN TRANSACTION');

try {
    // First check if the professors table exists
    $tableResult = $db->query("SELECT name FROM sqlite_master WHERE type='table' AND name='professors'");
    if (!$tableResult->fetchArray()) {
        echo "Error: professors table does not exist. Creating it now...\n";
        // Create the professors table if it doesn't exist
        $db->exec("CREATE TABLE IF NOT EXISTS professors (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            name TEXT,
            department TEXT,
            bio TEXT
        )");
    }
    
    // Check which columns already exist
    $result = $db->query("PRAGMA table_info(professors)");
    $existingColumns = array();
    while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
        $existingColumns[] = strtolower($row['name']);
        echo "Found existing column: " . $row['name'] . "\n";
    }
    
    // Only add columns that don't exist
    if (!in_array('avg_content_quality', $existingColumns)) {
        echo "Adding avg_content_quality column...\n";
        $db->exec("ALTER TABLE professors ADD COLUMN avg_content_quality REAL DEFAULT 0");
    }
    
    if (!in_array('avg_difficulty', $existingColumns)) {
        echo "Adding avg_difficulty column...\n";
        $db->exec("ALTER TABLE professors ADD COLUMN avg_difficulty REAL DEFAULT 0");
    }
    
    if (!in_array('overall_rating', $existingColumns)) {
        echo "Adding overall_rating column...\n";
        $db->exec("ALTER TABLE professors ADD COLUMN overall_rating REAL DEFAULT 0");
    }
    
    if (!in_array('review_count', $existingColumns)) {
        echo "Adding review_count column...\n";
        $db->exec("ALTER TABLE professors ADD COLUMN review_count INTEGER DEFAULT 0");
    }
    
    $db->exec('COMMIT');
    echo "Rating columns processed successfully.\n";
    $hasRatingColumns = true;
    
} catch (Exception $e) {
    $db->exec('ROLLBACK');
    echo "Error processing rating columns: " . $e->getMessage() . "\n";
    // Print the SQLite error code for more information
    echo "SQLite error code: " . $db->lastErrorCode() . "\n";
    echo "SQLite error message: " . $db->lastErrorMsg() . "\n";
}

// Verify columns were added
try {
    $verifyResult = $db->query("PRAGMA table_info(professors)");
    echo "Current professors table structure:\n";
    while ($row = $verifyResult->fetchArray(SQLITE3_ASSOC)) {
        echo "- Column: " . $row['name'] . " (Type: " . $row['type'] . ")\n";
    }
} catch (Exception $e) {
    echo "Error verifying table structure: " . $e->getMessage() . "\n";
}

// Add rating columns to courses table
$db->exec('BEGIN TRANSACTION');

try {
    // Check which columns already exist in courses table
    $result = $db->query("PRAGMA table_info(courses)");
    $existingColumns = array();
    while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
        $existingColumns[] = strtolower($row['name']);
        echo "Found existing column in courses: " . $row['name'] . "\n";
    }
    
    // Only add columns that don't exist
    if (!in_array('avg_content_quality', $existingColumns)) {
        echo "Adding avg_content_quality column to courses...\n";
        $db->exec("ALTER TABLE courses ADD COLUMN avg_content_quality REAL DEFAULT 0");
    }
    
    if (!in_array('avg_difficulty', $existingColumns)) {
        echo "Adding avg_difficulty column to courses...\n";
        $db->exec("ALTER TABLE courses ADD COLUMN avg_difficulty REAL DEFAULT 0");
    }
    
    if (!in_array('overall_rating', $existingColumns)) {
        echo "Adding overall_rating column to courses...\n";
        $db->exec("ALTER TABLE courses ADD COLUMN overall_rating REAL DEFAULT 0");
    }
    
    if (!in_array('review_count', $existingColumns)) {
        echo "Adding review_count column to courses...\n";
        $db->exec("ALTER TABLE courses ADD COLUMN review_count INTEGER DEFAULT 0");
    }
    
    $db->exec('COMMIT');
    echo "Rating columns added to courses table successfully.\n";
    
} catch (Exception $e) {
    $db->exec('ROLLBACK');
    echo "Error adding rating columns to courses table: " . $e->getMessage() . "\n";
    echo "SQLite error code: " . $db->lastErrorCode() . "\n";
    echo "SQLite error message: " . $db->lastErrorMsg() . "\n";
}

// Verify courses columns were added
try {
    $verifyResult = $db->query("PRAGMA table_info(courses)");
    echo "Current courses table structure:\n";
    while ($row = $verifyResult->fetchArray(SQLITE3_ASSOC)) {
        echo "- Column: " . $row['name'] . " (Type: " . $row['type'] . ")\n";
    }
} catch (Exception $e) {
    echo "Error verifying courses table structure: " . $e->getMessage() . "\n";
}

    
// Now, update the search functionality to use the actual database
echo "Updating search functionality to use the real database...\n";
echo "Created search example at $searchExampleFile\n";
echo "Next steps:\n";
echo "1. Integrate the search functionality into home.php\n";
echo "2. Update your professor and course detail pages to use real data\n";
echo "3. Customize the scraper to match your university's actual website structure\n";

// // Make sure we have the semester field in the courses table
// try {
//     // Check if the semester field exists in the courses table
//     $result = $db->query("PRAGMA table_info(courses)");
//     $columnExists = false;
    
//     while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
//         if ($row['name'] === 'semester') {
//             $columnExists = true;
//             break;
//         }
//     }
    
//     // Add the semester field if it doesn't exist
//     if (!$columnExists) {
//         echo "Adding 'semester' field to the courses table...\n";
//         $db->exec("ALTER TABLE courses ADD COLUMN semester TEXT");
//         echo "Added 'semester' field to the courses table.\n";
//     } else {
//         echo "The 'semester' field already exists in the courses table.\n";
//     }
// } catch (Exception $e) {
//     echo "Error checking or adding semester field: " . $e->getMessage() . "\n";
// }