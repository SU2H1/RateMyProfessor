<?php
/**
 * Course Deduplication Script
 * Identifies and merges duplicate courses in the database
 * 
 * IMPORTANT: Backup your database before running this script!
 * Run from command line: php deduplicate_courses.php
 * Or access via browser with admin credentials
 */

// Basic authentication for web access
if (php_sapi_name() !== 'cli') {
    // Only require authentication when accessed via web
    session_start();
    $isAuthorized = false;
    
    if (isset($_SESSION["loggedin"]) && $_SESSION["loggedin"] === true && isset($_SESSION["is_admin"]) && $_SESSION["is_admin"] === true) {
        $isAuthorized = true;
    } elseif (isset($_SERVER['PHP_AUTH_USER']) && isset($_SERVER['PHP_AUTH_PW'])) {
        // Check against config values (you should store these more securely)
        if ($_SERVER['PHP_AUTH_USER'] === 'admin' && $_SERVER['PHP_AUTH_PW'] === 'your-secure-password') {
            $isAuthorized = true;
        }
    }
    
    if (!$isAuthorized) {
        header('WWW-Authenticate: Basic realm="Deduplication Script"');
        header('HTTP/1.0 401 Unauthorized');
        echo "Authentication required to run this script.";
        exit;
    }
    
    // Set content type to text for better readability in browser
    header('Content-Type: text/plain; charset=utf-8');
}

// Function to output messages to both CLI and browser
function output($message) {
    echo $message . PHP_EOL;
    if (php_sapi_name() !== 'cli') {
        ob_flush();
        flush();
    }
}

// Include database configuration
require_once 'config.php';

// Normalize course name function (copied from search_api.php for consistency)
function normalizeCourseName($name) {
    if (empty($name)) return '';
    
    // Convert to lowercase and trim whitespace
    $name = mb_strtolower(trim($name));
    
    // Remove common suffixes and prefixes in brackets
    $name = preg_replace('/\s*\[[^\]]+\]\s*/', ' ', $name);
    
    // Remove semester indicators
    $indicators = [
        '1st half of semester', 'first half of semester', 
        '2nd half of semester', 'second half of semester',
        '【学期前半】', '【学期後半】', 
        '春学期', '秋学期', 'spring', 'fall'
    ];
    
    foreach ($indicators as $indicator) {
        $name = str_ireplace($indicator, '', $name);
    }
    
    // Remove special characters and extra spaces
    $name = preg_replace('/[^\p{L}\p{N}\s]/u', ' ', $name);
    $name = preg_replace('/\s+/', ' ', $name);
    
    return trim($name);
}

// Load the SFC courses JSON data
function loadJsonData() {
    $jsonFilePath = __DIR__ . '/sfc_courses.json';
    if (file_exists($jsonFilePath)) {
        $jsonData = file_get_contents($jsonFilePath);
        if ($jsonData !== false) {
            return json_decode($jsonData, true);
        }
    }
    return null;
}

try {
    output("Starting course deduplication process...");
    output("Connecting to database...");
    
    // Connect to the database
    $db = new SQLite3('database/ratemyteacher.db');
    
    // Disable foreign key constraints during the process
    $db->exec("PRAGMA foreign_keys = OFF");
    output("Foreign key constraints disabled for maintenance.");
    
    // Step 1: Add missing columns if they don't exist
    output("\nChecking database schema...");
    $columnsResult = $db->query("PRAGMA table_info(courses)");
    $columns = [];
    while ($column = $columnsResult->fetchArray(SQLITE3_ASSOC)) {
        $columns[] = $column['name'];
    }
    
    // Add required columns if they don't exist
    $requiredColumns = [
        'source_id' => 'TEXT',
        'canonical_name' => 'TEXT',
        'japanese_name' => 'TEXT',
        'english_name' => 'TEXT',
        'semester' => 'TEXT',
        'year' => 'TEXT',
        'is_synchronized' => 'INTEGER DEFAULT 0',
        'last_sync_date' => 'DATETIME'
    ];
    
    foreach ($requiredColumns as $column => $type) {
        if (!in_array($column, $columns)) {
            $db->exec("ALTER TABLE courses ADD COLUMN {$column} {$type}");
            output("Added missing column: {$column}");
        }
    }
    
    // Create indexes if they don't exist
    $indexesResult = $db->query("SELECT name FROM sqlite_master WHERE type='index' AND tbl_name='courses'");
    $indexes = [];
    while ($index = $indexesResult->fetchArray(SQLITE3_ASSOC)) {
        $indexes[] = $index['name'];
    }
    
    if (!in_array('idx_courses_source_id', $indexes)) {
        $db->exec("CREATE INDEX idx_courses_source_id ON courses(source_id)");
        output("Created index: idx_courses_source_id");
    }
    
    if (!in_array('idx_courses_canonical_name', $indexes)) {
        $db->exec("CREATE INDEX idx_courses_canonical_name ON courses(canonical_name)");
        output("Created index: idx_courses_canonical_name");
    }
    
    // Step 2: Load JSON data for reference
    output("\nLoading JSON course data...");
    $jsonData = loadJsonData();
    
    if (!$jsonData || !isset($jsonData['courses'])) {
        output("Warning: Could not load SFC courses JSON data. Proceeding with limited information.");
        $jsonCourses = [];
    } else {
        $jsonCourses = $jsonData['courses'];
        output("Loaded " . count($jsonCourses) . " courses from JSON data.");
    }
    
    // Step 3: Create a mapping of course names to source_ids
    output("\nBuilding course mappings...");
    $courseNameToSourceId = [];
    $sourceIdToInfo = [];
    
    foreach ($jsonCourses as $course) {
        if (isset($course['course_id'])) {
            $sourceId = $course['course_id'];
            $sourceIdToInfo[$sourceId] = $course;
            
            if (isset($course['translations']['ja']['name'])) {
                $courseNameToSourceId[$course['translations']['ja']['name']] = $sourceId;
            }
            if (isset($course['translations']['en']['name'])) {
                $courseNameToSourceId[$course['translations']['en']['name']] = $sourceId;
            }
        }
    }
    
    // Step 4: Update existing courses with source_ids and canonical names
    output("\nUpdating existing courses with source_ids and canonical names...");
    $coursesUpdated = 0;
    
    $stmt = $db->prepare("SELECT id, name, source_id, canonical_name FROM courses");
    $result = $stmt->execute();
    
    while ($course = $result->fetchArray(SQLITE3_ASSOC)) {
        $updates = [];
        $params = [':id' => $course['id']];
        
        // If source_id is empty, try to find it
        if (empty($course['source_id'])) {
            // Check if course name matches any known course
            if (isset($courseNameToSourceId[$course['name']])) {
                $updates[] = "source_id = :source_id";
                $params[':source_id'] = $courseNameToSourceId[$course['name']];
            }
        }
        
        // If canonical_name is empty, create it
        if (empty($course['canonical_name'])) {
            $updates[] = "canonical_name = :canonical_name";
            $params[':canonical_name'] = normalizeCourseName($course['name']);
        }
        
        // Update with source info if we have it
        if (!empty($params[':source_id'])) {
            $sourceId = $params[':source_id'];
            if (isset($sourceIdToInfo[$sourceId])) {
                $courseInfo = $sourceIdToInfo[$sourceId];
                
                if (isset($courseInfo['translations']['ja']['name'])) {
                    $updates[] = "japanese_name = :japanese_name";
                    $params[':japanese_name'] = $courseInfo['translations']['ja']['name'];
                }
                
                if (isset($courseInfo['translations']['en']['name'])) {
                    $updates[] = "english_name = :english_name";
                    $params[':english_name'] = $courseInfo['translations']['en']['name'];
                }
                
                if (isset($courseInfo['semester'])) {
                    $updates[] = "semester = :semester";
                    $params[':semester'] = $courseInfo['semester'];
                }
                
                if (isset($courseInfo['year'])) {
                    $updates[] = "year = :year";
                    $params[':year'] = $courseInfo['year'];
                }
                
                $updates[] = "is_synchronized = 1";
                $updates[] = "last_sync_date = datetime('now')";
            }
        }
        
        // Execute update if we have any changes
        if (!empty($updates)) {
            $updateQuery = "UPDATE courses SET " . implode(", ", $updates) . " WHERE id = :id";
            $updateStmt = $db->prepare($updateQuery);
            
            foreach ($params as $param => $value) {
                $updateStmt->bindValue($param, $value);
            }
            
            $updateStmt->execute();
            $coursesUpdated++;
        }
    }
    
    output("Updated $coursesUpdated courses with additional information.");
    
    // Step 5: Find and merge duplicate courses
    output("\nIdentifying duplicate courses...");
    
    // First, find duplicates by source_id
    output("\n1. Checking for duplicates by source_id...");
    $duplicatesBySourceId = [];
    
    $stmt = $db->prepare("
        SELECT source_id, COUNT(*) as count, GROUP_CONCAT(id) as ids
        FROM courses
        WHERE source_id IS NOT NULL AND source_id != ''
        GROUP BY source_id
        HAVING COUNT(*) > 1
    ");
    $result = $stmt->execute();
    
    while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
        $duplicatesBySourceId[$row['source_id']] = explode(',', $row['ids']);
        output("  Found " . $row['count'] . " duplicates for source_id: " . $row['source_id']);
    }
    
    // Then, find duplicates by canonical_name where source_id is null
    output("\n2. Checking for duplicates by canonical_name (where source_id is null)...");
    $duplicatesByName = [];
    
    $stmt = $db->prepare("
        SELECT canonical_name, COUNT(*) as count, GROUP_CONCAT(id) as ids
        FROM courses
        WHERE (source_id IS NULL OR source_id = '') 
          AND canonical_name IS NOT NULL 
          AND canonical_name != ''
        GROUP BY canonical_name
        HAVING COUNT(*) > 1
    ");
    $result = $stmt->execute();
    
    while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
        $duplicatesByName[$row['canonical_name']] = explode(',', $row['ids']);
        output("  Found " . $row['count'] . " duplicates for name: " . $row['canonical_name']);
    }
    
    // Step 6: Merge duplicates
    output("\nMerging duplicate courses...");
    $db->exec("BEGIN TRANSACTION");
    
    // Process source_id duplicates first
    $totalMerged = 0;
    
    foreach ($duplicatesBySourceId as $sourceId => $ids) {
        // Sort by ID (oldest first)
        sort($ids, SORT_NUMERIC);
        
        $primaryId = array_shift($ids); // Keep the oldest record
        output("Processing duplicates for source_id: $sourceId (Primary ID: $primaryId)");
        
        foreach ($ids as $duplicateId) {
            try {
                // Merge ratings from duplicate to primary
                $moveStmt = $db->prepare("
                    UPDATE ratings 
                    SET course_id = :primary_id 
                    WHERE course_id = :duplicate_id
                ");
                $moveStmt->bindValue(':primary_id', $primaryId, SQLITE3_INTEGER);
                $moveStmt->bindValue(':duplicate_id', $duplicateId, SQLITE3_INTEGER);
                $moveStmt->execute();
                
                // Get the count of moved ratings
                $countStmt = $db->prepare("
                    SELECT changes() as affected_rows
                ");
                $countResult = $countStmt->execute();
                $movedRatings = $countResult->fetchArray(SQLITE3_ASSOC)['affected_rows'];
                
                // Delete the duplicate
                $deleteStmt = $db->prepare("DELETE FROM courses WHERE id = :id");
                $deleteStmt->bindValue(':id', $duplicateId, SQLITE3_INTEGER);
                $deleteStmt->execute();
                
                output("  Merged duplicate ID $duplicateId into $primaryId (moved $movedRatings ratings)");
                $totalMerged++;
            } catch (Exception $e) {
                output("  Error merging duplicate ID $duplicateId: " . $e->getMessage());
            }
        }
    }
    
    // Then process name duplicates
    foreach ($duplicatesByName as $name => $ids) {
        // Sort by ID (oldest first)
        sort($ids, SORT_NUMERIC);
        
        $primaryId = array_shift($ids); // Keep the oldest record
        output("Processing duplicates for name: $name (Primary ID: $primaryId)");
        
        foreach ($ids as $duplicateId) {
            try {
                // Merge ratings from duplicate to primary
                $moveStmt = $db->prepare("
                    UPDATE ratings 
                    SET course_id = :primary_id 
                    WHERE course_id = :duplicate_id
                ");
                $moveStmt->bindValue(':primary_id', $primaryId, SQLITE3_INTEGER);
                $moveStmt->bindValue(':duplicate_id', $duplicateId, SQLITE3_INTEGER);
                $moveStmt->execute();
                
                // Get the count of moved ratings
                $countStmt = $db->prepare("
                    SELECT changes() as affected_rows
                ");
                $countResult = $countStmt->execute();
                $movedRatings = $countResult->fetchArray(SQLITE3_ASSOC)['affected_rows'];
                
                // Delete the duplicate
                $deleteStmt = $db->prepare("DELETE FROM courses WHERE id = :id");
                $deleteStmt->bindValue(':id', $duplicateId, SQLITE3_INTEGER);
                $deleteStmt->execute();
                
                output("  Merged duplicate ID $duplicateId into $primaryId (moved $movedRatings ratings)");
                $totalMerged++;
            } catch (Exception $e) {
                output("  Error merging duplicate ID $duplicateId: " . $e->getMessage());
            }
        }
    }
    
    $db->exec("COMMIT");
    output("\nSuccessfully merged $totalMerged duplicate courses.");
    
    // Step 7: Update ratings statistics
    output("\nUpdating course rating statistics...");
    
    // Get all courses with ratings
    $stmt = $db->prepare("
        SELECT DISTINCT c.id
        FROM courses c
        JOIN ratings r ON c.id = r.course_id
    ");
    $result = $stmt->execute();
    
    $coursesWithRatings = [];
    while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
        $coursesWithRatings[] = $row['id'];
    }
    
    output("Found " . count($coursesWithRatings) . " courses with ratings to update.");
    
    foreach ($coursesWithRatings as $courseId) {
        // Calculate average ratings
        $statsStmt = $db->prepare("
            SELECT 
                AVG(rating) as avg_rating,
                AVG(content_rating) as avg_content_quality,
                AVG(difficulty_rating) as avg_difficulty,
                COUNT(*) as review_count
            FROM ratings
            WHERE course_id = :course_id
        ");
        $statsStmt->bindValue(':course_id', $courseId, SQLITE3_INTEGER);
        $statsResult = $statsStmt->execute();
        $stats = $statsResult->fetchArray(SQLITE3_ASSOC);
        
        // Update course statistics
        $updateStmt = $db->prepare("
            UPDATE courses SET
                avg_content_quality = :content,
                avg_difficulty = :difficulty,
                overall_rating = :overall,
                review_count = :count
            WHERE id = :id
        ");
        $updateStmt->bindValue(':content', $stats['avg_content_quality'], SQLITE3_FLOAT);
        $updateStmt->bindValue(':difficulty', $stats['avg_difficulty'], SQLITE3_FLOAT);
        $updateStmt->bindValue(':overall', $stats['avg_rating'], SQLITE3_FLOAT);
        $updateStmt->bindValue(':count', $stats['review_count'], SQLITE3_INTEGER);
        $updateStmt->bindValue(':id', $courseId, SQLITE3_INTEGER);
        $updateStmt->execute();
    }
    
    // Step 8: Re-enable foreign key constraints
    $db->exec("PRAGMA foreign_keys = ON");
    output("\nForeign key constraints re-enabled.");
    
    output("\nDeduplication process completed successfully!");
    
} catch (Exception $e) {
    output("\nERROR: " . $e->getMessage());
    
    if (isset($db)) {
        $db->exec("ROLLBACK");
        $db->exec("PRAGMA foreign_keys = ON");
        output("Transaction rolled back and foreign key constraints re-enabled.");
    }
}