<?php
// Start session only if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Checks if a user is logged in.
 * @return bool True if logged in, false otherwise.
 */
if (!function_exists('isLoggedIn')) {
    function isLoggedIn() {
        return isset($_SESSION['user_id']) && isset($_SESSION['user_role']);
    }
}

/**
 * Checks if the logged-in user is an admin.
 * @return bool True if admin, false otherwise.
 */
if (!function_exists('isAdmin')) {
    function isAdmin() {
        return isLoggedIn() && $_SESSION['user_role'] === 'admin';
    }
}

/**
 * Redirects non-admin users away from admin pages.
 * @param bool $is_admin_page Set to true for files only admins should see.
 */
if (!function_exists('requireAuth')) {
    function requireAuth($is_admin_page = false) {
        if (!isLoggedIn()) {
            echo "<script>alert('Please log in to use this feature.'); window.location.href='index.php';</script>";
            exit();
        }

        if ($is_admin_page && !isAdmin()) {
            echo "<script>alert('Access Denied. Admin privileges required.'); window.location.href='index.php';</script>";
            exit();
        }
    }
}
?>

