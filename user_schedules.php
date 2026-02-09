<?php
session_start();
require_once "db.php";
require_once "auth_check_user.php"; // ensures only logged-in users can access

// Redirect if not logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

// ======================
// Academic Year (June → May)
// ======================
$currentYear = date('Y');
$startYear = (date('n') < 6) ? $currentYear - 1 : $currentYear;
$startDate = new DateTime("$startYear-06-01");
$endDate   = new DateTime(($startYear + 1) . "-05-31");

// Determine week range (Mon–Fri)
$weekParam = $_GET['week'] ?? '';
if ($weekParam) {
    $monday = date_create_from_format('Y-m-d', $weekParam);
    if (!$monday) $monday = new DateTime();
} else {
    $today = new DateTime();
    $monday = clone $today;
    $weekday = (int)$monday->format('N');
    $monday->modify('-' . ($weekday - 1) . ' days');
}

$week_monday_str = $monday->format('Y-m-d');

// Build array of 5 weekdays
$days = [];
for ($i = 0; $i < 5; $i++) {
    $d = clone $monday;
    $d->modify("+$i days");
    $days[] = $d;
}

// Prev/Next week with limits
$prev_mon = clone $monday; $prev_mon->modify('-7 days');
$next_mon = clone $monday; $next_mon->modify('+7 days');
$disablePrev = $prev_mon < $startDate;
$disableNext = $next_mon > $endDate;

// Fetch tasks
$tasksRes = $conn->query("SELECT id, task_name, location, frequency FROM cleaning_tasks ORDER BY sort_order ASC, id ASC");
$tasks = $tasksRes->fetch_all(MYSQLI_ASSOC);

// Fetch entries for the week
$dates_sql_in = implode(',', array_map(fn($dt) => "'" . $conn->real_escape_string($dt->format('Y-m-d')) . "'", $days));
$taskIds = array_map(fn($t) => intval($t['id']), $tasks);
$entries_map = [];
if (count($taskIds) > 0) {
    $ids_sql = implode(',', $taskIds);
    $entriesQ = "
      SELECT task_id, schedule_date, done
      FROM cleaning_entries
      WHERE schedule_date IN ($dates_sql_in)
      AND task_id IN ($ids_sql)
    ";
    $res = $conn->query($entriesQ);
    while ($r = $res->fetch_assoc()) {
        $entries_map[$r['task_id']][$r['schedule_date']] = intval($r['done']);
    }
}

// Fetch notes for the week
$week_notes = '';
$noteStmt = $conn->prepare("SELECT notes FROM cleaning_week_notes WHERE week_monday=?");
$noteStmt->bind_param("s", $week_monday_str);
$noteStmt->execute();
$res = $noteStmt->get_result();
if ($row = $res->fetch_assoc()) $week_notes = $row['notes'];
$noteStmt->close();

function esc($v){ return htmlspecialchars($v ?? '', ENT_QUOTES, 'UTF-8'); }
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<title>User | Weekly Cleaning Schedule</title>
<style>
body{font-family:'Segoe UI',sans-serif;background:#eef6fc;margin:0;padding:0;color:#023047;}
.header{background:#0077b6;color:#fff;padding:14px 20px;display:flex;justify-content:space-between;align-items:center;}
.header a{color:#fff;text-decoration:none;margin-left:14px;font-weight:600;}
.container{max-width:1100px;margin:22px auto;padding:20px;}
.card{background:#fff;border-radius:10px;padding:18px;box-shadow:0 2px 8px rgba(0,0,0,0.08);}
.topbar{display:flex;justify-content:space-between;align-items:center;margin-bottom:12px;}
.week-nav{display:flex;align-items:center;gap:10px;}
.week-nav button{background:#0077b6;color:#fff;border:none;padding:8px 10px;border-radius:6px;cursor:pointer;}
.week-nav button[disabled]{opacity:0.5;cursor:not-allowed;}
.week-title{font-size:20px;font-weight:700;color:#023e8a;}
.table-wrap{overflow:auto;margin-top:14px;}
.schedule-table{width:100%;border-collapse:collapse;border-radius:8px;overflow:hidden;}
.schedule-table th,.schedule-table td{padding:12px;border:1px solid #e6eef7;text-align:center;vertical-align:middle;}
.schedule-table th{background:#ffffff;color:#023e8a;}
.left-col{background:#f3fbff;text-align:left;padding-left:10px;min-width:220px;}
.row-index{width:46px;background:#d9eefc;font-weight:700;}
.task-name{font-weight:600;padding-left:8px;}
.cell-btn{font-size:20px;border:none;background:transparent;cursor:default;}
.cell-done{color:green;}
.cell-not{color:#d62828;}
.notes{width:100%;padding:10px;border-radius:6px;border:1px solid #cfe8ff;margin-top:12px;}
.save-btn{background:#0077b6;color:#fff;border:none;padding:10px 16px;border-radius:8px;cursor:pointer;font-weight:700;}
@media(max-width:900px){.left-col{min-width:160px;}.notes{width:100%;}}
</style>
</head>
<body>

<div class="header">
  <div><strong>Connect My Campus</strong> — Weekly Cleaning Schedule</div>
  <div>
    <a href="user_dashboard.php">🏠 Dashboard</a>
    <a href="logout.php">🚪 Logout</a>
  </div>
</div>

<div class="container">
  <div class="card">
    <div class="topbar">
      <div class="week-nav">
        <form method="get" style="display:inline;">
            <input type="hidden" name="week" value="<?=esc($prev_mon->format('Y-m-d'))?>">
            <button type="submit" <?= $disablePrev?'disabled':'' ?>>‹ Prev week</button>
        </form>
        <div class="week-title">Weekly Cleaning Schedule — <?= esc($monday->format('M j, Y')) ?> to <?= esc($days[4]->format('M j, Y')) ?></div>
        <form method="get" style="display:inline;">
            <input type="hidden" name="week" value="<?=esc($next_mon->format('Y-m-d'))?>">
            <button type="submit" <?= $disableNext?'disabled':'' ?>>Next week ›</button>
        </form>
      </div>
    </div>

    <div class="table-wrap">
        <table class="schedule-table">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Place / Task</th>
                    <?php foreach($days as $d): ?>
                        <th><?= esc($d->format('D')) ?><br><small><?= esc($d->format('M j')) ?></small></th>
                    <?php endforeach; ?>
                </tr>
            </thead>
            <tbody>
                <?php if(count($tasks)==0): ?>
                    <tr><td colspan="<?=7?>">No cleaning tasks defined yet.</td></tr>
                <?php else: $i=1; foreach($tasks as $t): ?>
                    <tr>
                        <td><?= $i++ ?></td>
                        <td class="left-col">
                            <div class="task-name"><?=esc($t['task_name'])?></div>
                            <small><?=esc($t['location'])?></small><br>
                            <small>Frequency: <?=esc($t['frequency'])?></small>
                        </td>
                        <?php foreach($days as $d):
                            $ds=$d->format('Y-m-d');
                            $done=$entries_map[$t['id']][$ds]??0;
                        ?>
                            <td>
                                <button type="button" class="cell-btn <?= $done?'cell-done':'cell-not' ?>" disabled>
                                    <?= $done?'✅':'❌' ?>
                                </button>
                            </td>
                        <?php endforeach; ?>
                    </tr>
                <?php endforeach; endif; ?>
            </tbody>
        </table>

        <?php if($week_notes): ?>
        <div class="notes">
            <strong>📝 Notes for this week:</strong><br>
            <?= nl2br(esc($week_notes)) ?>
        </div>
        <?php endif; ?>
    </div>
  </div>
</div>

</body>
</html>
