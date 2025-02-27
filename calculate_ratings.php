<?php
/**
 * calculate_ratings.php
 * 
 * Helper functions to calculate average ratings for professors and courses
 */

/**
 * Calculate a professor's average ratings across all courses they teach
 * 
 * @param int $professor_id The ID of the professor
 * @param SQLite3 $db Database connection
 * @return array An array with average ratings for content_quality, difficulty, and overall
 */
function calculateProfessorRatings($professor_id, $db) {
    // Initialize default values
    $result = [
        'content_quality' => 0,
        'difficulty' => 0,
        'overall' => 0,
        'review_count' => 0,
        'course_count' => 0
    ];
    
    try {
        // Step 1: Get all courses taught by this professor
        $query = "SELECT DISTINCT c.id FROM courses c 
                 WHERE c.professor_id = :professor_id";
        
        // Check if table has professor_id column
        $columns = [];
        $columnsResult = $db->query("PRAGMA table_info(courses)");
        $hasProfessorId = false;
        
        while ($col = $columnsResult->fetchArray(SQLITE3_ASSOC)) {
            if ($col['name'] === 'professor_id') {
                $hasProfessorId = true;
                break;
            }
        }
        
        // If courses don't have professor_id, we need to get courses from the ratings
        if (!$hasProfessorId) {
            $query = "SELECT DISTINCT r.course_id as id FROM ratings r 
                     WHERE r.professor_id = :professor_id";
        }
        
        $stmt = $db->prepare($query);
        $stmt->bindValue(':professor_id', $professor_id, SQLITE3_INTEGER);
        $coursesResult = $stmt->execute();
        
        $coursesIds = [];
        while ($row = $coursesResult->fetchArray(SQLITE3_ASSOC)) {
            if (isset($row['id']) && $row['id']) {
                $coursesIds[] = $row['id'];
            }
        }
        
        $result['course_count'] = count($coursesIds);
        
        // If no courses found, return default values
        if (empty($coursesIds)) {
            return $result;
        }
        
        // Step 2: Get ratings for each course and calculate the professor's average
        $totalContentQuality = 0;
        $totalDifficulty = 0;
        $totalRatingCount = 0;
        
        // Determine which columns exist in the ratings table
        $ratingsColumns = [];
        $columnsResult = $db->query("PRAGMA table_info(ratings)");
        
        $hasContentQuality = false;
        $hasContentRating = false;
        $hasDifficulty = false;
        $hasDifficultyRating = false;
        
        while ($col = $columnsResult->fetchArray(SQLITE3_ASSOC)) {
            if ($col['name'] === 'content_quality') {
                $hasContentQuality = true;
            } else if ($col['name'] === 'content_rating') {
                $hasContentRating = true;
            } else if ($col['name'] === 'difficulty') {
                $hasDifficulty = true;
            } else if ($col['name'] === 'difficulty_rating') {
                $hasDifficultyRating = true;
            }
        }
        
        // Determine field names for the query
        $contentField = $hasContentQuality ? 'content_quality' : ($hasContentRating ? 'content_rating' : 'rating');
        $difficultyField = $hasDifficulty ? 'difficulty' : ($hasDifficultyRating ? 'difficulty_rating' : 'rating');
        
        foreach ($coursesIds as $courseId) {
            $stmt = $db->prepare("
                SELECT 
                    AVG(r.$contentField) as avg_content_quality,
                    AVG(r.$difficultyField) as avg_difficulty,
                    COUNT(r.id) as rating_count
                FROM ratings r
                WHERE r.course_id = :course_id
            ");
            $stmt->bindValue(':course_id', $courseId, SQLITE3_INTEGER);
            $ratingResult = $stmt->execute();
            $ratings = $ratingResult->fetchArray(SQLITE3_ASSOC);
            
            if ($ratings && $ratings['rating_count'] > 0) {
                $totalContentQuality += $ratings['avg_content_quality'] * $ratings['rating_count'];
                $totalDifficulty += $ratings['avg_difficulty'] * $ratings['rating_count'];
                $totalRatingCount += $ratings['rating_count'];
            }
        }
        
        // Calculate weighted averages
        if ($totalRatingCount > 0) {
            $result['content_quality'] = round($totalContentQuality / $totalRatingCount, 1);
            $result['difficulty'] = round($totalDifficulty / $totalRatingCount, 1);
            $result['overall'] = round(($result['content_quality'] + $result['difficulty']) / 2, 1);
            $result['review_count'] = $totalRatingCount;
        }
        
    } catch (Exception $e) {
        // Log the error but continue with default values
        error_log("Error calculating professor ratings: " . $e->getMessage());
    }
    
    return $result;
}

/**
 * Calculate a course's average ratings
 * 
 * @param int $course_id The ID of the course
 * @param SQLite3 $db Database connection
 * @return array An array with average ratings for content_quality, difficulty, and overall
 */
function calculateCourseRatings($course_id, $db) {
    // Initialize default values
    $result = [
        'content_quality' => 0,
        'difficulty' => 0,
        'overall' => 0,
        'review_count' => 0
    ];
    
    try {
        // Determine which columns exist in the ratings table
        $ratingsColumns = [];
        $columnsResult = $db->query("PRAGMA table_info(ratings)");
        
        $hasContentQuality = false;
        $hasContentRating = false;
        $hasDifficulty = false;
        $hasDifficultyRating = false;
        
        while ($col = $columnsResult->fetchArray(SQLITE3_ASSOC)) {
            if ($col['name'] === 'content_quality') {
                $hasContentQuality = true;
            } else if ($col['name'] === 'content_rating') {
                $hasContentRating = true;
            } else if ($col['name'] === 'difficulty') {
                $hasDifficulty = true;
            } else if ($col['name'] === 'difficulty_rating') {
                $hasDifficultyRating = true;
            }
        }
        
        // Determine field names for the query
        $contentField = $hasContentQuality ? 'content_quality' : ($hasContentRating ? 'content_rating' : 'rating');
        $difficultyField = $hasDifficulty ? 'difficulty' : ($hasDifficultyRating ? 'difficulty_rating' : 'rating');
        
        $stmt = $db->prepare("
            SELECT 
                AVG(r.$contentField) as avg_content_quality,
                AVG(r.$difficultyField) as avg_difficulty,
                COUNT(r.id) as rating_count
            FROM ratings r
            WHERE r.course_id = :course_id
        ");
        $stmt->bindValue(':course_id', $course_id, SQLITE3_INTEGER);
        $ratingResult = $stmt->execute();
        $ratings = $ratingResult->fetchArray(SQLITE3_ASSOC);
        
        if ($ratings && $ratings['rating_count'] > 0) {
            $result['content_quality'] = round($ratings['avg_content_quality'], 1);
            $result['difficulty'] = round($ratings['avg_difficulty'], 1);
            $result['overall'] = round(($result['content_quality'] + $result['difficulty']) / 2, 1);
            $result['review_count'] = $ratings['rating_count'];
        }
        
    } catch (Exception $e) {
        // Log the error but continue with default values
        error_log("Error calculating course ratings: " . $e->getMessage());
    }
    
    return $result;
}

/**
 * Update stored average ratings for a course
 * 
 * @param int $course_id The ID of the course
 * @param SQLite3 $db Database connection
 * @return bool Success or failure
 */
function updateStoredCourseRatings($course_id, $db) {
    try {
        // Get the ratings
        $ratings = calculateCourseRatings($course_id, $db);
        
        // Check if the courses table has rating columns
        $columns = [];
        $columnsResult = $db->query("PRAGMA table_info(courses)");
        $hasRatingColumns = false;
        
        while ($col = $columnsResult->fetchArray(SQLITE3_ASSOC)) {
            if ($col['name'] === 'avg_content_quality') {
                $hasRatingColumns = true;
                break;
            }
        }
        
        // Only update if the table has the right columns
        if ($hasRatingColumns) {
            $stmt = $db->prepare("
                UPDATE courses 
                SET 
                    avg_content_quality = :content_quality,
                    avg_difficulty = :difficulty,
                    review_count = :review_count
                WHERE id = :course_id
            ");
            $stmt->bindValue(':content_quality', $ratings['content_quality'], SQLITE3_FLOAT);
            $stmt->bindValue(':difficulty', $ratings['difficulty'], SQLITE3_FLOAT);
            $stmt->bindValue(':review_count', $ratings['review_count'], SQLITE3_INTEGER);
            $stmt->bindValue(':course_id', $course_id, SQLITE3_INTEGER);
            return $stmt->execute() !== false;
        }
    } catch (Exception $e) {
        error_log("Error updating stored course ratings: " . $e->getMessage());
    }
    
    return false;
}

/**
 * Update stored average ratings for a professor
 * 
 * @param int $professor_id The ID of the professor
 * @param SQLite3 $db Database connection
 * @return bool Success or failure
 */
function updateStoredProfessorRatings($professor_id, $db) {
    try {
        // Get the ratings
        $ratings = calculateProfessorRatings($professor_id, $db);
        
        // Check if the professors table has rating columns
        $columns = [];
        $columnsResult = $db->query("PRAGMA table_info(professors)");
        $hasRatingColumns = false;
        
        while ($col = $columnsResult->fetchArray(SQLITE3_ASSOC)) {
            if ($col['name'] === 'avg_content_quality') {
                $hasRatingColumns = true;
                break;
            }
        }
        
        // Only update if the table has the right columns
        if ($hasRatingColumns) {
            $stmt = $db->prepare("
                UPDATE professors 
                SET 
                    avg_content_quality = :content_quality,
                    avg_difficulty = :difficulty,
                    overall_rating = :overall,
                    review_count = :review_count
                WHERE id = :professor_id
            ");
            $stmt->bindValue(':content_quality', $ratings['content_quality'], SQLITE3_FLOAT);
            $stmt->bindValue(':difficulty', $ratings['difficulty'], SQLITE3_FLOAT);
            $stmt->bindValue(':overall', $ratings['overall'], SQLITE3_FLOAT);
            $stmt->bindValue(':review_count', $ratings['review_count'], SQLITE3_INTEGER);
            $stmt->bindValue(':professor_id', $professor_id, SQLITE3_INTEGER);
            return $stmt->execute() !== false;
        }
    } catch (Exception $e) {
        error_log("Error updating stored professor ratings: " . $e->getMessage());
    }
    
    return false;
}