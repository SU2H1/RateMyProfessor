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
        // Always get courses from the ratings table to ensure we capture all professor-course relationships
        $query = "SELECT DISTINCT r.course_id as id FROM ratings r 
                 WHERE r.professor_id = :professor_id";
        
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
        // We'll treat each course equally regardless of number of reviews
        $totalContentQuality = 0;
        $totalDifficulty = 0;
        $totalRatingCount = 0;
        $coursesWithRatings = 0;
        
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
        // Always prioritize content_rating in the ratings table as that's what submit_rating.php uses
        $contentField = $hasContentRating ? 'content_rating' : ($hasContentQuality ? 'content_quality' : 'rating');
        $difficultyField = $hasDifficultyRating ? 'difficulty_rating' : ($hasDifficulty ? 'difficulty' : 'rating');
        
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
                // Each course counts as one unit (not weighted by number of reviews)
                $totalContentQuality += $ratings['avg_content_quality'];
                $totalDifficulty += $ratings['avg_difficulty'];
                $totalRatingCount += $ratings['rating_count'];
                $coursesWithRatings++;
            }
        }
        
        // Calculate average based on number of courses (not weighted by review count)
        if ($coursesWithRatings > 0) {
            $result['content_quality'] = round($totalContentQuality / $coursesWithRatings, 1);
            $result['difficulty'] = round($totalDifficulty / $coursesWithRatings, 1);
            
            // Calculate overall rating: high content quality is good, high difficulty is bad
            // Convert difficulty to an inverted score (5 - difficulty) so lower difficulty becomes higher score
            // Then average with content quality - this ensures overall rating reflects that easier courses are better
            $invertedDifficulty = 5 - $result['difficulty'];
            // Make sure the inverted difficulty doesn't go below 0
            $invertedDifficulty = max(0, $invertedDifficulty);
            
            // Overall is the average of content quality and inverted difficulty
            // Higher content quality and lower difficulty both result in higher overall score
            $result['overall'] = round(($result['content_quality'] + $invertedDifficulty) / 2, 1);
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
        // Always prioritize content_rating in the ratings table as that's what submit_rating.php uses
        $contentField = $hasContentRating ? 'content_rating' : ($hasContentQuality ? 'content_quality' : 'rating');
        $difficultyField = $hasDifficultyRating ? 'difficulty_rating' : ($hasDifficulty ? 'difficulty' : 'rating');
        
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
            
            // Calculate overall rating: high content quality is good, high difficulty is bad
            // Convert difficulty to an inverted score (5 - difficulty) so lower difficulty becomes higher score
            // Then average with content quality - this ensures overall rating reflects that easier courses are better
            $invertedDifficulty = 5 - $result['difficulty'];
            // Make sure the inverted difficulty doesn't go below 0
            $invertedDifficulty = max(0, $invertedDifficulty);
            
            // Overall is the average of content quality and inverted difficulty
            // Higher content quality and lower difficulty both result in higher overall score
            $result['overall'] = round(($result['content_quality'] + $invertedDifficulty) / 2, 1);
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