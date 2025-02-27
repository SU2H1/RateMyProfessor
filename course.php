<?php
/**
 * Course Detail Page
 * This file redirects to the course_page_template.php file
 */

// Debugging - log all parameters
error_log("Course.php accessed with parameters: " . print_r($_GET, true));

// Get course info from URL - support both formats
$course_id = isset($_GET['id']) ? $_GET['id'] : null;
$course_name = isset($_GET['course']) ? $_GET['course'] : '';
$professor_name = isset($_GET['professor']) ? $_GET['professor'] : '';
$language = isset($_GET['lang']) ? $_GET['lang'] : 'en';
$year = isset($_GET['year']) ? $_GET['year'] : null;

// Build the URL for the redirect
$redirect_url = 'course_page_template.php?';

// Add parameters to the URL
if (!empty($course_id)) {
    $redirect_url .= 'id=' . urlencode($course_id) . '&';
}
if (!empty($course_name)) {
    $redirect_url .= 'course=' . urlencode($course_name) . '&';
}
if (!empty($professor_name)) {
    $redirect_url .= 'professor=' . urlencode($professor_name) . '&';
}
if (!empty($year)) {
    $redirect_url .= 'year=' . urlencode($year) . '&';
}
$redirect_url .= 'lang=' . urlencode($language);

// Redirect to the template
header("Location: $redirect_url");
exit;