<?php
include('db_connect.php');
session_start();

$message = "";

// Handle gym_attendance save
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_gym_attendance'])) {
    $selected_date = $_POST['date'];
    
    if (!empty($selected_date)) {
        // Get all member IDs
        $all_gym_members = mysqli_query($conn, "SELECT id FROM gym_members");
        $all_ids = [];
        while ($m = mysqli_fetch_assoc($all_gym_members)) {
            $all_ids[] = $m['id'];
        }

        // Handle checked gym_attendance (Present)
        $checked = isset($_POST['gym_attendance']) ? $_POST['gym_attendance'] : [];

        foreach ($all_ids as $member_id) {
            if (isset($checked[$member_id])) {
                // Mark present
                $status = 'P';
                $check = mysqli_query($conn, "SELECT id FROM gym_attendance WHERE member_id='$member_id' AND date='$selected_date'");
                if (mysqli_num_rows($check) > 0) {
                    mysqli_query($conn, "UPDATE gym_attendance SET status='$status' WHERE member_id='$member_id' AND date='$selected_date'");
                } else {
                    mysqli_query($conn, "INSERT INTO gym_attendance (member_id, date, status) VALUES ('$member_id', '$selected_date', '$status')");
                }
            } else {
                // If unchecked → delete that day's record
                mysqli_query($conn, "DELETE FROM gym_attendance WHERE member_id='$member_id' AND date='$selected_date'");
            }
        }

        $message = "✅ gym_attendance successfully saved for $selected_date";
    } else {
        $message = "⚠️ Please select a date first.";
    }
}

// Handle "Unmark All" (delete all gym_attendance for selected date)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['unmark_all'])) {
    $selected_date = $_POST['date'];

    if (!empty($selected_date)) {
        // Delete all gym_attendance records for that date
        mysqli_query($conn, "DELETE FROM gym_attendance WHERE date='$selected_date'");
        $message = "⚠️ All gym_attendance records cleared for $selected_date";
    } else {
        $message = "⚠️ Please select a date first.";
    }
}

// Fetch all gym_members
$gym_members = mysqli_query($conn, "SELECT * FROM gym_members ORDER BY id ASC");

// Today's date
$today = date('Y-m-d');
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Admin - gym_attendance | GYMNASIUM</title>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap" rel="stylesheet">

<style>
body {
    margin: 0;
    padding: 0;
    font-family: 'Poppins', sans-serif;
    background: url('login.jpeg') no-repeat center center fixed;
    background-size: cover;
    color: white;
    backdrop-filter: brightness(60%);
}
.logout-btn {
    position: absolute;
    top: 20px;
    right: 30px;
    background: #222;
    color: #fff;
    border: none;
    border-radius: 20px;
    padding: 10px 20px;
    cursor: pointer;
}
.container {
    text-align: center;
    margin-top: 80px;
}
h1 {
    font-size: 2.5em;
    margin-bottom: 10px;
}
h2 {
    color: #bbb;
    margin-bottom: 30px;
}
.date-picker {
    background: rgba(0,0,0,0.6);
    border: 2px solid #00bfff;
    border-radius: 10px;
    padding: 10px 15px;
    color: white;
    font-size: 1em;
    outline: none;
    cursor: pointer;
}
.date-picker::-webkit-calendar-picker-indicator {
    filter: invert(1);
    cursor: pointer;
}
table {
    width: 80%;
    margin: auto;
    border-collapse: collapse;
    background: rgba(0,0,0,0.6);
    border-radius: 15px;
    overflow: hidden;
}
th, td {
    padding: 15px;
    text-align: center;
    border-bottom: 1px solid rgba(255,255,255,0.1);
}
th {
    background: rgba(0,0,0,0.8);
    color: #00bfff;
}
tr:hover {
    background: rgba(255,255,255,0.1);
}
.save-btn, .unmark-btn {
    margin-top: 20px;
    background-color: #00bfff;
    color: white;
    border: none;
    border-radius: 20px;
    padding: 10px 25px;
    cursor: pointer;
}
.save-btn:hover {
    background-color: #009acd;
}
.unmark-btn {
    background-color: #ff4d4d;
    margin-left: 10px;
}
.unmark-btn:hover {
    background-color: #e60000;
}
.message {
    margin-top: 15px;
    color: #00ff99;
}
</style>
</head>
<body>

<form action="logout.php" method="post">
  <button type="submit" class="logout-btn">Logout</button>
</form>

<div class="container">
  <h1>GYMNASIUM</h1>
  <h2>Admin - gym_attendance Management</h2>

  <form method="post">
    <label for="date">📅 Select Date:</label>
    <input type="date" name="date" class="date-picker" max="<?php echo $today; ?>" value="<?php echo isset($_POST['date']) ? $_POST['date'] : $today; ?>" required>
    <br><br>

    <table>
      <thead>
        <tr>
          <th>ID</th>
          <th>Name</th>
          <th>Department</th>
          <th>Present</th>
        </tr>
      </thead>
      <tbody>
        <?php 
        $date_filter = isset($_POST['date']) ? $_POST['date'] : $today;
        $gym_attendance_data = [];
        $existing_records = mysqli_query($conn, "SELECT member_id, status FROM gym_attendance WHERE date='$date_filter'");
        while ($a = mysqli_fetch_assoc($existing_records)) {
            $gym_attendance_data[$a['member_id']] = $a['status'];
        }

        while ($row = mysqli_fetch_assoc($gym_members)) { 
            $member_id = $row['id']; 
            $status = isset($gym_attendance_data[$member_id]) ? $gym_attendance_data[$member_id] : '';
        ?>
        <tr>
          <td><?php echo $member_id; ?></td>
          <td><?php echo htmlspecialchars($row['name']); ?></td>
          <td><?php echo htmlspecialchars($row['department']); ?></td>
          <td>
            <input type="checkbox" name="gym_attendance[<?php echo $member_id; ?>]" value="P" <?php if ($status == 'P') echo 'checked'; ?>> Present
          </td>
        </tr>
        <?php } ?>
      </tbody>
    </table>

    <button type="submit" name="save_gym_attendance" class="save-btn">Save gym_attendance</button>
    <button type="submit" name="unmark_all" class="unmark-btn">Unmark All</button>
  </form>
  <?php if ($message): ?>
    <p class="message"><?php echo $message; ?></p>
  <?php endif; ?>

<br><br>
<a href="gym_admin_dashboard.php" class="back-btn">⬅ Back to Dashboard</a>

<style>
.back-btn {
    background: #00bfff;
    color: white;
    padding: 12px 28px;
    border-radius: 10px;
    text-decoration: none;
    font-weight: bold;
    display: inline-block;
    transition: background 0.3s;
    box-shadow: 0 0 10px rgba(255,255,255,0.2);
}

.back-btn:hover {
    background: #0099cc;
}
</style>


</div>

</body>
</html>
