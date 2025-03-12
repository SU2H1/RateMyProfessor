<?php
/**
 * Course ID Updater
 * 
 * This script reads the SFC courses JSON file, replaces each course_id
 * with a unique sequential 9-digit code, and preserves the original ID
 * in a new field called 'original_course_id'.
 */

// Path to the JSON file
$jsonFilePath = __DIR__ . '/sfc_courses.json';

// Read the JSON file
$jsonContent = file_get_contents($jsonFilePath);
if (!$jsonContent) {
    echo "Error reading the JSON file: $jsonFilePath\n";
    exit(1);
}

// Decode the JSON content
$data = json_decode($jsonContent, true);
if (json_last_error() !== JSON_ERROR_NONE) {
    echo "Error parsing JSON: " . json_last_error_msg() . "\n";
    exit(1);
}

// Make sure we have the expected structure
if (!isset($data['courses']) || !is_array($data['courses'])) {
    echo "Error: The JSON file does not contain a 'courses' array.\n";
    exit(1);
}

// Generate new unique 9-digit codes sequentially
$startingCode = 100000000; // Smallest 9-digit number
$courses = &$data['courses']; // Use reference to directly modify the courses
$courseCount = count($courses);

// Update course_id for each course
for ($i = 0; $i < $courseCount; $i++) {
    $newCode = strval($startingCode + $i);
    
    // Check if newCode has 9 digits (it should, but let's double-check)
    if (strlen($newCode) != 9) {
        echo "Warning: Generated code $newCode does not have 9 digits.\n";
    }
    
    // Store the original course_id in a new field
    if (isset($courses[$i]['course_id'])) {
        $originalId = $courses[$i]['course_id'];
        $courses[$i]['original_course_id'] = $originalId;
        echo "Preserving original ID: $originalId as original_course_id\n";
    }
    
    // Update the course_id
    $courses[$i]['course_id'] = $newCode;
    
    echo "Updated course: " . ($originalId ?? 'None') . " -> $newCode\n";
}

// Update the metadata
if (isset($data['meta'])) {
    $data['meta']['generated_date'] = date('Y-m-d H:i:s');
    $data['meta']['last_update_fields'] = 'Course IDs updated to unique 9-digit codes, original IDs preserved in original_course_id';
}

// JSON encoding options
$jsonOptions = JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES;

// Encode the updated data
$updatedJsonContent = json_encode($data, $jsonOptions);
if ($updatedJsonContent === false) {
    echo "Error encoding JSON: " . json_last_error_msg() . "\n";
    exit(1);
}

// Write the updated data back to the JSON file
$result = file_put_contents($jsonFilePath, $updatedJsonContent);
if ($result === false) {
    echo "Error writing to file: $jsonFilePath\n";
    exit(1);
}

echo "Updated $courseCount courses with new unique 9-digit codes.\n";
echo "Original course IDs preserved in 'original_course_id' field.\n";
echo "Data saved to $jsonFilePath (" . strlen($updatedJsonContent) . " bytes)\n";