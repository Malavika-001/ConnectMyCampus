<?php
// delete_notice2.php
// Deletes a notice (admin only). Removes uploaded file from uploads/ and the DB row.
// Place this file in campus/notice/delete_notice2.php and call as:
//   delete_notice2.php?id=123

// start session only if not started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// require DB (adjust path if your db.php lives elsewhere)
require_once __DIR__ . '/../db.php'; // expects $conn (mysqli)

// auth: ensure admin
if (!isset($_SESSION['username']) || ($_SESSION['role'] ?? '') !== 'admin') {
    // not logged in or not admin -> login for notice module
    header('Location: ../login.php?module=notice');
    exit;
}

// validate id param
$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if ($id <= 0) {
    $_SESSION['notice_msg'] = "Invalid notice id.";
    header('Location: admin_notice_dashboard.php');
    exit;
}

try {
    // Use prepared stmt to fetch the notice row (table name is `notice` as you said)
    $select = $conn->prepare("SELECT id, file_path FROM notice WHERE id = ?");
    if ($select === false) {
        throw new Exception("Prepare failed: " . $conn->error);
    }
    $select->bind_param("i", $id);
    $select->execute();
    $res = $select->get_result();
    $row = $res->fetch_assoc();
    $select->close();

    if (!$row) {
        $_SESSION['notice_msg'] = "Notice not found!";
        header('Location: admin_notice_dashboard.php');
        exit;
    }

    // delete uploaded file if exists and is inside uploads/
    if (!empty($row['file_path'])) {
        // Normalize and ensure we only delete from uploads directory
        $filename = basename($row['file_path']); // prevents directory traversal
        $uploadsDir = __DIR__ . '/uploads/';
        $fullpath = $uploadsDir . $filename;

        if (file_exists($fullpath) && is_file($fullpath)) {
            // attempt to delete; suppress warning but capture result
            if (!@unlink($fullpath)) {
                // couldn't delete file — but we can still remove DB row; notify admin
                $_SESSION['notice_msg'] = "Warning: Could not remove uploaded file, but notice will be deleted.";
            }
        }
    }

    // delete DB row
    $del = $conn->prepare("DELETE FROM notice WHERE id = ?");
    if ($del === false) {
        throw new Exception("Prepare failed: " . $conn->error);
    }
    $del->bind_param("i", $id);
    if ($del->execute()) {
        $_SESSION['notice_msg'] = "Notice deleted successfully.";
    } else {
        $_SESSION['notice_msg'] = "Error deleting notice: " . $conn->error;
    }
    $del->close();

    // redirect back to admin dashboard (adjust filename if different)
    header('Location: admin_notice_dashboard.php');
    exit;

} catch (Exception $e) {
    // log or show friendly error
    $_SESSION['notice_msg'] = "An error occurred: " . htmlspecialchars($e->getMessage());
    header('Location: admin_notice_dashboard.php');
    exit;
}
