<?php
// auth_check_superadmin.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Ensure user is logged in
if (!isset($_SESSION['username']) || !isset($_SESSION['role'])) {
    header("Location: login.php");
    exit;
}

// Allow only super admins
if ($_SESSION['role'] !== 'superadmin') {
    echo "<script>alert('Access denied! Super Admins only.'); window.location.href='login.php';</script>";
    exit;
}
?>
