<?php
// campus/auth_check_user.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// If not logged in as user, send to login and remember module
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role'])) {
    header("Location: /campus/login.php");
    exit;
}
if ($_SESSION['role'] !== 'user') {
    // if admin accidentally hit a user page, send to admin dashboard of current module (best-effort)
    header("Location: /campus/main_dashboard.php");
    exit;
}

// Optionally: you can load user info into $CURRENT_USER for convenience
// require_once __DIR__ . '/db.php'; // if you need DB here uncomment
// $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ? LIMIT 1"); ...
