<?php
// Simple professor page for direct testing
$professorName = isset($_GET['name']) ? $_GET['name'] : 'No name provided';

echo "<h1>Professor Test Page</h1>";
echo "<p>Professor name: " . htmlspecialchars($professorName) . "</p>";

// Load JSON data file
$jsonData = file_get_contents(__DIR__ . '/sfc_courses.json');
$data = json_decode($jsonData, true);

// Find the professor
$foundProfessor = false;
foreach ($data['courses'] as $course) {
    foreach ($course['professors'] as $professor) {
        // Check by English name without spaces
        $professorUrlName = isset($professor['name']['en']) ? str_replace(' ', '', $professor['name']['en']) : '';
        if ($professorUrlName === $professorName) {
            echo "<h2>Found Professor!</h2>";
            echo "<p>Japanese name: " . htmlspecialchars($professor['name']['ja']) . "</p>";
            echo "<p>English name: " . htmlspecialchars($professor['name']['en']) . "</p>";
            $foundProfessor = true;
            break 2;
        }
    }
}

if (!$foundProfessor) {
    echo "<p>Professor not found in the database.</p>";
}
?>