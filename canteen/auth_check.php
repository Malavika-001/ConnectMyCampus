<?php
include_once __DIR__ . '/authentication.php';
/**
 * Canteen Authentication Checker
 * Ensures users are logged in and authorized for this module
 */
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Redirect if not logged in
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role'])) {
    header("Location: ../login.php?module=canteen");
    exit;
}

/**
 * Restrict access to admin-only pages
 */
function requireAdmin() {
    if ($_SESSION['role'] !== 'admin') {
        echo "<script>alert('Access Denied. Admin privileges required.'); window.location.href='user_dashboard.php';</script>";
        exit;
    }
}

/**
 * Restrict access to user-only pages
 */
function requireUser() {
    if ($_SESSION['role'] !== 'user') {
        echo "<script>alert('Access Denied. User privileges required.'); window.location.href='admin_dashboard.php';</script>";
        exit;
    }
}
?>
