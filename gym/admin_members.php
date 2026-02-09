<?php 
// admin_gym_members.php  (replace your old file with this)
include('db_connect.php');
session_start();

// Optional: restrict access to admins only (uncomment if you want)
// if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
//     header("Location: ../login.php");
//     exit;
//}

$errors = [];
$success = '';

// Handle Delete (POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_id'])) {
    $id = intval($_POST['delete_id']);
    $stmt = $conn->prepare("DELETE FROM gym_members WHERE id = ?");
    if ($stmt) {
        $stmt->bind_param("i", $id);
        if ($stmt->execute()) {
            $success = "Member deleted successfully.";
        } else {
            $errors[] = "Delete failed: " . $stmt->error;
        }
        $stmt->close();
    } else {
        $errors[] = "Delete prepare failed: " . $conn->error;
    }

    // redirect to avoid resubmission
    header("Location: admin_gym_members.php");
    exit();
}

// Handle Add / Update (POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_member'])) {
    $name = trim($_POST['name'] ?? '');
    $department = trim($_POST['department'] ?? '');
    $date_joined = trim($_POST['date_joined'] ?? '');
    $edit_id = isset($_POST['edit_id']) && $_POST['edit_id'] !== '' ? intval($_POST['edit_id']) : null;

    // basic validation
    if ($name === '') $errors[] = "Name is required.";
    if ($department === '') $errors[] = "Department is required.";
    if ($date_joined === '') $errors[] = "Date joined is required.";

    if (empty($errors)) {
        if ($edit_id === null) {
            // Insert
            $stmt = $conn->prepare("INSERT INTO gym_members (name, department, date_joined) VALUES (?, ?, ?)");
            if ($stmt) {
                $stmt->bind_param("sss", $name, $department, $date_joined);
                if ($stmt->execute()) {
                    $success = "Member added successfully.";
                } else {
                    $errors[] = "Insert failed: " . $stmt->error;
                }
                $stmt->close();
            } else {
                $errors[] = "Insert prepare failed: " . $conn->error;
            }
        } else {
            // Update
            $stmt = $conn->prepare("UPDATE gym_members SET name = ?, department = ?, date_joined = ? WHERE id = ?");
            if ($stmt) {
                $stmt->bind_param("sssi", $name, $department, $date_joined, $edit_id);
                if ($stmt->execute()) {
                    $success = "Member updated successfully.";
                } else {
                    $errors[] = "Update failed: " . $stmt->error;
                }
                $stmt->close();
            } else {
                $errors[] = "Update prepare failed: " . $conn->error;
            }
        }
    }

    // redirect to avoid resubmission
    header("Location: admin_gym_members.php");
    exit();
}

// Fetch all gym_members
$result = $conn->query("SELECT * FROM gym_members ORDER BY id ASC");
if ($result === false) {
    $errors[] = "Could not fetch members: " . $conn->error;
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Admin - Gym Members</title>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap" rel="stylesheet">
<style>
body {
    margin: 0;
    font-family: 'Poppins', sans-serif;
    background: url('login.jpeg') no-repeat center center fixed;
    background-size: cover;
    color: white;
    backdrop-filter: brightness(60%);
}

.container {
    text-align: center;
    margin-top: 70px;
}

h1 {
    font-size: 2.5em;
    margin-bottom: 10px;
}

h2 {
    color: #bbb;
    margin-bottom: 30px;
}

.form-wrap {
    background: rgba(0,0,0,0.7);
    width: 60%;
    margin: 0 auto 40px auto;
    padding: 20px;
    border-radius: 10px;
}

.form-wrap input {
    padding: 10px;
    margin: 10px;
    border: none;
    border-radius: 5px;
    width: 25%;
}

.form-wrap button {
    background: #00bfff;
    color: white;
    border: none;
    border-radius: 10px;
    padding: 10px 25px;
    cursor: pointer;
    font-weight: bold;
    transition: 0.3s;
}
.form-wrap button:hover {
    background: #0090cc;
}

table {
    width: 80%;
    margin: 0 auto;
    border-collapse: collapse;
    background: rgba(0,0,0,0.6);
    border-radius: 10px;
    overflow: hidden;
    box-shadow: 0 0 15px rgba(255,255,255,0.1);
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

.action-btn {
    background: #00bfff;
    color: white;
    border: none;
    border-radius: 8px;
    padding: 5px 10px;
    cursor: pointer;
    margin: 2px;
    transition: 0.3s;
    text-decoration: none;
    display: inline-block;
}

.action-btn:hover {
    background: #0099cc;
}

.delete-btn {
    background: #ff4444;
}
.delete-btn:hover {
    background: #cc0000;
}

.message {
    width: 60%;
    margin: 10px auto;
    padding: 10px 14px;
    border-radius: 8px;
    font-weight: 600;
}
.success { background: rgba(0,200,120,0.15); color: #b7ffcf; border: 1px solid rgba(0,200,120,0.25);}
.error { background: rgba(200,0,0,0.12); color: #ffd6d6; border: 1px solid rgba(200,0,0,0.18);}
</style>
</head>
<body>

<div class="container">
    <h1>GYMNASIUM</h1>
    <h2>Admin - Manage Members</h2>

    <?php if (!empty($success)): ?>
        <div class="message success"><?php echo htmlspecialchars($success); ?></div>
    <?php endif; ?>

    <?php if (!empty($errors)): ?>
        <div class="message error">
            <?php foreach ($errors as $e) echo htmlspecialchars($e) . "<br>"; ?>
        </div>
    <?php endif; ?>

    <!-- Add/Edit Form -->
    <div class="form-wrap">
        <form method="post" id="memberForm">
            <input type="hidden" name="edit_id" id="edit_id">
            <input type="text" name="name" id="name" placeholder="Member Name" required>
            <input type="text" name="department" id="department" placeholder="Department" required>
            <input type="date" name="date_joined" id="date_joined" required>
            <button type="submit" name="save_member" id="saveBtn">Save Member</button>
        </form>
    </div>

    <!-- gym_members Table -->
    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Name</th>
                <th>Department</th>
                <th>Date Joined</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
        <?php if ($result && $result->num_rows > 0): ?>
            <?php while ($row = $result->fetch_assoc()): ?>
            <tr>
                <td><?php echo $row['id']; ?></td>
                <td><?php echo htmlspecialchars($row['name']); ?></td>
                <td><?php echo htmlspecialchars($row['department']); ?></td>
                <td><?php echo htmlspecialchars($row['date_joined']); ?></td>
                <td>
                    <button class="action-btn" 
                        onclick="editMember(<?php echo (int)$row['id']; ?>, 
                                           '<?php echo addslashes(htmlspecialchars($row['name'])); ?>', 
                                           '<?php echo addslashes(htmlspecialchars($row['department'])); ?>', 
                                           '<?php echo addslashes(htmlspecialchars($row['date_joined'])); ?>')">Edit</button>

                    <!-- Delete is a POST form for reliability -->
                    <form method="post" style="display:inline;" onsubmit="return confirm('Are you sure you want to delete this member?');">
                        <input type="hidden" name="delete_id" value="<?php echo (int)$row['id']; ?>">
                        <button type="submit" class="action-btn delete-btn">Delete</button>
                    </form>
                </td>
            </tr>
            <?php endwhile; ?>
        <?php else: ?>
            <tr><td colspan="5">No members found.</td></tr>
        <?php endif; ?>
        </tbody>
    </table>
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

<script>
function editMember(id, name, department, date) {
    document.getElementById('edit_id').value = id;
    document.getElementById('name').value = name;
    document.getElementById('department').value = department;
    document.getElementById('date_joined').value = date;
    document.getElementById('saveBtn').innerText = "Update Member";
    window.scrollTo({ top: 0, behavior: 'smooth' });
}
</script>

</body>
</html>
