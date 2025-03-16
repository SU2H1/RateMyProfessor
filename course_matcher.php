<?php
/**
 * Course Matcher Utility
 * Provides robust matching functionality for courses across different data sources
 */

class CourseMatcher {
    private $db;
    private $jsonData;
    private $langMap = [];
    
    public function __construct($db) {
        $this->db = $db;
        $this->loadJsonData();
        $this->buildLanguageMap();
    }
    
    /**
     * Load course data from JSON file
     */
    private function loadJsonData() {
        $jsonFilePath = __DIR__ . '/sfc_courses.json';
        if (file_exists($jsonFilePath)) {
            $jsonData = file_get_contents($jsonFilePath);
            if ($jsonData !== false) {
                $this->jsonData = json_decode($jsonData, true);
            }
        }
    }
    
    /**
     * Build a mapping between Japanese and English course names
     */
    private function buildLanguageMap() {
        if (!$this->jsonData || !isset($this->jsonData['courses'])) {
            return;
        }
        
        foreach ($this->jsonData['courses'] as $course) {
            if (isset($course['translations'])) {
                $jaName = $course['translations']['ja']['name'] ?? '';
                $enName = $course['translations']['en']['name'] ?? '';
                
                if ($jaName && $enName) {
                    $this->langMap[$jaName] = $enName;
                    $this->langMap[$enName] = $jaName;
                }
            }
        }
    }
    
    /**
     * Normalize course name for comparison
     */
    public function normalizeCourseName($name) {
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
        
        // Remove course numbers and codes
        $name = preg_replace('/\b[a-z]{1,3}\d{1,4}\b/i', '', $name);
        
        // Remove special characters and extra spaces
        $name = preg_replace('/[^\p{L}\p{N}\s]/u', ' ', $name);
        $name = preg_replace('/\s+/', ' ', $name);
        
        return trim($name);
    }
    
    /**
     * Get similarity score between two strings (0-100)
     */
    public function getStringSimilarity($str1, $str2) {
        if (empty($str1) || empty($str2)) {
            return 0;
        }
        
        // Normalize both strings
        $str1 = $this->normalizeCourseName($str1);
        $str2 = $this->normalizeCourseName($str2);
        
        // Quick equality check
        if ($str1 === $str2) {
            return 100;
        }
        
        // Use Levenshtein distance for similarity
        $levenshtein = levenshtein($str1, $str2);
        $maxLength = max(mb_strlen($str1), mb_strlen($str2));
        
        if ($maxLength === 0) {
            return 0;
        }
        
        return round(100 * (1 - ($levenshtein / $maxLength)));
    }
    
    /**
     * Find matching course in database with confidence score
     */
    public function findCourseMatch($courseName, $courseId = null, $language = 'en') {
        $result = [
            'found' => false,
            'db_id' => null,
            'confidence' => 0,
            'source_id' => null,
            'matched_name' => null,
            'match_type' => null
        ];
        
        // 1. Try direct ID match first (highest confidence)
        if ($courseId) {
            $stmt = $this->db->prepare("
                SELECT id, name, source_id, english_name, japanese_name
                FROM courses 
                WHERE source_id = :source_id 
                LIMIT 1
            ");
            $stmt->bindValue(':source_id', $courseId, SQLITE3_TEXT);
            $res = $stmt->execute();
            $match = $res->fetchArray(SQLITE3_ASSOC);
            
            if ($match) {
                $result = [
                    'found' => true,
                    'db_id' => $match['id'],
                    'confidence' => 100,
                    'source_id' => $match['source_id'],
                    'matched_name' => $match['name'],
                    'match_type' => 'source_id'
                ];
                return $result;
            }
        }
        
        // If no course name provided, we can't continue with name matching
        if (empty($courseName)) {
            return $result;
        }
        
        // 2. Try exact name match (high confidence)
        $fields = ['name', 'english_name', 'japanese_name'];
        foreach ($fields as $field) {
            $stmt = $this->db->prepare("
                SELECT id, name, source_id, english_name, japanese_name
                FROM courses 
                WHERE {$field} = :name
                LIMIT 1
            ");
            $stmt->bindValue(':name', $courseName, SQLITE3_TEXT);
            $res = $stmt->execute();
            $match = $res->fetchArray(SQLITE3_ASSOC);
            
            if ($match) {
                $result = [
                    'found' => true,
                    'db_id' => $match['id'],
                    'confidence' => 95,
                    'source_id' => $match['source_id'],
                    'matched_name' => $match['name'],
                    'match_type' => "exact_{$field}"
                ];
                return $result;
            }
        }
        
        // 3. Try language equivalent (based on JSON mapping)
        if (isset($this->langMap[$courseName])) {
            $equivalentName = $this->langMap[$courseName];
            
            foreach ($fields as $field) {
                $stmt = $this->db->prepare("
                    SELECT id, name, source_id, english_name, japanese_name
                    FROM courses 
                    WHERE {$field} = :name
                    LIMIT 1
                ");
                $stmt->bindValue(':name', $equivalentName, SQLITE3_TEXT);
                $res = $stmt->execute();
                $match = $res->fetchArray(SQLITE3_ASSOC);
                
                if ($match) {
                    $result = [
                        'found' => true,
                        'db_id' => $match['id'],
                        'confidence' => 90,
                        'source_id' => $match['source_id'],
                        'matched_name' => $match['name'],
                        'match_type' => "lang_equivalent_{$field}"
                    ];
                    return $result;
                }
            }
        }
        
        // 4. Try normalized name match (medium confidence)
        $normalizedName = $this->normalizeCourseName($courseName);
        if (!empty($normalizedName)) {
            $stmt = $this->db->prepare("
                SELECT id, name, source_id, english_name, japanese_name, canonical_name
                FROM courses 
                WHERE canonical_name IS NOT NULL
                LIMIT 50
            ");
            $res = $stmt->execute();
            
            $bestMatch = null;
            $bestScore = 0;
            
            while ($row = $res->fetchArray(SQLITE3_ASSOC)) {
                $similarity = $this->getStringSimilarity($normalizedName, $row['canonical_name']);
                
                if ($similarity > $bestScore && $similarity >= 75) {
                    $bestScore = $similarity;
                    $bestMatch = $row;
                }
            }
            
            if ($bestMatch) {
                $result = [
                    'found' => true,
                    'db_id' => $bestMatch['id'],
                    'confidence' => $bestScore,
                    'source_id' => $bestMatch['source_id'],
                    'matched_name' => $bestMatch['name'],
                    'match_type' => 'normalized_similarity'
                ];
                return $result;
            }
        }
        
        // 5. Try fuzzy search match (lower confidence)
        $fuzzyName = '%' . str_replace(' ', '%', $normalizedName) . '%';
        $stmt = $this->db->prepare("
            SELECT id, name, source_id, english_name, japanese_name
            FROM courses 
            WHERE name LIKE :fuzzy
               OR english_name LIKE :fuzzy
               OR japanese_name LIKE :fuzzy
               OR canonical_name LIKE :fuzzy
            LIMIT 1
        ");
        $stmt->bindValue(':fuzzy', $fuzzyName, SQLITE3_TEXT);
        $res = $stmt->execute();
        $match = $res->fetchArray(SQLITE3_ASSOC);
        
        if ($match) {
            $result = [
                'found' => true,
                'db_id' => $match['id'],
                'confidence' => 60,
                'source_id' => $match['source_id'],
                'matched_name' => $match['name'],
                'match_type' => 'fuzzy'
            ];
            return $result;
        }
        
        // No match found
        return $result;
    }
    
    /**
     * Find course info in JSON data
     */
    public function findCourseInJson($courseName, $courseId = null) {
        if (!$this->jsonData || !isset($this->jsonData['courses'])) {
            return null;
        }
        
        // If we have a course ID, use that for an exact match
        if ($courseId) {
            foreach ($this->jsonData['courses'] as $course) {
                if (isset($course['course_id']) && $course['course_id'] === $courseId) {
                    return $course;
                }
            }
        }
        
        // If we have a course name, try to match that
        if ($courseName) {
            // Try exact match first
            foreach ($this->jsonData['courses'] as $course) {
                $jaName = $course['translations']['ja']['name'] ?? '';
                $enName = $course['translations']['en']['name'] ?? '';
                
                if ($jaName === $courseName || $enName === $courseName) {
                    return $course;
                }
            }
            
            // Then try normalized match
            $normalizedName = $this->normalizeCourseName($courseName);
            $bestMatch = null;
            $bestScore = 0;
            
            foreach ($this->jsonData['courses'] as $course) {
                $jaName = $course['translations']['ja']['name'] ?? '';
                $enName = $course['translations']['en']['name'] ?? '';
                
                $jaSimilarity = $this->getStringSimilarity($normalizedName, $jaName);
                $enSimilarity = $this->getStringSimilarity($normalizedName, $enName);
                
                $bestSimilarity = max($jaSimilarity, $enSimilarity);
                
                if ($bestSimilarity > $bestScore && $bestSimilarity >= 75) {
                    $bestScore = $bestSimilarity;
                    $bestMatch = $course;
                }
            }
            
            if ($bestMatch) {
                return $bestMatch;
            }
        }
        
        return null;
    }
    
    /**
     * Create or update course in database
     */
    public function createOrUpdateCourse($courseName, $courseId = null, $language = 'en') {
        // 1. First try to find a match
        $match = $this->findCourseMatch($courseName, $courseId, $language);
        
        if ($match['found']) {
            error_log("Found course match: " . json_encode($match));
            
            // If confidence is high enough, return the existing course
            if ($match['confidence'] >= 75) {
                return [
                    'id' => $match['db_id'],
                    'is_new' => false,
                    'confidence' => $match['confidence']
                ];
            }
        }
        
        // 2. Look for course in JSON data
        $courseInfo = $this->findCourseInJson($courseName, $courseId);
        
        // 3. Prepare data for insertion/update
        $jaName = '';
        $enName = '';
        $sourceId = null;
        $canonicalName = '';
        $semester = '';
        $year = '';
        
        if ($courseInfo) {
            $jaName = $courseInfo['translations']['ja']['name'] ?? '';
            $enName = $courseInfo['translations']['en']['name'] ?? '';
            $sourceId = $courseInfo['course_id'] ?? null;
            $semester = $courseInfo['semester'] ?? '';
            $year = $courseInfo['year'] ?? '';
        }
        
        // If we found the source ID from JSON but not from database, try one more lookup
        if ($sourceId && (!$match['found'] || !$match['source_id'])) {
            $stmt = $this->db->prepare("
                SELECT id FROM courses 
                WHERE source_id = :source_id
                LIMIT 1
            ");
            $stmt->bindValue(':source_id', $sourceId, SQLITE3_TEXT);
            $res = $stmt->execute();
            $sourceMatch = $res->fetchArray(SQLITE3_ASSOC);
            
            if ($sourceMatch) {
                return [
                    'id' => $sourceMatch['id'],
                    'is_new' => false,
                    'confidence' => 100
                ];
            }
        }
        
        // Set display name based on available data
        $displayName = $courseName;
        if (!$displayName) {
            $displayName = ($language === 'ja') ? ($jaName ?: $enName) : ($enName ?: $jaName);
        }
        
        // Create normalized canonical name
        $canonicalName = $this->normalizeCourseName($displayName);
        
        // 4. Insert or update
        if ($match['found']) {
            // Update existing record
            $stmt = $this->db->prepare("
                UPDATE courses SET
                    source_id = :source_id,
                    japanese_name = COALESCE(:japanese_name, japanese_name),
                    english_name = COALESCE(:english_name, english_name),
                    canonical_name = :canonical_name,
                    semester = COALESCE(:semester, semester),
                    year = COALESCE(:year, year),
                    is_synchronized = 1,
                    last_sync_date = datetime('now')
                WHERE id = :id
            ");
            
            $stmt->bindValue(':id', $match['db_id'], SQLITE3_INTEGER);
            $stmt->bindValue(':source_id', $sourceId, $sourceId ? SQLITE3_TEXT : SQLITE3_NULL);
            $stmt->bindValue(':japanese_name', $jaName, $jaName ? SQLITE3_TEXT : SQLITE3_NULL);
            $stmt->bindValue(':english_name', $enName, $enName ? SQLITE3_TEXT : SQLITE3_NULL);
            $stmt->bindValue(':canonical_name', $canonicalName, SQLITE3_TEXT);
            $stmt->bindValue(':semester', $semester, $semester ? SQLITE3_TEXT : SQLITE3_NULL);
            $stmt->bindValue(':year', $year, $year ? SQLITE3_TEXT : SQLITE3_NULL);
            
            $result = $stmt->execute();
            
            if ($result) {
                return [
                    'id' => $match['db_id'],
                    'is_new' => false,
                    'confidence' => $match['confidence']
                ];
            }
        }
        
        // Insert new record
        $stmt = $this->db->prepare("
            INSERT INTO courses (
                name,
                source_id,
                course_code,
                japanese_name,
                english_name,
                canonical_name,
                semester,
                year,
                language_code,
                is_synchronized,
                last_sync_date
            ) VALUES (
                :name,
                :source_id,
                :course_code,
                :japanese_name,
                :english_name,
                :canonical_name,
                :semester,
                :year,
                :language_code,
                1,
                datetime('now')
            )
        ");
        
        $stmt->bindValue(':name', $displayName, SQLITE3_TEXT);
        $stmt->bindValue(':source_id', $sourceId, $sourceId ? SQLITE3_TEXT : SQLITE3_NULL);
        $stmt->bindValue(':course_code', $sourceId, $sourceId ? SQLITE3_TEXT : SQLITE3_NULL);
        $stmt->bindValue(':japanese_name', $jaName, $jaName ? SQLITE3_TEXT : SQLITE3_NULL);
        $stmt->bindValue(':english_name', $enName, $enName ? SQLITE3_TEXT : SQLITE3_NULL);
        $stmt->bindValue(':canonical_name', $canonicalName, SQLITE3_TEXT);
        $stmt->bindValue(':semester', $semester, $semester ? SQLITE3_TEXT : SQLITE3_NULL);
        $stmt->bindValue(':year', $year, $year ? SQLITE3_TEXT : SQLITE3_NULL);
        $stmt->bindValue(':language_code', $language, SQLITE3_TEXT);
        
        $result = $stmt->execute();
        
        if ($result) {
            $newId = $this->db->lastInsertRowID();
            return [
                'id' => $newId,
                'is_new' => true,
                'confidence' => 100
            ];
        }
        
        throw new Exception("Failed to insert course: " . $this->db->lastErrorMsg());
    }
}