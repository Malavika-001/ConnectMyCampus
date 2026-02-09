<?php
session_start();
require_once "../db.php";
require_once "auth_check_admin.php";

// Academic year limits (June–May)
$currentYear = date('Y');
$startYear = (date('n') < 6) ? $currentYear - 1 : $currentYear;
$startDate = new DateTime("$startYear-06-01");
$endDate   = new DateTime(($startYear + 1) . "-05-31");

// Create tables if not exist
$pdo->exec("CREATE TABLE IF NOT EXISTS cleaning_tasks (
  id INT AUTO_INCREMENT PRIMARY KEY,
  sort_order INT DEFAULT 0,
  task_name VARCHAR(255) NOT NULL,
  location VARCHAR(255) DEFAULT NULL,
  frequency ENUM('Daily','Alternate Days','Weekly') DEFAULT 'Daily',
  pattern ENUM('MWF','TTH') DEFAULT 'MWF',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

$pdo->exec("CREATE TABLE IF NOT EXISTS cleaning_entries (
  id INT AUTO_INCREMENT PRIMARY KEY,
  task_id INT NOT NULL,
  schedule_date DATE NOT NULL,
  done TINYINT(1) NOT NULL DEFAULT 0,
  updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY ux_task_taskdate (task_id, schedule_date),
  FOREIGN KEY (task_id) REFERENCES cleaning_tasks(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

$pdo->exec("CREATE TABLE IF NOT EXISTS cleaning_week_notes (
  week_monday DATE PRIMARY KEY,
  notes TEXT,
  updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

// Load locations
$locations_file = __DIR__ . "/locations.txt";
$locations = file_exists($locations_file)
    ? file($locations_file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES)
    : [];

// Add task
if (isset($_POST['add_task'])) {
    $task_name = trim($_POST['task_name'] ?? '');
    $location  = trim($_POST['task_location'] ?? '');
    $frequency = $_POST['task_frequency'] ?? 'Daily';
    $pattern   = trim($_POST['task_pattern'] ?? '');
    if ($frequency === 'Alternate Days' && $pattern === '') $pattern = 'TTH';

    if ($task_name !== '') {
        $stmt = $pdo->prepare("INSERT INTO cleaning_tasks (task_name, location, frequency, pattern) VALUES (?,?,?,?)");
        $stmt->execute([$task_name, $location, $frequency, $pattern]);
    }
    header("Location: ".$_SERVER['PHP_SELF']);
    exit;
}

// Delete task
if (isset($_POST['delete_task'])) {
    $task_id = intval($_POST['task_id'] ?? 0);
    $pdo->prepare("DELETE FROM cleaning_tasks WHERE id=?")->execute([$task_id]);
    header("Location: ".$_SERVER['PHP_SELF']);
    exit;
}

// Save schedule
if (isset($_POST['save_schedule'])) {
    $week_monday = $_POST['week_monday'] ?? '';
    if ($week_monday) {
        $entries = $_POST['entries'] ?? [];
        $upsert = $pdo->prepare("
            INSERT INTO cleaning_entries (task_id, schedule_date, done)
            VALUES (?,?,?)
            ON DUPLICATE KEY UPDATE done=VALUES(done), updated_at=NOW()
        ");
        foreach ($entries as $taskId => $dates) {
            foreach ($dates as $date => $val) {
                $upsert->execute([$taskId, $date, ($val=='1')?1:0]);
            }
        }

        $notes = trim($_POST['week_notes'] ?? '');
        $pdo->prepare("
            INSERT INTO cleaning_week_notes (week_monday, notes)
            VALUES (?,?)
            ON DUPLICATE KEY UPDATE notes=VALUES(notes), updated_at=NOW()
        ")->execute([$week_monday, $notes]);
    }
    header("Location: ".$_SERVER['PHP_SELF'].'?week='.urlencode($_POST['week_monday']));
    exit;
}

// Determine current week
$weekParam=$_GET['week']??'';
if($weekParam){$monday=date_create_from_format('Y-m-d',$weekParam)?:new DateTime();}
else{$today=new DateTime();$monday=clone $today;$weekday=(int)$monday->format('N');$monday->modify('-'.($weekday-1).' days');}
$week_monday_str=$monday->format('Y-m-d');
$days=[];for($i=0;$i<5;$i++){$d=clone $monday;$d->modify("+$i days");$days[]=$d;}
$prev_mon=clone $monday;$prev_mon->modify('-7 days');
$next_mon=clone $monday;$next_mon->modify('+7 days');
$disablePrev=$prev_mon<$startDate;$disableNext=$next_mon>$endDate;

// Fetch data
$tasks=$pdo->query("SELECT id,task_name,location,frequency,pattern FROM cleaning_tasks ORDER BY sort_order,id")->fetchAll();
$entries_map=[];
if($tasks){
  $date_in=implode(',',array_map(fn($d)=>$pdo->quote($d->format('Y-m-d')),$days));
  $ids=implode(',',array_map(fn($t)=>intval($t['id']),$tasks));
  if($ids){
    $res=$pdo->query("SELECT task_id,schedule_date,done FROM cleaning_entries WHERE schedule_date IN ($date_in) AND task_id IN ($ids)");
    foreach($res as $r){$entries_map[$r['task_id']][$r['schedule_date']]=$r['done'];}
  }
}

// Compute summary
$summary=['Completed'=>0,'Partial'=>0,'Pending'=>0];
foreach($tasks as $t){
  $tid=$t['id'];$freq=$t['frequency'];$pattern=$t['pattern'];
  $reqDays=($freq==='Alternate Days')?($pattern==='TTH'?[2,4]:[1,3,5]):[1,2,3,4,5];
  $doneDays=[];
  if(isset($entries_map[$tid])){
    foreach($entries_map[$tid] as $date=>$done){if($done){$d=new DateTime($date);$doneDays[]=(int)$d->format('N');}}
  }
  $doneCount=count(array_intersect($reqDays,$doneDays));
  $reqCount=count($reqDays);
  $isComplete=($freq==='Weekly'&&$doneCount>=1)||($doneCount===$reqCount);
  if($isComplete)$summary['Completed']++; elseif($doneCount>0)$summary['Partial']++; else $summary['Pending']++;
}

// Week notes
$stmt=$pdo->prepare("SELECT notes FROM cleaning_week_notes WHERE week_monday=?");
$stmt->execute([$week_monday_str]);
$week_notes=$stmt->fetchColumn()?:'';
function esc($v){return htmlspecialchars($v??'',ENT_QUOTES,'UTF-8');}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<title>Admin Panel — Weekly Cleaning Schedule</title>
<style>
body{font-family:'Segoe UI',sans-serif;background:#e9f5ff;margin:0;color:#023047;}
.header{background:#0077b6;color:#fff;padding:15px 25px;display:flex;justify-content:space-between;align-items:center;}
.header a{color:#fff;text-decoration:none;font-weight:600;margin-left:20px;}
.header a:hover{text-decoration:underline;}
.container{max-width:1100px;margin:25px auto;padding:25px;}
.card{background:#fff;border-radius:14px;box-shadow:0 2px 10px rgba(0,0,0,0.1);padding:20px;}
.week-nav button{background:#0077b6;color:#fff;border:none;border-radius:6px;padding:7px 15px;cursor:pointer;font-weight:500;}
.week-nav button[disabled]{opacity:.5;cursor:not-allowed;}
.schedule-table{width:100%;border-collapse:collapse;margin-top:20px;}
.schedule-table th,.schedule-table td{border:1px solid #e6eef7;padding:10px;text-align:center;}
.schedule-table th{background:#f1f8ff;}
.left-col{text-align:left;background:#f8fcff;}
.cell-btn{font-size:22px;border:none;background:transparent;cursor:pointer;}
.cell-done{color:#38b000;}
.cell-not{color:#e63946;}
.delete-task-btn{background:#ff4d4f;color:#fff;border:none;border-radius:6px;padding:6px 10px;cursor:pointer;}
.summary{display:flex;justify-content:center;gap:15px;margin-top:18px;font-weight:600;}
.summary div{padding:8px 18px;border-radius:8px;}
.summary .completed{background:#d1ffd8;}
.summary .partial{background:#fff6c2;}
.summary .pending{background:#ffd1d1;}
.save-btn{background:#0077b6;color:#fff;border:none;border-radius:6px;padding:10px 18px;font-weight:600;cursor:pointer;}
.small-form input,.small-form select{padding:8px;border-radius:6px;border:1px solid #ccc;margin-right:8px;}
.small-form input[type=submit]{background:#0077b6;color:#fff;border:none;cursor:pointer;padding:8px 12px;border-radius:6px;}
.notes{width:70%;padding:10px;border-radius:6px;border:1px solid #bcd8ff;}
.topbar{display:flex;justify-content:space-between;align-items:center;gap:12px;flex-wrap:wrap;}
</style>
</head>
<body>
<div class="header">
  <div><strong>Admin Panel</strong> — Weekly Cleaning Schedule</div>
  <div><a href="admin_dashboard.php">🏠 Dashboard</a><a href="../logout.php">🚪 Logout</a></div>
</div>

<div class="container">
<div class="card">
<div class="topbar">
  <div class="week-nav">
    <form method="get"><input type="hidden" name="week" value="<?=esc($prev_mon->format('Y-m-d'))?>"><button <?=$disablePrev?'disabled':''?>>‹ Prev</button></form>
    <b><?=esc($monday->format('M j'))?> – <?=esc($days[4]->format('M j, Y'))?></b>
    <form method="get"><input type="hidden" name="week" value="<?=esc($next_mon->format('Y-m-d'))?>"><button <?=$disableNext?'disabled':''?>>Next ›</button></form>
  </div>
  <form method="post" class="small-form">
    <input type="text" name="task_name" placeholder="New task" required>
    <select name="task_location"><option value="">Location</option>
      <?php foreach($locations as $loc):?><option value="<?=esc($loc)?>"><?=esc($loc)?></option><?php endforeach;?>
    </select>
    <select name="task_frequency" id="freqSelect" onchange="togglePattern(this.value)">
      <option>Daily</option><option>Alternate Days</option><option>Weekly</option>
    </select>
    <select name="task_pattern" id="patternSelect" style="display:none;">
      <option value="MWF">Mon–Wed–Fri</option><option value="TTH">Tue–Thu</option>
    </select>
    <input type="submit" name="add_task" value="Add Task">
  </form>
</div>

<form method="post">
<input type="hidden" name="week_monday" value="<?=esc($week_monday_str)?>">
<table class="schedule-table">
<thead><tr><th>#</th><th>Place / Task</th>
<?php foreach($days as $d):?><th><?=esc($d->format('D'))?><br><small><?=esc($d->format('M j'))?></small></th><?php endforeach;?>
<th>Actions</th></tr></thead>
<tbody>
<?php if(!$tasks):?><tr><td colspan="7">No tasks yet — add one above.</td></tr>
<?php else:$i=1;foreach($tasks as $t):?>
<tr>
<td><?=$i++?></td>
<td class="left-col"><div><?=esc($t['task_name'])?></div><small><?=esc($t['location'])?></small><br><small><?=esc($t['frequency'])?><?php if($t['frequency']==='Alternate Days')echo' ('.esc($t['pattern']).')';?></small></td>
<?php foreach($days as $d):
$ds=$d->format('Y-m-d');$done=$entries_map[$t['id']][$ds]??0;
?>
<td><input type="hidden" id="hid_<?=$t['id'].'_'.$ds?>" name="entries[<?=$t['id']?>][<?=$ds?>]" value="<?=$done?>">
<button type="button" class="cell-btn <?=$done?'cell-done':'cell-not'?>" onclick="toggleCell(<?=$t['id']?>,'<?=$ds?>',this)"><?=$done?'✅':'❌'?></button></td>
<?php endforeach;?>
<td><form method="post" style="display:inline"><input type="hidden" name="task_id" value="<?=$t['id']?>"><input type="submit" name="delete_task" class="delete-task-btn" value="Delete"></form></td>
</tr>
<?php endforeach;endif;?>
</tbody></table>

<div class="summary">
  <div class="completed">✅ Completed: <?=$summary['Completed']?></div>
  <div class="partial">⚠️ Partial: <?=$summary['Partial']?></div>
  <div class="pending">❌ Pending: <?=$summary['Pending']?></div>
</div>

<div style="display:flex;justify-content:space-between;align-items:center;gap:10px;margin-top:15px;flex-wrap:wrap;">
  <textarea name="week_notes" class="notes" placeholder="Notes for this week..."><?=esc($week_notes)?></textarea>
  <button type="submit" name="save_schedule" class="save-btn">Save Schedule</button>
</div>
</form>
</div></div>
<script>
function toggleCell(id,d,btn){const h=document.getElementById('hid_'+id+'_'+d);if(!h)return;
if(h.value==='1'){h.value='0';btn.innerText='❌';btn.classList.remove('cell-done');btn.classList.add('cell-not');}
else{h.value='1';btn.innerText='✅';btn.classList.remove('cell-not');btn.classList.add('cell-done');}}
function togglePattern(f){document.getElementById('patternSelect').style.display=(f==='Alternate Days')?'inline-block':'none';}
</script>
</body>
</html>
