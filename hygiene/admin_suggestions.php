<?php
session_start();
require_once "../db.php";            // must provide $pdo (PDO)
require_once "auth_check_admin.php"; // ensures admin session

// Ensure admin access
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit;
}

$message = '';
$error = '';

/* -----------------------------
   Ensure suggestions table exists
   ----------------------------- */
try {
    $pdo->exec("
    CREATE TABLE IF NOT EXISTS suggestions (
      id INT AUTO_INCREMENT PRIMARY KEY,
      user_id VARCHAR(50) NOT NULL,
      location VARCHAR(255) DEFAULT 'Unknown',
      suggestion TEXT NOT NULL,
      status ENUM('Pending','Reviewed','Implemented') DEFAULT 'Pending',
      created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
      updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
      status_updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ");
} catch (PDOException $e) {
    // Non-fatal: show message but allow page to continue (DB must be available for useful operation)
    $error = "DB initialization error: " . htmlspecialchars($e->getMessage());
}

/* -----------------------------
   Handle update status
   ----------------------------- */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    $id = intval($_POST['id'] ?? 0);
    $status = $_POST['status'] ?? 'Pending';
    if ($id > 0 && in_array($status, ['Pending', 'Reviewed', 'Implemented'], true)) {
        try {
            $stmt = $pdo->prepare("UPDATE suggestions SET status = ?, updated_at = NOW(), status_updated_at = NOW() WHERE id = ?");
            $stmt->execute([$status, $id]);
            $message = "Status updated.";
        } catch (PDOException $e) {
            $error = "Failed to update status: " . htmlspecialchars($e->getMessage());
        }
    } else {
        $error = "Invalid status update request.";
    }
    // redirect to avoid repost on refresh
    header("Location: " . $_SERVER['PHP_SELF']);
    exit;
}

/* -----------------------------
   Handle deletion
   ----------------------------- */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_id'])) {
    $delete_id = intval($_POST['delete_id']);
    if ($delete_id > 0) {
        try {
            $stmt = $pdo->prepare("DELETE FROM suggestions WHERE id = ?");
            $stmt->execute([$delete_id]);
            $message = "Suggestion deleted.";
        } catch (PDOException $e) {
            $error = "Delete failed: " . htmlspecialchars($e->getMessage());
        }
    } else {
        $error = "Invalid delete request.";
    }
    header("Location: " . $_SERVER['PHP_SELF']);
    exit;
}

/* -----------------------------
   Filters: status & search
   ----------------------------- */
$filterStatus = trim($_GET['status'] ?? '');
$search = trim($_GET['search'] ?? '');

// Normalize status input: accept only known statuses, otherwise treat as "All"
$validStatuses = ['Pending', 'Reviewed', 'Implemented'];
if ($filterStatus !== '' && !in_array($filterStatus, $validStatuses, true)) {
    $filterStatus = '';
}

/* -----------------------------
   Build main query (with optional filters)
   We'll join suggestions.user_id -> users.college_id to show user info
   ----------------------------- */
$query = "SELECT s.*, u.college_id AS user_college_id, u.username AS user_username
          FROM suggestions s
          LEFT JOIN users u ON s.user_id = u.college_id
          WHERE 1=1";
$params = [];

// only add status clause when a real status is chosen
if ($filterStatus !== '') {
    $query .= " AND s.status = ?";
    $params[] = $filterStatus;
}

// search across multiple fields if search provided
if ($search !== '') {
    $query .= " AND (s.suggestion LIKE ? OR s.location LIKE ? OR u.college_id LIKE ? OR u.username LIKE ? OR s.created_at LIKE ?)";
    $like = "%{$search}%";
    $params[] = $like; // suggestion
    $params[] = $like; // location
    $params[] = $like; // college_id
    $params[] = $like; // username
    $params[] = $like; // created_at
}

$query .= " ORDER BY s.created_at DESC";

try {
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $suggestions = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $suggestions = [];
    $error = ($error ? $error . ' | ' : '') . "Could not fetch suggestions: " . htmlspecialchars($e->getMessage());
}

/* -----------------------------
   Find overdue suggestions (Pending or Reviewed, older than 10 days)
   ----------------------------- */
try {
    $overdueStmt = $pdo->prepare("
        SELECT s.*, u.college_id AS user_college_id, u.username AS user_username
        FROM suggestions s
        LEFT JOIN users u ON s.user_id = u.college_id
        WHERE (s.status = 'Pending' OR s.status = 'Reviewed')
          AND s.status_updated_at < DATE_SUB(NOW(), INTERVAL 10 DAY)
        ORDER BY s.status_updated_at ASC
    ");
    $overdueStmt->execute();
    $overdue = $overdueStmt->fetchAll(PDO::FETCH_ASSOC);
    $overdueCount = count($overdue);
} catch (PDOException $e) {
    $overdue = [];
    $overdueCount = 0;
}

/* -----------------------------
   Simple esc helper
   ----------------------------- */
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
.header-right { display: flex; align-items: center; gap: 15px; }
.container { max-width: 1100px; margin: 30px auto; background: white; border-radius: 10px; box-shadow: 0 3px 10px rgba(0,0,0,0.1); padding: 25px; }
h2 { color: #023047; }
.filter-bar { display: flex; justify-content: flex-start; align-items: center; gap: 10px; margin-bottom: 15px; flex-wrap:wrap; }
.filter-bar input[type=text], .filter-bar select { padding: 7px; border-radius: 6px; border: 1px solid #ccc; }
.filter-bar button { background: #0077b6; color: white; border: none; border-radius: 6px; padding: 7px 15px; cursor: pointer; }
.filter-bar button:hover { background: #023e8a; }
.table { width: 100%; border-collapse: collapse; margin-top: 15px; }
.table th, .table td { border: 1px solid #e0e0e0; padding: 10px; text-align: left; vertical-align: middle; }
.table th { background: #caf0f8; color: #023047; }
.table tr:nth-child(even) { background: #f9fbff; }
.badge { padding: 6px 10px; border-radius: 20px; font-weight: bold; color: white; display:inline-block; }
.badge.Pending { background: #f6c000; color: #000; }
.badge.Reviewed { background: #0077b6; }
.badge.Implemented { background: #2ecc71; }
select.status-select { background: #0077b6; color: white; border: none; border-radius: 6px; padding: 5px 10px; }
select.status-select option { color: black; }
button.delete-btn { background: #0077b6; color: white; border: none; border-radius: 6px; padding: 5px 10px; cursor: pointer; }
button.delete-btn:hover { background: #023e8a; }
footer { text-align: center; margin-top: 25px; color: #777; }
.bell { font-size: 20px; cursor: pointer; position: relative; display:inline-block; padding:6px; border-radius:6px; background:rgba(255,255,255,0.06); }
.bell .count { position:absolute; top:-6px; right:-6px; background:red; color:white; border-radius:50%; padding:2px 7px; font-size:12px; }
.popup { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background-color: rgba(0,0,0,0.5); z-index:1000; }
.popup-content { background-color: #fff; margin: 6% auto; padding: 20px; border-radius: 8px; width: 90%; max-width: 700px; color: #023047; }
.close-btn { float: right; cursor: pointer; color: #0077b6; font-weight: bold; }
@media(max-width:800px){ .filter-bar{flex-direction:column;align-items:stretch;} .table th, .table td{font-size:13px;} }
</style>
</head>
<body>

<div class="header">
  <div><strong>Admin Panel</strong> — Manage Suggestions</div>
  <div class="header-right">
    <span class="bell" title="Overdue Suggestions" onclick="document.getElementById('popup').style.display='block'">
      🔔
      <?php if ($overdueCount > 0): ?>
        <span class="count"><?= (int)$overdueCount ?></span>
      <?php endif; ?>
    </span>
    <a href="admin_dashboard.php">🏠 Dashboard</a>
    <a href="../logout.php">🚪 Logout</a>
  </div>
</div>

<div class="container">
  <h2>📋 All User Suggestions</h2>

  <?php if ($message): ?>
    <div style="padding:10px;background:#e6ffed;border-radius:6px;margin-bottom:12px;color:#006600;"><?= esc($message) ?></div>
  <?php endif; ?>
  <?php if ($error): ?>
    <div style="padding:10px;background:#ffecec;border-radius:6px;margin-bottom:12px;color:#990000;"><?= esc($error) ?></div>
  <?php endif; ?>

  <form method="get" class="filter-bar" role="search" aria-label="Filter suggestions">
    <input type="text" name="search" placeholder="Search suggestions/user/date" value="<?= esc($search) ?>">
    <select name="status" aria-label="Filter by status">
      <option value="" <?= $filterStatus==='' ? 'selected' : '' ?>>-- All Statuses --</option>
      <option value="Pending" <?= $filterStatus==='Pending' ? 'selected' : '' ?>>Pending</option>
      <option value="Reviewed" <?= $filterStatus==='Reviewed' ? 'selected' : '' ?>>Reviewed</option>
      <option value="Implemented" <?= $filterStatus==='Implemented' ? 'selected' : '' ?>>Implemented</option>
    </select>
    <button type="submit">Filter</button>
    <a href="<?= esc($_SERVER['PHP_SELF']) ?>" style="margin-left:8px; text-decoration:none; align-self:center;">Reset</a>
  </form>

  <?php if (empty($suggestions)): ?>
    <p style="text-align:center;">No suggestions found.</p>
  <?php else: ?>
  <table class="table" role="table" aria-label="Suggestions table">
    <thead>
      <tr>
        <th>ID</th>
        <th>User (College ID)</th>
        <th>Location</th>
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
        <td>
            <?= esc($s['user_college_id'] ?? 'Unknown') ?>
            <?php if (!empty($s['user_username'])): ?>
                <div style="color:#036; font-size:12px;"><?= esc($s['user_username']) ?></div>
            <?php endif; ?>
        </td>
        <td><?= esc($s['location'] ?? 'Unknown') ?></td>
        <td><?= nl2br(esc($s['suggestion'])) ?></td>
        <td>
          <span class="badge <?= esc($s['status']) ?>"><?= esc($s['status']) ?></span><br>
          <small>Updated: <?= esc($s['status_updated_at'] ?? $s['updated_at'] ?? $s['created_at']) ?></small>
          <form method="post" style="margin-top:6px;">
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
          <form method="post" onsubmit="return confirm('Delete this suggestion?');">
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

<!-- Popup for overdue suggestions -->
<div id="popup" class="popup" onclick="if(event.target===this)this.style.display='none'">
  <div class="popup-content" role="dialog" aria-modal="true" aria-labelledby="overdueHeading">
    <span class="close-btn" onclick="document.getElementById('popup').style.display='none'">✖</span>
    <h3 id="overdueHeading">⏰ Overdue Suggestions</h3>

    <?php if ($overdueCount === 0): ?>
      <p>No overdue suggestions 🎉</p>
    <?php else: ?>
      <ul>
        <?php foreach ($overdue as $o): ?>
          <li>
            <strong><?= esc($o['user_college_id'] ?? 'Unknown') ?></strong>
            <?= ' — ' . esc($o['suggestion']) ?>
            (<?= esc($o['status']) ?> since <?= esc(date('M d, Y', strtotime($o['status_updated_at']))) ?>)
          </li>
        <?php endforeach; ?>
      </ul>
    <?php endif; ?>
  </div>
</div>

<footer>
  © <?= date("Y") ?> Connect My Campus. All rights reserved.
</footer>

<script>
 // close popup with ESC
 document.addEventListener('keydown', function(e){
   if(e.key === 'Escape') document.getElementById('popup').style.display='none';
 });
</script>
</body>
</html>
