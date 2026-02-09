<?php
session_start();
include './db.php';

// ✅ Access control
if (!isset($_SESSION['username']) || $_SESSION['role'] != 'admin') {
    header("Location: login.php");
    exit;
}

// ✅ Get notice ID
if (!isset($_GET['id'])) {
    header("Location: dashboard.php");
    exit;
}

$id = intval($_GET['id']);
$query = $conn->prepare("SELECT * FROM notices WHERE id = ?");
$query->bind_param("i", $id);
$query->execute();
$result = $query->get_result();
$notice = $result->fetch_assoc();

if (!$notice) {
    die("Notice not found!");
}

// ✅ Update notice
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = $_POST['title'];
    $description = $_POST['description'];
    $file_path = $notice['file_path'];

    // Handle file upload (optional)
    if (isset($_FILES['file']) && $_FILES['file']['error'] == 0) {
        $upload_dir = "uploads/";
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }
        $filename = time() . "_" . basename($_FILES['file']['name']);
        $target_file = $upload_dir . $filename;
        if (move_uploaded_file($_FILES['file']['tmp_name'], $target_file)) {
            $file_path = $target_file;
        }
    }

    $stmt = $conn->prepare("UPDATE notices SET title=?, description=?, file_path=? WHERE id=?");
    $stmt->bind_param("sssi", $title, $description, $file_path, $id);

    if ($stmt->execute()) {
        $msg = "Notice updated successfully!";
        // Refresh notice data
        $query->execute();
        $result = $query->get_result();
        $notice = $result->fetch_assoc();
    } else {
        $msg = "Error updating notice: " . $conn->error;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Edit Notice | SSV College Notice Board</title>
<style>
    body {
        font-family: Arial, sans-serif;
        background: #f5f9ff;
        margin: 0;
        padding: 0;
    }
    header {
        background-color: #004aad;
        color: white;
        text-align: center;
        padding: 20px 0;
        font-size: 22px;
        font-weight: bold;
    }
    .sub-title {
        font-size: 16px;
        font-weight: normal;
        display: block;
        margin-top: 5px;
    }
    .container {
        max-width: 600px;
        margin: 30px auto;
        background: white;
        padding: 25px;
        border-radius: 10px;
        box-shadow: 0 0 10px rgba(0,0,0,0.1);
    }
    .section-title {
        text-align: center;
        color: #004aad;
        margin-bottom: 20px;
    }
    .form-card label {
        display: block;
        margin-top: 10px;
        color: #004aad;
        font-weight: bold;
    }
    .form-card input[type="text"],
    .form-card textarea {
        width: 100%;
        padding: 10px;
        border: 1px solid #ccc;
        border-radius: 5px;
        margin-top: 5px;
    }
    .btn {
        background-color: #004aad;
        color: white;
        padding: 10px 20px;
        border: none;
        border-radius: 5px;
        margin-top: 15px;
        cursor: pointer;
        width: 48%;
        font-size: 16px;
    }
    .btn:hover {
        background-color: #003b8a;
    }
    .btn-cancel {
        background-color: #ccc;
        color: black;
    }
    .btn-cancel:hover {
        background-color: #aaa;
    }
    .btn-row {
        display: flex;
        justify-content: space-between;
        margin-top: 20px;
    }
    p {
        font-size: 15px;
        text-align: center;
    }
</style>
</head>
<body>

<header>
    SSV College Valayanchiragara<br>
    <span class="sub-title">Edit Notice</span>
</header>

<div class="container">
    <h2 class="section-title">Edit Notice Details</h2>

    <?php if (!empty($msg)): ?>
        <p style="color:green; font-weight:bold;"><?= htmlspecialchars($msg); ?></p>
    <?php endif; ?>

    <div class="form-card">
        <form action="" method="POST" enctype="multipart/form-data">
            <label for="title">Notice Title</label>
            <input type="text" id="title" name="title" value="<?= htmlspecialchars($notice['title']); ?>" required>

            <label for="description">Description</label>
            <textarea id="description" name="description" rows="5"><?= htmlspecialchars($notice['description']); ?></textarea>

            <label for="file">Replace File (Optional)</label>
            <input type="file" id="file" name="file" accept=".pdf,.jpg,.png,.jpeg,.docx">

            <div class="btn-row">
                <button type="submit" class="btn">Update Notice</button>
                <a href="dashboard.php" class="btn btn-cancel">Cancel</a>
            </div>
        </form>
    </div>
</div>

</body>
</html>
