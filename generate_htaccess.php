<?php
/**
 * Generate test URLs for the Rate My Teacher website based on sfc_courses.json data
 */

// Load the sfc_courses.json file
$jsonData = file_get_contents(__DIR__ . '/sfc_courses.json');
$data = json_decode($jsonData, true);

// Make sure we got the courses data
if (!$data || !isset($data['courses']) || empty($data['courses'])) {
    die("Error: Unable to load course data from sfc_courses.json");
}

// Function to make URL-friendly strings
function makeUrlFriendly($str) {
    return urlencode(str_replace(' ', '-', $str));
}

// Generate URLs for professors
$professorUrls = [];
$courseUrls = [];

foreach ($data['courses'] as $course) {
    $courseNameJa = $course['translations']['ja']['name'] ?? 'Unknown Course';
    $courseNameEn = $course['translations']['en']['name'] ?? 'Unknown Course';
    $courseYear = $course['year'] ?? '';
    
    foreach ($course['professors'] as $professor) {
        $professorNameJa = $professor['name']['ja'] ?? 'Unknown Professor';
        $professorNameEn = $professor['name']['en'] ?? 'Unknown Professor';
        
        // Add professor URL (if not already added) - we'll use English name without spaces
        $professorUrlFriendly = str_replace(' ', '', $professorNameEn);
        $professorUrls[$professorUrlFriendly] = "https://web.sfc.keio.ac.jp/~s23447ks/RateMyTeacher/{$professorUrlFriendly}";
        
        // Add course URL - format: ProfessorName/CourseName/Year/Language
        // Japanese version
        $courseUrls[] = [
            'url' => "https://web.sfc.keio.ac.jp/~s23447ks/RateMyTeacher/{$professorUrlFriendly}/" . 
                    urlencode($courseNameJa) . "/{$courseYear}/jp",
            'professor' => $professorNameJa,
            'course' => $courseNameJa,
            'year' => $courseYear,
            'lang' => 'jp'
        ];
        
        // English version
        $courseUrls[] = [
            'url' => "https://web.sfc.keio.ac.jp/~s23447ks/RateMyTeacher/{$professorUrlFriendly}/" . 
                    urlencode($courseNameEn) . "/{$courseYear}/en",
            'professor' => $professorNameEn,
            'course' => $courseNameEn,
            'year' => $courseYear,
            'lang' => 'en'
        ];
    }
}

// Print professor URLs (sample of 10)
echo "<h1>Sample Professor URLs</h1>\n";
echo "<ul>\n";
$count = 0;
foreach ($professorUrls as $urlKey => $url) {
    echo "<li><a href=\"{$url}\">{$url}</a></li>\n";
    $count++;
    if ($count >= 10) break;
}
echo "</ul>\n";

// Print course URLs (sample of 10)
echo "<h1>Sample Course URLs</h1>\n";
echo "<ul>\n";
for ($i = 0; $i < min(10, count($courseUrls)); $i++) {
    $courseUrl = $courseUrls[$i];
    echo "<li><a href=\"{$courseUrl['url']}\">{$courseUrl['professor']} - {$courseUrl['course']} ({$courseUrl['year']}) - {$courseUrl['lang']}</a></li>\n";
}
echo "</ul>\n";

// Output the .htaccess file content for reference
echo "<h1>.htaccess File Content</h1>\n";
echo "<pre>RewriteEngine On\n\n";
echo "# Rewrite professor URLs with just name\n";
echo "RewriteRule ^([^/]+)/?$ professor.php?name=$1 [L,QSA]\n\n";
echo "# Rewrite course URLs with format: ProfessorName/CourseName/Year/Language\n";
echo "RewriteRule ^([^/]+)/([^/]+)/([^/]+)/([^/]+)/?$ course_page_template.php?professor=$1&course=$2&year=$3&lang=$4 [L,QSA]\n\n";
echo "# Prevent direct access to templates\n";
echo "RewriteRule ^.*_template\.php$ - [F,L]</pre>\n";

// Generate sample htaccess testing file
$htaccessContent = "RewriteEngine On\n\n";
$htaccessContent .= "# Rewrite professor URLs with just name\n";
$htaccessContent .= "RewriteRule ^([^/]+)/?$ professor.php?name=$1 [L,QSA]\n\n";
$htaccessContent .= "# Rewrite course URLs with format: ProfessorName/CourseName/Year/Language\n";
$htaccessContent .= "RewriteRule ^([^/]+)/([^/]+)/([^/]+)/([^/]+)/?$ course_page_template.php?professor=$1&course=$2&year=$3&lang=$4 [L,QSA]\n\n";
$htaccessContent .= "# Prevent direct access to templates\n";
$htaccessContent .= "RewriteRule ^.*_template\.php$ - [F,L]\n";

file_put_contents(__DIR__ . '/.htaccess', $htaccessContent);
echo "<p>Created .htaccess file in the project directory.</p>";