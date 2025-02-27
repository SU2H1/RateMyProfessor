<?php
// Full debugging of the search query
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>Search Debug Tool</h1>";

$search_term = isset($_GET['q']) ? $_GET['q'] : 'POLICY';
echo "<p>Searching for: <strong>" . htmlspecialchars($search_term) . "</strong></p>";

// Test database connection
$db_path = __DIR__ . '/database/ratemyteacher.db';
echo "<p>Database path: " . $db_path . "</p>";
echo "<p>Database exists: " . (file_exists($db_path) ? "Yes" : "No") . "</p>";

if (file_exists($db_path)) {
    try {
        $db = new SQLite3($db_path);
        echo "<p style='color:green'>✓ Successfully connected to database</p>";
        
        // Check tables
        echo "<h2>Database Structure</h2>";
        $tables = [];
        $result = $db->query("SELECT name FROM sqlite_master WHERE type='table'");
        echo "<ul>";
        while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
            echo "<li>" . $row['name'] . "</li>";
            $tables[] = $row['name'];
        }
        echo "</ul>";
        
        // Check data in professors
        if (in_array('professors', $tables)) {
            echo "<h2>Sample Professors Data</h2>";
            $result = $db->query("SELECT * FROM professors LIMIT 5");
            
            echo "<table border='1' cellpadding='5'>";
            echo "<tr><th>ID</th><th>Name</th><th>Department</th><th>Bio</th></tr>";
            
            $found = false;
            while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
                echo "<tr>";
                echo "<td>" . $row['id'] . "</td>";
                echo "<td>" . $row['name'] . "</td>";
                echo "<td>" . $row['department'] . "</td>";
                echo "<td>" . (isset($row['bio']) ? $row['bio'] : 'N/A') . "</td>";
                echo "</tr>";
                $found = true;
            }
            
            if (!$found) {
                echo "<tr><td colspan='4'>No professors found</td></tr>";
            }
            
            echo "</table>";
            
            // Count professors
            $result = $db->query("SELECT COUNT(*) FROM professors");
            $count = $result->fetchArray(SQLITE3_NUM)[0];
            echo "<p>Total professors: " . $count . "</p>";
        }
        
        // Check data in courses
        if (in_array('courses', $tables)) {
            echo "<h2>Sample Courses Data</h2>";
            $result = $db->query("SELECT * FROM courses LIMIT 5");
            
            echo "<table border='1' cellpadding='5'>";
            echo "<tr><th>ID</th><th>Name</th><th>Code</th><th>Description</th><th>Professor ID</th></tr>";
            
            $found = false;
            while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
                echo "<tr>";
                echo "<td>" . $row['id'] . "</td>";
                echo "<td>" . $row['name'] . "</td>";
                echo "<td>" . $row['course_code'] . "</td>";
                echo "<td>" . (isset($row['description']) ? $row['description'] : 'N/A') . "</td>";
                echo "<td>" . $row['professor_id'] . "</td>";
                echo "</tr>";
                $found = true;
            }
            
            if (!$found) {
                echo "<tr><td colspan='5'>No courses found</td></tr>";
            }
            
            echo "</table>";
            
            // Count courses
            $result = $db->query("SELECT COUNT(*) FROM courses");
            $count = $result->fetchArray(SQLITE3_NUM)[0];
            echo "<p>Total courses: " . $count . "</p>";
        }
        
        // Perform the search query for professors
        echo "<h2>Search Results for Professors</h2>";
        echo "<p>Query: SELECT * FROM professors WHERE LOWER(name) LIKE LOWER('%{$search_term}%') OR LOWER(department) LIKE LOWER('%{$search_term}%')</p>";
        
        $stmt = $db->prepare("SELECT * FROM professors WHERE LOWER(name) LIKE LOWER(?) OR LOWER(department) LIKE LOWER(?)");
        $stmt->bindValue(1, "%$search_term%", SQLITE3_TEXT);
        $stmt->bindValue(2, "%$search_term%", SQLITE3_TEXT);
        $result = $stmt->execute();
        
        echo "<table border='1' cellpadding='5'>";
        echo "<tr><th>ID</th><th>Name</th><th>Department</th><th>Bio</th></tr>";
        
        $found = false;
        while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
            echo "<tr>";
            echo "<td>" . $row['id'] . "</td>";
            echo "<td>" . $row['name'] . "</td>";
            echo "<td>" . $row['department'] . "</td>";
            echo "<td>" . (isset($row['bio']) ? $row['bio'] : 'N/A') . "</td>";
            echo "</tr>";
            $found = true;
        }
        
        if (!$found) {
            echo "<tr><td colspan='4'>No matching professors found</td></tr>";
        }
        
        echo "</table>";
        
        // Perform the search query for courses
        echo "<h2>Search Results for Courses</h2>";
        echo "<p>Query: SELECT * FROM courses WHERE LOWER(name) LIKE LOWER('%{$search_term}%') OR LOWER(course_code) LIKE LOWER('%{$search_term}%') OR LOWER(description) LIKE LOWER('%{$search_term}%')</p>";
        
        $stmt = $db->prepare("SELECT * FROM courses WHERE LOWER(name) LIKE LOWER(?) OR LOWER(course_code) LIKE LOWER(?) OR LOWER(description) LIKE LOWER(?)");
        $stmt->bindValue(1, "%$search_term%", SQLITE3_TEXT);
        $stmt->bindValue(2, "%$search_term%", SQLITE3_TEXT);
        $stmt->bindValue(3, "%$search_term%", SQLITE3_TEXT);
        $result = $stmt->execute();
        
        echo "<table border='1' cellpadding='5'>";
        echo "<tr><th>ID</th><th>Name</th><th>Code</th><th>Description</th><th>Professor ID</th></tr>";
        
        $found = false;
        while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
            echo "<tr>";
            echo "<td>" . $row['id'] . "</td>";
            echo "<td>" . $row['name'] . "</td>";
            echo "<td>" . $row['course_code'] . "</td>";
            echo "<td>" . (isset($row['description']) ? $row['description'] : 'N/A') . "</td>";
            echo "<td>" . $row['professor_id'] . "</td>";
            echo "</tr>";
            $found = true;
        }
        
        if (!$found) {
            echo "<tr><td colspan='5'>No matching courses found</td></tr>";
        }
        
        echo "</table>";
        
    } catch (Exception $e) {
        echo "<p style='color:red'>Error connecting to database: " . $e->getMessage() . "</p>";
    }
} else {
    echo "<p style='color:red'>Database file not found at: " . $db_path . "</p>";
}

// Test the search function
echo "<h2>Testing API Endpoint Response</h2>";
echo "<p>This shows exactly what the API endpoint returns for your query.</p>";

$api_url = "http://" . $_SERVER['HTTP_HOST'] . dirname($_SERVER['PHP_SELF']) . "/simple_search.php?query=" . urlencode($search_term);
echo "<p>API URL: <a href='" . $api_url . "' target='_blank'>" . $api_url . "</a></p>";

if (function_exists('curl_init')) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $api_url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
    $response = curl_exec($ch);
    $info = curl_getinfo($ch);
    $error = curl_error($ch);
    curl_close($ch);
    
    echo "<p>HTTP Status: " . $info['http_code'] . "</p>";
    if ($error) {
        echo "<p>cURL Error: " . $error . "</p>";
    }
    
    echo "<pre style='background:#f5f5f5; padding:10px; overflow-x:auto;'>";
    echo htmlspecialchars($response);
    echo "</pre>";
    
    try {
        $data = json_decode($response, true);
        if ($data === null && json_last_error() !== JSON_ERROR_NONE) {
            echo "<p>Error parsing JSON: " . json_last_error_msg() . "</p>";
        } else {
            echo "<p>Successfully parsed JSON. Results:</p>";
            echo "<ul>";
            echo "<li>Professors: " . count($data['professors']) . "</li>";
            echo "<li>Courses: " . count($data['courses']) . "</li>";
            echo "</ul>";
        }
    } catch (Exception $e) {
        echo "<p>Error processing JSON: " . $e->getMessage() . "</p>";
    }
} else {
    echo "<p>cURL not available. Cannot test API endpoint.</p>";
}

// JavaScript test
echo "<h2>JavaScript Fetch Test</h2>";
echo "<p>This tests if the frontend JavaScript fetch would work</p>";
?>

<div id="js-result" style="background: #f5f5f5; padding: 10px; margin-top: 10px; min-height: 100px;">
    <p>Results will appear here when you click the button.</p>
</div>
<button id="test-fetch" style="margin-top: 10px; padding: 5px 10px;">Test Search with JavaScript</button>

<script>
document.getElementById('test-fetch').addEventListener('click', function() {
    const resultDiv = document.getElementById('js-result');
    resultDiv.innerHTML = '<p>Loading...</p>';
    
    fetch('simple_search.php?query=<?php echo urlencode($search_term); ?>', {
        method: 'GET',
        headers: {
            'Cache-Control': 'no-cache',
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(response => {
        resultDiv.innerHTML += '<p>Response status: ' + response.status + '</p>';
        return response.text();
    })
    .then(text => {
        resultDiv.innerHTML += '<p>Raw response:</p><pre>' + text + '</pre>';
        
        try {
            const data = JSON.parse(text);
            resultDiv.innerHTML += '<p>Successfully parsed JSON. Found:</p>' +
                                  '<ul>' +
                                  '<li>Professors: ' + data.professors.length + '</li>' +
                                  '<li>Courses: ' + data.courses.length + '</li>' +
                                  '</ul>';
        } catch (e) {
            resultDiv.innerHTML += '<p>Error parsing JSON: ' + e.message + '</p>';
        }
    })
    .catch(error => {
        resultDiv.innerHTML = '<p>Error: ' + error.message + '</p>';
    });
});
</script>