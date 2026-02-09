<?php
session_start();

// If not logged in → go to common login
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role'])) {
    header("Location: /campus/login.php?module=notice");
    exit;
}

// Logged-in users
$role = $_SESSION['role'];

if ($role === 'admin') {
    header("Location: /campus/notice/admin_notice_dashboard.php");
    exit;
} else {
    header("Location: /campus/notice/view_notice2.php");
    exit;
}
?>
