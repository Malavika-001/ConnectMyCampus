<?php
// auth_check_superadmin.php
// Protects superadmin pages. Place at top of pages that only superadmin should access.

if (session_status() === PHP_SESSION_NONE) session_start();

// You said you have a superadmin username/password. This check accepts either:
// - $_SESSION['role'] === 'superadmin' OR
// - $_SESSION['username'] === 'superadmin' (fallback)
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'superadmin') {
    if (!isset($_SESSION['username']) || $_SESSION['username'] !== 'superadmin') {
        // Not a superadmin — redirect to main login
        header("Location: login.php");
        exit;
    }
}

// All good — superadmin is authenticated
