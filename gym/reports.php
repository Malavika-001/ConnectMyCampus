<?php
// campus/gym/reports.php
// User-facing report submission + (optional) view of their own reports.
// Uses mysqli $conn from db_connect.php and expects $_SESSION['user_id'] set.

session_start();
require_once __DIR__ . '/db_connect.php'; // provides $conn (mysqli)

// Require login for gym module
if (!isset($_SESSION['user_id']) || empty($_SESSION['user_id'])) {
    header("Location: /campus/login.php?module=gym");
    exit;
}

$user_id = (int) $_SESSION['user_id'];

// Fetch user info: prefer users table
$user_name = 'Unknown';
$department = 'Not specified';
$username_for_report = ''; // prefer username when saving reporter_name
if ($stmt = $conn->prepare("SELECT id, username, name, department, college_id FROM users WHERE id = ? LIMIT 1")) {
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $stmt->bind_result($u_id, $u_username, $u_name, $u_department, $u_college_id);
    if ($stmt->fetch()) {
        $user_name = $u_name ?: ($u_username ?: $user_name);
        $department = $u_department ?: $department;
        $username_for_report = $u_username ?: $u_name;
        $college_id_val = $u_college_id;
    }
    $stmt->close();
}

// Load locations from gym_locations.txt (one per line)
$locations_file = __DIR__ . '/gym_locations.txt';
$locations = [];
if (file_exists($locations_file)) {
    $lines = file($locations_file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        $line = trim($line);
        if ($line !== '') $locations[] = $line;
    }
}

// Check admin setting whether users can view their reports
$can_view = false;
if ($s = $conn->prepare("SELECT allow_user_view_reports FROM gym_admin_settings WHERE id = 1 LIMIT 1")) {
    $s->execute();
    $s->bind_result($allow_val);
    if ($s->fetch()) $can_view = (int)$allow_val === 1;
    $s->close();
}

// Detect whether gym_reports table has a 'user_id' column (optional)
$has_user_id_col = false;
$colchk = $conn->query("SHOW COLUMNS FROM `gym_reports` LIKE 'user_id'");
if ($colchk && $colchk->num_rows > 0) $has_user_id_col = true;
if ($colchk) $colchk->free();

// Handle submission
$message = "";
if ($_SERVER['REQUEST_METHOD'] === "POST" && isset($_POST['submit_report'])) {
    $issue = trim($_POST['issue'] ?? '');
    // location: from select when file present, else fallback text
    if (!empty($locations)) {
        $location = trim($_POST['location'] ?? '');
    } else {
        $location = trim($_POST['location_text'] ?? '');
    }

    if ($issue === '' || $location === '') {
        $message = "⚠️ Please fill out all fields.";
    } else {
        // If gym_reports has user_id column, insert it; otherwise insert reporter_name and department
        if ($has_user_id_col) {
            $sql = "INSERT INTO gym_reports (user_id, reporter_name, department, issue, location, status, report_time)
                    VALUES (?, ?, ?, ?, ?, 'Pending', NOW())";
            if ($ins = $conn->prepare($sql)) {
                // reporter_name prefer username (if available) else name
                $rep_name_to_save = $username_for_report ?: $user_name;
                $ins->bind_param("issss", $user_id, $rep_name_to_save, $department, $issue, $location);
                if ($ins->execute()) {
                    $message = "✅ Report submitted successfully!";
                } else {
                    $message = "❌ Error submitting report: " . htmlspecialchars($ins->error);
                }
                $ins->close();
            } else {
                $message = "❌ DB error: " . htmlspecialchars($conn->error);
            }
        } else {
            // legacy table: no user_id column
            $sql = "INSERT INTO gym_reports (reporter_name, department, issue, location, status, report_time)
                    VALUES (?, ?, ?, ?, 'Pending', NOW())";
            if ($ins = $conn->prepare($sql)) {
                $rep_name_to_save = $username_for_report ?: $user_name;
                $ins->bind_param("ssss", $rep_name_to_save, $department, $issue, $location);
                if ($ins->execute()) {
                    $message = "✅ Report submitted successfully!";
                } else {
                    $message = "❌ Error submitting report: " . htmlspecialchars($ins->error);
                }
                $ins->close();
            } else {
                $message = "❌ DB error: " . htmlspecialchars($conn->error);
            }
        }
    }
}

// If allowed, fetch this user's reports.
// We'll try to match by user_id (if the column exists), or by reporter_name matching username/name/college_id.
$reports = [];
if ($can_view) {
    if ($has_user_id_col) {
        // fetch by user_id
        $q = $conn->prepare("SELECT id, reporter_name, department, issue, location, status, report_time FROM gym_reports WHERE user_id = ? ORDER BY report_time DESC");
        $q->bind_param("i", $user_id);
        $q->execute();
        $res = $q->get_result();
        while ($row = $res->fetch_assoc()) $reports[] = $row;
        $q->close();
    } else {
        // fetch by reporter_name matches username OR name OR college_id
        // We'll build a prepared statement with three possible values.
        $val1 = $username_for_report ?: $user_name;
        $val2 = $user_name;
        $val3 = isset($college_id_val) && $college_id_val !== '' ? (string)$college_id_val : null;

        if ($val3 !== null) {
            $q = $conn->prepare("SELECT id, reporter_name, department, issue, location, status, report_time FROM gym_reports WHERE reporter_name = ? OR reporter_name = ? OR reporter_name = ? ORDER BY report_time DESC");
            $q->bind_param("sss", $val1, $val2, $val3);
        } else {
            $q = $conn->prepare("SELECT id, reporter_name, department, issue, location, status, report_time FROM gym_reports WHERE reporter_name = ? OR reporter_name = ? ORDER BY report_time DESC");
            $q->bind_param("ss", $val1, $val2);
        }

        if ($q) {
            $q->execute();
            $res = $q->get_result();
            while ($row = $res->fetch_assoc()) $reports[] = $row;
            $q->close();
        }
    }
}

// helper
function esc($v){ return htmlspecialchars($v ?? '', ENT_QUOTES, 'UTF-8'); }
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<title>Report - GYMNASIUM</title>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap" rel="stylesheet">
<style>
/* styling kept consistent with your theme */
body { margin:0;padding:0;font-family:'Poppins',sans-serif;background:url('login.jpeg') no-repeat center center fixed;background-size:cover;color:white; }
.logout-btn{ position:absolute; top:20px; right:30px; background:#222; color:#fff; border:none; border-radius:20px; padding:10px 20px; cursor:pointer; }
.container{ text-align:center; margin-top:100px; }
h1,h2{ margin:10px 0; }
.form-box{ background:rgba(0,0,0,0.6); border-radius:15px; padding:20px; width:50%; margin:30px auto; }
input[type="text"], textarea, select { width:90%; margin:10px 0; padding:10px; border-radius:10px; border:none; background:rgba(255,255,255,0.1); color:white; }
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
    <textarea name="issue" rows="3" placeholder="Describe your issue..." required><?= esc($_POST['issue'] ?? '') ?></textarea><br>

    <?php if (!empty($locations)): ?>
      <select name="location" required>
        <option value="">-- Select Location --</option>
        <?php foreach ($locations as $loc): ?>
          <option value="<?= esc($loc) ?>" <?= (isset($_POST['location']) && $_POST['location'] === $loc) ? 'selected' : '' ?>><?= esc($loc) ?></option>
        <?php endforeach; ?>
      </select>
    <?php else: ?>
      <!-- fallback free-text -->
      <input type="text" name="location" placeholder="Location (e.g., Locker Room)" required value="<?= esc($_POST['location_text'] ?? '') ?>">
    <?php endif; ?>

    <br>
    <button type="submit" name="submit_report">Submit Report</button>
    <div class="message"><?= esc($message) ?></div>
  </form>

  <a class="back-btn" href="/campus/gym/gym_home_dashboard.php">← Back to Dashboard</a>

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



