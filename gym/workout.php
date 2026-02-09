<?php
session_start();
include("db_connect.php");

$member_id = $_SESSION['member_id'] ?? 'SSV001';

// Fetch last 7 gym_attendance records
$query = "SELECT date FROM gym_attendance WHERE member_id='$member_id' ORDER BY date DESC LIMIT 7";
$result = mysqli_query($conn, $query);
$gym_attendance = [];
while ($row = mysqli_fetch_assoc($result)) {
    $gym_attendance[] = $row['date'];
}

// Determine missed day
$missed_day = null;
$total_days = count($gym_attendance);
if ($total_days < 7) {
    $missed_day = $total_days + 1;
}

// Fetch workout plans
$plans = mysqli_query($conn, "SELECT * FROM workout_plan ORDER BY id ASC");
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Workout Plan | GYMNASIUM</title>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap" rel="stylesheet">
<style>
body {
  margin: 0;
  padding: 0;
  font-family: 'Poppins', sans-serif;
  background: url('login.jpeg') no-repeat center center fixed;
  background-size: cover;
  color: white;
}
.container { text-align: center; margin-top: 80px; }
.grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
  gap: 20px;
  justify-content: center;
  width: 80%;
  margin: auto;
}
.card {
  background: rgba(0,0,0,0.6);
  border-radius: 15px;
  padding: 20px;
  border: 1px solid rgba(255,255,255,0.2);
  transition: 0.3s;
  text-align: left;
}
.highlight {
  border: 2px solid #00bfff;
  background: rgba(0,191,255,0.15);
  box-shadow: 0 0 15px #00bfff;
}
h3 { color: #00bfff; margin-bottom: 10px; }
h4 { color: #fff; margin-bottom: 5px; }
p { color: #ddd; font-size: 14px; line-height: 1.5em; }
.missed { color: #00bfff; font-weight: bold; margin-top: 10px; }
</style>
</head>
<body>

<div class="container">
  <h1>GYMNASIUM</h1>
  <h2>Workout Plan</h2>
<a class="back-btn" href="/campus/gym/gym_home_dashboard.php">← Back to Dashboard</a>
  <div class="grid">
    <?php while ($row = mysqli_fetch_assoc($plans)) {
      $class = ($row['id'] == $missed_day) ? 'card highlight' : 'card';
      echo "<div class='$class'>";
      echo "<h3>{$row['day']}</h3>";
      echo "<h4>Part: " . htmlspecialchars($row['exercise']) . "</h4>";
      echo "<p>" . nl2br(htmlspecialchars($row['description'])) . "</p>";

      if ($class == 'card highlight') {
          echo "<p class='missed'>⚠ You missed this workout day. Catch up soon!</p>";
      }

      echo "</div>";
    } ?>
 
 </div>

</div>

</body>
</html>
