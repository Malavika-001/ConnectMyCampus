<?php
session_start();
include './db.php';

// Access control
if (!isset($_SESSION['username']) || $_SESSION['role'] != 'admin') {
    header("Location: login.php");
    exit;
}

// Check if ID is provided
if (!isset($_GET['id'])) {
    header("Location: dashboard.php");
    exit;
}

$id = intval($_GET['id']);
$query = $conn->prepare("SELECT * FROM notices WHERE id = ?");
$query->bind_param("i", $id);
$query->execute();
$result = $query->get_result();
$notice = $result->fetch_assoc();

if (!$notice) {
    die("Notice not found!");
}

// Delete confirmation
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['confirm'])) {
        // Delete file if exists
        if (!empty($notice['file_path']) && file_exists($notice['file_path'])) {
            unlink($notice['file_path']);
        }

        // Delete notice record
        $stmt = $conn->prepare("DELETE FROM notices WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();

        header("Location: dashboard.php?msg=Notice+deleted+successfully");
        exit;
    } else {
        header("Location: dashboard.php");
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Delete Notice | SSV College Notice Board</title>
<link rel="stylesheet" href="style.css">
</head>
<body>

<header>
    SSV College Valayanchiragara<br>
    <span class="sub-title">Delete Notice</span>
</header>

<div class="container">
    <h2 class="section-title">Confirm Deletion</h2>

    <div class="form-card" style="text-align:center;">
        <p style="font-size:1.1rem; color:#333;">
            Are you sure you want to delete the following notice?
        </p>
        <div class="notice-card" style="max-width:500px; margin:20px auto; text-align:left;">
            <div class="notice-title"><?= htmlspecialchars($notice['title']); ?></div>
            <div class="notice-meta">📅 <?= date("d M Y, h:i A", strtotime($notice['created_at'])); ?></div>
            <div class="notice-desc"><?= nl2br(htmlspecialchars($notice['description'])); ?></div>
            <?php if (!empty($notice['file_path'])): ?>
                <a href="<?= htmlspecialchars($notice['file_path']); ?>" class="btn" target="_blank">View File</a>
            <?php endif; ?>
        </div>

        <form method="POST" style="margin-top:20px;">
            <button type="submit" name="confirm" class="btn danger">Yes, Delete</button>
            <a href="dashboard.php" class="btn">Cancel</a>
        </form>
    </div>
</div>

<footer>
    &copy; <?= date('Y'); ?> SSV College Valayanchiragara. All Rights Reserved.
</footer>

</body>
</html>
