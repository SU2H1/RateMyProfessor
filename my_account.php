<?php
// my_account.php - Redirect to the new account section page
session_start();

// Check if user is logged in
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("location: login.php");
    exit;
}

// Redirect to the new account section page
header("location: account-section.php");
exit;
?>