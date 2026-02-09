<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}


// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: main_dashboard.php");
    exit();
}

// Check if the user role is NOT 'user'
if ($_SESSION['role'] !== 'user') {
    // Redirect admins or others back to their page
    header("Location: admin_dashboard.php");
    exit();
}
?>
