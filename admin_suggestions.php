<?php
session_start();
require_once "../db.php";
require_once "auth_check_admin.php";

// Ensure admin access
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit;
}

// --- Create table if not exists ---
$pdo->exec("CREATE TABLE IF NOT EXISTS suggestions (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id VARCHAR(50) NOT NULL,
  suggestion TEXT NOT NULL,
  status ENUM('Pending','Reviewed','Implemented') DEFAULT 'Pending',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

// --- Handle status update ---
if (isset($_POST['update_status'])) {
    $id = intval($_POST['id']);
    $status = $_POST['status'] ?? 'Pending';
    $stmt = $pdo->prepare("UPDATE suggestions SET status = ?, updated_at = NOW() WHERE id = ?");
    $stmt->execute([$status, $id]);
    header("Location: " . $_SERVER['PHP_SELF']);
    exit;
}

// --- Handle deletion ---
if (isset($_POST['delete_id'])) {
    $delete_id = intval($_POST['delete_id']);
    $stmt = $pdo->prepare("DELETE FROM suggestions WHERE id = ?");
    $stmt->execute([$delete_id]);
    header("Location: " . $_SERVER['PHP_SELF']);
    exit;
}

// --- Filters ---
$filterStatus = $_GET['status'] ?? '';
$search = trim($_GET['search'] ?? '');

// ✅ FIXED: Join with users using `college_id` instead of `id`
$query = "SELECT s.*, 
                 u.college_id AS user_college_id,
                 u.username AS user_username
          FROM suggestions s
          LEFT JOIN users u ON s.user_id = u.college_id
          WHERE 1=1";

$params = [];

if ($filterStatus && $filterStatus !== 'All') {
    $query .= " AND s.status = ?";
    $params[] = $filterStatus;
}
if ($search !== '') {
    $query .= " AND (s.suggestion LIKE ? OR u.college_id LIKE ? OR u.username LIKE ? OR s.created_at LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

$query .= " ORDER BY s.created_at DESC";

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$suggestions = $stmt->fetchAll();

function esc($v) { return htmlspecialchars($v ?? '', ENT_QUOTES, 'UTF-8'); }
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Admin Panel — Manage Suggestions</title>
<style>
body { font-family: 'Segoe UI', sans-serif; background: #e9f5ff; margin: 0; color: #023047; }
.header { background: #0077b6; color: white; padding: 15px 25px; display: flex; justify-content: space-between; align-items: center; }
.header a { color: white; text-decoration: none; font-weight: bold; margin-left: 20px; }
.header a:hover { text-decoration: underline; }
.header-right { display: flex; align-items: center; gap: 15px; }
.container { max-width: 1100px; margin: 30px auto; background: white; border-radius: 10px; box-shadow: 0 3px 10px rgba(0,0,0,0.1); padding: 25px; }
h2 { color: #023047; }
.filter-bar { display: flex; justify-content: flex-start; align-items: center; gap: 10px; margin-bottom: 15px; }
.filter-bar input[type=text], .filter-bar select { padding: 7px; border-radius: 6px; border: 1px solid #ccc; }
.filter-bar button { background: #0077b6; color: white; border: none; border-radius: 6px; padding: 7px 15px; cursor: pointer; }
.filter-bar button:hover { background: #023e8a; }
.table { width: 100%; border-collapse: collapse; margin-top: 15px; }
.table th, .table td { border: 1px solid #e0e0e0; padding: 10px; text-align: left; }
.table th { background: #caf0f8; color: #023047; }
.table tr:nth-child(even) { background: #f9fbff; }
.badge { padding: 6px 10px; border-radius: 20px; font-weight: bold; color: white; }
.badge.Pending { background: #f6c000; color: #000; }
.badge.Reviewed { background: #0077b6; }
.badge.Implemented { background: #2ecc71; }
select.status-select { background: #0077b6; color: white; border: none; border-radius: 6px; padding: 5px 10px; }
select.status-select option { color: black; }
button.delete-btn { background: #0077b6; color: white; border: none; border-radius: 6px; padding: 5px 10px; cursor: pointer; }
button.delete-btn:hover { background: #023e8a; }
footer { text-align: center; margin-top: 25px; color: #777; }
.bell { font-size: 20px; cursor: pointer; position: relative; }
.bell::after { content: "•"; color: red; position: absolute; top: -5px; right: -5px; font-size: 20px; display: <?= rand(0,1)?'block':'none' ?>; }
</style>
</head>
<body>

<div class="header">
  <div><strong>Admin Panel</strong> — Manage Suggestions</div>
  <div class="header-right">
    <span class="bell" title="No overdue suggestions">🔔</span>
    <a href="admin_dashboard.php">🏠 Dashboard</a>
    <a href="../logout.php">🚪 Logout</a>
  </div>
</div>

<div class="container">
  <h2>📋 All User Suggestions</h2>

  <form method="get" class="filter-bar">
    <input type="text" name="search" placeholder="Search suggestions/user/date" value="<?= esc($search) ?>">
    <select name="status">
      <option <?= $filterStatus==''?'selected':'' ?>>-- All Statuses --</option>
      <option <?= $filterStatus=='Pending'?'selected':'' ?>>Pending</option>
      <option <?= $filterStatus=='Reviewed'?'selected':'' ?>>Reviewed</option>
      <option <?= $filterStatus=='Implemented'?'selected':'' ?>>Implemented</option>
    </select>
    <button type="submit">Filter</button>
  </form>

  <?php if (empty($suggestions)): ?>
    <p style="text-align:center;">No suggestions found.</p>
  <?php else: ?>
  <table class="table">
    <thead>
      <tr>
        <th>ID</th>
        <th>User (College ID)</th>
        <th>Suggestion</th>
        <th>Status</th>
        <th>Date Submitted</th>
        <th>Actions</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($suggestions as $s): ?>
      <tr>
        <td><?= esc($s['id']) ?></td>
        <td><?= esc($s['user_college_id'] ?? 'Unknown') ?></td>
        <td><?= esc($s['suggestion']) ?></td>
        <td>
          <span class="badge <?= esc($s['status']) ?>"><?= esc($s['status']) ?></span><br>
          <small>
              Updated:
              <?= isset($s['updated_at']) && $s['updated_at']
                  ? esc(date('Y-m-d H:i:s', strtotime($s['updated_at'])))
                  : esc($s['created_at']); ?>
          </small><br>
          <form method="post" style="margin-top:5px;">
            <input type="hidden" name="id" value="<?= esc($s['id']) ?>">
            <select name="status" class="status-select" onchange="this.form.submit()">
              <option value="Pending" <?= $s['status']=='Pending'?'selected':'' ?>>Pending</option>
              <option value="Reviewed" <?= $s['status']=='Reviewed'?'selected':'' ?>>Reviewed</option>
              <option value="Implemented" <?= $s['status']=='Implemented'?'selected':'' ?>>Implemented</option>
            </select>
            <input type="hidden" name="update_status" value="1">
          </form>
        </td>
        <td><?= esc($s['created_at']) ?></td>
        <td>
          <form method="post">
            <input type="hidden" name="delete_id" value="<?= esc($s['id']) ?>">
            <button type="submit" class="delete-btn">🗑 Delete</button>
          </form>
        </td>
      </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
  <?php endif; ?>
</div>

<footer>
  © <?= date("Y") ?> Connect My Campus. All rights reserved.
</footer>
</body>
</html>
