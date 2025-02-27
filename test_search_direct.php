<?php
// Direct test script - bypassing AJAX
require_once 'config.php';
require_once 'search.php';

$term = "Introduction";

// Output results directly
$results = search($term);
echo "<h1>Search Results for: $term</h1>";
echo "<pre>";
var_dump($results);
echo "</pre>";
?>