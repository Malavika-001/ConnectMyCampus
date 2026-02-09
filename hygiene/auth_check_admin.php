<?php
// campus/auth_check_admin.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id']) || !isset($_SESSION['role'])) {
    header("Location: /campus/login.php");
    exit;
}
if ($_SESSION['role'] !== 'admin') {
    header("Location: /campus/main_dashboard.php");
    exit;
}
