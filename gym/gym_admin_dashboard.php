<?php
session_start();
// Example: Redirect if admin is not logged in
// if (!isset($_SESSION['admin_logged_in'])) {
//     header("Location: admin_login.php");
//     exit();
// }
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Admin Dashboard - GYMNASIUM</title>
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap" rel="stylesheet">
  <script src="https://kit.fontawesome.com/a076d05399.js" crossorigin="anonymous"></script>
  <style>
    body {
      margin: 0;
      padding: 0;
      font-family: 'Poppins', sans-serif;
      background: url('login.jpeg') no-repeat center center fixed;
      background-size: cover;
      color: white;
      height: 100vh;
      display: flex;
      flex-direction: column;
      align-items: center;
      justify-content: center;
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
      font-weight: 500;
      transition: 0.3s;
    }

    .logout-btn:hover {
      background: #444;
    }

    .container {
      text-align: center;
    }

    h1 {
      font-size: 2.5em;
      margin-bottom: 5px;
      letter-spacing: 1px;
    }

    h2 {
      color: #bbb;
      font-size: 1em;
      margin-bottom: 50px;
    }

    .modules {
      display: grid;
      grid-template-columns: repeat(2, 220px);
      gap: 30px;
      justify-content: center;
    }

    .module {
      background: rgba(0, 0, 0, 0.6);
      border-radius: 20px;
      padding: 40px 20px;
      text-align: center;
      box-shadow: 0 0 10px rgba(255, 255, 255, 0.1);
      transition: 0.3s;
      cursor: pointer;
    }

    .module:hover {
      transform: scale(1.05);
      background: rgba(0, 0, 0, 0.8);
      box-shadow: 0 0 20px rgba(255, 255, 255, 0.2);
    }

    .module i {
      font-size: 2em;
      color: #00bfff;
      margin-bottom: 15px;
    }

    .module p {
      font-size: 1.1em;
      margin: 0;
    }
  </style>
</head>
<body>

  <form action="logout.php" method="post">
    <button type="submit" class="logout-btn">Logout</button>
  </form>

  <div class="container">
    <h1>GYMNASIUM</h1>
    <h2>Admin Dashboard - SSV College Gym</h2>

    <div class="modules">
      <div class="module" onclick="window.location.href='admin_members.php'">
        <i class="fas fa-users-cog"></i>
        <p>Manage Members</p>
      </div>

      <div class="module" onclick="window.location.href='admin_attendance.php'">
        <i class="fas fa-calendar-check"></i>
        <p>Manage Attendance</p>
      </div>

      <div class="module" onclick="window.location.href='admin_workout.php'">
        <i class="fas fa-dumbbell"></i>
        <p>Manage Workout Plans</p>
      </div>

      <div class="module" onclick="window.location.href='admin_report.php'">
        <i class="fas fa-exclamation-triangle"></i>
        <p>Manage Reports</p>
      </div>
    </div>
  </div>

</body>
</html>
