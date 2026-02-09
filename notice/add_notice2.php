<?php
// /campus/notice/add_notice2.php
declare(strict_types=1);
if (session_status() === PHP_SESSION_NONE) session_start();

require_once __DIR__ . '/db.php'; // expects $conn (mysqli)

// Only admins allowed
if (!isset($_SESSION['username']) || ($_SESSION['role'] ?? '') !== 'admin') {
    header("Location: /campus/login.php?module=notice");
    exit;
}

$msg = "";

// Load saved targets (static)
$targets = ['all' => 'All', 'student' => 'Students only', 'staff' => 'Staff only'];

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $target = in_array($_POST['target'] ?? 'all', ['all','student','staff']) ? $_POST['target'] : 'all';
    $file_path = '';

    if ($title === '') {
        $msg = "Title is required.";
    } else {
        // File upload
        if (!empty($_FILES['file']['name']) && $_FILES['file']['error'] === UPLOAD_ERR_OK) {
            $upload_dir_fs = __DIR__ . '/uploads/';  // filesystem path
            $upload_dir_web = 'uploads/';            // path stored in DB (web relative from /campus/notice/)
            if (!is_dir($upload_dir_fs)) {
                mkdir($upload_dir_fs, 0775, true);
            }
            $orig = basename($_FILES['file']['name']);
            $ext = pathinfo($orig, PATHINFO_EXTENSION);
            $safe_name = time() . '_' . preg_replace('/[^A-Za-z0-9_\-\.]/', '_', pathinfo($orig, PATHINFO_FILENAME));
            $filename = $safe_name . ($ext ? '.' . $ext : '');
            $target_fs = $upload_dir_fs . $filename;
            if (move_uploaded_file($_FILES['file']['tmp_name'], $target_fs)) {
                // store web relative path (so view pages can use /campus/notice/<file_path>)
                $file_path = $upload_dir_web . $filename;
            } else {
                $msg = "File upload failed.";
            }
        }

        if ($msg === '') {
            $stmt = $conn->prepare("INSERT INTO notice (title, description, file_path, target, created_at) VALUES (?, ?, ?, ?, NOW())");
            $stmt->bind_param("ssss", $title, $description, $file_path, $target);
            if ($stmt->execute()) {
                $msg = "✅ Notice added successfully!";
            } else {
                $msg = "❌ DB Error: " . $conn->error;
            }
            $stmt->close();
        }
    }
}
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title>Add Notice | SSV College</title>
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <style>
    body{font-family:Arial,Helvetica,sans-serif;background:#f4f8ff;margin:0}
    header{background:#0d47a1;color:#fff;padding:18px;text-align:center}
    .container{max-width:760px;margin:28px auto;padding:18px;background:#fff;border-radius:8px;box-shadow:0 6px 20px rgba(0,0,0,0.06)}
    label{display:block;margin-top:12px;color:#0d47a1;font-weight:600}
    input[type=text], textarea, select {width:100%;padding:10px;border-radius:6px;border:1px solid #ccc;margin-top:6px}
    .btn{margin-top:14px;padding:10px 14px;border:0;border-radius:6px;background:#0d47a1;color:#fff;cursor:pointer}
    .message{padding:10px;border-radius:6px;margin-bottom:10px}
    .msg-success{background:#d1ffd8;color:#065f09}
    .msg-error{background:#ffd1d1;color:#a40000}
    .btn-cancel{display:inline-block;margin-left:10px;padding:10px 14px;background:#e0e0e0;color:#000;border-radius:6px;text-decoration:none}
  </style>
</head>
<body>
<header>
  <h1>SSV College Valayanchiragara</h1>
  <div style="font-size:14px">Add Notice</div>
</header>

<div class="container">
  <?php if ($msg !== ''): ?>
    <div class="message <?= strpos($msg,'success')!==false ? 'msg-success' : (strpos($msg,'✅')!==false ? 'msg-success' : 'msg-error') ?>">
      <?= htmlspecialchars($msg) ?>
    </div>
  <?php endif; ?>

  <form method="post" enctype="multipart/form-data">
    <label for="title">Title</label>
    <input id="title" name="title" type="text" required>

    <label for="description">Description</label>
    <textarea id="description" name="description" rows="5"></textarea>

    <label for="target">Target</label>
    <select id="target" name="target">
      <?php foreach ($targets as $k=>$v): ?>
        <option value="<?= htmlspecialchars($k) ?>"><?= htmlspecialchars($v) ?></option>
      <?php endforeach; ?>
    </select>

    <label for="file">Upload Poster/File (optional)</label>
    <input id="file" name="file" type="file" accept=".pdf,.jpg,.jpeg,.png,.gif,.docx">

    <button class="btn" type="submit">Add Notice</button>
    <a class="btn-cancel" href="/campus/notice/admin_notice_dashboard.php">Cancel</a>
  </form>
</div>
</body>
</html>
