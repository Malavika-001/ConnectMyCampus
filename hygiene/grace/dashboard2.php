
<?php
session_start();
include './db.php';

// ✅ Access control
if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit;
}

// ✅ Fetch stats
$total  = $conn->query("SELECT COUNT(*) AS c FROM notices")->fetch_assoc()['c'];
$week   = $conn->query("SELECT COUNT(*) AS c FROM notices WHERE YEARWEEK(created_at)=YEARWEEK(NOW())")->fetch_assoc()['c'];
$month  = $conn->query("SELECT COUNT(*) AS c FROM notices WHERE MONTH(created_at)=MONTH(NOW())")->fetch_assoc()['c'];
$recent = $conn->query("SELECT * FROM notices ORDER BY created_at DESC LIMIT 5");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin Dashboard | SSV College Notice Board</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        body {
            font-family: Arial, sans-serif;
            background: #f5f9ff;
            margin: 0;
            padding: 0;
        }
        header {
            background-color: #004aad;
            color: white;
            text-align: center;
            padding: 20px 0;
            font-size: 22px;
            font-weight: bold;
        }
        .sub-title {
            font-size: 16px;
            font-weight: normal;
            display: block;
            margin-top: 5px;
        }
        .container {
            max-width: 900px;
            margin: 30px auto;
            background: white;
            padding: 25px;
            border-radius: 10px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        .section-title {
            text-align: center;
            color: #004aad;
            margin-bottom: 20px;
        }
        .stats-grid {
            display: flex;
            justify-content: space-between;
            gap: 10px;
            margin-bottom: 20px;
            flex-wrap: wrap;
        }
        .stat-card {
            background: #e9f1ff;
            flex: 1;
            min-width: 250px;
            text-align: center;
            padding: 15px;
            border-radius: 8px;
        }
        .stat-number {
            font-size: 24px;
            color: #004aad;
            font-weight: bold;
        }
        .btn-group {
            display: flex;
            justify-content: space-between;
            flex-wrap: wrap;
            margin-bottom: 20px;
        }
        .btn {
            background-color: #004aad;
            color: white;
            padding: 10px 20px;
            border: none;
            text-decoration: none;
            border-radius: 5px;
            cursor: pointer;
            text-align: center;
        }
        .btn:hover {
            background-color: #003b8a;
        }
        .btn-secondary {
            background-color: #777;
        }
        .btn-secondary:hover {
            background-color: #555;
        }
        .table-card table {
            width: 100%;
            border-collapse: collapse;
        }
        .table-card th, .table-card td {
            border: 1px solid #ddd;
            padding: 10px;
            text-align: center;
        }
        .table-card th {
            background-color: #004aad;
            color: white;
        }
        .btn-sm {
            padding: 5px 10px;
            font-size: 14px;
        }
        .btn-warning {
            background-color: #ffc107;
            color: black;
        }
        .btn-warning:hover {
            background-color: #e0a800;
        }
        .btn-danger {
            background-color: #dc3545;
        }
        .btn-danger:hover {
            background-color: #b52a36;
        }
    </style>
</head>
<body>

<header>
    SSV College Valayanchiragara<br>
    <span class="sub-title">Admin Dashboard</span>
</header>

<div class="container">
    <h2 class="section-title">Dashboard Overview</h2>

    <div class="stats-grid">
        <div class="stat-card">
            <h4>Total Notices</h4>
            <p class="stat-number"><?= $total; ?></p>
        </div>
        <div class="stat-card">
            <h4>This Week</h4>
            <p class="stat-number"><?= $week; ?></p>
        </div>
        <div class="stat-card">
            <h4>This Month</h4>
            <p class="stat-number"><?= $month; ?></p>
        </div>
    </div>

    <div class="btn-group">
        <a href="add_notice.php" class="btn">+ Add Notice or Poster</a>
        <a href="../logout.php" class="btn btn-secondary">Logout</a>
    </div>

    <h2 class="section-title">Recent Notices</h2>

    <div class="table-card">
        <table>
            <thead>
                <tr>
                    <th>Title</th>
                    <th>Date</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
            <?php while ($row = $recent->fetch_assoc()) { ?>
                <tr>
                    <td><?= htmlspecialchars($row['title']); ?></td>
                    <td><?= date("d M Y, h:i A", strtotime($row['created_at'])); ?></td>
                    <td>
                        <a href="edit_notice.php?id=<?= $row['id']; ?>" class="btn btn-warning btn-sm">Edit</a>
                        <a href="delete_notice.php?id=<?= $row['id']; ?>" class="btn btn-danger btn-sm" onclick="return confirm('Are you sure you want to delete this notice?');">Delete</a>
                    </td>
                </tr>
            <?php } ?>
            </tbody>
        </table>
    </div>
</div>

</body>
</html>

