<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: main_dashboard.php");
    exit();
}

// Check if the role is admin
if ($_SESSION['role'] !== 'admin') {
    // Redirect normal users to their dashboard
    header("Location: user_dashboard.php");
    exit();
}
?>
