<?php
session_start();
include './db.php';

// ✅ Access control
if (!isset($_SESSION['username']) || $_SESSION['role'] != 'admin') {
    header("Location: ../login.php");
    exit;
}

$msg = "";

// ✅ Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = $_POST['title'];
    $description = $_POST['description'];
    $file_path = '';

    // ✅ Handle file upload
    if (isset($_FILES['file']) && $_FILES['file']['error'] == 0) {
        $upload_dir = "uploads/";
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }

        $filename = time() . "_" . basename($_FILES['file']['name']);
        $target_file = $upload_dir . $filename;

        if (move_uploaded_file($_FILES['file']['tmp_name'], $target_file)) {
            $file_path = $filename; // Save only filename, not full path
        }
    }

    // ✅ Insert notice into database
    $stmt = $conn->prepare("INSERT INTO notices (title, description, file_path) VALUES (?, ?, ?)");
    $stmt->bind_param("sss", $title, $description, $file_path);

    if ($stmt->execute()) {
        $msg = "✅ Notice added successfully!";
    } else {
        $msg = "❌ Error: " . $conn->error;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Add Notice | SSV College Notice Board</title>
<link rel="stylesheet" href="style.css">
</head>
<body>

<header>
    <h1>SSV College Valayanchiragara</h1>
    <p class="sub-title">Add Notice</p>
</header>

<div class="container">
    <h2 class="section-title">Add a New Notice</h2>

    <?php if (!empty($msg)): ?>
        <p style="text-align:center; color:green; font-weight:bold;"><?= htmlspecialchars($msg); ?></p>
    <?php endif; ?>

    <div class="form-card">
        <form action="" method="POST" enctype="multipart/form-data">
            <label for="title">Notice Title</label>
            <input type="text" id="title" name="title" placeholder="Enter notice title" required>

            <label for="description">Description</label>
            <textarea id="description" name="description" rows="5" placeholder="Enter notice details"></textarea>

            <label for="file">Upload Poster/File</label>
            <input type="file" id="file" name="file" accept=".pdf,.jpg,.png,.jpeg,.docx">

            <button type="submit" class="btn">Add Notice</button>

 <a href="dashboard.php" class="btn btn-cancel">Cancel</a>
        </form>
    </div>
</div>

</body>
</html>
