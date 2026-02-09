<?php
// reports.php  (replace your current file with this)
// Shows user report submission + (optionally) the user's past reports.
// Expects: session login storing $_SESSION['user_id'] (INT) and db_connect.php providing $conn (mysqli)

session_start();
include("db_connect.php");

// Require login (redirect to module login if not logged in)
if (!isset($_SESSION['user_id']) || empty($_SESSION['user_id'])) {
    header("Location: /campus/login.php?module=gym");
    exit;
}

$user_id = (int) $_SESSION['user_id'];

// --- Fetch user info from users table (preferred)
$user_name = 'Unknown';
$department = 'Not specified';
$username = '';     // user's username (may be different from name)
$college_id = '';   // user's college id if present

if ($stmt = $conn->prepare("SELECT id, username, name, department, college_id FROM users WHERE id = ? LIMIT 1")) {
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $stmt->bind_result($u_id, $u_username, $u_name, $u_dept, $u_college_id);
    if ($stmt->fetch()) {
        $user_name  = $u_name ?: $u_username ?: $user_name;
        $department = $u_dept ?: $department;
        $username   = $u_username ?: '';
        $college_id = $u_college_id ?: '';
    }
    $stmt->close();
}

// --- Detect whether gym_reports has a user_id column
$has_user_id = false;
$colCheck = $conn->query("SHOW COLUMNS FROM `gym_reports` LIKE 'user_id'");
if ($colCheck && $colCheck->num_rows > 0) {
    $has_user_id = true;
    $colCheck->close();
}

// message to show to user
$message = "";

// Handle report submission (use prepared statements)
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['submit_report'])) {
    $issue = trim($_POST['issue'] ?? '');
    $location = trim($_POST['location'] ?? '');

    if ($issue === '' || $location === '') {
        $message = "⚠️ Please fill out all fields.";
    } else {
        if ($has_user_id) {
            // Insert with user_id (preferable)
            $sql = "INSERT INTO gym_reports (user_id, reporter_name, department, issue, location, status, report_time)
                    VALUES (?, ?, ?, ?, ?, 'Pending', NOW())";
            $ins = $conn->prepare($sql);
            if ($ins) {
                $ins->bind_param("issss", $user_id, $user_name, $department, $issue, $location);
                if ($ins->execute()) {
                    $message = "✅ Report submitted successfully!";
                } else {
                    $message = "❌ Error submitting report: " . htmlspecialchars($ins->error);
                }
                $ins->close();
            } else {
                $message = "❌ DB prepare error: " . htmlspecialchars($conn->error);
            }
        } else {
            // Legacy insert: no user_id column — store reporter_name and department only
            $sql = "INSERT INTO gym_reports (reporter_name, department, issue, location, status, report_time)
                    VALUES (?, ?, ?, ?, 'Pending', NOW())";
            $ins = $conn->prepare($sql);
            if ($ins) {
                $ins->bind_param("ssss", $user_name, $department, $issue, $location);
                if ($ins->execute()) {
                    $message = "✅ Report submitted successfully!";
                } else {
                    $message = "❌ Error submitting report: " . htmlspecialchars($ins->error);
                }
                $ins->close();
            } else {
                $message = "❌ DB prepare error: " . htmlspecialchars($conn->error);
            }
        }
    }
}

// --- Check admin setting whether users can view their reports
$can_view = false;
if ($s = $conn->prepare("SELECT allow_user_view_reports FROM gym_admin_settings WHERE id = 1 LIMIT 1")) {
    $s->execute();
    $s->bind_result($allow);
    if ($s->fetch()) {
        $can_view = (int)$allow === 1;
    }
    $s->close();
}

// --- Fetch this user's reports (if allowed)
$reports = [];
if ($can_view) {
    if ($has_user_id) {
        // Simple: select rows where user_id matches
        $q = $conn->prepare("SELECT id, reporter_name, department, issue, location, status, report_time FROM gym_reports WHERE user_id = ? ORDER BY report_time DESC");
        if ($q) {
            $q->bind_param("i", $user_id);
            $q->execute();
            $res = $q->get_result();
            while ($row = $res->fetch_assoc()) $reports[] = $row;
            $q->close();
        } else {
            // fallback: attempt a broader select to avoid crash (shouldn't normally happen)
            $res = $conn->query("SELECT id, reporter_name, department, issue, location, status, report_time FROM gym_reports ORDER BY report_time DESC LIMIT 0");
        }
    } else {
        // No user_id column: match reporter_name with user's username, name or college_id
        // Use prepared statement with three parameters (some may be empty)
        $q = $conn->prepare("SELECT id, reporter_name, department, issue, location, status, report_time
                             FROM gym_reports
                             WHERE (reporter_name = ? OR reporter_name = ? OR reporter_name = ?)
                             ORDER BY report_time DESC");
        if ($q) {
            // If username/name/college_id are empty, bind empty strings (fine)
            $bind1 = $username ?: $user_name;   // prefer username, else name
            $bind2 = $user_name;
            $bind3 = $college_id ?: '';
            $q->bind_param("sss", $bind1, $bind2, $bind3);
            $q->execute();
            $res = $q->get_result();
            while ($row = $res->fetch_assoc()) $reports[] = $row;
            $q->close();
        } else {
            // fallback (shouldn't happen)
            $res = $conn->query("SELECT id, reporter_name, department, issue, location, status, report_time FROM gym_reports ORDER BY report_time DESC LIMIT 0");
        }
    }
}

// --- Helper to escape for HTML
function esc($v){ return htmlspecialchars($v ?? '', ENT_QUOTES, 'UTF-8'); }
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Report - GYMNASIUM</title>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap" rel="stylesheet">
<style>
body { margin:0;padding:0;font-family:'Poppins',sans-serif;background:url('login.jpeg') no-repeat center center fixed;background-size:cover;color:white; }
.logout-btn{ position:absolute; top:20px; right:30px; background:#222; color:#fff; border:none; border-radius:20px; padding:10px 20px; cursor:pointer; }
.container{ text-align:center; margin-top:100px; }
h1,h2{ margin:10px 0; }
.form-box{ background:rgba(0,0,0,0.6); border-radius:15px; padding:20px; width:50%; margin:30px auto; }
input[type="text"], textarea { width:90%; margin:10px 0; padding:10px; border-radius:10px; border:none; background:rgba(255,255,255,0.1); color:white; }
button{ padding:10px 20px; border:none; border-radius:10px; background:#00bfff; color:white; cursor:pointer; font-weight:600; transition:0.3s; }
button:hover{ background:#0099cc; }
.message{ color:#00ffcc; margin-top:10px; font-weight:600; }
.table-container{ margin-top:40px; width:80%; margin-left:auto; margin-right:auto; background:rgba(0,0,0,0.6); border-radius:10px; padding:20px; }
table{ width:100%; border-collapse:collapse; color:white; }
th,td{ padding:10px; border-bottom:1px solid rgba(255,255,255,0.2); text-align:left; }
.status{ padding:5px 10px; border-radius:8px; font-weight:bold; display:inline-block; }
.status.Pending{ background:orange; color:black; }
.status.Resolved{ background:green; color:white; }
.status.Rejected{ background:crimson; color:white; }
.back-btn { display:inline-block; margin-top:12px; background:#0077b6; color:#fff; padding:8px 14px; border-radius:8px; text-decoration:none; }
</style>
</head>
<body>

<form action="/campus/logout.php" method="post"><button type="submit" class="logout-btn">Logout</button></form>

<div class="container">
  <h1>GYMNASIUM</h1>
  <h2>SSV COLLEGE GYM</h2>
  <h2>Report an Issue</h2>

  <div style="color:#fff;margin-bottom:8px;">Reporting as: <strong><?= esc($user_name) ?></strong> — <small><?= esc($department) ?></small></div>

  <form method="post" class="form-box">
    <h3>Submit a Report</h3>
    <textarea name="issue" rows="3" placeholder="Describe your issue..." required></textarea><br>
    <input type="text" name="location" placeholder="Location (e.g., Locker Room, Weights Area)" required><br>
    <button type="submit" name="submit_report">Submit Report</button>
    <div class="message"><?= esc($message) ?></div>
  </form>

  <a class="back-btn" href="/campus/gym/gym_admin_dashboard.php">← Back to Admin Dashboard</a>

  <div class="table-container" id="reports-table">
    <?php if (!$can_view): ?>
        <p style="color:#ff9999;">🚫 The admin has disabled viewing of previous reports.</p>
    <?php else: ?>
        <?php if (count($reports) === 0): ?>
            <p>No reports found.</p>
        <?php else: ?>
            <table>
            <tr><th>ID</th><th>Name</th><th>Issue</th><th>Location</th><th>Status</th><th>Time</th></tr>
            <?php foreach ($reports as $r): ?>
                <tr>
                    <td><?= esc($r['id']) ?></td>
                    <td><?= esc($r['reporter_name']) ?></td>
                    <td><?= nl2br(esc($r['issue'])) ?></td>
                    <td><?= esc($r['location']) ?></td>
                    <td><span class="status <?= esc($r['status']) ?>"><?= esc($r['status']) ?></span></td>
                    <td><?= esc($r['report_time']) ?></td>
                </tr>
            <?php endforeach; ?>
            </table>
        <?php endif; ?>
    <?php endif; ?>
  </div>
</div>

</body>
</html>
