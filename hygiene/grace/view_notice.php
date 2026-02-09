<?php
session_start();
include 'db.php';

if (!isset($_SESSION["username"])) {
    header("Location: login.php");
    exit;
}

$result = $conn->query("SELECT * FROM notices ORDER BY created_at DESC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Notice Board | SSV College</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<style>
body {
  background-color: #f0f6ff;
}
.card {
  margin: 15px auto;
  max-width: 700px;
  border-left: 6px solid #007bff;
}
</style>
</head>
<body>
<div class="container mt-4">
  <h3 class="text-center text-primary mb-4">SSV College Notice Board</h3>

  <div class="text-end mb-3">
    <span class="me-3">Logged in as: <?= htmlspecialchars($_SESSION["username"]) ?> (<?= $_SESSION["role"] ?>)</span>
    <a href="logout.php" class="btn btn-outline-danger btn-sm">Logout</a>
  </div>

  <?php while ($row = $result->fetch_assoc()): ?>
    <div class="card p-3">
      <h5><?= htmlspecialchars($row['title']) ?></h5>
      <p class="text-muted small mb-2"><?= date("d M Y, h:i A", strtotime($row['created_at'])) ?></p>
      <p><?= nl2br(htmlspecialchars($row['description'])) ?></p>

      <?php if (!empty($row['file_path'])): ?>
        <a href="<?= htmlspecialchars($row['file_path']) ?>" target="_blank" class="btn btn-primary btn-sm">View Poster</a>
        <a href="<?= htmlspecialchars($row['file_path']) ?>" download class="btn btn-outline-success btn-sm">Download</a>
      <?php endif; ?>
    </div>
  <?php endwhile; ?>

  <?php if ($result->num_rows === 0): ?>
    <p class="text-center text-muted">No notices available.</p>
  <?php endif; ?>
</div>
</body>
</html>
