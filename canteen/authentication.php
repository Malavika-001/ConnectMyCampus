<?php
/**
 * Canteen Authentication and Role Handling
 * Uses the global login system
 */
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Define only if not already defined
if (!function_exists('isLoggedIn')) {
    function isLoggedIn() {
        return isset($_SESSION['user_id']) && isset($_SESSION['role']);
    }
}

if (!function_exists('isAdmin')) {
    function isAdmin() {
        return isLoggedIn() && $_SESSION['role'] === 'admin';
    }
}

if (!function_exists('requireAuth')) {
    function requireAuth($is_admin_page = false) {
        if (!isLoggedIn()) {
            header("Location: ../login.php?module=canteen");
            exit;
        }
        if ($is_admin_page && !isAdmin()) {
            echo "<script>alert('Access Denied. Admin privileges required.'); window.location.href='user_dashboard.php';</script>";
            exit;
        }
    }
}
?>
