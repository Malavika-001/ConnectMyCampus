<?php
include_once __DIR__ . '/authentication.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $pageTitle ?? 'Canteen Service'; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <style>
        body {
            background-color: #f7f9fc;
            font-family: 'Poppins', sans-serif;
        }
        .service-card {
            cursor: pointer;
            transition: transform 0.2s, box-shadow 0.2s;
            min-height: 160px;
            padding: 15px;
            border: 1px solid #e4e9f0;
            border-radius: 12px;
            background: #ffffff;
        }
        .service-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        }
        .icon-lg { font-size: 3rem; margin-bottom: 10px; }
        .section-title {
            font-size: 1.5rem;
            color: #007bff;
            border-bottom: 2px solid #007bff;
            padding-bottom: 0.4rem;
            margin-bottom: 2rem;
            display: inline-block;
        }
        .about-box {
            background-color: #ffffff;
            border-left: 4px solid #007bff;
            padding: 2rem;
            border-radius: 10px;
            box-shadow: 0 4px 10px rgba(0,0,0,0.05);
        }
    </style>
</head>
<body>
<div class="container my-4">
    <header class="text-center py-3 mb-4 border-bottom">
        <h1>College Canteen Service 🍽️</h1>
        <?php if (isLoggedIn()): ?>
            <div class="alert alert-info p-2 mt-2">
                Logged in as: <strong><?= $_SESSION['username']; ?> (<?= ucfirst($_SESSION['role']); ?>)</strong>
            </div>
        <?php else: ?>
            <div class="alert alert-warning p-2 mt-2">
                Not Logged In. <a href="../login.php?module=canteen" class="alert-link">Login</a>
            </div>
        <?php endif; ?>
    </header>
