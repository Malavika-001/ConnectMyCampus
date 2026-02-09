<?php
include("db_connect.php");
session_start();

$message = "";

/* ------------------------------------
   ADD NEW WORKOUT PLAN
------------------------------------ */
if (isset($_POST['add_workout'])) {
    $exercise = mysqli_real_escape_string($conn, $_POST['exercise']);
    $description = mysqli_real_escape_string($conn, $_POST['description']);

    if (!empty($exercise) && !empty($description)) {
        // Find next available day number (auto-increment)
        $result = mysqli_query($conn, "SELECT MAX(CAST(SUBSTRING(day, 4) AS UNSIGNED)) AS max_day FROM workout_plan");
        $row = mysqli_fetch_assoc($result);
        $next_day_number = ($row['max_day'] ?? 0) + 1;
        $next_day = "Day " . $next_day_number;

        mysqli_query($conn, "INSERT INTO workout_plan (day, exercise, description) VALUES ('$next_day', '$exercise', '$description')");
        $message = "✅ Workout plan '$next_day' added successfully!";
    } else {
        $message = "⚠️ Please fill all fields before adding!";
    }
}

/* ------------------------------------
   UPDATE EXISTING WORKOUT PLANS
------------------------------------ */
if (isset($_POST['update_workout'])) {
    foreach ($_POST['plan'] as $id => $data) {
        $exercise = mysqli_real_escape_string($conn, $data['exercise']);
        $description = mysqli_real_escape_string($conn, $data['description']);
        mysqli_query($conn, "UPDATE workout_plan 
                             SET exercise='$exercise', description='$description' 
                             WHERE id='$id'");
    }
    $message = "✅ Workout plans updated successfully!";
}

/* ------------------------------------
   DELETE WORKOUT PLAN
------------------------------------ */
if (isset($_POST['delete_workout'])) {
    $id = intval($_POST['delete_workout']);
    mysqli_query($conn, "DELETE FROM workout_plan WHERE id='$id'");

    // Reorder days after deletion (Day 1 → Day n)
    $all = mysqli_query($conn, "SELECT id FROM workout_plan ORDER BY id ASC");
    $day_num = 1;
    while ($row = mysqli_fetch_assoc($all)) {
        $new_day = "Day " . $day_num;
        mysqli_query($conn, "UPDATE workout_plan SET day='$new_day' WHERE id='{$row['id']}'");
        $day_num++;
    }

    $message = "🗑️ Workout plan deleted and reordered successfully!";
}

/* ------------------------------------
   FETCH ALL WORKOUT PLANS
------------------------------------ */
$plans = mysqli_query($conn, "SELECT * FROM workout_plan ORDER BY id ASC");
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Admin - Workout Plan | GYMNASIUM</title>
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
.container {
  text-align: center;
  margin-top: 60px;
}
h1 {
  font-size: 2.5em;
  margin-bottom: 10px;
}
h2 {
  color: #bbb;
  margin-bottom: 20px;
}
.message {
  color: #00ff99;
  margin-bottom: 20px;
  font-weight: 600;
}

/* Form Styles */
.add-form, table {
  background: rgba(0,0,0,0.7);
  border-radius: 15px;
  padding: 20px;
  margin: 20px auto;
  width: 80%;
  color: white;
}

input[type="text"], textarea {
  width: 90%;
  padding: 10px;
  margin: 5px 0;
  border-radius: 10px;
  border: none;
  background: rgba(255,255,255,0.1);
  color: white;
}

textarea {
  height: 70px;
  resize: vertical;
}

button {
  background: #00bfff;
  color: white;
  border: none;
  border-radius: 20px;
  padding: 8px 18px;
  cursor: pointer;
  font-weight: 600;
  margin: 5px;
}
button:hover { background: #009acd; }

.delete-btn {
  background: #e74c3c;
}
.delete-btn:hover {
  background: #c0392b;
}

/* Table Styles */
table {
  width: 90%;
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
</style>
</head>
<body>
<div class="container">
  <h1>GYMNASIUM</h1>
  <h2>Admin - Manage Workout Plans</h2>

  <?php if (!empty($message)) echo "<p class='message'>$message</p>"; ?>

  <!-- ✅ Add New Workout Plan -->
  <div class="add-form">
    <h3>➕ Add New Workout Plan</h3>
    <form method="post">
      <input type="text" name="exercise" placeholder="Exercise Part (e.g. Chest, Legs, Cardio)" required><br><br>
      <textarea name="description" placeholder="Enter exercise details or list" required></textarea><br>
      <button type="submit" name="add_workout">Add Workout</button>
    </form>
  </div>

  <!-- ✅ Edit Existing Workout Plans -->
  <form method="post">
    <table>
      <thead>
        <tr>
          <th>Day</th>
          <th>Exercise Part</th>
          <th>Description</th>
          <th>Action</th>
        </tr>
      </thead>
      <tbody>
        <?php while ($row = mysqli_fetch_assoc($plans)) { ?>
        <tr>
          <td><strong><?php echo htmlspecialchars($row['day']); ?></strong></td>
          <td><input type="text" name="plan[<?php echo $row['id']; ?>][exercise]" value="<?php echo htmlspecialchars($row['exercise']); ?>" required></td>
          <td><textarea name="plan[<?php echo $row['id']; ?>][description]" required><?php echo htmlspecialchars($row['description']); ?></textarea></td>
          <td><button type="submit" name="delete_workout" value="<?php echo $row['id']; ?>" class="delete-btn">Delete</button></td>
        </tr>
        <?php } ?>
      </tbody>
    </table>

    <button type="submit" name="update_workout">💾 Save All Changes</button>
  </form>

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
