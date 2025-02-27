<?php
// Simple redirect to the instructors page with courses section
$lang = isset($_GET['lang']) ? $_GET['lang'] : 'en';
header("Location: ratemyteacher-instructions.php?section=courses&lang=$lang");
exit;
?>