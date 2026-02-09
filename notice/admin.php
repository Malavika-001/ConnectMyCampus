<?php
session_start();
if (!isset($_SESSION['username']) || $_SESSION['role'] != 'admin') {
  header("Location: ../login.php");
  exit;
}
?>
<h2>Welcome Admin, <?= $_SESSION['username']; ?>!</h2>
<a href="../logout.php">Logout</a>
