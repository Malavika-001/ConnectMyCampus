<?php
session_start();
require_once "../db.php";   // ✅ connects to main campus db.php
require_once "auth_check_admin.php";

// Redirect if not logged in or not admin
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php?module=hygiene");
    exit;
}

$username = $_SESSION['username'] ?? 'Unknown';
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Admin Dashboard | Connect My Campus - Hygiene</title>
<style>
    body {
        font-family: 'Segoe UI', sans-serif;
        background: #e6f2ff;
        margin: 0;
        padding: 0;
    }
    header {
        background: #0077b6;
        color: white;
        padding: 15px 30px;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }
    header h1 {
        font-size: 22px;
        margin: 0;
    }
    header a {
        color: white;
        text-decoration: none;
        font-weight: bold;
        margin-left: 15px;
    }
    .container {
        padding: 40px;
        text-align: center;
    }
    h2 {
        color: #023e8a;
    }
    .card-container {
        display: flex;
        justify-content: center;
        flex-wrap: wrap;
        gap: 25px;
        margin-top: 30px;
    }
    .card {
        background: white;
        border-radius: 12px;
        box-shadow: 0 4px 10px rgba(0,0,0,0.15);
        width: 250px;
        padding: 25px;
        text-align: center;
        transition: 0.3s ease;
    }
    .card:hover {
        transform: scale(1.05);
        box-shadow: 0 6px 15px rgba(0,0,0,0.2);
    }
    .card a {
        text-decoration: none;
        color: #0077b6;
        font-weight: bold;
        font-size: 18px;
    }
    .logout {
        background: white;
        color: #0077b6;
        padding: 6px 14px;
        border-radius: 6px;
        text-decoration: none;
        border: 1px solid white;
        transition: 0.3s;
    }
    .logout:hover {
        background: #023e8a;
        color: white;
    }
</style>
</head>
<body>

<header>
    <h1>🏫 Connect My Campus — Hygiene Admin Panel</h1>
    <div>
        <span>Welcome, <strong><?= htmlspecialchars($username) ?></strong> 👋</span>
        <a class="logout" href="../logout.php">Logout</a>
    </div>
</header>

<div class="container">
    <h2>Admin Dashboard</h2>
    <p>Manage all hygiene-related modules from one place.</p>

    <div class="card-container">
        <div class="card">
            <a href="admin_schedules.php">🧹 Manage Cleaning Schedules</a>
        </div>
        <div class="card">
            <a href="admin_suggestions.php">💡 View Suggestions</a>
        </div>
        <div class="card">
            <a href="admin_reports.php">📋 View Reports</a>
        </div>
    </div>
</div>

</body>
</html>
