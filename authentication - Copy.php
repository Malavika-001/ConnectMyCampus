<?php
/**
 * Authentication and Session Management
 * Manages user session, role, and login/logout (simplified for this module).
 */
session_start();

// --- Simplified Mock Login ---
// This is a minimal implementation. A real system requires a proper login page.
// For testing, uncomment and set the session variables.

// // To test as a student:
// $_SESSION['user_id'] = 'S1001';
//  $_SESSION['user_role'] = 'student';

// To test as an admin:
$_SESSION['user_id'] = 'A0001';
$_SESSION['user_role'] = 'admin';


/**
 * Checks if a user is logged in.
 * @return bool True if logged in, false otherwise.
 */
function isLoggedIn() {
    return isset($_SESSION['user_id']) && isset($_SESSION['user_role']);
}

/**
 * Checks if the logged-in user is an admin.
 * @return bool True if admin, false otherwise.
 */
function isAdmin() {
    return isLoggedIn() && $_SESSION['user_role'] === 'admin';
}

/**
 * Redirects non-admin users away from admin pages.
 * @param bool $is_admin_page Set to true for files only admins should see.
 */
function requireAuth($is_admin_page = false) {
    if (!isLoggedIn()) {
        // In a real app, redirect to a login page
        // header('Location: /login.php');
        // exit();
        
        // For this module demo:
        echo "<script>alert('Please log in to use this feature.'); window.location.href='index.php';</script>";
        exit();
    }
    
    if ($is_admin_page && !isAdmin()) {
        echo "<script>alert('Access Denied. Admin privileges required.'); window.location.href='index.php';</script>";
        exit();
    }
}