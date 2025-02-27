<?php
// Direct test of the API request using curl
$term = isset($_GET['q']) ? $_GET['q'] : 'POLICY';

echo "<h1>Testing Direct API Request for: " . htmlspecialchars($term) . "</h1>";

$url = "http://" . $_SERVER['HTTP_HOST'] . dirname($_SERVER['PHP_SELF']) . "/simple_search.php?query=" . urlencode($term);

echo "<p>Request URL: " . htmlspecialchars($url) . "</p>";

// Make a request to our own API
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
$response = curl_exec($ch);
$status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "<h2>Response Status: " . $status . "</h2>";
echo "<h2>Raw Response:</h2>";
echo "<pre style='background-color: #f5f5f5; padding: 10px; overflow-x: auto;'>";
echo htmlspecialchars($response);
echo "</pre>";

// Try to parse the JSON
echo "<h2>Parsed Response:</h2>";
try {
    $data = json_decode($response, true);
    if ($data === null && json_last_error() !== JSON_ERROR_NONE) {
        echo "<p>Error parsing JSON: " . json_last_error_msg() . "</p>";
    } else {
        echo "<pre style='background-color: #f5f5f5; padding: 10px; overflow-x: auto;'>";
        print_r($data);
        echo "</pre>";
        
        echo "<h3>Found " . count($data['professors']) . " professors and " . count($data['courses']) . " courses</h3>";
    }
} catch (Exception $e) {
    echo "<p>Exception: " . $e->getMessage() . "</p>";
}
?>