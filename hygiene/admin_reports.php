<?php
session_start();
require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/auth_check_admin.php';

// ensure only admins
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit;
}

$message = "";

// Update status handler
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    $report_id = intval($_POST['report_id']);
    $status = $_POST['status'] ?? '';

    if ($report_id > 0 && in_array($status, ['Open', 'In Progress', 'Resolved'], true)) {
        try {
            $stmt = $pdo->prepare("UPDATE reports SET status = ?, status_updated_at = NOW() WHERE id = ?");
            $stmt->execute([$status, $report_id]);
            $message = "✅ Report ID {$report_id} updated to {$status}.";
        } catch (PDOException $e) {
            $message = "❌ Database Error: " . $e->getMessage();
        }
    } else {
        $message = "⚠️ Invalid report ID or status.";
    }
}

// Fetch all reports (with users table join)
try {
    $sql = "
        SELECT
            reports.*,
            users.college_id AS reporter_college_id
        FROM reports
        LEFT JOIN users
            ON (reports.user_id = users.id OR reports.user_id = users.college_id)
        ORDER BY reports.created_at DESC
    ";
    $stmt = $pdo->query($sql);
    $reports = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $reports = [];
    $message = "⚠️ Could not load reports: " . $e->getMessage();
}

// Fetch overdue reports (older than 10 days, not resolved)
try {
    $stmt = $pdo->query("
        SELECT id, location, status_updated_at 
        FROM reports 
        WHERE status != 'Resolved' 
        AND status_updated_at < (NOW() - INTERVAL 10 DAY)
        ORDER BY status_updated_at ASC
    ");
    $overdue_reports = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $overdue_reports = [];
}
$overdue_count = count($overdue_reports);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<title>Admin Reports | Connect My Campus</title>
<style>
    body { font-family: 'Segoe UI',sans-serif; background-color:#eaf6ff; color:#023047; margin:0; padding:0; }
    header { background:#0077b6; color:#fff; padding:12px 28px; display:flex; justify-content:space-between; align-items:center; position:relative; }
    header a{ color:#fff; text-decoration:none; margin-left:12px; font-weight:600;}
    .container { padding:28px 44px; }
    h2 { color:#023e8a; }
    .message { background:#caf0f8; padding:10px 16px; border-left:4px solid #0077b6; border-radius:6px; margin-bottom:18px; }
    table { width:100%; border-collapse:collapse; background:#fff; border-radius:8px; overflow:hidden; box-shadow:0 3px 10px rgba(0,0,0,0.08); }
    th { background:#0077b6; color:#fff; padding:10px 12px; text-align:left; }
    td { padding:12px; border-bottom:1px solid #e9f3fb; vertical-align:middle; }
    tr:hover { background:#f1f9ff; }
    .badge { padding:6px 12px; border-radius:20px; font-weight:700; display:inline-block; }
    .open { background:#f9c74f; color:#000; }
    .inprogress { background:#90be6d; color:#fff; }
    .resolved { background:#43aa8b; color:#fff; }
    .status-form select{ padding:6px;border-radius:6px; }
    .status-form button{ background:#0077b6;color:#fff;border:none;padding:6px 10px;border-radius:6px;cursor:pointer; }
    .status-form button:hover{ background:#023e8a; }
    footer{ text-align:center; color:#555; margin-top:18px; padding:18px 0; }

    /* 🔔 Notification styles */
    .notification {
        position: relative;
        cursor: pointer;
        font-size: 20px;
        margin-left: 15px;
    }
    .notification .badge {
        position: absolute;
        top: -6px;
        right: -8px;
        background: red;
        color: white;
        font-size: 12px;
        border-radius: 50%;
        padding: 4px 6px;
    }
    .dropdown {
        display: none;
        position: absolute;
        top: 45px;
        right: 10px;
        background: white;
        color: black;
        border-radius: 8px;
        box-shadow: 0 4px 10px rgba(0,0,0,0.2);
        width: 300px;
        z-index: 1000;
    }
    .dropdown.show {
        display: block;
    }
    .dropdown ul {
        list-style: none;
        margin: 0;
        padding: 0;
    }
    .dropdown li {
        padding: 10px 15px;
        border-bottom: 1px solid #eee;
    }
    .dropdown li:hover {
        background: #f1f1f1;
    }
    .dropdown .empty {
        padding: 15px;
        text-align: center;
        color: #777;
    }
</style>
<script>
    function toggleDropdown() {
        document.getElementById("notifDropdown").classList.toggle("show");
    }
    window.onclick = function(e) {
        if (!e.target.closest('.notification')) {
            document.getElementById("notifDropdown").classList.remove("show");
        }
    };
</script>
</head>
<body>

<header>
    <div><strong>Connect My Campus — Hygiene Reports</strong></div>
    <div style="display:flex;align-items:center;">
        <div class="notification" onclick="toggleDropdown()">
            🔔
            <?php if ($overdue_count > 0): ?>
                <span class="badge"><?= $overdue_count ?></span>
            <?php endif; ?>
            <div class="dropdown" id="notifDropdown">
                <?php if ($overdue_count > 0): ?>
                    <ul>
                        <?php foreach ($overdue_reports as $r): ?>
                            <li>
                                <strong>ID:</strong> <?= htmlspecialchars($r['id']) ?> — 
                                <em><?= htmlspecialchars($r['location']) ?></em><br>
                                <small>Last updated: <?= htmlspecialchars($r['status_updated_at']) ?></small>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php else: ?>
                    <div class="empty">🎉 No overdue reports</div>
                <?php endif; ?>
            </div>
        </div>
        <a href="/campus/hygiene/admin_dashboard.php">🏠 Dashboard</a>
        <a href="/campus/hygiene/logout.php">🚪 Logout</a>
    </div>
</header>

<div class="container">
    <h2>📋 Manage Hygiene Reports</h2>

    <?php if (!empty($message)): ?>
        <div class="message"><?= htmlspecialchars($message) ?></div>
    <?php endif; ?>

    <?php if (empty($reports)): ?>
        <p>No reports found.</p>
    <?php else: ?>
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Report</th>
                    <th>Location</th>
                    <th>Status</th>
                    <th>Reported By (College ID)</th>
                    <th>Created</th>
                    <th>Last Updated</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($reports as $r): 
                $collegeId = $r['reporter_college_id'] ?: $r['user_id'];
                $statusLower = strtolower($r['status'] ?? '');
                $statusClass = $statusLower === 'open' ? 'open' : ($statusLower === 'in progress' ? 'inprogress' : ($statusLower === 'resolved' ? 'resolved' : ''));
            ?>
                <tr>
                    <td><?= htmlspecialchars($r['id']) ?></td>
                    <td><?= nl2br(htmlspecialchars($r['report'])) ?></td>
                    <td><?= htmlspecialchars($r['location']) ?></td>
                    <td><span class="badge <?= $statusClass ?>"><?= htmlspecialchars($r['status']) ?></span></td>
                    <td><?= htmlspecialchars($collegeId) ?></td>
                    <td><?= htmlspecialchars($r['created_at']) ?></td>
                    <td><?= htmlspecialchars($r['status_updated_at']) ?></td>
                    <td>
                        <form class="status-form" method="POST" action="">
                            <input type="hidden" name="report_id" value="<?= htmlspecialchars($r['id']) ?>">
                            <select name="status" required>
                                <option value="Open" <?= ($r['status'] ?? '') === 'Open' ? 'selected' : '' ?>>Open</option>
                                <option value="In Progress" <?= ($r['status'] ?? '') === 'In Progress' ? 'selected' : '' ?>>In Progress</option>
                                <option value="Resolved" <?= ($r['status'] ?? '') === 'Resolved' ? 'selected' : '' ?>>Resolved</option>
                            </select>
                            <button type="submit" name="update_status">Update</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>

<footer>© <?= date('Y') ?> Connect My Campus</footer>

</body>
</html>
