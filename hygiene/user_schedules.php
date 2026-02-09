<?php
// user_schedules.php - hygiene module (drop-in replace)
// Put at: /campus/hygiene/user_schedules.php

session_start();

// DEBUG BANNER - remove after verifying
// echo "<div style='background:#ffd; padding:8px; border:2px solid #f90;'>DEBUG: Loaded file: " . __FILE__ . "</div>";

require_once __DIR__ . '/../db.php';        // main DB connector
require_once __DIR__ . '/auth_check_user.php';

// Confirm session role
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'user') {
    header("Location: ../login.php?module=hygiene");
    exit;
}

// show which DB driver is available (for debugging - remove later)
$db_mode = 'none';
if (isset($pdo) && $pdo instanceof PDO) $db_mode = 'pdo';
elseif (isset($conn) && $conn instanceof mysqli) $db_mode = 'mysqli';

// If neither exist, fail fast with helpful message
if ($db_mode === 'none') {
    http_response_code(500);
    echo "<h2 style='color:red'>Database connection not found.</h2>";
    echo "<p>Ensure <code>../db.php</code> defines either <code>\$pdo</code> (PDO) or <code>\$conn</code> (mysqli).</p>";
    exit;
}

// helper esc
function esc($v){ return htmlspecialchars($v ?? '', ENT_QUOTES, 'UTF-8'); }

// Helper functions to run queries using either PDO or mysqli
function db_query_fetchall($sql) {
    global $pdo, $conn, $db_mode;
    if ($db_mode === 'pdo') {
        return $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    } else {
        $res = $conn->query($sql);
        return $res ? $res->fetch_all(MYSQLI_ASSOC) : [];
    }
}
function db_prepare_execute($sql, $params = []) {
    global $pdo, $conn, $db_mode;
    if ($db_mode === 'pdo') {
        $st = $pdo->prepare($sql);
        $st->execute($params);
        return $st;
    } else {
        // simple mysqli prepared statement helper (positional only)
        $st = $conn->prepare($sql);
        if ($st === false) return false;
        if (!empty($params)) {
            // build types string (all strings by default)
            $types = str_repeat('s', count($params));
            $st->bind_param($types, ...$params);
        }
        $st->execute();
        return $st;
    }
}

// =========================
// Week / date calculations
// =========================
$currentYear = date('Y');
$startYear = (date('n') < 6) ? $currentYear - 1 : $currentYear;
$startDate = new DateTime("$startYear-06-01");
$endDate   = new DateTime(($startYear + 1) . "-05-31");

$weekParam = $_GET['week'] ?? '';
if ($weekParam) {
    $monday = DateTime::createFromFormat('Y-m-d', $weekParam);
    if (!$monday) $monday = new DateTime();
} else {
    $today = new DateTime();
    $monday = clone $today;
    $weekday = (int)$monday->format('N');
    $monday->modify('-' . ($weekday - 1) . ' days'); // move to monday
}
$week_monday_str = $monday->format('Y-m-d');

// build Mon-Fri array
$days = [];
for ($i = 0; $i < 5; $i++) {
    $d = clone $monday;
    $d->modify("+$i days");
    $days[] = $d;
}
$prev_mon = clone $monday; $prev_mon->modify('-7 days');
$next_mon = clone $monday; $next_mon->modify('+7 days');
$disablePrev = $prev_mon < $startDate;
$disableNext = $next_mon > $endDate;

// =========================
// Fetch tasks (admin created)
 // cleaning_tasks: id, task_name, location, frequency, pattern, sort_order
// =========================
$tasks = db_query_fetchall("SELECT id, task_name, location, frequency FROM cleaning_tasks ORDER BY sort_order ASC, id ASC");

// =========================
// Fetch entries for the week (cleaning_entries: task_id, schedule_date, done)
// =========================
$entries_map = [];
if (!empty($tasks)) {
    // prepare date list and id list safely
    $date_list = array_map(function($d){ return $d->format('Y-m-d'); }, $days);
    $dates_in = "'" . implode("','", array_map('addslashes', $date_list)) . "'";
    $task_ids = array_map(function($t){ return intval($t['id']); }, $tasks);
    $ids_in = implode(',', $task_ids);
    if ($ids_in === '') $ids_in = '0';
    $sql = "SELECT task_id, schedule_date, done FROM cleaning_entries WHERE schedule_date IN ($dates_in) AND task_id IN ($ids_in)";
    $rows = db_query_fetchall($sql);
    foreach ($rows as $r) {
        $entries_map[$r['task_id']][$r['schedule_date']] = (int)$r['done'];
    }
}

// =========================
// Fetch week notes
// =========================
$week_notes = '';
if ($db_mode === 'pdo') {
    $st = db_prepare_execute("SELECT notes FROM cleaning_week_notes WHERE week_monday = ?", [$week_monday_str]);
    $week_notes = $st->fetchColumn() ?: '';
} else {
    $st = db_prepare_execute("SELECT notes FROM cleaning_week_notes WHERE week_monday = ?", [$week_monday_str]);
    if ($st) {
        $res = $st->get_result();
        if ($r = $res->fetch_assoc()) $week_notes = $r['notes'];
    }
}

// optional: logged-in info
$logged_user = $_SESSION['user_id'] ?? ($_SESSION['username'] ?? 'Unknown');
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<title>Hygiene — Weekly Cleaning Schedule</title>
<style>
body{font-family:Segoe UI, sans-serif;background:#eef6fc;color:#023047;margin:0}
.header{background:#0077b6;color:#fff;padding:12px 18px;display:flex;justify-content:space-between;align-items:center}
.header .left{font-weight:700}
.header a{color:#fff;text-decoration:none;margin-left:12px}
.container{max-width:1100px;margin:20px auto;padding:18px}
.card{background:#fff;border-radius:10px;padding:16px;box-shadow:0 2px 8px rgba(0,0,0,0.06)}
.week-nav{display:flex;justify-content:center;gap:12px;margin-bottom:14px}
.week-nav button{background:#0077b6;color:#fff;border:none;padding:8px 10px;border-radius:6px;cursor:pointer}
.week-title{font-size:16px;color:#023e8a;font-weight:700}
.schedule-table{width:100%;border-collapse:collapse;margin-top:10px}
.schedule-table th,.schedule-table td{padding:10px;border:1px solid #e6eef7;text-align:center}
.schedule-table th{background:#ffffff;color:#023047}
.left-col{background:#f3fbff;text-align:left;padding-left:10px;min-width:220px}
.task-name{font-weight:600}
.cell-btn{font-size:18px;border:none;background:transparent}
.cell-done{color:green}
.cell-not{color:#d62828}
.notes{margin-top:12px;padding:10px;border-radius:8px;background:#f9fcff;border:1px solid #cfe8ff}
.debug{font-size:12px;color:#444;margin-top:6px}
</style>
</head>
<body>

<div class="header">
  <div class="left">Connect My Campus — Hygiene</div>
  <div>
    Logged in as: <strong><?= esc($logged_user) ?></strong>
    <a href="user_dashboard.php">Dashboard</a>
    <a href="../logout.php">Logout</a>
  </div>
</div>

<div class="container">
  <div class="card">
    <div class="week-nav">
      <form method="get" style="display:inline">
        <input type="hidden" name="week" value="<?= esc($prev_mon->format('Y-m-d')) ?>">
        <button <?= $disablePrev ? 'disabled' : '' ?>>‹ Prev</button>
      </form>
      <div class="week-title"><?= esc($monday->format('M j, Y')) ?> — <?= esc($days[4]->format('M j, Y')) ?></div>
      <form method="get" style="display:inline">
        <input type="hidden" name="week" value="<?= esc($next_mon->format('Y-m-d')) ?>">
        <button <?= $disableNext ? 'disabled' : '' ?>>Next ›</button>
      </form>
    </div>

    <table class="schedule-table">
      <thead>
        <tr>
          <th>#</th>
          <th>Place / Task</th>
          <?php foreach ($days as $d): ?>
            <th><?= esc($d->format('D')) ?><br><small><?= esc($d->format('M j')) ?></small></th>
          <?php endforeach; ?>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($tasks)): ?>
          <tr><td colspan="7">No cleaning tasks scheduled yet.</td></tr>
        <?php else: $i = 1; foreach ($tasks as $t): ?>
          <tr>
            <td><?= $i++ ?></td>
            <td class="left-col">
              <div class="task-name"><?= esc($t['task_name']) ?></div>
              <small><?= esc($t['location']) ?></small><br>
              <small>Frequency: <?= esc($t['frequency']) ?></small>
            </td>
            <?php foreach ($days as $d):
                $ds = $d->format('Y-m-d');
                $done = $entries_map[$t['id']][$ds] ?? 0;
            ?>
              <td>
                <button class="cell-btn <?= $done ? 'cell-done' : 'cell-not' ?>" disabled>
                  <?= $done ? '✅' : '❌' ?>
                </button>
              </td>
            <?php endforeach; ?>
          </tr>
        <?php endforeach; endif; ?>
      </tbody>
    </table>

    <?php if (!empty($week_notes)): ?>
      <div class="notes"><strong>📝 Notes for this week:</strong><br><?= nl2br(esc($week_notes)) ?></div>
    <?php endif; ?>

    <div class="debug">
      DB mode: <?= esc($db_mode) ?> &nbsp;|&nbsp; File: <?= esc(basename(__FILE__)) ?>
    </div>
  </div>
</div>

</body>
</html>
