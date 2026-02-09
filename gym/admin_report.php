<?php
// campus/gym/admin_report.php
// Admin view for gym reports with filter/search (safe + styled)
// Expects mysqli $conn from db_connect.php

session_start();
require_once __DIR__ . '/db_connect.php';

// Access control: only admin/superadmin
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || !in_array($_SESSION['role'], ['admin','superadmin'], true)) {
    header("Location: /campus/login.php?module=gym");
    exit;
}

$message = '';
$error = '';

// Handle POST actions: toggle visibility / update status / delete
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Toggle user view setting
    if (isset($_POST['toggle_visibility'])) {
        $allow = isset($_POST['allow_user_view_reports']) && $_POST['allow_user_view_reports'] == '1' ? 1 : 0;
        $stmt = $conn->prepare("INSERT INTO gym_admin_settings (`id`,`allow_user_view_reports`) VALUES (1,?) ON DUPLICATE KEY UPDATE allow_user_view_reports = VALUES(allow_user_view_reports)");
        $stmt->bind_param("i", $allow);
        if ($stmt->execute()) $message = "Setting saved.";
        else $error = "Failed to save setting: " . $stmt->error;
        $stmt->close();
    }

    // Update status
    if (isset($_POST['update_status']) && isset($_POST['report_id'])) {
        $report_id = (int)$_POST['report_id'];
        $new_status = $_POST['status'] ?? 'Pending';
        $allowed = ['Pending','Resolved','Rejected'];
        if (!in_array($new_status, $allowed, true)) $error = "Invalid status.";
        else {
            $stmt = $conn->prepare("UPDATE gym_reports SET status = ? WHERE id = ?");
            $stmt->bind_param("si", $new_status, $report_id);
            if ($stmt->execute()) $message = "Status updated.";
            else $error = "Failed to update status: " . $stmt->error;
            $stmt->close();
        }
    }

    // Delete report
    if (isset($_POST['delete_report']) && isset($_POST['report_id'])) {
        $report_id = (int)$_POST['report_id'];
        $stmt = $conn->prepare("DELETE FROM gym_reports WHERE id = ?");
        $stmt->bind_param("i", $report_id);
        if ($stmt->execute()) $message = "Report deleted.";
        else $error = "Failed to delete: " . $stmt->error;
        $stmt->close();
    }
}

// Read the "allow users to view" setting
$allow_user_view_reports = false;
$chk = $conn->query("SHOW TABLES LIKE 'gym_admin_settings'");
if ($chk && $chk->num_rows > 0) {
    $r = $conn->query("SELECT allow_user_view_reports FROM gym_admin_settings WHERE id = 1 LIMIT 1");
    if ($r && $r->num_rows > 0) {
        $row = $r->fetch_assoc();
        $allow_user_view_reports = (int)$row['allow_user_view_reports'] === 1;
    }
}

// Read possible locations from gym_locations.txt for filter select
$locations_file = __DIR__ . '/gym_locations.txt';
$locations = [];
if (file_exists($locations_file)) {
    $lines = file($locations_file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        $line = trim($line);
        if ($line !== '') $locations[] = $line;
    }
}

// -------------------- Filters (from GET) --------------------
$filter_name = trim($_GET['q_name'] ?? '');
$filter_dept = trim($_GET['q_dept'] ?? '');
$filter_status = trim($_GET['q_status'] ?? '');
$filter_location = trim($_GET['q_location'] ?? '');
$filter_from = trim($_GET['q_from'] ?? '');
$filter_to = trim($_GET['q_to'] ?? '');

// Build base query and WHERE clauses (we'll safely escape values)
$where = [];
if ($filter_name !== '') {
    // match reporter_name or username/name in users — we'll do a LEFT JOIN in SELECT stage
    $where[] = "(gr.reporter_name LIKE '%" . $conn->real_escape_string($filter_name) . "%' OR u.username LIKE '%" . $conn->real_escape_string($filter_name) . "%' OR u.name LIKE '%" . $conn->real_escape_string($filter_name) . "%')";
}
if ($filter_dept !== '') {
    $where[] = "(gr.department LIKE '%" . $conn->real_escape_string($filter_dept) . "%' OR u.department LIKE '%" . $conn->real_escape_string($filter_dept) . "%')";
}
if ($filter_status !== '') {
    $where[] = "gr.status = '" . $conn->real_escape_string($filter_status) . "'";
}
if ($filter_location !== '') {
    $where[] = "gr.location = '" . $conn->real_escape_string($filter_location) . "'";
}
if ($filter_from !== '') {
    $from = $conn->real_escape_string($filter_from . ' 00:00:00');
    $where[] = "gr.report_time >= '" . $from . "'";
}
if ($filter_to !== '') {
    $to = $conn->real_escape_string($filter_to . ' 23:59:59');
    $where[] = "gr.report_time <= '" . $to . "'";
}

$where_sql = "";
if (count($where) > 0) $where_sql = "WHERE " . implode(" AND ", $where);

// ------------------ Fetch reports with left join to users ------------------
$query = "
  SELECT gr.id, gr.location, gr.reporter_name, gr.department AS legacy_department,
         gr.issue, gr.report_time, gr.status,
         u.id AS user_id, u.username AS user_username, u.name AS user_name, u.department AS user_department, u.college_id
  FROM gym_reports gr
  LEFT JOIN users u ON (u.username = gr.reporter_name OR u.name = gr.reporter_name OR u.college_id = gr.reporter_name)
  $where_sql
  ORDER BY gr.report_time DESC
  LIMIT 1000
";
$res = $conn->query($query);
$displayReports = [];
if ($res) {
    while ($row = $res->fetch_assoc()) {
        // Determine best display values
        $display_name = $row['user_name'] ?: $row['user_username'] ?: $row['reporter_name'];
        $display_dept = $row['user_department'] ?: $row['legacy_department'] ?: '';
        $displayReports[] = [
            'id' => $row['id'],
            'user_college_id' => $row['college_id'] ?: null,
            'user_id' => $row['user_id'] ?: null,
            'display_name' => $display_name,
            'display_username' => $row['user_username'] ?: null,
            'display_department' => $display_dept,
            'location' => $row['location'],
            'issue' => $row['issue'],
            'report_time' => $row['report_time'],
            'status' => $row['status'],
        ];
    }
} else {
    $error = "Failed to fetch reports: " . $conn->error;
}

// helper
function esc($v){ return htmlspecialchars($v ?? '', ENT_QUOTES, 'UTF-8'); }
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<title>Admin — Gym Reports</title>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap" rel="stylesheet">
<style>
  body { font-family:'Poppins',sans-serif; background:url('login.jpeg') no-repeat center center fixed; background-size:cover; color:white; margin:0; padding:0; }
  .container { width:90%; margin:40px auto; background:rgba(0,0,0,0.7); border-radius:15px; padding:20px; }
  h1 { text-align:center; margin-bottom:20px; color:#00bfff; }
  .topbar { display:flex; justify-content:space-between; align-items:center; gap:10px; margin-bottom:12px; flex-wrap:wrap; }
  .btn { background:#00bfff; color:#002; padding:8px 12px; border-radius:8px; text-decoration:none; font-weight:600; border:none; cursor:pointer; }
  .btn-danger { background:#ff4444; color:#fff; }
  .msg { padding:10px; border-radius:8px; margin-bottom:12px; }
  .success { background:#d1ffd8; color:#003800; }
  .error { background:#ffd1d1; color:#600; }
  table { width:100%; border-collapse:collapse; margin-top:12px; color:white; }
  th, td { padding:10px 8px; border-bottom:1px solid rgba(255,255,255,0.06); text-align:center; font-size:14px; vertical-align:middle; }
  th { background: rgba(255,255,255,0.03); color:#bfe9ff; }
  select.status-select { padding:6px; border-radius:6px; border:1px solid rgba(255,255,255,0.08); background:rgba(255,255,255,0.02); color:#fff; }
  .small { font-size:12px; color:#ccc; display:block; margin-top:6px; }
  .actions form { display:inline-block; margin-right:6px; }
  .back { margin-left:6px; background: #0077b6; color:white; padding:8px 12px; border-radius:8px; text-decoration:none; }
  .filters { display:flex; gap:10px; flex-wrap:wrap; align-items:center; margin-bottom:14px; }
  .filters input[type="text"], .filters select, .filters input[type="date"] { padding:6px 8px; border-radius:6px; border:1px solid rgba(255,255,255,0.08); background:rgba(255,255,255,0.02); color:#fff; }
  .filter-row { display:flex; gap:8px; align-items:center; }
  @media(max-width:900px){ .topbar { flex-direction:column; align-items:flex-start; } table { font-size:13px; } .filters { flex-direction:column; align-items:flex-start; } }
</style>
</head>
<body>

<div class="container">
  <div class="topbar">
    <div>
      <h1>Gym — Reports (Admin)</h1>
      <div class="small">Logged in as: <strong><?= esc($_SESSION['username'] ?? 'admin') ?></strong></div>
    </div>

    <div>
      <a class="btn" href="admin_gym_dashboard.php">← Back to Dashboard</a>
      <a class="btn" href="campus/logout.php">Logout</a>
    </div>
  </div>

  <?php if ($message): ?><div class="msg success"><?= esc($message) ?></div><?php endif; ?>
  <?php if ($error): ?><div class="msg error"><?= esc($error) ?></div><?php endif; ?>

  <!-- Toggle visibility -->
  <form method="post" style="text-align:center; margin-bottom:12px;">
    <label style="color:#bfe9ff;">
      <input type="checkbox" name="allow_user_view_reports" value="1" <?= $allow_user_view_reports ? 'checked' : '' ?>>
      &nbsp;Allow users to view previous reports
    </label>
    &nbsp;<button class="btn" type="submit" name="toggle_visibility">Save Setting</button>
  </form>

  <!-- FILTERS / SEARCH -->
  <form method="get" class="filters" style="justify-content:center;">
    <div class="filter-row">
      <label class="small">Name:</label>
      <input type="text" name="q_name" value="<?= esc($filter_name) ?>" placeholder="Reporter name or username">
    </div>
    <div class="filter-row">
      <label class="small">Dept:</label>
      <input type="text" name="q_dept" value="<?= esc($filter_dept) ?>" placeholder="Department">
    </div>
    <div class="filter-row">
      <label class="small">Status:</label>
      <select name="q_status">
        <option value="">All</option>
        <option value="Pending" <?= $filter_status==='Pending'?'selected':'' ?>>Pending</option>
        <option value="Resolved" <?= $filter_status==='Resolved'?'selected':'' ?>>Resolved</option>
        <option value="Rejected" <?= $filter_status==='Rejected'?'selected':'' ?>>Rejected</option>
      </select>
    </div>
    <div class="filter-row">
      <label class="small">Location:</label>
      <select name="q_location">
        <option value="">All</option>
        <?php foreach ($locations as $loc): ?>
            <option value="<?= esc($loc) ?>" <?= $filter_location === $loc ? 'selected':'' ?>><?= esc($loc) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="filter-row">
      <label class="small">From:</label>
      <input type="date" name="q_from" value="<?= esc($filter_from) ?>">
    </div>
    <div class="filter-row">
      <label class="small">To:</label>
      <input type="date" name="q_to" value="<?= esc($filter_to) ?>">
    </div>
    <div>
      <button class="btn" type="submit">Apply</button>
      <a class="btn" href="admin_report.php" style="margin-left:8px;">Reset</a>
    </div>
  </form>

  <!-- REPORTS TABLE -->
  <table>
    <thead>
      <tr>
        <th>#</th>
        <th>Member ID</th>
        <th>Reporter (Name / Username)</th>
        <th>Department</th>
        <th>Location</th>
        <th>Issue</th>
        <th>Reported On</th>
        <th>Status / Actions</th>
      </tr>
    </thead>
    <tbody>
      <?php if (count($displayReports) > 0): $i = 1; foreach ($displayReports as $r): $status = $r['status'] ?? 'Pending'; ?>
      <tr>
        <td><?= $i++ ?></td>
        <td><?= esc($r['user_college_id'] ?: '—') ?></td>
        <td style="text-align:left;">
            <?= esc($r['display_name']) ?>
            <?php if (!empty($r['display_username'])): ?><div class="small">(<?= esc($r['display_username']) ?>)</div><?php endif; ?>
        </td>
        <td><?= esc($r['display_department'] ?: '—') ?></td>
        <td><?= esc($r['location']) ?></td>
        <td style="text-align:left; max-width:260px;"><?= nl2br(esc($r['issue'])) ?></td>
        <td><?= esc($r['report_time']) ?></td>
        <td>
          <div style="margin-bottom:8px;"><span class="status-badge status-<?= esc($status) ?>"><?= esc($status) ?></span></div>

          <form method="post" style="display:inline;">
            <input type="hidden" name="report_id" value="<?= (int)$r['id'] ?>">
            <select name="status" class="status-select">
              <option value="Pending" <?= $status==='Pending'?'selected':'' ?>>Pending</option>
              <option value="Resolved" <?= $status==='Resolved'?'selected':'' ?>>Resolved</option>
              <option value="Rejected" <?= $status==='Rejected'?'selected':'' ?>>Rejected</option>
            </select>
            <input type="hidden" name="update_status" value="1">
            <button type="submit" class="btn">Update</button>
          </form>

          <form method="post" style="display:inline;" onsubmit="return confirm('Delete this report?');">
            <input type="hidden" name="report_id" value="<?= (int)$r['id'] ?>">
            <button type="submit" name="delete_report" class="delete-btn">Delete</button>
          </form>
        </td>
      </tr>
      <?php endforeach; else: ?>
      <tr><td colspan="8" style="text-align:center;">No reports found.</td></tr>
      <?php endif; ?>
    </tbody>
  </table>

</div>
</body>
</html>
