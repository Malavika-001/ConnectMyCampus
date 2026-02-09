<?php
session_start();
require_once "auth_check_superadmin.php";

/* ---------- DATABASE CONNECTIONS ---------- */

// Hygiene / Campus DB
$connCampus = new mysqli("localhost", "root", "Root1234", "campus");
if ($connCampus->connect_error) {
    die("Campus DB connection failed: " . $connCampus->connect_error);
}

// Gym / SmartCampus DB
$connGym = new mysqli("localhost", "root", "Root1234", "smartcampus");
if ($connGym->connect_error) {
    die("Gym DB connection failed: " . $connGym->connect_error);
}

// Compatibility alias (for any shared logic)
$conn = $connCampus;

/* ---------- CAMPUS (HYGIENE) ADMIN REQUESTS ---------- */

$campus_pending_sql = "
    SELECT id, name, username, admin_request_status, created_at
    FROM users
    WHERE admin_request_status = 'pending'
    ORDER BY created_at DESC
";
$campus_pending = $connCampus->query($campus_pending_sql);

// Approve / Reject Hygiene Admins
if (isset($_POST['approve_campus_admin']) || isset($_POST['reject_campus_admin'])) {
    $id = $_POST['id'];
    $new_status = isset($_POST['approve_campus_admin']) ? 'approved' : 'rejected';

    $stmt = $connCampus->prepare("UPDATE users SET admin_request_status = ? WHERE id = ?");
    $stmt->bind_param("si", $new_status, $id);
    $stmt->execute();
    $stmt->close();

    echo "<script>alert('Campus admin request has been {$new_status}.'); window.location.href='superadmin.php';</script>";
    exit;
}

/* ---------- GYM ADMIN REQUESTS (smartcampus DB) ---------- */

// 🧩 Updated: Removed `created_at` column (since it doesn’t exist)
$gym_pending_sql = "
    SELECT adm_no AS id, name, username, admin_request_status
    FROM users
    WHERE admin_request_status = 'pending'
    ORDER BY name ASC
";
$gym_pending = $connGym->query($gym_pending_sql);

// Approve / Reject Gym Admins
if (isset($_POST['approve_gym_admin']) || isset($_POST['reject_gym_admin'])) {
    $adm_no = $_POST['adm_no'];
    $new_status = isset($_POST['approve_gym_admin']) ? 'approved' : 'rejected';

    $stmt = $connGym->prepare("UPDATE users SET admin_request_status = ? WHERE adm_no = ?");
    $stmt->bind_param("ss", $new_status, $adm_no);
    $stmt->execute();
    $stmt->close();

    echo "<script>alert('Gym admin request has been {$new_status}.'); window.location.href='superadmin.php';</script>";
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Super Admin Dashboard | Connect My Campus</title>
<style>
body {
    font-family: 'Segoe UI', sans-serif;
    background: #0b132b;
    color: #f1f1f1;
    margin: 0;
}
header {
    background: #1c2541;
    color: #fff;
    padding: 15px 25px;
    display: flex;
    justify-content: space-between;
    align-items: center;
}
header a {
    color: #fff;
    text-decoration: none;
    margin-left: 20px;
}
.container {
    padding: 30px;
}
h2 {
    color: #00bfff;
    margin-bottom: 10px;
    border-bottom: 2px solid #1c2541;
    padding-bottom: 5px;
}
table {
    width: 100%;
    border-collapse: collapse;
    background: #1c2541;
    color: #fff;
    border-radius: 8px;
    overflow: hidden;
    margin-bottom: 40px;
}
th, td {
    padding: 12px;
    text-align: center;
    border-bottom: 1px solid #3a506b;
}
th {
    background: #3a506b;
}
tr:hover {
    background: #5bc0be;
    color: #000;
}
form.inline {
    display: inline;
}
input[type="submit"] {
    background: #5bc0be;
    color: #000;
    border: none;
    border-radius: 6px;
    padding: 6px 12px;
    cursor: pointer;
    font-weight: bold;
    margin: 3px;
}
input[type="submit"]:hover {
    background: #3a506b;
    color: #fff;
}
.logout-btn {
    background: #ff4d4d;
    border: none;
    padding: 8px 16px;
    color: white;
    border-radius: 6px;
    cursor: pointer;
}
.logout-btn:hover {
    background: #ff3333;
}
</style>
</head>
<body>

<header>
    <div><strong>Super Admin Dashboard</strong> — Manage Admin Requests</div>
    <div>
        <a href="logout.php" class="logout-btn">🚪 Logout</a>
    </div>
</header>

<div class="container">

<!-- 🌿 HYGIENE ADMIN REQUESTS -->
<h2>🧼 Hygiene Admin Requests</h2>
<table>
<tr>
    <th>ID</th>
    <th>Name</th>
    <th>Username</th>
    <th>Status</th>
    <th>Actions</th>
</tr>
<?php if ($campus_pending && $campus_pending->num_rows > 0): ?>
    <?php while ($row = $campus_pending->fetch_assoc()): ?>
    <tr>
        <td><?= htmlspecialchars($row['id']) ?></td>
        <td><?= htmlspecialchars($row['name']) ?></td>
        <td><?= htmlspecialchars($row['username']) ?></td>
        <td><?= htmlspecialchars($row['admin_request_status']) ?></td>
        <td>
            <form method="post" class="inline">
                <input type="hidden" name="id" value="<?= htmlspecialchars($row['id']) ?>">
                <input type="submit" name="approve_campus_admin" value="✅ Approve">
                <input type="submit" name="reject_campus_admin" value="❌ Reject">
            </form>
        </td>
    </tr>
    <?php endwhile; ?>
<?php else: ?>
    <tr><td colspan="5">No pending requests found.</td></tr>
<?php endif; ?>
</table>

<!-- 🏋️ GYM ADMIN REQUESTS -->
<h2>🏋️ Gym Admin Requests</h2>
<table>
<tr>
    <th>Adm No</th>
    <th>Name</th>
    <th>Username</th>
    <th>Status</th>
    <th>Actions</th>
</tr>
<?php if ($gym_pending && $gym_pending->num_rows > 0): ?>
    <?php while ($row = $gym_pending->fetch_assoc()): ?>
    <tr>
        <td><?= htmlspecialchars($row['id']) ?></td>
        <td><?= htmlspecialchars($row['name']) ?></td>
        <td><?= htmlspecialchars($row['username']) ?></td>
        <td><?= htmlspecialchars($row['admin_request_status']) ?></td>
        <td>
            <form method="post" class="inline">
                <input type="hidden" name="adm_no" value="<?= htmlspecialchars($row['id']) ?>">
                <input type="submit" name="approve_gym_admin" value="✅ Approve">
                <input type="submit" name="reject_gym_admin" value="❌ Reject">
            </form>
        </td>
    </tr>
    <?php endwhile; ?>
<?php else: ?>
    <tr><td colspan="5">No pending requests found.</td></tr>
<?php endif; ?>
</table>

</div>
</body>
</html>
