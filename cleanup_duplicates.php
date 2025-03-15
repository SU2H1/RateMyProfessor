<?php
/**
 * Cleanup Duplicates Script
 * 
 * This script identifies and merges duplicate courses and professors in the database.
 * Run this script from the command line: php cleanup_duplicates.php
 */

// Path to the database
$dbPath = 'database/ratemyteacher.db';

// Check if database file exists
if (!file_exists($dbPath)) {
    echo "Error: Database file not found at: $dbPath\n";
    exit(1);
}

// Connect to the database
try {
    $db = new SQLite3($dbPath);
    echo "Connected to database successfully.\n";
} catch (Exception $e) {
    echo "Error connecting to database: " . $e->getMessage() . "\n";
    exit(1);
}

// Start a transaction
$db->exec('BEGIN TRANSACTION');

try {
    // ===== CLEAN UP DUPLICATE COURSES =====
    echo "\n===== CLEANING UP DUPLICATE COURSES =====\n";
    
    // Find duplicate courses
    $duplicates = $db->query("
        SELECT name, COUNT(*) as count, GROUP_CONCAT(id) as ids
        FROM courses 
        GROUP BY name 
        HAVING count > 1
        ORDER BY count DESC
    ");
    
    $mergedCourses = 0;
    
    while ($row = $duplicates->fetchArray(SQLITE3_ASSOC)) {
        $name = $row['name'];
        $count = $row['count'];
        $ids = explode(',', $row['ids']);
        
        echo "Found $count duplicates for course: $name\n";
        echo "IDs: " . implode(', ', $ids) . "\n";
        
        // Keep the lowest ID (assumed to be the oldest)
        $keepId = min($ids);
        $deleteIds = array_diff($ids, [$keepId]);
        
        echo "Keeping ID: $keepId, Merging/Deleting IDs: " . implode(', ', $deleteIds) . "\n";
        
        // Update ratings to use the kept ID
        foreach ($deleteIds as $deleteId) {
            $stmt = $db->prepare("UPDATE ratings SET course_id = :keepId WHERE course_id = :deleteId");
            $stmt->bindValue(':keepId', $keepId, SQLITE3_INTEGER);
            $stmt->bindValue(':deleteId', $deleteId, SQLITE3_INTEGER);
            $result = $stmt->execute();
            
            // Count affected rows
            $changes = $db->changes();
            echo "Updated $changes ratings from course ID $deleteId to $keepId\n";
            
            // Delete the duplicate course
            $stmt = $db->prepare("DELETE FROM courses WHERE id = :deleteId");
            $stmt->bindValue(':deleteId', $deleteId, SQLITE3_INTEGER);
            $result = $stmt->execute();
            
            if ($result) {
                echo "Deleted course ID: $deleteId\n";
                $mergedCourses++;
            } else {
                echo "Failed to delete course ID: $deleteId\n";
            }
        }
        echo "\n";
    }
    
    echo "Merged $mergedCourses duplicate courses.\n";
    
    // ===== CLEAN UP DUPLICATE PROFESSORS =====
    echo "\n===== CLEANING UP DUPLICATE PROFESSORS =====\n";
    
    // Find duplicate professors
    $duplicates = $db->query("
        SELECT name, COUNT(*) as count, GROUP_CONCAT(id) as ids
        FROM professors 
        GROUP BY name 
        HAVING count > 1
        ORDER BY count DESC
    ");
    
    $mergedProfessors = 0;
    
    while ($row = $duplicates->fetchArray(SQLITE3_ASSOC)) {
        $name = $row['name'];
        $count = $row['count'];
        $ids = explode(',', $row['ids']);
        
        echo "Found $count duplicates for professor: $name\n";
        echo "IDs: " . implode(', ', $ids) . "\n";
        
        // Keep the lowest ID (assumed to be the oldest)
        $keepId = min($ids);
        $deleteIds = array_diff($ids, [$keepId]);
        
        echo "Keeping ID: $keepId, Merging/Deleting IDs: " . implode(', ', $deleteIds) . "\n";
        
        // Update ratings to use the kept ID
        foreach ($deleteIds as $deleteId) {
            $stmt = $db->prepare("UPDATE ratings SET professor_id = :keepId WHERE professor_id = :deleteId");
            $stmt->bindValue(':keepId', $keepId, SQLITE3_INTEGER);
            $stmt->bindValue(':deleteId', $deleteId, SQLITE3_INTEGER);
            $result = $stmt->execute();
            
            // Count affected rows
            $changes = $db->changes();
            echo "Updated $changes ratings from professor ID $deleteId to $keepId\n";
            
            // Check if there's a professor_id column in courses table
            $hasProfessorId = false;
            $columnsResult = $db->query("PRAGMA table_info(courses)");
            while ($col = $columnsResult->fetchArray(SQLITE3_ASSOC)) {
                if ($col['name'] === 'professor_id') {
                    $hasProfessorId = true;
                    break;
                }
            }
            
            // Update courses if they reference professors
            if ($hasProfessorId) {
                $stmt = $db->prepare("UPDATE courses SET professor_id = :keepId WHERE professor_id = :deleteId");
                $stmt->bindValue(':keepId', $keepId, SQLITE3_INTEGER);
                $stmt->bindValue(':deleteId', $deleteId, SQLITE3_INTEGER);
                $result = $stmt->execute();
                
                $changes = $db->changes();
                echo "Updated $changes courses from professor ID $deleteId to $keepId\n";
            }
            
            // Delete the duplicate professor
            $stmt = $db->prepare("DELETE FROM professors WHERE id = :deleteId");
            $stmt->bindValue(':deleteId', $deleteId, SQLITE3_INTEGER);
            $result = $stmt->execute();
            
            if ($result) {
                echo "Deleted professor ID: $deleteId\n";
                $mergedProfessors++;
            } else {
                echo "Failed to delete professor ID: $deleteId\n";
            }
        }
        echo "\n";
    }
    
    echo "Merged $mergedProfessors duplicate professors.\n";
    
    // Commit the transaction
    $db->exec('COMMIT');
    echo "\nCleanup completed successfully!\n";
    
} catch (Exception $e) {
    // Roll back the transaction in case of error
    $db->exec('ROLLBACK');
    echo "Error during cleanup: " . $e->getMessage() . "\n";
    exit(1);
}

// ===== RECALCULATE CACHED RATINGS =====
echo "\n===== RECALCULATING CACHED RATINGS =====\n";

try {
    // Check if the tables have rating columns
    $hasProfRatingColumns = false;
    $columnsResult = $db->query("PRAGMA table_info(professors)");
    while ($col = $columnsResult->fetchArray(SQLITE3_ASSOC)) {
        if ($col['name'] === 'avg_content_quality') {
            $hasProfRatingColumns = true;
            break;
        }
    }
    
    $hasCourseRatingColumns = false;
    $columnsResult = $db->query("PRAGMA table_info(courses)");
    while ($col = $columnsResult->fetchArray(SQLITE3_ASSOC)) {
        if ($col['name'] === 'avg_content_quality') {
            $hasCourseRatingColumns = true;
            break;
        }
    }
    
    // If rating columns exist, recalculate them
    if ($hasProfRatingColumns) {
        // Recalculate professor ratings
        echo "Recalculating professor ratings...\n";
        
        // Get all professors with ratings
        $professors = $db->query("
            SELECT DISTINCT p.id 
            FROM professors p
            JOIN ratings r ON p.id = r.professor_id
        ");
        
        while ($prof = $professors->fetchArray(SQLITE3_ASSOC)) {
            $profId = $prof['id'];
            
            // Get average content quality
            $stmt = $db->prepare("
                SELECT AVG(content_rating) as avg_content, 
                       AVG(difficulty_rating) as avg_difficulty,
                       COUNT(*) as review_count
                FROM ratings 
                WHERE professor_id = :prof_id
            ");
            $stmt->bindValue(':prof_id', $profId, SQLITE3_INTEGER);
            $result = $stmt->execute();
            $data = $result->fetchArray(SQLITE3_ASSOC);
            
            if ($data && $data['review_count'] > 0) {
                $avgContent = $data['avg_content'];
                $avgDifficulty = $data['avg_difficulty'];
                $reviewCount = $data['review_count'];
                
                // Calculate overall rating: (content_quality + (5 - difficulty)) / 2
                $overallRating = ($avgContent + (5 - $avgDifficulty)) / 2;
                
                // Update the professor
                $updateStmt = $db->prepare("
                    UPDATE professors 
                    SET avg_content_quality = :content,
                        avg_difficulty = :difficulty,
                        overall_rating = :overall,
                        review_count = :count
                    WHERE id = :prof_id
                ");
                $updateStmt->bindValue(':content', $avgContent, SQLITE3_FLOAT);
                $updateStmt->bindValue(':difficulty', $avgDifficulty, SQLITE3_FLOAT);
                $updateStmt->bindValue(':overall', $overallRating, SQLITE3_FLOAT);
                $updateStmt->bindValue(':count', $reviewCount, SQLITE3_INTEGER);
                $updateStmt->bindValue(':prof_id', $profId, SQLITE3_INTEGER);
                $updateStmt->execute();
                
                echo "Updated ratings for professor ID $profId: Content=$avgContent, Difficulty=$avgDifficulty, Overall=$overallRating, Reviews=$reviewCount\n";
            }
        }
    }
    
    if ($hasCourseRatingColumns) {
        // Recalculate course ratings
        echo "\nRecalculating course ratings...\n";
        
        // Get all courses with ratings
        $courses = $db->query("
            SELECT DISTINCT c.id 
            FROM courses c
            JOIN ratings r ON c.id = r.course_id
        ");
        
        while ($course = $courses->fetchArray(SQLITE3_ASSOC)) {
            $courseId = $course['id'];
            
            // Get average content quality
            $stmt = $db->prepare("
                SELECT AVG(content_rating) as avg_content, 
                       AVG(difficulty_rating) as avg_difficulty,
                       COUNT(*) as review_count
                FROM ratings 
                WHERE course_id = :course_id
            ");
            $stmt->bindValue(':course_id', $courseId, SQLITE3_INTEGER);
            $result = $stmt->execute();
            $data = $result->fetchArray(SQLITE3_ASSOC);
            
            if ($data && $data['review_count'] > 0) {
                $avgContent = $data['avg_content'];
                $avgDifficulty = $data['avg_difficulty'];
                $reviewCount = $data['review_count'];
                
                // Calculate overall rating: (content_quality + (5 - difficulty)) / 2
                $overallRating = ($avgContent + (5 - $avgDifficulty)) / 2;
                
                // Update the course
                $updateStmt = $db->prepare("
                    UPDATE courses 
                    SET avg_content_quality = :content,
                        avg_difficulty = :difficulty,
                        overall_rating = :overall,
                        review_count = :count
                    WHERE id = :course_id
                ");
                $updateStmt->bindValue(':content', $avgContent, SQLITE3_FLOAT);
                $updateStmt->bindValue(':difficulty', $avgDifficulty, SQLITE3_FLOAT);
                $updateStmt->bindValue(':overall', $overallRating, SQLITE3_FLOAT);
                $updateStmt->bindValue(':count', $reviewCount, SQLITE3_INTEGER);
                $updateStmt->bindValue(':course_id', $courseId, SQLITE3_INTEGER);
                $updateStmt->execute();
                
                echo "Updated ratings for course ID $courseId: Content=$avgContent, Difficulty=$avgDifficulty, Overall=$overallRating, Reviews=$reviewCount\n";
            }
        }
    }
    
    echo "\nRating recalculation completed successfully!\n";
    
} catch (Exception $e) {
    echo "Error during rating recalculation: " . $e->getMessage() . "\n";
}

// Close the database connection
$db->close();
echo "\nAll operations completed. Database connection closed.\n";
?>