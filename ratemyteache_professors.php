<?php
// Simple redirect to the instructors page with professors section
$lang = isset($_GET['lang']) ? $_GET['lang'] : 'en';
header("Location: ratemyteacher-instructions.php?section=professors&lang=$lang");
exit;
?>