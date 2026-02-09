<?php
session_start();
require_once "db.php";
require_once "auth_check_user.php";

// Redirect if not logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$success_msg = "";
$error_msg = "";

// Load locations from locations.txt
$locations_file = __DIR__ . "/locations.txt";
$locations = file_exists($locations_file) ? file($locations_file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) : [];

// Handle new suggestion
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['suggestion'])) {
    $suggestion = trim($_POST['suggestion']);
    $location   = trim($_POST['location']);
    $user_id    = $_SESSION['user_id'];

    if ($suggestion !== "" && $location !== "") {
        $stmt = $conn->prepare("
            INSERT INTO suggestions (user_id, suggestion, location, status, created_at, status_updated_at)
            VALUES (?, ?, ?, 'Pending', NOW(), NOW())
        ");
        $stmt->bind_param("iss", $user_id, $suggestion, $location);
        if ($stmt->execute()) $success_msg = "✅ Suggestion submitted successfully!";
        else $error_msg = "❌ Error submitting suggestion.";
        $stmt->close();
    }
}

// ✅ Fetch my suggestions (supports both old & new user_id formats)
$myStmt = $conn->prepare("
    SELECT s.suggestion, s.location, s.status, s.created_at, s.status_updated_at
    FROM suggestions s
    WHERE s.user_id = ? 
       OR s.user_id = (
           SELECT u.college_id FROM users u WHERE u.id = ?
       )
    ORDER BY s.created_at DESC
");
$myStmt->bind_param("ii", $_SESSION['user_id'], $_SESSION['user_id']);
$myStmt->execute();
$mySuggestions = $myStmt->get_result();
$myCount = $mySuggestions->num_rows;
$myStmt->close();

// ✅ Fetch community suggestions (exclude this user's suggestions)
$current_month = date('Y-m');
$commStmt = $conn->prepare("
    SELECT s.suggestion, s.location, s.status, s.created_at, s.status_updated_at
    FROM suggestions s
    WHERE 
        (s.user_id <> ? AND s.user_id <> (
            SELECT u.college_id FROM users u WHERE u.id = ?
        ))
        AND DATE_FORMAT(s.created_at, '%Y-%m') = ?
    ORDER BY s.created_at DESC
");
$commStmt->bind_param("iis", $_SESSION['user_id'], $_SESSION['user_id'], $current_month);
$commStmt->execute();
$communitySuggestions = $commStmt->get_result();
$communityCount = $communitySuggestions->num_rows;
$commStmt->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>User Suggestions | Connect My Campus</title>
<style>
body { font-family:'Segoe UI', sans-serif; background:#eef6fc; margin:0; padding:0; }
.container { max-width:900px; margin:30px auto; padding:0 15px; }
header { background:#0077b6; color:white; padding:15px 30px; display:flex; justify-content:space-between; align-items:center; }
header a { color:white; margin-left:20px; font-weight:600; }
button,input[type="submit"] { background:#0077b6; color:white; border:none; border-radius:8px; padding:10px 18px; cursor:pointer; }
button:hover,input[type="submit"]:hover { background:#023e8a; }
form { background:white; padding:20px; border-radius:12px; box-shadow:0 2px 10px rgba(0,0,0,0.08); margin-top:20px; }
textarea, select { width:100%; padding:10px; border-radius:6px; border:1px solid #ccc; margin-top:10px; }
.message { padding:12px 18px; margin:15px 0; border-radius:8px; font-weight:500; }
.success { background:#d1ffd8; color:#006400; }
.error { background:#ffd1d1; color:#a40000; }
.card { background:white; padding:20px; border-radius:12px; box-shadow:0 2px 10px rgba(0,0,0,0.08); margin-top:20px; }
table { width:100%; border-collapse: collapse; margin-top:10px; }
th, td { padding:12px 10px; text-align:left; vertical-align:top; }
th { background:#90e0ef; }
tr:nth-child(even) { background:#f8f9fa; }
.status-badge { padding:6px 12px; border-radius:20px; font-weight:bold; color:white; display:inline-block; min-width:80px; text-align:center; }
.status-Pending { background:#ffcc00; color:black; }
.status-Reviewed { background:#0077b6; }
.status-Implemented { background:#28a745; }
.updated-time { display:block; font-size:12px; color:#555; margin-top:4px; }
@media(max-width:700px){ header{flex-direction:column;text-align:center;} header a{margin:5px 0;} table, th, td{font-size:13px;} }
</style>
</head>
<body>

<header>
<div><strong>Connect My Campus</strong> — Suggestions</div>
<div>
    <a href="user_dashboard.php">🏠 Dashboard</a>
    <a href="logout.php">🚪 Logout</a>
</div>
</header>

<div class="container">
<?php if($success_msg):?><div class="message success"><?=htmlspecialchars($success_msg)?></div><?php endif;?>
<?php if($error_msg):?><div class="message error"><?=htmlspecialchars($error_msg)?></div><?php endif;?>

<button onclick="toggleForm()">➕ Add Suggestion</button>
<form method="POST" id="suggestionForm" style="display:none;">
<textarea name="suggestion" placeholder="Enter your suggestion..." required></textarea>
<select name="location" required>
<option value="">-- Select Location --</option>
<?php foreach($locations as $loc): ?>
<option value="<?=htmlspecialchars($loc)?>"><?=htmlspecialchars($loc)?></option>
<?php endforeach; ?>
</select>
<br><br>
<input type="submit" value="Submit">
</form>

<div class="card">
<h3>📋 My Suggestions (<?= $myCount ?>)</h3>
<table>
<tr><th>Suggestion</th><th>Location</th><th>Status</th><th>Date</th></tr>
<?php if($myCount > 0): while($r = $mySuggestions->fetch_assoc()): $statusClass='status-'.$r['status']; ?>
<tr>
<td><?=htmlspecialchars($r['suggestion'])?></td>
<td><?=htmlspecialchars($r['location'])?></td>
<td>
    <span class="status-badge <?=$statusClass?>"><?=htmlspecialchars($r['status'])?></span>
    <span class="updated-time">Last updated on: <?=htmlspecialchars($r['status_updated_at'])?></span>
</td>
<td><?=htmlspecialchars($r['created_at'])?></td>
</tr>
<?php endwhile; else: ?>
<tr><td colspan="4">No suggestions submitted yet.</td></tr>
<?php endif; ?>
</table>
</div>

<div class="card">
<h3>🌍 Community Suggestions (<?= $communityCount ?>)</h3>
<table>
<tr><th>Suggestion</th><th>Location</th><th>Status</th><th>Date</th></tr>
<?php if($communityCount > 0): while($r = $communitySuggestions->fetch_assoc()): $statusClass='status-'.$r['status']; ?>
<tr>
<td><?=htmlspecialchars($r['suggestion'])?></td>
<td><?=htmlspecialchars($r['location'])?></td>
<td>
    <span class="status-badge <?=$statusClass?>"><?=htmlspecialchars($r['status'])?></span>
    <span class="updated-time">Last updated on: <?=htmlspecialchars($r['status_updated_at'])?></span>
</td>
<td><?=htmlspecialchars($r['created_at'])?></td>
</tr>
<?php endwhile; else: ?>
<tr><td colspan="4">No community suggestions this month.</td></tr>
<?php endif; ?>
</table>
</div>

</div>

<script>
function toggleForm(){
    const form = document.getElementById("suggestionForm");
    form.style.display = form.style.display==="block"?"none":"block";
}
</script>

</body>
</html>
