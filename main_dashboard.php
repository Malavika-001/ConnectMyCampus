<?php
session_start();
require_once "db.php";

// Detect login state
$logged_in = isset($_SESSION['username']);
$username = $_SESSION['username'] ?? '';
$role = $_SESSION['role'] ?? '';
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Main Dashboard | Connect My Campus</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
<style>
    body {
        margin: 0;
        font-family: 'Segoe UI', sans-serif;
        background: #e6f4ff;
        color: #023047;
    }
    header {
        background: #0077b6;
        color: white;
        padding: 18px 30px;
        display: flex;
        justify-content: space-between;
        align-items: center;
        box-shadow: 0 2px 8px rgba(0,0,0,0.15);
    }
    header h1 {
        margin: 0;
        font-size: 1.8rem;
        font-weight: 600;
    }
    header .user-info {
        font-size: 1rem;
    }
    header a {
        color: #fff;
        text-decoration: none;
        margin-left: 20px;
        font-weight: 600;
    }
    main {
        max-width: 1200px;
        margin: 40px auto;
        text-align: center;
    }
    h2 {
        color: #0077b6;
        font-weight: 600;
        font-size: 1.6rem;
        margin-bottom: 30px;
    }
    .grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
        gap: 30px;
        justify-content: center;
        padding: 0 20px;
    }
    .module {
        background: white;
        border-radius: 12px;
        box-shadow: 0 3px 10px rgba(0,0,0,0.1);
        padding: 30px 20px;
        transition: all 0.3s;
        cursor: pointer;
        text-decoration: none;
        color: #023047;
    }
    .module:hover {
        transform: translateY(-5px);
        box-shadow: 0 6px 15px rgba(0,0,0,0.15);
    }
    .module i {
        font-size: 40px;
        color: #0077b6;
        margin-bottom: 10px;
    }
    .module h3 {
        margin: 10px 0 0;
        font-size: 1.2rem;
        color: #023047;
    }
    footer {
        text-align: center;
        padding: 20px;
        color: #555;
        font-size: 0.9rem;
        background: #f8f9fa;
        border-top: 1px solid #dceeff;
        margin-top: 50px;
    }
    .logout-btn {
        background: #ff4d4f;
        color: white;
        border: none;
        padding: 8px 14px;
        border-radius: 8px;
        font-weight: 600;
        cursor: pointer;
        transition: 0.3s;
    }
    .logout-btn:hover {
        background: #d62828;
    }
</style>
</head>
<body>

<header>
    <h1>Connect My Campus</h1>
    <div class="user-info">
        <?php if ($logged_in): ?>
            👋 Welcome, <strong><?= htmlspecialchars($username) ?></strong>
            <a href="logout.php" class="logout-btn">Logout</a>
        <?php else: ?>
            <a href="login.php">Login</a>
        <?php endif; ?>
    </div>
</header>

<main>
    <h2>Select a Module</h2>

    <div class="grid">
        <!-- Hygiene -->
        <a class="module" href="login.php?module=hygiene">
            <i class="fa-solid fa-broom"></i>
            <h3>Hygiene Portal</h3>
            <p style="color:#555;">Clean campus management & weekly schedules.</p>
        </a>

        <!-- Canteen -->
        <a class="module" href="login.php?module=canteen">
            <i class="fa-solid fa-utensils"></i>
            <h3>Canteen Management</h3>
            <p style="color:#555;">Food service and feedback monitoring.</p>
        </a>

<!-- Gym -->
<a class="module" href="/campus/gym/login.php?module=gym">
    <i class="fa-solid fa-dumbbell"></i>
    <h3>Gym Portal</h3>
    <p style="color:#555;">Fitness tracking & member access.</p>
</a>
       <!-- Notice Board -->
<a class="module" href="/campus/login.php?module=notice">
    <i class="fa-solid fa-bell"></i>
    <h3>Notice Board</h3>
    <p style="color:#555;">Instant updates & announcements</p>
</a>
    </div>
</main>

<footer>
    © <?= date("Y") ?> Connect My Campus. All rights reserved.
</footer>

</body>
</html>
