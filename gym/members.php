<?php
// Database connection
$host = "localhost";
$user = "root";
$password = "Root1234";
$dbname = "campus";

$conn = new mysqli($host, $user, $password, $dbname);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// ✅ Use correct column name (id instead of member_id)
$result = $conn->query("SELECT * FROM gym_members ORDER BY id DESC");
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>gym_members | GYMNASIUM</title>
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap" rel="stylesheet">
  <style>
    body {
        margin: 0;
        padding: 0;
        background: url('login.jpeg') no-repeat center center fixed;
        background-size: cover;
        color: white;
        font-family: 'Poppins', sans-serif;
        text-align: center;
        backdrop-filter: brightness(70%);
    }

    table {
      width: 80%;
      margin: 30px auto;
      border-collapse: collapse;
      background: rgba(255, 255, 255, 0.05);
      border-radius: 10px;
      overflow: hidden;
    }
    th, td {
      padding: 12px;
      border-bottom: 1px solid rgba(255, 255, 255, 0.1);
    }
    th { color: #00bfff; }
    tr:hover { background: rgba(255, 255, 255, 0.1); }
  </style>
</head>
<body>
  <h1>GYMNASIUM - gym_members</h1>
  <h2>Active gym_members List</h2>
<a class="back-btn" href="/campus/gym/gym_home_dashboard.php">← Back to Dashboard</a>

  <table>
    <tr>
      <th>ID</th>
      <th>Name</th>
      <th>Department</th>
      <th>Date Joined</th>
    </tr>
    <?php while ($row = $result->fetch_assoc()) { ?>
      <tr>
        <!-- ✅ Use correct key names -->
        <td><?php echo $row['id']; ?></td>
        <td><?php echo htmlspecialchars($row['name']); ?></td>
        <td><?php echo htmlspecialchars($row['department']); ?></td>
        <td><?php echo htmlspecialchars($row['date_joined']); ?></td>
      </tr>
    <?php } ?>
  </table>
</body>
</html>
