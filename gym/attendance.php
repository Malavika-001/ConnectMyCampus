<?php
session_start();
include('db_connect.php');

// Fetch only gym_members who have gym_attendance marked (present) with date
$gym_attendance = mysqli_query($conn, "
    SELECT 
        m.id AS member_id,
        m.name,
        a.date
    FROM gym_attendance a
    INNER JOIN gym_members m ON a.member_id = m.id
    ORDER BY a.date DESC
");
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>gym_attendance | GYMNASIUM</title>
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
.container {
    text-align: center;
    margin-top: 80px;
}
h1 { font-size: 2.5em; margin-bottom: 10px; }
h2 { color: #bbb; margin-bottom: 30px; }
table {
    width: 70%;
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
th { background: rgba(0,0,0,0.8); color: #00bfff; }
tr:hover { background: rgba(255,255,255,0.1); }
</style>
</head>
<body>

<div class="container">
  <h1>GYMNASIUM</h1>
  <h2>gym_attendance Record</h2>
<a class="back-btn" href="/campus/gym/gym_home_dashboard.php">← Back to Dashboard</a>
  <table>
    <thead>
      <tr>
        <th>Member ID</th>
        <th>Member Name</th>
        <th>Date</th>
      </tr>
    </thead>
    <tbody>
      <?php 
      if (mysqli_num_rows($gym_attendance) > 0) {
          while ($row = mysqli_fetch_assoc($gym_attendance)) { ?>
          <tr>
            <td><?php echo htmlspecialchars($row['member_id']); ?></td>
            <td><?php echo htmlspecialchars($row['name']); ?></td>
            <td><?php echo htmlspecialchars($row['date']); ?></td>
          </tr>
      <?php } 
      } else { ?>
          <tr><td colspan="3">No gym_attendance records found.</td></tr>
      <?php } ?>
    </tbody>
  </table>
</div>

</body>
</html>
