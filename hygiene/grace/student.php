<?php
session_start();
include './db.php';

if (!isset($_SESSION['username']) || $_SESSION['role'] != 'student') {
  header("Location: ../login.php");
  exit;
}

$result = $conn->query("SELECT * FROM notices WHERE target_role IN ('student','all') ORDER BY created_at DESC");
?>
<!DOCTYPE html>
<html>
<head>
  <meta charset="UTF-8">
  <title>Student Notices</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light p-4">
<div class="container">
  <h3 class="text-primary mb-3">Student Notices & Posters</h3>
  <a href="../logout.php" class="btn btn-secondary mb-3">Logout</a>

  <?php while($row = $result->fetch_assoc()): ?>
    <div class="card mb-3">
      <div class="card-body">
        <h5><?= htmlspecialchars($row['title']); ?></h5>
        <p><?= nl2br(htmlspecialchars($row['description'])); ?></p>
        <small class="text-muted"><?= date("d M Y, h:i A", strtotime($row['created_at'])); ?></small><br>
        <?php if(!empty($row['file_path'])): ?>
          <a href="../<?= $row['file_path']; ?>" target="_blank" class="btn btn-sm btn-outline-primary mt-2">View Poster</a>
          <a href="../<?= $row['file_path']; ?>" download class="btn btn-sm btn-outline-success mt-2">Download</a>
        <?php endif; ?>
      </div>
    </div>
  <?php endwhile; ?>
</div>
</body>
</html>
