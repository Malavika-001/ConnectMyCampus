<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Remove all session variables
session_unset();

// Destroy the session completely
session_destroy();

// Redirect to login page
header("Location: login.php");
exit();
?>
