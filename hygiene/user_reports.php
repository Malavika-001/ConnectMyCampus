<?php
session_start();
require_once "../db.php";
require_once "../auth_check_user.php";

// Redirect if not logged in
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'user') {
    header("Location: ../login.php?module=hygiene");
    exit;
}

$success_msg = "";
$error_msg = "";

// ✅ Load locations
$locations_file = __DIR__ . "/locations.txt";
$locations = file_exists($locations_file) ? file($locations_file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) : [];

// ==========================================
// ADD NEW REPORT
// ==========================================
if ($_SERVER["REQUEST_METHOD"] === "POST" && ($_POST['action'] ?? '') === 'add') {
    $report = trim($_POST['report']);
    $location = trim($_POST['location']);
    $user_id = (int) $_SESSION['user_id'];

    if ($report !== "" && $location !== "") {
        $stmt = $conn->prepare("INSERT INTO reports (user_id, report, location, status, created_at, status_updated_at)
                                VALUES (?, ?, ?, 'Open', NOW(), NOW())");
        $stmt->bind_param("iss", $user_id, $report, $location);
        if ($stmt->execute()) $success_msg = "✅ Report submitted successfully!";
        else $error_msg = "❌ Error submitting report: " . $stmt->error;
        $stmt->close();
    } else {
        $error_msg = "Please provide report text and select a location.";
    }
}

// ==========================================
// EDIT REPORT
// ==========================================
if ($_SERVER["REQUEST_METHOD"] === "POST" && ($_POST['action'] ?? '') === 'edit') {
    $id = (int) $_POST['id'];
    $report = trim($_POST['report']);
    $location = trim($_POST['location']);
    $user_id = (int) $_SESSION['user_id'];

    $stmt = $conn->prepare("UPDATE reports SET report = ?, location = ?, status_updated_at = NOW() WHERE id = ? AND user_id = ?");
    $stmt->bind_param("ssii", $report, $location, $id, $user_id);
    if ($stmt->execute()) {
        header("Location: user_reports.php?msg=updated");
        exit;
    } else {
        $error_msg = "❌ Failed to update report: " . $stmt->error;
    }
    $stmt->close();
}

// ==========================================
// DELETE REPORT
// ==========================================
if (isset($_GET['delete'])) {
    $id = (int) $_GET['delete'];
    $user_id = (int) $_SESSION['user_id'];
    $stmt = $conn->prepare("DELETE FROM reports WHERE id = ? AND user_id = ?");
    $stmt->bind_param("ii", $id, $user_id);
    if ($stmt->execute()) {
        header("Location: user_reports.php?msg=deleted");
        exit;
    } else {
        $error_msg = "❌ Failed to delete report: " . $stmt->error;
    }
    $stmt->close();
}

// ==========================================
// FETCH USER’S OWN REPORTS
// ==========================================
$user_id = (int) $_SESSION['user_id'];

$query = "
    SELECT r.id, r.report, r.location, r.status, r.created_at, r.status_updated_at
    FROM reports r
    WHERE r.user_id = ?
       OR r.user_id = (
           SELECT u.college_id FROM users u WHERE u.id = ?
       )
    ORDER BY r.created_at DESC
";

$stmt = $conn->prepare($query);
$stmt->bind_param("ii", $user_id, $user_id);
$stmt->execute();
$myReports = $stmt->get_result();
$myCount = $myReports->num_rows;
$stmt->close();

// ==========================================
// FETCH COMMUNITY REPORTS
// ==========================================
$current_month = date('Y-m');
$commStmt = $conn->prepare("
    SELECT r.report, r.location, r.status, r.created_at, r.status_updated_at
    FROM reports r
    WHERE (r.user_id <> ? AND r.user_id <> (
        SELECT u.college_id FROM users u WHERE u.id = ?
    ))
    AND DATE_FORMAT(r.created_at, '%Y-%m') = ?
    ORDER BY r.created_at DESC
");
$commStmt->bind_param("iis", $user_id, $user_id, $current_month);
$commStmt->execute();
$communityReports = $commStmt->get_result();
$communityCount = $communityReports->num_rows;
$commStmt->close();

// ==========================================
// FETCH OVERDUE REPORTS (10+ days old, still Open/In Progress)
// ==========================================
$overdueStmt = $conn->prepare("
    SELECT r.report, r.location, r.status, r.status_updated_at
    FROM reports r
    WHERE (r.status = 'Open' OR r.status = 'In Progress')
      AND r.status_updated_at < DATE_SUB(NOW(), INTERVAL 10 DAY)
      AND (r.user_id = ? OR r.user_id = (
          SELECT u.college_id FROM users u WHERE u.id = ?
      ))
");
$overdueStmt->bind_param("ii", $user_id, $user_id);
$overdueStmt->execute();
$overdueReports = $overdueStmt->get_result();
$overdueCount = $overdueReports->num_rows;
$overdueStmt->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<title>User Reports | Connect My Campus</title>
<style>
body { font-family:'Segoe UI', sans-serif; background:#eef6fc; margin:0; padding:0; }
.container { max-width:950px; margin:30px auto; padding:0 15px; }
header { background:#0077b6; color:white; padding:15px 30px; display:flex; justify-content:space-between; align-items:center; position:relative; }
header a { color:white; margin-left:20px; font-weight:600; }
button,input[type="submit"] { background:#0077b6; color:white; border:none; border-radius:8px; padding:8px 15px; cursor:pointer; }
button:hover,input[type="submit"]:hover { background:#023e8a; }
form { background:white; padding:20px; border-radius:12px; box-shadow:0 2px 10px rgba(0,0,0,0.08); margin-top:20px; }
textarea, select { width:100%; padding:10px; border-radius:6px; border:1px solid #ccc; margin-top:10px; }
.message { padding:12px 18px; margin:15px 0; border-radius:8px; font-weight:500; }
.success { background:#d1ffd8; color:#006400; }
.error { background:#ffd1d1; color:#a40000; }
.card { background:white; padding:20px; border-radius:12px; box-shadow:0 2px 10px rgba(0,0,0,0.08); margin-top:20px; }
table { width:100%; border-collapse:collapse; margin-top:10px; }
th, td { padding:12px 10px; text-align:left; vertical-align:top; }
th { background:#90e0ef; }
tr:nth-child(even) { background:#f8f9fa; }
.status-badge { padding:6px 12px; border-radius:20px; font-weight:bold; color:white; display:inline-block; min-width:100px; text-align:center; }
.status-Open { background:#ffcc00; color:black; }
.status-In\ Progress { background:#0077b6; color:white; }
.status-Resolved { background:#28a745; }
.updated-time { display:block; font-size:12px; color:#555; margin-top:4px; }
.edit-form { background:#f8f9fa; padding:10px; border-radius:8px; margin-top:8px; }
@media(max-width:700px){ header{flex-direction:column;text-align:center;} header a{margin:5px 0;} table, th, td{font-size:13px;} }

/* 🔔 Bell Styles */
.bell { cursor:pointer; font-size:20px; position:relative; }
.bell::after { content:"<?= $overdueCount>0?$overdueCount:'' ?>"; position:absolute; top:-6px; right:-8px; background:red; color:white; border-radius:50%; padding:2px 6px; font-size:12px; }

/* Popup */
.popup { display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.4); z-index:10; }
.popup-content { background:white; margin:10% auto; padding:20px; border-radius:8px; width:50%; max-width:600px; color:#023047; }
.close-btn { float:right; cursor:pointer; font-weight:bold; color:#0077b6; }
</style>
</head>
<body>

<header>
<div><strong>Connect My Campus</strong> — Reports</div>
<div>
    <span class="bell" onclick="document.getElementById('popup').style.display='block'">🔔</span>
    <a href="user_dashboard.php">🏠 Dashboard</a>
    <a href="../logout.php">🚪 Logout</a>
</div>
</header>

<div class="container">
<?php if(isset($_GET['msg']) && $_GET['msg']==='deleted'):?><div class="message success">🗑️ Report deleted successfully!</div><?php endif;?>
<?php if(isset($_GET['msg']) && $_GET['msg']==='updated'):?><div class="message success">✏️ Report updated successfully!</div><?php endif;?>
<?php if($success_msg):?><div class="message success"><?=htmlspecialchars($success_msg)?></div><?php endif;?>
<?php if($error_msg):?><div class="message error"><?=htmlspecialchars($error_msg)?></div><?php endif;?>

<button onclick="document.getElementById('addForm').style.display =
document.getElementById('addForm').style.display==='block'?'none':'block'">➕ Add Report</button>

<form method="POST" id="addForm" style="display:none;">
<input type="hidden" name="action" value="add">
<textarea name="report" placeholder="Describe the issue..." required></textarea>
<select name="location" required>
<option value="">-- Select Location --</option>
<?php foreach($locations as $loc): ?>
<option value="<?=htmlspecialchars($loc)?>"><?=htmlspecialchars($loc)?></option>
<?php endforeach; ?>
</select>
<br><br><input type="submit" value="Submit">
</form>

<div class="card">
<h3>📋 My Reports (<?= $myCount ?>)</h3>
<table>
<tr><th>Report</th><th>Location</th><th>Status</th><th>Date</th><th>Actions</th></tr>
<?php if($myCount>0): while($r=$myReports->fetch_assoc()): $statusClass='status-'.str_replace(' ', '\ ', $r['status']); ?>
<tr>
<td><?=htmlspecialchars($r['report'])?></td>
<td><?=htmlspecialchars($r['location'])?></td>
<td>
<span class="status-badge <?= $statusClass ?>"><?=htmlspecialchars($r['status'])?></span>
<span class="updated-time">Updated: <?=htmlspecialchars($r['status_updated_at'])?></span>
</td>
<td><?=htmlspecialchars($r['created_at'])?></td>
<td>
<a href="?edit=<?=$r['id']?>">✏️ Edit</a> |
<a href="?delete=<?=$r['id']?>" onclick="return confirm('Delete this report?')">🗑️ Delete</a>
</td>
</tr>
<?php if(isset($_GET['edit']) && $_GET['edit']==$r['id']): ?>
<tr><td colspan="5">
<form method="POST" class="edit-form">
<input type="hidden" name="action" value="edit">
<input type="hidden" name="id" value="<?=$r['id']?>">
<textarea name="report" required><?=htmlspecialchars($r['report'])?></textarea>
<select name="location" required>
<?php foreach($locations as $loc): ?>
<option value="<?=htmlspecialchars($loc)?>" <?= $loc===$r['location']?'selected':'' ?>><?=htmlspecialchars($loc)?></option>
<?php endforeach; ?>
</select>
<br><input type="submit" value="Save Changes">
</form>
</td></tr>
<?php endif; ?>
<?php endwhile; else: ?>
<tr><td colspan="5">No reports submitted yet.</td></tr>
<?php endif; ?>
</table>
</div>

<div class="card">
<h3>🌍 Community Reports (<?= $communityCount ?>)</h3>
<table>
<tr><th>Report</th><th>Location</th><th>Status</th><th>Date</th></tr>
<?php if($communityCount>0): while($r=$communityReports->fetch_assoc()): $statusClass='status-'.str_replace(' ', '\ ', $r['status']); ?>
<tr>
<td><?=htmlspecialchars($r['report'])?></td>
<td><?=htmlspecialchars($r['location'])?></td>
<td>
<span class="status-badge <?= $statusClass ?>"><?=htmlspecialchars($r['status'])?></span>
<span class="updated-time"><?=htmlspecialchars($r['status_updated_at'])?></span>
</td>
<td><?=htmlspecialchars($r['created_at'])?></td>
</tr>
<?php endwhile; else: ?>
<tr><td colspan="4">No community reports this month.</td></tr>
<?php endif; ?>
</table>
</div>
</div>

<!-- 🔔 Popup -->
<div id="popup" class="popup">
  <div class="popup-content">
    <span class="close-btn" onclick="document.getElementById('popup').style.display='none'">✖</span>
    <h3>⏰ Overdue Reports</h3>
    <?php if($overdueCount === 0): ?>
        <p>No overdue reports 🎉</p>
    <?php else: ?>
        <ul>
        <?php while($r = $overdueReports->fetch_assoc()): ?>
            <li><strong><?=htmlspecialchars($r['location'])?></strong> — <?=htmlspecialchars($r['report'])?> (<?=htmlspecialchars($r['status'])?> since <?=htmlspecialchars(date('M d, Y', strtotime($r['status_updated_at'])))?>)</li>
        <?php endwhile; ?>
        </ul>
    <?php endif; ?>
  </div>
</div>

</body>
</html>
