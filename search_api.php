<?php
// Include database configuration
require_once 'config.php';

// Get search query and language from GET parameters
$searchQuery = isset($_GET['q']) ? trim($_GET['q']) : '';
$language = isset($_GET['lang']) ? trim($_GET['lang']) : 'en';

// Initialize results array
$results = [
    'professors' => [],
    'courses' => []
];

// Only proceed if we have a search query
if (!empty($searchQuery)) {
    try {
        // Load JSON data for translations
        $jsonFilePath = __DIR__ . '/sfc_courses.json';
        $jsonData = null;
        
        if (file_exists($jsonFilePath)) {
            $jsonData = json_decode(file_get_contents($jsonFilePath), true);
        }
        
        // Combine database results with translations from JSON
        
        // Search for professors from the database
        $stmt = $conn->prepare("
            SELECT id, name, department, 
                   COALESCE(overall_rating, (avg_content_quality + avg_difficulty) / 2) as avg_rating,
                   COALESCE(review_count, 0) as review_count
            FROM professors
            WHERE name LIKE :search OR department LIKE :search
            ORDER BY 
                /* Prioritize exact matches to the top */
                CASE WHEN LOWER(name) = LOWER(:exact) THEN 0 ELSE 1 END,
                CASE WHEN LOWER(department) = LOWER(:exact) THEN 0 ELSE 1 END,
                avg_rating DESC, review_count DESC
            LIMIT 10
        ");
        $stmt->bindValue(':search', '%' . $searchQuery . '%', SQLITE3_TEXT);
        $stmt->bindValue(':exact', $searchQuery, SQLITE3_TEXT);
        $result = $stmt->execute();
        
        // Collect all database professors
        $dbProfessors = [];
        while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
            // Format the rating if it exists
            if (isset($row['avg_rating']) && $row['avg_rating'] !== null) {
                $row['avg_rating'] = number_format((float)$row['avg_rating'], 1);
            } else {
                $row['avg_rating'] = null;
            }
            
            // Add English as default
            $row['name_en'] = $row['name'];
            $row['department_en'] = $row['department'];
            
            // We'll try to add Japanese names later
            $dbProfessors[$row['id']] = $row;
        }
        
        // Search for courses from the database
        $stmt = $conn->prepare("
            SELECT c.id, c.name, c.course_code,
                   COALESCE(c.avg_content_quality, 0) as avg_content_quality,
                   COALESCE(c.avg_difficulty, 0) as avg_difficulty,
                   CASE 
                       WHEN COALESCE(c.review_count, 0) > 0 
                       THEN (COALESCE(c.avg_content_quality, 0) + (COALESCE(c.avg_difficulty, 0))) / 2
                       ELSE 0
                   END as avg_rating,
                   COALESCE(c.review_count, 0) as review_count,
                   p.name as professor_name
            FROM courses c
            LEFT JOIN professors p ON c.professor_id = p.id
            WHERE c.name LIKE :search OR c.course_code LIKE :search OR p.name LIKE :search
            ORDER BY 
                /* Prioritize exact matches to the top */
                CASE WHEN LOWER(c.name) = LOWER(:exact) THEN 0 ELSE 1 END,
                CASE WHEN LOWER(c.course_code) = LOWER(:exact) THEN 0 ELSE 1 END,
                avg_rating DESC, review_count DESC
            LIMIT 10
        ");
        $stmt->bindValue(':search', '%' . $searchQuery . '%', SQLITE3_TEXT);
        $stmt->bindValue(':exact', $searchQuery, SQLITE3_TEXT);
        $result = $stmt->execute();
        
        // Collect all database courses
        $dbCourses = [];
        while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
            // Format the rating if it exists
            if (isset($row['avg_rating']) && $row['avg_rating'] !== null) {
                $row['avg_rating'] = number_format((float)$row['avg_rating'], 1);
            } else {
                $row['avg_rating'] = null;
            }
            
            // Add English as default
            $row['name_en'] = $row['name'];
            $row['professor_name_en'] = $row['professor_name'];
            
            // We'll try to add Japanese names later
            $dbCourses[$row['id']] = $row;
        }
        
        // If we have JSON data, add Japanese names and additional search
        if ($jsonData && isset($jsonData['courses'])) {
            $japaneseProfessorsMap = []; // Map English names to Japanese names
            $japaneseCoursesMap = [];    // Map English course names to Japanese names
            
            // Extract translations from JSON
            foreach ($jsonData['courses'] as $course) {
                // Course translations
                if (isset($course['translations'])) {
                    $enCourseName = $course['translations']['en']['name'] ?? '';
                    $jaCourseName = $course['translations']['ja']['name'] ?? '';
                    if ($enCourseName && $jaCourseName) {
                        $japaneseCoursesMap[$enCourseName] = $jaCourseName;
                    }
                }
                
                // Professor translations
                if (isset($course['professors'])) {
                    foreach ($course['professors'] as $prof) {
                        $enName = $prof['name']['en'] ?? '';
                        $jaName = $prof['name']['ja'] ?? '';
                        $enDept = $prof['department']['en'] ?? '';
                        $jaDept = $prof['department']['ja'] ?? '';
                        
                        if ($enName && $jaName) {
                            $japaneseProfessorsMap[$enName] = [
                                'name' => $jaName,
                                'department' => $jaDept
                            ];
                        }
                    }
                }
            }
            
            // Now add Japanese translations to the database results
            foreach ($dbProfessors as &$prof) {
                $englishName = $prof['name'];
                if (isset($japaneseProfessorsMap[$englishName])) {
                    $prof['name_ja'] = $japaneseProfessorsMap[$englishName]['name'];
                    $prof['department_ja'] = $japaneseProfessorsMap[$englishName]['department'];
                } else {
                    // Default Japanese name if not found in map
                    $prof['name_ja'] = $prof['name'];
                    $prof['department_ja'] = $prof['department'];
                }
            }
            
            foreach ($dbCourses as &$course) {
                $englishName = $course['name'];
                if (isset($japaneseCoursesMap[$englishName])) {
                    $course['name_ja'] = $japaneseCoursesMap[$englishName];
                } else {
                    // Default Japanese name if not found in map
                    $course['name_ja'] = $course['name'];
                }
                
                // Add professor Japanese name if available
                $englishProfName = $course['professor_name'];
                if (isset($japaneseProfessorsMap[$englishProfName])) {
                    $course['professor_name_ja'] = $japaneseProfessorsMap[$englishProfName]['name'];
                } else {
                    $course['professor_name_ja'] = $course['professor_name'];
                }
            }
            
            // Search directly in JSON data for Japanese matches if language is Japanese
            if ($language === 'ja') {
                // Search for professors in JSON using Japanese names
                foreach ($jsonData['courses'] as $course) {
                    if (isset($course['professors'])) {
                        foreach ($course['professors'] as $prof) {
                            $jaName = $prof['name']['ja'] ?? '';
                            $jaDept = $prof['department']['ja'] ?? '';
                            
                            // Check if name or department matches search query
                            if (
                                (strpos(strtolower($jaName), strtolower($searchQuery)) !== false) ||
                                (strpos(strtolower($jaDept), strtolower($searchQuery)) !== false)
                            ) {
                                // Generate a unique ID based on professor name
                                $uniqueId = md5($prof['name']['en']);
                                
                                // Check if we already have this professor from the database
                                $alreadyAdded = false;
                                foreach ($dbProfessors as $dbProf) {
                                    if ($dbProf['name'] === $prof['name']['en']) {
                                        $alreadyAdded = true;
                                        break;
                                    }
                                }
                                
                                // If not already added, add to results
                                if (!$alreadyAdded) {
                                    $results['professors'][] = [
                                        'id' => $uniqueId,
                                        'name' => $prof['name']['en'],
                                        'name_en' => $prof['name']['en'],
                                        'name_ja' => $jaName,
                                        'department' => $prof['department']['en'],
                                        'department_en' => $prof['department']['en'],
                                        'department_ja' => $jaDept,
                                        'avg_rating' => null,
                                        'review_count' => 0
                                    ];
                                }
                            }
                        }
                    }
                    
                    // Search for courses in JSON using Japanese names
                    if (isset($course['translations'])) {
                        $jaCourseName = $course['translations']['ja']['name'] ?? '';
                        
                        // Check if course name matches search query
                        if (strpos(strtolower($jaCourseName), strtolower($searchQuery)) !== false) {
                            $uniqueId = md5($course['translations']['en']['name']);
                            
                            // Check if we already have this course from the database
                            $alreadyAdded = false;
                            foreach ($dbCourses as $dbCourse) {
                                if ($dbCourse['name'] === $course['translations']['en']['name']) {
                                    $alreadyAdded = true;
                                    break;
                                }
                            }
                            
                            // If not already added, add to results
                            if (!$alreadyAdded) {
                                $results['courses'][] = [
                                    'id' => $uniqueId,
                                    'name' => $course['translations']['en']['name'],
                                    'name_en' => $course['translations']['en']['name'],
                                    'name_ja' => $jaCourseName,
                                    'course_code' => $course['course_id'] ?? '',
                                    'avg_rating' => null,
                                    'review_count' => 0,
                                    'professor_name' => isset($course['professors'][0]) ? $course['professors'][0]['name']['en'] : '',
                                    'professor_name_en' => isset($course['professors'][0]) ? $course['professors'][0]['name']['en'] : '',
                                    'professor_name_ja' => isset($course['professors'][0]) ? $course['professors'][0]['name']['ja'] : ''
                                ];
                            }
                        }
                    }
                }
            }
        }
        
        // Add database results to final results array
        foreach ($dbProfessors as $prof) {
            $results['professors'][] = $prof;
        }
        
        foreach ($dbCourses as $course) {
            $results['courses'][] = $course;
        }
        
    } catch (Exception $e) {
        $results['error'] = "Error performing search: " . $e->getMessage();
    }
}

// Return results as JSON
header('Content-Type: application/json');
echo json_encode($results);