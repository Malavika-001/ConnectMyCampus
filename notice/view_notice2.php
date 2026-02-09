<?php
// /campus/notice/view_notice2.php
declare(strict_types=1);
if (session_status() === PHP_SESSION_NONE) session_start();

require_once __DIR__ . '/db.php';

// If not logged in, redirect to login for notice module
if (!isset($_SESSION['user_id'])) {
    header("Location: /campus/login.php?module=notice");
    exit;
}

$user_id = (int) $_SESSION['user_id'];

// Fetch user's category from users table (student/staff) — adapt column name if different
$stmt = $conn->prepare("SELECT category, college_id, username FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$res = $stmt->get_result();
$user = $res->fetch_assoc();
$stmt->close();

$user_category = strtolower(trim($user['category'] ?? ''));
if ($user_category !== 'student' && $user_category !== 'staff') {
    $user_category = ''; // fallback (only 'all' notice will apply)
}

// Build query: show notice targeted to 'all' OR to user's category
// Admins should still see all notice (optional). If you want admins to see all: check role
$is_admin = ($_SESSION['role'] ?? '') === 'admin';

if ($is_admin) {
    $q = "SELECT * FROM notice ORDER BY created_at DESC";
    $stmt = $conn->prepare($q);
} else {
    // show only 'all' OR user's category
    $q = "SELECT * FROM notice WHERE target = 'all'";
    $params = [];
    if ($user_category !== '') {
        $q .= " OR target = ?";
        $params[] = $user_category;
    }
    $q .= " ORDER BY created_at DESC";
    $stmt = $conn->prepare($q);
    if (!empty($params)) {
        // bind param (single string)
        $stmt->bind_param("s", $params[0]);
    }
}

$stmt->execute();
$result = $stmt->get_result();

// helper
function esc($s){ return htmlspecialchars($s ?? '', ENT_QUOTES, 'UTF-8'); }
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<title>notice — SSV College</title>
<meta name="viewport" content="width=device-width,initial-scale=1">
<style>
  body{font-family:Arial,Helvetica,sans-serif;background:#f4f8ff;margin:0}
  header{background:#0d47a1;color:#fff;padding:18px;text-align:center}
  .container{max-width:1100px;margin:26px auto;padding:18px}
  .grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(300px,1fr));gap:18px}
  .card{background:#fff;padding:16px;border-radius:8px;box-shadow:0 6px 20px rgba(0,0,0,0.06)}
  .title{color:#0d47a1;font-weight:700;margin-bottom:8px}
  .meta{font-size:13px;color:#555;margin-bottom:12px}
  .btn{display:inline-block;padding:8px 12px;background:#0d47a1;color:#fff;border-radius:6px;text-decoration:none;margin-right:8px}
  .notice-image{max-width:100%;border-radius:6px;margin-top:8px}
</style>
</head>
<body>
<header>
  SSV College Valayanchiragara — notice
</header>

<div class="container">
  <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:14px">
    <h2 style="margin:0">Latest notice</h2>
    <div>

      <a class="btn" href="/campus/logout.php">Logout</a>
    </div>
  </div>

  <div class="grid">
    <?php if ($result && $result->num_rows > 0): ?>
      <?php while ($row = $result->fetch_assoc()): ?>
        <div class="card">
          <div class="title"><?= esc($row['title']) ?></div>
          <div class="meta">📅 <?= date('d M Y, h:i A', strtotime($row['created_at'])) ?> — Target: <strong><?= esc($row['target']) ?></strong></div>
          <div><?= nl2br(esc($row['description'])) ?></div>

        <?php
$file = $row['file_path'];  // stored filename
$file_web = "uploads/" . basename($file);

$ext = strtolower(pathinfo($file_web, PATHINFO_EXTENSION));
$is_image = in_array($ext, ['jpg','jpeg','png','gif']);
?>

<?php if ($is_image): ?>
    <img src="<?= htmlspecialchars($file_web) ?>" class="notice-image" alt="Notice">
<?php endif; ?>

<a href="<?= htmlspecialchars($file_web) ?>" class="btn" target="_blank">View</a>
<a href="<?= htmlspecialchars($file_web) ?>" class="btn" download>Download</a>

        </div>
      <?php endwhile; ?>
    <?php else: ?>
      <p style="text-align:center;color:#555">No notice available.</p>
    <?php endif; ?>
  </div>
</div>
</body>
</html>
