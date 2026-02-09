<?php
// campus/auth_check_user.php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Ensure only logged-in users can access user pages
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role'])) {
    header("Location: /campus/login.php?module=hygiene");
    exit;
}

if ($_SESSION['role'] !== 'user') {
    // redirect admins or others to main dashboard
    header("Location: /campus/main_dashboard.php");
    exit;
}
?>
