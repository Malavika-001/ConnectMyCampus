<?php
// campus/notice/edit_notice2.php
session_start();
include __DIR__ . '/db.php'; // expects mysqli $conn

// Access control — admin only
if (!isset($_SESSION['username']) || ($_SESSION['role'] ?? '') !== 'admin') {
    header("Location: ../login.php?module=notice");
    exit;
}

// Validate ID
if (!isset($_GET['id']) || !ctype_digit($_GET['id'])) {
    header("Location: admin_notice_dashboard.php");
    exit;
}

$id = intval($_GET['id']);
$msg = '';

// Fetch existing notice
$stmt = $conn->prepare("SELECT * FROM notice WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$res = $stmt->get_result();
$notice = $res->fetch_assoc();
$stmt->close();

if (!$notice) {
    die("Notice not found.");
}

// Handle POST (update)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $target_role = $_POST['target_role'] ?? 'all';

    // Normalize target values to match ENUM definitions
    $allowedTargets = ['all', 'student', 'staff'];
    $target_role = in_array($target_role, $allowedTargets, true) ? $target_role : 'all';

    // preserve previous file unless replaced
    $file_path = $notice['file_path'] ?? '';

    // File upload handling (optional)
    if (!empty($_FILES['file']['name']) && isset($_FILES['file']) && $_FILES['file']['error'] === UPLOAD_ERR_OK) {
        $upload_dir = __DIR__ . '/uploads/';
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }

        // sanitize original name and prepend timestamp
        $orig = basename($_FILES['file']['name']);
        $ext = pathinfo($orig, PATHINFO_EXTENSION);
        $safe_name = preg_replace('/[^a-zA-Z0-9_\-\.]/', '_', pathinfo($orig, PATHINFO_FILENAME));
        $filename = time() . '_' . $safe_name . ($ext ? '.' . $ext : '');
        $target_full = $upload_dir . $filename;

        if (move_uploaded_file($_FILES['file']['tmp_name'], $target_full)) {
            // store relative path so web can load: use 'uploads/filename'
            $file_path = 'uploads/' . $filename;
            // optionally remove old file if it exists and is in uploads folder and not same as new
            if (!empty($notice['file_path']) && strpos($notice['file_path'], 'uploads/') === 0 && $notice['file_path'] !== $file_path) {
                $old = __DIR__ . '/' . $notice['file_path'];
                if (file_exists($old)) @unlink($old);
            }
        } else {
            $msg = "❌ Failed to move uploaded file.";
        }
    }

    if ($msg === '') {
        // Update both target_role and target columns to keep them consistent
        $upd = $conn->prepare(
            "UPDATE notice 
             SET title = ?, description = ?, file_path = ?, target_role = ?, target = ?
             WHERE id = ?"
        );
        if ($upd === false) {
            $msg = "Prepare failed: " . htmlspecialchars($conn->error);
        } else {
            $upd->bind_param("sssssi", $title, $description, $file_path, $target_role, $target_role, $id);
            if ($upd->execute()) {
                $msg = "✅ Notice updated successfully.";
                // Refresh $notice with new values
                $stmt2 = $conn->prepare("SELECT * FROM notice WHERE id = ?");
                $stmt2->bind_param("i", $id);
                $stmt2->execute();
                $notice = $stmt2->get_result()->fetch_assoc();
                $stmt2->close();
            } else {
                $msg = "❌ Update failed: " . htmlspecialchars($upd->error);
            }
            $upd->close();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<title>Edit Notice — SSV College</title>
<meta name="viewport" content="width=device-width,initial-scale=1">
<style>
    body { font-family: "Segoe UI", Tahoma, sans-serif; background:#f4f8ff; margin:0; color:#222; }
    header { background:#004aad; color:#fff; padding:18px; text-align:center; box-shadow:0 2px 6px rgba(0,0,0,0.08); }
    header .sub { font-size:13px; opacity:.9; display:block; margin-top:6px; }
    .container { max-width:800px; margin:28px auto; padding:18px; }
    .card { background:#fff; border-radius:10px; padding:20px; box-shadow:0 4px 16px rgba(0,0,0,0.06); }
    label { display:block; margin-top:10px; color:#004aad; font-weight:600; }
    input[type="text"], textarea, select { width:100%; padding:10px; border:1px solid #d8e9ff; border-radius:8px; margin-top:6px; font-size:15px; }
    textarea { min-height:140px; resize:vertical; }
    .btn-row { margin-top:16px; display:flex; gap:10px; }
    .btn { padding:10px 16px; border-radius:8px; border:none; cursor:pointer; font-weight:700; }
    .btn-save { background:#0077b6; color:#fff; }
    .btn-cancel { background:#e9eef9; color:#004aad; text-decoration:none; display:inline-flex; align-items:center; justify-content:center; }
    .msg { margin:12px 0; padding:10px 14px; border-radius:8px; }
    .msg.success { background:#dff7e0; color:#055000; }
    .msg.error { background:#ffe0e0; color:#8b0000; }
    .current-file { margin-top:12px; font-size:14px; color:#333; }
    .preview-img { max-width:100%; margin-top:10px; border-radius:8px; }
    .bottom-links { margin-top:18px; display:flex; justify-content:flex-end; gap:8px; }
    @media(max-width:600px){ .btn-row{flex-direction:column;} .bottom-links{justify-content:center;} }
</style>
</head>
<body>

<header>
    SSV College Valayanchiragara
    <span class="sub">Edit Notice</span>
</header>

<div class="container">
    <div class="card">
        <?php if ($msg): ?>
            <div class="msg <?= strpos($msg,'✅')===0 ? 'success' : 'error' ?>">
                <?= htmlspecialchars($msg) ?>
            </div>
        <?php endif; ?>

        <form method="post" enctype="multipart/form-data">
            <label for="title">Notice Title</label>
            <input id="title" name="title" type="text" value="<?= htmlspecialchars($notice['title'] ?? '') ?>" required>

            <label for="description">Description</label>
            <textarea id="description" name="description"><?= htmlspecialchars($notice['description'] ?? '') ?></textarea>

            <label for="file">Replace File / Poster (optional)</label>
            <input id="file" name="file" type="file" accept=".pdf,.jpg,.jpeg,.png,.docx">

            <?php if (!empty($notice['file_path'])): ?>
                <div class="current-file">
                    Current file: <?= htmlspecialchars($notice['file_path']) ?>
                    <?php
                        $fp = $notice['file_path'];
                        // If image, show preview
                        if (preg_match('/\.(jpe?g|png|gif)$/i', $fp)) {
                            $imgUrl = htmlspecialchars($fp);
                            echo "<div><img class='preview-img' src=\"{$imgUrl}\" alt=\"notice image\"></div>";
                        }
                    ?>
                </div>
            <?php endif; ?>

            <label for="target_role">Target</label>
            <select id="target_role" name="target_role" required>
                <?php
                $current = $notice['target_role'] ?? ($notice['target'] ?? 'all');
                $opts = ['all' => 'All (everyone)', 'student' => 'Students only', 'staff' => 'Staff only'];
                foreach ($opts as $val => $label) {
                    $sel = ($val === $current) ? 'selected' : '';
                    echo "<option value=\"" . htmlspecialchars($val) . "\" $sel>" . htmlspecialchars($label) . "</option>";
                }
                ?>
            </select>

            <div class="btn-row">
                <button type="submit" class="btn btn-save">Update Notice</button>
                <a href="admin_notice_dashboard.php" class="btn btn-cancel">Cancel</a>
            </div>
        </form>

        <div class="bottom-links">
            <a href="admin_notice_dashboard.php" class="btn btn-cancel">Go to Admin Dashboard</a>
        </div>
    </div>
</div>

</body>
</html>
