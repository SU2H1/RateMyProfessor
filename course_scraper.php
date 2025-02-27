<?php

/**
 * SFC Course Scraper - Test Version for Data Science 1
 * 
 * This version:
 * 1. Tests only Data Science 1 courses from 2023 and 2024
 * 2. Processes both Japanese and English syllabus URLs
 * 3. Links courses across languages using K-Number as the unique identifier
 * 4. Stores both language versions of each course
 * 5. FIXED: Properly extracts and applies English professor names
 */

// Set maximum execution time to 30 minutes for processing multiple URLs with pagination
ini_set('max_execution_time', 1800);
set_time_limit(1800);

// JSON file path
$jsonFilePath = __DIR__ . '/sfc_courses.json';

// Define the specific fields to scrape (used for filtering)
$targetFields = [
    'ja' => [
        // Data Science
        'データサイエンス1',
        'データサイエンス１',
        '基盤科目-データサイエンス科目-データサイエンス1',
        '23/11/2014/4.基盤科目-データサイエンス科目-データサイエンス1',
        'データサイエンス2',
        'データサイエンス２',

        // Information Technology
        '情報技術基礎科目',
        '基盤科目-情報技術基礎科目',
        '情報基礎',
        'プログラミング',
        'システムプログラミング',
        'オブジェクト指向',
        'スクリプト言語',

        // Other subject categories
        '総合政策・環境情報学部/2014/基盤科目-総合講座科目',
        '総合政策・環境情報学部/2014/基盤科目-言語コミュニケーション科目',
        '総合政策・環境情報学部/2014/基盤科目-データサイエンス科目-データサイエンス2',
        '総合政策・環境情報学部/2014/基盤科目-情報技術基礎科目',
        '総合政策・環境情報学部/2014/基盤科目-共通科目',
        '総合政策・環境情報学部/2014/先端科目-総合政策系',
        '総合政策・環境情報学部/2014/先端科目-環境情報系',
        '総合政策・環境情報学部/2014/特設科目',
        '基盤科目-総合講座科目',
        '基盤科目-言語コミュニケーション科目',
        '基盤科目-共通科目',
        '先端科目-総合政策系',
        '先端科目-環境情報系',
        '特設科目'
    ],
    'en' => [
        // Data Science
        'DATA SCIENCE 1',
        'DATA SCIENCE I',
        'FUNDAMENTAL SUBJECTS - SUBJECTS OF DATA SCIENCE - DATA SCIENCE 1',
        '23/11/2014/4.FUNDAMENTAL SUBJECTS - SUBJECTS OF DATA SCIENCE - DATA SCIENCE 1',
        'Subjects of Data Science - Data Science 2',
        'DATA SCIENCE 2',

        // Information Technology
        'FUNDAMENTALS OF INFORMATION TECHNOLOGY',
        'PROGRAMMING',
        'SYSTEM PROGRAMMING',
        'OBJECT-ORIENTED PROGRAMMING',
        'SCRIPT LANGUAGES',
        'Subjects of Fundamentals of Information Technology',

        // Other subject categories
        'FACULTY OF POLICY MANAGEMENT / ENVIRONMENT AND INFORMATION STUDIES/2014/Fundamental Subjects - Introductory Subjects',
        'Fundamental Subjects - Introductory Subjects',
        'FACULTY OF POLICY MANAGEMENT / ENVIRONMENT AND INFORMATION STUDIES/2014/Fundamental Subjects - Subjects of Language Communication',
        'FACULTY OF POLICY MANAGEMENT / ENVIRONMENT AND INFORMATION STUDIES/2014/Fundamental Subjects - Subjects of Data Science - Data Science 2',
        'FACULTY OF POLICY MANAGEMENT / ENVIRONMENT AND INFORMATION STUDIES/2014/Fundamental Subjects - Subjects of Fundamentals of Information Technology',
        'FACULTY OF POLICY MANAGEMENT / ENVIRONMENT AND INFORMATION STUDIES/2014/Fundamental Subjects - Interdisciplinary Subjects',
        'FACULTY OF POLICY MANAGEMENT / ENVIRONMENT AND INFORMATION STUDIES/2014/Advanced Subjects - Series of Policy Management',
        'FACULTY OF POLICY MANAGEMENT / ENVIRONMENT AND INFORMATION STUDIES/2014/Advanced Subjects - Series of Environment And Information Studies',
        'FACULTY OF POLICY MANAGEMENT / ENVIRONMENT AND INFORMATION STUDIES/2014/Special Subjects',
        'Fundamental Subjects - Subjects of Language Communication',
        'Interdisciplinary Subjects',
        'Advanced Subjects - Series of Policy Management',
        'Advanced Subjects - Series of Environment And Information Studies',
        'Special Subjects'
    ]
];

// URLs for testing Data Science courses only
$urlsToScrape = [
    // Data Science 1 (2024) - Japanese and English
    "https://syllabus.sfc.keio.ac.jp/courses?locale=ja&search%5Btitle%5D=&search%5Byear%5D=2024&search%5Bsemester%5D=&search%5Bsub_semester%5D=&search%5Bteacher_name%5D=&search%5Bsfc_guide_title%5D=23%2F11%2F2014%2F11.Special+Subjects&search%5Bsummary%5D=&button=",
    "https://syllabus.sfc.keio.ac.jp/courses?locale=en&search%5Btitle%5D=&search%5Byear%5D=2024&search%5Bsemester%5D=&search%5Bsub_semester%5D=&search%5Bteacher_name%5D=&search%5Bsfc_guide_title%5D=23%2F11%2F2014%2F11.Special+Subjects&search%5Bsummary%5D=&button=",

    // Data Science 1 (2023) - Japanese and English to test year combining
    "https://syllabus.sfc.keio.ac.jp/courses?locale=ja&search%5Btitle%5D=&search%5Byear%5D=2023&search%5Bsemester%5D=&search%5Bsub_semester%5D=&search%5Bteacher_name%5D=&search%5Bsfc_guide_title%5D=23%2F11%2F2014%2F11.Special+Subjects&search%5Bsummary%5D=&button=",
    "https://syllabus.sfc.keio.ac.jp/courses?locale=en&search%5Btitle%5D=&search%5Byear%5D=2023&search%5Bsemester%5D=&search%5Bsub_semester%5D=&search%5Bteacher_name%5D=&search%5Bsfc_guide_title%5D=23%2F11%2F2014%2F11.Special+Subjects&search%5Bsummary%5D=&button="
];

// Maximum number of pages to process per URL
$maxPagesPerUrl = 50;

// Function to fetch URL content
function fetchUrlContent($url)
{
    $userAgent = 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36';

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_USERAGENT, $userAgent);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_TIMEOUT, 60);

    $content = curl_exec($ch);

    if (curl_errno($ch)) {
        echo "Error fetching URL: " . curl_error($ch) . "\n";
        return false;
    }

    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    if ($httpCode != 200) {
        echo "HTTP Error: $httpCode for URL: $url\n";
    }

    curl_close($ch);
    return $content;
}

// Function to clean instructor names properly
function cleanInstructorName($name)
{
    $name = trim($name);
    // Remove any non-breaking spaces, regular spaces at start/end
    $name = preg_replace('/^[\s\xA0]+|[\s\xA0]+$/u', '', $name);
    return $name;
}

// Enhanced instructor name extraction with detection of language in the names
function extractInstructorNames($xpath, $courseLi, $language = 'ja')
{
    $instructors = [];

    // Different approach based on language of the page
    if ($language == 'en') {
        $instructorText = "Lecturer Name";

        echo "\nExtracting English instructors for a course...\n";

        // Method 1: Try to get from instructor element directly
        $instructorElement = $xpath->query('.//dt[contains(text(), "' . $instructorText . '")]/following-sibling::dd[1]', $courseLi)->item(0);

        if ($instructorElement) {
            echo "Found instructor element by direct query\n";

            // Clean and process the text content
            $content = trim($instructorElement->textContent);
            $content = str_replace("\xC2\xA0", " ", $content); // Replace non-breaking spaces

            echo "Raw instructor content: '$content'\n";

            // Split by commas, non-breaking spaces, and line breaks
            $instructorLines = preg_split('/\s*[\r\n,]+\s*/', $content);

            echo "Found " . count($instructorLines) . " instructor lines after splitting\n";

            foreach ($instructorLines as $line) {
                $name = trim($line);
                if (!empty($name)) {
                    // Make sure this is a valid English name (not blank or just spaces)
                    if (strlen($name) > 1) {
                        $instructors[] = $name;
                        echo "Added instructor: '$name'\n";
                    }
                }
            }

            // If we have instructors, return them
            if (!empty($instructors)) {
                return $instructors;
            }
        } else {
            echo "No instructor element found by direct query\n";
        }

        // Method 2: Try to get from links in the instructor section
        $instructorLinks = $xpath->query('.//dt[contains(text(), "' . $instructorText . '")]/following-sibling::dd[1]//a', $courseLi);

        if ($instructorLinks && $instructorLinks->length > 0) {
            echo "Found " . $instructorLinks->length . " instructor links\n";

            foreach ($instructorLinks as $link) {
                $name = trim($link->textContent);
                if (!empty($name) && strlen($name) > 1) {
                    $instructors[] = $name;
                    echo "Added instructor from link: '$name'\n";
                }
            }

            if (!empty($instructors)) {
                return $instructors;
            }
        } else {
            echo "No instructor links found\n";
        }

        // Method 3: Try a broader approach with just looking for dt tags
        $dtElements = $xpath->query('.//dt', $courseLi);
        echo "Scanning " . $dtElements->length . " dt elements for instructor info\n";

        foreach ($dtElements as $dt) {
            $dtText = trim($dt->textContent);
            if (stripos($dtText, 'Lecturer') !== false || stripos($dtText, 'Instructor') !== false) {
                echo "Found possible instructor dt element: '$dtText'\n";

                // Get the next dd element
                $dd = $dt->nextSibling;
                while ($dd && $dd->nodeName !== 'dd') {
                    $dd = $dd->nextSibling;
                }

                if ($dd) {
                    $content = trim($dd->textContent);
                    echo "Found instructor dd with content: '$content'\n";

                    $names = preg_split('/\s*[\r\n,]+\s*/', $content);
                    foreach ($names as $name) {
                        $cleanName = trim($name);
                        if (!empty($cleanName) && strlen($cleanName) > 1) {
                            $instructors[] = $cleanName;
                            echo "Added instructor from dt scan: '$cleanName'\n";
                        }
                    }

                    if (!empty($instructors)) {
                        return $instructors;
                    }
                }
            }
        }

        // Method 4: Try alternate HTML structures with class-info
        $instructorContent = $xpath->query('.//div[contains(@class, "class-info")]//dt[contains(text(), "' . $instructorText . '")]/following-sibling::dd[1]', $courseLi);

        if ($instructorContent && $instructorContent->length > 0) {
            echo "Found instructor content in class-info div\n";

            $content = trim($instructorContent->item(0)->textContent);
            echo "Raw content from class-info: '$content'\n";

            $names = preg_split('/\s*[\r\n,]+\s*/', $content);

            foreach ($names as $name) {
                $cleanName = trim($name);
                if (!empty($cleanName) && strlen($cleanName) > 1) {
                    $instructors[] = $cleanName;
                    echo "Added instructor from class-info: '$cleanName'\n";
                }
            }

            if (!empty($instructors)) {
                return $instructors;
            }
        } else {
            echo "No instructor content found in class-info div\n";
        }
    } else {
        // Japanese page extraction - much simpler and more direct
        $instructorText = "授業教員名";

        echo "\nExtracting Japanese instructors for a course...\n";

        // Look for the exact instructor element
        $instructorElement = $xpath->query('.//dt[contains(text(), "' . $instructorText . '")]/following-sibling::dd[1]', $courseLi)->item(0);

        if ($instructorElement) {
            echo "Found Japanese instructor element\n";

            // Get the raw content
            $content = trim($instructorElement->textContent);
            echo "Raw Japanese instructor content: '$content'\n";

            // Replace non-breaking spaces with regular ones
            $content = str_replace("\xC2\xA0", " ", $content);

            // Split by new lines to get each professor's full name
            $instructorLines = preg_split('/\s*[\r\n]+\s*/', $content);

            echo "Found " . count($instructorLines) . " Japanese instructor names\n";

            foreach ($instructorLines as $line) {
                $line = trim($line);
                if (!empty($line)) {
                    // Don't split the name into parts, keep as a single full name
                    $instructors[] = $line;
                    echo "Added Japanese instructor: '$line'\n";
                }
            }
        } else {
            echo "No Japanese instructor element found\n";

            // Try alternate method specifically for Japanese
            $classInfoInstructors = $xpath->query('.//div[contains(@class, "class-info")]//dt[contains(text(), "授業教員")]/following-sibling::dd[1]', $courseLi);

            if ($classInfoInstructors && $classInfoInstructors->length > 0) {
                echo "Found Japanese instructor in class-info\n";
                $content = trim($classInfoInstructors->item(0)->textContent);
                echo "Raw Japanese instructor from class-info: '$content'\n";

                $names = preg_split('/\s*[\r\n]+\s*/', $content);
                foreach ($names as $name) {
                    $cleanName = trim($name);
                    if (!empty($cleanName)) {
                        $instructors[] = $cleanName;
                        echo "Added Japanese instructor from class-info: '$cleanName'\n";
                    }
                }
            }
        }
    }

    // Check if we have instructor names that appear to be in the wrong language
    if (!empty($instructors)) {
        $validInstructors = [];

        foreach ($instructors as $key => $instructor) {
            $hasJapaneseChars = preg_match('/[\p{Hiragana}\p{Katakana}\p{Han}]/u', $instructor);
            $instructor = trim($instructor);

            // Skip empty or very short names
            if (empty($instructor) || strlen($instructor) <= 1) {
                continue;
            }

            // For English pages, we want English names
            if ($language == 'en') {
                if ($hasJapaneseChars) {
                    echo "Warning: Found Japanese instructor name ($instructor) on English page, will exclude\n";
                    // Skip Japanese names on English pages
                    continue;
                }
            }
            // For Japanese pages, we want Japanese names
            else if ($language == 'ja') {
                if (!$hasJapaneseChars && strpos($instructor, ' ') !== false) {
                    echo "Warning: Found English instructor name ($instructor) on Japanese page, will exclude\n";
                    // Skip English names on Japanese pages
                    continue;
                }
            }

            // Add valid instructor names
            $validInstructors[] = $instructor;
        }

        return $validInstructors;
    }

    return $instructors;
}

// Function to compare arrays of instructors (ignoring order)
function compareInstructors($instructors1, $instructors2)
{
    if (count($instructors1) !== count($instructors2)) {
        return false;
    }

    // Sort both arrays for comparison
    sort($instructors1);
    sort($instructors2);

    // Compare each element
    for ($i = 0; $i < count($instructors1); $i++) {
        if ($instructors1[$i] !== $instructors2[$i]) {
            return false;
        }
    }

    return true;
}

// Function to extract the language from a URL
function getLanguageFromUrl($url)
{
    if (strpos($url, 'locale=ja') !== false) {
        return 'ja';
    } elseif (strpos($url, 'locale=en') !== false) {
        return 'en';
    }
    return 'unknown';
}

// Function to find matching course for integration of ja/en translations
function findCourseByNameAndInstructors($courses, $courseName, $instructors, $regNumber, $year, $language, $courseData)
{
    // First try: Look for matching registration number (most reliable method)
    foreach ($courses as $index => $course) {
        if ($course['reg_number'] === $regNumber && $course['year'] === $year) {
            echo "Found course match by registration number and year: $regNumber, $year\n";
            return $index;
        }
    }

    // Second try: If not found by registration number, try by name and instructors
    // This helps identify the same course taught in different years
    foreach ($courses as $index => $course) {
        // Skip courses from the same year but with different reg numbers
        // These are different courses from the same year
        if ($course['year'] === $year && $course['reg_number'] !== $regNumber) {
            continue;
        }

        // Check if course name matches in any translation
        $nameMatched = false;
        foreach ($course['translations'] as $langData) {
            if (isset($langData['course_name']) && strtolower($langData['course_name']) === strtolower($courseName)) {
                $nameMatched = true;
                break;
            } elseif (isset($langData['name']) && strtolower($langData['name']) === strtolower($courseName)) {
                $nameMatched = true;
                break;
            }
        }

        // If name matches, check if instructors are the same
        if ($nameMatched) {
            // Extract instructor names from course (ignoring numeric keys)
            $existingInstructors = array_values($course['instructors']);

            // Sort both arrays for case-insensitive comparison
            $instructorsToCheck = array_map('strtolower', $instructors);
            $existingInstructorsLower = array_map('strtolower', $existingInstructors);

            sort($instructorsToCheck);
            sort($existingInstructorsLower);

            // If instructors are the same, consider it the same course
            if ($instructorsToCheck == $existingInstructorsLower) {
                return $index;
            }
        }
    }
    return -1;
}

// We need to keep track of the English professor names by registration number
// so we can pair them with Japanese professor names
$englishProfessorNames = [];

// Function to extract course information from an HTML page
function parseCourses($html, $existingCourses, $courseCounter = 0, $language = 'ja', $url = '')
{
    global $englishProfessorNames;
    global $targetFields;

    // New courses array
    $newCourses = [];

    // Save debug files
    file_put_contents(__DIR__ . '/debug_' . $language . '_body.html', $html);

    // Load HTML content
    $dom = new DOMDocument();
    @$dom->loadHTML(mb_convert_encoding($html, 'HTML-ENTITIES', 'UTF-8'));
    $xpath = new DOMXPath($dom);

    // Find all course listings
    $courseLiElements = $xpath->query('//div[@class="result"]/ul/li');

    echo "Found " . $courseLiElements->length . " course elements on this page ($language).\n";

    foreach ($courseLiElements as $courseLi) {
        $courseCounter++;

        // Extract course name from h2 tag
        $courseNameElement = $xpath->query('.//h2', $courseLi)->item(0);

        // Extract course name from h2 tag without any manipulation
        $courseName = $courseNameElement ? trim($courseNameElement->textContent) : 'Unknown Course';

        // Clean up the course name based on language
        if ($language == 'ja') {
            // Make sure the Japanese name only has Japanese characters or doesn't contain obvious English words
            if (!preg_match('/[\p{Hiragana}\p{Katakana}\p{Han}]/u', $courseName)) {
                echo "Warning: Japanese course with non-Japanese name: $courseName\n";
                $courseName = 'Unknown Course ' . $courseCounter;
            }
        } else {
            // Make sure the English name doesn't contain Japanese characters
            if (preg_match('/[\p{Hiragana}\p{Katakana}\p{Han}]/u', $courseName)) {
                echo "Warning: English course with Japanese characters: $courseName\n";
                $courseName = 'Unknown Course ' . $courseCounter;
            }
        }

        echo "[$courseCounter] Processing course: $courseName\n";

        // Create a courseData array to store all extracted information
        $courseData = [
            'name' => $courseName,
            'language' => $language
        ];

        // We're no longer using K-Numbers as they're unreliable
        // Just using registration numbers to match courses instead

        // Extract field/category name based on language
        if ($language == 'ja') {
            $fieldText = "分野";
            $fieldElement = $xpath->query('.//dt[contains(text(), "' . $fieldText . '")]/following-sibling::dd[1]', $courseLi)->item(0);
            $field = $fieldElement ? trim($fieldElement->textContent) : 'Unknown Field';

            // Verify the field has Japanese characters
            if (!preg_match('/[\p{Hiragana}\p{Katakana}\p{Han}]/u', $field)) {
                echo "Warning: Japanese course with non-Japanese field: $field\n";
                $field = '不明な分野';
            }
        } else {
            // For English, explicitly look for the Field label
            $fieldElement = $xpath->query('.//dt[text()="Field"]/following-sibling::dd[1]', $courseLi)->item(0);

            if ($fieldElement) {
                $field = trim($fieldElement->textContent);
            } else {
                // If Field not found, try looking for Category as fallback
                $categoryElement = $xpath->query('.//dt[contains(text(), "Category")]/following-sibling::dd[1]', $courseLi)->item(0);
                if ($categoryElement) {
                    $field = trim($categoryElement->textContent);
                } else {
                    $field = 'Unknown Field';
                }
            }

            // Verify the field doesn't have Japanese characters
            if (preg_match('/[\p{Hiragana}\p{Katakana}\p{Han}]/u', $field)) {
                echo "Warning: English course with Japanese characters in field: $field\n";
                $field = 'Unknown Field';
            }
        }

        $courseData['field'] = $field;

        echo "Field detected: $field\n";

        // Check if the field is one of our target fields or if the course name contains Data Science 1
        $fieldMatched = false;
        if (isset($targetFields[$language]) && is_array($targetFields[$language])) {
            foreach ($targetFields[$language] as $targetField) {
                if (stripos($field, $targetField) !== false) {
                    $fieldMatched = true;
                    echo "Field matched: $targetField\n";
                    break;
                }
            }
        }

        // For Data Science courses, they might not have the field explicitly marked
        // So we'll also match by course name for these special cases
        if (!$fieldMatched && (
            stripos($courseName, 'データサイエンス1') !== false ||
            stripos($courseName, 'データサイエンス１') !== false ||
            stripos($courseName, 'DATA SCIENCE 1') !== false ||
            stripos($courseName, 'DATA SCIENCE I') !== false
        )) {
            $fieldMatched = true;
            echo "Matched course by name for Data Science: $courseName\n";
        }

        // Skip this course if its field doesn't match our targets
        if (!$fieldMatched) {
            echo "Skipping course: $courseName with field: $field (not in target fields)\n";
            continue;
        }

        // Extract Course Registration Number
        $regNumberText = ($language == 'ja') ? "登録番号" : "Registration Number";
        $regNumberElement = $xpath->query('.//dt[contains(text(), "' . $regNumberText . '")]/following-sibling::dd[1]', $courseLi)->item(0);
        $regNumber = $regNumberElement ? trim($regNumberElement->textContent) : '';

        if (empty($regNumber)) {
            // Try alternative way of extracting registration number
            $courseDetailsElement = $xpath->query('.//div[@class="class-info"]', $courseLi)->item(0);
            if ($courseDetailsElement) {
                $regNumberAlternative = $xpath->query('.//dt[contains(text(), "' . $regNumberText . '")]/following-sibling::dd[1]', $courseDetailsElement)->item(0);
                if ($regNumberAlternative) {
                    $regNumber = trim($regNumberAlternative->textContent);
                    echo "Found registration number using alternative method: $regNumber\n";
                }
            }
        }

        if (empty($regNumber)) {
            echo "Warning: No Registration Number found for course: $courseName\n";
            // Generate a temporary number based on course name
            $regNumber = 'NO_REGNUMBER_' . md5($courseName . '_' . $language);
        }

        $courseData['reg_number'] = $regNumber;

        // Extract credits
        $creditsText = ($language == 'ja') ? "単位" : "Unit";
        $creditsElement = $xpath->query('.//dt[contains(text(), "' . $creditsText . '")]/following-sibling::dd[1]', $courseLi)->item(0);
        $credits = $creditsElement ? trim($creditsElement->textContent) : 'Unknown Credits';

        // Clean up credits based on language
        if ($language == 'ja') {
            // Make sure Japanese credits have 単位 in them
            if (strpos($credits, '単位') === false) {
                // Try to extract just the number
                if (preg_match('/(\d+)/', $credits, $matches)) {
                    $credits = $matches[1] . '単位';
                } else {
                    $credits = '2単位'; // Default fallback
                }
            }
        } else {
            // Make sure English credits have proper Unit/Credits format
            if (strpos(strtolower($credits), 'unit') === false && strpos(strtolower($credits), 'credit') === false) {
                // Try to extract just the number
                if (preg_match('/(\d+)/', $credits, $matches)) {
                    $credits = $matches[1] . ' Unit';
                } else {
                    $credits = '2 Unit'; // Default fallback
                }
            }
        }

        $courseData['credits'] = $credits;

        // Extract year-semester based on language
        $yearSemesterText = ($language == 'ja') ? "開講年度・学期" : "Year/Semester";
        $yearSemesterElement = $xpath->query('.//dt[contains(text(), "' . $yearSemesterText . '")]/following-sibling::dd[1]', $courseLi)->item(0);
        $yearSemester = $yearSemesterElement ? trim($yearSemesterElement->textContent) : 'Unknown';
        $courseData['year_semester'] = $yearSemester;

        // Extract semester year from year-semester text (e.g., "2023 春学期" -> "2023")
        preg_match('/(\d{4})/', $yearSemester, $yearMatches);
        $year = isset($yearMatches[1]) ? $yearMatches[1] : "Unknown";

        if ($year == "Unknown") {
            // Try to extract year from URL
            if (strpos($url, '2023') !== false) {
                $year = "2023";
            } elseif (strpos($url, '2024') !== false) {
                $year = "2024";
            }
        }

        $courseData['year'] = $year;

        // Extract instructor(s) using our enhanced method
        $instructors = extractInstructorNames($xpath, $courseLi, $language);
        $courseData['instructors'] = $instructors;

        if (!empty($instructors)) {
            // For English syllabi, store professor names by registration number
            if ($language == 'en') {
                $key = $regNumber . '_' . $year;
                $englishProfessorNames[$key] = $instructors;
                echo "EN[$key]: Stored English professor names: " . implode(", ", $instructors) . "\n";
            }

            // Check if we already have this course by registration number, k-number, or by name and instructors
            $existingIndex = findCourseByNameAndInstructors($existingCourses, $courseName, $instructors, $regNumber, $year, $language, $courseData);

            if ($existingIndex >= 0) {
                // Course already exists, update with this language's information
                echo "Found existing course with name: \"$courseName\" / Reg#: $regNumber. Adding $language translation.\n";

                // Create a formatted instructor list for this language
                $languageInstructors = [];
                foreach ($instructors as $index => $instructor) {
                    $languageInstructors[($index + 1)] = $instructor;
                }

                // We're no longer using K-Numbers

                // Add the translation data (excluding instructors - they'll be merged separately)
                $existingCourses[$existingIndex]['translations'][$language] = [
                    'name' => $courseName,
                    'field' => $field,
                    'credits' => $credits
                ];
            } else {
                // Create a numbered array of instructors
                $numberedInstructors = [];
                foreach ($instructors as $index => $instructor) {
                    $numberedInstructors[($index + 1)] = $instructor;
                }

                // This is a new course
                $newCourse = [
                    'reg_number' => $regNumber,
                    'instructors' => $numberedInstructors,
                    'year' => $year,
                    'translations' => [
                        $language => [
                            'name' => $courseName,
                            'field' => $field,
                            'credits' => $credits
                        ]
                    ]
                ];

                // We're no longer tracking K-Numbers

                $newCourses[] = $newCourse;
                echo "Added new course: \"$courseName\" (Registration Number: $regNumber) with " . count($instructors) . " instructor(s)\n";
            }
        } else {
            // Handle courses with unknown instructors
            $unknownInstructors = ["1" => "Unknown Instructor"];

            // Check if course exists by reg number or by name and unknown instructor
            $existingIndex = findCourseByNameAndInstructors($existingCourses, $courseName, ["Unknown Instructor"], $regNumber, $year, $language, $courseData);

            if ($existingIndex >= 0) {
                // Update existing course
                $existingCourses[$existingIndex]['translations'][$language] = [
                    'course_name' => $courseName,
                    'field' => $field,
                    'credits' => $credits,
                    'instructors' => $unknownInstructors
                ];
            } else {
                // Add new course with unknown instructor
                $newCourse = [
                    'reg_number' => $regNumber,
                    'instructors' => $unknownInstructors,
                    'year' => $year,
                    'translations' => [
                        $language => [
                            'name' => $courseName,
                            'field' => $field,
                            'credits' => $credits
                        ]
                    ]
                ];

                $newCourses[] = $newCourse;
                echo "Added course: \"$courseName\" (Registration Number: $regNumber) with unknown instructor\n";
            }
        }
    }

    return [
        'new_courses' => $newCourses,
        'updated_existing' => $existingCourses,
        'course_counter' => $courseCounter
    ];
}

// Function to process all pages of a given URL
function processAllPages($baseUrl, $existingCourses)
{
    global $maxPagesPerUrl;

    echo "Starting to process URL: $baseUrl\n";

    // Extract language from URL
    $language = getLanguageFromUrl($baseUrl);
    echo "Detected language: $language\n";

    // Extract year from URL for reference
    preg_match('/search%5Byear%5D=(\d{4})/', $baseUrl, $yearMatches);
    $year = isset($yearMatches[1]) ? $yearMatches[1] : "Unknown";

    // Try to extract field from URL for reference
    if (strpos($baseUrl, 'search%5Bsfc_guide_title%5D=') !== false) {
        preg_match('/search%5Bsfc_guide_title%5D=([^&]+)/', $baseUrl, $fieldMatches);
        $urlField = isset($fieldMatches[1]) ? urldecode($fieldMatches[1]) : "Unknown";
    } elseif (strpos($baseUrl, 'search%5Btitle%5D=') !== false) {
        // For Data Science, we're searching by title
        preg_match('/search%5Btitle%5D=([^&]+)/', $baseUrl, $titleMatches);
        $urlField = isset($titleMatches[1]) ? urldecode($titleMatches[1]) : "Unknown";
    } else {
        $urlField = "Unknown";
    }

    echo "Processing URL for Year: $year, Target: $urlField, Language: $language\n";

    $allNewCourses = [];
    $updatedExistingCourses = $existingCourses;
    $currentPage = 1;
    $currentUrl = $baseUrl;
    $courseCounter = 0;

    while ($currentPage <= $maxPagesPerUrl) {
        echo "\nProcessing page $currentPage...\n";

        // Fetch the content
        $htmlContent = fetchUrlContent($currentUrl);

        if (!$htmlContent) {
            echo "Failed to fetch content for page $currentPage. Stopping pagination.\n";
            break;
        }

        echo "Successfully fetched page $currentPage. Content length: " . strlen($htmlContent) . " bytes\n";

        // Save the debug file with the actual HTML
        file_put_contents(__DIR__ . '/debug_' . $language . '_body.html', $htmlContent);

        // Parse courses from this page
        $result = parseCourses($htmlContent, $updatedExistingCourses, $courseCounter, $language, $currentUrl);
        $newCourses = $result['new_courses'];
        $updatedExistingCourses = $result['updated_existing'];
        $courseCounter = $result['course_counter'];

        // Add these courses to our collection
        $allNewCourses = array_merge($allNewCourses, $newCourses);

        // Ensure the new courses are added to our tracking collection to prevent duplicates in later pages
        $updatedExistingCourses = array_merge($updatedExistingCourses, $newCourses);

        // Check if there's a next page
        $dom = new DOMDocument();
        @$dom->loadHTML(mb_convert_encoding($htmlContent, 'HTML-ENTITIES', 'UTF-8'));
        $xpath = new DOMXPath($dom);

        // Look for the "next" link
        $nextPageElement = $xpath->query('//span[@class="next"]/a[@rel="next"]')->item(0);

        if ($nextPageElement) {
            $nextPageUrl = $nextPageElement->getAttribute('href');
            echo "Found next page link: $nextPageUrl\n";

            // Add base URL if the href is relative
            if (strpos($nextPageUrl, 'http') !== 0) {
                $baseUrlParts = parse_url($baseUrl);
                $baseUrlRoot = $baseUrlParts['scheme'] . '://' . $baseUrlParts['host'];
                $nextPageUrl = $baseUrlRoot . $nextPageUrl;
            }

            // Update for next iteration
            $currentUrl = $nextPageUrl;
            $currentPage++;

            // Add a short delay between requests
            echo "Waiting 2 seconds before fetching next page...\n";
            sleep(2);
        } else {
            echo "No next page found. This is the last page.\n";
            break;
        }
    }

    if ($currentPage > $maxPagesPerUrl) {
        echo "Reached maximum page limit ($maxPagesPerUrl). Stopping pagination.\n";
    }

    echo "Finished processing all pages for this URL. Found " . count($allNewCourses) . " new courses and updated "
        . (count($updatedExistingCourses) - count($existingCourses)) . " existing courses.\n";

    return [
        'language' => $language,
        'year' => $year,
        'new_courses' => $allNewCourses,
        'updated_existing' => $updatedExistingCourses
    ];
}

// Main execution

// Step 1: Load existing courses from JSON file
$existingCourses = [];
if (file_exists($jsonFilePath)) {
    $jsonContent = file_get_contents($jsonFilePath);
    if ($jsonContent) {
        $existingCourses = json_decode($jsonContent, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            echo "Warning: Error parsing existing JSON file: " . json_last_error_msg() . "\n";
            $existingCourses = [];
        } else {
            echo "Loaded " . count($existingCourses) . " existing courses from $jsonFilePath\n";
        }
    }
}

// Step 2: Process URLs in pairs (Japanese and English versions of same courses)
$allCourses = $existingCourses;

// Group URLs by pairs (Japanese and English versions should be adjacent)
$urlPairs = [];
for ($i = 0; $i < count($urlsToScrape); $i += 2) {
    // Make sure we have enough URLs left
    if ($i + 1 < count($urlsToScrape)) {
        // Determine which URL is Japanese and which is English
        $jaUrl = strpos($urlsToScrape[$i], 'locale=ja') !== false ? $urlsToScrape[$i] : $urlsToScrape[$i + 1];
        $enUrl = strpos($urlsToScrape[$i], 'locale=en') !== false ? $urlsToScrape[$i] : $urlsToScrape[$i + 1];

        $urlPairs[] = [
            'ja' => $jaUrl,
            'en' => $enUrl
        ];
    } else {
        // Handle odd number of URLs
        $lastUrl = $urlsToScrape[$i];
        $urlPairs[] = [
            'ja' => strpos($lastUrl, 'locale=ja') !== false ? $lastUrl : '',
            'en' => strpos($lastUrl, 'locale=en') !== false ? $lastUrl : ''
        ];
    }
}

// Process each URL pair
foreach ($urlPairs as $index => $pair) {
    echo "\n" . str_repeat("=", 80) . "\n";
    echo "[URL Pair " . ($index + 1) . "/" . count($urlPairs) . "]\n";

    // First process Japanese version
    $jaResults = null;
    if (!empty($pair['ja'])) {
        echo "Processing Japanese version...\n";
        $jaResults = processAllPages($pair['ja'], $allCourses);
        echo "Found " . count($jaResults['new_courses']) . " new Japanese courses.\n";
    }

    // Next process English version
    $enResults = null;
    if (!empty($pair['en'])) {
        echo "Processing English version...\n";
        $enResults = processAllPages($pair['en'], $allCourses);
        echo "Found " . count($enResults['new_courses']) . " new English courses.\n";
    }

    // Merge Japanese and English data for the same courses
    if ($jaResults && $enResults) {
        echo "Matching and merging Japanese and English courses...\n";

        // Print debug information about the English courses first
        echo "\nDEBUG: English courses info:\n";
        foreach ($enResults['new_courses'] as $enCourse) {
            $regNum = $enCourse['reg_number'] ?? 'unknown';
            $courseName = isset($enCourse['translations']['en']['name']) ?
                $enCourse['translations']['en']['name'] :
                'unnamed';
            $instrCount = isset($enCourse['instructors']) ? count($enCourse['instructors']) : 0;

            echo "EN Course: $regNum - '$courseName' with $instrCount instructors\n";
            if (isset($enCourse['instructors']) && is_array($enCourse['instructors'])) {
                foreach ($enCourse['instructors'] as $idx => $instr) {
                    echo "  EN Instructor $idx: $instr\n";
                }
            }
        }

        // Map all Japanese courses by registration number
        $jaCoursesByRegNum = [];
        foreach ($jaResults['new_courses'] as $jaCourse) {
            $regNum = $jaCourse['reg_number'] ?? '';
            if (!empty($regNum)) {
                $jaCoursesByRegNum[$regNum] = $jaCourse;
            }
        }

        // Go through English courses and look for matches
        $mergedCourses = [];
        foreach ($enResults['new_courses'] as $enCourse) {
            $regNum = $enCourse['reg_number'] ?? '';

            // Check if there's a matching Japanese course
            if (!empty($regNum) && isset($jaCoursesByRegNum[$regNum])) {
                $jaCourse = $jaCoursesByRegNum[$regNum];

                // Debug information about the English course we're merging
                echo "\nMerging JP and EN for course reg# $regNum:\n";
                if (isset($enCourse['translations']['en']['name'])) {
                    echo "  EN name: " . $enCourse['translations']['en']['name'] . "\n";
                }
                if (isset($jaCourse['translations']['ja']['name'])) {
                    echo "  JP name: " . $jaCourse['translations']['ja']['name'] . "\n";
                }

                // Debug instructor information
                echo "  EN instructors: ";
                if (isset($enCourse['instructors']) && is_array($enCourse['instructors'])) {
                    echo implode(", ", $enCourse['instructors']) . "\n";
                } else {
                    echo "none\n";
                }

                echo "  JP instructors: ";
                if (isset($jaCourse['instructors']) && is_array($jaCourse['instructors'])) {
                    echo implode(", ", $jaCourse['instructors']) . "\n";
                } else {
                    echo "none\n";
                }

                // Create a merged course with data from both languages
                $mergedCourse = [
                    'reg_number' => $regNum,
                    'year' => $enCourse['year'] ?? $jaCourse['year'],
                    'translations' => [
                        'ja' => isset($jaCourse['translations']['ja']) ? $jaCourse['translations']['ja'] : [],
                        'en' => isset($enCourse['translations']['en']) ? $enCourse['translations']['en'] : []
                    ],
                    'instructors' => array_merge(
                        $jaCourse['instructors'] ?? [],
                        $enCourse['instructors'] ?? []
                    )
                ];

                $mergedCourses[] = $mergedCourse;
                echo "  Merged course: $regNum\n";

                // Remove this course from the Japanese map to mark it as processed
                unset($jaCoursesByRegNum[$regNum]);
            } else {
                // English-only course
                $mergedCourses[] = $enCourse;
                echo "  English-only course: $regNum\n";
            }
        }

        // Add any remaining Japanese courses that didn't have an English match
        foreach ($jaCoursesByRegNum as $regNum => $jaCourse) {
            $mergedCourses[] = $jaCourse;
            echo "  Japanese-only course: $regNum\n";
        }

        // Add these merged courses to our collection
        $allCourses = array_merge($allCourses, $mergedCourses);
        echo "Added " . count($mergedCourses) . " courses after merging Japanese and English data.\n";
    } elseif ($jaResults) {
        // Only Japanese courses available
        $allCourses = array_merge($allCourses, $jaResults['new_courses']);
        echo "Added " . count($jaResults['new_courses']) . " Japanese-only courses.\n";
    } elseif ($enResults) {
        // Only English courses available
        $allCourses = array_merge($allCourses, $enResults['new_courses']);
        echo "Added " . count($enResults['new_courses']) . " English-only courses.\n";
    }

    echo "Current total unique courses: " . count($allCourses) . "\n";

    // Add a delay between URL pairs
    if ($index < count($urlPairs) - 1) {
        echo "Waiting 3 seconds before processing next URL pair...\n";
        sleep(3);
    }
}

echo "\n" . str_repeat("=", 80) . "\n";
echo "POST-PROCESSING: Preparing courses for website integration\n";

// The data structure should already properly contain both Japanese and English versions
// of the same course when they have the same registration number and year
// Let's count the stats
$fullTranslationCount = 0;
$jaOnlyCount = 0;
$enOnlyCount = 0;

// First, find courses that appear in both 2023 and 2024
echo "Finding courses available in multiple years...\n";
$coursesByNameAndInstructor = [];
$multiyearCourses = [];
$combinedCourses = [];
$indicesToRemove = [];

// Group courses by name and instructors to identify courses available in multiple years
foreach ($allCourses as $index => $course) {
    $hasJa = isset($course['translations']['ja']);
    $hasEn = isset($course['translations']['en']);

    // Check if we have name field in either translation
    if ($hasJa && isset($course['translations']['ja']['course_name'])) {
        $courseName = $course['translations']['ja']['course_name'];
    } elseif ($hasJa && isset($course['translations']['ja']['name'])) {
        $courseName = $course['translations']['ja']['name'];
    } elseif ($hasEn && isset($course['translations']['en']['course_name'])) {
        $courseName = $course['translations']['en']['course_name'];
    } elseif ($hasEn && isset($course['translations']['en']['name'])) {
        $courseName = $course['translations']['en']['name'];
    } else {
        // If no name is found, use registration number
        $courseName = "Unknown Course: " . $course['reg_number'];
    }

    // Create a key from course name and instructors to identify the "same" course
    $instructorNames = [];
    if (isset($course['instructors']) && is_array($course['instructors'])) {
        foreach ($course['instructors'] as $instructor) {
            $instructorNames[] = $instructor;
        }
        sort($instructorNames); // Sort to ensure consistent order
    }

    // Try to extract professors differently
    $professorNames = [];
    if (isset($course['professors']) && is_array($course['professors'])) {
        foreach ($course['professors'] as $professor) {
            if (isset($professor['name']['ja'])) {
                $professorNames[] = $professor['name']['ja'];
            } elseif (isset($professor['name']['en'])) {
                $professorNames[] = $professor['name']['en'];
            }
        }
        sort($professorNames); // Sort to ensure consistent order
    }

    // Create key (use mb_strtolower for UTF-8 support)
    // Use either instructors or professors, depending on which is available
    if (!empty($professorNames)) {
        $key = mb_strtolower($courseName, 'UTF-8') . '|' . implode(',', $professorNames);
    } else {
        $key = mb_strtolower($courseName, 'UTF-8') . '|' . implode(',', $instructorNames);
    }

    if (!isset($coursesByNameAndInstructor[$key])) {
        $coursesByNameAndInstructor[$key] = [];
    }

    $coursesByNameAndInstructor[$key][] = [
        'index' => $index,
        'year' => $course['year'],
        'reg_number' => $course['reg_number']
    ];
}

// Combine courses that appear in multiple years
foreach ($coursesByNameAndInstructor as $key => $courseInstances) {
    if (count($courseInstances) > 1) {
        $years = array_column($courseInstances, 'year');
        $uniqueYears = array_unique($years);

        if (count($uniqueYears) > 1) {
            // This course appears in multiple years
            // Take the first instance as the base and combine years
            $baseInstanceIndex = $courseInstances[0]['index'];
            $baseCourse = $allCourses[$baseInstanceIndex];

            // Combine years into a single string like "2023&2024"
            sort($uniqueYears);
            $combinedYear = implode('&', $uniqueYears);

            // Create a combined course with information from the base course
            $combinedCourse = $baseCourse;
            $combinedCourse['year'] = $combinedYear;
            $combinedCourse['available_years'] = $uniqueYears;

            // Add this combined course to our special array
            $combinedCourses[] = $combinedCourse;

            // Mark all instances for removal from the original array
            foreach ($courseInstances as $instance) {
                $indicesToRemove[] = $instance['index'];
            }

            echo "Combined course '$key' available in years: " . implode(', ', $uniqueYears) . "\n";
            $multiyearCourses[] = $key;
        }
    }
}

// Remove original courses that are now part of combined courses
$indicesToRemove = array_unique($indicesToRemove);
rsort($indicesToRemove); // Sort in reverse order to safely remove items

foreach ($indicesToRemove as $index) {
    unset($allCourses[$index]);
}

// Add the combined courses to the main array
$allCourses = array_merge($allCourses, $combinedCourses);

echo "Combined " . count($multiyearCourses) . " courses that appear in multiple years.\n";

// Now count translation stats
foreach ($allCourses as $index => $course) {
    $hasJa = isset($course['translations']['ja']);
    $hasEn = isset($course['translations']['en']);

    if ($hasJa && $hasEn) {
        $fullTranslationCount++;
    } elseif ($hasJa) {
        $jaOnlyCount++;
        // No need to create a fake English translation since the real one should have been linked
        // If we get here, it means the English version wasn't found in the source
        echo "Warning: Course with reg# {$course['reg_number']} from {$course['year']} only has Japanese translation.\n";
    } elseif ($hasEn) {
        $enOnlyCount++;
        // No need to create a fake Japanese translation since the real one should have been linked
        // If we get here, it means the Japanese version wasn't found in the source
        echo "Warning: Course with reg# {$course['reg_number']} from {$course['year']} only has English translation.\n";
    }
}

echo "\n" . str_repeat("=", 80) . "\n";
echo "SUMMARY:\n";
echo "Total courses collected: " . count($allCourses) . "\n";
echo "Courses with both Japanese and English translations: $fullTranslationCount\n";
echo "Courses with only Japanese translation: $jaOnlyCount\n";
echo "Courses with only English translation: $enOnlyCount\n";
echo "Courses available in multiple years: " . count($multiyearCourses) . "\n";
echo "Each course with both translations can be displayed in either language on the website.\n";
echo "Courses available in multiple years are marked with available_years property listing all years offered.\n";

// Format the data for the Rate My Teacher website
// Convert to a structure that's compatible with the website's needs
$formattedCourses = [];

// This line was causing issues since ob_start() wasn't called
// Removing it

foreach ($allCourses as $course) {
    // Only process courses that have complete information
    if (!isset($course['translations']['ja']) || !isset($course['translations']['en'])) {
        // If we're missing either language, use what we have for both languages
        if (isset($course['translations']['ja']) && !isset($course['translations']['en'])) {
            // Create an English translation based on the Japanese version
            echo "Notice: Creating English translation from Japanese for course {$course['reg_number']}.\n";

            // Start with a copy of the Japanese translation
            $course['translations']['en'] = [];

            // For each field in Japanese translation, create an English equivalent
            foreach ($course['translations']['ja'] as $field => $value) {
                // Only copy values if field doesn't already exist
                if (!isset($course['translations']['en'][$field])) {
                    $course['translations']['en'][$field] = $value;
                }
            }

            // Make sure we'll translate these fields later, and not just use Japanese text in English fields

        } else if (isset($course['translations']['en']) && !isset($course['translations']['ja'])) {
            // Create a Japanese translation based on the English version  
            echo "Notice: Creating Japanese translation from English for course {$course['reg_number']}.\n";

            // For a proper website, we'd want to maintain both languages separately
            // But for now, we'll create a Japanese version from the English
            $course['translations']['ja'] = [];

            // For each field in English translation, create a Japanese version
            foreach ($course['translations']['en'] as $field => $value) {
                // Only copy values if field doesn't already exist
                if (!isset($course['translations']['ja'][$field])) {
                    $course['translations']['ja'][$field] = $value;
                }
            }

            // We'll need to check for and fix English text in Japanese fields later
        } else {
            // Skip courses with no translations at all
            echo "Warning: Skipping course with no translations: {$course['reg_number']}.\n";
            continue;
        }
    }

    // Fix the translations to ensure English translations have English text
    // First make sure all necessary keys exist in translations to avoid undefined array key errors
    foreach (['ja', 'en'] as $lang) {
        if (isset($course['translations'][$lang])) {
            if (!isset($course['translations'][$lang]['name']) && isset($course['translations'][$lang]['course_name'])) {
                $course['translations'][$lang]['name'] = $course['translations'][$lang]['course_name'];
            } elseif (!isset($course['translations'][$lang]['name'])) {
                $course['translations'][$lang]['name'] = "Unknown Course " . $course['reg_number'];
            }

            if (!isset($course['translations'][$lang]['field'])) {
                $course['translations'][$lang]['field'] = $lang == 'en' ? "Unknown Field" : "不明な分野";
            }

            if (!isset($course['translations'][$lang]['credits'])) {
                $course['translations'][$lang]['credits'] = $lang == 'en' ? "Unknown Credits" : "不明な単位";
            }
        }
    }

    // First, check if Japanese fields accidentally contain English text
    if (isset($course['translations']['ja'])) {
        // Map of common English course names to Japanese
        $englishToJapaneseTranslations = [
            'Basic Statistics' => '統計基礎',
            'Statistics' => '統計',
            'Probability' => '確率',
            'Data Science' => 'データサイエンス',
            'Machine Learning' => '機械学習',
            'Programming' => 'プログラミング',
            'Computer' => 'コンピュータ',
            'Information' => '情報',
            'Research' => '研究',
            'Mathematics' => '数学',
            'Physics' => '物理',
            'Chemistry' => '化学',
            'Biology' => '生物',
            'Psychology' => '心理',
            'Economics' => '経済',
            'Business' => '経営',
            'Law' => '法律',
            'Political Science' => '政治',
            'Sociology' => '社会',
            'History' => '歴史',
            'Literature' => '文学',
            'Linguistics' => '言語',
            'Philosophy' => '哲学',
            'Art' => '芸術',
            'Music' => '音楽',
            'Basic' => '基礎',
            'Applied' => '応用',
            'Special' => '特別',
            'Advanced' => '上級',
            'Practice' => '実践',
            'Introduction' => '概論',
            'Theory' => '理論',
            'Algorithms' => 'アルゴリズム',
            'Networks' => 'ネットワーク',
            'Security' => 'セキュリティ',
            'Software' => 'ソフトウェア',
            'Systems' => 'システム',
            'Database' => 'データベース',
            'Web' => 'ウェブ',
            'Mobile' => 'モバイル',
            'Artificial Intelligence' => '人工知能',
            'Robotics' => 'ロボット',
            'Graphics' => 'グラフィックス',
            'Media' => 'メディア',
            'Design' => 'デザイン',
            'Communications' => '通信',
            'Game' => 'ゲーム',
            'Human' => '人間',
            'Language Processing' => '言語処理',
            'Image Processing' => '画像処理',
            'Speech Processing' => '音声処理',
            'Big Data' => 'ビッグデータ',
            'Cloud' => 'クラウド',
            'Internet' => 'インターネット',
            'Cyber' => 'サイバー',
            'Analysis' => '分析',
            'Optimization' => '最適化',
            'Cognition' => '認知',
            'Vision' => '視覚',
            'Auditory' => '聴覚',
            'Foundation Course' => '基盤科目',
            'Data Science 1' => 'データサイエンス1',
            'Language Communication' => '言語コミュニケーション',
            'Interdisciplinary' => '共通科目',
            'Policy Management' => '総合政策系',
            'Environment And Information Studies' => '環境情報系',
            'Special Subjects' => '特設科目',
            'Unit' => '単位',
            'credits' => '単位',
            'Korean' => '朝鮮語',
            'Skill' => 'スキル',
            'Listening' => '聴解',
            'Outline' => '概論',
            'Earth' => '地球',
            'Environment' => '環境'
        ];

        // Check Japanese fields for English text and translate
        if (
            isset($course['translations']['ja']['name']) &&
            !preg_match('/[\p{Hiragana}\p{Katakana}\p{Han}]/u', $course['translations']['ja']['name'])
        ) {

            $englishName = $course['translations']['ja']['name'];
            $japaneseName = $englishName;

            // Try to translate using our English to Japanese dictionary
            foreach ($englishToJapaneseTranslations as $en => $ja) {
                $japaneseName = str_ireplace($en, $ja, $japaneseName);
            }

            // If after translation it still doesn't have any Japanese characters, 
            // and we have the English name, use "コース" + ID
            if (!preg_match('/[\p{Hiragana}\p{Katakana}\p{Han}]/u', $japaneseName)) {
                $japaneseName = "コース " . $course['course_id'];
            }

            $course['translations']['ja']['name'] = $japaneseName;
            echo "  Translated English name in Japanese field: $englishName -> $japaneseName\n";
        }

        // Translate field/department if needed
        if (
            isset($course['translations']['ja']['field']) &&
            !preg_match('/[\p{Hiragana}\p{Katakana}\p{Han}]/u', $course['translations']['ja']['field'])
        ) {

            $englishField = $course['translations']['ja']['field'];
            $japaneseField = $englishField;

            // Try to translate using our dictionary
            foreach ($englishToJapaneseTranslations as $en => $ja) {
                $japaneseField = str_ireplace($en, $ja, $japaneseField);
            }

            // If it still has no Japanese characters, use a default
            if (!preg_match('/[\p{Hiragana}\p{Katakana}\p{Han}]/u', $japaneseField)) {
                $japaneseField = "基盤科目";
            }

            $course['translations']['ja']['field'] = $japaneseField;
            echo "  Translated English field in Japanese field: $englishField -> $japaneseField\n";
        }

        // Translate credits if needed
        if (
            isset($course['translations']['ja']['credits']) &&
            !preg_match('/[\p{Hiragana}\p{Katakana}\p{Han}]/u', $course['translations']['ja']['credits'])
        ) {

            // Convert "2 credits" or "2 Unit" to "2単位"
            if (preg_match('/(\d+)\s*(credits|Units|Unit)/i', $course['translations']['ja']['credits'], $matches)) {
                $creditNum = $matches[1];
                $course['translations']['ja']['credits'] = $creditNum . "単位";
                echo "  Translated credits: {$course['translations']['ja']['credits']} -> {$creditNum}単位\n";
            }
        }
    }

    // Process English fields 
    if (isset($course['translations']['en'])) {
        // No translation maps - we'll use direct scraping instead

        // We need to keep field translations because these are consistent in the syllabus
        // and need to be mapped between languages

        // Post-process course names to ensure proper language and fix cross-language issues
        // 1. Fix English course names that contain Japanese characters
        if (isset($course['translations']['en']) && isset($course['translations']['en']['name'])) {
            $enName = $course['translations']['en']['name'];
            $hasJapaneseChars = preg_match('/[\p{Hiragana}\p{Katakana}\p{Han}]/u', $enName);
            $isCourseId = preg_match('/Course ID:\s*\d+/', $enName) || preg_match('/^Course \d+$/', $enName);

            // Only fix the English name if it's problematic
            if ($hasJapaneseChars || $isCourseId) {
                echo "Found problematic English name: '$enName'\n";

                // Try to find a matching course by registration number
                if (isset($course['reg_number'])) {
                    $regNumber = $course['reg_number'];
                    $courseYear = $course['year'];

                    echo "Looking for English name for course with registration number: $regNumber (year: $courseYear)\n";

                    // Check all existing courses for one with the same registration number
                    foreach ($existingCourses as $existingCourse) {
                        if (
                            $existingCourse['reg_number'] === $regNumber &&
                            $existingCourse['year'] === $courseYear &&
                            isset($existingCourse['translations']['en']) &&
                            isset($existingCourse['translations']['en']['name'])
                        ) {

                            $existingEnName = $existingCourse['translations']['en']['name'];

                            // Check if the existing name is better
                            if (
                                !preg_match('/Course ID:|^Course \d+$/', $existingEnName) &&
                                !preg_match('/[\p{Hiragana}\p{Katakana}\p{Han}]/u', $existingEnName) &&
                                strlen($existingEnName) > 3
                            ) {

                                echo "Found better English name: '$existingEnName'\n";
                                $course['translations']['en']['name'] = $existingEnName;
                                break;
                            }
                        }
                    }
                }

                // If we still have a problematic name and have a Japanese name available
                if (($hasJapaneseChars || $isCourseId) &&
                    isset($course['translations']['ja']) &&
                    isset($course['translations']['ja']['name'])
                ) {

                    $jaName = $course['translations']['ja']['name'];

                    // Try to find a match by Japanese name in existing courses
                    $matchFound = false;
                    foreach ($existingCourses as $existingCourse) {
                        if (
                            isset($existingCourse['translations']['ja']) &&
                            isset($existingCourse['translations']['ja']['name']) &&
                            isset($existingCourse['translations']['en']) &&
                            isset($existingCourse['translations']['en']['name'])
                        ) {

                            $existingJaName = $existingCourse['translations']['ja']['name'];
                            $existingEnName = $existingCourse['translations']['en']['name'];

                            // If Japanese names match and English name is good
                            if (
                                $existingJaName === $jaName &&
                                !preg_match('/Course ID:|^Course \d+$/', $existingEnName) &&
                                !preg_match('/[\p{Hiragana}\p{Katakana}\p{Han}]/u', $existingEnName) &&
                                strlen($existingEnName) > 3
                            ) {

                                echo "Found match by Japanese name: $jaName -> $existingEnName\n";
                                $course['translations']['en']['name'] = $existingEnName;
                                $matchFound = true;
                                break;
                            }
                        }
                    }

                    // As a last resort, use the Japanese name (better than Course ID)
                    if (!$matchFound && $isCourseId) {
                        echo "Using Japanese name as fallback: '$jaName'\n";
                        $course['translations']['en']['name'] = $jaName;
                    }
                }
            }
        }

        // 2. Fix Japanese course names that contain only English characters
        if (isset($course['translations']['ja']) && isset($course['translations']['ja']['name'])) {
            $jaName = $course['translations']['ja']['name'];
            $hasJapaneseChars = preg_match('/[\p{Hiragana}\p{Katakana}\p{Han}]/u', $jaName);

            // If the Japanese name doesn't have any Japanese characters
            if (!$hasJapaneseChars && strlen($jaName) > 3) {
                echo "Found Japanese name with only English characters: '$jaName'\n";

                // Look for a matching course with proper Japanese name
                if (isset($course['reg_number'])) {
                    $regNumber = $course['reg_number'];

                    foreach ($existingCourses as $existingCourse) {
                        if (
                            $existingCourse['reg_number'] === $regNumber &&
                            $existingCourse['year'] === $course['year'] &&
                            isset($existingCourse['translations']['ja']) &&
                            isset($existingCourse['translations']['ja']['name'])
                        ) {

                            $existingJaName = $existingCourse['translations']['ja']['name'];

                            // Only use if it actually has Japanese characters
                            if (preg_match('/[\p{Hiragana}\p{Katakana}\p{Han}]/u', $existingJaName)) {
                                echo "Found proper Japanese name: '$existingJaName'\n";
                                $course['translations']['ja']['name'] = $existingJaName;
                                break;
                            }
                        }
                    }
                }

                // If we still don't have a proper Japanese name and have a good English name
                if (
                    !preg_match('/[\p{Hiragana}\p{Katakana}\p{Han}]/u', $course['translations']['ja']['name']) &&
                    isset($course['translations']['en']) &&
                    isset($course['translations']['en']['name'])
                ) {

                    $enName = $course['translations']['en']['name'];

                    // Check if English name seems valid
                    if (
                        !preg_match('/Course ID:|^Course \d+$/', $enName) &&
                        !preg_match('/[\p{Hiragana}\p{Katakana}\p{Han}]/u', $enName) &&
                        strlen($enName) > 3
                    ) {

                        // In this rare case, we keep the English name in the Japanese field too
                        // This often happens with course names that are proper nouns or standard terms
                        echo "English-only course name, using in both languages: '$enName'\n";
                    }
                }
            }
        }

        // For fields, we're using exactly what's in the HTML without any transformations
        // This ensures consistency with the source data

        // Translate credits from "2単位" to "2 credits"
        if (
            isset($course['translations']['en']['credits']) &&
            preg_match('/(\d+)単位/', $course['translations']['en']['credits'], $matches)
        ) {
            $creditNum = $matches[1];
            $course['translations']['en']['credits'] = $creditNum . " credits";
        }
    }

    // Create a new course structure that works with Rate My Teacher
    // Removed description field as requested
    $formattedCourse = [
        'course_id' => $course['reg_number'],
        'year' => $course['year'],
        'translations' => [
            'ja' => [
                'name' => isset($course['translations']['ja']['name']) ? $course['translations']['ja']['name']
                    : (isset($course['translations']['ja']['course_name']) ? $course['translations']['ja']['course_name']
                        : ''),
                'field' => isset($course['translations']['ja']['field']) ? $course['translations']['ja']['field'] : '基盤科目',
                'credits' => isset($course['translations']['ja']['credits']) ? $course['translations']['ja']['credits'] : '2単位'
            ],
            'en' => [
                'name' => isset($course['translations']['en']['name']) ? $course['translations']['en']['name']
                    : (isset($course['translations']['en']['course_name']) ? $course['translations']['en']['course_name']
                        : 'Course ' . $course['reg_number']),
                'field' => isset($course['translations']['en']['field']) ? $course['translations']['en']['field'] : 'Foundation Course',
                'credits' => isset($course['translations']['en']['credits']) ? $course['translations']['en']['credits'] : '2 credits'
            ]
        ],
        'professors' => []
    ];

    // If Japanese name is empty or just "コース", try to use the English name or proper Japanese title
    if (
        empty($formattedCourse['translations']['ja']['name']) ||
        $formattedCourse['translations']['ja']['name'] == 'コース ' . $course['reg_number'] ||
        $formattedCourse['translations']['ja']['name'] == 'コース'
    ) {

        // Check if we can find a proper Japanese name from another course with the same registration number
        $foundProperJaName = false;
        foreach ($existingCourses as $existingCourse) {
            if (
                $existingCourse['reg_number'] === $course['reg_number'] &&
                isset($existingCourse['translations']['ja']) &&
                isset($existingCourse['translations']['ja']['name']) &&
                !empty($existingCourse['translations']['ja']['name']) &&
                $existingCourse['translations']['ja']['name'] != 'コース' &&
                $existingCourse['translations']['ja']['name'] != 'コース ' . $course['reg_number']
            ) {

                $formattedCourse['translations']['ja']['name'] = $existingCourse['translations']['ja']['name'];
                echo "Found proper Japanese name: {$existingCourse['translations']['ja']['name']}\n";
                $foundProperJaName = true;
                break;
            }
        }

        // If we still don't have a proper Japanese name, try to use the English one
        if (
            !$foundProperJaName && isset($formattedCourse['translations']['en']['name']) &&
            !empty($formattedCourse['translations']['en']['name']) &&
            $formattedCourse['translations']['en']['name'] != 'Course ' . $course['reg_number']
        ) {

            $formattedCourse['translations']['ja']['name'] = $formattedCourse['translations']['en']['name'];
            echo "Using English name for Japanese: {$formattedCourse['translations']['en']['name']}\n";
        }
    }

    // If we still have an empty Japanese name, use a fallback
    if (empty($formattedCourse['translations']['ja']['name'])) {
        $formattedCourse['translations']['ja']['name'] = "コース " . $course['reg_number'];
    }

    // No transformation of course names - use exactly what's in the HTML
    // This ensures names including any special designations like (GIGA), 
    // course numbers, EC05(Reading), etc. are preserved as-is

    // We're no longer using K-Numbers

    // Add the available_years attribute if this course occurs in multiple years
    if (isset($course['available_years'])) {
        $formattedCourse['available_years'] = $course['available_years'];
    }

    // Get department in both languages for professors
    $jaDepartment = isset($course['translations']['ja']['field']) ?
        $course['translations']['ja']['field'] : '';
    $enDepartment = isset($course['translations']['en']['field']) ?
        $course['translations']['en']['field'] : '';

    // Get professor information from both language translations if available
    $jaInstructors = [];
    $enInstructors = [];

    // Extract Japanese instructors if available
    if (isset($course['translations']['ja']) && isset($course['translations']['ja']['instructors'])) {
        foreach ($course['translations']['ja']['instructors'] as $instructor) {
            $jaInstructors[] = $instructor;
        }
    }

    // Extract English instructors if available
    if (isset($course['translations']['en']) && isset($course['translations']['en']['instructors'])) {
        foreach ($course['translations']['en']['instructors'] as $instructor) {
            $enInstructors[] = $instructor;
        }
    }

    // If we have no instructor data from translations, use the course's main instructors
    if (empty($jaInstructors) && empty($enInstructors)) {
        foreach ($course['instructors'] as $instructor) {
            // Check if instructor name contains Japanese characters
            if (preg_match('/[\p{Hiragana}\p{Katakana}\p{Han}]/u', $instructor)) {
                $jaInstructors[] = $instructor;
            } else {
                $enInstructors[] = $instructor;
            }
        }
    }

    // Determine how many professors we have
    $professorCount = max(count($jaInstructors), count($enInstructors));

    // If we have no professors at all, add at least one "Unknown" entry
    if ($professorCount == 0) {
        $formattedCourse['professors'][] = [
            'name' => [
                'ja' => '不明な教授',
                'en' => 'Unknown Professor'
            ],
            'department' => [
                'ja' => $jaDepartment,
                'en' => $enDepartment
            ]
        ];
    } else {
        // Add paired professors from both languages
        // Making sure to maintain the order since the syllabus should have instructors in the same order
        for ($i = 0; $i < $professorCount; $i++) {
            $jaName = isset($jaInstructors[$i]) ? $jaInstructors[$i] : '';
            $enName = isset($enInstructors[$i]) ? $enInstructors[$i] : '';

            // Simply use the names as they are from the source HTML
            // If one language is missing, use the other language's name

            // Use department values exactly as they are in the source HTML
            // No transformations or mappings

            // Check if we have both names or need to use one for both languages
            if (empty($jaName) && !empty($enName)) {
                $jaName = $enName; // Use English name for Japanese if Japanese is missing
            } else if (empty($enName) && !empty($jaName)) {
                $enName = $jaName; // Use Japanese name for English if English is missing
            }

            // Debug information to see what professor names we're including
            echo "Professor: Japanese name = '$jaName', English name = '$enName'\n";

            $formattedCourse['professors'][] = [
                'name' => [
                    'ja' => $jaName,
                    'en' => $enName
                ],
                'department' => [
                    'ja' => $jaDepartment,
                    'en' => $enDepartment
                ]
            ];
        }
    }

    $formattedCourses[] = $formattedCourse;
}

// Final post-processing to fix cross-language professor names
echo "\nFinal check: ensuring professor names are correctly matched across languages...\n";

// No post-processing of professors - use values exactly as they are in the source HTML
echo "Using professor names exactly as provided in the syllabus HTML.\n";

// Save the combined data with proper encoding for Japanese characters
$jsonOptions = JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES;

// Fix encoding issues by ensuring all text is properly encoded
foreach ($formattedCourses as &$course) {
    // Clean course translations
    foreach (['ja', 'en'] as $lang) {
        if (isset($course['translations'][$lang])) {
            foreach ($course['translations'][$lang] as $key => $value) {
                // Re-encode to ensure valid UTF-8
                $course['translations'][$lang][$key] = mb_convert_encoding($value, 'UTF-8', 'UTF-8');
            }
        }
    }

    // Clean professor names
    foreach ($course['professors'] as &$professor) {
        foreach (['ja', 'en'] as $lang) {
            if (isset($professor['name'][$lang])) {
                // Re-encode to ensure valid UTF-8
                $professor['name'][$lang] = mb_convert_encoding($professor['name'][$lang], 'UTF-8', 'UTF-8');
            }
            if (isset($professor['department'][$lang])) {
                // Re-encode to ensure valid UTF-8
                $professor['department'][$lang] = mb_convert_encoding($professor['department'][$lang], 'UTF-8', 'UTF-8');
            }
        }

        // If we have an encoding issue in Japanese name, replace with English
        if (
            isset($professor['name']['ja']) &&
            (strpos($professor['name']['ja'], '�') !== false || strlen($professor['name']['ja']) <= 1) &&
            isset($professor['name']['en']) &&
            strpos($professor['name']['en'], '�') === false &&
            strlen($professor['name']['en']) > 1
        ) {

            echo "Fixing Japanese name with encoding issues: " . $professor['name']['ja'] . " -> " . $professor['name']['en'] . "\n";
            $professor['name']['ja'] = $professor['name']['en'];
        }

        // If we have an encoding issue in English name, replace with Japanese
        if (
            isset($professor['name']['en']) &&
            (strpos($professor['name']['en'], '�') !== false || strlen($professor['name']['en']) <= 1) &&
            isset($professor['name']['ja']) &&
            strpos($professor['name']['ja'], '�') === false &&
            strlen($professor['name']['ja']) > 1
        ) {

            echo "Fixing English name with encoding issues: " . $professor['name']['en'] . " -> " . $professor['name']['ja'] . "\n";
            $professor['name']['en'] = $professor['name']['ja'];
        }
    }
}

// Load the existing file to merge with new data
$existingJsonContent = ['courses' => [], 'meta' => []];
if (file_exists($jsonFilePath)) {
    $existingJson = file_get_contents($jsonFilePath);
    if ($existingJson) {
        $tempContent = json_decode($existingJson, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            echo "Warning: Error parsing existing JSON file: " . json_last_error_msg() . "\n";
        } elseif (!isset($tempContent['courses'])) {
            echo "Warning: Existing JSON file has incorrect structure (missing 'courses' key).\n";
        } else {
            $existingJsonContent = $tempContent;
            echo "Loaded existing JSON with " . count($existingJsonContent['courses']) . " courses.\n";
        }
    }
}

// Make sure the existingJsonContent has the expected structure
if (!isset($existingJsonContent['courses'])) {
    $existingJsonContent['courses'] = [];
}

// Create an index of existing courses by registration number and year
$existingCourseIndex = [];
foreach ($existingJsonContent['courses'] as $existingCourse) {
    $key = $existingCourse['course_id'] . '_' . $existingCourse['year'];
    $existingCourseIndex[$key] = true;
}

// Filter out courses that already exist in the file
$newCoursesToAdd = [];
foreach ($formattedCourses as $newCourse) {
    $key = $newCourse['course_id'] . '_' . $newCourse['year'];
    if (!isset($existingCourseIndex[$key])) {
        $newCoursesToAdd[] = $newCourse;
    } else {
        echo "Skipping course already in file: " . $newCourse['course_id'] . " (" . $newCourse['year'] . ")\n";
    }
}

echo "Adding " . count($newCoursesToAdd) . " new courses to existing " .
    count($existingJsonContent['courses']) . " courses.\n";

// Merge the courses
$mergedCourses = array_merge($existingJsonContent['courses'], $newCoursesToAdd);

// Update the metadata
// Make sure we handle the field column safely
$fields = [];
if (!empty($newCoursesToAdd)) {
    foreach ($newCoursesToAdd as $course) {
        if (isset($course['field'])) {
            $fields[] = $course['field'];
        }
    }
}

$meta = [
    'total_count' => count($mergedCourses),
    'languages' => ['ja', 'en'],
    'generated_date' => date('Y-m-d H:i:s'),
    'last_update_fields' => !empty($fields) ?
        implode(', ', array_unique($fields)) :
        'No new fields added'
];

// Make sure $mergedCourses is defined and is an array
if (!isset($mergedCourses) || !is_array($mergedCourses)) {
    $mergedCourses = [];
}

// Create the final JSON
$jsonContent = json_encode([
    'courses' => $mergedCourses,
    'meta' => $meta
], $jsonOptions);

if ($jsonContent === false) {
    echo "Error encoding JSON: " . json_last_error_msg() . "\n";
} else {
    // Do NOT include UTF-8 BOM as it can cause parsing issues
    $jsonContent = $jsonContent;

    $result = file_put_contents($jsonFilePath, $jsonContent);
    if ($result === false) {
        echo "Error writing to file: $jsonFilePath\n";
    } else {
        echo "Data saved to $jsonFilePath (" . strlen($jsonContent) . " bytes)\n";
        echo "Done! The courses have been collected and formatted for the Rate My Teacher website.\n";
        echo "All courses have both Japanese and English translations for language toggle functionality.\n";
    }
}
