<?php
/**
 * Deduplication Script for Rate My Teacher
 * This script finds and merges duplicate course and professor records in the database
 */

// Include database configuration
require_once 'config.php';

// Set to true to run in verbose mode (more detailed output)
$verbose = true;

echo "Starting deduplication process...\n";

try {
    $db = new SQLite3('database/ratemyteacher.db');
    
    // Enable foreign keys
    $db->exec("PRAGMA foreign_keys = ON");
    
    // Start transaction
    $db->exec("BEGIN TRANSACTION");
    
    // 1. Deduplicate courses
    echo "Deduplicating courses...\n";
    
    // Find duplicate courses (same name)
    $duplicateCourses = $db->query("
        SELECT LOWER(TRIM(name)) as normalized_name, 
               COUNT(*) as count, 
               GROUP_CONCAT(id) as ids
        FROM courses
        GROUP BY normalized_name
        HAVING COUNT(*) > 1
    ");
    
    $courseCount = 0;
    while ($row = $duplicateCourses->fetchArray(SQLITE3_ASSOC)) {
        $courseCount++;
        echo "Found duplicates for course: {$row['normalized_name']} (Count: {$row['count']})\n";
        
        // Get list of IDs, with the first one being the one to keep
        $ids = explode(',', $row['ids']);
        $keepId = $ids[0];
        
        // Check which record to keep - prefer the one with more reviews
        $stmt = $db->prepare("
            SELECT course_id, COUNT(*) as reviews
            FROM ratings
            WHERE course_id IN (" . implode(',', $ids) . ")
            GROUP BY course_id
            ORDER BY reviews DESC
            LIMIT 1
        ");
        $result = $stmt->execute();
        $mostReviewed = $result->fetchArray(SQLITE3_ASSOC);
        
        if ($mostReviewed && $mostReviewed['course_id'] != $keepId) {
            $keepId = $mostReviewed['course_id'];
            if ($verbose) {
                echo "  Selected ID $keepId as primary because it has more reviews ({$mostReviewed['reviews']} reviews)\n";
            }
        }
        
        // Update ratings to point to the ID we're keeping
        foreach (array_slice($ids, 0) as $id) {
            if ($id == $keepId) continue; // Skip the one we're keeping
            
            // Count how many ratings will be updated
            $countStmt = $db->prepare("SELECT COUNT(*) as count FROM ratings WHERE course_id = :id");
            $countStmt->bindValue(':id', $id, SQLITE3_INTEGER);
            $countResult = $countStmt->execute();
            $ratingCount = $countResult->fetchArray(SQLITE3_ASSOC)['count'];
            
            echo "  Merging course ID $id into $keepId ($ratingCount ratings affected)\n";
            $db->exec("UPDATE ratings SET course_id = $keepId WHERE course_id = $id");
            $db->exec("DELETE FROM courses WHERE id = $id");
        }
    }
    echo "Deduplicated $courseCount courses\n";
    
    // 2. Deduplicate professors
    echo "\nDeduplicating professors...\n";
    
    // Find duplicate professors (same name, ignoring case and whitespace)
    $duplicateProfs = $db->query("
        SELECT 
            LOWER(REPLACE(TRIM(name), ' ', '')) as normalized_name, 
            COUNT(*) as count, 
            GROUP_CONCAT(id) as ids,
            GROUP_CONCAT(name) as names
        FROM professors
        GROUP BY normalized_name
        HAVING COUNT(*) > 1
    ");
    
    $profCount = 0;
    while ($row = $duplicateProfs->fetchArray(SQLITE3_ASSOC)) {
        $profCount++;
        echo "Found duplicates for professor normalized name: {$row['normalized_name']} (Count: {$row['count']})\n";
        echo "  Original names: {$row['names']}\n";
        
        // Get list of IDs, with the first one being the one to keep
        $ids = explode(',', $row['ids']);
        $keepId = $ids[0];
        
        // Check which record to keep - prefer the one with more reviews
        $stmt = $db->prepare("
            SELECT professor_id, COUNT(*) as reviews
            FROM ratings
            WHERE professor_id IN (" . implode(',', $ids) . ")
            GROUP BY professor_id
            ORDER BY reviews DESC
            LIMIT 1
        ");
        $result = $stmt->execute();
        $mostReviewed = $result->fetchArray(SQLITE3_ASSOC);
        
        if ($mostReviewed && $mostReviewed['professor_id'] != $keepId) {
            $keepId = $mostReviewed['professor_id'];
            if ($verbose) {
                echo "  Selected ID $keepId as primary because it has more reviews ({$mostReviewed['reviews']} reviews)\n";
            }
        }
        
        // Get the name of the professor we're keeping
        $nameStmt = $db->prepare("SELECT name FROM professors WHERE id = :id");
        $nameStmt->bindValue(':id', $keepId, SQLITE3_INTEGER);
        $nameResult = $nameStmt->execute();
        $keepName = $nameResult->fetchArray(SQLITE3_ASSOC)['name'];
        echo "  Keeping professor: $keepName (ID: $keepId)\n";
        
        // Update ratings to point to the ID we're keeping
        foreach (array_slice($ids, 0) as $id) {
            if ($id == $keepId) continue; // Skip the one we're keeping
            
            // Count how many ratings will be updated
            $countStmt = $db->prepare("SELECT COUNT(*) as count FROM ratings WHERE professor_id = :id");
            $countStmt->bindValue(':id', $id, SQLITE3_INTEGER);
            $countResult = $countStmt->execute();
            $ratingCount = $countResult->fetchArray(SQLITE3_ASSOC)['count'];
            
            // Get the name of the professor we're merging
            $nameStmt = $db->prepare("SELECT name FROM professors WHERE id = :id");
            $nameStmt->bindValue(':id', $id, SQLITE3_INTEGER);
            $nameResult = $nameStmt->execute();
            $mergeName = $nameResult->fetchArray(SQLITE3_ASSOC)['name'];
            
            echo "  Merging professor '$mergeName' (ID: $id) into '$keepName' ($ratingCount ratings affected)\n";
            $db->exec("UPDATE ratings SET professor_id = $keepId WHERE professor_id = $id");
            $db->exec("DELETE FROM professors WHERE id = $id");
        }
    }
    echo "Deduplicated $profCount professors\n";
    
    // 3. Update the average ratings for all affected professors and courses
    echo "\nUpdating average ratings...\n";
    
    // Load rating calculation functions
    require_once 'calculate_ratings.php';
    
    // Update all professors
    $professors = $db->query("SELECT id FROM professors");
    $updatedProfCount = 0;
    while ($prof = $professors->fetchArray(SQLITE3_ASSOC)) {
        updateStoredProfessorRatings($prof['id'], $db);
        $updatedProfCount++;
        if ($verbose && $updatedProfCount % 10 == 0) {
            echo "  Updated ratings for $updatedProfCount professors...\n";
        }
    }
    echo "Updated ratings for $updatedProfCount professors\n";
    
    // Update all courses
    $courses = $db->query("SELECT id FROM courses");
    $updatedCourseCount = 0;
    while ($course = $courses->fetchArray(SQLITE3_ASSOC)) {
        updateStoredCourseRatings($course['id'], $db);
        $updatedCourseCount++;
        if ($verbose && $updatedCourseCount % 10 == 0) {
            echo "  Updated ratings for $updatedCourseCount courses...\n";
        }
    }
    echo "Updated ratings for $updatedCourseCount courses\n";
    
    // Commit the changes
    $db->exec("COMMIT");
    echo "\nDeduplication complete!\n";
    
} catch (Exception $e) {
    if (isset($db)) {
        $db->exec("ROLLBACK");
    }
    echo "Error: " . $e->getMessage() . "\n";
    exit(1);
}

echo "\nSuggested next steps:\n";
echo "1. Run 'php -S localhost:8000' to start a local server\n";
echo "2. Visit 'http://localhost:8000/home.php' to check that everything works\n";
echo "3. Make sure the search functionality properly displays unique courses and professors\n";
?>