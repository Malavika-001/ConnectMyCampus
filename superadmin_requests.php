<?php
session_start();
require_once "db.php";

// Ensure only super admin can access
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'superadmin') {
    header("Location: login.php");
    exit;
}

// Handle approve/reject actions
if (isset($_POST['action']) && isset($_POST['user_id'])) {
    $action = $_POST['action']; // 'approve' or 'reject'
    $user_id = intval($_POST['user_id']);

    if ($action === 'approve') {
        $stmt = $conn->prepare("UPDATE users SET role='admin', admin_request_status='approved' WHERE id=?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $stmt->close();
    } elseif ($action === 'reject') {
        $stmt = $conn->prepare("UPDATE users SET role='user', admin_request_status='rejected' WHERE id=?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $stmt->close();
    }
}

// Fetch all pending admin requests
$pendingStmt = $conn->prepare("SELECT id, name, college_id, username, created_at FROM users WHERE admin_request_status='pending' ORDER BY created_at ASC");
$pendingStmt->execute();
$pendingRequests = $pendingStmt->get_result();
$pendingStmt->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Super Admin | Pending Admin Requests</title>
<style>
body { font-family:'Segoe UI',sans-serif; background:#eef6fc; margin:0; padding:0; }
header { background:#0077b6; color:white; padding:15px 30px; display:flex; justify-content:space-between; align-items:center; }
header a { color:white; margin-left:20px; text-decoration:none; }
.container { max-width:900px; margin:30px auto; padding:0 15px; }
table { width:100%; border-collapse:collapse; background:white; border-radius:8px; overflow:hidden; box-shadow:0 2px 8px rgba(0,0,0,0.1); }
th, td { padding:12px; text-align:center; border-bottom:1px solid #ddd; }
th { background:#90e0ef; }
tr:hover { background:#f8f9fa; }
button { background:#0077b6; color:white; border:none; padding:6px 10px; border-radius:6px; cursor:pointer; margin:2px; }
button.approve { background:#28a745; }
button.reject { background:#dc3545; }
button:hover { opacity:0.9; }
</style>
</head>
<body>

<header>
    <div><strong>Super Admin Panel</strong> — Pending Admin Requests</div>
    <div>
        <a href="superadmin_dashboard.php">🏠 Dashboard</a>
        <a href="logout.php">🚪 Logout</a>
    </div>
</header>

<div class="container">
<h2>📬 Pending Admin Requests</h2>

<?php if($pendingRequests->num_rows > 0): ?>
<table>
<tr>
<th>ID</th>
<th>Name</th>
<th>College ID</th>
<th>Username</th>
<th>Requested On</th>
<th>Actions</th>
</tr>
<?php while($row = $pendingRequests->fetch_assoc()): ?>
<tr>
<td><?= $row['id'] ?></td>
<td><?= htmlspecialchars($row['name']) ?></td>
<td><?= htmlspecialchars($row['college_id']) ?></td>
<td><?= htmlspecialchars($row['username']) ?></td>
<td><?= $row['created_at'] ?></td>
<td>
<form method="post" style="display:inline;">
<input type="hidden" name="user_id" value="<?= $row['id'] ?>">
<button type="submit" name="action" value="approve" class="approve">✅ Approve</button>
<button type="submit" name="action" value="reject" class="reject">❌ Reject</button>
</form>
</td>
</tr>
<?php endwhile; ?>
</table>
<?php else: ?>
<p>No pending admin requests. 🎉</p>
<?php endif; ?>

</div>
</body>
</html>
