<?php
ob_start(); // Fix: Prevent "Headers already sent" errors
session_start();

// Clear all session variables
$_SESSION = [];

// Destroy the session
session_destroy();

// Redirect to login page
header('Location: login.php');
exit;
?>
