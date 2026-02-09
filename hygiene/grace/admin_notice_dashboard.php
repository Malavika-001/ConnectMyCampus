<?php
session_start();
include './db.php'; // correct relative path

// Access control
if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit;
}

// Fetch stats
$total  = $conn->query("SELECT COUNT(*) AS c FROM notices")->fetch_assoc()['c'];
$week   = $conn->query("SELECT COUNT(*) AS c FROM notices WHERE YEARWEEK(created_at)=YEARWEEK(NOW())")->fetch_assoc()['c'];
$month  = $conn->query("SELECT COUNT(*) AS c FROM notices WHERE MONTH(created_at)=MONTH(NOW())")->fetch_assoc()['c'];
$recent = $conn->query("SELECT * FROM notices ORDER BY created_at DESC LIMIT 5");
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Notice Board Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light p-4">
<div class="container">
    <h3 class="text-primary mb-4">Admin Dashboard - Notice Board</h3>

    <div class="row text-center mb-4">
        <div class="col-md-4"><div class="card p-3"><h6>Total Notices</h6><p><?= $total; ?></p></div></div>
        <div class="col-md-4"><div class="card p-3"><h6>This Week</h6><p><?= $week; ?></p></div></div>
        <div class="col-md-4"><div class="card p-3"><h6>This Month</h6><p><?= $month; ?></p></div></div>
    </div>

    <a href="add_notice.php" class="btn btn-primary mb-3">+ Add Notice or Poster</a>
    <a href="../logout.php" class="btn btn-secondary mb-3 float-end">Logout</a>

    <table class="table table-bordered">
        <thead class="table-primary">
            <tr><th>Title</th><th>Date</th><th>Target</th><th>Actions</th></tr>
        </thead>
        <tbody>
        <?php while($row = $recent->fetch_assoc()) { ?>
            <tr>
                <td><?= htmlspecialchars($row['title']); ?></td>
                <td><?= date("d M Y, h:i A", strtotime($row['created_at'])); ?></td>
                <td><?= ucfirst($row['target_role']); ?></td>
                <td>
                    <a href="edit_notice.php?id=<?= $row['id']; ?>" class="btn btn-sm btn-warning">Edit</a>
                    <a href="delete_notice.php?id=<?= $row['id']; ?>" class="btn btn-sm btn-danger">Delete</a>
                </td>
            </tr>
        <?php } ?>
        </tbody>
    </table>
</div>
</body>
</html>
