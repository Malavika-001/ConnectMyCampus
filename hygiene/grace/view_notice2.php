<?php
session_start();
include './db.php';

// Check login
if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit;
}

// Fetch all notices
$query = "SELECT * FROM notices ORDER BY created_at DESC";
$result = $conn->query($query);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Notice Board | SSV College Valayanchiragara</title>
<style>
    * {
        margin: 0;
        padding: 0;
        box-sizing: border-box;
        font-family: "Segoe UI", Tahoma, Geneva, Verdana, sans-serif;
    }

    body {
        background-color: #f4f8ff;
        color: #222;
    }

    header {
        background-color: #0d47a1;
        color: white;
        text-align: center;
        padding: 20px 0;
        font-size: 1.8rem;
        font-weight: bold;
        letter-spacing: 1px;
        box-shadow: 0 2px 6px rgba(0,0,0,0.2);
    }

    .container {
        width: 90%;
        max-width: 1100px;
        margin: 30px auto;
    }

    h2.section-title {
        color: #0d47a1;
        text-align: center;
        margin-bottom: 25px;
        font-size: 1.6rem;
        border-bottom: 2px solid #0d47a1;
        display: inline-block;
        padding-bottom: 5px;
    }

    .notice-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
        gap: 20px;
    }

    .notice-card {
        background-color: white;
        border-radius: 10px;
        box-shadow: 0 3px 8px rgba(0,0,0,0.1);
        padding: 20px;
        transition: all 0.3s ease;
    }

    .notice-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 5px 15px rgba(0,0,0,0.15);
    }

    .notice-title {
        color: #0d47a1;
        font-weight: 600;
        font-size: 1.2rem;
        margin-bottom: 10px;
    }

    .notice-desc {
        color: #333;
        font-size: 0.95rem;
        margin-bottom: 15px;
        line-height: 1.4em;
    }

    .notice-meta {
        font-size: 0.85rem;
        color: #555;
        margin-bottom: 10px;
    }

    .btn {
        display: inline-block;
        text-decoration: none;
        background-color: #0d47a1;
        color: white;
        padding: 8px 15px;
        border-radius: 5px;
        transition: background 0.3s;
        font-size: 0.9rem;
    }

    .btn:hover {
        background-color: #1565c0;
    }

    footer {
        text-align: center;
        background-color: #0d47a1;
        color: white;
        padding: 12px;
        margin-top: 40px;
        font-size: 0.9rem;
    }

    @media (max-width: 600px) {
        header {
            font-size: 1.4rem;
        }
        .notice-card {
            padding: 15px;
        }
    }
</style>
</head>
<body>

<header>
    SSV College Valayanchiragara<br>
    <span style="font-size:1rem; font-weight:normal;">Notice Board</span>
</header>

<div class="container">
    <h2 class="section-title">Latest Notices</h2>
    <div class="notice-grid">
        <?php if ($result && $result->num_rows > 0): ?>
            <?php while($row = $result->fetch_assoc()): ?>
                <div class="notice-card">
                    <div class="notice-title"><?= htmlspecialchars($row['title']); ?></div>
                    <div class="notice-meta">
                        📅 <?= date("d M Y, h:i A", strtotime($row['created_at'])); ?>
                    </div>
                    <div class="notice-desc">
                        <?= nl2br(htmlspecialchars($row['description'])); ?>
                    </div>
                    <?php if (!empty($row['file_path'])): ?>
                        <a href="<?= htmlspecialchars($row['file_path']); ?>" class="btn" target="_blank">View</a>
<?php if (preg_match('/\.(jpg|jpeg|png|gif)$/i', $row['file_path'])): ?>
    <img src="<?= htmlspecialchars($row['file_path']); ?>" alt="Poster" style="width:100%; border-radius:8px; margin-bottom:10px;">
<?php endif; ?>

<a href="<?= htmlspecialchars($row['file_path']); ?>" class="btn" download>Download</a>

                    <?php endif; ?>
                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <p style="text-align:center; color:#555;">No notices available.</p>
        <?php endif; ?>
    </div>
</div>

<footer>
    &copy; <?= date('Y'); ?> SSV College Valayanchiragara. All Rights Reserved.
</footer>

</body>
</html>
