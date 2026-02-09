<?php
// notice/auth_check_notice_user.php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id'])) {
    header("Location: /campus/login.php?module=notice");
    exit;
}
?>
