<?php
session_start();
include('auth_check_superadmin.php');

// Database connection
$host = "localhost";
$user = "root";
$pass = "Root1234";
$db = "campus";
$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) die("Connection failed: " . $conn->connect_error);

// Restrict access to super admin only
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'superadmin') {
    header("Location: login.php");
    exit;
}

$message = "";

// ---------- HANDLE ACTIONS ----------

// Approve admin request
if (isset($_POST['approve'])) {
    $id = intval($_POST['user_id']);
    $conn->query("UPDATE users SET role='admin', admin_request_status='approved' WHERE id=$id");
    $message = "✅ Admin approved successfully.";
}

// Reject admin request
if (isset($_POST['reject'])) {
    $id = intval($_POST['user_id']);
    $conn->query("UPDATE users SET admin_request_status='rejected' WHERE id=$id");
    $message = "❌ Admin request rejected.";
}

// Delete user
if (isset($_POST['delete_user'])) {
    $id = intval($_POST['user_id']);
    $conn->query("DELETE FROM users WHERE id=$id");
    $message = "🗑️ User deleted successfully.";
}

// Reset password (for user or admin)
if (isset($_POST['reset_password'])) {
    $id = intval($_POST['user_id']);
    $new_pass = password_hash($_POST['new_password'], PASSWORD_DEFAULT);
    $conn->query("UPDATE users SET password='$new_pass' WHERE id=$id");
    $message = "🔑 Password reset successfully.";
}

// Remove admin privileges
if (isset($_POST['remove_admin'])) {
    $id = intval($_POST['user_id']);
    $conn->query("UPDATE users SET role='user', admin_request_status=NULL WHERE id=$id");
    $message = "⚙️ Admin privileges removed.";
}

// ---------- FETCH DATA ----------
$pending = $conn->query("SELECT * FROM users WHERE role='admin_request' AND admin_request_status='pending'");
$admins  = $conn->query("SELECT * FROM users WHERE role='admin'");
$users   = $conn->query("SELECT * FROM users WHERE role='user'");

$total_users = $conn->query("SELECT COUNT(*) AS c FROM users WHERE role='user'")->fetch_assoc()['c'];
$total_admins = $conn->query("SELECT COUNT(*) AS c FROM users WHERE role='admin'")->fetch_assoc()['c'];
$total_pending = $conn->query("SELECT COUNT(*) AS c FROM users WHERE role='admin_request' AND admin_request_status='pending'")->fetch_assoc()['c'];

// ---------- POPUP ALERT ----------
if ($total_pending > 0) {
    echo "<script>alert('🔔 There are $total_pending new admin requests pending approval!');</script>";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Super Admin Dashboard</title>
<style>
body { font-family: Arial, sans-serif; background: #f5f7fb; margin: 0; }
header { background: #007bff; color: #fff; padding: 15px 30px; font-size: 20px; }
.container { width: 95%; margin: 30px auto; }
h2 { color: #007bff; border-bottom: 2px solid #007bff; padding-bottom: 5px; }
table { width: 100%; border-collapse: collapse; margin: 20px 0; background: #fff; box-shadow: 0 2px 8px rgba(0,0,0,0.1); }
th, td { padding: 10px; text-align: center; border-bottom: 1px solid #ddd; }
th { background: #007bff; color: #fff; }
button { padding: 6px 10px; border: none; border-radius: 5px; color: white; cursor: pointer; }
.approve { background-color: #28a745; }
.reject { background-color: #dc3545; }
.delete { background-color: #dc3545; }
.reset { background-color: #17a2b8; }
.remove { background-color: #6c757d; }
.card { display: inline-block; background: white; padding: 15px; border-radius: 10px; margin: 10px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); width: 220px; text-align: center; }
.card h3 { color: #007bff; margin: 0; }
.message { color: green; font-weight: bold; text-align: center; margin: 15px 0; }
nav { text-align: right; margin-top: -45px; padding-right: 30px; }
nav a { color: white; text-decoration: none; margin-left: 15px; font-weight: bold; }
</style>
</head>
<body>
<header>
  Super Admin Dashboard
  <nav>
    <a href="logout.php">Logout</a>
  </nav>
</header>

<div class="container">
<?php if ($message): ?><p class="message"><?= htmlspecialchars($message) ?></p><?php endif; ?>

<!-- STATS -->
<div class="card"><h3>👤 Total Users</h3><p><?= $total_users ?></p></div>
<div class="card"><h3>🛡️ Total Admins</h3><p><?= $total_admins ?></p></div>
<div class="card"><h3>📩 Pending Requests</h3><p><?= $total_pending ?></p></div>

<!-- PENDING ADMIN REQUESTS -->
<h2>📩 Pending Admin Requests</h2>
<?php if ($pending->num_rows > 0): ?>
<table>
<tr><th>Name</th><th>Username</th><th>Department</th><th>Year</th><th>Phone</th><th>Actions</th></tr>
<?php while ($row = $pending->fetch_assoc()): ?>
<tr>
    <td><?= htmlspecialchars($row['name']) ?></td>
    <td><?= htmlspecialchars($row['username']) ?></td>
    <td><?= htmlspecialchars($row['department']) ?></td>
    <td><?= htmlspecialchars($row['year']) ?></td>
    <td><?= htmlspecialchars($row['phone']) ?></td>
    <td>
        <form method="POST" style="display:inline;">
            <input type="hidden" name="user_id" value="<?= $row['id'] ?>">
            <button class="approve" name="approve">Approve</button>
        </form>
        <form method="POST" style="display:inline;">
            <input type="hidden" name="user_id" value="<?= $row['id'] ?>">
            <button class="reject" name="reject">Reject</button>
        </form>
    </td>
</tr>
<?php endwhile; ?>
</table>
<?php else: ?><p>No pending requests.</p><?php endif; ?>

<!-- MANAGE ADMINS -->
<h2>🛡️ Manage Admins</h2>
<table>
<tr><th>Name</th><th>Username</th><th>Department</th><th>Phone</th><th>Actions</th></tr>
<?php while ($a = $admins->fetch_assoc()): ?>
<tr>
    <td><?= htmlspecialchars($a['name']) ?></td>
    <td><?= htmlspecialchars($a['username']) ?></td>
    <td><?= htmlspecialchars($a['department']) ?></td>
    <td><?= htmlspecialchars($a['phone']) ?></td>
    <td>
        <form method="POST" style="display:inline;">
            <input type="hidden" name="user_id" value="<?= $a['id'] ?>">
            <button class="remove" name="remove_admin">Remove Admin</button>
        </form>
        <form method="POST" style="display:inline;">
            <input type="hidden" name="user_id" value="<?= $a['id'] ?>">
            <input type="password" name="new_password" placeholder="New Password" required>
            <button class="reset" name="reset_password">Reset Password</button>
        </form>
    </td>
</tr>
<?php endwhile; ?>
</table>

<!-- MANAGE USERS -->
<h2>👥 Manage Users</h2>
<table>
<tr><th>Name</th><th>Username</th><th>Department</th><th>Year</th><th>Phone</th><th>Actions</th></tr>
<?php while ($u = $users->fetch_assoc()): ?>
<tr>
    <td><?= htmlspecialchars($u['name']) ?></td>
    <td><?= htmlspecialchars($u['username']) ?></td>
    <td><?= htmlspecialchars($u['department']) ?></td>
    <td><?= htmlspecialchars($u['year']) ?></td>
    <td><?= htmlspecialchars($u['phone']) ?></td>
    <td>
        <form method="POST" style="display:inline;">
            <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
            <button class="delete" name="delete_user">Delete</button>
        </form>
        <form method="POST" style="display:inline;">
            <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
            <input type="password" name="new_password" placeholder="New Password" required>
            <button class="reset" name="reset_password">Reset Password</button>
        </form>
    </td>
</tr>
<?php endwhile; ?>
</table>
</div>
</body>
</html>
