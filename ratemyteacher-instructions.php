<?php
// Display instructions for accessing Rate My Teacher

// Start session to check if user is logged in
require_once 'session_config.php'; //NEW
session_start();
$isLoggedIn = isset($_SESSION["loggedin"]) && $_SESSION["loggedin"] === true;
$username = $isLoggedIn ? htmlspecialchars($_SESSION["username"]) : '';

// Check if we have a section specified in the URL
$section = isset($_GET['section']) ? $_GET['section'] : null;

// Automatically scroll to section if specified
$scrollToSection = '';
if ($section === 'professors') {
    $scrollToSection = '<script>window.onload = function() { document.getElementById("professors").scrollIntoView(); }</script>';
} elseif ($section === 'courses') {
    $scrollToSection = '<script>window.onload = function() { document.getElementById("courses").scrollIntoView(); }</script>';
}

// Get a few sample professor names from the JSON data
$jsonData = file_get_contents(__DIR__ . '/sfc_courses.json');
$data = json_decode($jsonData, true);

// Extract all professor names for alphabetical listing
$allProfessors = [];
$allProfessorsJa = [];

foreach ($data['courses'] as $course) {
    foreach ($course['professors'] as $professor) {
        if (isset($professor['name']['en']) && isset($professor['name']['ja'])) {
            $profNameEnOriginal = $professor['name']['en']; // Keep original with spaces
            $profNameEn = str_replace(' ', '', $professor['name']['en']); // No spaces for URL
            $profNameJa = $professor['name']['ja'];
            
            // Store both English and Japanese names with their corresponding translated names
            if (!isset($allProfessors[$profNameEn])) {
                $allProfessors[$profNameEn] = [
                    'en' => $profNameEn,
                    'original_en' => $profNameEnOriginal, // Store original with spaces
                    'ja' => $profNameJa
                ];
                
                $allProfessorsJa[$profNameJa] = [
                    'en' => $profNameEn,
                    'original_en' => $profNameEnOriginal, // Store original with spaces
                    'ja' => $profNameJa
                ];
            }
        }
    }
}

// Sort professors alphabetically based on language
if (isset($_GET['lang']) && $_GET['lang'] == 'ja') {
    // Sort by Japanese name
    ksort($allProfessorsJa, SORT_STRING);
    $sortedProfessors = $allProfessorsJa;
} else {
    // Sort by English name
    ksort($allProfessors, SORT_STRING);
    $sortedProfessors = $allProfessors;
}

// Organize all courses by faculty, subject type, and year
$organizedCourses = [];
$professorCourseCount = []; // To track how many courses each professor teaches

// First, collect all courses and count how many each professor teaches
foreach ($data['courses'] as $course) {
    if (isset($course['translations']['en'])) {
        $faculty = isset($course['translations']['en']['faculty']) ? 
                   $course['translations']['en']['faculty'] : 
                   'FACULTY OF POLICY MANAGEMENT / ENVIRONMENT AND INFORMATION STUDIES';
                   
        $field = isset($course['translations']['en']['field']) ? 
                $course['translations']['en']['field'] : 
                'Uncategorized';
                
        $year = isset($course['year']) ? $course['year'] : '2024';
        
        // Extract subject type from field (e.g., "Fundamental Subjects - Introductory Subjects")
        preg_match('/^([^-]+)(?:-(.+))?$/', $field, $matches);
        $mainCategory = trim($matches[1] ?? 'Other');
        $subCategory = isset($matches[2]) ? trim($matches[2]) : '';
        
        // Create category key for sorting
        $categoryOrder = [
            'Fundamental Subjects' => 1,
            'Advanced Subjects' => 2,
            'Special Subjects' => 3
        ];
        
        $subCategoryOrder = [
            'Introductory Subjects' => 1,
            'Subjects of Language Communication' => 2,
            'Subjects of Data Science' => 3,
            'Subjects of Fundamentals of Information Technology' => 4,
            'Wellness Subjects' => 5,
            'Interdisciplinary Subjects' => 6,
            'Series of Policy Management' => 7,
            'Series of Environment And Information Studies' => 8
        ];
        
        $categoryKey = ($categoryOrder[$mainCategory] ?? 9) . '-' . 
                      ($subCategoryOrder[$subCategory] ?? 9) . '-' . 
                      $year;
        
        // Check if this category exists in our organization
        if (!isset($organizedCourses[$faculty][$categoryKey])) {
            $organizedCourses[$faculty][$categoryKey] = [
                'mainCategory' => $mainCategory,
                'subCategory' => $subCategory,
                'year' => $year,
                'courses' => []
            ];
        }
        
        // Add course to this category
        $organizedCourses[$faculty][$categoryKey]['courses'][] = [
            'name' => $course['translations']['en']['name'],
            'nameJa' => isset($course['translations']['ja']['name']) ? $course['translations']['ja']['name'] : '',
            'year' => $year,
            'course_id' => isset($course['course_id']) ? $course['course_id'] : '',
            'professors' => $course['professors']
        ];
        
        // Count courses per professor
        foreach ($course['professors'] as $professor) {
            if (isset($professor['name']['en'])) {
                $profName = $professor['name']['en'];
                if (!isset($professorCourseCount[$profName])) {
                    $professorCourseCount[$profName] = 0;
                }
                $professorCourseCount[$profName]++;
            }
        }
    }
}

// Sort professors by number of courses they teach (descending)
arsort($professorCourseCount);

// Extract professors ordered by course count
$sortedProfessorsByCount = [];
foreach ($professorCourseCount as $profName => $count) {
    // Find this professor in the original data
    foreach ($data['courses'] as $course) {
        foreach ($course['professors'] as $professor) {
            if (isset($professor['name']['en']) && $professor['name']['en'] === $profName) {
                $sortedProfessorsByCount[$profName] = [
                    'en' => str_replace(' ', '', $profName),
                    'original_en' => $profName, // Store original name with spaces
                    'ja' => $professor['name']['ja'] ?? '',
                    'count' => $count
                ];
                break 2;
            }
        }
    }
}

// Set professors ordered by course count as our sorted professors list
$sortedProfessors = $sortedProfessorsByCount;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <script async src="https://pagead2.googlesyndication.com/pagead/js/adsbygoogle.js?client=ca-pub-1649331460122770"
    crossorigin="anonymous"></script>
    <title>Rate My Teacher - SU2H1</title>
    <link rel="stylesheet" href="css/style.css">
    <?php echo $scrollToSection; ?>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Arial', sans-serif;
        }
        
        body {
            background-color: #f5f5f5;
        }
        
        .container {
            width: 100%;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }
        
        .tips-section {
            padding: 0 2rem 2rem;
        }
        
        .tips-list {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 1.5rem;
        }
        
        .tip-item {
            background-color: #f0f8ff;
            padding: 1.5rem;
            border-radius: 8px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            transition: transform 0.2s;
        }
        
        .tip-item:hover {
            transform: translateY(-5px);
        }
        
        header {
            background-color: #1e3a8a;
            color: white;
            padding: 1rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .header-left {
            width: 25%;
        }
        
        .header-center {
            width: 50%;
            text-align: center;
        }
        
        .header-right {
            width: 25%;
            display: flex;
            justify-content: flex-end;
            align-items: center;
        }
        
        .auth-links {
            margin-right: 15px;
        }
        
        .auth-links a, .auth-links span {
            color: white;
            text-decoration: none;
            margin-left: 15px;
            font-size: 14px;
        }
        
        .auth-links a:hover {
            text-decoration: underline;
        }
        
        .welcome-message {
            margin-right: 15px;
            font-size: 14px;
        }
        
        .logo {
            display: flex;
            flex-direction: column;
            align-items: center;
        }
        
        .dropdown {
            position: relative;
            display: inline-block;
        }
        
        .dropbtn {
            background-color: transparent;
            color: white;
            padding: 10px;
            font-size: 16px;
            border: none;
            cursor: pointer;
            display: flex;
            align-items: center;
        }
        
        .dropbtn::after {
            content: "▼";
            font-size: 12px;
            margin-left: 5px;
        }
        
        .dropdown-content {
            display: none;
            position: absolute;
            background-color: white;
            min-width: 160px;
            box-shadow: 0 8px 16px rgba(0,0,0,0.2);
            z-index: 1;
            border-radius: 4px;
            overflow: hidden;
        }
        
        .dropdown-content a {
            color: black;
            padding: 12px 16px;
            text-decoration: none;
            display: block;
            border-bottom: 1px solid #f1f1f1;
        }
        
        .dropdown-content a:hover {
            background-color: #f1f1f1;
        }
        
        .dropdown:hover .dropdown-content {
            display: block;
        }
        
        .delete-account {
            color: #dc3545 !important;
            border-top: 1px solid #eee;
            margin-top: 5px;
            padding-top: 10px;
        }
        
        .language-toggle {
            display: flex;
        }
        
        .language-toggle a {
            text-decoration: none;
        }
        
        .language-toggle button {
            background: none;
            border: 1px solid white;
            color: white;
            padding: 0.25rem 0.5rem;
            margin-left: 0.5rem;
            cursor: pointer;
            border-radius: 3px;
        }
        
        .language-toggle button.active {
            background-color: white;
            color: #1e3a8a;
        }
        
        main {
            flex: 1;
            padding: 2rem;
        }
        
        .content-box {
            flex: 1;
            background-color: white;
            margin: 1rem;
            padding: 1.5rem;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .content-box h2 {
            color: #1e3a8a;
            margin-bottom: 1rem;
            border-bottom: 2px solid #f0f0f0;
            padding-bottom: 0.5rem;
        }
        
        footer {
            background-color: #1e3a8a;
            color: white;
            text-align: center;
            padding: 1rem;
        }
        
        .note {
            background-color: #fffde7;
            padding: 15px;
            border-left: 4px solid #ffd600;
            margin-bottom: 20px;
            border-radius: 8px;
        }
        
        .link-list li {
            margin-bottom: 12px;
            padding: 10px;
            background-color: #f9f9f9;
            border-radius: 6px;
            transition: all 0.2s;
        }
        
        .link-list li:hover {
            background-color: #f0f4ff;
            transform: translateY(-2px);
            box-shadow: 0 3px 8px rgba(0,0,0,0.05);
        }
        
        .professor-list-container {
            max-height: 600px;
            overflow-y: auto;
            border: 1px solid #eee;
            border-radius: 8px;
            padding: 10px;
            margin-bottom: 20px;
        }
        
        .course-count {
            color: #666;
            font-size: 0.9em;
            margin-left: 5px;
        }
        
        /* Course list styling */
        .course-list-container {
            margin-top: 20px;
        }
        
        .faculty-section {
            margin-bottom: 30px;
        }
        
        .faculty-heading {
            background-color: #1e3a8a;
            color: white;
            padding: 10px 15px;
            border-radius: 6px;
            margin-bottom: 15px;
            font-size: 18px;
        }
        
        .category-section {
            margin-bottom: 20px;
        }
        
        .category-heading {
            color: #1e3a8a;
            border-bottom: 2px solid #eee;
            padding-bottom: 5px;
            margin-bottom: 10px;
        }
        
        .course-teachers {
            font-size: 0.9em;
            color: #555;
            display: inline-block;
            margin-left: 6px;
        }
        
        .link-list a {
            text-decoration: none;
            color: #1e3a8a;
            display: block;
        }
        
        .link-list a:hover {
            text-decoration: none;
        }
    </style>
</head>
<body>
    <div class="container">
        <header>
            <div class="header-left">
                <div class="dropdown">
                    <button class="dropbtn">Menu</button>
                    <div class="dropdown-content">
                        <?php if ($isLoggedIn): ?>
                            <a href="account-section.php">My Account</a>
                            <a href="logout.php">Logout</a>
                        <?php else: ?>
                            <a href="login.php" id="account-link">Login</a>
                            <a href="register.php">Register</a>
                        <?php endif; ?>
                        <a href="home.php#popular-professors">Top Professors</a>
                        <a href="ratemyteacher-instructions.php?section=professors&lang=<?php echo isset($_GET['lang']) && $_GET['lang'] == 'ja' ? 'ja' : 'en'; ?>">Professors</a>
                        <a href="home.php#top-courses">Top Courses</a>
                        <a href="ratemyteacher-instructions.php?section=courses&lang=<?php echo isset($_GET['lang']) && $_GET['lang'] == 'ja' ? 'ja' : 'en'; ?>">Courses</a>
                        <a href="tipsandtricks.php">Tips and Tricks</a>
                        <?php if ($isLoggedIn): ?>
                            <a href="delete_account.php" class="delete-account">Delete Account</a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <div class="header-center">
                <div class="logo">
                    <h1><a href="home.php" style="color: white; text-decoration: none;">Rate My Teacher</a></h1>
                </div>
            </div>
            
            <div class="header-right">
                <?php if ($isLoggedIn): ?>
                    <span class="welcome-message">Welcome, <?php echo $username; ?>!</span>
                <?php endif; ?>
                <div class="auth-links">
                    <?php if ($isLoggedIn): ?>
                        <a href="logout.php">Logout</a>
                    <?php else: ?>
                        <a href="login.php">Login</a>
                        <a href="register.php">Register</a>
                    <?php endif; ?>
                </div>
                <div class="language-toggle">
                    <a href="?section=<?php echo $section; ?>&lang=en"><button class="<?php echo !isset($_GET['lang']) || $_GET['lang'] == 'en' ? 'active' : ''; ?>">English</button></a>
                    <a href="?section=<?php echo $section; ?>&lang=ja"><button class="<?php echo isset($_GET['lang']) && $_GET['lang'] == 'ja' ? 'active' : ''; ?>">日本語</button></a>
                </div>
            </div>
        </header>
        
        <main>
            <div class="content-box">
                <h2><?php echo isset($_GET['lang']) && $_GET['lang'] == 'ja' ? 'Rate My Teacher' : 'Rate My Teacher'; ?></h2>
                
                <h3 id="professors" style="color: #1e3a8a; margin: 20px 0 10px;"><?php echo isset($_GET['lang']) && $_GET['lang'] == 'ja' ? '教員' : 'Instructors'; ?></h3>
                <p><?php echo isset($_GET['lang']) && $_GET['lang'] == 'ja' ? '以下のリンクをクリックして教員のプロフィールを表示:' : 'Click on any of these links to view a professor\'s profile:'; ?></p>
                
                <div class="professor-list-container">
                    <?php 
                    $isJapanese = isset($_GET['lang']) && $_GET['lang'] == 'ja';
                    ?>
                    
                    <ul class="link-list">
                        <?php foreach ($sortedProfessors as $prof): ?>
                        <li>
                            <?php
                            // For English display, use the original name with spaces if available
                            if ($isJapanese) {
                                $displayName = $prof['ja'];
                            } else {
                                // Use original_en if available, otherwise try to add spaces
                                if (isset($prof['original_en'])) {
                                    $displayName = $prof['original_en'];
                                } else {
                                    // Fallback to heuristic spacing if original_en is not available
                                    $spaced = '';
                                    for ($i = 0; $i < strlen($prof['en']); $i++) {
                                        if ($i > 0 && ctype_upper($prof['en'][$i]) && !ctype_upper($prof['en'][$i-1])) {
                                            $spaced .= ' ';
                                        }
                                        $spaced .= $prof['en'][$i];
                                    }
                                    $displayName = $spaced;
                                }
                            }
                            ?>
                            <a href="professor.php?name=<?php echo urlencode($prof['en']); ?>&lang=<?php echo $isJapanese ? 'ja' : 'en'; ?>">
                                <?php echo htmlspecialchars($displayName); ?>
                                <?php if (isset($prof['count'])): ?>
                                <span class="course-count">(<?php echo $prof['count']; ?> courses)</span>
                                <?php endif; ?>
                            </a>
                        </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
                
                <h3 id="courses" style="color: #1e3a8a; margin: 20px 0 10px;"><?php echo isset($_GET['lang']) && $_GET['lang'] == 'ja' ? '講義' : 'Courses'; ?></h3>
                <p><?php echo isset($_GET['lang']) && $_GET['lang'] == 'ja' ? '以下のリンクをクリックして講義の詳細を表示:' : 'Click on any of these links to view a course:'; ?></p>
                
                <div class="course-list-container">
                    <?php 
                    $isJapanese = isset($_GET['lang']) && $_GET['lang'] == 'ja';
                    
                    // For each faculty
                    foreach ($organizedCourses as $faculty => $categories): 
                        // Sort categories by our custom order
                        ksort($categories);
                    ?>
                    <div class="faculty-section">
                        <h4 class="faculty-heading"><?php echo htmlspecialchars($faculty); ?></h4>
                        
                        <?php foreach ($categories as $categoryKey => $category): ?>
                        <div class="category-section">
                            <h5 class="category-heading">
                                <?php echo htmlspecialchars($category['mainCategory']); ?>
                                <?php if (!empty($category['subCategory'])): ?>
                                 - <?php echo htmlspecialchars($category['subCategory']); ?>
                                <?php endif; ?>
                                (<?php echo htmlspecialchars($category['year']); ?>)
                            </h5>
                            
                            <ul class="link-list">
                                <?php foreach ($category['courses'] as $course): ?>
                                <li>
                                    <?php 
                                    // Only show the first professor for simplicity in the URL
                                    $firstProf = $course['professors'][0] ?? null;
                                    $profUrlName = isset($firstProf['name']['en']) ? str_replace(' ', '', $firstProf['name']['en']) : '';
                                    $courseName = $isJapanese && !empty($course['nameJa']) ? $course['nameJa'] : $course['name'];
                                    ?>
                                    <a href="course.php?professor=<?php echo urlencode($profUrlName); ?>&course=<?php echo urlencode($courseName); ?>&year=<?php echo urlencode($course['year']); ?>&lang=<?php echo $isJapanese ? 'ja' : 'en'; ?>">
                                        <?php echo htmlspecialchars($courseName); ?>
                                        
                                        <span class="course-teachers">
                                            <?php 
                                            $teacherNames = [];
                                            foreach ($course['professors'] as $prof) {
                                                $teacherNames[] = $isJapanese && isset($prof['name']['ja']) ? 
                                                    $prof['name']['ja'] : 
                                                    ($prof['name']['en'] ?? 'Unknown');
                                            }
                                            echo '(' . htmlspecialchars(implode(', ', $teacherNames)) . ')';
                                            ?>
                                        </span>
                                    </a>
                                </li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </main>
    </div><!-- End of .container div -->
    
    <!-- Footer outside the container to make it full width -->
    <footer style="background-color: #1e3a8a; color: white; text-align: center; padding: 1rem; width: 100%;">
        <p>2025 Rate My Teacher</p>
    </footer>
</body>
</html>