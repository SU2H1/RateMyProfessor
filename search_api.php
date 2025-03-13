<?php
// Include database configuration
require_once 'config.php';

// Get search query from GET parameter and ensure proper UTF-8 encoding
$searchQuery = isset($_GET['q']) ? trim($_GET['q']) : '';
$searchQuery = mb_convert_encoding($searchQuery, 'UTF-8', 'AUTO');

// Get language from the request or cookie
$currentLang = isset($_GET['lang']) ? $_GET['lang'] : (isset($_COOKIE['language']) ? $_COOKIE['language'] : 'en');

// Initialize results array
$results = [
    'professors' => [],
    'courses' => []
];

// Only proceed if we have a search query
if (!empty($searchQuery)) {
    try {
        // Log the search query for debugging
        error_log("Search query: " . $searchQuery . " (Language: " . $currentLang . ")");
        
        // Create a reversed mapping (Japanese to English) for searching
        $professorEnMap = [];
        $courseEnMap = [];
        $departmentEnMap = [];
        
        // Load translations from JSON file
        $jsonFilePath = __DIR__ . '/sfc_courses.json';
        if (file_exists($jsonFilePath)) {
            $jsonData = file_get_contents($jsonFilePath);
            if ($jsonData !== false) {
                $data = json_decode($jsonData, true);
                if ($data !== null) {
                    // Create mappings for both directions (en->ja and ja->en)
                    foreach ($data['courses'] as $course) {
                        // Map course names
                        if (isset($course['translations']['ja']['name']) && isset($course['translations']['en']['name'])) {
                            $enName = $course['translations']['en']['name'];
                            $jaName = $course['translations']['ja']['name'];
                            $courseEnMap[$jaName] = $enName; // Japanese to English mapping
                        }
                        
                        // Map professor names
                        foreach ($course['professors'] as $prof) {
                            if (isset($prof['name']['en']) && isset($prof['name']['ja'])) {
                                $enName = $prof['name']['en'];
                                $jaName = $prof['name']['ja'];
                                $professorEnMap[$jaName] = $enName; // Japanese to English mapping
                            }
                        }
                        
                        // Map department names
                        if (isset($course['department']['translations'])) {
                            if (isset($course['department']['translations']['en']) && isset($course['department']['translations']['ja'])) {
                                $enDept = $course['department']['translations']['en'];
                                $jaDept = $course['department']['translations']['ja'];
                                $departmentEnMap[$jaDept] = $enDept; // Japanese to English mapping
                            }
                        }
                    }
                }
            }
        }
        
        // Check if the search query might be in Japanese
        $hasJapaneseChars = preg_match('/[\x{3000}-\x{303F}]|[\x{3040}-\x{309F}]|[\x{30A0}-\x{30FF}]|[\x{FF00}-\x{FFEF}]|[\x{4E00}-\x{9FAF}]/u', $searchQuery);
        
        // If Japanese characters are detected, try to find English equivalents for searching
        $searchTerms = [$searchQuery];
        if ($hasJapaneseChars) {
            error_log("Japanese characters detected in search query");
            
            // Look for potential matches in our mappings
            foreach ($professorEnMap as $jaName => $enName) {
                if (mb_stripos($jaName, $searchQuery) !== false) {
                    $searchTerms[] = $enName;
                    error_log("Found matching professor name: $jaName -> $enName");
                }
            }
            
            foreach ($courseEnMap as $jaName => $enName) {
                if (mb_stripos($jaName, $searchQuery) !== false) {
                    $searchTerms[] = $enName;
                    error_log("Found matching course name: $jaName -> $enName");
                }
            }
            
            foreach ($departmentEnMap as $jaName => $enName) {
                if (mb_stripos($jaName, $searchQuery) !== false) {
                    $searchTerms[] = $enName;
                    error_log("Found matching department name: $jaName -> $enName");
                }
            }
        }
        
        // Build search conditions for multiple terms
        $professorConditions = [];
        $courseConditions = [];
        $searchParams = [];
        
        foreach ($searchTerms as $index => $term) {
            $paramName = ":search{$index}";
            $exactParamName = ":exact{$index}";
            
            $professorConditions[] = "name LIKE {$paramName} OR department LIKE {$paramName}";
            $courseConditions[] = "c.name LIKE {$paramName} OR c.course_code LIKE {$paramName} OR p.name LIKE {$paramName}";
            
            $searchParams[$paramName] = "%{$term}%";
            $searchParams[$exactParamName] = $term;
        }
        
        // Search for professors with multiple search terms
        $professorQuery = "
            SELECT id, name, department, 
                   COALESCE(overall_rating, (avg_content_quality + avg_difficulty) / 2) as avg_rating,
                   COALESCE(review_count, 0) as review_count
            FROM professors
            WHERE " . implode(" OR ", $professorConditions) . "
            ORDER BY 
                /* Prioritize exact matches to the top */
                CASE WHEN LOWER(name) = LOWER(:exact0) THEN 0 ELSE 1 END,
                CASE WHEN LOWER(department) = LOWER(:exact0) THEN 0 ELSE 1 END,
                avg_rating DESC, review_count DESC
            LIMIT 10
        ";
        
        $stmt = $conn->prepare($professorQuery);
        
        // Bind all search parameters
        foreach ($searchParams as $param => $value) {
            $stmt->bindValue($param, $value, SQLITE3_TEXT);
        }
        
        $result = $stmt->execute();
        
        while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
            // Format the rating if it exists
            if (isset($row['avg_rating']) && $row['avg_rating'] !== null) {
                $row['avg_rating'] = number_format((float)$row['avg_rating'], 1);
            } else {
                $row['avg_rating'] = null;
            }
            
            $results['professors'][] = $row;
        }
        
        // Search for courses with multiple search terms
        $courseQuery = "
            SELECT c.id, c.name, c.course_code,
                   COALESCE(c.avg_content_quality, 0) as avg_content_quality,
                   COALESCE(c.avg_difficulty, 0) as avg_difficulty,
                   (COALESCE(c.avg_content_quality, 0) + (COALESCE(c.avg_difficulty, 0))) / 2 as avg_rating,
                   COALESCE(c.review_count, 0) as review_count,
                   p.name as professor_name
            FROM courses c
            LEFT JOIN professors p ON c.professor_id = p.id
            WHERE " . implode(" OR ", $courseConditions) . "
            ORDER BY 
                /* Prioritize exact matches to the top */
                CASE WHEN LOWER(c.name) = LOWER(:exact0) THEN 0 ELSE 1 END,
                CASE WHEN LOWER(c.course_code) = LOWER(:exact0) THEN 0 ELSE 1 END,
                avg_rating DESC, review_count DESC
            LIMIT 10
        ";
        
        $stmt = $conn->prepare($courseQuery);
        
        // Bind all search parameters
        foreach ($searchParams as $param => $value) {
            $stmt->bindValue($param, $value, SQLITE3_TEXT);
        }
        
        $result = $stmt->execute();
        
        while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
            // Format the rating if it exists
            if (isset($row['avg_rating']) && $row['avg_rating'] !== null) {
                $row['avg_rating'] = number_format((float)$row['avg_rating'], 1);
            } else {
                $row['avg_rating'] = null;
            }
            
            $results['courses'][] = $row;
        }
        
        // If language is Japanese, translate results
        if ($currentLang == 'ja') {
            $professorJa = [];
            $courseJa = [];
            $departmentJa = [];
            
            // Prepare translations from the JSON data
            if (isset($data) && $data !== null) {
                foreach ($data['courses'] as $course) {
                    // Map course name
                    if (isset($course['translations']['ja']['name']) && isset($course['translations']['en']['name'])) {
                        $enName = $course['translations']['en']['name'];
                        $jaName = $course['translations']['ja']['name'];
                        $courseJa[$enName] = $jaName;
                    }
                    
                    // Map professor names
                    foreach ($course['professors'] as $prof) {
                        if (isset($prof['name']['en']) && isset($prof['name']['ja'])) {
                            $enName = $prof['name']['en'];
                            $jaName = $prof['name']['ja'];
                            $professorJa[$enName] = $jaName;
                        }
                    }
                    
                    // Map department names
                    if (isset($course['department']['translations'])) {
                        if (isset($course['department']['translations']['en']) && isset($course['department']['translations']['ja'])) {
                            $enDept = $course['department']['translations']['en'];
                            $jaDept = $course['department']['translations']['ja'];
                            $departmentJa[$enDept] = $jaDept;
                        }
                    }
                }
            }
            
            // Update professor names and departments to Japanese
            foreach ($results['professors'] as &$professor) {
                if (isset($professor['name']) && isset($professorJa[$professor['name']])) {
                    $professor['name'] = $professorJa[$professor['name']];
                }
                
                if (isset($professor['department']) && isset($departmentJa[$professor['department']])) {
                    $professor['department'] = $departmentJa[$professor['department']];
                }
            }
            
            // Update course names and professor names to Japanese
            foreach ($results['courses'] as &$course) {
                if (isset($course['name']) && isset($courseJa[$course['name']])) {
                    $course['name'] = $courseJa[$course['name']];
                }
                
                if (isset($course['professor_name']) && isset($professorJa[$course['professor_name']])) {
                    $course['professor_name'] = $professorJa[$course['professor_name']];
                }
            }
        }
    } catch (Exception $e) {
        error_log("Search error: " . $e->getMessage());
        $results['error'] = "Error performing search: " . $e->getMessage();
    }
}

// Return results as JSON with UTF-8 encoding
header('Content-Type: application/json; charset=utf-8');
echo json_encode($results);