<?php
session_start();
require_once __DIR__ . '/../auth_check_user.php';
require_once __DIR__ . '/../db.php';

// Redirect if not logged in
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'user') {
    header("Location: /campus/login.php?module=hygiene");
    exit;
}

$username = $_SESSION['username'] ?? 'User';

// Fetch user details
$stmt = $pdo->prepare("SELECT id, name, department, year FROM users WHERE username = ?");
$stmt->execute([$username]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>User Dashboard | Connect My Campus - Hygiene</title>
<style>
    body {
        font-family: 'Segoe UI', sans-serif;
        background: linear-gradient(120deg, #e0f7ff, #ffffff);
        margin: 0;
        padding: 0;
        text-align: center;
        color: #023047;
    }
    h1 {
        margin-top: 40px;
        font-size: 2.8rem;
        color: #0077b6;
        text-shadow: 1px 1px 2px rgba(0,0,0,0.1);
    }
    h2 {
        color: #023e8a;
        margin-top: -10px;
        font-weight: 500;
    }
    .tagline {
        font-size: 1rem;
        color: #555;
        margin-top: 6px;
        font-style: italic;
    }
    .user-info {
        margin: 40px auto;
        background: white;
        border-radius: 12px;
        box-shadow: 0 3px 8px rgba(0,0,0,0.1);
        width: 60%;
        padding: 25px;
    }
    .user-info p {
        font-size: 1.1rem;
        margin: 8px 0;
    }
    .divider {
        width: 60%;
        height: 2px;
        background: #90e0ef;
        margin: 40px auto 20px;
        border-radius: 2px;
    }
    .description {
        background: white;
        width: 65%;
        margin: 20px auto;
        padding: 25px;
        border-radius: 12px;
        box-shadow: 0 3px 10px rgba(0,0,0,0.1);
        line-height: 1.6;
        font-size: 1.05rem;
        color: #023047;
    }
    .links {
        margin-top: 40px;
    }
    .links a {
        display: inline-block;
        margin: 10px;
        padding: 12px 25px;
        background: #0077b6;
        color: white;
        text-decoration: none;
        border-radius: 8px;
        font-weight: 500;
        transition: background 0.3s;
    }
    .links a:hover {
        background: #023e8a;
    }
    footer {
        margin-top: 50px;
        color: #555;
        font-size: 0.9rem;
    }
    @media(max-width:800px){
        .user-info, .description { width: 85%; }
    }
</style>
</head>
<body>

<h1>Connect My Campus</h1>
<h2>Hygiene Portal – User Dashboard</h2>
<p class="tagline">A space to stay updated, share ideas, and report issues ✨</p>

<div class="user-info">
    <p><strong>Welcome, <?= htmlspecialchars($user['name'] ?? $username) ?>!</strong></p>
    <p>Department: <?= htmlspecialchars($user['department'] ?? 'N/A') ?> | Year: <?= htmlspecialchars($user['year'] ?? 'N/A') ?></p>
</div>

<div class="divider"></div>

<div class="description">
    Here, you can view cleaning schedules, submit improvement ideas, and report hygiene-related issues.  
    Together, we can build a cleaner and greener campus 🌱
</div>

<div class="links">
    <a href="user_schedules.php">🗓 View Cleaning Schedules</a>
    <a href="user_suggestions.php">💡 Submit Suggestions</a>
    <a href="user_reports.php">📝 Report Issues</a>
    <a href="../logout.php">🚪 Logout</a>
</div>

<footer>
    © <?= date("Y") ?> Connect My Campus. All rights reserved.
</footer>

</body>
</html>
