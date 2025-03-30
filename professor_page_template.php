<?php
/**
 * Professor Detail Page Template
 * This page displays detailed information about a specific professor
 */

// Include database configuration and rating calculation functions
require_once 'config.php';
require_once 'calculate_ratings.php';

// Start session to check if user is logged in
require_once 'session_config.php'; //NEW

// Start session to check if user is logged in
session_start();
$isLoggedIn = isset($_SESSION["loggedin"]) && $_SESSION["loggedin"] === true;


// Add these variables for login form
$email = $password = "";
$email_err = $password_err = $login_err = "";

// Process login form submission
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['login_submit'])) {
    // Check if email is empty
    if (empty(trim($_POST["email"]))) {
        $email_err = "Please enter email.";
    } else {
        $email = trim($_POST["email"]);
    }
    
    // Check if password is empty
    if (empty(trim($_POST["password"]))) {
        $password_err = "Please enter your password.";
    } else {
        $password = trim($_POST["password"]);
    }
    
    // Validate credentials
    if (empty($email_err) && empty($password_err)) {
        try {
            // For SQLite3, we need to use a different approach than mysqli
            $db = new SQLite3('database/ratemyteacher.db');
            $sql = "SELECT id, username, email, password, is_verified FROM users WHERE email = :email";
    
            $loginStmt = $db->prepare($sql);
            if ($loginStmt) {
                // Bind parameters
                $loginStmt->bindValue(':email', $email, SQLITE3_TEXT);
                
                // Execute the statement
                $loginResult = $loginStmt->execute();
                
                // Check if we found a user
                if ($loginRow = $loginResult->fetchArray(SQLITE3_ASSOC)) {
                    // Check password
                    if (password_verify($password, $loginRow['password'])) {
                        // Auto-verify all accounts for development
                        if ($loginRow['is_verified'] == 0) {
                            // Update the user to be verified
                            $verify_sql = "UPDATE users SET is_verified = 1 WHERE id = :id";
                            $verify_stmt = $db->prepare($verify_sql);
                            $verify_stmt->bindValue(':id', $loginRow['id'], SQLITE3_INTEGER);
                            $verify_stmt->execute();
                        }
                        
                        // Store data in session variables
                        $_SESSION["loggedin"] = true;
                        $_SESSION["id"] = $loginRow['id'];
                        $_SESSION["username"] = $loginRow['username'];
                        $_SESSION["email"] = $loginRow['email'];
                        
                        // Make sure session data is saved
                        session_write_close();
                        
                        // Redirect to the same page to refresh with logged in state
                        header("Location: " . $_SERVER['REQUEST_URI']);
                        exit();
                    } else {
                        // Password is not valid
                        $login_err = "Invalid email or password.";
                    }
                } else {
                    // Email doesn't exist
                    $login_err = "Invalid email or password.";
                }
            } else {
                $login_err = "Something went wrong. Please try again later.";
            }
        } catch (Exception $e) {
            $login_err = "Error: " . $e->getMessage();
        }
    }
}


// Debug information
echo "<!-- Debug Information\n";
echo "REQUEST: " . print_r($_GET, true) . "\n";
echo "-->\n";

// Load JSON data file
$jsonFilePath = __DIR__ . '/sfc_courses.json';
if (!file_exists($jsonFilePath)) {
    echo "Error: JSON data file not found at: $jsonFilePath";
    exit;
}

$jsonData = file_get_contents($jsonFilePath);
if ($jsonData === false) {
    echo "Error: Could not read JSON data file";
    exit;
}

$data = json_decode($jsonData, true);
if ($data === null) {
    echo "Error: Could not parse JSON data. JSON error: " . json_last_error_msg();
    exit;
}

// Get professor name from the URL
$professorParam = isset($_GET['name']) ? $_GET['name'] : null;

// Get language preference
$lang = isset($_GET['lang']) && $_GET['lang'] === 'en' ? 'en' : 'ja';

// Check if we have a professor name
if (!$professorParam) {
    echo "Professor name is required.";
    exit;
}

// Find the professor in the data
$professor = null;
$professorCourses = [];
$avgRating = 0.0;
$ratingCount = 0;
$hasRatings = false;
$roundedRating = 0;
$overallRating = 0;

// Iterate through courses to find professor
foreach ($data['courses'] as $course) {
    foreach ($course['professors'] as $prof) {
        // Match by English name without spaces
        $profNameNoSpaces = $prof['name']['en'];
        $profNameJa = $prof['name']['ja']; // Get Japanese name
        if (strcasecmp($profNameNoSpaces, $professorParam) === 0 || 
            strcasecmp($profNameJa, $professorParam) === 0) {
            $professor = $prof;
            $professorCourses[] = $course;
        }

    }
}

if (!$professor) {
    echo "Professor not found.";
    exit;
}

// Get professor display name based on language
$professorName = $professor['name'][$lang];
$department = $professor['department'][$lang];

// Set page title
$pageTitle = $professorName;

// Initialize database connection
$db = new SQLite3('database/ratemyteacher.db');

// Try to find professor in the database by name
$professorId = null;
$professorNameNoSpaces = str_replace(' ', '', $professorParam);
$stmt = $db->prepare("SELECT id, name, avg_content_quality, avg_difficulty, overall_rating, review_count FROM professors WHERE REPLACE(name, ' ', '') = :name OR REPLACE(name, ' ', '') LIKE :name_like");
$stmt->bindValue(':name', $professorNameNoSpaces, SQLITE3_TEXT);
$stmt->bindValue(':name_like', '%' . $professorNameNoSpaces . '%', SQLITE3_TEXT);
$result = $stmt->execute();
$row = $result->fetchArray(SQLITE3_ASSOC);
if ($row) {
    $professorId = $row['id'];
    $professorDbName = $row['name'];
    // Debug the professor found
    error_log("Found professor in database: ID=$professorId, Name={$row['name']}, Input={$professorParam}");
    
    // If the professor has stored ratings, use those directly
    if (isset($row['avg_content_quality']) && $row['avg_content_quality'] > 0) {
        $avgContentQuality = $row['avg_content_quality'];
        $avgDifficulty = $row['avg_difficulty'];
        $overallRating = $row['overall_rating'];
        $reviewCount = $row['review_count'];
        error_log("Found stored ratings for professor: Content=$avgContentQuality, Difficulty=$avgDifficulty, Overall=$overallRating, Reviews=$reviewCount");
    } else {
        error_log("No stored ratings found for professor ID=$professorId");
    }
} else {
    error_log("No professor found in database matching '$professorParam' or '$professorNameNoSpaces'");
}

// Get ratings based on professor ID if not already set
if (!isset($avgContentQuality) || $avgContentQuality == 0) {
    $avgContentQuality = 0;
    $avgDifficulty = 0;
    $reviewCount = 0;
}

if ($professorId) {
    // Use our calculation function to get average ratings across all courses
    $ratings = calculateProfessorRatings($professorId, $db);
    $avgContentQuality = $ratings['content_quality'];
    $avgDifficulty = $ratings['difficulty'];
    $overallRating = $ratings['overall'];
    $reviewCount = $ratings['review_count'];

    $jaToEnMap = [];
    foreach ($data['courses'] as $course) {
        foreach ($course['professors'] as $prof) {
            if (isset($prof['name']['ja']) && isset($prof['name']['en'])) {
                $jaToEnMap[$prof['name']['ja']] = str_replace(' ', '', $prof['name']['en']);
            }
        }
    }

    $altProfessorName = isset($jaToEnMap[$professorParam]) ? $jaToEnMap[$professorParam] : null;

    // Build the SQL query to search by either name
    $stmt = $db->prepare("
        SELECT id, name, avg_content_quality, avg_difficulty, overall_rating, review_count 
        FROM professors 
        WHERE REPLACE(name, ' ', '') = :name 
           OR REPLACE(name, ' ', '') LIKE :name_like
           OR (:alt_name IS NOT NULL AND (REPLACE(name, ' ', '') = :alt_name OR REPLACE(name, ' ', '') LIKE :alt_name_like))
    ");
    
    $stmt->bindValue(':name', $professorNameNoSpaces, SQLITE3_TEXT);
    $stmt->bindValue(':name_like', '%' . $professorNameNoSpaces . '%', SQLITE3_TEXT);
    $stmt->bindValue(':alt_name', $altProfessorName, SQLITE3_TEXT);
    $stmt->bindValue(':alt_name_like', $altProfessorName ? '%' . $altProfessorName . '%' : null, SQLITE3_TEXT);
    
    // Debug output to see what's going on with ratings
    error_log("Professor ID: $professorId, Content Quality: $avgContentQuality, Difficulty: $avgDifficulty, Overall: $overallRating, Reviews: $reviewCount");
} else {
    // If professor not found in DB, try to aggregate ratings from JSON data
    // This is a fallback but won't be as accurate
    $allContentRatings = [];
    $allDifficultyRatings = [];
    
    if (isset($data['ratings'])) {
        foreach ($data['ratings'] as $rating) {
            if (isset($rating['professor']) && 
                strcasecmp(str_replace(' ', '', $rating['professor']), $professorNameNoSpaces) === 0) {
                if (isset($rating['content_quality'])) {
                    $allContentRatings[] = $rating['content_quality'];
                }
                if (isset($rating['difficulty'])) {
                    $allDifficultyRatings[] = $rating['difficulty'];
                }
            }
        }
    }
    
    // Calculate averages if we have any ratings
    if (count($allContentRatings) > 0) {
        $avgContentQuality = round(array_sum($allContentRatings) / count($allContentRatings), 1);
        
        // Calculate overall rating with the formula: (content_quality + (5 - difficulty)) / 2
        if (count($allDifficultyRatings) > 0) {
            $invertedDifficulty = 5 - round(array_sum($allDifficultyRatings) / count($allDifficultyRatings), 1);
            $invertedDifficulty = max(0, $invertedDifficulty);
            $overallRating = round(($avgContentQuality + $invertedDifficulty) / 2, 1);
        } else {
            $overallRating = $avgContentQuality;
        }
    }
    if (count($allDifficultyRatings) > 0) {
        $avgDifficulty = round(array_sum($allDifficultyRatings) / count($allDifficultyRatings), 1);
    }
    $reviewCount = count($allContentRatings);
}

// Get all reviews for this professor from the database and calculate averages
$reviews = [];
$totalContentQuality = 0;
$totalDifficulty = 0;
$reviewsWithContentRating = 0;
$reviewsWithDifficultyRating = 0;

if ($professorId && $db) {
    try {
        // Debug professor ID for review search
        error_log("Searching for reviews with professor_id: $professorId");
        
        // Check if professor has any reviews
        $checkStmt = $db->prepare("SELECT COUNT(*) as count FROM ratings WHERE professor_id = :professor_id");
        $checkStmt->bindValue(':professor_id', $professorId, SQLITE3_INTEGER);
        $checkResult = $checkStmt->execute();
        $checkRow = $checkResult->fetchArray(SQLITE3_ASSOC);
        error_log("Found {$checkRow['count']} reviews for professor ID: $professorId");
        
        // Query to get all reviews for this professor across all courses
        $stmt = $db->prepare("
            SELECT 
                r.id,
                r.rating,
                r.content_rating,
                r.difficulty_rating,
                r.grade,
                r.comment,
                r.created_at,
                r.textbook,
                r.attendance_check,
                r.first_half,
                r.second_half,
                u.username,
                c.name as course_name
            FROM ratings r
            LEFT JOIN users u ON r.user_id = u.id
            LEFT JOIN courses c ON r.course_id = c.id
            WHERE r.professor_id = :professor_id
            ORDER BY r.created_at DESC
        ");
        $stmt->bindValue(':professor_id', $professorId, SQLITE3_INTEGER);
        $result = $stmt->execute();
        
        // Process each review
        while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
            // Format the date
            $timestamp = strtotime($row['created_at']);
            $formattedDate = $timestamp !== false ? date('F j, Y', $timestamp) : 'Unknown Date';
            
            // Add to reviews array with default username if missing
            $review = [
                'id' => $row['id'],
                'rating' => $row['rating'] ?? 3,
                'content_rating' => $row['content_rating'] ?? 0,
                'difficulty_rating' => $row['difficulty_rating'] ?? 0,
                'comment' => $row['comment'] ?? '',
                'created_at' => $formattedDate,
                'username' => $row['username'] ?? 'Student',
                'course_name' => $row['course_name'] ?? 'Unknown Course',
                'grade' => $row['grade'] ?? '',
                'textbook' => $row['textbook'] ?? '',
                'attendance_check' => $row['attendance_check'] ?? ''
            ];
            
            // Calculate totals for ratings
            if (isset($row['content_rating']) && $row['content_rating'] > 0) {
                $totalContentQuality += $row['content_rating'];
                $reviewsWithContentRating++;
            }
            
            if (isset($row['difficulty_rating']) && $row['difficulty_rating'] > 0) {
                $totalDifficulty += $row['difficulty_rating'];
                $reviewsWithDifficultyRating++;
            }
            
            // Try to parse JSON fields
            if (!empty($row['first_half'])) {
                try {
                    $review['first_half'] = json_decode($row['first_half'], true);
                } catch (Exception $e) {
                    $review['first_half'] = [];
                }
            } else {
                $review['first_half'] = [];
            }
            
            if (!empty($row['second_half'])) {
                try {
                    $review['second_half'] = json_decode($row['second_half'], true);
                } catch (Exception $e) {
                    $review['second_half'] = [];
                }
            } else {
                $review['second_half'] = [];
            }
            
            $reviews[] = $review;
        }
        
        // Calculate average ratings directly from the reviews
        $avgContentQuality = $reviewsWithContentRating > 0 ? round($totalContentQuality / $reviewsWithContentRating, 1) : 0;
        $avgDifficulty = $reviewsWithDifficultyRating > 0 ? round($totalDifficulty / $reviewsWithDifficultyRating, 1) : 0;
        $reviewCount = count($reviews);
        
        // Update overall rating based on latest ratings (content_quality + (5 - difficulty)) / 2
        if ($reviewsWithContentRating > 0 && $reviewsWithDifficultyRating > 0) {
            $invertedDifficulty = 5 - $avgDifficulty;
            $invertedDifficulty = max(0, $invertedDifficulty);
            $overallRating = round(($avgContentQuality + $invertedDifficulty) / 2, 1);
        } else if ($reviewsWithContentRating > 0) {
            $overallRating = $avgContentQuality;
        }
        
        error_log("Found $reviewCount reviews for professor ID: $professorId");
        error_log("Calculated avg content quality: $avgContentQuality, avg difficulty: $avgDifficulty");
    } catch (Exception $e) {
        error_log("Error fetching professor reviews: " . $e->getMessage());
    }
}

// Set up category scores for display with the calculated averages
$categoryScores = [
    'content' => [
        'score' => $avgContentQuality, 
        'percent' => $avgContentQuality * 20, // Convert to percentage (0-5 scale to 0-100%)
        'label' => $lang === 'ja' ? '授業内容の質' : 'Content Quality',
        'description' => $lang === 'ja' ? '高いほど良い' : 'Higher is better',
        'color' => '#6c757d' // Default grey (no reviews)
    ],
    'difficulty' => [
        'score' => $avgDifficulty, 
        'percent' => $avgDifficulty * 20, // Higher difficulty = more filled
        'raw_score' => $avgDifficulty, // Keep the raw score for proper display
        'label' => $lang === 'ja' ? '難易度' : 'Difficulty',
        'description' => $lang === 'ja' ? '高いほど難しい' : 'Higher = more difficult',
        'color' => '#6c757d' // Default grey (no reviews)
    ]
];

// Set content quality colors
if ($reviewCount > 0) {
    // Content quality colors: Red (bad) -> Orange (average) -> Green (good)
    if ($avgContentQuality >= 3.5) {
        $categoryScores['content']['color'] = '#28a745'; // green for good quality (3.5-5)
    } else if ($avgContentQuality >= 2.5) {
        $categoryScores['content']['color'] = '#fd7e14'; // orange for average quality (2.5-3.5)
    } else if ($avgContentQuality > 0) {
        $categoryScores['content']['color'] = '#dc3545'; // red for poor quality (1-2.5)
    }
    
    // Difficulty colors: Green (easy) -> Orange (medium) -> Red (hard)
    if ($avgDifficulty <= 2.5 && $avgDifficulty > 0) {
        $categoryScores['difficulty']['color'] = '#28a745'; // green for easy (1-2.5)
    } else if ($avgDifficulty <= 3.5) {
        $categoryScores['difficulty']['color'] = '#fd7e14'; // orange for medium (2.5-3.5)
    } else {
        $categoryScores['difficulty']['color'] = '#dc3545'; // red for hard (3.5-5)
    }
}
?>
<!DOCTYPE html>
<html lang="<?php echo $lang; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <script async src="https://pagead2.googlesyndication.com/pagead/js/adsbygoogle.js?client=ca-pub-1649331460122770"
    crossorigin="anonymous"></script>
    <title><?php echo $pageTitle; ?> - Gaku Neko</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Arial', sans-serif;
        }
        
        :root {
            --primary-color: #1e3a8a;
            --primary-light: #f0f4ff;
            --secondary-color: #6c757d;
            --light-gray: #f8f9fa;
            --dark-gray: #343a40;
            --success-color: #28a745;
            --warning-color: #ffc107;
            --box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            --hover-shadow: 0 5px 15px rgba(0,0,0,0.1);
            --border-color: #f0f0f0;
        }
        
        /* Fix for rating categories display on small screens */
        @media (max-width: 576px) {
            .rating-category {
                flex-wrap: wrap;
                padding-right: 40px;
                position: relative;
            }
            
            .category-name {
                width: 100% !important;
                margin-bottom: 8px;
            }
            
            .progress-bar {
                flex-grow: 1;
                width: 100% !important;
                margin: 0 !important;
            }
            
            .category-score {
                position: absolute !important;
                right: 0 !important;
                top: 50% !important;
                transform: translateY(-50%) !important;
            }
        }

        /* Global Style Improvements */
        body {
            background-color: #f5f5f5;
            font-family: 'Arial', sans-serif;
            line-height: 1.6;
            color: #333;
            margin: 0;
            padding: 0;
        }

        /* Container Styles */
        .container {
            width: 100%;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            max-width: 1200px;
            margin: 0 auto;
            padding: 2rem;
        }

        /* Content Box Styling */
        .content-box {
            flex: 1;
            background-color: white;
            margin: 1rem;
            padding: 1.5rem;
            border-radius: 8px;
            box-shadow: var(--box-shadow);
        }

        /* Professor Header Styling */
        .professor-title {
            color: var(--primary-color);
            font-size: 1.8rem;
            margin-bottom: 1rem;
            border-bottom: 2px solid var(--border-color);
            padding-bottom: 0.5rem;
        }

        .professor-subtitle {
            color: var(--secondary-color);
            display: flex;
            flex-wrap: wrap;
            gap: 20px;
            margin-bottom: 20px;
        }

        /* Rating Styles */
        .overall-rating {
            background-color: var(--primary-light);
            padding: 20px;
            border-radius: 8px;
            text-align: center;
            box-shadow: var(--box-shadow);
        }

        .rating-number {
            color: var(--primary-color);
            font-size: 42px;
            font-weight: bold;
        }

        .stars {
            color: var(--warning-color);
            font-size: 20px;
            margin: 10px 0;
        }

        /* Course Card Styling */
        .course-card {
            background-color: #f9f9f9;
            border-radius: 8px;
            padding: 15px;
            min-width: 200px;
            transition: transform 0.2s, box-shadow 0.2s;
            box-shadow: var(--box-shadow);
            margin-bottom: 15px;
            cursor: pointer;
        }

        .course-card:hover {
            transform: translateY(-3px);
            box-shadow: var(--hover-shadow);
        }

        .course-name {
            font-weight: bold;
            font-size: 1.1em;
            color: var(--primary-color);
            margin-bottom: 5px;
        }

        .course-department {
            color: #666;
            font-size: 0.9em;
            margin-bottom: 10px;
        }

        /* Section Titles */
        .section-title {
            color: var(--primary-color);
            font-size: 1.4rem;
            margin-bottom: 20px;
            border-bottom: 2px solid var(--border-color);
            padding-bottom: 10px;
        }

        /* Review Cards */
        .review-card {
            border-bottom: 1px solid var(--border-color);
            padding: 20px 0;
            margin-bottom: 10px;
        }

        /* Button Styles */
        .add-review-btn, .primary-btn, #rateButton, #writeReviewButton {
            background-color: var(--primary-color) !important;
            color: white !important;
            padding: 10px 20px !important;
            border-radius: 5px !important;
            text-decoration: none !important;
            font-weight: bold !important;
            border: none !important;
            cursor: pointer !important;
            transition: background-color 0.3s !important;
            display: inline-block !important;
        }

        .add-review-btn:hover, .primary-btn:hover, #rateButton:hover, #writeReviewButton:hover {
            background-color: #2a4db0 !important;
        }

        .content-blur {
    filter: blur(5px);
    pointer-events: none;
    user-select: none;
        }

        /* Login overlay styles */
        .login-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.7);
            display: flex;
            justify-content: center;
            align-items: center;
            z-index: 1000;
        }

        .login-container {
            background-color: white;
            border-radius: 8px;
            padding: 30px;
            width: 90%;
            max-width: 400px;
            box-shadow: 0 0 20px rgba(0, 0, 0, 0.3);
        }

        .login-title {
            color: #1e3a8a;
            margin-bottom: 20px;
            font-size: 24px;
            text-align: center;
        }

        .login-form .form-group {
            margin-bottom: 15px;
        }

        .login-form label {
            display: block;
            margin-bottom: 5px;
            font-weight: 500;
        }

        .login-form .form-control {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 16px;
        }

        .login-form .help-block {
            color: #dc3545;
            font-size: 14px;
            margin-top: 5px;
        }

        .login-form .btn-primary {
            background-color: #1e3a8a;
            color: white;
            padding: 12px 20px;
            border: none;
            border-radius: 4px;
            width: 100%;
            font-size: 16px;
            font-weight: bold;
            cursor: pointer;
            margin-top: 10px;
        }

        .login-form .btn-secondary {
            background-color: #6c757d;
            color: white;
            padding: 12px 20px;
            border: none;
            border-radius: 4px;
            width: 100%;
            font-size: 16px;
            font-weight: bold;
            cursor: pointer;
            margin-top: 10px;
        }

        .login-actions {
            display: flex;
            justify-content: space-between;
            margin-top: 20px;
            font-size: 14px;
        }

        .login-error {
            background-color: #f8d7da;
            color: #721c24;
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 4px;
            font-size: 14px;
        }

        .or-divider {
            display: flex;
            align-items: center;
            text-align: center;
            margin: 20px 0;
        }

        .or-divider::before,
        .or-divider::after {
            content: "";
            flex: 1;
            border-bottom: 1px solid #ddd;
        }

        .or-divider span {
            padding: 0 10px;
            color: #6c757d;
        }

        /* Footer Styling */
        footer {
            background-color: #1e3a8a;
            color: white;
            text-align: center;
            padding: 1rem;
            margin-top: auto;
        }

        /* Responsive Adjustments */
        @media (max-width: 768px) {
            .navbar {
                flex-direction: column;
                align-items: flex-start;
                padding: 10px 20px;
            }
            
            .nav-links {
                margin-top: 10px;
            }
            
            .nav-links a {
                margin-left: 0;
                margin-right: 15px;
            }
            
            .rating-overview {
                flex-direction: column;
            }
            
            .overall-rating {
                flex: 0 0 auto;
            }
            
            .professor-title {
                font-size: 24px;
            }
            
            .section-title {
                font-size: 20px;
            }
        }
    </style>
</head>
<body>
    <header style="background-color: #1e3a8a; color: white; padding: 0.5rem 1rem; display: flex; justify-content: space-between; align-items: center; height: 60px;">
        <div class="header-left" style="width: 25%;">
            <div class="dropdown" style="position: relative; display: inline-block;">
                <button class="dropbtn" style="background-color: transparent; color: white; padding: 10px; font-size: 16px; border: none; cursor: pointer; display: flex; align-items: center;">Menu <span style="margin-left: 5px; font-size: 12px;">▼</span></button>
                <div class="dropdown-content" style="position: absolute; background-color: white; min-width: 160px; box-shadow: 0 8px 16px rgba(0,0,0,0.2); z-index: 1; border-radius: 4px; overflow: hidden; display: none;">
                    <?php if ($isLoggedIn): ?>
                        <a href="account-section.php" style="color: black; padding: 12px 16px; text-decoration: none; display: block; border-bottom: 1px solid #f1f1f1;">My Account</a>
                        <a href="logout.php" style="color: black; padding: 12px 16px; text-decoration: none; display: block; border-bottom: 1px solid #f1f1f1;">Logout</a>
                    <?php else: ?>
                        <a href="login.php" style="color: black; padding: 12px 16px; text-decoration: none; display: block; border-bottom: 1px solid #f1f1f1;">Login</a>
                        <a href="register.php" style="color: black; padding: 12px 16px; text-decoration: none; display: block; border-bottom: 1px solid #f1f1f1;">Register</a>
                    <?php endif; ?>
                    <a href="home.php#popular-professors" style="color: black; padding: 12px 16px; text-decoration: none; display: block; border-bottom: 1px solid #f1f1f1;">Top Professors</a>
                    <a href="ratemyteacher-instructions.php?section=professors&lang=<?php echo $lang; ?>" style="color: black; padding: 12px 16px; text-decoration: none; display: block; border-bottom: 1px solid #f1f1f1;">Professors</a>
                    <a href="home.php#top-courses" style="color: black; padding: 12px 16px; text-decoration: none; display: block; border-bottom: 1px solid #f1f1f1;">Top Courses</a>
                    <a href="ratemyteacher-instructions.php?section=courses&lang=<?php echo $lang; ?>" style="color: black; padding: 12px 16px; text-decoration: none; display: block; border-bottom: 1px solid #f1f1f1;">Courses</a>
                    <a href="tipsandtricks.php" style="color: black; padding: 12px 16px; text-decoration: none; display: block; border-bottom: 1px solid #f1f1f1;">Tips and Tricks</a>
                </div>
            </div>
        </div>
        
        <div class="header-center" style="width: 50%; text-align: center;">
            <div class="logo" style="display: flex; flex-direction: column; align-items: center;">
                <h1 style="font-size: 1.5rem; margin: 0;"><a href="home.php" style="color: white; text-decoration: none;">Gaku Neko</a></h1>
            </div>
        </div>
        
        <div class="header-right" style="width: 25%; display: flex; justify-content: flex-end; align-items: center;">
            <?php if ($isLoggedIn): ?>
                <span class="welcome-message" style="margin-right: 15px; font-size: 14px;">Welcome, <?php echo htmlspecialchars($_SESSION["username"]); ?>!</span>
            <?php endif; ?>
            <div class="auth-links" style="margin-right: 15px;">
                <?php if ($isLoggedIn): ?>
                    <a href="logout.php" style="color: white; text-decoration: none; margin-left: 15px; font-size: 14px;">Logout</a>
                <?php else: ?>
                    <a href="login.php" style="color: white; text-decoration: none; margin-left: 15px; font-size: 14px;">Login</a>
                    <a href="register.php" style="color: white; text-decoration: none; margin-left: 15px; font-size: 14px;">Register</a>
                <?php endif; ?>
            </div>
            <div class="language-toggle" style="display: flex; align-items: center;">
                <a href="?name=<?php echo urlencode($professorParam); ?>&lang=en" style="display:inline-block; width:80px; text-align:center; padding:8px 0; margin-right:5px; background:<?php echo $lang == 'en' ? 'white' : 'transparent'; ?>; color:<?php echo $lang == 'en' ? '#1e3a8a' : 'white'; ?>; text-decoration:none; border:1px solid white; border-radius:4px;">English</a>
                <a href="?name=<?php echo urlencode($professorParam); ?>&lang=ja" style="display:inline-block; width:80px; text-align:center; padding:8px 0; background:<?php echo $lang == 'ja' ? 'white' : 'transparent'; ?>; color:<?php echo $lang == 'ja' ? '#1e3a8a' : 'white'; ?>; text-decoration:none; border:1px solid white; border-radius:4px;">日本語</a>
            </div>
        </div>
    </header>

        <?php if (!$isLoggedIn): ?>
            <div class="login-overlay">
                <div class="login-container">
                    <h2 class="login-title"><?php echo $lang === 'ja' ? 'ログインして続行' : 'Login to Continue'; ?></h2>
                    
                    <?php if (!empty($login_err)): ?>
                        <div class="login-error"><?php echo $login_err; ?></div>
                    <?php endif; ?>
                    
                    <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"] . '?' . $_SERVER['QUERY_STRING']); ?>" method="post" class="login-form">
                        <div class="form-group">
                            <label><?php echo $lang === 'ja' ? 'メールアドレス' : 'Email'; ?></label>
                            <input type="email" name="email" class="form-control" value="<?php echo $email; ?>" required>
                            <span class="help-block"><?php echo $email_err; ?></span>
                        </div>
                        <div class="form-group">
                            <label><?php echo $lang === 'ja' ? 'パスワード' : 'Password'; ?></label>
                            <input type="password" name="password" class="form-control" required>
                            <span class="help-block"><?php echo $password_err; ?></span>
                        </div>
                        <div class="form-group">
                            <button type="submit" name="login_submit" class="btn-primary">
                                <?php echo $lang === 'ja' ? 'ログイン' : 'Login'; ?>
                            </button>
                        </div>
                        
                        <div class="or-divider">
                            <span><?php echo $lang === 'ja' ? 'または' : 'OR'; ?></span>
                        </div>
                        
                        <div class="form-group">
                            <a href="register.php?redirect=<?php echo urlencode($_SERVER['REQUEST_URI']); ?>" class="btn-secondary" style="display: block; text-align: center; text-decoration: none;">
                                <?php echo $lang === 'ja' ? '新規登録' : 'Register'; ?>
                            </a>
                        </div>
                        
                        <div class="login-actions">
                            <a href="reset-password.php"><?php echo $lang === 'ja' ? 'パスワードをお忘れですか？' : 'Forgot Password?'; ?></a>
                            <a href="home.php"><?php echo $lang === 'ja' ? 'ホームに戻る' : 'Back to Home'; ?></a>
                        </div>
                    </form>
                </div>
            </div>
        <?php endif; ?>

    <div class="container">
        <div class="content-box <?php echo (!$isLoggedIn) ? 'content-blur' : ''; ?>">
            <div class="professor-header">
                <h1 class="professor-title" style="color: #1e3a8a; margin-bottom: 1rem; border-bottom: 2px solid #f0f0f0; padding-bottom: 0.5rem;"><?php echo htmlspecialchars($professorName); ?></h1>
                <div class="professor-subtitle" style="display: flex; flex-wrap: wrap; gap: 20px; margin-bottom: 20px; color: #666;">
                    <span style="display: flex; align-items: center;">
                        <svg width="16" height="16" viewBox="0 0 16 16" fill="currentColor" style="margin-right: 5px;">
                            <path d="M1 2.828c.885-.37 2.154-.769 3.388-.893 1.33-.134 2.458.063 3.112.752v9.746c-.935-.53-2.12-.603-3.213-.493-1.18.12-2.37.461-3.287.811V2.828zm7.5-.141c.654-.689 1.782-.886 3.112-.752 1.234.124 2.503.523 3.388.893v9.923c-.918-.35-2.107-.692-3.287-.81-1.094-.111-2.278-.039-3.213.492V2.687zM8 1.783C7.015.936 5.587.81 4.287.94c-1.514.153-3.042.672-3.994 1.105A.5.5 0 000 2.5v11a.5.5 0 00.707.455c.882-.4 2.303-.881 3.68-1.02 1.409-.142 2.59.087 3.223.877a.5.5 0 00.78 0c.633-.79 1.814-1.019 3.222-.877 1.378.139 2.8.62 3.681 1.02A.5.5 0 0016 13.5v-11a.5.5 0 00-.293-.455c-.952-.433-2.48-.952-3.994-1.105C10.413.809 8.985.936 8 1.783z"/>
                        </svg>
                        <?php echo htmlspecialchars($department); ?>
                    </span>
                </div>
            </div>

        <div class="professor-courses" style="margin-bottom: 30px;">
            <h2 class="section-title" style="color: #1e3a8a; margin-bottom: 20px; border-bottom: 2px solid #f0f0f0; padding-bottom: 10px;"><?php echo $lang === 'ja' ? '担当コース' : 'Courses Taught'; ?></h2>
            <div class="courses" style="display: flex; flex-wrap: wrap; gap: 20px;">
                <?php if (empty($professorCourses)): ?>
                    <p style="color: #666; font-style: italic;"><?php echo $lang === 'ja' ? 'コース情報はありません。' : 'No course information available.'; ?></p>
                <?php else: ?>
                    <?php foreach ($professorCourses as $course): ?>
                        <div class="course-card" style="background-color: #f9f9f9; border-radius: 8px; padding: 15px; min-width: 200px; transition: transform 0.2s, box-shadow 0.2s; box-shadow: 0 2px 5px rgba(0,0,0,0.1);">
                        <a href="course_page_template.php?course=<?php echo urlencode($course['translations']['en']['name']); ?>&professor=<?php echo urlencode($professorParam); ?>&lang=<?php echo $lang; ?>" style="text-decoration: none; color: inherit;">                                <div class="course-name" style="font-weight: bold; font-size: 1.1em; color: #1e3a8a; margin-bottom: 5px;"><?php echo htmlspecialchars($course['translations'][$lang]['name']); ?></div>
                            </a>
                            <div class="course-field" style="color: #666; font-size: 0.9em; margin-bottom: 10px;"><?php echo htmlspecialchars($course['translations'][$lang]['field']); ?></div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

        <div class="reviews-section">
            <h2 class="section-title" style="color: #1e3a8a; margin-bottom: 20px; border-bottom: 2px solid #f0f0f0; padding-bottom: 10px;"><?php echo $lang === 'ja' ? 'レビュー' : 'Reviews'; ?></h2>
            
            <?php if (empty($reviews)): ?>
                <div style="text-align: center; padding: 40px 20px; color: #666; background-color: #f9f9f9; border-radius: 8px; margin-bottom: 20px; box-shadow: 0 2px 5px rgba(0,0,0,0.05);">
                    <div style="font-size: 64px; margin-bottom: 10px; color: #1e3a8a;">
                        <i class="far fa-comment-dots"></i>
                    </div>
                    <h3 style="color: #1e3a8a; margin-bottom: 10px;"><?php echo $lang === 'ja' ? 'まだレビューがありません' : 'No Reviews Yet'; ?></h3>
                    <p style="margin-bottom: 20px;"><?php echo $lang === 'ja' ? 'レビュー機能は現在準備中です' : 'Review functionality coming soon'; ?></p>
                </div>
            <?php else: ?>
                <?php foreach ($reviews as $review): ?>
                    <div class="review-card" style="border-bottom: 1px solid #f0f0f0; padding: 20px 0; margin-bottom: 10px;">
                        <div class="review-header" style="display: flex; justify-content: space-between; margin-bottom: 10px;">
                            <div class="reviewer" style="font-weight: bold;"><?php echo htmlspecialchars($review['username']); ?></div>
                            <div class="review-date" style="color: #666; font-size: 0.9em;"><?php echo $review['created_at']; ?></div>
                        </div>
                        
                        <!-- Course name for the review -->
                        <div class="review-course" style="margin-bottom: 10px; font-size: 0.9em; color: #1e3a8a;">
                            <strong><?php echo $lang === 'ja' ? 'コース: ' : 'Course: '; ?></strong>
                            <?php 
                            // Find the translated course name if it exists
                            $translatedCourseName = $review['course_name']; // Default to English name
                            
                            // Look for the course in the course data to find its translation
                            foreach ($professorCourses as $course) {
                                if ($course['translations']['en']['name'] === $review['course_name']) {
                                    // If we found a match, use the translated name according to current language
                                    $translatedCourseName = $course['translations'][$lang]['name'];
                                    break;
                                }
                            }
                            
                            echo htmlspecialchars($translatedCourseName);
                            ?>
                        </div>
                        <!-- Rating information -->
                        <div class="review-ratings" style="display: flex; flex-wrap: wrap; gap: 15px; margin-bottom: 10px;">
                            <div class="review-rating" style="color: #ffc107;">
                                <strong><?php echo $lang === 'ja' ? '総合評価: ' : 'Overall: '; ?></strong>
                                <?php echo str_repeat('★', round($review['rating'])) . str_repeat('☆', 5 - round($review['rating'])); ?> 
                                (<?php echo $review['rating']; ?>)
                            </div>
                            
                            <?php if (isset($review['content_rating']) && $review['content_rating'] > 0): ?>
                            <div>
                                <strong><?php echo $lang === 'ja' ? '授業内容: ' : 'Content: '; ?></strong>
                                <?php echo $review['content_rating']; ?>/5
                            </div>
                            <?php endif; ?>
                            
                            <?php if (isset($review['difficulty_rating']) && $review['difficulty_rating'] > 0): ?>
                            <div>
                                <strong><?php echo $lang === 'ja' ? '難易度: ' : 'Difficulty: '; ?></strong>
                                <?php echo $review['difficulty_rating']; ?>/5
                            </div>
                            <?php endif; ?>
                            
                            <?php if (isset($review['grade']) && !empty($review['grade'])): ?>
                            <div>
                                <strong><?php echo $lang === 'ja' ? '成績: ' : 'Grade: '; ?></strong>
                                <?php echo $review['grade']; ?>
                            </div>
                            <?php endif; ?>
                        </div>
                        
                        <!-- Review comment -->
                        <?php if (!empty($review['comment'])): ?>
                        <div class="review-content" style="margin-bottom: 15px; line-height: 1.5;">
                            <?php echo nl2br(htmlspecialchars($review['comment'])); ?>
                        </div>
                        <?php endif; ?>
                        
                        <!-- Additional assessment details -->
                        <?php if (!empty($review['textbook']) || !empty($review['attendance_check']) || 
                                 !empty($review['first_half']) || !empty($review['second_half'])): ?>
                        <div class="review-details" style="margin-bottom: 10px; font-size: 0.9em; color: #666;">
                            <?php if (!empty($review['textbook'])): ?>
                            <div style="margin-bottom: 5px;">
                                <strong><?php echo $lang === 'ja' ? '教科書: ' : 'Textbook: '; ?></strong>
                                <?php 
                                echo $lang === 'ja' ? 
                                    match($review['textbook']) {
                                        'required' => '必須',
                                        'recommended' => '推奨',
                                        'not_needed' => '不要',
                                        default => $review['textbook'],
                                    } : 
                                    match($review['textbook']) {
                                        'required' => 'Required',
                                        'recommended' => 'Recommended',
                                        'not_needed' => 'Not Needed',
                                        default => $review['textbook'],
                                    }; 
                                ?>
                            </div>
                            <?php endif; ?>
                            
                            <?php if (!empty($review['attendance_check'])): ?>
                            <div style="margin-bottom: 5px;">
                                <strong><?php echo $lang === 'ja' ? '出席確認: ' : 'Attendance: '; ?></strong>
                                <?php 
                                echo $lang === 'ja' ? 
                                    match($review['attendance_check']) {
                                        'always' => '毎回取る',
                                        'sometimes' => '時々取る',
                                        'never' => '取らない',
                                        default => $review['attendance_check'],
                                    } : 
                                    match($review['attendance_check']) {
                                        'always' => 'Always',
                                        'sometimes' => 'Sometimes',
                                        'never' => 'Never',
                                        default => $review['attendance_check'],
                                    }; 
                                ?>
                            </div>
                            <?php endif; ?>
                            
                            <?php if (!empty($review['first_half'])): ?>
                            <div style="margin-bottom: 5px;">
                                <strong><?php echo $lang === 'ja' ? '授業前半: ' : 'First Half: '; ?></strong>
                                <?php 
                                $firstHalfLabels = [];
                                foreach ((array)$review['first_half'] as $method) {
                                    $firstHalfLabels[] = $lang === 'ja' ? 
                                        match($method) {
                                            'report' => 'レポート',
                                            'test' => 'テスト',
                                            'presentation' => '発表',
                                            'project' => 'プロジェクト',
                                            'nothing' => 'なし',
                                            default => $method,
                                        } : 
                                        match($method) {
                                            'report' => 'Report',
                                            'test' => 'Test',
                                            'presentation' => 'Presentation',
                                            'project' => 'Project',
                                            'nothing' => 'Nothing',
                                            default => $method,
                                        };
                                }
                                echo implode(', ', $firstHalfLabels);
                                ?>
                            </div>
                            <?php endif; ?>
                            
                            <?php if (!empty($review['second_half'])): ?>
                            <div>
                                <strong><?php echo $lang === 'ja' ? '授業後半: ' : 'Second Half: '; ?></strong>
                                <?php 
                                $secondHalfLabels = [];
                                foreach ((array)$review['second_half'] as $method) {
                                    $secondHalfLabels[] = $lang === 'ja' ? 
                                        match($method) {
                                            'report' => 'レポート',
                                            'test' => 'テスト',
                                            'presentation' => '発表',
                                            'project' => 'プロジェクト',
                                            'nothing' => 'なし',
                                            default => $method,
                                        } : 
                                        match($method) {
                                            'report' => 'Report',
                                            'test' => 'Test',
                                            'presentation' => 'Presentation',
                                            'project' => 'Project',
                                            'nothing' => 'Nothing',
                                            default => $method,
                                        };
                                }
                                echo implode(', ', $secondHalfLabels);
                                ?>
                            </div>
                            <?php endif; ?>
                        </div>
                        <?php endif; ?>
                        
                        <div class="review-tags">
                            <span class="tag" style="display: inline-block; background-color: #f0f4ff; color: #1e3a8a; padding: 3px 8px; border-radius: 3px; font-size: 0.8em;"><?php echo $lang === 'ja' ? '学生レビュー' : 'Student Review'; ?></span>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
        
        <!-- Review submission functionality is disabled -->
        </div>
    </div><!-- End of .container div -->
    
    <!-- Footer outside the container to make it full width -->
    <footer style="background-color: #1e3a8a; color: white; text-align: center; padding: 1rem; width: 100%;">
        <p>2025 Gaku Neko</p>
        <div style="display: flex; flex-wrap: wrap; justify-content: center; margin-top: 15px; gap: 25px;">
            <a href="ToS.php?lang=<?php echo $lang; ?>" style="color: white; text-decoration: underline;">
                <?php echo $lang == 'ja' ? '利用規約' : 'Terms and Conditions'; ?>
            </a>
            <a href="privacy_policy.php?lang=<?php echo $lang; ?>" style="color: white; text-decoration: underline;">
                <?php echo $lang == 'ja' ? 'プライバシーポリシー' : 'Privacy Policy'; ?>
            </a>
            <a href="about_us.php?lang=<?php echo $lang; ?>" style="color: white; text-decoration: underline;">
                <?php echo $lang == 'ja' ? '私たちについて' : 'About Us'; ?>
            </a>
        </div>
    </footer>

    <style>
        /* Add hover effect for dropdown menu */
        .dropdown:hover .dropdown-content {
            display: block !important;
        }
    </style>
    <script>
        // Review functionality is disabled
    </script>
</body>
</html>