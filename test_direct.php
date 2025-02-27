<?php
// Direct test of the search system
require_once 'config.php';

$term = isset($_GET['q']) ? $_GET['q'] : 'introduction';
echo "<h1>Testing Search for: " . htmlspecialchars($term) . "</h1>";

try {
    // Connect directly to database
    echo "<h2>Database Connection Test:</h2>";
    if ($conn) {
        echo "<p>Database connection successful!</p>";
        
        // Test direct SQL execution
        $result = $conn->query("SELECT COUNT(*) FROM courses");
        $count = $result->fetchArray(SQLITE3_NUM)[0];
        echo "<p>Course count: " . $count . "</p>";
        
        // Test a direct query for the search term
        $stmt = $conn->prepare("SELECT * FROM courses WHERE name LIKE :term");
        $stmt->bindValue(':term', "%$term%", SQLITE3_TEXT);
        $result = $stmt->execute();
        
        echo "<h3>Direct SQL Results:</h3><ul>";
        $found = false;
        while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
            echo "<li>" . htmlspecialchars($row['name']) . " (" . htmlspecialchars($row['course_code']) . ")</li>";
            $found = true;
        }
        if (!$found) {
            echo "<li>No courses found</li>";
        }
        echo "</ul>";
    } else {
        echo "<p>Database connection failed!</p>";
    }
} catch (Exception $e) {
    echo "<p>Error: " . $e->getMessage() . "</p>";
}

echo "<h2>Search Test Object:</h2>";
echo "<p>Below is a test search button that will trigger a search in a new tab:</p>";
?>

<form action="search.php" method="get" target="_blank">
    <input type="text" name="query" value="<?php echo htmlspecialchars($term); ?>">
    <input type="submit" value="Test Search">
</form>

<h2>AJAX Test:</h2>
<button id="testAjax">Test AJAX Search</button>
<div id="result" style="border: 1px solid #ccc; padding: 10px; margin-top: 10px; min-height: 100px;"></div>

<script>
document.getElementById('testAjax').addEventListener('click', function() {
    const term = '<?php echo addslashes($term); ?>';
    const resultDiv = document.getElementById('result');
    
    resultDiv.innerHTML = 'Loading...';
    
    fetch(`search.php?query=${encodeURIComponent(term)}&t=${Date.now()}`)
        .then(response => {
            console.log('Status:', response.status);
            return response.text();
        })
        .then(text => {
            console.log('Response:', text);
            try {
                const data = JSON.parse(text);
                resultDiv.innerHTML = '<h3>Results:</h3><pre>' + JSON.stringify(data, null, 2) + '</pre>';
            } catch (e) {
                resultDiv.innerHTML = '<h3>Error parsing JSON:</h3><p>' + e.message + '</p><pre>' + text + '</pre>';
            }
        })
        .catch(error => {
            resultDiv.innerHTML = '<h3>Error:</h3><p>' + error.message + '</p>';
        });
});
</script>